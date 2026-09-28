@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')
<div class="app dark:[background:#1a2a38] dark:[border-color:#2a3f50] [max-width:1480px] [margin:0_auto] [background:white] [border-radius:32px] [box-shadow:0_16px_48px_rgba(0,_20,_40,_0.06)] [padding:20px_28px_32px] [transition:background_0.3s] max-[800px]:[padding:14px] [width:100%] [max-width:100%] [border-radius:24px] [padding:18px_24px_24px] max-[768px]:[padding:14px_14px_20px] max-[768px]:[border-radius:16px]">
    <div class="content-wrap w-full [max-width:1400px] [margin:0_auto]">

        <!-- ===== PAGE HEADER ===== -->
        <div class="subscription-page-header [margin:32px_0_8px] text-center">
            <div class="subscription-page-badge inline-block [background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [color:#1a6b9c] [padding:6px_18px] [border-radius:30px] [font-size:13px] font-bold [margin-bottom:16px] [transition:0.3s]">🌍 {{ __('Georgia (GEL)') }}</div>
            <h1 class="subscription-page-title max-[480px]:text-[24px] [font-size:34px] font-extrabold [color:#0b2a40] [letter-spacing:-0.5px] [line-height:1.25] [max-width:900px] [margin:0_auto_12px] [transition:color_0.3s]">{{ __('Choose the plan that fits you best and get full access to professional market analytics.') }}</h1>
            <p class="subscription-page-subtitle max-[480px]:text-sm [font-size:16px] [color:#5a7d99] [max-width:800px] [margin:0_auto_12px] [transition:color_0.3s]">{{ __('Get real-time market data, advanced analytics, and professional tools built for serious traders.') }}</p>
            <p class="subscription-page-highlight [font-size:17px] font-bold [color:#1f9a6e] [margin-top:8px] [transition:color_0.3s]">✨ {{ __('Full Betfair analytics platform — for about 2 GEL per day.') }}</p>
        </div>

        <!-- ===== PRICING GRID ===== -->
        <div class="pricing-grid grid [grid-template-columns:repeat(3,_1fr)] [gap:24px] [margin:40px_0_32px] [align-items:stretch] max-[950px]:[grid-template-columns:1fr] max-[950px]:[max-width:500px] max-[950px]:[margin-left:auto] max-[950px]:[margin-right:auto]">

            <!-- WEEKLY -->
            <div class="pricing-card bg-white [border:1px_solid_#e6eff8] [border-radius:24px] [padding:32px_28px_28px] flex flex-col relative [transition:transform_0.3s,_box-shadow_0.3s,_border-color_0.3s,_background_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[transform:translateY(-6px)] [&:hover]:[box-shadow:0_20px_40px_rgba(26,_107,_156,_0.12)] [&:hover]:[border-color:#b8d4ec] dark:[&:hover]:[box-shadow:0_20px_40px_rgba(0,_0,_0,_0.3)] dark:[&:hover]:[border-color:#4a8ab5] [&.featured]:[border:2px_solid_#1a6b9c] [&.featured]:[box-shadow:0_12px_32px_rgba(26,_107,_156,_0.15)] [&.featured]:[transform:scale(1.03)] dark:[&.featured]:[border-color:#4a8ab5] dark:[&.featured]:[box-shadow:0_12px_32px_rgba(74,_138,_181,_0.2)] [&.featured:hover]:[transform:scale(1.03)_translateY(-6px)] [&.featured_.plan-icon]:[background:linear-gradient(135deg,_#d4e8f8,_#b8d4ec)] dark:[&.featured_.plan-icon]:[background:linear-gradient(135deg,_#1f3444,_#2a4a5e)] max-[1024px]:[&.featured]:[transform:scale(1)] max-[1024px]:[&.featured:hover]:[transform:translateY(-6px)] max-[768px]:[padding:26px_22px_22px] max-[480px]:[padding:24px_18px_20px]">
                <div class="plan-icon [width:64px] [height:64px] [border-radius:18px] flex items-center justify-center [font-size:30px] [margin:0_auto_16px] [background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)]">📅</div>
                <h3 class="plan-name [font-size:22px] font-extrabold [color:#0b2a40] text-center [margin-bottom:6px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:19px]">{{ __('Weekly') }}</h3>
                <p class="plan-desc [font-size:13.5px] [color:#5a7d99] text-center [margin-bottom:20px] [min-height:40px] [transition:color_0.3s] dark:[color:#8aaccc]">{{ __('Perfect for getting to know the platform and short-term use.') }}</p>

                <div class="plan-price text-center [margin-bottom:4px] [&_.amount]:[font-size:38px] [&_.amount]:[font-weight:800] [&_.amount]:[color:#1a6b9c] [&_.amount]:[transition:color_0.3s] dark:[&_.amount]:[color:#6aafdf] [&_.currency]:[font-size:22px] [&_.currency]:[font-weight:700] [&_.currency]:[color:#1a6b9c] [&_.currency]:[margin-left:2px] dark:[&_.currency]:[color:#6aafdf] max-[768px]:[&_.amount]:[font-size:32px] max-[480px]:[&_.amount]:[font-size:28px]">
                    <span class="amount">20.00</span><span class="currency">₾</span>
                </div>
                <p class="plan-period text-center [font-size:13px] [color:#8aaccc] [margin-bottom:20px] [transition:color_0.3s] dark:[color:#5a7d99]">{{ __('per week') }}</p>

                <div class="plan-divider h-px [background:#e6eff8] [margin:20px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

                <ul class="plan-features list-none p-0 [margin:0_0_24px] [flex:1] [&_li]:[display:flex] [&_li]:[align-items:center] [&_li]:[gap:10px] [&_li]:[padding:7px_0] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[transition:color_0.3s] dark:[&_li]:[color:#b0c8dd] [&_li_i]:[color:#1f9a6e] [&_li_i]:[font-size:14px] [&_li_i]:[flex-shrink:0] dark:[&_li_i]:[color:#5ab88a] [&_li.highlight]:[color:#1a6b9c] [&_li.highlight]:[font-weight:700] dark:[&_li.highlight]:[color:#6aafdf]">
                    <li><i class="fas fa-check-circle"></i> {{ __('Full access') }}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('Real-time data') }}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('All features') }}</li>
                </ul>

                <a href="{{ route('checkout-single') }}" class="plan-btn outline inline-flex items-center justify-center [gap:8px] w-full [padding:14px_24px] [border-radius:60px] [font-size:15px] font-bold [text-decoration:none] [transition:transform_0.2s,_box-shadow_0.2s,_background_0.3s] cursor-pointer border-0 [font-family:inherit] [&.primary]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&.primary]:[color:white] [&.primary:hover]:[transform:translateY(-2px)] [&.primary:hover]:[box-shadow:0_10px_24px_rgba(26,_107,_156,_0.35)] dark:[&.primary]:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&.outline]:[background:white] [&.outline]:[color:#0b2a40] [&.outline]:[border:2px_solid_#d4e0ec] [&.outline:hover]:[border-color:#1a6b9c] [&.outline:hover]:[color:#1a6b9c] [&.outline:hover]:[transform:translateY(-2px)] dark:[&.outline]:[background:#1a2a38] dark:[&.outline]:[color:#e8edf2] dark:[&.outline]:[border-color:#3a5568] dark:[&.outline:hover]:[border-color:#4a8ab5] dark:[&.outline:hover]:[color:#6aafdf]">
                    <i class="fas fa-arrow-right"></i>
                    {{ __('Choose Plan') }}
                </a>
            </div>

            <!-- MONTHLY (FEATURED) -->
            <div class="pricing-card featured bg-white [border:1px_solid_#e6eff8] [border-radius:24px] [padding:32px_28px_28px] flex flex-col relative [transition:transform_0.3s,_box-shadow_0.3s,_border-color_0.3s,_background_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[transform:translateY(-6px)] [&:hover]:[box-shadow:0_20px_40px_rgba(26,_107,_156,_0.12)] [&:hover]:[border-color:#b8d4ec] dark:[&:hover]:[box-shadow:0_20px_40px_rgba(0,_0,_0,_0.3)] dark:[&:hover]:[border-color:#4a8ab5] [&.featured]:[border:2px_solid_#1a6b9c] [&.featured]:[box-shadow:0_12px_32px_rgba(26,_107,_156,_0.15)] [&.featured]:[transform:scale(1.03)] dark:[&.featured]:[border-color:#4a8ab5] dark:[&.featured]:[box-shadow:0_12px_32px_rgba(74,_138,_181,_0.2)] [&.featured:hover]:[transform:scale(1.03)_translateY(-6px)] [&.featured_.plan-icon]:[background:linear-gradient(135deg,_#d4e8f8,_#b8d4ec)] dark:[&.featured_.plan-icon]:[background:linear-gradient(135deg,_#1f3444,_#2a4a5e)] max-[1024px]:[&.featured]:[transform:scale(1)] max-[1024px]:[&.featured:hover]:[transform:translateY(-6px)] max-[768px]:[padding:26px_22px_22px] max-[480px]:[padding:24px_18px_20px]">
                <div class="popular-badge absolute [top:-14px] [left:50%] [transform:translateX(-50%)] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [padding:6px_20px] [border-radius:30px] [font-size:12px] font-bold whitespace-nowrap [box-shadow:0_4px_12px_rgba(26,_107,_156,_0.3)] dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)]">🔥 {{ __('Most Popular') }}</div>
                <div class="plan-icon [width:64px] [height:64px] [border-radius:18px] flex items-center justify-center [font-size:30px] [margin:0_auto_16px] [background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)]">⭐</div>
                <h3 class="plan-name [font-size:22px] font-extrabold [color:#0b2a40] text-center [margin-bottom:6px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:19px]">{{ __('Monthly') }}</h3>
                <p class="plan-desc [font-size:13.5px] [color:#5a7d99] text-center [margin-bottom:20px] [min-height:40px] [transition:color_0.3s] dark:[color:#8aaccc]">{{ __('The best balance between price and features.') }}</p>

                <div class="plan-price text-center [margin-bottom:4px] [&_.amount]:[font-size:38px] [&_.amount]:[font-weight:800] [&_.amount]:[color:#1a6b9c] [&_.amount]:[transition:color_0.3s] dark:[&_.amount]:[color:#6aafdf] [&_.currency]:[font-size:22px] [&_.currency]:[font-weight:700] [&_.currency]:[color:#1a6b9c] [&_.currency]:[margin-left:2px] dark:[&_.currency]:[color:#6aafdf] max-[768px]:[&_.amount]:[font-size:32px] max-[480px]:[&_.amount]:[font-size:28px]">
                    <span class="amount">65.00</span><span class="currency">₾</span>
                </div>
                <p class="plan-period text-center [font-size:13px] [color:#8aaccc] [margin-bottom:20px] [transition:color_0.3s] dark:[color:#5a7d99]">{{ __('per month') }}</p>

                <div class="plan-divider h-px [background:#e6eff8] [margin:20px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

                <ul class="plan-features list-none p-0 [margin:0_0_24px] [flex:1] [&_li]:[display:flex] [&_li]:[align-items:center] [&_li]:[gap:10px] [&_li]:[padding:7px_0] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[transition:color_0.3s] dark:[&_li]:[color:#b0c8dd] [&_li_i]:[color:#1f9a6e] [&_li_i]:[font-size:14px] [&_li_i]:[flex-shrink:0] dark:[&_li_i]:[color:#5ab88a] [&_li.highlight]:[color:#1a6b9c] [&_li.highlight]:[font-weight:700] dark:[&_li.highlight]:[color:#6aafdf]">
                    <li><i class="fas fa-check-circle"></i> {{ __('Full access')}}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('Real-time data')}}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('All features')}}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('Priority support')}}</li>
                </ul>

                <a href="{{ route('checkout-single') }}" class="plan-btn primary inline-flex items-center justify-center [gap:8px] w-full [padding:14px_24px] [border-radius:60px] [font-size:15px] font-bold [text-decoration:none] [transition:transform_0.2s,_box-shadow_0.2s,_background_0.3s] cursor-pointer border-0 [font-family:inherit] [&.primary]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&.primary]:[color:white] [&.primary:hover]:[transform:translateY(-2px)] [&.primary:hover]:[box-shadow:0_10px_24px_rgba(26,_107,_156,_0.35)] dark:[&.primary]:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&.outline]:[background:white] [&.outline]:[color:#0b2a40] [&.outline]:[border:2px_solid_#d4e0ec] [&.outline:hover]:[border-color:#1a6b9c] [&.outline:hover]:[color:#1a6b9c] [&.outline:hover]:[transform:translateY(-2px)] dark:[&.outline]:[background:#1a2a38] dark:[&.outline]:[color:#e8edf2] dark:[&.outline]:[border-color:#3a5568] dark:[&.outline:hover]:[border-color:#4a8ab5] dark:[&.outline:hover]:[color:#6aafdf]">
                    <i class="fas fa-arrow-right"></i>
                    {{ __('Choose Plan') }}
                </a>
            </div>

            <!-- QUARTERLY -->
            <div class="pricing-card bg-white [border:1px_solid_#e6eff8] [border-radius:24px] [padding:32px_28px_28px] flex flex-col relative [transition:transform_0.3s,_box-shadow_0.3s,_border-color_0.3s,_background_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[transform:translateY(-6px)] [&:hover]:[box-shadow:0_20px_40px_rgba(26,_107,_156,_0.12)] [&:hover]:[border-color:#b8d4ec] dark:[&:hover]:[box-shadow:0_20px_40px_rgba(0,_0,_0,_0.3)] dark:[&:hover]:[border-color:#4a8ab5] [&.featured]:[border:2px_solid_#1a6b9c] [&.featured]:[box-shadow:0_12px_32px_rgba(26,_107,_156,_0.15)] [&.featured]:[transform:scale(1.03)] dark:[&.featured]:[border-color:#4a8ab5] dark:[&.featured]:[box-shadow:0_12px_32px_rgba(74,_138,_181,_0.2)] [&.featured:hover]:[transform:scale(1.03)_translateY(-6px)] [&.featured_.plan-icon]:[background:linear-gradient(135deg,_#d4e8f8,_#b8d4ec)] dark:[&.featured_.plan-icon]:[background:linear-gradient(135deg,_#1f3444,_#2a4a5e)] max-[1024px]:[&.featured]:[transform:scale(1)] max-[1024px]:[&.featured:hover]:[transform:translateY(-6px)] max-[768px]:[padding:26px_22px_22px] max-[480px]:[padding:24px_18px_20px]">
                <div class="plan-icon [width:64px] [height:64px] [border-radius:18px] flex items-center justify-center [font-size:30px] [margin:0_auto_16px] [background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)]">💎</div>
                <h3 class="plan-name [font-size:22px] font-extrabold [color:#0b2a40] text-center [margin-bottom:6px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:19px]">{{ __('Quarterly') }}</h3>
                <p class="plan-desc [font-size:13.5px] [color:#5a7d99] text-center [margin-bottom:20px] [min-height:40px] [transition:color_0.3s] dark:[color:#8aaccc]">{{ __('Best offer. Save 20% and get long-term access.') }}</p>

                <div class="plan-price text-center [margin-bottom:4px] [&_.amount]:[font-size:38px] [&_.amount]:[font-weight:800] [&_.amount]:[color:#1a6b9c] [&_.amount]:[transition:color_0.3s] dark:[&_.amount]:[color:#6aafdf] [&_.currency]:[font-size:22px] [&_.currency]:[font-weight:700] [&_.currency]:[color:#1a6b9c] [&_.currency]:[margin-left:2px] dark:[&_.currency]:[color:#6aafdf] max-[768px]:[&_.amount]:[font-size:32px] max-[480px]:[&_.amount]:[font-size:28px]">
                    <span class="amount">150.00</span><span class="currency">₾</span>
                </div>
                <p class="plan-period text-center [font-size:13px] [color:#8aaccc] [margin-bottom:20px] [transition:color_0.3s] dark:[color:#5a7d99]">{{ __('3 months • Save 45 ₾') }}</p>

                <div class="plan-divider h-px [background:#e6eff8] [margin:20px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

                <ul class="plan-features list-none p-0 [margin:0_0_24px] [flex:1] [&_li]:[display:flex] [&_li]:[align-items:center] [&_li]:[gap:10px] [&_li]:[padding:7px_0] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[transition:color_0.3s] dark:[&_li]:[color:#b0c8dd] [&_li_i]:[color:#1f9a6e] [&_li_i]:[font-size:14px] [&_li_i]:[flex-shrink:0] dark:[&_li_i]:[color:#5ab88a] [&_li.highlight]:[color:#1a6b9c] [&_li.highlight]:[font-weight:700] dark:[&_li.highlight]:[color:#6aafdf]">
                    <li><i class="fas fa-check-circle"></i> {{ __('Full access') }}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('Real-time data') }}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('All features') }}</li>
                    <li><i class="fas fa-check-circle"></i> {{ __('Priority support') }}</li>
                    <li class="highlight"><i class="fas fa-check-circle"></i> 20% {{ __('discount') }}</li>
                </ul>

                <a href="{{ route('checkout-single') }}" class="plan-btn outline inline-flex items-center justify-center [gap:8px] w-full [padding:14px_24px] [border-radius:60px] [font-size:15px] font-bold [text-decoration:none] [transition:transform_0.2s,_box-shadow_0.2s,_background_0.3s] cursor-pointer border-0 [font-family:inherit] [&.primary]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&.primary]:[color:white] [&.primary:hover]:[transform:translateY(-2px)] [&.primary:hover]:[box-shadow:0_10px_24px_rgba(26,_107,_156,_0.35)] dark:[&.primary]:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&.outline]:[background:white] [&.outline]:[color:#0b2a40] [&.outline]:[border:2px_solid_#d4e0ec] [&.outline:hover]:[border-color:#1a6b9c] [&.outline:hover]:[color:#1a6b9c] [&.outline:hover]:[transform:translateY(-2px)] dark:[&.outline]:[background:#1a2a38] dark:[&.outline]:[color:#e8edf2] dark:[&.outline]:[border-color:#3a5568] dark:[&.outline:hover]:[border-color:#4a8ab5] dark:[&.outline:hover]:[color:#6aafdf]">
                    <i class="fas fa-arrow-right"></i>
                    {{ __('Choose Plan') }}
                </a>
            </div>

        </div>

        <!-- ===== TRIAL BANNER ===== -->
        <div class="trial-banner [background:linear-gradient(135deg,_#1f9a6e,_#17a86b)] [border-radius:20px] [padding:28px_32px] text-center [margin:32px_0] text-white [box-shadow:0_12px_32px_rgba(31,_154,_110,_0.25)] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a7a56,_#17a86b)] [&_.trial-title]:[font-size:22px] [&_.trial-title]:[font-weight:800] [&_.trial-title]:[margin-bottom:6px] [&_.trial-desc]:[font-size:14.5px] [&_.trial-desc]:[opacity:0.95] max-[480px]:[padding:22px_20px] max-[480px]:[&_.trial-title]:[font-size:18px] max-[480px]:[&_.trial-desc]:[font-size:13px]">
            <div class="trial-title">✨ {{ __('7-Day Free Trial') }}</div>
            <div class="trial-desc">{{ __('No payment or card registration required. Get full access for 7 days.') }}</div>
        </div>

        <!-- ===== TRUST BADGES ===== -->
        <div class="trust-badges flex flex-wrap justify-center [gap:14px] [margin:28px_0] max-[480px]:[gap:10px]">
            <span class="trust-badge inline-flex items-center [gap:10px] [padding:12px_22px] [border-radius:14px] [background:#f8fbfe] [border:1px_solid_#e6eff8] [font-size:13.5px] font-semibold [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] [&_.badge-icon]:[font-size:20px] max-[768px]:[padding:10px_18px] max-[768px]:[font-size:12.5px] max-[480px]:[padding:8px_14px] max-[480px]:[font-size:12px]"><span class="badge-icon">🔒</span> {{ __('Secure Payments') }}</span>
            <span class="trust-badge inline-flex items-center [gap:10px] [padding:12px_22px] [border-radius:14px] [background:#f8fbfe] [border:1px_solid_#e6eff8] [font-size:13.5px] font-semibold [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] [&_.badge-icon]:[font-size:20px] max-[768px]:[padding:10px_18px] max-[768px]:[font-size:12.5px] max-[480px]:[padding:8px_14px] max-[480px]:[font-size:12px]"><span class="badge-icon">💳</span> {{ __('Visa, Mastercard, PayPal') }}</span>
            <span class="trust-badge inline-flex items-center [gap:10px] [padding:12px_22px] [border-radius:14px] [background:#f8fbfe] [border:1px_solid_#e6eff8] [font-size:13.5px] font-semibold [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] [&_.badge-icon]:[font-size:20px] max-[768px]:[padding:10px_18px] max-[768px]:[font-size:12.5px] max-[480px]:[padding:8px_14px] max-[480px]:[font-size:12px]"><span class="badge-icon">⚡</span> {{ __('Instant Access') }}</span>
        </div>

        <!-- ===== INFO GRID ===== -->
        <div class="info-grid grid [grid-template-columns:repeat(2,_1fr)] [gap:24px] [margin:32px_0] max-[768px]:[grid-template-columns:1fr]">
            <div class="info-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:24px_28px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&_h3]:[font-size:17px] [&_h3]:[font-weight:700] [&_h3]:[color:#0b2a40] [&_h3]:[margin-bottom:14px] [&_h3]:[display:flex] [&_h3]:[align-items:center] [&_h3]:[gap:8px] [&_h3]:[transition:color_0.3s] dark:[&_h3]:[color:#e8edf2] [&_ul]:[list-style:none] [&_ul]:[padding:0] [&_ul]:[margin:0] [&_ul_li]:[display:flex] [&_ul_li]:[align-items:flex-start] [&_ul_li]:[gap:10px] [&_ul_li]:[padding:6px_0] [&_ul_li]:[font-size:14px] [&_ul_li]:[color:#2a4d66] [&_ul_li]:[transition:color_0.3s] dark:[&_ul_li]:[color:#b0c8dd] [&_ul_li_i]:[color:#1f9a6e] [&_ul_li_i]:[font-size:14px] [&_ul_li_i]:[margin-top:3px] [&_ul_li_i]:[flex-shrink:0] dark:[&_ul_li_i]:[color:#5ab88a] max-[768px]:[padding:20px_22px]">
                <h3>🔄 {{ __('Features') }}</h3>
                <ul>
                    <li><i class="fas fa-check"></i> {{ __('7-day free trial') }}</li>
                    <li><i class="fas fa-check"></i> {{ __('Real-time odds') }}</li>
                    <li><i class="fas fa-check"></i> {{ __('Liquidity analysis') }}</li>
                    <li><i class="fas fa-check"></i> {{ __('Charts and filters') }}</li>
                </ul>
            </div>
            <div class="info-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:24px_28px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&_h3]:[font-size:17px] [&_h3]:[font-weight:700] [&_h3]:[color:#0b2a40] [&_h3]:[margin-bottom:14px] [&_h3]:[display:flex] [&_h3]:[align-items:center] [&_h3]:[gap:8px] [&_h3]:[transition:color_0.3s] dark:[&_h3]:[color:#e8edf2] [&_ul]:[list-style:none] [&_ul]:[padding:0] [&_ul]:[margin:0] [&_ul_li]:[display:flex] [&_ul_li]:[align-items:flex-start] [&_ul_li]:[gap:10px] [&_ul_li]:[padding:6px_0] [&_ul_li]:[font-size:14px] [&_ul_li]:[color:#2a4d66] [&_ul_li]:[transition:color_0.3s] dark:[&_ul_li]:[color:#b0c8dd] [&_ul_li_i]:[color:#1f9a6e] [&_ul_li_i]:[font-size:14px] [&_ul_li_i]:[margin-top:3px] [&_ul_li_i]:[flex-shrink:0] dark:[&_ul_li_i]:[color:#5ab88a] max-[768px]:[padding:20px_22px]">
                <h3>🔓 {{ __('Free Access') }}</h3>
                <ul>
                    <li><i class="fas fa-check"></i> {{ __('View up to 10 matches before registering') }}</li>
                    <li><i class="fas fa-check"></i> {{ __('Get to know the platform before subscribing') }}</li>
                </ul>
            </div>
        </div>

        <!-- ===== SUBSCRIPTION TERMS ===== -->
        <div class="info-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:24px_28px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [margin-bottom:32px]! [&_h3]:[font-size:17px] [&_h3]:[font-weight:700] [&_h3]:[color:#0b2a40] [&_h3]:[margin-bottom:14px] [&_h3]:[display:flex] [&_h3]:[align-items:center] [&_h3]:[gap:8px] [&_h3]:[transition:color_0.3s] dark:[&_h3]:[color:#e8edf2] [&_ul]:[list-style:none] [&_ul]:[padding:0] [&_ul]:[margin:0] [&_ul_li]:[display:flex] [&_ul_li]:[align-items:flex-start] [&_ul_li]:[gap:10px] [&_ul_li]:[padding:6px_0] [&_ul_li]:[font-size:14px] [&_ul_li]:[color:#2a4d66] [&_ul_li]:[transition:color_0.3s] dark:[&_ul_li]:[color:#b0c8dd] [&_ul_li_i]:[color:#1f9a6e] [&_ul_li_i]:[font-size:14px] [&_ul_li_i]:[margin-top:3px] [&_ul_li_i]:[flex-shrink:0] dark:[&_ul_li_i]:[color:#5ab88a] max-[768px]:[padding:20px_22px]">
            <h3>🔁 {{ __('Subscription Terms') }}</h3>
            <ul>
                <li><i class="fas fa-check"></i> {{ __('Auto-renewal') }}</li>
                <li><i class="fas fa-check"></i> {{ __('Cancel anytime') }}</li>
                <li><i class="fas fa-check"></i> {{ __('Access remains active until the end of the current subscription period') }}</li>
            </ul>
        </div>

        <!-- ===== FOOTER MESSAGE ===== -->
        <div class="footer-message text-center [padding:32px_20px_12px] [border-top:1px_solid_#e6edf6] [margin-top:32px] [transition:border-color_0.3s] dark:[border-top-color:#2a3f50] [&_p]:[font-size:14.5px] [&_p]:[color:#5a7d99] [&_p]:[font-style:italic] [&_p]:[margin-bottom:6px] [&_p]:[transition:color_0.3s] dark:[&_p]:[color:#8aaccc] [&_p_strong]:[color:#1a6b9c] [&_p_strong]:[font-style:normal] [&_p_strong]:[font-weight:700] dark:[&_p_strong]:[color:#6aafdf]">
            <p>👉 <strong>{{ __('Built for serious traders') }}</strong> — not for casual users.</p>
            <p>👉 <strong>{{ __('Only data. Only speed. Only edge.') }}</strong></p>
            <p>👉 <strong>{{ __('Stay ahead of the market.') }}</strong></p>
        </div>

    </div>
</div>
@endsection
