@extends('users.layout.main')

@section('title', 'Create BF Markets Account | Live Betfair Access')
@section('meta_description', 'Create your BF Markets account to access live Betfair odds, liquidity tracking, and
    real-time market monitoring across multiple sports.')

@push('head')
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ __('Create BF Markets Account | Live Betfair Access') }}">
    <meta property="og:description"
        content="{{ __('Create your BF Markets account to access live Betfair odds, liquidity tracking, and real-time market monitoring across multiple sports.') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/seo/register-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@push('head')
    @vite(['resources/users/css/auth.css', 'resources/users/js/auth/app.js'])
@endpush

@section('content')
<div class="customer-auth">
    <div class="betguru-auth-bg min-h-screen">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <main data-customer-form
                    data-selected-country="{{ old('country', $defaultCountry) }}"
                    data-selected-dial-code="{{ old('mobile_code', $defaultDialCode) }}"
                    data-selected-country-code="{{ old('country_code', $defaultCountryCode) }}"
                    data-default-timezone="{{ $defaultTimezone }}"
                    class="overflow-hidden rounded-[2rem] betguru-card">
                    <template data-country-config>{!! json_encode($countryOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</template>
                    <section class="p-6 sm:p-8 lg:p-10 theme-bg-surface">
                        <div class="">
                            <div class="mb-8 lg:hidden">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-base font-bold text-[rgb(var(--surface))] betguru-gradient-badge">
                                        BF
                                    </div>

                                    <div>
                                        <p class="text-lg font-bold theme-text-main">BF Markets</p>
                                        <p class="text-xs theme-text-muted">{{ __('Customer Registration') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                                    {{ __('Customer Register') }}</p>
                                <h2 class="mt-3 text-3xl font-bold tracking-tight theme-text-main">
                                    {{ __('Start your BF Markets access') }}
                                </h2>
                                <p class="mt-3 text-sm leading-6 theme-text-muted">
                                    {{ __('Register to access live Betfair market monitoring, liquidity insights, and advanced filters built for active users.') }}
                                </p>
                            </div>

                            <div class="betguru-trial-banner mb-6">
                                <div class="betguru-trial-icon">✨</div>

                                <div>
                                    <p class="betguru-trial-title">{{ __('7-Day Free Trial') }}</p>
                                    <p class="betguru-trial-text">
                                        {{ __('No payment required. No card required. Full access for 7 days.') }}
                                    </p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('register.store') }}" data-customer-submit class="space-y-5"
                                novalidate>
                                @csrf

                                <input type="hidden" name="device_id">
                                <input type="hidden" name="fingerprint_hash">
                                <input type="hidden" name="timezone">
                                <input type="hidden" name="screen_resolution">
                                <input type="hidden" name="webgl_vendor">
                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="name" class="betguru-label">{{ __('Name') }}</label>
                                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                                            placeholder="{{ __('Enter first name') }}" autocomplete="given-name"
                                            class="betguru-input">
                                        @error('name')
                                            <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="surname" class="betguru-label">{{ __('Surname') }}</label>
                                        <input id="surname" type="text" name="surname" value="{{ old('surname') }}"
                                            placeholder="{{ __('Enter last name') }}" autocomplete="family-name"
                                            class="betguru-input">
                                        @error('surname')
                                            <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="username" class="betguru-label">{{ __('Username') }}</label>
                                    <input id="username" type="text" name="username" value="{{ old('username') }}"
                                        placeholder="{{ __('Choose a username') }}" autocomplete="username"
                                        class="betguru-input">
                                    @error('username')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="betguru-label">{{ __('Email Address') }}</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                                        placeholder="{{ __('Enter email address') }}" autocomplete="email"
                                        class="betguru-input">
                                    @error('email')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div>
                                        <label for="password" class="betguru-label">{{ __('Password') }}</label>

                                        <div class="relative">
                                            <input id="password" type="password" name="password" data-password-input
                                                placeholder="{{ __('Create password') }}" autocomplete="new-password"
                                                class="betguru-input pr-16">

                                            <button type="button" data-password-toggle
                                                class="absolute inset-y-0 right-4 text-sm font-semibold transition theme-text-muted">
                                                <span data-password-toggle-label data-show-label="{{ __('Show') }}"
                                                    data-hide-label="{{ __('Hide') }}">{{ __('Show') }}</span>
                                            </button>
                                        </div>

                                        <p class="mt-2 text-xs font-medium theme-text-muted" data-password-strength-text>
                                            {{ __('Use at least 8 characters with letters, numbers, and symbols.') }}
                                        </p>

                                        @error('password')
                                            <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="mt-3">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="h-2 rounded-full theme-bg-border" data-password-strength-segment>
                                            </div>
                                            <div class="h-2 rounded-full theme-bg-border" data-password-strength-segment>
                                            </div>
                                            <div class="h-2 rounded-full theme-bg-border" data-password-strength-segment>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="betguru-label">{{ __('Mobile Number') }}</label>

                                    <div class="flex overflow-visible rounded-2xl border transition theme-panel-soft">
                                        <div class="relative w-[250px] betguru-divider-input theme-bg-surface">
                                            <input type="hidden" name="mobile_code">
                                            <input type="hidden" name="country_code">

                                            <button type="button" data-country-toggle="code" aria-expanded="false"
                                                class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left">
                                                <div class="flex min-w-0 items-center gap-3" data-country-selected="code">
                                                    <img data-country-selected-flag
                                                        class="h-5 w-7 rounded-sm object-cover shadow-sm">

                                                    <div class="min-w-0">
                                                        <p class="text-xs theme-text-muted" data-country-selected-label></p>
                                                    </div>
                                                </div>

                                                <svg class="h-4 w-4 shrink-0 theme-text-muted" viewBox="0 0 20 20"
                                                    fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <div data-country-dropdown="code" hidden
                                                class="absolute left-0 top-[calc(100%+8px)] z-30 w-[360px] rounded-2xl border p-2 shadow-2xl theme-panel-surface">
                                                <div class="mb-2">
                                                    <input type="text" data-country-search="code"
                                                        placeholder="{{ __('Search country or code...') }}"
                                                        class="betguru-input">
                                                </div>

                                                <div class="max-h-72 overflow-y-auto">
                                                    <div data-country-options="code"></div>
                                                    <p data-country-empty="code" hidden
                                                        class="px-3 py-3 text-sm theme-text-muted">
                                                        {{ __('No countries found.') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="text" name="mobile" value="{{ old('mobile') }}"
                                            placeholder="{{ __('Enter mobile number') }}" inputmode="numeric"
                                            autocomplete="tel-national"
                                            class="w-full bg-transparent px-4 py-3.5 text-sm outline-none theme-text-main">
                                    </div>

                                    @error('country_code')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror

                                    @error('mobile')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="betguru-label">{{ __('Country') }}</label>

                                    <div class="relative">
                                        <input type="hidden" name="country">

                                        <button type="button" data-country-toggle="country" aria-expanded="false"
                                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3.5 text-left transition theme-panel-soft">
                                            <div class="flex min-w-0 items-center gap-3" data-country-selected="country">
                                                <img data-country-selected-flag
                                                    class="h-5 w-7 rounded-sm object-cover shadow-sm">

                                                <span class="truncate text-sm font-semibold theme-text-main" data-country-selected-label></span>
                                            </div>

                                            <span data-country-placeholder="country" class="text-sm theme-text-muted" hidden>
                                                {{ __('Select country') }}
                                            </span>

                                            <svg class="h-4 w-4 shrink-0 theme-text-muted" viewBox="0 0 20 20"
                                                fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div data-country-dropdown="country" hidden
                                            class="absolute left-0 top-[calc(100%+8px)] z-30 w-full rounded-2xl border p-2 shadow-2xl theme-panel-surface">
                                            <div class="mb-2">
                                                <input type="text" data-country-search="country"
                                                    placeholder="{{ __('Search country...') }}" class="betguru-input">
                                            </div>

                                            <div class="max-h-72 overflow-y-auto">
                                                <div data-country-options="country"></div>
                                                <p data-country-empty="country" hidden
                                                    class="px-3 py-3 text-sm theme-text-muted">
                                                    {{ __('No countries found.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    @error('country')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="rounded-2xl px-4 py-4 betguru-card-soft">
                                    <label class="flex items-start gap-3">
                                        <input type="checkbox" name="terms" value="1"
                                            {{ old('terms') ? 'checked' : '' }}
                                            class="mt-1 h-4 w-4 rounded betguru-check">
                                        <span class="text-sm leading-6 theme-text-muted">
                                            {{ __('I agree to the') }}
                                            <a target="_blank" href="{{ route('terms-of-service') }}"
                                                class="betguru-link">{{ __('Terms of Service') }}</a>
                                            {{ __('and') }}
                                            <a target="_blank" href="{{ route('privacy-policy') }}"
                                                class="betguru-link">{{ __('Privacy Policy') }}</a>.
                                        </span>
                                    </label>

                                    @error('terms')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="betguru-btn-primary" data-submit-button>
                                    <svg data-submit-spinner hidden class="mr-2 h-5 w-5 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-20" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-90" fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                                    </svg>

                                    <span data-submit-label data-loading-label="{{ __('Creating account...') }}">{{ __('Start Free Trial') }}</span>
                                </button>

                                <div class="grid grid-cols-1 gap-3 pt-1 sm:grid-cols-3">
                                    <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                        {{ __('Secure Registration') }}
                                    </div>
                                    <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                        {{ __('SMS Verification') }}
                                    </div>
                                    <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                        {{ __('English / Georgian') }}
                                    </div>
                                </div>

                                <p class="text-center text-sm theme-text-muted">
                                    {{ __('Already have an account?') }}
                                    <a href="{{ route('login') }}" class="betguru-link">{{ __('Login') }}</a>
                                </p>
                            </form>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</div>
@endsection
