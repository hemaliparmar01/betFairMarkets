@unless ($append ?? false)
<div class="fixtures-container flex min-w-0 flex-col space-y-2 [gap:4px] [margin-top:4px]" id="fixturesContainer">
@endunless
    @if (count($data) === 0 && ! ($append ?? false))
        <div class="no-fixtures-message [padding:10px_0] [text-align:center] [font-size:13px] [color:#1e4763] dark:[color:#8aaccc]">No data available.</div>
    @endif
    @foreach ($data as $league => $matches)
        <div class="league-header break-words [font-size:13px] font-bold uppercase [letter-spacing:0.5px] [color:#1e4763] [padding:10px_0_6px_0] [border-bottom:2px_solid_#e6edf6] [margin-top:8px] [transition:0.3s] bg-white [z-index:2] dark:[color:#8aaccc] dark:[border-bottom-color:#2a3f50] dark:[background:#1a2a38] [&:first-of-type]:[margin-top:0] max-[480px]:text-[11px]" data-league-header="{{ $league }}">
            <span class="inline-flex items-center gap-1.5">
                @if ($matches['tournament_logo'] ?? null)
                    <img src="{{ $matches['tournament_logo'] }}" alt="" width="18" height="18" loading="lazy" decoding="async" onerror="this.remove()" class="h-[18px] w-[18px] shrink-0 object-contain">
                @endif
                <span>{{ $matches['tournament_name'] }} · {{ $matches['status'] }}</span>
            </span>
        </div>
        @foreach ($matches['matches'] as $event)
            @php
                $goalHighlightActive = ($event['goal_highlight_until'] ?? 0) > time();
                $liveStats = $event['live_stats'] ?? [];
                if (! isset($liveStats['corners']) && isset($event['corners']['home'], $event['corners']['away'])) {
                    $liveStats['corners'] = $event['corners'];
                }
                if (! isset($liveStats['yellow_cards']) && isset($event['card_counts']['home']['yellow'], $event['card_counts']['away']['yellow'])) {
                    $liveStats['yellow_cards'] = [
                        'home' => $event['card_counts']['home']['yellow'],
                        'away' => $event['card_counts']['away']['yellow'],
                    ];
                }
                if (! isset($liveStats['red_cards']) && isset($event['card_counts']['home']['total_red'], $event['card_counts']['away']['total_red'])) {
                    $liveStats['red_cards'] = [
                        'home' => $event['card_counts']['home']['total_red'],
                        'away' => $event['card_counts']['away']['total_red'],
                    ];
                }
                if (isset($liveStats['accurate_passes'], $liveStats['total_passes'])) {
                    $liveStats['pass_accuracy'] = [
                        'home' => $liveStats['total_passes']['home'] > 0 ? round(($liveStats['accurate_passes']['home'] / $liveStats['total_passes']['home']) * 100, 1) : 0,
                        'away' => $liveStats['total_passes']['away'] > 0 ? round(($liveStats['accurate_passes']['away'] / $liveStats['total_passes']['away']) * 100, 1) : 0,
                    ];
                }
                $topStats = [
                    'attacks' => ['label' => 'Attacks', 'percent' => false],
                    'dangerous_attacks' => ['label' => 'Dangerous Attacks', 'percent' => false],
                    'possession' => ['label' => 'Possession', 'percent' => true],
                    'total_shots' => ['label' => 'Shots', 'percent' => false],
                    'shots_on_target' => ['label' => 'On Target', 'percent' => false],
                ];
                $statSections = [
                    'Shots' => [
                        'total_shots' => ['Total Shots', false],
                        'shots_on_target' => ['Shots on Target', false],
                        'shots_off_target' => ['Shots off Target', false],
                        'blocked_shots' => ['Blocked Shots', false],
                    ],
                    'Attacks' => [
                        'attacks' => ['Attacks', false],
                        'dangerous_attacks' => ['Dangerous Attacks', false],
                        'offsides' => ['Offsides', false],
                    ],
                    'Passes' => [
                        'total_passes' => ['Total Passes', false],
                        'accurate_passes' => ['Accurate Passes', false],
                        'pass_accuracy' => ['Pass Accuracy', true],
                    ],
                    'Defense' => [
                        'fouls' => ['Fouls', false],
                        'tackles' => ['Tackles', false],
                        'interceptions' => ['Interceptions', false],
                    ],
                    'Corners & Cards' => [
                        'corners' => ['Corners', false],
                        'yellow_cards' => ['Yellow Cards', false],
                        'red_cards' => ['Red Cards', false],
                    ],
                ];
            @endphp
            <div data-match-url="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}" data-sportApi-match-id="{{ $event['sportsApiPro_match_id'] }}" data-betfair-match-id="{{ $event['betfair_match_id'] }}" @if ($goalHighlightActive) data-goal-highlight-until="{{ $event['goal_highlight_until'] }}" @endif class="event-row grid min-w-0 cursor-pointer grid-cols-[minmax(0,1fr)_118px_360px] items-center [background:#fafdff] [border-radius:12px] [padding:5px_12px_5px_16px] [border:1px_solid_#e6eff8] [gap:4px_12px] [transition:0.06s] [min-height:44px] dark:[background:#1a2a38] dark:[border-color:#2a3f50] dark:[&:hover]:[background:#223848] [&:hover]:[background:#f2f8ff] [&:hover]:[border-color:#c6d8ea] max-[1024px]:grid-cols-[118px_minmax(0,1fr)] max-[1024px]:gap-2 max-[480px]:grid-cols-1 max-[480px]:p-2.5 {{ $goalHighlightActive ? '!bg-[#ffe4ec] !border-[#f7a8bd] dark:!bg-[#4a2633]' : '' }}">
                <div class="event-left relative flex min-w-0 flex-nowrap items-center gap-2 overflow-hidden max-[1024px]:col-span-2 max-[480px]:col-span-1">
                    <button type="button" class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342] disabled:cursor-wait disabled:opacity-60 {{ ($event['is_favorite'] ?? false) ? 'active-fav' : '' }}" data-action="favorite" data-sport="football" data-event-key="{{ $event['unique_key'] }}" data-event-id="{{ $event['sportsApiPro_match_id'] }}" data-team1="{{ $event['team1'] }}" data-team2="{{ $event['team2'] }}" data-start-timestamp="{{ $event['timestamp'] }}" aria-pressed="{{ ($event['is_favorite'] ?? false) ? 'true' : 'false' }}" aria-label="Favorite {{ $event['team1'] }} vs {{ $event['team2'] }}"><i class="fas fa-star"></i></button>
                    @php $color = $event['status'] == 'Live' ? '#dc2e08' : '#1f4b66'; @endphp
                    <span style="color: {{ $color }}" class="event-time w-[52px] shrink-0 [font-size:13px] font-medium [transition:color_0.3s] text-right dark:[color:#b0c8dd]">{{ $event['minutes'] }}@if ($event['status'] === 'Live' && preg_match('/^\d+(?:\+\d*)?$/', (string) $event['minutes']) === 1)<span class="minute-tick-blink">'</span>@endif</span>
                    @if ($goalHighlightActive)
                        <span data-goal-indicator class="absolute left-[82px] z-10 rounded bg-[#ffe4ec] px-1 font-bold text-sm text-[#d22545] dark:bg-[#4a2633]">Goal</span>
                    @endif
                    @php
                        $homeCards = $event['card_counts']['home'] ?? [];
                        $awayCards = $event['card_counts']['away'] ?? [];
                        $homeCards['yellow'] = $homeCards['yellow'] ?? (($event['cards']['home'] ?? null) === 'yellow' ? 1 : 0);
                        $awayCards['yellow'] = $awayCards['yellow'] ?? (($event['cards']['away'] ?? null) === 'yellow' ? 1 : 0);
                    @endphp
                    <div class="team-score-group grid min-w-0 flex-1 grid-cols-[28px_28px_28px_minmax(0,180px)_44px_minmax(0,180px)_28px_28px_28px] items-center justify-center gap-x-0.5 overflow-hidden whitespace-nowrap font-semibold text-[15px] text-[#0b2a40] max-[768px]:grid-cols-[24px_24px_24px_minmax(0,1fr)_40px_minmax(0,1fr)_24px_24px_24px] max-[480px]:text-[12px]">
                        <span class="text-center text-xs text-[#48708d] max-[480px]:text-[10px] {{ ($event['corners']['home'] ?? 0) > 0 ? '' : 'invisible' }}" title="Home corners"><i class="fas fa-flag"></i> {{ $event['corners']['home'] ?? 0 }}</span>
                        <span class="text-center text-xs text-[#e6b422] max-[480px]:text-[10px] {{ ($homeCards['yellow'] ?? 0) > 0 ? '' : 'invisible' }}" title="Home yellow cards"><i class="fas fa-square"></i> {{ $homeCards['yellow'] ?? 0 }}</span>
                        <span class="text-center text-xs text-[#c74e4e] max-[480px]:text-[10px] {{ ($homeCards['total_red'] ?? 0) > 0 ? '' : 'invisible' }}" title="Direct red: {{ $homeCards['direct_red'] ?? 0 }}, second yellow: {{ $homeCards['second_yellow'] ?? 0 }}"><i class="fas fa-square"></i> {{ $homeCards['total_red'] ?? 0 }}</span>

                        <span class="team-name flex w-full min-w-0 items-center justify-end gap-1 pr-1 text-right font-semibold [transition:color_0.3s] dark:text-[#e8edf2]" title="{{ $event['team1'] }}">
                            @if ($event['home_team_logo'] ?? null)
                                <img src="{{ $event['home_team_logo'] }}" alt="" width="18" height="18" loading="lazy" decoding="async" onerror="this.remove()" class="h-[18px] w-[18px] shrink-0 object-contain">
                            @endif
                            <span class="min-w-0 truncate">{{ $event['team1'] }}</span>
                        </span>
                        <span class="score-badge live-score block w-[44px] min-w-[44px] max-w-[44px] justify-self-center whitespace-nowrap px-1 text-center text-[16px] font-bold tabular-nums [font-variant-numeric:tabular-nums] [transition:color_0.3s] dark:text-[#e8edf2] dark:[&.live-score]:text-[#ff6b6b] dark:[&.finished-score]:text-[#e8edf2] [&.live-score]:text-[#d63e3e] [&.finished-score]:text-[#0b2a40] [&.upcoming-score]:text-[#8aaccc] dark:[&.upcoming-score]:text-[#5a7d99] max-[480px]:text-[14px]">{{ $event['score'] ?? '—' }}</span>
                        <span class="team-name away flex w-full min-w-0 items-center justify-start gap-1 pl-1 text-left font-normal text-[#1f4b66] [transition:color_0.3s] dark:text-[#b0c8dd]" title="{{ $event['team2'] }}">
                            <span class="min-w-0 truncate">{{ $event['team2'] }}</span>
                            @if ($event['away_team_logo'] ?? null)
                                <img src="{{ $event['away_team_logo'] }}" alt="" width="18" height="18" loading="lazy" decoding="async" onerror="this.remove()" class="h-[18px] w-[18px] shrink-0 object-contain">
                            @endif
                        </span>

                        <span class="text-center text-xs text-[#e6b422] max-[480px]:text-[10px] {{ ($awayCards['yellow'] ?? 0) > 0 ? '' : 'invisible' }}" title="Away yellow cards"><i class="fas fa-square"></i> {{ $awayCards['yellow'] ?? 0 }}</span>
                        <span class="text-center text-xs text-[#c74e4e] max-[480px]:text-[10px] {{ ($awayCards['total_red'] ?? 0) > 0 ? '' : 'invisible' }}" title="Direct red: {{ $awayCards['direct_red'] ?? 0 }}, second yellow: {{ $awayCards['second_yellow'] ?? 0 }}"><i class="fas fa-square"></i> {{ $awayCards['total_red'] ?? 0 }}</span>
                        <span class="text-center text-xs text-[#48708d] max-[480px]:text-[10px] {{ ($event['corners']['away'] ?? 0) > 0 ? '' : 'invisible' }}" title="Away corners">{{ $event['corners']['away'] ?? 0 }} <i class="fas fa-flag"></i></span>
                    </div>
                </div>
                <div class="event-actions flex w-[118px] items-center justify-end [gap:2px] max-[1024px]:w-[118px] max-[1024px]:justify-start max-[480px]:w-full max-[480px]:justify-center">
                    <button type="button" class="live-stat-icon bg-transparent border-0 [color:#d63e3e] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] disabled:cursor-not-allowed disabled:opacity-30 dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#b52a2a]" data-action="livestats" data-match-id="{{ $event['sportsApiPro_match_id'] }}" aria-expanded="false" aria-controls="live-stats-{{ $event['sportsApiPro_match_id'] }}" title="Live Statistics" @disabled($event['status'] !== 'Live')><i class="fas fa-wave-square"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="chart" title="Chart"><i
                            class="fas fa-chart-simple"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="stats" title="Statistics"><i
                            class="fas fa-chart-bar"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] disabled:cursor-not-allowed disabled:opacity-30 dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40]" data-action="prediction" data-match-id="{{ $event['sportsApiPro_match_id'] }}" aria-expanded="false" aria-controls="prediction-{{ $event['sportsApiPro_match_id'] }}" title="Prediction" @disabled($event['status'] !== 'Prematch')><i
                            class="fas fa-brain"></i></button>
                </div>
                <div class="odds-group grid min-h-8 w-[360px] grid-cols-3 items-center gap-2 whitespace-nowrap max-[1024px]:w-full max-[480px]:col-span-1 max-[480px]:gap-1.5">
                    @foreach ($event['markets'] as $odd)
                        <div class="odd-box relative flex min-w-0 items-center justify-center gap-1 whitespace-nowrap rounded-[10px] border border-[#d7e3ef] bg-white px-2 py-0.5 text-center text-[15px] font-semibold text-[#0b2a40] shadow-[0_1px_2px_rgba(0,0,0,0.02)] transition duration-300 [cursor:default] dark:border-[#3a5568] dark:bg-[#1f3444] dark:text-[#e8edf2] dark:[&_.tooltip]:bg-[#2a4050] dark:[&_.tooltip]:text-[#e8edf2] [&_.tooltip]:invisible [&_.tooltip]:absolute [&_.tooltip]:bottom-[110%] [&_.tooltip]:left-1/2 [&_.tooltip]:z-10 [&_.tooltip]:-translate-x-1/2 [&_.tooltip]:whitespace-nowrap [&_.tooltip]:rounded-md [&_.tooltip]:bg-[#0b2a40] [&_.tooltip]:px-2 [&_.tooltip]:py-1 [&_.tooltip]:text-[11px] [&_.tooltip]:font-normal [&_.tooltip]:text-white [&_.tooltip]:opacity-0 [&_.tooltip]:transition [&:hover_.tooltip]:visible [&:hover_.tooltip]:opacity-100 [&_i]:text-xs [&_i]:transition-all [&_i]:duration-150">
                            {{ number_format($odd['currentBackOdds'], 2) }}
                            @if ($odd['odds_direction'] == 'down')
                                <i class="fas fa-arrow-down text-[#c74e4e]"></i>
                                <span class="tooltip">{{ number_format($odd['initialBackOdds'] ?? $odd['previousBackOdds'], 2) }} >> {{ number_format($odd['currentBackOdds'], 2) }}</span>
                            @elseif($odd['odds_direction'] == 'up')
                                <i class="fas fa-arrow-up text-[#1f9a6e]"></i>
                                <span class="tooltip">{{ number_format($odd['initialBackOdds'] ?? $odd['previousBackOdds'], 2) }} << {{ number_format($odd['currentBackOdds'], 2) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($event['status'] === 'Live')
                <div id="live-stats-{{ $event['sportsApiPro_match_id'] }}" data-live-stats-panel="{{ $event['sportsApiPro_match_id'] }}" role="dialog" aria-modal="true" aria-label="Live statistics for {{ $event['team1'] }} vs {{ $event['team2'] }}" class="fixed inset-0 z-[80] hidden overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6">
                    <div class="relative mx-auto my-4 w-full max-w-4xl overflow-hidden rounded-2xl border border-[#dbe7f2] bg-white shadow-2xl dark:border-[#2a3f50] dark:bg-[#162532] sm:my-8">
                    <div class="relative bg-gradient-to-r from-[#153f5c] to-[#2478a8] px-4 py-5 text-white sm:px-7">
                        <button type="button" data-close-live-stats class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-lg text-white transition hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/70" aria-label="Close live statistics"><i class="fas fa-times"></i></button>
                        <div class="mb-3 text-center text-[11px] font-bold uppercase tracking-[0.14em] text-[#b9def3]">{{ $matches['tournament_name'] }} · Live Statistics</div>
                        <div class="flex flex-wrap items-center justify-center gap-3 text-center sm:gap-6">
                            <span class="max-w-[36%] text-sm font-semibold sm:text-base">{{ $event['team1'] }}</span>
                            <span class="text-2xl font-extrabold tracking-wider sm:text-4xl">{{ $event['score'] }}</span>
                            <span class="max-w-[36%] text-sm font-semibold sm:text-base">{{ $event['team2'] }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-center gap-2 text-xs font-semibold uppercase tracking-wider">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                            {{ $event['minutes'] }}@if (preg_match('/^\d+(?:\+\d*)?$/', (string) $event['minutes']) === 1)<span class="minute-tick-blink">'</span>@endif
                        </div>
                    </div>

                    @if ($liveStats === [])
                        <div class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Live statistics are waiting for provider data.</div>
                    @else
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-5 dark:border-slate-700 dark:bg-[#1b2d3b] sm:px-7">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($topStats as $statKey => $statMeta)
                                    @if (isset($liveStats[$statKey]['home'], $liveStats[$statKey]['away']))
                                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-center dark:border-slate-700 dark:bg-[#203746]">
                                            <div class="mb-2 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $statMeta['label'] }}</div>
                                            <div class="flex items-center justify-center gap-2 text-sm font-extrabold text-[#245f84] dark:text-[#8ec8e9]">
                                                <span>{{ $liveStats[$statKey]['home'] + 0 }}{{ $statMeta['percent'] ? '%' : '' }}</span>
                                                <span class="text-[10px] font-medium text-slate-400">—</span>
                                                <span>{{ $liveStats[$statKey]['away'] + 0 }}{{ $statMeta['percent'] ? '%' : '' }}</span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-6 p-4 sm:p-7 lg:grid-cols-2">
                            @foreach ($statSections as $sectionTitle => $sectionStats)
                                @php
                                    $availableSectionStats = array_filter($sectionStats, static fn ($stat, $key) => isset($liveStats[$key]['home'], $liveStats[$key]['away']), ARRAY_FILTER_USE_BOTH);
                                @endphp
                                @if ($availableSectionStats !== [])
                                    <section>
                                        <h3 class="mb-4 flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-[#245f84] before:h-4 before:w-1 before:rounded-full before:bg-red-500 before:content-[''] dark:text-[#8ec8e9]">{{ $sectionTitle }}</h3>
                                        <div class="space-y-4">
                                            @foreach ($availableSectionStats as $statKey => [$label, $isPercent])
                                                @php
                                                    $homeValue = $liveStats[$statKey]['home'];
                                                    $awayValue = $liveStats[$statKey]['away'];
                                                    $totalValue = $homeValue + $awayValue;
                                                    $homeWidth = $totalValue > 0 ? round(($homeValue / $totalValue) * 100, 2) : 50;
                                                    $awayWidth = 100 - $homeWidth;
                                                @endphp
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between gap-3 text-xs">
                                                        <span class="min-w-9 font-bold text-[#245f84] dark:text-[#8ec8e9]">{{ $homeValue + 0 }}{{ $isPercent ? '%' : '' }}</span>
                                                        <span class="text-center font-semibold text-slate-500 dark:text-slate-300">{{ $label }}</span>
                                                        <span class="min-w-9 text-right font-bold text-[#245f84] dark:text-[#8ec8e9]">{{ $awayValue + 0 }}{{ $isPercent ? '%' : '' }}</span>
                                                    </div>
                                                    <div class="flex h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                        <span class="h-full bg-red-500 transition-[width] duration-500" style="width: {{ $homeWidth }}%"></span>
                                                        <span class="h-full bg-[#1f3a52] transition-[width] duration-500 dark:bg-[#8aaccc]" style="width: {{ $awayWidth }}%"></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    </div>
                </div>
            @endif
            @if ($event['status'] === 'Prematch')
                @php
                    $predictionPolls = [
                        '1x2' => [
                            'label' => 'Match Result',
                            'options' => [
                                '1' => ['label' => '1', 'sublabel' => $event['team1']],
                                'X' => ['label' => 'X', 'sublabel' => 'Draw'],
                                '2' => ['label' => '2', 'sublabel' => $event['team2']],
                            ],
                        ],
                        'uo' => [
                            'label' => 'Total Goals',
                            'options' => [
                                'UNDER' => ['label' => 'Under 2.5', 'sublabel' => '2 goals or less'],
                                'OVER' => ['label' => 'Over 2.5', 'sublabel' => '3 goals or more'],
                            ],
                        ],
                    ];
                @endphp
                <div id="prediction-{{ $event['sportsApiPro_match_id'] }}" data-prediction-panel="{{ $event['sportsApiPro_match_id'] }}" role="dialog" aria-modal="true" aria-label="Predictions for {{ $event['team1'] }} vs {{ $event['team2'] }}" class="fixed inset-0 z-[80] hidden overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6">
                    <div class="relative mx-auto my-4 w-full max-w-[700px] overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#162532] sm:my-8">
                        <div class="relative bg-gradient-to-br from-[#1a1a2e] to-[#16213e] px-5 py-6 text-center text-white sm:px-7">
                            <button type="button" data-close-prediction class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-lg text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70" aria-label="Close predictions"><i class="fas fa-times"></i></button>
                            <div class="mb-4 text-xs font-semibold uppercase tracking-[0.15em] text-[#48bb78]">{{ $matches['tournament_name'] }}</div>
                            <div class="flex items-center justify-center gap-3 text-base font-bold sm:text-xl">
                                <span>{{ $event['team1'] }}</span>
                                <span class="font-light text-slate-400">—</span>
                                <span>{{ $event['team2'] }}</span>
                            </div>
                            <div class="mt-2 text-sm text-slate-400">{{ date('H:i', $event['timestamp']) }}</div>
                        </div>

                        <div class="p-5 sm:p-7">
                            <h2 class="mb-2 flex items-center gap-2 text-sm font-bold text-[#1a1a2e] before:h-[18px] before:w-1 before:rounded-full before:bg-[#48bb78] before:content-[''] dark:text-white">Community Predictions</h2>
                            <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">Share your opinion before the match starts</p>
                            <span class="mb-5 inline-block rounded-full border border-[#48bb78] bg-[#f0fff4] px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-[#1a1a2e] dark:bg-[#183a2b] dark:text-[#8be0ac]">⚽ Football</span>

                            <div class="space-y-7">
                                @foreach ($predictionPolls as $pollKey => $poll)
                                    @php
                                        $userVote = $event['prediction_votes'][$pollKey] ?? null;
                                        $counts = $event['predictions'][$pollKey] ?? [];
                                        $totalVotes = array_sum($counts);
                                        $highestCount = $counts === [] ? 0 : max($counts);
                                    @endphp
                                    <section data-prediction-poll="{{ $pollKey }}">
                                        <div class="mb-3 text-[13px] font-semibold text-slate-600 dark:text-slate-300">{{ $poll['label'] }}</div>

                                        @if ($userVote === null)
                                            <div class="flex gap-3 max-[480px]:gap-2">
                                                @foreach ($poll['options'] as $optionValue => $option)
                                                    <button type="button" data-prediction-option data-match-id="{{ $event['sportsApiPro_match_id'] }}" data-poll="{{ $pollKey }}" data-value="{{ $optionValue }}" class="flex-1 select-none rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-4 text-center transition hover:-translate-y-0.5 hover:border-[#48bb78] hover:bg-[#f0fff4] dark:border-slate-700 dark:bg-[#1d3040] dark:hover:border-[#48bb78] dark:hover:bg-[#183a2b] max-[480px]:px-2 max-[480px]:py-3">
                                                        <span class="block text-[15px] font-semibold text-[#1a1a2e] dark:text-white max-[480px]:text-[13px]">{{ $option['label'] }}</span>
                                                        <span class="mt-1 block text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $option['sublabel'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="space-y-2.5">
                                                @foreach ($poll['options'] as $optionValue => $option)
                                                    @php
                                                        $count = (int) ($counts[$optionValue] ?? 0);
                                                        $percent = $totalVotes > 0 ? (int) round(($count / $totalVotes) * 100) : 0;
                                                        $isUserVote = $userVote === $optionValue;
                                                        $isWinner = $count > 0 && $count === $highestCount;
                                                    @endphp
                                                    <div class="flex items-center gap-3">
                                                        <span class="min-w-[80px] text-[13px] font-semibold text-[#1a1a2e] dark:text-white max-[480px]:min-w-[60px] max-[480px]:text-[11px]">{{ $option['label'] }}</span>
                                                        <div class="h-[26px] flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                            <div class="flex h-full min-w-10 items-center justify-end rounded-full px-2.5 text-right transition-[width] duration-500 {{ $isUserVote ? 'bg-gradient-to-r from-[#1a1a2e] to-[#2d3748]' : ($isWinner ? 'bg-gradient-to-r from-[#2f855a] to-[#38a169]' : 'bg-gradient-to-r from-[#48bb78] to-[#68d391]') }}" style="width: {{ max(10, $percent) }}%">
                                                                <span class="text-xs font-bold text-white">{{ $percent }}%</span>
                                                            </div>
                                                        </div>
                                                        <span class="min-w-[45px] text-right text-xs text-slate-500 dark:text-slate-400">{{ $count }}</span>
                                                    </div>
                                                @endforeach
                                                <div class="mt-4 border-t border-slate-200 pt-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">Total votes: {{ $totalVotes }}</div>
                                            </div>
                                        @endif
                                    </section>
                                @endforeach
                            </div>
                        </div>
                        <div class="px-7 pb-6 text-center text-[11px] text-slate-400">Community opinion · Not an official prediction</div>
                    </div>
                </div>
            @endif
        @endforeach
    @endforeach
@unless ($append ?? false)
</div>
@endunless

@push('js')
    @vite('resources/users/js/sports/football.js')
@endpush
