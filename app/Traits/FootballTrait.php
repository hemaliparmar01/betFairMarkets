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
        $getData = getInRedis('Football:matches');
        foreach ($getData as $data) {
            if(!$data['data_sync_complete']) {
                continue;
            }
            if($data['result_status'] == 'Finished') {
                continue;
            }
            dump($data);
            dump($data['markets']);
            $mainArray = [];
            foreach ($data['markets'] as $markets) {
                $tempArray = [];
                $tempArray['status'] = "Live";
                $tempArray['min'] = "Live";
            }
        }
    }
}
