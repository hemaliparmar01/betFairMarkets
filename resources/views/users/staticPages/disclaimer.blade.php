@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header !block [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-exclamation-triangle"></i> {{ __('Disclaimer') }}</h1>
        <p class="page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:13px] max-[768px]:[font-size:14px]">{{ __('BF Markets — The Data Terminal for Sports Exchange Markets') }}</p>
        <span class="last-updated inline-block [font-size:12px] [color:#6a8aaa] italic [margin-top:6px] [padding:4px_12px] [background:#f0f6fc] [border-radius:20px] [transition:0.3s]">{{ __('Last Updated: May 3, 2026') }}</span>
    </div>

    <!-- ===== SECTION 1 ===== -->
    <div class="disclaimer-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="disclaimer-section-header dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s]">
            <span class="section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">1</span>
            <span class="disclaimer-section-title dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] [font-size:20px] [font-weight:700] [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] max-[768px]:[font-size:17px]"><i class="fas fa-info-circle"></i> {{ __('General Description') }}</span>
        </div>
        <p>📊 <strong>{{ __('BF Markets') }}</strong> {{ __('is a sports exchange data analytics platform.') }}</p>
        <p>We:</p>
        <ul class="disclaimer-check-list list-none p-0 [margin:12px_0_16px] [&_li]:[padding:8px_0_8px_32px]! [&_li]:[position:relative]! [&_li]:[font-size:14px]! [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[top:8px] [&_li::before]:[color:#c74e4e] [&_li::before]:[font-weight:700] [&_li::before]:[font-size:15px] dark:[&_li::before]:[color:#e08080] [&_li]:before:[content:'✕']">
            <li>{{ __('Are not a bookmaker;') }}</li>
            <li>{{ __('Do not accept bets;') }}</li>
            <li>{{ __('Do not provide financial, investment, or trading advice.') }}</li>
        </ul>
        <p>{{ __('All data, charts, statistics, and analytics provided on the platform are for') }} <strong>{{ __('informational purposes only') }}</strong>.</p>
    </div>

    <div class="disclaimer-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 2 ===== -->
    <div class="disclaimer-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="disclaimer-section-header dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s]">
            <span class="section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">2</span>
            <span class="disclaimer-section-title dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] [font-size:20px] [font-weight:700] [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] max-[768px]:[font-size:17px]"><i class="fas fa-shield-alt"></i> {{ __('Risk Warning') }}</span>
        </div>
        <div class="disclaimer-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [background:linear-gradient(135deg,_#fff8e6,_#fef3d6)]! [border-left-color:#e6b422]! dark:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)]! dark:[border-left-color:#e6b422]! [&_.label]:[color:#b8860b]! [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">⚠️ {{ __('Risk Warning') }}</div>
            <p>{{ __('Sports exchange trading and betting involve') }} <strong>{{ __('significant financial risk') }}</strong>.</p>
            <p>{{ __('You may lose') }} <strong>{{ __('part or all of your invested funds') }}</strong>.</p>
        </div>
    </div>

    <div class="disclaimer-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 3 ===== -->
    <div class="disclaimer-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="disclaimer-section-header dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s]">
            <span class="disclaimer-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">3</span>
            <span class="disclaimer-section-title dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] [font-size:20px] [font-weight:700] [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] max-[768px]:[font-size:17px]"><i class="fas fa-ban"></i> {{ __('Limitation of Liability') }}</span>
        </div>
        <div class="disclaimer-info-card danger [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [background:linear-gradient(135deg,_#fef0f0,_#fce4e4)]! [border-left-color:#c74e4e]! dark:[background:linear-gradient(135deg,_#3a1a1a,_#2e1414)]! dark:[border-left-color:#e08080]! [&_.label]:[color:#c74e4e]! [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">❗ {{ __('BF Markets shall not be liable for:') }}</div>
            <ul class="disclaimer-guide-list list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px]! [&_li]:[position:relative]! [&_li]:[font-size:13px]! [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5] [&_li]:before:[content:'▸']">
                <li>{{ __('Financial losses;') }}</li>
                <li>{{ __('Trading or betting decisions made by users;') }}</li>
                <li>{{ __('Outcomes resulting from the use of data or analytics provided on the platform;') }}</li>
                <li>{{ __('Errors, delays, interruptions, or temporary unavailability of data or services.') }}</li>
            </ul>
        </div>
        <p><strong>{{ __('You use the platform entirely at your own risk.') }}</strong></p>
    </div>

    <div class="disclaimer-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 4 ===== -->
    <div class="disclaimer-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="disclaimer-section-header dark:[border-bottom-color:#2a3f50] [display:flex] [align-items:center] [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s]">
            <span class="section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">4</span>
            <span class="disclaimer-section-title dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] [font-size:20px] [font-weight:700] [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] max-[768px]:[font-size:17px]"><i class="fas fa-link"></i> {{ __('Brand Disclaimer') }}</span>
        </div>
        <div class="disclaimer-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">🔗 {{ __('Brand Independence') }}</div>
            <p><strong>{{ __('BF Markets') }}</strong> {{ __('is an independent data analytics platform and is') }} <strong>{{ ('not affiliated with, endorsed by, sponsored by, or otherwise connected to Betfair or any other betting exchange') }}</strong>.</p>
        </div>
        <p>{{ __('Betfair and any other trademarks referenced on the platform are the property of their respective owners and are used solely for') }} <strong>{{ __('informational and descriptive purposes') }}</strong>.</p>
    </div>
@endsection
