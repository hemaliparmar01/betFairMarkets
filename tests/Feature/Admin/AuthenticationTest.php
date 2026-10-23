<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerLoginSession;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_and_customers_cannot_access_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.logins.index'));
        $this->getJson('/admin/dashboard')->assertUnauthorized();
        $customer = CustomerFactory::new()->create();
        $this->actingAs($customer, 'customers')->get('/admin/dashboard')
            ->assertRedirect(route('admin.logins.index'));
    }

    public function test_admin_login_validation_and_logout(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password']);
        $this->get('/admin/login')->assertOk()->assertSee('password');
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'secret-password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated('web');
        $this->assertGuest('customers');
        $this->post('/admin/logout')->assertRedirect(route('admin.logins.index'));
        $this->assertGuest('web');
    }

    public function test_admin_login_and_logout_preserve_customer_session_tracking(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password']);
        $this->withSession(['customer_marker' => 'keep']);
        $previousSessionId = session()->getId();
        $customer = CustomerFactory::new()->create(['active_session_id' => $previousSessionId]);
        CustomerLoginSession::query()->create([
            'customer_id' => $customer->id, 'session_id' => $previousSessionId,
            'device_type' => 'desktop', 'logged_in_at' => now(),
        ]);
        $this->withCookie(config('session.cookie'), $previousSessionId);
        $this->actingAs($customer, 'customers')->post('/admin/login', [
            'email' => 'admin@example.com', 'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated('web');
        $this->assertAuthenticated('customers');
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertSame(session()->getId(), $customer->fresh()->active_session_id);
        $this->assertDatabaseHas('customers_login_sessions', ['customer_id' => $customer->id, 'session_id' => session()->getId()]);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/my-profile')->assertOk();
        $this->post('/admin/logout')->assertRedirect(route('admin.logins.index'));
        $this->assertGuest('web');
        $this->assertAuthenticated('customers');
        $this->assertSame('keep', session('customer_marker'));
    }

    public function test_admin_seeder_creates_login_without_overwriting_existing_credentials(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->sole();
        $this->assertTrue(Hash::check('Admin@12345', $admin->password));
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'Admin@12345'])
            ->assertRedirect(route('admin.dashboard'));
        $admin->update(['password' => 'changed-password']);
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('changed-password', $admin->fresh()->password));
    }

    public function test_admin_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'unknown@example.com', 'password' => 'incorrect']);
        }
        $this->post('/admin/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])
            ->assertTooManyRequests();
    }
}
