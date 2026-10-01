<?php

namespace App\Traits;

trait FootballTrait
{
    public function getFootballMatches() {
        $getData = getInRedis('Football:matches');

        if(request()->has('market_name')) {
            $marketName = request()->market_name;
        } else {
            $marketName = 'MATCH_ODDS';
        }

        $type = null;
        if(request()->has('type')) {
            $type = request()->type;
        }

        if($type) {
            switch ($type) {
                case 'upcoming':
                    $getData = array_values(array_filter( $getData, static fn (array $match): bool => $match['result_status'] == "Prematch" ));
                    break;

                case 'live':
                    $getData = array_values(array_filter( $getData, static fn (array $match): bool => $match['result_status'] == 'Live' ));
                    break;

                case 'all':
                    $getData = $getData;
                    break;

                default:
                    $getData = $getData;
                    break;
            }
        }

        $mainArray = [];
        foreach ($getData ?? [] as $key => $data) {
            if(($data['data_sync_complete'] ?? false) !== true) {
                continue;
            }
            $markets = $data['markets'];
            $marketKey = array_key_first(array_filter($markets,fn ($key) => str_contains($key, $marketName),ARRAY_FILTER_USE_KEY));
            $tournamentName = $data['tournament'];
            if (!isset($mainArray[$tournamentName])) {
                $mainArray[$tournamentName] = [
                    'tournament_name' => $tournamentName,
                    'unique_key' => $data['unique_key'],
                    'status' => $data['result_status'],
                    'matches' => [],
                ];
            }
            $mainArray[$tournamentName]['matches'][] = [
                'team1' => $data['team1'],
                'team2' => $data['team2'],
                'status' => $data['result_status'],
                'sportsApiPro_match_id' => $data['sportsApiPro_match_id'],
                'betfair_match_id' => $data['betfair_match_id'],
                'score' => $this->getScore($data),
                'minutes' => $this->calculateMinutes($data['minutes_status'] ?? $data['result_status'],$data['timestamp'],$data['result_status'],$data['added_time'] ?? []),
                // 'status_type' => $data['status_type'] ?? null,
                'cards' => $data['cards'] ?? [],
                'card_counts' => $data['card_counts'] ?? [],
                'last_goal' => $data['last_goal'] ?? null,
                'goal_highlight_until' => $data['goal_highlight_until'] ?? null,
                'added_time' => $data['added_time'] ?? [],
                'corners' => $data['corners'] ?? [],
                'markets' => $markets[$marketKey] ?? [],
            ];
        }
        return $mainArray;
    }

    public function calculateMinutes(?string $minute_status,int $timestamp,string $result_status,array $added_time = []) {
        if($minute_status == 'Prematch' || $result_status == 'Prematch') {
            return "Prematch";
        }

        if($minute_status == 'Finished' || $result_status == 'Finished') {
            return "FT";
        }

        if(!in_array($minute_status, ['Live', '1st half', '2nd half'], true) || $result_status != 'Live') {
            return null;
        }

        $elapsedMinutes = max(0, (int) floor((time() - $timestamp) / 60));

        if($minute_status == '2nd half') {
            $currentMinute = max(46, $elapsedMinutes - 15);
            if($currentMinute > 90) {
                $extraMinutes = $added_time['second_half'] ?? ($currentMinute - 90);
                return "90+{$extraMinutes}";
            }
            return $currentMinute;
        }

        if($minute_status == 'Live' && $elapsedMinutes > 90) {
            $extraMinutes = $added_time['second_half'] ?? ($elapsedMinutes - 90);
            return "90+{$extraMinutes}";
        }

        if($minute_status == 'Live' && $elapsedMinutes > 45) {
            return $elapsedMinutes;
        }

        if($elapsedMinutes > 45) {
            $extraMinutes = $added_time['first_half'] ?? ($elapsedMinutes - 45);
            return "45+{$extraMinutes}";
        }

        return $elapsedMinutes;
    }

    public function getScore(array $match) {
        if(in_array($match['result_status'], ['Live', 'Finished'], true)) {
            return $match['home_score'].' - '.$match['away_score'];
        } else {
            return '-';
        }
    }

    public function getAllMarkets() {
        $sportMarkets = [
            'default' => 'MATCH_ODDS',
            'options' => [
                [
                    'value' => 'MATCH_ODDS',
                    'label' => 'Match Odds / 1X2',
                    'oddsCount' => 3,
                ],
                [
                    'value' => 'OVER_UNDER_25',
                    'label' => 'Under/Over 2.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_05',
                    'label' => 'Under/Over 0.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_15',
                    'label' => 'Under/Over 1.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_35',
                    'label' => 'Under/Over 3.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'BOTH_TEAMS_TO_SCORE',
                    'label' => 'Both Teams To Score',
                    'oddsCount' => 2,
                ],
            ],
        ];
        return $sportMarkets;
    }

    public function getMarketActivites() {
        $getData = getInRedis('Football:matches') ?? [];
        $mainArray = [];
        $now = time();

        foreach ($getData as $data) {
            if(($data['data_sync_complete'] ?? false) !== true) {
                continue;
            }

            if(($data['result_status'] ?? null) === 'Finished') {
                continue;
            }

            $isLive = ($data['result_status'] ?? null) === 'Live';
            $secondsUntilStart = max(0, (int) ($data['timestamp'] ?? $now) - $now);
            $minutesUntilStart = (int) ceil($secondsUntilStart / 60);
            $statusPriority = $isLive ? 0 : ($minutesUntilStart <= 5 ? 1 : ($minutesUntilStart <= 10 ? 2 : 3));
            $eventTotalLiquidity = 0.0;

            foreach (($data['markets'] ?? []) as $eventMarkets) {
                if(!is_array($eventMarkets)) {
                    continue;
                }

                foreach ($eventMarkets as $eventMarket) {
                    if(is_array($eventMarket)) {
                        $eventTotalLiquidity += (float) ($eventMarket['backSize'] ?? 0)
                            + (float) ($eventMarket['laySize'] ?? 0);
                    }
                }
            }

            foreach (($data['markets'] ?? []) as $marketKey => $markets) {
                if(!is_array($markets)) {
                    continue;
                }

                $runners = [];
                $favoriteIndex = null;
                $lowestBackOdds = null;
                $totalBackSize = 0.0;
                $totalLaySize = 0.0;
                $marketId = null;
                $marketType = null;
                $marketName = null;
                $favoritePreviousBackOdds = null;
                $favoriteOddsDirection = 'same';

                foreach ($markets as $market) {
                    if(!is_array($market)) {
                        continue;
                    }

                    $backOdds = (float) ($market['currentBackOdds'] ?? $market['backOdds'] ?? 0);
                    $backSize = (float) ($market['backSize'] ?? 0);
                    $layOdds = (float) ($market['layOdds'] ?? 0);
                    $laySize = (float) ($market['laySize'] ?? 0);

                    $runners[] = [
                        'id' => $market['runnerId'] ?? null,
                        'name' => $market['runner'] ?? '',
                        'back' => $backOdds,
                        'backSize' => $backSize,
                        'lay' => $layOdds,
                        'laySize' => $laySize,
                        'previousBack' => (float) ($market['previousBackOdds'] ?? $backOdds),
                        'oddsDirection' => $market['odds_direction'] ?? 'same',
                    ];

                    $runnerIndex = array_key_last($runners);
                    if($backOdds > 0 && ($lowestBackOdds === null || $backOdds < $lowestBackOdds)) {
                        $lowestBackOdds = $backOdds;
                        $favoriteIndex = $runnerIndex;
                        $favoritePreviousBackOdds = (float) ($market['previousBackOdds'] ?? $backOdds);
                        $favoriteOddsDirection = $market['odds_direction'] ?? 'same';
                    }

                    $totalBackSize += $backSize;
                    $totalLaySize += $laySize;
                    $marketId ??= $market['marketId'] ?? null;
                    $marketType ??= $market['marketType'] ?? null;
                    $marketName ??= $market['name'] ?? null;
                }

                if(empty($runners)) {
                    continue;
                }

                if($favoriteIndex === null) {
                    $favoriteIndex = 0;
                    $favoritePreviousBackOdds = (float) ($runners[0]['previousBack'] ?? 0);
                    $favoriteOddsDirection = $runners[0]['oddsDirection'] ?? 'same';
                }

                $favoriteBackOdds = (float) $runners[$favoriteIndex]['back'];
                $oddsChangePercent = $favoritePreviousBackOdds > 0
                    ? (($favoritePreviousBackOdds - $favoriteBackOdds) / $favoritePreviousBackOdds) * 100
                    : 0.0;
                $absoluteOddsChange = abs($oddsChangePercent);
                $balance = $totalBackSize > ($totalLaySize * 1.05)
                    ? 'back'
                    : ($totalLaySize > ($totalBackSize * 1.05) ? 'lay' : 'balanced');
                $momentum = match ($favoriteOddsDirection) {
                    'down' => $absoluteOddsChange >= 10 ? 'strong_back' : 'back',
                    'up' => $absoluteOddsChange >= 10 ? 'strong_lay' : 'lay',
                    default => 'neutral',
                };
                $volatility = $absoluteOddsChange >= 10
                    ? 'high'
                    : ($absoluteOddsChange >= 3 ? 'medium' : 'low');
                $delta = ($oddsChangePercent > 0 ? '+' : '').number_format($oddsChangePercent, 1).'%';
                $displayMinutes = $isLive
                    ? $this->calculateMinutes(
                        $data['minutes_status'] ?? $data['result_status'],
                        (int) $data['timestamp'],
                        $data['result_status'],
                        $data['added_time'] ?? []
                    )
                    : null;
                $displayTime = $isLive
                    ? (is_numeric($displayMinutes) ? $displayMinutes."'" : $displayMinutes)
                    : ($minutesUntilStart >= 60
                        ? 'in '.intdiv($minutesUntilStart, 60).'h'.($minutesUntilStart % 60 > 0 ? ' '.($minutesUntilStart % 60).'m' : '')
                        : 'in '.$minutesUntilStart.' min');
                $uiMarket = match ($marketType) {
                    'MATCH_ODDS' => 'match_odds',
                    'OVER_UNDER_05' => 'ou_0_5',
                    'OVER_UNDER_15' => 'ou_1_5',
                    'OVER_UNDER_25' => 'ou_2_5',
                    'OVER_UNDER_35' => 'ou_3_5',
                    'OVER_UNDER_45' => 'ou_4_5',
                    'BOTH_TEAMS_TO_SCORE' => 'btts',
                    'CORRECT_SCORE' => 'correct_score',
                    'HALF_TIME' => 'first_half',
                    'FIRST_HALF_GOALS_05' => 'fh_ou_0_5',
                    'FIRST_HALF_GOALS_15' => 'fh_ou_1_5',
                    default => strtolower((string) ($marketType ?? $marketKey)),
                };

                $mainArray[] = [
                    'id' => (int) sprintf('%u', crc32(($data['unique_key'] ?? '').'_'.($marketId ?? $marketKey))),
                    'sport' => 'football',
                    'sportIcon' => '⚽',
                    'status' => $isLive ? 'LIVE' : 'UP',
                    'statusPriority' => $statusPriority,
                    'time' => $displayTime,
                    'comp' => $data['tournament'] ?? '',
                    'match' => ($data['team1'] ?? '').' vs '.($data['team2'] ?? ''),
                    'score' => $this->getScore($data),
                    'market' => $uiMarket,
                    'marketName' => $marketName ?? $marketType ?? $marketKey,
                    'marketDisplayScore' => $uiMarket === 'correct_score' ? $this->getScore($data) : null,
                    'runners' => $runners,
                    'favIdx' => $favoriteIndex,
                    'favorite' => $favoriteIndex !== null ? $runners[$favoriteIndex] : null,
                    'back' => $favoriteBackOdds,
                    'backSize' => $totalBackSize,
                    'lay' => (float) $runners[$favoriteIndex]['lay'],
                    'laySize' => $totalLaySize,
                    'balance' => $balance,
                    'momentum' => $momentum,
                    'delta' => $delta,
                    'volatility' => $volatility,
                    'totalBackSize' => $totalBackSize,
                    'totalLaySize' => $totalLaySize,
                    'marketLiquidity' => $totalBackSize + $totalLaySize,
                    'totalLiquidity' => $eventTotalLiquidity,
                ];
            }
        }

        usort($mainArray, function (array $first, array $second): int {
            $statusComparison = $first['statusPriority'] <=> $second['statusPriority'];

            if($statusComparison !== 0) {
                return $statusComparison;
            }

            return $second['totalLiquidity'] <=> $first['totalLiquidity'];
        });

        return $mainArray;
    }
}
