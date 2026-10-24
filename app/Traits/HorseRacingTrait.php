<?php

namespace App\Traits;

use App\Models\Favorite;
use App\Models\HorseRacingMatch;
use App\Models\PredictionVote;
use App\Services\MatchBackup;
use Carbon\Carbon;

trait HorseRacingTrait
{
    protected bool $horseracingHasMore = false;

    protected ?int $horseracingNextPage = null;

    public function getHorseRacingMatches(bool $grouped = true): array
    {
        if (in_array(request()->input('type'), ['ht00', '00_70', 'fav_losing', 'fav_not_winning'], true)) {
            return [];
        }

        if (in_array(request()->input('type'), ['ht00', '00_70', 'fav_losing', 'prematch_drops', 'market_shock', 'fav_not_winning'], true)) {
            $checkAuthAndSubscription = checkAuthAndSubscription();
            abort_unless($checkAuthAndSubscription['display_odds'], 403, __('An active 7-day free trial or paid subscription is required to use smart filters.'));
        }

        $customerId = request()->user('customers')?->getAuthIdentifier();
        $favorites = $customerId === null ? [] : array_fill_keys(Favorite::query()->where('sport', 'horseracing')->where('customer_id', $customerId)->pluck('event_key')->all(), true);
        $today = Carbon::now(getCurrentUserTimezone())->toDateString();
        $type = request()->input('type', 'all');
        $matches = array_map(
            fn (array $race): array => prepareMatchOddsMonitoring($race),
            array_filter(getVisibleSportsMatches('HorseRacing:matches') ?? [], static fn (array $race): bool => ($race['result_status'] ?? '') !== 'Finished')
        );
        $matches = array_values(array_filter($matches, static function (array $race) use ($today, $type, $favorites): bool {
            if (($race['result_status'] ?? '') === 'Finished' || ($race['data_sync_complete'] ?? false) !== true || Carbon::createFromTimestamp((int) $race['timestamp'], getCurrentUserTimezone())->toDateString() !== $today) {
                return false;
            }

            return match ($type) {
                'live' => false,
                'upcoming' => $race['result_status'] === 'Prematch',
                'finished' => $race['result_status'] === 'Finished',
                'favorites' => isset($favorites[$race['unique_key']]),
                'prematch_drops', 'market_shock' => $race['result_status'] === 'Prematch' && array_any($race['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => $type === 'prematch_drops' ? ($runner['prematch_drop_triggered'] ?? false) : (int) ($runner['market_shock_detected_at'] ?? 0) >= time() - 1200)),
                default => true,
            };
        }));
        usort($matches, static function (array $first, array $second) use ($type): int {
            if ($type === 'by_time') {
                return $first['timestamp'] <=> $second['timestamp'];
            }
            $bucket = static fn (array $race): int => $race['result_status'] === 'Finished' ? 4 : ($race['timestamp'] - time() <= 300 ? 1 : ($race['timestamp'] - time() <= 600 ? 2 : 3));
            $strength = ['strong_back' => 0, 'strong_lay' => 0, 'back' => 1, 'lay' => 1, 'neutral' => 2];
            $left = $first['market_indicators'][$first['betfair_match_id'].'_WIN'] ?? [];
            $right = $second['market_indicators'][$second['betfair_match_id'].'_WIN'] ?? [];

            return ($bucket($first) <=> $bucket($second))
                ?: (($strength[$left['momentum'] ?? 'neutral']) <=> ($strength[$right['momentum'] ?? 'neutral']))
                ?: (($right['liquidity'] ?? 0) <=> ($left['liquidity'] ?? 0))
                ?: ($first['timestamp'] <=> $second['timestamp']);
        });
        $page = max(1, request()->integer('page', 1));
        $perPage = min(1000, max(1, request()->integer('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->horseracingHasMore = $offset + $perPage < count($matches);
        $this->horseracingNextPage = $this->horseracingHasMore ? $page + 1 : null;
        $matches = array_slice($matches, $offset, $perPage);
        $ids = array_column($matches, 'betfair_match_id');
        $votes = $ids === [] ? collect() : PredictionVote::query()->where('sport', 'horseracing')->whereIn('match_id', $ids)->where('poll', 'winner')->select('match_id', 'value')->selectRaw('COUNT(*) as total')->groupBy('match_id', 'value')->get()->groupBy('match_id');
        $customerVotes = $customerId === null || $ids === [] ? [] : PredictionVote::query()->where('sport', 'horseracing')->where('customer_id', $customerId)->whereIn('match_id', $ids)->where('poll', 'winner')->pluck('value', 'match_id')->all();
        $groups = [];
        $preparedMatches = [];
        foreach ($matches as $match) {
            $match['id'] = (string) $match['betfair_match_id'];
            $match['status'] = $match['result_status'];
            $match['minutes'] = $this->calculateMinutes($match);
            $match['is_favorite'] = isset($favorites[$match['unique_key']]);
            $runners = array_values($match['markets'][$match['id'].'_WIN'] ?? []);
            usort($runners, static fn (array $a, array $b): int => ($a['sortPriority'] ?? 0) <=> ($b['sortPriority'] ?? 0));
            $match['win_runners'] = $runners;
            $match['indicators'] = $match['market_indicators'][$match['id'].'_WIN'] ?? [];
            $match['favorite_runner'] = array_find($runners, static fn (array $runner): bool => (string) $runner['runnerId'] === (string) ($match['indicators']['favorite_id'] ?? ''));
            $match['prediction_counts'] = [];
            foreach ($votes->get($match['id'], collect()) as $vote) {
                $match['prediction_counts'][(string) $vote->value] = (int) $vote->total;
            }
            $sessionKey = "horseracing_predictions.{$customerId}.{$match['id']}.winner";
            $match['prediction_vote'] = $customerId === null ? null : request()->session()->get($sessionKey, $customerVotes[$match['id']] ?? null);
            if ($match['prediction_vote'] !== null) {
                request()->session()->put($sessionKey, $match['prediction_vote']);
            }
            $key = leagueHeadingKey($match, $match['tournament']).'|'.$match['status'];
            if ($type === 'by_time') {
                $key .= '|'.$match['timestamp'];
            }
            $groups[$key] ??= [...leagueHeadingMetadata($match), 'tournament_name' => $match['tournament'], 'status' => $match['status'], 'matches' => []];
            $groups[$key]['matches'][] = $match;
            $preparedMatches[] = $match;
        }

        return $grouped ? array_values(rankCompetitionGroups($groups)) : $preparedMatches;
    }

    public function calculateMinutes(array $match): string
    {
        return $match['result_status'] === 'Finished' ? 'Finished' : Carbon::createFromTimestamp((int) $match['timestamp'], getCurrentUserTimezone())->format('H:i');
    }

    public function getMarketActivites(): array
    {
        return $this->getHorseRacingMatches(false);
    }

    public function getHorseRacingMatchDetail(string $matchId): ?array
    {
        // $match = array_find(getVisibleSportsMatches('HorseRacing:matches') ?? [], static fn (array $race): bool => (string) $race['betfair_match_id'] === $matchId);
        $match = collect(getVisibleSportsMatches('HorseRacing:matches') ?? [])->first(static fn (array $race): bool => (string) ($race['betfair_match_id'] ?? '') === $matchId);
        if ($match === null) {
            $stored = HorseRacingMatch::query()->with('markets')->where('betfair_match_id', $matchId)->first();
            if ($stored === null) {
                return null;
            }
            $match = $stored->payload;
            $match['markets'] = [];
            foreach ($stored->markets as $runner) {
                $match['markets'][$runner->market_id.'_'.$runner->market_type][] = $runner->payload;
            }
        }
        if (MatchBackup::isHidden($match, 'horseracing')) {
            return null;
        }
        // $match = prepareMatchOddsMonitoring($match);
        $match['id'] = $matchId;
        $match['status'] = $match['result_status'];
        $match['minutes'] = $this->calculateMinutes($match);

        return $match;
    }
}
