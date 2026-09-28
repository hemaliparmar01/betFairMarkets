<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

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
            if(!$data['data_sync_complete']) {
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
                'minutes' => $this->calculateMinutes($data['minutes_status'],$data['timestamp'],$data['result_status']),
                'cards' => $data['cards'] ?? [],
                'markets' => $markets[$marketKey] ?? [],
            ];
        }
        return $mainArray;
    }

    public function calculateMinutes(string $minute_status,int $timestamp,string $result_status) {
        if($result_status == 'Live') {
            if($minute_status == 'Started') {
                return "Live";
            } elseif(in_array($minute_status, ['Halftime', 'Half Time', 'HT'], true)) {
                return "HT";
            } elseif($minute_status == '1st half' || $minute_status == '2nd half') {
                $elapsedMinutes = (int) floor((time() - $timestamp) / 60);

                if($minute_status == '1st half') {
                    return $elapsedMinutes > 45 ? "45+" : $elapsedMinutes;
                }

                $currentMinute = $elapsedMinutes - 15;
                return $currentMinute > 90 ? "90+" : $currentMinute;
            }
        } else {
            return "mishdasd";
        }
    }

    public function getScore(array $match) {
        if($match['result_status'] == 'Live') {
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
}
