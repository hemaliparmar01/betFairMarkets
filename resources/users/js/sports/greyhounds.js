import { watchRacingFeed } from '../racingFeed.js';

let oddsTooltip = null;
const hideOddsTooltip = () => { oddsTooltip?.remove(); oddsTooltip = null; };
const showOddsTooltip = (event) => {
    const target = event.target.closest('[data-greyhounds-odds-tooltip]');
    if (!target || target.contains(event.relatedTarget)) return;
    hideOddsTooltip();
    oddsTooltip = document.createElement('div');
    oddsTooltip.setAttribute('role', 'tooltip');
    oddsTooltip.dataset.greyhoundsTooltip = '';
    oddsTooltip.className = 'pointer-events-none fixed z-[10001] whitespace-nowrap rounded-md bg-[#0b2a40] px-2 py-1 text-[11px] font-normal text-white shadow-sm dark:bg-[#2a4050] dark:text-[#e8edf2]';
    oddsTooltip.textContent = target.dataset.greyhoundsOddsTooltip;
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
        const target = event.target.closest('[data-greyhounds-odds-tooltip]');
        if (target && !target.contains(event.relatedTarget)) hideOddsTooltip();
    });
}
document.addEventListener('scroll', hideOddsTooltip, true);
document.addEventListener('click', hideOddsTooltip);
window.addEventListener('resize', hideOddsTooltip);

const dashboard = document.getElementById('greyhoundsDashboard');
if (dashboard) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const filters = document.getElementById('greyhoundsFilters');
        const markets = dashboard.dataset.sourceKind === 'markets';
    let selectedFilter = 'all';
    let loadedPages = 1;
    let loading = false;
    let refreshPending = false;
    let pendingAction = null;
    let refreshTimer = null;
    let availabilityTimer = null;
    let activeModal = null;
    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const number = (value) => Number(value) > 0 ? Number(value).toFixed(2) : '—';
    const parse = (value) => { try { return JSON.parse(value ?? '{}'); } catch { return {}; } };
    const loader = () => dashboard.querySelector('[data-greyhounds-lazy-loader]');
    const content = () => dashboard.querySelector(markets ? '[data-greyhounds-market-rows]' : '.fixtures-container');
    const showError = (message) => {
        let notice = dashboard.querySelector('[data-greyhounds-error]');
        if (!notice) { notice = document.createElement('p'); notice.dataset.greyhoundsError = ''; notice.className = 'p-3 text-center text-sm text-red-600'; dashboard.prepend(notice); }
        notice.textContent = message;
    };
    let refreshInterval = 100;
    document.querySelector("[data-greyhounds-speeds]")?.addEventListener("click", (event) => {
        const button = event.target.closest("[data-speed]"); if (!button) return;
        refreshInterval = Math.min(100, Number(button.dataset.speed));
        document.querySelectorAll("[data-greyhounds-speeds] button").forEach((item) => { item.classList.toggle("active",item === button); item.classList.toggle("bg-[#0b2a40]",item === button); item.classList.toggle("!text-white",item === button); });
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
            feedMonitor.update(result.feed);
            hideOddsTooltip();
            const target = content();
            const priceKey = (cell) => `${cell.closest('[data-match-id]')?.dataset.matchId}:${cell.getAttribute('title') ?? 'favorite'}`;
            const priorPrices = new Map([...target.querySelectorAll('[data-greyhounds-odds-tooltip]')].map((cell) => [priceKey(cell), cell.textContent.trim()]));
            const scrollLeft = target.closest('.overflow-x-auto')?.scrollLeft ?? target.scrollLeft;
            if (append) {
                target.querySelector('[data-greyhounds-empty]')?.remove();
                const fragment = document.createElement('template');
                fragment.innerHTML = markets ? `<table><tbody>${result.html}</tbody></table>` : result.html;
                const nodes = markets ? [...fragment.content.querySelector('tbody').children] : [...fragment.content.children];
                if (nodes[0]?.dataset.leagueHeader && nodes[0].dataset.leagueHeader === [...target.querySelectorAll('[data-league-header]')].at(-1)?.dataset.leagueHeader) nodes.shift();
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
            target.querySelectorAll('[data-greyhounds-odds-tooltip]').forEach((cell) => {
                const old = priorPrices.get(priceKey(cell));
                if (old != null && old !== cell.textContent.trim()) {
                    cell.classList.add('!bg-amber-100', 'dark:!bg-amber-950');
                    setTimeout(() => cell.classList.remove('!bg-amber-100', 'dark:!bg-amber-950'), 650);
                }
            });
            loader().dataset.hasMore = result.has_more ? 'true' : 'false';
            loader().dataset.nextPage = result.has_more ? String(loadedPages + 1) : '';
            loader().classList.toggle('hidden', !result.has_more);
            dashboard.querySelector('[data-greyhounds-error]')?.remove();
            scheduleAvailability();
            if (activeModal) {
                const opened = activeModal;
                const visibleRow = [...dashboard.querySelectorAll('[data-match-id]')].find((row) => row.dataset.matchId === opened.matchId);
                if (!visibleRow) {
                    const detailResponse = await fetch(opened.row.dataset.greyhoundsMatchUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'});
                    if (detailResponse.ok) {
                        const detail = await detailResponse.json();
                        opened.row.dataset.greyhoundsMarket = JSON.stringify(detail.market);
                        opened.row.dataset.greyhoundsPrediction = JSON.stringify(detail.prediction);
                    }
                }
                if (activeModal === opened) renderModal();
            }
        } catch (error) { feedMonitor.failed(); showError(error.message); }
        finally {
            loading = false;
            content()?.classList.remove('fixtures-loading');
            content()?.removeAttribute('aria-busy');
            const text = loader()?.querySelector('[data-lazy-loader-text]');
            if (text) text.textContent = '↓ Scroll for more races ↓';
            if (pendingAction) { const action = pendingAction; pendingAction = null; load(action); } else if (refreshPending && document.visibilityState === 'visible') { refreshPending = false; scheduleRefresh(); }
        }
    }
    function closeModal() { const modal = dashboard.querySelector('#greyhoundsModal'); modal?.classList.add('hidden'); modal?.classList.remove('flex'); activeModal = null; document.body.style.overflow = ''; }
    function renderModal() {
        if (!activeModal) return;
        const rows = [...dashboard.querySelectorAll('[data-match-id]')];
        const row = rows.find((candidate) => candidate.dataset.matchId === activeModal.matchId && (!activeModal.marketId || candidate.dataset.marketId === activeModal.marketId)) ?? activeModal.row;
        if (!row) { closeModal(); return; }
        activeModal.row = row.cloneNode(true);
        const modal = dashboard.querySelector('#greyhoundsModal');
        const panel = modal.querySelector('[data-greyhounds-modal-panel]');
        const scrollTop = panel.scrollTop;
        let title = ''; let body = '';
        if (activeModal.action === 'chart') {
            const data = parse(row.dataset.greyhoundsMarket); title = `${data.title ?? 'Race Winner'} · Odds Movement`;
            if (data.available === false) body = '<p>Race details and odds become available five minutes before the start.</p>';
            else if (data.locked) body = '<p>Login with an active subscription to view odds.</p>';
            else body = (data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? 0);
                const history = (runner.backOddsHistory ?? []).filter(point => Number(runner.initialBackOdds) > 1 && Number(point.timestamp) >= Number(runner.initialBackOddsTimestamp) && Number(point.odds) > 1);
                const values = history.map((point) => Number(point.odds));
                const minimum = Math.min(...values); const maximum = Math.max(...values); const range = maximum - minimum || 1;
                const points = values.map((value, index) => `${10 + index * 380 / Math.max(1, values.length - 1)},${90 - (value - minimum) / range * 70}`).join(' ');
                return `<section class="mb-4 rounded-xl border border-[#d4e0ec] p-4"><h3 class="font-bold">${escape(runner.runner)}</h3><p class="mt-1 text-sm">Opening ${number(initial)} → Current ${number(current)}</p>${values.length ? `<svg viewBox="0 0 400 110" class="mt-3 h-32 w-full" role="img" aria-label="Back odds history"><polyline fill="none" stroke="#1a6b9c" stroke-width="3" points="${points}" /></svg>` : '<p class="mt-3 text-sm">Waiting for meaningful market activity.</p>'}</section>`;
            }).join('') || '<p>Market data is not available yet.</p>';
        } else if (activeModal.action === 'prediction') {
            const data = parse(row.dataset.greyhoundsPrediction); title = 'Race Predictions';
            const total = Object.values(data.counts ?? {}).reduce((sum, count) => sum + Number(count), 0);
            body = `<p class="mb-4 text-sm">${data.locked ? 'Voting closes at the off time.' : data.vote ? 'Your prediction has been recorded.' : 'Who will win this race?'}</p><div class="grid gap-3">${Object.entries(data.choices ?? {}).map(([value, label]) => `<button type="button" data-action="vote" data-value="${escape(value)}" ${data.locked || data.vote ? 'disabled' : ''} class="rounded-xl border border-[#b9d7f3] p-3 text-left disabled:cursor-default ${data.vote === value ? 'bg-[#d5edff] text-[#1a6b9c]' : ''}"><b>${escape(label)}</b><span class="float-right">${Number(data.counts?.[value] ?? 0)} votes · ${total ? Math.round(Number(data.counts?.[value] ?? 0) / total * 100) : 0}%</span></button>`).join('')}</div>`;
        } else {
            const data = parse(row.dataset.greyhoundsMarket); title = data.title ?? 'Race Details';
            body = `<p class="mb-4 text-sm">🐕 ${escape(data.status)} · ${escape(data.minutes)}</p>`;
            if (data.available === false) body += '<p>Race details and odds become available five minutes before the start.</p>';
            else if (data.locked) body += '<p>Login with an active subscription to view odds.</p>';
            else body += `<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>${['Selection', 'Opening', 'Back', 'Size', 'Lay', 'Size'].map((label) => `<th class="px-2 py-2">${label}</th>`).join('')}</tr></thead><tbody>${(data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? runner.backOdds ?? 0);
                const arrow = initial > 0 && current > 0 ? current > initial ? ' ↑' : current < initial ? ' ↓' : '' : '';
                return `<tr class="border-t border-[#e3ecf5]"><td class="px-2 py-3">${escape(runner.runner)}${String(runner.runnerId) === String(data.indicators?.favorite_id) ? " ⭐" : ""}</td>${[initial, current, runner.backSize, runner.layOdds, runner.laySize].map((value, index) => `<td ${index === 1 && current > 0 ? `tabindex="0" data-greyhounds-odds-tooltip="${number(initial)} → ${number(current)}"` : ''} class="whitespace-nowrap px-2 py-3 font-semibold ${index === 1 ? 'bg-[#a6d8ff] text-[#0b2a40]' : index === 3 ? 'bg-[#fac9d1] text-[#4a1520]' : ''}">${(index === 2 || index === 4) && Number.isFinite(Number(value)) && Number(value) >= 0 ? Number(value).toFixed(2) : number(value)}${index === 1 ? arrow : ''}</td>`).join('')}</tr>`;
            }).join('')}</tbody></table></div><a class="mt-4 inline-block rounded-xl bg-[#0b2a40] px-4 py-2 text-sm font-bold text-white" href="${escape(row.dataset.greyhoundsMatchUrl)}">Race markets &amp; charts →</a>`;
        }
        hideOddsTooltip();
        if (activeModal.action === 'market') {
            const data = parse(row.dataset.greyhoundsMarket);
            if (!data.locked) {
                const indicator = data.indicators ?? {};
                const backPercent = Number(indicator.back_percent ?? 50);
                body += `<section class="mt-5"><h3 class="mb-2 font-bold">Available Liquidity Distribution</h3><p class="mb-2 text-xs">Sizes are available offers, not matched trades. Traded volume is not supplied by this feed.</p><div class="flex overflow-hidden rounded-lg text-center text-xs text-white"><div class="bg-[#1a6b9c] p-2" style="width:${backPercent}%">Back ${backPercent.toFixed(0)}%</div><div class="bg-[#bc3d60] p-2" style="width:${100-backPercent}%">Lay ${(100-backPercent).toFixed(0)}%</div></div><div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">${[ ["Balance",indicator.balance], ["Size momentum",indicator.momentum], ["Size delta",`${Number(indicator.delta ?? 0).toFixed(1)}%`], ["Volatility",indicator.volatility] ].map(([label,value])=>`<div class="rounded-lg border border-[#d4e0ec] p-3"><small>${label}</small><b class="block">${escape(value ?? "—")}</b></div>`).join("")}</div></section>`;
            }
            body += `<details class="mt-5 border-t border-[#d4e0ec] pt-3"><summary class="cursor-pointer font-bold">Terms</summary><dl class="space-y-2 py-3 text-sm"><dt class="font-bold">Balance</dt><dd>Back heavy above 58%; Lay heavy below 42%.</dd><dt class="font-bold">Momentum</dt><dd>Change in the favorite runner’s back size: strong above 10%, directional above 3%.</dd><dt class="font-bold">Delta</dt><dd>Percentage change in the current favorite runner’s back size.</dd><dt class="font-bold">Volatility</dt><dd>Standard deviation of log odds returns over the latest ten seconds.</dd></dl></details>`;
        }
        panel.innerHTML = `<div class="relative mb-5 flex items-center justify-between gap-3 pr-16"><h2 id="greyhoundsModalTitle" class="text-lg font-extrabold">${escape(title)}</h2><button type="button" data-action="expand-modal" aria-label="Expand" class="absolute right-10 top-0 text-xl">↗</button><button type="button" data-action="close-modal" aria-label="Close" class="absolute right-0 top-0 px-2 text-2xl">×</button></div>${body}`;
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
        if (event.target.id === 'greyhoundsModal') { closeModal(); return; }
        if (!button) return;
        if (button.dataset.requiresLogin === 'true') { window.location.assign(button.dataset.loginUrl); return; }
        const action = button.dataset.action;
        if (action === 'expand-modal') { const panel = dashboard.querySelector('[data-greyhounds-modal-panel]'); panel.classList.toggle('max-w-3xl'); panel.classList.toggle('max-w-6xl'); return; }
        if (action === 'close-modal') { closeModal(); return; }
        const row = button.closest('[data-match-id]');
        if (action === 'open-match') { window.location.assign(row.dataset.greyhoundsMatchUrl); return; }
        if (['prediction', 'market', 'chart'].includes(action)) { activeModal = { action, matchId: row.dataset.matchId, marketId: row.dataset.marketId ?? null, row: row.cloneNode(true) }; renderModal(); return; }
        if (action === 'vote' && activeModal) { button.disabled = true; await load({ prediction: { match_id: activeModal.matchId, poll: 'winner', value: button.dataset.value } }); return; }
        if (action !== 'favorite' || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch(dashboard.dataset.favoriteUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ sport: 'greyhounds', event_key: button.dataset.eventKey, event_id: button.dataset.eventId, team1: button.dataset.team1, team2: button.dataset.team2, start_timestamp: button.dataset.startTimestamp }) });
            if (response.status === 401) { window.location.assign(button.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to save favorite. Please try again.');
            await load();
        } catch (error) { showError(error.message); }
        finally { button.disabled = false; }
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) load({ append: true }); }, { rootMargin: '100px' }).observe(loader());
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') scheduleRefresh(); });
    function scheduleAvailability() {
        clearTimeout(availabilityTimer);
        const times = [...dashboard.querySelectorAll('[data-details-unlock-at]')].map(row=>Number(row.dataset.detailsUnlockAt)*1000).filter(time=>time>Date.now());
        if (times.length) availabilityTimer = setTimeout(()=>load(),Math.max(0,Math.min(...times)-Date.now()+50));
    }
    scheduleAvailability();
    const feedMonitor = watchRacingFeed(dashboard, scheduleRefresh, 'greyhounds');
}
