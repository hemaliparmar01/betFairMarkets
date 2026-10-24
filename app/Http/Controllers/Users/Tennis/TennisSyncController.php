<?php

namespace App\Http\Controllers\Users\Tennis;

use App\Http\Controllers\Controller;
use App\Models\TennisMarket;
use App\Models\TennisMatch;
use App\Services\MatchBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TennisSyncController extends Controller
{
    public string $getBetferKeys;

    public string $getsportsAPIProKeys;

    public string $sports;

    public function __construct()
    {
        $this->getBetferKeys = GetBetfairKey();
        $this->getsportsAPIProKeys = GetSportApiProKey();
        $this->sports = 'Tennis';
    }

    public function syncBetFairLiveData(): array
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=live&sports=tennis';
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
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=prematch&sports=tennis';
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
        $url = 'https://api.sportsapipro.com/v2/tennis/api/live/all';
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
        $scheduleData = ApiCall("https://api.sportsapipro.com/v2/tennis/api/schedule/{$date}", $key);
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
        $knownBetfairMatches = array_column($getExistingRecords, null, 'betfair_match_id');
        $missingIds = array_diff(array_keys($payloads['liveMap'] ?? []), array_keys($knownBetfairMatches));
        if ($missingIds !== []) {
            foreach (TennisMatch::query()->with('markets')->whereIn('betfair_match_id', $missingIds)->get() as $stored) {
                $restored = $stored->payload ?? [];
                $restored['markets'] = [];
                foreach ($stored->markets as $runner) {
                    $market = $runner->payload;
                    $restored['markets'][$market['marketId'].'_'.$market['marketType']][implode('|', [$market['runnerId'] ?? $market['runner'] ?? '', $market['handicap'] ?? 0])] = $market;
                }
                $getExistingRecords[$stored->unique_key] = $restored;
                $knownBetfairMatches[(string) $stored->betfair_match_id] = $restored;
            }
        }
        foreach ($payloads['liveMap'] ?? [] as $id => $payload) {
            if (empty($payload['openTimestamp']) && ! empty($knownBetfairMatches[$id]['timestamp'])) {
                $payloads['liveMap'][$id]['openTimestamp'] = Carbon::createFromTimestampUTC((int) $knownBetfairMatches[$id]['timestamp'])->toIso8601String();
            }
        }
        $mainArray = $getExistingRecords;
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
            $databaseRows = TennisMarket::query()
                ->whereIn('tennis_match_unique_key', array_keys($databaseLookupKeys))
                ->get(['tennis_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $storedMarket = $databaseRow->payload;
                if (is_array($storedMarket)) {
                    $databaseMarkets[$databaseRow->tennis_match_unique_key][] = $storedMarket;
                }
            }
        }

        foreach (($payloads['liveMap'] ?? []) as $payloadKey => $payload) {
            if (! isset($payload['openTimestamp'], $payload['team1'], $payload['team2']) || strtolower($payload['sport'] ?? 'tennis') !== 'tennis') {
                continue;
            }
            $currentTimestamp = Carbon::parse($payload['openTimestamp'])->timestamp;
            if (! $isLiveApi && Carbon::createFromTimestamp($currentTimestamp, getCurrentUserTimezone())->toDateString() !== Carbon::now(getCurrentUserTimezone())->toDateString()) {
                continue;
            }
            $team1 = $payload['team1'];
            $team2 = $payload['team2'];
            $uniqueKey = $this->generateUniqueKey($team1, $team2, $currentTimestamp);

            $knownMatch = $knownBetfairMatches[$payloadKey] ?? null;
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
            $tempArray['is_virtual'] = (bool) preg_match('/\b(?:cyber|virtual|esports?)\b/i', ($payload['team1'] ?? '').' '.($payload['team2'] ?? '').' '.($payload['tournament'] ?? ''));
            $tempArray['team1'] ??= $team1;
            $tempArray['team2'] ??= $team2;
            $tempArray['tournament'] ??= $payload['tournament'] ?? 'Tennis';
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

                    if (in_array($market['marketType'], ['MATCH_ODDS', 'NUMBER_OF_SETS', 'COMBINED_TOTAL', 'HANDICAP', 'SET_WINNER', 'SET_BETTING'], true)) {
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
                            in_array($runner['marketType'] ?? '', ['MATCH_ODDS'], true)
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
        $events = array_map(static function (array $event) use ($isLive): array {
            foreach (['home', 'away'] as $side) {
                $team = $event[$side.'Team'] ?? null;
                if (is_string($team)) {
                    $event[$side.'Team'] = ['name' => $team, 'id' => $event[$side.'TeamId'] ?? null];
                }
            }
            if (is_string($event['status'] ?? null)) {
                $code = (int) ($event['statusCode'] ?? 0);
                $event['status'] = ['description' => $event['status'], 'code' => $code, 'type' => $event['statusType'] ?? match ($code) {
                    80, 90, 91, 92, 93, 100, 110 => 'finished', 60 => 'postponed', 70 => 'cancelled', 8, 9, 10, 11, 12 => 'inprogress', default => $isLive ? 'inprogress' : 'notstarted',
                }];
            }

            $tournament = is_array($event['tournament'] ?? null) ? ($event['tournament']['name'] ?? '').' '.($event['tournament']['uniqueTournament']['name'] ?? '').' '.($event['tournament']['category']['name'] ?? '') : ($event['tournament'] ?? '');
            $event['is_virtual'] = (bool) preg_match('/\b(?:cyber|virtual|esports?)\b/i', ($event['homeTeam']['name'] ?? '').' '.($event['awayTeam']['name'] ?? '').' '.$tournament);

            return $event;
        }, $events);
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
            $existingKey = $this->checkIfRecordExistsOnAnotherApi($this->generateUniqueKey($home, $away, $timestamp), $home, $away, $timestamp, $existingMatches, ['is_virtual' => $event['is_virtual'], 'home' => [$home, $event['homeTeam']['shortName'] ?? ''], 'away' => [$away, $event['awayTeam']['shortName'] ?? '']]);
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
            $imageMatch = array_find($existingMatches, static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === (string) ($event['id'] ?? $event['eventId'] ?? '') && isset($match['betfair_match_id']));
            $imageMatchKey = $imageMatch['unique_key'] ?? $this->checkIfRecordExistsOnAnotherApi(
                $this->generateUniqueKey($event['homeTeam']['name'], $event['awayTeam']['name'], (int) $event['startTimestamp']),
                $event['homeTeam']['name'], $event['awayTeam']['name'], (int) $event['startTimestamp'], $existingMatches,
                ['is_virtual' => $event['is_virtual'], 'home' => [$event['homeTeam']['name'], $event['homeTeam']['shortName'] ?? ''], 'away' => [$event['awayTeam']['name'], $event['awayTeam']['shortName'] ?? '']]
            );
            if ($imageMatchKey === null || ! isset($existingMatches[$imageMatchKey]['betfair_match_id'])) {
                continue;
            }
            foreach (['teams' => [$event['homeTeam']['id'] ?? null, $event['awayTeam']['id'] ?? null], 'tournaments' => [is_array($event['tournament'] ?? null) ? ($event['tournament']['uniqueTournament']['id'] ?? $event['tournament']['id'] ?? null) : ($event['tournamentId'] ?? null)]] as $type => $ids) {
                foreach (array_unique($ids) as $entityId) {
                    if (! is_numeric($entityId) || (int) $entityId <= 0 || array_key_exists($type.':'.$entityId, $sportsImages)) {
                        continue;
                    }
                    $imageKey = $type.':'.$entityId;
                    $sportsImages[$imageKey] = getSportsApiProImage('tennis', $type, $entityId);
                }
            }
        }

        return withRedisLock($cacheKey, function () use ($events, $cacheKey, $isLive, $ttlSeconds, $sportsImages): array {
            $matches = getInRedis($cacheKey) ?? [];
            $knownIds = array_column($matches, 'sportsApiPro_match_id');
            $missingIds = array_diff(array_column($events, 'id'), $knownIds);
            if ($missingIds !== []) {
                foreach (TennisMatch::query()->with('markets')->whereIn('sports_api_pro_match_id', $missingIds)->get() as $stored) {
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
                $matchKey ??= $this->checkIfRecordExistsOnAnotherApi($uniqueKey, $homeTeam, $awayTeam, $timestamp, $matches, ['is_virtual' => $event['is_virtual'], 'home' => [$homeTeam, $event['homeTeam']['shortName'] ?? ''], 'away' => [$awayTeam, $event['awayTeam']['shortName'] ?? '']]) ?? $uniqueKey;
                if (($matches[$matchKey]['result_status'] ?? null) === 'Finished') {
                    continue;
                }
                $before = $matches[$matchKey] ?? null;
                $matches[$matchKey] ??= ['unique_key' => $matchKey, 'team1' => $homeTeam, 'team2' => $awayTeam, 'timestamp' => $timestamp, 'markets' => [], 'result_status' => $isLive ? 'Live' : 'Prematch'];
                $matches[$matchKey]['sportsApiPro_match_id'] = $matchId;
                $matches[$matchKey]['is_virtual'] = $event['is_virtual'];
                $matches[$matchKey]['data_sync_complete'] = isset($matches[$matchKey]['betfair_match_id']);
                $matches[$matchKey]['tournament'] = (is_array($event['tournament'] ?? null) ? ($event['tournament']['name'] ?? null) : ($event['tournament'] ?? null)) ?? ($matches[$matchKey]['tournament'] ?? 'Tennis');
                $this->applySportsApiEvent($matches[$matchKey], $event, 'rest');
                foreach (['home_team_logo' => ['teams', 'home_team_id'], 'away_team_logo' => ['teams', 'away_team_id'], 'tournament_logo' => ['tournaments', 'tournament_image_id']] as $field => [$type, $idKey]) {
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
        $matches = withRedisLock('Tennis:matches', function () use ($events, $isLive): array {
            $before = getInRedis('Tennis:matches') ?? [];
            $after = $this->readBetFairData(['liveMap' => $events], 'Tennis:matches', $isLive, 30);

            return array_filter($after, static fn (array $match, string $key): bool => $match != ($before[$key] ?? null), ARRAY_FILTER_USE_BOTH);
        }, 5);

        return $matches !== [];
    }

    public function applySportsApiEvent(array &$match, array $event, string $channel): bool
    {
        if (($match['result_status'] ?? null) === 'Finished') {
            return false;
        }
        foreach (['home', 'away'] as $side) {
            if (is_string($event[$side.'Team'] ?? null)) {
                $event[$side.'Team'] = ['name' => $event[$side.'Team'], 'id' => $event[$side.'TeamId'] ?? null];
            }
        }
        if (is_string($event['status'] ?? null)) {
            $code = (int) ($event['statusCode'] ?? 0);
            $event['status'] = ['description' => $event['status'], 'code' => $code, 'type' => $event['statusType'] ?? match (true) {
                $code >= 80 && $code <= 100, $code === 110 => 'finished', $code === 60 => 'postponed', $code === 70 => 'cancelled', $code >= 8 && $code <= 12 => 'inprogress', default => 'notstarted',
            }];
        }
        if (MatchBackup::isOlderEvent($match, $event) && ! in_array($event['status']['type'] ?? $event['status.type'] ?? null, ['finished', 'abandoned'], true) && ($event['status']['code'] ?? $event['status.code'] ?? null) != 100) {
            return false;
        }
        $before = $match;
        $type = $this->eventValue($event, 'status.type');
        $code = $this->eventValue($event, 'status.code');
        $terminal = in_array($type, ['finished', 'abandoned'], true) || ($code !== null && ((int) $code >= 80 && (int) $code <= 100 || (int) $code === 110));
        $changeTimestamp = $this->eventValue($event, 'changes.changeTimestamp');
        if (is_numeric($changeTimestamp) && (int) $changeTimestamp > 0) {
            $previousTimestamp = max($match['websocket_change_timestamps'][$channel] ?? 0, $match['sports_change_timestamp'] ?? 0);
            if ($previousTimestamp > 0 && (int) $changeTimestamp < $previousTimestamp && ! $terminal) {
                return false;
            }
            $match['websocket_change_timestamps'][$channel] = (int) $changeTimestamp;
            $match['sports_change_timestamp'] = max($match['sports_change_timestamp'] ?? 0, (int) $changeTimestamp);
        }
        $homeName = $this->eventValue($event, 'homeTeam.name');
        if (is_string($homeName)) {
            $shortName = (string) ($this->eventValue($event, 'homeTeam.shortName') ?? $homeName);
            $match['sports_api_home_is_team1'] = max($this->teamSimilarity($homeName, (string) ($match['team1'] ?? '')), $this->teamSimilarity($shortName, (string) ($match['team1'] ?? ''))) >= max($this->teamSimilarity($homeName, (string) ($match['team2'] ?? '')), $this->teamSimilarity($shortName, (string) ($match['team2'] ?? '')));
        }
        $homeIsTeam1 = $match['sports_api_home_is_team1'] ?? true;
        $scoreSides = $homeIsTeam1 ? ['home' => 'homeScore', 'away' => 'awayScore'] : ['home' => 'awayScore', 'away' => 'homeScore'];
        $pointPatches = [];
        foreach ($scoreSides as $side => $scoreKey) {
            $score = $this->eventValue($event, $scoreKey);
            $patch = is_array($score) ? $score : [];
            foreach (['current', 'display', 'period1', 'period2', 'period3', 'period4', 'period5', 'period1TieBreak', 'period2TieBreak', 'period3TieBreak', 'period4TieBreak', 'period5TieBreak', 'point', 'normaltime'] as $key) {
                $value = $this->eventValue($event, $scoreKey.'.'.$key);
                if ($value !== null) {
                    $patch[$key] = $value;
                }
            }
            foreach ($event as $key => $value) {
                if (str_starts_with((string) $key, $scoreKey.'.')) {
                    data_set($patch, substr($key, strlen($scoreKey) + 1), $value);
                }
            }
            $pointPatches[$side] = array_key_exists('point', $patch);
            if ($patch !== []) {
                if (! array_key_exists('current', $patch) && isset($patch['display'])) {
                    $patch['current'] = $patch['display'];
                }
                $match[$side.'_score_data'] = array_replace_recursive($match[$side.'_score_data'] ?? [], $patch);
                $match[$side.'_score'] = $match[$side.'_score_data']['current'] ?? $match[$side.'_score_data']['display'] ?? ($match[$side.'_score'] ?? null);
            }
        }
        foreach (['home_team_id' => $homeIsTeam1 ? 'homeTeam.id' : 'awayTeam.id', 'away_team_id' => $homeIsTeam1 ? 'awayTeam.id' : 'homeTeam.id', 'home_team_short_name' => $homeIsTeam1 ? 'homeTeam.shortName' : 'awayTeam.shortName', 'away_team_short_name' => $homeIsTeam1 ? 'awayTeam.shortName' : 'homeTeam.shortName', 'home_ranking' => $homeIsTeam1 ? 'homeTeam.ranking' : 'awayTeam.ranking', 'away_ranking' => $homeIsTeam1 ? 'awayTeam.ranking' : 'homeTeam.ranking', 'home_country_code' => $homeIsTeam1 ? 'homeTeam.country.alpha2' : 'awayTeam.country.alpha2', 'away_country_code' => $homeIsTeam1 ? 'awayTeam.country.alpha2' : 'homeTeam.country.alpha2', 'tournament_id' => 'tournament.id', 'tournament_image_id' => 'tournament.uniqueTournament.id', 'winner_code' => 'winnerCode', 'country_id' => 'tournament.category.country.id', 'country_name' => 'tournament.category.country.name', 'country_code' => 'tournament.category.country.alpha2', 'ground_type' => 'groundType', 'last_period' => 'lastPeriod'] as $field => $key) {
            $value = $this->eventValue($event, $key);
            if ($field === 'country_name' && $value === null && preg_match('/^[A-Z]{2}$/i', (string) $this->eventValue($event, 'tournament.category.country.alpha2')) === 1) {
                $value = $this->eventValue($event, 'tournament.category.name');
            }
            if ($field === 'tournament_id' || $field === 'tournament_image_id') {
                $value ??= $event['tournamentId'] ?? null;
            }
            if ($field === 'ground_type') {
                $value ??= $this->eventValue($event, 'tournament.uniqueTournament.groundType');
            }
            if ($value !== null) {
                $match[$field] = $field === 'winner_code' && ! $homeIsTeam1 && in_array((int) $value, [1, 2], true) ? 3 - (int) $value : $value;
            }
        }
        $serving = $this->eventValue($event, 'firstToServe');
        if ($serving !== null) {
            $match['serving_side'] = in_array((int) $serving, [1, 2], true) ? ((int) $serving === ($homeIsTeam1 ? 1 : 2) ? 'home' : 'away') : null;
        }
        $periods = $this->eventValue($event, 'periods');
        if (is_array($periods)) {
            $match['period_labels'] = array_replace($match['period_labels'] ?? [], $periods);
        }
        if ($terminal) {
            $match['result_status'] = 'Finished';
            $description = strtolower((string) $this->eventValue($event, 'status.description'));
            $match['minutes_status'] = str_contains($description, 'walkover') || (int) $code === 91 ? 'Walkover' : (str_contains($description, 'retired') || in_array((int) $code, [80, 92], true) ? 'Retired' : 'Finished');
            unset($match['serving_side']);
        } elseif (in_array($type, ['postponed', 'cancelled', 'canceled'], true) || in_array((int) $code, [60, 70], true)) {
            $match['result_status'] = 'Prematch';
            $match['minutes_status'] = in_array($type, ['cancelled', 'canceled'], true) || (int) $code === 70 ? 'Cancelled' : 'Postponed';
        } elseif (in_array($type, ['inprogress', 'willcontinue', 'interrupted'], true) || (int) $code >= 8 && (int) $code <= 12) {
            $match['result_status'] = 'Live';
            $match['minutes_status'] = $type === 'interrupted' ? 'Delayed' : match ((int) $code) {
                8 => '1st Set', 9 => '2nd Set', 10 => '3rd Set', 11 => '4th Set', 12 => '5th Set', default => $this->alignMinuteStatusWithResult($match['minutes_status'] ?? null, 'Live'),
            };
        } elseif ($type === 'notstarted' && ($match['result_status'] ?? null) !== 'Live') {
            $match['result_status'] = 'Prematch';
            $match['minutes_status'] = 'Prematch';
        }
        if (($match['result_status'] ?? null) === 'Live') {
            $description = strtolower((string) $this->eventValue($event, 'status.description'));
            if (preg_match('/^([1-5])(?:st|nd|rd|th) set$/', $description, $set)) {
                $match['current_set'] = (int) $set[1];
                $match['minutes_status'] = ucfirst($description);
            } elseif ((int) $code >= 8 && (int) $code <= 12) {
                $match['current_set'] = (int) $code - 7;
            } elseif ($this->eventValue($event, 'lastPeriod') !== null && preg_match('/^period([1-5])$/', (string) ($match['last_period'] ?? ''), $set)) {
                $match['current_set'] = (int) $set[1];
                if ($type !== 'interrupted') {
                    $match['minutes_status'] = ['1st Set', '2nd Set', '3rd Set', '4th Set', '5th Set'][(int) $set[1] - 1];
                }
            }
            if (($before['current_set'] ?? null) !== ($match['current_set'] ?? null)) {
                foreach (['home', 'away'] as $side) {
                    if (! $pointPatches[$side]) {
                        unset($match[$side.'_score_data']['point']);
                    }
                }
            }
        }
        $match['minutes_status'] ??= $match['result_status'] ?? 'Prematch';
        if ($this->eventValue($event, 'status.description') !== null) {
            $match['match_status_description'] = $this->eventValue($event, 'status.description');
        }
        if (($pointPatches['home'] || $pointPatches['away']) && isset($match['home_score_data']['point'], $match['away_score_data']['point'])) {
            $match['tennis_points_source'] = 'match';
        } elseif (! isset($match['home_score_data']['point'], $match['away_score_data']['point'])) {
            unset($match['tennis_points_source']);
        }

        MatchBackup::observe($match, 'tennis', $event, $channel, $before);

        return $match !== $before;
    }

    /** @param list<array{id: string, version: int, status: string, current_set: int, home: array, away: array}>|null $scoreUpdates */
    public function mergeSportsWebSocketData(string $channel, array $frameData, ?array &$scoreUpdates = null, ?int $sourceTimestamp = null): bool
    {
        $scoreUpdates = [];
        $sourceTimestamp = $sourceTimestamp === null ? null : ($sourceTimestamp < 100000000000 ? $sourceTimestamp * 1000 : $sourceTimestamp);
        $matches = getInRedis('Tennis:matches') ?? [];
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
                } elseif (str_ends_with($channel, ':point-by-point')) {
                    $sets = $event['pointByPoint'] ?? $event['sets'] ?? (array_is_list($event) ? $event : []);
                    if (is_array($sets) && $sets !== []) {
                        $latestSet = array_reduce($sets, static fn (?array $latest, array $set): array => $latest === null || (int) ($set['set'] ?? 0) > (int) ($latest['set'] ?? 0) ? $set : $latest, null);
                        $games = $latestSet['games'] ?? [];
                        $latestGame = array_reduce($games, static fn (?array $latest, array $game): array => $latest === null || (int) ($game['game'] ?? 0) > (int) ($latest['game'] ?? 0) ? $game : $latest, null);
                        $points = $latestGame['points'] ?? [];
                        $sequence = ['set' => (int) ($latestSet['set'] ?? 0), 'game' => (int) ($latestGame['game'] ?? 0), 'point_count' => count($points), 'score' => $latestGame['score'] ?? [], 'point' => $points === [] ? null : $points[array_key_last($points)]];
                        $previous = $match['last_point_sequence'] ?? [];
                        if ([$sequence['set'], $sequence['game'], $sequence['point_count']] >= [$previous['set'] ?? 0, $previous['game'] ?? 0, $previous['point_count'] ?? 0]) {
                            $match['last_point_sequence'] = $sequence;
                            $this->applyPointSequence($match, $sequence, $sourceTimestamp);
                        }
                    }
                } else {
                    if ($sourceTimestamp === null || $sourceTimestamp >= (int) ($match['tennis_score_source_timestamp'] ?? 0)) {
                        $this->applySportsApiEvent($match, $event, $channel);
                    }
                }
                if (($before['home_score_data'] ?? []) !== ($match['home_score_data'] ?? []) || ($before['away_score_data'] ?? []) !== ($match['away_score_data'] ?? []) || ($before['current_set'] ?? null) !== ($match['current_set'] ?? null) || ($before['result_status'] ?? null) !== ($match['result_status'] ?? null)) {
                    if ($sourceTimestamp !== null && ! str_ends_with($channel, ':point-by-point')) {
                        $match['tennis_score_source_timestamp'] = $sourceTimestamp;
                    }
                    MatchBackup::observe($match, 'tennis', ['status' => ['type' => $match['result_status'] === 'Finished' ? 'finished' : 'inprogress']], $channel, $before);
                    $match['tennis_score_version'] = max((int) ($before['tennis_score_version'] ?? 0) + 1, (int) floor(microtime(true) * 1000));
                    if (! MatchBackup::isHidden($match, 'tennis')) {
                        $scoreUpdates[] = [
                            'id' => (string) $match['sportsApiPro_match_id'],
                            'version' => $match['tennis_score_version'],
                            'status' => $match['result_status'] ?? 'Live',
                            'current_set' => (int) ($match['current_set'] ?? 1),
                            'home' => $match['home_score_data'] ?? [],
                            'away' => $match['away_score_data'] ?? [],
                        ];
                    }
                }
                if ($before !== $match) {
                    $changed[$key] = $match;
                }
                break;
            }
            unset($match);
        }
        if ($changed !== []) {
            storeInRedis('Tennis:matches', $matches, 30, false);
            $this->storeMatchesInDatabase($changed);
        }

        return $changed !== [];
    }

    private function applyPointSequence(array &$match, array $sequence, ?int $sourceTimestamp): void
    {
        if (($match['tennis_points_source'] ?? null) === 'match' && isset($match['home_score_data']['point'], $match['away_score_data']['point'])) {
            return;
        }
        if ($sourceTimestamp !== null && $sourceTimestamp < (int) ($match['tennis_score_source_timestamp'] ?? 0)) {
            return;
        }
        $period = 'period'.$sequence['set'];
        $homeGames = $match['home_score_data'][$period] ?? null;
        $awayGames = $match['away_score_data'][$period] ?? null;
        if ($sequence['set'] !== (int) ($match['current_set'] ?? 1) || $homeGames === null || $awayGames === null || $sequence['game'] !== (int) $homeGames + (int) $awayGames + 1 || in_array((int) ($sequence['score']['scoring'] ?? -1), [1, 2], true)) {
            return;
        }
        $point = $sequence['point'];
        if (! is_array($point) || ! isset($point['homePoint'], $point['awayPoint'])) {
            return;
        }
        $homeIsTeam1 = $match['sports_api_home_is_team1'] ?? true;
        $match['home_score_data']['point'] = (string) $point[$homeIsTeam1 ? 'homePoint' : 'awayPoint'];
        $match['away_score_data']['point'] = (string) $point[$homeIsTeam1 ? 'awayPoint' : 'homePoint'];
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
                        'tennis_match_unique_key' => $matchKey,
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
            ->lock('Tennis:database-write-lock', 120)
            ->block(10, function () use ($matchRows, $marketRows, $matchKeys, $marketSnapshotKeys): void {
                $cleaned = array_fill_keys(Redis::zrangebyscore('Tennis:matches:cleaned', time(), '+inf'), true);
                if ($cleaned !== []) {
                    $matchRows = array_filter($matchRows, static fn (array $row): bool => ! isset($cleaned[$row['unique_key']]));
                    $marketRows = array_filter($marketRows, static fn (array $row): bool => ! isset($cleaned[$row['tennis_match_unique_key']]));
                    $matchKeys = array_values(array_diff($matchKeys, array_keys($cleaned)));
                    $marketSnapshotKeys = array_values(array_diff($marketSnapshotKeys, array_keys($cleaned)));
                }
                $existingMatches = TennisMatch::query()
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
                        $marketRows = array_filter($marketRows, static fn (array $market): bool => $market['tennis_match_unique_key'] !== $row['unique_key']);

                        continue;
                    }
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    $incomingPayload = MatchBackup::merge($incomingPayload, $storedPayload ?? [], 'tennis');
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
                    $existingMarkets = TennisMarket::query()
                        ->whereIn('tennis_match_unique_key', $marketSnapshotKeys)
                        ->get(['selection_key', 'tennis_match_unique_key', 'payload'])
                        ->keyBy('selection_key');
                }

                $changedMarketRows = [];
                foreach ($marketRows as $selectionKey => $row) {
                    $existing = $existingMarkets->get($selectionKey);
                    $storedPayload = $existing?->payload;
                    $incomingPayload = json_decode($row['payload'], true);
                    if (
                        $existing === null
                        || $existing->tennis_match_unique_key !== $row['tennis_match_unique_key']
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
                        TennisMatch::query()->upsert(
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
                        TennisMarket::query()
                            ->whereIn('selection_key', $selectionChunk)
                            ->delete();
                    }
                }

                if ($changedMarketRows !== []) {
                    usort($changedMarketRows, static fn (array $left, array $right): int => $left['selection_key'] <=> $right['selection_key']);
                    foreach (array_chunk($changedMarketRows, 200) as $marketChunk) {
                        TennisMarket::query()->upsert(
                            $marketChunk,
                            ['selection_key'],
                            [
                                'tennis_match_unique_key',
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
        if ($resultStatus === 'Prematch' && in_array($minuteStatus, ['Postponed', 'Cancelled'], true)) {
            return $minuteStatus;
        }

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

    private function checkIfRecordExistsOnAnotherApi(string $uniqueKey, string $team1, string $team2, string|int $timestamp, ?array $existingRecords = null, ?array $teamAliases = null): ?string
    {
        $existingRecords ??= getInRedis($this->sports.':matches') ?? [];

        $sourceVirtual = $teamAliases['is_virtual'] ?? (bool) preg_match('/\b(?:cyber|virtual|esports?)\b/i', $team1.' '.$team2);
        if (array_key_exists($uniqueKey, $existingRecords) && ($existingRecords[$uniqueKey]['is_virtual'] ?? (bool) preg_match('/\b(?:cyber|virtual|esports?)\b/i', ($existingRecords[$uniqueKey]['team1'] ?? '').' '.($existingRecords[$uniqueKey]['team2'] ?? '').' '.($existingRecords[$uniqueKey]['tournament'] ?? ''))) === $sourceVirtual) {
            return $uniqueKey;
        }

        $homeAliases = array_values(array_filter($teamAliases['home'] ?? [$team1]));
        $awayAliases = array_values(array_filter($teamAliases['away'] ?? [$team2]));
        $nearbyCandidates = [];
        $exactCandidates = [];
        foreach ($existingRecords as $key => $record) {
            $recordVirtual = $record['is_virtual'] ?? (bool) preg_match('/\b(?:cyber|virtual|esports?)\b/i', ($record['team1'] ?? '').' '.($record['team2'] ?? '').' '.($record['tournament'] ?? ''));
            if ($sourceVirtual !== $recordVirtual) {
                continue;
            }
            $recordHome = array_filter([$record['team1'] ?? '', $record['home_team_short_name'] ?? '']);
            $recordAway = array_filter([$record['team2'] ?? '', $record['away_team_short_name'] ?? '']);
            $direct = array_any($recordHome, fn (string $name): bool => array_any($homeAliases, fn (string $alias): bool => $this->teamSimilarity($name, $alias) >= 80)) && array_any($recordAway, fn (string $name): bool => array_any($awayAliases, fn (string $alias): bool => $this->teamSimilarity($name, $alias) >= 80));
            $reversed = array_any($recordHome, fn (string $name): bool => array_any($awayAliases, fn (string $alias): bool => $this->teamSimilarity($name, $alias) >= 80)) && array_any($recordAway, fn (string $name): bool => array_any($homeAliases, fn (string $alias): bool => $this->teamSimilarity($name, $alias) >= 80));
            if (! $direct && ! $reversed) {
                continue;
            }
            if ((int) ($record['timestamp'] ?? 0) === (int) $timestamp) {
                $exactCandidates[] = $key;
            }
            if (in_array($record['result_status'] ?? null, ['Live', 'Prematch'], true) && abs((int) ($record['timestamp'] ?? 0) - (int) $timestamp) <= 3600) {
                $nearbyCandidates[] = $key;
            }
        }
        if (count($exactCandidates) === 1) {
            return $exactCandidates[0];
        }
        if ($exactCandidates === [] && count($nearbyCandidates) === 1) {
            return $nearbyCandidates[0];
        }

        return null;
    }

    private function teamSimilarity(string $teamA, string $teamB): float
    {
        $playersA = array_map(fn (string $name): string => $this->normalizeTeamName($name), explode('/', $teamA));
        $playersB = array_map(fn (string $name): string => $this->normalizeTeamName($name), explode('/', $teamB));
        if (count($playersA) !== count($playersB)) {
            return 0;
        }
        $score = static function (string $first, string $second): float {
            if ($first === $second) {
                return 100;
            }
            $firstTokens = array_values(array_filter(explode('_', $first), static fn (string $token): bool => strlen($token) > 1));
            $secondTokens = array_values(array_filter(explode('_', $second), static fn (string $token): bool => strlen($token) > 1));
            $short = count($firstTokens) <= count($secondTokens) ? $firstTokens : $secondTokens;
            $long = count($firstTokens) <= count($secondTokens) ? $secondTokens : $firstTokens;
            if ($short !== [] && array_diff($short, $long) === [] && strlen(implode('', $short)) >= 4) {
                return 100;
            }
            similar_text($first, $second, $percentage);

            return $percentage;
        };
        if (count($playersA) === 2) {
            return max(min($score($playersA[0], $playersB[0]), $score($playersA[1], $playersB[1])), min($score($playersA[0], $playersB[1]), $score($playersA[1], $playersB[0])));
        }

        return $score($playersA[0], $playersB[0]);
    }
}
