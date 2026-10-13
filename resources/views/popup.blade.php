@php $checkAuthAndSubscription = checkAuthAndSubscription(); @endphp
@if ($checkAuthAndSubscription['is_authenticated'] === false || $checkAuthAndSubscription['has_active_subscription'] === false)
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
