<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FootballTrait;

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
        $getData = getInRedis('Football:matches');
        if(empty($getData)) {
            return false;
        }
        $event = $request->data;
        foreach ($getData as $dataKey => &$cacheData) {
            try {
                if(!$cacheData['data_sync_complete']) {
                    continue;
                }
                if($cacheData['sportsApiPro_match_id'] == $event['id']) {
                    dump(" XJCNAC ZKXJCHZXKCJHZXCH ");
                    if(isset($event['status']['code'])) {
                        if($event['status']['code'] == 100) {
                            $getData[$dataKey]['result_status'] = 'Finished';
                        }
                    }
                }
            } catch (\Throwable $th) {
                dump($event);
                dump($request->data);
                dd($cacheData);
            }
        }
        $cacheKey = 'Football:matches';
        storeInRedis($cacheKey,$getData,10);

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
