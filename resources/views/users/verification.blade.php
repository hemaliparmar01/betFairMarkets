@extends('users.layout.main')

@section('title', 'Verify Your Account | BF Markets')
@section('meta_description', 'Create your BF Markets account to access live Betfair odds, liquidity tracking, and real-time market monitoring across multiple sports.')

@push('head')
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ __('Verify Your Account | BF Markets') }}">
    <meta property="og:description" content="{{ __('Create your BF Markets account to access live Betfair odds, liquidity tracking, and real-time market monitoring across multiple sports.') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/seo/login-og.jpg') }}">
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
                <main class="overflow-hidden rounded-[2rem] betguru-card">
                    <section class="p-6 sm:p-8 lg:p-10 theme-bg-surface">
                        <div class="mx-auto max-w-xl">
                            <div class="mb-8 lg:hidden">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-base font-bold text-[rgb(var(--surface))] betguru-gradient-badge">
                                        BF
                                    </div>

                                    <div>
                                        <p class="text-lg font-bold theme-text-main">
                                            BF Markets
                                        </p>
                                        <p class="text-xs theme-text-muted">
                                            {{ __('Verification') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                                    {{ __('Secure Verification') }}
                                </p>

                                <h2 class="mt-3 text-3xl font-bold tracking-tight theme-text-main">
                                    {{ __('Verify your account') }}
                                </h2>
                            </div>

                            @if (session('status'))<p role="status" class="mb-4 theme-alert-success">{{ session('status') }}</p>@endif
                            @if (session('success'))
                                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            {{--<div class="mb-6 rounded-[1.5rem] p-5 betguru-card-soft">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-[rgb(var(--surface))] betguru-gradient-badge">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16h.01M8.2 11.4a4 4 0 117.6 0c0 1.96-1.6 2.54-2.6 3.35-.63.5-1 1.06-1 1.75" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold theme-text-main">
                                            {{ __('Verification required') }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 theme-text-muted">
                                            {{ __('This extra step helps protect your account and keeps your premium access secure.') }}
                                        </p>
                                    </div>
                                </div>
                            </div> --}}

                            {{-- <form method="POST" action="#" class="space-y-5"> --}}
                            @if (session('verification_delivery_error'))
                                <p role="alert" class="mb-5 rounded-2xl px-4 py-3 text-sm theme-text-danger">{{ session('verification_delivery_error') }}</p>
                            @endif
                            @php $verification_via = $isLoginOtp ? 'login' : 'register'; @endphp
                            @if ($verification_via == 'register')
                                <p class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-alert-success">OTP sent via {{ $isLoginOtp ? $customer->login_channel_via : $customer->verification_via }}</p>
                            @else
                                <p class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-alert-success">OTP has been sent into {{ $customer->login_channel_via }}</p>
                            @endif

                            <form method="POST" action="{{ route($isLoginOtp ? 'login.mobile.verify-code' : 'customers.verification.verify') }}" class="space-y-5">
                                @csrf

                                <input type="hidden" name="user_id" value="{{ $customer->id }}">
                                <input type="hidden" name="verification_via" value="{{ $verification_via }}">
                                <div>
                                    <label for="verification_code" class="betguru-label">{{ __('Verification Code') }}</label>
                                    <input name="code" type="text" maxlength="6" data-numeric-code class="betguru-input text-center text-lg font-semibold tracking-[0.35em]" placeholder="{{ __('Enter 6-digit code') }}">
                                    @error('code')
                                        <p class="mt-2 text-sm theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="betguru-btn-primary">
                                    {{ __('Verify & Continue') }}
                                </button>
                            </form>

                            <div class="mt-5 rounded-2xl px-4 py-4 betguru-card-soft">
                                <div>
                                    <div>
                                        <p class="text-sm font-semibold theme-text-main">
                                            {{ __("Didn't receive the code?") }}
                                        </p>
                                        <p class="mt-1 text-sm theme-text-muted">
                                            {{ __('You can request a new verification code if the previous one has expired.') }}
                                        </p>
                                    </div>

                                    <form method="POST" action="{{ route($isLoginOtp ? 'login.mobile.resend-code' : 'customers.verification.resend') }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $customer->id }}">
                                        <input type="hidden" name="verification_via" value="{{ $verification_via }}">
                                        <button type="submit" class="rounded-2xl px-4 py-3 text-sm font-semibold theme-panel-subtle">
                                            {{ __('Resend Code') }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('Secure Access') }}
                                </div>
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('Fast Verification') }}
                                </div>
                                <div class="rounded-2xl px-4 py-3 text-center text-xs font-medium betguru-muted-box">
                                    {{ __('Protected Session') }}
                                </div>
                            </div>

                            <p class="mt-6 text-center text-sm theme-text-muted">
                                {{ __('Need to use a different account?') }}
                                <a href="{{ route('login') }}" class="betguru-link">{{ __('Back to login') }}</a>
                            </p>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</div>
@endsection
