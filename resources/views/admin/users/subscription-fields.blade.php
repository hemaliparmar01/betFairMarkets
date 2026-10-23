                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="betguru-label">Subscription Plan</label>
                            <select name="subscription_id" class="betguru-select">
                                <option value="" {{ $selectedPlanId ? '' : 'selected' }}>No plan / access only</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ (string) $selectedPlanId === (string) $plan->id ? 'selected' : '' }}>
                                        {{ $plan->label }} • {{ $plan->duration_days }} days
                                    </option>
                                @endforeach
                            </select>
                            @error('subscription_id')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Subscription Status</label>
                            <select name="subscription_status" class="betguru-select">
                                <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="expired" {{ $selectedStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                            @error('subscription_status')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Subscription Start Date</label>
                            <input type="datetime-local" name="subscription_starts_at" value="{{ old('subscription_starts_at', isset($customer) ? $customer->subscription_starts_at?->format('Y-m-d\TH:i') : '') }}" class="betguru-input">
                            @error('subscription_starts_at')<p class="text-sm theme-text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="betguru-label">Admin Free Days / Lifetime</label>
                            <input type="number" min="-1" max="3650" name="admin_free_days" value="{{ old('admin_free_days', 0) }}" class="betguru-input">
                            <p class="mt-1 text-xs theme-text-muted">-1 = Lifetime Access; 0 = no additional days; positive = free days. Use either free days or extension days.</p>
                            @error('admin_free_days')<p class="text-sm theme-text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="betguru-label">Subscription End Date</label>
                            <input type="datetime-local" name="subscription_ends_at" value="{{ $selectedEndsAt }}" class="betguru-input">
                            @error('subscription_ends_at')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="betguru-label">Add Active Days (Free)</label>
                            <input type="number" min="0" name="add_active_days" value="{{ old('add_active_days', 0) }}" class="betguru-input">
                            <p class="mt-1 text-xs theme-text-muted">
                                Extensions start at the current expiry, or now if expired. When free or extension days are entered, the end date is calculated automatically.
                            </p>
                            @error('add_active_days')
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="rounded-2xl px-4 py-4 theme-primary-panel">
                        <p class="text-sm leading-6 theme-text-soft">
                            Manual changes create or update an admin-managed access record so the paid gateway history stays untouched.
                        </p>
                    </div>

