@extends('layouts.master')
@section('title') My Profile @endsection

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
    .rb-profile-side {
        text-align: center;
        padding: 1.5rem 1rem;
    }
    .rb-profile-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e9ebec;
        background: #f3f6f9;
        margin: 0 auto 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 700;
        color: #405189;
        overflow: hidden;
    }
    .rb-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .rb-profile-side h5 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 0.25rem;
    }
    .rb-profile-side .rb-role {
        color: #878a99;
        font-size: 0.85rem;
        margin-bottom: 0.85rem;
    }
    .rb-profile-meta {
        text-align: left;
        border-top: 1px solid #e9ebec;
        padding-top: 0.85rem;
        margin-top: 0.5rem;
    }
    .rb-profile-meta .item {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.82rem;
        padding: 0.35rem 0;
        border-bottom: 1px dashed #eef2f7;
    }
    .rb-profile-meta .item:last-child { border-bottom: 0; }
    .rb-profile-meta .label { color: #878a99; }
    .rb-profile-meta .value { color: #212529; font-weight: 600; text-align: right; }
    .rb-profile-form .form-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #495057;
    }
    .rb-profile-form .form-control[readonly] {
        background: #f8fafc;
        border-color: #e9ebec;
    }
    .rb-profile-links a {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-right: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #405189;
        text-decoration: none;
    }
    .rb-profile-links a:hover { color: #0ab39c; }
</style>
@endsection

@section('content')
<div class="rb-profile-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="rb-page-title mb-0">My Profile</h2>
        <div class="rb-profile-links">
            <a href="{{ route('changePassword') }}"><i class="ri-lock-password-line"></i> Change Password</a>
            <a href="{{ route('loginHistory') }}"><i class="ri-history-line"></i> Login History</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card rb-profile-card">
                <div class="rb-profile-side">
                    <div class="rb-profile-avatar" id="side_avatar_wrap">
                        <img src="" id="side_profile_pic" alt="" style="display:none;" onerror="this.style.display='none'; document.getElementById('side_avatar_initials').style.display='flex';">
                        <span id="side_avatar_initials">NP</span>
                    </div>
                    <h5 id="side_fullname">—</h5>
                    <div class="rb-role" id="side_destination">—</div>
                    <div class="rb-profile-meta">
                        <div class="item">
                            <span class="label">Mobile</span>
                            <span class="value" id="side_mobile">—</span>
                        </div>
                        <div class="item">
                            <span class="label">Role</span>
                            <span class="value" id="side_role">—</span>
                        </div>
                        <div class="item">
                            <span class="label">Wallet</span>
                            <span class="value text-success" id="side_wallet">₹ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card rb-profile-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-user-line me-1"></i> Personal Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="javascript:void(0);" class="rb-profile-form">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" placeholder="Enter First Name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" placeholder="Enter Middle Name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" placeholder="Enter Last Name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="outlet_name" class="form-label">Outlet Name</label>
                                <input type="text" class="form-control" id="outlet_name" placeholder="Enter Outlet Name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="role_name" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role_name" placeholder="Enter Role Name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="text" class="form-control" id="mobile_number" placeholder="Enter Mobile Number" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="email_address" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email_address" placeholder="Enter Email Address" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" placeholder="Enter City" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" placeholder="State" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="district" class="form-label">District</label>
                                <input type="text" class="form-control" id="district" placeholder="Enter District" readonly>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function initialsFromName(first, middle, last) {
        var a = (first || '').trim().charAt(0);
        var b = (last || middle || '').trim().charAt(0);
        var s = (a + b).toUpperCase();
        return s || 'NP';
    }

    function setProfileAvatar(pic, initials) {
        var img = document.getElementById('side_profile_pic');
        var initEl = document.getElementById('side_avatar_initials');
        initEl.textContent = initials;
        if (pic) {
            img.style.display = 'block';
            initEl.style.display = 'none';
            img.src = '{{ env('APP_URL') }}/profile_pic/' + pic;
        } else {
            img.style.display = 'none';
            initEl.style.display = 'flex';
        }
    }

    function ajaxCall() {
        $.ajax({
            url: '{{ route('myProfileData') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}' },
            success: function (data) {
                if (data.type == "success") {
                    var user = data.data.user || {};
                    var fullName = [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ') || 'Admin';
                    $("#first_name").val(user.first_name || '');
                    $("#middle_name").val(user.middle_name || '');
                    $("#last_name").val(user.last_name || '');
                    $("#outlet_name").val(user.outlet_name || '');
                    $("#role_name").val(user.role_name || '');
                    $("#mobile_number").val(user.mobile_number || '');
                    $("#email_address").val(user.email_address || '');
                    $("#city").val(user.city || '');
                    $("#state").val(user.state || '');
                    $("#district").val(user.district || '');

                    $("#side_fullname").text(fullName);
                    $("#side_destination").text((user.outlet_name || '-') + ' / ' + (user.role_name || '-'));
                    $("#side_mobile").text(user.mobile_number || '—');
                    $("#side_role").text(user.role_name || '—');
                    var wallet = Number(user.wallet_balance || 0);
                    $("#side_wallet").text('₹ ' + wallet.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    setProfileAvatar(user.profile_pic, initialsFromName(user.first_name, user.middle_name, user.last_name));
                } else {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
            },
            error: function () {
                Error_Msg("Oops...", "Something went wrong!", "error");
            }
        });
    }

    ajaxCall();
</script>
@endsection
