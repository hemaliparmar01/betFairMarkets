<?php

namespace App\Http\Controllers\Users\Cricket;

use App\Http\Controllers\Controller;
use App\Models\CricketMarket;
use App\Models\CricketMatch;
use App\Services\MatchBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CricketSyncController extends Controller
{
    public string $getBetferKeys;

    public string $getsportsAPIProKeys;

    public string $sports;

    public function __construct()
    {
        $this->getBetferKeys = GetBetfairKey();
        $this->getsportsAPIProKeys = GetSportApiProKey();
        $this->sports = 'Cricket';
    }

    public function syncBetFairLiveData(): array
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=live&sports=cricket';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key) ?? [];
        $cacheKey = $this->sports.':matches';

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, true, 10),
            30
        );

        return $matches;
    }

    public function syncBetFairPrematchData(): array
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=prematch&sports=cricket';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key) ?? [];
        $cacheKey = $this->sports.':matches';

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, false, 30),
            30
        );

        return $matches;
    }

    public function syncSportsApiProLiveData(): array
    {
        $url = 'https://api.sportsapipro.com/v2/cricket/api/live';
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $liveData = ApiCall($url, $key, ['connect_timeout' => 3, 'timeout' => 15]) ?? [];
        $liveData = is_array($liveData['data'] ?? null) ? $liveData['data'] : ($liveData ?? []);
        $cacheKey = $this->sports.':matches';

        $matches = $this->readsportApiData($liveData, $cacheKey, true, 10);

        return $matches;
    }

    public function syncSportsApiProPrematchData(): array
    {
        $cacheKey = $this->sports.':matches';
        $date = Carbon::now('UTC')->format('Y-m-d');
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $scheduleData = ApiCall("https://api.sportsapipro.com/v2/cricket/api/schedule/{$date}", $key);
        $scheduleData = is_array($scheduleData['data'] ?? null) ? $scheduleData['data'] : ($scheduleData ?? []);
        if (! is_array($scheduleData['events'] ?? null)) {
            return [];
        }

        $matches = $this->readsportApiData(['events' => $scheduleData['events']], $cacheKey, false, 30);

        return $matches;
    }

    private function readBetFairData(array $payloads, string $cacheKey, bool $isLiveApi, int $ttlSeconds): array
    {
        $getExistingRecords = getInRedis($cacheKey) ?? [];
        $missingIds = array_keys(array_filter($payloads['liveMap'] ?? [], static fn (array $event, string|int $id): bool => ! array_any($getExistingRecords, static fn (array $match): bool => (string) ($match['betfair_match_id'] ?? '') === (string) $id), ARRAY_FILTER_USE_BOTH));
        if ($missingIds !== []) {
            foreach (CricketMatch::query()->with('markets')->whereIn('betfair_match_id', $missingIds)->get() as $stored) {
                $restored = $stored->payload ?? [];
                $restored['markets'] = [];
                foreach ($stored->markets as $runner) {
                    $market = $runner->payload;
                    $restored['markets'][$market['marketId'].'_'.$market['marketType']][implode('|', [$market['runnerId'] ?? $market['runner'] ?? '', $market['handicap'] ?? 0])] = $market;
                }
                $getExistingRecords[$stored->unique_key] = $restored;
            }
        }
        $mainArray = $getExistingRecords;
        $databaseMarkets = [];
        $databaseLookupKeys = [];

        foreach (($payloads['liveMap'] ?? []) as $payload) {
            if (! isset($payload['openTimestamp'], $payload['team1'], $payload['team2'])) {
                continue;
            }

            if (str_contains(strtolower($payload['team1'].' '.$payload['team2']), 'series betting')) {
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
            $databaseRows = CricketMarket::query()
                ->whereIn('cricket_match_unique_key', array_keys($databaseLookupKeys))
                ->get(['cricket_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $storedMarket = $databaseRow->payload;
                if (is_array($storedMarket)) {
                    $databaseMarkets[$databaseRow->cricket_match_unique_key][] = $storedMarket;
                }
            }
        }

        foreach (($payloads['liveMap'] ?? []) as $payloadKey => $payload) {
            if (! isset($payload['openTimestamp'], $payload['team1'], $payload['team2']) || strtolower($payload['sport'] ?? 'cricket') !== 'cricket') {
                continue;
            }
            if (str_contains(strtolower($payload['team1'].' '.$payload['team2']), 'series betting')) {
                continue;
            }
            $currentTimestamp = Carbon::parse($payload['openTimestamp'])->timestamp;
            if (! $isLiveApi && Carbon::createFromTimestamp($currentTimestamp, getCurrentUserTimezone())->toDateString() !== Carbon::now(getCurrentUserTimezone())->toDateString()) {
                continue;
            }
            $team1 = $payload['team1'];
            $team2 = $payload['team2'];
            $uniqueKey = $this->generateUniqueKey($team1, $team2, $currentTimestamp);

            $knownMatch = array_find($getExistingRecords, static fn (array $match): bool => (string) ($match['betfair_match_id'] ?? '') === (string) $payloadKey);
            $checkIsExists = $knownMatch['unique_key'] ?? $this->checkIfRecordExistsOnAnotherApi($uniqueKey, $team1, $team2, $currentTimestamp, $getExistingRecords);
            $uniqueKey = $checkIsExists ?? $uniqueKey;
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
            $result_status = ($tempArray['result_status'] ?? null) === 'Live' ? 'Live' : $currentStatus;
            $tempArray['unique_key'] = $uniqueKey;
            $tempArray['betfair_match_id'] = $payloadKey;
            $tempArray['team1'] ??= $team1;
            $tempArray['team2'] ??= $team2;
            $tempArray['tournament'] ??= $payload['tournament'] ?? 'Cricket';
            $tempArray['result_status'] = $result_status;
            $tempArray['minutes_status'] = $this->alignMinuteStatusWithResult(
                $tempArray['minutes_status'] ?? null,
                $result_status
            );
            $tempArray['timestamp'] ??= $currentTimestamp;
            $markets = $payload['markets'] ?? [];
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

                    if ($market['marketType'] === 'MATCH_ODDS' || str_ends_with($market['marketType'], '_RUNS')) {
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
        $changedMatches = array_filter($mainArray, static fn (array $match, string $key): bool => $match != ($getExistingRecords[$key] ?? null), ARRAY_FILTER_USE_BOTH);
        $this->storeMatchesInDatabase($changedMatches);
        storeInRedis($cacheKey, $mainArray, $ttlSeconds);

        return $mainArray;
    }

    private function readsportApiData(array $payloads, string $cacheKey, bool $isLive, int $ttlSeconds): array
    {
        $events = $payloads['events'] ?? [];
        if (! is_array($events) || $events === []) {
            return [];
        }
        $existingMatches = getInRedis($cacheKey) ?? [];
        $events = array_values(array_filter($events, function (array $event) use ($isLive, $existingMatches): bool {
            $timestamp = (int) ($event['startTimestamp'] ?? 0);
            $home = $event['homeTeam']['name'] ?? '';
            $away = $event['awayTeam']['name'] ?? '';
            if ($timestamp <= 0 || $home === '' || $away === '') {
                return false;
            }
            $known = array_find($existingMatches, static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) ($event['id'] ?? $event['eventId'] ?? ''));
            if (($known['result_status'] ?? null) === 'Finished') {
                return false;
            }
            if ($isLive) {
                return true;
            }
            $existingKey = $this->checkIfRecordExistsOnAnotherApi($this->generateUniqueKey($home, $away, $timestamp), $home, $away, $timestamp, $existingMatches);
            if ($existingKey !== null && isset($existingMatches[$existingKey]['betfair_match_id'])) {
                return ($existingMatches[$existingKey]['result_status'] ?? null) !== 'Finished';
            }

            return $timestamp >= time() && ($event['status']['type'] ?? 'notstarted') === 'notstarted'
                && Carbon::createFromTimestamp($timestamp, getCurrentUserTimezone())->isToday();
        }));
        if ($events === []) {
            return [];
        }
        $sportsImages = [];
        foreach ($events as $event) {
            foreach (['teams' => [$event['homeTeam']['id'] ?? null, $event['awayTeam']['id'] ?? null], 'tournaments' => [$event['tournament']['id'] ?? null]] as $type => $ids) {
                foreach (array_unique($ids) as $entityId) {
                    if (! is_numeric($entityId) || (int) $entityId <= 0 || array_key_exists($type.':'.$entityId, $sportsImages)) {
                        continue;
                    }
                    $imageKey = $type.':'.$entityId;
                    $sportsImages[$imageKey] = getSportsApiProImage('cricket', $type, $entityId);
                }
            }
        }

        return withRedisLock($cacheKey, function () use ($events, $cacheKey, $isLive, $ttlSeconds, $sportsImages): array {
            $matches = getInRedis($cacheKey) ?? [];
            $knownIds = array_column($matches, 'sportsApiPro_match_id');
            $missingIds = array_diff(array_column($events, 'id'), $knownIds);
            if ($missingIds !== []) {
                foreach (CricketMatch::query()->with('markets')->whereIn('sports_api_pro_match_id', $missingIds)->get() as $stored) {
                    $restored = $stored->payload ?? [];
                    $restored['markets'] = [];
                    foreach ($stored->markets as $runner) {
                        $market = $runner->payload;
                        $restored['markets'][$market['marketId'].'_'.$market['marketType']][implode('|', [$market['runnerId'] ?? $market['runner'] ?? '', $market['handicap'] ?? 0])] = $market;
                    }
                    $matches[$stored->unique_key] = $restored;
                }
            }
            $changedMatches = [];
            foreach ($events as $event) {
                $matchId = $event['id'] ?? $event['eventId'] ?? null;
                $homeTeam = $event['homeTeam']['name'] ?? null;
                $awayTeam = $event['awayTeam']['name'] ?? null;
                $timestamp = (int) ($event['startTimestamp'] ?? 0);
                if ($matchId === null || ! is_string($homeTeam) || ! is_string($awayTeam) || $timestamp <= 0
                ) {
                    continue;
                }
                $matchKey = null;
                foreach ($matches as $key => $candidate) {
                    if ((string) ($candidate['sportsApiPro_match_id'] ?? '') === (string) $matchId) {
                        $matchKey = $key;
                        break;
                    }
                }
                $uniqueKey = $this->generateUniqueKey($homeTeam, $awayTeam, (string) $timestamp);
                $matchKey ??= $this->checkIfRecordExistsOnAnotherApi($uniqueKey, $homeTeam, $awayTeam, $timestamp, $matches) ?? $uniqueKey;
                if (($matches[$matchKey]['result_status'] ?? null) === 'Finished') {
                    continue;
                }
                $before = $matches[$matchKey] ?? null;
                $matches[$matchKey] ??= ['unique_key' => $matchKey, 'team1' => $homeTeam, 'team2' => $awayTeam, 'timestamp' => $timestamp, 'markets' => [], 'result_status' => $isLive ? 'Live' : 'Prematch'];
                $matches[$matchKey]['sportsApiPro_match_id'] = $matchId;
                $matches[$matchKey]['data_sync_complete'] = isset($matches[$matchKey]['betfair_match_id']);
                $matches[$matchKey]['tournament'] = $event['tournament']['name'] ?? ($matches[$matchKey]['tournament'] ?? 'Cricket');
                $this->applySportsApiEvent($matches[$matchKey], $event, 'rest');
                foreach (['home_team_logo' => ['teams', 'home_team_id'], 'away_team_logo' => ['teams', 'away_team_id'], 'tournament_logo' => ['tournaments', 'tournament_id']] as $field => [$type, $idKey]) {
                    $matches[$matchKey][$field] = $sportsImages[$type.':'.($matches[$matchKey][$idKey] ?? '')] ?? ($matches[$matchKey][$field] ?? null);
                }
                if ($before != $matches[$matchKey]) {
                    $changedMatches[$matchKey] = $matches[$matchKey];
                }
            }
            $this->storeMatchesInDatabase($changedMatches);
            if ($matches !== []) {
                storeInRedis($cacheKey, $matches, $ttlSeconds);
            }

            return $changedMatches;
        }, 30);
    }

    public function mergeBetfairWebSocketData(array $events, bool $isLive): bool
    {
        if ($events === []) {
            return false;
        }
        $matches = withRedisLock('Cricket:matches', function () use ($events, $isLive): array {
            $before = getInRedis('Cricket:matches') ?? [];
            $after = $this->readBetFairData(['liveMap' => $events], 'Cricket:matches', $isLive, 30);

            return array_filter($after, static fn (array $match, string $key): bool => $match != ($before[$key] ?? null), ARRAY_FILTER_USE_BOTH);
        }, 5);

        return $matches !== [];
    }

    public function applySportsApiEvent(array &$match, array $event, string $channel): bool
    {
        if (($match['result_status'] ?? null) === 'Finished') {
            return false;
        }
        if (MatchBackup::isOlderEvent($match, $event) && ! in_array($event['status']['type'] ?? $event['status.type'] ?? null, ['finished', 'abandoned'], true) && ($event['status']['code'] ?? $event['status.code'] ?? null) != 100) {
            return false;
        }
        $before = $match;
        $type = $this->eventValue($event, 'status.type');
        $code = $this->eventValue($event, 'status.code');
        $changeTimestamp = $this->eventValue($event, 'changes.changeTimestamp');
        if (is_numeric($changeTimestamp) && (int) $changeTimestamp > 0) {
            $previousTimestamp = max($match['websocket_change_timestamps'][$channel] ?? 0, $match['sports_change_timestamp'] ?? 0);
            if ($previousTimestamp > 0 && $changeTimestamp <= $previousTimestamp && $type !== 'finished' && (int) $code !== 100) {
                return false;
            }
            $match['websocket_change_timestamps'][$channel] = $changeTimestamp;
            $match['sports_change_timestamp'] = max($match['sports_change_timestamp'] ?? 0, $changeTimestamp);
        }
        $homeTeamName = $this->eventValue($event, 'homeTeam.name');
        if (is_string($homeTeamName)) {
            $match['sports_api_home_is_team1'] = $this->normalizeTeamName($homeTeamName) !== $this->normalizeTeamName((string) ($match['team2'] ?? ''));
        }
        $homeIsTeam1 = $match['sports_api_home_is_team1'] ?? true;
        $scoreSides = $homeIsTeam1 ? ['home' => 'homeScore', 'away' => 'awayScore'] : ['home' => 'awayScore', 'away' => 'homeScore'];
        $updatedSides = [];
        foreach ($scoreSides as $side => $scoreKey) {
            $score = $this->eventValue($event, $scoreKey);
            $patch = is_array($score) ? $score : [];
            foreach (['current', 'display', 'innings', 'period1', 'period2', 'period3', 'period4', 'wickets', 'overs'] as $key) {
                $value = $this->eventValue($event, $scoreKey.'.'.$key);
                if ($value !== null) {
                    $patch[$key] = $value;
                }
            }
            foreach ($event as $key => $value) {
                if (str_starts_with((string) $key, $scoreKey.'.') && $value !== null) {
                    data_set($patch, substr($key, strlen($scoreKey) + 1), $value);
                }
            }
            if ($patch !== []) {
                $match[$side.'_score_data'] = array_replace_recursive($match[$side.'_score_data'] ?? [], $patch);
                $match[$side.'_score'] = $match[$side.'_score_data']['current'] ?? $match[$side.'_score_data']['display'] ?? ($match[$side.'_score'] ?? null);
                if (($before[$side.'_score_data']['innings'] ?? []) != ($match[$side.'_score_data']['innings'] ?? [])) {
                    $updatedSides[] = $side;
                }
            }
        }
        foreach (['home_team_id' => $homeIsTeam1 ? 'homeTeam.id' : 'awayTeam.id', 'away_team_id' => $homeIsTeam1 ? 'awayTeam.id' : 'homeTeam.id', 'tournament_id' => 'tournament.id', 'country_id' => 'tournament.category.country.id', 'country_name' => 'tournament.category.country.name', 'country_code' => 'tournament.category.country.alpha2', 'winner_code' => 'winnerCode'] as $field => $key) {
            $value = $this->eventValue($event, $key);
            if ($field === 'country_name' && $value === null && preg_match('/^[A-Z]{2}$/i', (string) $this->eventValue($event, 'tournament.category.country.alpha2')) === 1) {
                $value = $this->eventValue($event, 'tournament.category.name');
            }
            if ($value !== null) {
                $match[$field] = $field === 'winner_code' && ! $homeIsTeam1 && in_array((int) $value, [1, 2], true) ? 3 - (int) $value : $value;
            }
        }
        $battingTeamId = $this->eventValue($event, 'battingTeamId') ?? $this->eventValue($event, 'battingTeam.id');
        $battingSide = is_numeric($battingTeamId) && (int) $battingTeamId > 0
            ? ((string) $battingTeamId === (string) ($match['home_team_id'] ?? '') ? 'home'
                : ((string) $battingTeamId === (string) ($match['away_team_id'] ?? '') ? 'away' : null)) : null;
        if ($battingSide !== null) {
            $match['cricket_batting_side'] = $battingSide;
        } elseif (count($updatedSides) === 1) {
            $match['cricket_batting_side'] = $updatedSides[0];
        }
        $populatedSides = array_values(array_filter(['home', 'away'], static fn (string $side): bool => array_any($match[$side.'_score_data']['innings'] ?? [], static fn (array $inning): bool => is_numeric($inning['score'] ?? null))));
        if (count($populatedSides) === 1) {
            $match['cricket_first_batting_side'] ??= $populatedSides[0];
        }
        $tossWinner = $this->eventValue($event, 'tossWinnerCode');
        if (in_array((int) $tossWinner, [1, 2], true)) {
            $match['toss_winner_side'] = ((int) $tossWinner === 1) === $homeIsTeam1 ? 'home' : 'away';
        }
        $decision = $this->eventValue($event, 'tossDecision');
        if (is_string($decision) && in_array(strtolower($decision), ['bat', 'batting', 'bowl', 'bowling', 'field', 'fielding'], true)) {
            $match['toss_decision'] = in_array(strtolower($decision), ['bat', 'batting'], true) ? 'bat' : 'bowl';
        }
        foreach (['tossText', 'toss.text', 'toss'] as $key) {
            $text = $this->eventValue($event, $key);
            if (is_string($text) && trim($text) !== '') {
                $match['toss_info'] = $text;
                break;
            }
        }
        if (isset($match['toss_winner_side'], $match['toss_decision'])) {
            $winner = $match['toss_winner_side'];
            $match['toss_info'] = ($match[$winner === 'home' ? 'team1' : 'team2'] ?? ucfirst($winner)).' won the toss and elected to '.$match['toss_decision'].'.';
            $match['cricket_first_batting_side'] = $match['toss_decision'] === 'bat' ? $winner : ($winner === 'home' ? 'away' : 'home');
        }
        if ($type === 'finished' || (int) $code === 100) {
            $match['result_status'] = 'Finished';
            $match['minutes_status'] = 'Finished';
        } elseif ($type === 'inprogress' || $type === 'willcontinue' || in_array((int) $code, [21, 22, 23, 24, 31], true)) {
            $match['result_status'] = 'Live';
            $match['minutes_status'] = match ((int) $code) {
                21 => '1st Innings', 22 => '2nd Innings', 23 => '3rd Innings', 24 => '4th Innings', 31 => 'Innings Break', default => $this->alignMinuteStatusWithResult($match['minutes_status'] ?? null, 'Live'),
            };
        } elseif ($type === 'notstarted' && ($match['result_status'] ?? null) !== 'Live') {
            $match['result_status'] = 'Prematch';
            $match['minutes_status'] = 'Prematch';
        }
        $match['minutes_status'] ??= $match['result_status'] ?? 'Prematch';
        if ($this->eventValue($event, 'status.description') !== null) {
            $match['match_status_description'] = $this->eventValue($event, 'status.description');
        }

        MatchBackup::observe($match, 'cricket', $event, $channel, $before);

        return $match !== $before;
    }

    public function mergeSportsWebSocketData(string $channel, array $frameData): bool
    {
        $matches = getInRedis('Cricket:matches') ?? [];
        $events = str_starts_with($channel, 'live-scores')
            ? (isset($frameData['id']) || isset($frameData['eventId']) ? [$frameData] : ($frameData['events'] ?? (array_is_list($frameData) ? $frameData : [])))
            : [$frameData];
        preg_match('/^match:(\d+)/', $channel, $channelMatch);
        $changed = [];
        foreach ($events as $event) {
            $eventId = $event['id'] ?? $event['eventId'] ?? ($channelMatch[1] ?? null);
            foreach ($matches as $key => &$match) {
                if (($match['result_status'] ?? null) === 'Finished' || (string) ($match['sportsApiPro_match_id'] ?? '') !== (string) $eventId) {
                    continue;
                }
                $before = $match;
                if (str_ends_with($channel, ':stats')) {
                    $statistics = $event['statistics'] ?? $event;
                    if (($match['sports_api_home_is_team1'] ?? true) === false && is_array($statistics)) {
                        foreach ($statistics as &$period) {
                            if (! is_array($period) || ! isset($period['groups'])) {
                                continue;
                            }
                            foreach ($period['groups'] as &$group) {
                                if (! isset($group['statisticsItems'])) {
                                    continue;
                                }
                                foreach ($group['statisticsItems'] as &$item) {
                                    foreach ([['home', 'away'], ['homeValue', 'awayValue']] as [$home, $away]) {
                                        [$item[$home], $item[$away]] = [$item[$away] ?? null, $item[$home] ?? null];
                                    }
                                }
                                unset($item);
                            }
                            unset($group);
                        }
                        unset($period);
                    }
                    $match['live_stats'] = $statistics;
                } elseif (str_ends_with($channel, ':incidents')) {
                    $incidents = $event['incidents'] ?? $event['events'] ?? (array_is_list($event) ? $event : [$event]);
                    $latest = $incidents[0] ?? null;
                    if (is_array($latest) && isset($latest['battingTeamId'])) {
                        $this->applySportsApiEvent($match, ['battingTeamId' => $latest['battingTeamId']], $channel);
                    }
                    if (is_array($latest) && in_array($latest['incidentType'] ?? null, ['wicket', 'boundary', 'milestone'], true)) {
                        $incidentId = (string) ($latest['id'] ?? hash('sha256', json_encode($latest)));
                        if (($match['incidents_initialized'] ?? false) && ($match['last_incident']['id'] ?? null) !== $incidentId) {
                            $match['incident_highlight_until'] = time() + 40;
                        }
                        $match['last_incident'] = ['id' => $incidentId, 'type' => $latest['incidentType'], 'text' => $latest['text'] ?? ucfirst($latest['incidentType'])];
                    }
                    $match['incidents_initialized'] = true;
                } else {
                    $this->applySportsApiEvent($match, $event, $channel);
                }
                if ($before !== $match) {
                    $changed[$key] = $match;
                }
                break;
            }
            unset($match);
        }
        if ($changed !== []) {
            storeInRedis('Cricket:matches', $matches, 30, false);
            $this->storeMatchesInDatabase($changed);
        }

        return $changed !== [];
    }

    public function persistMatches(array $matches): void
    {
        $this->storeMatchesInDatabase($matches);
    }

    private function eventValue(array $event, string $key): mixed
    {
        return array_key_exists($key, $event) ? $event[$key] : data_get($event, $key);
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
                    ]));

                    $marketRows[$selectionKey] = [
                        'selection_key' => $selectionKey,
                        'cricket_match_unique_key' => $matchKey,
                        'market_id' => $market['marketId'] ?? null,
                        'market_type' => $market['marketType'] ?? null,
                        'runner_id' => $market['runnerId'] ?? null,
                        'runner' => $market['runner'] ?? null,
                        'sort_priority' => isset($market['sortPriority']) ? (int) $market['sortPriority'] : null,
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
            ->lock('Cricket:database-write-lock', 120)
            ->block(10, function () use ($matchRows, $marketRows, $matchKeys, $marketSnapshotKeys): void {
                $cleaned = array_fill_keys(Redis::zrangebyscore('Cricket:matches:cleaned', time(), '+inf'), true);
                if ($cleaned !== []) {
                    $matchRows = array_filter($matchRows, static fn (array $row): bool => ! isset($cleaned[$row['unique_key']]));
                    $marketRows = array_filter($marketRows, static fn (array $row): bool => ! isset($cleaned[$row['cricket_match_unique_key']]));
                    $matchKeys = array_values(array_diff($matchKeys, array_keys($cleaned)));
                    $marketSnapshotKeys = array_values(array_diff($marketSnapshotKeys, array_keys($cleaned)));
                }
                $existingMatches = CricketMatch::query()
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
                    if ($existing?->result_status === 'Finished') {
                        $marketSnapshotKeys = array_values(array_diff($marketSnapshotKeys, [$row['unique_key']]));
                        $marketRows = array_filter($marketRows, static fn (array $market): bool => $market['cricket_match_unique_key'] !== $row['unique_key']);

                        continue;
                    }
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    $incomingPayload = MatchBackup::merge($incomingPayload, $storedPayload ?? [], 'cricket');
                    $row['payload'] = json_encode($incomingPayload, JSON_THROW_ON_ERROR);
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
                    $existingMarkets = CricketMarket::query()
                        ->whereIn('cricket_match_unique_key', $marketSnapshotKeys)
                        ->get(['selection_key', 'cricket_match_unique_key', 'payload'])
                        ->keyBy('selection_key');
                }

                $changedMarketRows = [];
                foreach ($marketRows as $selectionKey => $row) {
                    $existing = $existingMarkets->get($selectionKey);
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    if (
                        $existing === null
                        || $existing->cricket_match_unique_key !== $row['cricket_match_unique_key']
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
                        CricketMatch::query()->upsert(
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
                        CricketMarket::query()
                            ->whereIn('selection_key', $selectionChunk)
                            ->delete();
                    }
                }

                if ($changedMarketRows !== []) {
                    usort($changedMarketRows, static fn (array $left, array $right): int => $left['selection_key'] <=> $right['selection_key']);
                    foreach (array_chunk($changedMarketRows, 200) as $marketChunk) {
                        CricketMarket::query()->upsert(
                            $marketChunk,
                            ['selection_key'],
                            [
                                'cricket_match_unique_key',
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

    private function alignMinuteStatusWithResult(?string $minuteStatus, string $resultStatus): string
    {
        return $resultStatus === 'Live' ? ($minuteStatus && $minuteStatus !== 'Prematch' ? $minuteStatus : 'Live') : $resultStatus;
    }

    private function matchDirection(float $previousOdds, float $currentOdds): string
    {
        if ($currentOdds == $previousOdds) {
            return 'same';
        } elseif ($currentOdds < $previousOdds) {
            return 'down';
        } elseif ($currentOdds > $previousOdds) {
            return 'up';
        }
    }

    private function generateUniqueKey(string $teamA, string $teamB, string|int $timestamp): string
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

    private function checkIfRecordExistsOnAnotherApi(string $uniqueKey, string $team1, string $team2, string|int $timestamp, ?array $existingRecords = null): ?string
    {
        $existingRecords ??= getInRedis($this->sports.':matches') ?? [];

        if (array_key_exists($uniqueKey, $existingRecords)) {
            return $uniqueKey;
        }

        $liveCandidates = [];
        foreach ($existingRecords as $key => $record) {
            $direct = $this->teamSimilarity($record['team1'] ?? '', $team1) >= 80 && $this->teamSimilarity($record['team2'] ?? '', $team2) >= 80;
            $reversed = $this->teamSimilarity($record['team1'] ?? '', $team2) >= 80 && $this->teamSimilarity($record['team2'] ?? '', $team1) >= 80;
            if (! $direct && ! $reversed) {
                continue;
            }
            if ((int) ($record['timestamp'] ?? 0) === (int) $timestamp) {
                return $key;
            }
            if (($record['result_status'] ?? null) === 'Live' && abs((int) ($record['timestamp'] ?? 0) - (int) $timestamp) <= 6 * 86400) {
                $liveCandidates[] = $key;
            }
        }
        if (count($liveCandidates) === 1) {
            return $liveCandidates[0];
        }

        return null;
    }

    private function teamSimilarity(string $teamA, string $teamB): float
    {
        $teamA = $this->normalizeTeamName($teamA);
        $teamB = $this->normalizeTeamName($teamB);

        if ($teamA === $teamB || preg_replace('/_bulls$/', '', $teamA) === preg_replace('/_bulls$/', '', $teamB)) {
            return 100;
        }

        similar_text($teamA, $teamB, $percentage);

        return $percentage;
    }
}
