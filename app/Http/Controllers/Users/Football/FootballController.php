<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
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
        $marketActivites = $this->getMarketActivites();
        return view('users.sports.football.marketActivity', compact("marketActivites"));
    }

    public function filterFootball() {
        $data = $this->getFootballMatches();
        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
            ])->render(),
        ]);
    }

    public function websocketFootball() {
        $data = $this->getFootballMatches();

        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
            ])->render(),
        ]);
    }

    public function tempDataMerge() {
        $data = $this->getMarketActivites();
        dd($data);
        dd($this->getFootballMatches());
    }
}
