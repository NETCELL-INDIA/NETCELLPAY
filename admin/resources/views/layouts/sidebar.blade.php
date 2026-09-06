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
        width: auto;
        height: 42px;
        max-width: 220px;
        border-radius: 0;
        background: transparent;
        padding: 0;
        object-fit: contain;
        image-rendering: auto;
        flex-shrink: 0;
        box-shadow: none;
    }
    [data-sidebar-size=sm] .np-brand-logo,
    [data-sidebar-size=sm-hover] .np-brand-logo {
        width: 36px;
        height: 36px;
        max-width: 36px;
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
    $brandLogo = admin_company_logo($company?->company_logo ?? $company?->company_icon ?? null);
@endphp
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ URL::asset('admin/dashboard') }}" class="np-brand logo logo-dark logo-light" title="{{ $brandName }}">
            @if($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="np-brand-logo">
            @else
                <span class="np-brand-mark">NP</span>
                <span class="np-brand-text">
                    <strong>{{ $brandName }}</strong>
                    <span>Admin Panel</span>
                </span>
            @endif
        </a>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                @if(admin_can('dashboard'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ URL::asset('admin/dashboard') }}">
                        <i class="bx bxs-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>
                @endif

                @if(admin_can('recharge_reports'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarRechargeReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRechargeReports">
                        <i class="bx bxs-file-doc"></i> <span>Recharge Reports</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarRechargeReports">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('recharge_reports.recharge_report'))<li class="nav-item"><a href="{{ URL::asset('admin/user-reports/recharge-report') }}" class="nav-link">Recharge Report</a></li>@endif
                            @if(admin_can('recharge_reports.manual_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/manual-report') }}" class="nav-link">Manual Recharge Report</a></li>@endif
                            @if(admin_can('recharge_reports.pending_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/pending-report') }}" class="nav-link">Pending Report</a></li>@endif
                            @if(admin_can('recharge_reports.live_report'))<li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="return openLiveRechargeReport('{{ URL::asset('admin/admin-reports/recharge-live-reports') }}')">Live Recharge Report</a></li>@endif
                            @if(admin_can('recharge_reports.margin_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/margin-report') }}" class="nav-link">Margin Report</a></li>@endif
                            @if(admin_can('recharge_reports.cashback_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/cashback-report') }}" class="nav-link">Cashback Report</a></li>@endif
                            @if(admin_can('recharge_reports.api_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/api-report') }}" class="nav-link">API Report</a></li>@endif
                            @if(admin_can('recharge_reports.refund_report'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/refund-report') }}" class="nav-link">Refund Report</a></li>@endif
                            @if(admin_can('recharge_reports.recharge_logs'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/recharge-logs') }}" class="nav-link">Recharge Logs</a></li>@endif
                            @if(admin_can('recharge_reports.fail2success'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/supplier-fail-2-success') }}" class="nav-link">Recharge Fail To Success</a></li>@endif
                            @if(admin_can('recharge_reports.rehit'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/resend-report') }}" class="nav-link">Resend Report</a></li>@endif
                            @if(admin_can('recharge_reports.amountwise'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/amountwise-report') }}" class="nav-link">Amountwise Report</a></li>@endif
                            @if(admin_can('recharge_reports.consumption'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/consumption-report') }}" class="nav-link">Consumption Report</a></li>@endif
                            @if(admin_can('recharge_reports.roffer'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/r-offer-report') }}" class="nav-link">R-Offer Report</a></li>@endif
                            @if(admin_can('recharge_reports.plan_logs'))<li class="nav-item"><a href="{{ URL::asset('admin/recharge-reports/plan-logs-report') }}" class="nav-link">Plan Logs Report</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('routings'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarRoutings" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRoutings">
                        <i class="bx bx-repost"></i> <span>Routings</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarRoutings">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('routings.general'))<li class="nav-item"><a href="{{ URL::asset('admin/routings/general') }}" class="nav-link">General Routings</a></li>@endif
                            @if(admin_can('routings.operator'))<li class="nav-item"><a href="{{ URL::asset('admin/routings/operator') }}" class="nav-link">Manage Operator</a></li>@endif
                            @if(admin_can('routings.api_switching'))<li class="nav-item"><a href="{{ URL::asset('admin/routings/api-switching') }}" class="nav-link">API Switching</a></li>@endif
                            @if(admin_can('routings.priority'))<li class="nav-item"><a href="{{ URL::asset('admin/company/routes-settings') }}" class="nav-link">Routes Priority</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('commission'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarCommission" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCommission">
                        <i class="bx bx-percentage"></i> <span>Commission</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarCommission">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('commission.scheme'))<li class="nav-item"><a href="{{ URL::asset('admin/commission') }}" class="nav-link">Scheme Commission</a></li>@endif
                            @if(admin_can('commission.denomination'))<li class="nav-item"><a href="{{ URL::asset('admin/commission/denomination') }}" class="nav-link">Denomination Commission</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('users'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUsers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUsers">
                        <i class="bx bxs-user-detail"></i> <span>Users</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUsers">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('users.list'))<li class="nav-item"><a href="{{ URL::asset('admin/users/list') }}" class="nav-link">Create / List Users</a></li>@endif
                            @if(admin_can('users.list') || admin_can('users.deleted'))<li class="nav-item"><a href="{{ URL::asset('admin/users/deleted') }}" class="nav-link">User Delete</a></li>@endif
                            @if(admin_can('users.kyc'))<li class="nav-item"><a href="{{ URL::asset('admin/users/kyc') }}" class="nav-link">Manage KYC</a></li>@endif
                            @if(admin_can('users.service_lock'))<li class="nav-item"><a href="{{ URL::asset('admin/users/service-lock') }}" class="nav-link">User Service Lock</a></li>@endif
                            @if(admin_can('users.login_history'))<li class="nav-item"><a href="{{ URL::asset('admin/users/login-history') }}" class="nav-link">Login History</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('payments'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPayments" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPayments">
                        <i class="bx bxs-wallet"></i> <span>Payments</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPayments">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('payments.fund'))<li class="nav-item"><a href="{{ URL::asset('admin/fund/credit-debit') }}" class="nav-link">Fund Credit / Debit</a></li>@endif
                            @if(admin_can('payments.fund_request'))<li class="nav-item"><a href="{{ URL::asset('admin/fund/fund-request') }}" class="nav-link">Pending Fund Requests</a></li>@endif
                            @if(admin_can('payments.fund_report'))<li class="nav-item"><a href="{{ URL::asset('admin/fund/fund-report') }}" class="nav-link">Fund Transfer History</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('accounts'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAccountsReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccountsReports">
                        <i class="bx bx-line-chart"></i> <span>Accounts Reports</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAccountsReports">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('accounts.account_report'))<li class="nav-item"><a href="{{ URL::asset('admin/user-reports/account-report') }}" class="nav-link">Account Reports</a></li>@endif
                            @if(admin_can('accounts.user_sale'))<li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/user-sale-report') }}" class="nav-link">User Sale Report</a></li>@endif
                            @if(admin_can('accounts.md_dt_sale'))<li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/md-dt-sale-report') }}" class="nav-link">MD / DT Sale Report</a></li>@endif
                            @if(admin_can('accounts.operator_sale'))<li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/provider-sale-report') }}" class="nav-link">Operator Sale Report</a></li>@endif
                            @if(admin_can('accounts.api_sale'))<li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/api-sale-report') }}" class="nav-link">API Sale Report</a></li>@endif
                            @if(admin_can('accounts.api_log'))<li class="nav-item"><a href="{{ URL::asset('admin/admin-reports/api-log-report') }}" class="nav-link">API Log Report</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('apis'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarApis" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApis">
                        <i class="bx bx-link"></i> <span>APIs</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarApis">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('apis.list'))<li class="nav-item"><a href="{{ URL::asset('admin/system/apis') }}" class="nav-link">Add / List APIs</a></li>@endif
                            @if(admin_can('apis.balance'))<li class="nav-item"><a href="{{ URL::asset('admin/apis/balance-check') }}" class="nav-link">API Balance Check</a></li>@endif
                            @if(admin_can('apis.bbps'))<li class="nav-item"><a href="{{ URL::asset('admin/apis/bbps') }}" class="nav-link">BBPS Biller / Fetch API</a></li>@endif
                            @if(admin_can('apis.plan'))<li class="nav-item"><a href="{{ URL::asset('admin/apis/plan-circle-dth-api') }}" class="nav-link">Plan / Circle / DTH Info Fetch API</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('complains'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarComplains" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarComplains">
                        <i class="bx bx-message-rounded-dots"></i> <span>Complains</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarComplains">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('complains.all'))<li class="nav-item"><a href="{{ URL::asset('admin/support/complaint') }}" class="nav-link">All Complains</a></li>@endif
                            @if(admin_can('recharge_reports.recharge_report'))<li class="nav-item"><a href="{{ URL::asset('admin/user-reports/recharge-report?complaint=1') }}" class="nav-link">Complaint from Recharge</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('system'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarSystem" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSystem">
                        <i class="bx bx-cog"></i> <span>System</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarSystem">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('system.settings'))<li class="nav-item"><a href="{{ URL::asset('admin/system-settings/system') }}" class="nav-link">System Setting</a></li>@endif
                            @if(admin_can('system.scheme'))<li class="nav-item"><a href="{{ URL::asset('admin/system/scheme') }}" class="nav-link">Scheme</a></li>@endif
                            @if(admin_can('system.banks'))<li class="nav-item"><a href="{{ URL::asset('admin/system/banks') }}" class="nav-link">Banks</a></li>@endif
                            @if(admin_can('system.amount_block'))<li class="nav-item"><a href="{{ URL::asset('admin/system/amount-block') }}" class="nav-link">Amount Block</a></li>@endif
                            @if(admin_can('system.amount_switch'))<li class="nav-item"><a href="{{ URL::asset('admin/system/amount-wize-switch') }}" class="nav-link">Amount Wise Switch</a></li>@endif
                            @if(admin_can('system.state_switch'))<li class="nav-item"><a href="{{ URL::asset('admin/system/state-wize-switch') }}" class="nav-link">State Wise Switch</a></li>@endif
                            @if(admin_can('system.user_switch'))<li class="nav-item"><a href="{{ URL::asset('admin/system/user-wize-switch') }}" class="nav-link">User Wise Switch</a></li>@endif
                            @if(admin_can('system.role') || admin_can('employees.permissions'))<li class="nav-item"><a href="{{ URL::asset('admin/system/role') }}" class="nav-link">Role</a></li>@endif
                            @if(admin_can('system.services'))<li class="nav-item"><a href="{{ URL::asset('admin/system/services') }}" class="nav-link">Operator Type / Services</a></li>@endif
                            @if(admin_can('system.providers'))<li class="nav-item"><a href="{{ URL::asset('admin/system/providers') }}" class="nav-link">Providers</a></li>@endif
                            @if(admin_can('system.announcement'))<li class="nav-item"><a href="{{ URL::asset('admin/system/announcement') }}" class="nav-link">Announcement</a></li>@endif
                            @if(admin_can('system.slider'))<li class="nav-item"><a href="{{ URL::asset('admin/system/slider') }}" class="nav-link">Slider</a></li>@endif
                            @if(admin_can('system.audit'))<li class="nav-item"><a href="{{ URL::asset('admin/system/audit-log') }}" class="nav-link">Audit Log</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                @if(admin_can('website'))
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarWebsite" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarWebsite">
                        <i class="bx bx-globe"></i> <span>Website</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarWebsite">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('website.ads'))<li class="nav-item"><a href="{{ URL::asset('admin/website/ads') }}" class="nav-link">Create Ads</a></li>@endif
                            @if(admin_can('website.pages'))<li class="nav-item"><a href="{{ URL::asset('admin/website/pages') }}" class="nav-link">User Website List</a></li>@endif
                            @if(admin_can('website.setting'))<li class="nav-item"><a href="{{ URL::asset('admin/website/setting') }}" class="nav-link">Website Setting</a></li>@endif
                            @if(admin_can('website.company'))<li class="nav-item"><a href="{{ URL::asset('admin/company/manage-company') }}" class="nav-link">Company Profile</a></li>@endif
                            @if(admin_can('website.company') || admin_can('website.logos'))<li class="nav-item"><a href="{{ route('companyLogosPage') }}" class="nav-link">Company Logos</a></li>@endif
                            @if(admin_can('website.banners'))<li class="nav-item"><a href="{{ URL::asset('admin/website/banners') }}" class="nav-link">Banner Setting</a></li>@endif
                            @if(admin_can('website.popups'))<li class="nav-item"><a href="{{ URL::asset('admin/website/popups') }}" class="nav-link">Popup Master</a></li>@endif
                            @if(admin_can('website.policy'))<li class="nav-item"><a href="{{ URL::asset('admin/website/policy') }}" class="nav-link">Web Policy</a></li>@endif
                        </ul>
                    </div>
                </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarExtras" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExtras">
                        <i class="bx bx-gift"></i> <span>Extras</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarExtras">
                        <ul class="nav nav-sm flex-column">
                            @if(admin_can('extras.sms_api'))<li class="nav-item"><a href="{{ route('smsApiListPage') }}" class="nav-link">SMS API List</a></li>@endif
                            @if(admin_can('extras.whatsapp'))<li class="nav-item"><a href="{{ route('whatsappApiPage') }}" class="nav-link">WhatsApp API</a></li>@endif
                            @if(admin_can('extras.whatsapp_template'))<li class="nav-item"><a href="{{ route('whatsappTemplatePage') }}" class="nav-link">WhatsApp Template List</a></li>@endif
                            @if(admin_can('extras.send_message'))<li class="nav-item"><a href="{{ URL::asset('admin/users/send-message') }}" class="nav-link">Send SMS / Notification</a></li>@endif
                            @if(admin_can('extras.sms_report'))<li class="nav-item"><a href="{{ route('sendSmsReport') }}" class="nav-link">Send SMS Report</a></li>@endif
                            @if(admin_can('extras.notification_report'))<li class="nav-item"><a href="{{ route('notificationSendReport') }}" class="nav-link">Notification Send Report</a></li>@endif
                            @if(admin_can('extras.sms_template'))<li class="nav-item"><a href="{{ URL::asset('admin/company/sms-template') }}" class="nav-link">SMS Templates</a></li>@endif
                            @if(admin_can('extras.email_template'))<li class="nav-item"><a href="{{ URL::asset('admin/company/email-template') }}" class="nav-link">Email Templates</a></li>@endif
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
