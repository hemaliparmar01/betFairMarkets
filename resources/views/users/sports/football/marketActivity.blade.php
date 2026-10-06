@extends('users.layout.main')

@section('title', request()->routeIs('football.pinnacle-odds') ? 'Pinnacle Odds · BF Markets' : 'Market Activity Terminal · BF Markets')

@section('content')
@if (request()->routeIs('football.pinnacle-odds'))
    <section id="pinnacleDashboard" class="my-5 min-w-0" data-source-url="{{ route('football.pinnacle-odds') }}">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-extrabold text-[#0b2a40] dark:text-[#e8edf2] max-[480px]:text-xl">
                    <i class="fas fa-arrow-trend-down text-[#dc4c64]"></i> {{ __('Pinnacle Odds') }}
                </h1>
                <p class="mt-1 text-sm text-[#5a7d99] dark:text-[#8aaccc]">{{ __('Realtime Pinnacle odds drops compared with Betfair prices') }}</p>
            </div>
            <span id="pinnacleConnection" class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">
                <span class="size-2 rounded-full bg-current"></span><span data-status-text>{{ __('Connecting') }}</span>
            </span>
        </div>

        <div class="mb-4 flex gap-2 overflow-x-auto pb-2 [scrollbar-width:thin]" data-sport-filters>
            @foreach ([['all','🎯','All'],['soccer','⚽','Soccer'],['tennis','🎾','Tennis'],['basketball','🏀','Basketball'],['ice-hockey','🏒','Ice Hockey'],['baseball','⚾','Baseball'],['cricket','🏏','Cricket'],['esports','🎮','Esports']] as [$value, $icon, $label])
                <button type="button" data-sport="{{ $value }}" class="pinnacle-sport {{ $value === 'all' ? 'border-[#0b2a40] bg-[#0b2a40] text-white' : 'border-[#d4e0ec] bg-white text-[#2a4d66] dark:border-[#3a5568] dark:bg-[#1f3444] dark:text-[#b0c8dd]' }} inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-bold">
                    <span>{{ $icon }} {{ __($label) }}</span><span class="rounded-full bg-black/10 px-1.5 py-0.5 text-[10px]" data-count>0</span>
                </button>
            @endforeach
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#dbe7f2] bg-[#f8fbfe] p-3 dark:border-[#2a3f50] dark:bg-[#1f3444]">
            <div class="flex rounded-lg border border-[#d4e0ec] bg-white p-1 dark:border-[#3a5568] dark:bg-[#1a2a38]" data-mode-switch>
                <button type="button" data-mode="prematch" class="rounded-md bg-[#0b2a40] px-3 py-1.5 text-xs font-bold text-white">📋 {{ __('Prematch') }}</button>
                <button type="button" data-mode="live" class="rounded-md px-3 py-1.5 text-xs font-bold text-[#5a7d99]">🔴 {{ __('Live') }}</button>
            </div>
            <div class="flex rounded-lg border border-[#d4e0ec] bg-white p-1 dark:border-[#3a5568] dark:bg-[#1a2a38]" data-view-switch>
                <button type="button" data-view="simple" class="rounded-md bg-[#1a6b9c] px-3 py-1.5 text-xs font-bold text-white">{{ __('Simple') }}</button>
                <button type="button" data-view="advanced" class="rounded-md px-3 py-1.5 text-xs font-bold text-[#5a7d99]">{{ __('Advanced') }}</button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-[#dbe7f2] bg-white dark:border-[#2a3f50] dark:bg-[#1a2a38]">
            <div class="flex flex-wrap items-center justify-between gap-3 bg-[#0b2a40] px-4 py-3 text-white">
                <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide"><span class="size-2 rounded-full bg-[#ff647c]"></span>{{ __('Pinnacle Drops') }}</h2>
                <span class="text-xs font-semibold text-white/70" data-alert-meta>{{ __('Prematch') }} · ODDS-DROP · ≥5%</span>
            </div>
            <div class="overflow-x-auto" data-alerts></div>
        </div>
    </section>

    <div id="pinnacleModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="pinnacleModalTitle">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl dark:bg-[#1a2a38]" data-modal-panel></div>
    </div>

    @push('js')
        <script>
            (() => {
                const root = document.getElementById('pinnacleDashboard');
                const modal = document.getElementById('pinnacleModal');
                if (!root || !modal) return;

                const state = { alerts: @json($pinnacleAlerts ?? []), sport: 'all', mode: 'prematch', view: 'simple', connected: @json($pinnacleFeedConnected ?? false) };
                const sportKey = value => { const key = String(value || '').toLowerCase().trim().replace(/\s+/g, '-'); return key === 'football' ? 'soccer' : key; };
                const number = value => value === null || value === undefined || value === '' ? null : Number(value);
                const price = value => Number.isFinite(number(value)) ? number(value).toFixed(2) : '—';
                const escapeHtml = value => { const el = document.createElement('div'); el.textContent = String(value ?? ''); return el.innerHTML; };
                const drop = alert => { const from = number(alert.from_price); const to = number(alert.to_price); return from > 0 && to > 0 ? ((from - to) / from) * 100 : 0; };
                const pinnacleEdge = alert => { const current = number(alert.to_price); const nvp = number(alert.nvp); return current > 0 && nvp > 0 ? ((current / nvp) - 1) * 100 : null; };
                const edge = alert => { const bf = number(alert.bf_back); const nvp = number(alert.nvp); return bf > 0 && nvp > 0 ? ((bf / nvp) - 1) * 100 : null; };
                const edgeText = value => value === null ? 'N/A' : Math.abs(value) > 50 ? 'Low Liquidity' : `${value > 0 ? '+' : ''}${value.toFixed(1)}%`;
                const edgeClass = value => value === null || Math.abs(value) > 50 ? 'text-slate-400 italic' : value >= 5 ? 'text-emerald-600' : value >= 3 ? 'text-green-500' : value >= 0 ? 'text-amber-600' : 'text-slate-500';
                const isLive = alert => alert.is_live === true || alert.is_live === 1 || alert.is_live === '1' || String(alert.status || '').toLowerCase() === 'live';
                const alertTime = value => { const raw = number(value); if (!raw) return '—'; const time = raw < 100000000000 ? raw * 1000 : raw; const seconds = Math.max(0, Math.floor((Date.now() - time) / 1000)); return seconds < 60 ? `${seconds}s ago` : seconds < 3600 ? `${Math.floor(seconds / 60)}m ago` : `${Math.floor(seconds / 3600)}h ago`; };

                const filtered = () => state.alerts.filter(alert => drop(alert) >= 5 && (state.sport === 'all' || sportKey(alert.sport) === state.sport) && (state.mode === 'live' ? isLive(alert) : !isLive(alert))).sort((a, b) => (number(b.alerted) || 0) - (number(a.alerted) || 0));
                const render = () => {
                    root.querySelectorAll('[data-sport]').forEach(button => {
                        const count = state.alerts.filter(alert => drop(alert) >= 5 && (button.dataset.sport === 'all' || sportKey(alert.sport) === button.dataset.sport) && (state.mode === 'live' ? isLive(alert) : !isLive(alert))).length;
                        button.querySelector('[data-count]').textContent = count;
                    });
                    root.querySelector('[data-alert-meta]').textContent = `${state.mode === 'live' ? 'Live' : 'Prematch'} · ODDS-DROP · ≥5%`;

                    const groups = new Map();
                    filtered().forEach(alert => {
                        const key = `${alert.home ?? ''}|${alert.away ?? ''}|${alert.league ?? ''}`;
                        if (!groups.has(key)) groups.set(key, []);
                        groups.get(key).push(alert);
                    });

                    if (!groups.size) {
                        root.querySelector('[data-alerts]').innerHTML = `<div class="px-5 py-14 text-center text-sm font-semibold text-[#8aaccc]"><div class="mb-2 text-4xl opacity-50">🔍</div>{{ __('No alerts match the current filters.') }}</div>`;
                        return;
                    }

                    const header = state.view === 'simple' ? `<div class="grid min-w-[760px] grid-cols-[80px_minmax(240px,1fr)_130px_100px_100px_110px] gap-3 border-b border-[#dbe7f2] bg-[#f5f9fc] px-4 py-2 text-[10px] font-extrabold uppercase tracking-wide text-[#6a8aaa] dark:border-[#2a3f50] dark:bg-[#1f3444]"><span>Drop</span><span>Match · Market</span><span class="text-center">Pinnacle Odds</span><span class="text-center">NVP</span><span class="text-center">Betfair</span><span class="text-center">Edge %</span></div>` : '';
                    const html = [...groups.values()].map((alerts, groupIndex) => {
                        const first = alerts[0];
                        const rows = alerts.map(alert => {
                            const alertIndex = state.alerts.indexOf(alert);
                            const dropValue = drop(alert);
                            const pinnacleEdgeValue = pinnacleEdge(alert);
                            const edgeValue = edge(alert);
                            if (state.view === 'advanced') return `<button type="button" data-alert-index="${alertIndex}" class="grid w-full min-w-[520px] grid-cols-[80px_1fr] gap-4 border-t border-[#e6eff8] px-5 py-4 text-left hover:bg-[#f8fbfe] dark:border-[#2a3f50] dark:hover:bg-[#1f3444]"><span class="text-right text-base font-black text-[#dc4c64]">−${Math.abs(dropValue).toFixed(1)}%</span><span class="min-w-0"><strong class="block text-sm text-[#12324a] dark:text-[#e8edf2]">${escapeHtml(alert.sect || 'Market')} · ${escapeHtml(alert.outcome || '')}</strong><span class="mt-1 block text-xs text-[#6a8aaa]">Pinnacle ${price(alert.from_price)} → <b class="text-[#dc4c64]">${price(alert.to_price)}</b> · NVP ${price(alert.nvp)} · Pinnacle Edge <b class="${edgeClass(pinnacleEdgeValue)}">${edgeText(pinnacleEdgeValue)}</b> · Betfair Back ${price(alert.bf_back)} / Lay ${price(alert.bf_lay)} · Sizes ${price(alert.bf_back_size)} / ${price(alert.bf_lay_size)} · Betfair Edge <b class="${edgeClass(edgeValue)}">${edgeText(edgeValue)}</b> · ${alertTime(alert.alerted)}</span></span></button>`;
                            return `<button type="button" data-alert-index="${alertIndex}" class="grid w-full min-w-[760px] grid-cols-[80px_minmax(240px,1fr)_130px_100px_100px_110px] items-center gap-3 border-t border-[#e6eff8] px-4 py-3 text-left hover:bg-[#f8fbfe] dark:border-[#2a3f50] dark:hover:bg-[#1f3444]"><span class="text-right text-sm font-black text-[#dc4c64]">−${Math.abs(dropValue).toFixed(1)}%</span><span class="min-w-0"><strong class="block truncate text-sm text-[#12324a] dark:text-[#e8edf2]">${escapeHtml(alert.sect || 'Market')} · ${escapeHtml(alert.outcome || '')}</strong><small class="text-[10px] font-bold uppercase text-[#6a8aaa]">${isLive(alert) ? 'LIVE' : 'PREMATCH'} · ${escapeHtml(alert.sport || '')}</small></span><span class="text-center text-xs font-semibold"><s class="text-slate-400">${price(alert.from_price)}</s> → <b class="text-[#dc4c64]">${price(alert.to_price)}</b></span><span class="text-center text-xs font-bold text-[#dc4c64]">${price(alert.nvp)}</span><span class="text-center text-xs font-extrabold text-[#1a6b9c]">${price(alert.bf_back)}</span><span class="text-center text-xs font-extrabold ${edgeClass(edgeValue)}">${edgeText(edgeValue)}</span></button>`;
                        }).join('');
                        return `<article class="m-2 overflow-hidden rounded-xl border border-[#dbe7f2] dark:border-[#2a3f50]"><button type="button" data-group="${groupIndex}" class="flex w-full items-center justify-between gap-3 bg-[#f5f9fc] px-4 py-3 text-left dark:bg-[#1f3444]"><span class="min-w-0"><strong class="block truncate text-sm text-[#12324a] dark:text-[#e8edf2]">${escapeHtml(first.home)} v ${escapeHtml(first.away)}</strong><small class="text-[10px] font-bold uppercase tracking-wide text-[#6a8aaa]">${isLive(first) ? 'LIVE' : 'PREMATCH'} · ${escapeHtml(first.sport)} · ${escapeHtml(first.league)}</small></span><span class="shrink-0 text-xs font-bold text-[#6a8aaa]">${alerts.length} alert${alerts.length === 1 ? '' : 's'} <i class="fas fa-chevron-down ml-1"></i></span></button><div data-group-body="${groupIndex}">${rows}</div></article>`;
                    }).join('');
                    root.querySelector('[data-alerts]').innerHTML = header + html;
                };

                const openModal = alert => {
                    const dropValue = drop(alert); const pinnacleEdgeValue = pinnacleEdge(alert); const edgeValue = edge(alert);
                    const recent = state.alerts.filter(item => item.home === alert.home && item.away === alert.away).sort((a, b) => (number(b.alerted) || 0) - (number(a.alerted) || 0)).slice(0, 5);
                    modal.querySelector('[data-modal-panel]').innerHTML = `<div class="flex items-start justify-between gap-4"><div><h2 id="pinnacleModalTitle" class="text-xl font-extrabold text-[#12324a] dark:text-[#e8edf2]">${escapeHtml(alert.home)} v ${escapeHtml(alert.away)}</h2><p class="mt-1 text-xs font-bold uppercase text-[#6a8aaa]">${isLive(alert) ? 'LIVE' : 'PREMATCH'} · ${escapeHtml(alert.sport)} · ${escapeHtml(alert.league)}</p></div><button type="button" data-close-modal class="flex size-9 shrink-0 items-center justify-center rounded-full border border-[#d4e0ec] text-[#5a7d99]">✕</button></div><div class="mt-5 grid gap-3 rounded-xl border border-[#dbe7f2] bg-[#f8fbfe] p-4 sm:grid-cols-2 dark:border-[#2a3f50] dark:bg-[#1f3444]"><div class="text-3xl font-black text-[#dc4c64]">−${Math.abs(dropValue).toFixed(1)}%</div><div class="grid grid-cols-2 gap-3 text-xs"><span>Pinnacle<br><b>${price(alert.from_price)} → ${price(alert.to_price)}</b></span><span>NVP<br><b>${price(alert.nvp)}</b></span><span>Pinnacle Edge<br><b class="${edgeClass(pinnacleEdgeValue)}">${edgeText(pinnacleEdgeValue)}</b></span><span>Betfair Back / Lay<br><b class="text-[#1a6b9c]">${price(alert.bf_back)} / ${price(alert.bf_lay)}</b></span><span>Betfair Sizes<br><b>${price(alert.bf_back_size)} / ${price(alert.bf_lay_size)}</b></span><span>Betfair Edge<br><b class="${edgeClass(edgeValue)}">${edgeText(edgeValue)}</b></span></div></div><h3 class="mb-2 mt-5 text-xs font-extrabold uppercase tracking-wide text-[#6a8aaa]">Full Market</h3><div class="overflow-x-auto"><table class="w-full min-w-[520px] text-sm"><thead class="border-b border-[#dbe7f2] text-left text-[10px] uppercase text-[#6a8aaa]"><tr><th class="p-2">Outcome</th><th class="p-2 text-right">Pinnacle</th><th class="p-2 text-right">NVP</th><th class="p-2 text-right">Betfair</th><th class="p-2 text-right">Edge</th></tr></thead><tbody><tr class="border-b border-[#e6eff8]"><td class="p-2 font-bold">${escapeHtml(alert.outcome || '—')}</td><td class="p-2 text-right font-bold text-[#dc4c64]">${price(alert.to_price)}</td><td class="p-2 text-right">${price(alert.nvp)}</td><td class="p-2 text-right font-bold text-[#1a6b9c]">${price(alert.bf_back)}</td><td class="p-2 text-right font-bold ${edgeClass(edgeValue)}">${edgeText(edgeValue)}</td></tr></tbody></table></div><h3 class="mb-2 mt-5 text-xs font-extrabold uppercase tracking-wide text-[#6a8aaa]">Recent Alerts</h3><div class="divide-y divide-[#e6eff8]">${recent.map(item => `<div class="flex items-center justify-between gap-3 py-2 text-xs"><span class="text-[#6a8aaa]">${alertTime(item.alerted)}</span><span class="flex-1">${escapeHtml(item.sect)} · ${price(item.from_price)} → ${price(item.to_price)}</span><b class="${edgeClass(edge(item))}">${edgeText(edge(item))}</b></div>`).join('')}</div>`;
                    modal.classList.remove('hidden'); modal.classList.add('flex');
                };

                root.addEventListener('click', event => {
                    const sportButton = event.target.closest('[data-sport]');
                    const switchButton = event.target.closest('[data-mode], [data-view]');
                    const groupButton = event.target.closest('[data-group]');
                    const alertButton = event.target.closest('[data-alert-index]');
                    if (sportButton) { state.sport = sportButton.dataset.sport; root.querySelectorAll('[data-sport]').forEach(button => { const active = button === sportButton; button.classList.toggle('border-[#0b2a40]', active); button.classList.toggle('bg-[#0b2a40]', active); button.classList.toggle('text-white', active); button.classList.toggle('border-[#d4e0ec]', !active); button.classList.toggle('bg-white', !active); button.classList.toggle('text-[#2a4d66]', !active); }); render(); }
                    if (switchButton) { const key = switchButton.dataset.mode ? 'mode' : 'view'; state[key] = switchButton.dataset[key]; switchButton.parentElement.querySelectorAll('button').forEach(button => { button.classList.remove('bg-[#0b2a40]','bg-[#1a6b9c]','text-white'); button.classList.add('text-[#5a7d99]'); }); switchButton.classList.remove('text-[#5a7d99]'); switchButton.classList.add(key === 'mode' ? 'bg-[#0b2a40]' : 'bg-[#1a6b9c]','text-white'); render(); }
                    if (groupButton) { const body = root.querySelector(`[data-group-body="${groupButton.dataset.group}"]`); body?.classList.toggle('hidden'); groupButton.querySelector('i')?.classList.toggle('-rotate-90'); }
                    if (alertButton) openModal(state.alerts[Number(alertButton.dataset.alertIndex)]);
                });
                modal.addEventListener('click', event => { if (event.target === modal || event.target.closest('[data-close-modal]')) { modal.classList.add('hidden'); modal.classList.remove('flex'); } });
                document.addEventListener('keydown', event => { if (event.key === 'Escape') { modal.classList.add('hidden'); modal.classList.remove('flex'); } });

                const status = root.querySelector('#pinnacleConnection');
                const refresh = async () => {
                    try {
                        const response = await fetch(root.dataset.sourceUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        const payload = await response.json();
                        state.alerts = payload.data ?? [];
                        state.connected = payload.connected === true;
                        status.className = state.connected ? 'inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700' : 'inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700';
                        status.querySelector('[data-status-text]').textContent = state.connected ? 'Live' : 'Disconnected';
                        render();
                    } catch (error) {
                        status.className = 'inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700';
                        status.querySelector('[data-status-text]').textContent = 'Disconnected';
                    }
                };

                status.className = state.connected ? 'inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700' : 'inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700';
                status.querySelector('[data-status-text]').textContent = state.connected ? 'Live' : 'Disconnected';
                render();
                window.setInterval(refresh, 3000);
            })();
        </script>
    @endpush
@else
@php
    $marketTags = [
        'match_odds' => [
            'label' => 'Match Odds',
            'cls' => 'mo',
        ],
        'ou_0_5' => [
            'label' => 'O/U 0.5',
            'cls' => 'ou',
        ],
        'ou_1_5' => [
            'label' => 'O/U 1.5',
            'cls' => 'ou',
        ],
        'ou_2_5' => [
            'label' => 'O/U 2.5',
            'cls' => 'ou',
        ],
        'ou_3_5' => [
            'label' => 'O/U 3.5',
            'cls' => 'ou',
        ],
        'ou_4_5' => [
            'label' => 'O/U 4.5',
            'cls' => 'ou',
        ],
        'btts' => [
            'label' => 'BTTS',
            'cls' => 'btts',
        ],
        'first_half' => [
            'label' => 'First Half',
            'cls' => 'fh',
        ],
        'fh_ou_0_5' => [
            'label' => 'FH O/U 0.5',
            'cls' => 'fh',
        ],
        'fh_ou_1_5' => [
            'label' => 'FH O/U 1.5',
            'cls' => 'fh',
        ],
        'correct_score' => [
            'label' => 'Correct Score',
            'cls' => 'cs',
        ],
        'win_market' => [
            'label' => 'Win Market',
            'cls' => 'win',
        ],
    ];
    $speedOptions = [
        ['value' => 140, 'label' => '⚡ Pro 140-177ms', 'pro' => true],
        ['value' => 1000, 'label' => '1s'],
        ['value' => 2000, 'label' => '2s'],
        ['value' => 3000, 'label' => '3s'],
        ['value' => 5000, 'label' => '5s'],
        ['value' => 8000, 'label' => '8s'],
        ['value' => 10000, 'label' => '10s'],
    ];
    $marketOptions = [
        ['value' => 'active', 'label' => 'Active Markets'],
        ['value' => 'match_odds', 'label' => 'Match Odds'],
        ['value' => 'ou_0_5', 'label' => 'Over/Under 0.5'],
        ['value' => 'ou_1_5', 'label' => 'Over/Under 1.5'],
        ['value' => 'ou_2_5', 'label' => 'Over/Under 2.5'],
        ['value' => 'ou_3_5', 'label' => 'Over/Under 3.5'],
        ['value' => 'ou_4_5', 'label' => 'Over/Under 4.5'],
        ['value' => 'btts', 'label' => 'BTTS (Yes/No)'],
        ['value' => 'first_half', 'label' => 'First Half'],
        ['value' => 'fh_ou_0_5', 'label' => 'First Half O/U 0.5'],
        ['value' => 'fh_ou_1_5', 'label' => 'First Half O/U 1.5'],
    ];
@endphp

    <div class="page-header my-[18px] flex min-w-0 flex-wrap items-center justify-between gap-3 [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px] max-[480px]:items-start">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-bolt"></i> {{ __('Market Activity') }}</h1>
        <div class="header-indicators flex flex-wrap items-center gap-2 [gap:8px] max-[480px]:w-full">
            <button type="button"
                class="collapse-toggle inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold   [gap:6px] [padding:6px_14px] [border-radius:30px] [font-size:12.5px]  [background:#f0f6fc] [border:1px_solid_#d4e0ec] [color:#1f4b66] cursor-pointer [transition:0.2s] select-none [font-family:inherit] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#b0c8dd] [&:hover]:[border-color:#1a6b9c] [&:hover]:[color:#1a6b9c] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[color:#6aafdf] [&_i]:[transition:transform_0.3s]"
                id="collapseToggle" title="Collapse/expand filters">
                <i class="fas fa-chevron-up"></i>
                <span>{{ __('Filters') }}</span>
            </button>
            <span class="live-indicator inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold   [gap:6px] [padding:4px_12px] [border-radius:30px] [background:#e8f7ee] [border:1px_solid_#c6e8d3] [color:#1f9a6e] [font-size:12px]  dark:[background:#1a3a2a] dark:[border-color:#2a5a3e] dark:[color:#5ab88a]">
                <span class="live-dot [width:8px] [height:8px] [border-radius:50%] [background:#1f9a6e] [animation:pulse_1.6s_infinite] dark:[background:#5ab88a]"></span>
                {{ __('Live') }}
            </span>
            <span class="feed-indicator inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold   [gap:6px] [padding:4px_12px] [border-radius:30px] [background:#f0f6fc] [border:1px_solid_#d4e0ec] [font-size:11.5px]  [color:#5a7d99] [font-variant-numeric:tabular-nums] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#8aaccc] [&_.feed-dot]:[width:6px] [&_.feed-dot]:[height:6px] [&_.feed-dot]:[border-radius:50%] [&_.feed-dot]:[background:#1f9a6e] [&_.feed-dot]:[animation:pulse_1s_infinite] dark:[&_.feed-dot]:[background:#5ab88a]">
                <span class="feed-dot"></span>
                {{ __('WS Feed') }} · <span class="feed-latency [color:#1a6b9c] dark:[color:#6aafdf]" id="latencyDisplay">154</span> {{ __('ms') }}
            </span>
        </div>
    </div>

    <!-- COLLAPSIBLE CONTROLS WRAPPER -->
    <div class="controls-wrapper overflow-hidden  [transition:max-height_0.35s_ease,_opacity_0.25s_ease] [max-height:800px] opacity-100" id="controlsWrapper">

        <!-- SPEED SELECTOR -->
        <div class="speed-bar flex flex-wrap items-center gap-2 border-b py-3    [gap:8px] [padding:10px_0_12px] [border-bottom:1px_solid_#e6edf6] [margin-bottom:12px] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50] max-[768px]:[gap:6px] max-[768px]:flex-nowrap max-[768px]:overflow-x-auto max-[768px]:pb-2 max-[768px]:[&_.speed-label]:shrink-0 max-[768px]:[&_.speed-pill]:shrink-0" id="speedBar">
            <span class="speed-label inline-flex items-center [gap:6px] [font-size:12px] font-bold [color:#5a7d99] uppercase [letter-spacing:0.4px] [padding-right:6px] dark:[color:#8aaccc] [&_i]:[color:#1a6b9c] [&_i]:[font-size:13px] dark:[&_i]:[color:#6aafdf]"><i class="fas fa-tachometer-alt"></i> {{ __('Speed:') }}</span>
            @foreach ($speedOptions as $speed)
                <button type="button" class="speed-pill {{ ($speed['pro'] ?? false) ? 'pro' : '' }} {{ $speed['value'] === 3000 ? 'active' : '' }} inline-flex items-center [gap:5px] [padding:6px_13px] [border-radius:20px] [font-size:12px] font-bold [background:#f0f6fc] [border:1px_solid_#d4e0ec] [color:#5a7d99] cursor-pointer [transition:0.2s] select-none [font-variant-numeric:tabular-nums] [font-family:inherit] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#8aaccc] [&:hover]:[border-color:#1a6b9c] [&:hover]:[color:#1a6b9c] [&:hover]:[transform:translateY(-1px)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[color:#6aafdf] [&.active]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&.active]:[border-color:#1a6b9c] [&.active]:[color:white] [&.active]:[box-shadow:0_4px_12px_rgba(26,_107,_156,_0.25)] dark:[&.active]:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] dark:[&.active]:[border-color:#4a8ab5] [&.pro]:[border-color:#e6b422] [&.pro]:[color:#b8860b] [&.pro]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] dark:[&.pro]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.pro]:[border-color:#e6b422] dark:[&.pro]:[color:#e6b422] [&.pro.active]:[background:linear-gradient(135deg,_#b8860b,_#e6b422)] [&.pro.active]:[border-color:#e6b422] [&.pro.active]:[color:white] [&.pro.active]:[box-shadow:0_4px_12px_rgba(230,_180,_34,_0.3)] max-[768px]:[padding:5px_10px] max-[768px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[font-size:10px]" data-speed="{{ $speed['value'] }}">{{ $speed['label'] }}</button>
            @endforeach
        </div>

        @include('users.layout.sports')

        @include('users.sports.football.football_filter')

        <!-- MARKET CONTAINER (football only) -->
        <div class="market-container flex flex-wrap items-center gap-2.5 border-b py-3   [gap:10px] [padding:10px_0_14px] [border-bottom:1px_solid_#e6edf6] [margin-bottom:14px] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50] [&[hidden]]:[display:none] max-[768px]:[gap:6px] max-[480px]:items-stretch max-[480px]:[&_.market-container-label]:w-full max-[480px]:[&_.market-select]:w-full max-[480px]:[&_.market-select]:min-w-0" id="marketContainer">
            <span class="market-container-label inline-flex items-center [gap:6px] [font-size:12px] font-bold [color:#5a7d99] uppercase [letter-spacing:0.4px] dark:[color:#8aaccc] [&_i]:[color:#1a6b9c] [&_i]:[font-size:13px] dark:[&_i]:[color:#6aafdf]"><i class="fas fa-filter"></i> {{ __('Market:') }}</span>
            <select class="market-select [appearance:none] [-webkit-appearance:none] [background-size:14px] [border:1px_solid_#d4e0ec] [border-radius:30px] [padding:8px_38px_8px_16px] [font-size:13px] [font-weight:700] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [min-width:200px] [&:hover]:[border-color:#1a6b9c] [&:focus]:[outline:none] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[background-color:#1f3444] dark:[border-color:#3a5568] dark:[color:#b0c8dd] max-[768px]:[font-size:12px] max-[768px]:[min-width:170px] max-[768px]:[padding:7px_34px_7px_14px]" id="marketSelect">
                @foreach ($marketOptions as $marketOption)
                    <option value="{{ $marketOption['value'] }}">{{ $marketOption['label'] }}</option>
                @endforeach
            </select>
        </div>

    </div>

    <div class="terminal grid min-w-0 gap-4 [grid-template-columns:1fr] [gap:16px] [transition:grid-template-columns_0.35s_ease] [&.with-panel]:[grid-template-columns:1fr_460px] [&.panel-expanded]:[grid-template-columns:1fr_0px] [&.panel-expanded_.detail-panel]:[position:fixed] [&.panel-expanded_.detail-panel]:[top:0] [&.panel-expanded_.detail-panel]:[left:0] [&.panel-expanded_.detail-panel]:[right:0] [&.panel-expanded_.detail-panel]:[bottom:0] [&.panel-expanded_.detail-panel]:[width:100vw] [&.panel-expanded_.detail-panel]:[height:100vh] [&.panel-expanded_.detail-panel]:[z-index:99999] [&.panel-expanded_.detail-panel]:[border-radius:0] [&.panel-expanded_.detail-panel]:[overflow-y:auto] [&.panel-expanded_.detail-panel]:[animation:expandIn_0.3s_ease] max-[1200px]:[&.with-panel]:[grid-template-columns:1fr] [&.panel-expanded_.detail-body]:[max-width:1200px] [&.panel-expanded_.detail-body]:[margin:0_auto] [&.panel-expanded_.detail-body]:[padding:24px_32px] max-[1024px]:[&.with-panel]:[grid-template-columns:1fr] max-[480px]:[&.panel-expanded_.detail-body]:[padding:16px]" id="terminal">
        <div class="events-table-wrap min-w-0 overflow-x-auto rounded-[14px] [border-radius:14px] [border:1px_solid_#e6eff8] [transition:border-color_0.3s] dark:[border-color:#2a3f50] max-[768px]:overflow-visible max-[768px]:border-0 max-[768px]:[&_.events-table]:!min-w-0 max-[768px]:[&_.events-table]:block max-[768px]:[&_thead]:hidden max-[768px]:[&_tbody]:grid max-[768px]:[&_tbody]:gap-3 max-[768px]:[&_tbody]:bg-transparent max-[768px]:[&_tbody_tr]:grid max-[768px]:[&_tbody_tr]:grid-cols-2 max-[768px]:[&_tbody_tr]:overflow-hidden max-[768px]:[&_tbody_tr]:rounded-xl max-[768px]:[&_tbody_tr]:border max-[768px]:[&_tbody_tr]:border-[#dce7f1] max-[768px]:[&_tbody_tr]:bg-white max-[768px]:dark:[&_tbody_tr]:border-[#2a3f50] max-[768px]:dark:[&_tbody_tr]:bg-[#1f3444] max-[768px]:[&_tbody_td]:flex max-[768px]:[&_tbody_td]:min-w-0 max-[768px]:[&_tbody_td]:items-center max-[768px]:[&_tbody_td]:justify-between max-[768px]:[&_tbody_td]:gap-2 max-[768px]:[&_tbody_td]:whitespace-normal max-[768px]:[&_tbody_td]:border-b max-[768px]:[&_tbody_td]:border-[#eef2f8] max-[768px]:[&_tbody_td]:px-3! max-[768px]:[&_tbody_td]:py-2! max-[768px]:[&_tbody_td]:before:shrink-0 max-[768px]:[&_tbody_td]:before:text-[10px] max-[768px]:[&_tbody_td]:before:font-bold max-[768px]:[&_tbody_td]:before:uppercase max-[768px]:[&_tbody_td]:before:tracking-wide max-[768px]:[&_tbody_td]:before:text-[#8aaccc] max-[768px]:[&_tbody_td]:before:content-[attr(data-label)] max-[768px]:[&_tbody_td.col-match]:col-span-2 max-[768px]:[&_tbody_td.col-match]:text-right max-[768px]:[&_tbody_td.col-odds]:text-right max-[768px]:[&_tbody_td:last-child]:col-span-2 max-[480px]:[&_tbody_tr]:grid-cols-1 max-[480px]:[&_tbody_td.col-match]:col-span-1 max-[480px]:[&_tbody_td:last-child]:col-span-1" id="eventsWrap">
            <table class="events-table table-fixed w-full min-w-[1800px] border-separate text-[13px] [border-collapse:separate] [border-spacing:0] [font-size:13px] bg-white [transition:background_0.3s] dark:[background:#1f3444] [&_thead]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&_thead]:[color:white] [&_thead]:[position:sticky] [&_thead]:[top:0] [&_thead]:[z-index:5] [&_thead_th]:[padding:10px_12px] [&_thead_th]:[text-align:left] [&_thead_th]:[font-weight:700] [&_thead_th]:[font-size:11px] [&_thead_th]:[letter-spacing:0.5px] [&_thead_th]:[text-transform:uppercase] [&_thead_th]:[white-space:nowrap] [&_tbody_td]:[padding:10px_12px] [&_tbody_td]:[border-bottom:1px_solid_#eef2f8] [&_tbody_td]:[color:#2a4d66] [&_tbody_td]:[white-space:nowrap] [&_tbody_td]:[transition:color_0.3s,_border-color_0.3s] dark:[&_tbody_td]:[border-bottom-color:#2a3f50] dark:[&_tbody_td]:[color:#b0c8dd] [&_tbody_tr]:[cursor:pointer] [&_tbody_tr]:[transition:background_0.2s] [&_tbody_tr:hover]:[background:#f8fbfe] dark:[&_tbody_tr:hover]:[background:#1a2e44] [&_tbody_tr.selected]:[background:#e8f2fc] dark:[&_tbody_tr.selected]:[background:#1a2e44] [&_td.col-odds]:[text-align:center] [&_td.col-odds]:[padding:6px_4px]! max-[768px]:[font-size:12px] max-[768px]:[min-width:1000px] max-[768px]:[&_thead_th]:[padding:8px_10px] max-[768px]:[&_tbody_td]:[padding:8px_10px]">
                <colgroup>
                    <col class="w-[125px]"><col class="w-[220px]"><col class="w-[280px]"><col class="w-[145px]">
                    <col class="w-[215px]"><col class="w-[85px]"><col class="w-[95px]"><col class="w-[85px]">
                    <col class="w-[95px]"><col class="w-[115px]"><col class="w-[90px]"><col class="w-[105px]">
                    <col class="w-[90px]"><col class="w-[115px]">
                </colgroup>
                <thead>
                    <tr>
                        <th>{{ __('TIME')}}</th>
                        <th>{{ __('COMP')}}</th>
                        <th>{{ __('MATCH')}}</th>
                        <th>{{ __('MARKET')}}</th>
                        <th>{{ __('FAV')}}</th>
                        <th>{{ __('BACK')}}</th>
                        <th>{{ __('SIZE')}}</th>
                        <th>{{ __('LAY')}}</th>
                        <th>{{ __('SIZE')}}</th>
                        <th>{{ __('BAL')}}</th>
                        <th>{{ __('MOM')}}</th>
                        <th>{{ __('Δ')}}</th>
                        <th>{{ __('VOL')}}</th>
                        <th>{{ __('ACTION')}}</th>
                    </tr>
                </thead>
                <tbody id="eventsBody">
                    @foreach ($marketActivites as $event)
                        @php
                            $favorite = $event['runners'][$event['favIdx']];
                            $marketTag = $marketTags[$event['market']] ?? ['label' => $event['market'], 'cls' => 'mo'];
                            $balanceClass = ['back' => 'ind-back-heavy', 'lay' => 'ind-lay-heavy', 'balanced' => 'ind-balanced'][$event['balance']] ?? 'ind-balanced';
                            $balanceLabel = ['back' => 'Back ↑', 'lay' => 'Lay ↓', 'balanced' => 'Balanced →'][$event['balance']] ?? 'Balanced →';
                            $momentumClass = ['strong_back' => 'ind-mom-strong-back', 'back' => 'ind-mom-back', 'strong_lay' => 'ind-mom-strong-lay', 'lay' => 'ind-mom-lay', 'neutral' => 'ind-mom-neutral'][$event['momentum']] ?? 'ind-mom-neutral';
                            $momentumLabel = ['strong_back' => '↑↑', 'back' => '↑', 'strong_lay' => '↓↓', 'lay' => '↓', 'neutral' => '→'][$event['momentum']] ?? '→';
                            $volatilityClass = ['low' => 'ind-vol-low', 'medium' => 'ind-vol-med', 'high' => 'ind-vol-high'][$event['volatility']] ?? 'ind-vol-med';
                            $volatilityLabel = ['low' => 'Low', 'medium' => 'Med', 'high' => 'High'][$event['volatility']] ?? 'Med';
                        @endphp
                        <tr data-id="{{ $event['id'] }}">
                            <td data-label="{{ __('Time') }}" class="col-time font-bold [color:#0b2a40] dark:[color:#e8edf2] [&_.status-live]:[color:#c74e4e] [&_.status-live]:[font-weight:800] [&_.status-live]:[margin-right:4px] dark:[&_.status-live]:[color:#e08080] [&_.status-up]:[color:#1a6b9c] [&_.status-up]:[font-weight:700] [&_.status-up]:[margin-right:4px] dark:[&_.status-up]:[color:#6aafdf]">
                                @if ($event['status'] === 'LIVE')<span class="status-live">● LIVE</span>@else<span class="status-up">▲ UP</span>@endif
                                @if ($event['status'] === 'LIVE' && str_ends_with((string) $event['time'], "'"))
                                    {{ substr((string) $event['time'], 0, -1) }}<span class="minute-tick-blink">'</span>
                                @else
                                    {{ $event['time'] }}
                                @endif
                            </td>
                            <td data-label="{{ __('Competition') }}" class="col-comp [font-size:11.5px] [color:#8aaccc] dark:[color:#5a7d99]">{{ $event['sportIcon'] }} {{ $event['comp'] }}</td>
                            <td data-label="{{ __('Match') }}" class="col-match font-semibold [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[margin-left:4px] [&_.fav-star]:[font-size:12px]">{{ $event['match'] }}@if (in_array($event['sport'], ['horseracing', 'greyhounds'], true)) <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>@endif</td>
                            <td data-label="{{ __('Market') }}" class="col-market font-semibold [font-size:12px]"><span class="market-tag {{ $marketTag['cls'] }} inline-flex items-center [gap:4px] [padding:3px_10px] [border-radius:20px] [font-size:11px] font-bold whitespace-nowrap [&.mo]:[background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [&.mo]:[color:#1a6b9c] [&.mo]:[border:1px_solid_#b8d4ec] dark:[&.mo]:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[&.mo]:[color:#6aafdf] dark:[&.mo]:[border-color:#2a4a5e] [&.ou]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.ou]:[color:#b8860b] [&.ou]:[border:1px_solid_#f0dfa8] dark:[&.ou]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.ou]:[color:#e6b422] dark:[&.ou]:[border-color:#5a4a1a] [&.btts]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.btts]:[color:#1f9a6e] [&.btts]:[border:1px_solid_#c6e8d3] dark:[&.btts]:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[&.btts]:[color:#5ab88a] dark:[&.btts]:[border-color:#2a5a3e] [&.cs]:[background:linear-gradient(135deg,_#fce8ee,_#f8d9e2)] [&.cs]:[color:#d44a6a] [&.cs]:[border:1px_solid_#f0c9d4] dark:[&.cs]:[background:linear-gradient(135deg,_#3a1f2a,_#2e1a22)] dark:[&.cs]:[color:#e0809a] dark:[&.cs]:[border-color:#5a2a3e] [&.fh]:[background:linear-gradient(135deg,_#f0e8fc,_#e8dcf8)] [&.fh]:[color:#7a4a9c] [&.fh]:[border:1px_solid_#d8c4ec] dark:[&.fh]:[background:linear-gradient(135deg,_#2e1a44,_#251535)] dark:[&.fh]:[color:#b888df] dark:[&.fh]:[border-color:#4a2a5e] [&.win]:[background:linear-gradient(135deg,_#fde8e8,_#fcd9d9)] [&.win]:[color:#c74e4e] [&.win]:[border:1px_solid_#f0c9c9] dark:[&.win]:[background:linear-gradient(135deg,_#3a1f1f,_#2e1a1a)] dark:[&.win]:[color:#e08080] dark:[&.win]:[border-color:#5a2a2a]">{{ $marketTag['label'] }}</span></td>
                            <td data-label="{{ __('Favourite') }}" class="col-fav font-bold [font-size:12.5px] [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-icon]:[color:#e6b422] [&_.fav-icon]:[font-size:11px] [&_.fav-icon]:[margin-right:4px]"><span class="fav-icon">{{ $event['market'] === 'correct_score' ? '⚽' : '⭐' }}</span>{{ $event['market'] === 'correct_score' ? ($event['marketDisplayScore'] ?? $event['score'] ?? '—') : $favorite['name'] }}</td>
                            <td data-label="{{ __('Back') }}" class="col-odds"><span class="odds-cell odds-cell-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] dark:[background:#72bbef] dark:[color:#0b2a40] dark:[border-color:#4a9fd8] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ number_format($favorite['back'], 2) }}</span></td>
                            <td data-label="{{ __('Back Size') }}" class="col-odds"><span class="size-cell size-cell-back [background:rgba(114,_187,_239,_0.18)] [color:#1a6b9c] dark:[background:rgba(114,_187,_239,_0.15)] dark:[color:#72bbef] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ $event['totalBackSize'] >= 1000 ? number_format($event['totalBackSize'] / 1000, 1).'K' : $event['totalBackSize'] }}</span></td>
                            <td data-label="{{ __('Lay') }}" class="col-odds"><span class="odds-cell odds-cell-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] dark:[background:#faa9ba] dark:[color:#4a1520] dark:[border-color:#e8889a] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ number_format($favorite['lay'], 2) }}</span></td>
                            <td data-label="{{ __('Lay Size') }}" class="col-odds"><span class="size-cell size-cell-lay [background:rgba(250,_169,_186,_0.18)] [color:#a8425a] dark:[background:rgba(250,_169,_186,_0.15)] dark:[color:#faa9ba] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ $event['totalLaySize'] >= 1000 ? number_format($event['totalLaySize'] / 1000, 1).'K' : $event['totalLaySize'] }}</span></td>
                            <td data-label="{{ __('Balance') }}"><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $balanceClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $balanceLabel }}</span></td>
                            <td data-label="{{ __('Momentum') }}"><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $momentumClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $momentumLabel }}</span></td>
                            <td data-label="{{ __('Delta') }}"><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ str_starts_with($event['delta'], '+') ? 'ind-delta-pos' : (str_starts_with($event['delta'], '-') ? 'ind-delta-neg' : 'ind-delta-zero') }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $event['delta'] }}</span></td>
                            <td data-label="{{ __('Volatility') }}"><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $volatilityClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $volatilityLabel }}</span></td>
                            <td data-label="{{ __('Action') }}"><button type="button" class="open-btn inline-flex items-center [gap:4px] [padding:5px_12px] [border-radius:20px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:11.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] whitespace-nowrap dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-1px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.3)]" data-id="{{ $event['id'] }}">{{ __('Open') }} →</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div
                class="flex min-w-full items-center justify-center py-10 text-sm font-semibold text-slate-300 dark:text-slate-600 {{ $hasMore ? '' : 'hidden' }}"
                data-market-lazy-loader
                data-has-more="{{ $hasMore ? 'true' : 'false' }}"
                data-next-page="{{ $nextPage }}"
            >
                <span data-market-loader-text>↓ {{ __('Scroll for more matches') }} ↓</span>
            </div>
        </div>

        <div class="detail-panel overflow-y-auto rounded-2xl border hidden [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:16px] p-0 overflow-hidden [transition:0.3s] [height:fit-content] [position:sticky] [top:20px] [max-height:calc(100vh_-_40px)] [overflow-y:auto] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&.open]:[display:block] [&.open]:[animation:slideIn_0.3s_ease] max-[1024px]:[position:static] max-[480px]:[padding:0]" id="detailPanel">
            <div class="empty-panel flex flex-col items-center justify-center [padding:60px_20px] text-center [color:#8aaccc] [&_i]:[font-size:40px] [&_i]:[margin-bottom:12px] [&_i]:[opacity:0.5] [&_p]:[font-size:14px]" id="emptyPanel">
                <i class="fas fa-mouse-pointer"></i>
                <p>{{ __('Select an event to view detailed data') }}</p>
            </div>
            <div id="detailContent" style="display:none;"></div>
        </div>
    </div>

    <button type="button" class="scroll-top fixed [right:20px] [bottom:20px] [width:45px] [height:45px] border-0 [border-radius:50%] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:20px] cursor-pointer [z-index:9999] hidden items-center justify-center [box-shadow:0_4px_16px_rgba(26,_107,_156,_0.3)] [transition:0.2s] [&.visible]:[display:flex] [&:hover]:[transform:translateY(-3px)]" id="scrollTop" aria-label="Scroll to top">↑</button>

    @push('js')
        <script>
            (function() {
                // ===== COLLAPSE TOGGLE =====
                const collapseToggle = document.getElementById('collapseToggle');
                if (localStorage.getItem('bf-markets-collapsed') === 'true') {
                    document.body.classList.add('collapsed');
                }
                collapseToggle.addEventListener('click', function() {
                    document.body.classList.toggle('collapsed');
                    const isCollapsed = document.body.classList.contains('collapsed');
                    localStorage.setItem('bf-markets-collapsed', isCollapsed ? 'true' : 'false');
                    if (typeof ym !== 'undefined') {
                        ym(109087271, 'reachGoal', 'market_activity_collapse_toggle', {
                            state: isCollapsed ? 'collapsed' : 'expanded'
                        });
                    }
                });

                // ===== SIMULATION =====
                const SIM = {
                    tickInterval: 3000,
                    isProMode: false,
                    latency: 154,
                    previousRunners: {},
                    timerId: null
                };

                function rand(min, max) {
                    return Math.random() * (max - min) + min;
                }

                function randInt(min, max) {
                    return Math.floor(rand(min, max + 1));
                }

                function r2(n) {
                    return Math.round(n * 100) / 100;
                }

                function fmtSize(n) {
                    return n >= 1000
                        ? (n / 1000).toFixed(1) + 'K'
                        : Number(n.toFixed(2)).toString();
                }

                function fmtDelta(prev, curr) {
                    if (prev === 0) return '0%';
                    const pct = ((curr - prev) / prev) * 100;
                    if (Math.abs(pct) < 0.05) return '0%';
                    return (pct > 0 ? '+' : '') + pct.toFixed(1) + '%';
                }

                // ===== MARKET TAGS =====
                const MARKET_TAGS = @json($marketTags);
                const events = @json($marketActivites);

                // ===== SIMULATE =====
                function simulateTick() {
                    const numUpdates = SIM.isProMode ? randInt(2, 5) : randInt(1, 3);
                    const shuffled = events.slice().sort(function() {
                        return Math.random() - 0.5;
                    });
                    for (let i = 0; i < numUpdates && i < shuffled.length; i++) {
                        updateEvent(shuffled[i]);
                    }
                    SIM.latency = SIM.isProMode ? randInt(140, 177) : randInt(80, 150);
                    const latEl = document.getElementById('latencyDisplay');
                    if (latEl) latEl.textContent = SIM.latency;
                    updateVisibleCells();
                    if (currentEventId !== null) updateDetailPanel();
                }

                function updateEvent(ev) {
                    if (!SIM.previousRunners[ev.id]) {
                        SIM.previousRunners[ev.id] = ev.runners.map(function(r) {
                            return {
                                back: r.back,
                                lay: r.lay,
                                backSize: r.backSize,
                                laySize: r.laySize
                            };
                        });
                    }

                    ev.runners.forEach(function(runner) {
                        const backDrift = rand(-0.025, 0.025);
                        const layDrift = rand(-0.025, 0.025);

                        let volatilityFactor = 1;
                        if (ev.sport === 'horseracing' || ev.sport === 'greyhounds') volatilityFactor = 1.6;
                        else if (ev.sport === 'football' && ev.status === 'LIVE') volatilityFactor = 1.4;
                        else if (ev.sport === 'tennis') volatilityFactor = 1.2;

                        let speedFactor = 1;
                        if (SIM.tickInterval >= 5000) speedFactor = 1.8;
                        else if (SIM.tickInterval >= 3000) speedFactor = 1.4;
                        else if (SIM.tickInterval >= 2000) speedFactor = 1.2;

                        let newBack = r2(runner.back * (1 + backDrift * volatilityFactor * speedFactor));
                        let newLay = r2(runner.lay * (1 + layDrift * volatilityFactor * speedFactor));
                        if (newLay <= newBack) newLay = r2(newBack + Math.max(0.01, newBack * 0.01));
                        newBack = Math.max(1.01, Math.min(50, newBack));
                        newLay = Math.max(1.02, Math.min(60, newLay));

                        const backSizeChange = rand(-0.12, 0.16) * speedFactor;
                        const laySizeChange = rand(-0.12, 0.16) * speedFactor;
                        let newBackSize = Math.round(runner.backSize * (1 + backSizeChange));
                        let newLaySize = Math.round(runner.laySize * (1 + laySizeChange));
                        newBackSize = Math.max(500, Math.min(200000, newBackSize));
                        newLaySize = Math.max(500, Math.min(200000, newLaySize));

                        runner.back = newBack;
                        runner.lay = newLay;
                        runner.backSize = newBackSize;
                        runner.laySize = newLaySize;
                    });

                    let totalBack = 0,
                        totalLay = 0;
                    ev.runners.forEach(function(r) {
                        totalBack += r.backSize;
                        totalLay += r.laySize;
                    });
                    const ratio = totalBack / (totalBack + totalLay);
                    if (ratio > 0.58) ev.balance = 'back';
                    else if (ratio < 0.42) ev.balance = 'lay';
                    else ev.balance = 'balanced';

                    const favRunner = ev.runners[ev.favIdx];
                    const prevFav = SIM.previousRunners[ev.id][ev.favIdx];
                    if (prevFav) ev.delta = fmtDelta(prevFav.backSize, favRunner.backSize);

                    const deltaNum = parseFloat(ev.delta.replace('%', '').replace('+', ''));
                    if (deltaNum > 10) ev.momentum = 'strong_back';
                    else if (deltaNum > 3) ev.momentum = 'back';
                    else if (deltaNum < -10) ev.momentum = 'strong_lay';
                    else if (deltaNum < -3) ev.momentum = 'lay';
                    else ev.momentum = 'neutral';

                    const absDelta = Math.abs(deltaNum);
                    if (absDelta > 15) ev.volatility = 'high';
                    else if (absDelta > 5) ev.volatility = 'medium';
                    else ev.volatility = 'low';

                    SIM.previousRunners[ev.id] = ev.runners.map(function(r) {
                        return {
                            back: r.back,
                            lay: r.lay,
                            backSize: r.backSize,
                            laySize: r.laySize
                        };
                    });
                }

                // ===== HELPERS =====
                function balanceClass(bal) {
                    return bal === 'back' ? 'ind-back-heavy' : bal === 'lay' ? 'ind-lay-heavy' : 'ind-balanced';
                }

                function balanceText(bal) {
                    return bal === 'back' ? 'Back ↑' : bal === 'lay' ? 'Lay ↓' : 'Balanced →';
                }

                function momentumClass(mom) {
                    if (mom === 'strong_back') return 'ind-mom-strong-back';
                    if (mom === 'back') return 'ind-mom-back';
                    if (mom === 'strong_lay') return 'ind-mom-strong-lay';
                    if (mom === 'lay') return 'ind-mom-lay';
                    return 'ind-mom-neutral';
                }

                function momentumText(mom) {
                    if (mom === 'strong_back') return '↑↑';
                    if (mom === 'back') return '↑';
                    if (mom === 'strong_lay') return '↓↓';
                    if (mom === 'lay') return '↓';
                    return '→';
                }

                function deltaClass(d) {
                    return d.startsWith('+') ? 'ind-delta-pos' : d.startsWith('-') ? 'ind-delta-neg' : 'ind-delta-zero';
                }

                function volClass(v) {
                    return v === 'low' ? 'ind-vol-low' : v === 'high' ? 'ind-vol-high' : 'ind-vol-med';
                }

                function volText(v) {
                    return v === 'low' ? 'Low' : v === 'high' ? 'High' : 'Med';
                }

                function timeDisplay(ev) {
                    if (ev.status === 'LIVE') return '<span class="status-live">● LIVE</span> ' + ev.time;
                    return '<span class="status-up">▲ UP</span> ' + ev.time;
                }

                function getMarketTag(marketKey) {
                    const tag = MARKET_TAGS[marketKey] || {
                        label: marketKey,
                        cls: 'mo'
                    };
                    return '<span class="market-tag ' + tag.cls + ' inline-flex items-center [gap:4px] [padding:3px_10px] [border-radius:20px] [font-size:11px] font-bold whitespace-nowrap [&.mo]:[background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [&.mo]:[color:#1a6b9c] [&.mo]:[border:1px_solid_#b8d4ec] dark:[&.mo]:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[&.mo]:[color:#6aafdf] dark:[&.mo]:[border-color:#2a4a5e] [&.ou]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.ou]:[color:#b8860b] [&.ou]:[border:1px_solid_#f0dfa8] dark:[&.ou]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.ou]:[color:#e6b422] dark:[&.ou]:[border-color:#5a4a1a] [&.btts]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.btts]:[color:#1f9a6e] [&.btts]:[border:1px_solid_#c6e8d3] dark:[&.btts]:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[&.btts]:[color:#5ab88a] dark:[&.btts]:[border-color:#2a5a3e] [&.cs]:[background:linear-gradient(135deg,_#fce8ee,_#f8d9e2)] [&.cs]:[color:#d44a6a] [&.cs]:[border:1px_solid_#f0c9d4] dark:[&.cs]:[background:linear-gradient(135deg,_#3a1f2a,_#2e1a22)] dark:[&.cs]:[color:#e0809a] dark:[&.cs]:[border-color:#5a2a3e] [&.fh]:[background:linear-gradient(135deg,_#f0e8fc,_#e8dcf8)] [&.fh]:[color:#7a4a9c] [&.fh]:[border:1px_solid_#d8c4ec] dark:[&.fh]:[background:linear-gradient(135deg,_#2e1a44,_#251535)] dark:[&.fh]:[color:#b888df] dark:[&.fh]:[border-color:#4a2a5e] [&.win]:[background:linear-gradient(135deg,_#fde8e8,_#fcd9d9)] [&.win]:[color:#c74e4e] [&.win]:[border:1px_solid_#f0c9c9] dark:[&.win]:[background:linear-gradient(135deg,_#3a1f1f,_#2e1a1a)] dark:[&.win]:[color:#e08080] dark:[&.win]:[border-color:#5a2a2a]">' + tag.label + '</span>';
                }

                function getFavDisplay(ev) {
                    if (ev.market === 'correct_score') {
                        return '<span class="fav-icon">⚽</span>' + (ev.marketDisplayScore || ev.score || '—');
                    }
                    const fav = ev.runners[ev.favIdx];
                    return '<span class="fav-icon">⭐</span>' + fav.name;
                }

                // ===== RENDER TABLE =====
                const eventsBody = document.getElementById('eventsBody');
                const marketRowTemplate = eventsBody.querySelector('tr[data-id]')?.cloneNode(true);
                const marketLazyLoader = document.querySelector('[data-market-lazy-loader]');
                const terminal = document.getElementById('terminal');
                const detailPanel = document.getElementById('detailPanel');
                const emptyPanel = document.getElementById('emptyPanel');
                const detailContent = document.getElementById('detailContent');
                const marketContainer = document.getElementById('marketContainer');
                const marketSelect = document.getElementById('marketSelect');
                let currentEventId = null;
                let activeMarketFilter = 'active';
                let activeFootballFilter = document.querySelector('.filter-pill.active')?.dataset.filter || 'all';

                function createEventRow(ev) {
                    if (!marketRowTemplate) return null;

                    const row = marketRowTemplate.cloneNode(true);
                    const cells = row.children;
                    const favorite = ev.runners[ev.favIdx] || ev.favorite;
                    if (!favorite) return null;

                    row.dataset.id = ev.id;
                    cells[0].innerHTML = ev.status === 'LIVE'
                        ? '<span class="status-live">● LIVE</span> '
                        : '<span class="status-up">▲ UP</span> ';
                    const eventTime = String(ev.time || '');
                    if (ev.status === 'LIVE' && eventTime.endsWith("'")) {
                        cells[0].append(document.createTextNode(eventTime.slice(0, -1)));
                        const minuteTick = document.createElement('span');
                        minuteTick.className = 'minute-tick-blink';
                        minuteTick.textContent = "'";
                        cells[0].append(minuteTick);
                    } else {
                        cells[0].append(document.createTextNode(eventTime));
                    }
                    cells[1].textContent = `${ev.sportIcon || '⚽'} ${ev.comp || ''}`;
                    cells[2].textContent = ev.match || '';
                    cells[3].innerHTML = getMarketTag(ev.market);
                    cells[4].innerHTML = '';
                    const favoriteIcon = document.createElement('span');
                    favoriteIcon.className = 'fav-icon';
                    favoriteIcon.textContent = ev.market === 'correct_score' ? '⚽' : '⭐';
                    cells[4].append(favoriteIcon, document.createTextNode(ev.market === 'correct_score'
                        ? (ev.marketDisplayScore || ev.score || '—')
                        : favorite.name));
                    cells[5].querySelector('span').textContent = Number(favorite.back || 0).toFixed(2);
                    cells[6].querySelector('span').textContent = fmtSize(Number(ev.totalBackSize || 0));
                    cells[7].querySelector('span').textContent = Number(favorite.lay || 0).toFixed(2);
                    cells[8].querySelector('span').textContent = fmtSize(Number(ev.totalLaySize || 0));

                    const indicatorClasses = [
                        'ind-back-heavy', 'ind-lay-heavy', 'ind-balanced',
                        'ind-mom-strong-back', 'ind-mom-back', 'ind-mom-neutral', 'ind-mom-lay', 'ind-mom-strong-lay',
                        'ind-delta-pos', 'ind-delta-neg', 'ind-delta-zero',
                        'ind-vol-low', 'ind-vol-med', 'ind-vol-high',
                    ];
                    [[9, balanceClass(ev.balance), balanceText(ev.balance)],
                        [10, momentumClass(ev.momentum), momentumText(ev.momentum)],
                        [11, deltaClass(ev.delta), ev.delta],
                        [12, volClass(ev.volatility), volText(ev.volatility)]
                    ].forEach(([index, className, label]) => {
                        const indicator = cells[index].querySelector('span');
                        indicator.classList.remove(...indicatorClasses);
                        indicator.classList.add(className);
                        indicator.textContent = label;
                    });
                    cells[13].querySelector('[data-id]').dataset.id = ev.id;

                    return row;
                }

                let marketLoading = false;
                let marketHasMore = marketLazyLoader?.dataset.hasMore === 'true';
                let marketNextPage = Number(marketLazyLoader?.dataset.nextPage || 2);

                function updateMarketLoader(hasMore, nextPage) {
                    marketHasMore = Boolean(hasMore);
                    marketNextPage = Number(nextPage || marketNextPage);
                    if (!marketLazyLoader) return;
                    marketLazyLoader.classList.toggle('hidden', !marketHasMore);
                    marketLazyLoader.dataset.hasMore = marketHasMore ? 'true' : 'false';
                    marketLazyLoader.querySelector('[data-market-loader-text]').textContent = '↓ Scroll for more matches ↓';
                }

                function replaceMarketActivityRows(data) {
                    const selectedEventId = currentEventId;
                    const detailWasOpen = detailPanel.classList.contains('open');
                    const detailWasExpanded = terminal.classList.contains('panel-expanded');

                    events.splice(0, events.length, ...data);
                    eventsBody.innerHTML = '';
                    data.forEach(function(ev) {
                        const row = createEventRow(ev);
                        if (row) eventsBody.appendChild(row);
                    });

                    if (detailWasOpen && events.some(function(ev) { return ev.id === selectedEventId; })) {
                        openDetail(selectedEventId);
                        if (detailWasExpanded) toggleExpand();
                    } else {
                        currentEventId = null;
                        detailPanel.classList.remove('open');
                        terminal.classList.remove('with-panel', 'panel-expanded');
                        detailContent.style.display = 'none';
                        emptyPanel.style.display = 'flex';
                    }
                }

                function marketActivityUrl(page, perPage = 20) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('per_page', perPage);
                    url.searchParams.set('type', activeFootballFilter);
                    url.searchParams.set('market_name', activeMarketFilter);

                    return url;
                }

                async function reloadMarketActivity() {
                    if (marketLoading) return;

                    marketLoading = true;
                    try {
                        const loadedRowCount = Math.max(20, events.length);
                        const response = await fetch(marketActivityUrl(1, loadedRowCount), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

                        const payload = await response.json();
                        replaceMarketActivityRows(payload.data);
                        updateMarketLoader(payload.has_more, Math.floor(payload.data.length / 20) + 1);
                    } catch (error) {
                        console.error('Market activity filter request failed:', error);
                    } finally {
                        marketLoading = false;
                    }
                }

                async function loadMoreMarketActivity() {
                    if (marketLoading || !marketHasMore) return;

                    marketLoading = true;
                    marketLazyLoader.querySelector('[data-market-loader-text]').textContent = 'Loading matches…';

                    try {
                        const response = await fetch(marketActivityUrl(marketNextPage), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

                        const payload = await response.json();
                        payload.data.forEach(function(ev) {
                            const row = createEventRow(ev);
                            if (row) eventsBody.appendChild(row);
                        });
                        events.push(...payload.data);
                        updateMarketLoader(payload.has_more, payload.next_page);
                    } catch (error) {
                        console.error('Market activity request failed:', error);
                        updateMarketLoader(marketHasMore, marketNextPage);
                    } finally {
                        marketLoading = false;
                    }
                }

                document.querySelectorAll('.filter-pill').forEach(function(pill) {
                    pill.addEventListener('click', function() {
                        document.querySelectorAll('.filter-pill').forEach(function(item) {
                            item.classList.remove('active');
                        });
                        pill.classList.add('active');
                        activeFootballFilter = pill.dataset.filter || 'all';
                        reloadMarketActivity();
                    });
                });

                marketSelect?.addEventListener('change', function() {
                    activeMarketFilter = marketSelect.value || 'active';
                    reloadMarketActivity();
                });

                if (marketLazyLoader) {
                    const marketObserver = new IntersectionObserver(function(entries) {
                        if (entries[0]?.isIntersecting) loadMoreMarketActivity();
                    }, { rootMargin: '200px 0px' });
                    marketObserver.observe(marketLazyLoader);
                }

                let marketRealtimeRefreshTimer = null;
                window.addEventListener('load', function() {
                    if (!window.Echo) return;

                    window.Echo.channel('sports.football.live')
                        .listen('.score.updated', function() {
                            if (marketRealtimeRefreshTimer !== null) return;

                            marketRealtimeRefreshTimer = window.setTimeout(function() {
                                marketRealtimeRefreshTimer = null;
                                reloadMarketActivity();
                            }, 500);
                        });
                }, { once: true });

                // function renderEvents(list) {
                //     eventsBody.innerHTML = '';
                //     list.forEach(function(ev) {
                //         alert("asdasd");

                //         const tr = document.createElement('tr');
                //         tr.dataset.id = ev.id;

                //         const favRunner = ev.runners[ev.favIdx];
                //         const favMark = (ev.sport === 'horseracing' || ev.sport === 'greyhounds') ?
                //             ' <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>' : '';

                //         tr.innerHTML = `
                //     <td class="col-time font-bold [color:#0b2a40] dark:[color:#e8edf2] [&_.status-live]:[color:#c74e4e] [&_.status-live]:[font-weight:800] [&_.status-live]:[margin-right:4px] dark:[&_.status-live]:[color:#e08080] [&_.status-up]:[color:#1a6b9c] [&_.status-up]:[font-weight:700] [&_.status-up]:[margin-right:4px] dark:[&_.status-up]:[color:#6aafdf]">${timeDisplay(ev)}</td>
                //     <td class="col-comp [font-size:11.5px] [color:#8aaccc] dark:[color:#5a7d99]">${ev.sportIcon} ${ev.comp}</td>
                //     <td class="col-match font-semibold [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[margin-left:4px] [&_.fav-star]:[font-size:12px]">${ev.match}${favMark}</td>
                //     <td class="col-market font-semibold [font-size:12px]">${getMarketTag(ev.market)}</td>
                //     <td class="col-fav font-bold [font-size:12.5px] [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-icon]:[color:#e6b422] [&_.fav-icon]:[font-size:11px] [&_.fav-icon]:[margin-right:4px]">${getFavDisplay(ev)}</td>
                //     <td data-label="{{ __('Back') }}" class="col-odds"><span class="odds-cell odds-cell-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] dark:[background:#72bbef] dark:[color:#0b2a40] dark:[border-color:#4a9fd8] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${favRunner.back.toFixed(2)}</span></td>
                //     <td data-label="{{ __('Back Size') }}" class="col-odds"><span class="size-cell size-cell-back [background:rgba(114,_187,_239,_0.18)] [color:#1a6b9c] dark:[background:rgba(114,_187,_239,_0.15)] dark:[color:#72bbef] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${fmtSize(favRunner.backSize)}</span></td>
                //     <td data-label="{{ __('Lay') }}" class="col-odds"><span class="odds-cell odds-cell-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] dark:[background:#faa9ba] dark:[color:#4a1520] dark:[border-color:#e8889a] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${favRunner.lay.toFixed(2)}</span></td>
                //     <td data-label="{{ __('Lay Size') }}" class="col-odds"><span class="size-cell size-cell-lay [background:rgba(250,_169,_186,_0.18)] [color:#a8425a] dark:[background:rgba(250,_169,_186,_0.15)] dark:[color:#faa9ba] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${fmtSize(favRunner.laySize)}</span></td>
                //     <td><span class="ind ${balanceClass(ev.balance)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${balanceText(ev.balance)}</span></td>
                //     <td><span class="ind ${momentumClass(ev.momentum)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${momentumText(ev.momentum)}</span></td>
                //     <td><span class="ind ${deltaClass(ev.delta)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${ev.delta}</span></td>
                //     <td><span class="ind ${volClass(ev.volatility)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${volText(ev.volatility)}</span></td>
                //     <td><button class="open-btn inline-flex items-center [gap:4px] [padding:5px_12px] [border-radius:20px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:11.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] whitespace-nowrap dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-1px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.3)]" data-id="${ev.id}">Open →</button></td>
                // `;

                //         eventsBody.appendChild(tr);
                //     });
                // }

                eventsBody.addEventListener('click', function(e) {
                    const row = e.target.closest('tr[data-id]');
                    if (row && eventsBody.contains(row)) openDetail(Number(row.dataset.id));
                });

                function openDetail(id) {
                    const ev = events.find(function(e) {
                        return e.id === id;
                    });
                    if (!ev) return;
                    currentEventId = id;

                    document.querySelectorAll('.events-table tbody tr').forEach(function(r) {
                        r.classList.toggle('selected', parseInt(r.dataset.id) === id);
                    });

                    let totalBack = 0,
                        totalLay = 0;
                    ev.runners.forEach(function(r) {
                        totalBack += r.backSize;
                        totalLay += r.laySize;
                    });
                    const totalLiq = totalBack + totalLay;
                    const backPct = totalLiq > 0 ? (totalBack / totalLiq * 100) : 50;
                    const layPct = 100 - backPct;

                    let runnersHtml = '';
                    ev.runners.forEach(function(r, idx) {
                        const isFav = idx === ev.favIdx;
                        runnersHtml += `
                    <div class="runner-row grid [grid-template-columns:1fr_auto_auto] [gap:10px] items-center [padding:10px_12px] bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [font-size:12.5px] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50]">
                        <div class="runner-name font-bold [color:#0b2a40] flex items-center [gap:5px] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[font-size:13px]">${r.name}${isFav ? ' <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>' : ''}</div>
                        <div class="runner-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] [border-radius:4px] [padding:5px_10px] font-extrabold text-center [min-width:70px] [font-variant-numeric:tabular-nums]">${r.back.toFixed(2)}<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">${fmtSize(r.backSize)}</span></div>
                        <div class="runner-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] [border-radius:4px] [padding:5px_10px] font-extrabold text-center [min-width:70px] [font-variant-numeric:tabular-nums]">${r.lay.toFixed(2)}<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">${fmtSize(r.laySize)}</span></div>
                    </div>
                `;
                    });

                    let scoreHtml = ev.score ? `<span>· ${ev.score}</span>` : '';
                    let metaHtml =
                        `${ev.sportIcon} ${ev.comp} · ${ev.status === 'LIVE' ? '● LIVE' : '▲ UPCOMING'} · ${ev.time} ${scoreHtml}`;

                    detailContent.innerHTML = `
                        <div class="detail-header [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [padding:10px_16px] [position:sticky] [top:0] [z-index:10] max-[480px]:[padding:8px_12px]">
                            <div class="detail-header-top flex items-center justify-between [gap:10px]">
                                <div class="detail-title [font-size:14.5px] font-extrabold [flex:1] min-w-0 whitespace-nowrap overflow-hidden [text-overflow:ellipsis] max-[480px]:[font-size:13px]">${ev.match}</div>
                                <div class="detail-header-actions flex [gap:6px] shrink-0">
                                    <button class="detail-action-btn [background:rgba(255,_255,_255,_0.15)] border-0 text-white [width:28px] [height:28px] [border-radius:50%] [font-size:13px] cursor-pointer flex items-center justify-center [transition:background_0.2s,_transform_0.2s] [font-family:inherit] [&:hover]:[background:rgba(255,_255,_255,_0.3)] [&:hover]:[transform:scale(1.08)] [&.expand-active]:[background:rgba(255,_255,_255,_0.35)]" id="expandBtn" title="Expand"><i class="fas fa-expand"></i></button>
                                    <button class="detail-action-btn [background:rgba(255,_255,_255,_0.15)] border-0 text-white [width:28px] [height:28px] [border-radius:50%] [font-size:13px] cursor-pointer flex items-center justify-center [transition:background_0.2s,_transform_0.2s] [font-family:inherit] [&:hover]:[background:rgba(255,_255,_255,_0.3)] [&:hover]:[transform:scale(1.08)] [&.expand-active]:[background:rgba(255,_255,_255,_0.35)]" onclick="closeDetail()" title="Close"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="detail-meta [font-size:11.5px] [opacity:0.9] flex items-center [gap:8px] flex-wrap [margin-top:2px] max-[480px]:[font-size:10.5px]">${metaHtml}</div>
                        </div>
                        <div class="detail-body [padding:16px_20px] max-[480px]:[padding:14px_16px]">
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-users"></i> Runners</div>
                                <div class="runners-list flex flex-col [gap:8px]">${runnersHtml}</div>
                            </div>
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-water"></i> Liquidity Distribution</div>
                                <div class="liquidity-bar flex [height:26px] [border-radius:8px] overflow-hidden [font-size:11px] font-bold [margin-top:6px]">
                                    <div class="liq-back [background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] text-white flex items-center justify-start [padding-left:10px] [transition:width_0.6s_ease]" style="width:${backPct}%;">Back ${backPct.toFixed(0)}%</div>
                                    <div class="liq-lay [background:linear-gradient(135deg,_#d44a6a,_#e0809a)] text-white flex items-center justify-end [padding-right:10px] [transition:width_0.6s_ease]" style="width:${layPct}%;">Lay ${layPct.toFixed(0)}%</div>
                                </div>
                            </div>
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-chart-line"></i> Indicators</div>
                                <div class="indicators-grid grid [grid-template-columns:repeat(3,_1fr)] [gap:8px] max-[480px]:[grid-template-columns:repeat(3,_1fr)] max-[480px]:[gap:6px]">
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Balance</div><div class="ind-value ${ev.balance === 'back' ? 'back-heavy' : (ev.balance === 'lay' ? 'lay-heavy' : 'balanced')}">${balanceText(ev.balance)}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Momentum</div><div class="ind-value ${ev.momentum === 'back' || ev.momentum === 'strong_back' ? 'back-heavy' : (ev.momentum === 'lay' || ev.momentum === 'strong_lay' ? 'lay-heavy' : 'balanced')}">${momentumText(ev.momentum)}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Delta</div><div class="ind-value ${ev.delta.startsWith('+') ? 'vol-low' : (ev.delta.startsWith('-') ? 'vol-high' : 'balanced')}">${ev.delta}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Volatility</div><div class="ind-value vol-${ev.volatility === 'low' ? 'low' : (ev.volatility === 'high' ? 'high' : 'med')}">${volText(ev.volatility)}</div></div>
                                </div>
                            </div>
                            <div class="terms-section collapsed [margin-top:20px] [padding-top:16px] [border-top:1px_dashed_#d4e0ec] [transition:border-color_0.3s] dark:[border-top-color:#3a5568] [&.collapsed_.terms-toggle-icon]:[transform:rotate(-90deg)] [&.collapsed_.terms-list]:[display:none] [body&_.collapse-toggle_i]:[transform:rotate(180deg)] [body&_.controls-wrapper]:[max-height:0] [body&_.controls-wrapper]:[opacity:0]" id="termsSection">
                                <div class="terms-header flex items-center justify-between cursor-pointer select-none [padding:6px_10px] [border-radius:8px] [background:rgba(26,_107,_156,_0.06)] [transition:background_0.2s] dark:[background:rgba(106,_175,_223,_0.08)] [&:hover]:[background:rgba(26,_107,_156,_0.12)] dark:[&:hover]:[background:rgba(106,_175,_223,_0.15)]" id="termsToggle">
                                    <div class="terms-header-left flex items-center [gap:8px] [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] dark:[color:#6aafdf] [&_i]:[font-size:12px]"><i class="fas fa-book-open"></i><span>Terms</span></div>
                                    <i class="fas fa-chevron-down terms-toggle-icon [font-size:12px] [color:#8aaccc] [transition:transform_0.3s] dark:[color:#5a7d99]"></i>
                                </div>
                                <div class="terms-list flex flex-col [gap:5px] [margin-top:10px] [animation:fadeInTerms_0.25s_ease]">
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key term-back">Back</span><span class="term-desc">Bet in favor of the event</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key term-lay">Lay</span><span class="term-desc">Bet against the event</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Odds</span><span class="term-desc">Price</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Size</span><span class="term-desc">Money available at best price</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Balance</span><span class="term-desc">Where the money is now</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Momentum</span><span class="term-desc">Where the money is moving</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Δ Delta</span><span class="term-desc">How fast Back Size changes</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Volatility</span><span class="term-desc">How unpredictable the market is</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Liquidity</span><span class="term-desc">Total money in the market</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Runner</span><span class="term-desc">Participant in the market</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Prematch</span><span class="term-desc">Market before event starts</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Favorite</span><span class="term-desc">Participant with the lowest Back Odds</span></div>
                                </div>
                            </div>
                        </div>
                    `;

                    emptyPanel.style.display = 'none';
                    detailContent.style.display = 'block';
                    detailPanel.classList.add('open');
                    terminal.classList.add('with-panel');
                    terminal.classList.remove('panel-expanded');

                    const expandBtn = document.getElementById('expandBtn');
                    if (expandBtn) expandBtn.addEventListener('click', toggleExpand);

                    const termsToggle = document.getElementById('termsToggle');
                    const termsSection = document.getElementById('termsSection');
                    if (termsToggle && termsSection) {
                        termsToggle.addEventListener('click', function() {
                            termsSection.classList.toggle('collapsed');
                        });
                    }

                    if (typeof ym !== 'undefined') {
                        ym(109087271, 'reachGoal', 'market_activity_open_detail', {
                            event: ev.match
                        });
                    }
                }

                function toggleExpand() {
                    const expandBtn = document.getElementById('expandBtn');
                    const isExpanded = terminal.classList.toggle('panel-expanded');
                    if (isExpanded) {
                        terminal.classList.remove('with-panel');
                        if (expandBtn) {
                            expandBtn.innerHTML = '<i class="fas fa-compress"></i>';
                            expandBtn.classList.add('expand-active');
                        }
                        document.body.style.overflow = 'hidden';
                    } else {
                        terminal.classList.add('with-panel');
                        if (expandBtn) {
                            expandBtn.innerHTML = '<i class="fas fa-expand"></i>';
                            expandBtn.classList.remove('expand-active');
                        }
                        document.body.style.overflow = '';
                    }
                }

                window.closeDetail = function() {
                    detailPanel.classList.remove('open');
                    terminal.classList.remove('with-panel');
                    terminal.classList.remove('panel-expanded');
                    document.body.style.overflow = '';
                    detailContent.style.display = 'none';
                    emptyPanel.style.display = 'flex';
                    currentEventId = null;
                    document.querySelectorAll('.events-table tbody tr').forEach(function(r) {
                        r.classList.remove('selected');
                    });
                };

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        if (terminal.classList.contains('panel-expanded')) toggleExpand();
                        else if (detailPanel.classList.contains('open')) closeDetail();
                    }
                });

                // ===== FILTERS =====
                let activeFilter = 'all';
                let activeSport = 'all';

            })();
        </script>
    @endpush
@endif
@endsection
