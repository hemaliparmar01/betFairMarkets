@php $checkAuthAndSubscription = $checkAuthAndSubscription ?? checkAuthAndSubscription(); @endphp
<div id="horseracingFilters" class="my-4 space-y-2 border-b border-[#e6edf6] pb-4 dark:border-[#2a3f50]">
    @foreach ([['all' => 'All', 'upcoming' => 'Upcoming', 'finished' => 'Finished', 'favorites' => 'Favorites', 'by_time' => 'By Time'], ['prematch_drops' => 'Prematch Drops', 'market_shock' => 'Market Shock']] as $filters)
        <div class="flex flex-wrap gap-2">
            @foreach ($filters as $value => $label)
                <button type="button" data-filter="{{ $value }}" @disabled(! $checkAuthAndSubscription['display_odds'] && in_array($value, ['ht00', '00_70', 'fav_losing', 'prematch_drops', 'market_shock', 'fav_not_winning'], true)) @class(['filter-pill cursor-pointer disabled:cursor-not-allowed disabled:opacity-40 rounded-full border border-[#d4e0ec] bg-[#f0f6fc] px-3.5 py-2 text-xs font-semibold text-[#2a4d66] transition hover:border-[#1a6b9c] dark:border-[#3a5568] dark:bg-[#1f3444] dark:text-[#b0c8dd] [&.active]:border-[#1a6b9c] [&.active]:bg-[#0b2a40] [&.active]:text-white', 'active' => $value === 'all'])>{{ __($label) }}</button>
            @endforeach
        </div>
    @endforeach
</div>
