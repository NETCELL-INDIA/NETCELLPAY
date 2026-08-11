@extends('layouts.master-without-nav')
@section('title')
    Login
@endsection
@section('content')
@php
    $company = $company ?? null;
    $companyName = $company->company_name ?? 'NETCELL PAY';
    $companyLogo = admin_company_logo($company->company_logo ?? null);
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

            <h1>{{ $companyName }}</h1>
            <p>Secure admin portal for recharge, users, reports and fund management — all in one place.</p>

            <ul class="np-login-features">
                <li><i class="ri-shield-keyhole-line"></i> Two-step login with OTP verification</li>
                <li><i class="ri-wallet-3-line"></i> Wallet, fund transfer and live reports</li>
                <li><i class="ri-dashboard-2-line"></i> Fast dashboard for daily operations</li>
            </ul>
        </div>
    </section>

    <section class="np-login-panel">
        <div class="np-login-card">
            <div class="np-login-card-head">
                <h2>Welcome Back</h2>
                <p>Sign in to your admin account</p>
            </div>

            <div class="np-login-steps">
                <span class="np-login-step active" id="login_step_badge">
                    <i class="ri-lock-password-line"></i> Login
                </span>
                <span class="np-login-step" id="otp_step_badge">
                    <i class="ri-shield-check-line"></i> OTP Verify
                </span>
            </div>

            <form name="form_login" class="form" id="form_login" autocomplete="off">
                <div class="mb-3 np-login-field" id="mobile_number_div">
                    <label for="mobile_number" class="form-label">Mobile Number</label>
                    <div class="np-login-input-wrap">
                        <i class="ri-smartphone-line field-icon"></i>
                        <input type="tel" class="form-control" pattern="[0-9]*" id="mobile_number"
                            placeholder="10-digit mobile number" maxlength="10" inputmode="numeric">
                    </div>
                </div>

                <div class="mb-3 np-login-field" id="password-input-div">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label mb-0" for="password-input">Password</label>
                        <a href="{{ route('forgotPassword') }}" class="np-login-forgot">Forgot password?</a>
                    </div>
                    <div class="np-login-input-wrap auth-pass-inputgroup">
                        <i class="ri-lock-2-line field-icon"></i>
                        <input type="password" class="form-control pe-5 password-input"
                            placeholder="Enter your password" id="password-input">
                        <button class="btn btn-link position-absolute password-addon text-muted"
                            type="button" id="password-addon" aria-label="Show password">
                            <i class="ri-eye-fill align-middle"></i>
                        </button>
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

                <div id="local_otp_hint" class="alert alert-warning py-2 px-3 mb-3" style="display:none">
                    <strong>Local OTP:</strong> <span id="local_otp_value"></span>
                    <div class="small mt-1">Enter this same code in both Mobile OTP and Email OTP fields.</div>
                </div>

                <div class="mt-4" id="lg-btn-div">
                    <button class="btn btn-success w-100 np-login-submit" type="button" onclick="login()">
                        <i class="ri-login-circle-line me-1"></i> Sign In
                    </button>
                </div>

                <div class="mt-4" id="otp-btn-div" style="display:none">
                    <button class="btn btn-success w-100 np-login-submit" type="button" onclick="checkLoginOtp()">
                        <i class="ri-shield-check-line me-1"></i> Verify OTP
                    </button>
                </div>
            </form>

            <div class="np-login-footer">
                &copy; <script>document.write(new Date().getFullYear())</script> {{ $companyName }}. All rights reserved.
                <div class="np-login-build-serial">Update Serial: {{ admin_build_serial() }}</div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('assets/js/pages/password-addon.init.js') }}"></script>
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
                customClass: {
                    confirmButton: 'btn btn-primary w-xs mt-2',
                },
                buttonsStyling: false,
                showCloseButton: true
            });
        }

        function showOtpStep(localOtp) {
            $("#mobile_number_div").hide();
            $("#password-input-div").hide();
            $("#mobile_otp_code_div").show();
            $("#email_otp_code_div").show();
            $("#lg-btn-div").hide();
            $("#otp-btn-div").show();
            $("#login_step_badge").removeClass('active');
            $("#otp_step_badge").addClass('active');

            if (localOtp) {
                $("#local_otp_value").text(localOtp);
                $("#local_otp_hint").show();
                $("#mobile_otp_code").val(localOtp);
                $("#email_otp_code").val(localOtp);
            }
        }

        function login() {
            var mobile_number = $("#mobile_number").val();
            var password = $("#password-input").val();

            if (mobile_number === "") {
                Error_Msg("Error", "Please enter mobile number", "error");
            } else if (password === "") {
                Error_Msg("Error", "Please enter password", "error");
            } else {
                $.ajax({
                    url: "{{ route('LoginCheck') }}",
                    type: 'post',
                    data: {
                        mobile_number: mobile_number,
                        password: password,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.type === "error") {
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        } else if (data.type === "otp_verify") {
                            Error_Msg("OTP Sent", data.message, "info");
                            showOtpStep(data.local_otp || "");
                        } else {
                            Error_Msg("Oops...", "Something went wrong!", "error");
                        }
                    },
                    error: function(jqXhr) {
                        var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message)
                            ? jqXhr.responseJSON.message
                            : "Something went wrong!";
                        Error_Msg("Oops...", msg, "error");
                    }
                });
            }
        }

        function checkLoginOtp() {
            var mobile_number = $("#mobile_number").val();
            var password = $("#password-input").val();
            var mobile_otp = $("#mobile_otp_code").val();
            var email_otp = $("#email_otp_code").val();

            if (mobile_number === "") {
                Error_Msg("Error", "Please enter mobile number", "error");
            } else if (password === "") {
                Error_Msg("Error", "Please enter password", "error");
            } else if (mobile_otp === "") {
                Error_Msg("Error", "Please enter mobile OTP", "error");
            } else if (email_otp === "") {
                Error_Msg("Error", "Please enter email OTP", "error");
            } else {
                $.ajax({
                    url: "{{ route('checkLoginOtp') }}",
                    type: 'post',
                    data: {
                        mobile_number: mobile_number,
                        password: password,
                        email_otp: email_otp,
                        mobile_otp: mobile_otp,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.type === "error") {
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        } else if (data.type === "success") {
                            Error_Msg("Success", data.message, "success");
                            window.location.replace("{{ url('admin/dashboard') }}");
                        } else {
                            Error_Msg("Oops...", "Something went wrong!", "error");
                        }
                    },
                    error: function(jqXhr) {
                        var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message)
                            ? jqXhr.responseJSON.message
                            : "Something went wrong!";
                        Error_Msg("Oops...", msg, "error");
                    }
                });
            }
        }

        $("#form_login").on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                if ($("#otp-btn-div").is(':visible')) {
                    checkLoginOtp();
                } else {
                    login();
                }
            }
        });
    </script>
@endsection
