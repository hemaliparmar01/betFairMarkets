<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPasswordService
{
    public function __construct(private CustomerRepository $customers) {}

    public function sendResetLink(string $email): void
    {
        Password::broker('customers')->sendResetLink([
            'email' => $email, 'is_blocked' => false, 'is_suspended' => false,
        ], function (Customer $customer, string $token): void {
            $url = rtrim(config('app.url'), '/').route('customers.password.reset', ['token' => $token, 'email' => $customer->email], false);
            Mail::raw("Reset your BF Markets password: {$url}\n\nThis link expires in 60 minutes. If you did not request it, ignore this email.", function (Message $message) use ($customer): void {
                $message->to($customer->email)->subject('Reset your BF Markets password');
            });
        });
    }

    /** @param array{email: string, token: string, password: string, password_confirmation: string} $data */
    public function reset(array $data): void
    {
        $status = Password::broker('customers')->reset($data + ['is_blocked' => false, 'is_suspended' => false], function (Customer $customer, string $password): void {
            DB::transaction(function () use ($customer, $password): void {
                $stored = $this->customers->find((int) $customer->id, true);
                if ($stored === null || $stored->is_blocked || $stored->is_suspended) {
                    throw ValidationException::withMessages(['email' => 'This reset link is invalid or expired.']);
                }
                foreach ($stored->loginSessions()->whereNull('logged_out_at')->pluck('session_id') as $sessionId) {
                    $this->customers->closeSession((int) $stored->id, $sessionId);
                }
                $this->customers->update((int) $stored->id, [
                    'password' => Hash::make($password), 'remember_token' => Str::random(60),
                    'active_session_id' => null, 'login_otp' => null,
                ]);
            });
            event(new PasswordReset($customer));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or expired.']);
        }
    }
}
