const oddsHistoryNumber = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
    useGrouping: false,
});

export const renderOddsHistory = (runner) => {
    const prices = [];
    let previous = null;
    const history = runner.backOddsHistory ?? runner.history ?? [];
    for (const point of Array.isArray(history) ? history : []) {
        const price = Number(point?.odds);
        if (!Number.isFinite(price) || price <= 1 || price === previous) continue;
        previous = price;
        prices.push(oddsHistoryNumber.format(price));
    }
    if (prices.length === 0) return '';
    const incomplete = runner.odds_history_incomplete ?? runner.history_incomplete ?? false;
    return `<div data-odds-history class="mt-2 min-w-0 text-left text-xs"><span class="font-semibold text-slate-500 dark:text-slate-400">Odds history</span><p class="mt-1 max-h-24 overflow-y-auto break-words font-mono leading-5 text-[#245f84] dark:text-[#8ec8e9]">${prices.join(' → ')}</p>${incomplete ? '<p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Earlier movements may be missing from the previously limited history.</p>' : ''}</div>`;
};
