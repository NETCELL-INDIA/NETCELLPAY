@extends('layouts.master-without-nav')
@section('title')
    Forgot password 
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
                                <img src="{{ URL::asset('assets/images/auth-one-bg.jpg') }}" alt="Background"  style="height: 100%;width: 100%;">
                                </div>
                                <!-- end col -->

                                <div class="col-lg-6">
                                    <div class="p-lg-5 p-4">
                                        <div style="text-align: center;">
                                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="Logo" alt="Logo"  style="height: 60px;">
                                        </div>

                                        <div class="mt-4">
                                            <form name="form_login" class="form" id="form_login">

                                                <div class="mb-3" id="mobile_number_div">
                                                    
                                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                                    <input type="number" class="form-control" pattern="[0-9]*" id="mobile_number"
                                                        placeholder="Enter mobile number"></br>
                                                    <div class="float-end">
                                                        <a href="javascript:void(0)" class="text-muted" onclick="sendOtpForgotPassword()">Generate OTP</a>
                                                    </div>
                                                </div>
                                                <div class="mb-3" id="mobile_otp_code_div" style="display:none">
                                                    <label for="mobile_otp_code" class="form-label">Mobile OTP</label>
                                                    <input type="number" class="form-control" pattern="[0-9]*" id="mobile_otp_code"
                                                        placeholder="Enter Mobile OTP">
                                                </div>
                                                <div class="mb-3" id="email_otp_code_div" style="display:none">
                                                    <label for="email_otp_code" class="form-label">Email OTP</label>
                                                    <input type="number" class="form-control" pattern="[0-9]*" id="email_otp_code"
                                                        placeholder="Enter Email OTP">
                                                </div>

                                                <div class="mt-4" id="lg-btn-div">
                                                    <button class="btn btn-success w-100" type="button" onclick="verifyOtpForgotPassword()">Verify OTP</button>
                                                </div>

                                            </form>
                                        </div>
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
                        <div class="text-center">
                        </div>
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
                            $("#mobile_otp_code_div").show();
                            $("#email_otp_code_div").show();
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
                            $("#mobile_otp_code_div").hide();
                            $("#email_otp_code_div").hide();
                            Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                            //window.location.replace("login")
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
