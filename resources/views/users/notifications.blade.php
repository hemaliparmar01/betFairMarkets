@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fab fa-telegram"></i> {{ __('Notifications') }}</h1>
        <p class="page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:13px] max-[768px]:[font-size:14px]">{{ __('Connect the Telegram bot and receive real-time sports exchange signals.') }}</p>
    </div>

    <!-- ===== TELEGRAM CONNECT CARD ===== -->
    <div class="section">
        <div class="tg-card [background:linear-gradient(135deg,_#f0f9ff,_#e6f4fc)] [border:1px_solid_#c9e5f5] [border-radius:20px] [padding:24px_28px] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[border-color:#2a4a5e] max-[768px]:[padding:18px_18px]">
            <div class="tg-card-header flex items-center [gap:16px] [margin-bottom:18px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:column] max-[480px]:[align-items:flex-start]">
                <div class="tg-logo [width:56px] [height:56px] [border-radius:14px] [background:linear-gradient(135deg,_#229ED9,_#1a6b9c)] flex items-center justify-center text-white [font-size:28px] shrink-0 [box-shadow:0_8px_20px_rgba(34,_158,_217,_0.3)] max-[768px]:[width:48px] max-[768px]:[height:48px] max-[768px]:[font-size:24px]"><i class="fab fa-telegram-plane"></i></div>
                <div class="tg-info [flex:1]">
                    <div class="tg-title [font-size:17px] font-bold [color:#0b2a40] [margin-bottom:4px] [transition:color_0.3s] dark:[color:#e8edf2]">{{ __('Telegram Notifications') }}</div>
                    <div class="tg-desc [font-size:13px] [color:#5a7d99] [transition:color_0.3s] dark:[color:#8aaccc]">{{ __('Our official bot:') }} <strong>@BFMarketsAlertsBot</strong></div>
                </div>
            </div>

            <!-- Not Connected State -->
            <div class="tg-status-bar flex items-center justify-between [gap:12px] flex-wrap [padding:14px_18px] [border-radius:14px] bg-white [border:1px_solid_#c9e5f5] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a4a5e] max-[768px]:[padding:12px_14px] max-[480px]:[flex-direction:column] max-[480px]:[align-items:stretch]" id="tgStatusDisconnected">
                <div class="tg-status-info flex items-center [gap:10px] [font-size:14px] font-semibold">
                    <span class="tg-status-dot disconnected [width:10px] [height:10px] [border-radius:50%] shrink-0 [&.connected]:[background:#1f9a6e] [&.connected]:[box-shadow:0_0_0_4px_rgba(31,_154,_110,_0.2)] [&.connected]:[animation:pulseGreen_2s_infinite] [&.disconnected]:[background:#c74e4e] [&.disconnected]:[box-shadow:0_0_0_4px_rgba(199,_78,_78,_0.15)] [&.error]:[background:#e6b422] [&.error]:[box-shadow:0_0_0_4px_rgba(230,_180,_34,_0.2)]"></span>
                    <span class="tg-status-text disconnected [&.connected]:[color:#1f9a6e] [&.disconnected]:[color:#c74e4e] [&.error]:[color:#b8860b]">{{ __('Not connected') }}</span>
                </div>
                <div class="tg-status-actions flex items-center [gap:8px] flex-wrap max-[480px]:[width:100%] max-[480px]:[&_.btn-tg]:[flex:1] max-[480px]:[&_.btn-tg]:[justify-content:center]">
                    <button class="btn-tg btn-tg-connect inline-flex items-center [gap:8px] [padding:10px_20px] [border-radius:60px] [font-size:13.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [text-decoration:none] [font-family:inherit] [background:linear-gradient(135deg,_#229ED9,_#1a6b9c)] text-white [box-shadow:0_4px_12px_rgba(34,_158,_217,_0.3)] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(34,_158,_217,_0.4)]" id="btnConnectTelegram">
                        <i class="fas fa-link"></i>
                        {{ __('Connect Telegram') }}
                    </button>
                </div>
            </div>

            <!-- Connected State -->
            <div class="tg-status-bar flex items-center justify-between [gap:12px] flex-wrap [padding:14px_18px] [border-radius:14px] bg-white [border:1px_solid_#c9e5f5] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a4a5e] max-[768px]:[padding:12px_14px] max-[480px]:[flex-direction:column] max-[480px]:[align-items:stretch]" id="tgStatusConnected" style="display:none;">
                <div class="tg-status-info flex items-center [gap:10px] [font-size:14px] font-semibold">
                    <span class="tg-status-dot connected [width:10px] [height:10px] [border-radius:50%] shrink-0 [&.connected]:[background:#1f9a6e] [&.connected]:[box-shadow:0_0_0_4px_rgba(31,_154,_110,_0.2)] [&.connected]:[animation:pulseGreen_2s_infinite] [&.disconnected]:[background:#c74e4e] [&.disconnected]:[box-shadow:0_0_0_4px_rgba(199,_78,_78,_0.15)] [&.error]:[background:#e6b422] [&.error]:[box-shadow:0_0_0_4px_rgba(230,_180,_34,_0.2)]"></span>
                    <span class="tg-status-text connected [&.connected]:[color:#1f9a6e] [&.disconnected]:[color:#c74e4e] [&.error]:[color:#b8860b]">{{ __('Connected as') }} <strong id="tgUsername">@username</strong></span>
                </div>
                <div class="tg-status-actions flex items-center [gap:8px] flex-wrap max-[480px]:[width:100%] max-[480px]:[&_.btn-tg]:[flex:1] max-[480px]:[&_.btn-tg]:[justify-content:center]">
                    <button class="btn-tg btn-tg-test inline-flex items-center [gap:8px] [padding:10px_20px] [border-radius:60px] [font-size:13.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [text-decoration:none] [font-family:inherit] bg-transparent [color:#1a6b9c] [border:1px_solid_#b8d4ec] dark:[color:#6aafdf] dark:[border-color:#2a4a5e] [&:hover]:[background:#e8f2fc] [&:hover]:[border-color:#1a6b9c] dark:[&:hover]:[background:#1a2e44] dark:[&:hover]:[border-color:#4a8ab5]" id="btnTestNotification">
                        <i class="fas fa-paper-plane"></i>
                        {{ __('Send Test Notification') }}
                    </button>
                    <button class="btn-tg btn-tg-disconnect inline-flex items-center [gap:8px] [padding:10px_20px] [border-radius:60px] [font-size:13.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [text-decoration:none] [font-family:inherit] bg-transparent [color:#c74e4e] [border:1px_solid_#f0c9c9] dark:[color:#e08080] dark:[border-color:#3a1f1f] [&:hover]:[background:#fde8e8] [&:hover]:[border-color:#e08080] dark:[&:hover]:[background:#3a1f1f]" id="btnDisconnectTelegram">
                        <i class="fas fa-unlink"></i>
                        {{ __('Disconnect') }}
                    </button>
                </div>
            </div>

            <!-- Error State -->
            <div class="tg-status-bar flex items-center justify-between [gap:12px] flex-wrap [padding:14px_18px] [border-radius:14px] bg-white [border:1px_solid_#c9e5f5] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a4a5e] max-[768px]:[padding:12px_14px] max-[480px]:[flex-direction:column] max-[480px]:[align-items:stretch]" id="tgStatusError" style="display:none;">
                <div class="tg-status-info flex items-center [gap:10px] [font-size:14px] font-semibold">
                    <span class="tg-status-dot error [width:10px] [height:10px] [border-radius:50%] shrink-0 [&.connected]:[background:#1f9a6e] [&.connected]:[box-shadow:0_0_0_4px_rgba(31,_154,_110,_0.2)] [&.connected]:[animation:pulseGreen_2s_infinite] [&.disconnected]:[background:#c74e4e] [&.disconnected]:[box-shadow:0_0_0_4px_rgba(199,_78,_78,_0.15)] [&.error]:[background:#e6b422] [&.error]:[box-shadow:0_0_0_4px_rgba(230,_180,_34,_0.2)]"></span>
                    <span class="tg-status-text error [&.connected]:[color:#1f9a6e] [&.disconnected]:[color:#c74e4e] [&.error]:[color:#b8860b]" id="tgErrorText">{{ __('Telegram connection failed') }}</span>
                </div>
                <div class="tg-status-actions flex items-center [gap:8px] flex-wrap max-[480px]:[width:100%] max-[480px]:[&_.btn-tg]:[flex:1] max-[480px]:[&_.btn-tg]:[justify-content:center]">
                    <button class="btn-tg btn-tg-connect inline-flex items-center [gap:8px] [padding:10px_20px] [border-radius:60px] [font-size:13.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [text-decoration:none] [font-family:inherit] [background:linear-gradient(135deg,_#229ED9,_#1a6b9c)] text-white [box-shadow:0_4px_12px_rgba(34,_158,_217,_0.3)] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(34,_158,_217,_0.4)]" id="btnRetryConnect">
                        <i class="fas fa-redo"></i>
                        {{ __('Retry') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="info-box flex items-start [gap:12px] [padding:14px_18px] [border-radius:12px] [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [font-size:13px] [color:#2a4d66] [margin-top:16px] [transition:0.3s] [line-height:1.6] dark:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[color:#b0c8dd] [&_i]:[color:#1a6b9c] [&_i]:[font-size:16px] [&_i]:[margin-top:2px] [&_i]:[flex-shrink:0] dark:[&_i]:[color:#6aafdf]">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>{{ __('How does it work?') }}</strong> {{ __('Click "Connect Telegram" — our official bot will open. Press') }}
                <strong>{{ __('/start') }}</strong> {{ __('and your account will be linked automatically.') }}
                <strong>{{ __('You will receive all signals') }}</strong> {{ __('directly in Telegram.') }}
            </div>
        </div>
    </div>

    <!-- ===== LIVE ALERTS GROUP ===== -->
    <div class="notif-group [margin-bottom:28px]">
        <div class="notif-group-header live flex items-center [gap:12px] [padding:12px_18px] [border-radius:12px] [margin:14px_0px] [transition:0.3s] [&.live]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.live]:[border-left:4px_solid_#c74e4e] dark:[&.live]:[background:linear-gradient(135deg,_#3a1f1f,_#3a1f2a)] [&.prematch]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.prematch]:[border-left:4px_solid_#e6b422] dark:[&.prematch]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)]">
            <span class="section-icon live"><i class="fas fa-circle"></i></span>
            <div>
                <div class="notif-group-title live [font-size:14px] font-extrabold uppercase [letter-spacing:0.5px] flex items-center [gap:8px] [&.live]:[color:#c74e4e] dark:[&.live]:[color:#e08080] [&.prematch]:[color:#b8860b] dark:[&.prematch]:[color:#e6b422]">🔴 {{ __('Live Alerts') }}</div>
                <div class="section-subtitle">{{ __('Real-time signals from in-play matches') }}</div>
            </div>
        </div>

        <div class="notif-list flex flex-col [gap:10px]">
            <!-- 1. HT 0-0 -->
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="ht_00">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __('HT 0-0 (Half Time 0-0)') }} <span class="notif-badge hot [&.hot]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.hot]:[color:#c74e4e] dark:[&.hot]:[background:#3a1f1f] dark:[&.hot]:[color:#e08080] [&.new]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.new]:[color:#1f9a6e] dark:[&.new]:[background:#1a3a2a] dark:[&.new]:[color:#5ab88a]">{{ __('LIVE') }}</span></div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Goalless at half-time — ideal for 2nd-half goal strategies.') }}</div>
                </div>
            </div>

            <!-- 2. 0-0 @ 70' -->
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="00_70">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __("0-0 @ 70' (Late Goal Radar)") }} <span class="notif-badge hot [&.hot]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.hot]:[color:#c74e4e] dark:[&.hot]:[background:#3a1f1f] dark:[&.hot]:[color:#e08080] [&.new]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.new]:[color:#1f9a6e] dark:[&.new]:[background:#1a3a2a] dark:[&.new]:[color:#5ab88a]">{{ __('LIVE') }}</span></div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Still 0-0 at or after the 70th minute — late goal opportunity.') }}</div>
                </div>
            </div>

            <!-- 3. Fav Losing -->
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="fav_losing">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __('Fav Losing (Favorite Losing)') }} <span class="notif-badge hot [&.hot]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.hot]:[color:#c74e4e] dark:[&.hot]:[background:#3a1f1f] dark:[&.hot]:[color:#e08080] [&.new]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.new]:[color:#1f9a6e] dark:[&.new]:[background:#1a3a2a] dark:[&.new]:[color:#5ab88a]">{{ __('LIVE') }}</span></div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Pre-match favorite is currently trailing in-play — potential comeback situation.') }}
                    </div>
                </div>
            </div>

            <!-- 4. Strategy: 1st Half LTD -->
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="ltd">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __('Strategy: 1st Half Lay The Draw (LTD)') }} <span class="notif-badge new [&.hot]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.hot]:[color:#c74e4e] dark:[&.hot]:[background:#3a1f1f] dark:[&.hot]:[color:#e08080] [&.new]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.new]:[color:#1f9a6e] dark:[&.new]:[background:#1a3a2a] dark:[&.new]:[color:#5ab88a]">{{ __('NEW') }}</span></div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Automated signals for high-pressure 1st Half Lay The Draw opportunities.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PREMATCH ALERTS GROUP ===== -->
    <div class="notif-group [margin-bottom:28px]">
        <div class="notif-group-header prematch flex items-center [gap:12px] [padding:12px_18px] [border-radius:12px] [margin:14px_0px] [transition:0.3s] [&.live]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.live]:[border-left:4px_solid_#c74e4e] dark:[&.live]:[background:linear-gradient(135deg,_#3a1f1f,_#3a1f2a)] [&.prematch]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.prematch]:[border-left:4px_solid_#e6b422] dark:[&.prematch]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)]">
            <span class="section-icon prematch"><i class="fas fa-clock"></i></span>
            <div>
                <div class="notif-group-title prematch [font-size:14px] font-extrabold uppercase [letter-spacing:0.5px] flex items-center [gap:8px] [&.live]:[color:#c74e4e] dark:[&.live]:[color:#e08080] [&.prematch]:[color:#b8860b] dark:[&.prematch]:[color:#e6b422]">🟡 {{ __('Prematch Alerts') }}</div>
                <div class="section-subtitle">{{ __('Pre-match market movement signals') }}</div>
            </div>
        </div>

        <div class="notif-list flex flex-col [gap:10px]">
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="prematch_drops">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __('Prematch Drops') }}</div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Early warning for significant pre-match odds movement.') }}</div>
                </div>
            </div>

            <!-- 6. Market Shock Detector -->
            <div class="notif-item flex items-start [gap:14px] [padding:16px_18px] [border-radius:14px] bg-white [border:2px_solid_#e6eff8] cursor-pointer [transition:0.25s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&:hover]:[border-color:#b8d4ec] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.08)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[box-shadow:0_6px_16px_rgba(74,_138,_181,_0.12)] max-[768px]:[padding:14px_14px] max-[768px]:[gap:12px] max-[480px]:[flex-direction:row]" data-filter="market_shock">
                <div class="notif-checkbox [width:22px] [height:22px] [border-radius:6px] [border:2px_solid_#d4e0ec] bg-white flex items-center justify-center shrink-0 [margin-top:2px] [transition:0.2s] [color:transparent] [font-size:12px] dark:[background:#1a2a38] dark:[border-color:#3a5568] max-[480px]:[width:20px] max-[480px]:[height:20px]"><i class="fas fa-check"></i></div>
                <div class="notif-content [flex:1]">
                    <div class="notif-title [font-size:15px] font-bold [color:#0b2a40] [margin-bottom:4px] flex items-center [gap:8px] flex-wrap [transition:color_0.3s] dark:[color:#e8edf2] [&_.notif-badge]:[font-size:10.5px] [&_.notif-badge]:[font-weight:800] [&_.notif-badge]:[padding:2px_8px] [&_.notif-badge]:[border-radius:20px] [&_.notif-badge]:[text-transform:uppercase] [&_.notif-badge]:[letter-spacing:0.3px] max-[768px]:[font-size:14px] max-[480px]:[font-size:13.5px]">{{ __('Market Shock Detector') }} <span class="notif-badge hot [&.hot]:[background:linear-gradient(135deg,_#fde8e8,_#fce8ee)] [&.hot]:[color:#c74e4e] dark:[&.hot]:[background:#3a1f1f] dark:[&.hot]:[color:#e08080] [&.new]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.new]:[color:#1f9a6e] dark:[&.new]:[background:#1a3a2a] dark:[&.new]:[color:#5ab88a]">{{ __('HOT') }}</span></div>
                    <div class="notif-desc [font-size:13px] [color:#5a7d99] [line-height:1.5] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:12.5px] max-[480px]:[font-size:12px]">{{ __('Sharp market movement within 1–3 minutes — rapid change detector.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SAVE BAR ===== -->
    <div class="save-bar [position:sticky] [bottom:0] flex items-center justify-between [gap:12px] flex-wrap [padding:16px_20px] [margin-top:24px] bg-white [border-top:1px_solid_#e6edf6] [border-radius:16px] [box-shadow:0_-8px_24px_rgba(0,_20,_40,_0.04)] [transition:0.3s] dark:[background:#1f3444] dark:[border-top-color:#2a3f50] dark:[box-shadow:0_-8px_24px_rgba(0,_0,_0,_0.2)] max-[768px]:[padding:12px_14px] max-[480px]:[flex-direction:column] max-[480px]:[align-items:stretch]">
        <div class="save-info [font-size:13px] [color:#5a7d99] flex items-center [gap:8px] [transition:color_0.3s] dark:[color:#8aaccc] [&_i]:[color:#1a6b9c] dark:[&_i]:[color:#6aafdf]">
            <i class="fas fa-shield-alt"></i>
            <span>{{ __('Your preferences are saved securely') }}</span>
        </div>
        <button class="btn-save inline-flex items-center [gap:8px] [padding:11px_28px] [border-radius:60px] [font-size:14px] font-bold [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(26,_107,_156,_0.3)] [&:disabled]:[opacity:0.5] [&:disabled]:[cursor:not-allowed] [&:disabled]:[transform:none] [&.saved]:[background:linear-gradient(135deg,_#1f9a6e,_#17a86b)] dark:[&.saved]:[background:linear-gradient(135deg,_#1a7a56,_#17a86b)] max-[768px]:[padding:10px_22px] max-[768px]:[font-size:13px] max-[480px]:[width:100%] max-[480px]:[justify-content:center]" id="btnSavePreferences">
            <i class="fas fa-save"></i>
            {{ __('Save Changes') }}
        </button>
    </div>
@endsection
