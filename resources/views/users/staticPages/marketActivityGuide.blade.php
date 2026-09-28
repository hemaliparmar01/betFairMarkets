@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-book-open"></i> Market Activity Guide</h1>
        <p class="page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:13px] max-[768px]:[font-size:14px]">BF Markets — The Data Terminal for Sports Exchange Markets</p>
    </div>

    <!-- ===== SECTION 1 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">1</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-info-circle"></i> What is Market Activity?</span>
        </div>
        <p><strong>Market Activity</strong> is a real-time monitoring dashboard for Betfair Exchange markets across multiple
            sports. It displays live odds, liquidity, and market movement in a single, high-density view — designed for fast
            decision-making.</p>
        <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">In simple terms</div>
            <p>Market Activity shows <strong>where the money is</strong>, <strong>where it's moving</strong>, and
                <strong>how fast</strong> — across all supported live and upcoming events, according to the respective sport
                and market type.</p>
        </div>
        <p>Unlike a traditional betting page, Market Activity is not designed for placing bets. It is a <strong>data
                terminal</strong> — built for scanning, filtering, and understanding market behavior at a glance.</p>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 2 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">2</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-bullseye"></i> Purpose</span>
        </div>
        <p>The page answers four key questions instantly:</p>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Indicator</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Where is the money right now?</td>
                    <td class="value-col">Balance</td>
                </tr>
                <tr>
                    <td>Where is the money moving toward?</td>
                    <td class="value-col">Momentum</td>
                </tr>
                <tr>
                    <td>How fast is Back Size changing?</td>
                    <td class="value-col">Δ (Delta)</td>
                </tr>
                <tr>
                    <td>How unpredictable is the market?</td>
                    <td class="value-col">Volatility</td>
                </tr>
            </tbody>
        </table></div>
        <p>These four indicators are calculated in real-time from Betfair Exchange odds and liquidity data, updated via
            <strong>real-time, low-latency updates</strong>.</p>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 3 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">3</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-users"></i> Who Uses Market Activity?</span>
        </div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
            <thead>
                <tr>
                    <th>User Type</th>
                    <th>Use Case</th>
                    <th>How They Use It</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Traders</strong></td>
                    <td>Identify market trends and enter/exit positions</td>
                    <td>Monitor Momentum and Δ to catch price movements early</td>
                </tr>
                <tr>
                    <td><strong>Arbitrageurs</strong></td>
                    <td>Find price discrepancies across markets</td>
                    <td>Compare Back/Lay odds and liquidity across events</td>
                </tr>
                <tr>
                    <td><strong>Swing Traders</strong></td>
                    <td>Capture medium-term moves</td>
                    <td>Use rolling history (1s / 3s / 5s / 10s) to confirm sustained trends</td>
                </tr>
                <tr>
                    <td><strong>Scalpers</strong></td>
                    <td>Exploit micro-movements</td>
                    <td>Real-time updates + Δ provide fast price action</td>
                </tr>
                <tr>
                    <td><strong>Horse Racing Traders</strong></td>
                    <td>Pre-race monitoring and analysis</td>
                    <td>Monitor <strong>prematch</strong> markets 5–10 minutes before the off; use Momentum, Δ, and the FAV
                        badge to assess market direction and intensity</td>
                </tr>
                <tr>
                    <td><strong>Data Analysts</strong></td>
                    <td>Study market behavior and liquidity</td>
                    <td>Volatility + Liquidity distribution provide a clear picture of market depth</td>
                </tr>
                <tr>
                    <td><strong>Casual Bettors</strong></td>
                    <td>Identify interesting market opportunities</td>
                    <td>Balance and Momentum highlight markets with notable liquidity or movement patterns</td>
                </tr>
                <tr>
                    <td><strong>Platform Admins</strong></td>
                    <td>Monitor system health</td>
                    <td>Real-time update frequency and event count show if the feed is healthy</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 4 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">4</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-sliders-h"></i> Key Features</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.1 Real-Time Odds & Liquidity</div>
            <p>Every event displays:</p>
            <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="back-col">Back Odds</td>
                        <td>Best available price to bet FOR a selection (Blue)</td>
                    </tr>
                    <tr>
                        <td class="back-col">Back Size</td>
                        <td>Amount of money available at the best Back price (Blue)</td>
                    </tr>
                    <tr>
                        <td class="lay-col">Lay Odds</td>
                        <td>Best available price to bet AGAINST a selection (Pink)</td>
                    </tr>
                    <tr>
                        <td class="lay-col">Lay Size</td>
                        <td>Amount of money available at the best Lay price (Pink)</td>
                    </tr>
                </tbody>
            </table></div>
            <p>All values are updated in real-time from the Betfair Exchange <strong>data stream</strong> with
                <strong>low-latency updates</strong>.</p>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.2 Indicators</div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th>Full Name</th>
                    <th>What It Shows</th>
                    <th>How It's Calculated</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="value-col">Balance</td>
                    <td>Liquidity Balance</td>
                    <td>Where the money is right now</td>
                    <td>Back Size vs Lay Size ratio</td>
                </tr>
                <tr>
                    <td class="value-col">Momentum</td>
                    <td>Market Momentum</td>
                    <td>Where the money is moving toward</td>
                    <td><strong>Composite indicator</strong> — evaluates odds movement, liquidity change, and market
                        balance. Final score is determined by BF Markets' internal calibration.</td>
                </tr>
                <tr>
                    <td class="value-col">Δ (Delta)</td>
                    <td>Price Change</td>
                    <td>How fast Back Size is changing</td>
                    <td>% change in Back Size over a short rolling window</td>
                </tr>
                <tr>
                    <td class="value-col">Volatility</td>
                    <td>Market Volatility</td>
                    <td>How unpredictable the market is</td>
                    <td>Standard deviation of log returns over a rolling window</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.3 Balance — Where the Money Is</div>
    <p><strong>Definition:</strong> Balance compares the total Back liquidity against the total Lay liquidity for a given
        runner.</p>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Value</th>
                <th>Meaning</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="back-col">Back Heavy ↑</td>
                <td>Greater concentration of liquidity on the Back side</td>
            </tr>
            <tr>
                <td class="value-col">Balanced →</td>
                <td>Liquidity is roughly evenly distributed on both sides</td>
            </tr>
            <tr>
                <td class="lay-col">Lay Heavy ↓</td>
                <td>Greater concentration of liquidity on the Lay side</td>
            </tr>
        </tbody>
    </table></div>
    <p><strong>Example:</strong></p>
    <div class="market-activity-example-block">Bayern Munich: Back Size 23.2K | Lay Size 4.2K
        → <span class="hl-blue">Back Heavy</span> (more money on Back)</div>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li><strong>Back Heavy</strong> → Liquidity is more concentrated on the Back side</li>
        <li><strong>Lay Heavy</strong> → Liquidity is more concentrated on the Lay side</li>
        <li><strong>Balanced</strong> → Liquidity is roughly evenly distributed</li>
    </ul>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.4 Momentum — Where the Money Is Moving</div>
    <p><strong>Definition:</strong> Momentum measures the direction and strength of market movement.</p>
    <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
        <div class="label">Conceptual Logic</div>
        <p>Momentum is a <strong>composite indicator</strong> that evaluates odds movement, liquidity change, and market
            balance. The final score is determined by <strong>BF Markets' internal calibration</strong>.</p>
    </div>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Value</th>
                <th>Meaning</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="back-col">Strong Back ↑↑</td>
                <td>Strong movement toward the Back side</td>
            </tr>
            <tr>
                <td class="back-col">Back ↑</td>
                <td>Moderate movement toward the Back side</td>
            </tr>
            <tr>
                <td class="value-col">Neutral →</td>
                <td>No significant movement</td>
            </tr>
            <tr>
                <td class="lay-col">Lay ↓</td>
                <td>Moderate movement toward the Lay side</td>
            </tr>
            <tr>
                <td class="lay-col">Strong Lay ↓↓</td>
                <td>Strong movement toward the Lay side</td>
            </tr>
        </tbody>
    </table></div>
    <div class="market-activity-example-block">Momentum: <span class="hl-blue">Back ↑</span>
        → Data shows a stronger concentration toward the Back side</div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.5 Δ (Delta) — How Fast Back Size Is Changing</div>
    <p><strong>Definition:</strong> Delta shows the <strong>percentage change in Back Size</strong> over a short rolling
        window.</p>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Value</th>
                <th>Meaning</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="back-col">+18% ↑</td>
                <td>Back Size increased by 18%</td>
            </tr>
            <tr>
                <td class="lay-col">-24% ↓</td>
                <td>Back Size decreased by 24%</td>
            </tr>
            <tr>
                <td class="value-col">0% →</td>
                <td>No significant change</td>
            </tr>
        </tbody>
    </table></div>
    <div class="market-activity-example-block">Δ 5s: <span class="hl-green">+50%</span>
        → Back Size increased by 50% in the last 5 seconds</div>
    <div class="market-activity-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
        <div class="label">Note</div>
        <p>Delta measures only Back Size. Lay Size changes are visible separately in the Lay Size column.</p>
    </div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.6 Volatility — How Unpredictable the Market Is</div>
    <p><strong>Definition:</strong> Volatility measures how much the odds are fluctuating over a rolling window.</p>
    <p><strong>Method:</strong> Standard deviation of log returns of Back Odds.</p>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Value</th>
                <th>Icon</th>
                <th>Meaning</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value-col">Low</td>
                <td>🟢</td>
                <td>Relatively stable movement of odds</td>
            </tr>
            <tr>
                <td class="value-col">Medium</td>
                <td>🟡</td>
                <td>Moderate fluctuation of odds</td>
            </tr>
            <tr>
                <td class="value-col">High</td>
                <td>🔴</td>
                <td>Fast / significant fluctuation of odds</td>
            </tr>
        </tbody>
    </table></div>
    <div class="market-activity-example-block">Volatility: <span class="hl-red">High 🔴</span>
        → 75th minute, score 3-2
        → Any goal will dramatically change the odds</div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.7 Horse Racing — Favorite Identification (Prematch Only)</div>
    <p>For Horse Racing events, the system automatically identifies the <strong>Favorite</strong> — the runner with the
        <strong>lowest Back Odds</strong>.</p>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Element</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value-col">⭐ FAV Badge</td>
                <td>Displayed next to the favorite runner</td>
            </tr>
            <tr>
                <td class="value-col">Favorite Odds</td>
                <td>Lowest Back Odds among all runners</td>
            </tr>
            <tr>
                <td class="value-col">Favorite Momentum</td>
                <td>Shows the direction and intensity of market movement relative to the favorite's odds</td>
            </tr>
        </tbody>
    </table></div>
    <div class="market-activity-example-block">Thunder Bolt: Back 2.58 <span class="hl-orange">⭐ FAV</span>
        Storm Chaser 1: Back 2.73
        Storm Chaser 2: Back 3.35
        → Thunder Bolt is the favorite (lowest Back Odds)</div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.8 Horse Racing — Pre-Race Monitoring Window (Prematch Only)</div>
    <div class="market-activity-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
        <div class="label">Important</div>
        <p>Horse Racing in Market Activity is available <strong>only in Prematch</strong>. Live races are
            <strong>not</strong> shown in this terminal.</p>
    </div>
    <p><strong>Why this matters:</strong></p>
    <p>Professional horse racing traders typically monitor the <strong>prematch</strong> market <strong>5–10 minutes before
            the race starts</strong>. During this window:</p>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li>Market activity increases significantly</li>
        <li>This causes rapid <strong>odds movement</strong> and significant <strong>money flow</strong></li>
        <li>The direction of market movement is reflected in changes across <strong>odds, liquidity, and other
                indicators</strong></li>
        <li>Traders need to assess market direction <strong>within a very short time window</strong></li>
    </ul>
    <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
        <div class="label">How BF Markets supports this</div>
        <p><strong>One of the core values of BF Markets Market Activity is the fast and clear representation of market
                data</strong> — specifically for traders who need to determine where the money is moving within a very short
            time window (5–10 minutes before the race).
    </div>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Feature</th>
                <th>Benefit for Pre-Race Monitoring</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value-col">Real-time, low-latency updates</td>
                <td>See price movements as they happen in the prematch market</td>
            </tr>
            <tr>
                <td class="value-col">Momentum</td>
                <td>Shows the direction and intensity of market movement</td>
            </tr>
            <tr>
                <td class="value-col">Δ (Delta)</td>
                <td>Shows how fast Back Size is changing</td>
            </tr>
            <tr>
                <td class="value-col">FAV Badge</td>
                <td>Quickly identifies the favorite</td>
            </tr>
            <tr>
                <td class="value-col">Ranking</td>
                <td>Races starting in ≤5 minutes appear at the top</td>
            </tr>
            <tr>
                <td class="value-col">Volatility</td>
                <td>Shows the level of price fluctuation</td>
            </tr>
        </tbody>
    </table></div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.9 Ranking & Sorting</div>
    <p>Events are automatically ranked and sorted by:</p>
    <p><strong>1. Status Priority:</strong></p>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li><strong>LIVE</strong> (highest) — applies to all sports except Horse Racing</li>
        <li><strong>UPCOMING ≤ 5 min</strong></li>
        <li><strong>UPCOMING ≤ 10 min</strong></li>
        <li><strong>UPCOMING > 10 min</strong></li>
    </ul>
    <p><strong>2. Tie-breakers (within same status group):</strong></p>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li>Momentum strength</li>
        <li>Liquidity (Back Size + Lay Size)</li>
        <li>Time</li>
    </ul>
    <div class="market-activity-info-card warning [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
        <div class="label">Note</div>
        <p>Horse Racing is <strong>Prematch only</strong> — LIVE priority does not apply to Horse Racing events.</p>
    </div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.10 Filters</div>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Filter</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value-col">Live</td>
                <td>Show only in-play events</td>
            </tr>
            <tr>
                <td class="value-col">Upcoming</td>
                <td>Show only pre-match events</td>
            </tr>
            <tr>
                <td class="value-col">Favorites</td>
                <td>Show only events you have starred</td>
            </tr>
            <tr>
                <td class="value-col">HT 0-0</td>
                <td>Half-time 0-0</td>
            </tr>
            <tr>
                <td class="value-col">0-0 @ 70'</td>
                <td>0-0 at 70 minutes</td>
            </tr>
            <tr>
                <td class="value-col">Fav Losing</td>
                <td>Favorite is currently losing</td>
            </tr>
            <tr>
                <td class="value-col">Prematch Drops</td>
                <td>Significant odds drop before kick-off</td>
            </tr>
            <tr>
                <td class="value-col">Market Shock</td>
                <td>Sudden, large market movement</td>
            </tr>
            <tr>
                <td class="value-col">Fav Not Winning</td>
                <td>Favorite is not winning</td>
            </tr>
        </tbody>
    </table></div>
    <p class="[font-size:12px]! [color:#6a8aaa]!"><em>For detailed filter descriptions, see the Filter Guide.</em></p>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">4.11 Sport Selector</div>
    <p>Market Activity supports the following sports (all available on Betfair Exchange):</p>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li>Football (Soccer)</li>
        <li>Tennis</li>
        <li>Horse Racing (<strong>Prematch only</strong>)</li>
        <li>Greyhounds</li>
        <li>Basketball</li>
        <li>Cricket</li>
        <li>Ice Hockey</li>
        <li>American Football</li>
        <li>Baseball</li>
    </ul>
    </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 5 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">5</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-desktop"></i> How to Read the Terminal</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">5.1 Left Column — Event List</div>
        <div class="market-activity-example-block"><span class="hl-muted">|</span> TIME <span class="hl-muted">|</span>
            COMP <span class="hl-muted">|</span> MATCH <span class="hl-muted">|</span> <span class="hl-blue">BACK</span>
            <span class="hl-muted">|</span> <span class="hl-blue">SIZE</span> <span class="hl-muted">|</span> <span
                class="hl-pink">LAY</span> <span class="hl-muted">|</span> <span class="hl-pink">SIZE</span> <span
                class="hl-muted">|</span> BAL <span class="hl-muted">|</span> MOM <span class="hl-muted">|</span> Δ <span
                class="hl-muted">|</span> VOL <span class="hl-muted">|</span> ACTION <span class="hl-muted">|</span>
        </div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="value-col">TIME</td>
                    <td>Match status (LIVE / UP) and minute. For Horse Racing: time until race starts</td>
                </tr>
                <tr>
                    <td class="value-col">COMP</td>
                    <td>Tournament / league name</td>
                </tr>
                <tr>
                    <td class="value-col">MATCH</td>
                    <td>Home team vs Away team. For Horse Racing: race name</td>
                </tr>
                <tr>
                    <td class="back-col">BACK</td>
                    <td>Best Back Odds (Blue)</td>
                </tr>
                <tr>
                    <td class="back-col">SIZE</td>
                    <td>Back Size (Blue)</td>
                </tr>
                <tr>
                    <td class="lay-col">LAY</td>
                    <td>Best Lay Odds (Pink)</td>
                </tr>
                <tr>
                    <td class="lay-col">SIZE</td>
                    <td>Lay Size (Pink)</td>
                </tr>
                <tr>
                    <td class="value-col">BAL</td>
                    <td>Balance indicator</td>
                </tr>
                <tr>
                    <td class="value-col">MOM</td>
                    <td>Momentum indicator</td>
                </tr>
                <tr>
                    <td class="value-col">Δ</td>
                    <td>Delta (Back Size change)</td>
                </tr>
                <tr>
                    <td class="value-col">VOL</td>
                    <td>Volatility indicator</td>
                </tr>
                <tr>
                    <td class="value-col">ACTION</td>
                    <td>"Open →" to view details</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">5.2 Right Panel — Detail View</div>
    <p>When you click "Open →" on any event, the right panel shows:</p>
    <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
        <thead>
            <tr>
                <th>Section</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value-col">Header</td>
                <td>Event title, status, time / time until start, and, where applicable, score</td>
            </tr>
            <tr>
                <td class="value-col">Runners Table</td>
                <td>All runners with Back/Lay Odds and Sizes</td>
            </tr>
            <tr>
                <td class="value-col">Liquidity Distribution</td>
                <td>Back vs Lay liquidity bar</td>
            </tr>
            <tr>
                <td class="value-col">Indicators</td>
                <td>Balance, Momentum, Volatility</td>
            </tr>
        </tbody>
    </table></div>
    </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 6 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">6</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-book"></i> Glossary of Terms</span>
        </div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:#1f3444]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:#2a3f50]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:#2a3f50]! dark:[&_tbody_td]:[color:#b0c8dd]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:#0b2a40]! dark:[&_.value-col]:[color:#e8edf2]! [&_.back-col]:[color:#1a6b9c]! [&_.back-col]:[font-weight:600]! dark:[&_.back-col]:[color:#6aafdf]! [&_.lay-col]:[color:#d44a6a]! [&_.lay-col]:[font-weight:600]! dark:[&_.lay-col]:[color:#e0809a]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:#1f3444]">
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Definition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="back-col">Back</td>
                    <td>Betting FOR a selection to win (Blue)</td>
                </tr>
                <tr>
                    <td class="lay-col">Lay</td>
                    <td>Betting AGAINST a selection to win (Pink)</td>
                </tr>
                <tr>
                    <td class="value-col">Odds</td>
                    <td>Decimal price — shows the total return per unit staked</td>
                </tr>
                <tr>
                    <td class="value-col">Size</td>
                    <td>Amount of money available at the best price</td>
                </tr>
                <tr>
                    <td class="value-col">Liquidity</td>
                    <td>Total money available in the market</td>
                </tr>
                <tr>
                    <td class="value-col">Runner</td>
                    <td>A participant in a market (team, player, horse)</td>
                </tr>
                <tr>
                    <td class="value-col">Market</td>
                    <td>A specific betting market (Match Odds, Over/Under, etc.)</td>
                </tr>
                <tr>
                    <td class="value-col">Event</td>
                    <td>A single match/race containing multiple markets</td>
                </tr>
                <tr>
                    <td class="value-col">Balance</td>
                    <td>Where the money is right now</td>
                </tr>
                <tr>
                    <td class="value-col">Momentum</td>
                    <td>Where the money is moving toward</td>
                </tr>
                <tr>
                    <td class="value-col">Δ (Delta)</td>
                    <td>How fast Back Size is changing</td>
                </tr>
                <tr>
                    <td class="value-col">Volatility</td>
                    <td>How unpredictable the market is</td>
                </tr>
                <tr>
                    <td class="value-col">Favorite</td>
                    <td>Runner with the lowest Back Odds (Horse Racing)</td>
                </tr>
                <tr>
                    <td class="value-col">FAV Badge</td>
                    <td>⭐ indicator for the favorite runner</td>
                </tr>
                <tr>
                    <td class="value-col">Real-time feed</td>
                    <td>Continuous live data stream from Betfair Exchange</td>
                </tr>
                <tr>
                    <td class="value-col">Latency</td>
                    <td>Delay between data publish and display — kept low</td>
                </tr>
                <tr>
                    <td class="value-col">Prematch</td>
                    <td>Markets available before the event starts</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 7 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">7</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-flask"></i> Practical Examples</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">Example 1: Back Heavy Market (Football)</div>
        <div class="market-activity-example-block">Bayern Munich vs Dortmund — 75' (3-2)

            <span class="hl-blue">Bayern Munich</span>: Back <span class="hl-blue">2.39</span> | Back Size <span
                class="hl-blue">23.2K</span> | Lay <span class="hl-pink">2.05</span> | Lay Size <span
                class="hl-pink">4.2K</span>
            <span class="hl-blue">Dortmund</span>: Back <span class="hl-blue">3.31</span> | Back Size <span
                class="hl-blue">3.7K</span> | Lay <span class="hl-pink">4.23</span> | Lay Size <span
                class="hl-pink">10.9K</span>
            <span class="hl-blue">The Draw</span>: Back <span class="hl-blue">5.51</span> | Back Size <span
                class="hl-blue">12.5K</span> | Lay <span class="hl-pink">5.34</span> | Lay Size <span
                class="hl-pink">9.0K</span>

            <span class="hl-blue">Balance: Back Heavy</span>
            <span class="hl-muted">Momentum: Neutral</span>
            <span class="hl-red">Volatility: High</span>
        </div>
        <p><strong>Interpretation:</strong></p>
        <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Back Heavy</strong> → 22.7K on Back vs 5.4K on Lay — data shows stronger concentration toward
                Bayern's Back side</li>
            <li><strong>Momentum Neutral</strong> → No significant movement</li>
            <li><strong>Volatility High</strong> → 75th minute, 3-2 — any goal changes everything</li>
        </ul>
    </div>

    <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
        <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:#b0c8dd]">Example 2: Horse Racing Favorite (Prematch)</div>
    <div class="market-activity-example-block">Horse Racing — Upcoming
        Race starts in 4 min

        <span class="hl-blue">Thunder Bolt</span>: Back <span class="hl-blue">2.58</span> | Size <span
            class="hl-blue">10.3K</span> | Lay <span class="hl-pink">2.47</span> | Size <span
            class="hl-pink">10.6K</span> <span class="hl-orange">⭐ FAV</span>
        <span class="hl-blue">Storm Chaser 1</span>: Back <span class="hl-blue">2.73</span> | Size <span
            class="hl-blue">8.7K</span> | Lay <span class="hl-pink">2.93</span> | Size <span class="hl-pink">5.2K</span>
        <span class="hl-blue">Storm Chaser 2</span>: Back <span class="hl-blue">3.35</span> | Size <span
            class="hl-blue">2.3K</span> | Lay <span class="hl-pink">3.11</span> | Size <span class="hl-pink">6.1K</span>

        <span class="hl-muted">Balance: Balanced →</span>
        <span class="hl-muted">Momentum: Neutral →</span>
        <span class="hl-orange">Volatility: Medium 🟡</span>
    </div>
    <p><strong>Interpretation:</strong></p>
    <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
        <li><strong>Thunder Bolt is FAV</strong> → Lowest Back Odds (2.58)</li>
        <li><strong>Balanced</strong> → Money is roughly equal on both sides</li>
        <li><strong>Neutral</strong> → No significant movement</li>
        <li><strong>Medium Volatility</strong> → Moderate fluctuation</li>
        <li><strong>Race starts in 4 min</strong> → This race appears at the top due to the ranking rule (≤5 min)</li>
    </ul>
    </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 8 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">8</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-cogs"></i> Performance & Architecture</span>
        </div>
        <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Updates:</strong> The core live feed is <strong>event-driven</strong> — pushed per change, not
                polled. Auxiliary data may be refreshed through other methods.</li>
            <li><strong>History:</strong> Short rolling window per event (1s / 3s / 5s / 10s)</li>
            <li><strong>Rendering:</strong> Only changed rows are updated (selective DOM updates)</li>
            <li><strong>Ranking:</strong> Debounced — positions change only when significant movement occurs</li>
            <li><strong>Memory:</strong> Compact rolling history per event</li>
        </ul>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:#2a3f50]"></div>

    <!-- ===== SECTION 9 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:#b0c8dd] dark:[&_li]:[color:#b0c8dd] [&_strong]:[color:#0b2a40] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:#e8edf2]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white font-bold [font-size:15px] shrink-0">9</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:#0b2a40] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-check-circle"></i> Summary</span>
        </div>
        <p><strong>Market Activity is a professional-grade sports exchange monitoring terminal.</strong></p>
        <p>It is designed for:</p>
        <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:#1a6b9c] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Traders</strong> who need real-time market intelligence</li>
            <li><strong>Arbitrageurs</strong> who scan for discrepancies</li>
            <li><strong>Analysts</strong> who study market behavior</li>
            <li><strong>Bettors</strong> who want to observe where significant money flow is occurring</li>
        </ul>
        <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">Core Principle</div>
            <p><strong>It is not a betting page — it is a data terminal.</strong></p>
        </div>
    </div>

@endsection
