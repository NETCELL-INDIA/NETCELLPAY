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
            <form action="javascript:void(0);" class="rb-profile-form">
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
</script>
@endsection
