@extends('layouts.master-without-nav')
@section('title')
    Forgot Password
@endsection
@section('content')
@php
    $company = $company ?? null;
    $companyName = $company->company_name ?? 'NETCELL PAY';
    $companyLogo = !empty($company->company_logo)
        ? rtrim(env('APP_URL'), '/') . '/company_logo/' . $company->company_logo
        : null;
@endphp

<div class="np-login-page">
    <section class="np-login-brand">
        <div class="np-login-brand-inner">
            <div class="np-login-brand-logo">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                @else
                    <span class="np-login-brand-mark">NP</span>
                @endif
            </div>
            <h1>Reset Password</h1>
            <p>Enter your registered admin mobile number. We will send OTP to verify and reset your password.</p>
            <ul class="np-login-features">
                <li><i class="ri-shield-keyhole-line"></i> Secure OTP verification</li>
                <li><i class="ri-smartphone-line"></i> Mobile and email OTP</li>
                <li><i class="ri-lock-unlock-line"></i> New password sent after verify</li>
            </ul>
        </div>
    </section>

    <section class="np-login-panel">
        <div class="np-login-card">
            <div class="np-login-card-head">
                <h2>Forgot Password</h2>
                <p>Recover your admin account access</p>
            </div>

            <form name="form_login" class="form" id="form_login" autocomplete="off">
                <div class="mb-3 np-login-field" id="mobile_number_div">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label for="mobile_number" class="form-label mb-0">Mobile Number</label>
                        <a href="javascript:void(0)" class="np-login-forgot" onclick="sendOtpForgotPassword()">Generate OTP</a>
                    </div>
                    <div class="np-login-input-wrap">
                        <i class="ri-smartphone-line field-icon"></i>
                        <input type="tel" class="form-control" pattern="[0-9]*" id="mobile_number"
                            placeholder="10-digit mobile number" maxlength="10" inputmode="numeric">
                    </div>
                </div>

                <div class="mb-3 np-login-field" id="mobile_otp_code_div" style="display:none">
                    <label for="mobile_otp_code" class="form-label">Mobile OTP</label>
                    <div class="np-login-input-wrap">
                        <i class="ri-message-2-line field-icon"></i>
                        <input type="tel" class="form-control" pattern="[0-9]*" id="mobile_otp_code"
                            placeholder="Enter mobile OTP" maxlength="6" inputmode="numeric">
                    </div>
                </div>

                <div class="mb-3 np-login-field" id="email_otp_code_div" style="display:none">
                    <label for="email_otp_code" class="form-label">Email OTP</label>
                    <div class="np-login-input-wrap">
                        <i class="ri-mail-line field-icon"></i>
                        <input type="tel" class="form-control" pattern="[0-9]*" id="email_otp_code"
                            placeholder="Enter email OTP" maxlength="6" inputmode="numeric">
                    </div>
                </div>

                <div class="mt-4" id="lg-btn-div">
                    <button class="btn btn-success w-100 np-login-submit" type="button" onclick="verifyOtpForgotPassword()">
                        <i class="ri-shield-check-line me-1"></i> Verify OTP
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('loginPage') }}" class="np-login-forgot"><i class="ri-arrow-left-line"></i> Back to Login</a>
            </div>

            @if(app()->environment('local'))
                <div class="np-login-note" id="local_otp_hint" style="display:none">
                    Local development OTP: use <strong>123456</strong> in both fields.
                </div>
            @endif

            <div class="np-login-footer">
                &copy; <script>document.write(new Date().getFullYear())</script> {{ $companyName }}. All rights reserved.
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
    <script>
        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function Error_Msg(title, text, icon) {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonClass: 'btn btn-primary w-xs mt-2',
                buttonsStyling: false,
                showCloseButton: true
            });
        }

        function showOtpFields() {
            $("#mobile_otp_code_div").show();
            $("#email_otp_code_div").show();
            $("#local_otp_hint").show();
        }

        function sendOtpForgotPassword() {
            var mobile_number = $("#mobile_number").val();
            if (mobile_number === "") {
                Error_Msg("Error", "Please enter mobile number", "error");
                return;
            }

            $.ajax({
                url: "{{ route('sendOtpForgotPassword') }}",
                type: 'post',
                data: { mobile_number: mobile_number, _token: '{{ csrf_token() }}' },
                success: function(data) {
                    if (data.type === "error") {
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    } else if (data.type === "otp_verify") {
                        Error_Msg("OTP Sent", data.message, "info");
                        showOtpFields();
                    } else {
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                },
                error: function() {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }

        function verifyOtpForgotPassword() {
            var mobile_number = $("#mobile_number").val();
            var mobile_otp = $("#mobile_otp_code").val();
            var email_otp = $("#email_otp_code").val();

            if (mobile_number === "") {
                Error_Msg("Error", "Please enter mobile number", "error");
            } else if (mobile_otp === "") {
                Error_Msg("Error", "Please enter mobile OTP", "error");
            } else if (email_otp === "") {
                Error_Msg("Error", "Please enter email OTP", "error");
            } else {
                $.ajax({
                    url: "{{ route('verifyOtpForgotPassword') }}",
                    type: 'post',
                    data: { mobile_number: mobile_number, email_otp: email_otp, mobile_otp: mobile_otp, _token: '{{ csrf_token() }}' },
                    success: function(data) {
                        if (data.type === "error") {
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        } else if (data.type === "success") {
                            Error_Msg("Success", data.message, "success");
                            setTimeout(function() {
                                window.location.href = "{{ route('loginPage') }}";
                            }, 2000);
                        } else {
                            Error_Msg("Oops...", "Something went wrong!", "error");
                        }
                    },
                    error: function() {
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                });
            }
        }
    </script>
@endsection
