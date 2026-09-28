@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="manifesto-page-header [margin:28px_0_8px]">
        <h1 class="manifesto-page-title [font-size:32px] font-bold [color:#0b2a40] [letter-spacing:-0.5px] flex items-center [gap:12px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#e6b422] [&_i]:[font-size:28px] max-[768px]:[font-size:24px]"><i class="fas fa-fire"></i> BF Markets Manifesto</h1>
        <p class="manifesto-page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc]">BF Markets — The Data Terminal for Sports Exchange Markets</p>
    </div>

    <!-- ===== SECTION 1 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">1</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-eye"></i> Vision</span>
        </div>
        <p><strong>BF Markets</strong> is not a gambling platform. It is a <strong>financial terminal for sports exchanges</strong>, built on <strong>Quant-Dashboard</strong> principles.</p>
        <p>Our philosophy is rooted in <strong>Bloomberg-style thinking</strong>:</p>
        <div class="manifesto-quote-block [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [border-radius:16px] [padding:28px_32px] [margin:24px_0] [font-size:19px] font-semibold italic text-center [letter-spacing:-0.3px] relative overflow-hidden [box-shadow:0_8px_24px_rgba(11,_42,_64,_0.15)] dark:[background:linear-gradient(135deg,_#1a6b9c,_#2a7aaa)] max-[768px]:[font-size:16px] max-[768px]:[padding:22px_24px] [&_i]:[margin-right:10px]! [&_i]:[opacity:0.7]! [&_i]:[font-size:18px]! [&::before]:[position:absolute] [&::before]:[top:-20px] [&::before]:[left:20px] [&::before]:[font-size:100px] [&::before]:[opacity:0.12] [&::before]:[line-height:1]">
            <i class="fas fa-quote-left"></i>
            Data must be clean, signals must be clear, and reactions must be instantaneous.
        </div>
    </div>

    <div class="manifesto-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 2 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">2</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-filter"></i> Data Hygiene</span>
        </div>
        <p>Like a Bloomberg Terminal, our highest priority is <strong>informational purity</strong>.</p>
        <p>We filter out market noise.</p>
        <div class="highlight-box [background:#fafdff] [border:2px_solid_#1a6b9c] [border-radius:16px] [padding:22px_26px] [margin:20px_0] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#4a8ab5] [&_p]:[font-size:16px] [&_p]:[font-weight:500] [&_p]:[margin:0] [&_p]:[color:#0b2a40] dark:[&_p]:[color:#e8edf2] [&_.title]:[font-size:11px] [&_.title]:[font-weight:700] [&_.title]:[text-transform:uppercase] [&_.title]:[color:#1a6b9c] [&_.title]:[letter-spacing:0.6px] [&_.title]:[margin-bottom:10px] dark:[&_.title]:[color:#6aafdf]">
            <div class="title">Core Principle</div>
            <p>A BF Markets user does not waste time on insignificant <strong>0.01 fluctuations</strong>.</p>
        </div>
        <p>Our <strong>Smart Threshold</strong> technology ensures that only movements with <strong>meaningful market impact</strong> appear on your screen.</p>
    </div>

    <div class="manifesto-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 3 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">3</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-vector-square"></i> Vector-Based Intelligence</span>
        </div>
        <p>We <strong>reject static history</strong>.</p>
        <p>Traditional platforms display differences relative to the opening odds, often creating a <strong>distorted view of the market</strong>.</p>
        <p>BF Markets utilizes <strong>Dynamic Velocity Tracking</strong>.</p>
        <div class="manifesto-quote-block [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [border-radius:16px] [padding:28px_32px] [margin:24px_0] [font-size:19px] font-semibold italic text-center [letter-spacing:-0.3px] relative overflow-hidden [box-shadow:0_8px_24px_rgba(11,_42,_64,_0.15)] dark:[background:linear-gradient(135deg,_#1a6b9c,_#2a7aaa)] max-[768px]:[font-size:16px] max-[768px]:[padding:22px_24px] [&_i]:[margin-right:10px]! [&_i]:[opacity:0.7]! [&_i]:[font-size:18px]! [&::before]:[position:absolute] [&::before]:[top:-20px] [&::before]:[left:20px] [&::before]:[font-size:100px] [&::before]:[opacity:0.12] [&::before]:[line-height:1]">
            <i class="fas fa-arrow-right"></i>
            We show you not where the price has been, but where it is moving right now.
        </div>
        <p>Our arrows represent <strong>live market momentum</strong> rather than historical inertia.</p>
    </div>

    <div class="manifesto-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 4 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">4</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-balance-scale"></i> Neutrality as an Asset</span>
        </div>
        <p>In a financial terminal, <strong>silence is information</strong>.</p>
        <div class="manifesto-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">💡 Principle of Neutrality</div>
            <p>If no arrow is displayed in the BF Markets terminal, it means the <strong>market is in equilibrium</strong>.</p>
        </div>
        <p>We do not create the illusion of movement where none exists.</p>
        <p>This disciplined and neutral approach protects traders from <strong>impulsive decisions</strong> and allows them to act only when the market provides a <strong>genuine signal</strong>.</p>
    </div>

    <div class="manifesto-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 5 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">5</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-layer-group"></i> Quant-Level Architecture</span>
        </div>
        <p>Our system is built upon <strong>two fundamental layers</strong>:</p>

        <div class="manifesto-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">⚡ Execution Layer (Terminal UI)</div>
            <p>Ultra-fast, snapshot-based visualization designed for <strong>immediate decision-making</strong>.</p>
        </div>

        <div class="manifesto-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">🔬 Analytical Layer (Market Shock & Drops)</div>
            <p>Deep, cumulative algorithms that analyze <strong>market anomalies and structural changes</strong> over time.</p>
        </div>

        <p>This architecture is designed to deliver exceptional <strong>stability, precision, and performance</strong> under heavy workloads.</p>
    </div>

    <div class="manifesto-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 6 ===== -->
    <div class="manifesto-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="manifesto-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="manifesto-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">6</span>
            <span class="manifesto-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-bullseye"></i> Signal over Information</span>
        </div>
        <p>Information is available to <strong>everyone</strong>.</p>
        <p>Signals are for <strong>those who know how to see them</strong>.</p>
        <p>The mission of BF Markets is to <strong>extract opportunity from chaos</strong>.</p>

        <div class="manifesto-quote-block [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [border-radius:16px] [padding:28px_32px] [margin:24px_0] [font-size:19px] font-semibold italic text-center [letter-spacing:-0.3px] relative overflow-hidden [box-shadow:0_8px_24px_rgba(11,_42,_64,_0.15)] dark:[background:linear-gradient(135deg,_#1a6b9c,_#2a7aaa)] max-[768px]:[font-size:16px] max-[768px]:[padding:22px_24px] [&_i]:[margin-right:10px]! [&_i]:[opacity:0.7]! [&_i]:[font-size:18px]! [&::before]:[position:absolute] [&::before]:[top:-20px] [&::before]:[left:20px] [&::before]:[font-size:100px] [&::before]:[opacity:0.12] [&::before]:[line-height:1]">
            <i class="fas fa-quote-left"></i>
            We don't provide data points; we provide market direction.
        </div>
    </div>

@endsection
