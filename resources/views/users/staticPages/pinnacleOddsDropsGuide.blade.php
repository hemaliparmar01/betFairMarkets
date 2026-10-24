@extends('users.layout.main')

@section('title', 'Pinnacle Odds Drops Guide · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:var(--color-primary)] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-arrow-trend-down"></i> Pinnacle Odds Drops Guide</h1>
        <p class="page-subtitle [font-size:14px] [color:var(--color-text-muted)] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:13px] max-[768px]:[font-size:14px]">BF Markets — The Data Terminal for Sports Exchange Markets</p>
    </div>

    <!-- ===== SECTION 1 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">1</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-info-circle"></i> What are Pinnacle Odds Drops?</span>
        </div>
        <p><strong>Pinnacle Odds Drops</strong> is a real-time monitoring dashboard that shows Pinnacle odds drops and compares them with Betfair Exchange market prices.</p>
        <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">In simple terms</div>
            <p>Pinnacle Odds Drops shows <strong>which match had an odds drop</strong>, <strong>how big the drop was</strong>, <strong>what Pinnacle and Betfair prices are right now</strong>, and <strong>whether there's an Edge (value)</strong>.</p>
        </div>
        <p>Unlike a traditional betting page, Pinnacle Odds Drops is not designed for placing bets. It is a <strong>monitoring tool</strong> — built for traders to quickly spot where the price moved.</p>
        <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">Why is Pinnacle special?</div>
            <p>Pinnacle is considered one of the world's sharpest and most accurate bookmakers. Changes in its odds directly reflect the real market situation and value.</p>
        </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 2 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">2</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-bullseye"></i> Purpose</span>
        </div>
        <p>The page answers four key questions instantly:</p>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Indicator</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Which match had an odds drop?</td>
                    <td class="value-col">Drop %</td>
                </tr>
                <tr>
                    <td>How big was the drop?</td>
                    <td class="value-col">NVP</td>
                </tr>
                <tr>
                    <td>What are Pinnacle and Betfair prices?</td>
                    <td class="value-col">Edge %</td>
                </tr>
                <tr>
                    <td>Is there an Edge (value)?</td>
                    <td class="value-col">Edge %</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 3 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">3</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-users"></i> Who Uses Pinnacle Odds Drops?</span>
        </div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
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
                    <td>Identify odds drops</td>
                    <td>Monitor Drop % and Edge %</td>
                </tr>
                <tr>
                    <td><strong>Arbitrageurs</strong></td>
                    <td>Find price discrepancies</td>
                    <td>Compare Pinnacle and Betfair prices</td>
                </tr>
                <tr>
                    <td><strong>Value Bettors</strong></td>
                    <td>Find value opportunities</td>
                    <td>Look for positive Edge %</td>
                </tr>
                <tr>
                    <td><strong>Data Analysts</strong></td>
                    <td>Study market behavior</td>
                    <td>Analyze drop patterns</td>
                </tr>
                <tr>
                    <td><strong>Casual Bettors</strong></td>
                    <td>Identify interesting matches</td>
                    <td>See where the money is moving</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 4 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">4</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-sliders-h"></i> Key Features</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.1 Drop % — Odds Drop Percentage</div>
            <p><strong>Definition:</strong> Drop % shows how much the Pinnacle odds dropped.</p>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">Example: Pinnacle <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">2.20</span> → <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">1.95</span> = <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">-11.4%</span></div>
            <p>This means Pinnacle reduced the odds from 2.20 to 1.95 — an 11.4% drop.</p>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.2 NVP (No-Vig Price) — Fair Price</div>
            <p><strong>Definition:</strong> NVP is the fair price — the odds without the bookmaker's margin (vig).</p>
            <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
                <div class="label">Example</div>
                <p>NVP = 2.02 → This is the fair price without the bookmaker's margin.</p>
            </div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.3 Pinnacle Edge — Pinnacle Value</div>
            <p><strong>Definition:</strong> Pinnacle Edge shows Pinnacle's price deviation from the fair price.</p>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">Example: <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">Pinnacle Edge</span> = (1.95 / 2.02 - 1) × 100 = <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">-3.47%</span></div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.4 Betfair Edge — Betfair Value</div>
            <p><strong>Definition:</strong> Betfair Edge shows Betfair's Back price deviation from the fair price.</p>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">Example: <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">Betfair Edge</span> = (2.14 / 2.02 - 1) × 100 = <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">+5.94%</span></div>
            <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
                <div class="label">Interpretation</div>
                <p><span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">> +3%</span> → Green (value)</p>
                <p><span class="hl-orange font-semibold text-[#b98400] dark:text-[#f4c75b]">0% - +3%</span> → Yellow (borderline)</p>
                <p><span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">< 0%</span> → Grey (no value)</p>
            </div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.5 Live vs Prematch Modes</div>
            <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
                <thead>
                    <tr>
                        <th>Mode</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="value-col">Prematch</td>
                        <td>Before the match starts</td>
                    </tr>
                    <tr>
                        <td class="value-col">Live</td>
                        <td>During the match</td>
                    </tr>
                </tbody>
            </table></div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.6 Sport Filters</div>
            <p>The page supports the following sports:</p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li>⚽ Soccer</li>
                <li>🎾 Tennis</li>
                <li>🏀 Basketball</li>
                <li>🏒 Ice Hockey</li>
                <li>⚾ Baseball</li>
                <li>🏏 Cricket</li>
                <li>🎮 Esports</li>
            </ul>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.7 Simple vs Advanced View</div>
            <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
                <thead>
                    <tr>
                        <th>View</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="value-col">Simple</td>
                        <td>Compact view — one row per alert</td>
                    </tr>
                    <tr>
                        <td class="value-col">Advanced</td>
                        <td>Detailed view — more info (NVP, Pinnacle Edge, Betfair sizes)</td>
                    </tr>
                </tbody>
            </table></div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.8 Group by Match</div>
            <p>If a match has multiple alerts (e.g. 4 different totals), they are grouped into one card:</p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li><strong>Header:</strong> Match + sport + league + alert count</li>
                <li><strong>Click header:</strong> Collapse / Expand</li>
                <li><strong>Inside:</strong> Individual alerts</li>
            </ul>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">4.9 Detail Overlay</div>
            <p>Clicking any alert opens a detailed popup:</p>
            <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="value-col">Top Block</td>
                        <td>Drop, Pinnacle, NVP, Pinnacle Edge, Betfair Back/Lay, Sizes, Betfair Edge, Time</td>
                    </tr>
                    <tr>
                        <td class="value-col">Full Market</td>
                        <td>Table (Outcome, Pinnacle, NVP, Betfair, Edge)</td>
                    </tr>
                    <tr>
                        <td class="value-col">Recent Alerts</td>
                        <td>Last 5 alerts for the same match</td>
                    </tr>
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 5 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">5</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-desktop"></i> How to Read the Terminal</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">5.1 Column Headers</div>
        <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]"><span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> DROP <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> MATCH · MARKET <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> PINNACLE ODDS <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> NVP <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> BETFAIR <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> EDGE % <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span></div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="value-col">DROP</td>
                    <td>Drop percentage (red)</td>
                </tr>
                <tr>
                    <td class="value-col">MATCH</td>
                    <td>Match + market</td>
                </tr>
                <tr>
                    <td class="value-col">PINNACLE ODDS</td>
                    <td>Old → new odds</td>
                </tr>
                <tr>
                    <td class="value-col">NVP</td>
                    <td>No-Vig Price (fair price)</td>
                </tr>
                <tr>
                    <td class="value-col">BETFAIR</td>
                    <td>Betfair odds</td>
                </tr>
                <tr>
                    <td class="value-col">EDGE %</td>
                    <td>Edge percentage</td>
                </tr>
            </tbody>
        </table></div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">5.2 Alert Row Example</div>
        <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">
            <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">-11.4%</span> <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> Inter v Napoli · Moneyline <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">2.20 → 1.95</span> <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">2.02</span> <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">2.14</span> <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">|</span> <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">+5.9%</span>
        </div>
        </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 6 ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">6</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-book"></i> Glossary of Terms</span>
        </div>
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain rounded-xl" role="region" aria-label="Scrollable guide table" tabindex="0"><table class="market-activity-guide-table min-w-[480px] w-full [border-collapse:collapse] [margin:12px_0_16px] [font-size:13px] [border-radius:12px] overflow-hidden [box-shadow:0_2px_8px_rgba(0,0,0,0.03)] [&_thead_th]:[background:#f0f6fc]! [&_thead_th]:[color:#1e4763]! [&_thead_th]:[font-weight:600]! [&_thead_th]:[font-size:11px]! [&_thead_th]:[text-transform:uppercase]! [&_thead_th]:[letter-spacing:0.3px]! [&_thead_th]:[padding:10px_14px]! [&_thead_th]:[text-align:left]! [&_thead_th]:[border-bottom:2px_solid_#e3ecf5]! [&_thead_th]:[transition:0.3s]! dark:[&_thead_th]:[background:var(--color-surface-raised-dark)]! dark:[&_thead_th]:[color:#8aaccc]! dark:[&_thead_th]:[border-bottom-color:var(--color-border-dark)]! [&_tbody_td]:[padding:9px_14px]! [&_tbody_td]:[border-bottom:1px_solid_#eef2f8]! [&_tbody_td]:[color:#2a4d66]! [&_tbody_td]:[transition:0.3s]! [&_tbody_td]:[vertical-align:top]! dark:[&_tbody_td]:[border-bottom-color:var(--color-border-dark)]! dark:[&_tbody_td]:[color:var(--color-text-muted-dark)]! [&_.value-col]:[font-weight:600]! [&_.value-col]:[color:var(--color-primary)]! dark:[&_.value-col]:[color:var(--color-text-dark)]! [&_tbody_tr:hover_td]:[background:#fafdff] dark:[&_tbody_tr:hover_td]:[background:var(--color-surface-raised-dark)]">
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Definition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="value-col">Drop</td>
                    <td>Odds movement to a lower price</td>
                </tr>
                <tr>
                    <td class="value-col">NVP</td>
                    <td>No-Vig Price — fair price without margin</td>
                </tr>
                <tr>
                    <td class="value-col">Edge</td>
                    <td>Value percentage vs fair price</td>
                </tr>
                <tr>
                    <td class="value-col">Pinnacle</td>
                    <td>Bookmaker known for sharp odds</td>
                </tr>
                <tr>
                    <td class="value-col">Betfair</td>
                    <td>Exchange market for Back/Lay prices</td>
                </tr>
                <tr>
                    <td class="value-col">Sharp Money</td>
                    <td>Money from professional bettors</td>
                </tr>
                <tr>
                    <td class="value-col">CLV</td>
                    <td>Closing Line Value — value vs closing line</td>
                </tr>
                <tr>
                    <td class="value-col">Back</td>
                    <td>Betting FOR a selection</td>
                </tr>
                <tr>
                    <td class="value-col">Lay</td>
                    <td>Betting AGAINST a selection</td>
                </tr>
                <tr>
                    <td class="value-col">Live</td>
                    <td>During the match</td>
                </tr>
                <tr>
                    <td class="value-col">Prematch</td>
                    <td>Before the match starts</td>
                </tr>
                <tr>
                    <td class="value-col">Alert</td>
                    <td>Notification of a market movement</td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 7 — NEW: How to Use This Strategy Effectively? ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">7</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-lightbulb"></i> How to Use This Strategy Effectively?</span>
        </div>
        <p>Monitoring Pinnacle odds drops is <strong>one of the most powerful tools</strong> in sports betting. Here are four main reasons why this is critically important:</p>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">7.1 Following "Sharp Money"</div>
            <p>Pinnacle stands out for its very high limits and the fact that it does not block professional players. When odds drop sharply, it almost always means that professional syndicates or large bettors have placed significant money on a specific outcome. Knowing this information allows you to follow the market experts' trend.</p>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">7.2 Reflection of Insider Information</div>
            <p>Sharp odds drops often happen before the news becomes public. This could be:</p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li>Sudden injury or disqualification of a key player</li>
                <li>Internal team conflict or a coaching change</li>
                <li>Sharp change in motivation (e.g., if a competitor lost points)</li>
            </ul>
            <p>Pinnacle's algorithms and users react instantly, giving you an advantage.</p>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">7.3 Finding Value Bets</div>
            <p>This is the most practical benefit:</p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li>When the Pinnacle odds drop (e.g., from 2.10 to 1.80), other, softer bookmakers are often slow to react</li>
                <li>You can find a local or less popular bookmaker where the old, higher odds (2.10) are still available</li>
                <li>This way, you place a bet at odds higher than their real probability, which means guaranteed profit in the long run (CLV — Closing Line Value)</li>
            </ul>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">
                Example: Pinnacle <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">2.10 → 1.80</span>
                Soft Bookmaker still <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">2.10</span>
                → <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">Value Bet</span> (value exists)
            </div>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">7.4 Match-Fixing Indicator</div>
            <p>In lower leagues or less popular sports, <strong>anomalous and sharp odds drops</strong> often indicate suspicious or fixed matches. If odds drop without any logical reason, it is a signal that something unsportsmanlike is happening in the market.</p>
            <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
                <div class="label">How BF Markets Helps</div>
                <p>BF Markets' <strong>Pinnacle Odds Drops</strong> page monitors exactly this strategy. It automatically records Pinnacle odds drops, analyzes Betfair Exchange prices, and displays <strong>Edge %</strong> — allowing you to:</p>
                <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                    <li>Quickly see where "sharp money" is moving</li>
                    <li>Discover Value Bets by Edge %</li>
                    <li>Receive signals about anomalous drops</li>
                    <li>Get automated alerts on Telegram</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 8 — Practical Examples ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">8</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-flask"></i> Practical Examples</span>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">Example 1: Sharp Drop (Football)</div>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">
                Inter vs Napoli — Moneyline

                Pinnacle: <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">2.20 → 1.95</span> (<span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">-11.4%</span>)
                NVP: <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">2.02</span>
                Betfair Back: <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">2.14</span>
                Betfair Edge: <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">+5.9%</span>
            </div>
            <p><strong>Interpretation:</strong></p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li><strong>Drop -11.4%</strong> → Pinnacle sharply reduced the odds — sharp money has entered</li>
                <li><strong>Betfair Edge +5.9%</strong> → Betfair price is above fair price — value exists</li>
                <li><strong>NVP 2.02</strong> → Fair price</li>
            </ul>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">Example 2: Prematch Drop (Basketball)</div>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">
                Sunshine Coast Phoenix vs Cairns Dolphins — Moneyline

                Pinnacle: <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">2.86 → 2.70</span> (<span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">-5.6%</span>)
                NVP: <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">3.04</span>
                Betfair: <span class="hl-muted text-[var(--color-text-muted)] dark:text-[#8aaccc]">not matched</span>
            </div>
            <p><strong>Interpretation:</strong></p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li><strong>Drop -5.6%</strong> → Small drop detected</li>
                <li><strong>Betfair not matched</strong> → Betfair price unavailable</li>
                <li><strong>NVP 3.04</strong> → Fair price</li>
            </ul>
        </div>

        <div class="market-activity-subsection [margin-bottom:24px] [padding-left:8px]">
            <div class="market-activity-subsection-title [font-size:16px] font-semibold [color:#1e4763] [margin-bottom:10px] [transition:color_0.3s] dark:[color:var(--color-text-muted-dark)]">Example 3: Value Bet (Soft Bookmaker vs Pinnacle)</div>
            <div class="market-activity-example-block my-3 max-w-full overflow-x-auto rounded-xl border border-[#dce8f4] bg-[#f5f9fd] p-4 font-mono text-[13px] leading-relaxed whitespace-pre-line text-[#2a4d66] dark:border-[var(--color-border-dark)] dark:bg-[#152635] dark:text-[var(--color-text-muted-dark)]">
                Pinnacle: <span class="hl-red font-semibold text-[#dc4c64] dark:text-[#f08b9c]">2.10 → 1.80</span> (-14.3%)
                Soft Bookmaker: still <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">2.10</span>

                NVP: <span class="hl-blue font-semibold text-[var(--color-primary-accent)] dark:text-[#6aafdf]">1.85</span>
                Soft Bookmaker Edge: <span class="hl-green font-semibold text-[#1da878] dark:text-[#4adeaa]">+13.5%</span>
            </div>
            <p><strong>Interpretation:</strong></p>
            <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
                <li><strong>Pinnacle reduced the odds</strong> → The market is changing direction</li>
                <li><strong>Soft Bookmaker still has old odds</strong> → Opportunity</li>
                <li><strong>+13.5% Edge</strong> → Value exists — CLV</li>
            </ul>
        </div>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 9 — Performance ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">9</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-cogs"></i> Performance &amp; Architecture</span>
        </div>
        <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Updates:</strong> Real-time, event-driven</li>
            <li><strong>Live and Prematch:</strong> Both modes run in parallel</li>
            <li><strong>Latency:</strong> Kept low for fast decision-making</li>
            <li><strong>History:</strong> Short rolling window per event</li>
        </ul>
    </div>

    <div class="market-activity-divider h-px [background:#e6edf6] [margin:28px_0] [transition:background_0.3s] dark:[background:var(--color-border-dark)]"></div>

    <!-- ===== SECTION 10 — Summary ===== -->
    <div class="market-activity-section [margin:32px_0] [&_p]:[font-size:15px] [&_p]:[color:#2a4d66] [&_p]:[margin-bottom:10px] [&_p]:[transition:color_0.3s] [&_li]:[font-size:15px] [&_li]:[color:#2a4d66] [&_li]:[margin-bottom:10px] [&_li]:[transition:color_0.3s] dark:[&_p]:[color:var(--color-text-muted-dark)] dark:[&_li]:[color:var(--color-text-muted-dark)] [&_strong]:[color:var(--color-primary)] [&_strong]:[font-weight:600] [&_strong]:[transition:color_0.3s] dark:[&_strong]:[color:var(--color-text-dark)]">
        <div class="market-activity-section-header flex items-center [gap:12px] [margin-bottom:18px] [padding-bottom:12px] [border-bottom:2px_solid_#e6edf6] [transition:border-color_0.3s] dark:[border-bottom-color:var(--color-border-dark)]">
            <span class="market-activity-section-number inline-flex items-center justify-center [width:36px] [height:36px] [border-radius:10px] [background:linear-gradient(135deg,_var(--color-primary),_var(--color-primary-accent))] text-white font-bold [font-size:15px] shrink-0">10</span>
            <span class="market-activity-section-title [font-size:22px] font-bold [color:var(--color-primary)] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:var(--color-text-dark)] [&_i]:[color:#dc4c64] [&_i]:[margin-right:8px] [&_i]:[font-size:20px] max-[768px]:[font-size:18px]"><i class="fas fa-check-circle"></i> Summary</span>
        </div>
        <p><strong>Pinnacle Odds Drops is a professional-grade market monitoring tool.</strong></p>
        <p>It is designed for:</p>
        <ul class="guide-list [&_li::before]:content-['▸'] list-none p-0 [margin:8px_0_14px] [&_li]:[padding:5px_0_5px_22px] [&_li]:[position:relative] [&_li]:[font-size:13px] [&_li::before]:[position:absolute] [&_li::before]:[left:4px] [&_li::before]:[color:var(--color-primary-accent)] [&_li::before]:[font-weight:700] dark:[&_li::before]:[color:#4a8ab5]">
            <li><strong>Traders</strong> who need real-time market intelligence</li>
            <li><strong>Arbitrageurs</strong> who scan for price discrepancies</li>
            <li><strong>Analysts</strong> who study market behavior</li>
            <li><strong>Value Bettors</strong> who are looking for value</li>
        </ul>
        <div class="market-activity-info-card [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_var(--color-primary-accent)] [border-radius:0_12px_12px_0] [padding:16px_20px] [margin:14px_0_18px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_var(--color-surface-raised-dark))] dark:[border-left-color:#4a8ab5] [&_p]:[margin-bottom:6px]! [&_p]:[font-size:14px]! [&_.label]:[font-size:11px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:var(--color-primary-accent)] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:6px] dark:[&_.label]:[color:#6aafdf]">
            <div class="label">Core Principle</div>
            <p><strong>It is not a betting page — it is a data terminal.</strong></p>
        </div>
    </div>

@endsection
