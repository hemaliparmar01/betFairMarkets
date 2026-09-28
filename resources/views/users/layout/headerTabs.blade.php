{{-- <div class="border-b border-[#e3ecf5] !p-[12px] nav-links flex flex-wrap gap-6 text-[15px] font-medium  [gap:28px]  [font-size:15px] [color:#1e4763]  dark:[&_span]:[color:#b0c8dd] dark:[&_span]:[cursor:pointer] dark:[&_span.active-nav]:[color:#e8edf2] dark:[&_span.active-nav]:[border-bottom-color:#4a8ab5] dark:[&_span:hover]:[color:#e8edf2] [&_span]:[cursor:pointer] [&_span]:[padding:4px_0] [&_span]:[border-bottom:2px_solid_transparent] [&_span]:[transition:0.2s] [&_span.active-nav]:[border-bottom-color:#1a6b9c] [&_span.active-nav]:[color:#0b2a40] [&_span:hover]:[color:#0b2a40] [&_span:hover]:[border-bottom-color:#8aaccc]" id="navLinks">
    <a href="{{ route('football.home') }}" class="active-nav">Fixtures</a>
    <a href="{{ route('football.market-activity') }}">Market Activity</a>
    <a href="#">Notifications</a>
    <a href="#">Subscriptions</a>
    <a href="#">Contact</a>
</div> --}}
{{-- <div id="navLinks" class="flex flex-wrap !p-[12px] items-center gap-3 border-b border-[#e3ecf5] p-3 text-[15px] font-medium">
    <a href="{{ route('football.home') }}" @class(['group flex min-w-[190px] items-center gap-3 rounded-lg border bg-white !p-[8px] transition duration-200','border-[#d7e4f2] hover:border-[#1687d9] hover:shadow-sm','border-l-[3px] !border-l-[#1687d9] shadow-sm' => request()->routeIs('football.home')])>
        <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-[#eaf4ff] text-[#1687d9]">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3M16 2v3M3 9h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 13h2M11 13h2M15 13h2M7 17h2M11 17h2M15 17h2"/>
            </svg>
        </span>

        <span class="flex flex-col leading-tight">
            <span class="font-semibold text-[#12324a]">{{ __('Fixtures') }}</span>
            <span class="mt-1 text-xs font-normal text-[#5d7c99]">
                {{ __('Matches') }} &amp; {{ __('schedules') }}
            </span>
        </span>
    </a>

    <a href="{{ route('football.market-activity') }}" @class(['group flex min-w-[190px] items-center gap-3 rounded-lg border bg-white !p-[8px]  transition duration-200','border-[#d7e4f2] hover:border-[#e7b629] hover:shadow-sm','border-l-[3px] !border-l-[#e7b629] shadow-sm' => request()->routeIs('football.market-activity'),])>
        <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-[#fff6d9] text-[#e7a900]">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h4l2.5-7 4 14 3-10 2 3H22"/>
            </svg>
        </span>

        <span class="flex flex-col leading-tight">
            <span class="font-semibold text-[#12324a]">
                {{ __('Market Activity') }}
            </span>
            <span class="mt-1 text-xs font-normal text-[#5d7c99]">
                {{ __('Live market signals') }}
            </span>
        </span>
    </a>

    <span class="mx-2 hidden h-10 w-px bg-[#d7e4f2] md:block"></span>

    <a href="#" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Notifications') }}</a>
    <a href="#" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Subscriptions') }}</a>
    <a href="#" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Contact') }}</a>
</div> --}}
