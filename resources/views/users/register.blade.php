@extends('users.layout.main')

@section('title', ($isVerification ?? false) ? 'Verify Email · BF Markets' : 'Register · BF Markets')

@section('content')
    @if ($isVerification ?? false)
        <section class="mx-auto my-10 max-w-lg rounded-3xl border border-[#d7e7f3] bg-white p-6 shadow-sm sm:p-8 dark:border-[#365165] dark:bg-[#1d3040]">
            <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-[#edf6fc] text-xl text-[#1a6b9c] dark:bg-[#162936]"><i class="fas fa-envelope" aria-hidden="true"></i></span>
            <h1 class="mt-5 text-2xl font-extrabold text-[#0b2a40] dark:text-white">Verify your email</h1>
            <p class="mt-3 text-sm leading-6 text-[#5a7d99] dark:text-[#a9bfd0]">Enter the 6-digit code sent to <strong class="break-all">{{ $customer->email }}</strong>. The code expires in 10 minutes.</p>
            @if (session('verification_delivery_error'))
                <p role="alert" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">{{ session('verification_delivery_error') }}</p>
            @endif
            @if (session('status'))
                <p role="status" class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
            @endif
            <form action="{{ route('customers.verification.verify') }}" method="post" class="mt-6">
                @csrf
                <label for="verification-code" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-white">Verification code</label>
                <input id="verification-code" name="code" required type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" aria-describedby="verification-code-error" @if ($errors->has('code')) aria-invalid="true" @endif class="h-12 w-full rounded-xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 text-sm text-[#0b2a40] outline-none focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white text-center text-xl tracking-[0.35em]" placeholder="000000" />
                @error('code') <p id="verification-code-error" role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                <button type="submit" class="mt-5 h-12 w-full rounded-xl bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] font-bold text-white">Verify Email</button>
            </form>
            <form action="{{ route('customers.verification.resend') }}" method="post" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Resend Code</button>
                <p class="mt-2 text-xs text-[#5a7d99] dark:text-[#a9bfd0]">Wait 60 seconds before requesting another code.</p>
            </form>
            <a href="{{ route('login') }}" class="mt-6 block text-center text-sm text-[#5a7d99] hover:underline dark:text-[#a9bfd0]">Back to login</a>
        </section>
    @else

    <section class="relative isolate my-6 overflow-hidden rounded-[28px] border border-[#dce9f4] bg-gradient-to-br from-[#f5faff] via-white to-[#edf6fc] px-3 py-6 sm:px-6 sm:py-10 lg:px-10 dark:border-[#2a3f50] dark:from-[#142634] dark:via-[#1a2a38] dark:to-[#173247]">
        <div class="pointer-events-none absolute -right-20 -top-20 -z-10 size-72 rounded-full bg-[#79bde8]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 -z-10 size-72 rounded-full bg-[#1a6b9c]/10 blur-3xl"></div>

        <div class="mx-auto max-w-6xl rounded-[24px] border border-[#d7e7f3] bg-white/95 p-4 shadow-[0_20px_60px_rgba(11,42,64,0.10)] sm:p-7 lg:p-10 dark:border-[#314b5d] dark:bg-[#1d3040]/95">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#1a6b9c] dark:text-[#79bde8]">Customer Register</span>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-[#0b2a40] sm:text-4xl dark:text-white">Start your BF Markets access</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-[#5a7d99] sm:text-base dark:text-[#a9bfd0]">Access real-time sports market activity, Pinnacle odds drop signals, advanced filters, and powerful tools for monitoring sports exchange markets.</p>
            </div>

            <div class="mt-7 flex items-start gap-4 rounded-2xl border border-[#bfdaed] bg-gradient-to-r from-[#edf6fc] to-[#f7fbfe] p-4 sm:p-5 dark:border-[#36576f] dark:from-[#162d3d] dark:to-[#1f3444]">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-[#1a6b9c] shadow-sm dark:bg-[#29465b] dark:text-[#8ac7eb]" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></span>
                <div>
                    <h2 class="font-bold text-[#0b2a40] dark:text-white">7-Day Free Trial</h2>
                    <p class="mt-1 text-sm leading-6 text-[#5a7d99] dark:text-[#a9bfd0]">No payment required. No card required. Full access for 7 days.</p>
                </div>
            </div>

            <form class="mt-7" action="{{ route('register.store') }}" method="post" data-customer-auth>
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="register-name" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Name</label>
                        <input id="register-name" name="name" value="{{ old('name') }}" required type="text" autocomplete="given-name" placeholder="Enter first name" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                        @error('name') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="register-surname" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Surname</label>
                        <input id="register-surname" name="surname" value="{{ old('surname') }}" required type="text" autocomplete="family-name" placeholder="Enter last name" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                        @error('surname') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="register-username" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Username</label>
                        <input id="register-username" name="username" value="{{ old('username') }}" required type="text" autocomplete="username" placeholder="Choose a username" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                        @error('username') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="register-email" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Email Address</label>
                        <input id="register-email" name="email" value="{{ old('email') }}" required type="email" autocomplete="email" placeholder="Enter email address" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                        @error('email') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="register-password" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Password</label>
                        <div class="relative">
                            <input id="register-password" name="password" required minlength="8" type="password" autocomplete="new-password" aria-describedby="password-hint password-strength-label" placeholder="Create password" class="h-14 w-full rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] px-4 pr-20 text-sm text-[#0b2a40] outline-none transition placeholder:text-[#88a5b9] focus:border-[#1a6b9c] focus:ring-4 focus:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936] dark:text-white dark:placeholder:text-[#718fa4]" />
                            <button type="button" data-password-toggle="register-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[#1a6b9c] hover:text-[#0b2a40] dark:text-[#79bde8] dark:hover:text-white">Show</button>
                        </div>
                        @error('password') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                        <p id="password-hint" class="mt-2 text-xs text-[#6e8ca2] dark:text-[#8eabbf]">Use at least 8 characters with letters, numbers, and symbols.</p>
                        <div class="mt-3 grid grid-cols-3 gap-2" aria-hidden="true">
                            <span data-password-strength-bar class="h-1.5 rounded-full bg-[#d4e8f5]"></span>
                            <span data-password-strength-bar class="h-1.5 rounded-full bg-[#d4e8f5]"></span>
                            <span data-password-strength-bar class="h-1.5 rounded-full bg-[#d4e8f5]"></span>
                        </div>
                        <p id="password-strength-label" role="status" aria-live="polite" class="mt-2 text-xs font-semibold text-[#6e8ca2] dark:text-[#8eabbf]">Enter a password to check its strength.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="register-mobile" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Mobile Number</label>
                        <div class="flex h-14 overflow-hidden rounded-2xl border border-[#cfe1ee] bg-[#f8fbfe] transition focus-within:border-[#1a6b9c] focus-within:ring-4 focus-within:ring-[#1a6b9c]/10 dark:border-[#365165] dark:bg-[#162936]">
                            <div class="relative shrink-0 border-r border-[#cfe1ee] dark:border-[#365165]">
                                <select id="register-country-code" name="country_code" class="h-full w-[190px] appearance-none border-0 bg-transparent py-0 pl-3 pr-8 text-sm font-semibold text-[#0b2a40] outline-none sm:w-[245px] sm:pl-5 dark:text-white" aria-label="Country calling code">
                                    @foreach ($countries as $country)
                                        <option value="{{ $country['code'] }}" data-country="{{ $country['name'] }}" data-timezone="{{ $country['timezone'] }}" @selected($country['code'] === old('country_code', 'IN'))>
                                            {{ $country['flag'] }} {{ $country['name'] }} (+{{ $country['calling_code'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-[#7898ad]" aria-hidden="true"></i>
                            </div>
                            <input id="register-mobile" name="mobile" value="{{ old('mobile') }}" required type="tel" inputmode="tel" autocomplete="tel" placeholder="Enter mobile number" class="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-[#0b2a40] outline-none placeholder:text-[#88a5b9] dark:text-white dark:placeholder:text-[#718fa4]" />
                        </div>
                        @error('country_code') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                        @error('mobile') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="register-country" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Country</label>
                        <input id="register-country" name="country" type="text" value="India" disabled class="h-14 w-full cursor-not-allowed rounded-2xl border border-[#cfe1ee] bg-[#edf4f8] px-4 text-sm font-semibold text-[#496b83] opacity-100 outline-none dark:border-[#365165] dark:bg-[#142633] dark:text-[#a9bfd0]" />
                    </div>
                    <div>
                        <label for="register-timezone" class="mb-2 block text-sm font-semibold text-[#0b2a40] dark:text-[#e8edf2]">Timezone</label>
                        <input id="register-timezone" name="timezone" type="text" value="Asia/Kolkata" disabled class="h-14 w-full cursor-not-allowed rounded-2xl border border-[#cfe1ee] bg-[#edf4f8] px-4 text-sm font-semibold text-[#496b83] opacity-100 outline-none dark:border-[#365165] dark:bg-[#142633] dark:text-[#a9bfd0]" />
                    </div>
                </div>

                <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-2xl border border-[#d7e7f3] bg-[#f8fbfe] p-4 text-sm leading-6 text-[#5a7d99] dark:border-[#365165] dark:bg-[#162936] dark:text-[#a9bfd0]">
                    <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-1 size-4 shrink-0 rounded border-[#aec9dc] text-[#1a6b9c] focus:ring-[#1a6b9c]" />
                    <span>I agree to the <a href="{{ route('terms-of-service') }}" class="font-bold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Terms of Service</a> and <a href="{{ route('privacy-policy') }}" class="font-bold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Privacy Policy</a>.</span>
                </label>
                @error('terms') <p role="alert" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p> @enderror

                <button type="submit" class="mt-6 inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] px-6 text-sm font-bold text-white shadow-[0_12px_28px_rgba(26,107,156,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(26,107,156,0.32)]">
                    Start Free Trial
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="mt-6 grid gap-3 text-center text-xs font-semibold text-[#496b83] sm:grid-cols-3 dark:text-[#a9bfd0]">
                <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-lock mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>Secure Registration</div>
                <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-envelope mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>Email Verification</div>
                <div class="rounded-xl border border-[#d7e7f3] px-3 py-3 dark:border-[#365165]"><i class="fas fa-language mr-1.5 text-[#1a6b9c] dark:text-[#79bde8]"></i>English / Georgian</div>
            </div>

            <p class="mt-7 text-center text-sm text-[#5a7d99] dark:text-[#a9bfd0]">Already have an account? <a href="{{ route('login') }}" class="font-bold text-[#1a6b9c] hover:underline dark:text-[#79bde8]">Login</a></p>
        </div>
    </section>
    @endif
@endsection

@unless ($isVerification ?? false)
@push('js')
    <script>
        const countryCodeSelect = document.getElementById('register-country-code');
        const countryInput = document.getElementById('register-country');
        const timezoneInput = document.getElementById('register-timezone');

        const updateCountryDetails = () => {
            const selectedCountry = countryCodeSelect.options[countryCodeSelect.selectedIndex];

            countryInput.value = selectedCountry.dataset.country;
            timezoneInput.value = selectedCountry.dataset.timezone;
        };

        countryCodeSelect.addEventListener('change', updateCountryDetails);
        updateCountryDetails();

        const passwordInput = document.getElementById('register-password');
        const strengthBars = document.querySelectorAll('[data-password-strength-bar]');
        const strengthLabel = document.getElementById('password-strength-label');
        const updatePasswordStrength = () => {
            const password = passwordInput.value;
            const letters = /\p{L}/u.test(password);
            const numbers = /\p{N}/u.test(password);
            const symbols = /[^\p{L}\p{N}\s]/u.test(password);
            const meetsMinimum = password.length >= 8 && letters && numbers && symbols;
            const strong = meetsMinimum && password.length >= 12 && /\p{Ll}/u.test(password) && /\p{Lu}/u.test(password);
            const level = password.length === 0 ? 0 : strong ? 3 : meetsMinimum ? 2 : 1;
            const colors = ['bg-[#d4e8f5]', 'bg-red-500', 'bg-amber-500', 'bg-emerald-500'];

            strengthBars.forEach((bar, index) => {
                bar.classList.remove(...colors);
                bar.classList.add(index < level ? colors[level] : colors[0]);
            });
            strengthLabel.textContent = ['Enter a password to check its strength.', 'Weak password', 'Medium password', 'Strong password'][level];
            strengthLabel.classList.remove('text-[#6e8ca2]', 'dark:text-[#8eabbf]', 'text-red-600', 'text-amber-600', 'text-emerald-600');
            strengthLabel.classList.add(['text-[#6e8ca2]', 'text-red-600', 'text-amber-600', 'text-emerald-600'][level]);
        };
        passwordInput.addEventListener('input', updatePasswordStrength);
        passwordInput.addEventListener('change', updatePasswordStrength);
        updatePasswordStrength();

        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const shouldShow = input.type === 'password';

                input.type = shouldShow ? 'text' : 'password';
                button.textContent = shouldShow ? 'Hide' : 'Show';
            });
        });
    </script>
@endpush
@endunless
