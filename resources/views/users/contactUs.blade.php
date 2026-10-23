@extends('users.layout.main')

@push('head')
    @vite('resources/users/css/auth.css')
@endpush

@section('title', __('Contact Us | BF Markets'))
@section('meta_description', __('Need help or have a question? Our team is available 24/7 and ready to assist you.'))

@section('content')
<div class="customer-auth">
    @php
        $title = __('Contact Us');
        $subtitle = __('Need help or have a question? Our team is available 24/7 and ready to assist you.');

        $supportTitle = __('24/7 Customer Support');
        $supportText = __('Our team is available around the clock to assist you with platform access, subscriptions, payments, and technical questions.');

        $companyText = __('BF Markets is owned, developed, and operated by Digital Analytics Group LLC.');

        $trustIndicators = [
            __('🔒 GDPR Compliant'),
            __('⚡ Real-time Infrastructure'),
            __('🏢 Digital Analytics Group LLC'),
            __('📈 99.9% Uptime SLA'),
            __('💬 24/7 Customer Support'),
        ];
    @endphp

    <div class="betguru-auth-bg min-h-screen">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="rounded-[2rem] p-6 sm:p-8 lg:p-10 betguru-card-elevated">
                    <div class="mx-auto max-w-4xl text-center">
                        <span class="inline-flex items-center rounded-full border border-[rgb(var(--border))] bg-[rgb(var(--surface))]/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] theme-text-primary">
                            {{ __('Support') }}
                        </span>

                        <h1 class="mt-5 text-3xl font-bold theme-text-main sm:text-4xl lg:text-5xl">
                            {{ $title }}
                        </h1>

                        <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 theme-text-muted sm:text-base">
                            {{ $subtitle }}
                        </p>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        @foreach ($trustIndicators as $indicator)
                            <span class="inline-flex items-center rounded-full border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-4 py-2 text-sm font-medium theme-text-soft shadow-sm">
                                {{ $indicator }}
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-10 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="rounded-[1.75rem] p-6 sm:p-8 betguru-card-soft">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl font-bold theme-text-main">
                                        {{ __('Send Us a Message') }}
                                    </h2>
                                    <p class="mt-2 text-sm leading-6 theme-text-muted">
                                        {{ __('Fill out the form below and our team will get back to you as soon as possible.') }}
                                    </p>
                                </div>
                            </div>

                            @if (session('success'))
                                <div class="mt-6 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="mt-6 rounded-2xl border border-[rgb(var(--danger)_/_0.25)] bg-[rgb(var(--danger)_/_0.10)] px-4 py-3 text-sm font-medium text-[rgb(var(--danger))]">
                                    {{ __('Please review the entered details and try again.') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('contact-us.store') }}" class="mt-6 space-y-5">
                                @csrf

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label for="name" class="betguru-label">
                                            {{ __('Name') }}
                                        </label>
                                        <input
                                            id="name"
                                            name="name"
                                            type="text"
                                            value="{{ old('name', auth('customers')->user()->name ?? '') }}"
                                            placeholder="{{ __('Enter your name') }}"
                                            class="betguru-input"
                                        >
                                        @error('name')
                                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="email" class="betguru-label">
                                            {{ __('Email Address') }}
                                        </label>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value="{{ old('email', auth('customers')->user()->email ?? '') }}"
                                            placeholder="{{ __('Enter your email address') }}"
                                            class="betguru-input"
                                        >
                                        @error('email')
                                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="subject" class="betguru-label">
                                        {{ __('Subject') }}
                                    </label>
                                    <input
                                        id="subject"
                                        name="subject"
                                        type="text"
                                        value="{{ old('subject') }}"
                                        placeholder="{{ __('Enter the subject') }}"
                                        class="betguru-input"
                                    >
                                    @error('subject')
                                        <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="message" class="betguru-label">
                                        {{ __('Message') }}
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="7"
                                        placeholder="{{ __('Write your message here') }}"
                                        class="betguru-input resize-none"
                                    >{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-2xl px-6 py-3 text-sm font-semibold text-[rgb(var(--surface))] transition hover:-translate-y-0.5 betguru-gradient-cta sm:w-auto"
                                    >
                                        {{ __('Send Message') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-[1.75rem] p-6 sm:p-8 betguru-card-soft">
                                <h2 class="text-2xl font-bold theme-text-main">
                                    {{ __('Contact Methods') }}
                                </h2>

                                <div class="mt-6 space-y-5">
                                    <div class="rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-5 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] theme-text-primary">
                                            {{ __('Email Support') }}
                                        </p>
                                        <a href="mailto:office@betfairmarkets.com" class="mt-2 inline-flex text-base font-semibold theme-text-main hover:opacity-80">
                                            📧 office@betfairmarkets.com
                                        </a>
                                    </div>

                                    <div class="rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-5 py-5">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] theme-text-primary">
                                            {{ __('WhatsApp Support') }}
                                        </p>

                                        <a
                                            href="https://wa.me/995511705508"
                                            target="_blank"
                                            rel="noopener"
                                            class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-[rgb(var(--success))] px-5 py-4 text-sm font-semibold text-[rgb(var(--surface))] transition hover:bg-[rgb(var(--success))]"
                                        >
                                            💬 {{ __('Chat on WhatsApp') }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[1.75rem] p-6 sm:p-8 betguru-card-soft">
                                <h2 class="text-2xl font-bold theme-text-main">
                                    {{ $supportTitle }}
                                </h2>
                                <p class="mt-4 text-sm leading-7 theme-text-soft">
                                    {{ $supportText }}
                                </p>
                            </div>

                            <div class="rounded-[1.75rem] p-6 sm:p-8 betguru-card-soft">
                                <h2 class="text-2xl font-bold theme-text-main">
                                    {{ __('Company Information') }}
                                </h2>
                                <p class="mt-4 text-sm leading-7 theme-text-soft">
                                    {{ $companyText }}
                                </p>

                                <a
                                    href="https://digitalanalyticsgroup.com"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-4 inline-flex text-sm font-semibold theme-text-primary hover:opacity-80"
                                >
                                    https://digitalanalyticsgroup.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
