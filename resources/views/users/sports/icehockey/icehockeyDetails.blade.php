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
        {{ $group['tournament_name'] }}
    </h2>
    @foreach ($group['matches'] as $event)
        @php
            $choices = ['1' => $event['team1'], '2' => $event['team2']];
            if ($event['prediction_has_draw']) { $choices['X'] = 'Tie (60 min)'; }
            $prediction = ['match_id' => (string) $event['sportsApiPro_match_id'], 'choices' => $choices, 'counts' => $event['prediction_counts'], 'vote' => $event['prediction_vote'], 'locked' => $event['status'] !== 'Prematch'];
            $highlight = (int) ($event['incident_highlight_until'] ?? 0) > time();
        @endphp
        <div data-icehockey-match-url="{{ route('icehockey.matches.show', $event['sportsApiPro_match_id']) }}" data-match-id="{{ $event['sportsApiPro_match_id'] }}" data-icehockey-stats="{{ json_encode(['team1' => $event['team1'], 'team2' => $event['team2'], 'home' => $event['home_score_data'] ?? [], 'away' => $event['away_score_data'] ?? [], 'statistics' => $event['live_stats'] ?? []]) }}" data-icehockey-prediction="{{ json_encode($prediction) }}" @if ($highlight) data-incident-highlight-until="{{ $event['incident_highlight_until'] }}" @endif
            @class(['event-row my-2 grid min-w-[1060px] grid-cols-[28px_100px_minmax(460px,1fr)_82px_310px] items-center gap-3 rounded-xl border border-[#e6eff8] bg-[#fafdff] px-3 py-2.5 text-[#12324a] hover:bg-[#f2f8ff] dark:border-[#2a3f50] dark:bg-[#1a2a38] dark:text-[#e8edf2]', '!bg-[#ffe4ec] dark:!bg-[#4a2633]' => $highlight])>
            <button type="button" data-action="favorite" data-requires-login="{{ $authenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" data-sport="icehockey" data-event-key="{{ $event['unique_key'] }}" data-event-id="{{ $event['sportsApiPro_match_id'] }}" data-team1="{{ $event['team1'] }}" data-team2="{{ $event['team2'] }}" data-start-timestamp="{{ $event['timestamp'] }}" aria-pressed="{{ $event['is_favorite'] ? 'true' : 'false' }}" @class(['fav-star cursor-pointer border-0 bg-transparent text-lg text-[#b8ccdf]', '!text-[#e6b422]' => $event['is_favorite']])><i class="fas fa-star"></i></button>
            <span class="text-center text-xs font-semibold {{ $event['status'] === 'Live' ? 'text-[#dc2e08]' : 'text-[#5a7d99]' }}">{{ $event['minutes'] }}@if ($highlight)<strong class="block text-red-600" data-incident-indicator>{{ $event['last_incident']['text'] ?? 'Update' }}</strong>@endif</span>
            <button type="button" data-action="open-match" class="grid min-w-0 grid-cols-[minmax(0,1fr)_210px_minmax(0,1fr)] items-center gap-2 text-sm">
                <span class="flex min-w-0 items-center justify-end gap-1.5 text-right font-bold">@if ($event['home_team_logo'] ?? null)<img src="{{ $event['home_team_logo'] }}" alt="" class="size-5 shrink-0 object-contain" onerror="this.remove()">@endif<span class="break-words">{{ $event['team1'] }}</span></span>
                <strong class="text-center text-xs text-[#dc4c4c]">{{ $event['score'] }}</strong>
                <span class="flex min-w-0 items-center gap-1.5 text-left"><span class="break-words">{{ $event['team2'] }}</span>@if ($event['away_team_logo'] ?? null)<img src="{{ $event['away_team_logo'] }}" alt="" class="size-5 shrink-0 object-contain" onerror="this.remove()">@endif</span>
            </button>
            <div class="flex items-center justify-center gap-4">
                <button type="button" class="cursor-pointer text-[#46799a] disabled:cursor-not-allowed disabled:opacity-30"
                    @if ($checkAuthAndSubscription['display_odds'])
                        data-action="stats" title="{{ __('Live match statistics') }}"
                    @else
                        disabled title="{{ __('Statistics (requires subscription)') }}"
                    @endif
                    ><i class="fas fa-chart-bar"></i></button>
                <button type="button" class="cursor-pointer text-[#46799a] disabled:cursor-not-allowed disabled:opacity-30"
                    @if ($checkAuthAndSubscription['display_odds'])
                        data-action="prediction" data-requires-login="{{ $authenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" title="{{ __('Predictions') }}"
                    @else
                        disabled title="{{ __('Prediction (requires subscription)') }}"
                    @endif
                    @disabled($event['status'] !== 'Prematch')><i class="fas fa-brain"></i></button>
            </div>
            <div class="flex min-w-0 gap-2 overflow-x-auto [scrollbar-width:thin]">
                @foreach ($event['markets'] !== [] ? $event['markets'] : [[], [], []] as $runner)
                    <span @if ($checkAuthAndSubscription['display_odds'] && (float) ($runner['currentBackOdds'] ?? 0) > 0) tabindex="0" data-icehockey-odds-tooltip="{{ number_format($runner['initialBackOdds'] ?? $runner['previousBackOdds'] ?? $runner['currentBackOdds'], 2) }} {{ ($runner['odds_direction'] ?? '') === 'up' ? '<<' : '>>' }} {{ number_format($runner['currentBackOdds'], 2) }}" aria-label="{{ $runner['runner'] ?? 'Odds' }}" @else title="{{ $runner['runner'] ?? 'Unavailable' }}" @endif class="inline-flex min-w-[96px] shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#d4e2f3] bg-white px-2 py-1 text-sm font-bold dark:border-[#3a5568] dark:bg-[#1f3444]">
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
    @if (! $append)<p class="py-10 text-center text-sm text-[#5a7d99]" data-icehockey-empty>{{ __('No data available.') }}</p>@endif
@endforelse
@if (! $append)
</div>
<div id="icehockeyModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="icehockeyModalTitle"><div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 text-[#12324a] shadow-xl dark:bg-[#1a2a38] dark:text-[#e8edf2]" data-icehockey-modal-panel></div></div>
@endif
