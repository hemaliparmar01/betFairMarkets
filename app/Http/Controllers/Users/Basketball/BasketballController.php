<?php

namespace App\Http\Controllers\Users\Basketball;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Services\MatchBackup;
use App\Traits\BasketballTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class BasketballController extends Controller
{
    use BasketballTrait;

    public function basketball(): View
    {
        $data = $this->getBasketballMatches();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->basketballHasMore;
        $nextPage = $this->basketballNextPage;

        return view('users.sports.basketball.basketball', compact('data', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function marketActivity(): View|JsonResponse
    {
        $checkAuthAndSubscription = checkAuthAndSubscription();
        if (! $checkAuthAndSubscription['display_odds']) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => __('Register and activate your 7-day free trial (first-time users), or purchase a paid subscription to access Market Activity and Pinnacle Drops.'),
                    'access_required' => true,
                    'data' => [],
                ], 403);
            }

            return view('users.sports.basketball.marketActivity', compact('checkAuthAndSubscription'));
        }

        $marketActivites = $this->getMarketActivites();
        $getAllMarkets = $this->getAllMarkets();
        $hasMore = $this->marketActivityHasMore;
        $nextPage = $this->marketActivityNextPage;
        if (request()->expectsJson()) {
            return response()->json(['html' => view('users.sports.basketball.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'))->fragment('basketballMarketRows'), 'has_more' => $hasMore, 'next_page' => $nextPage]);
        }

        return view('users.sports.basketball.marketActivity', compact('marketActivites', 'getAllMarkets', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId): View
    {
        $match = $this->getBasketballMatchDetail($matchId);
        abort_if($match === null, 404);

        return view('users.sports.basketball.matchDetail', compact('match'));
    }

    public function filterBasketball(): JsonResponse
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
            $sessionKey = "basketball_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('Basketball:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('Basketball:matches') ?? [];
                    $matchKey = null;

                    foreach ($matches as $key => $match) {
                        if (
                            MatchBackup::isHidden($match, 'basketball')
                            || ($match['data_sync_complete'] ?? false) !== true
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
                        $hasDraw = array_any($matches[$matchKey]['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => ($runner['marketType'] ?? '') === 'MATCH_ODDS' && in_array(strtolower((string) ($runner['runner'] ?? '')), ['draw', 'the draw', 'tie'], true)));
                        abort_unless($hasDraw, 422, 'Tie is not a selection in this match market.');
                    }

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'basketball')
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
                        'basketball',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
                        'sport' => 'basketball',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('Basketball:matches', $matches, 10);
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

        $data = $this->getBasketballMatches();

        return response()->json([
            'html' => view('users.sports.basketball.basketballDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->basketballHasMore,
            'next_page' => $this->basketballNextPage,
        ]);
    }

    public function websocketBasketball(): JsonResponse
    {
        $data = $this->getBasketballMatches();

        return response()->json([
            'html' => view('users.sports.basketball.basketballDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->basketballHasMore,
            'next_page' => $this->basketballNextPage,
        ]);
    }
}
