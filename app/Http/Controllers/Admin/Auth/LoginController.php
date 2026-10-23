<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Customer;
use App\Models\CustomerLoginSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function loginView(): View|RedirectResponse
    {
        return Auth::guard('web')->check()
            ? redirect()->route('admin.dashboard')
            : view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $previousSessionId = $request->session()->getId();
        $customer = Auth::guard('customers')->user();

        if (! Auth::guard('web')->attempt($request->safe()->only(['email', 'password']), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Invalid admin credentials.']);
        }

        $request->session()->regenerate();

        if ($customer !== null) {
            DB::transaction(function () use ($customer, $previousSessionId, $request): void {
                Customer::query()->whereKey($customer->getAuthIdentifier())
                    ->where('active_session_id', $previousSessionId)
                    ->update(['active_session_id' => $request->session()->getId()]);
                CustomerLoginSession::query()->where('customer_id', $customer->getAuthIdentifier())
                    ->where('session_id', $previousSessionId)->whereNull('logged_out_at')
                    ->update(['session_id' => $request->session()->getId()]);
            });
            $customer->refresh();
        }

        return redirect()->route('admin.dashboard')->with('status', 'Login successful.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.logins.index')->with('status', 'Logout successful.');
    }
}
