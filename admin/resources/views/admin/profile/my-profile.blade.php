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
    .rb-avatar-upload {
        position: relative;
        width: 96px;
        margin: 0 auto 0.85rem;
    }
    .rb-avatar-upload .rb-profile-avatar {
        width: 96px;
        height: 96px;
        margin: 0;
    }
    .rb-avatar-upload .rb-photo-btn {
        position: absolute;
        right: -2px;
        bottom: -2px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid #fff;
        background: #405189;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(64, 81, 137, 0.35);
        padding: 0;
    }
    .rb-avatar-upload .rb-photo-btn:hover { background: #364574; }
    .rb-avatar-upload .rb-photo-btn:disabled {
        opacity: 0.7;
        cursor: wait;
    }
    .rb-photo-hint {
        font-size: 0.75rem;
        color: #878a99;
        margin-top: 0.35rem;
    }
</style>
@endsection

@section('content')
<div class="rb-profile-page">
    <h2 class="rb-page-title">My Profile</h2>
    @include('admin.profile._nav')

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card rb-profile-card">
                <div class="rb-profile-side">
                    <div class="rb-avatar-upload">
                        <div class="rb-profile-avatar" id="side_avatar_wrap">
                            <img src="" id="side_profile_pic" alt="" style="display:none;" onerror="this.style.display='none'; document.getElementById('side_avatar_initials').style.display='flex';">
                            <span id="side_avatar_initials">NP</span>
                        </div>
                        <button type="button" class="rb-photo-btn" id="profile_photo_btn" title="Change photo">
                            <i class="ri-camera-line"></i>
                        </button>
                        <input type="file" id="profile_pic_input" accept="image/jpeg,image/jpg,image/png,image/webp" hidden>
                    </div>
                    <h5 id="side_fullname">—</h5>
                    <div class="rb-role" id="side_destination">—</div>
                    <div class="rb-photo-hint">JPG / PNG, max 2 MB</div>
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

    function setProfileAvatar(pic, initials, fullUrl) {
        var img = document.getElementById('side_profile_pic');
        var initEl = document.getElementById('side_avatar_initials');
        initEl.textContent = initials || initEl.textContent || 'NP';
        var url = fullUrl || (pic ? '{{ admin_asset('profile_pic') }}/' + pic : '');
        if (url) {
            img.style.display = 'block';
            initEl.style.display = 'none';
            img.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now();
        } else {
            img.style.display = 'none';
            initEl.style.display = 'flex';
        }
    }

    function Error_Msg(title, text, icon) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
                buttonsStyling: false,
                showCloseButton: true
            });
        } else {
            alert(text);
        }
    }

    function capitalizeFirstLetter(string) {
        string = String(string || '');
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    $('#profile_photo_btn').on('click', function () {
        $('#profile_pic_input').trigger('click');
    });

    $('#profile_pic_input').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            Error_Msg('Error', 'Image must be under 2 MB.', 'error');
            this.value = '';
            return;
        }

        var fd = new FormData();
        fd.append('profile_pic', file);
        fd.append('_token', '{{ csrf_token() }}');

        var $btn = $('#profile_photo_btn');
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i>');

        $.ajax({
            url: '{{ route('myProfilePhotoUpdate') }}',
            method: 'post',
            data: fd,
            processData: false,
            contentType: false,
            success: function (data) {
                $btn.prop('disabled', false).html('<i class="ri-camera-line"></i>');
                if (data.type === 'success') {
                    setProfileAvatar(
                        data.data.profile_pic,
                        $('#side_avatar_initials').text(),
                        data.data.profile_pic_url
                    );
                    // Refresh topbar avatars if present
                    if (data.data.profile_pic_url) {
                        $('.rb-avatar').attr('src', data.data.profile_pic_url + '?t=' + Date.now());
                    }
                    Error_Msg('Success', data.message, 'success');
                } else {
                    Error_Msg(capitalizeFirstLetter(data.type || 'error'), data.message || 'Upload failed', data.type || 'error');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="ri-camera-line"></i>');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload failed';
                Error_Msg('Error', msg, 'error');
            }
        });

        this.value = '';
    });

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
