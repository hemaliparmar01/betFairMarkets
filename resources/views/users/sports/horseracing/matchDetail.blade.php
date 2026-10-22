@php $raceSport = $raceSport ?? 'horseracing'; $raceTitle = $raceTitle ?? 'Horse Racing'; @endphp
@extends('users.layout.main')
@section('title', $match['team1'].' · '.$match['team2'].' · BF Markets')
@section('content')
    @include('users.layout.sports')
    <section id="horseRaceDetail" data-horse-detail data-race-channel="sports.{{ $raceSport }}.live" data-race-sport="{{ $raceSport }}" data-horse-url="{{ route($raceSport.'.matches.show', $match['id']) }}" data-horse-payload="{{ json_encode($detail) }}" class="min-w-0 pt-4 text-[#12324a] dark:text-[#e8edf2]">
        <a href="{{ route($raceSport.'.home') }}" class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-[#5a7d99] hover:text-[#1a6b9c]">← {{ __('Back to '.$raceTitle) }}</a>
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-[#2a3f50] dark:bg-[#1a2a38]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3"><span class="text-2xl">{{ $raceSport === 'greyhounds' ? '🐕' : '🏇' }}</span><div><h1 class="text-xl font-bold" data-horse-course>{{ $match['team1'] }}</h1><p class="text-sm text-[#5a7d99]" data-horse-description>{{ $match['team2'] }}</p><p class="text-xs text-[#5a7d99]">{{ $match['country'] ?? '' }}</p></div></div>
                <div class="flex items-center gap-4"><div class="text-right"><div class="text-2xl font-bold">{{ $match['minutes'] }}</div><div id="countdown-timer" class="text-xs font-medium text-[#5a7d99]"></div></div><span data-horse-status class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">{{ $match['status'] === 'Prematch' ? __('Upcoming') : $match['status'] }}</span></div>
            </div>
        </div>
        <div class="mb-3 flex flex-wrap gap-2" data-horse-markets>@foreach ($detail['markets'] as $market)<button type="button" data-horse-market="{{ $market['type'] }}" class="rounded-full border border-[#d4e0ec] px-3 py-1 text-xs font-semibold [&.active]:bg-[#0b2a40] [&.active]:text-white">{{ $market['type'] }}</button>@endforeach</div>
        <p class="mb-3 text-xs text-[#5a7d99]">{{ __('Pressure shows odds direction and available exchange liquidity, not traded volume.') }}</p>
        <p data-horse-error class="hidden mb-3 text-sm text-red-500"></p>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#2a3f50] dark:bg-[#1a2a38]">
            <div class="runner-header"><div>{{ __('Runner') }}</div><div class="text-center">{{ __('Favourite') }}</div><div class="text-center">{{ __('Back') }}</div><div class="text-center">{{ __('Lay') }}</div><div class="text-center">{{ __('Available Liquidity Pressure') }}</div></div>
            <div id="runners-container" aria-live="polite">@if (($detail['race']['available'] ?? true) === false)<p class="p-8 text-center">{{ __('Race details and odds become available five minutes before the start.') }}</p>@elseif (! $checkAuthAndSubscription['display_odds'])<p class="flex items-center justify-center gap-3 p-8">@include('lock_icon') {{ __('Log in with an active subscription to view race odds.') }}</p>@endif</div>
        </div>
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 dark:bg-[#203746] dark:border-[#2a3f50]">
                <h3 class="text-xs font-bold text-gray-700 dark:text-[#e8edf2] uppercase tracking-wider mb-3">Visual Indicators Legend</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 text-xs text-gray-600 dark:text-[#b0c8dd]">
                    <div class="flex items-center gap-2">
                        <span class="legend-arrow-down text-base">↓</span>
                        <span>Odds dropped (shortening)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="legend-arrow-up text-base">↑</span>
                        <span>Odds rose (drifting)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="legend-arrow-down text-base">↓↓</span>
                        <span>Large movement (5%+)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span style="color: #f59e0b; font-size: 14px;">⚡</span>
                        <span>Sharp movement (8%+)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] line-through text-gray-400">2.50</span>
                        <span>→</span>
                        <span class="font-bold">2.45</span>
                        <span>Old → New</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full bg-[#e8f7ee] text-[#1f9a6e] text-[10px] font-bold">-2.0%</span>
                        <span>Change %</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="40" height="16" viewBox="0 0 40 16" fill="none">
                            <path d="M2 12 L10 8 L18 10 L26 4 L34 6 L38 2" stroke="#1f9a6e" stroke-width="2" stroke-linecap="round" fill="none"/>
                        </svg>
                        <span>Recent trend (last 30 seconds)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm bg-[#1f9a6e]"></span>
                        <span>Back pressure</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm bg-[#c74e4e]"></span>
                        <span>Lay pressure</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span>1st Favourite</span>
                    </div>
                </div>
            </div>

    </section>
@endsection
@push('head')
<style>
#horseRaceDetail {
.odd-box.flash-size { animation: size-flash 0.65s ease-out; }
@keyframes size-flash { 50% { box-shadow: inset 0 0 0 3px #e6b422; } }
.runner-header {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.4fr 1.4fr 1.6fr;
            gap: 12px;
            padding: 10px 16px;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        .runner-row {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.4fr 1.4fr 1.6fr;
            gap: 12px;
            padding: 10px 16px;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }
        .runner-row:last-child { border-bottom: none; }
        .runner-row:hover { background-color: #f8fbff; }

        
        .odd-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            line-height: 1.2;
            transition: all 0.3s ease;
            min-height: 52px;
            position: relative;
            overflow: hidden;
        }
        .back-box { background-color: #a6d8ff; border: 1px solid #c6d8ea; color: #0b2a40; }
        .lay-box { background-color: #fac9d1; border: 1px solid #eac6d0; color: #0b2a40; }

        
        .odd-value {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 15px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        
        .change-arrow {
            font-size: 12px;
            font-weight: 900;
            transition: transform 0.2s ease;
        }
        .change-arrow.up { color: #c74e4e; }    
        .change-arrow.down { color: #1f9a6e; }  
        .change-arrow.double {
            animation: arrow-pulse 0.6s ease-in-out;
        }

        
        .odds-history {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            color: #94a3b8;
            margin-top: 2px;
            font-variant-numeric: tabular-nums;
        }
        .odds-history .old-value {
            text-decoration: line-through;
            opacity: 0.7;
        }
        .odds-history .new-value {
            color: #475569;
            font-weight: 700;
        }

        
        .change-pct {
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 99px;
            margin-top: 2px;
        }
        .change-pct.up {
            background: #fde8e8;
            color: #c74e4e;
        }
        .change-pct.down {
            background: #e8f7ee;
            color: #1f9a6e;
        }

        
        @keyframes flash-down {
            0% { background-color: #bbf7d0; border-color: #1f9a6e; }
            100% { background-color: #e8f2fc; border-color: #c6d8ea; }
        }
        @keyframes flash-up {
            0% { background-color: #fecaca; border-color: #c74e4e; }
            100% { background-color: #e8f2fc; border-color: #c6d8ea; }
        }
        .flash-down { animation: flash-down 1s ease-out; }
        .flash-up { animation: flash-up 1s ease-out; }

        
        @keyframes arrow-pulse {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(3px); }
        }

        
        .sharp-change {
            position: absolute;
            top: 2px;
            right: 4px;
            font-size: 10px;
            color: #f59e0b;
            animation: bolt-flash 0.8s ease-in-out 3;
        }
        @keyframes bolt-flash {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        
        .sparkline-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 20px;
            width: 100%;
            margin-top: 4px;
            position: relative;
        }
        .sparkline {
            width: 100%;
            height: 20px;
            display: block;
        }
        .sparkline-path {
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .sparkline-path.up { stroke: #c74e4e; }
        .sparkline-path.down { stroke: #1f9a6e; }
        .sparkline-path.flat { stroke: #94a3b8; }

        
        .fav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }
        .fav-1 {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
        }
        .fav-2 {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }
        .fav-3 {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
        }
        .fav-none {
            background-color: #f3f4f6;
            color: #9ca3af;
            border: 1px dashed #d1d5db;
        }

        @keyframes badge-pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .badge-changed {
            animation: badge-pop 0.5s ease-out;
        }

        
        .pressure-container {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 100%;
        }
        .pressure-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .pressure-bar-bg {
            width: 100%;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
        }
        .pressure-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.5s ease;
        }
        .pressure-bar-fill.back { background: linear-gradient(90deg, #1f9a6e, #34d399); }
        .pressure-bar-fill.lay { background: linear-gradient(90deg, #c74e4e, #f87171); }
        .pressure-bar-fill.neutral { background: #9ca3af; }

        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #64748b;
        }
        .legend-arrow-up { color: #c74e4e; font-weight: 900; }
        .legend-arrow-down { color: #1f9a6e; font-weight: 900; }

        
        @media (max-width: 768px) {
            .runner-header { display: none; }
            .runner-row {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 12px;
                border-bottom: 1px solid #e2e8f0;
                background-color: #fafbfc;
                border-radius: 8px;
                margin-bottom: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .runner-row:hover { background-color: #fafbfc; }
            .runner-name-mobile {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                font-weight: 700;
                color: #0b2a40;
                font-size: 15px;
                margin-bottom: 4px;
            }
            .runner-odds-mobile {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .odd-box { min-height: 48px; font-size: 14px; }
            .odd-box::before {
                content: attr(data-label);
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #64748b;
                margin-bottom: 2px;
            }
            .pressure-container { grid-column: auto; margin-top: 4px; }
            .pressure-label { font-size: 10px; }
        }
        @media (max-width: 480px) {
            .runner-row { padding: 10px; }
            .runner-name-mobile { font-size: 13px; }
            .odd-box { font-size: 13px; min-height: 44px; padding: 4px 6px; }
            .odds-history { font-size: 9.5px; }
            .change-pct { font-size: 9px; }
        }
    
}
.dark #horseRaceDetail { .runner-header,.runner-row:hover {background:#203746;color:#e8edf2} .runner-row {background:#1a2a38;border-color:#2a3f50} .back-box {background:#25435c;color:#e8edf2;border-color:#3a5568} .lay-box {background:#4a2633;color:#e8edf2;border-color:#674052} .odds-history .new-value {color:#e8edf2} .runner-name-mobile,.runner-row {color:inherit} }
#horseRaceDetail .runner-row > * {min-width:0}
#horseRaceDetail .runner-name {overflow-wrap:anywhere}
#horseRaceDetail .sparkline {max-width:100px}
#horseRaceDetail .sparkline-path.flat {stroke-dasharray:2 2}
</style>
@endpush
@push('js')
    @vite('resources/users/js/sports/horseracing.js')
@endpush

@push('head')<style>@media(max-width:768px){#horseRaceDetail .horse-desktop{display:none}#horseRaceDetail .runner-name-mobile{display:flex}}@media(min-width:769px){#horseRaceDetail .runner-name-mobile{display:none}}</style>@endpush
