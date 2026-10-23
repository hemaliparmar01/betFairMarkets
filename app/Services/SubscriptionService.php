<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerSubscriptions;
use App\Models\SubscriptionPayment;
use App\Models\Subscriptions;
use App\Services\Payments\BogPaymentService;
use App\Services\Payments\FittService;
use App\Services\Payments\PaypalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubscriptionService
{
    /** @return array<string, array{label: string, recurring: bool}> */
    public function gateways(): array
    {
        $result = [];
        if (filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret')) && filled(config('services.paypal.webhook_id'))) {
            $result['paypal'] = ['label' => 'PayPal (EUR)', 'recurring' => true];
        }
        if (filled(config('services.bog.client_id')) && filled(config('services.bog.secret')) && filled(config('services.bog.public_key'))) {
            $result['bog'] = ['label' => 'Bank of Georgia (GEL)', 'recurring' => (bool) config('services.bog.recurring_enabled')];
        }
        if (filled(config('services.flitt.merchant_id')) && filled(config('services.flitt.key'))) {
            $result['tbc_flitt'] = ['label' => 'TBC / Flitt ('.config('services.flitt.currency').')', 'recurring' => (bool) config('services.flitt.recurring_enabled')];
        }

        return $result;
    }

    public function url(string $route, array $parameters = []): string
    {
        return rtrim(config('app.url'), '/').route($route, $parameters, false);
    }

    public function checkout(Customer $customer, Subscriptions $plan, string $gateway, bool $autoRenew, string $key): CustomerSubscriptions
    {
        $available = $this->gateways();
        if (! $plan->is_active || ! isset($available[$gateway]) || ($autoRenew && ! $available[$gateway]['recurring'])) {
            throw ValidationException::withMessages(['gateway' => 'This plan or payment option is unavailable.']);
        }
        if ($gateway === 'paypal' && (! $autoRenew || blank($plan->paypal_plan_id))) {
            throw ValidationException::withMessages(['gateway' => 'PayPal requires an available recurring billing plan and AutoPay consent.']);
        }

        return Cache::lock('subscription-customer:'.$customer->id, 120)->block(5, function () use ($customer, $plan, $gateway, $autoRenew, $key): CustomerSubscriptions {
            $record = DB::transaction(function () use ($customer, $plan, $gateway, $autoRenew, $key): CustomerSubscriptions {
                $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
                if ($lockedCustomer->hasLifetimeAccess() || (($lockedCustomer->subscription_ends_at?->isFuture() ?? false) && ($lockedCustomer->admin_free_days > 0 || $lockedCustomer->subscriptions()->where('gateway', 'admin')->where('status', 'active')->exists()))) {
                    throw ValidationException::withMessages(['subscription' => 'Admin-granted access is already available. A paid checkout is not required.']);
                }
                if ($lockedCustomer->hasActiveSubscription() && CustomerSubscriptions::query()->where('customer_id', $customer->id)
                    ->where('gateway', '!=', 'admin')->where('status', 'cancelled')->exists()) {
                    throw ValidationException::withMessages(['subscription' => 'Your paid subscription remains available until its expiry. Start another subscription after that period ends.']);
                }
                $existing = CustomerSubscriptions::query()->where('customer_id', $customer->id)
                    ->where('gateway', '!=', 'admin')->whereIn('status', ['pending', 'active', 'past_due', 'cancellation_pending'])->latest('id')->first();
                if ($existing !== null) {
                    if ($existing->status === 'pending' && $existing->subscription_id === $plan->id && $existing->gateway === $gateway) {
                        return $existing;
                    }
                    throw ValidationException::withMessages(['subscription' => 'An existing subscription or checkout must be resolved before starting another.']);
                }
                if (CustomerSubscriptions::query()->where('checkout_key', $key)->exists()) {
                    throw ValidationException::withMessages(['subscription' => 'This checkout request has already been used.']);
                }
                $currency = $gateway === 'bog' ? 'GEL' : ($gateway === 'paypal' ? 'EUR' : config('services.flitt.currency'));
                if (! in_array($currency, ['EUR', 'GEL'], true)) {
                    throw ValidationException::withMessages(['gateway' => 'No subscription price is configured for this currency.']);
                }
                $amount = $currency === 'GEL' ? $plan->price_for_local : $plan->price_for_international;
                if ((float) $amount <= 0) {
                    throw ValidationException::withMessages(['subscription' => 'This plan has no valid price.']);
                }

                return CustomerSubscriptions::query()->create([
                    'customer_id' => $customer->id, 'subscription_id' => $plan->id,
                    'gateway' => $gateway, 'amount' => $amount, 'currency' => $currency,
                    'status' => 'pending', 'checkout_key' => $key, 'active_customer_id' => $customer->id,
                    'auto_renew' => $autoRenew, 'duration_days' => $plan->duration_days,
                    'provider_plan_id' => $gateway === 'paypal' ? $plan->paypal_plan_id : null,
                ]);
            });
            if ($record->checkout_url !== null) {
                return $record;
            }
            if ($record->gateway === 'paypal') {
                $paypal = app(PaypalService::class);
                $remotePlan = $paypal->client()->get('/v1/billing/plans/'.rawurlencode($plan->paypal_plan_id))->throw()->json();
                $cycle = collect($remotePlan['billing_cycles'] ?? [])->firstWhere('tenure_type', 'REGULAR');
                $frequency = data_get($cycle, 'frequency');
                $days = match (data_get($frequency, 'interval_unit')) {
                    'DAY' => 1, 'WEEK' => 7, 'MONTH' => 30, 'YEAR' => 365, default => 0
                } * (int) data_get($frequency, 'interval_count', 0);
                if (($remotePlan['status'] ?? '') !== 'ACTIVE' || $days !== $record->duration_days
                    || (int) round((float) data_get($cycle, 'pricing_scheme.fixed_price.value') * 100) !== (int) round((float) $record->amount * 100)
                    || data_get($cycle, 'pricing_scheme.fixed_price.currency_code') !== $record->currency) {
                    throw ValidationException::withMessages(['gateway' => 'The PayPal billing plan does not match the selected price or period.']);
                }
                $record->update(['provider_request_started_at' => now()]);
                $response = $paypal->create([
                    'plan_id' => $plan->paypal_plan_id, 'custom_id' => $record->checkout_key,
                    'application_context' => ['user_action' => 'SUBSCRIBE_NOW',
                        'return_url' => $this->url('subscriptions.return'), 'cancel_url' => $this->url('subscriptions.return')],
                ], $record->checkout_key);
                $reference = $response['id'] ?? null;
                $url = collect($response['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;
            } elseif ($record->gateway === 'tbc_flitt') {
                $payload = [
                    'order_id' => $record->checkout_key, 'order_desc' => $plan->label,
                    'amount' => (int) round((float) $record->amount * 100), 'currency' => $record->currency,
                    'sender_email' => $customer->email, 'delayed' => 'N',
                    'response_url' => $this->url('subscriptions.return'), 'server_callback_url' => $this->url('subscriptions.webhook', ['gateway' => 'tbc_flitt']),
                    'merchant_data' => ['checkout_key' => $record->checkout_key],
                ];
                if ($record->auto_renew) {
                    $payload['subscription'] = 'Y';
                    $payload['recurring_data'] = ['every' => $record->duration_days, 'period' => 'day',
                        'amount' => $payload['amount'], 'state' => 'shown_readonly', 'readonly' => 'Y', 'quantity' => max(1, min(120, (int) config('services.flitt.cycles', 120)))];
                }
                $record->update(['provider_request_started_at' => now(), 'gateway_subscription_id' => $record->checkout_key]);
                $response = app(FittService::class)->createCheckout($payload);
                $reference = $record->checkout_key;
                $url = $response['checkout_url'] ?? null;
            } else {
                $bog = app(BogPaymentService::class);
                $record->update(['provider_request_started_at' => now()]);
                $response = $bog->createOrder([
                    'external_order_id' => $record->checkout_key, 'capture' => 'automatic',
                    'callback_url' => $this->url('subscriptions.webhook', ['gateway' => 'bog']),
                    'purchase_units' => ['currency' => $record->currency, 'total_amount' => (float) $record->amount,
                        'basket' => [['quantity' => 1, 'unit_price' => (float) $record->amount, 'product_id' => (string) $plan->id]]],
                    'redirect_urls' => ['success' => $this->url('subscriptions.return'), 'fail' => $this->url('subscriptions.return')],
                ]);
                $reference = $response['id'] ?? null;
                $url = data_get($response, '_links.redirect.href');
                if ($record->auto_renew && is_string($reference)) {
                    $bog->enableAutomaticPayments($reference);
                }
            }
            if (! is_string($reference) || $reference === '' || ! is_string($url) || ! str_starts_with($url, 'https://')) {
                throw new RuntimeException('The provider did not return a valid checkout. The pending attempt is retained for safe reconciliation.');
            }
            $record->update(['gateway_subscription_id' => $reference, 'checkout_url' => $url]);

            return $record->refresh();
        });
    }

    public function paid(CustomerSubscriptions $subscription, string $eventId, string $paymentId, string $amount, string $currency, Carbon $paidAt, ?Carbon $periodEnd = null): void
    {
        if ($paymentId === '' || (int) round((float) $amount * 100) !== (int) round((float) $subscription->amount * 100) || $currency !== $subscription->currency) {
            throw ValidationException::withMessages(['payment' => 'The confirmed payment does not match the subscription.']);
        }
        DB::transaction(function () use ($subscription, $eventId, $paymentId, $amount, $currency, $paidAt, $periodEnd): void {
            $customer = Customer::query()->whereKey($subscription->customer_id)->lockForUpdate()->firstOrFail();
            $record = CustomerSubscriptions::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (SubscriptionPayment::query()->where('event_id', $eventId)->orWhere(function ($query) use ($record, $paymentId): void {
                $query->where('gateway', $record->gateway)->where('provider_payment_id', $paymentId);
            })->exists()) {
                return;
            }
            $end = $periodEnd ?? $paidAt->copy()->addDays($record->duration_days);
            if ($end->lte($paidAt)) {
                throw ValidationException::withMessages(['payment' => 'Invalid billing period.']);
            }
            SubscriptionPayment::query()->create([
                'customer_subscription_id' => $record->id, 'gateway' => $record->gateway, 'event_id' => $eventId,
                'provider_payment_id' => $paymentId, 'kind' => 'payment', 'status' => 'paid', 'amount' => $amount, 'currency' => $currency,
                'payload' => ['paid_at' => $paidAt->toIso8601String(), 'paid_until' => $end->toIso8601String()],
            ]);
            if ($record->last_payment_at !== null && $paidAt->lte($record->last_payment_at)) {
                return;
            }
            if (! $customer->hasLifetimeAccess() && ! (($customer->admin_free_days > 0 || $customer->subscriptions()->where('gateway', 'admin')->where('status', 'active')->exists()) && $customer->subscription_ends_at?->gte($end))) {
                $customer->forceFill(['subscription_starts_at' => $paidAt, 'subscription_ends_at' => $end, 'subscription_plan' => $record->subscriptionPlan->label])->save();
            }
            $cancelled = $record->cancelled_at !== null || $record->status === 'cancellation_pending';
            $record->update([
                'status' => $cancelled ? ($record->status === 'cancellation_pending' ? 'cancellation_pending' : 'cancelled') : 'active',
                'last_payment_at' => $paidAt, 'next_billing_at' => $record->auto_renew && ! $cancelled ? $end : null,
                'billing_failures' => 0,
            ]);
        });
    }

    public function failed(CustomerSubscriptions $subscription, string $eventId): void
    {
        DB::transaction(function () use ($subscription, $eventId): void {
            $record = CustomerSubscriptions::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (SubscriptionPayment::query()->where('event_id', $eventId)->exists()) {
                return;
            }
            SubscriptionPayment::query()->create(['customer_subscription_id' => $record->id, 'gateway' => $record->gateway,
                'event_id' => $eventId, 'kind' => 'failure', 'status' => 'failed']);
            if ($record->last_payment_at === null && in_array($record->gateway, ['bog', 'tbc_flitt'], true)) {
                $record->update(['status' => 'failed', 'auto_renew' => false, 'active_customer_id' => null, 'next_billing_at' => null]);
            } elseif (! in_array($record->status, ['cancelled', 'expired', 'cancellation_pending'], true)) {
                $record->update(['status' => 'past_due', 'billing_failures' => $record->billing_failures + 1,
                    'next_billing_at' => $record->gateway === 'bog' ? now()->addDay() : $record->next_billing_at]);
            }
        });
    }

    public function cancel(Customer $customer, CustomerSubscriptions $subscription): bool
    {
        abort_unless($subscription->customer_id === $customer->id, 403);

        return Cache::lock('subscription-customer:'.$customer->id, 120)->block(5, function () use ($subscription): bool {
            $record = $subscription->refresh();
            if ($record->status === 'cancellation_pending') {
                return false;
            }
            if (! $record->auto_renew && $record->status !== 'cancellation_pending') {
                return true;
            }
            if ($record->status === 'pending' && $record->provider_request_started_at === null && $record->gateway_subscription_id === null) {
                $record->update(['status' => 'cancelled', 'auto_renew' => false, 'active_customer_id' => null, 'cancelled_at' => now()]);

                return true;
            }
            if (blank($record->gateway_subscription_id)) {
                throw ValidationException::withMessages(['subscription' => 'The provider reference is missing; cancellation cannot be confirmed.']);
            }
            if ($record->gateway === 'paypal') {
                app(PaypalService::class)->cancel($record->gateway_subscription_id, $record->checkout_key.'-cancel');
            } elseif ($record->gateway === 'tbc_flitt') {
                app(FittService::class)->stopSubscription($record->gateway_subscription_id);
            } elseif ($record->gateway === 'bog') {
                app(BogPaymentService::class)->deleteSavedCard($record->gateway_subscription_id, $record->checkout_key);
                $record->update(['status' => 'cancellation_pending', 'auto_renew' => false, 'next_billing_at' => null]);

                return false;
            } else {
                throw ValidationException::withMessages(['subscription' => 'This gateway does not support provider cancellation.']);
            }
            $record->update(['status' => 'cancelled', 'auto_renew' => false, 'next_billing_at' => null, 'cancelled_at' => now()]);

            return true;
        });
    }
}
