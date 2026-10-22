<?php

namespace App\Http\Controllers\Users\Greyhounds;

use App\Events\GreyhoundsSportsScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\GreyhoundsMarket;
use App\Models\GreyhoundsMatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

class GreyhoundsSyncController extends Controller
{
    public function syncBetFairPrematchData(): array
    {
        $response = ApiCall('https://betfair-odds.com/betfair/v1/livemap', ['x-portal-apikey' => GetBetfairKey()], [
            'query' => ['feed' => 'greyhounds'],
            'connect_timeout' => 5,
            'timeout' => 30,
            'raw_response' => true,
            'follow_redirects' => true,
        ]);
        $data = json_decode($response['body'], true);
        if (! $response['successful'] || ! is_array($data['liveMap'] ?? null) || ($data['meta']['stale'] ?? true)) {
            Log::warning('Greyhounds snapshot unavailable; existing races retained', ['status' => $response['status']]);

            return [];
        }
        if ($this->mergeBetfairWebSocketData($data['liveMap'], false, true)) {
            try {
                Event::dispatch(new GreyhoundsSportsScoreUpdated);
            } catch (Throwable $e) {
                Log::warning('Greyhounds recovery refresh broadcast failed', ['exception' => $e::class]);
            }
        }

        return getInRedis('Greyhounds:matches') ?? [];
    }

    public function mergeBetfairWebSocketData(array $events, bool $isLive = false, bool $isSnapshot = false): bool
    {
        return withRedisLock('Greyhounds:matches', function () use ($events, $isSnapshot): bool {
            $matches = getInRedis('Greyhounds:matches') ?? [];
            $known = array_column($matches, 'betfair_match_id');
            $missing = array_diff(array_keys($events), $known);
            $query = GreyhoundsMatch::query()->with('markets');
            if ($matches === [] && $isSnapshot) {
                $stored = $query->where('result_status', '!=', 'Finished')->orWhereIn('betfair_match_id', array_keys($events))->get();
            } else {
                $stored = $missing === [] ? collect() : $query->whereIn('betfair_match_id', $missing)->get();
            }
            foreach ($stored as $record) {
                $race = $record->payload;
                $race['markets'] = [];
                foreach ($record->markets as $selection) {
                    $runner = $selection->payload;
                    $race['markets'][$runner['marketId'].'_'.$runner['marketType']][$runner['runnerId'].'|'.($runner['handicap'] ?? 0)] = $runner;
                }
                $matches[$record->unique_key] = $race;
            }
            $changed = [];
            $now = microtime(true);
            if ($isSnapshot) {
                foreach ($matches as $race) {
                    if (($race['result_status'] ?? '') !== 'Finished' && ! array_key_exists((string) $race['betfair_match_id'], $events)) {
                        $events[(string) $race['betfair_match_id']] = ['removed' => true];
                    }
                }
            }
            foreach ($events as $raceId => $event) {
                $key = 'greyhounds|'.$raceId;
                $before = $matches[$key] ?? null;
                if (($before['result_status'] ?? null) === 'Finished') {
                    continue;
                }
                if (($event['removed'] ?? false) === true || ($event['win_market_status'] ?? null) === 'CLOSED') {
                    if ($before !== null) {
                        $matches[$key]['result_status'] = 'Finished';
                        $matches[$key]['minutes_status'] = 'Finished';
                        $matches[$key]['feed_available'] = false;
                        $changed[$key] = $matches[$key];
                    }

                    continue;
                }
                if (($event['win_market_status'] ?? null) === 'SUSPENDED') {
                    if ($before !== null && ($before['market_status'] ?? '') !== 'SUSPENDED') {
                        $matches[$key]['market_status'] = 'SUSPENDED';
                        $changed[$key] = $matches[$key];
                    }

                    continue;
                }
                if (($event['sport'] ?? '') !== 'greyhounds' || empty($event['openTimestamp']) || ! is_array($event['markets'] ?? null)) {
                    continue;
                }
                $race = array_replace($before ?? [], ['unique_key' => $key, 'betfair_match_id' => (string) $raceId,
                    'team1' => $event['team1'] ?? $before['team1'] ?? '', 'team2' => $event['team2'] ?? $before['team2'] ?? '',
                    'tournament' => $event['tournament'] ?? $before['tournament'] ?? '', 'timestamp' => Carbon::parse($event['openTimestamp'])->timestamp,
                    'result_status' => 'Prematch', 'minutes_status' => 'Prematch', 'feed_available' => true, 'market_status' => $event['win_market_status'] ?? 'OPEN']);
                if (! empty($event['status'])) {
                    $race['market_status'] = $event['status'];
                    if (($event['win_market_status'] ?? null) === 'CLOSED') {
                        $race['result_status'] = 'Finished';
                        $race['minutes_status'] = 'Finished';
                    }
                }
                $markets = [];
                foreach ($event['markets'] as $runner) {
                    if (! isset($runner['marketId'], $runner['runnerId'], $runner['marketType']) || ! in_array($runner['marketType'], ['WIN', 'PLACE', 'MATCH_BET', 'MONEY_BACK_2ND', 'EXACTA', 'QUINELLA'], true)) {
                        continue;
                    }
                    $marketKey = $runner['marketId'].'_'.$runner['marketType'];
                    $selectionKey = $runner['runnerId'].'|'.($runner['handicap'] ?? 0);
                    $previous = $before['markets'][$marketKey][$selectionKey] ?? [];
                    $runner = array_replace($previous, $runner);
                    $hasPreviousOpening = hasMeaningfulOpeningOdds($previous);
                    $runner = updateOddsMonitoring($runner, $previous, $now, $now >= $race['timestamp'] - 300 && $now < $race['timestamp']);
                    $previous = $hasPreviousOpening ? $previous : [];
                    $current = $runner['currentBackOdds'];
                    $validQuote = $current > 1;
                    $runner['previousLayOdds'] = (float) ($previous['layOdds'] ?? $runner['layOdds'] ?? 0);
                    $runner['previousBackSize'] = (float) ($previous['backSize'] ?? $runner['backSize'] ?? 0);
                    $runner['currentBackOdds'] = $current;
                    $runner['odds_direction'] = $runner['initialBackOdds'] > 0 && $current > 0 ? ($current < $runner['initialBackOdds'] ? 'down' : ($current > $runner['initialBackOdds'] ? 'up' : 'same')) : 'same';
                    $runner['number'] = in_array($runner['marketType'], ['WIN', 'PLACE', 'MATCH_BET', 'MONEY_BACK_2ND'], true) && preg_match('/^(\d+)\.\s*/', $runner['runner'] ?? '', $trap) ? (int) $trap[1] : ($previous['number'] ?? null);
                    $monitoring = ($runner['initialBackOdds'] ?? 0) > 1;
                    $movement = array_values(array_filter($previous['movementHistory'] ?? [], static fn (array $point): bool => $point['timestamp'] >= $now - 30));
                    if ($monitoring && $validQuote && ($previous === [] || $current !== (float) ($previous['currentBackOdds'] ?? 0))) {
                        $movement[] = ['timestamp' => $now, 'odds' => $current];
                    }
                    $runner['movementHistory'] = array_slice($movement, -8);
                    $runner['pressure_type'] = ! $validQuote ? 'neutral' : ($current < $runner['previousBackOdds'] ? 'back' : ($current > $runner['previousBackOdds'] ? 'lay' : 'neutral'));
                    $runner['pressure_amount'] = (float) ($runner['pressure_type'] === 'back' ? ($runner['backSize'] ?? 0) : ($runner['pressure_type'] === 'lay' ? ($runner['laySize'] ?? 0) : 0));
                    $runner['pressure_source'] = 'available_liquidity';

                    $recent = array_values(array_filter($previous['recentOddsHistory'] ?? [], static fn (array $point): bool => (float) $point['timestamp'] >= $now - 10));
                    if ($monitoring && $validQuote && ($previous === [] || $current !== (float) ($previous['currentBackOdds'] ?? 0))) {
                        $recent[] = ['timestamp' => $now, 'odds' => $current];
                    }
                    $runner['recentOddsHistory'] = array_slice($recent, -50);
                    $runner['prematch_drop_baseline_odds'] = $previous['prematch_drop_baseline_odds'] ?? null;
                    $runner['prematch_drop_triggered'] = (bool) ($previous['prematch_drop_triggered'] ?? false);
                    $snapshots = array_values(array_filter($previous['market_shock_snapshots'] ?? [], static fn (array $point): bool => (int) $point['timestamp'] >= (int) $now - 180));
                    if ($monitoring && $validQuote && ($snapshots === [] || (float) $snapshots[array_key_last($snapshots)]['odds'] !== $current)) {
                        $snapshots[] = ['timestamp' => (int) $now, 'odds' => $current];
                    }
                    $comparison = $snapshots === [] ? 0 : max(array_column($snapshots, 'odds'));
                    $threshold = $comparison >= 1.2 && $comparison < 2 ? 5 : ($comparison >= 2 && $comparison < 4 ? 8 : ($comparison >= 4 && $comparison <= 20 ? 12 : null));
                    $runner['market_shock_detected_at'] = $threshold !== null && $current > 0 && ($comparison - $current) / $comparison * 100 >= $threshold ? (int) $now : ($previous['market_shock_detected_at'] ?? null);
                    $runner['market_shock_snapshots'] = array_slice($snapshots, -7);
                    $markets[$marketKey][$selectionKey] = $runner;
                }
                foreach ($markets as &$runners) {
                    $liquidity = array_sum(array_map(static fn (array $runner): float => (float) ($runner['backSize'] ?? 0) + (float) ($runner['laySize'] ?? 0), $runners));
                    foreach ($runners as &$runner) {
                        $baseline = (float) ($runner['prematch_drop_baseline_odds'] ?? 0);
                        if ($baseline <= 0 && $liquidity >= 1000 && ($runner['initialBackOdds'] ?? 0) > 1) {
                            $baseline = $runner['initialBackOdds'];
                            $runner['prematch_drop_baseline_odds'] = $baseline;
                        }
                        $runner['prematch_drop_triggered'] = $runner['prematch_drop_triggered'] || ($baseline > 0 && $runner['currentBackOdds'] > 0 && ($baseline - $runner['currentBackOdds']) / $baseline * 100 >= 1);
                    }
                    unset($runner);
                }
                unset($runners);
                $race['market_indicators'] = [];
                foreach ($markets as $marketKey => $runners) {
                    $favorite = array_reduce($runners, static function (?array $favorite, array $runner): ?array {
                        return ($runner['initialBackOdds'] ?? 0) > 1 && $runner['currentBackOdds'] > 0 && ($favorite === null || $runner['currentBackOdds'] < $favorite['currentBackOdds']) ? $runner : $favorite;
                    }, null);
                    $totalBack = array_sum(array_column($runners, 'backSize'));
                    $totalLay = array_sum(array_column($runners, 'laySize'));
                    $liquidity = $totalBack + $totalLay;
                    $ratio = $liquidity > 0 ? $totalBack / $liquidity : 0.5;
                    $previousSize = (float) ($favorite['previousBackSize'] ?? 0);
                    $delta = $previousSize > 0 ? ((float) ($favorite['backSize'] ?? 0) - $previousSize) / $previousSize * 100 : 0;
                    $recent = $favorite['recentOddsHistory'] ?? [];
                    $returns = [];
                    for ($index = 1; $index < count($recent); $index++) {
                        if ($recent[$index - 1]['odds'] > 0 && $recent[$index]['odds'] > 0) {
                            $returns[] = log($recent[$index]['odds'] / $recent[$index - 1]['odds']);
                        }
                    }
                    $mean = $returns === [] ? 0 : array_sum($returns) / count($returns);
                    $deviation = $returns === [] ? 0 : sqrt(array_sum(array_map(static fn (float $value): float => ($value - $mean) ** 2, $returns)) / count($returns)) * 100;
                    $race['market_indicators'][$marketKey] = ['favorite_id' => $favorite['runnerId'] ?? null, 'favorite_back_size' => $favorite['backSize'] ?? 0, 'back_total' => $totalBack, 'lay_total' => $totalLay, 'liquidity' => $liquidity, 'back_percent' => $ratio * 100,
                        'balance' => $ratio > 0.58 ? 'back' : ($ratio < 0.42 ? 'lay' : 'balanced'), 'delta' => $delta,
                        'momentum' => $delta > 10 ? 'strong_back' : ($delta > 3 ? 'back' : ($delta < -10 ? 'strong_lay' : ($delta < -3 ? 'lay' : 'neutral'))),
                        'volatility' => $deviation > 15 ? 'high' : ($deviation > 5 ? 'medium' : 'low'), 'volatility_percent' => $deviation];
                }
                $race['markets'] = $markets;
                $race['data_sync_complete'] = isset($markets[$raceId.'_WIN']);
                if ($race !== $before) {
                    $matches[$key] = $race;
                    $changed[$key] = $race;
                }
            }
            if ($changed !== []) {
                $this->storeMatchesInDatabase($changed);
            }
            if ($changed !== [] || $stored->isNotEmpty()) {
                storeInRedis('Greyhounds:matches', $matches, 60);
            }

            return $changed !== [];
        }, 5);
    }

    private function storeMatchesInDatabase(array $matches): void
    {
        $rows = [];
        $selections = [];
        $now = now();
        foreach ($matches as $match) {
            $payload = $match;
            unset($payload['markets']);
            $rows[] = ['unique_key' => $match['unique_key'], 'betfair_match_id' => $match['betfair_match_id'], 'sports_api_pro_match_id' => null, 'team1' => $match['team1'], 'team2' => $match['team2'], 'tournament' => $match['tournament'], 'start_timestamp' => $match['timestamp'], 'result_status' => $match['result_status'], 'minutes_status' => $match['minutes_status'], 'data_sync_complete' => $match['data_sync_complete'], 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => $now, 'updated_at' => $now];
            foreach ($match['markets'] ?? [] as $runners) {
                foreach ($runners as $runner) {
                    $selection = hash('sha256', implode('|', [$match['unique_key'], $runner['marketId'], $runner['runnerId'], $runner['handicap'] ?? 0]));
                    $selections[] = ['selection_key' => $selection, 'greyhounds_match_unique_key' => $match['unique_key'], 'market_id' => $runner['marketId'], 'market_type' => $runner['marketType'], 'runner_id' => $runner['runnerId'], 'runner' => $runner['runner'], 'sort_priority' => $runner['sortPriority'] ?? null, 'handicap' => $runner['handicap'] ?? 0, 'previous_back_odds' => $runner['previousBackOdds'], 'current_back_odds' => $runner['currentBackOdds'], 'back_size' => $runner['backSize'] ?? 0, 'lay_odds' => $runner['layOdds'] ?? 0, 'lay_size' => $runner['laySize'] ?? 0, 'payload' => json_encode($runner, JSON_THROW_ON_ERROR), 'created_at' => $now, 'updated_at' => $now];
                }
            }
        }
        $keys = array_column($rows, 'unique_key');
        $frozen = GreyhoundsMatch::query()->whereIn('unique_key', $keys)->where('result_status', 'Finished')->pluck('unique_key')->all();
        $rows = array_values(array_filter($rows, static fn (array $row): bool => ! in_array($row['unique_key'], $frozen, true)));
        $selections = array_values(array_filter($selections, static fn (array $row): bool => ! in_array($row['greyhounds_match_unique_key'], $frozen, true)));
        foreach (array_chunk($rows, 50) as $chunk) {
            GreyhoundsMatch::query()->upsert($chunk, ['unique_key'], array_diff(array_keys($chunk[0]), ['unique_key', 'created_at']));
        }
        if ($rows !== []) {
            $activeKeys = array_column($rows, 'unique_key');
            $incoming = array_column($selections, 'selection_key');
            GreyhoundsMarket::query()->whereIn('greyhounds_match_unique_key', $activeKeys)->whereNotIn('selection_key', $incoming)->delete();
        }
        foreach (array_chunk($selections, 200) as $chunk) {
            GreyhoundsMarket::query()->upsert($chunk, ['selection_key'], array_diff(array_keys($chunk[0]), ['selection_key', 'created_at']));
        }
    }
}
