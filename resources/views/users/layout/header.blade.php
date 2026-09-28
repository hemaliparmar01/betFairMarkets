{{-- <div
    class="flex flex-wrap items-center justify-between border-b border-[var(--color-border)] pb-3 transition-colors duration-300">
    <div class="flex flex-col items-start">
        <img src="{{ url('logo.png') }}" class="h-auto max-w-full w-[250px] max-[480px]:w-[190px]" alt="" srcset="">
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <button
            class="rounded-[30px] border border-[var(--color-border-control)] bg-transparent px-3 py-[3px] text-base text-[var(--color-text-muted)] transition-colors duration-200 hover:border-[var(--color-primary)] hover:text-[var(--color-text-main)]"
            id="themeToggle" title="Dark/Light mode"><i class="fas fa-moon"></i></button>
        <button
            class="lang-btn active-lang rounded-[30px] border border-[var(--color-border-control)] bg-transparent px-3 py-[3px] text-[13px] font-medium text-[var(--color-text-muted)] transition-colors duration-200 [&.active-lang]:border-[var(--color-primary)] [&.active-lang]:bg-[var(--color-primary)] [&.active-lang]:text-[var(--color-on-primary)] dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]"
            data-lang="en">EN</button>
        <button
            class="lang-btn rounded-[30px] border border-[var(--color-border-control)] bg-transparent px-3 py-[3px] text-[13px] font-medium text-[var(--color-text-muted)] transition-colors duration-200 [&.active-lang]:border-[var(--color-primary)] [&.active-lang]:bg-[var(--color-primary)] [&.active-lang]:text-[var(--color-on-primary)] dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]"
            data-lang="ka">KA</button>
        <span
            class="cursor-default rounded-[60px] border border-[var(--color-border-strong)] bg-transparent px-4 py-1 text-[13px] font-medium text-[var(--color-text-muted)] transition-colors duration-300">{{ __('Login') }}</span>
        <span
            class="cursor-default rounded-[60px] bg-[var(--color-primary)] px-[18px] py-[5px] text-[13px] font-medium text-[var(--color-on-primary)] transition-colors duration-300">{{ __('Start Free Trial') }}</span>
    </div>
</div>

<div class="my-1.5 flex flex-wrap gap-7 text-[15px] font-medium text-[var(--color-text-secondary)]" id="navLinks">
    <span
        class="active-nav cursor-pointer border-b-2 border-transparent py-1 transition-colors duration-200 hover:border-[var(--color-primary-hover)] hover:text-[var(--color-text-heading)] [&.active-nav]:border-[var(--color-primary-accent)] [&.active-nav]:text-[var(--color-text-heading)]">{{ __('Fixtures') }}</span>
    <span
        class="cursor-pointer border-b-2 border-transparent py-1 transition-colors duration-200 hover:border-[var(--color-primary-hover)] hover:text-[var(--color-text-heading)] [&.active-nav]:border-[var(--color-primary-accent)] [&.active-nav]:text-[var(--color-text-heading)]">{{ __('Market Activity') }}</span>
    <span
        class="cursor-pointer border-b-2 border-transparent py-1 transition-colors duration-200 hover:border-[var(--color-primary-hover)] hover:text-[var(--color-text-heading)] [&.active-nav]:border-[var(--color-primary-accent)] [&.active-nav]:text-[var(--color-text-heading)]">{{ __('Notifications') }}</span>
    <span
        class="cursor-pointer border-b-2 border-transparent py-1 transition-colors duration-200 hover:border-[var(--color-primary-hover)] hover:text-[var(--color-text-heading)] [&.active-nav]:border-[var(--color-primary-accent)] [&.active-nav]:text-[var(--color-text-heading)]">{{ __('Subscriptions') }}</span>
    <span
        class="cursor-pointer border-b-2 border-transparent py-1 transition-colors duration-200 hover:border-[var(--color-primary-hover)] hover:text-[var(--color-text-heading)] [&.active-nav]:border-[var(--color-primary-accent)] [&.active-nav]:text-[var(--color-text-heading)]">{{ __('Contact') }}</span>
</div> --}}
{{-- <div class="top-bar dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [padding-bottom:12px] [border-bottom:1px_solid_#e3ecf5] [transition:border-color_0.3s] [gap:14px] [padding-bottom:14px] max-[480px]:[align-items:center]">
    <div class="logo-area [display:flex] [flex-direction:column] [align-items:flex-start] [gap:0]">
        <span class="logo-bf dark:[color:#e8edf2] dark:[&_i]:[color:#4a8ab5] [font-weight:700] [font-size:26px] [letter-spacing:-0.5px] [color:#0b2a40] [background:transparent] [padding:0] [display:flex] [align-items:center] [gap:8px] [transition:color_0.3s] [&_i]:[font-size:22px] [&_i]:[color:#0b2a40] [&_i]:[transition:color_0.3s] [font-size:24px] [&_i]:[font-size:20px] [&_i]:[color:#1a6b9c] max-[768px]:[font-size:20px]"><i class="fas fa-chart-line"></i> BF Markets</span>
        <span class="logo-sub dark:[color:#8aaccc] [font-weight:400] [font-size:13px] [color:#5a7d99] [letter-spacing:0.2px] [margin-top:-2px] [transition:color_0.3s] [font-size:12px] max-[768px]:[font-size:11px]">The Data Terminal for Sports Exchange Markets</span>
    </div>
    <div class="top-right [display:flex] [align-items:center] [gap:16px] [flex-wrap:wrap] [gap:10px] max-[480px]:[width:100%] max-[480px]:[justify-content:flex-start]">
        <button class="theme-toggle dark:[border-color:#3a5568] dark:[color:#b0c8dd] [background:none] [border:1px_solid_#d4e0ec] [border-radius:30px] [padding:3px_12px] [font-size:16px] [cursor:pointer] [color:#1f4b66] [transition:0.2s] [padding:4px_14px] [font-size:15px]" id="themeToggle" title="Dark/Light mode"><i class="fas fa-moon"></i></button>
        <button class="lang-btn active-lang dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]" data-lang="en">EN</button>
        <button class="lang-btn dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]" data-lang="ka">KA</button>
        <span class="btn-outline dark:[border-color:#3a5568] dark:[color:#b0c8dd] [border:1px_solid_#b8cdde] [background:transparent] [padding:4px_16px] [border-radius:60px] [font-weight:500] [font-size:13px] [color:#1f4b66] [cursor:default] [transition:0.3s] [padding:5px_16px] [font-size:12.5px] [cursor:pointer] [text-decoration:none] [display:inline-block]">Login</span>
        <span class="btn-primary dark:[background:#4a8ab5] dark:[color:white] [background:#0b2a40] [border:none] [padding:5px_18px] [border-radius:60px] [font-weight:500] [font-size:13px] [color:white] [cursor:default] [transition:0.3s] [padding:5px_16px] [font-size:12.5px] [cursor:pointer] [text-decoration:none] [display:inline-block]">Start Free Trial</span>
    </div>
</div> --}}
<header class="top-bar flex flex-wrap items-center justify-between gap-3.5 border-b border-[#e3ecf5] pb-3.5 dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [padding-bottom:12px] [border-bottom:1px_solid_#e3ecf5] [transition:border-color_0.3s] [gap:14px] [padding-bottom:14px] max-[480px]:[align-items:center]">
    <div class="logo-area flex flex-col items-start [display:flex] [flex-direction:column] [align-items:flex-start] [gap:0]">
        <a href="{{ route('football.home') }}" class="flex flex-col items-start">
            <img src="{{ url('logo.png') }}" class="h-auto max-w-full w-[250px] max-[480px]:w-[190px]" alt="" srcset="">
        </a>
    </div>
    <button type="button" id="mobileNavToggle" class="ml-auto inline-flex size-10 items-center justify-center rounded-lg border border-[#d4e0ec] text-[#1f4b66] dark:border-[#3a5568] dark:text-[#b0c8dd] lg:hidden" aria-controls="navLinks" aria-expanded="false" aria-label="Open menu">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <div class="top-right flex flex-wrap items-center gap-2.5 [display:flex] [align-items:center] [gap:16px] [flex-wrap:wrap] [gap:10px] max-[480px]:[width:100%] max-[480px]:[justify-content:flex-start]">
        <button type="button" class="theme-toggle cursor-pointer rounded-full border border-[#d4e0ec] px-3.5 py-1 dark:[border-color:#3a5568] dark:[color:#b0c8dd] [background:none] [border:1px_solid_#d4e0ec] [border-radius:30px] [padding:3px_12px] [font-size:16px] [cursor:pointer] [color:#1f4b66] [transition:0.2s] [padding:4px_14px] [font-size:15px]" id="themeToggle" title="Dark/Light mode"><i class="fas fa-moon"></i></button>
        <button type="button" class="lang-btn active-lang cursor-pointer rounded-full border border-[#d4e0ec] px-3.5 py-1 text-[13px] dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]" data-lang="en">EN</button>
        <button type="button" class="lang-btn cursor-pointer rounded-full border border-[#d4e0ec] px-3.5 py-1 text-[13px] dark:[border-color:#3a5568] dark:[color:#b0c8dd] dark:[&.active-lang]:[background:#4a8ab5] dark:[&.active-lang]:[border-color:#4a8ab5] dark:[&.active-lang]:[color:white] [background:none] [border:1px_solid_#d4e0ec] [padding:3px_12px] [border-radius:30px] [font-size:13px] [font-weight:500] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [&.active-lang]:[background:#0b2a40] [&.active-lang]:[color:white] [&.active-lang]:[border-color:#0b2a40] [padding:4px_14px] [text-decoration:none]" data-lang="ka">KA</button>
        <span class="btn-outline cursor-default rounded-full border border-[#b8cdde] px-4 py-1 text-[13px] dark:[border-color:#3a5568] dark:[color:#b0c8dd] [border:1px_solid_#b8cdde] [background:transparent] [padding:4px_16px] [border-radius:60px] [font-weight:500] [font-size:13px] [color:#1f4b66] [cursor:default] [transition:0.3s] [padding:5px_16px] [font-size:12.5px] [cursor:pointer] [text-decoration:none] [display:inline-block]">{{ __('Login') }}</span>
        <span class="btn-primary cursor-default rounded-full bg-[#0b2a40] px-4 py-1 text-[13px] text-white dark:[background:#4a8ab5] dark:[color:white] [background:#0b2a40] [border:none] [padding:5px_18px] [border-radius:60px] [font-weight:500] [font-size:13px] [color:white] [cursor:default] [transition:0.3s] [padding:5px_16px] [font-size:12.5px] [cursor:pointer] [text-decoration:none] [display:inline-block]">{{ __('Start Free Trial') }}</span>
    </div>
</header>
<div id="navLinks" class="hidden flex-wrap !p-[12px] items-center gap-3 border-b border-[#e3ecf5] p-3 text-[15px] font-medium lg:flex max-[480px]:!px-0 [&>a:nth-child(-n+2)]:max-[480px]:min-w-0 [&>a:nth-child(-n+2)]:max-[480px]:w-full [&>a]:max-[480px]:break-words max-[480px]:gap-2">
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

    <a href="{{ route('football.market-activity') }}" @class(['group flex min-w-[190px] items-center gap-3 rounded-lg border bg-white !p-[8px]  transition duration-200','border-[#d7e4f2] hover:border-[#e7b629] hover:shadow-sm','border-l-[3px] !border-l-[#e7b629] shadow-sm' => request()->routeIs('football.market-activity')])>
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

    <a href="{{ route('notifications') }}" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Notifications') }}</a>
    <a href="{{ route('subscriptions') }}" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Subscriptions') }}</a>
    <a href="{{ route('contact-us') }}" class="rounded-md !p-[8px] text-[#234d6b] transition hover:bg-[#f3f8fc] hover:text-[#1687d9]">{{ __('Contact') }}</a>
</div>
<script>
    (() => {
        const toggle = document.getElementById('mobileNavToggle');
        const nav = document.getElementById('navLinks');
        if (!toggle || !nav) return;

        const setOpen = (open) => {
            nav.classList.toggle('hidden', !open);
            nav.classList.toggle('flex', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            toggle.querySelector('i').className = open ? 'fas fa-times' : 'fas fa-bars';
        };

        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
        nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });
    })();
</script>
