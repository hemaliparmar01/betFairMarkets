@extends('admin.layouts.main', ['active' => 'users'])
@section('title', 'Add Customer | Admin')
@section('content')
    <div class="rounded-[2rem] p-6 betguru-card-elevated">
        <h1 class="mb-6 text-2xl font-bold theme-text-main">Add Customer</h1>
        @if ($errors->any())<div class="mb-4 theme-text-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                @foreach (['name' => 'Name', 'surname' => 'Surname', 'username' => 'Username', 'email' => 'Email', 'mobile' => 'Mobile Number'] as $field => $label)
                    <div><label for="{{ $field }}" class="betguru-label">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field) }}" required class="betguru-input">
                    @error($field)<p class="text-sm theme-text-danger">{{ $message }}</p>@enderror</div>
                @endforeach
                <div><label class="betguru-label" for="country_code">Country</label>
                    <select id="country_code" name="country_code" required class="betguru-select">
                        @foreach ($countries as $country)<option value="{{ $country['code'] }}" @selected(old('country_code', 'GE') === $country['code'])>{{ $country['name'] }} (+{{ $country['calling_code'] }})</option>@endforeach
                    </select>
                </div>
                @foreach (['password' => 'Password', 'password_confirmation' => 'Confirm Password'] as $field => $label)
                    <div><label for="{{ $field }}" class="betguru-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" required class="betguru-input"></div>
                @endforeach
            </div>
            <h2 class="text-xl font-bold theme-text-main">Optional Admin-Granted Access</h2>
            <p class="theme-text-muted">Leave the plan and free days empty to create a customer without a subscription. No payment or AutoPay is created. Accounts are verified by the administrator.</p>
            @include('admin.users.subscription-fields', ['selectedPlanId' => old('subscription_id'), 'selectedStatus' => old('subscription_status', 'active'), 'selectedEndsAt' => old('subscription_ends_at', '')])
            <button type="submit" class="rounded-2xl px-6 py-3 font-semibold text-white betguru-gradient-cta">Create Customer</button>
        </form>
    </div>
@endsection
