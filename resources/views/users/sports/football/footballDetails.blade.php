<div class="fixtures-container space-y-2 flex flex-col [gap:4px] [margin-top:4px]" id="fixturesContainer">
    @foreach ($data as $league => $matches)
        <div class="league-header [font-size:13px] font-bold uppercase [letter-spacing:0.5px] [color:#1e4763] [padding:10px_0_6px_0] [border-bottom:2px_solid_#e6edf6] [margin-top:8px] [transition:0.3s] bg-white [z-index:2] dark:[color:#8aaccc] dark:[border-bottom-color:#2a3f50] dark:[background:#1a2a38] [&:first-of-type]:[margin-top:0]">{{ $matches['tournament_name'] }} · {{ $matches['status'] }}</div>
        @foreach ($matches['matches'] as $event)
            <div data-sportApi-match-id="{{ $event['sportsApiPro_match_id'] }}" data-betfair-match-id="{{ $event['betfair_match_id'] }}" class="event-row flex items-center [background:#fafdff] [border-radius:12px] [padding:5px_12px_5px_16px] [border:1px_solid_#e6eff8] [gap:4px_12px] flex-wrap [transition:0.06s] [min-height:44px] dark:[background:#1a2a38] dark:[border-color:#2a3f50] dark:[&:hover]:[background:#223848] [&:hover]:[background:#f2f8ff] [&:hover]:[border-color:#c6d8ea]">
                <div class="event-left flex items-center flex-wrap [gap:6px_10px] [flex:2_1_380px] max-[800px]:[flex:1_1_100%]">
                    <button type="button" class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]" data-action="favorite" aria-label="Favorite {{ $event['home'] ?? 'ahdkjahskd' }} vs {{ $event['away'] ?? 'shxc' }}"><i class="fas fa-star"></i></button>
                    @php $color = $event['status'] == 'Live' ? '#dc2e08' : '#1f4b66'; @endphp
                    <span style="color: {{ $color }}"  class="event-time [font-size:13px] font-medium [min-width:44px] [transition:color_0.3s] text-right dark:[color:#b0c8dd]">{{ $event['minutes'] }}</span>
                    <div class="team-score-group flex items-center [gap:6px] font-semibold [font-size:15px] [color:#0b2a40] [flex:1] justify-center [min-width:200px] max-[800px]:[justify-content:flex-start] max-[800px]:[min-width:100%]">
                        @if (($event['corners']['home'] ?? 0) > 0)
                            <span class="corner-icon [font-size:13px] [color:#48708d] [margin:0_2px] [transition:color_0.3s] dark:[color:#8aaccc]"><i class="fas fa-flag"></i></span><span class="corner-count [font-size:13px] font-semibold [color:#1f4b66] [transition:color_0.3s] dark:[color:#b0c8dd]">{{ "qwqw" }}</span>
                        @endif
                        <span class="team-name font-semibold [transition:color_0.3s] whitespace-nowrap dark:[color:#e8edf2] dark:[&.away]:[color:#b0c8dd]">{{ $event['team1'] }}</span>
                        @foreach (['home', 'away'] as $side)
                            @if (in_array($event['cards'][$side] ?? null, ['yellow', 'red'], true))
                                <span class="card-icon card-{{ $event['cards'][$side] }} [font-size:14px] [margin:0_2px] {{ $event['cards'][$side] === 'red' ? 'text-[#c74e4e]' : 'text-[#e6b422]' }}"><i class="fas fa-square"></i></span>
                            @endif
                        @endforeach
                        <span class="score-badge live-score font-bold [font-size:16px] [padding:0_4px] [min-width:28px] text-center [transition:color_0.3s] dark:[color:#e8edf2] dark:[&.live-score]:[color:#ff6b6b] dark:[&.finished-score]:[color:#e8edf2] [&.live-score]:[color:#d63e3e] [&.finished-score]:[color:#0b2a40] [&.upcoming-score]:[color:#8aaccc] dark:[&.upcoming-score]:[color:#5a7d99]">{{ $event['score'] ?? '—' }}</span>
                        <span class="team-name font-semibold [transition:color_0.3s] whitespace-nowrap dark:[color:#e8edf2] dark:[&.away]:[color:#b0c8dd] [&.away]:[font-weight:400] [&.away]:[color:#1f4b66] [&.away]:[transition:color_0.3s]">{{ $event['team2'] }}</span>
                        @if (($event['corners']['away'] ?? 0) > 0)
                            <span class="corner-count [font-size:13px] font-semibold [color:#1f4b66] [transition:color_0.3s] dark:[color:#b0c8dd]">{{ $event['corners']['away'] }}</span><span class="corner-icon [font-size:13px] [color:#48708d] [margin:0_2px] [transition:color_0.3s] dark:[color:#8aaccc]"><i class="fas fa-flag"></i></span>
                        @endif
                    </div>
                </div>
                <div class="event-actions flex items-center [gap:2px] [margin-left:auto]">
                    <button type="button" class="live-stat-icon bg-transparent border-0 [color:#d63e3e] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#b52a2a] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="livestats" title="Live Statistics"><i class="fas fa-wave-square"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="chart" title="Chart"><i
                            class="fas fa-chart-simple"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="stats" title="Statistics"><i
                            class="fas fa-chart-bar"></i></button>
                    <button type="button" class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:#e8edf2] [&:hover]:[background:#dce8f5] [&:hover]:[color:#0b2a40] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]" data-action="prediction" title="Prediction"><i
                            class="fas fa-brain"></i></button>
                </div>
                <div class="odds-group flex items-center [gap:6px] [min-width:110px] justify-end [margin-left:4px] max-[800px]:[margin-left:0] max-[800px]:[width:100%] max-[800px]:[justify-content:flex-start]">
                    @foreach ($event['markets'] as $odd)
                        <div class="odd-box bg-white [border-radius:10px] [padding:2px_8px_2px_8px] font-semibold [font-size:15px] [min-width:44px] text-center [border:1px_solid_#d7e3ef] [color:#0b2a40] flex items-center [gap:3px] [box-shadow:0_1px_2px_rgba(0,_0,_0,_0.02)] [transition:0.3s] [cursor:default] relative justify-center dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#e8edf2] dark:[&_.tooltip]:[background:#2a4050] dark:[&_.tooltip]:[color:#e8edf2] [&_.tooltip]:[visibility:hidden] [&_.tooltip]:[opacity:0] [&_.tooltip]:[position:absolute] [&_.tooltip]:[bottom:110%] [&_.tooltip]:[left:50%] [&_.tooltip]:[transform:translateX(-50%)] [&_.tooltip]:[background:#0b2a40] [&_.tooltip]:[color:white] [&_.tooltip]:[padding:3px_8px] [&_.tooltip]:[border-radius:6px] [&_.tooltip]:[font-size:11px] [&_.tooltip]:[font-weight:400] [&_.tooltip]:[white-space:nowrap] [&_.tooltip]:[transition:0.2s] [&_.tooltip]:[z-index:10] [&:hover_.tooltip]:[visibility:visible] [&:hover_.tooltip]:[opacity:1] [&_i]:[font-size:12px] [&_i]:[transition:all_0.15s_ease]">
                            {{ number_format($odd['currentBackOdds'], 2) }}
                            @if ($odd['odds_direction'] == 'down')
                                <i class="fas fa-arrow-down text-[#c74e4e]"></i>
                                <span class="tooltip">{{ number_format($odd['previousBackOdds'], 2) }} >> {{ number_format($odd['currentBackOdds'], 2) }}</span>
                            @elseif($odd['odds_direction'] == 'up')
                                <i class="fas fa-arrow-up text-[#1f9a6e]"></i>
                                <span class="tooltip">{{ number_format($odd['previousBackOdds'], 2) }} << {{ number_format($odd['currentBackOdds'], 2) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endforeach
</div>

@push('js')
    @vite('resources/users/js/sports/football.js')
@endpush
