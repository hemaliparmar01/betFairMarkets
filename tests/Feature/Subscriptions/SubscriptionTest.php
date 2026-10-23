<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Customer;
use App\Models\CustomerSubscriptions;
use App\Models\SubscriptionPayment;
use App\Models\Subscriptions;
use App\Repositories\CustomerRepository;
use App\Services\CustomerAuthService;
use App\Services\Payments\FittService;
use App\Services\SubscriptionService;
use Database\Factories\CustomerFactory;
use Database\Factories\SubscriptionsFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Subscriptions $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $auth = \Mockery::mock(CustomerAuthService::class, [app(CustomerRepository::class)])->makePartial();
        $auth->shouldReceive('handle')->andReturnUsing(fn ($request, $next) => $next($request));
        $this->app->instance(CustomerAuthService::class, $auth);
        Http::preventStrayRequests();
        config([
            'services.paypal.base_url' => 'https://api-m.sandbox.paypal.com',
            'services.paypal.client_id' => 'test-client', 'services.paypal.secret' => 'test-secret', 'services.paypal.webhook_id' => 'test-webhook',
            'services.flitt.base_url' => 'https://test.flitt.example', 'services.flitt.merchant_id' => '123',
            'services.flitt.key' => 'test-key', 'services.flitt.currency' => 'EUR', 'services.flitt.recurring_enabled' => true,
        ]);
        $this->customer = CustomerFactory::new()->create();
        $this->plan = SubscriptionsFactory::new()->create(['label' => 'Premium', 'paypal_plan_id' => 'P-TEST']);
    }

    public function test_plans_checkout_and_profile_use_database_records(): void
    {
        $this->get('/subscriptions')->assertOk()->assertSee('Premium');
        $this->get('/subscriptions-details?plan='.$this->plan->id)->assertRedirect(route('login'));
        $this->actingAs($this->customer, 'customers')->get('/subscriptions-details?plan='.$this->plan->id)
            ->assertOk()->assertSee('AutoPay')->assertSee('PayPal');
        $this->get('/my-profile?section=subscription')->assertOk();
    }

    public function test_customer_dates_are_the_only_access_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('customers', 'subscription_starts_at'));
        $this->assertFalse(Schema::hasColumn('customers_subscriptions', 'starts_at'));
        $this->assertFalse(Schema::hasColumn('customers_subscriptions', 'ends_at'));
    }

    public function test_checkout_requires_customer_authentication(): void
    {
        $this->post('/subscriptions/'.$this->plan->id.'/checkout', ['gateway' => 'paypal', 'checkout_key' => (string) Str::uuid(), 'auto_renew' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_paypal_checkout_is_idempotent_and_does_not_grant_access(): void
    {
        $this->paypalFake();
        $this->actingAs($this->customer, 'customers');
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post('/subscriptions/'.$this->plan->id.'/checkout', ['gateway' => 'paypal', 'checkout_key' => (string) Str::uuid(), 'auto_renew' => 1])
                ->assertSessionHasNoErrors()->assertRedirect('https://www.sandbox.paypal.com/approve');
        }
        $this->assertDatabaseCount('customers_subscriptions', 1);
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
        $this->assertSame('pending', CustomerSubscriptions::query()->sole()->status);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/v1/billing/subscriptions') && $request->hasHeader('PayPal-Request-Id'));
        $creates = collect(Http::recorded())->filter(fn ($pair): bool => str_ends_with($pair[0]->url(), '/v1/billing/subscriptions'));
        $this->assertCount(1, $creates);
    }

    public function test_paypal_rejects_mismatched_remote_plan_and_missing_consent(): void
    {
        $this->paypalFake(incorrectPrice: true);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$this->plan->id.'/checkout',
            ['gateway' => 'paypal', 'checkout_key' => (string) Str::uuid(), 'auto_renew' => 1])->assertSessionHasErrors('gateway');
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
        $this->post('/subscriptions/'.$this->plan->id.'/checkout', ['gateway' => 'paypal', 'checkout_key' => (string) Str::uuid()])->assertSessionHasErrors('gateway');
    }

    public function test_payment_and_renewal_extend_only_customer_dates_once(): void
    {
        $record = $this->subscription();
        $service = app(SubscriptionService::class);
        $now = now()->startOfSecond();
        $service->paid($record, 'sale-event-1', 'sale-1', '10.00', 'EUR', $now);
        $firstEnd = $this->customer->fresh()->subscription_ends_at;
        $service->paid($record, 'sale-event-duplicate', 'sale-1', '10.00', 'EUR', $now);
        $this->assertSame($firstEnd->toDateTimeString(), $this->customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertDatabaseCount('subscription_payments', 1);
        $service->paid($record, 'sale-event-2', 'sale-2', '10.00', 'EUR', $now->copy()->addDays(30));
        $this->assertSame($now->copy()->addDays(60)->toDateTimeString(), $this->customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertSame('active', $record->fresh()->status);
    }

    public function test_failed_renewal_does_not_extend_access_and_is_deduplicated(): void
    {
        $record = $this->subscription();
        $service = app(SubscriptionService::class);
        $service->paid($record, 'paid', 'sale', '10', 'EUR', now()->subDays(30));
        $end = $this->customer->fresh()->subscription_ends_at->toDateTimeString();
        $service->failed($record, 'failure-1');
        $service->failed($record, 'failure-1');
        $this->assertSame('past_due', $record->fresh()->status);
        $this->assertSame(1, $record->fresh()->billing_failures);
        $this->assertFalse($this->customer->fresh()->hasActiveSubscription());
        $this->assertSame($end, $this->customer->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_wrong_amount_or_currency_cannot_activate(): void
    {
        $record = $this->subscription();
        $this->expectException(ValidationException::class);
        try {
            app(SubscriptionService::class)->paid($record, 'bad', 'bad-sale', '1.00', 'GEL', now());
        } finally {
            $this->assertNull($this->customer->fresh()->subscription_ends_at);
        }
    }

    public function test_unverified_callbacks_cannot_activate_subscriptions(): void
    {
        $this->subscription();
        $this->postJson('/subscriptions/payments/webhooks/paypal', ['id' => 'forged'])->assertUnauthorized();
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', ['order_id' => 'forged'])->assertUnauthorized();
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
    }

    public function test_signed_flitt_callbacks_activate_once_and_reject_wrong_amounts(): void
    {
        $record = $this->subscription('tbc_flitt');
        $payload = $this->flittPayload($record);
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $payload)->assertOk();
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $payload)->assertOk();
        $this->assertDatabaseCount('subscription_payments', 1);
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $payload['payment_id'] = 'second';
        $payload['amount'] = 1;
        $payload['signature'] = app(FittService::class)->generateSignature($payload);
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $payload)->assertUnprocessable();
    }

    public function test_flitt_checkout_requests_provider_managed_schedule(): void
    {
        $provider = app(FittService::class);
        $response = ['checkout_url' => 'https://pay.flitt.com/approve', 'response_status' => 'success'];
        $response['signature'] = $provider->generateSignature($response);
        Http::fake(['test.flitt.example/*' => Http::response(['response' => $response])]);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$this->plan->id.'/checkout',
            ['gateway' => 'tbc_flitt', 'checkout_key' => (string) Str::uuid(), 'auto_renew' => 1])->assertRedirect('https://pay.flitt.com/approve');
        Http::assertSent(function ($request): bool {
            $data = $request->data()['request'];
            $schedule = json_decode($data['recurring_data'], true);

            return $data['subscription'] === 'Y' && $schedule['every'] === 30 && $schedule['amount'] === 1000;
        });
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
    }

    public function test_confirmed_flitt_cancellation_keeps_paid_access_and_is_repeat_safe(): void
    {
        $record = $this->subscription('tbc_flitt');
        app(SubscriptionService::class)->paid($record, 'initial', 'paid', '10', 'EUR', now());
        $end = $this->customer->fresh()->subscription_ends_at->toDateTimeString();
        $response = ['response_status' => 'success', 'status' => 'disabled', 'order_id' => $record->gateway_subscription_id];
        $response['signature'] = app(FittService::class)->generateSignature($response);
        Http::fake(['test.flitt.example/*' => Http::response(['response' => $response])]);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        $this->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        Http::assertSentCount(1);
        $this->assertFalse($record->fresh()->auto_renew);
        $this->assertSame('cancelled', $record->fresh()->status);
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $this->assertSame($end, $this->customer->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_provider_cancellation_failure_is_not_displayed_as_success(): void
    {
        $record = $this->subscription('tbc_flitt');
        Http::fake(['test.flitt.example/*' => Http::response([], 500)]);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHasErrors('subscription');
        $this->assertTrue($record->fresh()->auto_renew);
        $this->assertNull($record->fresh()->cancelled_at);
    }

    public function test_customers_cannot_cancel_someone_elses_subscription(): void
    {
        $record = $this->subscription();
        $this->actingAs(CustomerFactory::new()->create(), 'customers')->post('/subscriptions/'.$record->id.'/cancel')->assertForbidden();
    }

    public function test_cancelled_access_expires_and_is_not_renewed(): void
    {
        $record = $this->subscription('bog');
        $record->update(['status' => 'cancelled', 'auto_renew' => false]);
        $this->customer->forceFill(['subscription_ends_at' => now()->subMinute()])->save();
        $this->artisan('subscriptions:process')->assertSuccessful();
        $this->assertSame('expired', $record->fresh()->status);
        $this->assertFalse($this->customer->fresh()->hasActiveSubscription());
        Http::assertNothingSent();
    }

    public function test_return_url_never_activates_access(): void
    {
        $this->subscription();
        $this->actingAs($this->customer, 'customers')->get('/subscriptions/payment/return?status=success')->assertRedirect();
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
    }

    public function test_verified_paypal_sale_webhook_and_renewal_are_deduplicated(): void
    {
        $record = $this->subscription();
        $details = ['id' => 'I-TEST', 'custom_id' => $record->checkout_key, 'plan_id' => 'P-TEST', 'status' => 'ACTIVE',
            'billing_info' => ['last_payment' => ['time' => now()->toIso8601String()], 'next_billing_time' => now()->addDays(30)->toIso8601String()]];
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
            'api-m.sandbox.paypal.com/v1/billing/subscriptions/I-TEST' => Http::response($details),
        ]);
        $headers = ['PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID' => 'test', 'PAYPAL-TRANSMISSION-SIG' => 'test', 'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String()];
        $event = ['id' => 'WH-1', 'event_type' => 'PAYMENT.SALE.COMPLETED', 'resource' => [
            'id' => 'SALE-1', 'billing_agreement_id' => 'I-TEST', 'create_time' => now()->toIso8601String(),
            'amount' => ['total' => '10.00', 'currency' => 'EUR'],
        ]];
        $this->postJson('/subscriptions/payments/webhooks/paypal', $event, $headers)->assertOk();
        $this->postJson('/subscriptions/payments/webhooks/paypal', $event, $headers)->assertOk();
        $this->assertDatabaseCount('subscription_payments', 1);
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $this->assertSame('active', $record->fresh()->status);
        $this->assertSame(now()->addDays(30)->toDateTimeString(), $this->customer->fresh()->subscription_ends_at->toDateTimeString());
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), 'verify-webhook-signature') && $request['webhook_id'] === 'test-webhook');
    }

    public function test_bog_signed_receipt_activates_only_the_matching_paid_order(): void
    {
        $privateKey = $this->bogConfiguration();
        $record = $this->subscription('bog');
        $record->update(['amount' => '20.00', 'currency' => 'GEL']);
        Http::fake([
            'bog.example/oauth' => Http::response(['access_token' => 'bog-token']),
            'bog.example/payments/v1/receipt/bog-payment' => Http::response([
                'order_id' => 'bog-payment', 'external_order_id' => $record->checkout_key,
                'order_status' => ['key' => 'completed'], 'zoned_create_date' => now()->toIso8601String(),
                'purchase_units' => ['transfer_amount' => '20.00', 'currency_code' => 'GEL'],
            ]),
        ]);
        $payload = ['event' => 'order_payment', 'body' => ['order_id' => 'bog-payment']];
        $this->postJson('/subscriptions/payments/webhooks/bog', $payload, ['Callback-Signature' => 'invalid'])->assertUnauthorized();
        openssl_sign(json_encode($payload), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $this->postJson('/subscriptions/payments/webhooks/bog', $payload, ['Callback-Signature' => base64_encode($signature)])->assertOk();
        $this->postJson('/subscriptions/payments/webhooks/bog', $payload, ['Callback-Signature' => base64_encode($signature)])->assertOk();
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $this->assertDatabaseCount('subscription_payments', 1);
    }

    public function test_bog_renewal_submission_uses_a_persistent_idempotency_key(): void
    {
        $this->bogConfiguration();
        $record = $this->subscription('bog');
        $record->update(['status' => 'active', 'next_billing_at' => now()->subMinute()]);
        $this->customer->forceFill(['subscription_ends_at' => now()->subMinute()])->save();
        Http::fake([
            'bog.example/oauth' => Http::response(['access_token' => 'bog-token']),
            'bog.example/payments/v1/ecommerce/orders/order-test/subscribe' => Http::response(['id' => 'renewal-order']),
        ]);
        $this->artisan('subscriptions:process')->assertSuccessful();
        $this->artisan('subscriptions:process')->assertSuccessful();
        $requests = collect(Http::recorded())->filter(fn ($pair): bool => str_ends_with($pair[0]->url(), '/subscribe'));
        $this->assertCount(1, $requests);
        $payment = SubscriptionPayment::query()->sole();
        $this->assertSame('submitted', $payment->status);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/subscribe') && $request['external_order_id'] === $payment->idempotency_key && $request->hasHeader('Idempotency-Key', $payment->idempotency_key));
        $this->assertFalse($this->customer->fresh()->hasActiveSubscription());
    }

    public function test_bog_accepted_cancellation_remains_pending_and_stops_local_renewals(): void
    {
        $this->bogConfiguration();
        $record = $this->subscription('bog');
        $record->update(['status' => 'active', 'next_billing_at' => now()->subMinute()]);
        $this->customer->forceFill(['subscription_ends_at' => now()->addDays(5)])->save();
        Http::fake([
            'bog.example/oauth' => Http::response(['access_token' => 'bog-token']),
            'bog.example/payments/v1/charges/card/order-test' => Http::response([], 202),
        ]);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        $this->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        Http::assertSentCount(2);
        $this->assertSame('cancellation_pending', $record->fresh()->status);
        $this->assertFalse($record->fresh()->auto_renew);
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $this->artisan('subscriptions:process')->assertSuccessful();
        Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/subscribe'));
    }

    public function test_legacy_dates_are_archived_before_schema_removal(): void
    {
        config(['database.connections.legacy_subscription_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection('legacy_subscription_test');
        try {
            foreach (['0001_01_01_000000_create_users_table.php', '2026_10_09_112216_create_customer_auth_tables.php', '2026_10_23_004218_create_admin_subscription_tables.php'] as $file) {
                (require database_path('migrations/'.$file))->up();
            }
            $customer = CustomerFactory::new()->create(['subscription_ends_at' => '2026-10-01 00:00:00']);
            $plan = SubscriptionsFactory::new()->create();
            DB::table('customers_subscriptions')->insert([
                'customer_id' => $customer->id, 'subscription_id' => $plan->id, 'gateway' => 'admin',
                'amount' => 10, 'currency' => 'EUR', 'status' => 'active',
                'starts_at' => '2026-10-15 00:00:00', 'ends_at' => '2026-11-15 00:00:00',
            ]);
            (require database_path('migrations/2026_10_23_012533_centralize_customer_subscription_billing.php'))->up();
            $this->assertFalse(Schema::hasColumn('customers_subscriptions', 'ends_at'));
            $this->assertSame('2026-11-15 00:00:00', $customer->fresh()->subscription_ends_at->toDateTimeString());
            $this->assertSame('2026-10-15 00:00:00', $customer->fresh()->subscription_starts_at->toDateTimeString());
            $this->assertSame('2026-11-15 00:00:00', SubscriptionPayment::query()->sole()->payload['ends_at']);
            $this->assertDatabaseCount('customers_subscriptions', 1);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('legacy_subscription_test');
        }
    }

    private function bogConfiguration(): \OpenSSLAsymmetricKey
    {
        $privateKey = openssl_pkey_new(['private_key_bits' => 2048]);
        config([
            'services.bog.base_url' => 'https://bog.example', 'services.bog.auth_url' => 'https://bog.example/oauth',
            'services.bog.client_id' => 'test-client', 'services.bog.secret' => 'test-secret',
            'services.bog.public_key' => openssl_pkey_get_details($privateKey)['key'], 'services.bog.recurring_enabled' => true,
        ]);

        return $privateKey;
    }

    public function test_paypal_cancellation_is_verified_remotely_and_repeat_safe(): void
    {
        $record = $this->subscription();
        app(SubscriptionService::class)->paid($record, 'first', 'payment-first', '10', 'EUR', now());
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            'api-m.sandbox.paypal.com/v1/billing/subscriptions/I-TEST' => Http::sequence()->push(['status' => 'ACTIVE'])->push(['status' => 'CANCELLED']),
            'api-m.sandbox.paypal.com/v1/billing/subscriptions/I-TEST/cancel' => Http::response('', 204),
        ]);
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        $this->post('/subscriptions/'.$record->id.'/cancel')->assertSessionHas('status');
        $this->assertSame('cancelled', $record->fresh()->status);
        $this->assertFalse($record->fresh()->auto_renew);
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
        $requests = collect(Http::recorded())->filter(fn ($pair): bool => str_ends_with($pair[0]->url(), '/cancel'));
        $this->assertCount(1, $requests);
    }

    public function test_provider_scheduled_flitt_renewals_use_unique_payments_not_parent_order_ids(): void
    {
        $record = $this->subscription('tbc_flitt');
        $initial = $this->flittPayload($record);
        $initial['order_time'] = now('Asia/Tbilisi')->subDays(30)->format('d.m.Y H:i:s');
        $initial['signature'] = app(FittService::class)->generateSignature($initial);
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $initial)->assertOk();
        $renewal = $this->flittPayload($record);
        $renewal['order_id'] = 'provider-renewal-order';
        $renewal['payment_id'] = 'renewal-payment';
        $renewal['merchant_data'] = json_encode(['checkout_key' => $record->checkout_key]);
        $renewal['signature'] = app(FittService::class)->generateSignature($renewal);
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $renewal)->assertOk();
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $renewal)->assertOk();
        $this->assertSame(2, SubscriptionPayment::query()->where('kind', 'payment')->count());
        $this->assertTrue($this->customer->fresh()->hasActiveSubscription());
    }

    public function test_gateway_post_return_only_redirects_and_never_activates_access(): void
    {
        $this->post('/subscriptions/payment/return', ['order_status' => 'approved'])->assertStatus(303)
            ->assertRedirect(route('subscriptions.return'));
        $this->assertNull($this->customer->fresh()->subscription_ends_at);
    }

    public function test_cancelled_paid_access_prevents_overlapping_checkout(): void
    {
        $record = $this->subscription();
        $record->update(['status' => 'cancelled', 'auto_renew' => false]);
        $this->customer->forceFill(['subscription_ends_at' => now()->addDays(5)])->save();
        $this->actingAs($this->customer, 'customers')->post('/subscriptions/'.$this->plan->id.'/checkout',
            ['gateway' => 'paypal', 'checkout_key' => (string) Str::uuid(), 'auto_renew' => 1])->assertSessionHasErrors('subscription');
        $this->assertDatabaseCount('customers_subscriptions', 1);
        Http::assertNothingSent();
    }

    public function test_initial_failed_checkout_releases_customer_slot_without_granting_access(): void
    {
        $record = $this->subscription('tbc_flitt');
        app(SubscriptionService::class)->failed($record, 'initial-failure');
        $this->assertSame('failed', $record->fresh()->status);
        $this->assertNull($record->fresh()->active_customer_id);
        $this->assertFalse($this->customer->fresh()->hasActiveSubscription());
    }

    public function test_delayed_paypal_sale_does_not_grant_the_latest_unpaid_period(): void
    {
        $record = $this->subscription();
        $paidAt = now()->subDays(60)->startOfSecond();
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
            'api-m.sandbox.paypal.com/v1/billing/subscriptions/I-TEST' => Http::response([
                'id' => 'I-TEST', 'custom_id' => $record->checkout_key, 'plan_id' => 'P-TEST', 'status' => 'ACTIVE',
                'billing_info' => ['last_payment' => ['time' => now()->toIso8601String()],
                    'next_billing_time' => now()->addDays(30)->toIso8601String()],
            ]),
        ]);
        $headers = ['PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID' => 'test', 'PAYPAL-TRANSMISSION-SIG' => 'test', 'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String()];
        $this->postJson('/subscriptions/payments/webhooks/paypal', [
            'id' => 'WH-OLD', 'event_type' => 'PAYMENT.SALE.COMPLETED', 'resource' => [
                'id' => 'SALE-OLD', 'billing_agreement_id' => 'I-TEST', 'create_time' => $paidAt->toIso8601String(),
                'amount' => ['total' => '10.00', 'currency' => 'EUR'],
            ],
        ], $headers)->assertOk();
        $this->assertSame($paidAt->addDays(30)->toDateTimeString(), $this->customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertFalse($this->customer->fresh()->hasActiveSubscription());
    }

    private function subscription(string $gateway = 'paypal'): CustomerSubscriptions
    {
        return CustomerSubscriptions::query()->create([
            'customer_id' => $this->customer->id, 'subscription_id' => $this->plan->id,
            'amount' => 10, 'currency' => 'EUR', 'gateway' => $gateway, 'gateway_subscription_id' => $gateway === 'paypal' ? 'I-TEST' : 'order-test',
            'provider_plan_id' => $gateway === 'paypal' ? 'P-TEST' : null, 'status' => 'pending',
            'auto_renew' => true, 'checkout_key' => (string) Str::uuid(), 'duration_days' => 30,
        ]);
    }

    /** @return array<string, mixed> */
    private function flittPayload(CustomerSubscriptions $record): array
    {
        $payload = ['order_id' => $record->checkout_key, 'payment_id' => '1234', 'merchant_id' => '123',
            'amount' => 1000, 'currency' => 'EUR', 'response_status' => 'success', 'order_status' => 'approved',
            'order_time' => now('Asia/Tbilisi')->format('d.m.Y H:i:s')];
        $payload['signature'] = app(FittService::class)->generateSignature($payload);

        return $payload;
    }

    private function paypalFake(bool $incorrectPrice = false): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v1/billing/plans/*' => Http::response(['status' => 'ACTIVE', 'billing_cycles' => [[
                'tenure_type' => 'REGULAR', 'frequency' => ['interval_unit' => 'DAY', 'interval_count' => 30],
                'pricing_scheme' => ['fixed_price' => ['value' => $incorrectPrice ? '99.00' : '10.00', 'currency_code' => 'EUR']],
            ]]]),
            'api-m.sandbox.paypal.com/v1/billing/subscriptions' => Http::response(['id' => 'I-TEST', 'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/approve']]]),
        ]);
    }
}
