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
        'users/manage-kyc' => 'admin/users/kyc',
        'users/signup-requests' => 'admin/business/signups',
        'users/refer-earn-users' => 'admin/business/referrals',
        'khatabook/list-of-users' => 'admin/business/khatabook',
        'khatabook/auto-credit' => 'admin/business/khatabook',
        'khatabook/credit-ledger' => 'admin/business/khatabook',
        'khatabook/credit-history' => 'admin/business/khatabook',
        'khatabook/outstanding-report' => 'admin/business/khatabook',
        'accounts/tds' => 'admin/business/tds',
        'accounts/invoice' => 'admin/business/tds',
        'dmt/dmt-report' => 'admin/business/dmt',
        'dmt/dmt-kyc-report' => 'admin/business/signups',
        'users/deleted-users' => 'admin/users/deleted',

        // Payments
        'payments/fund-transfer' => 'admin/fund/credit-debit',
        'payments/credit-debit' => 'admin/fund/credit-debit',
        'payments/list-fund-transfers' => 'admin/fund/fund-report',
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
        'apis/balance-status-check' => 'admin/apis/balance-check',
        'apis/bill-fetch-settings' => 'admin/apis/bbps',
        'apis/plan-circle-dth-api' => 'admin/apis/plan-circle-dth-api',
        'apis/plan_circle_fetch_api_settings' => 'admin/apis/plan_circle_fetch_api_settings',
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
        'w2r/pending-requests' => 'admin/recharge-reports/wrong-recharge',
        'w2r/processed-requests' => 'admin/recharge-reports/wrong-recharge?status=done',
    ];

    /**
     * Remaining modules with no Netcell backend yet (show placeholder).
     */
    private array $pages = [
        // DMT
        'dmt/dmt-settings' => ['section' => 'DMT', 'title' => 'DMT Settings', 'description' => 'DMT configuration — not in this Netcell build yet'],
        'dmt/dmt-inward-margins' => ['section' => 'DMT', 'title' => 'DMT Inward Margins', 'description' => 'Not in this Netcell build yet'],
        'dmt/dmt-outward-margins' => ['section' => 'DMT', 'title' => 'DMT Outward Margins', 'description' => 'Not in this Netcell build yet'],

        // Express DMT
        'express-dmt/express-dmt-report' => ['section' => 'Express DMT', 'title' => 'Express DMT Report', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/express-dmt-settings' => ['section' => 'Express DMT', 'title' => 'Express DMT Settings', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/inward-margins' => ['section' => 'Express DMT', 'title' => 'Inward Margins', 'description' => 'Not in this Netcell build yet'],
        'express-dmt/outward-margins' => ['section' => 'Express DMT', 'title' => 'Outward Margins', 'description' => 'Not in this Netcell build yet'],

        // Users (no dedicated pages)
        // Khatabook pages redirect to the working ledger.

        // Payments missing
        'payments/balance-exchange-settings' => ['section' => 'Payments', 'title' => 'Balance Exchange Settings', 'description' => 'Balance exchange module not present'],

        // Accounts missing
        'accounts/balance-exchange-report' => ['section' => 'Accounts Reports', 'title' => 'Balance Exchange Report', 'description' => 'Not present in this Netcell build'],

        // Pancard
        'pancard/pancard-report' => ['section' => 'Pancard', 'title' => 'Pancard Report', 'description' => 'Pancard not in this Netcell build'],
        'pancard/pancard-agents' => ['section' => 'Pancard', 'title' => 'Pancard Agents', 'description' => 'Pancard not in this Netcell build'],
        'pancard/pancard-settings' => ['section' => 'Pancard', 'title' => 'Pancard Settings', 'description' => 'Pancard not in this Netcell build'],

        // W2R

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
