// One observer covers existing and dynamically inserted blocking overlays.
const selector = '[role="dialog"][aria-modal="true"], dialog[open], [data-scroll-lock]';
let locked = false;
let savedScroll = { x: 0, y: 0 };

export function hasBlockingOverlay(elements) {
    return [...elements].some(element => !element.hidden && element.getAttribute('aria-hidden') !== 'true'
        && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden');
}

function synchronize() {
    const open = hasBlockingOverlay(document.querySelectorAll(selector));
    if (open === locked) return;
    locked = open;
    if (open) {
        savedScroll = { x: window.scrollX, y: window.scrollY };
        const gutter = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
        document.body.style.setProperty('--modal-scroll-top', `${-savedScroll.y}px`);
        document.body.style.setProperty('--modal-body-padding', `${parseFloat(getComputedStyle(document.body).paddingRight) + gutter}px`);
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('--modal-scroll-top');
        document.body.style.removeProperty('--modal-body-padding');
        window.scrollTo({ left: savedScroll.x, top: savedScroll.y, behavior: 'instant' });
    }
}

const observer = new MutationObserver(synchronize);
observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style', 'hidden', 'open', 'aria-hidden', 'aria-modal'] });
document.addEventListener('toggle', synchronize, true);
window.addEventListener('pageshow', synchronize);
window.addEventListener('pagehide', () => {
    document.body.classList.remove('modal-open');
    locked = false;
});
synchronize();
