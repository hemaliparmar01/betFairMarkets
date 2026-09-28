@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="tos-page-header [margin:28px_0_8px]">
        <h1 class="tos-page-title [font-size:32px] font-bold [color:#0b2a40] [letter-spacing:-0.5px] flex items-center [gap:12px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:28px] max-[768px]:[font-size:24px]"><i class="fas fa-file-contract"></i> Terms of Service</h1>
        <p class="tos-page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc]">BF Markets — The Data Terminal for Sports Exchange Markets</p>
        <span class="tos-last-updated inline-block [font-size:12px] [color:#6a8aaa] italic [margin-top:6px] [padding:4px_12px] [background:#f0f6fc] [border-radius:20px] [transition:0.3s] dark:[background:#1f3444] dark:[color:#8aaccc]">Last Updated: May 3, 2026</span>
    </div>

    <!-- ===== SECTION 1 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">1</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-info-circle"></i> Introduction</span>
        </div>
        <p>Welcome to BF Markets ("Service", "we", "us", or "our"). BF Markets is a sports exchange data analytics platform that provides real-time odds, liquidity monitoring, market depth visualization, and other analytical tools and data.</p>
        <div class="tos-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [background:linear-gradient(135deg,_#fff8e6,_#fef3d6)]! [border-left-color:#e6b422]! dark:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)]! dark:[border-left-color:#e6b422]! [&_.label]:[color:#b8860b]! [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">⚠️ Important</div>
            <p>We are not a bookmaker, do not accept bets, and do not provide gambling services.</p>
        </div>
        <div class="tos-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">🔹 Owner and Operator</div>
            <p>The BF Markets platform is created, owned, and operated by <strong>Digital Analytics Group LLC</strong>.</p>
            <p><a href="https://digitalanalyticsgroup.com" target="_blank">https://digitalanalyticsgroup.com</a></p>
        </div>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 2 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">2</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-check-circle"></i> Acceptance of Terms</span>
        </div>
        <p>By accessing or using the Service, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree with any part of these Terms, please do not use the Service.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 3 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">3</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-user-check"></i> Age Restriction</span>
        </div>
        <p>The Service is intended solely for individuals who are at least <strong>18 years of age</strong>.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 4 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">4</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-list-alt"></i> Description of the Service</span>
        </div>
        <p>The platform provides users with real-time odds, liquidity data, market depth visualizations, charts, and analytical tools. All information provided through the Service is for <strong>informational purposes only</strong>.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 5 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">5</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-exclamation-triangle"></i> No Financial Advice</span>
        </div>
        <p>Our Service does not constitute financial, investment, trading, or betting advice or recommendations. Any decisions made by users are entirely their own responsibility.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 6 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">6</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-shield-alt"></i> Risk Acknowledgement</span>
        </div>
        <p>Sports exchange trading and betting involve significant financial risks and may result in financial losses. Your use of the Service is entirely <strong>at your own risk and responsibility</strong>.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 7 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">7</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-user-cog"></i> User Obligations</span>
        </div>
        <p>Users agree to:</p>
        <ul class="tos-guide-list">
            <li>Use the Service only for lawful purposes;</li>
            <li>Not share their account with third parties;</li>
            <li>Not copy, scrape, distribute, or otherwise use the platform's data in any unauthorized or unlawful manner.</li>
        </ul>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 8 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">8</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-credit-card"></i> Subscriptions and Payments</span>
        </div>
        <p>New users receive a <strong>7-day free trial period</strong> upon registration. During the trial period, no payment is required and no charges will be automatically deducted from the user's payment card.</p>
        <p>After the trial period expires, users must manually select one of the available paid subscription plans in order to continue using the Service:</p>
        <p>
            <span class="plan-badge">1 Week</span>
            <span class="plan-badge">1 Month</span>
            <span class="plan-badge">1 Quarter (3 Months)</span>
        </p>

        <div class="tos-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">💡 Auto Renewal</div>
            <p>When purchasing their first paid subscription, users may choose whether to enable the automatic renewal feature. Automatic renewal will only be activated if the user expressly provides consent by selecting the appropriate checkbox ("Auto Renewal" or "Subscription").</p>
            <p>If the user does not enable automatic renewal, the subscription will not renew automatically and the user must manually purchase a new subscription if they wish to continue using the Service after the current subscription period expires.</p>
            <p>If the user enables automatic renewal, the subscription fee will be automatically charged to the designated payment method at the beginning of each subsequent billing period until the user cancels the automatic renewal feature.</p>
        </div>

        <div class="tos-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">💳 Payments</div>
            <p>All payments are <strong>final and non-refundable</strong>, except where otherwise required by applicable law.</p>
        </div>

        <div class="tos-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📧 Automatic Renewal Notifications</div>
            <p>Users will receive an email notification before the automatic payment date:</p>
            <ul class="tos-guide-list">
                <li><strong>3 days in advance</strong> for monthly and quarterly plans;</li>
                <li><strong>24 hours in advance</strong> for weekly plans.</li>
            </ul>
        </div>

        <div class="tos-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">💰 Price Changes</div>
            <p>We reserve the right to change subscription fees. Users will be notified of any price changes at least <strong>30 calendar days in advance</strong>. Any new pricing will take effect from the next billing cycle.</p>
        </div>

        <div class="tos-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [background:linear-gradient(135deg,_#fff8e6,_#fef3d6)]! [border-left-color:#e6b422]! dark:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)]! dark:[border-left-color:#e6b422]! [&_.label]:[color:#b8860b]! [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">💳 Payment Methods and User Responsibility</div>
            <p>Subscription payments may only be made using a payment card registered in the <strong>user's own name</strong>. The platform performs automatic verification of the cardholder's first and last name; therefore, payments made using a card issued in another person's name may be technically restricted.</p>
        </div>

        <div class="tos-info-card danger [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">⚠️ Unauthorized Transactions</div>
            <p>The use of another person's (third party's) bank card is strictly prohibited. If an unauthorized transaction is processed due to a technical issue, BF Markets reserves the right to immediately suspend the user's account, cancel the transaction, and initiate additional verification procedures.</p>
            <p>By using the Service, you confirm that the payment card used belongs to you and that you accept full responsibility for any unauthorized activity.</p>
            <p>If an automatic payment cannot be processed due to technical reasons, including banking restrictions, expired cards, insufficient funds, or other technical issues, the user must manually make the payment if they wish to continue using the Service.</p>
        </div>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 9 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">9</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-check-double"></i> Data Accuracy</span>
        </div>
        <p>We do not guarantee that the information and data provided through the platform will always be 100% accurate, complete, uninterrupted, or error-free.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 10 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">10</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-handshake"></i> Third-Party Services</span>
        </div>
        <p>The Service may rely on third-party services, including payment providers and data suppliers. We are not responsible for the performance, availability, or reliability of such third-party services.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 11 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">11</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-copyright"></i> Intellectual Property</span>
        </div>
        <p>All content, data, designs, software, and other intellectual property displayed on the platform belong to BF Markets or are used under appropriate licenses or permissions.</p>
        <p>Any copying, distribution, transmission, modification, or commercial use of any materials without our prior written consent is strictly prohibited.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 12 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">12</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-balance-scale"></i> Limitation of Liability</span>
        </div>
        <p>BF Markets shall not be liable for:</p>
        <ul class="tos-guide-list">
            <li>Financial losses;</li>
            <li>Trading or betting outcomes;</li>
            <li>Data errors, delays, or interruptions;</li>
            <li>Temporary unavailability of the Service;</li>
            <li>Any decisions made by users based on information available on the platform.</li>
        </ul>
        <div class="tos-info-card danger [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:12px_0_16px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:4px]! [&_p]:[font-size:13px]! [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">⚠️ Payment Provider Disclaimer</div>
            <p>We are not responsible for technical failures or interruptions of payment providers, including but not limited to TBC, PayPal, BOG, Flitt, or any other independent payment service providers.</p>
        </div>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 13 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">13</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-toggle-on"></i> Service Availability</span>
        </div>
        <p>We reserve the right to temporarily suspend, modify, update, or discontinue the Service at any time without prior notice.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 14 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">14</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-user-slash"></i> Account Suspension and Termination</span>
        </div>
        <p>We may temporarily suspend, restrict, or permanently terminate a user's account if there is reasonable suspicion that these Terms of Service or applicable laws have been violated.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 15 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">15</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-gavel"></i> Governing Law</span>
        </div>
        <p>These Terms of Service shall be governed by and construed in accordance with the laws of <strong>Georgia</strong>.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 16 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">16</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-sync-alt"></i> Amendments</span>
        </div>
        <p>We reserve the right to amend these Terms of Service at any time. The updated version will be published on this page and, unless otherwise specified, shall become effective immediately upon publication.</p>
    </div>

    <div class="tos-divider h-px [background:#e6edf6] [margin:24px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 17 ===== -->
    <div class="tos-section [margin:28px_0] [&_p]:[font-size:14px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:8px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:14px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:8px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2] [&_a]:[color:#1a6b9c] [&_a]:[text-decoration:none] [&_a]:[font-weight:600] [&_a]:[transition:0.2s] dark:[&_a]:[color:#6aafdf] [&_a:hover]:[text-decoration:underline]!">
        <div class="tos-section-header flex items-center [gap:12px] [margin-bottom:16px] [padding-bottom:10px] [border-bottom:1px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="tos-section-number inline-flex items-center justify-center [width:34px] [height:34px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:14px] shrink-0">17</span>
            <span class="tos-section-title [font-size:20px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:18px] max-[768px]:[font-size:17px]"><i class="fas fa-envelope"></i> Contact Information</span>
        </div>
        <p><i class="fas fa-envelope [color:#1a6b9c]! [margin-right:8px]!"></i> <a href="mailto:office@betfairmarkets.com">office@betfairmarkets.com</a></p>
    </div>

@endsection
