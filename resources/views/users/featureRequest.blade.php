@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header !grid [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-lightbulb"></i> {{ __('Feature Request') }}</h1>
        <p class="page-subtitle [font-size:14px] [color:#5a7d99] [margin-top:6px] [transition:color_0.3s] dark:[color:#8aaccc] max-[768px]:[font-size:13px] max-[768px]:[font-size:14px]">{{ __('Share your idea with us. We will store it and send it to the admin for review.') }}</p>
    </div>

    <!-- ===== FORM ===== -->
    <div class="feature-request-form-card [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:20px] [padding:28px_32px] [margin:24px_0_10px] [transition:background_0.3s,_border-color_0.3s]">

        <div class="success-message hidden items-center [gap:12px] [background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [border-left:4px_solid_#1f9a6e] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin-bottom:20px] [font-size:14px] [color:#0b2a40] [transition:0.3s] dark:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[border-left-color:#5ab88a] dark:[color:#e8edf2] [&.show]:[display:flex] [&_i]:[color:#1f9a6e] [&_i]:[font-size:20px] dark:[&_i]:[color:#5ab88a]" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <span>{{ __('Your request has been submitted successfully! Thank you.') }}</span>
        </div>

        <form id="featureRequestForm" method="POST" action="#">
            <div class="form-group [margin-bottom:18px]">
                <label for="email" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">{{ __('Email') }} <span class="required">*</span></label>
                <input id="email" type="email" name="email" class="form-input [font-family:inherit] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="Enter your email" required />
            </div>

            <div class="form-group [margin-bottom:18px]">
                <label for="message" class="form-label block [font-size:13.5px] font-semibold [color:#0b2a40] [margin-bottom:8px] [transition:color_0.3s] dark:[color:#e8edf2] max-[480px]:[font-size:13px]">Feature Request <span class="required">*</span></label>
                <textarea id="message" name="message" class="form-textarea [font-family:inherit] [resize:vertical] [min-height:140px] [line-height:1.6] [width:100%] [padding:12px_16px] [font-size:14px] [color:#0b2a40] [background:white] [border:1px_solid_#d4e0ec] [border-radius:12px] [outline:none] [transition:border-color_0.2s,_box-shadow_0.2s,_background_0.3s,_color_0.3s] dark:[background:#1a2a38] dark:[border-color:#3a5568] dark:[color:#e8edf2] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[&:focus]:[border-color:#4a8ab5] dark:[&:focus]:[box-shadow:0_0_0_3px_rgba(74,_138,_181,_0.2)] [&::placeholder]:[color:#8aaccc] dark:[&::placeholder]:[color:#5a7d99] max-[480px]:[padding:10px_14px] max-[480px]:[font-size:13px]" placeholder="Describe your idea or desired functionality..." required></textarea>
            </div>

            <button type="submit" class="btn-submit !w-auto inline-flex items-center justify-center [gap:8px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white border-0 [padding:12px_32px] [border-radius:60px] [font-size:14px] font-semibold cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] w-full dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-2px)] [&:hover]:[box-shadow:0_8px_20px_rgba(26,_107,_156,_0.3)] [&:active]:[transform:translateY(0)]">
                <i class="fas fa-paper-plane"></i>
                {{ __('Submit Request') }}
            </button>
        </form>

        <div class="feature-request-info-box [background:linear-gradient(135deg,_#f0f7ff,_#e8f2fc)] [border-left:4px_solid_#1a6b9c] [border-radius:0_12px_12px_0] [padding:14px_18px] [margin:20px_0_0] [transition:0.3s] [&_.label]:[font-size:10px] [&_.label]:[font-weight:700] [&_.label]:[text-transform:uppercase] [&_.label]:[color:#1a6b9c] [&_.label]:[letter-spacing:0.5px] [&_.label]:[margin-bottom:4px] [&_p]:[font-size:13.5px] [&_p]:[color:#2a4d66] [&_p]:[margin:0]">
            <div class="label">💡 {{ __('How it works') }}</div>
            <p>{{ __('All requests are stored and forwarded to the administration for review. We carefully review your ideas, and they may be added to the platform in the future.') }}</p>
        </div>

    </div>
@endsection
