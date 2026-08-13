<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ URL::asset('users/dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ URL::asset('users/dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>Menu</span></li>


                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ URL::asset('users/dashboard') }}">
                    <i class="bx bxs-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>
                @if(in_array((int) Session::get('role_id'), [3, 4, 5, 6], true))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarServices" data-bs-toggle="collapse" role="button" aria-expanded="{{ (int) Session::get('role_id') === 6 ? 'true' : 'false' }}" aria-controls="sidebarServices">
                        <i class='bx bxs-spreadsheet' ></i> <span>Services</span>
                    </a>
                    <div class="collapse menu-dropdown {{ (int) Session::get('role_id') === 6 ? 'show' : '' }}" id="sidebarServices">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/services/mobile') }}" class="nav-link">Mobile Recharge</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/services/postpaid') }}" class="nav-link">Postpaid</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/services/dth') }}" class="nav-link">DTH Recharge</a>
                            </li>
                            @if((int) Session::get('role_id') !== 6)
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/services/bill-payments') }}" class="nav-link">Bill Payments (BBPS)</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

            

                <!-- <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUsers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUsers">
                        <i class='bx bxs-user-detail'></i> <span>Users</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUsers">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/users/list') }}" class="nav-link">Users List</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/users/kyc-list') }}" class="nav-link">Kyc Users List</a>
                            </li>
                            
                        </ul>
                    </div>
                </li> -->
                
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarFund" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFund">
                        <i class='bx bxs-wallet'></i> <span>Fund</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarFund">
                        <ul class="nav nav-sm flex-column">
                            
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/fund/fund-request') }}" class="nav-link"> Fund Request</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/fund/fund-report') }}" class="nav-link"> Fund Report</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @if(Session::get('role_id') == 4 ||Session::get('role_id') == 5)
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUserList" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUserList">
                        <i class='bx bxs-wallet'></i> <span>Users</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUserList">
                        <ul class="nav nav-sm flex-column">
                            
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/users/fund-request') }}" class="nav-link"> Fund Request</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/users/list') }}" class="nav-link"> User List</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/users/banks') }}" class="nav-link"> Bank List</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

                @endif

                

                
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUserReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUserReports">
                        <i class='bx bxs-report' ></i></i> <span>User Reports</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUserReports">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/user-reports/account-report') }}" class="nav-link"> Account Report </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/user-reports/recharge-report') }}" class="nav-link"> Recharge </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="{{ URL::asset('users/user-reports/bill-payment-report') }}" class="nav-link"> Bill Payment </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/user-reports/money-transfer-report') }}" class="nav-link"> Money Transfer </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/user-reports/aeps-report') }}" class="nav-link"> AEPS </a>
                            </li> -->
                          
                        </ul>
                    </div>
                </li>


                @if(!in_array((int) Session::get('role_id'), [4, 5, 6], true))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ URL::asset('users/api-settings') }}">
                        <i class='bx bx-link-alt'></i> <span>API Settings</span>
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarSupport" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSupport">
                        <i class='bx bx-support' ></i> <span>Support</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarSupport">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ URL::asset('users/support/complaint') }}" class="nav-link">Complaint</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
