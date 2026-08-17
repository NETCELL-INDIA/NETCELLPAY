@extends('layouts.master')
@section('title') Change Password @endsection

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
    <h2 class="rb-page-title">Change Password</h2>

    <div class="card rb-profile-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ri-lock-password-line me-1"></i> Change Password
            </h5>
            <a href="{{ route('myProfile') }}" class="btn btn-sm btn-light">
                <i class="ri-arrow-left-line me-1"></i> Back to Profile
            </a>
        </div>
        <div class="card-body p-4">
            <form action="javascript:void(0);" class="rb-profile-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="pt_current_password" class="form-label">Current Password*</label>
                        <input type="password" class="form-control" id="pt_current_password" placeholder="Enter current password">
                    </div>
                    <div class="col-md-4">
                        <label for="pt_new_password" class="form-label">New Password*</label>
                        <input type="password" class="form-control" id="pt_new_password" placeholder="Enter new password">
                    </div>
                    <div class="col-md-4">
                        <label for="pt_confirm_password" class="form-label">Confirm Password*</label>
                        <input type="password" class="form-control" id="pt_confirm_password" placeholder="Confirm password">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-success" id="password_submit_btn">
                            <i class="ri-lock-password-line me-1"></i> Change Password
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
    $('#password_submit_btn').click(function () {
        var current_password = $("#pt_current_password").val();
        var new_password = $("#pt_new_password").val();
        var confirm_password = $("#pt_confirm_password").val();

        if (current_password == "") {
            Error_Msg("Oops...", "Enter current password.", "error");
        } else if (new_password == "") {
            Error_Msg("Oops...", "Enter new password.", "error");
        } else if (confirm_password == "") {
            Error_Msg("Oops...", "Enter confirm password.", "error");
        } else if (new_password != confirm_password) {
            Error_Msg("Oops...", "New password & confirm password do not match.", "error");
        } else {
            $.ajax({
                url: '{{ route('myProfilePasswordChange') }}',
                method: 'post',
                data: {
                    current_password,
                    new_password,
                    confirm_password,
                    _token: '{{ csrf_token() }}'
                },
                success: function (data) {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    if (data.type == "success") {
                        $("#pt_current_password").val("");
                        $("#pt_new_password").val("");
                        $("#pt_confirm_password").val("");
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
