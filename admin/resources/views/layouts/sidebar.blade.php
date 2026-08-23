<style>
    :root {
        --np-sidebar: #3b2a6e;
        --np-sidebar-hover: #4a3785;
        --np-accent: #22c55e;
    }
    [data-layout=vertical] .app-menu.navbar-menu {
        background: var(--np-sidebar) !important;
        border-right: 0 !important;
        box-shadow: none !important;
    }
    [data-layout=vertical] .navbar-brand-box {
        background: var(--np-sidebar) !important;
        padding: 0.45rem 0.85rem !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        height: auto !important;
        min-height: 56px !important;
        display: flex !important;
        align-items: center !important;
    }
    .np-brand {
        display: flex !important;
        align-items: center;
        gap: 0.6rem;
        text-decoration: none !important;
        color: #fff !important;
        width: 100%;
        padding: 0;
    }
    .np-brand-logo {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #fff;
        padding: 3px;
        object-fit: contain;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
    }
    .np-brand-mark {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #fff;
        color: var(--np-accent);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
    }
    .np-brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
        min-width: 0;
    }
    .np-brand-text strong {
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .np-brand-text span {
        font-size: 0.7rem;
        font-weight: 500;
        letter-spacing: 0.04em;
        color: rgba(255, 255, 255, 0.72);
        margin-top: 2px;
    }
    /* Hide theme clutter: MENU label box, hover pill, overlays */
    [data-layout=vertical] .navbar-nav .menu-title,
    [data-layout=vertical] .navbar-nav .menu-title span,
    [data-layout=vertical] .navbar-nav .menu-title::before,
    [data-layout=vertical] .navbar-nav .menu-title::after {
        display: none !important;
        border: 0 !important;
        outline: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        content: none !important;
        width: 0 !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #vertical-hover {
        display: none !important;
    }
    [data-layout=vertical] .navbar-menu .sidebar-background,
    [data-layout=vertical] .navbar-menu::before,
    [data-layout=vertical] .navbar-menu::after {
        display: none !important;
        background: none !important;
    }
    /* Menu flush under brand — no top gap */
    [data-layout=vertical] .app-menu.navbar-menu {
        padding-bottom: 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }
    [data-layout=vertical] #scrollbar {
        padding-top: 0 !important;
        margin-top: 0 !important;
        flex: 1 1 auto !important;
        height: auto !important;
        min-height: 0 !important;
    }
    [data-layout=vertical] #scrollbar .container-fluid,
    [data-layout=vertical] #scrollbar .simplebar-content-wrapper,
    [data-layout=vertical] #scrollbar .simplebar-content {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    [data-layout=vertical] .navbar-nav .nav-link.menu-link {
        color: rgba(255, 255, 255, 0.92) !important;
        font-weight: 500;
        border-radius: 8px;
        margin: 1px 8px;
        padding: 0.5rem 0.75rem !important;
        border: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;
    }
    [data-layout=vertical] .navbar-nav > li.nav-item:first-of-type > .nav-link.menu-link {
        margin-top: 0 !important;
    }
    [data-layout=vertical] .navbar-nav .nav-link.menu-link:hover,
    [data-layout=vertical] .navbar-nav .nav-link.menu-link.active,
    [data-layout=vertical] .navbar-nav .nav-link.menu-link[aria-expanded=true] {
        background: var(--np-sidebar-hover) !important;
        color: #fff !important;
    }
    [data-layout=vertical] .navbar-nav .nav-link.menu-link i {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    [data-layout=vertical] .navbar-nav .menu-dropdown .nav-link {
        color: rgba(255, 255, 255, 0.78) !important;
        padding-left: 2.4rem !important;
        border: 0 !important;
    }
    [data-layout=vertical] .navbar-nav .menu-dropdown .nav-link:hover,
    [data-layout=vertical] .navbar-nav .menu-dropdown .nav-link.active {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.08);
    }
    [data-layout=vertical] .navbar-menu .navbar-nav .nav-sm {
        padding-left: 0 !important;
    }
</style>

@php
    $brandName = $company?->company_name ?? 'NETCELL PAY';
    $brandIcon = admin_company_logo($company?->company_icon ?? null);
@endphp
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ URL::asset('admin/dashboard') }}" class="np-brand logo logo-dark logo-light" title="{{ $brandName }}">
            @if($brandIcon)
                <img src="{{ $brandIcon }}" alt="{{ $brandName }}" class="np-brand-logo">
            @else
                <span class="np-brand-mark">NP</span>
            @endif
            <span class="np-brand-text">
                <strong>{{ $brandName }}</strong>
                <span>Admin Panel</span>
            </span>
        </a>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ URL::asset('admin/dashboard') }}">
                        <i class="bx bxs-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>

                {{-- Recharge Reports --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarRechargeReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRechargeReports">
                        <i class="bx bxs-file-doc"></i> <span>Recharge Reports</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarRechargeReports">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/user-reports/recharge-report') }}" class="nav-link">Recharge Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/manual-report') }}" class="nav-link">Manual Recharge Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/pending-report') }}" class="nav-link">Pending Report</a></li>
                            <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="window.open('{{ URL::asset('admin/admin-reports/recharge-live-reports') }}','liveRechargeReport','width='+(screen.width-80)+',height='+(screen.height-120)+',left=40,top=40,resizable=yes,scrollbars=yes'); return false;">Live Recharge Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/margin-report') }}" class="nav-link">Margin Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/cashback-report') }}" class="nav-link">Cashback Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/api-report') }}" class="nav-link">API Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/refund-report') }}" class="nav-link">Refund Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/recharge-logs') }}" class="nav-link">Recharge Logs</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/supplier-fail-2-success') }}" class="nav-link">Supplier Fail 2 Success</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/rehit-recharge-history') }}" class="nav-link">Rehit Recharge History</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/amountwise-report') }}" class="nav-link">Amountwise Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/consumption-report') }}" class="nav-link">Consumption Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/r-offer-report') }}" class="nav-link">R-Offer Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/plan-logs-report') }}" class="nav-link">Plan Logs Report</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Routings (all existing Netcell routing tools) --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarRoutings" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRoutings">
                        <i class="bx bx-repost"></i> <span>Routings</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarRoutings">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/routings/general') }}" class="nav-link">General Routings</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/routings/operator') }}" class="nav-link">Operator API Switch</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/company/routes-settings') }}" class="nav-link">Routes Priority</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Users --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUsers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUsers">
                        <i class="bx bxs-user-detail"></i> <span>Users</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUsers">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Create / List Users</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/login-history') }}" class="nav-link">Login History</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Manage KYC</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Payments --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPayments" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPayments">
                        <i class="bx bxs-wallet"></i> <span>Payments</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPayments">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Fund Transfer / Credit Debit</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/fund/fund-request') }}" class="nav-link">Pending Fund Requests</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/fund/fund-report') }}" class="nav-link">Fund Transfer History</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Accounts Reports --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAccountsReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccountsReports">
                        <i class="bx bx-line-chart"></i> <span>Accounts Reports</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAccountsReports">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/user-reports/account-report') }}" class="nav-link">Account Ledger</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/fund/fund-report') }}" class="nav-link">Payment / Fund Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/user-sale-report') }}" class="nav-link">User Sale Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/md-dt-sale-report') }}" class="nav-link">MD / DT Sale Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/provider-sale-report') }}" class="nav-link">Operator Sale Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/api-sale-report') }}" class="nav-link">API Sale Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/api-log-report') }}" class="nav-link">API Log Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Balance Report (Users)</a></li>
                        </ul>
                    </div>
                </li>

                {{-- APIs --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarApis" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApis">
                        <i class="bx bx-link"></i> <span>APIs</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarApis">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/apis') }}" class="nav-link">Add / List APIs</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/apis/plan-circle-dth-api') }}" class="nav-link">Plan / Circle / DTH Info Fetch API</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/plan-logs-report') }}" class="nav-link">Plan / Roffer / HLR Logs</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/api-log-report') }}" class="nav-link">API Log Report</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Complains --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarComplains" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarComplains">
                        <i class="bx bx-message-rounded-dots"></i> <span>Complains</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarComplains">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/support/complaint') }}" class="nav-link">All Complains</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/user-reports/recharge-report?complaint=1') }}" class="nav-link">Complaint from Recharge</a></li>
                        </ul>
                    </div>
                </li>

                {{-- System --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarSystem" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSystem">
                        <i class="bx bx-cog"></i> <span>System</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarSystem">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/system-settings/system') }}" class="nav-link">System Setting</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/scheme') }}" class="nav-link">Scheme</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/banks') }}" class="nav-link">Banks</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/amount-block') }}" class="nav-link">Amount Block</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/amount-wize-switch') }}" class="nav-link">Amount Wise Switch</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/state-wize-switch') }}" class="nav-link">State Wise Switch</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/user-wize-switch') }}" class="nav-link">User Wise Switch</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/role') }}" class="nav-link">Role</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/services') }}" class="nav-link">Services</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/providers') }}" class="nav-link">Providers</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/announcement') }}" class="nav-link">Announcement</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/slider') }}" class="nav-link">Slider</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Website --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarWebsite" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarWebsite">
                        <i class="bx bx-globe"></i> <span>Website</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarWebsite">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/ads') }}" class="nav-link">Create Ads</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/pages') }}" class="nav-link">User Website List</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/setting') }}" class="nav-link">Website Setting</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/company/manage-company') }}" class="nav-link">Company Profile</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/banners') }}" class="nav-link">Banner Setting</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/popups') }}" class="nav-link">Popup Master</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/website/policy') }}" class="nav-link">Web Policy</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Employees / Roles --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarEmployees" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarEmployees">
                        <i class="bx bxs-user-badge"></i> <span>Employees</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarEmployees">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Create / List Employees</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/system/role') }}" class="nav-link">Permissions / Roles</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/user-sale-report') }}" class="nav-link">Performance Report</a></li>
                        </ul>
                    </div>
                </li>

                {{-- Extras --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarExtras" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExtras">
                        <i class="bx bx-gift"></i> <span>Extras</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarExtras">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item"><a href="{{ route('smsApiListPage') }}" class="nav-link">SMS API List</a></li>
                            <li class="nav-item"><a href="{{ route('whatsappApiPage') }}" class="nav-link">WhatsApp API</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/users/send-message') }}" class="nav-link">Send SMS / Notification</a></li>
                            <li class="nav-item"><a href="{{ route('sendSmsReport') }}" class="nav-link">Send SMS Report</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/company/sms-template') }}" class="nav-link">SMS Templates</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/company/email-template') }}" class="nav-link">Email Templates</a></li>
                            <li class="nav-item"><a href="{{ URL::asset('admin/profile/my-profile') }}" class="nav-link">My Profile</a></li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
<div class="vertical-overlay"></div>
