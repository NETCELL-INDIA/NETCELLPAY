@extends('layouts.master')
@section('title') PIN Reset @endsection

@section('css')
<style>
    .rb-profile-page .rb-page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #405189;
        margin-bottom: 1rem;
    }
    .rb-profile-card {
        border: 1px solid #e9ebec;
        border-radius: 0.4rem;
        box-shadow: none;
        overflow: hidden;
    }
    .rb-profile-card .card-header {
        background: #405189 !important;
        color: #fff !important;
        border: 0 !important;
        padding: 0.75rem 1rem;
    }
    .rb-profile-card .card-header .card-title {
        color: #fff !important;
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
    }
    .rb-profile-form .form-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #495057;
    }
</style>
@endsection

@section('content')
<div class="rb-profile-page">
    <h2 class="rb-page-title">PIN Reset</h2>

    <div class="card rb-profile-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ri-key-2-line me-1"></i> PIN Reset
            </h5>
            <a href="{{ route('myProfile') }}" class="btn btn-sm btn-light">
                <i class="ri-arrow-left-line me-1"></i> Back to Profile
            </a>
        </div>
        <div class="card-body p-4">
            <form action="javascript:void(0);" class="rb-profile-form" id="password_reset_form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="pr_current_password" class="form-label">Current Password*</label>
                        <input type="password" class="form-control" id="pr_current_password" placeholder="Enter current password" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="pr_new_pin" class="form-label">New PIN*</label>
                        <input type="password" class="form-control" id="pr_new_pin" maxlength="4" inputmode="numeric" placeholder="Enter 4-digit PIN" autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
                    </div>
                    <div class="col-md-4">
                        <label for="pr_confirm_pin" class="form-label">Confirm PIN*</label>
                        <input type="password" class="form-control" id="pr_confirm_pin" maxlength="4" inputmode="numeric" placeholder="Confirm 4-digit PIN" autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-success" id="pin_reset_submit_btn">
                            <i class="ri-key-2-line me-1"></i> Reset PIN
                        </button>
                    </div>
                    <div class="col-12 text-center mt-2">
                        <a href="javascript:void(0);" id="forgot_pin_toggle" class="text-primary fw-semibold">
                            <i class="ri-shield-keyhole-line me-1"></i> Forgot PIN? Reset via OTP
                        </a>
                    </div>
                </div>
            </form>

            <form action="javascript:void(0);" class="rb-profile-form d-none" id="otp_reset_form">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-info py-2 mb-0" role="alert">
                            <i class="ri-information-line me-1"></i> OTP will be sent to your registered mobile number &amp; email.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">OTP*</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="pr_otp" maxlength="6" inputmode="numeric" placeholder="Enter 6-digit OTP" autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                            <button type="button" class="btn btn-primary" id="send_otp_btn">Send OTP</button>
                        </div>
                        <small class="text-muted d-none" id="local_otp_hint"></small>
                    </div>
                    <div class="col-md-4">
                        <label for="pr_otp_new_pin" class="form-label">New PIN*</label>
                        <input type="password" class="form-control" id="pr_otp_new_pin" maxlength="4" inputmode="numeric" placeholder="Enter 4-digit PIN" autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
                    </div>
                    <div class="col-md-4">
                        <label for="pr_otp_confirm_pin" class="form-label">Confirm PIN*</label>
                        <input type="password" class="form-control" id="pr_otp_confirm_pin" maxlength="4" inputmode="numeric" placeholder="Confirm 4-digit PIN" autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <a href="javascript:void(0);" id="back_to_password_reset" class="text-muted">
                            <i class="ri-arrow-left-line me-1"></i> Back to password reset
                        </a>
                        <button type="button" class="btn btn-success" id="otp_reset_submit_btn">
                            <i class="ri-key-2-line me-1"></i> Reset PIN via OTP
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('#pin_reset_submit_btn').click(function () {
        var current_password = $("#pr_current_password").val();
        var new_pin = $("#pr_new_pin").val();
        var confirm_pin = $("#pr_confirm_pin").val();

        if (current_password == "") {
            Error_Msg("Oops...", "Enter current password.", "error");
        } else if (new_pin == "" || new_pin.length != 4) {
            Error_Msg("Oops...", "Enter 4-digit new PIN.", "error");
        } else if (confirm_pin == "") {
            Error_Msg("Oops...", "Enter confirm PIN.", "error");
        } else if (new_pin != confirm_pin) {
            Error_Msg("Oops...", "New PIN & confirm PIN do not match.", "error");
        } else {
            $.ajax({
                url: '{{ route('pinResetChange') }}',
                method: 'post',
                data: {
                    current_password,
                    new_pin,
                    confirm_pin,
                    _token: '{{ csrf_token() }}'
                },
                success: function (data) {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    if (data.type == "success") {
                        $("#pr_current_password").val("");
                        $("#pr_new_pin").val("");
                        $("#pr_confirm_pin").val("");
                    }
                },
                error: function () {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }
    });

    $('#forgot_pin_toggle').click(function () {
        $("#password_reset_form").addClass("d-none");
        $("#otp_reset_form").removeClass("d-none");
    });

    $('#back_to_password_reset').click(function () {
        $("#otp_reset_form").addClass("d-none");
        $("#password_reset_form").removeClass("d-none");
    });

    $('#send_otp_btn').click(function () {
        var btn = $(this);
        btn.prop("disabled", true).text("Sending...");
        $.ajax({
            url: '{{ route('pinResetOtpSend') }}',
            method: 'post',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data) {
                Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                if (data.type == "success") {
                    if (data.local_otp) {
                        $("#local_otp_hint").removeClass("d-none").text("Local dev OTP: " + data.local_otp);
                    }
                    btn.text("Resend OTP");
                }
                btn.prop("disabled", false);
            },
            error: function () {
                Error_Msg("Oops...", "Something went wrong!", "error");
                btn.prop("disabled", false).text("Send OTP");
            }
        });
    });

    $('#otp_reset_submit_btn').click(function () {
        var otp = $("#pr_otp").val();
        var new_pin = $("#pr_otp_new_pin").val();
        var confirm_pin = $("#pr_otp_confirm_pin").val();

        if (otp == "" || otp.length != 6) {
            Error_Msg("Oops...", "Enter 6-digit OTP.", "error");
        } else if (new_pin == "" || new_pin.length != 4) {
            Error_Msg("Oops...", "Enter 4-digit new PIN.", "error");
        } else if (confirm_pin == "") {
            Error_Msg("Oops...", "Enter confirm PIN.", "error");
        } else if (new_pin != confirm_pin) {
            Error_Msg("Oops...", "New PIN & confirm PIN do not match.", "error");
        } else {
            $.ajax({
                url: '{{ route('pinResetOtpVerify') }}',
                method: 'post',
                data: {
                    otp,
                    new_pin,
                    confirm_pin,
                    _token: '{{ csrf_token() }}'
                },
                success: function (data) {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    if (data.type == "success") {
                        $("#pr_otp").val("");
                        $("#pr_otp_new_pin").val("");
                        $("#pr_otp_confirm_pin").val("");
                        $("#local_otp_hint").addClass("d-none").text("");
                        $("#otp_reset_form").addClass("d-none");
                        $("#password_reset_form").removeClass("d-none");
                    }
                },
                error: function () {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }
    });
</script>
@endsection
