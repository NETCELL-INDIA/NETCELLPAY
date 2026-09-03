<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class AdminMenuService
{
    public const SUPERADMIN_ROLE_ID = 1;

    public const SYSTEM_ROLE_IDS = [1, 2, 3, 4, 5, 6];

    public static function ensureTables(): void
    {
        try {
            if (Schema::hasTable('roles')) {
                if (! Schema::hasColumn('roles', 'is_admin')) {
                    Schema::table('roles', function ($table) {
                        $table->unsignedTinyInteger('is_admin')->default(0);
                    });
                }
                if (! Schema::hasColumn('roles', 'slug') && Schema::hasColumn('roles', 'role_name')) {
                    Schema::table('roles', function ($table) {
                        $table->string('slug', 100)->nullable();
                    });
                }
            }
            if (! Schema::hasTable('role_menus')) {
                Schema::create('role_menus', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('role_id')->index();
                    $table->string('menu_key', 120)->index();
                    $table->timestamps();
                    $table->unique(['role_id', 'menu_key']);
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public static function adminRoleIds(): array
    {
        self::ensureTables();
        $ids = [self::SUPERADMIN_ROLE_ID];
        try {
            if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'is_admin')) {
                $extra = DB::table('roles')->where('is_admin', 1)->pluck('id')->all();
                $ids = array_merge($ids, $extra);
            }
        } catch (\Throwable $e) {
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function catalog(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'paths' => ['admin/dashboard']],
            ['key' => 'recharge_reports', 'label' => 'Recharge Reports', 'children' => [
                ['key' => 'recharge_reports.recharge_report', 'label' => 'Recharge Report', 'paths' => ['admin/user-reports/recharge-report']],
                ['key' => 'recharge_reports.manual_report', 'label' => 'Manual Recharge Report', 'paths' => ['admin/recharge-reports/manual-report']],
                ['key' => 'recharge_reports.pending_report', 'label' => 'Pending Report', 'paths' => ['admin/recharge-reports/pending-report']],
                ['key' => 'recharge_reports.live_report', 'label' => 'Live Recharge Report', 'paths' => ['admin/admin-reports/recharge-live-reports']],
                ['key' => 'recharge_reports.margin_report', 'label' => 'Margin Report', 'paths' => ['admin/recharge-reports/margin-report']],
                ['key' => 'recharge_reports.cashback_report', 'label' => 'Cashback Report', 'paths' => ['admin/recharge-reports/cashback-report']],
                ['key' => 'recharge_reports.api_report', 'label' => 'API Report', 'paths' => ['admin/recharge-reports/api-report']],
                ['key' => 'recharge_reports.refund_report', 'label' => 'Refund Report', 'paths' => ['admin/recharge-reports/refund-report']],
                ['key' => 'recharge_reports.recharge_logs', 'label' => 'Recharge Logs', 'paths' => ['admin/recharge-reports/recharge-logs']],
                ['key' => 'recharge_reports.fail2success', 'label' => 'Supplier Fail 2 Success', 'paths' => ['admin/recharge-reports/supplier-fail-2-success']],
                ['key' => 'recharge_reports.rehit', 'label' => 'Resend Report', 'paths' => ['admin/recharge-reports/resend-report', 'admin/recharge-reports/retry-log', 'admin/recharge-reports/rehit-recharge-history']],
                ['key' => 'recharge_reports.amountwise', 'label' => 'Amountwise Report', 'paths' => ['admin/recharge-reports/amountwise-report']],
                ['key' => 'recharge_reports.consumption', 'label' => 'Consumption Report', 'paths' => ['admin/recharge-reports/consumption-report']],
                ['key' => 'recharge_reports.roffer', 'label' => 'R-Offer Report', 'paths' => ['admin/recharge-reports/r-offer-report']],
                ['key' => 'recharge_reports.plan_logs', 'label' => 'Plan Logs Report', 'paths' => ['admin/recharge-reports/plan-logs-report']],
            ]],
            ['key' => 'routings', 'label' => 'Routings', 'children' => [
                ['key' => 'routings.general', 'label' => 'General Routings', 'paths' => ['admin/routings/general']],
                ['key' => 'routings.operator', 'label' => 'Manage Operator', 'paths' => ['admin/routings/operator']],
                ['key' => 'routings.api_switching', 'label' => 'API Switching', 'paths' => ['admin/routings/api-switching']],
                ['key' => 'routings.priority', 'label' => 'Routes Priority', 'paths' => ['admin/company/routes-settings']],
            ]],
            ['key' => 'commission', 'label' => 'Commission', 'children' => [
                ['key' => 'commission.scheme', 'label' => 'Scheme Commission', 'paths' => ['admin/commission', 'admin/system/scheme']],
                ['key' => 'commission.denomination', 'label' => 'Denomination Commission', 'paths' => ['admin/commission/denomination']],
            ]],
            ['key' => 'users', 'label' => 'Users', 'children' => [
                ['key' => 'users.list', 'label' => 'Create / List Users', 'paths' => ['admin/users/list']],
                ['key' => 'users.kyc', 'label' => 'Manage KYC', 'paths' => ['admin/users/kyc']],
                ['key' => 'users.service_lock', 'label' => 'User Service Lock', 'paths' => ['admin/users/service-lock']],
                ['key' => 'users.login_history', 'label' => 'Login History', 'paths' => ['admin/users/login-history']],
            ]],
            ['key' => 'payments', 'label' => 'Payments', 'children' => [
                ['key' => 'payments.fund', 'label' => 'Fund Credit / Debit', 'paths' => ['admin/fund/credit-debit']],
                ['key' => 'payments.fund_request', 'label' => 'Pending Fund Requests', 'paths' => ['admin/fund/fund-request']],
                ['key' => 'payments.fund_report', 'label' => 'Fund Transfer History', 'paths' => ['admin/fund/fund-report']],
            ]],
            ['key' => 'accounts', 'label' => 'Accounts Reports', 'children' => [
                ['key' => 'accounts.account_report', 'label' => 'Account Reports', 'paths' => ['admin/user-reports/account-report']],
                ['key' => 'accounts.user_sale', 'label' => 'User Sale Report', 'paths' => ['admin/admin-reports/user-sale-report']],
                ['key' => 'accounts.md_dt_sale', 'label' => 'MD / DT Sale Report', 'paths' => ['admin/admin-reports/md-dt-sale-report']],
                ['key' => 'accounts.operator_sale', 'label' => 'Operator Sale Report', 'paths' => ['admin/admin-reports/provider-sale-report']],
                ['key' => 'accounts.api_sale', 'label' => 'API Sale Report', 'paths' => ['admin/admin-reports/api-sale-report']],
                ['key' => 'accounts.api_log', 'label' => 'API Log Report', 'paths' => ['admin/admin-reports/api-log-report']],
            ]],
            ['key' => 'apis', 'label' => 'APIs', 'children' => [
                ['key' => 'apis.list', 'label' => 'Add / List APIs', 'paths' => ['admin/system/apis']],
                ['key' => 'apis.balance', 'label' => 'API Balance Check', 'paths' => ['admin/apis/balance-check']],
                ['key' => 'apis.bbps', 'label' => 'BBPS Biller / Fetch API', 'paths' => ['admin/apis/bbps']],
                ['key' => 'apis.plan', 'label' => 'Plan / Circle / DTH Info Fetch API', 'paths' => ['admin/apis/plan-circle-dth-api', 'admin/apis/plan_circle_fetch_api_settings']],
            ]],
            ['key' => 'complains', 'label' => 'Complains', 'children' => [
                ['key' => 'complains.all', 'label' => 'All Complains', 'paths' => ['admin/support/complaint']],
            ]],
            ['key' => 'system', 'label' => 'System', 'children' => [
                ['key' => 'system.settings', 'label' => 'System Setting', 'paths' => ['admin/system-settings']],
                ['key' => 'system.scheme', 'label' => 'Scheme', 'paths' => ['admin/system/scheme']],
                ['key' => 'system.banks', 'label' => 'Banks', 'paths' => ['admin/system/banks']],
                ['key' => 'system.amount_block', 'label' => 'Amount Block', 'paths' => ['admin/system/amount-block']],
                ['key' => 'system.amount_switch', 'label' => 'Amount Wise Switch', 'paths' => ['admin/system/amount-wize-switch']],
                ['key' => 'system.state_switch', 'label' => 'State Wise Switch', 'paths' => ['admin/system/state-wize-switch']],
                ['key' => 'system.user_switch', 'label' => 'User Wise Switch', 'paths' => ['admin/system/user-wize-switch']],
                ['key' => 'system.role', 'label' => 'Role / Permissions', 'paths' => ['admin/system/role']],
                ['key' => 'system.services', 'label' => 'Operator Type / Services', 'paths' => ['admin/system/services']],
                ['key' => 'system.providers', 'label' => 'Providers', 'paths' => ['admin/system/providers']],
                ['key' => 'system.announcement', 'label' => 'Announcement', 'paths' => ['admin/system/announcement']],
                ['key' => 'system.slider', 'label' => 'Slider', 'paths' => ['admin/system/slider']],
                ['key' => 'system.audit', 'label' => 'Audit Log', 'paths' => ['admin/system/audit-log']],
            ]],
            ['key' => 'website', 'label' => 'Website', 'children' => [
                ['key' => 'website.ads', 'label' => 'Create Ads', 'paths' => ['admin/website/ads']],
                ['key' => 'website.pages', 'label' => 'User Website List', 'paths' => ['admin/website/pages']],
                ['key' => 'website.setting', 'label' => 'Website Setting', 'paths' => ['admin/website/setting']],
                ['key' => 'website.company', 'label' => 'Company Profile', 'paths' => ['admin/company/manage-company', 'admin/company/logos']],
                ['key' => 'website.logos', 'label' => 'Company Logos', 'paths' => ['admin/company/logos']],
                ['key' => 'website.banners', 'label' => 'Banner Setting', 'paths' => ['admin/website/banners']],
                ['key' => 'website.popups', 'label' => 'Popup Master', 'paths' => ['admin/website/popups']],
                ['key' => 'website.policy', 'label' => 'Web Policy', 'paths' => ['admin/website/policy']],
            ]],
            ['key' => 'employees', 'label' => 'Employees', 'children' => [
                ['key' => 'employees.list', 'label' => 'Create / List Employees', 'paths' => ['admin/users/list']],
                ['key' => 'employees.permissions', 'label' => 'Permissions / Roles', 'paths' => ['admin/system/role']],
                ['key' => 'employees.performance', 'label' => 'Performance Report', 'paths' => ['admin/admin-reports/user-sale-report']],
            ]],
            ['key' => 'extras', 'label' => 'Extras', 'children' => [
                ['key' => 'extras.sms_api', 'label' => 'SMS API List', 'paths' => ['admin/extras/sms-api-list']],
                ['key' => 'extras.whatsapp', 'label' => 'WhatsApp API', 'paths' => ['admin/extras/whatsapp-api']],
                ['key' => 'extras.whatsapp_template', 'label' => 'WhatsApp Template List', 'paths' => ['admin/company/whatsapp-template']],
                ['key' => 'extras.send_message', 'label' => 'Send SMS / Notification', 'paths' => ['admin/users/send-message']],
                ['key' => 'extras.sms_report', 'label' => 'Send SMS Report', 'paths' => ['admin/extras/send-sms-report']],
                ['key' => 'extras.notification_report', 'label' => 'Notification Send Report', 'paths' => ['admin/extras/notification-send-report']],
                ['key' => 'extras.sms_template', 'label' => 'SMS Templates', 'paths' => ['admin/company/sms-template']],
                ['key' => 'extras.email_template', 'label' => 'Email Templates', 'paths' => ['admin/company/email-template']],
            ]],
        ];
    }

    public static function isSuperAdmin(?int $roleId = null): bool
    {
        $roleId = $roleId ?? (int) Session::get('role_id');

        return $roleId === self::SUPERADMIN_ROLE_ID;
    }

    public static function allowedKeys(?int $roleId = null): array
    {
        self::ensureTables();
        $roleId = $roleId ?? (int) Session::get('role_id');
        if ($roleId === self::SUPERADMIN_ROLE_ID) {
            return ['*'];
        }
        if ($roleId < 1 || ! Schema::hasTable('role_menus')) {
            return [];
        }
        try {
            return DB::table('role_menus')->where('role_id', $roleId)->pluck('menu_key')->map(fn ($k) => (string) $k)->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function can(string $key, ?int $roleId = null): bool
    {
        if (self::isSuperAdmin($roleId)) {
            return true;
        }
        $allowed = self::allowedKeys($roleId);
        if (in_array($key, $allowed, true)) {
            return true;
        }
        foreach ($allowed as $item) {
            if ($item !== '' && (str_starts_with($item, $key.'.') || str_starts_with($key, $item.'.'))) {
                return true;
            }
        }

        return false;
    }

    public static function canAccessPath(string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '' || $path === 'admin' || str_starts_with($path, 'admin/profile') || str_starts_with($path, 'admin/dashboard')) {
            return true;
        }

        $matchedKeys = [];
        foreach (self::flatItems() as $item) {
            foreach ($item['paths'] ?? [] as $prefix) {
                $prefix = trim($prefix, '/');
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    $matchedKeys[] = $item['key'];
                }
            }
        }
        $matchedKeys = array_unique($matchedKeys);
        if ($matchedKeys === []) {
            return true;
        }
        foreach ($matchedKeys as $key) {
            if (self::can($key)) {
                return true;
            }
        }

        return false;
    }

    public static function flatItems(): array
    {
        $out = [];
        foreach (self::catalog() as $group) {
            if (! empty($group['children'])) {
                foreach ($group['children'] as $child) {
                    $out[] = $child;
                }
            } else {
                $out[] = $group;
            }
        }

        return $out;
    }
}

if (! function_exists('admin_can')) {
    function admin_can(string $key): bool
    {
        return \App\Services\AdminMenuService::can($key);
    }
}
