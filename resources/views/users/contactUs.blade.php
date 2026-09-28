@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')
    <div class="page-header [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="contact-page-title"><i class="fas fa-envelope"></i> {{ __('Contact Us') }}</h1>
        <p class="contact-page-subtitle">{{ __('Need help or have a question? Our team is available 24/7 and ready to assist you.') }}</p>
    </div>

    <div class="badges flex flex-wrap [gap:10px] [margin:20px_0_8px]">
        <span class="badge inline-flex items-center [gap:6px] [padding:8px_16px] [border-radius:30px] [font-size:12.5px] font-medium [background:#f0f7ff] [border:1px_solid_#e3ecf5] [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] max-[480px]:[font-size:11.5px] max-[480px]:[padding:6px_12px]">🔒 {{ __('GDPR Compliant') }}</span>
        <span class="badge inline-flex items-center [gap:6px] [padding:8px_16px] [border-radius:30px] [font-size:12.5px] font-medium [background:#f0f7ff] [border:1px_solid_#e3ecf5] [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] max-[480px]:[font-size:11.5px] max-[480px]:[padding:6px_12px]">⚡ {{ __('Real-time Infrastructure') }}</span>
        <span class="badge inline-flex items-center [gap:6px] [padding:8px_16px] [border-radius:30px] [font-size:12.5px] font-medium [background:#f0f7ff] [border:1px_solid_#e3ecf5] [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] max-[480px]:[font-size:11.5px] max-[480px]:[padding:6px_12px]">🏢 {{ __('Digital Analytics Group LLC') }}</span>
        <span class="badge inline-flex items-center [gap:6px] [padding:8px_16px] [border-radius:30px] [font-size:12.5px] font-medium [background:#f0f7ff] [border:1px_solid_#e3ecf5] [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] max-[480px]:[font-size:11.5px] max-[480px]:[padding:6px_12px]">📈 {{ __('99.9% Uptime SLA') }}</span>
        <span class="badge inline-flex items-center [gap:6px] [padding:8px_16px] [border-radius:30px] [font-size:12.5px] font-medium [background:#f0f7ff] [border:1px_solid_#e3ecf5] [color:#2a4d66] [transition:0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] dark:[color:#b0c8dd] max-[480px]:[font-size:11.5px] max-[480px]:[padding:6px_12px]">💬 {{ __('24/7 Customer Support') }}</span>
    </div>

    <div class="contact-grid grid [grid-template-columns:1.3fr_1fr] [gap:28px] [margin:24px_0_10px] max-[1024px]:[grid-template-columns:1fr] max-[900px]:[grid-template-columns:1fr]">
        <div class="contact-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:28px_32px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&_h2]:[font-size:20px] [&_h2]:[font-weight:700] [&_h2]:[color:#0b2a40] [&_h2]:[margin-bottom:6px] [&_h2]:[transition:color_0.3s] dark:[&_h2]:[color:#e8edf2] [&_.card-desc]:[font-size:13.5px] [&_.card-desc]:[color:#5a7d99] [&_.card-desc]:[margin-bottom:18px] [&_.card-desc]:[transition:color_0.3s] dark:[&_.card-desc]:[color:#8aaccc] max-[768px]:[padding:20px_18px] max-[768px]:[border-radius:16px] max-[480px]:[padding:16px_14px]">
            <h2>{{ __('Send Us a Message') }}</h2>
            <p class="card-desc">{{ __('Fill out the form below and our team will get back to you as soon as possible.') }}</p>

            <div class="success-message hidden items-center [gap:12px] [background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [border-left:4px_solid_#1f9a6e] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin-bottom:20px] [font-size:14px] [color:#0b2a40] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[border-left-color:#5ab88a] dark:[color:#e8edf2] [&.show]:[display:flex] [&_i]:[color:#1f9a6e] [&_i]:[font-size:20px] dark:[&_i]:[color:#5ab88a]" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <span>{{ __('Your message has been sent successfully! Thank you.') }}</span>
            </div>

            <form id="contactForm" method="POST" action="#">
                <div class="form-row grid [grid-template-columns:1fr_1fr] [gap:16px] max-[600px]:[grid-template-columns:1fr]">
                    <div class="form-group [margin-bottom:18px]">
                        <label for="name" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" class="form-input [font-family:inherit] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="{{ __('Enter your name') }}" />
                    </div>
                    <div class="form-group [margin-bottom:18px]">
                        <label for="email" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">Email Address <span class="[color:#c74e4e]!">*</span></label>
                        <input id="email" name="email" type="email" class="form-input [font-family:inherit] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="{{ __('Enter your email address') }}" required />
                    </div>
                </div>

                <div class="form-group [margin-bottom:18px]">
                    <label for="subject" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">{{ __('Subject') }}</label>
                    <input id="subject" name="subject" type="text" class="form-input [font-family:inherit] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="{{ __('Enter the subject') }}" />
                </div>

                <div class="form-group [margin-bottom:18px]">
                    <label for="message" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">{{ __('Message') }} <span class="[color:#c74e4e]!">*</span></label>
                    <textarea id="message" name="message" class="form-textarea [font-family:inherit] [resize:vertical] [min-height:140px] [line-height:1.6] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="{{ __('Write your message here...') }}" required></textarea>
                </div>

                <button type="submit" class="btn-submit inline-flex items-center justify-center [gap:8px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white border-0 [padding:12px_32px] [border-radius:60px] [font-size:14px] font-semibold cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] w-full dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(26,_107,_156,_0.3)] [&:active]:[transform:translateY(0)]">
                    <i class="fas fa-paper-plane"></i>
                    {{ __('Send Message') }}
                </button>
            </form>
        </div>

        <div>
            <div class="contact-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:28px_32px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&_h2]:[font-size:20px] [&_h2]:[font-weight:700] [&_h2]:[color:#0b2a40] [&_h2]:[margin-bottom:6px] [&_h2]:[transition:color_0.3s] dark:[&_h2]:[color:#e8edf2] [&_.card-desc]:[font-size:13.5px] [&_.card-desc]:[color:#5a7d99] [&_.card-desc]:[margin-bottom:18px] [&_.card-desc]:[transition:color_0.3s] dark:[&_.card-desc]:[color:#8aaccc] max-[768px]:[padding:20px_18px] max-[768px]:[border-radius:16px] max-[480px]:[padding:16px_14px]">
                <h2>{{ __('Contact Methods') }}</h2>

                <div class="contact-method [background:white] [border:1px_solid_#e6eff8] [border-radius:14px] [padding:16px_20px] [margin-bottom:14px] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.method-label]:[font-size:10px] [&_.method-label]:[font-weight:700] [&_.method-label]:[text-transform:uppercase] [&_.method-label]:[color:#1a6b9c] [&_.method-label]:[letter-spacing:0.5px] [&_.method-label]:[margin-bottom:6px] dark:[&_.method-label]:[color:#6aafdf] [&_a]:[font-size:14.5px] [&_a]:[font-weight:600] [&_a]:[color:#0b2a40] [&_a]:[text-decoration:none] [&_a]:[transition:color_0.2s] [&_a]:[display:inline-flex] [&_a]:[align-items:center] [&_a]:[gap:6px] [&_a:hover]:[color:#1a6b9c] dark:[&_a]:[color:#e8edf2] dark:[&_a:hover]:[color:#6aafdf]">
                    <div class="method-label">{{ __('Email Support') }}</div>
                    <a href="mailto:office@betfairmarkets.com">📧 office@betfairmarkets.com</a>
                </div>

                <div class="contact-method [background:white] [border:1px_solid_#e6eff8] [border-radius:14px] [padding:16px_20px] [margin-bottom:14px] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.method-label]:[font-size:10px] [&_.method-label]:[font-weight:700] [&_.method-label]:[text-transform:uppercase] [&_.method-label]:[color:#1a6b9c] [&_.method-label]:[letter-spacing:0.5px] [&_.method-label]:[margin-bottom:6px] dark:[&_.method-label]:[color:#6aafdf] [&_a]:[font-size:14.5px] [&_a]:[font-weight:600] [&_a]:[color:#0b2a40] [&_a]:[text-decoration:none] [&_a]:[transition:color_0.2s] [&_a]:[display:inline-flex] [&_a]:[align-items:center] [&_a]:[gap:6px] [&_a:hover]:[color:#1a6b9c] dark:[&_a]:[color:#e8edf2] dark:[&_a:hover]:[color:#6aafdf]">
                    <div class="method-label">{{ __('WhatsApp Support') }}</div>
                    <a href="https://wa.me/995511705508" target="_blank" rel="noopener" class="btn-whatsapp inline-flex items-center justify-center [gap:8px] w-full [padding:12px_20px] [background:linear-gradient(135deg,_#25D366,_#1ebe5b)] text-white border-0 [border-radius:12px] [font-size:14px] font-semibold [text-decoration:none] cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [margin-top:10px] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(37,_211,_102,_0.3)]">
                        💬 {{ __('Chat on WhatsApp') }}
                    </a>
                </div>
            </div>

            <div class="contact-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:28px_32px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [margin-top:24px]! [&_h2]:[font-size:20px] [&_h2]:[font-weight:700] [&_h2]:[color:#0b2a40] [&_h2]:[margin-bottom:6px] [&_h2]:[transition:color_0.3s] dark:[&_h2]:[color:#e8edf2] [&_.card-desc]:[font-size:13.5px] [&_.card-desc]:[color:#5a7d99] [&_.card-desc]:[margin-bottom:18px] [&_.card-desc]:[transition:color_0.3s] dark:[&_.card-desc]:[color:#8aaccc] max-[768px]:[padding:20px_18px] max-[768px]:[border-radius:16px] max-[480px]:[padding:16px_14px]">
                <h2>{{ __('24/7 Customer Support') }}</h2>
                <p class="card-desc [margin-bottom:0]!">
                    {{ __('Our team is available around the clock to assist you with platform access, subscriptions, payments, and technical questions.') }}
                </p>
            </div>

            <div class="contact-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:28px_32px] [transition:background_0.3s,_border-color_0.3s] dark:[background:#1f3444] dark:[border-color:#2a3f50] [margin-top:24px]! [&_h2]:[font-size:20px] [&_h2]:[font-weight:700] [&_h2]:[color:#0b2a40] [&_h2]:[margin-bottom:6px] [&_h2]:[transition:color_0.3s] dark:[&_h2]:[color:#e8edf2] [&_.card-desc]:[font-size:13.5px] [&_.card-desc]:[color:#5a7d99] [&_.card-desc]:[margin-bottom:18px] [&_.card-desc]:[transition:color_0.3s] dark:[&_.card-desc]:[color:#8aaccc] max-[768px]:[padding:20px_18px] max-[768px]:[border-radius:16px] max-[480px]:[padding:16px_14px]">
                <h2>{{ __('Company Information') }}</h2>
                <p class="card-desc [margin-bottom:8px]!">
                    {{ __('BF Markets is owned, developed, and operated by Digital Analytics Group LLC.') }}
                </p>
                <a href="https://digitalanalyticsgroup.com" target="_blank" rel="noopener" class="[font-size:13.5px]! [font-weight:600]! [color:#1a6b9c]! [text-decoration:none]!">
                    https://digitalanalyticsgroup.com
                </a>
            </div>
        </div>

    </div>
@endsection
