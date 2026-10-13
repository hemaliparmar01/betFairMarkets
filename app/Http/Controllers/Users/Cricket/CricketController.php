<?php

namespace App\Http\Controllers\Users\Cricket;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Traits\CricketTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class CricketController extends Controller
{
    use CricketTrait;

    public function cricket(): View
    {
        $data = $this->getCricketMatches();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->cricketHasMore;
        $nextPage = $this->cricketNextPage;

        return view('users.sports.cricket.cricket', compact('data', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function marketActivity(): View|JsonResponse
    {
        $marketActivites = $this->getMarketActivites();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->marketActivityHasMore;
        $nextPage = $this->marketActivityNextPage;
        if (request()->expectsJson()) {
            return response()->json(['html' => view('users.sports.cricket.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'))->fragment('cricketMarketRows'), 'has_more' => $hasMore, 'next_page' => $nextPage]);
        }

        return view('users.sports.cricket.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId): View
    {
        $match = $this->getCricketMatchDetail($matchId);
        abort_if($match === null, 404);

        return view('users.sports.cricket.matchDetail', compact('match'));
    }

    public function filterCricket(): JsonResponse
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
            $sessionKey = "cricket_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('Cricket:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('Cricket:matches') ?? [];
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
                        $hasDraw = array_any($matches[$matchKey]['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => ($runner['marketType'] ?? '') === 'MATCH_ODDS' && in_array(strtolower((string) ($runner['runner'] ?? '')), ['draw', 'the draw'], true)));
                        abort_unless($hasDraw, 422, 'Draw is not a selection in this match market.');
                    }

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'cricket')
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
                        'cricket',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
                        'sport' => 'cricket',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('Cricket:matches', $matches, 10);
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

        $data = $this->getCricketMatches();

        return response()->json([
            'html' => view('users.sports.cricket.cricketDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->cricketHasMore,
            'next_page' => $this->cricketNextPage,
        ]);
    }

    public function websocketCricket(): JsonResponse
    {
        $data = $this->getCricketMatches();

        return response()->json([
            'html' => view('users.sports.cricket.cricketDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->cricketHasMore,
            'next_page' => $this->cricketNextPage,
        ]);
    }
}
