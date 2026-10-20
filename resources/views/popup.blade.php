@php $checkAuthAndSubscription = checkAuthAndSubscription(); @endphp
@if ($premiumGate ?? false)
    @if (! $checkAuthAndSubscription['display_odds'])
        @php
            $premiumRoutes = [
                'football.market-activity', 'cricket.market-activity', 'icehockey.market-activity',
                'americanfootball.market-activity', 'baseball.market-activity', 'basketball.market-activity',
                'tennis.market-activity', 'greyhounds.market-activity', 'horseracing.market-activity',
                'football.pinnacle-odds', 'horseracing.home', 'greyhounds.home',
            ];
            $restrictedPremiumUrls = array_map(static fn (string $name): string => route($name), $premiumRoutes);
        @endphp
        <dialog id="premiumAccess" aria-labelledby="premiumAccessTitle" aria-describedby="premiumAccessMessage"
            class="fixed inset-0 m-auto max-h-[calc(100dvh_-_2rem)] w-[calc(100%_-_2rem)] max-w-md overflow-y-auto rounded-2xl border-0 bg-white p-6 text-[#0b2a40] shadow-xl backdrop:bg-black/60 dark:bg-[#1a2a38] dark:text-[#e8edf2]">
            <form method="dialog" class="absolute right-3 top-3">
                <button type="submit" autofocus aria-label="{{ __('Close') }}" class="flex size-9 cursor-pointer items-center justify-center rounded-full text-xl text-[#5a7d99] hover:bg-[#edf6fc] dark:text-[#b0c8dd] dark:hover:bg-[#29465b]">&times;</button>
            </form>
            <i class="fas fa-lock mb-3 text-2xl text-[#1a6b9c]" aria-hidden="true"></i>
            <h2 id="premiumAccessTitle" class="pr-8 text-lg font-bold">{{ __('Registration and subscription required') }}</h2>
            <p id="premiumAccessMessage" class="mt-3 text-sm leading-relaxed text-[#5a7d99] dark:text-[#b0c8dd]">{{ __('Register and activate your 7-day free trial (first-time users), or purchase a paid subscription to access Market Activity, Pinnacle Drops, Horse Racing and Greyhounds.') }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('register') }}" class="rounded-xl bg-[#0b2a40] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1a6b9c] dark:bg-[#4a8ab5]">{{ __('Register · 7-day free trial') }}</a>
                <a href="{{ route('subscriptions') }}" class="rounded-xl border border-[#d4e0ec] px-4 py-2 text-sm font-semibold text-[#1f4b66] hover:bg-[#edf6fc] dark:border-[#3a5568] dark:text-[#b0c8dd] dark:hover:bg-[#29465b]">{{ __('View subscriptions') }}</a>
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-[#1a6b9c] underline dark:text-[#8aaccc]">{{ __('Log in') }}</a>
            </div>
        </dialog>
        <script>
            (() => {
                const dialog = document.getElementById('premiumAccess');
                const restrictedUrls = @json($restrictedPremiumUrls);
                document.addEventListener('click', (event) => {
                    const link = event.target.closest('a[href]');
                    if (!event.target.closest('[data-premium-required]') && !restrictedUrls.includes(link?.href)) return;
                    event.preventDefault();
                    if (!dialog.open) dialog.showModal();
                });
                @if (request()->routeIs(...$premiumRoutes) || request()->routeIs('horseracing.matches.show', 'greyhounds.matches.show'))
                    dialog.showModal();
                @endif
            })();
        </script>
    @endif
@elseif ($checkAuthAndSubscription['is_authenticated'] === false || $checkAuthAndSubscription['has_active_subscription'] === false)
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('Bottom Modal') }}</title>
        @vite(['resources/users/css/app.css', 'resources/users/js/app.js'])
    </head>

    <body class="m-0 p-0">
        <div id="subscriptionModal" class="fixed inset-0 z-50 flex items-end justify-center bg-black/60">
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 text-[#0b2a40] shadow-xl dark:bg-[#1a2a38] dark:text-[#e8edf2]">
                <button type="button" onclick="document.getElementById('subscriptionModal')?.remove()" class="absolute cursor-pointer top-4 right-4 text-[#5b7c99] hover:text-[#0b2a40] dark:text-[#b0c8dd] dark:hover:text-[#e8edf2] text-xl font-bold">
                    &times;
                </button>
                <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-[#d4e0ec] dark:bg-[#3a5568]"></div>

                <h2 class="text-[1rem] font-semibold">
                    @if ($checkAuthAndSubscription['is_authenticated'] === false)
                        {{ __('Login Required') }}
                    @elseif ($checkAuthAndSubscription['has_active_subscription'] === false)
                        {{ __('Subscription Required') }}
                    @endif
                </h2>

                <div class="text-[0.9rem] mt-2 mb-2 text-[#5b7c99] dark:text-[#b0c8dd]" data-subscription-message>
                    @if ($checkAuthAndSubscription['is_authenticated'] === false)
                        {{ __('Please log in to access match insights and platform features.') }}
                    @elseif ($checkAuthAndSubscription['has_active_subscription'] === false)
                        {{ __('Your subscription is inactive. Subscribe now to unlock all premium content and betting insights.') }}
                    @endif
                </div>

                <div class="pt-4 text-end">
                    @if ($checkAuthAndSubscription['is_authenticated'] === false)
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center px-[8px] py-[8px] rounded-xl bg-[#0b2a40] text-white font-medium transition-colors hover:bg-[#1a6b9c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1a6b9c] dark:bg-[#4a8ab5] dark:hover:bg-[#3f79a0] dark:focus-visible:outline-[#4a8ab5]">
                            {{ __('Log in') }}
                        </a>

                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center px-[8px] py-[8px] rounded-xl border border-[#d4e0ec] bg-[#f0f6fc] text-[#1f4b66] font-medium ml-2 transition-colors hover:border-[#1a6b9c] hover:bg-[#e3effa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1a6b9c] dark:border-[#3a5568] dark:bg-[#253a4a] dark:text-[#b0c8dd] dark:hover:border-[#4a8ab5] dark:hover:bg-[#29465b] dark:focus-visible:outline-[#4a8ab5]">
                            {{ __('Register') }}
                        </a>
                    @elseif ($checkAuthAndSubscription['has_active_subscription'] === false)
                        <a href="{{ route('checkout-single') }}"
                            class="inline-flex items-center justify-center px-[8px] py-[8px] rounded-xl bg-[#0b2a40] text-white font-medium transition-colors hover:bg-[#1a6b9c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1a6b9c] dark:bg-[#4a8ab5] dark:hover:bg-[#3f79a0] dark:focus-visible:outline-[#4a8ab5]">
                            {{ __('Subscribe Now') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </body>

    </html>
@endif
