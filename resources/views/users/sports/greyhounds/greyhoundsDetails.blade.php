@php $append = $append ?? false; $checkAuthAndSubscription = checkAuthAndSubscription(); @endphp
@if (! $append)<div class="fixtures-container overflow-x-auto [scrollbar-width:thin]">@endif
@forelse ($data as $group)
    <h2 data-league-header="{{ $group['country'] }}" class="mb-2 mt-5 border-b border-[#e3ecf5] pb-2 text-xs font-bold uppercase tracking-wide text-[#1e4763] dark:border-[#2a3f50] dark:text-[#b0c8dd]">{{ $group['country'] }}</h2>
    @foreach ($group['matches'] as $race)
        @php
            $displayOdds = $race['details_available'] && $checkAuthAndSubscription['display_odds'];
            $choices = array_column($race['win_runners'], 'runner', 'runnerId');
            $prediction = ['match_id' => $race['id'], 'poll' => 'winner', 'choices' => $choices, 'counts' => $race['prediction_counts'], 'vote' => $race['prediction_vote'], 'locked' => ! $race['details_available'] || $race['status'] === 'Finished' || $race['timestamp'] <= time()];
            $viewRunners = array_slice($race['win_runners'], 0, 3);
            $payload = ['title' => $race['team1'].' · '.$race['team2'], 'team1' => $race['team1'], 'team2' => $race['team2'], 'available' => $race['details_available'], 'status' => $race['status'], 'minutes' => $race['minutes'], 'runners' => $displayOdds ? array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['marketId','handicap','runner','runnerId','trap','initialBackOdds','initialBackOddsTimestamp','opening_odds_source','backOddsHistory','odds_history_incomplete','currentBackOdds','backSize','layOdds','laySize'])), $race['win_runners']) : [], 'indicators' => $displayOdds ? $race['indicators'] : [], 'locked' => ! $displayOdds];
        @endphp
        <div data-details-unlock-at="{{ $race['timestamp'] - 300 }}" data-match-id="{{ $race['id'] }}" data-greyhounds-match-url="{{ route('greyhounds.matches.show', $race['id']) }}" data-greyhounds-market="{{ json_encode($payload) }}" data-greyhounds-prediction="{{ json_encode($prediction) }}" class="event-row my-2 grid min-w-[880px] grid-cols-[28px_90px_minmax(280px,1fr)_100px_320px] items-center gap-3 rounded-xl border border-[#e6eff8] bg-[#fafdff] px-3 py-3 text-[#12324a] hover:bg-[#f2f8ff] dark:border-[#2a3f50] dark:bg-[#1a2a38] dark:text-[#e8edf2]">
            <button type="button" data-action="favorite" data-requires-login="{{ auth('customers')->check() ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" data-sport="greyhounds" data-event-key="{{ $race['unique_key'] }}" data-event-id="{{ $race['id'] }}" data-team1="{{ $race['team1'] }}" data-team2="{{ $race['team2'] }}" data-start-timestamp="{{ $race['timestamp'] }}" aria-pressed="{{ $race['is_favorite'] ? 'true' : 'false' }}" @class(['fav-star cursor-pointer text-lg text-[#b8ccdf]', '!text-[#e6b422]' => $race['is_favorite']])><i class="fas fa-star"></i></button>
            <span class="whitespace-nowrap text-center text-xs font-semibold text-[#5a7d99]">{{ $race['minutes'] }}</span>
            <button type="button" data-action="open-match" class="text-center text-sm font-bold">{{ $race['team1'] }} · {{ $race['team2'] }} @if ($race['details_available'])<span class="text-amber-500" title="{{ $race['favorite_runner']['runner'] ?? '' }}">⭐</span>@endif</button>
            <div class="flex justify-center gap-3">
                <button type="button" class="cursor-pointer text-[#46799a] disabled:cursor-not-allowed disabled:opacity-30"
                    @if ($checkAuthAndSubscription['display_odds'])
                        data-action="market" title="{{ __('Runners and liquidity') }}"
                    @else
                        disabled title="{{ __('Statistics (requires subscription)') }}"
                    @endif
                    ><i class="fas fa-chart-bar"></i></button>
                <button type="button" data-action="chart" title="{{ __('Chart') }}" class="text-[#46799a]"><i class="fas fa-chart-line"></i></button>
                <button type="button" class="cursor-pointer text-[#46799a] disabled:cursor-not-allowed disabled:opacity-30"
                    @if ($checkAuthAndSubscription['display_odds'])
                        data-action="prediction" data-requires-login="{{ auth('customers')->check() ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" title="{{ __('Predictions') }}"
                    @else
                        disabled title="{{ __('Prediction (requires subscription)') }}"
                    @endif
                    @disabled($race['status'] !== 'Prematch' || $prediction['locked'])><i class="fas fa-brain"></i></button>
            </div>
            <div class="flex gap-2">
                @if (! $race['details_available'])<span class="w-full text-center text-xs text-[#5a7d99]">{{ __('Details and odds open 5 minutes before start') }}</span>@else
                @foreach (array_pad($viewRunners, 3, []) as $runner)
                    <span @if ($displayOdds && ($runner['currentBackOdds'] ?? 0) > 0) tabindex="0" data-greyhounds-odds-tooltip="{{ ($runner['initialBackOdds'] ?? 0) > 1 ? number_format($runner['initialBackOdds'], 2) : __('Opening pending') }} → {{ number_format($runner['currentBackOdds'], 2) }}" @endif title="{{ $runner['runner'] ?? __('Unavailable') }}" class="inline-flex min-w-[96px] items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#d4e2f3] bg-white px-2 py-1 text-sm font-bold dark:border-[#3a5568] dark:bg-[#1f3444]">@if ($displayOdds){{ ($runner['currentBackOdds'] ?? 0) > 0 ? number_format($runner['currentBackOdds'], 2) : '—' }}@if (($runner['odds_direction'] ?? '') === 'up')<span class="text-emerald-600">↑</span>@elseif (($runner['odds_direction'] ?? '') === 'down')<span class="text-red-500">↓</span>@endif @else @include('lock_icon') @endif</span>
                @endforeach
                @endif
            </div>
        </div>
    @endforeach
@empty
    @if (! $append)<p data-greyhounds-empty class="py-10 text-center text-sm text-[#5a7d99]">{{ __('No data available.') }}</p>@endif
@endforelse
@if (! $append)
</div>
<div id="greyhoundsModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="greyhoundsModalTitle"><div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 text-[#12324a] shadow-xl dark:bg-[#1a2a38] dark:text-[#e8edf2]" data-greyhounds-modal-panel></div></div>
@endif
