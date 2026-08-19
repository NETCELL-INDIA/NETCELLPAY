@extends('layouts.master-without-nav')
@section('title')
    Login 
@endsection
@section('content')
    @php
        $company = DB::table('companies')
            ->where('status', '1')
            ->where('domain', request()->getHost())
            ->first();
        $company = $company ?: DB::table('companies')->where('status', '1')->first();
    @endphp

    <style>
        :root {
            --login-primary: #635bff;
            --login-secondary: #00bfa6;
            --login-ink: #172033;
            --login-muted: #718096;
        }

        body {
            background: #f4f7fb;
        }

        .login-page {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 32px 16px;
            overflow: hidden;
            background:
                radial-gradient(circle at 8% 10%, rgba(0, 191, 166, .16), transparent 28%),
                radial-gradient(circle at 92% 90%, rgba(99, 91, 255, .14), transparent 30%),
                #f4f7fb;
        }

        .login-page::before,
        .login-page::after {
            position: absolute;
            width: 260px;
            height: 260px;
            content: "";
            border: 1px solid rgba(99, 91, 255, .12);
            border-radius: 50%;
        }

        .login-page::before {
            top: -110px;
            right: -70px;
        }

        .login-page::after {
            bottom: -130px;
            left: -90px;
        }

        .login-shell {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            width: min(1040px, 100%);
            min-height: 620px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(23, 32, 51, .06);
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(33, 45, 82, .14);
        }

        .login-showcase {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 52px;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(145deg, #171b3d 0%, #34308f 52%, #00a892 140%);
        }

        .login-showcase::after {
            position: absolute;
            right: -90px;
            bottom: -120px;
            width: 390px;
            height: 390px;
            content: "";
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
        }

        .brand-mark {
            display: inline-flex;
            gap: 12px;
            align-items: center;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .brand-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, #00d9bd, #7069ff);
            border-radius: 13px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, .18);
        }

        .showcase-copy {
            position: relative;
            z-index: 1;
            max-width: 410px;
        }

        .showcase-copy h1 {
            margin-bottom: 18px;
            color: #fff;
            font-size: clamp(34px, 4vw, 50px);
            font-weight: 750;
            line-height: 1.08;
            letter-spacing: -1.5px;
        }

        .showcase-copy p {
            margin: 0;
            color: rgba(255, 255, 255, .72);
            font-size: 16px;
            line-height: 1.75;
        }

        .trust-row {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 26px;
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
        }

        .trust-row span {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .trust-row i {
            color: #42e8cf;
            font-size: 18px;
        }

        .login-panel {
            display: flex;
            align-items: center;
            padding: 54px 64px;
        }

        .login-form-wrap {
            width: 100%;
        }

        .company-logo {
            display: flex;
            min-height: 54px;
            margin-bottom: 34px;
            align-items: center;
        }

        .company-logo img {
            max-width: 180px;
            max-height: 58px;
            object-fit: contain;
        }

        .login-heading {
            margin-bottom: 8px;
            color: var(--login-ink);
            font-size: 30px;
            font-weight: 750;
            letter-spacing: -.6px;
        }

        .login-subtitle {
            margin-bottom: 34px;
            color: var(--login-muted);
            font-size: 15px;
        }

        .login-label {
            margin-bottom: 9px;
            color: #39445a;
            font-size: 13px;
            font-weight: 650;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap > i {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: 17px;
            color: #98a2b3;
            font-size: 19px;
            transform: translateY(-50%);
        }

        .login-input {
            height: 52px;
            padding: 0 48px;
            color: var(--login-ink);
            background: #f8fafc;
            border: 1px solid #e7ebf1;
            border-radius: 13px;
            transition: .2s ease;
        }

        .login-input:focus {
            background: #fff;
            border-color: var(--login-primary);
            box-shadow: 0 0 0 4px rgba(99, 91, 255, .1);
        }

        .password-addon {
            top: 50% !important;
            right: 8px !important;
            color: #8791a4 !important;
            transform: translateY(-50%);
        }

        .forgot-link {
            color: var(--login-primary);
            font-size: 13px;
            font-weight: 650;
        }

        .forgot-link:hover {
            color: #4740d4;
        }

        .login-button {
            height: 52px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(110deg, var(--login-primary), #7770ff);
            border: 0;
            border-radius: 13px;
            box-shadow: 0 14px 28px rgba(99, 91, 255, .22);
            transition: .2s ease;
        }

        .login-button:hover {
            color: #fff;
            box-shadow: 0 17px 32px rgba(99, 91, 255, .3);
            transform: translateY(-1px);
        }

        .security-note {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 26px;
            color: #98a2b3;
            font-size: 12px;
        }

        @media (max-width: 860px) {
            .login-shell {
                grid-template-columns: 1fr;
                max-width: 540px;
            }

            .login-showcase {
                min-height: 245px;
                padding: 34px;
            }

            .showcase-copy h1 {
                margin: 24px 0 10px;
                font-size: 30px;
            }

            .showcase-copy p,
            .trust-row {
                display: none;
            }
        }

        @media (max-width: 520px) {
            .login-page {
                padding: 0;
                background: #fff;
            }

            .login-shell {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .login-showcase {
                min-height: 190px;
                padding: 28px 24px;
            }

            .showcase-copy h1 {
                font-size: 25px;
            }

            .login-panel {
                padding: 38px 24px 44px;
            }
        }
    </style>

    <main class="login-page">
        <section class="login-shell" aria-label="Account login">
            <aside class="login-showcase">
                <div class="brand-mark">
                    <span class="brand-icon"><i class="ri-flashlight-fill"></i></span>
                    <span>Netcell Pay</span>
                </div>

                <div class="showcase-copy">
                    <h1>Payments made simple. Business made smarter.</h1>
                    <p>Manage recharges, bill payments and financial services from one secure, reliable platform.</p>
                </div>

                <div class="trust-row">
                    <span><i class="ri-shield-check-line"></i> Secure access</span>
                    <span><i class="ri-customer-service-2-line"></i> Reliable support</span>
                </div>
            </aside>

            <div class="login-panel">
                <div class="login-form-wrap">
                    <div class="company-logo">
                        @if($company && $company->company_logo)
                            <img src="{{ rtrim(env('ADMIN_HOST'), '/') }}/company_logo/{{ $company->company_logo }}"
                                alt="{{ $company->company_name ?? 'Netcell Pay' }}">
                        @else
                            <div class="brand-mark text-dark">
                                <span class="brand-icon"><i class="ri-flashlight-fill"></i></span>
                                <span>Netcell Pay</span>
                            </div>
                        @endif
                    </div>

                    <h2 class="login-heading">Welcome back</h2>
                    <p class="login-subtitle">Sign in to continue to your account.</p>

                    <form name="form_login" id="form_login" onsubmit="event.preventDefault(); login();">
                        <div class="mb-4" id="mobile_number_div">
                            <label for="mobile_number" class="login-label">Mobile number</label>
                            <div class="login-input-wrap">
                                <i class="ri-smartphone-line"></i>
                                <input type="tel" class="form-control login-input" pattern="[0-9]{10}" maxlength="10"
                                    inputmode="numeric" autocomplete="username" id="mobile_number"
                                    placeholder="Enter your 10-digit number">
                            </div>
                        </div>

                        <div class="mb-4" id="password-input-div">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="login-label" for="password-input">Password</label>
                                <a href="{{ route('forgotPassword') }}" class="forgot-link">Forgot password?</a>
                            </div>
                            <div class="login-input-wrap auth-pass-inputgroup">
                                <i class="ri-lock-2-line"></i>
                                <input type="password" class="form-control login-input password-input"
                                    autocomplete="current-password" placeholder="Enter your password" id="password-input">
                                <button class="btn btn-link position-absolute text-decoration-none password-addon"
                                    type="button" id="password-addon" aria-label="Show password">
                                    <i class="ri-eye-fill align-middle"></i>
                                </button>
                            </div>
                        </div>

                        <div id="local_otp_hint" class="alert alert-warning py-2 px-3 mb-3" style="display:none">
                            <strong>Local OTP:</strong> <span id="local_otp_value"></span>
                            <div class="small mt-1">Use this code in the verification field below.</div>
                        </div>

                        <div class="mb-4" id="otp_code_div" style="display:none">
                            <label for="otp_code" class="login-label">Verification code</label>
                            <div class="login-input-wrap">
                                <i class="ri-key-2-line"></i>
                                <input type="text" class="form-control login-input" pattern="[0-9]{6}" maxlength="6"
                                    inputmode="numeric" autocomplete="one-time-code" id="otp_code"
                                    placeholder="Enter 6-digit OTP">
                            </div>
                        </div>

                        <div id="lg-btn-div">
                            <button class="btn login-button w-100" type="submit">
                                Sign in <i class="ri-arrow-right-line ms-1"></i>
                            </button>
                        </div>

                        <div id="otp-btn-div" style="display:none">
                            <button class="btn login-button w-100" type="button" onclick="checkLoginOtp()">
                                Verify OTP <i class="ri-shield-check-line ms-1"></i>
                            </button>
                        </div>
                    </form>

                    <div class="security-note">
                        <i class="ri-lock-line"></i>
                        <span>Your connection is protected and encrypted.</span>
                    </div>

                    <div class="login-footer">
                        &copy; <script>document.write(new Date().getFullYear())</script> {{ $company->company_name ?? 'Netcell Pay' }}. All rights reserved.
                        <div class="login-build-serial">Update Serial: {{ user_build_serial() }}</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    </div>
    <!-- end auth-page-wrapper -->

@endsection
@section('script')
    <script src="{{ URL::asset('assets/js/pages/password-addon.init.js') }}"></script>
    <!-- Sweet Alerts js -->
    <script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <!-- Sweet alert init js-->
    <script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
    <script >
        var loginLat = '';
        var loginLng = '';
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                loginLat = pos.coords.latitude;
                loginLng = pos.coords.longitude;
            }, function() {}, { enableHighAccuracy: true, timeout: 4000, maximumAge: 300000 });
        }

        function capitalizeFirstLetter(string){
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function Error_Msg(title,text,icon) {
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
            $("#otp_code_div").show();
            $("#lg-btn-div").hide();
            $("#otp-btn-div").show();

            if (localOtp) {
                $("#local_otp_value").text(localOtp);
                $("#local_otp_hint").show();
                $("#otp_code").val(localOtp);
            }
        }


       function login() {
            mobile_number = $("#mobile_number").val();
            password = $("#password-input").val();

            if (mobile_number=="") {
                Error_Msg("Error","please enter mobile number","error");
            } else if(password==""){
                Error_Msg("Error","please enter password","error");
            } else {
                $.ajax({
                    url: "{{ route('LoginCheck') }}",
                    type: 'post',
                    dataType: 'json',
                    data: { 
                            mobile_number:mobile_number,
                            password:password,
                            latitude: loginLat,
                            longitude: loginLng,
                            _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                        },
                    success: function( data, textStatus, jQxhr ){
                       console.log( data );
                       if(data.type=="error"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                        }else if(data.type=="otp_verify"){ 
                            Error_Msg("Otp Verify",data.message,"warning");
                            showOtpStep(data.local_otp || "");
                        }else if(data.type=="success"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                            window.location.replace("{{ url('/users/dashboard') }}")
                       }else{
                            Error_Msg("Oops...","Something went wrong!","error");
                       }
                    },
                    error: function( jqXhr, textStatus, errorThrown ){
                        var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message) ? jqXhr.responseJSON.message : "Something went wrong!";
                        Error_Msg("Oops...", msg, "error");
                    }
                });
            } 
       }

       function checkLoginOtp() {
            mobile_number = $("#mobile_number").val();
            password = $("#password-input").val();
            otp = $("#otp_code").val();

            if (mobile_number=="") {
                Error_Msg("Error","please enter mobile number","error");
            } else if(password==""){
                Error_Msg("Error","please enter password","error");
            } else {
                $.ajax({
                    url: "{{ route('checkLoginOtp') }}",
                    type: 'post',
                    dataType: 'json',
                    data: { 
                            mobile_number:mobile_number,
                            password:password,otp,
                            latitude: loginLat,
                            longitude: loginLng,
                            _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                        },
                    success: function( data, textStatus, jQxhr ){
                       console.log( data );
                       if(data.type=="error"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                       }else if(data.type=="success"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                            window.location.replace("{{ url('/users/dashboard') }}")
                       }else{
                            Error_Msg("Oops...","Something went wrong!","error");
                       }
                    },
                    error: function( jqXhr, textStatus, errorThrown ){
                        var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message) ? jqXhr.responseJSON.message : "Something went wrong!";
                        Error_Msg("Oops...", msg, "error");
                    }
                });
            } 
       }
           
        
    </script>
@endsection
