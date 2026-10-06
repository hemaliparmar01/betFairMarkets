<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function syncBetFairLiveData()
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=live&sports=soccer';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key);
        $cacheKey = $this->sports.':matches';

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, true, 10)
        );
        $this->storeMatchesInDatabase($matches);

        return $matches;
    }

    public function syncBetFairPrematchData()
    {
        $url = 'https://betfair-odds.com/betfair/v1/livemap?feed=prematch&sports=soccer';
        $key = 'x-portal-apikey: '.$this->getBetferKeys;
        $liveData = ApiCall($url, $key);
        $cacheKey = $this->sports.':matches';

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readBetFairData($liveData, $cacheKey, false, 30)
        );
        $this->storeMatchesInDatabase($matches);

        return $matches;
    }

    public function syncSportsApiProLiveData()
    {
        $url = 'https://api.sportsapipro.com/v2/football/live';
        $key = 'x-api-key: '.$this->getsportsAPIProKeys;
        $liveData = ApiCall($url, $key);
        $cacheKey = $this->sports.':matches';

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readsportApiData($liveData, $cacheKey, true, 10)
        );
        $this->storeMatchesInDatabase($matches);

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

        $matches = withRedisLock(
            $cacheKey,
            fn () => $this->readsportApiData(['events' => $scheduleData['events']], $cacheKey, false, 30)
        );
        $this->storeMatchesInDatabase($matches);

        return $matches;
    }

    private function readBetFairData(array $payloads, string $cacheKey, bool $isLiveApi, int $ttlSeconds)
    {
        $mainArray = [];
        $getExistingRecords = getInRedis($cacheKey) ?? [];
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
            $existingMarkets = $getExistingRecords[$existingKey ?? $payloadUniqueKey]['markets'] ?? [];

            if ($existingMarkets === []) {
                $databaseLookupKeys[$payloadUniqueKey] = true;
                if ($existingKey !== null) {
                    $databaseLookupKeys[$existingKey] = true;
                }
            }
        }

        if ($databaseLookupKeys !== []) {
            $databaseRows = DB::table('football_markets')
                ->whereIn('football_match_unique_key', array_keys($databaseLookupKeys))
                ->get(['football_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $storedMarket = json_decode($databaseRow->payload, true);
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
                $sortPriority = $market['sortPriority'] ?? null;
                $marketKey = $market['marketId'].'_'.$market['marketType'];

                $previousMarket = $getSingleRecords['markets'][$marketKey][$sortPriority] ?? [];

                if ($previousMarket === []) {
                    foreach (($getSingleRecords['markets'] ?? []) as $previousMarketRunners) {
                        foreach (($previousMarketRunners ?? []) as $candidate) {
                            $sameRunner = isset($market['runnerId'], $candidate['runnerId'])
                                ? (string) $candidate['runnerId'] === (string) $market['runnerId']
                                : strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($market['runner'] ?? '')) === 0;

                            if (
                                ($candidate['marketType'] ?? null) === ($market['marketType'] ?? null)
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
                            ($candidate['marketType'] ?? null) === ($market['marketType'] ?? null)
                            && $sameRunner
                            && (float) ($candidate['handicap'] ?? 0) === (float) ($market['handicap'] ?? 0)
                        ) {
                            $previousMarket = $candidate;
                            break;
                        }
                    }
                }

                $previousBackOdds = $previousMarket['currentBackOdds'] ?? $previousMarket['backOdds'] ?? $market['backOdds'];
                $currentOdds = (float) $market['backOdds'] > 0 ? $market['backOdds'] : $previousBackOdds;
                $backOddsHistory = $previousMarket['backOddsHistory'] ?? [];
                $initialBackOddsTimestamp = $previousMarket['initialBackOddsTimestamp'] ?? null;

                if ($initialBackOddsTimestamp !== null) {
                    $initialBackOdds = (float) ($previousMarket['initialBackOdds'] ?? $currentOdds);
                } elseif (isset($backOddsHistory[0]['odds'])) {
                    $initialBackOdds = (float) $backOddsHistory[0]['odds'];
                    $initialBackOddsTimestamp = (int) ($backOddsHistory[0]['timestamp'] ?? $now);
                } else {
                    $initialBackOdds = (float) ($previousMarket['initialBackOdds']
                        ?? $previousMarket['backOdds']
                        ?? $currentOdds);
                    $initialBackOddsTimestamp = $now;
                }

                if ($initialBackOdds <= 0 && $currentOdds > 0) {
                    $initialBackOdds = (float) $currentOdds;
                    $initialBackOddsTimestamp = $now;
                }

                if ($backOddsHistory === [] && $initialBackOdds > 0) {
                    $backOddsHistory[] = ['timestamp' => $now, 'odds' => $initialBackOdds];
                }

                $lastHistoryOdds = (float) ($backOddsHistory[array_key_last($backOddsHistory)]['odds'] ?? 0);
                if ($currentOdds > 0 && $currentOdds !== $lastHistoryOdds) {
                    $backOddsHistory[] = ['timestamp' => $now, 'odds' => (float) $currentOdds];
                }

                $market['previousBackOdds'] = $previousBackOdds;
                $market['currentBackOdds'] = $currentOdds;
                $market['initialBackOdds'] = $initialBackOdds;
                $market['initialBackOddsTimestamp'] = $initialBackOddsTimestamp;
                $market['backOddsHistory'] = array_slice($backOddsHistory, -12);
                $market['odds_direction'] = $this->matchDirection($initialBackOdds, $currentOdds);

                if (! $isLiveApi && $currentOdds > 0) {
                    $baselineOdds = (float) ($previousMarket['prematch_drop_baseline_odds'] ?? 0);
                    if ($baselineOdds <= 0 && ($marketLiquidity[$marketKey] ?? 0) >= 1000) {
                        $baselineOdds = (float) $currentOdds;
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

    private function readsportApiData(array $payloads, string $cacheKey, bool $isLive, int $ttlSeconds)
    {
        if (empty($payloads)) {
            return [];
        }
        $getExistingRecords = getInRedis($cacheKey) ?? [];
        $databaseMatches = [];
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

            $minutesStatus = $this->sportsApiMinuteStatus(
                $statusCode,
                $getExistingRecords[$checkIsExists]['minutes_status'] ?? ($isLive ? 'Live' : 'Prematch')
            );
            if ($minutesStatus === 'Live' && (int) floor((time() - $startTimestamp) / 60) >= 60) {
                $minutesStatus = '2nd half';
            }
            $resultStatus = $isLive == true ? 'Live' : 'Prematch';
            $minutesStatus = $this->alignMinuteStatusWithResult($minutesStatus, $resultStatus);
            $homeScore = is_array($payload['homeScore'] ?? null)
                ? ($payload['homeScore']['current'] ?? 0)
                : ($payload['homeScore'] ?? 0);
            $awayScore = is_array($payload['awayScore'] ?? null)
                ? ($payload['awayScore']['current'] ?? 0)
                : ($payload['awayScore'] ?? 0);
            $tournament = is_array($payload['tournament'] ?? null)
                ? ($payload['tournament']['name'] ?? '')
                : ($payload['tournament'] ?? '');

            if ($checkIsExists) {
                $data_sync_complete = isset($getExistingRecords[$checkIsExists]['betfair_match_id']);
                $additionalData = [
                    'data_sync_complete' => $data_sync_complete,
                    'home_score' => $homeScore,
                    'sportsApiPro_match_id' => $payload['id'],
                    'away_score' => $awayScore,
                    'minutes_status' => $minutesStatus,
                    'result_status' => $resultStatus,
                    // 'status_type' => $payload['status']['type'] ?? null,
                ];
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
                $getExistingRecords[$uniqueKey] = $tempArray;
                $databaseMatches[$uniqueKey] = $tempArray;
            }
        }
        storeInRedis($cacheKey, $getExistingRecords, $ttlSeconds);

        return $databaseMatches;
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
            unset($matchPayload['markets']);

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
                $existingMatches = DB::table('football_matches')
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
                    $storedPayload = $existing === null ? null : json_decode($existing->payload, true);
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
                    $existingMarkets = DB::table('football_markets')
                        ->whereIn('football_match_unique_key', $marketSnapshotKeys)
                        ->get(['selection_key', 'football_match_unique_key', 'payload'])
                        ->keyBy('selection_key');
                }

                $changedMarketRows = [];
                foreach ($marketRows as $selectionKey => $row) {
                    $existing = $existingMarkets->get($selectionKey);
                    $storedPayload = $existing === null ? null : json_decode($existing->payload, true);
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
                        DB::table('football_matches')->upsert(
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
                        DB::table('football_markets')
                            ->whereIn('selection_key', $selectionChunk)
                            ->delete();
                    }
                }

                if ($changedMarketRows !== []) {
                    usort($changedMarketRows, static fn (array $left, array $right): int => $left['selection_key'] <=> $right['selection_key']);
                    foreach (array_chunk($changedMarketRows, 200) as $marketChunk) {
                        DB::table('football_markets')->upsert(
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
