@extends('layouts.master-without-nav')
@section('title')
Register Now
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
        <section class="login-shell register-shell" aria-label="Create account">
            <aside class="login-showcase">
                <div class="brand-mark">
                    <span class="brand-icon"><i class="ri-user-add-fill"></i></span>
                    <span>Netcell Pay</span>
                </div>

                <div class="showcase-copy">
                    <h1>Start your digital business journey.</h1>
                    <p>Create your account to access recharge, bill payment, banking and money transfer services from one secure platform.</p>
                </div>

                <div class="trust-row">
                    <span><i class="ri-rocket-2-line"></i> Quick onboarding</span>
                    <span><i class="ri-shield-check-line"></i> Secure verification</span>
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

                    <h2 class="login-heading">Create your account</h2>
                    <p class="login-subtitle">Enter your details below. We will verify your mobile number before creating the account.</p>

                    <form name="form_login" id="form_login" onsubmit="event.preventDefault(); userRegister();">
                        <div class="auth-register-grid" id="register_div">
                            <div>
                                <label for="first_name" class="login-label">First name</label>
                                <div class="login-input-wrap">
                                    <i class="ri-user-3-line"></i>
                                    <input type="text" class="form-control login-input" id="first_name"
                                        autocomplete="given-name" placeholder="First name">
                                </div>
                            </div>

                            <div>
                                <label for="last_name" class="login-label">Last name</label>
                                <div class="login-input-wrap">
                                    <i class="ri-user-3-line"></i>
                                    <input type="text" class="form-control login-input" id="last_name"
                                        autocomplete="family-name" placeholder="Last name">
                                </div>
                            </div>

                            <div>
                                <label for="mobile_number" class="login-label">Mobile number</label>
                                <div class="login-input-wrap">
                                    <i class="ri-smartphone-line"></i>
                                    <input type="tel" class="form-control login-input" pattern="[0-9]{10}" maxlength="10"
                                        inputmode="numeric" autocomplete="tel" id="mobile_number"
                                        placeholder="10-digit number">
                                </div>
                            </div>

                            <div>
                                <label for="email_address" class="login-label">Email address</label>
                                <div class="login-input-wrap">
                                    <i class="ri-mail-line"></i>
                                    <input type="email" class="form-control login-input" id="email_address"
                                        autocomplete="email" placeholder="Email address">
                                </div>
                            </div>

                            <div class="auth-grid-full">
                                <label for="city_name" class="login-label">City</label>
                                <div class="login-input-wrap">
                                    <i class="ri-map-pin-line"></i>
                                    <input type="text" class="form-control login-input" id="city_name"
                                        autocomplete="address-level2" placeholder="Enter your city">
                                </div>
                            </div>
                        </div>

                        <div id="otp_code_div" style="display:none">
                            <input type="hidden" id="token">
                            <div class="auth-step-note">
                                <i class="ri-information-line"></i>
                                <span>Enter the 6-digit verification code sent to your registered mobile number.</span>
                            </div>
                            <label for="otp_code" class="login-label">Verification code</label>
                            <div class="login-input-wrap">
                                <i class="ri-key-2-line"></i>
                                <input type="text" class="form-control login-input" pattern="[0-9]{6}" maxlength="6"
                                    inputmode="numeric" autocomplete="one-time-code" id="otp_code"
                                    placeholder="Enter 6-digit OTP">
                            </div>
                        </div>

                        <div class="mt-4" id="rn-btn-div">
                            <button class="btn login-button w-100" type="submit">
                                Continue to verification <i class="ri-arrow-right-line"></i>
                            </button>
                        </div>

                        <div class="mt-4" id="otp-btn-div" style="display:none">
                            <button class="btn login-button w-100" type="button" onclick="checkRegisterOtp()">
                                Verify and create account <i class="ri-shield-check-line"></i>
                            </button>
                        </div>
                    </form>

                    <a class="auth-back-link" href="{{ url('/users/login') }}">
                        <i class="ri-arrow-left-line"></i> Already registered? Sign in
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


    function userRegister() {
        first_name = $("#first_name").val();
        last_name = $("#last_name").val();
        mobile_number = $("#mobile_number").val();
        email_address = $("#email_address").val();
        city_name = $("#city_name").val();

        if (first_name == "") {
            Error_Msg("Error", "please enter first name", "error");
        } else if (last_name == "") {
            Error_Msg("Error", "please enter last name", "error");
        } else if (mobile_number == "") {
            Error_Msg("Error", "please enter mobile number", "error");
        } else if (email_address == "") {
            Error_Msg("Error", "please enter email address", "error");
        } else if (city_name == "") {
            Error_Msg("Error", "please enter city name", "error");
        } else {
            $.ajax({
                url: "{{ route('sendOtpUserRegister') }}",
                type: 'post',
                data: {
                    first_name,
                    last_name,
                    mobile_number,
                    email_address,
                    city_name,
                    _token: '{{csrf_token()}}'
                },
                success: function (data, textStatus, jQxhr) {
                    console.log(data);
                    if (data.type == "error") {
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    } else if (data.type == "otp_verify") {
                        Error_Msg(capitalizeFirstLetter("Otp Verify"), data.message, "warning");
                        $("#register_div").hide();
                        $("#otp_code_div").show();
                        $("#rn-btn-div").hide();
                        $("#otp-btn-div").show();
                        $("#token").val(data.token);
                    } else if (data.type == "success") {
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                       // window.location.replace("dashboard")
                    } else {
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                },
                error: function (jqXhr, textStatus, errorThrown) {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }
    }

    function checkRegisterOtp() {
        mobile_number = $("#mobile_number").val();
        otp = $("#otp_code").val();
        token = $("#token").val();

        if (mobile_number == "") {
            Error_Msg("Error", "please enter mobile number", "error");
        } else if (otp == "") {
            Error_Msg("Error", "please enter otp", "error");
        } else if (token == "") {
            Error_Msg("Error", "please enter token", "error");
        } else {
            $.ajax({
                url: "{{ route('verifyOtpUserRegister') }}",
                type: 'post',
                data: {
                    mobile_number,token,otp,
                    _token: '{{csrf_token()}}'
                },
                success: function (data, textStatus, jQxhr) {
                    console.log(data);
                    if (data.type == "error") {
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    } else if (data.type == "success") {
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        setTimeout(function () {
                            window.location.replace("{{ url('/users/login') }}");
                        }, 1800);
                    } else {
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                },
                error: function (jqXhr, textStatus, errorThrown) {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }
    }


</script>
@endsection