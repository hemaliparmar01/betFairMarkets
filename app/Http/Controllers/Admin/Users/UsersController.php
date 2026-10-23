<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscriptions;
use App\Services\Admin\UserManagementService;
use App\Services\CustomerAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class UsersController extends Controller
{
    public function index(Request $request, UserManagementService $userManagementService): View
    {
        $customers = $userManagementService->getAll($request->all());
        $getState = $userManagementService->getStats();

        return view('admin.users.index', compact('customers', 'getState'));
    }

    public function create(CustomerAuthService $auth): View
    {
        return view('admin.users.create', ['countries' => $auth->countries(),
            'plans' => Subscriptions::query()->where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function store(Request $request, UserManagementService $users, CustomerAuthService $auth): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email'))),
            'username' => mb_strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'surname' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('customers', 'username')],
            'email' => ['required', 'email', 'max:254', Rule::unique('customers', 'email')],
            'password' => ['required', 'confirmed', 'string', 'max:255', Password::min(8)->letters()->numbers()->symbols()],
            'country_code' => ['required', Rule::in($auth->countries()->pluck('code')->all())],
            'mobile' => ['required', 'regex:/^[0-9]{4,15}$/', Rule::unique('customers', 'mobile')->where('country_code', $request->input('country_code'))],
        ] + $this->subscriptionRules($request, false));
        $country = $auth->countries()->firstWhere('code', $data['country_code']);
        $data['country'] = $country['name'];
        $data['mobile_code'] = $country['calling_code'];
        $customer = $users->create($data);

        return redirect()->route('admin.users.edit', $customer)->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer, UserManagementService $userManagementService): View
    {
        $data = $userManagementService->findById($customer);

        return view('admin.users.edit', $data);
    }

    public function update(Request $request, Customer $customer, UserManagementService $userManagementService): RedirectResponse
    {
        $section = (string) $request->input('section', 'profile');

        $validated = $section === 'subscription'
            ? $request->validate(['section' => ['required', 'in:subscription']] + $this->subscriptionRules($request))
            : $request->validate([
                'section' => ['required', 'in:profile'],
                'name' => ['required', 'string', 'max:100'],
                'surname' => ['required', 'string', 'max:100'],
                'username' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('customers', 'username')->ignore($customer->id),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:254',
                    Rule::unique('customers', 'email')->ignore($customer->id),
                ],
                'mobile' => [
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('customers', 'mobile')->where('country_code', $customer->country_code)->ignore($customer->id),
                ],
                'country' => ['required', 'string', 'max:100'],
                'is_blocked' => ['required', 'boolean'],
                'is_suspended' => ['required', 'boolean'],
                'trial_ends_at' => ['nullable', 'date'],
            ]);

        try {
            $userManagementService->update($customer, $validated);

            return redirect()
                ->route('admin.users.edit', $customer)
                ->with('success', 'User updated successfully.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Admin user update failed.', [
                'customer_id' => $customer->id,
                'section' => $section,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong while updating the user.');
        }
    }

    private function subscriptionRules(Request $request, bool $requirePlan = true): array
    {
        return [
            'subscription_id' => [Rule::requiredIf($requirePlan && (int) $request->input('admin_free_days') === 0 && (int) $request->input('add_active_days') === 0), 'nullable', 'integer', Rule::exists('subscriptions', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'subscription_status' => [$requirePlan ? 'required' : 'sometimes', 'string', 'in:active,pending,cancelled,expired'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'admin_free_days' => ['nullable', 'integer', 'min:-1', 'max:3650', Rule::prohibitedIf((int) $request->input('add_active_days') > 0 && (int) $request->input('admin_free_days') !== 0)],
            'add_active_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
