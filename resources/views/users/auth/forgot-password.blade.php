@extends('layouts.master-without-nav')
@section('title')
    Forgot password 
@endsection
@section('content')
    @php
        $company = DB::table('companies')
            ->where('status', '1')
            ->where('domain', request()->getHost())
            ->first();
        $company = $company ?: DB::table('companies')->where('status', '1')->first();
    @endphp

    <main class="login-page">
        <section class="login-shell" aria-label="Password recovery">
            <aside class="login-showcase">
                <div class="brand-mark">
                    <span class="brand-icon"><i class="ri-shield-keyhole-fill"></i></span>
                    <span>Netcell Pay</span>
                </div>

                <div class="showcase-copy">
                    <h1>Recover access securely.</h1>
                    <p>Verify your registered mobile number and email to receive a secure temporary password.</p>
                </div>

                <div class="trust-row">
                    <span><i class="ri-shield-check-line"></i> Verified recovery</span>
                    <span><i class="ri-lock-2-line"></i> Protected account</span>
                </div>
            </aside>

            <div class="login-panel">
                <div class="login-form-wrap">
                    <div class="company-logo">
                        @if($company && !empty($company->company_logo))
                            <img src="{{ rtrim(env('ADMIN_HOST'), '/') }}/company_logo/{{ $company->company_logo }}"
                                alt="{{ $company->company_name ?? 'Netcell Pay' }}"
                                onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div class="brand-mark text-dark" hidden>
                                <span class="brand-icon"><i class="ri-flashlight-fill"></i></span>
                                <span>{{ $company->company_name ?? 'Netcell Pay' }}</span>
                            </div>
                        @else
                            <div class="brand-mark text-dark">
                                <span class="brand-icon"><i class="ri-flashlight-fill"></i></span>
                                <span>{{ $company->company_name ?? 'Netcell Pay' }}</span>
                            </div>
                        @endif
                    </div>

                    <h2 class="login-heading">Forgot password?</h2>
                    <p class="login-subtitle">Enter your registered mobile number. We will send verification codes to your mobile and email.</p>

                    <form name="form_login" id="form_login" onsubmit="event.preventDefault();">
                        <div class="mb-4" id="mobile_number_div">
                            <label for="mobile_number" class="login-label">Registered mobile number</label>
                            <div class="login-input-wrap">
                                <i class="ri-smartphone-line"></i>
                                <input type="tel" class="form-control login-input" pattern="[0-9]{10}" maxlength="10"
                                    inputmode="numeric" autocomplete="tel" id="mobile_number"
                                    placeholder="Enter your 10-digit number">
                            </div>
                        </div>

                        <div id="otp-fields" style="display:none">
                            <div class="auth-step-note">
                                <i class="ri-information-line"></i>
                                <span>Enter both 6-digit codes sent to your registered mobile number and email address.</span>
                            </div>

                            <div class="mb-3" id="mobile_otp_code_div">
                                <label for="mobile_otp_code" class="login-label">Mobile OTP</label>
                                <div class="login-input-wrap">
                                    <i class="ri-message-2-line"></i>
                                    <input type="text" class="form-control login-input" pattern="[0-9]{6}" maxlength="6"
                                        inputmode="numeric" autocomplete="one-time-code" id="mobile_otp_code"
                                        placeholder="Enter mobile OTP">
                                </div>
                            </div>

                            <div class="mb-4" id="email_otp_code_div">
                                <label for="email_otp_code" class="login-label">Email OTP</label>
                                <div class="login-input-wrap">
                                    <i class="ri-mail-check-line"></i>
                                    <input type="text" class="form-control login-input" pattern="[0-9]{6}" maxlength="6"
                                        inputmode="numeric" id="email_otp_code" placeholder="Enter email OTP">
                                </div>
                            </div>
                        </div>

                        <div id="send-otp-action">
                            <button class="btn login-button w-100" type="button" onclick="sendOtpForgotPassword()">
                                Send verification codes <i class="ri-arrow-right-line"></i>
                            </button>
                        </div>

                        <div id="verify-otp-action" style="display:none">
                            <button class="btn login-button w-100" type="button" onclick="verifyOtpForgotPassword()">
                                Verify and reset password <i class="ri-shield-check-line"></i>
                            </button>
                            <button class="auth-secondary-button mt-3" type="button" onclick="sendOtpForgotPassword()">
                                <i class="ri-refresh-line"></i> Resend codes
                            </button>
                        </div>
                    </form>

                    <a class="auth-back-link" href="{{ url('/users/login') }}">
                        <i class="ri-arrow-left-line"></i> Back to sign in
                    </a>
                </div>
            </div>
        </section>
    </main>

@endsection
@section('script')
    <script src="{{ URL::asset('assets/js/pages/password-addon.init.js') }}"></script>
    <!-- Sweet Alerts js -->
    <script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <!-- Sweet alert init js-->
    <script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
    <script >
        function capitalizeFirstLetter(string){
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function Error_Msg(title,text,icon) {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonClass: 'btn btn-primary w-xs mt-2',
                buttonsStyling: false,
                showCloseButton: true
            });
        }


       function sendOtpForgotPassword() {
            mobile_number = $("#mobile_number").val();
            if (mobile_number=="") {
                Error_Msg("Error","please enter mobile number","error");
            } else {

                $.ajax({
                    url: "{{ route('sendOtpForgotPassword') }}",
                    type: 'post',
                    data: {mobile_number,_token: '{{csrf_token()}}'},
                    success: function( data, textStatus, jQxhr ){
                       console.log( data );
                       if(data.type=="error"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                        }else if(data.type=="otp_verify"){ 
                            Error_Msg(capitalizeFirstLetter("Otp Verify"),data.message,"warning");
                            $("#otp-fields").show();
                            $("#send-otp-action").hide();
                            $("#verify-otp-action").show();
                       }else{
                            Error_Msg("Oops...","Something went wrong!","error");
                       }
                    },
                    error: function( jqXhr, textStatus, errorThrown ){
                        Error_Msg("Oops...","Something went wrong!","error");
                    }
                });
            } 
       }

       function verifyOtpForgotPassword() {
            mobile_number = $("#mobile_number").val();
            mobile_otp = $("#mobile_otp_code").val();
            email_otp = $("#email_otp_code").val();

            if (mobile_number=="") {
                Error_Msg("Error","please enter mobile number","error");
            } else if(mobile_otp==""){
                Error_Msg("Error","please enter mobile otp","error");
            } else if(email_otp==""){
                Error_Msg("Error","please enter email otp","error");
            } else {
                $.ajax({
                    url: "{{ route('verifyOtpForgotPassword') }}",
                    type: 'post',
                    data: {mobile_number,email_otp,mobile_otp,_token: '{{csrf_token()}}'},
                    success: function( data, textStatus, jQxhr ){
                       console.log( data );
                       if(data.type=="error"){
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                       }else if(data.type=="success"){
                            $("#otp-fields").hide();
                            $("#verify-otp-action").hide();
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                            setTimeout(function () {
                                window.location.replace("{{ url('/users/login') }}");
                            }, 1800);
                       }else{
                            Error_Msg("Oops...","Something went wrong!","error");
                       }
                    },
                    error: function( jqXhr, textStatus, errorThrown ){
                        Error_Msg("Oops...","Something went wrong!","error");
                    }
                });
            } 
       }
           
        
    </script>
@endsection
