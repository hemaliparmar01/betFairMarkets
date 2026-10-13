<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerFingerprint;
use App\Models\CustomerLoginSession;
use App\Models\Favorite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerRepository
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): int
    {
        return (int) Customer::query()->create($attributes)->getKey();
    }

    public function find(int $customerId, bool $lock = false): ?Customer
    {
        $query = Customer::query()->where('id', $customerId);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $customerId, array $attributes): void
    {
        Customer::query()->where('id', $customerId)->update($attributes + ['updated_at' => now()]);
    }

    public function timezone(int $customerId): ?string
    {
        return CustomerFingerprint::query()->where('customer_id', $customerId)->latest('id')->value('timezone');
    }

    public function favorites(int $customerId): LengthAwarePaginator
    {
        return Favorite::query()->where('customer_id', $customerId)->latest('id')->paginate(20)->withQueryString();
    }

    /** @param array<string, mixed> $fingerprint */
    public function recordFingerprint(int $customerId, array $fingerprint): void
    {
        $fingerprint = array_filter($fingerprint, static fn (mixed $value): bool => $value !== null);
        CustomerFingerprint::query()->upsert([
            $fingerprint + ['customer_id' => $customerId, 'created_at' => now(), 'updated_at' => now()],
        ], ['customer_id', 'fingerprint_hash'], [...array_keys($fingerprint), 'updated_at']);
    }

    public function updateTimezone(int $customerId, string $timezone): void
    {
        $fingerprintId = CustomerFingerprint::query()->where('customer_id', $customerId)->latest('id')->value('id');
        if ($fingerprintId !== null) {
            CustomerFingerprint::query()->where('id', $fingerprintId)->update(['timezone' => $timezone, 'updated_at' => now()]);
        }
    }

    /**
     * @param  array{ip_address: ?string, user_agent: ?string, device_type: string}  $session
     * @param  array<string, mixed>  $fingerprint
     */
    public function recordLogin(int $customerId, string $sessionId, array $session, array $fingerprint, string $channel, ?string $previousSessionId = null): void
    {
        DB::transaction(function () use ($customerId, $sessionId, $session, $fingerprint, $channel, $previousSessionId): void {
            $customer = Customer::query()->where('id', $customerId)->lockForUpdate()->first();
            if ($customer === null || $customer->is_blocked || $customer->is_suspended) {
                throw ValidationException::withMessages(['identity' => 'Your account is unavailable.']);
            }

            if ($customer->active_session_id !== null && $customer->active_session_id !== $previousSessionId) {
                throw ValidationException::withMessages(['session_conflict' => 'Your account is already logged in on another device.']);
            }

            $openSessions = CustomerLoginSession::query()->where('customer_id', $customerId)->whereNull('logged_out_at')->pluck('session_id');
            foreach ($openSessions as $previousId) {
                $this->closeSession($customerId, $previousId);
            }

            $now = now();
            Customer::query()->where('id', $customerId)->increment('login_count', 1, [
                'active_session_id' => $sessionId,
                'last_login_at' => $now,
                'login_channel_via' => $channel,
                'updated_at' => $now,
            ]);

            CustomerLoginSession::query()->insert($session + [
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'logged_in_at' => $now,
            ]);

            $this->recordFingerprint($customerId, $fingerprint);
        });
    }

    public function closeSession(int $customerId, string $sessionId): void
    {
        $session = CustomerLoginSession::query()
            ->where('customer_id', $customerId)
            ->where('session_id', $sessionId)
            ->whereNull('logged_out_at')
            ->first();

        if ($session !== null) {
            CustomerLoginSession::query()->where('id', $session->id)->whereNull('logged_out_at')->update([
                'logged_out_at' => now(),
                'duration_seconds' => max(0, (int) Carbon::parse($session->logged_in_at)->diffInSeconds(now())),
            ]);
        }

        Customer::query()->where('id', $customerId)->where('active_session_id', $sessionId)->update([
            'active_session_id' => null,
            'updated_at' => now(),
        ]);
    }
}
