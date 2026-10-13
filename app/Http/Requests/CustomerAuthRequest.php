<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Services\CustomerAuthService;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! $this->routeIs('customers.profile.update') || $this->user('customers') !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CustomerAuthService $auth): array
    {
        if ($this->routeIs('customers.verification.verify')) {
            return ['code' => ['required', 'regex:/^[0-9]{6}$/']];
        }

        if ($this->routeIs('customers.profile.update')) {
            $customer = $this->user('customers');

            return [
                'name' => ['required', 'string', 'max:100'],
                'surname' => ['required', 'string', 'max:100'],
                'username' => ['prohibited'],
                'country_code' => ['required', Rule::in($auth->countries()->pluck('code')->all()), Rule::unique(Customer::class, 'country_code')->where('mobile', $customer->mobile)->ignore($customer->getAuthIdentifier())],
                'timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
                'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
                'email' => ['prohibited'],
                'mobile' => ['prohibited'],
                'mobile_code' => ['prohibited'],
                'current_password' => ['nullable', 'required_with:password', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
            ];
        }
        if ($this->routeIs('login.sessions.confirm')) {
            return ['confirm' => ['required', 'accepted']];
        }

        $rules = [
            'device_id' => ['nullable', 'uuid'],
            'fingerprint_hash' => ['nullable', 'regex:/^[a-f0-9]{64}$/'],
            'screen_resolution' => ['nullable', 'regex:/^\d{1,5}x\d{1,5}$/'],
            'webgl_vendor' => ['nullable', 'string', 'max:255'],
            'webgl_hash' => ['nullable', 'regex:/^[a-f0-9]{64}$/'],
            'fonts_hash' => ['nullable', 'regex:/^[a-f0-9]{64}$/'],
            'canvas_hash' => ['nullable', 'regex:/^[a-f0-9]{64}$/'],
        ];

        if ($this->routeIs('register.store')) {
            return $rules + [
                'name' => ['required', 'string', 'max:100'],
                'surname' => ['required', 'string', 'max:100'],
                'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_.-]+$/', Rule::unique(Customer::class, 'username')],
                'email' => ['required', 'email', 'max:254', Rule::unique(Customer::class, 'email')],
                'password' => ['required', 'string', 'max:255', Password::min(8)->letters()->numbers()->symbols()],
                'mobile' => ['required', 'regex:/^[0-9]{4,15}$/', Rule::unique(Customer::class, 'mobile')->where('country_code', $this->input('country_code'))],
                'country_code' => ['required', Rule::in($auth->countries()->pluck('code')->all())],
                'terms' => ['accepted'],
            ];
        }

        return $rules + [
            'identity' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['name', 'surname', 'username', 'email', 'identity', 'mobile', 'country_code'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $data[$field] = match ($field) {
                    'email', 'username', 'identity' => mb_strtolower($value),
                    'country_code' => strtoupper($value),
                    'mobile' => preg_replace('/[\s().-]+/', '', $value),
                    default => $value,
                };
            }
        }

        $this->merge($data);
    }
}
