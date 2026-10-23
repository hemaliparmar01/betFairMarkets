@extends('users.layout.main')

@section('title', 'Login to BF Markets | Live Betfair Access')
@section('meta_description',
    'Login to BF Markets to access live Betfair odds, liquidity tracking, smart filters, and
    real-time market monitoring.')

@push('head')
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:title" content="{{ __('Login to BF Markets | Live Betfair Access') }}">
    <meta property="og:description"
        content="{{ __('Login to BF Markets to access live Betfair odds, liquidity tracking, smart filters, and real-time market monitoring.') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/seo/login-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@push('css')
    <style>
        .email-otp-warning {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 16px;
            margin-bottom: 18px;
            border: 1px solid rgb(var(--border-strong));
            border-radius: 14px;
            background: rgb(var(--bg-soft));
            color: rgb(var(--text-main));
        }

        .email-otp-warning__icon {
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgb(var(--primary));
            color: rgb(var(--primary-contrast));
            font-weight: 800;
        }

        .email-otp-warning__content {
            flex: 1;
        }

        .email-otp-warning__title {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 800;
        }

        .email-otp-warning__message {
            margin: 0 0 14px;
            color: rgb(var(--text-muted));
            line-height: 1.5;
        }

        .email-otp-warning__button {
            border: 0;
            border-radius: 12px;
            padding: 12px 18px;
            background: rgb(var(--primary));
            color: rgb(var(--primary-contrast));
            font-weight: 800;
            cursor: pointer;
        }

        .email-otp-warning__button:hover {
            background: rgb(var(--primary-hover));
        }
    </style>
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
                    data-selected-dial-code="{{ $defaultDialCode }}"
                    data-selected-country-code="{{ $defaultCountryCode }}"
                    class="overflow-hidden rounded-[2rem] betguru-card">
                    <template data-country-config>{!! json_encode($countryOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</template>
                    {{-- Right Side --}}
                    @if (session('customer_pending_login'))
                        <div class="mb-5 betguru-device-conflict">
                            <div class="betguru-device-conflict__header">
                                <div class="betguru-device-conflict__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v4m0 4h.01M10.29 3.86l-8 14A1 1 0 003.16 19h17.68a1 1 0 00.87-1.5l-8-14a1 1 0 00-1.74 0z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p class="betguru-device-conflict__eyebrow">{{ __('Session Conflict') }}</p>
                                    <h3 class="betguru-device-conflict__title">
                                        {{ __('This account is already active on another device') }}
                                    </h3>
                                    <p class="betguru-device-conflict__text">
                                        {{ __('To continue on this device, confirm once and we will automatically log out the previous session.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="betguru-device-conflict__meta">
                                <div class="betguru-device-conflict__meta-item">
                                    <span class="betguru-device-conflict__meta-label">{{ __('Device') }}</span>
                                    <span class="betguru-device-conflict__meta-value">
                                        {{ ucfirst($conflictingSession?->device_type ?? __('Unknown')) }}
                                    </span>
                                </div>

                                <div class="betguru-device-conflict__meta-item">
                                    <span class="betguru-device-conflict__meta-label">{{ __('IP Address') }}</span>
                                    <span class="betguru-device-conflict__meta-value">
                                        {{ $conflictingSession?->ip_address ?? __('N/A') }}
                                    </span>
                                </div>

                                <div class="betguru-device-conflict__meta-item">
                                    <span class="betguru-device-conflict__meta-label">{{ __('Login Time') }}</span>
                                    <span class="betguru-device-conflict__meta-value">
                                        {{ $conflictingSession?->logged_in_at?->format('d M Y H:i') ?? __('N/A') }}
                                    </span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('login.sessions.confirm') }}"
                                class="betguru-device-conflict__form">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="betguru-btn-primary betguru-device-conflict__button">
                                    {{ __('Log out previous device and continue') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('login.sessions.cancel') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="betguru-link">Cancel</button>
                            </form>
                        </div>
                    @endif
                    <section class="p-6 sm:p-8 lg:p-10 theme-bg-surface">
                        <div class="mx-auto max-w-xl">
                            <div class="mb-8 lg:hidden">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-base font-bold text-[rgb(var(--surface))] betguru-gradient-badge">
                                        BF
                                    </div>

                                    <div>
                                        <p class="text-lg font-bold theme-text-main">
                                            BF Markets
                                        </p>
                                        <p class="text-xs theme-text-muted">
                                            {{ __('Customer Login') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8">
                                @if (app()->getLocale() == 'ka')
                                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                                        {{ __('Login to the system') }}
                                    </p>
                                @else
                                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                                        {{ __('Customer Login') }}
                                    </p>
                                    <h2 class="mt-3 text-3xl font-bold tracking-tight theme-text-main">
                                        {{ __('Welcome Back') }}
                                    </h2>
                                @endif
                                <p class="mt-3 text-sm leading-6 theme-text-muted">
                                    {{ __('Sign in to continue monitoring live Betfair markets, liquidity movement, and custom match filters.') }}
                                </p>
                            </div>

                            @if (session('status'))
                                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-alert-success">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            {{-- Login Method Tabs --}}
                            <div class="mb-6 rounded-[1.25rem] p-1 betguru-card-soft">
                                <div class="grid grid-cols-2 gap-1">
                                    <button type="button" data-login-tab="password"
                                        class="rounded-2xl px-4 py-3 text-sm font-semibold transition {{ (session('active_login_tab') ?? (session('login_otp_mobile') || old('mobile') ? 'otp' : 'password')) === 'password' ? 'betguru-toggle-tab-active' : 'betguru-toggle-tab-inactive' }}">
                                        {{ __('Password Login') }}
                                    </button>

                                    <button type="button" data-login-tab="otp"
                                        class="rounded-2xl px-4 py-3 text-sm font-semibold transition {{ (session('active_login_tab') ?? (session('login_otp_mobile') || old('mobile') ? 'otp' : 'password')) === 'otp' ? 'betguru-toggle-tab-active' : 'betguru-toggle-tab-inactive' }}">
                                        {{ __('OTP login') }}</button>
                                </div>
                            </div>

                            {{-- Password Login --}}
                            <div data-login-panel="password" {{ (session('active_login_tab') ?? (session('login_otp_mobile') || old('mobile') ? 'otp' : 'password')) !== 'password' ? 'hidden' : '' }}>
                                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="device_id">
                                    <input type="hidden" name="fingerprint_hash">
                                    <input type="hidden" name="timezone">
                                    <input type="hidden" name="screen_resolution">
                                    <input type="hidden" name="webgl_vendor">

                                    <div>
                                        <label for="login_identity"
                                            class="betguru-label">{{ __('Email or Username') }}</label>
                                        <input id="login_identity" type="text" name="identity"
                                            value="{{ old('identity') }}"
                                            placeholder="{{ __('Enter email or username') }}" autocomplete="username"
                                            class="betguru-input">
                                        @error('identity')
                                            <p class="mt-1 text-xs theme-text-danger">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <div class="mb-2 flex items-center justify-between">
                                            <label for="login_password"
                                                class="betguru-label !mb-0">{{ __('Password') }}</label>
                                            <a href="{{ route('customers.password.request') }}"
                                                class="betguru-link text-sm">{{ __('Forgot Password?') }}</a>
                                        </div>

                                        <div class="relative">
                                            <input id="login_password" type="password" data-password-input
                                                name="password" placeholder="{{ __('Enter password') }}"
                                                autocomplete="current-password" class="betguru-input pr-16">

                                            <button type="button" data-password-toggle
                                                class="absolute inset-y-0 right-4 text-sm font-semibold transition theme-text-muted">
                                                <span data-password-toggle-label data-show-label="{{ __('Show') }}"
                                                    data-hide-label="{{ __('Hide') }}">{{ __('Show') }}</span>
                                            </button>
                                        </div>

                                        @error('password')
                                            <p class="mt-1 text-xs theme-text-danger">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <button type="submit" class="betguru-btn-primary">
                                        {{ __('Sign In') }}
                                    </button>
                                </form>
                            </div>

                            {{-- Mobile OTP Login --}}
                            <div data-login-panel="otp" {{ (session('active_login_tab') ?? (session('login_otp_mobile') || old('mobile') ? 'otp' : 'password')) !== 'otp' ? 'hidden' : '' }}>
                                <form method="POST" action="{{ route('login.mobile.send-code') }}" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="device_id">
                                    <input type="hidden" name="fingerprint_hash">
                                    <input type="hidden" name="timezone">
                                    <input type="hidden" name="screen_resolution">
                                    <input type="hidden" name="webgl_vendor">

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

                                        @error('mobile')
                                            <p class="mt-1 text-xs theme-text-danger">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                        @if ($errors->has('sms_warning'))
                                            <div class="email-otp-warning mt-3">
                                                <div class="email-otp-warning__icon">!</div>

                                                <div class="email-otp-warning__content"><h3 class="email-otp-warning__title">{{ __('SMS limit reached') }}</h3>

                                                    <p class="email-otp-warning__message">
                                                        {{ __('Would you like to receive the verification code via your registered email instead?') }}
                                                    </p>

                                                    <button type="submit" name="otp_channel" value="email" class="email-otp-warning__button">
                                                        {{ __('Send OTP to Email') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                        @error('country_code')
                                            <p class="mt-1 text-xs theme-text-danger">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                        @error('identity')
                                            <p class="mt-1 text-xs theme-text-danger">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    @if (!$errors->has('sms_warning'))
                                        <button type="submit" class="betguru-btn-primary">
                                            {{ __('Send Login Code') }}
                                        </button>
                                    @endif
                                </form>

                                {{-- @if (session('login_otp_mobile') || old('mobile'))
                                    <form method="POST" action="{{ route('login.mobile.verify-code') }}"
                                        class="mt-5 space-y-5">
                                        @csrf

                                        <input type="hidden" name="country_code"
                                            value="{{ old('country_code', '+995') }}">
                                        <input type="hidden" name="mobile" value="{{ old('mobile') }}">
                                        <input type="hidden" name="device_id">
                                        <input type="hidden" name="fingerprint_hash">
                                        <input type="hidden" name="timezone">
                                        <input type="hidden" name="screen_resolution">
                                        <input type="hidden" name="webgl_vendor">

                                        <div>
                                            <label class="betguru-label">{{ __('Verification Code') }}</label>
                                            <input type="text" name="code" value="{{ old('code') }}"
                                                placeholder="{{ __('Enter 6-digit code') }}" class="betguru-input">

                                            @error('code')
                                                <p class="mt-1 text-xs theme-text-danger">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <button type="submit" class="betguru-btn-primary">
                                            {{ __('Verify & Login') }}
                                        </button>
                                    </form>
                                @endif --}}
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('Fast Access') }}
                                </div>
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('OTP Ready') }}
                                </div>
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('Secure Session') }}
                                </div>
                            </div>

                            <p class="mt-6 text-center text-sm theme-text-muted">
                                {{ __("Don't have an account?") }}
                                <a href="{{ route('register') }}" class="betguru-link">{{ __('Create one now') }}</a>
                            </p>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</div>
@endsection
