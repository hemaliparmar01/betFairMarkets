<?php

namespace App\Http\Controllers\Users\HorseRacing;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Traits\HorseRacingTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class HorseRacingController extends Controller
{
    use HorseRacingTrait;

    public function horseracing(): View
    {
        $data = $this->getHorseRacingMatches();
        $hasMore = $this->horseracingHasMore;
        $nextPage = $this->horseracingNextPage;

        return view('users.sports.horseracing.horseracing', compact('data', 'hasMore', 'nextPage'));
    }

    public function marketActivity(): View|JsonResponse
    {
        $marketActivites = $this->getMarketActivites();
        $hasMore = $this->horseracingHasMore;
        $nextPage = $this->horseracingNextPage;
        if (request()->expectsJson()) {
            return response()->json(['html' => view('users.sports.horseracing.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'))->fragment('horseracingMarketRows'), 'has_more' => $hasMore, 'next_page' => $nextPage]);
        }

        return view('users.sports.horseracing.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'));
    }

    public function matchDetail(string $matchId): View|JsonResponse
    {
        $match = $this->getHorseRacingMatchDetail($matchId);
        abort_if($match === null, 404);

        $displayOdds = checkAuthAndSubscription()['display_odds'];
        $runners = array_values($match['markets'][$matchId.'_WIN'] ?? []);
        $customerId = request()->user('customers')?->getAuthIdentifier();
        $votes = PredictionVote::query()->where('sport', 'horseracing')->where('match_id', $matchId)->where('poll', 'winner');
        $counts = (clone $votes)->select('value')->selectRaw('COUNT(*) as total')->groupBy('value')->pluck('total', 'value');

        $detail = [
            'race' => ['id' => $matchId, 'course' => $match['team1'], 'description' => $match['team2'], 'meeting' => $match['tournament'], 'country' => $match['country'] ?? null, 'start_time' => gmdate('Y-m-d\TH:i:s\Z', $match['timestamp']), 'status' => $match['status'], 'market_status' => $match['market_status'] ?? 'OPEN'],
            'markets' => $displayOdds ? array_map(static fn (array $selections): array => ['type' => $selections[array_key_first($selections)]['marketType'], 'runners' => array_values(array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['runner', 'runnerId', 'number', 'initialBackOdds', 'currentBackOdds', 'previousBackOdds', 'previousLayOdds', 'backSize', 'layOdds', 'laySize', 'backOddsHistory', 'movementHistory', 'pressure_type', 'pressure_amount', 'pressure_source'])), $selections))], array_values($match['markets'])) : [],
            'market' => ['title' => $match['team1'].' · '.$match['team2'], 'status' => $match['status'], 'minutes' => $match['minutes'], 'locked' => ! $displayOdds,
                'runners' => $displayOdds ? array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['runner', 'runnerId', 'number', 'initialBackOdds', 'currentBackOdds', 'previousBackOdds', 'previousLayOdds', 'backSize', 'layOdds', 'laySize', 'backOddsHistory', 'movementHistory', 'pressure_type', 'pressure_amount', 'pressure_source'])), $runners) : [],
                'indicators' => $displayOdds ? ($match['market_indicators'][$matchId.'_WIN'] ?? []) : []],
            'prediction' => ['choices' => array_column($runners, 'runner', 'runnerId'), 'counts' => $counts,
                'vote' => $customerId === null ? null : (clone $votes)->where('customer_id', $customerId)->value('value'),
                'locked' => $match['status'] === 'Finished' || $match['timestamp'] <= time()],
        ];
        if (request()->expectsJson()) {
            return response()->json($detail);
        }

        return view('users.sports.horseracing.matchDetail', compact('match', 'detail'));
    }

    public function filterHorseRacing(): JsonResponse
    {
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
            $sessionKey = "horseracing_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('HorseRacing:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('HorseRacing:matches') ?? [];
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

                    $runners = $matches[$matchKey]['markets'][$matchId.'_WIN'] ?? [];
                    abort_unless(array_any($runners, static fn (array $runner): bool => (string) $runner['runnerId'] === (string) $value), 422, 'Select a valid race runner.');
                    abort_unless((int) $matches[$matchKey]['timestamp'] > time(), 422, 'Voting closes at the off time.');

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'horseracing')
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
                        'horseracing',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
                        'sport' => 'horseracing',
                        'match_id' => (string) $matchId,
                        'poll' => $poll,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted > 0) {
                        $matches[$matchKey]['predictions'][$poll][$value] = (int) ($matches[$matchKey]['predictions'][$poll][$value] ?? 0) + 1;
                        storeInRedis('HorseRacing:matches', $matches, 10);
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

        $data = $this->getHorseRacingMatches();

        return response()->json([
            'html' => view('users.sports.horseracing.horseracingDetails', [
                'data' => $data,
                'append' => request()->boolean('append'),
            ])->render(),
            'has_more' => $this->horseracingHasMore,
            'next_page' => $this->horseracingNextPage,
        ]);
    }

    public function websocketHorseRacing(): JsonResponse
    {
        $data = $this->getHorseRacingMatches();

        return response()->json([
            'html' => view('users.sports.horseracing.horseracingDetails', [
                'data' => $data,
            ])->render(),
            'has_more' => $this->horseracingHasMore,
            'next_page' => $this->horseracingNextPage,
        ]);
    }
}
