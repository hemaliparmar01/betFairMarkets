const overlaySelector = '[role="dialog"], [data-live-stats-panel], [data-statistics-panel], [data-prediction-panel], [data-quick-stats-panel]';
const stateClasses = ['selected', 'analytics-expanded', 'collapsed', 'expanded', 'expand-active', 'flash-yellow', 'flash-yellow-alt'];

function key(node) {
    if (node.nodeType !== 1) return null;
    if (node.id) return `id:${node.id}`;
    for (const attribute of ['data-realtime-key', 'data-runner-id', 'data-horse-market', 'data-id', 'data-sportapi-match-id', 'data-football-timeline', 'data-timeline-event', 'data-live-stats-panel', 'data-statistics-panel', 'data-prediction-panel', 'data-quick-stats-panel', 'data-match-id', 'data-market-id', 'data-league-header', 'data-statistics-period', 'data-quick-stat', 'data-quick-count']) {
        if (node.hasAttribute(attribute)) return `${node.tagName}:${attribute}:${node.getAttribute(attribute)}:${node.getAttribute('data-market-id') ?? ''}:${node.getAttribute('data-action') ?? ''}`;
    }
    return null;
}

function isOpenOverlay(node) {
    return node.matches?.(overlaySelector) && !node.hidden && !node.classList.contains('hidden') && node.getAttribute('aria-hidden') !== 'true';
}

export function markRealtimeUnavailable(panel, unavailable) {
    let message = panel.querySelector('[data-realtime-unavailable]');
    if (!unavailable) { message?.remove(); return; }
    if (message) return;
    message = document.createElement('p');
    message.dataset.realtimeUnavailable = '';
    message.setAttribute('role', 'status');
    message.className = 'realtime-unavailable';
    message.textContent = 'Live data is temporarily unavailable. Showing the last received values.';
    panel.append(message);
}

function patchNode(current, incoming) {
    if (current.isEqualNode(incoming)) return;
    if (current.nodeType !== 1) {
        if (current.nodeValue !== incoming.nodeValue) current.nodeValue = incoming.nodeValue;
        return;
    }
    const overlay = current.matches(overlaySelector);
    const formValue = ['INPUT', 'SELECT', 'TEXTAREA'].includes(current.tagName) ? current.value : undefined;
    const checked = current.tagName === 'INPUT' ? current.checked : undefined;
    const preservedClasses = new Map(stateClasses.map(name => [name, current.classList.contains(name)]));
    const expanded = current.getAttribute('aria-expanded');
    for (const attribute of [...current.attributes]) {
        if (attribute.name === 'class' || attribute.name === 'open' && current.tagName === 'DETAILS' || overlay && ['class', 'style', 'hidden', 'aria-hidden', 'aria-modal'].includes(attribute.name) || attribute.name === 'aria-expanded') continue;
        if (!incoming.hasAttribute(attribute.name)) current.removeAttribute(attribute.name);
    }
    for (const attribute of [...incoming.attributes]) {
        if (attribute.name === 'class' || attribute.name === 'open' && current.tagName === 'DETAILS' || overlay && ['class', 'style', 'hidden', 'aria-hidden', 'aria-modal'].includes(attribute.name) || attribute.name === 'aria-expanded' && expanded !== null) continue;
        if (current.getAttribute(attribute.name) !== attribute.value) current.setAttribute(attribute.name, attribute.value);
    }
    if (!overlay) {
        const classes = new Set((incoming.getAttribute('class') ?? '').split(/\s+/).filter(Boolean));
        for (const [name, active] of preservedClasses) {
            if (active) classes.add(name);
            else classes.delete(name);
        }
        const className = [...classes].join(' ');
        if ((current.getAttribute('class') ?? '') !== className) {
            if (className) current.setAttribute('class', className);
            else current.removeAttribute('class');
        }
    }
    patchRealtimeChildren(current, incoming);
    if (formValue !== undefined && current.value !== formValue) current.value = formValue;
    if (checked !== undefined && current.checked !== checked) current.checked = checked;
}

// Keep existing nodes (including fullscreen elements, focus and scroll containers) alive.
export function patchRealtimeChildren(target, incoming) {
    const previous = [...target.childNodes];
    const keyed = new Map(previous.map(node => [key(node), node]).filter(([value]) => value !== null));
    const unkeyed = new Map();
    for (const node of previous) {
        if (key(node) !== null) continue;
        const type = `${node.nodeType}:${node.nodeName}`;
        if (!unkeyed.has(type)) unkeyed.set(type, { nodes: [], index: 0 });
        unkeyed.get(type).nodes.push(node);
    }
    const retained = new Set();
    let position = target.firstChild;
    for (const next of [...incoming.childNodes]) {
        const nextKey = key(next);
        const candidates = unkeyed.get(`${next.nodeType}:${next.nodeName}`);
        const current = nextKey !== null ? keyed.get(nextKey) : candidates?.nodes[candidates.index++];
        const node = current && current.nodeName === next.nodeName ? current : next;
        if (node === current) patchNode(node, next);
        if (node !== position) {
            const fullscreen = document.fullscreenElement;
            if (node.parentNode === target && target.moveBefore) target.moveBefore(node, position);
            else if (!fullscreen || !(node === fullscreen || node.contains?.(fullscreen))) target.insertBefore(node, position);
        }
        retained.add(node);
        position = node.nextSibling;
    }
    for (const node of previous) {
        if (retained.has(node)) continue;
        const overlays = node.nodeType === 1 ? [node, ...node.querySelectorAll(overlaySelector)].filter(isOpenOverlay) : [];
        if (overlays.length) overlays.forEach(panel => markRealtimeUnavailable(panel, true));
        else node.remove();
    }
}

export function patchRealtimeHtml(target, html) {
    const template = document.createElement('template');
    template.innerHTML = target.tagName === 'TBODY' ? `<table><tbody>${html}</tbody></table>` : html;
    patchRealtimeChildren(target, target.tagName === 'TBODY' ? template.content.querySelector('tbody') : template.content);
}
