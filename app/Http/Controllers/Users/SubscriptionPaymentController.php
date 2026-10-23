<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\CustomerSubscriptions;
use App\Models\SubscriptionPayment;
use App\Models\Subscriptions;
use App\Services\Payments\BogPaymentService;
use App\Services\Payments\FittService;
use App\Services\Payments\PaypalService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubscriptionPaymentController extends Controller
{
    public function checkout(Request $request, Subscriptions $plan, SubscriptionService $subscriptions): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_keys($subscriptions->gateways()))],
            'auto_renew' => ['sometimes', 'boolean'], 'checkout_key' => ['required', 'uuid'],
        ]);
        try {
            $record = $subscriptions->checkout($request->user('customers'), $plan, $validated['gateway'], $request->boolean('auto_renew'), $validated['checkout_key']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Checkout could not be confirmed. Your pending attempt is retained; retry safely or contact support.']);
        }

        return redirect()->away($record->checkout_url);
    }

    public function returnedPost(): RedirectResponse
    {
        return redirect()->route('subscriptions.return', [], 303);
    }

    public function returned(): RedirectResponse
    {
        return redirect()->route('customers.profile', ['section' => 'subscription'])->with('status', 'Payment is being verified. Access updates only after provider confirmation.');
    }

    public function cancel(Request $request, CustomerSubscriptions $subscription, SubscriptionService $subscriptions): RedirectResponse
    {
        abort_unless($subscription->customer_id === $request->user('customers')->id, 403);
        try {
            $confirmed = $subscriptions->cancel($request->user('customers'), $subscription);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['subscription' => 'The provider has not confirmed cancellation. Please retry; no successful cancellation has been recorded.']);
        }

        return back()->with('status', $confirmed ? 'AutoPay cancelled. Already-paid access continues until the end of the billing period.' : 'Cancellation requested. Provider confirmation is pending; no new local renewal will be initiated.');
    }

    public function webhook(Request $request, string $gateway, SubscriptionService $subscriptions): JsonResponse
    {
        abort_unless(in_array($gateway, ['paypal', 'bog', 'tbc_flitt'], true), 404);
        if ($gateway === 'paypal') {
            $provider = app(PaypalService::class);
            abort_unless($provider->verify($request), 401);
            $event = $request->json()->all();
            $eventId = 'paypal:'.($event['id'] ?? '');
            abort_unless(! empty($event['id']), 422);
            if (SubscriptionPayment::query()->where('event_id', $eventId)->exists()) {
                return response()->json(['received' => true]);
            }
            $resource = $event['resource'] ?? [];
            $reference = $resource['billing_agreement_id'] ?? $resource['id'] ?? '';
            $details = $provider->details($reference);
            $record = CustomerSubscriptions::query()->where('gateway', 'paypal')
                ->where('checkout_key', $details['custom_id'] ?? '')->firstOrFail();
            abort_unless(($details['id'] ?? '') === $reference && ($details['custom_id'] ?? '') === $record->checkout_key
                && ($details['plan_id'] ?? '') === $record->provider_plan_id
                && ($record->gateway_subscription_id === null || $record->gateway_subscription_id === $reference), 422);
            if ($record->gateway_subscription_id === null) {
                $record->update(['gateway_subscription_id' => $reference]);
            }
            if (($event['event_type'] ?? '') === 'PAYMENT.SALE.COMPLETED') {
                abort_unless(filled($resource['create_time'] ?? null), 422);
                $paidAt = Carbon::parse($resource['create_time']);
                $latestPaymentAt = data_get($details, 'billing_info.last_payment.time');
                $periodEnd = filled($latestPaymentAt) && Carbon::parse($latestPaymentAt)->equalTo($paidAt)
                    && filled(data_get($details, 'billing_info.next_billing_time'))
                    ? Carbon::parse(data_get($details, 'billing_info.next_billing_time')) : null;
                $subscriptions->paid($record, $eventId, (string) ($resource['id'] ?? ''),
                    (string) data_get($resource, 'amount.total', ''), (string) data_get($resource, 'amount.currency', ''),
                    $paidAt, $periodEnd);
            } elseif (($event['event_type'] ?? '') === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED') {
                $subscriptions->failed($record, $eventId);
            } elseif (in_array($details['status'] ?? '', ['CANCELLED', 'EXPIRED', 'SUSPENDED'], true)) {
                $record->update(['status' => $details['status'] === 'SUSPENDED' ? 'past_due' : 'cancelled',
                    'auto_renew' => false, 'next_billing_at' => null, 'cancelled_at' => now()]);
            }
        } elseif ($gateway === 'tbc_flitt') {
            $payload = $request->input('response', $request->all());
            if (is_string($payload)) {
                $payload = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            }
            abort_unless(is_array($payload), 422);
            $provider = app(FittService::class);
            abort_unless($provider->verifySignature($payload) && (string) ($payload['merchant_id'] ?? '') === (string) config('services.flitt.merchant_id'), 401);
            $merchant = is_string($payload['merchant_data'] ?? null) ? json_decode($payload['merchant_data'], true) : ($payload['merchant_data'] ?? []);
            $key = $merchant['checkout_key'] ?? $payload['order_id'] ?? '';
            $record = CustomerSubscriptions::query()->where('gateway', 'tbc_flitt')->where('checkout_key', $key)->firstOrFail();
            $paymentId = (string) ($payload['payment_id'] ?? '');
            abort_unless($paymentId !== '' && filled($payload['order_id'] ?? null), 422);
            $eventId = 'flitt:'.$paymentId.':'.($payload['order_status'] ?? '');
            if (($payload['order_status'] ?? '') === 'approved' && ($payload['response_status'] ?? '') === 'success') {
                abort_unless(filled($payload['order_time'] ?? null), 422);
                $subscriptions->paid($record, $eventId, $paymentId, $provider->fromMinorUnits($payload['amount'] ?? ''),
                    (string) ($payload['currency'] ?? ''), Carbon::createFromFormat('d.m.Y H:i:s', $payload['order_time'], config('services.flitt.timezone', 'Asia/Tbilisi'))->utc());
            } elseif (in_array($payload['order_status'] ?? '', ['declined', 'expired'], true)) {
                $subscriptions->failed($record, $eventId);
            }
        } else {
            $provider = app(BogPaymentService::class);
            abort_unless($provider->verifyCallbackSignature($request->getContent(), $request->header('Callback-Signature')), 401);
            $orderId = (string) $request->input('body.order_id', '');
            abort_unless($orderId !== '', 422);
            $receipt = $provider->getOrderDetails($orderId);
            $key = (string) ($receipt['external_order_id'] ?? '');
            $payment = SubscriptionPayment::query()->where('idempotency_key', $key)->first();
            $record = $payment?->subscription ?? CustomerSubscriptions::query()->where('gateway', 'bog')->where('checkout_key', $key)->firstOrFail();
            abort_unless(($receipt['order_id'] ?? '') === $orderId, 422);
            $status = data_get($receipt, 'order_status.key');
            $eventId = 'bog:'.$orderId.':'.$status;
            if ($status === 'completed') {
                abort_unless(filled($receipt['zoned_create_date'] ?? null), 422);
                $subscriptions->paid($record, 'bog:'.$orderId.':completed', $orderId,
                    (string) data_get($receipt, 'purchase_units.transfer_amount', ''),
                    (string) data_get($receipt, 'purchase_units.currency_code', data_get($receipt, 'purchase_units.currency', '')),
                    Carbon::parse($receipt['zoned_create_date']));
            } elseif (in_array($status, ['rejected', 'failed', 'expired'], true)) {
                $subscriptions->failed($record, 'bog:'.$orderId.':'.$status);
            }
        }

        if (isset($record, $eventId)) {
            SubscriptionPayment::query()->firstOrCreate(['event_id' => $eventId], [
                'customer_subscription_id' => $record->id, 'gateway' => $record->gateway,
                'kind' => 'lifecycle', 'status' => $record->fresh()->status,
            ]);
        }

        return response()->json(['received' => true]);
    }
}
