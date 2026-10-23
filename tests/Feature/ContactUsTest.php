<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactUsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);
        Http::preventStrayRequests();
    }

    public function test_guest_contact_page_preserves_source_fields_and_support_content(): void
    {
        $this->get('/contact-us')->assertOk()->assertViewIs('users.contactUs')
            ->assertSee('Send Us a Message')->assertSee('Contact Methods')->assertSee('Company Information')
            ->assertSee('office@betfairmarkets.com')->assertSee('https://wa.me/995511705508', false)
            ->assertSee('name="name"', false)->assertSee('name="email"', false)
            ->assertSee('name="subject"', false)->assertSee('name="message"', false)
            ->assertSee('name="_token"', false)->assertSee(route('contact-us.store'), false);
    }

    public function test_submission_requires_all_source_fields_and_preserves_old_input(): void
    {
        Mail::fake();
        $this->from('/contact-us')->post('/contact-us', ['name' => 'Contact User'])->assertRedirect('/contact-us')
            ->assertSessionHasErrors(['email', 'subject', 'message'])->assertSessionHasInput('name', 'Contact User');
        $this->from('/contact-us')->post('/contact-us', [])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_invalid_email_and_source_length_limits_are_rejected(): void
    {
        $this->from('/contact-us')->post('/contact-us', [
            'name' => str_repeat('a', 101), 'email' => 'invalid', 'subject' => str_repeat('b', 151), 'message' => 'Help',
        ])->assertSessionHasErrors(['name', 'email', 'subject']);
        $this->postJson('/contact-us', array_replace($this->submission(), ['email' => str_repeat('a', 145).'@example.com']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_guest_submission_is_saved_and_existing_email_notification_is_rendered(): void
    {
        $notifications = [];
        Event::listen(MessageSending::class, function (MessageSending $event) use (&$notifications): void {
            $notifications[] = $event->message;
        });
        $this->from('/contact-us')->post('/contact-us', array_replace($this->submission(), [
            'customer_id' => 987, 'status' => 'closed', 'name' => '  Contact User  ',
        ]))->assertRedirect('/contact-us')->assertSessionHasNoErrors()->assertSessionHas('success', 'Message sent successfully.');
        $this->assertDatabaseHas('contact_messages', array_merge($this->submission(), ['customer_id' => null, 'status' => 'new']));
        $this->assertCount(1, $notifications);
        $this->assertSame('office@betfairmarkets.com', $notifications[0]->getTo()[0]->getAddress());
        $this->assertSame('New Contact Message: Payment question', $notifications[0]->getSubject());
        $this->assertStringContainsString('Please help with my subscription.', $notifications[0]->getHtmlBody());
        $this->get('/contact-us')->assertSee('Message sent successfully.');
    }

    public function test_authenticated_customer_is_prefilled_and_linked_using_betfair_guard(): void
    {
        Mail::fake();
        $customer = CustomerFactory::new()->create(['password' => 'Secret@123']);
        $this->post('/login', ['identity' => $customer->email, 'password' => 'Secret@123'])->assertSessionHasNoErrors();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->actingAs($customer->fresh(), 'customers')->get('/contact-us')->assertOk()->assertSee($customer->email)->assertSee($customer->name);
        $this->from('/contact-us')->post('/contact-us', $this->submission())->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('contact_messages', ['customer_id' => $customer->id, 'subject' => 'Payment question']);
        $this->assertTrue(ContactMessage::query()->sole()->customer->is($customer));
    }

    public function test_email_failure_shows_error_and_retains_input_without_partial_submission(): void
    {
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Test mail transport failure'));
        $this->from('/contact-us')->post('/contact-us', $this->submission())->assertRedirect('/contact-us')
            ->assertSessionHas('error', 'Unable to send your message. Please try again.')
            ->assertSessionHasInput('message', 'Please help with my subscription.');
        $this->assertDatabaseCount('contact_messages', 0);
        $this->get('/contact-us')->assertSee('Unable to send your message. Please try again.');
    }

    public function test_contact_messages_require_admin_authentication(): void
    {
        $this->get('/admin/contact-messages')->assertRedirect(route('admin.logins.index'));
        $this->actingAs(CustomerFactory::new()->create(), 'customers')->get('/admin/contact-messages')
            ->assertRedirect(route('admin.logins.index'));
    }

    public function test_submitted_messages_appear_in_admin_listing_with_full_details(): void
    {
        Mail::fake();
        $this->from('/contact-us')->post('/contact-us', $this->submission())->assertSessionHas('success');
        $this->actingAs(User::factory()->create(), 'web')->get('/admin/contact-messages')->assertOk()
            ->assertViewIs('admin.contact-messages.index')->assertSee('Contact Messages')
            ->assertSee('Contact User')->assertSee('contact@example.com')->assertSee('Payment question')
            ->assertSee('Please help with my subscription.')->assertSee('Name')->assertSee('Email')->assertSee('Subject')->assertSee('Message')->assertSee('Date');
    }

    public function test_admin_search_covers_each_source_search_field_and_keeps_pagination_filters(): void
    {
        ContactMessage::factory()->create(['name' => 'Unique Name', 'email' => 'mailmatch@example.com',
            'subject' => 'Special subject', 'message' => 'Detailed inquiry']);
        ContactMessage::factory()->create(['name' => 'Other Customer', 'email' => 'other@example.com', 'subject' => 'Other', 'message' => 'Unrelated']);
        $this->actingAs(User::factory()->create(), 'web');
        foreach (['Unique Name', 'mailmatch', 'Special subject', 'Detailed inquiry'] as $search) {
            $this->get('/admin/contact-messages?'.http_build_query(['search' => $search]))->assertOk()
                ->assertSee('Unique Name')->assertDontSee('Other Customer');
        }
        ContactMessage::factory()->count(2)->create(['subject' => 'Special subject']);
        $this->get('/admin/contact-messages?search=Special&per_page=1')->assertOk()
            ->assertViewHas('contactMessages', fn ($messages): bool => $messages->total() === 3 && $messages->count() === 1)
            ->assertSee('search=Special', false)->assertSee('per_page=1', false);
        $this->get('/admin/contact-messages?search=absent')->assertOk()->assertSee('No contact messages found.');
    }

    public function test_admin_pagination_is_bounded_and_lists_latest_first(): void
    {
        ContactMessage::factory()->create();
        $last = ContactMessage::factory()->create();
        $this->actingAs(User::factory()->create(), 'web')->get('/admin/contact-messages?per_page=-10')->assertOk()
            ->assertViewHas('contactMessages', fn ($messages): bool => $messages->perPage() === 1 && $messages->first()->is($last));
        $this->get('/admin/contact-messages?per_page=999')->assertOk()
            ->assertViewHas('contactMessages', fn ($messages): bool => $messages->perPage() === 100 && $messages->total() === 2);
    }

    public function test_admin_and_email_output_escape_customer_content(): void
    {
        $message = ContactMessage::factory()->create(['message' => '<script>alert("contact")</script>']);
        $this->actingAs(User::factory()->create(), 'web')->get('/admin/contact-messages')->assertOk()
            ->assertSee($message->message)->assertDontSee($message->message, false);
        $html = view('users.emails.contact-message', ['contactMessage' => $message])->render();
        $this->assertStringContainsString(e($message->message), $html);
        $this->assertStringNotContainsString($message->message, $html);
    }

    /** @return array<string, string> */
    private function submission(): array
    {
        return ['name' => 'Contact User', 'email' => 'contact@example.com', 'subject' => 'Payment question', 'message' => 'Please help with my subscription.'];
    }
}
