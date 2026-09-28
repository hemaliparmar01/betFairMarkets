<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FootballSyncController extends Controller
{
    public string $getBetferKeys;
    public string $getsportsAPIProKeys;
    public string $sports;

    public function __construct() {
        $this->getBetferKeys = GetBetfairKey();
        $this->getsportsAPIProKeys = GetSportApiProKey();
        $this->sports = 'Football';
    }

    public function syncBetFairLiveData() {
        $url = "https://betfair-odds.com/betfair/v1/livemap?feed=live&sports=soccer";
        $key = "x-portal-apikey: " . $this->getBetferKeys;
        $liveData = ApiCall($url,$key);
        $cacheKey = $this->sports.':matches';
        return $this->readBetFairData($liveData,$cacheKey,true,10);
    }

    public function syncBetFairPrematchData() {
        $url = "https://betfair-odds.com/betfair/v1/livemap?feed=prematch&sports=soccer";
        $key = "x-portal-apikey: " . $this->getBetferKeys;
        $liveData = ApiCall($url,$key);
        $cacheKey = $this->sports.':matches';
        return $this->readBetFairData($liveData,$cacheKey,false,30);
    }

    public function syncSportsApiProLiveData() {
        $url = "https://api.sportsapipro.com/v2/football/live";
        $key = "x-api-key: " . $this->getsportsAPIProKeys;
        $liveData = ApiCall($url,$key);
        $cacheKey = $this->sports.':matches';
        return $this->readsportApiData($liveData,$cacheKey,10);
    }

    private function readBetFairData(array $payloads,string $cacheKey, bool $isLiveApi,int $ttlSeconds) {
        $mainArray = [];
        $getExistingRecords = getInRedis($cacheKey);
        foreach ($payloads['liveMap'] as $payloadKey => $payload) {
            $currentTimestamp = Carbon::parse($payload['openTimestamp'])->timestamp;
            $team1 = $payload['team1'];
            $team2 = $payload['team2'];
            $uniqueKey = $this->generateUniqueKey($team1,$team2,$currentTimestamp);

            $checkIsExists = $this->checkIfRecordExistsOnAnotherApi($uniqueKey,$team1,$team2,$currentTimestamp);
            if($checkIsExists) {
                $tempArray = $getExistingRecords[$checkIsExists];
                $data_sync_complete = isset($getExistingRecords[$checkIsExists]['sportsApiPro_match_id']);
                $tempArray['data_sync_complete'] = $data_sync_complete;
            } else {
                $tempArray = [];
                $tempArray['data_sync_complete'] = false;
            }

            $getSingleRecords = $getExistingRecords[$payloadKey] ?? [];

            $result_status = $isLiveApi == true ? 'Live' : 'Prematch';
            $tempArray['unique_key'] = $uniqueKey;
            $tempArray['betfair_match_id'] = $payloadKey;
            $tempArray['team1'] = $team1;
            $tempArray['team2'] = $team2;
            $tempArray['tournament'] = $payload['tournament'];
            $tempArray['result_status'] = $result_status;
            $tempArray['timestamp'] = $currentTimestamp;
            $markets = $this->filterSupportedMarkets($payload['markets']);
            $tempMarketArray = [];
            foreach ($markets as $market) {
                $sortPriority = $market['sortPriority'] ?? null;
                $marketKey = $market['marketId'].'_'.$market['marketType'];

                if(!empty($getSingleRecords)) {
                    $previousBackOdds = $getSingleRecords['markets'][$marketKey][$sortPriority]['backOdds'] ?? $market['backOdds'];
                } else {
                    $previousBackOdds = $market['backOdds'];
                }
                $currentOdds = $market['backOdds'];
                $market['previousBackOdds'] = $previousBackOdds;
                $market['currentBackOdds'] = $currentOdds;
                $market['odds_direction'] = $this->matchDirection($previousBackOdds,$currentOdds);
                $tempMarketArray[$marketKey][$sortPriority] = $market;
                $tempArray['markets'] = $tempMarketArray;
            }
            $mainArray[$uniqueKey] = $tempArray;
        }
        storeInRedis($cacheKey,$mainArray,$ttlSeconds);
        dd($mainArray);
    }

    private function readsportApiData(array $payloads, string $cacheKey,int $ttlSeconds) {
        if(empty($payloads)) {
            return;
        }
        $getExistingRecords = getInRedis($cacheKey);
        foreach ($payloads['events'] as $payloadKey => $payload) {
            $tempArray = [];
            $uniqueKey = $this->generateUniqueKey($payload['homeTeam'],$payload['awayTeam'],$payload['startTimestamp']);
            $checkIsExists = $this->checkIfRecordExistsOnAnotherApi($uniqueKey,$payload['homeTeam'],$payload['awayTeam'],$payload['startTimestamp']);
            if($checkIsExists) {
                $data_sync_complete = isset($getExistingRecords[$checkIsExists]['betfair_match_id']);
                $additionalData = [
                    'data_sync_complete' => $data_sync_complete,
                    'home_score' => $payload['homeScore'],
                    'sportsApiPro_match_id' => $payload['id'],
                    'away_score' => $payload['awayScore'],
                    'minutes_status' => $payload['status'],
                ];
                $getExistingRecords[$checkIsExists] = array_merge($getExistingRecords[$checkIsExists] ,$additionalData);
            } else {
                $tempArray['unique_key'] = $uniqueKey;
                $tempArray['sportsApiPro_match_id'] = $payload['id'];
                $tempArray['timestamp'] = $payload['startTimestamp'];
                $tempArray['data_sync_complete'] = false;
                $tempArray['home_score'] = $payload['homeScore'];
                $tempArray['away_score'] = $payload['awayScore'];
                $tempArray['minutes_status'] = $payload['status'];
                $tempArray['team1'] = $payload['homeTeam'];
                $tempArray['team2'] = $payload['awayTeam'];
                $getExistingRecords[$uniqueKey] = $tempArray;
            }
        }
        storeInRedis($cacheKey,$getExistingRecords,$ttlSeconds);
        dd($getExistingRecords);
    }

    public function getCacheStoreData() {
        $data = getInRedis($this->sports.':matches');
        foreach ($data as $key => $value) {
            dump($value);
        }
        dd("1");
    }

    private function matchDirection(float $previousOdds,float $currentOdds) {
        if($currentOdds ==  $previousOdds) {
            return "same";
        } elseif($currentOdds < $previousOdds) {
            return "down";
        } elseif($currentOdds > $previousOdds) {
            return "up";
        }
    }

    private function filterSupportedMarkets(array $markets) {
        $storeOnlyMarkets = ["MATCH_ODDS","OVER_UNDER_05","OVER_UNDER_25","OVER_UNDER_35","OVER_UNDER_45","OVER_UNDER_55","OVER_UNDER_65","OVER_UNDER_75","OVER_UNDER_85","BOTH_TEAMS_TO_SCORE","CORRECT_SCORE","HALF_TIME","HALF_TIME_FULL_TIME","DRAW_NO_BET,HALF_TIME_SCORE","OVER_UNDER_15"];
        return array_values(array_filter($markets, fn ($market) => in_array($market['marketType'],$storeOnlyMarkets,true)));
    }

    private function generateUniqueKey(string $teamA,string $teamB,string $timestamp) {
        $teamA = $this->normalizeTeamName($teamA);
        $teamB = $this->normalizeTeamName($teamB);
        $generatedName = $teamA.'|'.$teamB.'|'.$timestamp;
        return $generatedName;
    }

    private function normalizeTeamName(string $name): string {
        $name = Str::ascii($name);
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\s]/', ' ', $name);
        $replacements = [
            ' utd ' => ' united ',
            ' ath ' => ' athletic ',
            ' st '  => ' saint ',
        ];
        $name = ' ' . $name . ' ';
        foreach ($replacements as $from => $to) {
            $name = str_replace($from, $to, $name);
        }
        $name = trim(preg_replace('/\s+/', ' ', $name));
        return str_replace(' ', '_', $name);
    }

    // private function checkIfRecordExistsOnAnotherApi(string $uniqueKey,string $team1,string $team2,string $timestamp) {
    //     $existingRecords = getInRedis($this->sports.':matches');
    //     if (array_key_exists($uniqueKey, $existingRecords)) {
    //         dump('Exact Match Found', $existingRecords[$uniqueKey]);
    //     } else {
    //         foreach ($existingRecords as $key => $record) {
    //             $sameTimestamp = ($record['timestamp'] ?? null) === $timestamp;
    //             $team1Match = $this->teamSimilarity($record['team1'] ?? '', $team1) >= 80;
    //             $team2Match = $this->teamSimilarity($record['team2'] ?? '',$team2) >= 80;

    //             if ($sameTimestamp && $team1Match && $team2Match) {
    //                 dump('Similar Match Found', $key, $record);
    //                 break;
    //             }
    //         }
    //     }
    // }

    private function checkIfRecordExistsOnAnotherApi(string $uniqueKey,string $team1, string $team2,string|int $timestamp) {
        $existingRecords = getInRedis($this->sports . ':matches') ?? [];

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
            $team2Score = $this->teamSimilarity($record['team2'] ?? '',$team2);

            if ($team1Score >= 80 && $team2Score >= 80) {
                return $key;
            }
        }
        return null;
    }

    private function teamSimilarity(string $teamA, string $teamB): float {
        $teamA = $this->normalizeTeamName($teamA);
        $teamB = $this->normalizeTeamName($teamB);

        if ($teamA === $teamB) {
            return 100;
        }

        similar_text($teamA, $teamB, $percentage);

        return $percentage;
    }
}
