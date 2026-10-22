@php $sequence = oddsMovementSequence($history ?? []); @endphp
@if ($sequence !== '')
    <div data-odds-history class="mt-2 min-w-0 text-left text-xs">
        <span class="font-semibold text-slate-500 dark:text-slate-400">{{ __('Odds history') }}</span>
        <p class="mt-1 max-h-24 overflow-y-auto break-words font-mono leading-5 text-[#245f84] dark:text-[#8ec8e9]">{{ $sequence }}</p>
        @if ($incomplete ?? false)
            <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">{{ __('Earlier movements may be missing from the previously limited history.') }}</p>
        @endif
    </div>
@endif
