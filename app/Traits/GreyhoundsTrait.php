<?php

namespace App\Traits;

use App\Models\Favorite;
use App\Models\GreyhoundsMatch;
use App\Models\PredictionVote;
use Carbon\Carbon;

trait GreyhoundsTrait
{
    protected bool $greyhoundsHasMore = false;

    protected ?int $greyhoundsNextPage = null;

    public function getGreyhoundsMatches(bool $grouped = true): array
    {
        if (in_array(request()->input('type'), ['ht00', '00_70', 'fav_losing', 'prematch_drops', 'market_shock', 'fav_not_winning'], true)) {
            $checkAuthAndSubscription = checkAuthAndSubscription();
            abort_unless($checkAuthAndSubscription['display_odds'], 403, __('An active 7-day free trial or paid subscription is required to use smart filters.'));
        }

        $customerId = request()->user('customers')?->getAuthIdentifier();
        $favorites = $customerId === null ? [] : array_fill_keys(Favorite::query()->where('sport', 'greyhounds')->where('customer_id', $customerId)->pluck('event_key')->all(), true);
        $today = Carbon::now(getCurrentUserTimezone())->toDateString();
        $type = request()->input('type', 'all');
        $matches = array_map(function (array $race): array {
            $race['country'] = $this->getGreyhoundsCountry($race);
            $race['details_available'] = now()->timestamp >= (int) $race['timestamp'] - 300;

            return $race;
        }, getInRedis('Greyhounds:matches') ?? []);
        $matches = array_values(array_filter($matches, static function (array $race) use ($today, $type, $favorites): bool {
            if (empty($race['betfair_match_id']) || Carbon::createFromTimestamp((int) $race['timestamp'], getCurrentUserTimezone())->toDateString() !== $today) {
                return false;
            }

            return match ($type) {
                'live' => false,
                'upcoming' => $race['result_status'] === 'Prematch',
                'finished' => $race['result_status'] === 'Finished',
                'favorites' => isset($favorites[$race['unique_key']]),
                'prematch_drops', 'market_shock' => $race['details_available'] && $race['result_status'] === 'Prematch' && array_any($race['markets'] ?? [], static fn (array $runners): bool => array_any($runners, static fn (array $runner): bool => $type === 'prematch_drops' ? ($runner['prematch_drop_triggered'] ?? false) : (int) ($runner['market_shock_detected_at'] ?? 0) >= time() - 1200)),
                default => true,
            };
        }));
        usort($matches, static fn (array $first, array $second): int => ($type === 'by_time' ? 0 : strcasecmp($first['country'], $second['country']))
            ?: ($first['timestamp'] <=> $second['timestamp'])
            ?: strcmp((string) $first['betfair_match_id'], (string) $second['betfair_match_id']));
        $page = max(1, request()->integer('page', 1));
        $perPage = min(1000, max(1, request()->integer('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $this->greyhoundsHasMore = $offset + $perPage < count($matches);
        $this->greyhoundsNextPage = $this->greyhoundsHasMore ? $page + 1 : null;
        $matches = array_slice($matches, $offset, $perPage);
        $ids = array_column($matches, 'betfair_match_id');
        $votes = $ids === [] ? collect() : PredictionVote::query()->where('sport', 'greyhounds')->whereIn('match_id', $ids)->where('poll', 'winner')->select('match_id', 'value')->selectRaw('COUNT(*) as total')->groupBy('match_id', 'value')->get()->groupBy('match_id');
        $customerVotes = $customerId === null || $ids === [] ? [] : PredictionVote::query()->where('sport', 'greyhounds')->where('customer_id', $customerId)->whereIn('match_id', $ids)->where('poll', 'winner')->pluck('value', 'match_id')->all();
        $groups = [];
        $preparedMatches = [];
        foreach ($matches as $match) {
            $match['id'] = (string) $match['betfair_match_id'];
            $match['status'] = $match['result_status'];
            $match['minutes'] = $this->calculateMinutes($match);
            $match['is_favorite'] = isset($favorites[$match['unique_key']]);
            $runners = $match['details_available'] ? array_values($match['markets'][$match['id'].'_WIN'] ?? []) : [];
            usort($runners, static fn (array $a, array $b): int => ($a['sortPriority'] ?? 0) <=> ($b['sortPriority'] ?? 0));
            $match['win_runners'] = $runners;
            $match['indicators'] = $match['details_available'] ? ($match['market_indicators'][$match['id'].'_WIN'] ?? []) : [];
            $match['favorite_runner'] = array_find($runners, static fn (array $runner): bool => (string) $runner['runnerId'] === (string) ($match['indicators']['favorite_id'] ?? ''));
            $match['prediction_counts'] = [];
            foreach ($votes->get($match['id'], collect()) as $vote) {
                $match['prediction_counts'][(string) $vote->value] = (int) $vote->total;
            }
            $sessionKey = "greyhounds_predictions.{$customerId}.{$match['id']}.winner";
            $match['prediction_vote'] = $customerId === null ? null : request()->session()->get($sessionKey, $customerVotes[$match['id']] ?? null);
            if ($match['prediction_vote'] !== null) {
                request()->session()->put($sessionKey, $match['prediction_vote']);
            }
            if (! $match['details_available']) {
                $match['markets'] = [];
                $match['market_indicators'] = [];
                $match['prediction_counts'] = [];
                $match['prediction_vote'] = null;
            }
            $key = $match['country'];
            if ($type === 'by_time') {
                $key .= '|'.$match['timestamp'];
            }
            $groups[$key] ??= ['country' => $match['country'], 'matches' => []];
            $groups[$key]['matches'][] = $match;
            $preparedMatches[] = $match;
        }

        return $grouped ? array_values($groups) : $preparedMatches;
    }

    public function calculateMinutes(array $match): string
    {
        return $match['result_status'] === 'Finished' ? 'Finished' : Carbon::createFromTimestamp((int) $match['timestamp'], getCurrentUserTimezone())->format('H:i');
    }

    public function getMarketActivites(): array
    {
        return $this->getGreyhoundsMatches(false);
    }

    public function getGreyhoundsMatchDetail(string $matchId): ?array
    {
        $match = array_find(getInRedis('Greyhounds:matches') ?? [], static fn (array $race): bool => (string) $race['betfair_match_id'] === $matchId);
        if ($match === null) {
            $stored = GreyhoundsMatch::query()->with('markets')->where('betfair_match_id', $matchId)->first();
            if ($stored === null) {
                return null;
            }
            $match = $stored->payload;
            $match['markets'] = [];
            foreach ($stored->markets as $runner) {
                $match['markets'][$runner->market_id.'_'.$runner->market_type][] = $runner->payload;
            }
        }
        $match['details_available'] = now()->timestamp >= (int) $match['timestamp'] - 300;
        $match['country'] = $this->getGreyhoundsCountry($match);
        $match['id'] = $matchId;
        $match['status'] = $match['result_status'];
        $match['minutes'] = $this->calculateMinutes($match);

        return $match;
    }

    private function getGreyhoundsCountry(array $race): string
    {
        if (is_string($race['country'] ?? null) && $race['country'] !== '') {
            return $race['country'];
        }
        $venue = trim((string) ($race['team1'] ?? ''));
        if (preg_match('/\((AUS|AU|NZ|USA|US|IRE|IRL|GB|UK)\)\s*$/i', $venue, $country)) {
            return match (strtoupper($country[1])) {
                'AUS', 'AU' => 'Australia', 'NZ' => 'New Zealand', 'USA', 'US' => 'United States', 'IRE', 'IRL' => 'Ireland', default => 'United Kingdom',
            };
        }
        $countries = [
            'Central Park' => 'United Kingdom', 'Dunstall Park' => 'United Kingdom', 'Harlow' => 'United Kingdom', 'Hove' => 'United Kingdom',
            'Kinsley' => 'United Kingdom', 'Monmore' => 'United Kingdom', 'Monmore Green' => 'United Kingdom', 'Newcastle' => 'United Kingdom',
            'Nottingham' => 'United Kingdom', 'Romford' => 'United Kingdom', 'Sheffield' => 'United Kingdom', 'Towcester' => 'United Kingdom',
            'Valley' => 'United Kingdom', 'Yarmouth' => 'United Kingdom', 'Oxford' => 'United Kingdom', 'Sunderland' => 'United Kingdom',
            'Doncaster' => 'United Kingdom', 'Star Pelaw' => 'United Kingdom', 'Swindon' => 'United Kingdom', 'Suffolk Downs' => 'United Kingdom',
            'Clonmel' => 'Ireland', 'Cork' => 'Ireland', 'Curraheen Park' => 'Ireland', 'Dundalk' => 'Ireland', 'Galway' => 'Ireland',
            'Kilkenny' => 'Ireland', 'Lifford' => 'Ireland', 'Limerick' => 'Ireland', 'Mullingar' => 'Ireland', 'Newbridge' => 'Ireland',
            'Shelbourne Park' => 'Ireland', 'Thurles' => 'Ireland', 'Tralee' => 'Ireland', 'Waterford' => 'Ireland', 'Youghal' => 'Ireland',
        ];

        return $countries[$venue] ?? 'Country unavailable';
    }
}
