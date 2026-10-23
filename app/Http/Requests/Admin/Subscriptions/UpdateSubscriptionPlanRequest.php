<?php

namespace App\Http\Requests\Admin\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'label_translations' => ['nullable', 'array'],
            'label_translations.*' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'is_popular' => ['required', 'boolean'],
            'description' => ['required', 'string', 'max:10000'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.*' => ['nullable', 'string', 'max:10000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'price_for_local' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'price_for_international' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'price_label' => ['required', 'string', 'max:255'],
            'price_label_translations' => ['nullable', 'array'],
            'price_label_translations.*' => ['nullable', 'string', 'max:255'],
            'saving_label' => ['nullable', 'string', 'max:255'],
            'saving_label_translations' => ['nullable', 'array'],
            'saving_label_translations.*' => ['nullable', 'string', 'max:255'],
            'features' => ['required', 'array', 'min:1', 'max:50'],
            'features.*.text' => ['required', 'string', 'max:1000'],
            'features.*.translations' => ['nullable', 'array'],
            'features.*.translations.*' => ['nullable', 'string', 'max:1000'],
            'features.*.is_highlighted' => ['sometimes', 'boolean'],
            'button_text' => ['required', 'string', 'max:255'],
            'button_text_translations' => ['nullable', 'array'],
            'button_text_translations.*' => ['nullable', 'string', 'max:255'],
            'renewal_reminder_days_before' => ['required', 'integer', 'min:0', 'max:3650'],
            'paypal_plan_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
