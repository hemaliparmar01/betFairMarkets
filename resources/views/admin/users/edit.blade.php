@extends('admin.layouts.main', ['active' => 'users'])

@section('title', 'Manage User | Admin')

@section('content')
    @php
        $trialEndsAtValue = old('trial_ends_at', filled($customer->trial_ends_at) ? \Illuminate\Support\Carbon::parse($customer->trial_ends_at)->format('Y-m-d\TH:i') : '');
        $selectedPlanId = old('subscription_id', $currentSubscription?->subscription_id);
        $selectedStatus = old('subscription_status', $currentSubscription?->status ?? 'active');
        $selectedEndsAt = old('subscription_ends_at', $customer->subscription_ends_at ? $customer->subscription_ends_at->format('Y-m-d\TH:i') : '');
        $lastLoginAt = filled($customer->last_login_at) ? \Illuminate\Support\Carbon::parse($customer->last_login_at) : null;
        $currentGatewayLabel = match (strtolower((string) ($currentSubscription?->gateway ?? ''))) {
            'paypal' => 'PayPal',
            'bog' => 'Bank of Georgia',
            'tbc' => 'TBC Bank',
            'admin' => 'Admin Manual',
            default => '—',
        };
    @endphp

    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 theme-bg-base">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                        User Management
                    </p>
                    <h1 class="mt-2 text-3xl font-bold theme-text-main">
                        Manage {{ $customer->name }} {{ $customer->surname }}
                    </h1>
                    <p class="mt-2 text-sm theme-text-muted">
                        Update account access, review login history, and control subscription visibility from one screen.
                    </p>
                </div>

                <a href="{{ route('admin.users') }}"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold theme-panel-subtle">
                    ← Back
                </a>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-4">
                <div class="rounded-3xl p-5 xl:col-span-2 betguru-card-elevated">
                    <div class="flex items-center gap-4">
                        @if ($customer->avatar_path)
                            <img src="{{ asset('storage/' . $customer->avatar_path) }}"
                                alt="{{ $customer->username }}"
                                class="h-20 w-20 rounded-full object-cover">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-full text-2xl font-bold text-white betguru-gradient-badge">
                                {{ strtoupper(substr($customer->name ?? 'U', 0, 1) . substr($customer->surname ?? '', 0, 1)) ?: strtoupper(substr($customer->username ?? 'U', 0, 2)) }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-2xl font-bold theme-text-main">
                                    {{ $customer->name }} {{ $customer->surname }}
                                </p>

                                @if ((int) ($customer->is_online ?? 0) === 1)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-success">
                                        Online
                                    </span>
                                @endif

                                @if ((int) $customer->is_verified === 1)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-success">
                                        Verified
                                    </span>
                                @else
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-accent">
                                        Unverified
                                    </span>
                                @endif

                                @if ($customer->is_blocked)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-danger-strong">
                                        Blocked
                                    </span>
                                @endif

                                @if ($customer->is_suspended)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-accent">
                                        Suspended
                                    </span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm theme-text-muted">
                                #{{ $customer->id }} • {{ $customer->username }} • {{ $customer->email }}
                            </p>
                            <p class="mt-1 text-sm theme-text-muted">
                                {{ $customer->country ?: 'Country not added' }} • {{ $customer->mobile ?: 'Mobile not added' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] theme-text-muted">Current Subscription</p>
                    <p class="mt-3 text-lg font-bold theme-text-main">
                        {{ $currentSubscription?->subscriptionPlan?->label ?? 'No Active Plan' }}
                    </p>
                    <p class="mt-2 text-sm theme-text-muted">
                        {{ ucfirst(str_replace('_', ' ', (string) ($currentSubscription?->status ?? 'inactive'))) }}
                    </p>
                    <p class="mt-1 text-sm theme-text-muted">
                        {{ $customer->hasLifetimeAccess() ? 'Lifetime Access' : ($customer->subscription_ends_at?->format('d M Y h:i A') ?? 'No end date') }}
                    </p>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] theme-text-muted">Login Activity</p>
                    <p class="mt-3 text-lg font-bold theme-text-main">
                        {{ (int) $customer->login_count }} logins
                    </p>
                    <p class="mt-2 text-sm theme-text-muted">
                        Last login: {{ $lastLoginAt ? $lastLoginAt->format('d M Y h:i A') : '—' }}
                    </p>
                    <p class="mt-1 text-sm theme-text-muted">
                        Gateway: {{ $currentGatewayLabel }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1fr]">
                <form method="POST" action="{{ route('admin.users.update', $customer) }}"
                    class="rounded-[2rem] p-6 space-y-6 betguru-card-elevated">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="profile">

                    <div>
                        <h2 class="text-xl font-bold theme-text-main">Account Access</h2>
                        <p class="mt-2 text-sm theme-text-muted">
                            Update customer identity details, block status, temporary suspension, and trial end date.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="betguru-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="betguru-input">
                            @error('name')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Surname</label>
                            <input type="text" name="surname" value="{{ old('surname', $customer->surname) }}" class="betguru-input">
                            @error('surname')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Username</label>
                            <input type="text" name="username" value="{{ old('username', $customer->username) }}" class="betguru-input">
                            @error('username')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="betguru-input">
                            @error('email')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}" class="betguru-input">
                            @error('mobile')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Country</label>
                            <input type="text" name="country" value="{{ old('country', $customer->country) }}" class="betguru-input">
                            @error('country')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Verification Status</label>
                            <div class="rounded-2xl border px-4 py-3 text-sm font-semibold {{ (int) $customer->is_verified === 1 ? 'theme-chip-success' : 'theme-chip-accent' }}">
                                {{ (int) $customer->is_verified === 1 ? 'Verified' : 'Unverified' }}
                            </div>
                        </div>

                        <div>
                            <label class="betguru-label">Verification Via</label>
                            <div class="rounded-2xl border px-4 py-3 text-sm font-semibold theme-panel-subtle">
                                {{ $customer->verification_via ? ucfirst((string) $customer->verification_via) : '—' }}
                            </div>
                        </div>

                        <div>
                            <label class="betguru-label">Blocked</label>
                            <select name="is_blocked" class="betguru-select">
                                <option value="0" {{ old('is_blocked', (int) $customer->is_blocked) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_blocked', (int) $customer->is_blocked) == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                            @error('is_blocked')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Temporary Suspension</label>
                            <select name="is_suspended" class="betguru-select">
                                <option value="0" {{ old('is_suspended', (int) $customer->is_suspended) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_suspended', (int) $customer->is_suspended) == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                            @error('is_suspended')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="betguru-label">Trial End Date</label>
                            <input type="datetime-local" name="trial_ends_at" value="{{ $trialEndsAtValue }}" class="betguru-input">
                            <p class="mt-1 text-xs theme-text-muted">
                                Leave empty if the user should not have a trial end date.
                            </p>
                            @error('trial_ends_at')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit"
                            class="rounded-2xl px-6 py-3 text-sm font-semibold text-white betguru-gradient-cta">
                            Update Account
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.users.update', $customer) }}"
                    class="rounded-[2rem] p-6 space-y-6 betguru-card-elevated">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="subscription">

                    <div>
                        <h2 class="text-xl font-bold theme-text-main">Manual Subscription Control</h2>
                        <p class="mt-2 font-semibold theme-text-main">{{ $customer->hasLifetimeAccess() ? 'Lifetime Access' : ($customer->hasActiveSubscription() ? 'Active' : 'Expired') }} · Admin free days: {{ (int) $customer->admin_free_days }} · Source: {{ $customer->hasLifetimeAccess() || $customer->admin_free_days > 0 || $currentSubscription?->gateway === 'admin' ? 'Admin Granted' : 'Paid' }}</p>
                        <p class="mt-2 text-sm theme-text-muted">
                            Activate Subscription, Grant Free Days, Extend Subscription, or Grant Lifetime Access. Disable paid AutoPay first; no provider billing is changed here.
                        </p>
                    </div>

                    @include('admin.users.subscription-fields')

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit"
                            class="rounded-2xl px-6 py-3 text-sm font-semibold text-white betguru-gradient-cta">
                            Update Subscription
                        </button>
                    </div>
                </form>
            </div>

            <section class="mt-6 overflow-x-auto rounded-[2rem] p-6 betguru-card-elevated">
            <h2 class="mb-4 text-xl font-bold theme-text-main">Subscription History</h2>
            <p class="mb-4 text-sm theme-text-muted">Current access starts: {{ $customer->subscription_starts_at?->format('d M Y H:i') ?? '—' }} · Ends: {{ $customer->hasLifetimeAccess() ? 'Lifetime Access' : ($customer->subscription_ends_at?->format('d M Y H:i') ?? '—') }} · Plan: {{ $customer->subscription_plan ?? '—' }}</p>
            <table class="w-full text-left text-sm theme-text-main">
                <thead><tr><th class="p-3">Plan</th><th class="p-3">Status</th><th class="p-3">Amount</th><th class="p-3">Gateway</th><th class="p-3">Next billing</th></tr></thead>
                <tbody>
                    @forelse ($customer->subscriptions as $record)
                        <tr class="border-t theme-border">
                            <td class="p-3">{{ $record->subscriptionPlan?->label ?? '—' }}</td>
                            <td class="p-3">{{ ucfirst($record->status) }}</td>
                            <td class="p-3">{{ $record->amount }} {{ $record->currency }}</td>
                            <td class="p-3">{{ $record->gateway }}</td>
                            <td class="p-3">{{ $record->next_billing_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-3 theme-text-muted">No subscription history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="mt-6 overflow-hidden rounded-[2rem] betguru-card-elevated">
                <div class="border-b px-6 py-5 theme-border">
                    <h2 class="text-xl font-bold theme-text-main">Login History</h2>
                    <p class="mt-1 text-sm theme-text-muted">
                        Review when the customer logged in, the IP address, device type, and session duration.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="theme-bg-surface-soft">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Logged In</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">IP Address</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Device</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Duration</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Logged Out</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">User Agent</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($loginHistory as $history)
                                @php
                                    $loggedInAt = filled($history->logged_in_at) ? \Illuminate\Support\Carbon::parse($history->logged_in_at) : null;
                                    $loggedOutAt = filled($history->logged_out_at) ? \Illuminate\Support\Carbon::parse($history->logged_out_at) : null;
                                    $duration = filled($history->duration_seconds)
                                        ? \Carbon\CarbonInterval::seconds((int) $history->duration_seconds)->cascade()->forHumans()
                                        : ($loggedOutAt ? '—' : 'Active');
                                @endphp

                                <tr>
                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $loggedInAt ? $loggedInAt->format('d M Y h:i A') : '—' }}
                                    </td>
                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $history->ip_address ?: '—' }}
                                    </td>
                                    <td class="px-5 py-5">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-primary">
                                            {{ ucfirst((string) ($history->device_type ?: 'unknown')) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $duration }}
                                    </td>
                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $loggedOutAt ? $loggedOutAt->format('d M Y h:i A') : 'Active' }}
                                    </td>
                                    <td class="px-5 py-5 text-sm theme-text-muted">
                                        <div class="max-w-[340px] break-words">
                                            {{ $history->user_agent ?: '—' }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm theme-text-muted">
                                        No login history found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t px-5 py-4 theme-border">
                    {{ $loginHistory->links() }}
                </div>
            </section>
        </div>
    </div>
@endsection
