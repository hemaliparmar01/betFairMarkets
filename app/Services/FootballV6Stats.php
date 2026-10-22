<?php

namespace App\Services;

use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FootballV6Stats
{
    public function queueDueMatches(): void
    {
        $queued = [];
        $fixtures = null;
        $deferred = [];
        foreach (getInRedis('Football:matches') ?? [] as $match) {
            $matchId = (string) ($match['sportsApiPro_match_id'] ?? '');
            $delay = max(abs(crc32($matchId)) % 50, (int) ($match['v6_stats']['next_poll_at'] ?? 0) - time());
            if (! ctype_digit($matchId) || (int) $matchId <= 0 || isset($queued[$matchId]) || ! $this->isLive($match)
                || $delay > 59) {
                continue;
            }
            $queued[$matchId] = true;
            $fixtures ??= $this->fixtures();
            $v6Id = $this->resolveFixtureId($match, $fixtures);
            if ($v6Id === null) {
                $deferred[$matchId] = ['v6_id' => null, 'retry_after' => 300];

                continue;
            }
            $unavailableUntil = (int) Cache::store('redis')->get('Football:v6:unavailable:'.$v6Id, 0);
            if ($unavailableUntil > time()) {
                $deferred[$matchId] = ['v6_id' => $v6Id, 'retry_after' => max(60, $unavailableUntil - time())];

                continue;
            }
            $key = 'Football:v6:stats:'.$matchId.':pending';
            $lock = Cache::store('redis')->lock($key, 3600);
            if (! $lock->get()) {
                continue;
            }
            $owner = $lock->owner();
            try {
                dispatch(static function () use ($matchId, $key, $owner): void {
                    $lock = Cache::store('redis')->restoreLock($key, $owner);
                    try {
                        if ($lock->isOwnedByCurrentProcess()) {
                            app(self::class)->pollMatch($matchId);
                        }
                    } finally {
                        $lock->release();
                    }
                })->name('Football V6 live statistics '.$matchId)->onQueue('sports-statistics')->delay($delay)
                    ->catch(static function (Throwable $e) use ($key, $owner): void {
                        Cache::store('redis')->restoreLock($key, $owner)->release();
                        Log::warning('Football V6 statistics job failed', ['exception' => $e::class]);
                    });
            } catch (Throwable $e) {
                $lock->release();
                throw $e;
            }
        }
        if ($deferred !== []) {
            $this->savePollingStates($deferred);
        }
    }

    /** @param array<string, mixed> $match */
    public function isLive(array $match): bool
    {
        if (($match['result_status'] ?? '') !== 'Live') {
            return false;
        }
        $clock = $match['timeline']['clock'] ?? [];
        if (($clock['finished'] ?? false) || ($clock['status_type'] ?? '') === 'finished') {
            return false;
        }
        if ((int) ($clock['status_code'] ?? 0) > 0) {
            return in_array((int) $clock['status_code'], [6, 7, 31], true);
        }

        return in_array(strtolower(trim((string) ($match['minutes_status'] ?? ''))), ['1h', 'ht', '2h', '1st half', 'half time', 'halftime', '2nd half'], true);
    }

    public function pollMatch(string $matchId): void
    {
        $match = array_find(getInRedis('Football:matches') ?? [], fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId && $this->isLive($match));
        if ($match === null || ($match['v6_stats']['next_poll_at'] ?? 0) > time()) {
            return;
        }

        $diagnostics = null;
        $v6Id = null;
        try {
            $v6Id = $this->resolveFixtureId($match, $this->fixtures());
            if (! is_string($v6Id) || preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $v6Id) !== 1) {
                $this->saveMapping($matchId, null, 300);

                return;
            }
            $unavailableUntil = (int) Cache::store('redis')->get('Football:v6:unavailable:'.$v6Id, 0);
            if ($unavailableUntil > time()) {
                $this->saveMapping($matchId, $v6Id, max(60, $unavailableUntil - time()));

                return;
            }
            $version = $this->dictionaryVersion();
            if (! Cache::store('redis')->add('Football:v6:stats:'.$matchId.':request', true, 60)) {
                return;
            }
            $requestedAt = microtime(true);
            $response = ApiCall('https://api.sportsapipro.com/v6/football/match/'.$v6Id.'/team-stats', ['x-api-key' => GetSportApiProKey()], [
                'connect_timeout' => 3, 'timeout' => 10, 'low_priority' => true,
            ], $diagnostics);
            $payload = is_array($response) ? ($response['data'] ?? $response) : [];
            if (! is_array($response) || ($response['success'] ?? true) === false || ! is_array($payload['matchStats'][0]['stats'] ?? null)
                || (isset($payload['matchId']) && (string) $payload['matchId'] !== $v6Id)
                || (isset($diagnostics['http_status']) && ($diagnostics['http_status'] < 200 || $diagnostics['http_status'] >= 300))) {
                $notFound = in_array($diagnostics['http_status'] ?? null, [404, 410], true);
                if ($notFound) {
                    Cache::store('redis')->put('Football:v6:unavailable:'.$v6Id, time() + 3600, 3600);
                }
                $this->saveMapping($matchId, $notFound ? null : $v6Id, $notFound ? 3600 : 300, $notFound);
                if (($diagnostics['skipped'] ?? '') !== 'quota_reserve') {
                    Log::warning('Football V6 team statistics unavailable; cached statistics retained', ['match_id' => $matchId, 'v6_match_id' => $v6Id, 'api' => $diagnostics]);
                }

                return;
            }
            app(FootballSyncController::class)->mergeV6TeamStats($matchId, $v6Id, $payload, $requestedAt, $version);
        } catch (Throwable $e) {
            $this->saveMapping($matchId, $v6Id, 300);
            Log::warning('Football V6 live statistics failed; cached statistics retained', ['match_id' => $matchId, 'api' => $diagnostics, 'exception' => $e::class, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  list<array<string, mixed>>  $fixtures
     */
    private function resolveFixtureId(array $match, array $fixtures): ?string
    {
        $cachedId = $match['sportsApiPro_v6_match_id'] ?? null;
        if (is_string($cachedId) && array_any($fixtures, static fn (array $fixture): bool => ($fixture['id'] ?? null) === $cachedId)) {
            return $cachedId;
        }

        return $this->mapFixture($match, $fixtures);
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  list<array<string, mixed>>  $fixtures
     */
    public function mapFixture(array $match, array $fixtures): ?string
    {
        $home = $this->normalizeTeam((string) ($match['team1'] ?? ''));
        $away = $this->normalizeTeam((string) ($match['team2'] ?? ''));
        if ($home === '' || $away === '' || (int) ($match['timestamp'] ?? 0) <= 0) {
            return null;
        }
        $candidates = [];
        foreach ($fixtures as $fixture) {
            if (! is_string($fixture['id'] ?? null) || abs((int) ($fixture['matchTime'] ?? 0) - (int) $match['timestamp']) > 300) {
                continue;
            }
            $fixtureHome = $this->normalizeTeam((string) ($fixture['homeTeam']['name'] ?? ''));
            $fixtureAway = $this->normalizeTeam((string) ($fixture['awayTeam']['name'] ?? ''));
            if ($this->sameTeam($home, $fixtureHome) && $this->sameTeam($away, $fixtureAway)) {
                $candidates[$fixture['id']] = $fixture;
            }
        }
        if (count($candidates) > 1) {
            $competition = $this->normalizeTeam((string) ($match['tournament'] ?? ''));
            $candidates = array_filter($candidates, fn (array $fixture): bool => $competition !== ''
                && $this->normalizeTeam((string) ($fixture['competition']['name'] ?? $fixture['league']['name'] ?? '')) === $competition);
        }

        return count($candidates) === 1 ? (string) array_key_first($candidates) : null;
    }

    /** @return list<array<string, mixed>> */
    private function fixtures(): array
    {
        $fixtures = Cache::store('redis')->lock('Football:v6:catalogue-lock', 20)->block(2, static function (): array {
            $cached = Cache::store('redis')->get('Football:v6:catalogue');
            if (is_array($cached)) {
                return $cached;
            }
            $response = ApiCall('https://api.sportsapipro.com/v6/football/live', ['x-api-key' => GetSportApiProKey()], [
                'connect_timeout' => 3, 'timeout' => 10, 'low_priority' => true,
            ], $diagnostics);
            $payload = is_array($response) ? ($response['data'] ?? $response) : [];
            $fixtures = ($response['success'] ?? true) !== false && ($diagnostics['http_status'] ?? 200) === 200 && is_array($payload['matches'] ?? null) ? $payload['matches'] : [];
            Cache::store('redis')->put('Football:v6:catalogue', $fixtures, 60);
            if ($fixtures === [] && ($diagnostics['skipped'] ?? '') !== 'quota_reserve') {
                Log::warning('Football V6 live catalogue unavailable', ['api' => $diagnostics]);
            }

            return $fixtures;
        });

        return array_values(array_filter($fixtures, static fn (array $fixture): bool => (! isset($fixture['matchStatus']) || (int) $fixture['matchStatus'] === 2)
            && (! isset($fixture['statusId']) || in_array((int) $fixture['statusId'], [2, 3, 4], true))));
    }

    private function dictionaryVersion(): ?string
    {
        return Cache::store('redis')->lock('Football:v6:dictionary-lock', 10)->block(2, static function (): ?string {
            $cached = Cache::store('redis')->get('Football:v6:dictionary', []);
            if (($cached['check_after'] ?? 0) > time()) {
                return $cached['version'] ?? null;
            }
            $response = ApiCall('https://v6.football.sportsapipro.com/reference/football/stat-ids', [], ['connect_timeout' => 2, 'timeout' => 5]);
            if (is_array($response) && is_string($response['version'] ?? null) && isset($response['stats'][123], $response['stats'][124])) {
                $cached = ['version' => $response['version'], 'stats' => $response['stats']];
            }
            $cached['check_after'] = time() + 3600;
            Cache::store('redis')->put('Football:v6:dictionary', $cached, 86400);

            return $cached['version'] ?? null;
        });
    }

    private function saveMapping(string $matchId, ?string $v6Id, int $retryAfter = 60, bool $clearMapping = false): bool
    {
        return $this->savePollingStates([$matchId => ['v6_id' => $v6Id, 'retry_after' => $retryAfter, 'clear_mapping' => $clearMapping]]);
    }

    /** @param array<int|string, array{v6_id: ?string, retry_after: int, clear_mapping?: bool}> $states */
    private function savePollingStates(array $states): bool
    {
        return withRedisLock('Football:matches', function () use ($states): bool {
            $matches = getInRedis('Football:matches') ?? [];
            $changed = false;
            foreach ($matches as &$match) {
                $state = $states[(string) ($match['sportsApiPro_match_id'] ?? '')] ?? null;
                if ($state === null || ! $this->isLive($match)) {
                    continue;
                }
                if ($state['v6_id'] !== null) {
                    $match['sportsApiPro_v6_match_id'] = $state['v6_id'];
                } elseif ($state['clear_mapping'] ?? false) {
                    unset($match['sportsApiPro_v6_match_id']);
                }
                $match['v6_stats']['last_attempt_at'] = time();
                $match['v6_stats']['next_poll_at'] = time() + $state['retry_after'];
                $changed = true;
            }
            unset($match);
            if ($changed) {
                storeInRedis('Football:matches', $matches, 30);
            }

            return $changed;
        });
    }

    private function normalizeTeam(string $name): string
    {
        $name = strtolower(Str::ascii($name));
        $name = preg_replace('/\bunder[\s-]*(\d{1,2})\b/', 'u$1', $name);
        $name = preg_replace('/\bu[\s-]+(\d{1,2})\b/', 'u$1', $name);
        $name = preg_replace('/\b(women|womens|ladies|feminine|femenino|feminino|w)\b/', 'women', $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);
        $name = preg_replace('/\b(fc|cf|sc|afc|club|football|futbol|futebol)\b/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function sameTeam(string $first, string $second): bool
    {
        if ($first === '' || $second === '') {
            return false;
        }
        preg_match_all('/\b(u\d{1,2}|women|reserves?|ii|b)\b/', $first, $firstQualifiers);
        preg_match_all('/\b(u\d{1,2}|women|reserves?|ii|b)\b/', $second, $secondQualifiers);
        if ($firstQualifiers[0] !== $secondQualifiers[0]) {
            return false;
        }
        similar_text($first, $second, $similarity);

        return $similarity >= 80;
    }
}
