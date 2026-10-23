<?php

namespace App\Console\Commands;

use App\Models\CustomerSubscriptions;
use App\Models\SubscriptionPayment;
use App\Services\Payments\BogPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class ProcessSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Expire paid access and submit idempotent BOG agreement renewals; PayPal and Flitt manage their own schedules';

    public function handle(SubscriptionService $subscriptions): int
    {
        $failures = 0;
        CustomerSubscriptions::query()->with('customer')->whereIn('status', ['active', 'past_due', 'cancelled', 'cancellation_pending'])
            ->chunkById(100, function ($records) use ($subscriptions, &$failures): void {
                foreach ($records as $record) {
                    try {
                        Cache::lock('subscription-customer:'.$record->customer_id, 120)->block(5, function () use ($record, $subscriptions): void {
                            $record->refresh();
                            if ($record->customer->hasLifetimeAccess()) {
                                return;
                            }
                            $end = $record->customer->subscription_ends_at;
                            if (! $record->auto_renew && $end !== null && $end->lte(now())) {
                                $record->update(['status' => 'expired', 'active_customer_id' => null, 'next_billing_at' => null]);

                                return;
                            }
                            if ($end !== null && $end->lte(now()) && $record->auto_renew && $record->status === 'active') {
                                $record->update(['status' => 'past_due']);
                            }
                            if ($record->gateway !== 'bog' || ! config('services.bog.recurring_enabled') || ! $record->auto_renew
                                || ! in_array($record->status, ['active', 'past_due'], true)
                                || $record->next_billing_at === null || $record->next_billing_at->isFuture()
                                || $record->billing_failures >= 3 || blank($record->gateway_subscription_id)) {
                                return;
                            }
                            $key = 'bog-renewal:'.$record->id.':'.$record->next_billing_at->timestamp;
                            $externalId = (string) Str::uuid();
                            $payment = SubscriptionPayment::query()->firstOrCreate(['event_id' => $key], [
                                'idempotency_key' => $externalId,
                                'customer_subscription_id' => $record->id, 'gateway' => 'bog', 'event_id' => $key,
                                'kind' => 'renewal_request', 'status' => 'pending',
                                'payload' => ['external_order_id' => $externalId],
                            ]);
                            if ($payment->status !== 'pending') {
                                return;
                            }
                            $response = app(BogPaymentService::class)->chargeAutomaticPayment($record->gateway_subscription_id,
                                $subscriptions->url('subscriptions.webhook', ['gateway' => 'bog']), $payment->payload['external_order_id']);
                            if (empty($response['id'])) {
                                throw new \RuntimeException('BOG did not return a renewal order ID.');
                            }
                            $payment->update(['status' => 'submitted', 'payload' => $payment->payload + ['order_id' => $response['id']]]);
                            if ($end !== null && $end->lte(now())) {
                                $record->update(['status' => 'past_due']);
                            }
                        });
                    } catch (Throwable $exception) {
                        report($exception);
                        $failures++;
                    }
                }
            });
        $this->info('Subscription processing complete. Provider request failures: '.$failures);

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
