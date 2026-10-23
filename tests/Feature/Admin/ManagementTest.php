<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerLoginSession;
use App\Models\CustomerSubscriptions;
use App\Models\Subscriptions;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Factories\SubscriptionsFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(), 'web');
    }

    public function test_customer_details_show_existing_login_history_and_subscription(): void
    {
        $customer = CustomerFactory::new()->create(['active_session_id' => 'customer-session']);
        $plan = SubscriptionsFactory::new()->create(['label' => 'Monthly Premium']);
        CustomerSubscriptions::query()->create([
            'customer_id' => $customer->id, 'subscription_id' => $plan->id, 'status' => 'active',
            'gateway' => 'admin', 'amount' => 10, 'currency' => 'EUR',
        ]);
        $this->recordLogin($customer);

        $this->get('/admin/users?search='.$customer->email)->assertOk()->assertSee($customer->email);
        $this->get('/admin/users/'.$customer->id.'/edit')->assertOk()
            ->assertSee('127.0.0.42')->assertSee('Monthly Premium')->assertSee('Test Browser');
    }

    public function test_customer_profile_preserves_authentication_fields_and_updates_restrictions(): void
    {
        $customer = CustomerFactory::new()->create();
        $password = $customer->password;
        $this->put('/admin/users/'.$customer->id, [
            'section' => 'profile', 'name' => 'Updated', 'surname' => $customer->surname,
            'username' => $customer->username, 'email' => $customer->email,
            'mobile' => $customer->mobile, 'country' => $customer->country,
            'is_blocked' => 1, 'is_suspended' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.edit', $customer));
        $this->assertSame('Updated', $customer->fresh()->name);
        $this->assertTrue($customer->fresh()->is_blocked);
        $this->assertSame($password, $customer->fresh()->password);
        $this->assertSame('IN', $customer->fresh()->country_code);
    }

    public function test_admin_assignment_syncs_existing_subscription_fields_and_extends_days(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create(['label' => 'Premium']);
        $this->put('/admin/users/'.$customer->id, [
            'section' => 'subscription', 'subscription_id' => $plan->id,
            'subscription_status' => 'active', 'add_active_days' => 5,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $customer->refresh();
        $this->assertTrue($customer->hasActiveSubscription());
        $this->assertSame('Premium', $customer->subscription_plan);
        $this->assertSame(now()->addDays(5)->toDateString(), $customer->subscription_ends_at->toDateString());
        $this->assertDatabaseHas('customers_subscriptions', ['customer_id' => $customer->id, 'status' => 'active', 'gateway' => 'admin']);
    }

    public function test_pending_and_expired_assignments_do_not_grant_access(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create();
        foreach (['pending', 'expired'] as $status) {
            $this->put('/admin/users/'.$customer->id, [
                'section' => 'subscription', 'subscription_id' => $plan->id,
                'subscription_status' => $status, 'subscription_ends_at' => now()->addMonth()->toDateString(),
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertFalse($customer->fresh()->hasActiveSubscription());
        }
    }

    public function test_inactive_and_deleted_plans_cannot_be_assigned(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create(['is_active' => false]);
        $payload = ['section' => 'subscription', 'subscription_id' => $plan->id, 'subscription_status' => 'active'];
        $this->put('/admin/users/'.$customer->id, $payload)->assertSessionHasErrors('subscription_id');
        $plan->update(['is_active' => true]);
        $plan->delete();
        $this->put('/admin/users/'.$customer->id, $payload)->assertSessionHasErrors('subscription_id');
    }

    public function test_subscription_crud_search_and_safe_icon_upload(): void
    {
        Storage::fake('public');
        $this->get('/admin/subscriptions/create')->assertOk();
        $payload = $this->planPayload();
        $payload['icon'] = UploadedFile::fake()->image('plan.png');
        $this->post('/admin/subscriptions', $payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.subscriptions.index'));
        $plan = Subscriptions::query()->sole();
        Storage::disk('public')->assertExists($plan->icon);
        $this->assertSame(['ka' => 'Premium'], $plan->label_translations);
        $this->get('/admin/subscriptions?search=Premium')->assertOk()->assertSee('Premium');
        $this->get('/admin/subscriptions/'.$plan->id.'/edit')->assertOk()->assertSee('Premium');
        $previousIcon = $plan->icon;
        $this->put('/admin/subscriptions/'.$plan->id, $this->planPayload() + ['icon' => null])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($previousIcon, $plan->fresh()->icon);
        $this->delete('/admin/subscriptions/'.$plan->id)->assertRedirect(route('admin.subscriptions.index'));
        $this->assertSoftDeleted($plan);
    }

    public function test_plan_validation_rejects_executable_uploads_and_invalid_features(): void
    {
        $payload = $this->planPayload();
        $payload['icon'] = UploadedFile::fake()->create('shell.php', 1, 'application/x-php');
        $payload['features'] = [['text' => '']];
        $payload['duration_days'] = -1;
        $this->post('/admin/subscriptions', $payload)->assertSessionHasErrors(['icon', 'features.0.text', 'duration_days']);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_soft_deleted_plan_details_remain_in_customer_history(): void
    {
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create(['label' => 'Historical Premium']);
        CustomerSubscriptions::query()->create([
            'customer_id' => $customer->id, 'subscription_id' => $plan->id, 'status' => 'expired',
            'gateway' => 'admin', 'amount' => 10, 'currency' => 'EUR',
        ]);
        $plan->delete();
        $this->get('/admin/users/'.$customer->id.'/edit')->assertOk()->assertSee('Historical Premium');
    }

    public function test_dashboard_uses_database_totals_and_recent_logins(): void
    {
        $customer = CustomerFactory::new()->create(['active_session_id' => 'customer-session', 'subscription_ends_at' => now()->addDays(3)]);
        $this->recordLogin($customer);
        SubscriptionsFactory::new()->create();
        $this->get('/admin/dashboard')->assertOk()->assertSee($customer->email)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['totalUsers'] === 1
                && $stats['onlineUsers'] === 1 && $stats['activeSubscriptions'] === 1
                && $stats['expiringSoonUsers'] === 1 && $stats['activePlans'] === 1);
    }

    public function test_all_management_routes_require_admin_authentication(): void
    {
        auth('web')->logout();
        $customer = CustomerFactory::new()->create();
        $plan = SubscriptionsFactory::new()->create();
        foreach ([['GET', '/admin/users'], ['GET', '/admin/users/'.$customer->id.'/edit'],
            ['PUT', '/admin/users/'.$customer->id], ['GET', '/admin/subscriptions'],
            ['GET', '/admin/subscriptions/create'], ['POST', '/admin/subscriptions'],
            ['GET', '/admin/subscriptions/'.$plan->id.'/edit'], ['PUT', '/admin/subscriptions/'.$plan->id],
            ['DELETE', '/admin/subscriptions/'.$plan->id]] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('admin.logins.index'));
        }
    }

    /** @return array<string, mixed> */
    private function planPayload(): array
    {
        return [
            'label' => 'Premium', 'label_translations' => ['ka' => 'Premium'], 'is_popular' => 0,
            'description' => 'Full access', 'duration_days' => 30, 'price_for_local' => 20,
            'price_for_international' => 10, 'price_label' => 'per month',
            'features' => [['text' => 'Live markets', 'is_highlighted' => 0]],
            'button_text' => 'Subscribe', 'renewal_reminder_days_before' => 3, 'is_active' => 1,
        ];
    }

    private function recordLogin(Customer $customer): void
    {
        CustomerLoginSession::query()->create([
            'customer_id' => $customer->id, 'session_id' => 'customer-session',
            'ip_address' => '127.0.0.42', 'user_agent' => 'Test Browser', 'device_type' => 'desktop',
            'logged_in_at' => now()->subHour(),
        ]);
    }
}
