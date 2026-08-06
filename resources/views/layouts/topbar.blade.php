<style>
    .badge {
        --vz-badge-font-size: 0.75rem;
    }
.card {
    border-color: #ee5a7b !important;
    margin-bottom: 1.5rem;
    box-shadow: 0px 0px 3px 3px #009688;
}
.card-header {
    border-bottom: 1px solid var(--vz-border-color);
    background: linear-gradient(to bottom, #E91E63 32%, #FF5722 100%);

}
.form-control{
    border-radius: 0;
}
.form-select{
    border-radius: 0;
}
.btn.active, .btn.show, .btn:active, .btn:first-child:active, .btn:focus, .btn:hover, :not(.btn-check)+.btn:active {
    border-color: transparent;
    background-color: #4CAF50;
    color: white;
}
</style>
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ URL::asset('users/dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_icon}}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" style="height: 40px;">
                        </span>
                    </a>

                    <a href="{{ URL::asset('users/dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_icon}}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" style="height: 40px;">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                    id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- App Search-->
                <form class="app-search d-none d-md-block">
                    <div class="position-relative">
                        <button type="button" class="btn btn-se btn-success rounded-pill LoadWallet"><i
                                class="mdi mdi-wallet label-icon align-middle rounded-pill fs-16 me-2"></i> ₹
                                {{DB::table('users')->where('id',Session::get('user_id'))->first()->wallet_balance}}</button>
                    </div>

                </form>
            </div>

            <div class="d-flex align-items-center">


                <div class="dropdown ms-1 topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ URL::asset('/assets/images/flags/us.svg') }}" class="me-2 rounded" height="20"
                            alt="Header Language" height="16">
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">

                        <!-- item-->
                        <a href="#" class="dropdown-item notify-item language py-2" data-lang="en"
                            title="English">
                            <img src="{{ URL::asset('assets/images/flags/us.svg') }}" alt="user-image"
                                class="me-2 rounded" height="20">
                            <span class="align-middle">English</span>
                        </a>
                    </div>
                </div>

                <!-- <div class="dropdown topbar-head-dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-category-alt fs-22'></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg p-0 dropdown-menu-end">
                        <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fw-semibold fs-15"> Web Apps </h6>
                                </div>
                                <div class="col-auto">

                                </div>
                            </div>
                        </div>

                        <div class="p-2">
                            <div class="row g-0">
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/github.png') }}" alt="Github">
                                        <span>GitHub</span>
                                    </a>
                                </div>
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/bitbucket.png') }}"
                                            alt="bitbucket">
                                        <span>Bitbucket</span>
                                    </a>
                                </div>
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/dribbble.png') }}" alt="dribbble">
                                        <span>Dribbble</span>
                                    </a>
                                </div>
                            </div>

                            <div class="row g-0">
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/dropbox.png') }}" alt="dropbox">
                                        <span>Dropbox</span>
                                    </a>
                                </div>
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/mail_chimp.png') }}"
                                            alt="mail_chimp">
                                        <span>Mail Chimp</span>
                                    </a>
                                </div>
                                <div class="col">
                                    <a class="dropdown-icon-item" href="#!">
                                        <img src="{{ URL::asset('assets/images/brands/slack.png') }}" alt="slack">
                                        <span>Slack</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                        class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user"
                                src="" id="nav_profile_pic" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"></span>
                                <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text"></span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header" id="nav_first_name">Welcome Shiba!</h6>
                        <a class="dropdown-item" href="{{ route('myProfile') }}"><i
                                class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Profile</span></a>
                                <a class="dropdown-item" id="addMoney"><i
                                class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Instant Load Money</span></a>
                        <a class="dropdown-item" href="{{ route('myProfileCommission') }}"><i
                                class="mdi mdi-percent text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">My Commission</span></a>
                       
                        <a class="dropdown-item" id="helpSupport"><i
                                class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Help/Support</span></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#"><i
                                class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Balance : <b id="nav_wallet_balance"></b></span></a>
                        <a class="dropdown-item" href="{{ route('myProfile') }}"><span
                                class="badge bg-soft-success text-success mt-1 float-end">New</span><i
                                class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Settings</span></a>
                        <a class="dropdown-item " href="{{ route('usersLogout') }}"><i
                                class="bx bx-power-off font-size-16 align-middle me-1"></i> <span
                                key="t-logout">Logout</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Help & Support Modals -->
<div id="helpSupportModal" class="modal" tabindex="-1" aria-labelledby="helpSupportModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpSupportModalLabel">Help & Support Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <!-- Vertical alignment (align-items-center) -->
                <div class="row align-items-center" style="padding: 20px;">
                    <div class="col-sm-4">
                        
                    </div>
                    <div class="col-sm-8">
                        <img src="" alt="Logo" id="sh_comapany_logo" style="height: 90px;">
                    </div>
                    
                </div>
                <div class="row align-items-center" style="padding: 20px;">
                    <div class="col-sm-6">
                        <h6>Support No. :</h6>
                    </div>
                    <div class="col-sm-6">
                        <h6 id="sh_support_number" ></h6>
                    </div>
                </div>
                <div class="row align-items-center" style="padding: 20px;">
                    <div class="col-sm-6">
                        <h6>Alternet Support No. : </h6>
                    </div>
                    <div class="col-sm-6">
                        <h6 id="sh_support_number_2" ></h6>
                    </div>
                </div>
                <div class="row align-items-center" style="padding: 20px;">
                    <div class="col-sm-6">
                        <h6>Support Email :</h6>
                    </div>
                    <div class="col-sm-6">
                        <h6 id="sh_support_email" ></h6>
                    </div>
                </div>
                <div class="row align-items-center" style="padding: 20px;">
                    <div class="col-sm-6">
                        <h6>Company Address :</h6>
                    </div>
                    <div class="col-sm-6">
                        <h6 id="sh_company_address" ></h6>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<!-- Help & Support Modals -->
<div id="addMoneyModal" class="modal" tabindex="-1" aria-labelledby="addMoneyModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMoneyModalLabel">Add Money Online</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
            <form action="#" method="POST" id="addMoney_details_form">
                    @csrf
                    <div class="live-preview">
                        <div class="row gy-4">
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Enter Amount: <a style="color: red">*</a></label>
                                    <input type="number" name="amount" class="form-control" required="">
                                </div>
                            </div>
                        </div>
                        <!--end row-->
                    </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="addMoney_details_btn">Submit</button>
            </div>
            </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
