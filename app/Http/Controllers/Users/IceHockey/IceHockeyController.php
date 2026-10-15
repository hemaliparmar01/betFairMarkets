<?php

namespace App\Http\Controllers\Users\IceHockey;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Traits\IceHockeyTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class IceHockeyController extends Controller
{
    use IceHockeyTrait;

    public function icehockey(): View
    {
        $data = $this->getIceHockeyMatches();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->icehockeyHasMore;
        $nextPage = $this->icehockeyNextPage;

        return view('users.sports.icehockey.icehockey', compact('data', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function marketActivity(): View|JsonResponse
    {
        $marketActivites = $this->getMarketActivites();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->marketActivityHasMore;
        $nextPage = $this->marketActivityNextPage;
        if (request()->expectsJson()) {
            return response()->json(['html' => view('users.sports.icehockey.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'))->fragment('icehockeyMarketRows'), 'has_more' => $hasMore, 'next_page' => $nextPage]);
        }

        return view('users.sports.icehockey.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId): View
    {
        $match = $this->getIceHockeyMatchDetail($matchId);
        abort_if($match === null, 404);

        return view('users.sports.icehockey.matchDetail', compact('match'));
    }

    public function filterIceHockey(): JsonResponse
    {
        $prediction = request()->input('prediction');
        if (is_array($prediction)) {
            $customerId = request()->user('customers')?->getAuthIdentifier();
            abort_if($customerId === null, 401, 'Please log in to submit predictions.');
            $matchId = $prediction['match_id'] ?? null;
            $poll = $prediction['poll'] ?? null;
            $value = $prediction['value'] ?? null;
            $allowedValues = [
                '1x2' => ['1', 'X', '2'],
            ];
            $sessionKey = "ice_hockey_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('IceHockey:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('IceHockey:matches') ?? [];
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
                    if ($value === 'X') {
                        $hasDraw = array_any($matches[$matchKey]['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => ($runner['marketType'] ?? '') === 'RT_MATCH_ODDS' && in_array(strtolower((string) ($runner['runner'] ?? '')), ['draw', 'the draw', 'tie'], true)));
                        abort_unless($hasDraw, 422, 'Tie is not a selection in this match market.');
                    }

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'icehockey')
                        ->where('match_id', (string) $matchId)
                        ->where('poll', $poll);

                    $storedValue = $ownerQuery->value('value');

                    if ($storedValue !== null) {
                        request()->session()->put($sessionKey, $storedValue);

                        return;
                    }

                    $voteKey = hash('sha256', implode('|', [
                        'user',
                        $customerId,
                        'icehockey',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
                        'sport' => 'icehockey',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('IceHockey:matches', $matches, 10);
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

        $data = $this->getIceHockeyMatches();

        return response()->json([
            'html' => view('users.sports.icehockey.icehockeyDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->icehockeyHasMore,
            'next_page' => $this->icehockeyNextPage,
        ]);
    }

    public function websocketIceHockey(): JsonResponse
    {
        $data = $this->getIceHockeyMatches();

        return response()->json([
            'html' => view('users.sports.icehockey.icehockeyDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->icehockeyHasMore,
            'next_page' => $this->icehockeyNextPage,
        ]);
    }
}
