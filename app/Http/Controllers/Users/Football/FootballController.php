<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use App\Traits\FootballTrait;
use Illuminate\Support\Facades\DB;

class FootballController extends Controller
{
    use FootballTrait;

    public function football()
    {
        $data = $this->getFootballMatches();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->footballHasMore;
        $nextPage = $this->footballNextPage;
        return view('users.sports.football.football', compact('data', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function marketActivity()
    {
        if (request()->routeIs('football.pinnacle-odds')) {
            $pinnaclePayload = getInRedis('Pinnacle:drops');
            $pinnacleFeedConnected = is_array($pinnaclePayload);
            $pinnacleAlerts = $pinnaclePayload ?? [];
            $pinnacleAlerts = $pinnacleAlerts['alerts'] ?? $pinnacleAlerts['data'] ?? $pinnacleAlerts;
            $pinnacleAlerts = is_array($pinnacleAlerts) ? array_values($pinnacleAlerts) : [];

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $pinnacleAlerts,
                    'connected' => $pinnacleFeedConnected,
                ]);
            }

            return view('users.sports.football.marketActivity', compact('pinnacleAlerts', 'pinnacleFeedConnected'));
        }

        $marketActivites = $this->getMarketActivites();
        $hasMore = $this->marketActivityHasMore;
        $nextPage = $this->marketActivityNextPage;

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $marketActivites,
                'has_more' => $hasMore,
                'next_page' => $nextPage,
            ]);
        }

        return view('users.sports.football.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId)
    {
        $match = $this->getFootballMatchDetail($matchId);

        abort_if($match === null, 404);

        return view('users.sports.football.matchDetail', compact('match'));
    }

    public function filterFootball()
    {
        $prediction = request()->input('prediction');
        if (is_array($prediction)) {
            $matchId = $prediction['match_id'] ?? null;
            $poll = $prediction['poll'] ?? null;
            $value = $prediction['value'] ?? null;
            $allowedValues = [
                '1x2' => ['1', 'X', '2'],
                'uo' => ['UNDER', 'OVER'],
            ];
            $sessionKey = "football_predictions.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('Football:matches', function () use ($matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('Football:matches') ?? [];
                    $matchKey = null;

                    foreach ($matches as $key => $match) {
                        if (
                            ($match['data_sync_complete'] ?? false) !== true
                            || ($match['sportsApiPro_match_id'] ?? null) != $matchId
                            || ($match['result_status'] ?? null) !== 'Prematch'
                        ) {
                            continue;
                        }

                        $matchKey = $key;
                        break;
                    }

                    if ($matchKey === null) {
                        return;
                    }

                    $userId = request()->user()?->getAuthIdentifier();
                    $sessionId = $userId === null ? request()->session()->getId() : null;
                    $ownerQuery = DB::table('prediction_votes')
                        ->where('sport', 'football')
                        ->where('match_id', (string) $matchId)
                        ->where('poll', $poll);

                    if ($userId === null) {
                        $ownerQuery->where('session_id', $sessionId);
                    } else {
                        $ownerQuery->where('user_id', $userId);
                    }

                    $storedValue = $ownerQuery->value('value');

                    if ($storedValue !== null) {
                        request()->session()->put($sessionKey, $storedValue);

                        return;
                    }

                    $voteKey = hash('sha256', implode('|', [
                        $userId === null ? 'session' : 'user',
                        $userId ?? $sessionId,
                        'football',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = DB::table('prediction_votes')->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'user_id' => $userId,
                        'session_id' => $sessionId,
                        'sport' => 'football',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('Football:matches', $matches, 10);
                        request()->session()->put($sessionKey, $value);
                    } else {
                        $storedValue = $ownerQuery->value('value');
                        if ($storedValue !== null) {
                            request()->session()->put($sessionKey, $storedValue);
                        }
                    }
                });
            }
        }

        $data = $this->getFootballMatches();

        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->footballHasMore,
            'next_page' => $this->footballNextPage,
        ]);
    }

    public function websocketFootball()
    {
        $data = $this->getFootballMatches();

        return response()->json([
            'html' => view('users.sports.football.footballDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->footballHasMore,
            'next_page' => $this->footballNextPage,
        ]);
    }

    public function tempDataMerge()
    {
        $data = $this->getMarketActivites();
        dd($data);
        dd($this->getFootballMatches());
    }
}
