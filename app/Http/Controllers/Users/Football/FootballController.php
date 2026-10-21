<?php

namespace App\Http\Controllers\Users\Football;

use App\Http\Controllers\Controller;
use App\Models\PredictionVote;
use App\Traits\FootballTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class FootballController extends Controller
{
    use FootballTrait;

    public function football()
    {
        $data = $this->getFootballMatches();
        $hasMore = $this->footballHasMore;
        $nextPage = $this->footballNextPage;

        return view('users.sports.football.football', compact('data', 'hasMore', 'nextPage'));
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

            return view('users.sports.football.marketActivity', compact('checkAuthAndSubscription'));
        }

        if (request()->routeIs('football.pinnacle-odds')) {
            $pinnaclePayload = getInRedis('Pinnacle:drops');
            $pinnacleAlerts = $pinnaclePayload ?? [];
            $pinnacleAlerts = $pinnacleAlerts['alerts'] ?? $pinnacleAlerts['data'] ?? $pinnacleAlerts;
            $pinnacleAlerts = is_array($pinnacleAlerts) ? array_values($pinnacleAlerts) : [];
            $pinnacleFeeds = [];
            foreach (['live', 'prematch'] as $mode) {
                $feed = $pinnaclePayload['feeds'][$mode] ?? [];
                $pinnacleFeeds[$mode] = time() - (int) ($feed['last_frame_at'] ?? 0) <= 90
                    ? ($feed['status'] ?? 'disconnected')
                    : (($feed['status'] ?? null) === 'connecting' && time() - (int) ($pinnaclePayload['updated_at'] ?? 0) <= 90 ? 'connecting' : 'disconnected');
            }
            $pinnacleFeedConnected = $pinnacleFeeds['prematch'] === 'connected';
            $normalizeName = static function (string $name): string {
                $name = ' '.trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($name)))).' ';

                return trim(str_replace([' utd ', ' ath ', ' st '], [' united ', ' athletic ', ' saint '], $name));
            };
            $footballMatches = [];
            foreach (getInRedis('Football:matches') ?? [] as $match) {
                if (($match['data_sync_complete'] ?? false) === true && ($match['result_status'] ?? null) !== 'Finished') {
                    $footballMatches[$normalizeName((string) $match['team1']).'|'.$normalizeName((string) $match['team2'])][] = $match;
                }
            }

            $pinnacleEvents = array_column($pinnacleAlerts, null, 'id');

            foreach ($pinnacleAlerts as &$alert) {
                $alert['bf_back'] = $alert['bf_lay'] = $alert['bf_back_size'] = $alert['bf_lay_size'] = null;
                $alert['full_market'] = [];
                if (isset($alert['parent_id'], $pinnacleEvents[$alert['parent_id']])) {
                    $parent = $pinnacleEvents[$alert['parent_id']];
                    $alert['home'] = $parent['home'];
                    $alert['away'] = $parent['away'];
                }
                $period = (int) ($alert['period'] ?? 0);
                $section = strtolower(trim((string) ($alert['sect'] ?? '')));
                $outcome = strtolower(trim((string) ($alert['outcome'] ?? '')));
                $line = $alert['point'] ?? $alert['points'] ?? $alert['hdp'] ?? null;
                if (! is_numeric($line) && preg_match('/(?:over|under|total|o\/u)\s*([0-9]+(?:\.[0-9]+)?)/i', $section.' '.$outcome, $lineMatch)) {
                    $line = $lineMatch[1];
                }
                $marketType = match (true) {
                    $period === 0 && in_array($section, ['moneyline', 'money line', '1x2', 'match odds'], true) => 'MATCH_ODDS',
                    $period === 1 && in_array($section, ['moneyline', 'money line', '1x2'], true) => 'HALF_TIME',
                    $period === 0 && preg_match('/^(total|totals|o\/u)/', $section) === 1 && is_numeric($line) && fmod((float) $line, 1.0) === 0.5 => 'OVER_UNDER_'.(int) round((float) $line * 10),
                    $period === 0 && in_array($section, ['both teams to score', 'btts'], true) => 'BOTH_TEAMS_TO_SCORE',
                    $period === 0 && in_array($section, ['draw no bet', 'dnb'], true) => 'DRAW_NO_BET',
                    $period === 0 && in_array($section, ['spread', 'spreads', 'asian handicap'], true) && is_numeric($line) => 'ASIAN_HANDICAP',
                    default => null,
                };
                $runners = [];
                if (($alert['sport'] ?? null) === 'soccer' && $marketType !== null) {
                    $candidates = $footballMatches[$normalizeName((string) ($alert['home'] ?? '')).'|'.$normalizeName((string) ($alert['away'] ?? ''))] ?? [];
                    $starts = $alert['starts'] ?? null;
                    $startTimestamp = is_numeric($starts) ? (int) $starts : (is_string($starts) ? strtotime($starts) : false);
                    if ($startTimestamp && $startTimestamp > 100_000_000_000) {
                        $startTimestamp = (int) floor($startTimestamp / 1000);
                    }
                    $candidates = array_values(array_filter($candidates, static fn (array $match): bool => (($alert['is_live'] ?? false) ? 'Live' : 'Prematch') === $match['result_status']
                        && (! $startTimestamp || abs((int) $match['timestamp'] - $startTimestamp) <= 3600)));
                    if (count($candidates) === 1) {
                        foreach ($candidates[0]['markets'] ?? [] as $marketRunners) {
                            if (is_array($marketRunners) && ($marketRunners[array_key_first($marketRunners)]['marketType'] ?? null) === $marketType) {
                                $runners = array_values($marketRunners);
                                if ($marketType === 'ASIAN_HANDICAP') {
                                    $homeHandicap = isset($alert['point']) && (in_array($outcome, ['away', '2'], true) || $normalizeName($outcome) === $normalizeName((string) ($alert['away'] ?? '')))
                                        ? -(float) $line
                                        : (float) $line;
                                    $runners = array_values(array_filter($runners, static fn (array $runner): bool => abs((float) ($runner['handicap'] ?? 0) - ($normalizeName((string) ($runner['runner'] ?? '')) === $normalizeName((string) ($alert['home'] ?? '')) ? $homeHandicap : -$homeHandicap)) < 0.0001));
                                    if ($runners === []) {
                                        continue;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
                $matchesOutcome = static function (array $runner, string $selection) use ($alert, $normalizeName): bool {
                    $name = $normalizeName((string) ($runner['runner'] ?? ''));

                    return match (strtolower($selection)) {
                        'home', '1' => $name === $normalizeName((string) ($alert['home'] ?? '')),
                        'away', '2' => $name === $normalizeName((string) ($alert['away'] ?? '')),
                        'draw', 'x' => in_array($name, ['draw', 'the draw'], true),
                        'over' => str_starts_with($name, 'over '),
                        'under' => str_starts_with($name, 'under '),
                        'yes', 'no' => $name === $selection,
                        default => $name === $normalizeName($selection),
                    };
                };
                $selectedRunner = array_find($runners, static fn (array $runner): bool => $matchesOutcome($runner, $outcome) || (str_starts_with($outcome, 'over ') && $matchesOutcome($runner, 'over')) || (str_starts_with($outcome, 'under ') && $matchesOutcome($runner, 'under')));
                if ($selectedRunner !== null) {
                    $alert['bf_back'] = (float) ($selectedRunner['currentBackOdds'] ?? $selectedRunner['backOdds'] ?? 0) > 1 ? (float) ($selectedRunner['currentBackOdds'] ?? $selectedRunner['backOdds']) : null;
                    $alert['bf_lay'] = (float) ($selectedRunner['layOdds'] ?? 0) > 1 ? (float) $selectedRunner['layOdds'] : null;
                    $alert['bf_back_size'] = $selectedRunner['backSize'] ?? null;
                    $alert['bf_lay_size'] = $selectedRunner['laySize'] ?? null;
                }
                $pinnaclePrices = match (true) {
                    in_array($section, ['moneyline', 'money line', '1x2', 'match odds', 'draw no bet', 'dnb', 'spread', 'spreads', 'asian handicap'], true) => ['home' => $alert['price_x'] ?? null, 'away' => $alert['price_y'] ?? null, 'draw' => $alert['price_z'] ?? null],
                    preg_match('/^(total|totals|o\/u|teamtotal)/', $section) === 1 => ['over' => $alert['price_x'] ?? null, 'under' => $alert['price_y'] ?? null],
                    in_array($section, ['both teams to score', 'btts'], true) => ['yes' => $alert['price_x'] ?? null, 'no' => $alert['price_y'] ?? null],
                    default => [],
                };
                $pinnaclePrices = array_filter($pinnaclePrices, static fn (mixed $price, mixed $side): bool => in_array($side, ['home', 'away', 'draw', 'over', 'under', 'yes', 'no'], true) && is_numeric($price) && (float) $price > 1, ARRAY_FILTER_USE_BOTH);
                if ($pinnaclePrices !== []) {
                    $impliedTotal = array_sum(array_map(static fn (mixed $price): float => 1 / (float) $price, $pinnaclePrices));
                    foreach ($pinnaclePrices as $side => $pinnaclePrice) {
                        $runner = array_find($runners, static fn (array $runner): bool => $matchesOutcome($runner, $side));
                        $alert['full_market'][] = ['outcome' => ucfirst($side), 'to_price' => (float) $pinnaclePrice, 'nvp' => strtolower($side) === $outcome ? ($alert['nvp'] ?? null) : (count($pinnaclePrices) > 1 && (float) ($alert['nvp'] ?? 0) > 0 ? (float) $pinnaclePrice * $impliedTotal : null), 'bf_back' => $runner['currentBackOdds'] ?? $runner['backOdds'] ?? null];
                    }
                } else {
                    $alert['full_market'][] = ['outcome' => $alert['outcome'] ?? '', 'to_price' => $alert['to_price'] ?? null, 'nvp' => $alert['nvp'] ?? null, 'bf_back' => $alert['bf_back']];
                }
            }
            unset($alert);

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $pinnacleAlerts,
                    'connected' => $pinnacleFeedConnected,
                    'feeds' => $pinnacleFeeds,
                ]);
            }

            return view('users.sports.football.marketActivity', compact('pinnacleAlerts', 'pinnacleFeedConnected', 'pinnacleFeeds'));
        }

        $marketActivites = $this->getMarketActivites();
        $hasMore = $this->marketActivityHasMore;
        $nextPage = $this->marketActivityNextPage;

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $marketActivites,
                'html' => view('users.sports.football.marketActivity', compact('marketActivites', 'hasMore', 'nextPage'))->fragment('footballMarketRows'),
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
            $customerId = request()->user('customers')?->getAuthIdentifier();
            abort_if($customerId === null, 401, 'Please log in to submit predictions.');
            $matchId = $prediction['match_id'] ?? null;
            $poll = $prediction['poll'] ?? null;
            $value = $prediction['value'] ?? null;
            $allowedValues = [
                '1x2' => ['1', 'X', '2'],
                'uo' => ['UNDER', 'OVER'],
            ];
            $sessionKey = "football_predictions.{$customerId}.{$matchId}.{$poll}";

            if ($matchId !== null && isset($allowedValues[$poll]) && in_array($value, $allowedValues[$poll], true) && ! request()->session()->has($sessionKey)) {
                withRedisLock('Football:matches', function () use ($customerId, $matchId, $poll, $sessionKey, $value): void {
                    $matches = getInRedis('Football:matches') ?? [];
                    $matchKey = null;

                    foreach ($matches as $key => $match) {
                        if (
                            ($match['sportsApiPro_match_id'] ?? null) != $matchId
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

                    $ownerQuery = PredictionVote::query()
                        ->where('customer_id', $customerId)
                        ->where('sport', 'football')
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
                        'football',
                        $matchId,
                        $poll,
                    ]));
                    $now = now();
                    $inserted = PredictionVote::query()->insertOrIgnore([
                        'vote_key' => $voteKey,
                        'customer_id' => $customerId,
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
