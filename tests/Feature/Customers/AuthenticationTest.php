<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\CustomerLoginSession;
use Database\Factories\CustomerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{code: string, recipient: string}> */
    private array $messages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['services.infobip.key' => null, 'services.infobip.base_url' => null]);
        Http::preventStrayRequests();
        Mail::shouldReceive('raw')->andReturnUsing(function (string $text, callable $callback): void {
            $message = new Message(new Email);
            $callback($message);
            preg_match('/\b([0-9]{6})\b/', $text, $match);
            $this->messages[] = ['code' => $match[1] ?? '', 'recipient' => $message->getSymfonyMessage()->getTo()[0]->getAddress()];
        });
    }

    public function test_imported_forms_render_in_existing_layout(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot Password?')->assertSee('OTP login');
        $this->get('/register')->assertOk()->assertSee('name="country_code"', false);
        $this->get('/forgot-password')->assertOk()->assertSee('Send reset link');
    }

    public function test_registration_and_verification_use_existing_customer_schema(): void
    {
        $this->post('/register', $this->registration())->assertSessionHasNoErrors()->assertRedirect(route('customers.verification'));
        $customer = Customer::query()->sole();
        $this->assertFalse($customer->is_verified);
        $this->assertTrue(Hash::check('Secret@123', $customer->password));
        $this->assertSame('newcustomer@example.com', $this->messages[0]['recipient']);
        $this->assertStringNotContainsString($this->messages[0]['code'], $customer->code);
        $this->get('/verify-email')->assertOk()->assertSee('maxlength="6"', false);
        $this->post('/verify-email', ['code' => $this->messages[0]['code']])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue($customer->fresh()->is_verified);
        $this->assertGuest('customers');
    }

    public function test_invalid_registration_does_not_create_a_customer(): void
    {
        $this->post('/register', array_replace($this->registration(), ['country_code' => 'ZZ', 'password' => 'weak', 'terms' => 0]))
            ->assertSessionHasErrors(['country_code', 'password', 'terms']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_password_login_preserves_session_tracking_and_guard_isolation(): void
    {
        $customer = CustomerFactory::new()->create(['username' => 'customer', 'password' => 'Secret@123']);
        $this->post('/login', ['identity' => 'CUSTOMER', 'password' => 'Secret@123'])->assertRedirect(route('football.home'));
        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertGuest('web');
        $this->assertDatabaseHas('customers_login_sessions', ['customer_id' => $customer->id, 'logged_out_at' => null]);
        $this->assertSame(1, $customer->fresh()->login_count);
    }

    public function test_customer_logout_closes_the_tracked_session(): void
    {
        $customer = CustomerFactory::new()->create(['password' => 'Secret@123']);
        $this->post('/login', ['identity' => $customer->email, 'password' => 'Secret@123'])
            ->assertRedirect(route('football.home'));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest('customers');
        $this->assertNull($customer->fresh()->active_session_id);
        $this->assertNotNull(CustomerLoginSession::query()->sole()->logged_out_at);
    }

    public function test_password_login_routes_unverified_customers_to_verification(): void
    {
        $customer = CustomerFactory::new()->create(['is_verified' => false, 'password' => 'Secret@123']);
        $this->post('/login', ['identity' => $customer->email, 'password' => 'Secret@123'])
            ->assertRedirect(route('customers.verification'));
        $this->assertGuest('customers');
        $this->assertSame($customer->email, $this->messages[0]['recipient']);
    }

    public function test_password_login_requires_explicit_device_switch_confirmation(): void
    {
        $customer = CustomerFactory::new()->create(['active_session_id' => 'old-device', 'password' => 'Secret@123']);
        $this->recordSession($customer, 'old-device');
        $this->post('/login', ['identity' => $customer->email, 'password' => 'Secret@123'])->assertRedirect(route('login'));
        $this->assertGuest('customers');
        $this->get('/login')->assertOk()->assertSee('Log out previous device and continue');
        $this->post('/login/logout-previous-sessions', ['confirm' => 1])->assertRedirect(route('football.home'));
        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertNotNull(CustomerLoginSession::query()->where('session_id', 'old-device')->value('logged_out_at'));
    }

    public function test_email_fallback_otp_is_single_use_and_records_login(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->sendOtp($customer)->assertRedirect(route('login.mobile.verification'));
        $this->assertSame($customer->email, $this->messages[0]['recipient']);
        $this->assertSame('email', $customer->fresh()->login_channel_via);
        $this->assertStringNotContainsString($this->messages[0]['code'], $customer->fresh()->login_otp);
        $this->get('/login/mobile/verification')->assertOk()->assertSee('maxlength="6"', false);
        $this->post('/login/mobile/verify-code', ['code' => $this->messages[0]['code']])->assertRedirect(route('football.home'));
        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertNull($customer->fresh()->login_otp);
        $this->assertSame(1, $customer->fresh()->login_count);
    }

    public function test_otp_wrong_attempts_are_limited_and_expired_codes_fail(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->sendOtp($customer);
        $code = $this->messages[0]['code'];
        $wrong = $code === '111111' ? '222222' : '111111';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login/mobile/verify-code', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/login/mobile/verify-code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest('customers');
        $this->travel(11)->minutes();
        $this->get('/login/mobile/verification')->assertRedirect(route('login'));
    }

    public function test_otp_resend_has_cooldown_and_invalidates_the_previous_code(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->sendOtp($customer);
        $oldCode = $this->messages[0]['code'];
        $this->post('/login/mobile/resend-code')->assertSessionHasErrors('mobile');
        $this->assertNotNull(session('customer_login_otp'));
        $this->travel(61)->seconds();
        $this->post('/login/mobile/resend-code')->assertRedirect(route('login.mobile.verification'));
        $this->assertCount(2, $this->messages);
        if ($oldCode !== $this->messages[1]['code']) {
            $this->post('/login/mobile/verify-code', ['code' => $oldCode])->assertSessionHasErrors('code');
        }
        $this->post('/login/mobile/verify-code', ['code' => $this->messages[1]['code']])->assertRedirect(route('football.home'));
    }

    public function test_otp_login_keeps_device_switch_confirmation(): void
    {
        $customer = CustomerFactory::new()->create(['active_session_id' => 'old-device']);
        $this->recordSession($customer, 'old-device');
        $this->sendOtp($customer);
        $this->post('/login/mobile/verify-code', ['code' => $this->messages[0]['code']])->assertRedirect(route('login'));
        $this->assertGuest('customers');
        $this->post('/login/logout-previous-sessions', ['confirm' => 1])->assertRedirect(route('football.home'));
        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertSame('email', $customer->fresh()->login_channel_via);
    }

    public function test_sms_delivery_uses_configuration_and_enforces_sms_limit(): void
    {
        config(['services.infobip.key' => 'test-key', 'services.infobip.base_url' => 'https://sms.example.test']);
        Http::fake(['sms.example.test/*' => Http::response(['messages' => [['status' => ['groupId' => 1]]]])]);
        $customer = CustomerFactory::new()->create();
        $this->sendOtp($customer)->assertRedirect(route('login.mobile.verification'));
        $this->assertSame('mobile', $customer->fresh()->login_channel_via);
        $this->assertSame(1, $customer->fresh()->login_sms_count);
        $this->assertCount(0, $this->messages);
        Http::assertSentCount(1);
        $customer->forceFill(['login_sms_count' => 3])->save();
        $this->travel(61)->seconds();
        $this->sendOtp($customer)->assertRedirect(route('login.mobile.verification'));
        Http::assertSentCount(1);
        $this->assertSame('email', $customer->fresh()->login_channel_via);
        $this->assertSame($customer->email, $this->messages[0]['recipient']);
    }

    public function test_blocked_and_unverified_customers_cannot_bypass_verification(): void
    {
        $customer = CustomerFactory::new()->create(['is_blocked' => true]);
        $this->sendOtp($customer)->assertSessionHasErrors('mobile');
        $customer->forceFill(['is_blocked' => false, 'is_verified' => false])->save();
        $this->sendOtp($customer)->assertRedirect(route('customers.verification'));
        $this->assertGuest('customers');
        $this->assertNull($customer->fresh()->login_otp);
    }

    public function test_password_change_invalidates_outstanding_login_codes(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->sendOtp($customer);
        $customer->update(['password' => 'Different@123']);
        $this->post('/login/mobile/verify-code', ['code' => $this->messages[0]['code']])->assertSessionHasErrors('code');
        $this->assertGuest('customers');
    }

    /** @return array<string, string|int> */
    private function registration(): array
    {
        return ['name' => 'New', 'surname' => 'Customer', 'username' => 'newcustomer', 'email' => 'newcustomer@example.com',
            'password' => 'Secret@123', 'country_code' => 'GE', 'mobile' => '555123456', 'terms' => 1];
    }

    private function sendOtp(Customer $customer): TestResponse
    {
        return $this->post('/login/mobile/send-code', ['country_code' => $customer->country_code, 'mobile' => $customer->mobile]);
    }

    private function recordSession(Customer $customer, string $sessionId): void
    {
        CustomerLoginSession::query()->create(['customer_id' => $customer->id, 'session_id' => $sessionId, 'device_type' => 'desktop', 'logged_in_at' => now()->subHour()]);
    }
}
