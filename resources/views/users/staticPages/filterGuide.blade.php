@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')
    <!-- ===== PAGE HEADER ===== -->
    <div class="filter-guide-page-header [margin:28px_0_8px]">
        <h1 class="filter-guide-page-title [font-size:32px] font-bold [color:#0b2a40] [letter-spacing:-0.5px] flex items-center [gap:12px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#e6b422] [&_i]:[font-size:28px] max-[768px]:[font-size:24px]"><i class="fas fa-filter"></i> Filter Guide</h1>
        <p class="filter-guide-page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc]">BF Markets — The Data Terminal for Sports Exchange Markets</p>
    </div>

    <!-- ===== 1. LIVE ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">1</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-circle [color:#d63e3e]!"></i> Live</span>
        </div>
        <p><strong>What it is:</strong> In-play match filter.</p>
        <p><strong>Usage:</strong> Instantly displays all matches that are currently live. Ideal for users trading live market dynamics, in-play goals, and changing odds.</p>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 2. UPCOMING ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">2</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-clock"></i> Upcoming</span>
        </div>
        <p><strong>What it is:</strong> Upcoming match filter.</p>
        <p><strong>Usage:</strong> Displays matches scheduled to start later. Helps with pre-match planning, analysis, and organizing your daily watchlist.</p>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 3. FINISHED ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">3</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-archive"></i> Finished</span>
        </div>
        <p><strong>What it is:</strong> Archive of completed matches.</p>
        <p><strong>Usage:</strong> Shows completed matches and their final results. Useful for reviewing outcomes and analyzing your trading strategies.</p>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 4. FAVORITES ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">4</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-star [color:#e6b422]!"></i> Favorites</span>
        </div>
        <p><strong>What it is:</strong> Your personal watchlist.</p>
        <p><strong>Usage:</strong> Displays all matches marked with a star (⭐), allowing you to monitor only the games that matter to you.</p>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 5. BY TIME ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">5</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-sort-amount-down"></i> By Time</span>
        </div>
        <p><strong>What it is:</strong> Chronological sorting tool.</p>
        <p><strong>Usage:</strong> Sorts matches by kickoff time, from earliest to latest, making it easier to organize your trading schedule.</p>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 6. HT 0-0 ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">6</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-futbol"></i> HT 0-0 (Half Time 0-0)</span>
        </div>
        <p><strong>What it is:</strong> Live filter for matches that are goalless at half-time.</p>
        <p><strong>Usage:</strong> Identifies live matches where the first half has ended 0-0.</p>
        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Strategy</div>
            <p>Many traders use this filter to explore second-half goal markets (Over 0.5, Over 1.5, Over 2.5, etc.), as odds for goals often increase significantly after a goalless first half.</p>
        </div>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 7. 0-0 @ 70' ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">7</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-hourglass-half"></i> 0-0 @ 70'</span>
        </div>
        <p><strong>What it is:</strong> Live filter for matches that remain goalless at or after the 70th minute.</p>
        <p><strong>Usage:</strong> Displays matches that are still 0-0.</p>
        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Strategy</div>
            <p>Widely used to identify potential "Late Goal" opportunities. At this stage of the match, the odds for at least one goal are often significantly higher.</p>
        </div>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 8. FAV LOSING ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">8</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-arrow-down [color:#c74e4e]!"></i> Fav Losing (Favorite Losing)</span>
        </div>
        <p><strong>What it is:</strong> Live tracker for matches where the pre-match favorite is currently losing.</p>
        <p><strong>Usage:</strong> Displays matches in which a heavily favored team is behind during live play.</p>
        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Strategy</div>
            <p>Some traders monitor these situations for potential opportunities involving the favorite not to lose (1X/X2) or a possible comeback scenario, as live odds may differ substantially from pre-match prices.</p>
        </div>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 9. PREMATCH DROPS ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">9</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-arrow-trend-down"></i> Prematch Drops</span>
        </div>
        <p><strong>What it is:</strong> Early-warning radar for initial pre-match odds movements.</p>
        <p><strong>Usage:</strong> Displays matches that have not started yet but have already shown early price movement.</p>
        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Strategy</div>
            <p>The system identifies the earliest odds changes that may develop into broader market trends. This allows users to detect emerging market momentum at an early stage.</p>
        </div>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 10. MARKET SHOCK DETECTOR ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">10</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-bolt [color:#e6b422]!"></i> Market Shock Detector</span>
        </div>
        <p><strong>What it is:</strong> A lightning-fast detector for sudden market shocks and sharp price movements.</p>
        <p><strong>How it works:</strong> Unlike Prematch Drops, this filter operates exclusively on pre-match markets and detects aggressive price movements occurring within approximately the last 1 to 3 minutes. It scans Football (four core markets), Tennis, and Cricket (Match Odds).</p>
        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Strategy</div>
            <p>The filter is designed to identify moments when the global exchange market reacts rapidly to significant events, such as injuries, lineup announcements, or other material information. It helps users observe market changes before slower-moving markets fully adjust.</p>
        </div>
    </div>

    <div class="filter-guide-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== 11. FAV NOT WINNING ===== -->
    <div class="filter-guide-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="filter-guide-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="filter-guide-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">11</span>
            <span class="filter-guide-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-hand-paper [color:#1a6b9c]!"></i> Fav Not Winning</span>
        </div>
        <p><strong>What is Fav Not Winning?</strong></p>
        <p>This is a <strong>live filter</strong> that shows matches where the pre-match favorite is <strong>not yet winning</strong> — meaning the favorite is either drawing or losing.</p>

        <p><strong>How is it different from Fav Losing?</strong></p>
        <ul class="filter-guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:10px_0_16px] [&_li]:[padding:6px_0_6px_24px]! [&_li]:[position:relative]! [&_li]:[font-size:14px]! [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Fav Losing</strong> — shows only matches where the favorite is <strong>losing</strong>.</li>
            <li><strong>Fav Not Winning</strong> — shows both matches where the favorite is losing and matches where the favorite's game is <strong>still a draw</strong>.</li>
        </ul>

        <p><strong>Examples:</strong></p>
        <div class="filter-guide-example-block [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:12px] [padding:14px_18px] [font-family:'SF_Mono',_'Fira_Code',_'Consolas',_monospace] [font-size:13px] [color:#1e4763] [margin:10px_0_14px] whitespace-pre-wrap [line-height:1.8] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] [&_.hl-green]:[color:#1f9a6e]! [&_.hl-green]:[font-weight:600]! dark:[&_.hl-green]:[color:#5ab88a]! [&_.hl-red]:[color:#c74e4e]! [&_.hl-red]:[font-weight:600]! dark:[&_.hl-red]:[color:#e08080]! [&_.hl-blue]:[color:#1a6b9c]! [&_.hl-blue]:[font-weight:600]! dark:[&_.hl-blue]:[color:#6aafdf]! [&_.hl-muted]:[color:#6a8aaa]!">
            <span class="hl-red">0–1</span> — the favorite is losing → <span class="hl-green">shown</span>
            <span class="hl-red">1–2</span> — the favorite is losing → <span class="hl-green">shown</span>
            <span class="hl-blue">0–0</span> — the match involving the favorite is still a draw → <span class="hl-green">shown</span>
            <span class="hl-blue">1–1</span> — the match involving the favorite is still a draw → <span class="hl-green">shown</span>
            <span class="hl-red">1–0</span> — if the home team is the favorite and is winning → <span class="hl-red">not shown</span>
        </div>

        <p><strong>How does the filter work?</strong></p>
        <p>The filter starts working <strong>from the 29th minute</strong> and monitors matches where the pre-match favorite's odds are within the defined range. If the favorite is drawing or losing, the match appears in the filter.</p>
        <p>Once the favorite takes the lead and is winning, the match is <strong>automatically removed</strong> from the filter.</p>

        <div class="filter-guide-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">📊 Why can this filter be useful?</div>
            <p>Fav Not Winning brings together, in one view, matches where the favorite is not yet following the expected winning scenario — whether the match is a draw or the favorite is losing. This makes it easier to monitor the favorite's current position and how the match develops.</p>
        </div>
    </div>

@endsection
