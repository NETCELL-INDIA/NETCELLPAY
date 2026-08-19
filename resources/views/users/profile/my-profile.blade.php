@extends('layouts.master')
@section('title')
    My Profile
@endsection
@section('content')
    <div class="position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg profile-setting-img">
            <img src="{{ URL::asset('assets/images/profile-bg.jpg') }}" class="profile-wid-img" alt="">
            <div class="overlay-content">
                <div class="text-end p-3">
                    <!-- <div class="p-0 ms-auto rounded-circle profile-photo-edit">
                        <input id="profile-foreground-img-file-input"  class="profile-foreground-img-file-input">
                        <label for="profile-foreground-img-file-input" class="profile-photo-edit btn btn-light">
                            <i class="ri-image-edit-line align-bottom me-1"></i> Change Cover
                        </label>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-3">
            <div class="card mt-n5">
                <div class="card-body p-4">
                    <div class="text-center">
                        <div class="profile-user position-relative d-inline-block mx-auto  mb-4">
                            <img src="" id="side_profile_pic"
                                class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                            <!-- <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                <input id="profile-img-file-input"  class="profile-img-file-input">
                                <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                    <span class="avatar-title rounded-circle bg-light text-body">
                                        <i class="ri-camera-fill"></i>
                                    </span>
                                </label>
                            </div> -->
                        </div>
                        <h5 class="fs-16 mb-1" id="side_fullname">CPS Company</h5>
                        <p class="text-muted mb-0" id="side_destination">CPS Company / Developer</p>
                    </div>
                </div>
            </div>
            <!--end card-->
            <!-- <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-5">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Complete Your Profile</h5>
                        </div>
                    </div>
                    <div class="progress animated-progress custom-progress progress-label">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 30%" aria-valuenow="30"
                            aria-valuemin="0" aria-valuemax="100">
                            <div class="label">30%</div>
                        </div>
                    </div>
                </div>
            </div> -->
            <!-- <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Portfolio</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="javascript:void(0);" class="badge bg-light text-primary fs-12"><i
                                    class="ri-add-fill align-bottom me-1"></i> Add</a>
                        </div>
                    </div>
                    <div class="mb-3 d-flex">
                        <div class="avatar-xs d-block flex-shrink-0 me-3">
                            <span class="avatar-title rounded-circle fs-16 bg-dark text-light">
                                <i class="ri-github-fill"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" id="gitUsername" placeholder="Username"
                            value="@daveadame">
                    </div>
                    <div class="mb-3 d-flex">
                        <div class="avatar-xs d-block flex-shrink-0 me-3">
                            <span class="avatar-title rounded-circle fs-16 bg-primary">
                                <i class="ri-global-fill"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="websiteInput" placeholder="www.example.com"
                            value="www.velzon.com">
                    </div>
                    <div class="mb-3 d-flex">
                        <div class="avatar-xs d-block flex-shrink-0 me-3">
                            <span class="avatar-title rounded-circle fs-16 bg-success">
                                <i class="ri-dribbble-fill"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="dribbleName" placeholder="Username"
                            value="@dave_adame">
                    </div>
                    <div class="d-flex">
                        <div class="avatar-xs d-block flex-shrink-0 me-3">
                            <span class="avatar-title rounded-circle fs-16 bg-danger">
                                <i class="ri-pinterest-fill"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="pinterestName" placeholder="Username"
                            value="Advance Dave">
                    </div>
                </div>
            </div> -->
            <!--end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-9">
            <div class="card mt-xxl-n5">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link text-body active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                <i class="fas fa-home"></i>
                                Personal Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body" data-bs-toggle="tab" href="#changePassword" role="tab">
                                <i class="far fa-user"></i>
                                Change Password
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body" data-bs-toggle="tab" href="#changePIN" role="tab">
                                <i class="far fa-user"></i>
                                Change PIN
                            </a>
                        </li>
                       <li class="nav-item">
                            <a class="nav-link text-body" data-bs-toggle="tab" href="#loginHistory" role="tab">
                                <i class="far fa-envelope"></i>
                                Login History
                            </a>
                        </li>
                         <!-- <li class="nav-item">
                            <a class="nav-link text-body" data-bs-toggle="tab" href="#privacy" role="tab">
                                <i class="far fa-envelope"></i>
                                Privacy Policy
                            </a>
                        </li> -->
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <div class="tab-pane active" id="personalDetails" role="tabpanel">
                            <form action="javascript:void(0);">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name"
                                                placeholder="Enter First Name" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="middle_name" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name"
                                                placeholder="Enter Middle Name" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name"
                                                placeholder="Enter Last Name" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-2">
                                        <div class="mb-3">
                                            <label for="outlet_name" class="form-label">Outlet Name</label>
                                            <input type="text" class="form-control" id="outlet_name"
                                                placeholder="Enter Outlet Name" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-2">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <input type="text" class="form-control" id="role_name"
                                                placeholder="Enter Role Name" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="mobile_number" class="form-label">Mobile Number</label>
                                            <input type="number" class="form-control" id="mobile_number"
                                                placeholder="Enter Mobile  Number" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="email_address" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email_address"
                                                placeholder="Enter Email Address" value="" readonly>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" placeholder="Enter City"
                                                value="" readonly/>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" class="form-control" id="state"
                                                placeholder="State" value="" readonly/>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="district" class="form-label">District</label>
                                            <input type="text" class="form-control"
                                                id="district" placeholder="Enter District" value="" readonly>
                                        </div>
                                    </div>

                                    @if(Session::get('role_id') == 3)
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="ip_address" class="form-label">IP Address (Recharge API)</label>
                                            <input type="text" class="form-control"
                                                id="ip_address" value="" readonly>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="callback_url" class="form-label">Callback URL (Recharge API)</label>
                                            <input type="text" class="form-control"
                                                id="callback_url" value="" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <div class="float-end">
                                                <a href="javascript:void(0)" onclick="generateKey()" class="badge rounded-pill text-bg-success">Generate Key</a>
                                            </div>
                                            <label for="api_key" class="form-label">API Key (Recharge API)</label>
                                            
                                            <input type="text" class="form-control"
                                                id="api_key" value="" readonly>
                                        </div>
                                    </div>
                                    @endif
                                    <!--end col-->
                                    <!-- <div class="col-lg-12">
                                        <div class="hstack gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-primary">Updates</button>
                                            <button type="button" class="btn btn-soft-success">Cancel</button>
                                        </div>
                                    </div> -->
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                        <!--end tab-pane-->
                        <div class="tab-pane" id="changePassword" role="tabpanel">
                            <form action="javascript:void(0);">
                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="pt_old_password" class="form-label">Current Password*</label>
                                            <input type="password" class="form-control" id="pt_current_password"
                                                placeholder="Enter current password">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="pt_new_password" class="form-label">New Password*</label>
                                            <input type="password" class="form-control" id="pt_new_password"
                                                placeholder="Enter new password">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="pt_confirm_password" class="form-label">Confirm
                                                Password*</label>
                                            <input type="password" class="form-control" id="pt_confirm_password"
                                                placeholder="Confirm password">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-12">
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success" id="password_submit_btn">Change
                                                Password</button>
                                        </div>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                        <!--end tab-pane-->
                        <div class="tab-pane" id="changePIN" role="tabpanel">
                            <form action="javascript:void(0);">
                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="tab_current_pin" class="form-label">Current
                                            PIN*</label>
                                            <input type="password" class="form-control" id="tab_current_pin"
                                                placeholder="Enter current PIN">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="tab_new_pin" class="form-label">New
                                            PIN*</label>
                                            <input type="password" class="form-control" id="tab_new_pin"
                                                placeholder="Enter new PIN">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="tab_confirm_pin" class="form-label">Confirm
                                            PIN*</label>
                                            <input type="password" class="form-control" id="tab_confirm_pin"
                                                placeholder="Confirm PIN">
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-12">
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success" id="pin_submit_btn">Change
                                                PIN</button>
                                        </div>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                        <!--end tab-pane-->
                        <div class="tab-pane" id="loginHistory" role="tabpanel">
                            <!-- Variants -->
                            <table class="table table-nowrap" >
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Ip Address</th>
                                        <th scope="col">Login Path</th>
                                        <th scope="col">Date & Time</th>
                                        <th scope="col" class="text-center">Location</th>
                                    </tr>
                                </thead>
                                <tbody id="loginHistoryResult">
                                    
                                </tbody>
                            </table>
                        </div>
                        <!--end tab-pane-->
                        <div class="tab-pane" id="experience" role="tabpanel">
                            <form>
                                <div id="newlink">
                                    <div id="1">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="mb-3">
                                                    <label for="jobTitle" class="form-label">Job
                                                        Title</label>
                                                    <input type="text" class="form-control" id="jobTitle"
                                                        placeholder="Job title" value="Lead Designer / Developer">
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="companyName" class="form-label">Company
                                                        Name</label>
                                                    <input type="text" class="form-control" id="companyName"
                                                        placeholder="Company name" value="Themesbrand">
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label for="experienceYear" class="form-label">Experience
                                                        Years</label>
                                                    <div class="row">
                                                        <div class="col-lg-5">
                                                            <select class="form-control" data-choices
                                                                data-choices-search-false name="experienceYear"
                                                                id="experienceYear">
                                                                <option value="">Select years</option>
                                                                <option value="Choice 1">2001</option>
                                                                <option value="Choice 2">2002</option>
                                                                <option value="Choice 3">2003</option>
                                                                <option value="Choice 4">2004</option>
                                                                <option value="Choice 5">2005</option>
                                                                <option value="Choice 6">2006</option>
                                                                <option value="Choice 7">2007</option>
                                                                <option value="Choice 8">2008</option>
                                                                <option value="Choice 9">2009</option>
                                                                <option value="Choice 10">2010</option>
                                                                <option value="Choice 11">2011</option>
                                                                <option value="Choice 12">2012</option>
                                                                <option value="Choice 13">2013</option>
                                                                <option value="Choice 14">2014</option>
                                                                <option value="Choice 15">2015</option>
                                                                <option value="Choice 16">2016</option>
                                                                <option value="Choice 17" selected>2017
                                                                </option>
                                                                <option value="Choice 18">2018</option>
                                                                <option value="Choice 19">2019</option>
                                                                <option value="Choice 20">2020</option>
                                                                <option value="Choice 21">2021</option>
                                                                <option value="Choice 22">2022</option>
                                                            </select>
                                                        </div>
                                                        <!--end col-->
                                                        <div class="col-auto align-self-center">
                                                            to
                                                        </div>
                                                        <!--end col-->
                                                        <div class="col-lg-5">
                                                            <select class="form-control" data-choices
                                                                data-choices-search-false name="choices-single-default2">
                                                                <option value="">Select years</option>
                                                                <option value="Choice 1">2001</option>
                                                                <option value="Choice 2">2002</option>
                                                                <option value="Choice 3">2003</option>
                                                                <option value="Choice 4">2004</option>
                                                                <option value="Choice 5">2005</option>
                                                                <option value="Choice 6">2006</option>
                                                                <option value="Choice 7">2007</option>
                                                                <option value="Choice 8">2008</option>
                                                                <option value="Choice 9">2009</option>
                                                                <option value="Choice 10">2010</option>
                                                                <option value="Choice 11">2011</option>
                                                                <option value="Choice 12">2012</option>
                                                                <option value="Choice 13">2013</option>
                                                                <option value="Choice 14">2014</option>
                                                                <option value="Choice 15">2015</option>
                                                                <option value="Choice 16">2016</option>
                                                                <option value="Choice 17">2017</option>
                                                                <option value="Choice 18">2018</option>
                                                                <option value="Choice 19">2019</option>
                                                                <option value="Choice 20" selected>2020
                                                                </option>
                                                                <option value="Choice 21">2021</option>
                                                                <option value="Choice 22">2022</option>
                                                            </select>
                                                        </div>
                                                        <!--end col-->
                                                    </div>
                                                    <!--end row-->
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-12">
                                                <div class="mb-3">
                                                    <label for="jobDescription" class="form-label">Job
                                                        Description</label>
                                                    <textarea class="form-control" id="jobDescription" rows="3"
                                                        placeholder="Enter description">You always want to make sure that your fonts work well together and try to limit the number of fonts you use to three or less. Experiment and play around with the fonts that you already have in the software you're working with reputable font websites. </textarea>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="hstack gap-2 justify-content-end">
                                                <a class="btn btn-success" href="javascript:deleteEl(1)">Delete</a>
                                            </div>
                                        </div>
                                        <!--end row-->
                                    </div>
                                </div>
                                <div id="newForm" style="display: none;">

                                </div>
                                <div class="col-lg-12">
                                    <div class="hstack gap-2">
                                        <button type="submit" class="btn btn-success">Update</button>
                                        <a href="javascript:new_link()" class="btn btn-primary">Add
                                            New</a>
                                    </div>
                                </div>
                                <!--end col-->
                            </form>
                        </div>
                        <!--end tab-pane-->
                        <div class="tab-pane" id="privacy" role="tabpanel">
                            <div class="mb-4 pb-2">
                                <h5 class="card-title text-decoration-underline mb-3">Security:</h5>
                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0">
                                    <div class="flex-grow-1">
                                        <h6 class="fs-13 mb-1">Two-factor Authentication</h6>
                                        <p class="text-muted">Two-factor authentication is an enhanced
                                            security meansur. Once enabled, you'll be required to give
                                            two types of identification when you log into Google
                                            Authentication and SMS are Supported.</p>
                                    </div>
                                    <div class="flex-shrink-0 ms-sm-3">
                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Enable Two-facor
                                            Authentication</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0 mt-2">
                                    <div class="flex-grow-1">
                                        <h6 class="fs-13 mb-1">Secondary Verification</h6>
                                        <p class="text-muted">The first factor is a password and the
                                            second commonly includes a text with a code sent to your
                                            smartphone, or biometrics using your fingerprint, face, or
                                            retina.</p>
                                    </div>
                                    <div class="flex-shrink-0 ms-sm-3">
                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Set
                                            up secondary method</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0 mt-2">
                                    <div class="flex-grow-1">
                                        <h6 class="fs-13 mb-1">Backup Codes</h6>
                                        <p class="text-muted mb-sm-0">A backup code is automatically
                                            generated for you when you turn on two-factor authentication
                                            through your iOS or Android Twitter app. You can also
                                            generate a backup code on twitter.com.</p>
                                    </div>
                                    <div class="flex-shrink-0 ms-sm-3">
                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Generate backup
                                            codes</a>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h5 class="card-title text-decoration-underline mb-3">Application
                                    Notifications:</h5>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex">
                                        <div class="flex-grow-1">
                                            <label for="directMessage" class="form-check-label fs-14">Direct
                                                messages</label>
                                            <p class="text-muted">Messages from people you follow</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="directMessage" checked />
                                            </div>
                                        </div>
                                    </li>
                                    <li class="d-flex mt-2">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label fs-14" for="desktopNotification">
                                                Show desktop notifications
                                            </label>
                                            <p class="text-muted">Choose the option you want as your
                                                default setting. Block a site: Next to "Not allowed to
                                                send notifications," click Add.</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="desktopNotification" checked />
                                            </div>
                                        </div>
                                    </li>
                                    <li class="d-flex mt-2">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label fs-14" for="emailNotification">
                                                Show email notifications
                                            </label>
                                            <p class="text-muted"> Under Settings, choose Notifications.
                                                Under Select an account, choose the account to enable
                                                notifications for. </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="emailNotification" />
                                            </div>
                                        </div>
                                    </li>
                                    <li class="d-flex mt-2">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label fs-14" for="chatNotification">
                                                Show chat notifications
                                            </label>
                                            <p class="text-muted">To prevent duplicate mobile
                                                notifications from the Gmail and Chat apps, in settings,
                                                turn off Chat notifications.</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="chatNotification" />
                                            </div>
                                        </div>
                                    </li>
                                    <li class="d-flex mt-2">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label fs-14" for="purchaesNotification">
                                                Show purchase notifications
                                            </label>
                                            <p class="text-muted">Get real-time purchase alerts to
                                                protect yourself from fraudulent charges.</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="purchaesNotification" />
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h5 class="card-title text-decoration-underline mb-3">Delete This
                                    Account:</h5>
                                <p class="text-muted">Go to the Data & Privacy section of your profile
                                    Account. Scroll to "Your data & privacy options." Delete your
                                    Profile Account. Follow the instructions to delete your account :
                                </p>
                                <div>
                                    <input type="password" class="form-control" id="passwordInput"
                                        placeholder="Enter your password" value="make@321654987" style="max-width: 265px;">
                                </div>
                                <div class="hstack gap-2 mt-3">
                                    <a href="javascript:void(0);" class="btn btn-soft-danger">Close &
                                        Delete This Account</a>
                                    <a href="javascript:void(0);" class="btn btn-light">Cancel</a>
                                </div>
                            </div>
                        </div>
                        <!--end tab-pane-->
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script>
        $('#password_submit_btn').click(function() {
            var current_password = $("#pt_current_password").val();
            var new_password = $("#pt_new_password").val();
            var confirm_password = $("#pt_confirm_password").val();
            
            if(current_password == ""){
                Error_Msg("Oops...", "Enter current password.", "error");
            }else if(new_password == ""){
                Error_Msg("Oops...", "Enter new password.", "error");
            }else if(confirm_password == ""){
                Error_Msg("Oops...", "Enter confirm password.", "error");
            }else if(new_password != confirm_password){
                Error_Msg("Oops...", "New password & confirm password do not match.", "error");        
            }else{
                $.ajax({
                    url: '{{ route('myProfilePasswordChange') }}',
                    method: 'post',
                    data: {
                        current_password,
                        new_password,
                        confirm_password,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if(data.type == "success"){
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                            $("#pt_current_password").val("");
                            $("#pt_new_password").val("");
                            $("#pt_confirm_password").val("");
                        }else{
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        }
                    },
                    error: function(err) {
                        console.log(err);
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                });
            }
        });
        $('#pin_submit_btn').click(function() {
            var current_pin = $("#tab_current_pin").val();
            var new_pin = $("#tab_new_pin").val();
            var confirm_pin = $("#tab_confirm_pin").val();
            
            if(current_pin == ""){
                Error_Msg("Oops...", "Enter current pin.", "error");
            }else if(new_pin == ""){
                Error_Msg("Oops...", "Enter new pin.", "error");
            }else if(confirm_pin == ""){
                Error_Msg("Oops...", "Enter confirm pin.", "error");
            }else if(new_pin != confirm_pin){
                Error_Msg("Oops...", "New pin & confirm pin do not match.", "error");        
            }else{
                $.ajax({
                    url: '{{ route('myProfilePinChange') }}',
                    method: 'post',
                    data: {
                        current_pin,
                        new_pin,
                        confirm_pin,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if(data.type == "success"){
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                            $("#tab_current_pin").val("");
                            $("#tab_new_pin").val("");
                            $("#tab_confirm_pin").val("");
                        }else{
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        }
                    },
                    error: function(err) {
                        console.log(err);
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                });
            }
        });

        function generateKey() {
            $.ajax({
                url: '{{ route('myProfileGenerateKey') }}',
                method: 'post',
                data: {_token: '{{ csrf_token() }}'},
                success: function(data) {
                    if(data.type == "success"){
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        location.reload();
                    }else{
                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        //ajaxCall();
                    }
                },
                error: function(err) {
                    console.log(err);
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            });
        }
        ajaxCall();
        getProfileDataLocalStorage();
        function getProfileDataLocalStorage() {
            data = $.parseJSON(localStorage.getItem("profileData"));
            $("#first_name").val(data.user.first_name);
            $("#middle_name").val(data.user.middle_name);
            $("#last_name").val(data.user.last_name);
            $("#outlet_name").val(data.user.outlet_name);
            $("#role_name").val(data.user.role_name);
            $("#mobile_number").val(data.user.mobile_number);
            $("#email_address").val(data.user.email_address);
            $("#city").val(data.user.city);
            $("#state").val(data.user.state);
            $("#district").val(data.user.district);
            $("#ip_address").val(data.user.ip_address);
            $("#callback_url").val(data.user.callback_url);
            $("#api_key").val(data.user.api_key);
            admin_url = '{{env('ADMIN_HOST')}}';
            $("#side_profile_pic").attr("src", admin_url+"/public/profile_pic/"+data.user.profile_pic);
            $("#side_fullname").text(data.user.first_name+" "+data.user.middle_name+" "+data.user.last_name);
            $("#side_destination").text(data.user.outlet_name+" / "+data.user.role_name);
            html = '';
            $.each(data.login_history, function(k,v) {
                var pin = (v.maps_url)
                    ? '<a href="'+v.maps_url+'" target="_blank" rel="noopener noreferrer" title="Open map" style="color:#e03131;font-size:1.25rem;"><i class="ri-map-pin-2-fill"></i></a>'
                    : '<span class="text-muted">-</span>';
                html+='<tr><td>'+parseInt(k+1)+'</td><td>'+v.ip_address+'</td><td>'+v.login_path+'</td><td>'+parseDateTime(v.created_at)+'</td><td class="text-center">'+pin+'</td></tr>';
            });
            console.log(html);
            $('#loginHistoryResult').append(html);
        }
       // console.log(localStorage.getItem("profileData"));
        
        // $("#city").val(data.user.city);
        // $("#city").val(data.user.city);
    </script>   
    <script src="{{ URL::asset('assets/js/pages/profile-setting.init.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
