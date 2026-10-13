<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAuthRequest;
use App\Services\CustomerAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthController extends Controller
{
    public function __construct(private CustomerAuthService $auth) {}

    public function login(): View
    {
        return view('users.login', ['countries' => $this->auth->countries()]);
    }

    public function register(): View
    {
        return view('users.register', ['countries' => $this->auth->countries()]);
    }

    public function store(CustomerAuthRequest $request): RedirectResponse
    {
        try {
            $this->auth->register($request, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'These account details are already registered. Please log in.']);
        }

        return redirect()->route('customers.verification');
    }

    public function authenticate(CustomerAuthRequest $request): RedirectResponse
    {
        if (! $this->auth->login($request, $request->validated())) {
            return redirect()->route($request->session()->has('customer_verification') && ! $request->session()->has('customer_pending_login') ? 'customers.verification' : 'login');
        }

        return redirect()->intended(route('football.home'));
    }

    public function confirmLogin(CustomerAuthRequest $request): RedirectResponse
    {
        $this->auth->confirmLogin($request);

        return redirect()->intended(route('football.home'));
    }

    public function cancelLogin(Request $request): RedirectResponse
    {
        $request->session()->forget('customer_pending_login');

        return redirect()->route('login');
    }

    public function profile(Request $request): View
    {
        return view('users.profile', $this->auth->profile($request));
    }

    public function updateProfile(CustomerAuthRequest $request): RedirectResponse
    {
        try {
            $passwordChanged = $this->auth->updateProfile($request, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['username' => 'The account details are already in use. Please try another username or country.']);
        }
        if ($passwordChanged) {
            $this->auth->logout($request);

            return redirect()->route('login')->with('status', 'Profile and password updated. Please log in with your new password.');
        }

        return redirect()->route('customers.profile')->with('status', 'Your profile has been updated.');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $path = $request->user('customers')->avatar_path;
        abort_unless(is_string($path) && str_starts_with($path, 'customer-avatars/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function verification(Request $request): View|RedirectResponse
    {
        $customer = $this->auth->verificationCustomer($request);
        if ($customer === null) {
            return redirect()->route('login');
        }

        return view('users.register', ['isVerification' => true, 'customer' => $customer]);
    }

    public function verifyEmail(CustomerAuthRequest $request): RedirectResponse
    {
        $this->auth->verifyEmail($request, $request->validated('code'));

        return redirect()->route('login')->with('status', 'Email verified successfully. You can now log in.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $this->auth->sendVerification($request);

        return redirect()->route('customers.verification')->with('status', $request->session()->has('verification_delivery_error') ? 'Please retry sending your code.' : 'A new verification code has been sent.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()->route('login');
    }
}
