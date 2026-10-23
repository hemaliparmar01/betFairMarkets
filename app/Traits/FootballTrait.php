<?php

namespace App\Traits;

use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Models\Favorite;
use App\Models\FootballMatch;
use App\Models\PredictionVote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait FootballTrait
{
    private const LEAGUE_PRIORITY = [
        'Premier League', 'La Liga', 'Serie A', 'Bundesliga', 'Ligue 1',
        'UEFA Champions League', 'UEFA Europa League', 'UEFA Conference League',
        'Primeira Liga', 'Eredivisie', 'Süper Lig', 'Belgian Pro League',
        'Scottish Premiership', 'Greek Super League', 'Austrian Bundesliga',
        'Swiss Super League', 'EFL Championship', 'Serie B', 'LaLiga 2', 'Ligue 2',
        'Danish Superliga', 'Eliteserien', 'Allsvenskan', 'Ekstraklasa',
        'Czech First League', 'Brasileirão Série A', 'Argentine Primera División',
        'Liga MX', 'Major League Soccer (MLS)', 'Saudi Pro League', 'J1 League',
        'K League 1', 'Chinese Super League', 'Croatian HNL', 'Serbian SuperLiga',
        'Romanian SuperLiga', 'Ukrainian Premier League', 'Israeli Premier League',
        'Norwegian Eliteserien', 'Swedish Allsvenskan', 'EFL League One', 'Liga',
        'Segunda Federación / Primera Federación', 'Serie C',
        'National 1 / Championnat National', 'Liga Portugal 2', 'Eerste Divisie',
        'Belgian Challenger Pro League', 'Scottish Championship', 'Greek Super League 2',
        'Danish 1st Division', 'Swedish Superettan', 'Norwegian OBOS-ligaen',
        'Polish I Liga', 'Czech National Football League', 'Croatian First Football League',
        'Serbian First League', 'Major International Competitions', 'FIFA World Cup',
        'UEFA European Championship', 'Copa America', 'CONMEBOL Libertadores',
        'CONMEBOL Sudamericana', 'UEFA Nations League', 'FIFA Club World Cup',
        'AFC Champions League Elite', 'CAF Champions League', 'CONCACAF Champions Cup',
    ];

    protected bool $footballHasMore = false;

    protected ?int $footballNextPage = null;

    protected bool $marketActivityHasMore = false;

    protected ?int $marketActivityNextPage = null;

    public function getFootballMatches(): array
    {
        $checkAuthAndSubscription = checkAuthAndSubscription();
        if (in_array(request()->input('type'), ['ht00', '00_70', 'fav_losing', 'prematch_drops', 'market_shock', 'fav_not_winning'], true)) {
            abort_unless($checkAuthAndSubscription['display_odds'], 403, __('An active 7-day free trial or paid subscription is required to use smart filters.'));
        }

        $getData = getInRedis('Football:matches') ?? [];
        $customerId = request()->user('customers')?->getAuthIdentifier();
        $favoriteKeys = $customerId === null ? [] : array_fill_keys(
            Favorite::query()->where('sport', 'football')->where('customer_id', $customerId)->pluck('event_key')->all(),
            true
        );

        $marketName = request()->input('market_name');
        $marketName = is_string($marketName) && $marketName !== '' ? $marketName : 'MATCH_ODDS';

        $type = null;
        if (request()->has('type')) {
            $type = request()->type;
        }

        $dayStart = Carbon::now(getCurrentUserTimezone())->startOfDay();
        $todayStart = $dayStart->timestamp;
        $tomorrowStart = $dayStart->copy()->addDay()->timestamp;
        $matchesFilter = null;

        if ($type) {
            switch ($type) {
                case 'upcoming':
                    $matchesFilter = static fn (array $match): bool => ($match['result_status'] ?? null) === 'Prematch';
                    break;

                case 'live':
                    $matchesFilter = static fn (array $match): bool => ($match['result_status'] ?? null) === 'Live';
                    break;

                case 'finished':
                    $finishedCutoff = time() - (30 * 86400);
                    $matchesFilter = static fn (array $match): bool => ($match['result_status'] ?? null) === 'Finished'
                        && (int) ($match['timestamp'] ?? 0) >= $finishedCutoff;
                    break;

                case 'by_time':
                    break;

                case '00_70':
                    $matchesFilter = function (array $match): bool {
                        if (
                            ($match['result_status'] ?? null) !== 'Live'
                            || (int) ($match['home_score'] ?? 0) !== 0
                            || (int) ($match['away_score'] ?? 0) !== 0
                        ) {
                            return false;
                        }

                        $minute = $this->calculateMinutes(
                            $match['minutes_status'] ?? $match['result_status'],
                            (int) ($match['timestamp'] ?? time()),
                            $match['result_status'],
                            $match['added_time'] ?? [],
                            $match['current_period_start_timestamp'] ?? null
                        );

                        return is_numeric($minute) ? (int) $minute >= 70 : str_starts_with((string) $minute, '90+');
                    };
                    break;

                case 'ht00':
                    $matchesFilter = static function (array $match): bool {
                        $minuteStatus = strtolower(trim((string) ($match['minutes_status'] ?? '')));

                        return ($match['result_status'] ?? null) === 'Live'
                            && in_array($minuteStatus, ['half time', 'halftime', 'ht'], true)
                            && (int) ($match['home_score'] ?? 0) === 0
                            && (int) ($match['away_score'] ?? 0) === 0;
                    };
                    break;

                case 'favorites':
                    $matchesFilter = static fn (array $match): bool => isset($favoriteKeys[$match['unique_key'] ?? '']);
                    break;

                case 'fav_losing':
                case 'fav_not_winning':
                    $matchesFilter = function (array $match) use ($type): bool {
                        $favoriteOdds = (float) ($match['bet365_prematch']['favorite_odds'] ?? 0);
                        $maximumOdds = $type === 'fav_losing' ? 2.10 : 1.87;

                        if (
                            ($match['result_status'] ?? null) !== 'Live'
                            || $favoriteOdds < 1.01
                            || $favoriteOdds > $maximumOdds
                        ) {
                            return false;
                        }

                        if ($type === 'fav_not_winning') {
                            $minute = $this->calculateMinutes(
                                $match['minutes_status'] ?? $match['result_status'],
                                (int) ($match['timestamp'] ?? time()),
                                $match['result_status'],
                                $match['added_time'] ?? [],
                                $match['current_period_start_timestamp'] ?? null
                            );

                            if ($minute === 'HT') {
                                $currentMinute = 45;
                            } elseif (preg_match('/^(\d+)/', (string) $minute, $minuteMatch) === 1) {
                                $currentMinute = (int) $minuteMatch[1];
                            } else {
                                return false;
                            }

                            if ($currentMinute < 29) {
                                return false;
                            }
                        }

                        $favoriteSide = $match['bet365_prematch']['favorite_side'] ?? null;
                        $homeScore = (int) ($match['home_score'] ?? 0);
                        $awayScore = (int) ($match['away_score'] ?? 0);

                        if ($favoriteSide === 'home') {
                            return $type === 'fav_losing'
                                ? $homeScore < $awayScore
                                : $homeScore <= $awayScore;
                        }

                        if ($favoriteSide === 'away') {
                            return $type === 'fav_losing'
                                ? $awayScore < $homeScore
                                : $awayScore <= $homeScore;
                        }

                        return false;
                    };
                    break;

                case 'prematch_drops':
                    $matchesFilter = static function (array $match): bool {
                        if (($match['result_status'] ?? null) !== 'Prematch') {
                            return false;
                        }

                        if (($match['pinnacle_drop_triggered'] ?? false) === true) {
                            return true;
                        }

                        foreach (($match['markets'] ?? []) as $marketRunners) {
                            foreach (($marketRunners ?? []) as $runner) {
                                if (($runner['prematch_drop_triggered'] ?? false) === true && hasMeaningfulOpeningOdds($runner)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    };
                    break;

                case 'market_shock':
                    $shockCutoff = time() - 1200;
                    $matchesFilter = static function (array $match) use ($shockCutoff): bool {
                        if (($match['result_status'] ?? null) !== 'Prematch') {
                            return false;
                        }

                        if ((int) ($match['pinnacle_market_shock_detected_at'] ?? 0) >= $shockCutoff) {
                            return true;
                        }

                        foreach (($match['markets'] ?? []) as $marketRunners) {
                            foreach (($marketRunners ?? []) as $runner) {
                                if ((int) ($runner['market_shock_detected_at'] ?? 0) >= $shockCutoff && hasMeaningfulOpeningOdds($runner)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    };
                    break;

                default:
                    break;
            }
        }

        $leaguePriorities = [];
        $getData = array_values(array_filter($getData, function (array $match) use ($type, $todayStart, $tomorrowStart, $matchesFilter, &$leaguePriorities): bool {
            if (
                ! isset($match['timestamp'])
                || ! is_numeric($match['sportsApiPro_match_id'] ?? null)
                || (int) $match['sportsApiPro_match_id'] <= 0
                || ($type !== 'finished' && ($match['result_status'] ?? null) === 'Finished')
                || (($match['result_status'] ?? null) === 'Prematch'
                    && ((int) $match['timestamp'] < $todayStart || (int) $match['timestamp'] >= $tomorrowStart))
                || ($matchesFilter !== null && ! $matchesFilter($match))
            ) {
                return false;
            }

            $tournament = (string) ($match['tournament'] ?? '');
            $country = $match['tournament_country'] ?? null;
            $leaguePriorities[$tournament][$country ?? ''] ??= $this->footballLeaguePriority($tournament, $country);

            return true;
        }));
        $sortByTime = in_array($type, ['by_time', 'upcoming'], true);
        usort($getData, static function (array $first, array $second) use ($sortByTime, $leaguePriorities): int {
            $timeComparison = (int) ($first['timestamp'] ?? 0) <=> (int) ($second['timestamp'] ?? 0);
            $priorityComparison = $leaguePriorities[$first['tournament'] ?? ''][$first['tournament_country'] ?? '']
                <=> $leaguePriorities[$second['tournament'] ?? ''][$second['tournament_country'] ?? ''];

            return $sortByTime
                ? ($timeComparison ?: $priorityComparison)
                : ($priorityComparison ?: $timeComparison);
        });
        $page = max(1, (int) request()->input('page', 1));
        $perPage = min(100, max(1, (int) request()->input('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->footballHasMore = count($getData) > ($offset + $perPage);
        $this->footballNextPage = $this->footballHasMore ? $page + 1 : null;
        $getData = array_slice($getData, $offset, $perPage);

        $matchIds = array_values(array_unique(array_map(
            static fn (array $match): string => (string) $match['sportsApiPro_match_id'],
            $getData
        )));
        $predictionCounts = [];
        $storedPredictionVotes = [];

        if ($matchIds !== []) {
            $countRows = PredictionVote::query()
                ->select(['match_id', 'poll', 'value'])
                ->selectRaw('COUNT(*) as total')
                ->where('sport', 'football')
                ->whereIn('match_id', $matchIds)
                ->groupBy('match_id', 'poll', 'value')
                ->get();

            foreach ($countRows as $row) {
                $predictionCounts[$row->match_id][$row->poll][$row->value] = (int) $row->total;
            }

            if ($customerId !== null) {
                $voteQuery = PredictionVote::query()
                    ->select(['match_id', 'poll', 'value'])
                    ->where('sport', 'football')
                    ->where('customer_id', $customerId)
                    ->whereIn('match_id', $matchIds);

                foreach ($voteQuery->get() as $row) {
                    $storedPredictionVotes[$row->match_id][$row->poll] = $row->value;
                }
            }
        }

        $showTimeline = in_array($type ?? 'all', ['all', 'live', 'finished', 'by_time'], true);
        $canDisplayOdds = $checkAuthAndSubscription['display_odds'];
        $liveOddsKeys = ! $showTimeline && $canDisplayOdds ? array_values(array_map(
            static fn (array $match): string => 'Football:bet365:live:'.$match['sportsApiPro_match_id'],
            array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? '') === 'Live')
        )) : [];
        $liveOddsCache = ! $showTimeline && $canDisplayOdds && $liveOddsKeys !== []
            ? Cache::store('redis')->many($liveOddsKeys)
            : [];
        $pinnacleAlerts = $canDisplayOdds && in_array($type, ['prematch_drops', 'market_shock'], true)
            ? array_column(getInRedis('Pinnacle:drops')['signals'] ?? [], null, 'alert_key')
            : [];
        $canBackfillTimeline = $showTimeline && $canDisplayOdds;
        $usesBetfairOdds = $canDisplayOdds && in_array($type, ['prematch_drops', 'market_shock'], true);
        $mainArray = [];
        foreach ($getData as $data) {
            if ($usesBetfairOdds) {
                $data = prepareMatchOddsMonitoring($data);
            }
            $tournamentName = $data['tournament'];
            $leagueKey = $type === 'by_time' ? $tournamentName.'|'.$data['timestamp'] : $tournamentName;
            $matchId = (string) $data['sportsApiPro_match_id'];
            $timeline = $data['timeline'] ?? [];
            $oddsMarkets = ! $showTimeline && $canDisplayOdds
                ? $this->footballOddsMarkets($data, $type ?? 'all', $marketName, $liveOddsCache['Football:bet365:live:'.$matchId] ?? [], $pinnacleAlerts)
                : [];
            if ($canBackfillTimeline && in_array($data['result_status'], ['Live', 'Finished'], true)) {
                app(FootballSyncController::class)->queueTimelineBackfill($matchId, $data['result_status'] === 'Finished', isset($data['home_score'], $data['away_score']) ? [
                    'home' => (int) ($data['home_score'] ?? 0), 'away' => (int) ($data['away_score'] ?? 0),
                ] : null, $timeline);
            }
            $predictionVotes = [];

            foreach (['1x2', 'uo'] as $poll) {
                $sessionKey = "football_predictions.{$customerId}.{$matchId}.{$poll}";
                $predictionVotes[$poll] = $customerId === null ? null : request()->session()->get($sessionKey);

                if ($predictionVotes[$poll] === null && isset($storedPredictionVotes[$matchId][$poll])) {
                    $predictionVotes[$poll] = $storedPredictionVotes[$matchId][$poll];
                    request()->session()->put($sessionKey, $predictionVotes[$poll]);
                }
            }

            if (! isset($mainArray[$leagueKey])) {
                $mainArray[$leagueKey] = [
                    'tournament_name' => $tournamentName,
                    'tournament_id' => $data['tournament_id'] ?? null,
                    'tournament_logo' => $data['tournament_logo'] ?? null,
                    'country_id' => $data['country_id'] ?? null,
                    'unique_key' => $data['unique_key'],
                    'status' => $data['result_status'],
                    'matches' => [],
                ];
            }
            $mainArray[$leagueKey]['matches'][] = [
                'unique_key' => $data['unique_key'],
                'is_favorite' => isset($favoriteKeys[$data['unique_key']]),
                'team1' => $data['team1'],
                'team2' => $data['team2'],
                'home_team_id' => $data['home_team_id'] ?? null,
                'away_team_id' => $data['away_team_id'] ?? null,
                'home_team_logo' => $data['home_team_logo'] ?? null,
                'away_team_logo' => $data['away_team_logo'] ?? null,
                'status' => $data['result_status'],
                'sportsApiPro_match_id' => $data['sportsApiPro_match_id'],
                'betfair_match_id' => $data['betfair_match_id'] ?? null,
                'score' => $this->getScore($data),
                'timestamp' => $data['timestamp'],
                'minutes' => $this->calculateMinutes($data['minutes_status'] ?? $data['result_status'], $data['timestamp'], $data['result_status'], $data['added_time'] ?? [], $data['current_period_start_timestamp'] ?? null),
                // 'status_type' => $data['status_type'] ?? null,
                'cards' => $data['cards'] ?? [],
                'card_counts' => $data['card_counts'] ?? [],
                'card_counts_from_timeline' => isset($data['timeline']['events']),
                'card_counts_updated_at' => $data['card_counts_updated_at'] ?? 0,
                'card_stats_updated_at' => $data['card_stats_updated_at'] ?? 0,
                'last_goal' => $data['last_goal'] ?? null,
                'goal_highlight_until' => $data['goal_highlight_until'] ?? null,
                'added_time' => $data['added_time'] ?? [],
                'corners' => $data['corners'] ?? [],
                'live_stats' => $data['live_stats'] ?? [],
                'live_stats_meta' => $data['live_stats_meta'] ?? [],
                'statistics_periods' => $canDisplayOdds ? ($data['statistics_periods'] ?? []) : [],
                'prematch_statistics' => $canDisplayOdds ? ($data['prematch_statistics'] ?? []) : [],
                'statistics_requests' => $canDisplayOdds ? ($data['statistics_requests'] ?? []) : [],
                'predictions' => $predictionCounts[$matchId] ?? ($data['predictions'] ?? []),
                'prediction_votes' => $predictionVotes,
                'timeline' => $showTimeline ? $this->footballTimeline($data, $timeline) : null,
                'markets' => array_merge([], ...array_column($oddsMarkets, 'runners')),
                'odds_markets' => $oddsMarkets,
            ];
        }

        return $mainArray;
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $liveOdds
     * @param  array<string, array<string, mixed>>  $pinnacleAlerts
     * @return list<array{source: string, label: string, runners: list<array{runner: string, currentBackOdds: ?float, previousBackOdds: ?float, odds_direction: string}>}>
     */
    private function footballOddsMarkets(array $match, string $filter, string $marketName, array $liveOdds, array $pinnacleAlerts): array
    {
        $groups = [];
        if (in_array($filter, ['prematch_drops', 'market_shock'], true)) {
            $shockCutoff = time() - 1200;
            foreach ($match['pinnacle_markets'] ?? [] as $state) {
                $qualifies = $filter === 'prematch_drops'
                    ? ($state['drop_triggered'] ?? false) === true
                    : (int) ($state['shock_detected_at'] ?? 0) >= $shockCutoff;
                if (! $qualifies) {
                    continue;
                }
                $quote = array_replace($pinnacleAlerts[$state['alert_key'] ?? ''] ?? [], $state);
                $section = trim((string) ($quote['sect'] ?? ''));
                $outcome = trim((string) ($quote['outcome'] ?? ''));
                if ($section === '' || $outcome === '') {
                    continue;
                }
                $line = $quote['point'] ?? $quote['points'] ?? $quote['hdp'] ?? null;
                $label = in_array(strtolower($section), ['moneyline', 'money line', '1x2', 'match odds'], true) ? 'Match Odds / 1X2' : $section;
                if ($label !== 'Match Odds / 1X2' && is_numeric($line) && ! str_contains($label, (string) $line)) {
                    $label .= ' '.$line;
                }
                $runner = match (strtolower($outcome)) {
                    'home', '1' => $match['team1'],
                    'away', '2' => $match['team2'],
                    'draw', 'x' => 'Draw',
                    default => $outcome,
                };
                $odd = $this->footballOddsRunner($runner, $state['current_odds'] ?? null, $state['baseline_odds'] ?? $quote['from_price'] ?? null);
                if ($odd['currentBackOdds'] === null) {
                    continue;
                }
                $groupKey = 'pinnacle:'.strtolower($section).'|'.$line;
                $groups[$groupKey] ??= ['source' => 'Pinnacle', 'label' => $label, 'runners' => []];
                $groups[$groupKey]['runners'][] = $odd;
            }
            foreach ($match['markets'] ?? [] as $runners) {
                $qualifies = array_any($runners, static fn (array $runner): bool => $filter === 'prematch_drops'
                    ? ($runner['prematch_drop_triggered'] ?? false) === true
                    : (int) ($runner['market_shock_detected_at'] ?? 0) >= $shockCutoff);
                if (! $qualifies) {
                    continue;
                }
                usort($runners, static fn (array $first, array $second): int => (int) ($first['sortPriority'] ?? 0) <=> (int) ($second['sortPriority'] ?? 0));
                $first = $runners[0];
                $groups[] = [
                    'source' => 'Betfair',
                    'label' => ($first['marketType'] ?? '') === 'MATCH_ODDS' ? 'Match Odds / 1X2' : ($first['name'] ?? str_replace('_', ' ', $first['marketType'] ?? 'Market')),
                    'runners' => array_map(fn (array $runner): array => $this->footballOddsRunner(
                        (string) ($runner['runner'] ?? 'Selection'),
                        $runner['currentBackOdds'] ?? $runner['backOdds'] ?? null,
                        $runner['initialBackOdds'] ?? null
                    ), $runners),
                ];
            }
            $outcomeOrder = [strtolower(trim($match['team1'])) => 0, 'draw' => 1, 'the draw' => 1, 'x' => 1, strtolower(trim($match['team2'])) => 2];
            foreach ($groups as &$group) {
                if ($group['label'] === 'Match Odds / 1X2') {
                    usort($group['runners'], static fn (array $first, array $second): int => ($outcomeOrder[strtolower(trim($first['runner']))] ?? 3)
                        <=> ($outcomeOrder[strtolower(trim($second['runner']))] ?? 3));
                }
            }
            unset($group);

            return array_values($groups);
        }

        if ($marketName !== 'MATCH_ODDS') {
            return [];
        }
        $runners = [];
        if (($match['result_status'] ?? '') === 'Live') {
            $market = array_find($liveOdds['markets'] ?? [], static fn (array $market): bool => (int) ($market['market_id'] ?? 0) === 1
                && in_array(strtolower((string) ($market['period'] ?? 'full-time')), ['full-time', 'full time'], true));
            if ($market === null) {
                return [];
            }
            $choices = array_column($market['choices'] ?? [], null, 'name');
            foreach (['1' => $match['team1'], 'X' => 'Draw', '2' => $match['team2']] as $selection => $name) {
                $choice = $choices[$selection] ?? [];
                $opening = $choice['opening_odds'] ?? [];
                $runners[] = $this->footballOddsRunner($name, ($market['suspended'] ?? false) ? null : ($choice['decimal_odds'] ?? null), hasMeaningfulOpeningOdds($opening) ? $opening['initialBackOdds'] : null);
            }
        } else {
            $quote = $match['bet365_prematch'] ?? [];
            if ((int) ($quote['bookmaker_id'] ?? 0) !== 126) {
                return [];
            }
            foreach (['home_odds' => $match['team1'], 'draw_odds' => 'Draw', 'away_odds' => $match['team2']] as $key => $name) {
                $runners[] = $this->footballOddsRunner($name, $quote[$key] ?? null, $quote['opening_odds'][$key]['initialBackOdds'] ?? null);
            }
        }

        return [['source' => 'bet365', 'label' => ($match['result_status'] === 'Live' ? 'Live' : 'Pre-match').' · Match Odds / 1X2', 'runners' => $runners]];
    }

    /** @return array{runner: string, currentBackOdds: ?float, previousBackOdds: ?float, odds_direction: string} */
    private function footballOddsRunner(string $name, mixed $current, mixed $previous): array
    {
        $current = is_numeric($current) && is_finite((float) $current) && (float) $current > 1 ? (float) $current : null;
        $previous = is_numeric($previous) && is_finite((float) $previous) && (float) $previous > 1 ? (float) $previous : null;

        return [
            'runner' => $name, 'currentBackOdds' => $current, 'previousBackOdds' => $previous,
            'odds_direction' => $current === null || $previous === null || $current === $previous ? 'same' : ($current < $previous ? 'down' : 'up'),
        ];
    }

    private function footballLeaguePriority(string $tournament, ?string $country = null): int
    {
        $normalize = static fn (string $name): string => strtolower((string) preg_replace('/[^a-z0-9]+/i', '', Str::ascii($name)));
        static $priorities = null;
        $priorities ??= array_flip(array_map($normalize, self::LEAGUE_PRIORITY));
        $aliases = [
            'englishpremierleague' => 'Premier League', 'spanishlaliga' => 'La Liga',
            'italianseriea' => 'Serie A', 'germanbundesliga' => 'Bundesliga',
            'frenchligue1' => 'Ligue 1', 'uefaeuropaconferenceleague' => 'UEFA Conference League',
            'portugueseprimeiraliga' => 'Primeira Liga', 'dutcheredivisie' => 'Eredivisie',
            'turkishsuperlig' => 'Süper Lig', 'belgianjupilerproleague' => 'Belgian Pro League',
            'englishchampionship' => 'EFL Championship', 'englishleague1' => 'EFL League One',
            'brazilianseriea' => 'Brasileirão Série A', 'usamls' => 'Major League Soccer (MLS)',
            'mls' => 'Major League Soccer (MLS)', 'majorleaguesoccer' => 'Major League Soccer (MLS)',
        ];
        $name = $normalize($tournament);
        if ($country !== null && $country !== '' && $name === 'premierleague' && $normalize($country) !== 'england') {
            return PHP_INT_MAX;
        }
        if ($name === 'bundesliga' && $normalize($country ?? '') === 'austria') {
            $name = 'austrianbundesliga';
        }

        return $priorities[$normalize($aliases[$name] ?? $name)] ?? PHP_INT_MAX;
    }

    private function footballTimeline(array $match, array $timeline): array
    {
        $clock = $timeline['clock'] ?? [];
        $finished = ($match['result_status'] ?? '') === 'Finished' || ($clock['finished'] ?? false);
        $clockCode = strtoupper((string) ($clock['status_code'] ?? ''));
        $clockStatus = match ($clockCode) {
            '6' => '1st half',
            '7' => '2nd half',
            '31', 'HT' => 'Half Time',
            default => null,
        };
        $matchStatus = $match['minutes_status'] ?? $match['result_status'];
        $phases = ['1st half' => 1, 'Half Time' => 2, '2nd half' => 3];
        $matchPeriodStart = (int) ($match['current_period_start_timestamp'] ?? 0);
        $clockPeriodStart = (int) ($clock['period_start'] ?? 0);
        $useClock = $clockStatus !== null && (! isset($phases[$matchStatus])
            || ($clockPeriodStart >= $matchPeriodStart && ($phases[$clockStatus] > $phases[$matchStatus] || ($matchPeriodStart === 0 && $clockPeriodStart > 0 && $phases[$clockStatus] === $phases[$matchStatus]))));
        $minuteStatus = $useClock ? $clockStatus : $matchStatus;
        $periodStart = $useClock ? $clockPeriodStart : $matchPeriodStart;
        $live = ! $finished && (($match['result_status'] ?? '') === 'Live' || ($clock['status_type'] ?? '') === 'inprogress' || $clockStatus !== null);
        $minuteLabel = (string) $this->calculateMinutes($minuteStatus, (int) $match['timestamp'], $finished ? 'Finished' : ($live ? 'Live' : 'Prematch'), $match['added_time'] ?? [], $periodStart ?: null);
        $halfTime = $minuteLabel === 'HT';
        $periodLimit = $minuteStatus === '2nd half' ? 90 : 45;
        $minute = 0;
        if ($finished) {
            $minute = 90;
            $minuteLabel = 'FT';
        } elseif ($halfTime) {
            $minute = 45;
        } elseif ($live) {
            if (preg_match('/^(\d+)(?:\+(\d+))?/', $minuteLabel, $parts)) {
                $minute = (int) $parts[1] + (int) ($parts[2] ?? 0);
            }
            if ($useClock && $periodStart > 0) {
                $initial = (int) ($clock['initial'] ?? ($minuteStatus === '2nd half' ? 2700 : 0));
                $minute = max(0, (int) floor((time() - $periodStart + $initial) / 60));
                $periodLimit = (int) (($clock['max'] ?? ($minuteStatus === '2nd half' ? 5400 : 2700)) / 60);
            }
            if ($minute > 45 && ! str_starts_with($minuteLabel, '45+')) {
                $periodLimit = max(90, $periodLimit);
            }
            $minuteLabel = $minute > $periodLimit ? $periodLimit.'+'.($minute - $periodLimit) : (string) $minute;
        } else {
            $minuteLabel = __('Not started');
        }
        $events = array_values($timeline['events'] ?? []);
        foreach ($timeline['unplaced_card_counts'] ?? [] as $side => $cards) {
            foreach ($cards as $kind => $count) {
                for ($index = 0; $index < $count; $index++) {
                    $events[] = [
                        'id' => 'stats-'.$side.'-'.$kind.'-'.$index,
                        'kind' => $kind,
                        'minute' => 0, 'added_time' => 0, 'seconds' => 0, 'time_known' => false,
                        'is_home' => $side === 'home', 'source' => 'statistics',
                        'player' => '', 'player_surname' => '', 'home_score' => null, 'away_score' => null,
                    ];
                }
            }
        }
        usort($events, static fn (array $first, array $second): int => ($first['seconds'] <=> $second['seconds']) ?: strcmp($first['id'], $second['id']));
        $missingHomeGoals = 0;
        $missingGoals = 0;
        $goalsPending = false;
        if (isset($match['home_score'], $match['away_score'])) {
            $confirmedGoals = ['home' => (int) $match['home_score'], 'away' => (int) $match['away_score']];
            $events = array_values(array_filter($events, static function (array $event) use (&$confirmedGoals): bool {
                if ($event['kind'] !== 'goal') {
                    return true;
                }
                $side = $event['is_home'] ? 'home' : 'away';

                return $confirmedGoals[$side]-- > 0;
            }));
            $missingHomeGoals = max(0, $confirmedGoals['home']);
            $missingGoals = $missingHomeGoals + max(0, $confirmedGoals['away']);
            $goalsPending = $confirmedGoals['home'] !== 0 || $confirmedGoals['away'] !== 0;
        }
        $stoppageCount = count(array_filter($events, static function (array $event): bool {
            $minute = $event['minute'] === 0 && $event['added_time'] === 0 && $event['seconds'] > 0
                ? (int) floor($event['seconds'] / 60) : $event['minute'];

            return $minute + $event['added_time'] > 90;
        }));
        $stoppageIndex = 0;
        $lanes = ['home' => [], 'away' => []];
        $untimedEvents = ['home' => [], 'away' => []];
        foreach ($events as &$event) {
            $event['time_known'] = $event['time_known'] ?? ($event['minute'] > 0 || $event['added_time'] > 0 || $event['seconds'] > 0);
            if ($event['minute'] === 0 && $event['added_time'] === 0 && $event['seconds'] > 0) {
                $event['minute'] = (int) floor($event['seconds'] / 60);
            }
            $event['left'] = $event['time_known'] ? min(100, max(0, $event['minute'] / 90 * 100)) : null;
            $event['end_offset'] = null;
            if ($event['minute'] + $event['added_time'] > 90) {
                $event['end_offset'] = $stoppageCount - 1 - $stoppageIndex++;
                $event['left'] = max(0, 100 - $event['end_offset'] * 2);
            }
            $side = $event['is_home'] ? 'home' : 'away';
            $lane = 0;
            if ($event['time_known']) {
                while (isset($lanes[$side][$lane]) && $event['left'] - $lanes[$side][$lane] < 4) {
                    $lane++;
                }
                $lanes[$side][$lane] = $event['left'];
            }
            $event['lane'] = $lane;
            $displayMinute = min(90, $event['minute']);
            $added = $event['added_time'] + max(0, $event['minute'] - 90);
            $event['minute_label'] = $event['time_known'] ? $displayMinute.($added > 0 ? '+'.$added : '')."'" : '';
            $event['label'] = match ($event['kind']) {
                'goal' => __('Goal'),
                'yellow' => __('Yellow card'),
                default => __('Red card'),
            };
            $event['player'] = trim((string) ($event['player'] ?? ''));
            $playerParts = $event['player'] !== '' ? preg_split('/\s+/u', $event['player']) : [];
            $event['player_surname'] = trim((string) ($event['player_surname'] ?? '')) ?: ($playerParts !== [] ? end($playerParts) : '');
            if (! $event['time_known']) {
                $untimedEvents[$side][] = $event;
            }
        }
        unset($event);

        $goalLabel = $missingGoals > 0 ? __('Goal') : '';
        for ($index = 0; $index < $missingGoals; $index++) {
            $isHome = $index < $missingHomeGoals;
            $side = $isHome ? 'home' : 'away';
            $goalIndex = $isHome ? $index : $index - $missingHomeGoals;
            $event = [
                'id' => 'score-'.$side.'-goal-'.$goalIndex,
                'kind' => 'goal',
                'minute' => 0, 'added_time' => 0, 'seconds' => 0, 'time_known' => false,
                'is_home' => $isHome, 'source' => 'score',
                'player' => '', 'player_surname' => '', 'home_score' => null, 'away_score' => null,
                'left' => null, 'end_offset' => null, 'lane' => 0, 'minute_label' => '', 'label' => $goalLabel,
            ];
            $events[] = $event;
            $untimedEvents[$side][] = $event;
        }

        return [
            'events' => $events,
            'untimed_events' => $untimedEvents,
            'goals_pending' => $goalsPending,
            'height' => 64 + max(0, count($lanes['home']) - 1, count($lanes['away']) - 1) * 28,
            'minute' => $minute,
            'minute_label' => $minuteLabel.($live && ! $halfTime ? "'" : ''),
            'period_limit' => $periodLimit,
            'progress' => min(100, max(0, $minute / 90 * 100)),
            'running' => $live && ! $halfTime,
            'seconds_into_minute' => $live && ! $halfTime ? max(0, time() - ($periodStart ?: (int) $match['timestamp'])) % 60 : 0,
            'calculated_at' => time(),
            'finished' => $finished,
        ];
    }

    public function calculateMinutes(?string $minute_status, int $timestamp, string $result_status, array $added_time = [], ?int $current_period_start_timestamp = null)
    {
        if ($minute_status == 'Prematch' || $result_status == 'Prematch') {
            $time = Carbon::parse($timestamp)->timezone(getCurrentUserTimezone())->format('H:i');

            return $time;
        }

        if ($minute_status == 'Finished' || $result_status == 'Finished') {
            return 'FT';
        }

        if ($minute_status === 'Half Time' && $result_status === 'Live') {
            return 'HT';
        }

        if (! in_array($minute_status, ['Live', '1st half', '2nd half'], true) || $result_status != 'Live') {
            return null;
        }

        $elapsedMinutes = max(0, (int) floor((time() - $timestamp) / 60));
        $periodElapsedMinutes = $current_period_start_timestamp !== null && $current_period_start_timestamp > 0
            ? max(0, (int) floor((time() - $current_period_start_timestamp) / 60))
            : null;

        $isExplicitSecondHalf = $minute_status === '2nd half';
        if ($minute_status === 'Live' && $elapsedMinutes >= 60) {
            $minute_status = '2nd half';
        }

        if ($minute_status == '2nd half') {
            $currentMinute = $isExplicitSecondHalf && $periodElapsedMinutes !== null
                ? 45 + $periodElapsedMinutes
                : max(46, $elapsedMinutes - 15);
            if ($currentMinute > 90) {
                $extraMinutes = $added_time['second_half'] ?? ($currentMinute - 90);

                return "90+{$extraMinutes}";
            }

            return $currentMinute;
        }

        $firstHalfElapsedMinutes = $minute_status === '1st half' && $periodElapsedMinutes !== null
            ? $periodElapsedMinutes
            : $elapsedMinutes;

        if ($minute_status == 'Live' && $firstHalfElapsedMinutes > 45) {
            $extraMinutes = $added_time['first_half'] ?? null;

            return $extraMinutes === null ? '45+' : "45+{$extraMinutes}";
        }

        if ($firstHalfElapsedMinutes > 45) {
            $extraMinutes = $added_time['first_half'] ?? ($firstHalfElapsedMinutes - 45);

            return "45+{$extraMinutes}";
        }

        return $firstHalfElapsedMinutes;
    }

    public function getScore(array $match)
    {
        if (in_array($match['result_status'], ['Live', 'Finished'], true)) {
            return $match['home_score'].' - '.$match['away_score'];
        } else {
            return '-';
        }
    }

    public function getAllMarkets()
    {
        $sportMarkets = [
            'default' => 'MATCH_ODDS',
            'options' => [
                [
                    'value' => 'MATCH_ODDS',
                    'label' => 'Match Odds / 1X2',
                    'oddsCount' => 3,
                ],
                [
                    'value' => 'OVER_UNDER_25',
                    'label' => 'Under/Over 2.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_05',
                    'label' => 'Under/Over 0.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_15',
                    'label' => 'Under/Over 1.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OVER_UNDER_35',
                    'label' => 'Under/Over 3.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'BOTH_TEAMS_TO_SCORE',
                    'label' => 'Both Teams To Score',
                    'oddsCount' => 2,
                ],
            ],
        ];

        return $sportMarkets;
    }

    public function getFootballMatchDetail(string $matchId): ?array
    {
        $match = null;

        // foreach ((getInRedis('Football:matches') ?? []) as $candidate) {
        //     if (
        //         (string) ($candidate['sportsApiPro_match_id'] ?? '') === $matchId
        //     ) {
        //         $match = $candidate;
        //         break;
        //     }
        // }
        $match = collect(getInRedis('Football:matches') ?? [])->first(fn ($candidate) => (string) ($candidate['sportsApiPro_match_id'] ?? '') === (string) $matchId);

        if ($match === null) {
            $storedMatch = FootballMatch::query()
                ->with('markets')
                ->where('sports_api_pro_match_id', $matchId)
                ->first();

            if ($storedMatch !== null) {
                $match = array_replace($storedMatch->payload ?? [], [
                    'unique_key' => $storedMatch->unique_key,
                    'sportsApiPro_match_id' => $storedMatch->sports_api_pro_match_id,
                    'betfair_match_id' => $storedMatch->betfair_match_id,
                    'team1' => $storedMatch->team1,
                    'team2' => $storedMatch->team2,
                    'tournament' => $storedMatch->tournament,
                    'result_status' => $storedMatch->result_status,
                    'minutes_status' => $storedMatch->minutes_status,
                    'timestamp' => $storedMatch->start_timestamp,
                    'data_sync_complete' => $storedMatch->data_sync_complete,
                    'markets' => [],
                ]);

                foreach ($storedMatch->markets->sortBy('sort_priority') as $storedMarket) {
                    $match['markets'][$storedMarket->market_id.'_'.$storedMarket->market_type][] = $storedMarket->payload;
                }

                foreach (['home_team_logo' => 'home_team_id', 'away_team_logo' => 'away_team_id'] as $logoKey => $teamIdKey) {
                    $teamId = (int) ($match[$teamIdKey] ?? 0);
                    $localImages = $teamId > 0 ? glob(public_path("images/football/teams/{$teamId}.*")) : [];

                    if ($localImages !== false && isset($localImages[0]) && is_file($localImages[0])) {
                        $match[$logoKey] = '/images/football/teams/'.basename($localImages[0]);
                    }
                }
            }
        }

        if ($match === null) {
            return null;
        }

        // $match = prepareMatchOddsMonitoring($match);
        // $marketLabels = [];
        // foreach ($this->getAllMarkets()['options'] as $option) {
        //     $marketLabels[$option['value']] = $option['label'];
        // }

        $markets = [];
        foreach (($match['markets'] ?? []) as $marketRunners) {
            if (! is_array($marketRunners)) {
                continue;
            }

            foreach ($marketRunners as $runner) {
                $marketType = $runner['marketType'] ?? null;
                // if (! isset($marketLabels[$marketType])) {
                //     continue;
                // }

                $currentOdds = (float) ($runner['currentBackOdds'] ?? $runner['backOdds'] ?? 0);
                // if ($currentOdds <= 0) {
                //     continue;
                // }

                $initialOdds = (float) ($runner['initialBackOdds'] ?? 0);
                // $history = $runner['backOddsHistory'] ?? [];

                $markets[$marketType] ??= [
                    'type' => $marketType,
                    'label' => $runner['name'],
                    'runners' => [],
                ];
                $markets[$marketType]['runners'][] = [
                    'runner_id' => $runner['runnerId'] ?? null,
                    'match_id' => (int) $matchId,
                    'market_id' => $runner['marketId'] ?? null,
                    'history' => oddsMovementHistory($runner),
                    'history_incomplete' => (bool) ($runner['odds_history_incomplete'] ?? false),
                    'name' => $runner['runner'] ?? 'Selection',
                    'initial_back_odds' => $initialOdds,
                    'current_back_odds' => $currentOdds,
                    'direction' => $initialOdds > 1 ? $currentOdds <=> $initialOdds : 0,
                    'change_percent' => $initialOdds > 0
                        ? (($currentOdds - $initialOdds) / $initialOdds) * 100
                        : 0,
                    // 'history' => count($history) > 12
                    //     ? array_merge([$history[0]], array_slice($history, -11))
                    //     : $history,
                ];
            }
        }
        // dump($markets);

        // $orderedMarkets = [];
        // foreach (array_keys($marketLabels) as $marketType) {
        //     if (isset($markets[$marketType])) {
        //         $orderedMarkets[] = $markets[$marketType];
        //     }
        // }

        return [
            'id' => $matchId,
            'team1' => $match['team1'],
            'team2' => $match['team2'],
            'home_team_logo' => $match['home_team_logo'] ?? null,
            'away_team_logo' => $match['away_team_logo'] ?? null,
            'tournament' => $match['tournament'] ?? '',
            'status' => $match['result_status'],
            'score' => $this->getScore($match),
            'minutes' => $this->calculateMinutes(
                $match['minutes_status'] ?? $match['result_status'],
                (int) $match['timestamp'],
                $match['result_status'],
                $match['added_time'] ?? [],
                $match['current_period_start_timestamp'] ?? null
            ),
            'timestamp' => (int) $match['timestamp'],
            'markets' => $markets,
        ];
    }

    public function getMarketActivites()
    {
        $getData = array_map('prepareMatchOddsMonitoring', getInRedis('Football:matches') ?? []);
        $mainArray = [];
        $now = time();
        $type = request()->input('type', 'all');

        switch ($type) {
            case 'live':
                $getData = array_values(array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Live'));
                break;

            case 'upcoming':
                $getData = array_values(array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Prematch'));
                break;

            case 'favorites':
                $customerId = request()->user('customers')?->getAuthIdentifier();
                $favoriteKeys = $customerId === null ? [] : array_fill_keys(
                    Favorite::query()->where('sport', 'football')->where('customer_id', $customerId)->pluck('event_key')->all(),
                    true
                );
                $getData = array_values(array_filter(
                    $getData,
                    static fn (array $match): bool => isset($favoriteKeys[$match['unique_key'] ?? ''])
                ));
                break;

            case 'ht00':
                $getData = array_values(array_filter($getData, static function (array $match): bool {
                    $minuteStatus = strtolower(trim((string) ($match['minutes_status'] ?? '')));

                    return ($match['result_status'] ?? null) === 'Live'
                        && in_array($minuteStatus, ['half time', 'halftime', 'ht'], true)
                        && (int) ($match['home_score'] ?? 0) === 0
                        && (int) ($match['away_score'] ?? 0) === 0;
                }));
                break;

            case '00_70':
                $getData = array_values(array_filter($getData, function (array $match): bool {
                    if (
                        ($match['result_status'] ?? null) !== 'Live'
                        || (int) ($match['home_score'] ?? 0) !== 0
                        || (int) ($match['away_score'] ?? 0) !== 0
                    ) {
                        return false;
                    }

                    $minute = $this->calculateMinutes(
                        $match['minutes_status'] ?? $match['result_status'],
                        (int) ($match['timestamp'] ?? time()),
                        $match['result_status'],
                        $match['added_time'] ?? [],
                        $match['current_period_start_timestamp'] ?? null
                    );

                    return is_numeric($minute) ? (int) $minute >= 70 : str_starts_with((string) $minute, '90+');
                }));
                break;

            case 'fav_losing':
            case 'fav_not_winning':
                $getData = array_values(array_filter($getData, function (array $match) use ($type): bool {
                    $favoriteOdds = (float) ($match['bet365_prematch']['favorite_odds'] ?? 0);
                    $maximumOdds = $type === 'fav_losing' ? 2.10 : 1.87;

                    if (
                        ($match['result_status'] ?? null) !== 'Live'
                        || $favoriteOdds < 1.01
                        || $favoriteOdds > $maximumOdds
                    ) {
                        return false;
                    }

                    if ($type === 'fav_not_winning') {
                        $minute = $this->calculateMinutes(
                            $match['minutes_status'] ?? $match['result_status'],
                            (int) ($match['timestamp'] ?? time()),
                            $match['result_status'],
                            $match['added_time'] ?? [],
                            $match['current_period_start_timestamp'] ?? null
                        );

                        if ($minute === 'HT') {
                            $currentMinute = 45;
                        } elseif (preg_match('/^(\d+)/', (string) $minute, $minuteMatch) === 1) {
                            $currentMinute = (int) $minuteMatch[1];
                        } else {
                            return false;
                        }

                        if ($currentMinute < 29) {
                            return false;
                        }
                    }

                    $favoriteSide = $match['bet365_prematch']['favorite_side'] ?? null;
                    $homeScore = (int) ($match['home_score'] ?? 0);
                    $awayScore = (int) ($match['away_score'] ?? 0);

                    if ($favoriteSide === 'home') {
                        return $type === 'fav_losing'
                            ? $homeScore < $awayScore
                            : $homeScore <= $awayScore;
                    }

                    if ($favoriteSide === 'away') {
                        return $type === 'fav_losing'
                            ? $awayScore < $homeScore
                            : $awayScore <= $homeScore;
                    }

                    return false;
                }));
                break;

            case 'prematch_drops':
                $getData = array_values(array_filter($getData, static function (array $match): bool {
                    if (($match['result_status'] ?? null) !== 'Prematch') {
                        return false;
                    }

                    if (($match['pinnacle_drop_triggered'] ?? false) === true) {
                        return true;
                    }

                    foreach (($match['markets'] ?? []) as $marketRunners) {
                        foreach (($marketRunners ?? []) as $runner) {
                            if (($runner['prematch_drop_triggered'] ?? false) === true) {
                                return true;
                            }
                        }
                    }

                    return false;
                }));
                break;

            case 'market_shock':
                $shockCutoff = $now - 1200;
                $getData = array_values(array_filter($getData, static function (array $match) use ($shockCutoff): bool {
                    if (($match['result_status'] ?? null) !== 'Prematch') {
                        return false;
                    }

                    if ((int) ($match['pinnacle_market_shock_detected_at'] ?? 0) >= $shockCutoff) {
                        return true;
                    }

                    foreach (($match['markets'] ?? []) as $marketRunners) {
                        foreach (($marketRunners ?? []) as $runner) {
                            if ((int) ($runner['market_shock_detected_at'] ?? 0) >= $shockCutoff) {
                                return true;
                            }
                        }
                    }

                    return false;
                }));
                break;
        }

        foreach ($getData as $data) {
            if (($data['data_sync_complete'] ?? false) !== true) {
                continue;
            }

            if (($data['result_status'] ?? null) === 'Finished') {
                continue;
            }

            $isLive = ($data['result_status'] ?? null) === 'Live';
            $secondsUntilStart = max(0, (int) ($data['timestamp'] ?? $now) - $now);
            $minutesUntilStart = (int) ceil($secondsUntilStart / 60);
            $statusPriority = $isLive ? 0 : ($minutesUntilStart <= 5 ? 1 : ($minutesUntilStart <= 10 ? 2 : 3));
            $eventTotalLiquidity = 0.0;

            foreach (($data['markets'] ?? []) as $eventMarkets) {
                if (! is_array($eventMarkets)) {
                    continue;
                }

                foreach ($eventMarkets as $eventMarket) {
                    if (is_array($eventMarket)) {
                        $eventTotalLiquidity += (float) ($eventMarket['backSize'] ?? 0)
                            + (float) ($eventMarket['laySize'] ?? 0);
                    }
                }
            }

            foreach (($data['markets'] ?? []) as $marketKey => $markets) {
                if (! is_array($markets)) {
                    continue;
                }

                $runners = [];
                $favoriteIndex = null;
                $lowestBackOdds = null;
                $totalBackSize = 0.0;
                $totalLaySize = 0.0;
                $marketId = null;
                $marketType = null;
                $marketName = null;
                $favoritePreviousBackOdds = null;
                $favoriteOddsDirection = 'same';

                foreach ($markets as $market) {
                    if (! is_array($market)) {
                        continue;
                    }

                    $backOdds = (float) ($market['currentBackOdds'] ?? $market['backOdds'] ?? 0);
                    $backSize = (float) ($market['backSize'] ?? 0);
                    $layOdds = (float) ($market['layOdds'] ?? 0);
                    $laySize = (float) ($market['laySize'] ?? 0);

                    $runners[] = [
                        'id' => $market['runnerId'] ?? null,
                        'name' => $market['runner'] ?? '',
                        'back' => $backOdds,
                        'backSize' => $backSize,
                        'lay' => $layOdds,
                        'laySize' => $laySize,
                        'initialBack' => (float) ($market['initialBackOdds'] ?? 0),
                    ];

                    $runnerIndex = array_key_last($runners);
                    if ($backOdds > 0 && ($lowestBackOdds === null || $backOdds < $lowestBackOdds)) {
                        $lowestBackOdds = $backOdds;
                        $favoriteIndex = $runnerIndex;
                        $favoritePreviousBackOdds = (float) ($market['initialBackOdds'] ?? 0);
                        $favoriteOddsDirection = $favoritePreviousBackOdds <= 1 || $backOdds === $favoritePreviousBackOdds
                            ? 'same'
                            : ($backOdds < $favoritePreviousBackOdds ? 'down' : 'up');
                    }

                    $totalBackSize += $backSize;
                    $totalLaySize += $laySize;
                    $marketId ??= $market['marketId'] ?? null;
                    $marketType ??= $market['marketType'] ?? null;
                    $marketName ??= $market['name'] ?? null;
                }

                if (empty($runners)) {
                    continue;
                }

                if ($favoriteIndex === null) {
                    $favoriteIndex = 0;
                    $favoritePreviousBackOdds = (float) ($runners[0]['initialBack'] ?? 0);
                    $favoriteOddsDirection = (float) ($runners[0]['back'] ?? 0) === $favoritePreviousBackOdds
                        ? 'same'
                        : ((float) ($runners[0]['back'] ?? 0) < $favoritePreviousBackOdds ? 'down' : 'up');
                }

                $favoriteBackOdds = (float) $runners[$favoriteIndex]['back'];
                $oddsChangePercent = $favoritePreviousBackOdds > 0
                    ? (($favoritePreviousBackOdds - $favoriteBackOdds) / $favoritePreviousBackOdds) * 100
                    : 0.0;
                $absoluteOddsChange = abs($oddsChangePercent);
                $balance = $totalBackSize > ($totalLaySize * 1.05)
                    ? 'back'
                    : ($totalLaySize > ($totalBackSize * 1.05) ? 'lay' : 'balanced');
                $momentum = match ($favoriteOddsDirection) {
                    'down' => $absoluteOddsChange >= 10 ? 'strong_back' : 'back',
                    'up' => $absoluteOddsChange >= 10 ? 'strong_lay' : 'lay',
                    default => 'neutral',
                };
                $volatility = $absoluteOddsChange >= 10
                    ? 'high'
                    : ($absoluteOddsChange >= 3 ? 'medium' : 'low');
                $delta = ($oddsChangePercent > 0 ? '+' : '').number_format($oddsChangePercent, 1).'%';
                $displayMinutes = $isLive
                    ? $this->calculateMinutes(
                        $data['minutes_status'] ?? $data['result_status'],
                        (int) $data['timestamp'],
                        $data['result_status'],
                        $data['added_time'] ?? [],
                        $data['current_period_start_timestamp'] ?? null
                    )
                    : null;
                $displayTime = $isLive
                    ? (preg_match('/^\d+(?:\+\d*)?$/', (string) $displayMinutes) === 1 ? $displayMinutes."'" : $displayMinutes)
                    : ($minutesUntilStart >= 60
                        ? 'in '.intdiv($minutesUntilStart, 60).'h'.($minutesUntilStart % 60 > 0 ? ' '.($minutesUntilStart % 60).'m' : '')
                        : 'in '.$minutesUntilStart.' min');
                $uiMarket = match ($marketType) {
                    'MATCH_ODDS' => 'match_odds',
                    'OVER_UNDER_05' => 'ou_0_5',
                    'OVER_UNDER_15' => 'ou_1_5',
                    'OVER_UNDER_25' => 'ou_2_5',
                    'OVER_UNDER_35' => 'ou_3_5',
                    'OVER_UNDER_45' => 'ou_4_5',
                    'BOTH_TEAMS_TO_SCORE' => 'btts',
                    'CORRECT_SCORE' => 'correct_score',
                    'HALF_TIME' => 'first_half',
                    'FIRST_HALF_GOALS_05' => 'fh_ou_0_5',
                    'FIRST_HALF_GOALS_15' => 'fh_ou_1_5',
                    default => strtolower((string) ($marketType ?? $marketKey)),
                };

                $mainArray[] = [
                    'id' => (int) sprintf('%u', crc32(($data['unique_key'] ?? '').'_'.($marketId ?? $marketKey))),
                    'sport' => 'football',
                    'sportIcon' => '⚽',
                    'status' => $isLive ? 'LIVE' : 'UP',
                    'statusPriority' => $statusPriority,
                    'time' => $displayTime,
                    'comp' => $data['tournament'] ?? '',
                    'competition_country' => $data['tournament_country'] ?? null,
                    'match' => ($data['team1'] ?? '').' vs '.($data['team2'] ?? ''),
                    'score' => $this->getScore($data),
                    'market' => $uiMarket,
                    'marketName' => $marketName ?? $marketType ?? $marketKey,
                    'marketDisplayScore' => $uiMarket === 'correct_score' ? $this->getScore($data) : null,
                    'runners' => $runners,
                    'favIdx' => $favoriteIndex,
                    'favorite' => $favoriteIndex !== null ? $runners[$favoriteIndex] : null,
                    'back' => $favoriteBackOdds,
                    'backSize' => $totalBackSize,
                    'lay' => (float) $runners[$favoriteIndex]['lay'],
                    'laySize' => $totalLaySize,
                    'balance' => $balance,
                    'momentum' => $momentum,
                    'delta' => $delta,
                    'volatility' => $volatility,
                    'totalBackSize' => $totalBackSize,
                    'totalLaySize' => $totalLaySize,
                    'marketLiquidity' => $totalBackSize + $totalLaySize,
                    'totalLiquidity' => $eventTotalLiquidity,
                ];
            }
        }

        usort($mainArray, function (array $first, array $second): int {
            $leagueComparison = $this->footballLeaguePriority((string) ($first['comp'] ?? ''), $first['competition_country'] ?? null)
                <=> $this->footballLeaguePriority((string) ($second['comp'] ?? ''), $second['competition_country'] ?? null);
            if ($leagueComparison !== 0) {
                return $leagueComparison;
            }
            $statusComparison = $first['statusPriority'] <=> $second['statusPriority'];

            if ($statusComparison !== 0) {
                return $statusComparison;
            }

            return $second['totalLiquidity'] <=> $first['totalLiquidity'];
        });

        $marketName = request()->input('market_name', 'active');
        if ($marketName !== 'active') {
            $mainArray = array_values(array_filter(
                $mainArray,
                static fn (array $market): bool => ($market['market'] ?? null) === $marketName
            ));
        }

        $page = max(1, (int) request()->input('page', 1));
        $perPage = min(100, max(1, (int) request()->input('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->marketActivityHasMore = count($mainArray) > ($offset + $perPage);
        $this->marketActivityNextPage = $this->marketActivityHasMore ? $page + 1 : null;

        return array_slice($mainArray, $offset, $perPage);
    }
}
