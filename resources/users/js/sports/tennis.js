import { patchRealtimeChildren, patchRealtimeHtml, markRealtimeUnavailable } from '../realtimeDom.js';
import { renderOddsHistory } from '../oddsHistory';
let oddsTooltip = null;
const hideOddsTooltip = () => { oddsTooltip?.remove(); oddsTooltip = null; };
const showOddsTooltip = (event) => {
    const target = event.target.closest('[data-tennis-odds-tooltip]');
    if (!target || target.contains(event.relatedTarget)) return;
    hideOddsTooltip();
    oddsTooltip = document.createElement('div');
    oddsTooltip.setAttribute('role', 'tooltip');
    oddsTooltip.dataset.tennisTooltip = '';
    oddsTooltip.className = 'pointer-events-none fixed z-[10001] whitespace-nowrap rounded-md bg-[#0b2a40] px-2 py-1 text-[11px] font-normal text-white shadow-sm dark:bg-[#2a4050] dark:text-[#e8edf2]';
    oddsTooltip.textContent = target.dataset.tennisOddsTooltip;
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
        const target = event.target.closest('[data-tennis-odds-tooltip]');
        if (target && !target.contains(event.relatedTarget)) hideOddsTooltip();
    });
}
document.addEventListener('scroll', hideOddsTooltip, true);
document.addEventListener('click', hideOddsTooltip);
window.addEventListener('resize', hideOddsTooltip);

const dashboard = document.getElementById('tennisDashboard');
if (dashboard) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const filters = document.getElementById('tennisFilters');
    const dropdown = document.getElementById('marketDropdown');
    const markets = dashboard.dataset.sourceKind === 'markets';
    let selectedFilter = 'all';
    let loadedPages = 1;
    let loading = false;
    let refreshPending = false;
    let pendingAction = null;
    let refreshTimer = null;
    let activeModal = null;
    let highlightTimer = null;
    let pointHighlightTimer = null;
    const pointStates = new Map();
    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const number = (value) => Number(value) > 0 ? Number(value).toFixed(2) : '—';
    const parse = (value) => { try { return JSON.parse(value ?? '{}'); } catch { return {}; } };
    const loader = () => dashboard.querySelector('[data-tennis-lazy-loader]');
    const content = () => dashboard.querySelector(markets ? '[data-tennis-market-rows]' : '.fixtures-container');
    const pointValue = (value) => value == null ? null : String(value).toUpperCase().replace(/^AD$/, 'A');
    const pointWinners = (previous, current) => {
        if (['home', 'away'].some((side) => pointValue(previous[side]?.point) === null || pointValue(current[side]?.point) === null)) return [];
        if (pointValue(current.home.point) === '0' && pointValue(current.away.point) === '0') return [];
        const period = `period${current.current_set ?? 1}`;
        const newGame = previous.current_set !== current.current_set || previous.home?.[period] !== current.home?.[period] || previous.away?.[period] !== current.away?.[period];
        if (newGame) {
            return ['home', 'away'].filter((side) => pointValue(current[side].point) !== '0');
        }
        const winners = new Set();
        for (const side of ['home', 'away']) {
            const other = side === 'home' ? 'away' : 'home';
            const before = pointValue(previous[side].point); const after = pointValue(current[side].point);
            if (before !== after && (after === 'A' || Number(after) > Number(before))) winners.add(side);
            if (before === 'A' && after === '40') winners.add(other);
        }
        return [...winners];
    };
    function paintPoints(row, state) {
        row.dataset.matchStatus = state.status;
        row.querySelectorAll('.match-score[data-score-status]').forEach((score) => { score.dataset.scoreStatus = state.status; });
        const points = row.querySelector('[data-tennis-points]');
        if (!points) return;
        const visible = state.status === 'Live' && state.home?.point != null && state.away?.point != null;
        points.classList.toggle('hidden', !visible);
        for (const side of ['home', 'away']) {
            const number = points.querySelector(`[data-tennis-point="${side}"]`);
            number.textContent = state[side]?.point ?? '—';
            number.classList.toggle('highlight-purple', visible && state.highlights[side] > Date.now());
        }
        const stats = parse(row.dataset.tennisStats);
        row.dataset.tennisStats = JSON.stringify({ ...stats, home: state.home, away: state.away, current_set: state.current_set });
        row.dataset.tennisScoreVersion = String(state.version);
    }
    function expirePointHighlights() {
        window.clearTimeout(pointHighlightTimer);
        let nextExpiry = Infinity;
        dashboard.querySelectorAll('[data-tennis-points]').forEach((points) => {
            const row = points.closest('[data-match-id]');
            const state = pointStates.get(row.dataset.matchId);
            if (!state) return;
            for (const side of ['home', 'away']) {
                const remaining = state.highlights[side] - Date.now();
                points.querySelector(`[data-tennis-point="${side}"]`).classList.toggle('highlight-purple', state.status === 'Live' && remaining > 0);
                if (remaining > 0) nextExpiry = Math.min(nextExpiry, remaining);
            }
        });
        pointHighlightTimer = Number.isFinite(nextExpiry) ? window.setTimeout(expirePointHighlights, nextExpiry) : null;
    }
    function rememberPoints(row, score, liveUpdate = false) {
        const previous = pointStates.get(row.dataset.matchId);
        const version = Number(score.version ?? 0);
        if (previous && previous.version > 0 && version <= previous.version) { paintPoints(row, previous); return; }
        const highlights = { ...(previous?.highlights ?? { home: 0, away: 0 }) };
        if (previous && liveUpdate) {
            for (const side of ['home', 'away']) {
                if (pointValue(previous[side]?.point) !== pointValue(score[side]?.point)) highlights[side] = 0;
            }
            const winners = score.status === 'Live' ? pointWinners(previous, score) : [];
            for (const winner of winners) highlights[winner] = Date.now() + 5000;
        }
        const state = { ...score, version, highlights };
        pointStates.set(row.dataset.matchId, state);
        paintPoints(row, state);
    }
    function syncPointRows() {
        const visibleIds = new Set();
        dashboard.querySelectorAll('[data-tennis-stats][data-match-id]').forEach((row) => {
            const data = parse(row.dataset.tennisStats);
            visibleIds.add(row.dataset.matchId);
            rememberPoints(row, { version: Number(row.dataset.tennisScoreVersion ?? 0), status: row.dataset.tennisStatus, current_set: data.current_set, home: data.home ?? {}, away: data.away ?? {} }, true);
        });
        for (const id of pointStates.keys()) { if (!visibleIds.has(id)) pointStates.delete(id); }
        expirePointHighlights();
    }
    const showError = (message) => {
        let notice = dashboard.querySelector('[data-tennis-error]');
        if (!notice) { notice = document.createElement('p'); notice.dataset.tennisError = ''; notice.className = 'p-3 text-center text-sm text-red-600'; dashboard.prepend(notice); }
        notice.textContent = message;
    };
    function scheduleRefresh() {
        if (refreshTimer !== null) return;
        refreshTimer = window.setTimeout(() => { refreshTimer = null; load(); }, 700);
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
        const params = { type: selectedFilter, market_name: dropdown?.value ?? '', page, per_page: perPage, append };
        if (prediction) params.prediction = prediction;
        const text = loader()?.querySelector('[data-lazy-loader-text]');
        if (text) text.textContent = 'Loading matches…';
        try {
            const url = new URL(dashboard.dataset.sourceUrl, window.location.origin);
            const options = { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' };
            if (dashboard.dataset.sourceMethod === 'POST') { options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(params); }
            else Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value === true ? '1' : value === false ? '0' : String(value)));
            const response = await fetch(url, options);
            if (response.status === 401) { window.location.assign(dashboard.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to load matches. Please try again.');
            const result = await response.json();
            hideOddsTooltip();
            const target = content();
            const scrollLeft = target.closest('.overflow-x-auto')?.scrollLeft ?? target.scrollLeft;
            if (append) {
                target.querySelector('[data-tennis-empty]')?.remove();
                const fragment = document.createElement('template');
                fragment.innerHTML = markets ? `<table><tbody>${result.html}</tbody></table>` : result.html;
                const nodes = markets ? [...fragment.content.querySelector('tbody').children] : [...fragment.content.children];
                if (!markets && nodes[0]?.dataset.leagueHeader === [...target.querySelectorAll('[data-league-header]')].at(-1)?.dataset.leagueHeader) nodes.shift();
                nodes.forEach((node) => target.append(node));
                loadedPages++;
            } else {
                if (markets) patchRealtimeHtml(target, result.html);
                else {
                    const fragment = document.createElement('template'); fragment.innerHTML = result.html;
                    patchRealtimeChildren(target, fragment.content.querySelector('.fixtures-container'));
                }
                if (reset) loadedPages = 1;
            }
            const overflow = target.closest('.overflow-x-auto') ?? target; overflow.scrollLeft = scrollLeft;
            loader().dataset.hasMore = result.has_more ? 'true' : 'false';
            loader().dataset.nextPage = result.has_more ? String(loadedPages + 1) : '';
            loader().classList.toggle('hidden', !result.has_more);
            dashboard.querySelector('[data-tennis-error]')?.remove();
            syncPointRows();
            if (activeModal) renderModal();
            expireHighlights();
        } catch (error) { showError(error.message); }
        finally {
            loading = false;
            content()?.classList.remove('fixtures-loading');
            content()?.removeAttribute('aria-busy');
            const text = loader()?.querySelector('[data-lazy-loader-text]');
            if (text) text.textContent = '↓ Scroll for more matches ↓';
            if (pendingAction) { const action = pendingAction; pendingAction = null; load(action); } else if (refreshPending && document.visibilityState === 'visible') { refreshPending = false; scheduleRefresh(); }
        }
    }
    function expireHighlights() {
        window.clearTimeout(highlightTimer);
        let nextExpiry = Infinity;
        dashboard.querySelectorAll('[data-incident-highlight-until]').forEach((row) => {
            const remaining = Number(row.dataset.incidentHighlightUntil) * 1000 - Date.now();
            if (remaining <= 0) { row.classList.remove('!bg-[#ffe4ec]', 'dark:!bg-[#4a2633]'); row.querySelector('[data-incident-indicator]')?.remove(); delete row.dataset.incidentHighlightUntil; }
            else nextExpiry = Math.min(nextExpiry, remaining);
        });
        if (Number.isFinite(nextExpiry)) highlightTimer = window.setTimeout(expireHighlights, nextExpiry + 50);
    }
    function closeModal() { const modal = dashboard.querySelector('#tennisModal'); modal?.classList.add('hidden'); modal?.classList.remove('flex'); activeModal = null; document.body.style.overflow = ''; }
    function renderModal() {
        const rows = [...dashboard.querySelectorAll('[data-match-id]')];
        const row = rows.find((candidate) => candidate.dataset.matchId === activeModal.matchId && (!activeModal.marketId || candidate.dataset.marketId === activeModal.marketId));
        if (!row) { const panel = dashboard.querySelector('[data-tennis-modal-panel]'); if (panel) markRealtimeUnavailable(panel, true); return; }
        const modal = dashboard.querySelector('#tennisModal');
        const panel = modal.querySelector('[data-tennis-modal-panel]');
        const scoreStatus = escape(row.dataset.matchStatus ?? row.dataset.tennisStatus ?? '');
        let title = ''; let body = '';
        if (activeModal.action === 'stats') {
            const data = parse(row.dataset.tennisStats); title = 'Live Match Statistics';
            if (data.surface) body += `<p class="mb-3 text-sm text-[#5a7d99]">${escape(data.surface)}</p>`;
            body += `<div class="grid gap-3 sm:grid-cols-2">${[['home', data.team1], ['away', data.team2]].map(([side, team]) => {
                const score = data[side] ?? {};
                return `<section class="rounded-xl border border-[#d4e0ec] p-4"><h3 class="mb-2 font-bold">${escape(team)}${data.serving === side ? ' <span class="text-amber-500">● Serving</span>' : ''}</h3><p class="mb-2 text-xl font-bold"><span class="match-score" data-score-status="${scoreStatus}">${escape(score.current ?? score.display ?? '—')}</span> <small class="text-xs font-normal">sets</small></p>${score.point != null ? `<p class="mb-2 text-sm">Current point: <b>${escape(score.point)}</b></p>` : ''}${[1, 2, 3, 4, 5].filter((set) => score[`period${set}`] != null).map((set) => `<p class="py-1">${escape(data.periods?.[`period${set}`] ?? `Set ${set}`)}: <b>${escape(score[`period${set}`])}</b>${score[`period${set}TieBreak`] != null ? ` <small>(TB ${escape(score[`period${set}TieBreak`])})</small>` : ''}</p>`).join('')}</section>`;
            }).join('')}</div>`;
            const statistics = Array.isArray(data.statistics) ? data.statistics : [data.statistics];
            body += statistics.map((period) => `<h3 class="mt-5 text-xs font-bold uppercase text-[#5a7d99]">${escape(period?.period === 'ALL' ? 'Match' : period?.period ?? '')}</h3>${(period?.groups ?? []).map((group) => `<h4 class="mt-3 font-bold">${escape(group.groupName)}</h4>${(group.statisticsItems ?? []).map((item) => `<div class="grid grid-cols-[1fr_2fr_1fr] border-b border-[#e3ecf5] py-2 text-center text-sm"><span>${escape(item.home ?? item.homeValue ?? '—')}</span><span>${escape(item.name)}</span><span>${escape(item.away ?? item.awayValue ?? '—')}</span></div>`).join('')}`).join('')}`).join('');
        } else if (activeModal.action === 'chart') {
            const data = parse(row.dataset.tennisChart); title = `${data.title ?? 'Match Winner'} · Odds Movement`;
            if (data.locked) body = '<p>Login with an active subscription to view odds.</p>';
            else body = (data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? 0);
                const history = (runner.backOddsHistory ?? []).filter((point) => initial > 1 && Number(point.timestamp) >= Number(runner.initialBackOddsTimestamp) && Number(point.odds) > 1);
                const values = history.map((point) => Number(point.odds));
                const minimum = Math.min(...values); const maximum = Math.max(...values); const range = maximum - minimum || 1;
                const points = values.map((value, index) => `${10 + index * 380 / Math.max(1, values.length - 1)},${90 - (value - minimum) / range * 70}`).join(' ');
                return `<section class="mb-4 rounded-xl border border-[#d4e0ec] p-4"><h3 class="font-bold">${escape(runner.runner)}</h3><p class="mt-1 text-sm">Initial ${number(initial)} → Current ${number(current)}</p>${values.length ? `<svg viewBox="0 0 400 110" class="mt-3 h-32 w-full" role="img" aria-label="Back odds history"><polyline fill="none" stroke="#1a6b9c" stroke-width="3" points="${points}" /></svg>` : '<p class="mt-3 text-sm">No odds history available.</p>'}</section>`;
            }).join('') || '<p>Market data is not available yet.</p>';
        } else if (activeModal.action === 'prediction') {
            const data = parse(row.dataset.tennisPrediction); title = 'Match Predictions';
            const total = Object.values(data.counts ?? {}).reduce((sum, count) => sum + Number(count), 0);
            body = `<p class="mb-4 text-sm">${data.locked ? 'Voting closes when the match starts.' : data.vote ? 'Your prediction has been recorded.' : 'Who will win this match?'}</p><div class="grid gap-3">${Object.entries(data.choices ?? {}).map(([value, label]) => `<button type="button" data-action="vote" data-value="${escape(value)}" ${data.locked || data.vote ? 'disabled' : ''} class="rounded-xl border border-[#b9d7f3] p-3 text-left disabled:cursor-default ${data.vote === value ? 'bg-[#d5edff] text-[#1a6b9c]' : ''}"><b>${escape(label)}</b><span class="float-right">${Number(data.counts?.[value] ?? 0)} votes · ${total ? Math.round(Number(data.counts?.[value] ?? 0) / total * 100) : 0}%</span></button>`).join('')}</div>`;
        } else {
            const data = parse(row.dataset.tennisMarket); title = data.title ?? 'Market Details';
            body = `<p class="mb-2 font-bold">${escape(data.team1)} vs ${escape(data.team2)}</p><p class="mb-4 text-sm"><span class="match-score" data-score-status="${escape(data.status)}">${escape(data.score)}</span> · ${escape(data.status)} · ${escape(data.minutes)}</p>`;
            if (data.locked) body += '<p>Login with an active subscription to view odds.</p>';
            else body += `<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>${['Selection', 'Initial', 'Back', 'Size', 'Lay', 'Size'].map((label) => `<th class="px-2 py-2">${label}</th>`).join('')}</tr></thead><tbody>${(data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? runner.backOdds ?? 0);
                const arrow = initial > 0 && current > 0 ? current > initial ? ' ↑' : current < initial ? ' ↓' : '' : '';
                return `<tr class="border-t border-[#e3ecf5]"><td class="px-2 py-3">${escape(runner.runner)}${renderOddsHistory(runner)}</td>${[initial, current, runner.backSize, runner.layOdds, runner.laySize].map((value, index) => `<td ${index === 1 && current > 0 ? `tabindex="0" data-tennis-odds-tooltip="${number(initial)} ${current > initial ? '&lt;&lt;' : '&gt;&gt;'} ${number(current)}"` : ''} class="whitespace-nowrap px-2 py-3 font-semibold">${number(value)}${index === 1 ? arrow : ''}</td>`).join('')}</tr>`;
            }).join('')}</tbody></table></div><a class="mt-4 inline-block rounded-xl bg-[#0b2a40] px-4 py-2 text-sm font-bold text-white" href="${escape(row.dataset.tennisMatchUrl)}">Match markets &amp; charts →</a>`;
        }
        hideOddsTooltip();
        patchRealtimeHtml(panel, `<div class="mb-5 flex items-center justify-between gap-3"><h2 id="tennisModalTitle" class="text-lg font-extrabold">${escape(title)}</h2><button type="button" data-action="close-modal" aria-label="Close" class="px-2 text-2xl">×</button></div>${body}`);
        modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden';
    }
    filters?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]'); if (!button) return;
        selectedFilter = button.dataset.filter;
        filters.querySelectorAll('[data-filter]').forEach((item) => item.classList.toggle('active', item === button));
        closeModal(); load({ reset: true });
    });
    dropdown?.addEventListener('change', () => { closeModal(); load({ reset: true }); });
    dashboard.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (event.target.id === 'tennisModal') { closeModal(); return; }
        if (!button) {
            const row = event.target.closest('[data-tennis-match-url]');
            if (row && !event.target.closest('a, button, input, select, textarea, [role="dialog"]')) {
                window.location.assign(row.dataset.tennisMatchUrl);
            }
            return;
        }
        if (button.dataset.requiresLogin === 'true') { window.location.assign(button.dataset.loginUrl); return; }
        const action = button.dataset.action;
        if (action === 'close-modal') { closeModal(); return; }
        const row = button.closest('[data-match-id]');
        if (action === 'open-match') { window.location.assign(row.dataset.tennisMatchUrl); return; }
        if (['stats', 'prediction', 'market', 'chart'].includes(action)) { activeModal = { action, matchId: row.dataset.matchId, marketId: row.dataset.marketId ?? null }; renderModal(); return; }
        if (action === 'vote' && activeModal) { button.disabled = true; await load({ prediction: { match_id: activeModal.matchId, poll: '1x2', value: button.dataset.value } }); return; }
        if (action !== 'favorite' || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch(dashboard.dataset.favoriteUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ sport: 'tennis', event_key: button.dataset.eventKey, event_id: button.dataset.eventId, team1: button.dataset.team1, team2: button.dataset.team2, start_timestamp: button.dataset.startTimestamp }) });
            if (response.status === 401) { window.location.assign(button.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to save favorite. Please try again.');
            await load();
        } catch (error) { showError(error.message); }
        finally { button.disabled = false; }
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) load({ append: true }); }, { rootMargin: '100px' }).observe(loader());
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') scheduleRefresh(); });
    const receiveScoreUpdate = (event) => {
        const rows = new Map([...dashboard.querySelectorAll('[data-tennis-points]')].map((points) => { const row = points.closest('[data-match-id]'); return [row.dataset.matchId, row]; }));
        for (const score of event.scores ?? []) {
            const row = rows.get(String(score.id));
            if (row) rememberPoints(row, score, true);
        }
        expirePointHighlights();
        if (activeModal?.action === 'stats') renderModal();
        scheduleRefresh();
    };
    const subscribe = () => window.Echo?.channel('sports.tennis.live').listen('.score.updated', receiveScoreUpdate);
    if (window.Echo) subscribe(); else window.addEventListener('load', subscribe, { once: true });
    expireHighlights();
    syncPointRows();
}
