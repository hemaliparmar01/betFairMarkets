export function watchRacingFeed(root, refresh, sport) {
    const notice = document.createElement('p');
    notice.className = 'col-span-full mb-3 text-xs text-[#5a7d99] dark:text-[#b0c8dd]';
    notice.setAttribute('role', 'status');
    root.prepend(notice);
    let health = null;
    let subscribed = false;
    let receivedAt = 0;
    let boundaryTimer = null;
    function render() {
        const unavailable = !subscribed || health?.state !== 'live' || Date.now() - receivedAt > 45000;
        notice.classList.toggle('text-amber-700', unavailable);
        notice.textContent = !subscribed ? 'Live connection unavailable. Displaying the last received values.'
            : !health || health.state === 'waiting' ? 'Waiting for the provider feed.'
            : health.state !== 'live' || Date.now() - receivedAt > 45000 ? 'Provider updates unavailable or delayed. Displaying the last received values.'
            : 'Feed connected. Prices and available sizes update when the provider sends a change.';
    }
    function subscribe() {
        const channel = window.Echo?.channel(`sports.${sport}.live`);
        channel?.listen('.score.updated', refresh);
        channel?.subscribed(() => { subscribed = true; render(); refresh(); });
        window.Echo?.connector?.pusher?.connection?.bind('state_change', ({ current }) => {
            if (current !== 'connected') { subscribed = false; render(); }
        });
    }
    if (window.Echo) subscribe(); else window.addEventListener('load', subscribe, { once: true });
    // Read our own cached state only; this does not call the provider API.
    window.setInterval(() => {
        render();
        if (document.visibilityState === 'visible') refresh();
    }, 15000);
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
    render();
    refresh();
    return {
        update(value) {
            health = value; receivedAt = Date.now(); render();
            window.clearTimeout(boundaryTimer);
            const remaining = Number(value?.next_refresh_at) * 1000 - Date.now();
            if (remaining > 0 && remaining < 86400000) boundaryTimer = window.setTimeout(refresh, remaining + 100);
        },
        failed() { health = { state: 'unavailable' }; receivedAt = 0; render(); },
    };
}
