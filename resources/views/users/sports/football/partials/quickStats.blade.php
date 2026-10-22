@php
    $quickStats = [];
    foreach (['attacks', 'dangerous_attacks', 'possession', 'total_shots'] as $statKey) {
        $home = is_numeric($liveStats[$statKey]['home'] ?? null) ? max(0, (float) $liveStats[$statKey]['home']) : null;
        $away = is_numeric($liveStats[$statKey]['away'] ?? null) ? max(0, (float) $liveStats[$statKey]['away']) : null;
        $available = $home !== null && $away !== null;
        $total = ($home ?? 0) + ($away ?? 0);
        $quickStats[$statKey] = [
            'home' => $home, 'away' => $away,
            'home_fill' => $available ? ($total > 0 ? round(($home / $total) * 100, 4) : 50) : null,
        ];
    }
    $shots = $quickStats['total_shots'];
@endphp
<div id="quick-stats-{{ $event['sportsApiPro_match_id'] }}"
    data-quick-stats-panel="{{ $event['sportsApiPro_match_id'] }}" role="dialog" aria-modal="true"
    aria-labelledby="quick-stats-title-{{ $event['sportsApiPro_match_id'] }}" tabindex="-1"
    class="fixed inset-0 z-[85] hidden overflow-y-auto bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6">
    <div class="relative mx-auto my-8 w-full max-w-lg overflow-hidden rounded-xl border border-white/15 bg-[#121212] text-slate-100 shadow-2xl sm:my-20">
        <header class="border-b border-white/10 px-4 py-3 pr-12">
            <h2 id="quick-stats-title-{{ $event['sportsApiPro_match_id'] }}" class="text-xs font-semibold text-slate-300">{{ __('Quick Stats') }}</h2>
            <div class="mt-1 flex items-center justify-between gap-2 text-xs">
                <span class="flex min-w-0 flex-1 items-center gap-1.5"><span class="h-2 w-2 shrink-0 rounded-full bg-[#d62352]" aria-hidden="true"></span><span class="truncate" title="{{ $event['team1'] }}">{{ $event['team1'] }}</span></span>
                <strong data-score-status="{{ $event['status'] }}" class="match-score match-score-on-dark shrink-0 text-sm tabular-nums">{{ $event['score'] }}</strong>
                <span class="flex min-w-0 flex-1 items-center justify-end gap-1.5"><span class="truncate text-right" title="{{ $event['team2'] }}">{{ $event['team2'] }}</span><span class="h-2 w-2 shrink-0 rounded-full bg-[#f4d747]" aria-hidden="true"></span></span>
            </div>
            <button type="button" data-close-quick-stats aria-label="{{ __('Close Quick Stats') }}"
                class="absolute right-2 top-2 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-lg text-slate-300 hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-white">&times;</button>
        </header>
        <div class="px-3 py-5 sm:px-5">
            <div class="grid grid-cols-3 gap-2">
                @foreach (['attacks' => ['Attacks', 'fa-angle-right'], 'dangerous_attacks' => ['Dangerous Attacks', 'fa-angles-right'], 'possession' => ['Possession %', 'fa-percent']] as $statKey => [$label, $icon])
                    @php $stat = $quickStats[$statKey]; @endphp
                    <div data-quick-stat="{{ $statKey }}" data-home-fill="{{ $stat['home_fill'] }}" class="min-w-0 text-center">
                        <div class="min-h-8 text-[10px] leading-4 text-slate-300 sm:min-h-5">{{ __($label) }}</div>
                        <div class="flex items-center justify-center gap-1.5 font-bold tabular-nums">
                            <span data-quick-home class="min-w-4 text-right text-xs sm:text-sm">{{ $stat['home'] ?? '—' }}</span>
                            <span class="relative h-9 w-9 shrink-0">
                                <svg viewBox="0 0 40 40" class="h-full w-full" role="img"
                                    aria-label="{{ __($label).': '.$event['team1'].' '.($stat['home'] ?? __('Not available')).', '.$event['team2'].' '.($stat['away'] ?? __('Not available')) }}">
                                    <circle cx="20" cy="20" r="15" fill="none" stroke="{{ $stat['home_fill'] !== null ? '#f4d747' : '#475569' }}" stroke-width="4" />
                                    @if ($stat['home_fill'] !== null)
                                        <circle cx="20" cy="20" r="15" pathLength="100" fill="none" stroke="#d62352" stroke-width="4"
                                            stroke-dasharray="{{ $stat['home_fill'] }} {{ 100 - $stat['home_fill'] }}" transform="rotate(-90 20 20)" />
                                    @endif
                                </svg>
                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center text-[10px] text-slate-200" aria-hidden="true"><i class="fas {{ $icon }}"></i></span>
                            </span>
                            <span data-quick-away class="min-w-4 text-left text-xs sm:text-sm">{{ $stat['away'] ?? '—' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 grid grid-cols-[auto_minmax(0,1fr)_auto] items-end gap-2 sm:gap-4">
                <div class="grid grid-cols-3 gap-2 text-center text-xs tabular-nums" aria-label="{{ $event['team1'] }}">
                    <span title="{{ __('Home corners') }}"><i class="fas fa-flag text-[#d62352]" aria-hidden="true"></i><strong data-quick-count="corners-home" class="mt-1 block">{{ $liveStats['corners']['home'] ?? '—' }}</strong></span>
                    <span title="{{ __('Home red cards') }}"><i class="fas fa-square text-[#d62352]" aria-hidden="true"></i><strong data-quick-count="red_cards-home" class="mt-1 block">{{ $liveStats['red_cards']['home'] ?? '—' }}</strong></span>
                    <span title="{{ __('Home yellow cards') }}"><i class="fas fa-square text-[#f4d747]" aria-hidden="true"></i><strong data-quick-count="yellow_cards-home" class="mt-1 block">{{ $liveStats['yellow_cards']['home'] ?? '—' }}</strong></span>
                </div>
                <div data-quick-stat="total_shots" data-home-fill="{{ $shots['home_fill'] }}" class="min-w-0">
                    <div class="mb-1.5 text-center text-[10px] text-slate-300">{{ __('Shots / On Target') }}</div>
                    <div class="flex items-center gap-1.5 text-xs font-bold tabular-nums">
                        <span class="shrink-0" data-quick-home-shots>{{ $shots['home'] ?? '—' }}/{{ $liveStats['shots_on_target']['home'] ?? '—' }}</span>
                        <span class="flex h-1.5 min-w-4 flex-1 overflow-hidden rounded-full bg-slate-600" role="img" aria-label="{{ __('Shots ratio').' '.($shots['home'] ?? '—').' : '.($shots['away'] ?? '—') }}">
                            @if ($shots['home_fill'] !== null)
                                <span class="h-full bg-[#d62352]" style="width: {{ $shots['home_fill'] }}%"></span>
                                <span class="h-full bg-[#f4d747]" style="width: {{ 100 - $shots['home_fill'] }}%"></span>
                            @endif
                        </span>
                        <span class="shrink-0" data-quick-away-shots>{{ $shots['away'] ?? '—' }}/{{ $liveStats['shots_on_target']['away'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs tabular-nums" aria-label="{{ $event['team2'] }}">
                    <span title="{{ __('Away yellow cards') }}"><i class="fas fa-square text-[#f4d747]" aria-hidden="true"></i><strong data-quick-count="yellow_cards-away" class="mt-1 block">{{ $liveStats['yellow_cards']['away'] ?? '—' }}</strong></span>
                    <span title="{{ __('Away red cards') }}"><i class="fas fa-square text-[#d62352]" aria-hidden="true"></i><strong data-quick-count="red_cards-away" class="mt-1 block">{{ $liveStats['red_cards']['away'] ?? '—' }}</strong></span>
                    <span title="{{ __('Away corners') }}"><i class="fas fa-flag text-[#f4d747]" aria-hidden="true"></i><strong data-quick-count="corners-away" class="mt-1 block">{{ $liveStats['corners']['away'] ?? '—' }}</strong></span>
                </div>
            </div>
            <p data-quick-unavailable @if (! array_any($quickStats, static fn (array $stat): bool => $stat['home_fill'] === null)) hidden @endif class="mt-4 text-center text-[10px] text-slate-400">{{ __('Some statistics are not available for this match.') }}</p>
        </div>
    </div>
</div>
