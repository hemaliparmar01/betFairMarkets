<?php

namespace App\Http\Controllers\Users\Greyhounds;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Traits\GreyhoundsTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class GreyhoundsController extends Controller
{
    use GreyhoundsTrait;

    public function greyhounds(): View|JsonResponse
    {
        if (! checkAuthAndSubscription()['display_odds']) {
            return $this->marketActivity();
        }

        $data = $this->getGreyhoundsMatches();
        $hasMore = $this->greyhoundsHasMore;
        $nextPage = $this->greyhoundsNextPage;

        return view('users.sports.greyhounds.greyhounds', compact('data', 'hasMore', 'nextPage'));
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

            return view('users.sports.greyhounds.marketActivity', compact('checkAuthAndSubscription'));
        }

        $marketActivites = $this->getMarketActivites();
        $hasMore = $this->greyhoundsHasMore;
        $nextPage = $this->greyhoundsNextPage;
        if (request()->expectsJson()) {
            return response()->json(['feed' => array_merge(racingFeedHealth('greyhounds'), ['next_refresh_at' => $this->greyhoundsNextRefreshAt]), 'html' => view('users.sports.greyhounds.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'))->fragment('greyhoundsMarketRows'), 'has_more' => $hasMore, 'next_page' => $nextPage]);
        }

        return view('users.sports.greyhounds.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId): View|JsonResponse
    {
        if (! checkAuthAndSubscription()['display_odds']) {
            return $this->marketActivity();
        }

        $match = $this->getGreyhoundsMatchDetail($matchId);
        abort_if($match === null, 404);

        $available = $match['details_available'];
        $displayOdds = $available && checkAuthAndSubscription()['display_odds'];
        $runners = array_values($match['markets'][$matchId.'_WIN'] ?? []);
        $customerId = request()->user('customers')?->getAuthIdentifier();
        $votes = PredictionVote::query()->where('sport', 'greyhounds')->where('match_id', $matchId)->where('poll', 'winner');
        $counts = (clone $votes)->select('value')->selectRaw('COUNT(*) as total')->groupBy('value')->pluck('total', 'value');

        $detail = [
            'feed' => array_merge(racingFeedHealth('greyhounds'), ['next_refresh_at' => $this->greyhoundsNextRefreshAt]),
            'race' => ['id' => $matchId, 'available' => $available, 'available_at' => gmdate('Y-m-d\TH:i:s\Z', $match['timestamp'] - 300), 'course' => $match['team1'], 'description' => $match['team2'], 'meeting' => $match['tournament'], 'country' => $match['country'] ?? null, 'start_time' => gmdate('Y-m-d\TH:i:s\Z', $match['timestamp']), 'status' => $match['status'], 'market_status' => $match['market_status'] ?? 'OPEN'],
            'markets' => $displayOdds ? array_map(static fn (array $selections): array => ['type' => $selections[array_key_first($selections)]['marketType'], 'runners' => array_values(array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['marketId', 'handicap', 'runner', 'runnerId', 'trap', 'number', 'initialBackOdds', 'initialBackOddsTimestamp', 'opening_odds_source', 'backOddsHistory', 'odds_history_incomplete', 'currentBackOdds', 'previousBackOdds', 'previousLayOdds', 'backSize', 'layOdds', 'laySize', 'pressure_type', 'pressure_amount', 'pressure_source'])), $selections))], array_values($match['markets'])) : [],
            'market' => ['title' => $match['team1'].' · '.$match['team2'], 'status' => $match['status'], 'minutes' => $match['minutes'], 'available' => $available, 'locked' => ! $displayOdds,
                'runners' => $displayOdds ? array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['marketId', 'handicap', 'runner', 'runnerId', 'trap', 'number', 'initialBackOdds', 'initialBackOddsTimestamp', 'opening_odds_source', 'backOddsHistory', 'odds_history_incomplete', 'currentBackOdds', 'previousBackOdds', 'previousLayOdds', 'backSize', 'layOdds', 'laySize', 'pressure_type', 'pressure_amount', 'pressure_source'])), $runners) : [],
                'indicators' => $displayOdds ? ($match['market_indicators'][$matchId.'_WIN'] ?? []) : []],
            'prediction' => ['choices' => $available ? array_column($runners, 'runner', 'runnerId') : [], 'counts' => $available ? $counts : [],
                'vote' => $customerId === null ? null : (clone $votes)->where('customer_id', $customerId)->value('value'),
                'locked' => ! $available || $match['status'] === 'Finished' || $match['timestamp'] <= time()],
        ];
        if (request()->expectsJson()) {
            return response()->json($detail);
        }

        return view('users.sports.greyhounds.matchDetail', compact('match', 'detail'));
    }

    public function filterGreyhounds(): JsonResponse
    {
        abort_unless(checkAuthAndSubscription()['display_odds'], 403, __('Registration and an active 7-day free trial or paid subscription are required to access Horse Racing and Greyhounds.'));

        $prediction = request()->input('prediction');
        if (is_array($prediction)) {
            $customerId = request()->user('customers')?->getAuthIdentifier();
            abort_if($customerId === null, 401, 'Please log in to submit predictions.');
            request()->validate(['prediction.match_id' => ['required', 'string', 'max:255'], 'prediction.poll' => ['required', 'in:winner'], 'prediction.value' => ['required', 'string', 'max:50']]);
            $matchId = $prediction['match_id'] ?? null;
            $poll = $prediction['poll'] ?? null;
            $value = $prediction['value'] ?? null;
            $allowedValues = [
                'winner' => [(string) $value],
            ];
            $sessionKey = "greyhounds_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('Greyhounds:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('Greyhounds:matches') ?? [];
                    $matchKey = null;

                    foreach ($matches as $key => $match) {
                        if (
                            ($match['data_sync_complete'] ?? false) !== true
                            || ($match['betfair_match_id'] ?? null) != $matchId
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

                    abort_unless(now()->timestamp >= (int) $matches[$matchKey]['timestamp'] - 300, 422, 'Race details become available five minutes before the start.');
                    $runners = $matches[$matchKey]['markets'][$matchId.'_WIN'] ?? [];
                    abort_unless(array_any($runners, static fn (array $runner): bool => (string) $runner['runnerId'] === (string) $value), 422, 'Select a valid race runner.');
                    abort_unless((int) $matches[$matchKey]['timestamp'] > time(), 422, 'Voting closes at the off time.');

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'greyhounds')
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
                        'greyhounds',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
                        'sport' => 'greyhounds',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('Greyhounds:matches', $matches, 10);
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

        $data = $this->getGreyhoundsMatches();

        return response()->json([
            'feed' => array_merge(racingFeedHealth('greyhounds'), ['next_refresh_at' => $this->greyhoundsNextRefreshAt]),
            'html' => view('users.sports.greyhounds.greyhoundsDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->greyhoundsHasMore,
            'next_page' => $this->greyhoundsNextPage,
        ]);
    }

    public function websocketGreyhounds(): JsonResponse
    {
        abort_unless(checkAuthAndSubscription()['display_odds'], 403, __('Registration and an active 7-day free trial or paid subscription are required to access Horse Racing and Greyhounds.'));

        $data = $this->getGreyhoundsMatches();

        return response()->json([
            'feed' => array_merge(racingFeedHealth('greyhounds'), ['next_refresh_at' => $this->greyhoundsNextRefreshAt]),
            'html' => view('users.sports.greyhounds.greyhoundsDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->greyhoundsHasMore,
            'next_page' => $this->greyhoundsNextPage,
        ]);
    }
}
