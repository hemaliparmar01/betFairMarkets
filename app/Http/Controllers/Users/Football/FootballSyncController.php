<?php

namespace App\Http\Controllers\Users\Football;

use App\Events\FootballSportsScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\FootballMarket;
use App\Models\FootballMatch;
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
        $cacheKey = 'Football:timeline:'.$channelMatch[1];
        $isIncidents = ($channelMatch[2] ?? '') === 'incidents';
        $receivedAt = $requestedAt ?? microtime(true);

        return Cache::store('redis')->lock($cacheKey.':write', 15)->block(2, static function () use ($cacheKey, $payload, $frameType, $isIncidents, $receivedAt, $requestedAt): bool {
            $timeline = Cache::store('redis')->get($cacheKey, []);
            $timeline = is_array($timeline) ? $timeline : [];
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
                    $minute = max(0, (int) $incident['time']);
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
                        'added_time' => $added,
                        'seconds' => is_numeric($incident['timeSeconds'] ?? null) ? (int) $incident['timeSeconds'] : ($minute + $added) * 60,
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
                        || ($requestedAt !== null && ($timeline['clock']['finished'] ?? false))
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
            Cache::store('redis')->put($cacheKey, $timeline, 31 * 86400);

            return true;
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

    /** @param array{home: int, away: int}|null $expectedScore */
    public function queueTimelineBackfill(int|string $matchId, bool $finished = false, ?array $expectedScore = null): void
    {
        if (! ctype_digit((string) $matchId) || (int) $matchId <= 0) {
            return;
        }
        $cacheKey = 'Football:timeline:'.$matchId;
        $pending = null;
        try {
            $timeline = Cache::store('redis')->get($cacheKey, []);
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
                try {
                    $controller = app(self::class);
                    if ($expectedScore !== null) {
                        $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);
                        if (isset($match['home_score'], $match['away_score'])) {
                            $expectedScore = ['home' => (int) $match['home_score'], 'away' => (int) $match['away_score']];
                        }
                    }
                    $timeline = Cache::store('redis')->get($cacheKey, []);
                    if (! $lock->isOwnedByCurrentProcess() || (($timeline['initialized'] ?? false) && (! $finished || ($timeline['finalized'] ?? false)) && $controller->timelineGoalsMatchScore($timeline, $expectedScore))) {
                        return;
                    }
                    foreach ($repairGoals ? [':incidents'] : ($finished ? ['', ':incidents'] : ['', ':incidents', ':odds']) as $suffix) {
                        $current = Cache::store('redis')->get($cacheKey, []);
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
                        $response = ApiCall('https://api.sportsapipro.com/v2/football/match/'.$matchId.($suffix === ':odds' ? '/odds/all' : ($suffix === '' ? '' : '/incidents')),
                            ['x-api-key' => GetSportApiProKey()], ['connect_timeout' => 3, 'timeout' => 15]);
                        if (! is_array($response) || ($response['success'] ?? true) === false) {
                            continue;
                        }
                        $payload = $response['data'] ?? $response;
                        $payload = $suffix === '' ? ($payload['event'] ?? $payload) : $payload;
                        if (is_array($payload)) {
                            if ($suffix === ':odds') {
                                $controller->mergeBet365LiveOdds($matchId, $payload, $requestedAt);

                                continue;
                            }
                            $stored = $controller->mergeTimelineFrame('match:'.$matchId.$suffix, $payload, 'snapshot', $requestedAt);
                            if ($finished && $suffix !== '' && $stored) {
                                Cache::store('redis')->lock($cacheKey.':write', 15)->block(2, static function () use ($cacheKey): void {
                                    $timeline = Cache::store('redis')->get($cacheKey, []);
                                    $timeline['finalized'] = true;
                                    Cache::store('redis')->put($cacheKey, $timeline, 31 * 86400);
                                });
                            }
                        }
                    }
                    $timeline = Cache::store('redis')->get($cacheKey, []);
                    if (! ($timeline['initialized'] ?? false) || ($finished && ! ($timeline['finalized'] ?? false)) || ! $controller->timelineGoalsMatchScore($timeline, $expectedScore)) {
                        Cache::store('redis')->put($cacheKey.':retry', true, $repairGoals ? 60 : 300);
                        if (! $finished && ! $repairGoals) {
                            $controller->queueCardStatsFallback($matchId);
                        }
                    }
                    Event::dispatch(new FootballSportsScoreUpdated);
                } catch (Throwable $e) {
                    Cache::store('redis')->put($cacheKey.':retry', true, $repairGoals ? 60 : 300);
                    Log::warning('Football timeline backfill failed', [
                        'match_id' => $matchId,
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
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15]);
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['liveMap'] ?? null)) {
            Log::warning('Football Betfair live snapshot unavailable; existing matches retained');

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
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15]);
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['liveMap'] ?? null)) {
            Log::warning('Football Betfair prematch snapshot unavailable; existing matches retained');

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
        $url = 'https://api.sportsapipro.com/v2/football/live/all';
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15]);
        $liveData = is_array($liveData['data'] ?? null) ? $liveData['data'] : $liveData;
        $cacheKey = $this->sports.':matches';
        if (! is_array($liveData['events'] ?? null)) {
            Log::warning('Football SportsAPI Pro live snapshot unavailable; existing matches retained');

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
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $response = ApiCall('https://api.sportsapipro.com/v4/football/matches', ['x-api-key' => GetSportApiProKey()], [
                        'query' => ['sport' => 'football', 'status' => 'all', 'perPage' => 150, 'page' => $page, 'startDate' => $date.'T00:00:00Z', 'endDate' => $date.'T23:59:59Z'],
                        'connect_timeout' => 3, 'timeout' => 15,
                    ]);
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
                    Log::warning('Football bet365 catalogue failed', ['date' => $date, 'page' => $page, 'exception' => $e::class, 'message' => $e->getMessage()]);
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
        $cooldownKey = $cacheKey.(($match['result_status'] ?? '') === 'Live' ? ':final-cooldown' : ':cooldown');
        $pending = null;
        try {
            if (! in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
                || (($match['result_status'] ?? '') === 'Live' && ($match['bet365_prematch']['finalized'] ?? false))
                || Cache::store('redis')->has($cooldownKey)) {
                return;
            }
            $pending = Cache::store('redis')->lock($cacheKey.':pending', 3600);
            if (! $pending->get()) {
                return;
            }
            $owner = $pending->owner();
            dispatch(static function () use ($matchId, $oddsMatchId, $cacheKey, $cooldownKey, $owner): void {
                $lock = Cache::store('redis')->restoreLock($cacheKey.':pending', $owner);
                $status = null;
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId);
                    if ($match === null || ! in_array($match['result_status'] ?? '', ['Live', 'Prematch'], true)
                        || (($match['result_status'] ?? '') === 'Live' && ($match['bet365_prematch']['finalized'] ?? false))) {
                        return;
                    }
                    $requestedAt = microtime(true);
                    $response = ApiCall('https://api.sportsapipro.com/v4/football/match/'.$oddsMatchId.'/odds', ['x-api-key' => GetSportApiProKey()], ['query' => ['oddGroupId' => 1], 'connect_timeout' => 3, 'timeout' => 15, 'raw_response' => true]);
                    if (is_array($response) && array_key_exists('body', $response)) {
                        $status = (int) $response['status'];
                        $response = json_decode($response['body'], true);
                    }
                    $controller = app(self::class);
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
                                $openings[$selection] = array_merge(meaningfulOpeningOdds(['backOdds' => $favorite[$selection] ?? null], $previous, $requestedAt), ['monitoring_baseline_version' => 1]);
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
                    Cache::store('redis')->put($cooldownKey, true, 300);
                    Log::warning('Football bet365 odds failed', ['match_id' => $matchId, 'odds_match_id' => $oddsMatchId, 'http_status' => $status, 'exception' => $e::class, 'message' => $e->getMessage()]);
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
                    $opening = array_merge(meaningfulOpeningOdds(['backOdds' => $choice['decimal_odds']], $openings[$selection] ?? [], $receivedAt, ! $market['suspended']), ['monitoring_baseline_version' => 1]);
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

    public function queueCardStatsFallback(int|string $matchId): void
    {
        $cacheKey = 'Football:card-fallback:'.$matchId;
        $pending = null;
        try {
            if (Cache::store('redis')->has($cacheKey.':cooldown')) {
                return;
            }
            $pending = Cache::store('redis')->lock($cacheKey.':pending', 3600);
            if (! $pending->get()) {
                return;
            }
            $owner = $pending->owner();
            dispatch(static function () use ($matchId, $cacheKey, $owner): void {
                $lock = Cache::store('redis')->restoreLock($cacheKey.':pending', $owner);
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $match = array_find(getInRedis('Football:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId);
                    if (($match['result_status'] ?? '') !== 'Live' || ($match['incidents_initialized'] ?? false)
                        || isset($match['live_stats']['yellow_cards'], $match['live_stats']['red_cards'])) {
                        return;
                    }
                    $controller = app(self::class);
                    $v6Id = $match['sportsApiPro_v6_match_id'] ?? null;
                    if ($v6Id === null) {
                        $fixtures = Cache::store('redis')->lock('Football:v6:catalogue-lock', 20)->block(2, static function (): array {
                            return Cache::store('redis')->remember('Football:v6:catalogue', 300, static function (): array {
                                $response = ApiCall('https://api.sportsapipro.com/v6/football/live', ['x-api-key' => GetSportApiProKey()], ['connect_timeout' => 3, 'timeout' => 15]);
                                $payload = is_array($response) ? ($response['data'] ?? $response) : [];
                                if (! is_array($payload['matches'] ?? null)) {
                                    Log::warning('Football V6 fallback catalogue unavailable');
                                }

                                return is_array($payload['matches'] ?? null) ? $payload['matches'] : [];
                            });
                        });
                        $candidates = array_filter($fixtures, static fn (array $fixture): bool => ! empty($fixture['id'])
                            && abs((int) ($fixture['matchTime'] ?? 0) - (int) $match['timestamp']) <= 300
                            && $controller->teamSimilarity($match['team1'], (string) ($fixture['homeTeam']['name'] ?? '')) >= 80
                            && $controller->teamSimilarity($match['team2'], (string) ($fixture['awayTeam']['name'] ?? '')) >= 80);
                        if (count($candidates) !== 1) {
                            Cache::store('redis')->put($cacheKey.':cooldown', true, 3600);

                            return;
                        }
                        $v6Id = reset($candidates)['id'];
                    }
                    if (! is_string($v6Id) || preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $v6Id) !== 1) {
                        return;
                    }
                    $requestedAt = microtime(true);
                    $response = ApiCall('https://api.sportsapipro.com/v6/football/match/'.$v6Id.'/team-stats', ['x-api-key' => GetSportApiProKey()], ['connect_timeout' => 3, 'timeout' => 15]);
                    $payload = is_array($response) ? ($response['data'] ?? $response) : [];
                    $changed = $controller->mergeCardStatsFallback($matchId, $v6Id, $payload, $requestedAt);
                    Cache::store('redis')->put($cacheKey.':cooldown', true, $changed ? 900 : 3600);
                } catch (Throwable $e) {
                    Cache::store('redis')->put($cacheKey.':cooldown', true, 3600);
                    Log::warning('Football card stats fallback failed', ['match_id' => $matchId, 'exception' => $e::class]);
                } finally {
                    $lock->release();
                }
            })->name('Football card stats fallback '.$matchId)->onQueue('default')->catch(static function (Throwable $e) use ($cacheKey, $owner): void {
                Cache::store('redis')->restoreLock($cacheKey.':pending', $owner)->release();
                Cache::store('redis')->put($cacheKey.':cooldown', true, 3600);
            });
        } catch (Throwable $e) {
            $pending?->release();
            Log::warning('Football card stats fallback could not be queued', ['match_id' => $matchId, 'exception' => $e::class]);
        }
    }

    public function mergeCardStatsFallback(int|string $matchId, string $v6Id, array $payload, float $requestedAt): bool
    {
        $stats = [];
        foreach (['103' => 'yellow_cards', '104' => 'red_cards', '102' => 'corners'] as $id => $name) {
            $values = $payload['matchStats'][0]['stats'][$id]['values'] ?? null;
            if (is_array($values) && is_numeric($values[0] ?? null) && is_numeric($values[1] ?? null)) {
                $stats[$name] = ['home' => max(0, (int) $values[0]), 'away' => max(0, (int) $values[1])];
            }
        }
        if ($stats === []) {
            return false;
        }
        $changed = withRedisLock('Football:matches', static function () use ($matchId, $v6Id, $stats, $requestedAt): array {
            $matches = getInRedis('Football:matches') ?? [];
            foreach ($matches as $key => $match) {
                if ((string) ($match['sportsApiPro_match_id'] ?? '') !== (string) $matchId || ($match['result_status'] ?? '') !== 'Live'
                    || ($match['incidents_initialized'] ?? false) || ($match['cards_updated_at'] ?? 0) > $requestedAt) {
                    continue;
                }
                $matches[$key]['sportsApiPro_v6_match_id'] = $v6Id;
                $matches[$key]['live_stats'] = array_replace($stats, $match['live_stats'] ?? []);
                foreach (array_keys(array_diff_key($stats, $match['live_stats'] ?? [])) as $stat) {
                    $matches[$key]['live_stats_sources'][$stat] = 'v6';
                }
                $matches[$key]['card_stats_fallback_at'] = $requestedAt;
                $matches[$key]['card_stats_updated_at'] = $requestedAt;
                if (! isset($match['corners']) && isset($stats['corners'])) {
                    $matches[$key]['corners'] = $stats['corners'];
                }
                storeInRedis('Football:matches', $matches, 30);

                return [$key => $matches[$key]];
            }

            return [];
        });
        if ($changed !== []) {
            $this->storeMatchesInDatabase($changed);
            Event::dispatch(new FootballSportsScoreUpdated);
        }

        return $changed !== [];
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
                $opening = meaningfulOpeningOdds(['backOdds' => $from], $state, $at);
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
                $matches[$key]['pinnacle_markets'][$selection] = array_merge($display, $opening, [
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
        $mainArray = array_filter($getExistingRecords, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Finished');
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
            if (($getExistingRecords[$existingKey ?? $payloadUniqueKey]['result_status'] ?? null) === 'Finished') {
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
            if ($checkIsExists !== null && ($getExistingRecords[$checkIsExists]['result_status'] ?? null) === 'Finished') {
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
            $databaseMatches = array_filter($getExistingRecords, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Finished');

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
                    : 'Prematch';
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
