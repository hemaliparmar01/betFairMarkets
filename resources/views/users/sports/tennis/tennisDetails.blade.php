@php
    $append = $append ?? false;
    $checkAuthAndSubscription = checkAuthAndSubscription();
    $authenticated = auth('customers')->check();
@endphp
@if (! $append)
<div class="fixtures-container overflow-x-auto [scrollbar-width:thin]">
@endif
@forelse ($data as $group)
    <h2 data-league-header="{{ $group['tournament_name'].'|'.$group['status'] }}" class="mb-2 mt-5 flex items-center gap-2 border-b border-[#e3ecf5] pb-2 text-xs font-bold uppercase tracking-wide text-[#1e4763] dark:border-[#2a3f50] dark:text-[#b0c8dd]">
        @if ($group['tournament_logo'] ?? null)<img src="{{ $group['tournament_logo'] }}" alt="" class="size-5 object-contain" onerror="this.remove()">@endif
        {{ $group['tournament_name'] }} · {{ $group['status'] }}
    </h2>
    @foreach ($group['matches'] as $event)
        @php
            $choices = ['1' => $event['team1'], '2' => $event['team2']];
            if ($event['prediction_has_draw']) { $choices['X'] = 'Tie'; }
            $prediction = ['match_id' => (string) $event['sportsApiPro_match_id'], 'choices' => $choices, 'counts' => $event['prediction_counts'], 'vote' => $event['prediction_vote'], 'locked' => $event['status'] !== 'Prematch'];
            $highlight = (int) ($event['incident_highlight_until'] ?? 0) > time();
            $setScores = [];
            for ($set = 1; $set <= 5; $set++) {
                $period = 'period'.$set;
                if (isset($event['home_score_data'][$period], $event['away_score_data'][$period])) {
                    $setScores[] = $event['home_score_data'][$period].'-'.$event['away_score_data'][$period];
                }
            }
            $chart = ['title' => request()->input('market_name', 'MATCH_ODDS') === 'NUMBER_OF_SETS' ? 'Total Sets' : 'Match Winner', 'runners' => $checkAuthAndSubscription['display_odds'] ? array_map(static fn (array $runner): array => array_intersect_key($runner, array_flip(['runner', 'initialBackOdds', 'currentBackOdds', 'backOddsHistory'])), $event['markets']) : [], 'locked' => ! $checkAuthAndSubscription['display_odds']];
        @endphp
        <div data-tennis-match-url="{{ route('tennis.matches.show', $event['sportsApiPro_match_id']) }}" data-match-id="{{ $event['sportsApiPro_match_id'] }}" data-tennis-stats="{{ json_encode(['team1' => $event['team1'], 'team2' => $event['team2'], 'home' => $event['home_score_data'] ?? [], 'away' => $event['away_score_data'] ?? [], 'statistics' => $event['live_stats'] ?? [], 'periods' => $event['period_labels'] ?? [], 'serving' => $event['serving_side'] ?? null, 'surface' => $event['ground_type'] ?? null, 'current_set' => $event['current_set'] ?? null]) }}" data-tennis-chart="{{ json_encode($chart) }}" data-tennis-prediction="{{ json_encode($prediction) }}"
            class="event-row my-2 grid min-w-[960px] grid-cols-[28px_94px_minmax(420px,1fr)_120px_220px] items-center gap-3 rounded-xl border border-[#e6eff8] bg-[#fafdff] px-3 py-3 text-[#12324a] hover:bg-[#f2f8ff] dark:border-[#2a3f50] dark:bg-[#1a2a38] dark:text-[#e8edf2]">
            <button type="button" data-action="favorite" data-requires-login="{{ $authenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" data-sport="tennis" data-event-key="{{ $event['unique_key'] }}" data-event-id="{{ $event['sportsApiPro_match_id'] }}" data-team1="{{ $event['team1'] }}" data-team2="{{ $event['team2'] }}" data-start-timestamp="{{ $event['timestamp'] }}" aria-pressed="{{ $event['is_favorite'] ? 'true' : 'false' }}" @class(['fav-star cursor-pointer border-0 bg-transparent text-lg text-[#b8ccdf]', '!text-[#e6b422]' => $event['is_favorite']])><i class="fas fa-star"></i></button>
            <span class="whitespace-nowrap text-center text-xs font-semibold {{ $event['status'] === 'Live' ? 'text-[#dc2e08]' : 'text-[#5a7d99]' }}">{{ $event['minutes'] }}</span>
            <button type="button" data-action="open-match" class="grid min-w-0 grid-cols-[minmax(0,1fr)_80px_minmax(0,1fr)] items-center gap-x-2 gap-y-1 text-sm">
                <span class="flex min-w-0 items-center justify-end gap-1.5 text-right font-bold">@if ($event['home_team_logo'] ?? null)<img src="{{ $event['home_team_logo'] }}" alt="" class="size-5 shrink-0 object-contain" onerror="this.remove()">@endif<span class="break-words">{{ $event['team1'] }}</span>@if ($event['status'] === 'Live' && ($event['serving_side'] ?? null) === 'home')<span class="shrink-0 text-[10px] text-amber-500" title="{{ __('Serving') }}">●</span>@endif</span>
                <span class="text-center"><strong class="whitespace-nowrap {{ $event['status'] === 'Live' ? 'text-[#dc4c4c]' : 'text-[#12324a] dark:text-[#e8edf2]' }}">{{ $event['score'] }}</strong>@if ($event['status'] === 'Live' && isset($event['home_score_data']['point'], $event['away_score_data']['point']))<small class="block whitespace-nowrap text-[10px] font-medium text-[#5a7d99]">{{ $event['home_score_data']['point'] }} : {{ $event['away_score_data']['point'] }}</small>@endif</span>
                <span class="flex min-w-0 items-center gap-1.5 text-left">@if ($event['status'] === 'Live' && ($event['serving_side'] ?? null) === 'away')<span class="shrink-0 text-[10px] text-amber-500" title="{{ __('Serving') }}">●</span>@endif<span class="break-words">{{ $event['team2'] }}</span>@if ($event['away_team_logo'] ?? null)<img src="{{ $event['away_team_logo'] }}" alt="" class="size-5 shrink-0 object-contain" onerror="this.remove()">@endif</span>
                @if (count($setScores) > 1)<small class="col-span-3 text-center text-[10px] font-normal text-[#5a7d99]">{{ implode(' · ', $setScores) }}</small>@endif
            </button>
            <div class="flex items-center justify-center gap-3">
                <button type="button" data-action="stats" title="{{ __('Live Statistics') }}" class="text-red-500"><i class="fas fa-wave-square"></i></button>
                <button type="button" data-action="chart" title="{{ __('Chart') }}" class="text-[#46799a]"><i class="fas fa-chart-line"></i></button>
                <button type="button" data-action="stats" title="{{ __('Statistics') }}" class="text-[#46799a]"><i class="fas fa-chart-bar"></i></button>
                <button type="button" data-action="prediction" data-requires-login="{{ $authenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" title="{{ __('Predictions') }}" class="text-[#46799a]"><i class="fas fa-brain"></i></button>
            </div>
            <div class="flex min-w-0 gap-2 overflow-x-auto [scrollbar-width:thin]">
                @foreach ($event['markets'] !== [] ? $event['markets'] : [[], []] as $runner)
                    <span @if ($checkAuthAndSubscription['display_odds'] && (float) ($runner['currentBackOdds'] ?? 0) > 0) tabindex="0" data-tennis-odds-tooltip="{{ number_format($runner['initialBackOdds'] ?? $runner['previousBackOdds'] ?? $runner['currentBackOdds'], 2) }} {{ ($runner['odds_direction'] ?? '') === 'up' ? '<<' : '>>' }} {{ number_format($runner['currentBackOdds'], 2) }}" aria-label="{{ $runner['runner'] ?? 'Odds' }}" @else title="{{ $runner['runner'] ?? 'Unavailable' }}" @endif class="inline-flex min-w-[96px] shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#d4e2f3] bg-white px-2 py-1 text-sm font-bold dark:border-[#3a5568] dark:bg-[#1f3444]">
                        @if ($checkAuthAndSubscription['display_odds'])
                            {{ (float) ($runner['currentBackOdds'] ?? 0) > 0 ? number_format($runner['currentBackOdds'], 2) : '—' }}
                            @if (($runner['odds_direction'] ?? '') === 'up')<span class="text-emerald-600">↑</span>@elseif (($runner['odds_direction'] ?? '') === 'down')<span class="text-red-500">↓</span>@endif
                        @else
                            @include('lock_icon')
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endforeach
@empty
    @if (! $append)<p class="py-10 text-center text-sm text-[#5a7d99]" data-tennis-empty>{{ __('No data available.') }}</p>@endif
@endforelse
@if (! $append)
</div>
<div id="tennisModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="tennisModalTitle"><div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 text-[#12324a] shadow-xl dark:bg-[#1a2a38] dark:text-[#e8edf2]" data-tennis-modal-panel></div></div>
@endif
