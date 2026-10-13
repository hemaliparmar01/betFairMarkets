@extends('users.layout.main')

@section('title', 'Login · BF Markets')

@section('content')
    <section class="relative isolate my-6 overflow-hidden rounded-[28px] border border-[#dce9f4] bg-gradient-to-br from-[#f5faff] via-white to-[#edf6fc] px-3 py-6 sm:px-6 sm:py-10 lg:px-10 dark:border-[#2a3f50] dark:from-[#142634] dark:via-[#1a2a38] dark:to-[#173247]">
        <div class="pointer-events-none absolute -right-20 -top-20 -z-10 size-72 rounded-full bg-[#79bde8]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 -z-10 size-72 rounded-full bg-[#1a6b9c]/10 blur-3xl"></div>

        <div class="mx-auto max-w-4xl">
            <div class="mb-7 text-center sm:mb-9">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#bfdaed] bg-white/80 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-[#1a6b9c] shadow-sm dark:border-[#36576f] dark:bg-[#1f3444]/90 dark:text-[#7dc0e9]">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                    Customer Login
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-[#0b2a40] sm:text-4xl dark:text-white">Welcome back to BF Markets</h1>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-[#5a7d99] sm:text-base dark:text-[#a9bfd0]">Access live market monitoring, movement insights, and your saved football matches.</p>
            </div>

            <div class="rounded-[24px] border border-[#d7e7f3] bg-white/95 p-4 shadow-[0_20px_60px_rgba(11,42,64,0.10)] sm:p-7 lg:p-9 dark:border-[#314b5d] dark:bg-[#1d3040]/95">
                <div class="grid grid-cols-2 gap-1.5 rounded-2xl border border-[#d8e8f4] bg-[#edf6fc] p-1.5 dark:border-[#304c60] dark:bg-[#152837]" role="tablist" aria-label="Login method">
                    <button type="button" id="password-login-tab" class="rounded-xl px-3 py-3 text-sm font-bold text-[#5a7d99] transition aria-selected:bg-white aria-selected:text-[#0b2a40] aria-selected:shadow-sm sm:text-base dark:text-[#9bb5c8] dark:aria-selected:bg-[#29465b] dark:aria-selected:text-white" data-login-tab="password" role="tab" aria-controls="password-login-panel" aria-selected="true">
                        <i class="fas fa-lock mr-1.5" aria-hidden="true"></i>
                        Password Login
                    </button>
                    <button type="button" id="otp-login-tab" class="rounded-xl px-3 py-3 text-sm font-bold text-[#5a7d99] transition aria-selected:bg-white aria-selected:text-[#0b2a40] aria-selected:shadow-sm sm:text-base dark:text-[#9bb5c8] dark:aria-selected:bg-[#29465b] dark:aria-selected:text-white" data-login-tab="otp" role="tab" aria-controls="otp-login-panel" aria-selected="false">
                        <i class="fas fa-mobile-screen-button mr-1.5" aria-hidden="true"></i>
                        OTP Login
                    </button>
                </div>

                <div class="mt-7">
                    @if (session('status'))
                        <p role="status" class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
                    @endif
                    @if (session('customer_pending_login'))
                        <div role="alert" class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-[#0b2a40] dark:border-amber-800 dark:bg-[#162936] dark:text-white">
                            <h2 class="font-bold">Already logged in on another device</h2>
                            <p class="mt-2 leading-6">Only one login can be active at a time. Would you like to log out all previous devices and continue here?</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <form action="{{ route('login.sessions.confirm') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="confirm" value="1">
                                    <button type="submit" class="rounded-xl bg-[#1a6b9c] px-4 py-3 font-semibold text-white">Logout previous devices &amp; continue</button>
                                </form>
                                <form action="{{ route('login.sessions.cancel') }}" method="post">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-[#b8cdde] px-4 py-3 font-semibold dark:border-[#365165]">Cancel</button>
                                </form>
                            </div>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="password-login-panel" data-login-panel="password" role="tabpanel" aria-labelledby="password-login-tab">
                        <form action="{{ route('login.store') }}" method="post" data-customer-auth>
                            @csrf
                        <div class="space-y-5">
                            <div>
                                <label for="login-identity" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Email Address or Username</label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-[#75a2bf]" aria-hidden="true"></i>
                                    <input id="login-identity" name="identity" value="{{ old('identity') }}" required type="text" autocomplete="username" placeholder="Enter email address or username" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] pl-11 pr-4 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                                </div>
                            </div>

                            <div>
                                <label for="login-password" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Password</label>
                                <div class="relative">
                                    <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-[#75a2bf]" aria-hidden="true"></i>
                                    <input id="login-password" name="password" required type="password" autocomplete="current-password" placeholder="Enter your password" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] pl-11 pr-20 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                                    <button type="button" data-password-toggle="login-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[#1a6b9c] hover:text-[#0b2a40] dark:text-[#79bde8] dark:hover:text-white">Show</button>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <label class="inline-flex cursor-pointer items-center gap-2 text-[#5a7d99] dark:text-[#a9bfd0]">
                                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-[#aec9dc] text-[#1a6b9c] focus:ring-[#1a6b9c]" />
                                    Remember me
                                </label>
                                <button type="button" class="font-semibold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Forgot password?</button>
                            </div>
                        </div>

                        <button type="submit" class="mt-7 inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] px-6 text-sm font-bold text-white shadow-[0_12px_28px_rgba(26,107,156,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(26,107,156,0.32)]">
                            Login to Your Account
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        </form>
                    </div>

                    <div id="otp-login-panel" class="hidden" data-login-panel="otp" role="tabpanel" aria-labelledby="otp-login-tab">
                        <div>
                            <label for="login-mobile" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Mobile Number</label>
                            <div class="flex h-14 overflow-hidden rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] transition focus-within:border-[#1a6b9c] focus-within:ring-4 focus-within:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936]">
                                <div class="flex shrink-0 items-center gap-2 border-r border-[#cfe1ee] px-4 text-sm font-bold text-[#0b2a40] sm:px-5 dark:border-[#365165] dark:text-white">
                                    <span aria-hidden="true">🇬🇪</span>
                                    <span>+995</span>
                                </div>
                                <input id="login-mobile" name="mobile" type="tel" inputmode="tel" autocomplete="tel" placeholder="Enter mobile number" class="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-[#0b2a40] outline-none placeholder:text-[#88a5b9] dark:text-white dark:placeholder:text-[#718fa4]" />
                            </div>
                        </div>

                        <div class="mt-5 flex items-start gap-3 rounded-2xl border border-[#cfe1ee] bg-[#edf6fc] p-4 text-sm leading-6 text-[#496b83] dark:border-[#365165] dark:bg-[#162936] dark:text-[#a9bfd0]">
                            <i class="fas fa-circle-info mt-1 text-[#1a6b9c] dark:text-[#79bde8]" aria-hidden="true"></i>
                            <p>A verification code will be sent to your registered mobile number or email address.</p>
                        </div>

                        <button type="button" class="mt-7 inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] px-6 text-sm font-bold text-white shadow-[0_12px_28px_rgba(26,107,156,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(26,107,156,0.32)]">
                            Send Login Code
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-7 grid gap-3 text-center text-xs font-semibold text-[#496b83] sm:grid-cols-3 dark:text-[#a9bfd0]">
                    <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-bolt mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>Fast Access</div>
                    <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-mobile-screen mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>OTP Ready</div>
                    <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-shield-halved mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>Secure Session</div>
                </div>

                <p class="mt-7 text-center text-sm text-[#5a7d99] dark:text-[#a9bfd0]">Don't have an account? <a href="{{ route('register') }}" class="font-bold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Create one now</a></p>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        (() => {
            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const selectedMethod = tab.dataset.loginTab;

                    tabs.forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
                    panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.loginPanel !== selectedMethod));
                });
            });

            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.passwordToggle);
                    const shouldShow = input.type === 'password';

                    input.type = shouldShow ? 'text' : 'password';
                    button.textContent = shouldShow ? 'Hide' : 'Show';
                });
            });
        })();
    </script>
@endpush
