@extends('layouts.master')

@section('title') Create / List Users @endsection

@section('css')

<!--datatable css-->

<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<!--datatable responsive css-->

<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />

<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />



@endsection

@section('content')

<div class="users-list-page">

@component('components.breadcrumb')

@slot('li_1') Users @endslot

@slot('title')Create / List Users @endslot

@endcomponent



<div class="row">

    <div class="col-lg-12">

        <div class="card users-filter-card">

            <div class="card-header align-items-center d-flex py-2">

                <h4 class="card-title mb-0 flex-grow-1">Filters</h4>

            </div>

            <div class="card-body py-2 px-3">

                <form action="#">

                    <div class="users-filter-grid">

                        <div class="users-filter-field">

                            <input type="hidden" name="id_value" id="id_value" value="0">

                            <label class="form-label">User Search</label>

                            <input type="text" class="form-control form-control-sm" name="user_id" value="" id="user_id" placeholder="ID / mobile / email">

                            <div id="user_list"></div>

                        </div>

                        <div class="users-filter-field">

                            <label class="form-label">Parent</label>

                            <input type="hidden" name="parent_id_f" id="parent_id_f" value="0">

                            <input type="text" class="form-control form-control-sm" name="parent_id_value_f" value="" id="parent_id_value_f" placeholder="Parent user">

                            <div id="user_list_f"></div>

                        </div>

                        <div class="users-filter-field">

                            <label class="form-label">Mobile / Name</label>

                            <input type="text" class="form-control form-control-sm" name="list_keyword" value="" id="list_keyword" placeholder="Mobile, name...">

                        </div>

                        <div class="users-filter-field users-filter-field--sm">

                            <label class="form-label">Role</label>

                            <select class="form-select form-select-sm role_name" aria-label="Role" id="role_name">

                                <option selected value="0">All</option>

                                @foreach ($role as $v)

                                <option value="{{$v->id}}">{{$v->role_name}}</option>

                                @endforeach

                            </select>

                        </div>

                        <div class="users-filter-field users-filter-field--sm">

                            <label class="form-label">Status</label>

                            <select class="form-select form-select-sm" name="status_name" id="status_name">

                                <option selected value="All">All</option>

                                <option value="1">Active</option>

                                <option value="0">Deactive</option>

                            </select>

                        </div>

                        <div class="users-filter-field users-filter-field--sm">

                            <label class="form-label">KYC</label>

                            <select class="form-select form-select-sm" name="status_kyc" id="status_kyc">

                                <option selected value="All">All</option>

                                <option value="Approved">Approved</option>

                                <option value="Rejected">Rejected</option>

                                <option value="Pending">Pending</option>

                                <option value="Under Process">Under Process</option>

                            </select>

                        </div>

                        <div class="users-filter-field users-filter-field--btn">

                            <button type="button" id="search_btn" class="btn btn-primary btn-sm w-100" onclick="fetchAll(1,10)">

                                <i class="ri-search-line"></i> Search

                            </button>

                        </div>

                    </div>                          

                </form>

            </div>

        </div>

    </div>

</div>



<div class="row">

    <div class="col-lg-12">

        <div class="card users-list-card">

            <div class="card-header align-items-center d-flex py-2">

                <h4 class="card-title mb-0 flex-grow-1">List</h4>

                <button type="button" class="btn btn-info btn-sm" onclick="createNew()">Create New</button>

            </div>

            <div class="card-body py-2 px-3" id="list_result">

                <h4 class="text-center text-secondary my-3">No record found</h4>

            </div>

        </div>

    </div>

</div>



<!-- Fund Modals -->

<div id="fundModal" class="modal" tabindex="-1" aria-labelledby="fundModalLabel" aria-hidden="true" style="display: none;">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" >Fund Transfer/Reverse</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

            </div>

            <div class="modal-body">

                <form action="#" method="POST" id="fund_details_form">

                    @csrf

                    <input type="hidden" name="id" id="id">

                    <div class="live-preview">

                        <div class="row gy-4">

                            <div class="col-xxl-6 col-md-12">

                                <div>

                                    <label class="col-form-label">Type: <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 type" aria-label="Default select example" name="type" required="required">

                                        <option selected="">Select Type</option>

                                        <option value="Transfer">Transfer</option>

                                        <option value="Reverse">Reverse</option>

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-6 col-md-12">

                                <div>

                                    <label for="amount" class="col-form-label">Amount: <a style="color: red">*</a></label>

                                    <input type="number" class="form-control" name="amount" id="amount" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-12 col-md-12">

                                <div>

                                    <label for="bank_name" class="form-label">Remark: <a style="color: red">*</a></label>

                                    <textarea class="form-control" name="remark" id="remark" required="required"></textarea>

                                </div>

                            </div>

                            <div class="col-xxl-12 col-md-12">

                                <div>

                                    <label for="fund_t_pin" class="form-label">PIN: <a style="color: red">*</a></label>

                                    <input type="password" class="form-control" name="t_pin" id="fund_t_pin" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="Enter 4-digit PIN" required autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">

                                </div>

                            </div>

                            <!--end col-->

                        </div>

                        <!--end row-->

                    </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                <button type="submit" class="btn btn-primary" id="fund_details_btn">Save Changes</button>

            </div>

            </form>

        </div><!-- /.modal-content -->

    </div><!-- /.modal-dialog -->

</div><!-- /.modal -->



<!-- Reset Password Modal -->

<div id="resetPasswordModal" class="modal fade" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <form id="reset_password_form" autocomplete="off">

                @csrf

                <input type="hidden" name="id" id="reset_password_user_id">

                <div class="modal-body">

                    <p class="text-muted mb-3" id="reset_password_user_label">Set a new password for this user.</p>

                    <div class="alert alert-secondary py-2 px-3 mb-3" id="reset_current_credentials">
                        <div class="fw-semibold mb-2"><i class="ri-information-line me-1"></i> Current Credentials</div>
                        <div class="small mb-1"><span class="text-muted">Mobile:</span> <span id="reset_cred_mobile">—</span></div>
                        <div class="small mb-1"><span class="text-muted">T-PIN:</span> <strong id="reset_cred_pin">—</strong></div>
                        <div class="small mb-0">
                            <span class="text-muted">Password:</span>
                            <strong id="reset_cred_password">—</strong>
                            <span class="text-muted" id="reset_cred_password_note"></span>
                        </div>
                    </div>

                    <div class="mb-3">

                        <label for="reset_password" class="form-label">New Password <span class="text-danger">*</span></label>

                        <div class="position-relative auth-pass-inputgroup">

                            <input type="password" class="form-control pe-5 password-input" name="password" id="reset_password" minlength="8" required autocomplete="new-password" placeholder="Enter new password">

                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">

                                <i class="ri-eye-fill align-middle"></i>

                            </button>

                        </div>

                    </div>

                    <div class="mb-0">

                        <label for="reset_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>

                        <div class="position-relative auth-pass-inputgroup">

                            <input type="password" class="form-control pe-5 password-input" name="password_confirmation" id="reset_password_confirmation" minlength="8" required autocomplete="new-password" placeholder="Confirm new password">

                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">

                                <i class="ri-eye-fill align-middle"></i>

                            </button>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" class="btn btn-primary" id="reset_password_btn">Save Password</button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- Details Modals -->

<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="detailsModalLabel">Create Details</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

            </div>

            <div class="modal-body">

                <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">

                    @csrf

                    <input type="hidden" name="edit_id" id="edit_id">

                    <input type="hidden" name="old_profile_pic" id="old_profile_pic">

                    <div class="live-preview">

                        <div class="row gy-4">

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <!-- <label for="parent_id" class="form-label">Parent Id: <a style="color: red">*</a></label>

                                    <input type="number" class="form-control" name="parent_id" id="parent_id" required="required"> -->

                                    <label class="form-label">Parent:</label>
                                    <select class="form-select mb-1" name="parent_id" id="parent_id">
                                        <option value="0">Select Parent</option>
                                        @foreach (($parents ?? []) as $parentUser)
                                            @php
                                                $parentLabel = trim(($parentUser->role_name ?? '') . ' — ' . ($parentUser->outlet_name ?? '') . ' | ' . trim(($parentUser->first_name ?? '') . ' ' . ($parentUser->last_name ?? '')) . ' | ' . ($parentUser->mobile_number ?? ''));
                                            @endphp
                                            <option value="{{ $parentUser->id }}">{{ $parentLabel }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Admin, Master Distributor, Distributor only</small>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Role:</label>

                                    <select class="form-select mb-3 role_id" aria-label="Default select example" name="role_id">

                                        <option selected="">Select Role</option>

                                        @foreach ($role as $v)

                                        <option value="{{$v->id}}">{{$v->role_name}}</option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Scheme:</label>

                                    <select class="form-select mb-3 scheme_id" aria-label="Default select example" name="scheme_id">

                                        <option selected="">Select Scheme</option>

                                        @foreach ($scheme as $v)

                                        <option value="{{$v->id}}">{{$v->scheme_name}}</option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="outlet_name" class="form-label">Outlet Name: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="outlet_name" id="outlet_name" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="first_name" class="form-label">First Name: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="first_name" id="first_name" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="date_of_birth" class="form-label">Date Of Birth: <a style="color: red">*</a></label>

                                    <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="email_address" class="form-label">Email Address: <a style="color: red">*</a></label>

                                    <input type="email" class="form-control" name="email_address" id="email_address" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="mobile_number" class="form-label">Mobile Number: <a style="color: red">*</a></label>

                                    <input type="number" class="form-control" name="mobile_number" id="mobile_number" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                <label class="form-label">Login Type:</label>

                                    <select class="form-select mb-3 login_type" aria-label="Default select example" name="login_type">

                                        <option value="" selected>Select Type</option>

                                        <option value="PASSWORD">Password</option>

                                        <option value="OTP">OTP</option>

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6 user-auto-send-wrap">

                                <div>

                                    <label class="form-label">Password</label>
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="auto_send" id="auto_send_value" value="1">
                                        <input class="form-check-input" type="checkbox" id="auto_send_password" checked>
                                        <label class="form-check-label" for="auto_send_password">Auto Send</label>
                                    </div>
                                    <small class="text-muted">Password is generated automatically and sent if Auto Send is on.</small>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6" id="user_password_wrap" style="display:none">

                                <div>

                                    <label for="user_password" class="form-label">Password:</label>

                                    <div class="position-relative auth-pass-inputgroup">

                                        <input type="password" class="form-control pe-5 password-input" name="password" id="user_password" minlength="8" autocomplete="new-password" placeholder="Enter password">

                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">

                                            <i class="ri-eye-fill align-middle"></i>

                                        </button>

                                    </div>

                                    <small class="text-muted user-password-hint-edit">Leave blank to keep current password.</small>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6" id="user_password_confirm_wrap" style="display:none">

                                <div>

                                    <label for="user_password_confirmation" class="form-label">Confirm Password: <span class="text-danger user-password-required">*</span></label>

                                    <div class="position-relative auth-pass-inputgroup">

                                        <input type="password" class="form-control pe-5 password-input" name="password_confirmation" id="user_password_confirmation" minlength="8" autocomplete="new-password" placeholder="Confirm password">

                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">

                                            <i class="ri-eye-fill align-middle"></i>

                                        </button>

                                    </div>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Gender:</label>

                                    <select class="form-select mb-3 gender" aria-label="Default select example" name="gender">

                                        <option selected="">Select Gender</option>

                                        <option value="Male">Male</option>

                                        <option value="Female">Female</option>

                                        <option value="Others">Others</option>

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="flat_door_no" class="form-label">Flat Door No: </label>

                                    <input type="text" class="form-control" name="flat_door_no" id="flat_door_no">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="road_street" class="form-label">Road Street: </label>

                                    <input type="text" class="form-control" name="road_street" id="road_street">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="pincode" class="form-label">PIN Code: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="pincode" id="pincode" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit PIN" autocomplete="postal-code">

                                    <small class="text-muted" id="pincode_hint">Enter PIN to auto-fill Area, City, State, District</small>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="area_locality" class="form-label">Area Locality: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="area_locality" id="area_locality" required="required" list="area_locality_list">

                                    <datalist id="area_locality_list"></datalist>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="city" class="form-label">City: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="city" id="city" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="state" class="form-label">State: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="state" id="state" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="district" class="form-label">District: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="district" id="district" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="minium_balance" class="form-label">Minium Balance: <a style="color: red">*</a></label>

                                    <input type="number" class="form-control" name="minium_balance" id="minium_balance" value="0" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Kyc Status: <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 kyc_status" aria-label="Default select example" name="kyc_status">

                                        <option selected="">Select Status</option>

                                        <option value="Pending">Pending</option>

                                        <option value="Under Review">Under Review</option>

                                        <option value="Approved">Approved</option>

                                        <option value="Rejected">Rejected</option>

                                        

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 bg-light">
                                    <span class="fw-semibold text-dark">
                                        <i class="ri-bank-line me-1"></i> Bank Account Details
                                    </span>
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="bank_details_enabled" id="bank_details_enabled" value="1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="bank_details_switch" checked>
                                        <label class="form-check-label" for="bank_details_switch" id="bank_details_switch_label">On</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12" id="bank_details_fields">
                            <div class="row gy-4">

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="bank_account_number" class="form-label">Bank Account Number: <a style="color: red" class="bank-req-star">*</a></label>

                                    <input type="number" class="form-control bank-detail-input" name="bank_account_number" id="bank_account_number" value="0" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="branch_name" class="form-label">Bank Branch: <a style="color: red" class="bank-req-star">*</a></label>

                                    <input type="text" class="form-control bank-detail-input" name="branch_name" id="branch_name" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="ifsc_code" class="form-label">IFSC Code: <a style="color: red" class="bank-req-star">*</a></label>

                                    <input type="text" class="form-control bank-detail-input" name="ifsc_code" id="ifsc_code" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="bank_account_type" class="form-label">Account Type: <a style="color: red" class="bank-req-star">*</a></label>

                                    <select class="form-select mb-3 bank_account_type bank-detail-input" id="bank_account_type" aria-label="Default select example" name="bank_account_type" required="required">

                                        <option selected="">Select Type</option>

                                        <option value="Savings">Savings</option>

                                        <option value="Current">Current</option>

                                        <option value="NRI">NRI</option>

                                        <option value="Salary">Salary</option>

                                    </select>

                                </div>

                            </div>

                            </div>
                            </div>

                            <!--end col-->

                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="callback_url" class="form-label">Recharge Callback URL:</label>

                                    <input type="text" class="form-control" name="callback_url" id="callback_url" value="https://callbackurl.com">

                                </div>

                            </div>

                            <!--end col-->

                            <!--end col-->

                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="complaint_callback_url" class="form-label">Complaint Callback URL:</label>

                                    <input type="text" class="form-control" name="complaint_callback_url" id="complaint_callback_url" value="https://callbackurl.com">

                                </div>

                            </div>

                            <!--end col-->

                            <!--end col-->

                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <label for="ip_address" class="form-label">IP Address: </label>

                                    <input type="text" class="form-control" name="ip_address" id="ip_address" value="" placeholder="API IP whitelist (required for API access)">

                                </div>

                            </div>

                            

                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <label for="profile_pic" class="form-label">Profile Pic:</label>

                                    <input type="file" class="form-control" name="profile_pic" id="profile_pic" accept="image/png, image/gif, image/jpeg">

                                    

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <label class="form-label">Status:</label>

                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">

                                        <option selected="">Select Status</option>

                                        <option value="1">Active</option>

                                        <option value="0">Deactive</option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <!--end row-->

                    </div>

                

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                <button type="submit" class="btn btn-primary" id="edit_details_btn">Save Changes</button>

            </div>

            </form>

        </div><!-- /.modal-content -->

    </div><!-- /.modal-dialog -->

</div><!-- /.modal -->

</div><!-- /.users-list-page -->

@endsection

@section('script')

<script>

    var urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('role')){
       $("#role_name").val(urlParams.get('role')).change();
    }
    if(urlParams.has('kyc')){
       $("#status_kyc").val(urlParams.get('kyc')).change();
    }

    fetchAll(1,10);

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

    function setUserPasswordMode(isCreate) {
        $('#user_password, #user_password_confirmation').val('').attr('type', 'password').prop('readonly', false).prop('required', false);

        if (isCreate) {
            $('#auto_send_password').prop('checked', true);
            $('#auto_send_value').val('1');
            $('.user-auto-send-wrap').show();
            $('#user_password_wrap, #user_password_confirm_wrap').hide();
            $('#detailsModalLabel').text('Create Details');
        } else {
            $('#auto_send_password').prop('checked', false);
            $('#auto_send_value').val('0');
            $('.user-auto-send-wrap').hide();
            $('#user_password_wrap, #user_password_confirm_wrap').show();
            $('#detailsModalLabel').text('Edit Details');
        }
    }

    $(document).on('change', '#auto_send_password', function () {
        $('#auto_send_value').val($(this).is(':checked') ? '1' : '0');
    });

    function validateUserPasswordFields(isCreate) {
        if (isCreate) {
            return true;
        }

        var pwd = $('#user_password').val();
        var pwdConfirm = $('#user_password_confirmation').val();

        if (pwd || pwdConfirm) {
            if (!pwd || pwd.length < 8) {
                Error_Msg('Error', 'Password must be at least 8 characters.', 'error');
                return false;
            }
            if (pwd !== pwdConfirm) {
                Error_Msg('Error', 'Password confirmation does not match.', 'error');
                return false;
            }
        }

        return true;
    }

    function showDuplicateUserError(data) {
        if (!data.existing_user) {
            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
            return;
        }

        var user = data.existing_user;
        var statusText = user.deleted ? 'Deleted' : (user.status === '1' ? 'Active' : 'Inactive');
        var html = '<div class="text-start">';
        html += '<p class="mb-2">' + data.message + '</p>';
        html += '<div class="border rounded p-3 bg-light">';
        html += '<div><strong>User ID:</strong> ' + user.user_code + ' (#' + user.id + ')</div>';
        html += '<div><strong>Outlet:</strong> ' + user.outlet_name + '</div>';
        html += '<div><strong>Name:</strong> ' + user.name + '</div>';
        html += '<div><strong>Mobile:</strong> ' + user.mobile_number + '</div>';
        html += '<div><strong>Email:</strong> ' + (user.email_address || '-') + '</div>';
        html += '<div><strong>Status:</strong> ' + statusText + '</div>';
        html += '</div></div>';

        Swal.fire({
            title: 'Duplicate User Found',
            html: html,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'View User',
            cancelButtonText: 'Show In List',
            customClass: {
                confirmButton: 'btn btn-primary w-xs mt-2',
                cancelButton: 'btn btn-soft-secondary w-xs mt-2',
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then(function(result) {
            if (result.isConfirmed) {
                openUserDetails(user.id);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                $('#list_keyword').val(user.mobile_number || user.outlet_name || user.name);
                fetchAll(1, 10);
            }
        });
    }

    function openUserDetails(id) {
        $.ajax({
            url: '{{ route('userlistGet') }}',
            method: 'post',
            data: {
                id: id,
                _token: '{{ csrf_token() }}'
            },
            success: function(data) {
                if (data.type == "error") {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    return;
                }
                if (data.type != "success") {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                    return;
                }

                setParentSelect(data.data.parent_id, data.parent);
                $(".role_id").val(data.data.role_id).change();
                $(".scheme_id").val(data.data.scheme_id).change();
                $("#outlet_name").val(data.data.outlet_name);
                $("#first_name").val(data.data.first_name);
                $("#date_of_birth").val(data.data.date_of_birth);
                $("#email_address").val(data.data.email_address);
                $("#mobile_number").val(data.data.mobile_number);
                $(".login_type").val(data.data.login_type).change();
                $(".gender").val(data.data.gender).change();
                $("#flat_door_no").val(data.data.flat_door_no);
                $("#road_street").val(data.data.road_street);
                $("#area_locality").val(data.data.area_locality);
                $("#city").val(data.data.city);
                $("#state").val(data.data.state);
                $("#district").val(data.data.district);
                $("#pincode").val(data.data.pincode || '');
                $("#minium_balance").val(data.data.minium_balance);
                $(".kyc_status").val(data.data.kyc_status).change();
                $("#bank_account_number").val(data.data.bank_account_number);
                $("#branch_name").val(data.data.branch_name);
                $("#ifsc_code").val(data.data.ifsc_code);
                $(".bank_account_type").val(data.data.bank_account_type).change();

                var hasBankDetails = !!(
                    (data.data.bank_account_number && String(data.data.bank_account_number) !== '0') ||
                    (data.data.ifsc_code && String(data.data.ifsc_code).trim() !== '') ||
                    (data.data.branch_name && String(data.data.branch_name).trim() !== '') ||
                    (data.data.bank_account_type && String(data.data.bank_account_type).trim() !== '')
                );
                setBankDetailsEnabled(hasBankDetails);

                $("#ip_address").val(data.data.ip_address);
                $("#callback_url").val(data.data.callback_url);
                $("#complaint_callback_url").val(data.data.complaint_callback_url);
                $("#old_profile_pic").val(data.data.profile_pic);
                $("#edit_id").val(data.data.id);
                $("#user_password").val('');
                $("#user_password_confirmation").val('');
                setUserPasswordMode(false);
                $(".status").val(data.data.status).change();
                $('#detailsModal').modal('show');
            },
            error: function() {
                Error_Msg("Oops...", "Something went wrong!", "error");
            }
        });
    }

    function tableSearch(page) {

        limit = $('#page_limit').val();

        page = page;

        fetchAll(page,limit);

    }

    $(document).on('change','#page_limit',function(){

        page = 1;

        limit = $('#page_limit').val();

        fetchAll(page,limit);

    });





    $(document).on('keyup','#searchValueTable',function(){

        var value = $( this ).val();

        if (this.value.length < 1) {

            $("#pagination_table tr").css("display", "");

        } else {

            $("#pagination_table tbody tr:not(:contains('"+this.value+"'))").css("display", "none");

            $("#pagination_table tbody tr:contains('"+this.value+"')").css("display", "");

        }

        //console.log(search);

    });

    function fetchAll(page,limit) {

        var user_id = $("#id_value").val();

        var parent_id = $("#parent_id_f").val();

        var min_wallet = 0;

        var max_wallet = 0;

        var role_id = $("#role_name").val();

        var status = $("#status_name").val();

        var kyc_status = $("#status_kyc").val();

        var keyword = $("#list_keyword").val();



        $("#search_btn").text('Wait...');

        $('#search_btn').prop('disabled', true);

        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $.ajax({

            url: '{{ route('userlistList') }}',

            method: 'post',

            data: {

                _token: '{{csrf_token()}}',

                page,

                limit,

                user_id,

                parent_id,

                min_wallet,

                max_wallet,

                role_id,

                status,

                kyc_status,

                keyword

            },

            success: function(res) {

                $("#search_btn").text('Search');

                $('#search_btn').prop('disabled', false);

                $("#list_result").html(res);

            },

            error: function() {

                $("#search_btn").text('Search');

                $('#search_btn').prop('disabled', false);

                $("#list_result").html('<h4 class="text-center text-danger my-3">Unable to load users. Please refresh and try again.</h4>');

            }

        });

    }



    $('#user_id').on('keyup', function(){

        $('#id_value').val("0");

        search();

    });



    function search(){

        var keyword = $('#user_id').val();

        $.ajax({

            url: '{{ route('rechargeSearchUuser') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}',keyword:keyword},

            success: function(res) {

                $('#user_list').show();

                var users = (typeof adminUsersFromRes === 'function') ? adminUsersFromRes(res) : ((res && res.users) ? res.users : []);

                htmlView = "";

                for(let i = 0; i < users.length; i++){

                    htmlView += '<a onclick="selectValue(`'+users[i].id+'`,`'+users[i].outlet_name+' | '+users[i].first_name+' '+users[i].middle_name+' '+users[i].last_name+' | '+users[i].mobile_number+'`)">'+users[i].outlet_name+' | '+users[i].first_name+' '+users[i].middle_name+' '+users[i].last_name+' | '+users[i].mobile_number+'</a></br></hr>';

                }

                $('#user_list').html(htmlView);

            }

        });

    }



    function setParentSelect(parentId, parent) {
        var $sel = $('#parent_id');
        parentId = String(parentId || 0);
        if (parentId !== '0' && $sel.find('option[value="' + parentId + '"]').length === 0) {
            var label = 'Current parent';
            if (parent) {
                label = [parent.role_name, parent.outlet_name, ((parent.first_name || '') + ' ' + (parent.last_name || '')).trim(), parent.mobile_number]
                    .filter(Boolean)
                    .join(' | ');
            } else {
                label = 'Parent #' + parentId;
            }
            $sel.append($('<option>', { value: parentId, text: label }));
        }
        $sel.val(parentId);
    }

    $('#parent_id_value_f').on('keyup', function(){

        $('#parent_id_f').val("0");

        searchF();

    });



    function searchF(){

        var keyword = $('#parent_id_value_f').val();

        $.ajax({

            url: '{{ route('parentListSearchUuser') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}',keyword:keyword},

            success: function(res) {

                $('#user_list_f').show();

                var users = (typeof adminUsersFromRes === 'function') ? adminUsersFromRes(res) : ((res && res.users) ? res.users : []);

                htmlView = "";

                for(let i = 0; i < users.length; i++){

                    htmlView += '<a onclick="selectValueF(`'+users[i].id+'`,`'+users[i].outlet_name+' | '+users[i].first_name+' '+users[i].middle_name+' '+users[i].last_name+' | '+users[i].mobile_number+'`)">'+users[i].outlet_name+' | '+users[i].first_name+' '+users[i].middle_name+' '+users[i].last_name+' | '+users[i].mobile_number+'</a></br></hr>';

                }

                $('#user_list_f').html(htmlView);

            }

        });

    }



    function selectValueF(id,full_text) {

        $('#parent_id_f').val(id);

        $('#parent_id_value_f').val(full_text);

        $('#user_list_f').hide();

    }



    function selectValue(id,full_text) {

        $('#id_value').val(id);

        $('#user_id').val(full_text);

        $('#user_list').hide();

    }



    





    $(document).on('click', '.fundTransfer', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        $("#fund_details_form")[0].reset();

        $("#id").val(id);

        $('#fundModal').modal({backdrop: 'static', keyboard: false});

        $('#fundModal').modal('show');

        //alert(id);

    });





    $("#fund_details_form").submit(function(e) {

        e.preventDefault();

        var pin = String($("#fund_t_pin").val() || '').replace(/\D/g, '');
        if (pin.length !== 4) {
            Error_Msg("Error", "Please enter a valid 4-digit PIN.", "error");
            return;
        }

        const fd = new FormData(this);

        $("#fund_details_btn").text('Please wait...');

        $('#fund_details_btn').prop('disabled', true);

        $.ajax({

          url: '{{ route('fundUpdate') }}',

          method: 'post',

          data: fd,

          cache: false,

          contentType: false,

          processData: false,

          dataType: 'json',

          success: function(data) {

            if(data.type=="error"){

                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                $("#fund_details_btn").text('Save Changes');

                $('#fund_details_btn').prop('disabled', false);

            }else if(data.type=="success"){  

                Error_Msg("Updated",data.message,"success");

                fetchAll(1,10);

                $("#fund_details_btn").text('Save Changes');

                $('#fund_details_btn').prop('disabled', false);

                $("#edit_details_form")[0].reset();

                $("#fundModal").modal('hide');

            }else{

                Error_Msg("Oops...","Something went wrong!","error");

                $("#fund_details_btn").text('Save Changes');

                $('#fund_details_btn').prop('disabled', false);

            }

          },

          error: function( jqXhr, textStatus, errorThrown ){

            Error_Msg("Oops...","Something went wrong!","error");

            $("#fund_details_btn").text('Save Changes');

            $('#fund_details_btn').prop('disabled', false);

         }

        });

    }); 



    $(document).on('click', '.resetPassword', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');
        let userName = $(this).data('user-name') || 'this user';
        let userMobile = $(this).data('user-mobile') || '—';
        let userPin = $(this).data('user-pin') || '—';

        $("#reset_password_form")[0].reset();
        $("#reset_password_user_id").val(id);
        $("#reset_password_user_label").text('Set a new password for ' + userName + '.');
        $("#reset_cred_mobile").text(userMobile);
        $("#reset_cred_pin").text(userPin || '—');
        $("#reset_cred_password").text('—');
        $("#reset_cred_password_note").text('Loading...');
        $('#resetPasswordModal').modal({backdrop: 'static', keyboard: false});
        $('#resetPasswordModal').modal('show');

        $.ajax({
            url: '{{ route('userlistGet') }}',
            method: 'post',
            data: { id: id, _token: '{{ csrf_token() }}' },
            success: function(data) {
                if (data.type === 'success' && data.data) {
                    $("#reset_cred_mobile").text(data.data.mobile_number || userMobile);
                    $("#reset_cred_pin").text(data.data.t_pin || '—');
                    if (data.data.visible_password) {
                        $("#reset_cred_password").text(data.data.visible_password);
                        $("#reset_cred_password_note").text('');
                    } else {
                        $("#reset_cred_password").text('Not available');
                        $("#reset_cred_password_note").text(' — set a new password below to save it for admin reference.');
                    }
                }
            },
            error: function() {
                $("#reset_cred_password_note").text('');
            }
        });

    });



    $("#reset_password_form").submit(function(e) {

        e.preventDefault();

        var pwd = $("#reset_password").val();
        var pwdConfirm = $("#reset_password_confirmation").val();

        if (!pwd || pwd.length < 8) {
            Error_Msg("Error", "Password must be at least 8 characters.", "error");
            return;
        }

        if (pwd !== pwdConfirm) {
            Error_Msg("Error", "Password confirmation does not match.", "error");
            return;
        }

        $("#reset_password_btn").text('Please wait...');
        $('#reset_password_btn').prop('disabled', true);

        $.ajax({

            url: '{{ route('resetPassword') }}',

            method: 'post',

            data: $(this).serialize(),

            dataType: 'json',

            success: function(data) {

                if(data.type=="error"){

                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                }else if(data.type=="success"){

                    Error_Msg("Success", data.message + "\n\nNew password: " + pwd, "success");
                    $("#resetPasswordModal").modal('hide');

                }else{

                    Error_Msg("Oops...", data.message || "Something went wrong!", "error");

                }

                $("#reset_password_btn").text('Save Password');
                $('#reset_password_btn').prop('disabled', false);

            },

            error: function( jqXhr, textStatus, errorThrown ){

                var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message)
                    ? jqXhr.responseJSON.message
                    : "Something went wrong!";
                Error_Msg("Oops...", msg, "error");
                $("#reset_password_btn").text('Save Password');
                $('#reset_password_btn').prop('disabled', false);

            }

        });

    });



    $(document).on('click', '.resetPIN', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        let csrf = '{{ csrf_token() }}';

        $.ajax({

            url: '{{ route('resetPIN') }}',

            method: 'post',

            data: {

            id: id,

            _token: csrf

            },

            success: function(data) {

                if(data.type=="error"){

                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                }else if(data.type=="success"){

                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                }else{

                    Error_Msg("Oops...","Something went wrong!","error");

                }           

            },

            error: function( jqXhr, textStatus, errorThrown ){

                Error_Msg("Oops...","Something went wrong!","error");

            }

        });

    });



    $(document).on('click', '.deleteData', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        let csrf = '{{ csrf_token() }}';

        Swal.fire({

          title: 'Are you sure?',

          text: "You won't be able to revert this!",

          icon: 'warning',

          showCancelButton: true,

          confirmButtonColor: '#3085d6',

          cancelButtonColor: '#d33',

          confirmButtonText: 'Yes, delete it!'

        }).then((result) => {

          if (result.isConfirmed) {

            $.ajax({

              url: '{{ route('userlistDelete') }}',

              method: 'post',

              data: {

                id: id,

                _token: csrf

              },

              success: function(data) {

                if(data.type=="error"){

                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                }else if(data.type=="success"){

                   Swal.fire(

                        'Deleted!',

                        data.message,

                        'success'

                    )

                    fetchAll(1,10);

                }else{

                    Error_Msg("Oops...","Something went wrong!","error");

                }

                

              },

              error: function( jqXhr, textStatus, errorThrown ){

                Error_Msg("Oops...","Something went wrong!","error");

            }

            });

          }

        })

    });



    $(document).on('click', '.editDetails', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        openUserDetails(id);

    });

    $("#edit_details_form").submit(function(e) {

        e.preventDefault();

        var isCreate = !$("#edit_id").val() || $("#edit_id").val() === "0";
        if (!validateUserPasswordFields(isCreate)) {
            return;
        }

        const fd = new FormData(this);

        $("#edit_details_btn").text('Please wait...');

        $('#edit_details_btn').prop('disabled', true);

        $.ajax({

          url: '{{ route('userlistUpdate') }}',

          method: 'post',

          data: fd,

          cache: false,

          contentType: false,

          processData: false,

          dataType: 'json',

          success: function(data) {

            

            if(data.type=="error"){

                if (data.existing_user) {
                    showDuplicateUserError(data);
                } else {
                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                }

                $("#edit_details_btn").text('Save Changes');

                $('#edit_details_btn').prop('disabled', false);

            }else if(data.type=="success"){  

                Error_Msg("Updated",data.message,"success");

                fetchAll(1,10);

                $("#edit_details_btn").text('Save Changes');

                $('#edit_details_btn').prop('disabled', false);

                $("#edit_details_form")[0].reset();

                $("#detailsModal").modal('hide');

            }else{

                Error_Msg("Oops...","Something went wrong!","error");

                $("#edit_details_btn").text('Save Changes');

                $('#edit_details_btn').prop('disabled', false);

            }

          },

          error: function( jqXhr, textStatus, errorThrown ){

            var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message) ? jqXhr.responseJSON.message : "Something went wrong!";
            Error_Msg("Oops...", msg, "error");

            $("#edit_details_btn").text('Save Changes');

            $('#edit_details_btn').prop('disabled', false);

         }

        });

    }); 







    

    

    function createNew() {

        $('#detailsModal').modal('show');

        $("#edit_details_form")[0].reset();

        $("#edit_id").val(0);

        $("#parent_id").val("0");

        setUserPasswordMode(true);

        $("#area_locality_list").empty();

        $("#pincode_hint").text('Enter PIN to auto-fill Area, City, State, District').removeClass('text-danger text-success');

        setBankDetailsEnabled(true);

        $('#detailsModal').modal({backdrop: 'static', keyboard: false});

        $('#detailsModal').modal('show');

    }

    // PIN → auto-fill Area Locality, City, State, District
    var _pinLookupTimer = null;
    var _pinLookupLast = '';

    function fillFromPincode(pin) {
        pin = String(pin || '').replace(/\D/g, '');
        if (pin.length !== 6) return;
        if (pin === _pinLookupLast) return;
        _pinLookupLast = pin;

        $("#pincode_hint").text('Looking up PIN...').removeClass('text-danger text-success');

        $.ajax({
            url: '{{ route('userlistLookupPincode') }}',
            method: 'post',
            data: {
                pincode: pin,
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res.type === 'success' && res.data) {
                    $("#area_locality").val(res.data.area_locality || '');
                    $("#city").val(res.data.city || '');
                    $("#state").val(res.data.state || '');
                    $("#district").val(res.data.district || '');

                    var list = $("#area_locality_list");
                    list.empty();
                    (res.data.localities || []).forEach(function (name) {
                        list.append($('<option>').attr('value', name));
                    });

                    $("#pincode_hint").text('Address filled from PIN ' + pin).addClass('text-success').removeClass('text-danger');
                } else {
                    _pinLookupLast = '';
                    $("#pincode_hint").text(res.message || 'PIN not found').addClass('text-danger').removeClass('text-success');
                }
            },
            error: function () {
                _pinLookupLast = '';
                $("#pincode_hint").text('PIN lookup failed. Try again.').addClass('text-danger').removeClass('text-success');
            }
        });
    }

    $(document).on('input', '#pincode', function () {
        var pin = $(this).val().replace(/\D/g, '').slice(0, 6);
        $(this).val(pin);
        clearTimeout(_pinLookupTimer);
        if (pin.length === 6) {
            _pinLookupTimer = setTimeout(function () { fillFromPincode(pin); }, 250);
        } else {
            _pinLookupLast = '';
            $("#pincode_hint").text('Enter PIN to auto-fill Area, City, State, District').removeClass('text-danger text-success');
        }
    });

    $(document).on('blur', '#pincode', function () {
        fillFromPincode($(this).val());
    });

    // Bank Account Details ON/OFF
    function setBankDetailsEnabled(enabled) {
        enabled = !!enabled;
        $('#bank_details_enabled').val(enabled ? '1' : '0');
        $('#bank_details_switch').prop('checked', enabled);
        $('#bank_details_switch_label').text(enabled ? 'On' : 'Off');

        if (enabled) {
            $('#bank_details_fields').show();
            $('.bank-req-star').show();
            $('.bank-detail-input').prop('required', true).prop('disabled', false);
            if (!$('#bank_account_number').val()) {
                $('#bank_account_number').val('0');
            }
        } else {
            $('#bank_details_fields').hide();
            $('.bank-req-star').hide();
            $('.bank-detail-input').prop('required', false).prop('disabled', true);
            $('#bank_account_number').val('');
            $('#branch_name').val('');
            $('#ifsc_code').val('');
            $('.bank_account_type').val('').change();
        }
    }

    $(document).on('change', '#bank_details_switch', function () {
        setBankDetailsEnabled($(this).is(':checked'));
    });

</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>



<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>



{{-- <script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}

<script src="{{ URL::asset('assets/libs/prismjs/prism.js') }}"></script>



<script src="{{ URL::asset('assets/js/pages/password-addon.init.js') }}"></script>




@endsection

