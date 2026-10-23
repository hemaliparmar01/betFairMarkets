<?php

namespace Tests\Feature\Customers;

use App\Models\CustomerLoginSession;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{text: string, recipient: string}> */
    private array $messages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::shouldReceive('raw')->andReturnUsing(function (string $text, callable $callback): void {
            $message = new Message(new Email);
            $callback($message);
            $this->messages[] = ['text' => $text, 'recipient' => $message->getSymfonyMessage()->getTo()[0]->getAddress()];
        });
    }

    public function test_password_reset_pages_render_with_correct_form_actions(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee(route('customers.password.email'));
        $this->get('/reset-password/example-token?email=customer@example.com')->assertOk()
            ->assertSee('example-token')->assertSee('customer@example.com')->assertSee('password_confirmation');
    }

    public function test_forgot_password_sends_customer_link_and_keeps_generic_response(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $customer->email])
            ->assertRedirect('/forgot-password')->assertSessionHas('status');
        $this->assertSame($customer->email, $this->messages[0]['recipient']);
        $this->assertStringContainsString('/reset-password/', $this->messages[0]['text']);
        $status = session('status');
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status', $status);
        $this->assertCount(1, $this->messages);
        $customer->forceFill(['is_blocked' => true])->save();
        $this->post('/forgot-password', ['email' => $customer->email])->assertSessionHas('status', $status);
        $this->assertCount(1, $this->messages);
    }

    public function test_reset_changes_only_customer_password_and_closes_existing_sessions(): void
    {
        $customer = CustomerFactory::new()->create(['active_session_id' => 'previous-session', 'remember_token' => 'previous-token']);
        $admin = User::factory()->create(['email' => $customer->email, 'password' => 'Admin@12345']);
        CustomerLoginSession::query()->create([
            'customer_id' => $customer->id, 'session_id' => 'previous-session',
            'device_type' => 'desktop', 'logged_in_at' => now()->subHour(),
        ]);
        $token = Password::broker('customers')->createToken($customer);
        $payload = ['email' => $customer->email, 'token' => $token, 'password' => 'NewSecret@123', 'password_confirmation' => 'NewSecret@123'];
        $this->post('/reset-password', $payload)->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $customer->refresh();
        $this->assertTrue(Hash::check('NewSecret@123', $customer->password));
        $this->assertNull($customer->active_session_id);
        $this->assertNotSame('previous-token', $customer->remember_token);
        $this->assertNotNull(CustomerLoginSession::query()->sole()->logged_out_at);
        $this->assertTrue(Hash::check('Admin@12345', $admin->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->assertGuest('customers');
    }

    public function test_invalid_or_expired_reset_links_cannot_change_password(): void
    {
        $customer = CustomerFactory::new()->create();
        $original = $customer->password;
        $payload = ['email' => $customer->email, 'token' => 'invalid-token', 'password' => 'NewSecret@123', 'password_confirmation' => 'NewSecret@123'];
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $payload['token'] = Password::broker('customers')->createToken($customer);
        $this->travel(61)->minutes();
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->assertSame($original, $customer->fresh()->password);
    }

    public function test_reset_validation_and_blocked_account_protection(): void
    {
        $customer = CustomerFactory::new()->create();
        $token = Password::broker('customers')->createToken($customer);
        $this->post('/reset-password', ['email' => $customer->email, 'token' => $token, 'password' => 'short'])
            ->assertSessionHasErrors('password');
        $customer->forceFill(['is_suspended' => true])->save();
        $this->post('/reset-password', ['email' => $customer->email, 'token' => $token,
            'password' => 'NewSecret@123', 'password_confirmation' => 'NewSecret@123'])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_requests_are_throttled(): void
    {
        $customer = CustomerFactory::new()->create();
        $this->post('/forgot-password', ['email' => $customer->email])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => $customer->email])->assertSessionHas('status');
        $this->assertCount(1, $this->messages);
    }
}
