@extends('layouts.master-without-nav')
@section('title')
Register Now
@endsection
@section('content')
@php
    $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
@endphp
<!-- auth-page wrapper -->
<div class="auth-page-wrapper auth-bg-cover py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-overlay"></div>
    <!-- auth-page content -->
    <div class="auth-page-content overflow-hidden pt-lg-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6">
                                <img src="/assets/images/auth-one-bg.jpg" alt="Bener" style="height: 100%;width: 100%;">
                                <!-- <div class="p-lg-5 p-4 auth-one-bg h-100">
                                        <div class="position-relative h-100 d-flex flex-column">
                                        </div>
                                    </div> -->
                            </div>
                            <!-- end col -->

                            <div class="col-lg-6">
                                <div class="p-lg-5 p-4">
                                    <div>
                                        <h5 class="text-primary" style="text-align: center;">
                                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}"
                                                alt="Logo" style="height: 60px;">
                                        </h5>
                                        <!-- <p class="text-muted">Sign in to continue to .</p>  -->
                                    </div>

                                    <div class="mt-4">
                                        <form name="form_login" class="form" id="form_login">
                                            <div id="register_div">
                                                <div class="mb-3">
                                                    <label for="first_name" class="form-label">First Name</label>
                                                    <input type="text" class="form-control" pattern="[A-Z]*"
                                                        id="first_name" placeholder="Enter First Name">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="last_name" class="form-label">Last Name</label>
                                                    <input type="text" class="form-control" pattern="[A-Z]*"
                                                        id="last_name" placeholder="Enter Last Name">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                                    <input type="number" class="form-control" pattern="[0-9]*"
                                                        id="mobile_number" placeholder="Enter mobile number">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="email_address" class="form-label">Email Address</label>
                                                    <input type="eamil" class="form-control" id="email_address"
                                                        placeholder="Email Address">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="city_name" class="form-label">City Name</label>
                                                    <input type="text" class="form-control" pattern="[A-Z]*"
                                                        id="city_name" placeholder="Enter City Name">
                                                </div>
                                            </div>
                                            <div class="mb-3" id="otp_code_div" style="display:none">
                                            <input type="hidden" id="token">
                                                <label for="otp_code" class="form-label">OTP</label>
                                                <input type="number" class="form-control" pattern="[0-9]*" id="otp_code"
                                                    placeholder="Enter OTP">
                                            </div>
                                            <div class="mt-4" id="rn-btn-div">
                                                <button class="btn btn-success w-100" type="button"
                                                    onclick="userRegister()">REGISTER NOW</button>
                                            </div>
                                            <div class="mt-4" id="otp-btn-div" style="display:none">
                                                <button class="btn btn-success w-100" type="button"
                                                    onclick="checkRegisterOtp()">VERIFY OTP</button>
                                            </div>

                                        </form>

                                    </div>

                                    <!-- <div class="mt-5 text-center">
                                            <p class="mb-0">Don't have an account ? <a
                                                    href="auth-signup-cover"
                                                    class="fw-semibold text-primary text-decoration-underline"> Signup</a>
                                            </p>
                                        </div> -->
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->

            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end auth page content -->

    <!-- footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <!-- <div class="text-center">
                            <script>
                                document.write(new Date().getFullYear())
                            </script> {{$name}}. Crafted with <i
                                    class="mdi mdi-heart text-danger"></i> by Themesbrand</p>
                        </div> -->
                </div>
            </div>
        </div>
    </footer>
    <!-- end Footer -->
</div>
<!-- end auth-page-wrapper -->

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
                        //window.location.replace("dashboard")
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