@extends('admin.layouts.main', ['active' => 'subscriptions'])

@section('title', 'Edit Subscription Plan')

@section('content')
    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 theme-bg-base">
        <div class="mx-auto max-w-4xl">

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="mt-2 text-3xl font-bold theme-text-main">
                        Edit Subscription Plan
                    </h1>
                    <p class="mt-2 text-sm theme-text-muted">
                        Update pricing, duration and status for BetFair Markets users.
                    </p>
                </div>

                <a href="{{ url('admin/subscriptions') }}"
                   class="rounded-2xl px-4 py-2 text-sm font-semibold theme-panel-subtle">
                    ← Back
                </a>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ url("admin/subscriptions/{$subscription->id}") }}" enctype="multipart/form-data" class="rounded-[2rem] p-6 space-y-6 betguru-card-elevated" x-data="{features: @js(old('features', json_decode($subscription->features, true) ?: [
            ['text' => '','translations' => [],'is_highlighted' => false]])),
            init() {
                    this.features = this.features.map((feature, index) => ({
                        _id: Date.now() + index,
                        text: feature.text ?? '',
                        translations: feature.translations ?? {},
                        is_highlighted:
                            feature.is_highlighted === true ||
                            feature.is_highlighted === 1 ||
                            feature.is_highlighted === '1'
                    }));
                },
                addFeature() {
                    this.features.push({
                        _id: Date.now() + Math.random(),
                        text: '',
                        translations: {},
                        is_highlighted: false
                    });
                },
                removeFeature(index) {
                    this.features.splice(index, 1);

                    if (this.features.length === 0) {
                        this.addFeature();
                    }
                }
            }">
                @csrf
                @method('PUT')
                <div>
                    <h2 class="text-lg font-bold theme-text-main">Basic Information</h2>
                    <p class="mt-1 text-sm theme-text-muted">Add the plan title, icon and short description displayed on the pricing card.</p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="betguru-label">Plan Label</label>
                        <input type="text" name="label" value="{{ old('label',$subscription->label) }}" placeholder="e.g. Monthly" class="betguru-input">
                        @error('label')
                            <p class="mt-1 text-xs theme-text-danger">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Plan Icon</label>
                        <input type="file" name="icon" value="{{ old('icon') }}" placeholder="e.g. 🚀" class="betguru-input">

                        @error('icon')
                            <p class="mt-1 text-xs theme-text-danger">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                        <div>
                            <label class="betguru-label">Plan Label ({{ $localeLabel }})</label>
                            <input type="text" name="label_translations[{{ $locale }}]" value="{{ data_get($subscription->label_translations, $locale) }}" placeholder="Translated plan label" class="betguru-input">
                            @error("label_translations.$locale")
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="betguru-label">Description</label>
                    <textarea name="description" rows="3" placeholder="e.g. Perfect balance between price and value." class="betguru-input resize-none">{{ old('description',$subscription->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs theme-text-danger">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                        <div>
                            <label class="betguru-label">Description ({{ $localeLabel }})</label>
                            <textarea name="description_translations[{{ $locale }}]" rows="3" placeholder="Translated description" class="betguru-input resize-none">{{ data_get(json_decode($subscription->description_translations), $locale) }}</textarea>
                            @error("description_translations.$locale")
                                <p class="mt-1 text-xs theme-text-danger">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="betguru-label">Is Popular?</label>
                    <select name="is_popular" class="betguru-select">
                        <option value="" disabled {{ old('is_popular') ? '' : 'selected' }}>Select duration</option>
                        <option value="1" {{ old('is_popular',$subscription->is_popular) == true ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_popular',$subscription->is_popular) == false ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                {{-- Plan Duration --}}
                <div class="border-t pt-6 theme-border">
                    <h2 class="text-lg font-bold theme-text-main">Plan Duration</h2>
                    <p class="mt-1 text-sm theme-text-muted">
                        Configure the subscription duration and billing interval.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1">
                    <div>
                        <label class="betguru-label">Duration</label>
                        <select name="duration_days" class="betguru-select">
                            <option value="" disabled {{ old('duration_days') ? '' : 'selected' }}>Select duration</option>
                            <option value="7" {{ old('duration_days',$subscription->duration_days) == 7 ? 'selected' : '' }}>Weekly - 7 Days</option>
                            <option value="30" {{ old('duration_days',$subscription->duration_days) == 30 ? 'selected' : '' }}>Monthly - 30 Days</option>
                            <option value="90" {{ old('duration_days',$subscription->duration_days) == 90 ? 'selected' : '' }}>Quarterly - 90 Days</option>
                        </select>
                        @error('duration_days')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="border-t pt-6 theme-border">
                    <h2 class="text-lg font-bold theme-text-main">Pricing</h2>
                    <p class="mt-1 text-sm theme-text-muted">
                        Configure separate prices for Georgian and international users.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="betguru-label">Price for Georgian Users</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold pointer-events-none theme-text-muted">GEL</span>
                            <input type="number" step="0.01" min="0" name="price_for_local" value="{{ old('price_for_local',$subscription->price_for_local) }}" placeholder="20.00" class="betguru-input betguru-pl-15">
                        </div>
                        <p class="mt-1 text-xs theme-text-muted">Shown and charged when the user's country is Georgia.</p>
                        @error('price_for_local')
                            <p class="mt-1 text-xs theme-text-danger">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Price for International Users</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold pointer-events-none theme-text-muted">EUR</span>
                            <input type="number" step="0.01" min="0" name="price_for_international" value="{{ old('price_for_international',$subscription->price_for_international) }}" placeholder="29.00" class="betguru-input betguru-pl-15">
                        </div>

                        <p class="mt-1 text-xs theme-text-muted">Shown and charged for users outside Georgia.</p>

                        @error('price_for_international')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Price Label</label>
                        <input type="text" name="price_label" value="{{ old('price_label',$subscription->price_label) }}" placeholder="e.g. per month" class="betguru-input">
                        @error('price_label')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Saving Label</label>
                        <input type="text" name="saving_label" value="{{ old('saving_label',$subscription->saving_label) }}" placeholder="e.g. 3 months • Save 18 €" class="betguru-input">
                        @error('saving_label')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                        <div>
                            <label class="betguru-label">Price Label ({{ $localeLabel }})</label>
                            <input type="text" name="price_label_translations[{{ $locale }}]" value="{{ data_get(json_decode($subscription->price_label_translations), $locale) }}" placeholder="Translated price label" class="betguru-input">
                            @error("price_label_translations.$locale")
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="betguru-label">Saving Label ({{ $localeLabel }})</label>
                            <input type="text" name="saving_label_translations[{{ $locale }}]" value="{{ data_get(json_decode($subscription->saving_label_translations), $locale) }}" placeholder="Translated saving label" class="betguru-input">
                            @error("saving_label_translations.$locale")
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Plan Features Repeater --}}
                <div class="border-t pt-6 theme-border">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold theme-text-main">Plan Features</h2>
                        </div>

                        <button type="button" @click="addFeature()" class="rounded-2xl px-4 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5 betguru-gradient-cta">
                            + Add Feature
                        </button>
                    </div>
                </div>

                @if ($errors->has('features') || $errors->has('features.*.text'))
                    <p class="text-xs theme-text-danger">
                        {{ $errors->first('features') ?: $errors->first('features.*.text') }}
                    </p>
                @endif

                <div class="space-y-4">
                    <template x-for="(feature, index) in features" :key="feature._id">
                        <div class="rounded-2xl p-5 theme-panel-subtle">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-bold theme-text-main">
                                    Feature <span x-text="index + 1"></span>
                                </h3>

                                <button type="button" @click="removeFeature(index)" class="rounded-xl px-3 py-2 text-xs font-semibold theme-text-danger">Remove</button>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="betguru-label">Feature Text</label>

                                    <input type="text"
                                        :name="`features[${index}][text]`"
                                        x-model="feature.text"
                                        placeholder="e.g. Full access"
                                        class="betguru-input">
                                </div>

                                <div>
                                    <label class="betguru-label">Feature Style</label>

                                    <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-2xl px-4 theme-bg-base">
                                        <input type="hidden"
                                            :name="`features[${index}][is_highlighted]`"
                                            value="0">

                                        <input type="checkbox"
                                            :name="`features[${index}][is_highlighted]`"
                                            value="1"
                                            x-model="feature.is_highlighted"
                                            class="h-4 w-4 rounded">

                                        <span class="text-sm theme-text-main">
                                            Highlight this feature
                                        </span>
                                    </label>
                                </div>

                                @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                                    <div>
                                        <label class="betguru-label">
                                            Feature Text ({{ $localeLabel }})
                                        </label>

                                        <input type="text"
                                            :name="`features[${index}][translations][{{ $locale }}]`"
                                            x-model="feature.translations['{{ $locale }}']"
                                            placeholder="Translated feature text"
                                            class="betguru-input">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </template>
                </div>
                {{-- <div class="space-y-4">
                    <template x-for="(feature, index) in features" :key="feature._id">
                        @foreach(json_decode($subscription->features) as $index => $feature)
                            @php $allFeatures = json_decode($subscription->features) @endphp
                            <div class="rounded-2xl p-5 theme-panel-subtle">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-bold theme-text-main">Feature <span x-text="index + 1"></span></h3>
                                    <button type="button" @click="removeFeature($index)" class="rounded-xl px-3 py-2 text-xs font-semibold theme-text-danger">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="betguru-label">Feature Text</label>
                                        <input type="text" value="{{ $feature->text }}" name="features[{{ $index }}][text]" placeholder="e.g. Full access" class="betguru-input">
                                    </div>
                                    <div>
                                        <label class="betguru-label">Feature Style</label>
                                        <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-2xl px-4 theme-bg-base">
                                            <input type="hidden" name="features[{{ $index }}][is_highlighted]" value="{{ $feature->is_highlighted }}">
                                            <input type="checkbox" name="features[{{ $index }}][is_highlighted]" {{ $feature->is_highlighted == 1 ? 'checked' : '' }} class="h-4 w-4 rounded">
                                            <span class="text-sm theme-text-main">Highlight this feature</span>
                                        </label>
                                        <p class="mt-1 text-xs theme-text-muted">Use this for features such as discounts or special benefits.</p>
                                    </div>

                                    @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                                        <div>
                                            <label class="betguru-label">Feature Text ({{ $localeLabel }})</label>
                                            <input type="text" value="{{ $feature->translations->$locale }}" name="features[{{ $index }}][translations][{{ $locale }}]" placeholder="Translated feature text" class="betguru-input">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </template>
                </div> --}}

                {{-- Button Configuration --}}
                <div class="border-t pt-6 theme-border">
                    <h2 class="text-lg font-bold theme-text-main">Button Configuration</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="betguru-label">Button Text</label>
                        <input type="text" name="button_text" value="{{ old('button_text', $subscription->button_text) }}" placeholder="e.g. Choose Plan" class="betguru-input">
                        @error('button_text')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    @foreach (['ka' => 'Georgian'] as $locale => $localeLabel)
                        <div>
                            <label class="betguru-label">Button Text ({{ $localeLabel }})</label>
                            <input type="text" name="button_text_translations[{{ $locale }}]" value="{{ data_get(json_decode($subscription->button_text_translations), $locale) }}" placeholder="Translated button text" class="betguru-input">
                            @error("button_text_translations.$locale")
                                <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Payment and General Settings --}}
                <div class="border-t pt-6 theme-border">
                    <h2 class="text-lg font-bold theme-text-main">
                        Payment and General Settings
                    </h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="betguru-label">PayPal Plan ID</label>
                        <input type="text" name="paypal_plan_id" value="{{ old('paypal_plan_id',$subscription->paypal_plan_id) }}" placeholder="PayPal Plan ID" class="betguru-input">
                        @error('paypal_plan_id')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Renewal Reminder Days Before</label>
                        <input type="number" min="0" name="renewal_reminder_days_before" value="{{ old('renewal_reminder_days_before',$subscription->renewal_reminder_days_before) }}" placeholder="e.g. 5" class="betguru-input">
                        @error('renewal_reminder_days_before')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="betguru-label">Status</label>

                        <select name="is_active" class="betguru-select">
                            <option value="1" {{ old('is_active', $subscription->is_active) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $subscription->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>

                        @error('is_active')
                            <p class="mt-1 text-xs theme-text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Pricing Information --}}
                <div class="rounded-2xl px-4 py-4 theme-primary-panel">
                    <p class="text-sm leading-6 theme-text-soft">
                        <strong>Pricing rule:</strong>
                        Georgian users will see and pay in <strong>GEL</strong>.
                        International users will see and pay in <strong>EUR</strong>.
                        Subscriptions renew automatically unless cancelled.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ url('admin/subscriptions') }}"
                        class="rounded-2xl px-5 py-3 text-sm font-semibold theme-panel-subtle">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-2xl px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 betguru-gradient-cta">Update Plan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
