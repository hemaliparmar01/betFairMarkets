let oddsTooltip = null;
const hideOddsTooltip = () => { oddsTooltip?.remove(); oddsTooltip = null; };
const showOddsTooltip = (event) => {
    const target = event.target.closest('[data-horseracing-odds-tooltip]');
    if (!target || target.contains(event.relatedTarget)) return;
    hideOddsTooltip();
    oddsTooltip = document.createElement('div');
    oddsTooltip.setAttribute('role', 'tooltip');
    oddsTooltip.dataset.horseracingTooltip = '';
    oddsTooltip.className = 'pointer-events-none fixed z-[10001] whitespace-nowrap rounded-md bg-[#0b2a40] px-2 py-1 text-[11px] font-normal text-white shadow-sm dark:bg-[#2a4050] dark:text-[#e8edf2]';
    oddsTooltip.textContent = target.dataset.horseracingOddsTooltip;
    document.body.append(oddsTooltip);
    const rect = target.getBoundingClientRect();
    const width = oddsTooltip.offsetWidth;
    oddsTooltip.style.left = `${Math.max(8, Math.min(window.innerWidth - width - 8, rect.left + rect.width / 2 - width / 2))}px`;
    oddsTooltip.style.top = `${rect.top > oddsTooltip.offsetHeight + 8 ? rect.top - oddsTooltip.offsetHeight - 6 : rect.bottom + 6}px`;
};
document.addEventListener('pointerover', showOddsTooltip);
document.addEventListener('focusin', showOddsTooltip);
for (const type of ['pointerout', 'focusout']) {
    document.addEventListener(type, (event) => {
        const target = event.target.closest('[data-horseracing-odds-tooltip]');
        if (target && !target.contains(event.relatedTarget)) hideOddsTooltip();
    });
}
document.addEventListener('scroll', hideOddsTooltip, true);
document.addEventListener('click', hideOddsTooltip);
window.addEventListener('resize', hideOddsTooltip);

const dashboard = document.getElementById('horseracingDashboard');
if (dashboard) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const filters = document.getElementById('horseracingFilters');
        const markets = dashboard.dataset.sourceKind === 'markets';
    let selectedFilter = 'all';
    let loadedPages = 1;
    let loading = false;
    let refreshPending = false;
    let pendingAction = null;
    let refreshTimer = null;
    let activeModal = null;
    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const number = (value) => Number(value) > 0 ? Number(value).toFixed(2) : '—';
    const parse = (value) => { try { return JSON.parse(value ?? '{}'); } catch { return {}; } };
    const loader = () => dashboard.querySelector('[data-horseracing-lazy-loader]');
    const content = () => dashboard.querySelector(markets ? '[data-horseracing-market-rows]' : '.fixtures-container');
    const showError = (message) => {
        let notice = dashboard.querySelector('[data-horseracing-error]');
        if (!notice) { notice = document.createElement('p'); notice.dataset.horseracingError = ''; notice.className = 'p-3 text-center text-sm text-red-600'; dashboard.prepend(notice); }
        notice.textContent = message;
    };
    let refreshInterval = 3000;
    document.querySelector("[data-horseracing-speeds]")?.addEventListener("click", (event) => {
        const button = event.target.closest("[data-speed]"); if (!button) return;
        refreshInterval = Number(button.dataset.speed);
        document.querySelectorAll("[data-horseracing-speeds] button").forEach((item) => { item.classList.toggle("active",item === button); item.classList.toggle("bg-[#0b2a40]",item === button); item.classList.toggle("!text-white",item === button); });
    });
    function scheduleRefresh() {
        if (refreshTimer !== null) return;
        refreshTimer = window.setTimeout(() => { refreshTimer = null; load(); }, refreshInterval);
    }
    async function load({ append = false, reset = false, prediction = null } = {}) {
        if (reset && !markets) {
            content()?.classList.add('fixtures-loading');
            content()?.setAttribute('aria-busy', 'true');
        }
        if (loading) { if (reset || prediction) pendingAction = { reset, prediction }; else if (!append) refreshPending = true; return; }
        if (document.visibilityState !== 'visible' && !reset && !prediction) { refreshPending = true; return; }
        if (append && loader()?.dataset.hasMore !== 'true') return;
        loading = true;
        const page = append ? Number(loader().dataset.nextPage) : 1;
        const perPage = append || reset ? 20 : loadedPages * 20;
        const params = { type: selectedFilter, page, per_page: perPage, append };
        if (prediction) params.prediction = prediction;
        const text = loader()?.querySelector('[data-lazy-loader-text]');
        if (text) text.textContent = 'Loading races…';
        try {
            const url = new URL(dashboard.dataset.sourceUrl, window.location.origin);
            const options = { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' };
            if (dashboard.dataset.sourceMethod === 'POST') { options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(params); }
            else Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value === true ? '1' : value === false ? '0' : String(value)));
            const response = await fetch(url, options);
            if (response.status === 401) { window.location.assign(dashboard.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to load races. Please try again.');
            const result = await response.json();
            hideOddsTooltip();
            const target = content();
            const priceKey = (cell) => `${cell.closest('[data-match-id]')?.dataset.matchId}:${cell.getAttribute('title') ?? 'favorite'}`;
            const priorPrices = new Map([...target.querySelectorAll('[data-horseracing-odds-tooltip]')].map((cell) => [priceKey(cell), cell.textContent.trim()]));
            const scrollLeft = target.closest('.overflow-x-auto')?.scrollLeft ?? target.scrollLeft;
            if (append) {
                target.querySelector('[data-horseracing-empty]')?.remove();
                const fragment = document.createElement('template');
                fragment.innerHTML = markets ? `<table><tbody>${result.html}</tbody></table>` : result.html;
                const nodes = markets ? [...fragment.content.querySelector('tbody').children] : [...fragment.content.children];
                if (!markets && nodes[0]?.dataset.leagueHeader === [...target.querySelectorAll('[data-league-header]')].at(-1)?.dataset.leagueHeader) nodes.shift();
                nodes.forEach((node) => target.append(node));
                loadedPages++;
            } else {
                if (markets) target.innerHTML = result.html;
                else {
                    const fragment = document.createElement('template'); fragment.innerHTML = result.html;
                    target.replaceChildren(...fragment.content.querySelector('.fixtures-container').childNodes);
                }
                if (reset) loadedPages = 1;
            }
            const overflow = target.closest('.overflow-x-auto') ?? target; overflow.scrollLeft = scrollLeft;
            target.querySelectorAll('[data-horseracing-odds-tooltip]').forEach((cell) => {
                const old = priorPrices.get(priceKey(cell));
                if (old != null && old !== cell.textContent.trim()) {
                    cell.classList.add('!bg-amber-100', 'dark:!bg-amber-950');
                    setTimeout(() => cell.classList.remove('!bg-amber-100', 'dark:!bg-amber-950'), 650);
                }
            });
            loader().dataset.hasMore = result.has_more ? 'true' : 'false';
            loader().dataset.nextPage = result.has_more ? String(loadedPages + 1) : '';
            loader().classList.toggle('hidden', !result.has_more);
            dashboard.querySelector('[data-horseracing-error]')?.remove();
            if (activeModal) {
                const opened = activeModal;
                const visibleRow = [...dashboard.querySelectorAll('[data-match-id]')].find((row) => row.dataset.matchId === opened.matchId);
                if (!visibleRow) {
                    const detailResponse = await fetch(opened.row.dataset.horseracingMatchUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'});
                    if (detailResponse.ok) {
                        const detail = await detailResponse.json();
                        opened.row.dataset.horseracingMarket = JSON.stringify(detail.market);
                        opened.row.dataset.horseracingPrediction = JSON.stringify(detail.prediction);
                    }
                }
                if (activeModal === opened) renderModal();
            }
        } catch (error) { showError(error.message); }
        finally {
            loading = false;
            content()?.classList.remove('fixtures-loading');
            content()?.removeAttribute('aria-busy');
            const text = loader()?.querySelector('[data-lazy-loader-text]');
            if (text) text.textContent = '↓ Scroll for more races ↓';
            if (pendingAction) { const action = pendingAction; pendingAction = null; load(action); } else if (refreshPending && document.visibilityState === 'visible') { refreshPending = false; scheduleRefresh(); }
        }
    }
    function closeModal() { const modal = dashboard.querySelector('#horseracingModal'); modal?.classList.add('hidden'); modal?.classList.remove('flex'); activeModal = null; document.body.style.overflow = ''; }
    function renderModal() {
        if (!activeModal) return;
        const rows = [...dashboard.querySelectorAll('[data-match-id]')];
        const row = rows.find((candidate) => candidate.dataset.matchId === activeModal.matchId && (!activeModal.marketId || candidate.dataset.marketId === activeModal.marketId)) ?? activeModal.row;
        if (!row) { closeModal(); return; }
        activeModal.row = row.cloneNode(true);
        const modal = dashboard.querySelector('#horseracingModal');
        const panel = modal.querySelector('[data-horseracing-modal-panel]');
        const scrollTop = panel.scrollTop;
        let title = ''; let body = '';
        if (activeModal.action === 'chart') {
            const data = parse(row.dataset.horseracingMarket); title = `${data.title ?? 'Race Winner'} · Odds Movement`;
            if (data.locked) body = '<p>Login with an active subscription to view odds.</p>';
            else body = (data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? 0);
                const history = (runner.backOddsHistory ?? []).filter((point) => Number(point.odds) > 0).slice(-12);
                if (initial > 0 && Number(history[0]?.odds) !== initial) history.unshift({odds: initial});
                if (current > 0 && Number(history.at(-1)?.odds) !== current) history.push({odds: current});
                const values = history.map((point) => Number(point.odds));
                const minimum = Math.min(...values); const maximum = Math.max(...values); const range = maximum - minimum || 1;
                const points = values.map((value, index) => `${10 + index * 380 / Math.max(1, values.length - 1)},${90 - (value - minimum) / range * 70}`).join(' ');
                return `<section class="mb-4 rounded-xl border border-[#d4e0ec] p-4"><h3 class="font-bold">${escape(runner.runner)}</h3><p class="mt-1 text-sm">Initial ${number(initial)} → Current ${number(current)}</p>${values.length ? `<svg viewBox="0 0 400 110" class="mt-3 h-32 w-full" role="img" aria-label="Back odds history"><polyline fill="none" stroke="#1a6b9c" stroke-width="3" points="${points}" /></svg>` : '<p class="mt-3 text-sm">No odds history available.</p>'}</section>`;
            }).join('') || '<p>Market data is not available yet.</p>';
        } else if (activeModal.action === 'prediction') {
            const data = parse(row.dataset.horseracingPrediction); title = 'Race Predictions';
            const total = Object.values(data.counts ?? {}).reduce((sum, count) => sum + Number(count), 0);
            body = `<p class="mb-4 text-sm">${data.locked ? 'Voting closes at the off time.' : data.vote ? 'Your prediction has been recorded.' : 'Who will win this race?'}</p><div class="grid gap-3">${Object.entries(data.choices ?? {}).map(([value, label]) => `<button type="button" data-action="vote" data-value="${escape(value)}" ${data.locked || data.vote ? 'disabled' : ''} class="rounded-xl border border-[#b9d7f3] p-3 text-left disabled:cursor-default ${data.vote === value ? 'bg-[#d5edff] text-[#1a6b9c]' : ''}"><b>${escape(label)}</b><span class="float-right">${Number(data.counts?.[value] ?? 0)} votes · ${total ? Math.round(Number(data.counts?.[value] ?? 0) / total * 100) : 0}%</span></button>`).join('')}</div>`;
        } else {
            const data = parse(row.dataset.horseracingMarket); title = data.title ?? 'Race Details';
            body = `<p class="mb-4 text-sm">🏇 ${escape(data.status)} · ${escape(data.minutes)}</p>`;
            if (data.locked) body += '<p>Login with an active subscription to view odds.</p>';
            else body += `<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>${['Selection', 'Initial', 'Back', 'Size', 'Lay', 'Size'].map((label) => `<th class="px-2 py-2">${label}</th>`).join('')}</tr></thead><tbody>${(data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? runner.backOdds ?? 0);
                const arrow = initial > 0 && current > 0 ? current > initial ? ' ↑' : current < initial ? ' ↓' : '' : '';
                return `<tr class="border-t border-[#e3ecf5]"><td class="px-2 py-3">${escape(runner.runner)}${String(runner.runnerId) === String(data.indicators?.favorite_id) ? " ⭐" : ""}</td>${[initial, current, runner.backSize, runner.layOdds, runner.laySize].map((value, index) => `<td ${index === 1 && current > 0 ? `tabindex="0" data-horseracing-odds-tooltip="${number(initial)} → ${number(current)}"` : ''} class="whitespace-nowrap px-2 py-3 font-semibold">${number(value)}${index === 1 ? arrow : ''}</td>`).join('')}</tr>`;
            }).join('')}</tbody></table></div><a class="mt-4 inline-block rounded-xl bg-[#0b2a40] px-4 py-2 text-sm font-bold text-white" href="${escape(row.dataset.horseracingMatchUrl)}">Race markets &amp; charts →</a>`;
        }
        hideOddsTooltip();
        if (activeModal.action === 'market') {
            const data = parse(row.dataset.horseracingMarket);
            if (!data.locked) {
                const indicator = data.indicators ?? {};
                const backPercent = Number(indicator.back_percent ?? 50);
                body += `<section class="mt-5"><h3 class="mb-2 font-bold">Liquidity Distribution</h3><div class="flex overflow-hidden rounded-lg text-center text-xs text-white"><div class="bg-[#1a6b9c] p-2" style="width:${backPercent}%">Back ${backPercent.toFixed(0)}%</div><div class="bg-[#bc3d60] p-2" style="width:${100-backPercent}%">Lay ${(100-backPercent).toFixed(0)}%</div></div><div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">${[ ["Balance",indicator.balance], ["Momentum",indicator.momentum], ["Delta",`${Number(indicator.delta ?? 0).toFixed(1)}%`], ["Volatility",indicator.volatility] ].map(([label,value])=>`<div class="rounded-lg border border-[#d4e0ec] p-3"><small>${label}</small><b class="block">${escape(value ?? "—")}</b></div>`).join("")}</div></section>`;
            }
            body += `<details class="mt-5 border-t border-[#d4e0ec] pt-3"><summary class="cursor-pointer font-bold">Terms</summary><dl class="space-y-2 py-3 text-sm"><dt class="font-bold">Balance</dt><dd>Back heavy above 58%; Lay heavy below 42%.</dd><dt class="font-bold">Momentum</dt><dd>Change in the favorite runner’s back size: strong above 10%, directional above 3%.</dd><dt class="font-bold">Delta</dt><dd>Percentage change in the current favorite runner’s back size.</dd><dt class="font-bold">Volatility</dt><dd>Standard deviation of log odds returns over the latest ten seconds.</dd></dl></details>`;
        }
        panel.innerHTML = `<div class="relative mb-5 flex items-center justify-between gap-3 pr-16"><h2 id="horseracingModalTitle" class="text-lg font-extrabold">${escape(title)}</h2><button type="button" data-action="expand-modal" aria-label="Expand" class="absolute right-10 top-0 text-xl">↗</button><button type="button" data-action="close-modal" aria-label="Close" class="absolute right-0 top-0 px-2 text-2xl">×</button></div>${body}`;
        panel.scrollTop = scrollTop;
        modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden';
    }
    filters?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]'); if (!button) return;
        selectedFilter = button.dataset.filter;
        filters.querySelectorAll('[data-filter]').forEach((item) => item.classList.toggle('active', item === button));
        closeModal(); load({ reset: true });
    });
    dashboard.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (event.target.id === 'horseracingModal') { closeModal(); return; }
        if (!button) return;
        if (button.dataset.requiresLogin === 'true') { window.location.assign(button.dataset.loginUrl); return; }
        const action = button.dataset.action;
        if (action === 'expand-modal') { const panel = dashboard.querySelector('[data-horseracing-modal-panel]'); panel.classList.toggle('max-w-3xl'); panel.classList.toggle('max-w-6xl'); return; }
        if (action === 'close-modal') { closeModal(); return; }
        const row = button.closest('[data-match-id]');
        if (action === 'open-match') { window.location.assign(row.dataset.horseracingMatchUrl); return; }
        if (['prediction', 'market', 'chart'].includes(action)) { activeModal = { action, matchId: row.dataset.matchId, marketId: row.dataset.marketId ?? null, row: row.cloneNode(true) }; renderModal(); return; }
        if (action === 'vote' && activeModal) { button.disabled = true; await load({ prediction: { match_id: activeModal.matchId, poll: 'winner', value: button.dataset.value } }); return; }
        if (action !== 'favorite' || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch(dashboard.dataset.favoriteUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ sport: 'horseracing', event_key: button.dataset.eventKey, event_id: button.dataset.eventId, team1: button.dataset.team1, team2: button.dataset.team2, start_timestamp: button.dataset.startTimestamp }) });
            if (response.status === 401) { window.location.assign(button.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to save favorite. Please try again.');
            await load();
        } catch (error) { showError(error.message); }
        finally { button.disabled = false; }
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) load({ append: true }); }, { rootMargin: '100px' }).observe(loader());
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') scheduleRefresh(); });
    const subscribe = () => window.Echo?.channel('sports.horseracing.live').listen('.score.updated', scheduleRefresh);
    if (window.Echo) subscribe(); else window.addEventListener('load', subscribe, { once: true });
}

function analyzeHorseChange(previous, current) {
    if (!(previous > 0 && current > 0)) return {direction: 'flat', magnitude: 'tiny', pct: 0, arrow: ''};
    const diff = current - previous;
    const pct = Number((diff / previous * 100).toFixed(8));
    const absolute = Math.abs(pct);
    const direction = diff > 0.001 ? 'up' : diff < -0.001 ? 'down' : 'flat';
    const magnitude = absolute >= 8 ? 'sharp' : absolute >= 5 ? 'large' : absolute >= 3 ? 'medium' : absolute >= 1 ? 'small' : 'tiny';
    const arrow = direction === 'flat' || absolute < 0.1 ? '' : direction === 'up' ? (absolute >= 5 ? '↑↑' : '↑') : (absolute >= 5 ? '↓↓' : '↓');
    return {direction, magnitude, pct, arrow};
}
function horseFavouriteRanks(runners) {
    const prices = [...new Set(runners.map(r => Math.round(Number(r.currentBackOdds) * 100)).filter(p => p > 0))].sort((a,b)=>a-b).slice(0,3);
    return Object.fromEntries(runners.map(r => [String(r.runnerId), prices.indexOf(Math.round(Number(r.currentBackOdds) * 100)) + 1]));
}
function horseSparkline(history) {
    const values = (history ?? []).filter(p => Number(p.timestamp) >= Date.now()/1000 - 30 && Number(p.odds) > 0).slice(-8).map(p => Number(p.odds));
    if (values.length < 2 || Math.max(...values) === Math.min(...values)) return '<svg class="sparkline" viewBox="0 0 100 20" aria-label="No recent odds movement"><path class="sparkline-path flat" d="M0 10 L100 10"/></svg>';
    const min = Math.min(...values); const range = Math.max(...values) - min;
    const direction = values.at(-1) < values[0] ? 'down' : values.at(-1) > values[0] ? 'up' : 'flat';
    const path = values.map((value,i) => `${i ? 'L' : 'M'}${(i/(values.length-1)*100).toFixed(2)} ${(18-(value-min)/range*16).toFixed(2)}`).join(' ');
    return `<svg class="sparkline" viewBox="0 0 100 20" preserveAspectRatio="none" aria-label="Recent back odds trend"><path class="sparkline-path ${direction}" d="${path}"/></svg>`;
}
const horseDetail = document.querySelector('[data-horse-detail]');
if (horseDetail) {
    let detail = JSON.parse(horseDetail.dataset.horsePayload);
    let selectedMarket = 'WIN';
    let timer = null; let loading = false; let queued = false;
    const lastPrices = new Map(); const lastBadges = new Map();
    const escapeHorse = value => String(value ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const price = value => Number(value)>0 ? Number(value).toFixed(2) : '—';
    const container = horseDetail.querySelector('#runners-container');
    const status = horseDetail.querySelector('[data-horse-status]');
    function countdown() {
        const el = horseDetail.querySelector('#countdown-timer');
        const seconds = Math.max(0, Math.floor((Date.parse(detail.race.start_time)-Date.now())/1000));
        let text; let color = 'text-[#5a7d99]';
        if (detail.race.status === 'Finished') { text = 'Race Finished'; }
        else if (!seconds) { text = 'Race Started'; color = 'text-red-600 animate-pulse'; }
        else if (seconds < 300) { text = `Starts in ${Math.floor(seconds/60)}:${String(seconds%60).padStart(2,'0')}`; color = seconds < 60 ? 'text-red-600 animate-pulse' : 'text-orange-600'; }
        else { const minutes = Math.floor(seconds/60); text = minutes >= 60 ? `Starts in ${Math.floor(minutes/60)}h ${minutes%60}m` : `Starts in ${minutes} min`; if (seconds <= 600) color = 'text-blue-600'; }
        if (detail.race.available === false && Date.now() >= Date.parse(detail.race.available_at) && timer === null && !loading) {
            scheduleDetail();
        }
        el.textContent = text; el.className = `text-xs font-semibold ${color}`;
    }
    function renderRunners() {
        status.textContent = detail.race.status === 'Finished' ? 'Finished' : detail.race.market_status === 'SUSPENDED' ? 'Suspended' : 'Upcoming';
        if (detail.market.locked) {
            container.innerHTML = detail.race.available === false ? '<p class="p-8 text-center">Race details and odds become available five minutes before the start.</p>' : '<p class="p-8 text-center">🔒 Log in with an active subscription to view race odds.</p>';
            countdown(); return;
        }
        const market = detail.markets.find(m => m.type === selectedMarket) ?? detail.markets[0];
        if (!market) { container.innerHTML = '<p class="p-8 text-center">No runner data available.</p>'; return; }
        selectedMarket = market.type;
        const selectors = horseDetail.querySelector('[data-horse-markets]');
        const types = [...new Set(detail.markets.map(item=>item.type))];
        if (JSON.stringify([...selectors.querySelectorAll('button')].map(button=>button.dataset.horseMarket)) !== JSON.stringify(types)) {
            selectors.innerHTML = types.map(type=>`<button type="button" data-horse-market="${escapeHorse(type)}" class="rounded-full border border-[#d4e0ec] px-3 py-1 text-xs font-semibold [&.active]:bg-[#0b2a40] [&.active]:text-white">${escapeHorse(type)}</button>`).join('');
        }
        horseDetail.querySelectorAll('[data-horse-market]').forEach(button=>button.classList.toggle('active',button.dataset.horseMarket === selectedMarket));
        const ranks = horseFavouriteRanks(market.runners);
        const maximum = Math.max(0,...market.runners.map(r=>Number(r.pressure_amount ?? 0)));
        container.innerHTML = market.runners.map(runner => {
            const key = `${selectedMarket}|${runner.runnerId}`;
            const rank = ranks[String(runner.runnerId)];
            const label = rank ? ['','🥇 1st Favourite','🥈 2nd Favourite','🥉 3rd Favourite'][rank] : '—';
            const badgeChanged = lastBadges.has(key) && lastBadges.get(key) !== rank;
            lastBadges.set(key,rank);
            const badge = `<span class="fav-badge ${rank ? `fav-${rank}` : 'fav-none'} ${badgeChanged ? 'badge-changed' : ''}">${label}</span>`;
            const previous = lastPrices.get(key);
            const boxes = [['back',Number(runner.currentBackOdds), Number(runner.previousBackOdds)],['lay',Number(runner.layOdds),Number(runner.previousLayOdds)]].map(([side,current,prior],index) => {
                const comparison = previous?.[side] ?? prior;
                const change = analyzeHorseChange(comparison,current);
                const fresh = previous !== undefined && previous[side] !== current && detail.race.market_status !== 'SUSPENDED';
                const flash = fresh && Math.abs(change.pct) >= 3 ? `flash-${change.direction}` : '';
                const history = change.direction !== 'flat' && Math.abs(change.pct) > 0.1 ? `<div class="odds-history"><span class="old-value">${price(comparison)}</span>→<span class="new-value">${price(current)}</span></div>` : '';
                const percent = change.arrow ? `<span class="change-pct ${change.direction}">${change.pct>0?'+':''}${change.pct.toFixed(1)}%</span>` : '';
                const bolt = fresh && Math.abs(change.pct)>=8 ? '<span class="sharp-change">⚡</span>' : '';
                const tooltip = side === 'back' ? `Initial ${price(runner.initialBackOdds)} → Current ${price(current)}` : `Previous ${price(comparison)} → Current ${price(current)}`;
                return `<div class="odd-box ${side}-box ${flash}" data-label="${side === 'back' ? 'Back' : 'Lay'}" tabindex="0" data-horseracing-odds-tooltip="${tooltip}">${bolt}<div class="odd-value">${price(current)}${change.arrow ? `<span class="change-arrow ${change.direction} ${Math.abs(change.pct)>=5?'double':''}">${change.arrow}</span>` : ''}</div>${history}${percent}${index===0?`<div class="sparkline-container">${horseSparkline(runner.movementHistory)}</div>`:''}</div>`;
            }).join('');
            lastPrices.set(key,{back:Number(runner.currentBackOdds),lay:Number(runner.layOdds)});
            const pressure = ['back','lay'].includes(runner.pressure_type) ? runner.pressure_type : 'neutral';
            const amount = Number(runner.pressure_amount ?? 0);
            const width = maximum > 0 ? Math.min(100,amount/maximum*100) : 0;
            const name = escapeHorse(String(runner.runner).replace(/^\d+\.\s*/,''));
            const number = runner.number ?? runner.trap ?? '—';
            return `<div class="runner-row" data-runner-id="${escapeHorse(runner.runnerId)}"><div class="runner-name-mobile"><span>${escapeHorse(number)} ${name}</span>${badge}</div><div class="horse-desktop runner-name flex items-center gap-2 text-sm font-semibold"><span class="text-xs text-gray-400">${escapeHorse(number)}</span>${name}</div><div class="horse-desktop text-center">${badge}</div>${boxes}<div class="pressure-container"><div class="pressure-label"><span style="color:${pressure==='back'?'#1f9a6e':pressure==='lay'?'#c74e4e':'#94a3b8'}">${pressure==='back'?'⬇ BACK':pressure==='lay'?'⬆ LAY':'—'}</span><span>${amount>0?`Available ${amount.toLocaleString(undefined,{maximumFractionDigits:2})}`:'No pressure'}</span></div><div class="pressure-bar-bg"><div class="pressure-bar-fill ${pressure}" style="width:${width}%"></div></div></div></div>`;
        }).join('');
        countdown();
    }
    async function refreshDetail() {
        if (loading || document.visibilityState !== 'visible') { queued = true; return; }
        loading = true;
        try {
            const response = await fetch(horseDetail.dataset.horseUrl, {headers:{Accept:'application/json'},cache:'no-store'});
            if (!response.ok) throw new Error('Unable to refresh race details.');
            detail = await response.json(); renderRunners(); horseDetail.querySelector('[data-horse-error]').classList.add('hidden');
        } catch (error) { const el = horseDetail.querySelector('[data-horse-error]'); el.textContent = error.message; el.classList.remove('hidden'); }
        finally { loading = false; if (queued) { queued = false; scheduleDetail(); } }
    }
    function scheduleDetail() {
        if (timer !== null) return;
        const remaining = Date.parse(detail.race.start_time)-Date.now();
        const interval = remaining<30000 ? 500 : remaining<300000 ? 1250 : remaining<600000 ? 2000 : 3500;
        timer = setTimeout(()=>{timer=null;refreshDetail();},interval);
    }
    horseDetail.querySelector('[data-horse-markets]')?.addEventListener('click',event=>{const button=event.target.closest('[data-horse-market]'); if(button){selectedMarket=button.dataset.horseMarket;renderRunners();}});
    renderRunners(); setInterval(countdown,1000);
    const subscribeDetail = () => { window.Echo?.channel(horseDetail.dataset.raceChannel ?? 'sports.horseracing.live').listen('.score.updated',scheduleDetail); window.Echo?.connector?.pusher?.connection?.bind('connected',scheduleDetail); };
    if(window.Echo) subscribeDetail(); else window.addEventListener('load',subscribeDetail,{once:true});
    document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')scheduleDetail();});
}
