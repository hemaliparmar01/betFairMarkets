@extends('users.layout.main')

@section('title', 'BF Markets Analytics | Subscription Plans & Pricing')
@section('meta_description', 'BF Markets Analytics subscription plans - Weekly, monthly, and quarterly plans for
    professional sports exchange data terminal.')

    @push('head')
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&family=Noto+Sans+Georgian:wght@100..900&display=swap"
            rel="stylesheet">
    @endpush

@push('head')
    @vite('resources/users/css/auth.css')
@endpush

@section('content')
<div class="customer-auth">
    <div class="min-h-screen bg-[rgb(var(--bg-soft))] font-['Noto_Sans_Georgian',Inter,sans-serif] text-[rgb(var(--text-main))]">

        <main class="max-w-5xl mx-auto px-5 py-10 md:py-14">
            @php $current_country = (auth('customers')->user()?->country_code === 'GE' ? 'Georgia' : 'International'); @endphp
            <div>
                <div class="text-center mb-8">
                    <div class="inline-block bg-[rgb(var(--primary)_/_0.10)] bg-opacity-10 rounded-full px-4 py-1.5 mb-4">
                        @if ($current_country == 'Georgia')
                            <span class="text-[rgb(var(--primary))] font-bold text-sm"> {{ __('Georgia (GEL)') }}</span>
                        @else
                            <span class="text-[rgb(var(--primary))] font-bold text-sm">🌍 {{ __('International (EUR)') }}</span>
                        @endif
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-[rgb(var(--text-main))] mb-3">{{ __('Choose the Plan That Fits Your Trading Needs') }}</h1>
                    <p class="text-[rgb(var(--text-soft))] text-lg max-w-3xl mx-auto">{{ __('Get real-time market data, advanced analytics, and professional tools designed for serious traders.') }}</p>
                    <p class="mt-4 text-lg font-bold text-[rgb(var(--secondary))]">
                        @if ($current_country == 'Georgia')
                            სრული Betfair ანალიტიკური პლატფორმა — დღეში დაახლოებით 2 ლარად.
                        @else
                            Complete Betfair analytics platform for less than €1 per day.
                        @endif
                    </p>
                </div>

                <!-- Pricing Cards - English -->
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    @foreach ($plans as $plan)
                        @php $locale = app()->getLocale(); @endphp
                        @if ($locale === 'en')
                            @php
                                $planLabel = $plan->label;
                                $planDescription = $plan->description;
                                $priceLabel = $plan->price_label;
                                if($plan->saving_label != null) {
                                    $priceLabel .= " • ".$plan->saving_label;
                                }
                                $popularLabel = 'Most Popular';
                                $buttonText = $plan->button_text;
                            @endphp
                        @else
                            @php
                                $planLabel = data_get($plan->label_translations, $locale);
                                $planDescription = data_get(json_decode($plan->description_translations), $locale);
                                $priceLabel = data_get(json_decode($plan->price_label_translations), $locale);
                                if(data_get(json_decode($plan->saving_label_translations), $locale) != null) {
                                    $priceLabel .= " • ".data_get(json_decode($plan->saving_label_translations), $locale);
                                }
                                $popularLabel = __('Most Popular');
                                $buttonText = data_get(json_decode($plan->button_text_translations), $locale);
                            @endphp
                        @endif
                        <div
                            class="bg-[rgb(var(--surface))] rounded-2xl overflow-hidden flex flex-col transition-all duration-300 {{ $plan->is_popular == 1
                                ? 'shadow-lg border-2 border-[rgb(var(--primary))] scale-105 relative hover:-translate-y-1 hover:shadow-[0_20px_35px_-12px_rgb(var(--shadow-primary)_/_0.2)] hover:border-[rgb(var(--primary)_/_0.25)]'
                                : 'shadow-md border border-[rgb(var(--border))] hover:-translate-y-4 hover:border-[rgb(var(--primary)_/_0.25)] hover:shadow-[0_20px_35px_-12px_rgb(var(--shadow-primary)_/_0.2)]' }}">

                            @if ($plan->is_popular)
                                <div class="absolute top-0 right-0 bg-[rgb(var(--primary))] text-[rgb(var(--text-main))] text-xs font-bold px-3 py-1 rounded-bl-lg">🔥 {{ $popularLabel }}</div>
                            @endif

                            <div class="p-6 text-center flex-1 flex flex-col">
                                <div class="text-4xl mb-2 m-auto"><img src="{{ asset('storage/' . $plan->icon) }}" alt="Subscription Icon"></div>

                                <h3 class="text-xl font-bold text-[rgb(var(--text-main))] mb-1">{{ $planLabel }}</h3>

                                <p class="text-sm text-[rgb(var(--text-muted))] mb-3">{{ $planDescription }}</p>

                                <div class="text-3xl font-bold text-[rgb(var(--primary))] my-3">
                                    @if ($current_country == 'Georgia')
                                        {{ $plan->price_for_local }} ₾
                                    @else
                                        {{ $plan->price_for_international }} €
                                    @endif
                                </div>

                                <p class="text-[rgb(var(--text-muted))] text-sm">{{ $priceLabel }}</p>
                                <hr class="my-4">
                                <ul class="text-left space-y-2 text-sm text-[rgb(var(--text-soft))] flex-1">
                                    @foreach (json_decode($plan->features) as $item)
                                        @if ($locale === 'en')
                                            @php
                                                $featureText = $item->text;
                                            @endphp
                                        @else
                                            @php
                                                $featureText = data_get($item->translations, $locale);
                                            @endphp
                                        @endif

                                        <li class="flex items-center gap-2 {{ $item->is_highlighted == 1 ? 'text-[rgb(var(--primary))] font-semibold' : '' }}"> ✓ {{ $featureText }}</li>
                                    @endforeach
                                </ul>

                                <div class="mt-5">
                                    <a href="{{ route('checkout-single', ['plan' => $plan->id]) }}" class="w-full rounded-full bg-[rgb(var(--primary))] px-5 py-2.5 text-center text-sm font-bold text-[rgb(var(--text-main))] shadow-[0_4px_12px_rgb(var(--shadow-primary)_/_0.25)] transition-all duration-200 hover:scale-[1.02] hover:bg-[rgb(var(--primary-strong))] hover:shadow-[0_8px_20px_rgb(var(--shadow-primary)_/_0.35)]">
                                        {{ $buttonText }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Trial Banner -->
                <div
                    class="mb-8 rounded-xl bg-gradient-to-br from-[rgb(var(--primary))] to-[rgb(var(--secondary))] p-4 text-center text-[rgb(var(--text-main))] shadow-md">
                    <p class="font-bold text-[rgb(var(--text-main))] text-lg">✨ {{ __('7-Day Free Trial') }}</p>
                    <p class="text-[rgb(var(--text-main))] text-sm opacity-90">{{ __('No payment required. No card required. Full access for 7 days.') }}</p>
                </div>

                <!-- Trust Indicators - Horizontal -->
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div
                        class="flex items-center gap-2.5 rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-6 py-2.5 shadow-[0_2px_8px_rgb(var(--shadow-soft)_/_0.05)] [&>span:first-child]:text-[22px] [&>span:last-child]:whitespace-nowrap [&>span:last-child]:text-[13px] [&>span:last-child]:font-semibold [&>span:last-child]:text-[rgb(var(--text-soft))]">
                        <span>🔒</span>
                        <span>{{ __('Secure Payments') }}</span>
                    </div>
                    <div
                        class="flex items-center gap-2.5 rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-6 py-2.5 shadow-[0_2px_8px_rgb(var(--shadow-soft)_/_0.05)] [&>span:first-child]:text-[22px] [&>span:last-child]:whitespace-nowrap [&>span:last-child]:text-[13px] [&>span:last-child]:font-semibold [&>span:last-child]:text-[rgb(var(--text-soft))]">
                        <span>💳</span>
                        <span>{{ __('Visa, Mastercard, PayPal') }}</span>
                    </div>
                    <div
                        class="flex items-center gap-2.5 rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-6 py-2.5 shadow-[0_2px_8px_rgb(var(--shadow-soft)_/_0.05)] [&>span:first-child]:text-[22px] [&>span:last-child]:whitespace-nowrap [&>span:last-child]:text-[13px] [&>span:last-child]:font-semibold [&>span:last-child]:text-[rgb(var(--text-soft))]">
                        <span>⚡</span>
                        <span>{{ __('Instant Access') }}</span>
                    </div>
                </div>

                <!-- Two Column Grid -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-[rgb(var(--surface))] rounded-xl p-5 border border-[rgb(var(--border))] shadow-sm">
                        <p class="font-bold text-[rgb(var(--text-main))] mb-3 text-lg">🔄 {{ __('Features & Access') }}</p>
                        <ul class="space-y-2 text-[rgb(var(--text-soft))] [&_li]:flex [&_li]:items-center [&_li]:gap-2">
                            <li>✔ {{ __('7-day free trial') }}</li>
                            <li>✔ {{ __('Real-time odds & liquidity tracking') }}</li>
                            <li>✔ {{ __('Advanced charts & filters') }}</li>
                            <li>✔ {{ __('Multi-market monitoring') }}</li>
                        </ul>
                    </div>
                    <div class="bg-[rgb(var(--surface))] rounded-xl p-5 border border-[rgb(var(--border))] shadow-sm">
                        <p class="font-bold text-[rgb(var(--text-main))] mb-3 text-lg">🔓 {{ __('Free Access') }}</p>
                        <ul class="space-y-2 text-[rgb(var(--text-soft))] [&_li]:flex [&_li]:items-center [&_li]:gap-2">
                            <li>✔ {{ __('View up to 10 matches before registration') }}</li>
                            <li>✔ {{ __('Experience the platform before subscribing') }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Subscription Terms - Clean List -->
                <div class="bg-[rgb(var(--surface))] rounded-xl p-5 mb-8 border border-[rgb(var(--border))] shadow-sm">
                    <p class="font-bold text-[rgb(var(--text-main))] mb-3 text-lg">🔁 {{ __('Subscription Terms') }}</p>
                    <ul class="space-y-1.5 text-[rgb(var(--text-soft))] [&_li]:flex [&_li]:items-center [&_li]:gap-2">
                        <li>• {{ __('Automatic renewal') }}</li>
                        <li>• {{ __('Cancel anytime') }}</li>
                        <li>• {{ __('Access remains active until the end of your billing period') }}</li>
                    </ul>
                </div>

                <!-- Footer Message -->
                <div class="text-center text-[rgb(var(--text-soft))] italic border-t pt-6 mt-4">
                    <p>👉 {{ __('Built for serious traders — not for casual users.') }}</p>
                    <p>👉 {{ __('Only data. Only speed. Only edge.') }}</p>
                    <p>👉 {{ __('Stay ahead of the market.') }}</p>
                </div>
            </div>

        </main>
    </div>
</div>
@endsection
