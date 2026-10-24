import { patchRealtimeChildren, patchRealtimeHtml, markRealtimeUnavailable } from '../realtimeDom.js';
const getSelectedFilters = () => {
    const activePill = document.querySelector('.filter-pill.active');
    const marketDropdown = document.querySelector('.market-dropdown');

    return {
        type: activePill?.dataset.filter ?? null,
        market_name: marketDropdown?.value || 'MATCH_ODDS',
    };
};

let openFootballTimeline = null;

const restoreFootballTimeline = () => {
    let found = false;
    document.querySelectorAll('[data-football-timeline]').forEach((timeline) => {
        const panel = timeline.querySelector('[data-timeline-panel]');
        const toggle = timeline.querySelector('[data-timeline-toggle]');
        const open = openFootballTimeline?.matchId === timeline.dataset.footballTimeline;
        panel?.classList.toggle('hidden', !open);
        toggle?.setAttribute('aria-expanded', String(open));
        if (!open || !panel) return;
        found = true;
        const selected = openFootballTimeline.eventId
            ? timeline.querySelector(`[data-timeline-event="${CSS.escape(openFootballTimeline.eventId)}"]`)
            : null;
        if (!selected) openFootballTimeline.eventId = null;
        panel.querySelector('[data-timeline-detail-score]').textContent = selected?.dataset.eventScore || timeline.dataset.timelineScore;
        const detailMinute = panel.querySelector('[data-timeline-detail-minute]');
        detailMinute.textContent = selected ? selected.dataset.eventMinute || '' : timeline.dataset.timelineLabel;
        detailMinute.hidden = Boolean(selected) && !selected.dataset.eventMinute;
        const detail = panel.querySelector('[data-timeline-detail-event]');
        detail.classList.toggle('hidden', !selected);
        detail.classList.toggle('flex', Boolean(selected));
        panel.querySelector('[data-timeline-goals]')?.classList.toggle('hidden', Boolean(selected));
        panel.querySelector('[data-timeline-detail-icon]').textContent = selected
            ? ({ goal: '⚽', yellow: '🟨', red: '🟥' }[selected.dataset.eventKind] || '') : '';
        panel.querySelector('[data-timeline-detail-player]').textContent = selected?.dataset.eventSurname || selected?.dataset.eventPlayer || '';
        panel.querySelector('[data-timeline-detail-kind]').textContent = selected?.dataset.eventLabel || '';
    });
    if (!found) openFootballTimeline = null;
};

const footballTimelineClockAnchors = new WeakMap();
const refreshFootballTimelineClocks = () => {
    document.querySelectorAll('[data-football-timeline][data-timeline-running="true"]').forEach((timeline) => {
        if (!footballTimelineClockAnchors.has(timeline)) footballTimelineClockAnchors.set(timeline, performance.now() - Number(timeline.dataset.timelineSecond ?? 0) * 1000);
        const elapsed = Math.max(0, Math.floor((performance.now() - footballTimelineClockAnchors.get(timeline)) / 60000));
        const minute = Math.min(Number(timeline.dataset.timelineClockLimit), Number(timeline.dataset.timelineMinute) + elapsed);
        const limit = Number(timeline.dataset.timelinePeriodLimit);
        const label = (minute > limit ? `${limit}+${minute - limit}` : String(minute)) + "'";
        timeline.dataset.timelineLabel = label;
        const progress = timeline.querySelector('[data-timeline-progress]');
        if (progress) progress.style.width = `${Math.min(100, Math.max(0, minute / 90 * 100))}%`;
        if (openFootballTimeline?.matchId === timeline.dataset.footballTimeline && !openFootballTimeline.eventId) {
            const detailMinute = timeline.querySelector('[data-timeline-detail-minute]');
            if (detailMinute) detailMinute.textContent = label;
        }
    });
};

const FOOTBALL_PAGE_SIZE = 20;
const footballLazyLoader = document.querySelector('[data-football-lazy-loader]');
let footballPage = 1;
let footballHasMore = footballLazyLoader?.dataset.hasMore === 'true';
let footballLoading = false;
let footballPendingRequest = null;
let footballPendingRealtime = false;
let footballRealtimeTimer = null;
const footballPendingPredictions = [];

const updateFootballLazyLoader = (hasMore, nextPage = null) => {
    footballHasMore = Boolean(hasMore);
    if (nextPage) {
        footballPage = Number(nextPage) - 1;
    }

    if (!footballLazyLoader) {
        return;
    }

    footballLazyLoader.dataset.hasMore = footballHasMore ? 'true' : 'false';
    footballLazyLoader.classList.toggle('hidden', !footballHasMore);
    footballLazyLoader.querySelector('[data-lazy-loader-text]').textContent = '↓ Scroll for more matches ↓';
};

const appendFootballMatches = (html) => {
    const container = document.querySelector('.fixtures-container');
    if (!container) {
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = html.trim();
    const incoming = template.content.querySelector('.fixtures-container') ?? template.content;
    if (!incoming.querySelector('.event-row')) {
        return;
    }

    container.querySelectorAll('.no-fixtures-message').forEach((message) => message.remove());
    const firstHeader = incoming.querySelector('[data-league-header]');
    const currentHeaders = container.querySelectorAll('[data-league-header]');
    const lastHeader = currentHeaders[currentHeaders.length - 1];

    if (firstHeader && lastHeader && firstHeader.dataset.leagueHeader === lastHeader.dataset.leagueHeader) {
        firstHeader.remove();
    }

    container.append(...incoming.childNodes);
};

const footballGoalState = new Map();
let footballGoalTimer = null;
let footballLiveSnapshotReceived = false;
const clearGoalHighlight = (row) => {
    row.classList.remove('fixture-goal-active', '!bg-[#ffe4ec]', '!border-[#f7a8bd]', 'dark:!bg-[#4a2633]');
    row.removeAttribute('data-goal-highlight-until');
    row.querySelectorAll('[data-goal-indicator]').forEach(indicator => indicator.remove());
    row.querySelectorAll('.fixture-goal-team').forEach(team => team.classList.remove('fixture-goal-team'));
};

const scheduleGoalHighlights = (detectChanges = false) => {
    window.clearTimeout(footballGoalTimer);
    footballGoalTimer = null;
    const now = Date.now();
    const currentIds = new Set();
    let nextExpiry = Infinity;
    document.querySelectorAll('.event-row[data-match-url]').forEach(row => {
        const id = row.getAttribute('data-sportApi-match-id');
        currentIds.add(id);
        const score = side => row.dataset[`${side}Score`] !== '' && Number.isFinite(Number(row.dataset[`${side}Score`])) ? Number(row.dataset[`${side}Score`]) : null;
        const home = score('home');
        const away = score('away');
        const previous = footballGoalState.get(id);
        const signal = Number(row.dataset.goalSignal) || 0;
        const state = { home, away, signal, until: previous?.until ?? 0, sides: previous?.sides ?? [] };
        if (previous && (detectChanges || (signal * 1000 > now && signal > (previous.signal ?? 0))) && row.dataset.matchStatus === 'Live') {
            const scoring = ['home', 'away'].filter(side => state[side] !== null && previous[side] !== null && state[side] > previous[side]);
            if (scoring.length) {
                state.sides = scoring;
                state.until = now + Math.max(0, Number(row.dataset.goalHighlightSeconds) || 40) * 1000;
            } else if (['home', 'away'].some(side => state[side] !== null && previous[side] !== null && state[side] < previous[side])) {
                state.until = 0;
            }
        }
        clearGoalHighlight(row);
        if (state.until > now && row.dataset.matchStatus === 'Live') {
            row.classList.add('fixture-goal-active');
            for (const side of state.sides) {
                const team = row.querySelector(`[data-goal-team="${side}"]`);
                if (!team) continue;
                team.classList.add('fixture-goal-team');
                const label = document.createElement('span');
                label.dataset.goalIndicator = '';
                label.className = 'shrink-0 text-xs font-bold';
                label.textContent = 'Goal';
                team.append(label);
            }
            nextExpiry = Math.min(nextExpiry, state.until - now);
        }
        footballGoalState.set(id, state);
    });
    for (const id of footballGoalState.keys()) if (!currentIds.has(id)) footballGoalState.delete(id);
    if (Number.isFinite(nextExpiry)) footballGoalTimer = window.setTimeout(() => scheduleGoalHighlights(), nextExpiry + 10);
};

let openLiveStatsMatchId = null;
let openPredictionMatchId = null;
let openQuickStatsMatchId = null;

let openStatisticsMatchId = null;
const statisticsRequests = new Map();

const closeStatisticsPanel = (restoreFocus = true) => {
    if (openStatisticsMatchId === null) return;
    const matchId = CSS.escape(openStatisticsMatchId);
    document.querySelector(`[data-statistics-panel="${matchId}"]`)?.classList.add('hidden');
    const button = document.querySelector(`[data-action="stats"][data-match-id="${matchId}"]`);
    button?.setAttribute('aria-expanded', 'false');
    if (restoreFocus) button?.focus({ preventScroll: true });
    openStatisticsMatchId = null;
    if (openQuickStatsMatchId === null && openLiveStatsMatchId === null && openPredictionMatchId === null) {
        document.body.classList.remove('overflow-hidden');
    }
};

const restoreStatisticsPanel = () => {
    if (openStatisticsMatchId === null) return;
    const matchId = CSS.escape(openStatisticsMatchId);
    const panel = document.querySelector(`[data-statistics-panel="${matchId}"]`);
    const button = document.querySelector(`[data-action="stats"][data-match-id="${matchId}"]`);
    if (!panel || !button || button.disabled) {
        if (panel) markRealtimeUnavailable(panel, true);
        return;
    }
    panel.classList.remove('hidden');
    panel.querySelector('[data-statistics-loading]').hidden = !statisticsRequests.has(openStatisticsMatchId);
    button.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

const loadFootballStatistics = async (button, scope = 'all') => {
    const matchId = button.dataset.matchId;
    if (statisticsRequests.has(matchId)) {
        await statisticsRequests.get(matchId);
        if (scope === 'live') return;
    }
    const panel = () => document.querySelector(`[data-${scope === 'live' ? 'live-stats' : 'statistics'}-panel="${CSS.escape(matchId)}"]`);
    const error = panel()?.querySelector('[data-statistics-error]');
    if (error) error.hidden = true;
    const url = new URL(button.dataset.statisticsUrl, window.location.origin);
    url.searchParams.set('scope', scope);
    const promise = (async () => {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Statistics could not be loaded. Please try again.');
            const result = await response.json();
            const body = panel()?.querySelector('[data-statistics-body]');
            if (body && scope === 'all') patchRealtimeHtml(body, result.html);
            await callFixtureData(getSelectedFilters(), { preserveLoaded: true, realtime: true });
        } catch (requestError) {
            const currentError = panel()?.querySelector('[data-statistics-error]');
            if (currentError) {
                currentError.textContent = requestError.message;
                currentError.hidden = false;
            }
        } finally {
            statisticsRequests.delete(matchId);
            const loading = panel()?.querySelector('[data-statistics-loading]');
            if (loading) loading.hidden = true;
        }
    })();
    statisticsRequests.set(matchId, promise);
    const loading = panel()?.querySelector('[data-statistics-loading]');
    if (loading) loading.hidden = false;
    await promise;
};

const closeQuickStatsPanel = (restoreFocus = true) => {
    if (openQuickStatsMatchId === null) {
        return;
    }

    const matchId = CSS.escape(openQuickStatsMatchId);
    document.querySelector(`[data-quick-stats-panel="${matchId}"]`)?.classList.add('hidden');
    const button = document.querySelector(`[data-action="quickstats"][data-match-id="${matchId}"]`);
    button?.setAttribute('aria-expanded', 'false');
    if (restoreFocus) button?.focus({ preventScroll: true });
    openQuickStatsMatchId = null;
    if (openLiveStatsMatchId === null && openPredictionMatchId === null && openStatisticsMatchId === null) {
        document.body.classList.remove('overflow-hidden');
    }
};

const restoreQuickStatsPanel = () => {
    if (openQuickStatsMatchId === null) {
        return;
    }

    const matchId = CSS.escape(openQuickStatsMatchId);
    const panel = document.querySelector(`[data-quick-stats-panel="${matchId}"]`);
    const button = document.querySelector(`[data-action="quickstats"][data-match-id="${matchId}"]`);
    if (!panel || !button || button.disabled) {
        if (panel) markRealtimeUnavailable(panel, true);
        return;
    }

    panel.classList.remove('hidden');
    button.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

let liveStatsScrollState = null;
const captureLiveStatsScroll = () => {
    if (openLiveStatsMatchId === null) return;
    const panel = document.querySelector(`[data-live-stats-panel="${CSS.escape(openLiveStatsMatchId)}"]`);
    const content = panel?.querySelector('.live-statistics-scroll');
    if (!content) return;
    liveStatsScrollState = {
        matchId: openLiveStatsMatchId,
        top: content.scrollTop,
        periods: new Map([...content.querySelectorAll('[data-statistics-period]')].map(period => [period.dataset.statisticsPeriod, period.open])),
    };
};
const applyLiveStatsScroll = panel => {
    if (!liveStatsScrollState || liveStatsScrollState.matchId !== openLiveStatsMatchId) return;
    const content = panel.querySelector('.live-statistics-scroll');
    if (!content) return;
    content.querySelectorAll('[data-statistics-period]').forEach(period => {
        if (liveStatsScrollState.periods.has(period.dataset.statisticsPeriod)) period.open = liveStatsScrollState.periods.get(period.dataset.statisticsPeriod);
    });
    content.scrollTop = liveStatsScrollState.top;
};

const closeLiveStatsPanel = () => {
    liveStatsScrollState = null;
    document.querySelectorAll('[data-live-stats-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="livestats"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    if (openPredictionMatchId === null && openQuickStatsMatchId === null && openStatisticsMatchId === null) {
        document.body.classList.remove('overflow-hidden');
    }
    openLiveStatsMatchId = null;
};

const closePredictionPanel = () => {
    document.querySelectorAll('[data-prediction-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="prediction"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    if (openLiveStatsMatchId === null && openQuickStatsMatchId === null && openStatisticsMatchId === null) {
        document.body.classList.remove('overflow-hidden');
    }
    openPredictionMatchId = null;
};

const restoreLiveStatsPanel = () => {
    if (openLiveStatsMatchId === null) {
        return;
    }

    const panel = document.querySelector(`[data-live-stats-panel="${CSS.escape(openLiveStatsMatchId)}"]`);
    const button = document.querySelector(`[data-action="livestats"][data-match-id="${CSS.escape(openLiveStatsMatchId)}"]`);

    if (!panel || !button) {
        if (panel) markRealtimeUnavailable(panel, true);
        return;
    }

    panel.classList.remove('hidden');
    applyLiveStatsScroll(panel);
    const loading = panel.querySelector('[data-statistics-loading]');
    if (loading) loading.hidden = !statisticsRequests.has(openLiveStatsMatchId);
    button.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

const restorePredictionPanel = () => {
    if (openPredictionMatchId === null) {
        return;
    }

    const panel = document.querySelector(`[data-prediction-panel="${CSS.escape(openPredictionMatchId)}"]`);
    const button = document.querySelector(`[data-action="prediction"][data-match-id="${CSS.escape(openPredictionMatchId)}"]`);

    if (!panel || !button) {
        if (panel) markRealtimeUnavailable(panel, true);
        return;
    }

    panel.classList.remove('hidden');
    button.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

document.addEventListener('click', (event) => {
    const loginRequiredAction = event.target.closest('[data-requires-login="true"]');
    if (loginRequiredAction) {
        event.stopImmediatePropagation();
        window.location.href = loginRequiredAction.dataset.loginUrl;
        return;
    }

    const chartButton = event.target.closest('[data-action="chart"][data-chart-url]');
    if (chartButton && !chartButton.disabled) {
        window.location.href = chartButton.dataset.chartUrl;
        return;
    }

    const statisticsPanel = event.target.closest('[data-statistics-panel]');
    if (event.target.closest('[data-close-statistics]') || (statisticsPanel && event.target === statisticsPanel)) {
        closeStatisticsPanel();
        return;
    }
    const statisticsButton = event.target.closest('[data-action="stats"][data-statistics-url]');
    if (statisticsButton && !statisticsButton.disabled) {
        const shouldOpen = openStatisticsMatchId !== statisticsButton.dataset.matchId;
        closeQuickStatsPanel(false);
        closeLiveStatsPanel();
        closePredictionPanel();
        closeStatisticsPanel(false);
        if (shouldOpen) {
            openStatisticsMatchId = statisticsButton.dataset.matchId;
            restoreStatisticsPanel();
            document.querySelector(`[data-statistics-panel="${CSS.escape(openStatisticsMatchId)}"] [data-close-statistics]`)?.focus({ preventScroll: true });
            loadFootballStatistics(statisticsButton);
        }
        return;
    }

    const quickStatsPanel = event.target.closest('[data-quick-stats-panel]');
    if (event.target.closest('[data-close-quick-stats]') || (quickStatsPanel && event.target === quickStatsPanel)) {
        closeQuickStatsPanel();
        return;
    }

    const quickStatsButton = event.target.closest('[data-action="quickstats"]');
    if (quickStatsButton) {
        if (quickStatsButton.disabled) return;
        const panel = document.querySelector(`[data-quick-stats-panel="${CSS.escape(quickStatsButton.dataset.matchId)}"]`);
        if (!panel) return;
        const shouldOpen = panel.classList.contains('hidden');
        closeQuickStatsPanel(false);
        closeLiveStatsPanel();
        closePredictionPanel();
        closeStatisticsPanel(false);
        if (shouldOpen) {
            openQuickStatsMatchId = quickStatsButton.dataset.matchId;
            restoreQuickStatsPanel();
        }
        return;
    }

    const timeline = event.target.closest('[data-football-timeline]');
    if (timeline) {
        const selected = event.target.closest('[data-timeline-event]');
        const toggle = event.target.closest('[data-timeline-toggle]');
        if (selected || toggle || event.target.closest('[data-timeline-close]')) {
            const matchId = timeline.dataset.footballTimeline;
            const eventId = selected?.dataset.timelineEvent || null;
            const same = openFootballTimeline?.matchId === matchId && openFootballTimeline?.eventId === eventId;
            openFootballTimeline = event.target.closest('[data-timeline-close]') || same ? null : { matchId, eventId };
            restoreFootballTimeline();
        }
        return;
    }
    if (openFootballTimeline) {
        openFootballTimeline = null;
        restoreFootballTimeline();
    }

    const matchRow = event.target.closest('[data-match-url]');
    if (matchRow && !event.target.closest('button, a, input, select, textarea, [data-prediction-panel], [data-live-stats-panel], [data-statistics-panel], [data-quick-stats-panel]')) {
        window.location.href = matchRow.dataset.matchUrl;
        return;
    }

    if (event.target.closest('[data-close-prediction]')) {
        closePredictionPanel();
        return;
    }

    const predictionPanel = event.target.closest('[data-prediction-panel]');
    if (predictionPanel && event.target === predictionPanel) {
        closePredictionPanel();
        return;
    }

    const predictionButton = event.target.closest('[data-action="prediction"]');
    if (predictionButton && !predictionButton.disabled) {
        const matchId = predictionButton.dataset.matchId;
        const panel = document.querySelector(`[data-prediction-panel="${CSS.escape(matchId)}"]`);
        if (!panel) {
            return;
        }

        const shouldOpen = panel.classList.contains('hidden');
        closeQuickStatsPanel(false);
        closeLiveStatsPanel();
        closeStatisticsPanel(false);
        closePredictionPanel();
        if (shouldOpen) {
            panel.classList.remove('hidden');
            predictionButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
            openPredictionMatchId = matchId;
        }
        return;
    }

    if (event.target.closest('[data-close-live-stats]')) {
        closeLiveStatsPanel();
        return;
    }

    const openPanel = event.target.closest('[data-live-stats-panel]');
    if (openPanel && event.target === openPanel) {
        closeLiveStatsPanel();
        return;
    }

    const button = event.target.closest('[data-action="livestats"]');
    if (!button || button.disabled) {
        return;
    }

    const matchId = button.dataset.matchId;
    const panel = document.querySelector(`[data-live-stats-panel="${CSS.escape(matchId)}"]`);
    if (!panel) {
        return;
    }

    const shouldOpen = panel.classList.contains('hidden');
    closeQuickStatsPanel(false);
    closePredictionPanel();
    closeStatisticsPanel(false);
    document.querySelectorAll('[data-live-stats-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="livestats"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));

    if (shouldOpen) {
        panel.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
        openLiveStatsMatchId = matchId;
        loadFootballStatistics(button, 'live');
    } else {
        closeLiveStatsPanel();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Tab' && openQuickStatsMatchId !== null) {
        event.preventDefault();
        document.querySelector(`[data-quick-stats-panel="${CSS.escape(openQuickStatsMatchId)}"] [data-close-quick-stats]`)?.focus({ preventScroll: true });
    }
    if (event.key === 'Escape') {
        if (openQuickStatsMatchId !== null) {
            closeQuickStatsPanel();
        } else if (openPredictionMatchId !== null) {
            closePredictionPanel();
        } else if (openLiveStatsMatchId !== null) {
            closeLiveStatsPanel();
        } else if (openStatisticsMatchId !== null) {
            closeStatisticsPanel();
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || openQuickStatsMatchId !== null) return;
    const panel = openStatisticsMatchId !== null
        ? document.querySelector(`[data-statistics-panel="${CSS.escape(openStatisticsMatchId)}"]`)
        : openLiveStatsMatchId !== null
            ? document.querySelector(`[data-live-stats-panel="${CSS.escape(openLiveStatsMatchId)}"]`)
            : null;
    if (!panel) return;
    const focusable = [...panel.querySelectorAll('button:not(:disabled), summary, a[href], [tabindex="0"]')].filter((item) => item.getClientRects().length);
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
        event.preventDefault(); last?.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) {
        event.preventDefault(); first?.focus();
    }
});

const pills = document.querySelectorAll('.filter-pill');
pills.forEach((pill) => {
    pill.onclick = async function () {
        const wasSelected = this.classList.contains('active');
        pills.forEach((item) => {
            item.classList.remove('active');
        });
        if (!wasSelected) {
            this.classList.add('active');
        }
        callFixtureData(getSelectedFilters(), { reset: true });
    }
});

document.querySelectorAll('.market-dropdown').forEach((element) => {
    element.addEventListener('change', function () {
        callFixtureData(getSelectedFilters(), { reset: true });
    });
});

const callFixtureData = async (value, options = {}) => {
    const append = options.append === true;
    const preserveLoaded = options.preserveLoaded === true;
    const fixtures = document.querySelector('.fixtures-container');

    if (options.reset === true && !append) {
        fixtures?.classList.add('fixtures-loading');
        fixtures?.setAttribute('aria-busy', 'true');
    }

    if (footballLoading) {
        if (value.prediction) {
            return new Promise((resolve) => {
                footballPendingPredictions.push({ prediction: value.prediction, options, resolve });
            });
        } else if (options.reset === true) {
            footballPendingRequest = { value, options };
        } else if (options.realtime === true) {
            footballPendingRealtime = true;
        }
        return;
    }

    clearTimeout(footballRealtimeTimer);
    footballRealtimeTimer = null;
    footballPendingRealtime = false;
    footballLoading = true;
    if (append && footballLazyLoader) {
        footballLazyLoader.querySelector('[data-lazy-loader-text]').textContent = 'Loading matches…';
    }

    try {
        const url = new URL(options.realtime === true ? '/football/websocket-football' : '/football/filter-football', window.location.origin);
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                ...value,
                page: append ? footballPage + 1 : 1,
                per_page: preserveLoaded ? footballPage * FOOTBALL_PAGE_SIZE : FOOTBALL_PAGE_SIZE,
                append,
            }),
        });

        if (response.status === 401) {
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            const failure = await response.json().catch(() => ({}));
            throw new Error(failure.message || `HTTP error: ${response.status}`);
        }

        document.querySelector('[data-football-request-error]')?.remove();
        const data = await response.json();
        const selected = getSelectedFilters();
        if (footballPendingRequest || selected.type !== value.type || selected.market_name !== value.market_name) {
            return;
        }

        if (append) {
            appendFootballMatches(data.html);
            footballPage += 1;
        } else {
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const responseContainer = template.content.querySelector('.fixtures-container');
            const incoming = responseContainer ?? template.content;
            patchRealtimeChildren(document.querySelector('.fixtures-container'), incoming);
            footballPage = preserveLoaded ? Math.max(1, footballPage) : 1;
        }
        updateFootballLazyLoader(data.has_more, append ? data.next_page : (preserveLoaded ? footballPage + 1 : data.next_page));
        const detectGoals = footballLiveSnapshotReceived && options.append !== true && options.reset !== true;
        if (options.realtime === true) footballLiveSnapshotReceived = true;
        scheduleGoalHighlights(detectGoals);
        restoreLiveStatsPanel();
        restorePredictionPanel();
        restoreQuickStatsPanel();
        restoreStatisticsPanel();
        refreshFootballTimelineClocks();
        restoreFootballTimeline();

    } catch (error) {
        console.error('Request failed:', error);
        if (options.realtime !== true) {
            let notice = document.querySelector('[data-football-request-error]');
            if (!notice) {
                notice = document.createElement('p');
                notice.dataset.footballRequestError = '';
                notice.className = 'my-3 text-sm text-red-600 dark:text-red-400';
                notice.setAttribute('role', 'alert');
                fixtures?.before(notice);
            }
            notice.textContent = error.message;
        }
        updateFootballLazyLoader(footballHasMore);
    } finally {
        footballLoading = false;
        if (!footballPendingRequest) {
            fixtures?.classList.remove('fixtures-loading');
            fixtures?.removeAttribute('aria-busy');
        }
        if (footballPendingRequest) {
            const pending = footballPendingRequest;
            footballPendingRequest = null;
            callFixtureData(pending.value, pending.options);
        } else if (footballPendingPredictions.length) {
            const pending = footballPendingPredictions.shift();
            callFixtureData({ ...getSelectedFilters(), prediction: pending.prediction }, pending.options).then(pending.resolve);
        } else if (footballPendingRealtime) {
            scheduleFootballRealtimeRefresh();
        }
    }
};

const scheduleFootballRealtimeRefresh = () => {
    if (document.visibilityState !== 'visible') {
        footballPendingRealtime = false;
        return;
    }

    if (footballLoading) {
        footballPendingRealtime = true;
        return;
    }

    if (footballRealtimeTimer !== null) {
        return;
    }

    footballRealtimeTimer = setTimeout(() => {
        footballRealtimeTimer = null;
        callFixtureData(getSelectedFilters(), { preserveLoaded: true, realtime: true });
    }, 100);
};

if (footballLazyLoader) {
    const footballObserver = new IntersectionObserver((entries) => {
        if (entries[0]?.isIntersecting && footballHasMore && !footballLoading) {
            callFixtureData(getSelectedFilters(), { append: true });
        }
    }, { rootMargin: '200px 0px' });

    footballObserver.observe(footballLazyLoader);
}

let minuteRefreshInProgress = false;

const refreshFixtureMinutes = async () => {
    if (
        minuteRefreshInProgress ||
        document.visibilityState !== 'visible' ||
        !document.querySelector('.fixtures-container')
    ) {
        return;
    }

    minuteRefreshInProgress = true;

    try {
        await callFixtureData(getSelectedFilters(), { preserveLoaded: true });
    } finally {
        minuteRefreshInProgress = false;
    }
};

setInterval(refreshFixtureMinutes, 60_000);

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refreshFixtureMinutes();
    }
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        refreshFixtureMinutes();
    }
});

window.Echo.channel('sports.football.live')
    .listen('.score.updated', scheduleFootballRealtimeRefresh);

scheduleGoalHighlights();

document.addEventListener('click', async (event) => {
    const favoriteButton = event.target.closest('[data-action="favorite"]');
    if (favoriteButton && !favoriteButton.disabled) {
        favoriteButton.disabled = true;

        try {
            const response = await fetch('/favorites/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    sport: favoriteButton.dataset.sport,
                    event_key: favoriteButton.dataset.eventKey,
                    event_id: favoriteButton.dataset.eventId,
                    team1: favoriteButton.dataset.team1,
                    team2: favoriteButton.dataset.team2,
                    start_timestamp: Number(favoriteButton.dataset.startTimestamp),
                }),
            });

            if (response.status === 401) {
                window.location.href = favoriteButton.dataset.loginUrl || '/login';
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }

            const data = await response.json();
            document.querySelectorAll('[data-action="favorite"]').forEach((button) => {
                if (button.dataset.sport !== favoriteButton.dataset.sport || button.dataset.eventKey !== favoriteButton.dataset.eventKey) {
                    return;
                }

                button.classList.toggle('active-fav', data.is_favorite);
                button.setAttribute('aria-pressed', data.is_favorite ? 'true' : 'false');
            });

            if (!data.is_favorite && getSelectedFilters().type === 'favorites') {
                await callFixtureData(getSelectedFilters(), { reset: true });
            }
        } catch (error) {
            console.error('Favorite update failed:', error);
        } finally {
            favoriteButton.disabled = false;
        }

        return;
    }

    const option = event.target.closest('[data-prediction-option]');
    if (!option || option.disabled) {
        return;
    }

    option.closest('[data-prediction-poll]')?.querySelectorAll('[data-prediction-option]').forEach((item) => {
        item.disabled = true;
    });
    openPredictionMatchId = option.dataset.matchId;

    await callFixtureData({
        ...getSelectedFilters(),
        prediction: {
            match_id: option.dataset.matchId,
            poll: option.dataset.poll,
            value: option.dataset.value,
        },
    }, { preserveLoaded: true });
});

refreshFootballTimelineClocks();
setInterval(refreshFootballTimelineClocks, 1000);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && openFootballTimeline) {
        openFootballTimeline = null;
        restoreFootballTimeline();
    }
});
