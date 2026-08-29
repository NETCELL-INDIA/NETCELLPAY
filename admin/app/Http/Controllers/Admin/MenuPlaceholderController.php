<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\URL;

class MenuPlaceholderController extends Controller
{
    /**
     * Map old Rambhiya placeholder menu URLs → existing Netcell working pages.
     * Anything not listed still shows the placeholder shell.
     */
    private array $redirects = [
        // Users
        'users/create-user' => 'admin/users/list',
        'users/login-history' => 'admin/users/login-history',
        'users/default-margins' => 'admin/system/scheme',
        'users/default-cashback' => 'admin/system/scheme',
        'users/special-margins' => 'admin/system/scheme',
        'users/manage-kyc' => 'admin/users/list',
        'users/deleted-users' => 'admin/users/list',

        // Payments
        'payments/fund-transfer' => 'admin/users/list',
        'payments/list-fund-transfers' => 'admin/fund/fund-report',
        'payments/credit-debit' => 'admin/users/list',
        'payments/bank-details' => 'admin/system/banks',
        'payments/online-fund-report' => 'admin/fund/fund-report',

        // Accounts
        'accounts/payment-report' => 'admin/fund/fund-report',
        'accounts/account-ledger' => 'admin/user-reports/account-report',
        'accounts/profit-report' => 'admin/admin-reports/user-sale-report',
        'accounts/performance-report' => 'admin/admin-reports/user-sale-report',
        'accounts/balance-report' => 'admin/users/list',

        // APIs
        'apis/quick-add' => 'admin/system/apis',
        'apis/add-api' => 'admin/system/apis',
        'apis/bill-fetch-settings' => 'admin/system/apis',
        'apis/plan-circle-dth-api' => 'admin/apis/plan-circle-dth-api',
        'apis/plan_circle_fetch_api_settings' => 'admin/apis/plan_circle_fetch_api_settings',
        'apis/balance-status-check' => 'admin/system/apis',
        'apis/special-inward-margins' => 'admin/system/scheme',

        // Complains / Operators
        'complains/create-complain' => 'admin/support/complaint',
        'complains/all-complains' => 'admin/support/complaint',
        'operators/create-operator' => 'admin/system/providers',
        'operators/list-operators' => 'admin/system/providers',

        // Employees
        'employees/create-employee' => 'admin/users/list',
        'employees/list-employee' => 'admin/users/list',
        'employees/permissions' => 'admin/system/role',
        'employees/performance-report' => 'admin/admin-reports/user-sale-report',

        // Extras
        'extras/manage-news' => 'admin/system/announcement',
        'extras/send-mobile-notification' => 'admin/users/send-message',
        'extras/send-sms' => 'admin/users/send-message',
        'extras/sms-api-list' => 'admin/extras/sms-api-list',
        'extras/sms-api' => 'admin/extras/sms-api-list',
        'extras/send-whatsapp' => 'admin/users/send-message',
        'extras/send-email' => 'admin/company/email-template',
        'extras/api-document' => 'admin/admin-reports/api-log-report',
        'extras/settings' => 'admin/company/manage-company',
        'extras/slab-settings' => 'admin/system/scheme',
        'extras/messages-logs' => 'admin/extras/send-sms-report',
        'extras/send-sms-report' => 'admin/extras/send-sms-report',
        'extras/notification-send-report' => 'admin/extras/notification-send-report',

        // Routings (legacy placeholder keys)
        'routings/general-routings' => 'admin/routings/general',
        'routings/operator-routing' => 'admin/routings/operator',
        'routings/servicewise-routing' => 'admin/system/state-wize-switch',
        'routings/pending-settings' => 'admin/system/amount-block',
    ];

    /**
     * Remaining modules with no Netcell backend yet (show placeholder).
     */
    private array $pages = [
        // DMT
        'dmt/dmt-report' => ['section' => 'DMT', 'title' => 'DMT Report', 'description' => 'Domestic money transfer report — module not in this Netcell build yet'],
        'dmt/dmt-kyc-report' => ['section' => 'DMT', 'title' => 'DMT KYC Report', 'description' => 'DMT KYC — not in this Netcell build yet'],
        'dmt/dmt-settings' => ['section' => 'DMT', 'title' => 'DMT Settings', 'description' => 'DMT configuration — not in this Netcell build yet'],
        'dmt/dmt-inward-margins' => ['section' => 'DMT', 'title' => 'DMT Inward Margins', 'description' => 'Not in this Netcell build yet'],
        'dmt/dmt-outward-margins' => ['section' => 'DMT', 'title' => 'DMT Outward Margins', 'description' => 'Not in this Netcell build yet'],

        // Express DMT
        'express-dmt/express-dmt-report' => ['section' => 'Express DMT', 'title' => 'Express DMT Report', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/express-dmt-settings' => ['section' => 'Express DMT', 'title' => 'Express DMT Settings', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/inward-margins' => ['section' => 'Express DMT', 'title' => 'Inward Margins', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/outward-margins' => ['section' => 'Express DMT', 'title' => 'Outward Margins', 'description' => 'Not in this Netcell build yet'],

        // Users (no dedicated pages)
        'users/signup-requests' => ['section' => 'Users', 'title' => 'Signup Requests', 'description' => 'Signup requests module not present'],
        'users/refer-earn-users' => ['section' => 'Users', 'title' => 'Refer & Earn Users', 'description' => 'Refer & Earn module not present'],

        // Khatabook
        'khatabook/list-of-users' => ['section' => 'Khatabook', 'title' => 'List of Users', 'description' => 'Khatabook not in this Netcell build'],
        'khatabook/auto-credit' => ['section' => 'Khatabook', 'title' => 'Auto Credit', 'description' => 'Khatabook not in this Netcell build'],
        'khatabook/credit-ledger' => ['section' => 'Khatabook', 'title' => 'Credit Ledger', 'description' => 'Khatabook not in this Netcell build'],
        'khatabook/credit-history' => ['section' => 'Khatabook', 'title' => 'Credit History', 'description' => 'Khatabook not in this Netcell build'],
        'khatabook/outstanding-report' => ['section' => 'Khatabook', 'title' => 'Outstanding Report', 'description' => 'Khatabook not in this Netcell build'],

        // Payments missing
        'payments/balance-exchange-settings' => ['section' => 'Payments', 'title' => 'Balance Exchange Settings', 'description' => 'Balance exchange module not present'],

        // Accounts missing
        'accounts/balance-exchange-report' => ['section' => 'Accounts Reports', 'title' => 'Balance Exchange Report', 'description' => 'Not present in this Netcell build'],
        'accounts/invoice' => ['section' => 'Accounts Reports', 'title' => 'Invoice', 'description' => 'Invoice module not present'],
        'accounts/tds' => ['section' => 'Accounts Reports', 'title' => 'TDS', 'description' => 'TDS module not present'],

        // Pancard
        'pancard/pancard-report' => ['section' => 'Pancard', 'title' => 'Pancard Report', 'description' => 'Pancard not in this Netcell build'],
        'pancard/pancard-agents' => ['section' => 'Pancard', 'title' => 'Pancard Agents', 'description' => 'Pancard not in this Netcell build'],
        'pancard/pancard-settings' => ['section' => 'Pancard', 'title' => 'Pancard Settings', 'description' => 'Pancard not in this Netcell build'],

        // W2R
        'w2r/pending-requests' => ['section' => 'Wrong 2 Right', 'title' => 'Pending Requests', 'description' => 'Wrong 2 Right not in this Netcell build'],
        'w2r/processed-requests' => ['section' => 'Wrong 2 Right', 'title' => 'Processed Requests', 'description' => 'Wrong 2 Right not in this Netcell build'],

        // Employees missing
        'employees/login-history' => ['section' => 'Employees', 'title' => 'Login History', 'description' => 'Employee login history not present'],
    ];

    public function show(string $section, string $slug)
    {
        $key = $section . '/' . $slug;

        if (isset($this->redirects[$key])) {
            return redirect(URL::asset($this->redirects[$key]));
        }

        if (!isset($this->pages[$key])) {
            abort(404);
        }

        $page = $this->pages[$key];

        return view('admin.recharge-reports.placeholder', [
            'title' => $page['title'],
            'description' => $page['description'],
            'slug' => $key,
            'section' => $page['section'],
        ]);
    }
}
