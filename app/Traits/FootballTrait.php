<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait FootballTrait
{
    protected bool $footballHasMore = false;

    protected ?int $footballNextPage = null;

    protected bool $marketActivityHasMore = false;

    protected ?int $marketActivityNextPage = null;

    public function getFootballMatches()
    {
        $getData = getInRedis('Football:matches') ?? [];
        $favoriteQuery = DB::table('favorites')->where('sport', 'football');
        $userId = request()->user()?->getAuthIdentifier();

        if ($userId === null) {
            $favoriteQuery->where('session_id', request()->session()->getId());
        } else {
            $favoriteQuery->where('user_id', $userId);
        }

        $favoriteKeys = array_fill_keys($favoriteQuery->pluck('event_key')->all(), true);

        if (request()->has('market_name')) {
            $marketName = request()->market_name;
        } else {
            $marketName = 'MATCH_ODDS';
        }

        $type = null;
        if (request()->has('type')) {
            $type = request()->type;
        }

        $today = Carbon::now(getCurrentUserTimezone())->toDateString();
        $getData = array_values(array_filter($getData, static fn (array $match): bool => isset($match['timestamp'])
            && Carbon::createFromTimestamp((int) $match['timestamp'], getCurrentUserTimezone())->toDateString() === $today
        ));

        if ($type) {
            switch ($type) {
                case 'upcoming':
                    $getData = array_values(array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Prematch'));
                    usort($getData, static fn (array $first, array $second): int => (int) ($first['timestamp'] ?? 0) <=> (int) ($second['timestamp'] ?? 0));
                    break;

                case 'live':
                    $getData = array_values(array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Live'));
                    break;

                case 'finished':
                    $finishedCutoff = time() - (30 * 86400);
                    $getData = array_values(array_filter($getData, static fn (array $match): bool => ($match['result_status'] ?? null) === 'Finished'
                        && (int) ($match['timestamp'] ?? 0) >= $finishedCutoff
                    ));
                    break;

                case 'by_time':
                    usort($getData, static fn (array $first, array $second): int => (int) ($second['timestamp'] ?? 0) <=> (int) ($first['timestamp'] ?? 0));
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
                            $match['added_time'] ?? []
                        );

                        return is_numeric($minute) ? (int) $minute >= 70 : str_starts_with((string) $minute, '90+');
                    }));
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

                case 'favorites':
                    $getData = array_values(array_filter(
                        $getData,
                        static fn (array $match): bool => isset($favoriteKeys[$match['unique_key'] ?? ''])
                    ));
                    break;

                case 'fav_losing':
                case 'fav_not_winning':
                    $getData = array_values(array_filter($getData, function (array $match) use ($type): bool {
                        $favoriteOdds = (float) ($match['prematch_favorite_odds'] ?? 0);
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
                                $match['added_time'] ?? []
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

                        $favoriteSide = $match['prematch_favorite_side'] ?? null;
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
                    $shockCutoff = time() - 1200;
                    $getData = array_values(array_filter($getData, static function (array $match) use ($shockCutoff): bool {
                        if (($match['result_status'] ?? null) !== 'Prematch') {
                            return false;
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

                case 'all':
                    $getData = $getData;
                    break;

                default:
                    $getData = $getData;
                    break;
            }
        }

        $getData = array_values(array_filter(
            $getData,
            static fn (array $match): bool => ($match['data_sync_complete'] ?? false) === true
        ));
        $page = max(1, (int) request()->input('page', 1));
        $perPage = min(100, max(1, (int) request()->input('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->footballHasMore = count($getData) > ($offset + $perPage);
        $this->footballNextPage = $this->footballHasMore ? $page + 1 : null;
        $getData = array_slice($getData, $offset, $perPage);

        $matchIds = array_values(array_unique(array_filter(array_map(
            static fn (array $match): ?string => isset($match['sportsApiPro_match_id'])
                ? (string) $match['sportsApiPro_match_id']
                : null,
            $getData
        ))));
        $predictionCounts = [];
        $storedPredictionVotes = [];

        if ($matchIds !== []) {
            $countRows = DB::table('prediction_votes')
                ->select(['match_id', 'poll', 'value'])
                ->selectRaw('COUNT(*) as total')
                ->where('sport', 'football')
                ->whereIn('match_id', $matchIds)
                ->groupBy('match_id', 'poll', 'value')
                ->get();

            foreach ($countRows as $row) {
                $predictionCounts[$row->match_id][$row->poll][$row->value] = (int) $row->total;
            }

            $voteQuery = DB::table('prediction_votes')
                ->select(['match_id', 'poll', 'value'])
                ->where('sport', 'football')
                ->whereIn('match_id', $matchIds);

            if ($userId === null) {
                $voteQuery->where('session_id', request()->session()->getId());
            } else {
                $voteQuery->where('user_id', $userId);
            }

            foreach ($voteQuery->get() as $row) {
                $storedPredictionVotes[$row->match_id][$row->poll] = $row->value;
            }
        }

        $mainArray = [];
        foreach ($getData ?? [] as $key => $data) {
            if (($data['data_sync_complete'] ?? false) !== true) {
                continue;
            }
            $markets = $data['markets'];
            $marketKey = array_key_first(array_filter($markets, fn ($key) => str_contains($key, $marketName), ARRAY_FILTER_USE_KEY));
            $selectedMarketRunners = $markets[$marketKey] ?? [];

            if ($marketName === 'MATCH_ODDS') {
                $homeTeam = trim((string) ($data['team1'] ?? ''));
                $awayTeam = trim((string) ($data['team2'] ?? ''));
                $homeRunner = [];
                $drawRunner = [];
                $awayRunner = [];
                $otherRunners = [];

                foreach ($selectedMarketRunners as $selectedMarketRunner) {
                    $runnerName = trim((string) ($selectedMarketRunner['runner'] ?? ''));

                    if (strcasecmp($runnerName, $homeTeam) === 0) {
                        $homeRunner[] = $selectedMarketRunner;
                    } elseif (stripos($runnerName, 'draw') !== false) {
                        $drawRunner[] = $selectedMarketRunner;
                    } elseif (strcasecmp($runnerName, $awayTeam) === 0) {
                        $awayRunner[] = $selectedMarketRunner;
                    } else {
                        $otherRunners[] = $selectedMarketRunner;
                    }
                }

                $selectedMarketRunners = array_merge($homeRunner, $drawRunner, $awayRunner, $otherRunners);
            }

            foreach ($selectedMarketRunners as &$selectedRunner) {
                $initialOdds = (float) ($selectedRunner['initialBackOdds'] ?? $selectedRunner['previousBackOdds'] ?? 0);
                $currentOdds = (float) ($selectedRunner['currentBackOdds'] ?? $selectedRunner['backOdds'] ?? 0);
                $selectedRunner['odds_direction'] = $initialOdds <= 0 || $currentOdds === $initialOdds
                    ? 'same'
                    : ($currentOdds < $initialOdds ? 'down' : 'up');
            }
            unset($selectedRunner);

            $tournamentName = $data['tournament'];
            $matchId = (string) $data['sportsApiPro_match_id'];
            $predictionVotes = [];

            foreach (['1x2', 'uo'] as $poll) {
                $sessionKey = "football_predictions.{$matchId}.{$poll}";
                $predictionVotes[$poll] = request()->session()->get($sessionKey);

                if ($predictionVotes[$poll] === null && isset($storedPredictionVotes[$matchId][$poll])) {
                    $predictionVotes[$poll] = $storedPredictionVotes[$matchId][$poll];
                    request()->session()->put($sessionKey, $predictionVotes[$poll]);
                }
            }

            if (! isset($mainArray[$tournamentName])) {
                $mainArray[$tournamentName] = [
                    'tournament_name' => $tournamentName,
                    'unique_key' => $data['unique_key'],
                    'status' => $data['result_status'],
                    'matches' => [],
                ];
            }
            $mainArray[$tournamentName]['matches'][] = [
                'unique_key' => $data['unique_key'],
                'is_favorite' => isset($favoriteKeys[$data['unique_key']]),
                'team1' => $data['team1'],
                'team2' => $data['team2'],
                'status' => $data['result_status'],
                'sportsApiPro_match_id' => $data['sportsApiPro_match_id'],
                'betfair_match_id' => $data['betfair_match_id'],
                'score' => $this->getScore($data),
                'timestamp' => $data['timestamp'],
                'minutes' => $this->calculateMinutes($data['minutes_status'] ?? $data['result_status'], $data['timestamp'], $data['result_status'], $data['added_time'] ?? []),
                // 'status_type' => $data['status_type'] ?? null,
                'cards' => $data['cards'] ?? [],
                'card_counts' => $data['card_counts'] ?? [],
                'last_goal' => $data['last_goal'] ?? null,
                'goal_highlight_until' => $data['goal_highlight_until'] ?? null,
                'added_time' => $data['added_time'] ?? [],
                'corners' => $data['corners'] ?? [],
                'live_stats' => $data['live_stats'] ?? [],
                'predictions' => $predictionCounts[$matchId] ?? ($data['predictions'] ?? []),
                'prediction_votes' => $predictionVotes,
                'markets' => $selectedMarketRunners,
            ];
        }

        return $mainArray;
    }

    public function calculateMinutes(?string $minute_status, int $timestamp, string $result_status, array $added_time = [])
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

        if ($minute_status === 'Live' && $elapsedMinutes >= 60) {
            $minute_status = '2nd half';
        }

        if ($minute_status == '2nd half') {
            $currentMinute = max(46, $elapsedMinutes - 15);
            if ($currentMinute > 90) {
                $extraMinutes = $added_time['second_half'] ?? ($currentMinute - 90);

                return "90+{$extraMinutes}";
            }

            return $currentMinute;
        }

        if ($minute_status == 'Live' && $elapsedMinutes > 45) {
            $extraMinutes = $added_time['first_half'] ?? null;

            return $extraMinutes === null ? '45+' : "45+{$extraMinutes}";
        }

        if ($elapsedMinutes > 45) {
            $extraMinutes = $added_time['first_half'] ?? ($elapsedMinutes - 45);

            return "45+{$extraMinutes}";
        }

        return $elapsedMinutes;
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

        foreach ((getInRedis('Football:matches') ?? []) as $candidate) {
            if (
                ($candidate['data_sync_complete'] ?? false) === true
                && (string) ($candidate['sportsApiPro_match_id'] ?? '') === $matchId
            ) {
                $match = $candidate;
                break;
            }
        }

        if ($match === null) {
            return null;
        }

        $marketLabels = [];
        foreach ($this->getAllMarkets()['options'] as $option) {
            $marketLabels[$option['value']] = $option['label'];
        }

        $markets = [];
        foreach (($match['markets'] ?? []) as $marketRunners) {
            if (! is_array($marketRunners)) {
                continue;
            }

            foreach ($marketRunners as $runner) {
                $marketType = $runner['marketType'] ?? null;
                if (! isset($marketLabels[$marketType])) {
                    continue;
                }

                $currentOdds = (float) ($runner['currentBackOdds'] ?? $runner['backOdds'] ?? 0);
                if ($currentOdds <= 0) {
                    continue;
                }

                $initialOdds = (float) ($runner['initialBackOdds']
                    ?? $runner['prematch_drop_baseline_odds']
                    ?? $runner['previousBackOdds']
                    ?? $currentOdds);
                $history = array_values(array_filter(
                    $runner['backOddsHistory'] ?? [],
                    static fn (array $point): bool => (float) ($point['odds'] ?? 0) > 0
                ));

                if (
                    $history === []
                    || (float) ($history[0]['odds'] ?? 0) !== $initialOdds
                ) {
                    array_unshift($history, [
                        'timestamp' => (int) ($runner['initialBackOddsTimestamp'] ?? $match['timestamp']),
                        'odds' => $initialOdds,
                    ]);
                }

                if ((float) ($history[array_key_last($history)]['odds'] ?? 0) !== $currentOdds) {
                    $history[] = ['timestamp' => time(), 'odds' => $currentOdds];
                }

                $markets[$marketType] ??= [
                    'type' => $marketType,
                    'label' => $marketLabels[$marketType],
                    'runners' => [],
                ];
                $markets[$marketType]['runners'][] = [
                    'name' => $runner['runner'] ?? 'Selection',
                    'initial_back_odds' => $initialOdds,
                    'current_back_odds' => $currentOdds,
                    'direction' => $currentOdds <=> $initialOdds,
                    'change_percent' => $initialOdds > 0
                        ? (($currentOdds - $initialOdds) / $initialOdds) * 100
                        : 0,
                    'history' => count($history) > 12
                        ? array_merge([$history[0]], array_slice($history, -11))
                        : $history,
                ];
            }
        }

        $orderedMarkets = [];
        foreach (array_keys($marketLabels) as $marketType) {
            if (isset($markets[$marketType])) {
                $orderedMarkets[] = $markets[$marketType];
            }
        }

        return [
            'id' => $matchId,
            'team1' => $match['team1'],
            'team2' => $match['team2'],
            'tournament' => $match['tournament'] ?? '',
            'status' => $match['result_status'],
            'score' => $this->getScore($match),
            'minutes' => $this->calculateMinutes(
                $match['minutes_status'] ?? $match['result_status'],
                (int) $match['timestamp'],
                $match['result_status'],
                $match['added_time'] ?? []
            ),
            'timestamp' => (int) $match['timestamp'],
            'markets' => $orderedMarkets,
        ];
    }

    public function getMarketActivites()
    {
        $getData = getInRedis('Football:matches') ?? [];
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
                $favoriteQuery = DB::table('favorites')->where('sport', 'football');
                $userId = request()->user()?->getAuthIdentifier();

                if ($userId === null) {
                    $favoriteQuery->where('session_id', request()->session()->getId());
                } else {
                    $favoriteQuery->where('user_id', $userId);
                }

                $favoriteKeys = array_fill_keys($favoriteQuery->pluck('event_key')->all(), true);
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
                        $match['added_time'] ?? []
                    );

                    return is_numeric($minute) ? (int) $minute >= 70 : str_starts_with((string) $minute, '90+');
                }));
                break;

            case 'fav_losing':
            case 'fav_not_winning':
                $getData = array_values(array_filter($getData, function (array $match) use ($type): bool {
                    $favoriteOdds = (float) ($match['prematch_favorite_odds'] ?? 0);
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
                            $match['added_time'] ?? []
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

                    $favoriteSide = $match['prematch_favorite_side'] ?? null;
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
                        'initialBack' => (float) ($market['initialBackOdds'] ?? $market['previousBackOdds'] ?? $backOdds),
                    ];

                    $runnerIndex = array_key_last($runners);
                    if ($backOdds > 0 && ($lowestBackOdds === null || $backOdds < $lowestBackOdds)) {
                        $lowestBackOdds = $backOdds;
                        $favoriteIndex = $runnerIndex;
                        $favoritePreviousBackOdds = (float) ($market['initialBackOdds'] ?? $market['previousBackOdds'] ?? $backOdds);
                        $favoriteOddsDirection = $backOdds === $favoritePreviousBackOdds
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
                        $data['added_time'] ?? []
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
