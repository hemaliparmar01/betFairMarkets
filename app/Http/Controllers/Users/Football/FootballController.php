<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FootballTrait;
use Illuminate\Support\Facades\Log;

class FootballController extends Controller
{
    use FootballTrait;

    public function football() {
        $data = $this->getFootballMatches();
        $getAllMarkets = $this->getAllMarkets();
        return view('users.sports.football.football',compact("data","getAllMarkets"));
    }

    public function marketActivity() {
        return view('users.sports.football.marketActivity');
    }

    public function filterFootball() {
        $data = $this->getFootballMatches();
        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
            ])->render(),
        ]);
    }

    public function websocketFootball(Request $request) {
        Log::info('WebSocket football event received', [
            'channel' => $request->channel,
            'data' => $request->data,
        ]);

        $getData = getInRedis('Football:matches');
        if (empty($getData)) {
            return false;
        }

        $event = $request->data;
        $channel = $request->channel;

        if (is_string($channel) && preg_match('/^match:(\d+):incidents$/', $channel, $channelMatch)) {
            $matchId = $channelMatch[1];
            $incidents = $event['incidents'] ?? [];

            foreach ($getData as $dataKey => $cacheData) {
                if (empty($cacheData['data_sync_complete'])) {
                    continue;
                }

                if ($cacheData['sportsApiPro_match_id'] != $matchId) {
                    continue;
                }

                $cards = [
                    'home' => null,
                    'away' => null,
                ];

                $getData[$dataKey]['events'] = $incidents;

                foreach ($incidents as $incident) {
                    if (($incident['incidentType'] ?? null) != 'card') {
                        continue;
                    }

                    $incidentClass = $incident['incidentClass'] ?? null;
                    if (! in_array($incidentClass, ['yellow', 'red'], true)) {
                        continue;
                    }

                    $side = ! empty($incident['isHome']) ? 'home' : 'away';
                    if ($incidentClass == 'red' || $cards[$side] === null) {
                        $cards[$side] = $incidentClass;
                    }
                }

                $getData[$dataKey]['cards'] = $cards;
                break;
            }
        }

        foreach ($getData as $dataKey => $cacheData) {
            if (is_string($channel) && str_ends_with($channel, ':incidents')) {
                break;
            }

            if (empty($cacheData['data_sync_complete'])) {
                continue;
            }

            if ($cacheData['sportsApiPro_match_id'] != $event['id']) {
                continue;
            }

            if (isset($event['homeScore']['current'])) {
                $getData[$dataKey]['home_score'] = $event['homeScore']['current'];
            }

            if (isset($event['awayScore']['current'])) {
                $getData[$dataKey]['away_score'] = $event['awayScore']['current'];
            }

            if (isset($event['status']['code'])) {
                if ($event['status']['code'] == 6) {
                    $getData[$dataKey]['minutes_status'] = '1st half';
                    $getData[$dataKey]['result_status'] = 'Live';
                } elseif ($event['status']['code'] == 7) {
                    $getData[$dataKey]['minutes_status'] = '2nd half';
                    $getData[$dataKey]['result_status'] = 'Live';
                } elseif ($event['status']['code'] == 31) {
                    $getData[$dataKey]['minutes_status'] = 'Halftime';
                    $getData[$dataKey]['result_status'] = 'Live';
                } elseif ($event['status']['code'] == 100) {
                    $getData[$dataKey]['result_status'] = 'Finished';
                }
            }

            break;
        }
        $cacheKey = 'Football:matches';
        storeInRedis($cacheKey, $getData, 10);

        $data = $this->getFootballMatches();

        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
            ])->render(),
        ]);
    }

    public function tempDataMerge() {
        dd($this->getFootballMatches());
    }
}
