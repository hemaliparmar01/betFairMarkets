const getSelectedFilters = () => {
    const activePill = document.querySelector('.filter-pill.active');
    const marketDropdown = document.querySelector('.market-dropdown');

    return {
        type: activePill?.dataset.filter ?? null,
        market_name: marketDropdown?.value ?? null,
    };
};

const FOOTBALL_PAGE_SIZE = 20;
const footballLazyLoader = document.querySelector('[data-football-lazy-loader]');
let footballPage = 1;
let footballHasMore = footballLazyLoader?.dataset.hasMore === 'true';
let footballLoading = false;

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
    const firstHeader = template.content.querySelector('[data-league-header]');
    const currentHeaders = container.querySelectorAll('[data-league-header]');
    const lastHeader = currentHeaders[currentHeaders.length - 1];

    if (firstHeader && lastHeader && firstHeader.dataset.leagueHeader === lastHeader.dataset.leagueHeader) {
        firstHeader.remove();
    }

    container.append(template.content);
};

const clearGoalHighlight = (row) => {
    row.classList.remove('!bg-[#ffe4ec]', '!border-[#f7a8bd]', 'dark:!bg-[#4a2633]');
    row.removeAttribute('data-goal-highlight-until');
    row.querySelector('[data-goal-indicator]')?.remove();
};

const scheduleGoalHighlights = () => {
    document.querySelectorAll('[data-goal-highlight-until]').forEach((row) => {
        const expiresAt = Number(row.dataset.goalHighlightUntil) * 1000;
        const remainingTime = expiresAt - Date.now();

        if (remainingTime <= 0) {
            clearGoalHighlight(row);
            return;
        }

        setTimeout(() => clearGoalHighlight(row), remainingTime);
    });
};

let openLiveStatsMatchId = null;
let openPredictionMatchId = null;

const closeLiveStatsPanel = () => {
    document.querySelectorAll('[data-live-stats-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="livestats"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    if (openPredictionMatchId === null) {
        document.body.classList.remove('overflow-hidden');
    }
    openLiveStatsMatchId = null;
};

const closePredictionPanel = () => {
    document.querySelectorAll('[data-prediction-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="prediction"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    if (openLiveStatsMatchId === null) {
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
        closeLiveStatsPanel();
        return;
    }

    panel.classList.remove('hidden');
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
        closePredictionPanel();
        return;
    }

    panel.classList.remove('hidden');
    button.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

document.addEventListener('click', (event) => {
    const matchRow = event.target.closest('[data-match-url]');
    if (matchRow && !event.target.closest('button, a, input, select, textarea')) {
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
    document.querySelectorAll('[data-live-stats-panel]').forEach((item) => item.classList.add('hidden'));
    document.querySelectorAll('[data-action="livestats"]').forEach((item) => item.setAttribute('aria-expanded', 'false'));

    if (shouldOpen) {
        panel.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
        openLiveStatsMatchId = matchId;
    } else {
        closeLiveStatsPanel();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (openPredictionMatchId !== null) {
            closePredictionPanel();
        } else if (openLiveStatsMatchId !== null) {
            closeLiveStatsPanel();
        }
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

    if (footballLoading) {
        return;
    }

    footballLoading = true;
    if (append && footballLazyLoader) {
        footballLazyLoader.querySelector('[data-lazy-loader-text]').textContent = 'Loading matches…';
    }

    try {
        const url = new URL('/football/filter-football',window.location.origin);
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

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        const data = await response.json();
        if (append) {
            appendFootballMatches(data.html);
            footballPage += 1;
        } else {
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const responseContainer = template.content.querySelector('.fixtures-container');
            document.querySelector('.fixtures-container').innerHTML = responseContainer?.innerHTML ?? data.html;
            footballPage = preserveLoaded ? Math.max(1, footballPage) : 1;
        }
        updateFootballLazyLoader(data.has_more, append ? data.next_page : (preserveLoaded ? footballPage + 1 : data.next_page));
        scheduleGoalHighlights();
        restoreLiveStatsPanel();
        restorePredictionPanel();

    } catch (error) {
        console.error('Request failed:', error);
        updateFootballLazyLoader(footballHasMore);
    } finally {
        footballLoading = false;
    }
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

window.Echo.channel('sports.football.live')
    .listen('.score.updated', async () => {
        try {
            const url = new URL('/football/websocket-football',window.location.origin);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    ...getSelectedFilters(),
                    page: 1,
                    per_page: footballPage * FOOTBALL_PAGE_SIZE,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }

            const data = await response.json();

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const responseContainer = template.content.querySelector('.fixtures-container');
            document.querySelector('.fixtures-container').innerHTML = responseContainer?.innerHTML ?? data.html;
            updateFootballLazyLoader(data.has_more, footballPage + 1);
            scheduleGoalHighlights();
            restoreLiveStatsPanel();
            restorePredictionPanel();

        } catch (error) {
            console.error('Request failed:', error);
        }
    });

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
