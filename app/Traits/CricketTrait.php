<?php

namespace App\Traits;

use App\Models\CricketMatch;
use App\Models\Favorite;
use App\Models\PredictionVote;
use Carbon\Carbon;

trait CricketTrait
{
    protected bool $cricketHasMore = false;

    protected ?int $cricketNextPage = null;

    protected bool $marketActivityHasMore = false;

    protected ?int $marketActivityNextPage = null;

    public function getCricketMatches(): array
    {
        if (in_array(request()->input('type'), ['ht00', '00_70', 'fav_losing', 'prematch_drops', 'market_shock', 'fav_not_winning'], true)) {
            $checkAuthAndSubscription = checkAuthAndSubscription();
            abort_unless($checkAuthAndSubscription['display_odds'], 403, __('An active 7-day free trial or paid subscription is required to use smart filters.'));
        }

        $matches = array_values(array_map('prepareMatchOddsMonitoring', getInRedis('Cricket:matches') ?? []));
        $customerId = request()->user('customers')?->getAuthIdentifier();
        $favoriteKeys = $customerId === null ? [] : array_fill_keys(Favorite::query()->where('sport', 'cricket')->where('customer_id', $customerId)->pluck('event_key')->all(), true);
        $today = Carbon::now(getCurrentUserTimezone())->toDateString();
        $type = request()->input('type', 'all');
        $matches = array_values(array_filter($matches, static function (array $match) use ($today, $type, $favoriteKeys): bool {
            if (($match['data_sync_complete'] ?? false) !== true || ! isset($match['sportsApiPro_match_id'], $match['timestamp'])) {
                return false;
            }
            $status = $match['result_status'] ?? 'Prematch';
            if ($status === 'Prematch' && Carbon::createFromTimestamp((int) $match['timestamp'], getCurrentUserTimezone())->toDateString() !== $today) {
                return false;
            }

            return match ($type) {
                'live' => $status === 'Live',
                'upcoming' => $status === 'Prematch',
                'finished' => $status === 'Finished' && (int) $match['timestamp'] >= time() - 30 * 86400,
                'favorites' => isset($favoriteKeys[$match['unique_key'] ?? '']),
                'innings_break' => $status === 'Live' && ($match['minutes_status'] ?? '') === 'Innings Break',
                'prematch_drops', 'market_shock' => $status === 'Prematch' && array_any($match['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => $type === 'prematch_drops' ? ($runner['prematch_drop_triggered'] ?? false) === true : (int) ($runner['market_shock_detected_at'] ?? 0) >= time() - 1200)),
                default => true,
            };
        }));
        usort($matches, static function (array $left, array $right) use ($type): int {
            if ($type === 'by_time') {
                return (int) $left['timestamp'] <=> (int) $right['timestamp'];
            }
            $order = ['Live' => 0, 'Prematch' => 1, 'Finished' => 2];

            return (($order[$left['result_status']] ?? 3) <=> ($order[$right['result_status']] ?? 3)) ?: ((int) $left['timestamp'] <=> (int) $right['timestamp']);
        });
        $page = max(1, (int) request()->input('page', 1));
        $perPage = min(1000, max(1, (int) request()->input('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->cricketHasMore = $offset + $perPage < count($matches);
        $this->cricketNextPage = $this->cricketHasMore ? $page + 1 : null;
        $matches = array_slice($matches, $offset, $perPage);
        $matchIds = array_map(static fn (array $match): string => (string) $match['sportsApiPro_match_id'], $matches);
        $votes = $matchIds === [] ? collect() : PredictionVote::query()->where('sport', 'cricket')->whereIn('match_id', $matchIds)->where('poll', '1x2')->select('match_id', 'value')->selectRaw('COUNT(*) as total')->groupBy('match_id', 'value')->get()->groupBy('match_id');
        $customerVotes = $customerId === null || $matchIds === [] ? [] : PredictionVote::query()->where('sport', 'cricket')->where('customer_id', $customerId)->whereIn('match_id', $matchIds)->where('poll', '1x2')->pluck('value', 'match_id')->all();
        $groups = [];
        $marketType = request()->input('market_name', 'MATCH_ODDS');
        foreach ($matches as $match) {
            $runners = [];
            foreach ($match['markets'] ?? [] as $marketRunners) {
                if (($marketRunners[array_key_first($marketRunners)]['marketType'] ?? null) === $marketType) {
                    $runners = array_values($marketRunners);
                    break;
                }
            }
            usort($runners, static function (array $first, array $second) use ($match, $marketType): int {
                if ($marketType !== 'MATCH_ODDS') {
                    return (int) ($first['sortPriority'] ?? 0) <=> (int) ($second['sortPriority'] ?? 0);
                }
                $team1 = strtolower((string) $match['team1']);
                $rank = static fn (array $runner): int => in_array(strtolower($runner['runner'] ?? ''), ['draw', 'the draw'], true) ? 1 : (strtolower($runner['runner'] ?? '') === $team1 ? 0 : 2);

                return $rank($first) <=> $rank($second);
            });
            foreach ($runners as &$runner) {
                $initial = (float) ($runner['initialBackOdds'] ?? 0);
                $current = (float) ($runner['currentBackOdds'] ?? $runner['backOdds'] ?? 0);
                $runner['odds_direction'] = $initial > 0 && $current > 0 ? ($current < $initial ? 'down' : ($current > $initial ? 'up' : 'same')) : 'same';
            }
            unset($runner);
            $matchId = (string) $match['sportsApiPro_match_id'];
            $predictionCounts = ['1' => 0, '2' => 0, 'X' => 0];
            foreach ($votes->get($matchId, collect()) as $vote) {
                $predictionCounts[$vote->value] = (int) $vote->total;
            }
            $sessionKey = "cricket_predictions.{$customerId}.{$matchId}.1x2";
            $customerVote = $customerId === null ? null : request()->session()->get($sessionKey, $customerVotes[$matchId] ?? null);
            if ($customerId !== null && $customerVote !== null) {
                request()->session()->put($sessionKey, $customerVote);
            }
            $groupKey = ($match['tournament'] ?? 'Cricket').'|'.$match['result_status'];
            if ($type === 'by_time') {
                $groupKey .= '|'.$match['timestamp'];
            }
            $groups[$groupKey] ??= ['tournament_name' => $match['tournament'] ?? 'Cricket', 'tournament_logo' => $match['tournament_logo'] ?? null, 'status' => $match['result_status'], 'matches' => []];
            $groups[$groupKey]['matches'][] = array_replace($match, [
                'status' => $match['result_status'],
                'is_favorite' => isset($favoriteKeys[$match['unique_key']]),
                'score' => $this->getScore($match),
                'score_display' => $this->cricketScoreDisplay($match),
                'home_score_display' => $this->formatCricketScore($match['home_score_data'] ?? []),
                'away_score_display' => $this->formatCricketScore($match['away_score_data'] ?? []),
                'minutes' => $match['result_status'] === 'Prematch' ? Carbon::createFromTimestamp((int) $match['timestamp'], getCurrentUserTimezone())->format('H:i') : ($match['minutes_status'] ?? $match['result_status']),
                'markets' => $runners,
                'prediction_has_draw' => array_any($match['markets'] ?? [], static fn (array $selections): bool => array_any($selections, static fn (array $runner): bool => ($runner['marketType'] ?? '') === 'MATCH_ODDS' && in_array(strtolower($runner['runner'] ?? ''), ['draw', 'the draw'], true))),
                'prediction_counts' => $predictionCounts,
                'prediction_vote' => $customerVote,
            ]);
        }

        return array_values($groups);
    }

    public function formatCricketScore(array $score): string
    {
        $innings = is_array($score['innings'] ?? null) ? $score['innings'] : [];
        $scores = [];
        foreach ($innings as $inning) {
            if (! is_array($inning) || ! isset($inning['score'])) {
                continue;
            }
            $display = (string) $inning['score'];
            if (isset($inning['wickets'])) {
                $display .= '/'.$inning['wickets'];
            }
            if (isset($inning['overs'])) {
                $display .= ' ('.$inning['overs'].' ov)';
            }
            $scores[] = $display;
        }

        return $scores !== [] ? implode(' & ', $scores) : (isset($score['current']) ? (string) $score['current'] : '—');
    }

    public function getScore(array $match): string
    {
        return ($match['result_status'] ?? 'Prematch') === 'Prematch' ? 'vs' : $this->formatCricketScore($match['home_score_data'] ?? []).' - '.$this->formatCricketScore($match['away_score_data'] ?? []);
    }

    /**
     * The provider's `current` is a cumulative total in multi-innings matches.
     * Never use it in place of the current individual innings score.
     *
     * @param  array<string, mixed>  $match
     * @return array{main: list<array{side: string, team: string, score: string, overs: ?string, number: int}>, innings: ?string, previous: list<string>, toss: ?string}
     */
    public function cricketScoreDisplay(array $match): array
    {
        $display = ['main' => [], 'innings' => null, 'previous' => [], 'toss' => $match['toss_info'] ?? null];
        if (($match['result_status'] ?? 'Prematch') === 'Prematch') {
            return $display;
        }
        $innings = [];
        foreach (['home' => 'team1', 'away' => 'team2'] as $side => $team) {
            $score = $match[$side.'_score_data'] ?? [];
            foreach ($score['innings'] ?? [] as $key => $inning) {
                if (! is_array($inning) || ! is_numeric($inning['score'] ?? null)) {
                    continue;
                }
                preg_match('/(\d+)/', (string) $key, $number);
                $wickets = is_numeric($inning['wickets'] ?? null) ? '/'.$inning['wickets'] : '';
                $innings[$side][] = ['side' => $side, 'team' => $match[$team] ?? ucfirst($side), 'score' => (string) $inning['score'].$wickets,
                    'overs' => is_numeric($inning['overs'] ?? null) ? (string) $inning['overs'] : null, 'number' => (int) ($number[1] ?? 1)];
            }
            if (empty($score['innings']) && is_numeric($score['current'] ?? null)
                && ((float) $score['current'] > 0 || is_numeric($score['wickets'] ?? null) || is_numeric($score['overs'] ?? null))) {
                $innings[$side][] = ['side' => $side, 'team' => $match[$team] ?? ucfirst($side),
                    'score' => (string) $score['current'].(isset($score['wickets']) ? '/'.$score['wickets'] : ''),
                    'overs' => is_numeric($score['overs'] ?? null) ? (string) $score['overs'] : null, 'number' => 1];
            }
            if (isset($innings[$side])) {
                usort($innings[$side], static fn (array $left, array $right): int => $left['number'] <=> $right['number']);
            }
        }
        $latest = array_map(static fn (array $scores): array => $scores[array_key_last($scores)], $innings);
        $battingSide = $match['cricket_batting_side'] ?? null;
        if (! isset($latest[$battingSide ?? ''])) {
            $battingSide = null;
        }
        if ($battingSide === null && count($latest) === 1) {
            $battingSide = array_key_first($latest);
        } elseif ($battingSide === null && count($latest) === 2) {
            if ($latest['home']['number'] !== $latest['away']['number']) {
                $battingSide = $latest['home']['number'] > $latest['away']['number'] ? 'home' : 'away';
            } elseif ($latest['home']['number'] === 1 && isset($match['cricket_first_batting_side'])) {
                $battingSide = $match['cricket_first_batting_side'] === 'home' ? 'away' : 'home';
            }
        }
        $display['main'] = $battingSide !== null ? [$latest[$battingSide]] : array_values($latest);
        if ($display['main'] !== []) {
            $number = $display['main'][0]['number'];
            if ($number === 1 && preg_match('/^([1-4])(?:st|nd|rd|th) Innings?$/i', $match['minutes_status'] ?? '', $phase)) {
                $number = (int) $phase[1];
            }
            $display['innings'] = ($number === 1 ? '1st' : ($number === 2 ? '2nd' : ($number === 3 ? '3rd' : $number.'th'))).' Innings';
        }
        $previous = [];
        foreach ($innings as $side => $scores) {
            foreach ($scores as $inning) {
                if (array_any($display['main'], static fn (array $current): bool => $current['side'] === $side && $current['number'] === $inning['number'])) {
                    continue;
                }
                $previous[$inning['number']][] = $inning['team'].': '.$inning['score'];
            }
        }
        ksort($previous);
        foreach ($previous as $number => $scores) {
            $display['previous'][] = ($number === 1 ? '1st' : ($number === 2 ? '2nd' : $number.'th')).' Inn: '.implode(' · ', $scores);
        }

        return $display;
    }

    public function getAllMarkets(): array
    {
        $options = ['MATCH_ODDS' => ['value' => 'MATCH_ODDS', 'label' => 'Match Odds', 'oddsCount' => 3]];
        foreach (getInRedis('Cricket:matches') ?? [] as $match) {
            foreach ($match['markets'] ?? [] as $runners) {
                $runner = $runners[array_key_first($runners)] ?? [];
                $type = $runner['marketType'] ?? null;
                if ($type !== null && ! isset($options[$type])) {
                    $options[$type] = ['value' => $type, 'label' => $runner['name'] ?? str_replace('_', ' ', $type), 'oddsCount' => count($runners)];
                }
            }
        }

        return ['default' => 'MATCH_ODDS', 'options' => array_values($options)];
    }

    public function getCricketMatchDetail(string $matchId): ?array
    {
        // $match = array_find(getInRedis('Cricket:matches') ?? [], static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $matchId && ($match['data_sync_complete'] ?? false) === true);
        $match = collect(getInRedis('Cricket:matches') ?? [])->first(static fn (array $candidate): bool => (string) ($candidate['sportsApiPro_match_id'] ?? '') === $matchId);
        if ($match === null) {
            $stored = CricketMatch::query()->with('markets')->where('sports_api_pro_match_id', $matchId)
                // ->where('data_sync_complete', true)
                ->first();
            if ($stored === null) {
                return null;
            }
            $match = array_replace($stored->payload ?? [], ['unique_key' => $stored->unique_key, 'team1' => $stored->team1, 'team2' => $stored->team2, 'tournament' => $stored->tournament, 'result_status' => $stored->result_status, 'minutes_status' => $stored->minutes_status, 'timestamp' => $stored->start_timestamp, 'markets' => []]);
            foreach ($stored->markets->sortBy('sort_priority') as $market) {
                $match['markets'][$market->market_id.'_'.$market->market_type][] = $market->payload;
            }
            foreach (['home_team_logo' => 'home_team_id', 'away_team_logo' => 'away_team_id'] as $logo => $idKey) {
                $id = (int) ($match[$idKey] ?? 0);
                $images = $id > 0 ? glob(public_path("images/cricket/teams/{$id}.*")) : [];
                $match[$logo] = $images && is_file($images[0]) ? '/images/cricket/teams/'.basename($images[0]) : null;
            }
        }
        // $match = prepareMatchOddsMonitoring($match);
        $markets = [];
        foreach ($match['markets'] ?? [] as $marketRunners) {
            if (! is_array($marketRunners)) {
                continue;
            }
            // $runners = array_values($marketRunners);
            // usort($runners, static fn (array $left, array $right): int => (int) ($left['sortPriority'] ?? 0) <=> (int) ($right['sortPriority'] ?? 0));
            foreach ($marketRunners as $runner) {
                $marketType = $runner['marketType'] ?? '';
                $current = (float) ($runner['currentBackOdds'] ?? $runner['backOdds'] ?? 0);
                // if ($current <= 0) {
                //     continue;
                // }
                $initial = (float) ($runner['initialBackOdds'] ?? 0);
                // $history = array_values(array_filter($runner['backOddsHistory'] ?? [], static fn (array $point): bool => (float) ($point['odds'] ?? 0) > 0));
                $markets[$marketType] ??= ['type' => $marketType, 'label' => $runner['name'] ?? 'Market', 'runners' => []];
                $markets[$marketType]['runners'][] = [
                    'runner_id' => $runner['runnerId'] ?? null,
                    'match_id' => $matchId,
                    'market_id' => $runner['marketId'] ?? null,
                    'chart_url' => betfairMarketChartUrl($runner),
                    'history' => oddsMovementHistory($runner),
                    'history_incomplete' => (bool) ($runner['odds_history_incomplete'] ?? false),
                    'name' => $runner['runner'] ?? 'Selection',
                    'initial_back_odds' => $initial,
                    'current_back_odds' => $current,
                    'direction' => $initial > 1 ? $current <=> $initial : 0,
                    'change_percent' => $initial > 0 ? (($current - $initial) / $initial) * 100 : 0,
                    // 'history' => count($history) > 12 ? array_merge([$history[0]], array_slice($history, -11)) : $history,
                ];
            }
        }
        // usort($markets, static fn (array $left, array $right): int => (($left['type'] !== 'MATCH_ODDS') <=> ($right['type'] !== 'MATCH_ODDS')) ?: ($left['label'] <=> $right['label']));

        return array_replace($match, ['score_display' => $this->cricketScoreDisplay($match), 'home_score_display' => $this->formatCricketScore($match['home_score_data'] ?? []), 'away_score_display' => $this->formatCricketScore($match['away_score_data'] ?? []), 'id' => $matchId, 'status' => $match['result_status'], 'score' => $this->getScore($match), 'minutes' => $match['minutes_status'] ?? $match['result_status'], 'markets' => $markets]);
    }

    public function getMarketActivites(): array
    {
        $groups = $this->getCricketMatches();
        $this->marketActivityHasMore = $this->cricketHasMore;
        $this->marketActivityNextPage = $this->cricketNextPage;
        $matchesById = getInRedis('Cricket:matches') ?? [];
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['matches'] as $match) {
                $raw = prepareMatchOddsMonitoring($matchesById[$match['unique_key']] ?? $match);
                foreach ($raw['markets'] ?? [] as $key => $runners) {
                    if (request()->filled('market_name') && ($runners[array_key_first($runners)]['marketType'] ?? '') !== request()->input('market_name')) {
                        continue;
                    }
                    $favorite = null;
                    foreach ($runners as $runner) {
                        $back = (float) ($runner['currentBackOdds'] ?? $runner['backOdds'] ?? 0);
                        if ($back > 0 && ($favorite === null || $back < (float) ($favorite['currentBackOdds'] ?? $favorite['backOdds'] ?? 0))) {
                            $favorite = $runner;
                        }
                    }
                    if (in_array(request()->input('type'), ['prematch_drops', 'market_shock'], true) && ! array_any($runners, static fn (array $runner): bool => request()->input('type') === 'prematch_drops' ? ($runner['prematch_drop_triggered'] ?? false) === true : (int) ($runner['market_shock_detected_at'] ?? 0) >= time() - 1200)) {
                        continue;
                    }
                    $first = $runners[array_key_first($runners)] ?? [];
                    $rows[] = ['id' => $key, 'match_id' => $match['sportsApiPro_match_id'], 'team1' => $match['team1'], 'team2' => $match['team2'], 'tournament' => $group['tournament_name'], 'status' => $match['status'], 'minutes' => $match['minutes'], 'score' => $match['score'], 'marketName' => $first['name'] ?? $first['marketType'] ?? 'Market', 'marketType' => $first['marketType'] ?? '', 'runners' => array_values($runners), 'favorite' => $favorite];
                }
            }
        }

        return $rows;
    }
}
