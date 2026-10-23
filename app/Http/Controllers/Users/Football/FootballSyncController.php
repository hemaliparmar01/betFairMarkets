<?php

namespace App\Http\Controllers\Users\Football;

use App\Events\FootballSportsScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\FootballMarket;
use App\Models\FootballMatch;
use App\Services\FootballV6Stats;
use App\Services\LiveMatchExpiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FootballSyncController extends Controller
{
    public string $getBetferKeys;

    public string $getsportsAPIProKeys;

    public string $sports;

    public function __construct()
    {
        $this->getBetferKeys = GetBetfairKey();
        $this->getsportsAPIProKeys = GetSportApiProKey();
        $this->sports = 'Football';
    }

    public function mergeTimelineFrame(string $channel, array $payload, string $frameType = 'update', ?float $requestedAt = null): bool
    {
        if (! preg_match('/^match:(\d+)(?::(incidents|stats))?$/', $channel, $channelMatch)) {
            return false;
        }

        return withRedisLock('Football:matches', function () use ($channel, $channelMatch, $payload, $frameType, $requestedAt): bool {
            $matches = getInRedis('Football:matches') ?? [];
            $key = array_find_key($matches, static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $channelMatch[1]);
            if ($key === null || ! $this->applyTimelineFrame($matches[$key], $channel, $payload, $frameType, $requestedAt)) {
                return false;
            }
            storeInRedis('Football:matches', $matches, 10);

            return true;
        });
    }

    /** @param array<string, mixed> $match */
    public function applyTimelineFrame(array &$match, string $channel, array $payload, string $frameType = 'update', ?float $requestedAt = null): bool
    {
        if (! preg_match('/^match:(\d+)(?::(incidents|stats))?$/', $channel, $channelMatch)
            || (string) ($match['sportsApiPro_match_id'] ?? '') !== $channelMatch[1]) {
            return false;
        }
        $isIncidents = ($channelMatch[2] ?? '') === 'incidents';
        $receivedAt = $requestedAt ?? microtime(true);
        $timeline = $match['timeline'] ?? [];
        $timeline = is_array($timeline) ? $timeline : [];
        if (($match['result_status'] ?? '') === 'Finished' && ($timeline['finalized'] ?? false) && $requestedAt === null) {
            return false;
        }
        $before = $timeline;
        $preserveNewerEvents = ($timeline['incidents_updated_at'] ?? 0) > $receivedAt;
        if ($isIncidents && (! $preserveNewerEvents || ($frameType === 'snapshot' && ! ($timeline['initialized'] ?? false)))) {
            $incidents = $payload['incidents'] ?? $payload['events'] ?? (array_is_list($payload) ? $payload : (isset($payload['incidentType']) || isset($payload['isDeleted'], $payload['id']) ? [$payload] : null));
            if (! is_array($incidents)) {
                return false;
            }
            $events = $frameType === 'snapshot' && ! $preserveNewerEvents ? [] : ($timeline['events'] ?? []);
            foreach ($incidents as $incident) {
                if (! is_array($incident)) {
                    continue;
                }
                if (($incident['isDeleted'] ?? false) === true && isset($incident['id'])) {
                    unset($events[(string) $incident['id']]);
                    $timeline['removed'][(string) $incident['id']] = $receivedAt;

                    continue;
                }
                $kind = $incident['incidentType'] ?? null;
                $class = $incident['incClass'] ?? $incident['incidentClass'] ?? '';
                $home = filter_var($incident['isHome'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (! array_key_exists('isHome', $incident) || $home === null || ! is_numeric($incident['time'] ?? null)
                    || ! in_array($kind, ['goal', 'card'], true)
                    || ($kind === 'card' && ! in_array($class, ['yellow', 'red', 'yellowRed'], true))) {
                    continue;
                }
                $incidentMinute = (int) $incident['time'];
                $incidentSeconds = is_numeric($incident['timeSeconds'] ?? null) ? (int) $incident['timeSeconds'] : null;
                $timeKnown = $incidentMinute >= 0 || $incidentSeconds > 0;
                $minute = $incidentMinute >= 0 ? $incidentMinute : ($incidentSeconds > 0 ? (int) floor($incidentSeconds / 60) : 0);
                $added = max(0, (int) ($incident['addedTime'] ?? 0));
                $player = trim((string) ($incident['player']['name'] ?? ''));
                if ($player === '') {
                    $player = trim(($incident['player']['firstName'] ?? '').' '.($incident['player']['lastName'] ?? ''));
                }
                $id = (string) ($incident['id'] ?? sha1(json_encode([$kind, $class, $minute, $added, $home, $incident['player']['id'] ?? $player])));
                if (($preserveNewerEvents && isset($events[$id])) || ($timeline['removed'][$id] ?? 0) > $receivedAt) {
                    continue;
                }
                unset($timeline['removed'][$id]);
                $events[$id] = [
                    'id' => $id,
                    'kind' => $kind === 'goal' ? 'goal' : ($class === 'yellow' ? 'yellow' : 'red'),
                    'incident_class' => $class,
                    'minute' => $minute,
                    'time_known' => $timeKnown,
                    'added_time' => $added,
                    'seconds' => $incidentSeconds !== null && $incidentSeconds >= 0 ? $incidentSeconds : ($minute + $added) * 60,
                    'is_home' => $home,
                    'player' => $player,
                    'player_surname' => ! empty($incident['player']['lastName']) ? (string) $incident['player']['lastName'] : null,
                    'home_score' => $incident['homeScore'] ?? null,
                    'away_score' => $incident['awayScore'] ?? null,
                ];
            }
            $timeline['events'] = $events;
            $timeline['initialized'] = ($timeline['initialized'] ?? false) || $frameType === 'snapshot';
            if ($frameType === 'snapshot') {
                $timeline['finalized'] = ($timeline['finalized'] ?? false)
                    || ($requestedAt !== null && (($timeline['clock']['finished'] ?? false) || ($match['result_status'] ?? '') === 'Finished'))
                    || array_any($incidents, static fn (mixed $incident): bool => is_array($incident)
                        && ($incident['incidentType'] ?? null) === 'period'
                        && in_array(strtoupper((string) ($incident['text'] ?? '')), ['FT', 'AET', 'AP'], true)
                        && ($incident['isLive'] ?? null) === false);
            }
            $timeline['incidents_updated_at'] = max($receivedAt, $timeline['incidents_updated_at'] ?? 0);
        } elseif (! $isIncidents && ($timeline['clock_updated_at'] ?? 0) <= $receivedAt) {
            $read = static fn (string $path): mixed => array_key_exists($path, $payload) ? $payload[$path] : data_get($payload, $path);
            $clock = array_filter([
                'status_code' => $read('status.code'),
                'status_type' => $read('status.type'),
                'status_description' => $read('status.description'),
                'period_start' => $read('time.currentPeriodStartTimestamp'),
                'initial' => $read('time.initial'),
                'max' => $read('time.max'),
                'last_period_timestamp' => $read('time.lastPeriodTimestamp'),
            ], static fn (mixed $value): bool => $value !== null);
            foreach (['period_start', 'last_period_timestamp'] as $field) {
                if (isset($clock[$field]) && (! is_numeric($clock[$field]) || (int) $clock[$field] <= 0)) {
                    unset($clock[$field]);
                }
            }
            foreach (['initial', 'max'] as $field) {
                if (isset($clock[$field]) && (! is_numeric($clock[$field]) || (int) $clock[$field] < ($field === 'initial' ? 0 : 1))) {
                    unset($clock[$field]);
                }
            }
            if (in_array((int) ($clock['status_code'] ?? 0), [6, 7], true)) {
                $clock['initial'] ??= (int) $clock['status_code'] === 7 ? 2700 : 0;
                $clock['max'] ??= (int) $clock['status_code'] === 7 ? 5400 : 2700;
            }
            if ($clock === []) {
                return false;
            }
            $clock['finished'] = ($timeline['clock']['finished'] ?? false)
                || in_array(strtoupper((string) ($clock['status_code'] ?? '')), ['FT', '100'], true)
                || ($clock['status_type'] ?? null) === 'finished';
            $timeline['clock'] = array_replace($timeline['clock'] ?? [], $clock);
            $timeline['clock_updated_at'] = $receivedAt;
        }
        if ($timeline === $before) {
            return false;
        }
        $match['timeline'] = $timeline;
        if ($isIncidents) {
            $this->syncTimelineCards($match);
        }

        return true;
    }

    /** @param array<string, mixed> $match */
    public function syncTimelineCards(array &$match): void
    {
        $timeline = $match['timeline'] ?? [];
        foreach (['yellow_cards', 'red_cards'] as $stat) {
            if (! isset($match['card_stats'][$stat]) && in_array($match['live_stats_sources'][$stat] ?? '', ['v2', 'v6'], true)
                && is_array($match['live_stats'][$stat] ?? null)) {
                $match['card_stats'][$stat] = $match['live_stats'][$stat];
            }
        }
        if (! is_array($timeline['events'] ?? null) && empty($match['card_stats'])) {
            return;
        }
        $counts = [
            'home' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
            'away' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
        ];
        foreach ($timeline['events'] ?? [] as $incident) {
            if (! in_array($incident['kind'] ?? '', ['yellow', 'red'], true)) {
                continue;
            }
            $side = $incident['is_home'] ? 'home' : 'away';
            if ($incident['kind'] === 'yellow') {
                $counts[$side]['yellow']++;
            } else {
                $counts[$side][($incident['incident_class'] ?? '') === 'yellowRed' ? 'second_yellow' : 'direct_red']++;
                $counts[$side]['total_red']++;
            }
        }
        $unplacedCards = ['home' => ['yellow' => 0, 'red' => 0], 'away' => ['yellow' => 0, 'red' => 0]];
        foreach (['home', 'away'] as $side) {
            $yellow = max($counts[$side]['yellow'], (int) ($match['card_stats']['yellow_cards'][$side] ?? 0));
            $red = max($counts[$side]['total_red'], (int) ($match['card_stats']['red_cards'][$side] ?? 0));
            $unplacedCards[$side]['yellow'] = $yellow - $counts[$side]['yellow'];
            $unplacedCards[$side]['red'] = $red - $counts[$side]['total_red'];
            $counts[$side]['yellow'] = $yellow;
            $counts[$side]['total_red'] = $red;
        }
        $match['timeline']['unplaced_card_counts'] = $unplacedCards;
        $match['card_counts'] = $counts;
        $match['cards'] = [
            'home' => $counts['home']['total_red'] > 0 ? 'red' : ($counts['home']['yellow'] > 0 ? 'yellow' : null),
            'away' => $counts['away']['total_red'] > 0 ? 'red' : ($counts['away']['yellow'] > 0 ? 'yellow' : null),
        ];
        if (is_array($timeline['events'] ?? null)) {
            $match['incidents_initialized'] = true;
        }
        $match['cards_updated_at'] = $timeline['incidents_updated_at'] ?? microtime(true);
        $match['card_counts_updated_at'] = $match['cards_updated_at'];
        $match['live_stats']['yellow_cards'] = ['home' => $counts['home']['yellow'], 'away' => $counts['away']['yellow']];
        $match['live_stats']['red_cards'] = ['home' => $counts['home']['total_red'], 'away' => $counts['away']['total_red']];
        $match['live_stats_sources']['yellow_cards'] = 'reconciled';
        $match['live_stats_sources']['red_cards'] = 'reconciled';
    }

    /** @return array<string, mixed> */
    public function getMatchTimeline(int|string $matchId): array
    {
        $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);

        return $match['timeline'] ?? [];
    }

    public function migrateTimelineCache(): int
    {
        return withRedisLock('Football:matches', function (): int {
            $matches = getInRedis('Football:matches') ?? [];
            $keys = [];
            foreach ($matches as $key => $match) {
                if (! isset($match['timeline']['events']) && ! empty($match['sportsApiPro_match_id'])) {
                    $keys[$key] = 'Football:timeline:'.$match['sportsApiPro_match_id'];
                }
            }
            if ($keys === []) {
                return 0;
            }
            $legacyTimelines = Cache::store('redis')->many(array_values($keys));
            $migratedKeys = [];
            $changed = false;
            foreach ($keys as $key => $legacyKey) {
                $before = $matches[$key];
                $current = $matches[$key]['timeline'] ?? [];
                $legacy = $legacyTimelines[$legacyKey] ?? null;
                if (is_array($legacy) && $legacy !== []) {
                    $matches[$key]['timeline'] = array_replace($current, $legacy);
                    if (($current['clock_updated_at'] ?? 0) > ($legacy['clock_updated_at'] ?? 0)) {
                        $matches[$key]['timeline']['clock'] = $current['clock'];
                        $matches[$key]['timeline']['clock_updated_at'] = $current['clock_updated_at'];
                    }
                    $migratedKeys[] = $legacyKey;
                } else {
                    $matches[$key]['timeline'] = $current;
                }
                $this->syncTimelineCards($matches[$key]);
                $changed = $matches[$key] !== $before || $changed;
            }
            if ($changed) {
                storeInRedis('Football:matches', $matches, 10);
            }
            Cache::store('redis')->deleteMultiple($migratedKeys);

            return count($migratedKeys);
        });
    }

    /**
     * @param  array<string, mixed>  $timeline
     * @param  array{home: int, away: int}|null  $score
     */
    public function timelineGoalsMatchScore(array $timeline, ?array $score): bool
    {
        if ($score === null) {
            return true;
        }
        $goals = ['home' => 0, 'away' => 0];
        foreach ($timeline['events'] ?? [] as $event) {
            if (($event['kind'] ?? '') === 'goal') {
                $goals[$event['is_home'] ? 'home' : 'away']++;
            }
        }

        return $goals['home'] === $score['home'] && $goals['away'] === $score['away'];
    }

    /**
     * @param  array{home: int, away: int}|null  $expectedScore
     * @param  array<string, mixed>|null  $timeline
     */
    public function queueTimelineBackfill(int|string $matchId, bool $finished = false, ?array $expectedScore = null, ?array $timeline = null): void
    {
        if (! ctype_digit((string) $matchId) || (int) $matchId <= 0) {
            return;
        }
        $cacheKey = 'Football:timeline-backfill:'.$matchId;
        $pending = null;
        try {
            $timeline ??= $this->getMatchTimeline($matchId);
            if (($timeline['initialized'] ?? false) && (! $finished || ($timeline['finalized'] ?? false)) && $this->timelineGoalsMatchScore($timeline, $expectedScore)) {
                return;
            }
            if (Cache::store('redis')->has($cacheKey.':retry')) {
                return;
            }
            $pending = Cache::store('redis')->lock($cacheKey.':pending', 3600);
            if (! $pending->get()) {
                return;
            }
            $owner = $pending->owner();
            $repairGoals = ($timeline['initialized'] ?? false) && ! $this->timelineGoalsMatchScore($timeline, $expectedScore);
            dispatch(static function () use ($matchId, $cacheKey, $owner, $finished, $expectedScore, $repairGoals): void {
                $lock = Cache::store('redis')->restoreLock($cacheKey.':pending', $owner);
                $apiDiagnostics = null;
                try {
                    $controller = app(self::class);
                    if ($expectedScore !== null) {
                        $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);
                        if (isset($match['home_score'], $match['away_score'])) {
                            $expectedScore = ['home' => (int) $match['home_score'], 'away' => (int) $match['away_score']];
                        }
                    }
                    $timeline = $controller->getMatchTimeline($matchId);
                    if (! $lock->isOwnedByCurrentProcess() || (($timeline['initialized'] ?? false) && (! $finished || ($timeline['finalized'] ?? false)) && $controller->timelineGoalsMatchScore($timeline, $expectedScore))) {
                        return;
                    }
                    foreach ($repairGoals ? [':incidents'] : ($finished ? ['', ':incidents'] : ['', ':incidents', ':odds']) as $suffix) {
                        $current = $controller->getMatchTimeline($matchId);
                        if (($suffix === '' && ! $finished && ! empty($current['clock'])) || ($suffix === ':incidents' && ($current['initialized'] ?? false) && (! $finished || ($current['finalized'] ?? false)) && $controller->timelineGoalsMatchScore($current, $expectedScore))) {
                            continue;
                        }
                        if ($suffix === ':odds') {
                            $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);
                            if (($match['result_status'] ?? '') !== 'Live' || Cache::store('redis')->has('Football:bet365:live:'.$matchId)) {
                                continue;
                            }
                        }
                        $requestedAt = microtime(true);
                        $response = ApiCall('https://api.sportsapipro.com/v2/football/match/'.$matchId.($suffix === ':odds' ? '/odds' : ($suffix === '' ? '' : '/incidents')),
                            ['x-api-key' => GetSportApiProKey()], ['connect_timeout' => 3, 'timeout' => 15], $apiDiagnostics);
                        if (! is_array($response) || ($response['success'] ?? true) === false) {
                            Log::warning('Football timeline backfill response unavailable', ['match_id' => $matchId, 'api' => $apiDiagnostics]);

                            continue;
                        }
                        $payload = $response['data'] ?? $response;
                        $payload = $suffix === '' ? ($payload['event'] ?? $payload) : $payload;
                        if (is_array($payload)) {
                            if ($suffix === ':odds') {
                                $controller->mergeBet365LiveOdds($matchId, $payload, $requestedAt);

                                continue;
                            }
                            $controller->mergeTimelineFrame('match:'.$matchId.$suffix, $payload, 'snapshot', $requestedAt);
                        }
                    }
                    $timeline = $controller->getMatchTimeline($matchId);
                    if (! ($timeline['initialized'] ?? false) || ($finished && ! ($timeline['finalized'] ?? false)) || ! $controller->timelineGoalsMatchScore($timeline, $expectedScore)) {
                        Cache::store('redis')->put($cacheKey.':retry', true, $repairGoals ? 60 : 300);
                    }
                    Event::dispatch(new FootballSportsScoreUpdated);
                } catch (Throwable $e) {
                    Cache::store('redis')->put($cacheKey.':retry', true, $repairGoals ? 60 : 300);
                    Log::warning('Football timeline backfill failed', [
                        'match_id' => $matchId,
                        'api' => $apiDiagnostics,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                } finally {
                    $lock->release();
                }
            })->name('Football timeline backfill '.$matchId)->onQueue('default')->delay(now()->addSeconds($repairGoals ? 2 : 0))->catch(static function (Throwable $e) use ($cacheKey, $owner, $repairGoals): void {
                Cache::store('redis')->restoreLock($cacheKey.':pending', $owner)->release();
                Cache::store('redis')->put($cacheKey.':retry', true, $repairGoals ? 60 : 300);
            });
        } catch (Throwable $e) {
            $pending?->release();
            Log::warning('Football timeline could not be queued', [
                'match_id' => $matchId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function syncBetFairLiveData()
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=live&sports=soccer';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15], $apiDiagnostics);
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['liveMap'] ?? null)) {
            Log::warning('Football Betfair live snapshot unavailable; existing matches retained', $apiDiagnostics ?? []);

            return getInRedis($cacheKey) ?? [];
        }

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, true, 10),
            30
        );
        $this->storeMatchesInDatabase($matches);

        return $matches;
    }

    public function syncBetFairPrematchData()
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=prematch&sports=soccer';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15], $apiDiagnostics);
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['liveMap'] ?? null)) {
            Log::warning('Football Betfair prematch snapshot unavailable; existing matches retained', $apiDiagnostics ?? []);

            return getInRedis($cacheKey) ?? [];
        }

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, false, 30),
            30
        );
        $this->storeMatchesInDatabase($matches);

        return $matches;
    }

    public function syncSportsApiProLiveData()
    {
        $this->migrateTimelineCache();
        $url = 'https://api.sportsapipro.com/v2/football/live/all';
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15], $apiDiagnostics);
        $liveData = is_array($liveData['data'] ?? null) ? $liveData['data'] : $liveData;
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['events'] ?? null)) {
            Log::warning('Football SportsAPI Pro live snapshot unavailable; existing matches retained', $apiDiagnostics ?? []);

            return getInRedis($cacheKey) ?? [];
        }

        $matches = $this->readsportApiData($liveData, $cacheKey, true, 10);
        $this->storeMatchesInDatabase($matches);
        $this->queueBet365Favorites();
        $this->mergePinnacleDrops(getInRedis('Pinnacle:drops')['signals'] ?? []);

        return $matches;
    }

    public function syncSportsApiProPrematchData()
    {
        $cacheKey = $this->sports.':matches';
        $date = Carbon::now('UTC')->format('Y-m-d');
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $scheduleData = ApiCall("https://api.sportsapipro.com/v2/football/schedule/{$date}", $key);
        if (! is_array($scheduleData['events'] ?? null)) {
            return;
        }

        $matches = $this->readsportApiData(['events' => $scheduleData['events']], $cacheKey, false, 30);
        $this->storeMatchesInDatabase($matches);
        $this->queueBet365Favorites();
        $this->mergePinnacleDrops(getInRedis('Pinnacle:drops')['signals'] ?? []);

        return $matches;
    }

    public function queueBet365Favorites(?string $date = null, int $page = 1): void
    {
        if ($date === null) {
            $dates = [];
            foreach (getInRedis('Football:matches') ?? [] as $match) {
                if (empty($match['sportsApiPro_match_id']) || ! in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
                    || (int) ($match['timestamp'] ?? 0) < time() - 86400 || (int) ($match['timestamp'] ?? 0) > time() + 86400) {
                    continue;
                }
                if (($match['result_status'] ?? '') === 'Live' && isset($match['bet365_prematch']['match_id'])) {
                    $this->queueBet365MatchOdds($match, (int) $match['bet365_prematch']['match_id']);

                    continue;
                }
                $dates[gmdate('Y-m-d', (int) $match['timestamp'])] = true;
            }
            foreach (array_keys($dates) as $matchDate) {
                $this->queueBet365Favorites($matchDate);
            }

            return;
        }
        $cacheKey = 'Football:bet365:catalogue:'.$date;
        $pending = null;
        try {
            if ($page === 1 && Cache::store('redis')->has($cacheKey.':cooldown')) {
                return;
            }
            $pending = Cache::store('redis')->lock($cacheKey.':pending:'.$page, 3600);
            if (! $pending->get()) {
                return;
            }
            $owner = $pending->owner();
            dispatch(static function () use ($date, $page, $cacheKey, $owner): void {
                $lock = Cache::store('redis')->restoreLock($cacheKey.':pending:'.$page, $owner);
                $apiDiagnostics = null;
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $response = ApiCall('https://api.sportsapipro.com/v4/football/matches', ['x-api-key' => GetSportApiProKey()], [
                        'query' => ['sport' => 'football', 'status' => 'all', 'perPage' => 150, 'page' => $page, 'startDate' => $date.'T00:00:00Z', 'endDate' => $date.'T23:59:59Z'],
                        'connect_timeout' => 3, 'timeout' => 15,
                    ], $apiDiagnostics);
                    $payload = $response['data'] ?? $response;
                    if (! is_array($payload['matches'] ?? null)) {
                        throw new \RuntimeException('SportsAPI Pro V4 Football catalogue unavailable.');
                    }
                    $controller = app(self::class);
                    $matches = getInRedis('Football:matches') ?? [];
                    foreach ($payload['matches'] as $fixture) {
                        if (! is_array($fixture) || (int) ($fixture['sportId'] ?? 1) !== 1 || empty($fixture['id'])
                            || ! is_string($fixture['homeTeam'] ?? null) || ! is_string($fixture['awayTeam'] ?? null) || empty($fixture['matchDate'])) {
                            continue;
                        }
                        try {
                            $timestamp = Carbon::parse($fixture['matchDate'], 'UTC')->timestamp;
                        } catch (Throwable) {
                            continue;
                        }
                        $candidates = array_filter($matches, static fn (array $match): bool => ! empty($match['sportsApiPro_match_id'])
                            && in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
                            && (int) ($match['timestamp'] ?? 0) === $timestamp
                            && $controller->teamSimilarity($match['team1'] ?? '', $fixture['homeTeam']) >= 80
                            && $controller->teamSimilarity($match['team2'] ?? '', $fixture['awayTeam']) >= 80);
                        if (count($candidates) === 1) {
                            $match = reset($candidates);
                            $controller->queueBet365MatchOdds($match, (int) $fixture['id']);
                        }
                    }
                    if ($page < (int) ($payload['totalPages'] ?? 1) && $page < 50) {
                        $controller->queueBet365Favorites($date, $page + 1);
                    }
                    Cache::store('redis')->put($cacheKey.':cooldown', true, 900);
                } catch (Throwable $e) {
                    Cache::store('redis')->put($cacheKey.':cooldown', true, 300);
                    Log::warning('Football bet365 catalogue failed', ['date' => $date, 'page' => $page, 'api' => $apiDiagnostics, 'exception' => $e::class, 'message' => $e->getMessage()]);
                } finally {
                    $lock->release();
                }
            })->name('Football bet365 catalogue '.$date.' page '.$page)->onQueue('default')->catch(static function (Throwable $e) use ($cacheKey, $owner, $page): void {
                Cache::store('redis')->restoreLock($cacheKey.':pending:'.$page, $owner)->release();
                Cache::store('redis')->put($cacheKey.':cooldown', true, 300);
            });
        } catch (Throwable $e) {
            $pending?->release();
            Log::warning('Football bet365 catalogue could not be queued', ['date' => $date, 'exception' => $e::class]);
        }
    }

    public function queueBet365MatchOdds(array $match, int $oddsMatchId): void
    {
        $matchId = (string) $match['sportsApiPro_match_id'];
        $cacheKey = 'Football:bet365:'.$matchId;
        $unavailableKey = 'Football:bet365:unavailable:'.$oddsMatchId;
        $cooldownKey = $cacheKey.(($match['result_status'] ?? '') === 'Live' ? ':final-cooldown' : ':cooldown');
        $pending = null;
        try {
            if ($oddsMatchId <= 0 || ! $this->canFetchBet365Odds($match)
                || Cache::store('redis')->has($cooldownKey) || Cache::store('redis')->has($unavailableKey)) {
                return;
            }
            $pending = Cache::store('redis')->lock($cacheKey.':pending', 3600);
            if (! $pending->get()) {
                return;
            }
            $owner = $pending->owner();
            dispatch(static function () use ($matchId, $oddsMatchId, $cacheKey, $cooldownKey, $unavailableKey, $owner): void {
                $lock = Cache::store('redis')->restoreLock($cacheKey.':pending', $owner);
                $status = null;
                $apiDiagnostics = null;
                try {
                    if (! $lock->isOwnedByCurrentProcess() || Cache::store('redis')->has($unavailableKey)) {
                        return;
                    }
                    $controller = app(self::class);
                    $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId && $controller->canFetchBet365Odds($match));
                    if ($match === null) {
                        return;
                    }
                    $requestedAt = microtime(true);
                    $response = ApiCall('https://api.sportsapipro.com/v4/football/match/'.$oddsMatchId.'/odds', ['x-api-key' => GetSportApiProKey()], ['query' => ['oddGroupId' => 1], 'connect_timeout' => 3, 'timeout' => 15, 'raw_response' => true], $apiDiagnostics);
                    if (is_array($response) && array_key_exists('body', $response)) {
                        $status = (int) $response['status'];
                        $response = json_decode($response['body'], true);
                    }
                    $payload = is_array($response) ? ($response['data'] ?? $response) : [];
                    if ((int) ($payload['matchId'] ?? 0) !== $oddsMatchId) {
                        throw new \RuntimeException('SportsAPI Pro V4 odds unavailable or returned a different match ID'.($status !== null ? ' (HTTP '.$status.')' : '').'.');
                    }
                    $favorite = $controller->bet365Favorite($payload);
                    if ($favorite === null) {
                        Cache::store('redis')->put($cooldownKey, true, 900);

                        return;
                    }
                    $changed = withRedisLock('Football:matches', static function () use ($matchId, $oddsMatchId, $favorite, $requestedAt): array {
                        $matches = getInRedis('Football:matches') ?? [];
                        foreach ($matches as $key => $match) {
                            if ((string) ($match['sportsApiPro_match_id'] ?? '') !== $matchId || ! in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
                                || (($match['result_status'] ?? '') === 'Live' && ($match['bet365_prematch']['finalized'] ?? false))
                                || ($match['bet365_prematch']['fetched_at'] ?? 0) > $requestedAt) {
                                continue;
                            }
                            $openings = [];
                            foreach (['home_odds', 'draw_odds', 'away_odds'] as $selection) {
                                $previous = (int) ($match['bet365_prematch']['match_id'] ?? 0) === $oddsMatchId
                                    ? ($match['bet365_prematch']['opening_odds'][$selection] ?? []) : [];
                                $openings[$selection] = updateOddsMonitoring(['backOdds' => $favorite[$selection] ?? null], $previous, $requestedAt);
                            }
                            $matches[$key]['bet365_prematch'] = array_merge($favorite, [
                                'bookmaker_id' => 126, 'match_id' => $oddsMatchId, 'fetched_at' => $requestedAt,
                                'opening_odds' => $openings,
                                'finalized' => $match['result_status'] === 'Live',
                                'previous_odds' => array_intersect_key($match['bet365_prematch'] ?? [], array_flip(['home_odds', 'draw_odds', 'away_odds'])),
                            ]);
                            storeInRedis('Football:matches', $matches, 30);

                            return [$key => $matches[$key]];
                        }

                        return [];
                    });
                    Cache::store('redis')->put($cooldownKey, true, 900);
                    if ($changed !== []) {
                        $controller->storeMatchesInDatabase($changed);
                        Event::dispatch(new FootballSportsScoreUpdated);
                    }
                } catch (Throwable $e) {
                    $retryAfter = in_array($status, [404, 410], true) ? 3600 : 300;
                    if (in_array($status, [404, 410], true)) {
                        Cache::store('redis')->put($unavailableKey, true, $retryAfter);
                    }
                    Cache::store('redis')->put($cooldownKey, true, $retryAfter);
                    Log::warning('Football bet365 odds failed', ['match_id' => $matchId, 'odds_match_id' => $oddsMatchId, 'http_status' => $status, 'api' => $apiDiagnostics, 'exception' => $e::class, 'message' => $e->getMessage()]);
                } finally {
                    $lock->release();
                }
            })->name('Football bet365 prematch odds '.$matchId)->onQueue('default')->catch(static function (Throwable $e) use ($cacheKey, $cooldownKey, $owner): void {
                Cache::store('redis')->restoreLock($cacheKey.':pending', $owner)->release();
                Cache::store('redis')->put($cooldownKey, true, 300);
            });
        } catch (Throwable $e) {
            $pending?->release();
            Log::warning('Football bet365 odds could not be queued', ['match_id' => $matchId, 'exception' => $e::class]);
        }
    }

    /** @param array<string, mixed> $match */
    private function canFetchBet365Odds(array $match): bool
    {
        return in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
            && (int) ($match['timestamp'] ?? 0) >= time() - 86400
            && (int) ($match['timestamp'] ?? 0) <= time() + 86400
            && ! ($match['timeline']['clock']['finished'] ?? false)
            && ($match['timeline']['clock']['status_type'] ?? '') !== 'finished'
            && ! (($match['result_status'] ?? '') === 'Live' && ($match['bet365_prematch']['finalized'] ?? false));
    }

    /** @return array{home_odds: float, draw_odds: ?float, away_odds: float, favorite_side: string, favorite_odds: float}|null */
    private function bet365Favorite(array $payload): ?array
    {
        foreach ($payload['prematch'] ?? [] as $market) {
            if (! is_array($market) || (int) ($market['og'] ?? $market['id'] ?? 0) !== 1) {
                continue;
            }
            foreach ($market['periods'] ?? [] as $period) {
                if (! is_array($period) || preg_replace('/[^a-z]/', '', strtolower((string) ($period['name'] ?? ''))) !== 'fulltime') {
                    continue;
                }
                foreach ($period['odds'] ?? [] as $row) {
                    if (! is_array($row) || (int) ($row['bid'] ?? 0) !== 126 || filter_var($row['suspended'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }
                    $home = $row['o1'] ?? null;
                    $away = $row['o3'] ?? $row['o2'] ?? null;
                    $draw = $row['oX'] ?? (isset($row['o3']) ? ($row['o2'] ?? null) : null);
                    if (! is_numeric($home) || ! is_numeric($away) || ! is_finite((float) $home) || ! is_finite((float) $away) || (float) $home <= 1 || (float) $away <= 1) {
                        continue;
                    }

                    return [
                        'home_odds' => (float) $home,
                        'draw_odds' => is_numeric($draw) && is_finite((float) $draw) && (float) $draw > 1 ? (float) $draw : null,
                        'away_odds' => (float) $away,
                        'favorite_side' => (float) $home <= (float) $away ? 'home' : 'away',
                        'favorite_odds' => min((float) $home, (float) $away),
                    ];
                }
            }
        }

        return null;
    }

    public function mergeBet365LiveOdds(int|string $matchId, array $payload, ?float $receivedAt = null, ?int $sourceTimestamp = null): bool
    {
        if (! is_array($payload['markets'] ?? null) || (isset($payload['eventId']) && (string) $payload['eventId'] !== (string) $matchId)
            || (isset($payload['providerId']) && (int) $payload['providerId'] !== 1)) {
            return false;
        }
        $markets = [];
        foreach ($payload['markets'] as $market) {
            if (! is_array($market) || ! filter_var($market['isLive'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            $suspended = filter_var($market['suspended'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $choices = [];
            foreach ($market['choices'] ?? [] as $choice) {
                if (! is_array($choice)) {
                    continue;
                }
                $choices[] = [
                    'id' => $choice['sourceId'] ?? $choice['id'] ?? null,
                    'name' => (string) ($choice['name'] ?? ''),
                    'decimal_odds' => $suspended ? null : $this->bet365DecimalOdds($choice['fractionalValue'] ?? null),
                ];
            }
            $markets[] = [
                'id' => $market['id'] ?? $market['marketId'] ?? null,
                'market_id' => $market['marketId'] ?? null, 'name' => (string) ($market['marketName'] ?? ''),
                'period' => $market['marketPeriod'] ?? null, 'suspended' => $suspended, 'choices' => $choices,
            ];
        }
        $receivedAt ??= microtime(true);

        $cacheKey = 'Football:bet365:live:'.$matchId;

        return Cache::store('redis')->lock($cacheKey.':write', 15)->block(2, static function () use ($matchId, $markets, $receivedAt, $sourceTimestamp, $cacheKey): bool {
            $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);
            $latest = Cache::store('redis')->get($cacheKey, []);
            if (($match['result_status'] ?? '') !== 'Live' || ($latest['updated_at'] ?? 0) > $receivedAt
                || ($sourceTimestamp !== null && ($latest['source_timestamp'] ?? 0) > $sourceTimestamp)) {
                return false;
            }
            $openings = $latest['openings'] ?? [];
            foreach ($markets as &$market) {
                foreach ($market['choices'] as &$choice) {
                    $selection = hash('sha256', json_encode([$market['id'], $market['market_id'], $market['period'], $market['name'], $choice['id'], $choice['name']], JSON_THROW_ON_ERROR));
                    $opening = updateOddsMonitoring(['backOdds' => $choice['decimal_odds']], $openings[$selection] ?? [], $receivedAt, ! $market['suspended']);
                    $openings[$selection] = $opening;
                    $choice['initial_decimal_odds'] = $opening['initialBackOdds'];
                    $choice['opening_odds'] = $opening;
                }
                unset($choice);
            }
            unset($market);
            Cache::store('redis')->put($cacheKey, ['provider_id' => 1, 'markets' => $markets, 'openings' => $openings, 'updated_at' => $receivedAt, 'source_timestamp' => $sourceTimestamp], 86400);

            return true;
        });
    }

    private function bet365DecimalOdds(mixed $fraction): ?float
    {
        if (! is_string($fraction) || preg_match('/^\s*(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)\s*$/', $fraction, $parts) !== 1 || (float) $parts[2] <= 0) {
            return null;
        }
        $decimal = (float) $parts[1] / (float) $parts[2] + 1;

        return is_finite($decimal) && $decimal > 1 ? $decimal : null;
    }

    /** @param array<string, mixed> $match
     * @param  array<string, mixed>  $payload
     */
    public function applyMatchStatistics(array &$match, array $payload, ?float $requestedAt = null): bool
    {
        $receivedAt = $requestedAt ?? microtime(true);
        $changeTimestamp = data_get($payload, 'changes.changeTimestamp', $payload['changes.changeTimestamp'] ?? null);
        if (($requestedAt !== null && ($match['statistics_updated_at'] ?? 0) > $requestedAt)
            || ($requestedAt === null && is_numeric($changeTimestamp) && $changeTimestamp < ($match['statistics_change_timestamp'] ?? 0))) {
            return false;
        }

        $statKeys = [
            'attacks' => 'attacks', 'dangerousattacks' => 'dangerous_attacks',
            'ballpossession' => 'possession', 'possession' => 'possession',
            'totalshotsongoal' => 'total_shots', 'totalshots' => 'total_shots',
            'shotsongoal' => 'shots_on_target', 'shotsontarget' => 'shots_on_target',
            'shotsoffgoal' => 'shots_off_target', 'shotsofftarget' => 'shots_off_target',
            'blockedscoringattempt' => 'blocked_shots', 'blockedshots' => 'blocked_shots',
            'passes' => 'total_passes', 'totalpasses' => 'total_passes',
            'accuratepasses' => 'accurate_passes', 'totaltackle' => 'tackles', 'tackles' => 'tackles',
            'interceptionwon' => 'interceptions', 'interceptions' => 'interceptions',
            'fouls' => 'fouls', 'cornerkicks' => 'corners', 'corners' => 'corners',
            'yellowcards' => 'yellow_cards', 'redcards' => 'red_cards', 'offsides' => 'offsides',
        ];
        $liveStats = [];
        $meta = [];
        $periods = $match['statistics_periods'] ?? [];
        $stack = [[$payload, 'ALL', 'Match statistics']];
        $hasItems = false;
        while ($stack !== []) {
            [$data, $period, $group] = array_pop($stack);
            $period = strtoupper((string) ($data['period'] ?? $data['periodName'] ?? $period));
            $group = (string) ($data['groupName'] ?? $group);
            $providerKey = (string) ($data['key'] ?? $data['name'] ?? $data['statisticName'] ?? '');
            if ($providerKey !== '' && (array_key_exists('home', $data) || array_key_exists('homeValue', $data))
                && (array_key_exists('away', $data) || array_key_exists('awayValue', $data))) {
                $name = strtolower(preg_replace('/[^a-z0-9]/i', '', $providerKey));
                $key = $statKeys[$name] ?? Str::snake(Str::camel($providerKey));
                $homeDisplay = $data['home'] ?? $data['homeValue'] ?? null;
                $awayDisplay = $data['away'] ?? $data['awayValue'] ?? null;
                if ((is_scalar($homeDisplay) || $homeDisplay === null) && (is_scalar($awayDisplay) || $awayDisplay === null)) {
                    $hasItems = true;
                    $periods[$period][$group][$key] = [
                        'label' => (string) ($data['name'] ?? $data['statisticName'] ?? Str::headline($providerKey)),
                        'home' => $homeDisplay, 'away' => $awayDisplay,
                    ];
                }
                if (! is_scalar($homeDisplay) && $homeDisplay !== null || ! is_scalar($awayDisplay) && $awayDisplay !== null) {
                    continue;
                }
                $isPercent = preg_match('/^-?\d+(?:\.\d+)?%$/', trim((string) $homeDisplay)) === 1
                    && preg_match('/^-?\d+(?:\.\d+)?%$/', trim((string) $awayDisplay)) === 1;
                $home = $isPercent ? rtrim(trim((string) $homeDisplay), '%') : ($data['homeValue'] ?? $homeDisplay);
                $away = $isPercent ? rtrim(trim((string) $awayDisplay), '%') : ($data['awayValue'] ?? $awayDisplay);
                if ($period === 'ALL' && ! in_array($key, ['attacks', 'dangerous_attacks'], true) && is_numeric($home) && is_numeric($away)) {
                    $liveStats[$key] = ['home' => (float) $home, 'away' => (float) $away];
                    $meta[$key] = ['label' => $periods[$period][$group][$key]['label'], 'percent' => $isPercent];
                }
            }
            foreach (array_reverse($data, true) as $value) {
                if (is_array($value)) {
                    $stack[] = [$value, $period, $group];
                }
            }
        }
        if (! $hasItems) {
            return false;
        }
        $match['statistics_periods'] = $periods;
        $match['statistics_updated_at'] = $receivedAt;
        if ($requestedAt === null && is_numeric($changeTimestamp)) {
            $match['statistics_change_timestamp'] = $changeTimestamp;
        }
        $match['live_stats'] = array_replace($match['live_stats'] ?? [], $liveStats);
        $match['live_stats_meta'] = array_replace($match['live_stats_meta'] ?? [], $meta);
        foreach (array_keys($liveStats) as $stat) {
            $match['live_stats_sources'][$stat] = 'v2';
        }
        foreach (['yellow_cards', 'red_cards'] as $stat) {
            if (isset($liveStats[$stat])) {
                $match['card_stats'][$stat] = $liveStats[$stat];
                $match['cards_updated_at'] = $receivedAt;
                $match['card_stats_updated_at'] = $receivedAt;
            }
        }
        if (isset($liveStats['corners'])) {
            $match['corners'] = array_map(static fn (float $value): int => (int) $value, $liveStats['corners']);
        }
        $this->syncTimelineCards($match);

        return true;
    }

    /** @return array<string, mixed>|null */
    public function loadMatchStatistics(string $matchId, bool $includePrematch = true): ?array
    {
        $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId);
        if ($match === null) {
            return null;
        }
        $lock = Cache::store('redis')->lock('Football:statistics:'.$matchId.':fetch', 60);
        if (! $lock->get()) {
            return $match;
        }
        try {
            $responses = [];
            $attempts = [];
            $sections = $includePrematch ? ['pregame-form', 'h2h', 'streaks'] : [];
            if (($match['result_status'] ?? '') !== 'Prematch') {
                $sections[] = 'statistics';
            }
            foreach ($sections as $section) {
                $lastAttempt = $match['statistics_requests'][$section] ?? [];
                $ttl = ($lastAttempt['available'] ?? false)
                    ? ($section === 'statistics' ? (($match['result_status'] ?? '') === 'Finished' ? 86400 : 30) : 1800)
                    : 120;
                if (($lastAttempt['at'] ?? 0) > microtime(true) - $ttl
                    || ($section === 'statistics' && ($match['statistics_updated_at'] ?? 0) > microtime(true) - 30)) {
                    continue;
                }
                $requestedAt = microtime(true);
                $diagnostics = null;
                try {
                    $response = ApiCall('https://api.sportsapipro.com/v2/football/match/'.$matchId.'/'.$section,
                        ['x-api-key' => GetSportApiProKey()], ['connect_timeout' => 3, 'timeout' => 6], $diagnostics);
                    $payload = $response['data'] ?? $response;
                    $expectedKeys = match ($section) {
                        'statistics' => ['statistics'],
                        'pregame-form' => ['homeTeam', 'awayTeam'],
                        'h2h' => ['teamDuel', 'managerDuel'],
                        'streaks' => ['general', 'head2head'],
                    };
                    $available = is_array($response) && ($response['success'] ?? true) !== false
                        && is_array($payload) && array_any($expectedKeys, static fn (string $key): bool => array_key_exists($key, $payload))
                        && (! isset($response['matchId']) || (string) $response['matchId'] === $matchId)
                        && (! isset($diagnostics['http_status']) || ($diagnostics['http_status'] >= 200 && $diagnostics['http_status'] < 300));
                    if ($available) {
                        $responses[$section] = ['data' => $payload, 'at' => $requestedAt];
                    } else {
                        Log::warning('Football statistics response unavailable', ['match_id' => $matchId, 'section' => $section, 'api' => $diagnostics]);
                    }
                } catch (Throwable $exception) {
                    $available = false;
                    Log::warning('Football statistics request failed', ['match_id' => $matchId, 'section' => $section, 'api' => $diagnostics, 'exception' => $exception::class]);
                }
                $attempts[$section] = ['at' => $requestedAt, 'available' => $available];
            }
            if ($attempts === []) {
                return $match;
            }

            return withRedisLock('Football:matches', function () use ($matchId, $responses, $attempts): ?array {
                $matches = getInRedis('Football:matches') ?? [];
                $key = array_find_key($matches, static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId);
                if ($key === null) {
                    return null;
                }
                foreach ($responses as $section => $response) {
                    if ($section === 'statistics') {
                        $this->applyMatchStatistics($matches[$key], $response['data'], $response['at']);
                    } else {
                        $matches[$key]['prematch_statistics'][$section] = $response['data'];
                    }
                }
                $matches[$key]['statistics_requests'] = array_replace($matches[$key]['statistics_requests'] ?? [], $attempts);
                storeInRedis('Football:matches', $matches, 10);

                return $matches[$key];
            });
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed> $payload */
    public function mergeV6TeamStats(int|string $matchId, string $v6Id, array $payload, float $requestedAt, ?string $dictionaryVersion = null): bool
    {
        $fullMatch = $payload['matchStats'][0]['stats'] ?? null;
        if (! is_array($fullMatch) || (isset($payload['matchId']) && (string) $payload['matchId'] !== $v6Id)) {
            return false;
        }
        $stats = [];
        foreach (['123' => 'attacks', '124' => 'dangerous_attacks', '103' => 'yellow_cards', '104' => 'red_cards', '102' => 'corners'] as $id => $name) {
            $values = $fullMatch[$id]['values'] ?? [];
            $pair = ['home' => null, 'away' => null];
            foreach (['home' => 0, 'away' => 1] as $side => $index) {
                if (is_numeric($values[$index] ?? null) && is_finite((float) $values[$index]) && (float) $values[$index] >= 0) {
                    $pair[$side] = (int) $values[$index];
                }
            }
            if (in_array($name, ['attacks', 'dangerous_attacks'], true) || ($pair['home'] !== null && $pair['away'] !== null)) {
                $stats[$name] = $pair;
            }
        }
        $changed = withRedisLock('Football:matches', static function () use ($matchId, $v6Id, $stats, $requestedAt, $dictionaryVersion): array {
            $matches = getInRedis('Football:matches') ?? [];
            $persist = [];
            $updated = false;
            $liveStats = app(FootballV6Stats::class);
            foreach ($matches as $key => &$match) {
                if ((string) ($match['sportsApiPro_match_id'] ?? '') !== (string) $matchId || ! $liveStats->isLive($match)
                    || ($match['v6_stats']['updated_at'] ?? 0) > $requestedAt) {
                    continue;
                }
                $before = $match['live_stats'] ?? [];
                $newMapping = ($match['sportsApiPro_v6_match_id'] ?? null) !== $v6Id;
                $match['sportsApiPro_v6_match_id'] = $v6Id;
                foreach (['attacks' => 'Attacks', 'dangerous_attacks' => 'Dangerous Attacks'] as $stat => $label) {
                    $match['live_stats'][$stat] = $stats[$stat];
                    $match['live_stats_sources'][$stat] = 'v6';
                    $match['live_stats_meta'][$stat] = ['label' => $label, 'percent' => false, 'source' => 'v6'];
                }
                if (! ($match['incidents_initialized'] ?? false) && ($match['cards_updated_at'] ?? 0) <= $requestedAt) {
                    foreach (['yellow_cards', 'red_cards', 'corners'] as $stat) {
                        if (isset($stats[$stat]) && ! isset($match['live_stats'][$stat])) {
                            $match['live_stats'][$stat] = $stats[$stat];
                            $match['live_stats_sources'][$stat] = 'v6';
                            $match['card_stats_fallback_at'] = $requestedAt;
                            $match['card_stats_updated_at'] = $requestedAt;
                        }
                    }
                    if (! isset($match['corners']) && isset($stats['corners'])) {
                        $match['corners'] = $stats['corners'];
                    }
                    if (isset($match['live_stats']['yellow_cards'], $match['live_stats']['red_cards'])) {
                        app(self::class)->syncTimelineCards($match);
                    }
                }
                $match['v6_stats'] = [
                    'updated_at' => $requestedAt, 'last_attempt_at' => (int) $requestedAt,
                    'next_poll_at' => (int) $requestedAt + 60, 'dictionary_version' => $dictionaryVersion,
                ];
                $updated = true;
                if ($newMapping || $before !== $match['live_stats']) {
                    $persist[$key] = $match;
                    unset($persist[$key]['markets']);
                }
            }
            unset($match);
            if ($updated) {
                storeInRedis('Football:matches', $matches, 30);
            }

            return $persist;
        });
        if ($changed !== []) {
            $this->storeMatchesInDatabase($changed);
            Event::dispatch(new FootballSportsScoreUpdated);
        }

        return $changed !== [];
    }

    /** @param array<string, mixed> $payload */
    public function mergeCardStatsFallback(int|string $matchId, string $v6Id, array $payload, float $requestedAt): bool
    {
        return $this->mergeV6TeamStats($matchId, $v6Id, $payload, $requestedAt);
    }

    /** @param list<array<string, mixed>> $alerts */
    public function mergePinnacleDrops(array $alerts): void
    {
        $now = time();
        $alerts = array_filter($alerts, static fn (array $alert): bool => strtolower((string) ($alert['sport'] ?? '')) === 'soccer'
            && ! ($alert['is_live'] ?? true) && (int) ($alert['period'] ?? 0) === 0 && empty($alert['special'])
            && (int) ($alert['alerted'] ?? 0) >= $now - 10800 && (int) ($alert['alerted'] ?? 0) <= $now + 30);
        if ($alerts === []) {
            return;
        }
        $changed = withRedisLock('Football:matches', function () use ($alerts): array {
            $matches = getInRedis('Football:matches') ?? [];
            $changed = [];
            foreach ($alerts as $alert) {
                $starts = $alert['starts'] ?? null;
                $starts = is_numeric($starts) ? (int) $starts : (is_string($starts) ? strtotime($starts) : false);
                if ($starts > 100_000_000_000) {
                    $starts = (int) floor($starts / 1000);
                }
                if (! $starts || ! is_numeric($alert['from_price'] ?? null) || ! is_numeric($alert['to_price'] ?? null)) {
                    continue;
                }
                $from = (float) $alert['from_price'];
                $to = (float) $alert['to_price'];
                if (! is_finite($from) || ! is_finite($to) || $from <= 1 || $to <= 1 || $to >= $from) {
                    continue;
                }
                $candidates = array_filter($matches, fn (array $match): bool => ($match['result_status'] ?? '') === 'Prematch'
                    && ! empty($match['sportsApiPro_match_id']) && abs((int) ($match['timestamp'] ?? 0) - $starts) <= 300
                    && $this->teamSimilarity($match['team1'] ?? '', (string) ($alert['home'] ?? '')) >= 80
                    && $this->teamSimilarity($match['team2'] ?? '', (string) ($alert['away'] ?? '')) >= 80);
                if (count($candidates) !== 1) {
                    continue;
                }
                $key = array_key_first($candidates);
                $selection = hash('sha256', implode('|', [$alert['id'], $alert['period'] ?? 0, $alert['sect'] ?? '', $alert['outcome'] ?? '', $alert['point'] ?? $alert['points'] ?? $alert['hdp'] ?? '']));
                $state = $matches[$key]['pinnacle_markets'][$selection] ?? [];
                $at = (int) $alert['alerted'];
                if ($at < (int) ($state['updated_at'] ?? 0)) {
                    continue;
                }
                $display = [
                    'sect' => (string) ($alert['sect'] ?? ''), 'outcome' => (string) ($alert['outcome'] ?? ''),
                    'point' => $alert['point'] ?? $alert['points'] ?? $alert['hdp'] ?? null, 'from_price' => $from,
                ];
                if (($state['alert_key'] ?? null) === ($alert['alert_key'] ?? json_encode($alert))) {
                    if (array_diff_key($display, $state) !== []) {
                        $matches[$key]['pinnacle_markets'][$selection] = array_replace($display, $state);
                        $changed[$key] = $matches[$key];
                    }

                    continue;
                }
                $opening = hasMeaningfulOpeningOdds($state) ? $state : updateOddsMonitoring(['backOdds' => $from], [], $at);
                $opening = updateOddsMonitoring(['backOdds' => $to], $opening, $at);
                $baseline = (float) $opening['initialBackOdds'];
                $dropTriggered = ($state['drop_triggered'] ?? false) || round(($baseline - $to) / $baseline * 100, 8) >= 1;
                $snapshots = array_values(array_filter($state['snapshots'] ?? [], static fn (array $snapshot): bool => $snapshot['timestamp'] >= $at - 180 && $snapshot['timestamp'] <= $at));
                $interval = $alert['interval'] ?? null;
                if (is_numeric($interval) && (float) $interval >= 0 && (float) $interval <= 180) {
                    $snapshots[] = ['timestamp' => $at - (int) ceil((float) $interval), 'odds' => $from];
                }
                $section = strtolower(trim((string) ($alert['sect'] ?? '')));
                $outcome = strtolower(trim((string) ($alert['outcome'] ?? '')));
                $line = $alert['point'] ?? $alert['points'] ?? $alert['hdp'] ?? null;
                if (! is_numeric($line) && preg_match('/(?:over|under|total|o\/u)\s*([0-9]+(?:\.[0-9]+)?)/i', $section.' '.$outcome, $lineMatch)) {
                    $line = $lineMatch[1];
                }
                $shockMarket = in_array($section, ['moneyline', 'money line', '1x2', 'match odds', 'both teams to score', 'btts'], true)
                    || (preg_match('/^(total|totals|o\/u)/', $section) === 1 && is_numeric($line) && in_array((float) $line, [1.5, 2.5], true));
                $shockAt = (int) ($state['shock_detected_at'] ?? 0);
                if ($shockMarket && $snapshots !== []) {
                    $comparison = max(array_column($snapshots, 'odds'));
                    $threshold = $comparison >= 1.20 && $comparison <= 1.99 ? 5
                        : ($comparison >= 2.00 && $comparison <= 3.99 ? 8 : ($comparison >= 4.00 && $comparison <= 20.00 ? 12 : null));
                    if ($threshold !== null && round(($comparison - $to) / $comparison * 100, 8) >= $threshold && $shockAt < $at - 1200) {
                        $shockAt = $at;
                    }
                }
                $snapshots[] = ['timestamp' => $at, 'odds' => $to];
                $matches[$key]['pinnacle_markets'][$selection] = array_merge($opening, $display, [
                    'monitoring_baseline_version' => 1,
                    'baseline_odds' => $baseline, 'current_odds' => $to, 'drop_triggered' => $dropTriggered,
                    'shock_detected_at' => $shockAt, 'snapshots' => array_slice($snapshots, -7),
                    'updated_at' => $at, 'alert_key' => $alert['alert_key'] ?? json_encode($alert),
                ]);
                $matches[$key]['pinnacle_drop_triggered'] = ($matches[$key]['pinnacle_drop_triggered'] ?? false) || $dropTriggered;
                $matches[$key]['pinnacle_market_shock_detected_at'] = max((int) ($matches[$key]['pinnacle_market_shock_detected_at'] ?? 0), $shockAt);
                $changed[$key] = $matches[$key];
            }
            if ($changed !== []) {
                storeInRedis('Football:matches', $matches, 30);
            }

            return $changed;
        });
        if ($changed !== []) {
            $this->storeMatchesInDatabase($changed);
            Event::dispatch(new FootballSportsScoreUpdated);
        }
    }

    private function readBetFairData(array $payloads, string $cacheKey, bool $isLiveApi, int $ttlSeconds)
    {
        $getExistingRecords = getInRedis($cacheKey) ?? [];
        $mainArray = array_filter($getExistingRecords, static fn (array $match): bool => in_array($match['result_status'] ?? null, ['Finished', LiveMatchExpiry::AWAITING_REPLAY], true));
        $databaseMarkets = [];
        $databaseLookupKeys = [];

        foreach (($payloads['liveMap'] ?? []) as $payload) {
            if (! isset($payload['openTimestamp'], $payload['team1'], $payload['team2'])) {
                continue;
            }

            $payloadTimestamp = Carbon::parse($payload['openTimestamp'])->timestamp;
            $payloadUniqueKey = $this->generateUniqueKey($payload['team1'], $payload['team2'], $payloadTimestamp);
            $existingKey = $this->checkIfRecordExistsOnAnotherApi(
                $payloadUniqueKey,
                $payload['team1'],
                $payload['team2'],
                $payloadTimestamp,
                $getExistingRecords
            );
            if (in_array($getExistingRecords[$existingKey ?? $payloadUniqueKey]['result_status'] ?? null, ['Finished', LiveMatchExpiry::AWAITING_REPLAY], true)) {
                continue;
            }
            $existingMarkets = $getExistingRecords[$existingKey ?? $payloadUniqueKey]['markets'] ?? [];

            if ($existingMarkets === []) {
                $databaseLookupKeys[$payloadUniqueKey] = true;
                if ($existingKey !== null) {
                    $databaseLookupKeys[$existingKey] = true;
                }
            }
        }

        if ($databaseLookupKeys !== []) {
            $databaseRows = FootballMarket::query()
                ->whereIn('football_match_unique_key', array_keys($databaseLookupKeys))
                ->get(['football_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $storedMarket = $databaseRow->payload;
                if (is_array($storedMarket)) {
                    $databaseMarkets[$databaseRow->football_match_unique_key][] = $storedMarket;
                }
            }
        }

        foreach ($payloads['liveMap'] as $payloadKey => $payload) {
            $currentTimestamp = Carbon::parse($payload['openTimestamp'])->timestamp;
            $team1 = $payload['team1'];
            $team2 = $payload['team2'];
            $uniqueKey = $this->generateUniqueKey($team1, $team2, $currentTimestamp);

            $checkIsExists = $this->checkIfRecordExistsOnAnotherApi($uniqueKey, $team1, $team2, $currentTimestamp, $getExistingRecords);
            if ($checkIsExists !== null && in_array($getExistingRecords[$checkIsExists]['result_status'] ?? null, ['Finished', LiveMatchExpiry::AWAITING_REPLAY], true)) {
                $mainArray[$checkIsExists] = $getExistingRecords[$checkIsExists];

                continue;
            }
            if ($checkIsExists) {
                $tempArray = $getExistingRecords[$checkIsExists];
                $data_sync_complete = isset($getExistingRecords[$checkIsExists]['sportsApiPro_match_id']);
                $tempArray['data_sync_complete'] = $data_sync_complete;
            } else {
                $tempArray = [];
                $tempArray['data_sync_complete'] = false;
            }

            $getSingleRecords = $checkIsExists ? ($getExistingRecords[$checkIsExists] ?? []) : ($getExistingRecords[$uniqueKey] ?? []);

            $currentStatus = $isLiveApi == true ? 'Live' : 'Prematch';
            $result_status = $currentStatus;
            $tempArray['unique_key'] = $uniqueKey;
            $tempArray['betfair_match_id'] = $payloadKey;
            $tempArray['team1'] = $team1;
            $tempArray['team2'] = $team2;
            $tempArray['tournament'] = $payload['tournament'];
            $tempArray['result_status'] = $result_status;
            $tempArray['minutes_status'] = $this->alignMinuteStatusWithResult(
                $tempArray['minutes_status'] ?? null,
                $result_status
            );
            $tempArray['timestamp'] = $currentTimestamp;
            $markets = $this->filterSupportedMarkets($payload['markets']);
            $tempMarketArray = [];
            $marketLiquidity = [];
            $now = time();

            foreach ($markets as $market) {
                $liquidityKey = $market['marketId'].'_'.$market['marketType'];
                $marketLiquidity[$liquidityKey] = ($marketLiquidity[$liquidityKey] ?? 0)
                    + (float) ($market['backSize'] ?? 0)
                    + (float) ($market['laySize'] ?? 0);
            }

            foreach ($markets as $market) {
                $sortPriority = implode('|', [$market['runnerId'] ?? $market['runner'] ?? '', $market['handicap'] ?? 0]);
                $marketKey = $market['marketId'].'_'.$market['marketType'];

                $previousMarket = $getSingleRecords['markets'][$marketKey][$sortPriority] ?? [];

                if ($previousMarket === []) {
                    foreach (($getSingleRecords['markets'] ?? []) as $previousMarketRunners) {
                        foreach (($previousMarketRunners ?? []) as $candidate) {
                            $sameRunner = isset($market['runnerId'], $candidate['runnerId'])
                                ? (string) $candidate['runnerId'] === (string) $market['runnerId']
                                : strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($market['runner'] ?? '')) === 0;

                            if (
                                ($candidate['marketId'] ?? null) === ($market['marketId'] ?? null)
                                && ($candidate['marketType'] ?? null) === ($market['marketType'] ?? null)
                                && $sameRunner
                                && (float) ($candidate['handicap'] ?? 0) === (float) ($market['handicap'] ?? 0)
                            ) {
                                $previousMarket = $candidate;
                                break 2;
                            }
                        }
                    }
                }

                if ($previousMarket === []) {
                    foreach (array_merge(
                        $databaseMarkets[$checkIsExists] ?? [],
                        $databaseMarkets[$uniqueKey] ?? []
                    ) as $candidate) {
                        $sameRunner = isset($market['runnerId'], $candidate['runnerId'])
                            ? (string) $candidate['runnerId'] === (string) $market['runnerId']
                            : strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($market['runner'] ?? '')) === 0;

                        if (
                            ($candidate['marketId'] ?? null) === ($market['marketId'] ?? null)
                            && ($candidate['marketType'] ?? null) === ($market['marketType'] ?? null)
                            && $sameRunner
                            && (float) ($candidate['handicap'] ?? 0) === (float) ($market['handicap'] ?? 0)
                        ) {
                            $previousMarket = $candidate;
                            break;
                        }
                    }
                }

                $previousMarket = prepareOddsMonitoring($previousMarket);
                $market = updateOddsMonitoring($market, $previousMarket, $now);
                $previousBackOdds = $market['previousBackOdds'];
                $currentOdds = $market['currentBackOdds'];
                $initialBackOdds = (float) ($market['initialBackOdds'] ?? 0);

                if (! $isLiveApi && $currentOdds > 0 && $initialBackOdds > 1) {
                    $baselineOdds = (float) ($previousMarket['prematch_drop_baseline_odds'] ?? 0);
                    if ($baselineOdds <= 0 && ($marketLiquidity[$marketKey] ?? 0) >= 1000) {
                        $baselineOdds = $initialBackOdds;
                    }

                    $market['prematch_drop_baseline_odds'] = $baselineOdds ?: null;
                    $market['prematch_drop_triggered'] = (bool) ($previousMarket['prematch_drop_triggered'] ?? false);
                    $market['prematch_drop_detected_at'] = $previousMarket['prematch_drop_detected_at'] ?? null;

                    if ($baselineOdds > 0 && ! $market['prematch_drop_triggered']) {
                        $previousDrop = (($baselineOdds - (float) $previousBackOdds) / $baselineOdds) * 100;
                        $currentDrop = (($baselineOdds - (float) $currentOdds) / $baselineOdds) * 100;
                        if ($previousDrop < 1 && $currentDrop >= 1) {
                            $market['prematch_drop_triggered'] = true;
                            $market['prematch_drop_detected_at'] = $now;
                        }
                    }

                    if (in_array($market['marketType'], ['MATCH_ODDS', 'OVER_UNDER_15', 'OVER_UNDER_25', 'BOTH_TEAMS_TO_SCORE'], true)) {
                        $snapshots = array_values(array_filter(
                            $previousMarket['market_shock_snapshots'] ?? [],
                            static fn (array $snapshot): bool => (int) ($snapshot['timestamp'] ?? 0) >= ($now - 180)
                        ));
                        $shockDetectedAt = (int) ($previousMarket['market_shock_detected_at'] ?? 0);

                        if (empty($snapshots)) {
                            $snapshots[] = ['timestamp' => $now, 'odds' => (float) $previousBackOdds];
                        }

                        if ((float) $previousBackOdds !== (float) $currentOdds) {
                            $comparisonOdds = max(array_column($snapshots, 'odds'));
                            $requiredDrop = $comparisonOdds >= 1.20 && $comparisonOdds <= 1.99
                                ? 5
                                : ($comparisonOdds >= 2.00 && $comparisonOdds <= 3.99
                                    ? 8
                                    : ($comparisonOdds >= 4.00 && $comparisonOdds <= 20.00 ? 12 : null));
                            $shockDrop = $comparisonOdds > 0
                                ? (($comparisonOdds - (float) $currentOdds) / $comparisonOdds) * 100
                                : 0;

                            if ($requiredDrop !== null && $shockDrop >= $requiredDrop && $shockDetectedAt < ($now - 1200)) {
                                $shockDetectedAt = $now;
                            }

                            $snapshots[] = ['timestamp' => $now, 'odds' => (float) $currentOdds];
                        }

                        $market['market_shock_snapshots'] = array_slice($snapshots, -7);
                        $market['market_shock_detected_at'] = $shockDetectedAt ?: null;
                    }
                }

                $tempMarketArray[$marketKey][$sortPriority] = $market;
                $tempArray['markets'] = $tempMarketArray;
            }

            if (! $isLiveApi) {
                $favoriteOdds = null;
                $favoriteRunner = null;
                $favoriteSide = null;
                $homeName = $this->normalizeTeamName($team1);
                $awayName = $this->normalizeTeamName($team2);

                foreach ($tempMarketArray as $marketRunners) {
                    foreach ($marketRunners as $runner) {
                        $runnerName = $this->normalizeTeamName((string) ($runner['runner'] ?? ''));
                        $runnerOdds = (float) ($runner['currentBackOdds'] ?? 0);
                        if (
                            ($runner['marketType'] ?? null) === 'MATCH_ODDS'
                            && in_array($runnerName, [$homeName, $awayName], true)
                            && $runnerOdds > 0
                            && ($favoriteOdds === null || $runnerOdds < $favoriteOdds)
                        ) {
                            $favoriteOdds = $runnerOdds;
                            $favoriteRunner = $runner['runner'];
                            $favoriteSide = $runnerName === $homeName ? 'home' : 'away';
                        }
                    }
                }

                $tempArray['prematch_favorite_runner'] = $favoriteRunner;
                $tempArray['prematch_favorite_odds'] = $favoriteOdds;
                $tempArray['prematch_favorite_side'] = $favoriteSide;
            }
            unset($tempArray['events'], $tempArray['goals'], $tempArray['goal_counts'], $tempArray['status_description']);
            $mainArray[$uniqueKey] = $tempArray;
        }
        storeInRedis($cacheKey, $mainArray, $ttlSeconds);

        return $mainArray;
    }

    private function readsportApiData(array $payloads, string $cacheKey, bool $isLive, int $ttlSeconds): array
    {
        if (empty($payloads)) {
            return [];
        }
        $sportsImages = [];
        $getSportsImage = static function (string $type, mixed $entityId) use (&$sportsImages): ?string {
            $imageKey = $type.':'.(int) $entityId;
            if (! array_key_exists($imageKey, $sportsImages)) {
                $sportsImages[$imageKey] = getSportsApiProImage('football', $type, $entityId);
            }

            return $sportsImages[$imageKey];
        };

        foreach (($payloads['events'] ?? []) as $payload) {
            $homeTeam = is_array($payload['homeTeam'] ?? null)
                ? ($payload['homeTeam']['name'] ?? null)
                : ($payload['homeTeam'] ?? null);
            $awayTeam = is_array($payload['awayTeam'] ?? null)
                ? ($payload['awayTeam']['name'] ?? null)
                : ($payload['awayTeam'] ?? null);
            $startTimestamp = (int) ($payload['startTimestamp'] ?? 0);
            $statusType = is_array($payload['status'] ?? null) ? ($payload['status']['type'] ?? null) : null;

            if (
                ! isset($payload['id']) || ! is_string($homeTeam) || ! is_string($awayTeam) || $startTimestamp <= 0
                || (! $isLive && ($startTimestamp < time() || ($statusType !== null && $statusType !== 'notstarted')))
            ) {
                continue;
            }

            $getSportsImage('teams', is_array($payload['homeTeam']) ? ($payload['homeTeam']['id'] ?? null) : null);
            $getSportsImage('teams', is_array($payload['awayTeam']) ? ($payload['awayTeam']['id'] ?? null) : null);
            $getSportsImage('tournaments', is_array($payload['tournament'] ?? null) ? ($payload['tournament']['id'] ?? null) : null);
        }

        return withRedisLock($cacheKey, function () use ($payloads, $cacheKey, $isLive, $ttlSeconds, $sportsImages): array {
            $getExistingRecords = getInRedis($cacheKey) ?? [];
            $databaseMatches = array_filter($getExistingRecords, static fn (array $match): bool => in_array($match['result_status'] ?? null, ['Finished', LiveMatchExpiry::AWAITING_REPLAY], true));

            foreach (($payloads['events'] ?? []) as $payloadKey => $payload) {
                $homeTeam = is_array($payload['homeTeam'] ?? null)
                    ? ($payload['homeTeam']['name'] ?? null)
                    : ($payload['homeTeam'] ?? null);
                $awayTeam = is_array($payload['awayTeam'] ?? null)
                    ? ($payload['awayTeam']['name'] ?? null)
                    : ($payload['awayTeam'] ?? null);
                $startTimestamp = (int) ($payload['startTimestamp'] ?? 0);
                $status = $payload['status'] ?? null;
                $statusCode = is_array($status) ? ($status['code'] ?? null) : null;
                $statusType = is_array($status) ? ($status['type'] ?? null) : null;

                if (! isset($payload['id']) || ! is_string($homeTeam) || ! is_string($awayTeam) || $startTimestamp <= 0) {
                    continue;
                }

                if (! $isLive && ($startTimestamp < time() || ($statusType !== null && $statusType !== 'notstarted'))) {
                    continue;
                }

                $tempArray = [];
                $uniqueKey = $this->generateUniqueKey($homeTeam, $awayTeam, (string) $startTimestamp);
                $checkIsExists = $this->checkIfRecordExistsOnAnotherApi($uniqueKey, $homeTeam, $awayTeam, $startTimestamp, $getExistingRecords);
                $existingRecord = $checkIsExists === null ? [] : ($getExistingRecords[$checkIsExists] ?? []);

                if (($existingRecord['result_status'] ?? null) === 'Finished') {
                    $databaseMatches[$checkIsExists] = $existingRecord;

                    continue;
                }

                $minutesStatus = $this->sportsApiMinuteStatus(
                    $statusCode,
                    $existingRecord['minutes_status'] ?? ($isLive ? 'Live' : 'Prematch')
                );
                if ($minutesStatus === 'Live' && (int) floor((time() - $startTimestamp) / 60) >= 60) {
                    $minutesStatus = '2nd half';
                }
                $resultStatus = $isLive
                    ? $this->sportsApiResultStatus($statusCode, $statusType, $existingRecord['result_status'] ?? 'Live')
                    : (($existingRecord['result_status'] ?? null) === LiveMatchExpiry::AWAITING_REPLAY ? LiveMatchExpiry::AWAITING_REPLAY : 'Prematch');
                $minutesStatus = $this->alignMinuteStatusWithResult($minutesStatus, $resultStatus);
                $homeScore = is_array($payload['homeScore'] ?? null)
                    ? ($payload['homeScore']['current'] ?? 0)
                    : ($payload['homeScore'] ?? 0);
                $awayScore = is_array($payload['awayScore'] ?? null)
                    ? ($payload['awayScore']['current'] ?? 0)
                    : ($payload['awayScore'] ?? 0);
                $homeTeamId = is_array($payload['homeTeam'] ?? null) ? ($payload['homeTeam']['id'] ?? null) : null;
                $awayTeamId = is_array($payload['awayTeam'] ?? null) ? ($payload['awayTeam']['id'] ?? null) : null;
                $tournamentData = is_array($payload['tournament'] ?? null) ? $payload['tournament'] : [];
                $tournament = $tournamentData !== [] ? ($tournamentData['name'] ?? '') : ($payload['tournament'] ?? '');
                $tournamentId = $tournamentData['id'] ?? null;
                $countryId = $tournamentData['category']['country']['id'] ?? null;
                $timeData = is_array($payload['time'] ?? null) ? $payload['time'] : [];
                $currentPeriodStartTimestamp = $timeData['currentPeriodStartTimestamp'] ?? null;
                $lastPeriodEndTimestamp = $timeData['lastPeriodEndTimestamp'] ?? null;
                $addedTime = array_filter([
                    'first_half' => isset($timeData['injuryTime1']) && is_numeric($timeData['injuryTime1'])
                        ? (int) $timeData['injuryTime1']
                        : null,
                    'second_half' => isset($timeData['injuryTime2']) && is_numeric($timeData['injuryTime2'])
                        ? (int) $timeData['injuryTime2']
                        : null,
                ], static fn (mixed $value): bool => $value !== null);
                $homeTeamLogo = $existingRecord['home_team_logo'] ?? null;
                $awayTeamLogo = $existingRecord['away_team_logo'] ?? null;
                $tournamentLogo = $existingRecord['tournament_logo'] ?? null;
                $homeTeamLogo = is_string($homeTeamLogo) && File::isFile(public_path(ltrim($homeTeamLogo, '/')))
                    ? $homeTeamLogo
                    : ($sportsImages['teams:'.(int) $homeTeamId] ?? null);
                $awayTeamLogo = is_string($awayTeamLogo) && File::isFile(public_path(ltrim($awayTeamLogo, '/')))
                    ? $awayTeamLogo
                    : ($sportsImages['teams:'.(int) $awayTeamId] ?? null);
                $tournamentLogo = is_string($tournamentLogo) && File::isFile(public_path(ltrim($tournamentLogo, '/')))
                    ? $tournamentLogo
                    : ($sportsImages['tournaments:'.(int) $tournamentId] ?? null);

                if ($checkIsExists) {
                    $data_sync_complete = isset($getExistingRecords[$checkIsExists]['betfair_match_id']);
                    $additionalData = array_filter([
                        'data_sync_complete' => $data_sync_complete,
                        'home_score' => $homeScore,
                        'sportsApiPro_match_id' => $payload['id'],
                        'timeline' => $existingRecord['timeline'] ?? [],
                        'away_score' => $awayScore,
                        'minutes_status' => $minutesStatus,
                        'result_status' => $resultStatus,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'home_team_logo' => $homeTeamLogo,
                        'away_team_logo' => $awayTeamLogo,
                        'tournament_logo' => $tournamentLogo,
                        'tournament_id' => $tournamentId,
                        'tournament_country' => $tournamentData['category']['name'] ?? null,
                        'country_id' => $countryId,
                        'current_period_start_timestamp' => is_numeric($currentPeriodStartTimestamp) && (int) $currentPeriodStartTimestamp > 0
                            ? (int) $currentPeriodStartTimestamp
                            : null,
                        'last_period_end_timestamp' => is_numeric($lastPeriodEndTimestamp) && (int) $lastPeriodEndTimestamp > 0
                            ? (int) $lastPeriodEndTimestamp
                            : null,
                        'added_time' => $addedTime !== [] ? array_replace($getExistingRecords[$checkIsExists]['added_time'] ?? [], $addedTime) : null,
                        // 'status_type' => $payload['status']['type'] ?? null,
                    ], static fn (mixed $value): bool => $value !== null);
                    $getExistingRecords[$checkIsExists] = array_merge($getExistingRecords[$checkIsExists], $additionalData);
                    unset($getExistingRecords[$checkIsExists]['status_description']);
                    unset($getExistingRecords[$checkIsExists]['events'], $getExistingRecords[$checkIsExists]['goals'], $getExistingRecords[$checkIsExists]['goal_counts']);
                    $databaseMatches[$checkIsExists] = $getExistingRecords[$checkIsExists];
                } else {
                    $tempArray['unique_key'] = $uniqueKey;
                    $tempArray['sportsApiPro_match_id'] = $payload['id'];
                    $tempArray['timeline'] = [];
                    $tempArray['timestamp'] = $startTimestamp;
                    $tempArray['data_sync_complete'] = false;
                    $tempArray['home_score'] = $homeScore;
                    $tempArray['away_score'] = $awayScore;
                    $tempArray['minutes_status'] = $minutesStatus;
                    $tempArray['result_status'] = $resultStatus;
                    // $tempArray['status_type'] = $payload['status']['type'] ?? null;
                    $tempArray['team1'] = $homeTeam;
                    $tempArray['team2'] = $awayTeam;
                    $tempArray['tournament'] = $tournament;
                    $tempArray['home_team_id'] = $homeTeamId;
                    $tempArray['away_team_id'] = $awayTeamId;
                    $tempArray['home_team_logo'] = $homeTeamLogo;
                    $tempArray['away_team_logo'] = $awayTeamLogo;
                    $tempArray['tournament_logo'] = $tournamentLogo;
                    $tempArray['tournament_id'] = $tournamentId;
                    $tempArray['tournament_country'] = $tournamentData['category']['name'] ?? null;
                    $tempArray['country_id'] = $countryId;
                    if (is_numeric($currentPeriodStartTimestamp) && (int) $currentPeriodStartTimestamp > 0) {
                        $tempArray['current_period_start_timestamp'] = (int) $currentPeriodStartTimestamp;
                    }
                    if (is_numeric($lastPeriodEndTimestamp) && (int) $lastPeriodEndTimestamp > 0) {
                        $tempArray['last_period_end_timestamp'] = (int) $lastPeriodEndTimestamp;
                    }
                    if ($addedTime !== []) {
                        $tempArray['added_time'] = $addedTime;
                    }
                    $getExistingRecords[$uniqueKey] = $tempArray;
                    $databaseMatches[$uniqueKey] = $tempArray;
                }
            }
            storeInRedis($cacheKey, $getExistingRecords, $ttlSeconds);

            return $databaseMatches;
        }, 30);
    }

    /** @param array<string, array<string, mixed>> $matches */
    public function persistOddsSnapshots(array $matches): void
    {
        $this->storeMatchesInDatabase($matches);
    }

    private function storeMatchesInDatabase(array $matches): void
    {
        if ($matches === []) {
            return;
        }

        $now = Carbon::now();
        $matchRows = [];
        $marketRows = [];
        $matchKeys = [];
        $marketSnapshotKeys = [];

        foreach ($matches as $match) {
            if (! is_array($match) || empty($match['unique_key'])) {
                continue;
            }

            $matchKey = (string) $match['unique_key'];
            $matchKeys[] = $matchKey;
            $matchPayload = $match;
            unset($matchPayload['markets'], $matchPayload['home_team_logo'], $matchPayload['away_team_logo'], $matchPayload['tournament_logo']);

            $matchRows[] = [
                'unique_key' => $match['unique_key'],
                'betfair_match_id' => $match['betfair_match_id'] ?? null,
                'sports_api_pro_match_id' => $match['sportsApiPro_match_id'] ?? null,
                'team1' => $match['team1'] ?? '',
                'team2' => $match['team2'] ?? '',
                'tournament' => $match['tournament'] ?? null,
                'result_status' => $match['result_status'] ?? null,
                'minutes_status' => $match['minutes_status'] ?? null,
                'start_timestamp' => (int) ($match['timestamp'] ?? 0),
                'data_sync_complete' => (bool) ($match['data_sync_complete'] ?? false),
                'payload' => json_encode($matchPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (array_key_exists('markets', $match) && is_array($match['markets'])) {
                $marketSnapshotKeys[$matchKey] = true;
            }

            foreach (($match['markets'] ?? []) as $marketSelections) {
                if (! is_array($marketSelections)) {
                    continue;
                }

                foreach ($marketSelections as $sortPriority => $market) {
                    if (! is_array($market)) {
                        continue;
                    }

                    $selectionKey = hash('sha256', implode('|', [
                        $matchKey,
                        $market['marketId'] ?? '',
                        $market['marketType'] ?? '',
                        $market['runnerId'] ?? '',
                        $market['handicap'] ?? 0,
                        $sortPriority,
                    ]));

                    $marketRows[$selectionKey] = [
                        'selection_key' => $selectionKey,
                        'football_match_unique_key' => $matchKey,
                        'market_id' => $market['marketId'] ?? null,
                        'market_type' => $market['marketType'] ?? null,
                        'runner_id' => $market['runnerId'] ?? null,
                        'runner' => $market['runner'] ?? null,
                        'sort_priority' => is_numeric($sortPriority) ? (int) $sortPriority : null,
                        'handicap' => isset($market['handicap']) ? (float) $market['handicap'] : null,
                        'previous_back_odds' => (float) ($market['previousBackOdds'] ?? $market['backOdds'] ?? 0),
                        'current_back_odds' => (float) ($market['currentBackOdds'] ?? $market['backOdds'] ?? 0),
                        'back_size' => (float) ($market['backSize'] ?? 0),
                        'lay_odds' => (float) ($market['layOdds'] ?? 0),
                        'lay_size' => (float) ($market['laySize'] ?? 0),
                        'payload' => json_encode($market, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if ($matchRows === []) {
            return;
        }

        $matchKeys = array_values(array_unique($matchKeys));
        $marketSnapshotKeys = array_keys($marketSnapshotKeys);

        Cache::store('redis')
            ->lock('Football:database-write-lock', 120)
            ->get(function () use ($matchRows, $marketRows, $matchKeys, $marketSnapshotKeys): void {
                $existingMatches = FootballMatch::query()
                    ->whereIn('unique_key', $matchKeys)
                    ->get([
                        'unique_key',
                        'betfair_match_id',
                        'sports_api_pro_match_id',
                        'team1',
                        'team2',
                        'tournament',
                        'result_status',
                        'minutes_status',
                        'start_timestamp',
                        'data_sync_complete',
                        'payload',
                    ])
                    ->keyBy('unique_key');

                $changedMatchRows = [];
                foreach ($matchRows as $row) {
                    $existing = $existingMatches->get($row['unique_key']);
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    if ($existing?->result_status === LiveMatchExpiry::AWAITING_REPLAY
                        && ! in_array($row['result_status'], ['Finished', LiveMatchExpiry::AWAITING_REPLAY], true)
                        && (int) $existing->start_timestamp === $row['start_timestamp']) {
                        $row['result_status'] = LiveMatchExpiry::AWAITING_REPLAY;
                        $incomingPayload['result_status'] = LiveMatchExpiry::AWAITING_REPLAY;
                        $row['payload'] = json_encode($incomingPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    }
                    if (
                        $existing === null
                        || (string) $existing->betfair_match_id !== (string) $row['betfair_match_id']
                        || (string) $existing->sports_api_pro_match_id !== (string) $row['sports_api_pro_match_id']
                        || $existing->team1 !== $row['team1']
                        || $existing->team2 !== $row['team2']
                        || $existing->tournament !== $row['tournament']
                        || $existing->result_status !== $row['result_status']
                        || $existing->minutes_status !== $row['minutes_status']
                        || (int) $existing->start_timestamp !== $row['start_timestamp']
                        || (bool) $existing->data_sync_complete !== $row['data_sync_complete']
                        || $storedPayload != $incomingPayload
                    ) {
                        $changedMatchRows[] = $row;
                    }
                }

                $existingMarkets = collect();
                if ($marketSnapshotKeys !== []) {
                    $existingMarkets = FootballMarket::query()
                        ->whereIn('football_match_unique_key', $marketSnapshotKeys)
                        ->get(['selection_key', 'football_match_unique_key', 'payload'])
                        ->keyBy('selection_key');
                }

                $changedMarketRows = [];
                foreach ($marketRows as $selectionKey => $row) {
                    $existing = $existingMarkets->get($selectionKey);
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    if (
                        $existing === null
                        || $existing->football_match_unique_key !== $row['football_match_unique_key']
                        || $storedPayload != $incomingPayload
                    ) {
                        $changedMarketRows[] = $row;
                    }
                }

                $incomingSelectionKeys = array_fill_keys(array_keys($marketRows), true);
                $staleSelectionKeys = [];
                foreach ($existingMarkets as $selectionKey => $existing) {
                    if (! isset($incomingSelectionKeys[$selectionKey])) {
                        $staleSelectionKeys[] = $selectionKey;
                    }
                }

                if ($changedMatchRows === [] && $changedMarketRows === [] && $staleSelectionKeys === []) {
                    return;
                }

                if ($changedMatchRows !== []) {
                    usort($changedMatchRows, static fn (array $left, array $right): int => $left['unique_key'] <=> $right['unique_key']);
                    foreach (array_chunk($changedMatchRows, 50) as $matchChunk) {
                        FootballMatch::query()->upsert(
                            $matchChunk,
                            ['unique_key'],
                            [
                                'betfair_match_id',
                                'sports_api_pro_match_id',
                                'team1',
                                'team2',
                                'tournament',
                                'result_status',
                                'minutes_status',
                                'start_timestamp',
                                'data_sync_complete',
                                'payload',
                                'updated_at',
                            ]
                        );
                    }
                }

                if ($staleSelectionKeys !== []) {
                    sort($staleSelectionKeys);
                    foreach (array_chunk($staleSelectionKeys, 500) as $selectionChunk) {
                        FootballMarket::query()
                            ->whereIn('selection_key', $selectionChunk)
                            ->delete();
                    }
                }

                if ($changedMarketRows !== []) {
                    usort($changedMarketRows, static fn (array $left, array $right): int => $left['selection_key'] <=> $right['selection_key']);
                    foreach (array_chunk($changedMarketRows, 200) as $marketChunk) {
                        FootballMarket::query()->upsert(
                            $marketChunk,
                            ['selection_key'],
                            [
                                'football_match_unique_key',
                                'market_id',
                                'market_type',
                                'runner_id',
                                'runner',
                                'sort_priority',
                                'handicap',
                                'previous_back_odds',
                                'current_back_odds',
                                'back_size',
                                'lay_odds',
                                'lay_size',
                                'payload',
                                'updated_at',
                            ]
                        );
                    }
                }
            });
    }

    private function sportsApiMinuteStatus(int|string|null $statusCode, string $currentStatus): string
    {
        return match ((int) $statusCode) {
            6 => '1st half',
            7 => '2nd half',
            31 => 'Half Time',
            100 => 'Finished',
            default => in_array($currentStatus, ['Live', 'Prematch', 'Finished', 'Half Time', '1st half', '2nd half'], true)
                ? $currentStatus
                : 'Live',
        };
    }

    private function sportsApiResultStatus(int|string|null $statusCode, ?string $statusType, string $currentStatus): string
    {
        if ($currentStatus === 'Finished') {
            return 'Finished';
        }

        if ((int) $statusCode === 100 || $statusType === 'finished') {
            return 'Finished';
        }

        if ($currentStatus === LiveMatchExpiry::AWAITING_REPLAY) {
            return $currentStatus;
        }

        if (in_array((int) $statusCode, [6, 7, 31], true) || $statusType === 'inprogress') {
            return 'Live';
        }

        return in_array($currentStatus, ['Live', 'Prematch', 'Finished'], true)
            ? $currentStatus
            : 'Live';
    }

    private function alignMinuteStatusWithResult(?string $minuteStatus, string $resultStatus): string
    {
        if ($resultStatus === 'Finished') {
            return 'Finished';
        }

        if ($resultStatus === 'Prematch') {
            return 'Prematch';
        }

        return in_array($minuteStatus, ['Live', 'Half Time', '1st half', '2nd half'], true)
            ? $minuteStatus
            : 'Live';
    }

    public function getCacheStoreData()
    {
        $data = getInRedis($this->sports.':matches');
        foreach ($data as $key => $value) {
            dump($value);
        }
        dd('1');
    }

    private function matchDirection(float $previousOdds, float $currentOdds)
    {
        if ($currentOdds == $previousOdds) {
            return 'same';
        } elseif ($currentOdds < $previousOdds) {
            return 'down';
        } elseif ($currentOdds > $previousOdds) {
            return 'up';
        }
    }

    private function filterSupportedMarkets(array $markets)
    {
        return $markets;
        $storeOnlyMarkets = ['MATCH_ODDS', 'OVER_UNDER_05', 'OVER_UNDER_25', 'OVER_UNDER_35', 'OVER_UNDER_45', 'OVER_UNDER_55', 'OVER_UNDER_65', 'OVER_UNDER_75', 'OVER_UNDER_85', 'BOTH_TEAMS_TO_SCORE', 'CORRECT_SCORE', 'HALF_TIME', 'HALF_TIME_FULL_TIME', 'DRAW_NO_BET,HALF_TIME_SCORE', 'OVER_UNDER_15'];

        return array_values(array_filter($markets, fn ($market) => in_array($market['marketType'], $storeOnlyMarkets, true)));
    }

    private function generateUniqueKey(string $teamA, string $teamB, string $timestamp)
    {
        $teamA = $this->normalizeTeamName($teamA);
        $teamB = $this->normalizeTeamName($teamB);
        $generatedName = $teamA.'|'.$teamB.'|'.$timestamp;

        return $generatedName;
    }

    private function normalizeTeamName(string $name): string
    {
        $name = Str::ascii($name);
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\s]/', ' ', $name);
        $replacements = [
            ' utd ' => ' united ',
            ' ath ' => ' athletic ',
            ' st ' => ' saint ',
        ];
        $name = ' '.$name.' ';
        foreach ($replacements as $from => $to) {
            $name = str_replace($from, $to, $name);
        }
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return str_replace(' ', '_', $name);
    }

    private function checkIfRecordExistsOnAnotherApi(string $uniqueKey, string $team1, string $team2, string|int $timestamp, ?array $existingRecords = null)
    {
        $existingRecords ??= getInRedis($this->sports.':matches') ?? [];

        if (array_key_exists($uniqueKey, $existingRecords)) {
            return $uniqueKey;
        }

        foreach ($existingRecords as $key => $record) {
            $recordTimestamp = (int) ($record['timestamp'] ?? 0);
            $currentTimestamp = (int) $timestamp;

            if ($recordTimestamp !== $currentTimestamp) {
                continue;
            }
            $team1Score = $this->teamSimilarity($record['team1'] ?? '', $team1);
            $team2Score = $this->teamSimilarity($record['team2'] ?? '', $team2);

            if ($team1Score >= 80 && $team2Score >= 80) {
                return $key;
            }
        }

        return null;
    }

    private function teamSimilarity(string $teamA, string $teamB): float
    {
        $teamA = $this->normalizeTeamName($teamA);
        $teamB = $this->normalizeTeamName($teamB);

        if ($teamA === $teamB) {
            return 100;
        }

        similar_text($teamA, $teamB, $percentage);

        return $percentage;
    }
}
