let oddsTooltip = null;
const hideOddsTooltip = () => { oddsTooltip?.remove(); oddsTooltip = null; };
const showOddsTooltip = (event) => {
    const target = event.target.closest('[data-basketball-odds-tooltip]');
    if (!target || target.contains(event.relatedTarget)) return;
    hideOddsTooltip();
    oddsTooltip = document.createElement('div');
    oddsTooltip.setAttribute('role', 'tooltip');
    oddsTooltip.dataset.basketballTooltip = '';
    oddsTooltip.className = 'pointer-events-none fixed z-[10001] whitespace-nowrap rounded-md bg-[#0b2a40] px-2 py-1 text-[11px] font-normal text-white shadow-sm dark:bg-[#2a4050] dark:text-[#e8edf2]';
    oddsTooltip.textContent = target.dataset.basketballOddsTooltip;
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
        const target = event.target.closest('[data-basketball-odds-tooltip]');
        if (target && !target.contains(event.relatedTarget)) hideOddsTooltip();
    });
}
document.addEventListener('scroll', hideOddsTooltip, true);
document.addEventListener('click', hideOddsTooltip);
window.addEventListener('resize', hideOddsTooltip);

const dashboard = document.getElementById('basketballDashboard');
if (dashboard) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const filters = document.getElementById('basketballFilters');
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
    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const number = (value) => Number(value) > 0 ? Number(value).toFixed(2) : '—';
    const parse = (value) => { try { return JSON.parse(value ?? '{}'); } catch { return {}; } };
    const loader = () => dashboard.querySelector('[data-basketball-lazy-loader]');
    const content = () => dashboard.querySelector(markets ? '[data-basketball-market-rows]' : '.fixtures-container');
    const showError = (message) => {
        let notice = dashboard.querySelector('[data-basketball-error]');
        if (!notice) { notice = document.createElement('p'); notice.dataset.basketballError = ''; notice.className = 'p-3 text-center text-sm text-red-600'; dashboard.prepend(notice); }
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
                target.querySelector('[data-basketball-empty]')?.remove();
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
            loader().dataset.hasMore = result.has_more ? 'true' : 'false';
            loader().dataset.nextPage = result.has_more ? String(loadedPages + 1) : '';
            loader().classList.toggle('hidden', !result.has_more);
            dashboard.querySelector('[data-basketball-error]')?.remove();
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
    function closeModal() { const modal = dashboard.querySelector('#basketballModal'); modal?.classList.add('hidden'); modal?.classList.remove('flex'); activeModal = null; document.body.style.overflow = ''; }
    function renderModal() {
        const rows = [...dashboard.querySelectorAll('[data-match-id]')];
        const row = rows.find((candidate) => candidate.dataset.matchId === activeModal.matchId && (!activeModal.marketId || candidate.dataset.marketId === activeModal.marketId));
        if (!row) { closeModal(); return; }
        const modal = dashboard.querySelector('#basketballModal');
        const panel = modal.querySelector('[data-basketball-modal-panel]');
        const scrollTop = panel.scrollTop;
        let title = ''; let body = '';
        if (activeModal.action === 'stats') {
            const data = parse(row.dataset.basketballStats); title = 'Live Match Statistics';
            body = `<div class="grid gap-3 sm:grid-cols-2">${[['home', data.team1], ['away', data.team2]].map(([side, team]) => {
                const score = data[side] ?? {};
                return `<section class="rounded-xl border border-[#d4e0ec] p-4"><h3 class="mb-2 font-bold">${escape(team)}</h3><p class="mb-2 text-xl font-bold">${escape(score.current ?? score.display ?? '—')}</p>${[['period1', 'Q1'], ['period2', 'Q2'], ['period3', 'Q3'], ['period4', 'Q4'], ['overtime', 'OT']].filter(([key]) => score[key] != null).map(([key, label]) => `<p class="py-1">${escape(data.periods?.[key] ?? label)}: <b>${escape(score[key])}</b></p>`).join('')}</section>`;
            }).join('')}</div>`;
            const statistics = Array.isArray(data.statistics) ? data.statistics : [data.statistics];
            body += statistics.map((period) => (period?.groups ?? []).map((group) => `<h3 class="mt-4 font-bold">${escape(group.groupName)}</h3>${(group.statisticsItems ?? []).map((item) => `<div class="grid grid-cols-[1fr_2fr_1fr] border-b border-[#e3ecf5] py-2 text-center text-sm"><span>${escape(item.home ?? item.homeValue ?? '—')}</span><span>${escape(item.name)}</span><span>${escape(item.away ?? item.awayValue ?? '—')}</span></div>`).join('')}`).join('')).join('');
        } else if (activeModal.action === 'prediction') {
            const data = parse(row.dataset.basketballPrediction); title = 'Match Predictions';
            const total = Object.values(data.counts ?? {}).reduce((sum, count) => sum + Number(count), 0);
            body = `<p class="mb-4 text-sm">${data.locked ? 'Voting closes when the match starts.' : data.vote ? 'Your prediction has been recorded.' : 'Who will win this match?'}</p><div class="grid gap-3">${Object.entries(data.choices ?? {}).map(([value, label]) => `<button type="button" data-action="vote" data-value="${escape(value)}" ${data.locked || data.vote ? 'disabled' : ''} class="rounded-xl border border-[#b9d7f3] p-3 text-left disabled:cursor-default ${data.vote === value ? 'bg-[#d5edff] text-[#1a6b9c]' : ''}"><b>${escape(label)}</b><span class="float-right">${Number(data.counts?.[value] ?? 0)} votes · ${total ? Math.round(Number(data.counts?.[value] ?? 0) / total * 100) : 0}%</span></button>`).join('')}</div>`;
        } else {
            const data = parse(row.dataset.basketballMarket); title = data.title ?? 'Market Details';
            body = `<p class="mb-2 font-bold">${escape(data.team1)} vs ${escape(data.team2)}</p><p class="mb-4 text-sm">${escape(data.score)} · ${escape(data.status)} · ${escape(data.minutes)}</p>`;
            if (data.locked) body += '<p>Login with an active subscription to view odds.</p>';
            else body += `<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>${['Selection', 'Initial', 'Back', 'Size', 'Lay', 'Size'].map((label) => `<th class="px-2 py-2">${label}</th>`).join('')}</tr></thead><tbody>${(data.runners ?? []).map((runner) => {
                const initial = Number(runner.initialBackOdds ?? 0); const current = Number(runner.currentBackOdds ?? runner.backOdds ?? 0);
                const arrow = initial > 0 && current > 0 ? current > initial ? ' ↑' : current < initial ? ' ↓' : '' : '';
                return `<tr class="border-t border-[#e3ecf5]"><td class="px-2 py-3">${escape(runner.runner)}</td>${[initial, current, runner.backSize, runner.layOdds, runner.laySize].map((value, index) => `<td ${index === 1 && current > 0 ? `tabindex="0" data-basketball-odds-tooltip="${number(initial)} ${current > initial ? '&lt;&lt;' : '&gt;&gt;'} ${number(current)}"` : ''} class="whitespace-nowrap px-2 py-3 font-semibold">${number(value)}${index === 1 ? arrow : ''}</td>`).join('')}</tr>`;
            }).join('')}</tbody></table></div><a class="mt-4 inline-block rounded-xl bg-[#0b2a40] px-4 py-2 text-sm font-bold text-white" href="${escape(row.dataset.basketballMatchUrl)}">Match markets &amp; charts →</a>`;
        }
        hideOddsTooltip();
        panel.innerHTML = `<div class="mb-5 flex items-center justify-between gap-3"><h2 id="basketballModalTitle" class="text-lg font-extrabold">${escape(title)}</h2><button type="button" data-action="close-modal" aria-label="Close" class="px-2 text-2xl">×</button></div>${body}`;
        panel.scrollTop = scrollTop;
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
        if (event.target.id === 'basketballModal') { closeModal(); return; }
        if (!button) return;
        if (button.dataset.requiresLogin === 'true') { window.location.assign(button.dataset.loginUrl); return; }
        const action = button.dataset.action;
        if (action === 'close-modal') { closeModal(); return; }
        const row = button.closest('[data-match-id]');
        if (action === 'open-match') { window.location.assign(row.dataset.basketballMatchUrl); return; }
        if (['stats', 'prediction', 'market'].includes(action)) { activeModal = { action, matchId: row.dataset.matchId, marketId: row.dataset.marketId ?? null }; renderModal(); return; }
        if (action === 'vote' && activeModal) { button.disabled = true; await load({ prediction: { match_id: activeModal.matchId, poll: '1x2', value: button.dataset.value } }); return; }
        if (action !== 'favorite' || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch(dashboard.dataset.favoriteUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ sport: 'basketball', event_key: button.dataset.eventKey, event_id: button.dataset.eventId, team1: button.dataset.team1, team2: button.dataset.team2, start_timestamp: button.dataset.startTimestamp }) });
            if (response.status === 401) { window.location.assign(button.dataset.loginUrl); return; }
            if (!response.ok) throw new Error('Unable to save favorite. Please try again.');
            await load();
        } catch (error) { showError(error.message); }
        finally { button.disabled = false; }
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) load({ append: true }); }, { rootMargin: '100px' }).observe(loader());
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') scheduleRefresh(); });
    const subscribe = () => window.Echo?.channel('sports.basketball.live').listen('.score.updated', scheduleRefresh);
    if (window.Echo) subscribe(); else window.addEventListener('load', subscribe, { once: true });
    expireHighlights();
}
