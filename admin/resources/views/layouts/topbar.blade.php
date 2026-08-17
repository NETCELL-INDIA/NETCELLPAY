<header id="page-topbar" class="rb-topbar">
    <div class="layout-width">
        <div class="navbar-header rb-navbar">
            <div class="d-flex align-items-center">
                <div class="navbar-brand-box horizontal-logo">
                    @php
                        $tbBrand = $company?->company_name ?? 'NETCELL PAY';
                    @endphp
                    <a href="{{ URL::asset('admin/dashboard') }}" class="np-h-brand logo logo-dark logo-light" title="{{ $tbBrand }}">
                        <span class="np-h-text">
                            <strong>{{ $tbBrand }}</strong>
                            <span>Admin Panel</span>
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-2 fs-16 header-item vertical-menu-btn topnav-hamburger"
                    id="topnav-hamburger-icon" title="Toggle menu">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <button type="button" class="btn rb-wallet-btn LoadWallet ms-1" id="topbar_wallet_btn">
                    <i class="ri-wallet-3-line me-1"></i>
                    <span id="topbar_wallet_amount">₹ {{ number_format(round(optional(DB::table('users')->where('id', Session::get('user_id'))->first())->wallet_balance ?? 0, 2), 2) }}</span>
                </button>
            </div>

            <div class="d-flex align-items-center rb-top-actions">
                <button type="button" class="btn rb-icon-btn" title="Live Recharge Report"
                    onclick="window.open('{{ URL::asset('admin/admin-reports/recharge-live-reports') }}','liveRechargeReport','width='+(screen.width-80)+',height='+(screen.height-120)+',left=40,top=40,resizable=yes,scrollbars=yes')">
                    <i class="ri-pulse-line"></i>
                </button>

                <button type="button" class="btn rb-icon-btn" title="Complaints"
                    onclick="location.href='{{ URL::asset('admin/support/complaint') }}'">
                    <i class="ri-customer-service-2-line"></i>
                    <span class="rb-badge" id="TopBarComplaintCount"></span>
                </button>

                <button type="button" class="btn rb-icon-btn" title="Pending Recharges"
                    onclick="location.href='{{ URL::asset('admin/user-reports/recharge-report?status=Pending') }}'">
                    <i class="ri-time-line"></i>
                    <span class="rb-badge rb-badge-warn" id="TopBarPendingCount"></span>
                </button>

                <button type="button" class="btn rb-icon-btn" id="adminFullscreenBtn" title="Fullscreen"
                    onclick="return window.ncAdminFs && window.ncAdminFs.toggle(event);">
                    <i class="ri-fullscreen-line" id="adminFullscreenIcon"></i>
                </button>
                <script>
                (function () {
                    var ORIGIN = location.origin;
                    var inFrame = window.self !== window.top;

                    function nativeOn(doc) {
                        doc = doc || document;
                        return !!(doc.fullscreenElement || doc.webkitFullscreenElement
                            || doc.mozFullScreenElement || doc.msFullscreenElement);
                    }
                    function requestFs(doc) {
                        doc = doc || document;
                        var node = doc.documentElement;
                        var req = node.requestFullscreen || node.webkitRequestFullscreen
                            || node.mozRequestFullScreen || node.msRequestFullscreen;
                        if (!req) return;
                        try {
                            var p = req.call(node);
                            if (p && typeof p.catch === 'function') p.catch(function () {});
                        } catch (err) {}
                    }
                    function exitFs(doc) {
                        doc = doc || document;
                        if (!nativeOn(doc)) return;
                        var fn = doc.exitFullscreen || doc.webkitExitFullscreen
                            || doc.mozCancelFullScreen || doc.msExitFullscreen;
                        if (fn) {
                            try { fn.call(doc); } catch (err) {}
                        }
                    }
                    function setIcon(on) {
                        var icon = document.getElementById('adminFullscreenIcon');
                        var btn = document.getElementById('adminFullscreenBtn');
                        if (icon) icon.className = on ? 'ri-fullscreen-exit-line' : 'ri-fullscreen-line';
                        if (btn) {
                            btn.classList.toggle('is-active', on);
                            btn.title = on ? 'Exit fullscreen' : 'Fullscreen';
                        }
                    }
                    function postParent(action) {
                        try { window.top.postMessage({ type: 'nc-admin-fs', action: action }, ORIGIN); } catch (err) {}
                    }
                    function keepParentFs() {
                        try {
                            var d = window.top.document;
                            if (nativeOn(d)) return;
                            requestFs(d);
                        } catch (err) {}
                    }
                    function shellEl() { return document.getElementById('nc-fs-shell'); }

                    function enterShell() {
                        if (inFrame) return;
                        if (!shellEl()) {
                            var wrap = document.createElement('div');
                            wrap.id = 'nc-fs-shell';
                            var iframe = document.createElement('iframe');
                            iframe.id = 'nc-fs-frame';
                            iframe.setAttribute('allow', 'fullscreen');
                            iframe.src = location.href;
                            wrap.appendChild(iframe);
                            document.body.appendChild(wrap);
                        }
                        requestFs(document);
                        setIcon(true);
                    }
                    function exitShell() {
                        if (inFrame) {
                            postParent('exit');
                            return;
                        }
                        var iframe = document.getElementById('nc-fs-frame');
                        var next = null;
                        try {
                            if (iframe && iframe.contentWindow) next = iframe.contentWindow.location.href;
                        } catch (err) {}
                        var wrap = shellEl();
                        if (wrap) wrap.remove();
                        exitFs(document);
                        setIcon(false);
                        if (next && next.split('#')[0] !== location.href.split('#')[0]) {
                            location.replace(next);
                        }
                    }

                    window.ncAdminFs = {
                        toggle: function (e) {
                            if (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                            }
                            if (inFrame) {
                                postParent('toggle');
                                return false;
                            }
                            if (shellEl()) exitShell();
                            else enterShell();
                            return false;
                        }
                    };

                    if (inFrame) {
                        setIcon(true);
                        document.addEventListener('click', function (e) {
                            if (e.target && e.target.closest && e.target.closest('#adminFullscreenBtn')) return;
                            var a = e.target.closest && e.target.closest('a[href]');
                            if (a && (a.target === '_top' || a.target === '_parent')) {
                                a.target = '_self';
                            }
                            keepParentFs();
                        }, true);
                    } else {
                        window.addEventListener('message', function (e) {
                            if (e.origin !== ORIGIN) return;
                            if (!e.data || e.data.type !== 'nc-admin-fs') return;
                            if (e.data.action === 'toggle') {
                                if (shellEl()) exitShell();
                                else enterShell();
                            } else if (e.data.action === 'exit') {
                                exitShell();
                            } else if (e.data.action === 'keep') {
                                if (shellEl()) requestFs(document);
                            }
                        });
                    }
                })();
                </script>

                @php
                    $topUser = optional(DB::table('users')->where('id', Session::get('user_id'))->first());
                    $topUserName = trim(($topUser->first_name ?? '') . ' ' . ($topUser->last_name ?? ''));
                    if ($topUserName === '') {
                        $topUserName = $topUser->outlet_name ?? 'Admin';
                    }
                    $topUserMobile = $topUser->mobile_number ?? '';
                    $topUserWallet = round($topUser->wallet_balance ?? 0, 2);
                    $topUserAvatar = admin_company_logo($company?->company_icon ?? null)
                        ?? admin_asset('assets/images/users/avatar-1.jpg');
                @endphp
                <div class="dropdown ms-1">
                    <button type="button" class="btn rb-user-btn" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ $topUserName }}">
                        <img class="rb-avatar" src="{{ $topUserAvatar }}" alt="{{ $topUserName }}">
                    </button>
                    <div class="dropdown-menu dropdown-menu-end rb-user-menu">
                        <div class="rb-user-menu-head">
                            <img class="rb-avatar rb-avatar-lg" src="{{ $topUserAvatar }}" alt="">
                            <div class="min-w-0">
                                <div class="rb-user-name text-truncate" id="nav_first_name">{{ $topUserName }}</div>
                                @if($topUserMobile)
                                    <div class="rb-user-role">{{ $topUserMobile }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="rb-user-wallet">
                            <div>
                                <div class="rb-user-wallet-label">Wallet Balance</div>
                                <div class="rb-user-wallet-amt" id="topbar_dropdown_wallet">₹ {{ number_format($topUserWallet, 2) }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success LoadWallet">Add Fund</button>
                        </div>

                        <div class="rb-user-section">Account</div>
                        <a class="dropdown-item" href="{{ route('myProfile') }}">
                            <i class="ri-user-3-line me-2"></i> My Profile
                        </a>
                        <a class="dropdown-item" href="{{ route('changePassword') }}">
                            <i class="ri-lock-password-line me-2"></i> Change Password
                        </a>
                        <a class="dropdown-item" href="{{ route('loginHistory') }}">
                            <i class="ri-history-line me-2"></i> Login History
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/company/manage-company') }}">
                            <i class="ri-building-line me-2"></i> Company Settings
                        </a>

                        <div class="rb-user-section">Quick Links</div>
                        <a class="dropdown-item" href="{{ URL::asset('admin/user-reports/recharge-report?status=Pending') }}">
                            <i class="ri-time-line me-2"></i> Pending Recharges
                            <span class="rb-dd-badge rb-dd-badge-warn" id="DropPendingCount"></span>
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/support/complaint') }}">
                            <i class="ri-customer-service-2-line me-2"></i> Complaints
                            <span class="rb-dd-badge" id="DropComplaintCount"></span>
                        </a>
                        <a class="dropdown-item" href="javascript:void(0);"
                            onclick="window.open('{{ URL::asset('admin/admin-reports/recharge-live-reports') }}','liveRechargeReport','width='+(screen.width-80)+',height='+(screen.height-120)+',left=40,top=40,resizable=yes,scrollbars=yes')">
                            <i class="ri-pulse-line me-2"></i> Live Recharge Report
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/fund/fund-request') }}">
                            <i class="ri-wallet-3-line me-2"></i> Fund Requests
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/user-reports/account-report') }}">
                            <i class="ri-file-list-3-line me-2"></i> Account Ledger
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/users/list') }}">
                            <i class="ri-group-line me-2"></i> Manage Users
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/users/send-message') }}">
                            <i class="ri-mail-send-line me-2"></i> Send Message
                        </a>

                        <div class="rb-user-section">Support</div>
                        <a class="dropdown-item" id="helpSupport" href="javascript:void(0);">
                            <i class="ri-question-line me-2"></i> Help / Support
                        </a>
                        <a class="dropdown-item" href="{{ URL::asset('admin/system/announcement') }}">
                            <i class="ri-megaphone-line me-2"></i> Announcements
                        </a>

                        <div class="dropdown-divider my-0"></div>
                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="logOut()">
                            <i class="ri-logout-box-r-line me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div id="LoadWalletModal" class="modal fade" tabindex="-1" aria-labelledby="LoadWalletModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="LoadWalletModalLabel">Load Wallet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="admin_load_wallet_form">
                    @csrf
                    <div class="mb-3">
                        <label for="admin_load_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" id="admin_load_amount" min="1" step="0.01" placeholder="Enter amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_load_remark" class="form-label">Remark <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remark" id="admin_load_remark" rows="3" placeholder="Enter remark" required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="admin_load_wallet_btn">Add Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@php
    $helpCompany = $company ?? DB::table('companies')->where('status', 1)->first();
@endphp
<div id="helpSupportModal" class="modal fade" tabindex="-1" aria-labelledby="helpSupportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpSupportModalLabel">Help / Support</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Company</div>
                    <div class="fw-semibold">{{ $helpCompany->company_name ?? 'NETCELL PAY' }}</div>
                </div>
                @if(!empty($helpCompany->support_number))
                <div class="mb-3">
                    <div class="text-muted small mb-1">Support Mobile</div>
                    <a href="tel:{{ $helpCompany->support_number }}" class="fw-semibold text-decoration-none">{{ $helpCompany->support_number }}</a>
                    @if(!empty($helpCompany->support_number_2) && $helpCompany->support_number_2 !== $helpCompany->support_number)
                        <span class="text-muted"> / </span>
                        <a href="tel:{{ $helpCompany->support_number_2 }}" class="fw-semibold text-decoration-none">{{ $helpCompany->support_number_2 }}</a>
                    @endif
                </div>
                @endif
                @if(!empty($helpCompany->support_email))
                <div class="mb-3">
                    <div class="text-muted small mb-1">Support Email</div>
                    <a href="mailto:{{ $helpCompany->support_email }}" class="fw-semibold text-decoration-none">{{ $helpCompany->support_email }}</a>
                </div>
                @endif
                @if(!empty($helpCompany->company_address))
                <div class="mb-0">
                    <div class="text-muted small mb-1">Address</div>
                    <div>{{ $helpCompany->company_address }}</div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
