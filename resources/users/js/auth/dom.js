export const parseJson = (value, fallback = null) => {
    if (!value) return fallback;

    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
};

export const setVisible = (element, visible) => {
    if (!element) return;
    element.hidden = !visible;
};

export const delegate = (root, eventName, selector, handler) => {
    root.addEventListener(eventName, (event) => {
        const target = event.target.closest(selector);
        if (target && root.contains(target)) handler(event, target);
    });
};

export const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

export const escapeHtml = (value) => {
    const node = document.createElement('div');
    node.textContent = String(value ?? '');
    return node.innerHTML;
};
