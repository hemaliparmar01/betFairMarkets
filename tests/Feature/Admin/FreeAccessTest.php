<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerSubscriptions;
use App\Models\User;
use App\Services\Payments\FittService;
use App\Services\SubscriptionService;
use Database\Factories\CustomerFactory;
use Database\Factories\SubscriptionsFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FreeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00', 'UTC'));
        Http::preventStrayRequests();
        Mail::fake();
        $this->actingAs(User::factory()->create(), 'web');
    }

    public function test_admin_creates_verified_customer_without_subscription_and_hashes_password(): void
    {
        $this->get('/admin/users/create')->assertOk()->assertSee('Add Customer')->assertSee('Lifetime Access');
        $this->post('/admin/users', $this->details())->assertSessionHasNoErrors()->assertRedirect();
        $customer = Customer::query()->sole();
        $this->assertTrue(Hash::check('Secret@123', $customer->password));
        $this->assertTrue($customer->is_verified);
        $this->assertSame('91', $customer->mobile_code);
        $this->assertFalse($customer->hasActiveSubscription());
        $this->assertDatabaseCount('customers_subscriptions', 0);
        $this->assertDatabaseCount('subscription_payments', 0);
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_admin_customer_creation_rejects_duplicates_and_invalid_passwords(): void
    {
        $this->post('/admin/users', $this->details())->assertSessionHasNoErrors();
        $this->post('/admin/users', $this->details())->assertSessionHasErrors(['email', 'username', 'mobile']);
        $this->post('/admin/users', array_replace($this->details(), ['password' => 'weak', 'country_code' => 'ZZ']))->assertSessionHasErrors(['password', 'country_code']);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_customer_can_be_created_with_free_plan_or_lifetime_access(): void
    {
        $plan = SubscriptionsFactory::new()->create();
        $this->post('/admin/users', $this->details() + ['subscription_id' => $plan->id, 'admin_free_days' => 7])
            ->assertSessionHasNoErrors();
        $customer = Customer::query()->sole();
        $this->assertSame(7, $customer->admin_free_days);
        $this->assertSame(now()->addDays(7)->toDateTimeString(), $customer->subscription_ends_at->toDateTimeString());
        $this->assertDatabaseHas('customers_subscriptions', ['customer_id' => $customer->id, 'gateway' => 'admin', 'amount' => 0, 'auto_renew' => false]);
        $this->post('/admin/users', array_replace($this->details(), ['email' => 'lifetime@example.com', 'username' => 'lifetime', 'mobile' => '9999999998', 'admin_free_days' => -1]))->assertSessionHasNoErrors();
        $lifetime = Customer::query()->where('email', 'lifetime@example.com')->firstOrFail();
        $this->assertTrue($lifetime->hasLifetimeAccess());
        $this->assertNull($lifetime->subscription_ends_at);
        Http::assertNothingSent();
    }

    public function test_manual_activation_uses_customer_dates_without_a_payment(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create();
        $this->grant($customer, ['subscription_id' => $plan->id, 'subscription_starts_at' => now()->subDay()->toIso8601String(), 'subscription_ends_at' => now()->addDays(20)->toIso8601String()])->assertSessionHasNoErrors();
        $this->assertTrue($customer->fresh()->hasActiveSubscription());
        $this->assertSame(now()->subDay()->toDateTimeString(), $customer->fresh()->subscription_starts_at->toDateTimeString());
        $this->assertDatabaseCount('subscription_payments', 0);
        $this->assertDatabaseHas('customers_subscriptions', ['gateway' => 'admin', 'gateway_subscription_id' => null, 'amount' => 0, 'auto_renew' => false]);
        Http::assertNothingSent();
    }

    public function test_seven_thirty_and_ninety_day_grants_expire_without_double_counting(): void
    {
        foreach ([7, 30, 90] as $days) {
            $customer = CustomerFactory::new()->create();
            $this->grant($customer, ['admin_free_days' => $days])->assertSessionHasNoErrors();
            $customer->refresh();
            $this->actingAs($customer, 'customers');
            $this->assertTrue(checkAuthAndSubscription()['has_active_subscription']);
            $this->assertSame($days, $customer->admin_free_days);
            $this->travel($days)->days();
            $this->assertFalse($customer->hasActiveSubscription());
            $this->assertFalse(checkAuthAndSubscription()['has_active_subscription']);
            $this->travelBack();
            $this->actingAs(User::query()->firstOrFail(), 'web');
        }
    }

    public function test_active_expired_and_repeated_extensions_keep_all_granted_days(): void
    {
        $plan = SubscriptionsFactory::new()->create();
        $customer = CustomerFactory::new()->create(['subscription_starts_at' => now()->subDays(10), 'subscription_ends_at' => now()->addDays(10)]);
        foreach ([7, 30] as $days) {
            $this->grant($customer, ['subscription_id' => $plan->id, 'add_active_days' => $days])->assertSessionHasNoErrors();
        }
        $this->assertSame(now()->addDays(47)->toDateTimeString(), $customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertSame(37, $customer->fresh()->admin_free_days);
        $this->assertDatabaseCount('customers_subscriptions', 1);
        $expired = CustomerFactory::new()->create(['subscription_ends_at' => now()->subDays(20)]);
        $this->grant($expired, ['admin_free_days' => 7])->assertSessionHasNoErrors();
        $this->assertSame(now()->addDays(7)->toDateTimeString(), $expired->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_lifetime_takes_precedence_over_all_dates_and_is_not_expired_or_billed(): void
    {
        $customer = CustomerFactory::new()->create(['subscription_ends_at' => now()->subDay()]);
        $plan = SubscriptionsFactory::new()->create();
        $this->grant($customer, ['subscription_id' => $plan->id, 'admin_free_days' => -1])->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertNull($customer->subscription_ends_at);
        $customer->forceFill(['subscription_starts_at' => now()->addYears(10), 'subscription_ends_at' => now()->subYear()])->save();
        $this->assertTrue($customer->hasActiveSubscription());
        $this->actingAs($customer, 'customers');
        $this->assertTrue(checkAuthAndSubscription()['display_odds']);
        $this->artisan('subscriptions:process')->assertSuccessful();
        $this->assertDatabaseHas('customers_subscriptions', ['customer_id' => $customer->id, 'status' => 'active']);
        $this->actingAs(User::query()->firstOrFail(), 'web')->get('/admin/users/'.$customer->id.'/edit')->assertOk()->assertSee('Lifetime Access');
        $this->get('/admin/users')->assertOk()->assertSee('Lifetime Access');
        Http::assertNothingSent();
    }

    public function test_future_start_and_attempted_shortening_are_handled_safely(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create();
        $this->grant($customer, ['subscription_id' => $plan->id, 'subscription_starts_at' => now()->addDays(3)->toIso8601String(), 'admin_free_days' => 7])->assertSessionHasNoErrors();
        $this->assertFalse($customer->fresh()->hasActiveSubscription());
        $active = CustomerFactory::new()->create(['subscription_ends_at' => now()->addDays(30)]);
        $this->grant($active, ['subscription_id' => $plan->id, 'subscription_ends_at' => now()->addDay()->toIso8601String()])->assertSessionHasErrors('subscription_ends_at');
        $this->assertSame(now()->addDays(30)->toDateTimeString(), $active->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_lifetime_and_free_grants_do_not_modify_or_charge_active_provider_billing(): void
    {
        $customer = CustomerFactory::new()->create();
        $record = $this->paidRecord($customer, true);
        foreach ([-1, 7] as $days) {
            $this->grant($customer, ['admin_free_days' => $days])->assertSessionHasErrors('subscription_id');
        }
        $this->assertTrue($record->fresh()->auto_renew);
        $this->assertSame('I-TEST-'.$customer->id, $record->fresh()->gateway_subscription_id);
        $this->assertDatabaseCount('customers_subscriptions', 1);
        $this->assertDatabaseCount('subscription_payments', 0);
        Http::assertNothingSent();
    }

    public function test_delayed_paid_notification_preserves_admin_extension_and_lifetime(): void
    {
        foreach ([30, -1] as $days) {
            $customer = CustomerFactory::new()->create(['subscription_ends_at' => now()->addDays(10)]);
            $record = $this->paidRecord($customer);
            $this->grant($customer, ['admin_free_days' => $days])->assertSessionHasNoErrors();
            $end = $customer->fresh()->subscription_ends_at;
            app(SubscriptionService::class)->paid($record, 'event-'.$customer->id, 'payment-'.$customer->id, '10.00', 'EUR', now(), now()->addDays(30));
            $this->assertEquals($end, $customer->fresh()->subscription_ends_at);
            $this->assertTrue($customer->fresh()->hasActiveSubscription());
        }
        $this->assertDatabaseCount('subscription_payments', 2);
    }

    public function test_new_checkout_is_blocked_while_admin_access_is_available(): void
    {
        config(['services.paypal.client_id' => 'test', 'services.paypal.secret' => 'test', 'services.paypal.webhook_id' => 'test']);
        $customer = CustomerFactory::new()->create(['admin_free_days' => -1]);
        $plan = SubscriptionsFactory::new()->create(['paypal_plan_id' => 'P-TEST']);
        try {
            app(SubscriptionService::class)->checkout($customer, $plan, 'paypal', true, (string) Str::uuid());
            $this->fail('A lifetime customer must not enter paid checkout.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subscription', $exception->errors());
        }
        Http::assertNothingSent();
    }

    public function test_only_admin_can_create_customers_or_grant_access(): void
    {
        auth('web')->logout();
        $customer = CustomerFactory::new()->create();
        $this->post('/admin/users', $this->details())->assertRedirect(route('admin.logins.index'));
        $this->actingAs($customer, 'customers');
        $this->grant($customer, ['admin_free_days' => -1])->assertRedirect(route('admin.logins.index'));
        $this->post('/admin/users', $this->details())->assertRedirect(route('admin.logins.index'));
        $this->assertNull($customer->fresh()->admin_free_days);
    }

    public function test_verified_gateway_webhook_keeps_admin_granted_access(): void
    {
        config(['services.flitt.merchant_id' => '123', 'services.flitt.key' => 'test-secret', 'services.flitt.currency' => 'EUR']);
        $customer = CustomerFactory::new()->create(['subscription_ends_at' => now()->addDays(10)]);
        $record = $this->paidRecord($customer);
        $record->update(['gateway' => 'tbc_flitt']);
        $this->grant($customer, ['admin_free_days' => 30])->assertSessionHasNoErrors();
        $payload = ['order_id' => $record->checkout_key, 'payment_id' => 'flitt-payment', 'merchant_id' => '123',
            'amount' => 1000, 'currency' => 'EUR', 'order_status' => 'approved', 'response_status' => 'success',
            'order_time' => now('Asia/Tbilisi')->format('d.m.Y H:i:s')];
        $payload['signature'] = app(FittService::class)->generateSignature($payload);
        $this->postJson('/subscriptions/payments/webhooks/tbc_flitt', $payload)->assertOk();
        $this->assertSame(now()->addDays(40)->toDateTimeString(), $customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertDatabaseCount('subscription_payments', 1);
        Http::assertNothingSent();
    }

    public function test_lifetime_access_is_displayed_in_customer_profile(): void
    {
        $customer = CustomerFactory::new()->create(['password' => 'Secret@123', 'admin_free_days' => -1]);
        $this->post('/login', ['identity' => $customer->email, 'password' => 'Secret@123'])->assertSessionHasNoErrors();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->actingAs($customer->fresh(), 'customers')->get('/my-profile?section=subscription')->assertOk()->assertSee('Lifetime Access')->assertSee('Admin Granted');
    }

    public function test_manual_dates_preserve_the_instant_when_an_offset_is_provided(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create();
        $this->grant($customer, ['subscription_id' => $plan->id,
            'subscription_starts_at' => '2026-10-23T16:00:00+04:00',
            'subscription_ends_at' => '2026-10-30T16:00:00+04:00'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-23 12:00:00', $customer->fresh()->subscription_starts_at->toDateTimeString());
        $this->assertSame('2026-10-30 12:00:00', $customer->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertTrue($customer->fresh()->hasActiveSubscription());
    }

    public function test_invalid_free_day_combinations_and_pending_lifetime_are_rejected(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->grant($customer, ['admin_free_days' => -2])->assertSessionHasErrors('admin_free_days');
        $this->grant($customer, ['admin_free_days' => -1, 'add_active_days' => 7])->assertSessionHasErrors('admin_free_days');
        $this->put('/admin/users/'.$customer->id, ['section' => 'subscription', 'subscription_status' => 'pending', 'admin_free_days' => -1])->assertSessionHasErrors('subscription_status');
        $this->assertFalse($customer->fresh()->hasActiveSubscription());
        Http::assertNothingSent();
    }

    public function test_scheduled_free_access_cannot_overlap_a_new_paid_checkout(): void
    {
        config(['services.paypal.client_id' => 'test', 'services.paypal.secret' => 'test', 'services.paypal.webhook_id' => 'test']);
        $customer = CustomerFactory::new()->create(['admin_free_days' => 7, 'subscription_starts_at' => now()->addDay(), 'subscription_ends_at' => now()->addDays(8)]);
        $plan = SubscriptionsFactory::new()->create(['paypal_plan_id' => 'P-TEST']);
        try {
            app(SubscriptionService::class)->checkout($customer, $plan, 'paypal', true, (string) Str::uuid());
            $this->fail('Scheduled free access must not overlap a new paid subscription.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subscription', $exception->errors());
        }
        $this->assertDatabaseCount('customers_subscriptions', 0);
        Http::assertNothingSent();
    }

    private function grant(Customer $customer, array $data): TestResponse
    {
        return $this->put('/admin/users/'.$customer->id, ['section' => 'subscription', 'subscription_status' => 'active'] + $data);
    }

    private function paidRecord(Customer $customer, bool $autoRenew = false): CustomerSubscriptions
    {
        return CustomerSubscriptions::query()->create(['customer_id' => $customer->id, 'subscription_id' => SubscriptionsFactory::new()->create()->id,
            'gateway' => 'paypal', 'gateway_subscription_id' => 'I-TEST-'.$customer->id, 'amount' => 10, 'currency' => 'EUR', 'auto_renew' => $autoRenew,
            'checkout_key' => (string) Str::uuid(), 'status' => $autoRenew ? 'active' : 'cancelled', 'duration_days' => 30]);
    }

    /** @return array<string, mixed> */
    private function details(): array
    {
        return ['name' => 'Admin', 'surname' => 'Customer', 'username' => 'admincustomer', 'email' => 'admincustomer@example.com',
            'password' => 'Secret@123', 'password_confirmation' => 'Secret@123', 'country_code' => 'IN', 'mobile' => '9999999999'];
    }
}
