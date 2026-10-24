<div class="odd-box {{ request()->input('type') === 'prematch_drops' ? 'prematch-drops__odds' : 'market-shock-card__odds' }}" title="{{ $odd['runner'] }}">
    @if (request()->input('type') === 'prematch_drops')
        <span class="prematch-drops__odd alert-unavailable" title="{{ __('Separate opening baseline unavailable') }}">—</span>
        <span class="prematch-drops__arrow" aria-hidden="true">→</span>
    @endif
    @if ($odd['currentBackOdds'] !== null)
        @if ($odd['previousBackOdds'] !== null)
            <span class="{{ request()->input('type') === 'prematch_drops' ? 'prematch-drops__odd' : 'market-shock-card__odd' }}">{{ number_format($odd['previousBackOdds'], 2) }}</span>
            <span class="{{ request()->input('type') === 'prematch_drops' ? 'prematch-drops__arrow' : 'market-shock-card__arrow' }}" aria-hidden="true">
                @if ($odd['odds_direction'] === 'down')↓@elseif ($odd['odds_direction'] === 'up')↑@else →@endif
            </span>
        @endif
        <span class="{{ request()->input('type') === 'prematch_drops' ? 'prematch-drops__odd prematch-drops__odd--current' : 'market-shock-card__odd market-shock-card__odd--current' }}">{{ number_format($odd['currentBackOdds'], 2) }}</span>
        @if ($odd['previousBackOdds'] !== null)
            <span class="tooltip football-alert-tooltip">{{ number_format($odd['previousBackOdds'], 2) }} → {{ number_format($odd['currentBackOdds'], 2) }}</span>
        @endif
    @else
        <span aria-label="{{ __('Odds unavailable') }}">—</span>
    @endif
</div>
