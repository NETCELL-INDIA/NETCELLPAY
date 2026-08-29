<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

class DashboardController extends Controller
{
    public function Dashboard(Request $post)
    {
        $slider_list = collect();
        try {
            $slider_list = DB::table('sliders')
                ->where('status', 1)
                ->where('deleted_at', 0)
                ->where('user_id', 1)
                ->get();
        } catch (\Throwable $e) {
            // incomplete local DB
        }

        return view('admin.dashboard', ['slider_list' => $slider_list]);
    }

    public function adminLoadWallet(Request $post)
    {
        $rules = array(
            'remark'  => 'required',
            'amount' => 'required|numeric',
            't_pin' => 'required|digits:4',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',
                'message' => $error
            ));
        }
        $userId = (int) Session::get('user_id');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'type' => 'error',
                'message' => 'User not found.',
            ]);
        }
        if (! verify_user_pin($user->t_pin ?? '', $post->t_pin)) {
            return response()->json([
                'type' => 'error',
                'message' => 'Invalid PIN.',
            ]);
        }

        try {
            DB::beginTransaction();
            $order_id = "FND" . rand(11111111111, 9999999999);
            $user = DB::table('users')->where('id', $userId)->first();
            $newBalance = (float) $user->wallet_balance + (float) $post->amount;
            DB::table('reports')->insert([
                'user_id' => $user->id,
                'credit_user_id' => $user->id,
                'debit_user_id' => 0,
                'amount' => $post->amount,
                'total_amount' => $post->amount,
                'fund_type' => "Credit",
                'transaction_type' => "Self Money",
                'remark' => $post->remark,
                'order_id' => $order_id,
                'status' => "Success",
                'opening_balance' => $user->wallet_balance,
                'closing_balance' => $newBalance,
                'transaction_date' => Carbon::now() . ":" . rand(111, 999),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $newBalance]);
            DB::commit();
            return response()->json(array(
                'type' => 'success',
                'message' => "Wallet loaded successfully.",
                'wallet_balance' => $newBalance,
            ));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(array(
                'type' => 'error',
                'message' => "Something Went Wrong."
            ));
        }
    }

    public function topbarCount()
    {
        try {
            $payload = \Illuminate\Support\Facades\Cache::remember('admin_topbar_counts', 20, function () {
                $pending = DB::table('reports')
                    ->where('transaction_type', 'Recharge')
                    ->where('status', 'Pending')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->count();
                $complaint = $this->countPendingComplaints();
                return [
                    'pending' => $pending,
                    'complaint' => $complaint
                ];
            });
            return response()->json([
                'type' => 'success',
                'message' => "Fetch Successfully",
                'data' => $payload
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => "Something Went Wrong."
            ]);
        }
    }

    public function dashboardReportsList(Request $post)
    {
        if ($post->from_date) {
            $from_date = $post->from_date . " 00:00:00";
            $to_date = $post->to_date . " 23:59:59";
        } else {
            $from_date = Carbon::today()->format('Y-m-d') . " 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d') . " 23:59:59";
        }

        try {
            $cacheKey = 'admin_dashboard_'.md5($from_date.'|'.$to_date);
            $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, 15, function () use ($from_date, $to_date) {
                return [
                    'pending' => $this->pendingSummary(),
                    'today_by_service' => $this->todayByService($from_date, $to_date),
                    'balances' => $this->balanceStatistics(),
                    'accounts' => $this->accountStatistics(),
                    'recharges' => $this->rechargeStatistics($from_date, $to_date),
                    'top_operators' => $this->topOperators($from_date, $to_date),
                    'top_retailers' => $this->topRetailers($from_date, $to_date),
                    'top_api_users' => $this->topApiUsers($from_date, $to_date),
                    'announcement' => $this->announcementMessage(),
                ];
            });
            return response()->json(array_merge(['type' => 'success'], $payload));
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to load dashboard data',
                'pending' => $this->emptyPending(),
                'today_by_service' => $this->emptyServiceRows(),
                'balances' => $this->emptyRoleMoneyRows(),
                'accounts' => $this->emptyRoleCountRows(),
                'recharges' => $this->emptyRoleMoneyRows(true),
                'top_operators' => [],
                'top_retailers' => [],
                'top_api_users' => [],
                'announcement' => '',
            ]);
        }
    }

    private function countPendingComplaints(): int
    {
        return (int) DB::table('complaints as c')
            ->join('reports as r', 'r.id', '=', 'c.report_id')
            ->whereIn('c.status', ['Open', 'Under Review', 'Pending'])
            ->whereColumn('r.complaint_id', 'c.id')
            ->whereNotIn('r.status', ['Success', 'Refunded', 'Refund'])
            ->count('c.id');
    }

    private function pendingSummary(): array
    {
        $pendingRecharges = 0;
        $pendingComplaints = 0;
        $pendingFund = 0;
        $pendingUpi = 0;
        $pendingRefunds = 0;
        $kycPending = 0;

        try {
            $pendingRecharges = DB::table('reports')
                ->where('transaction_type', 'Recharge')
                ->where('status', 'Pending')
                ->count();
        } catch (\Throwable $e) {
        }

        try {
            $pendingComplaints = $this->countPendingComplaints();
        } catch (\Throwable $e) {
        }

        try {
            $pendingFund = DB::table('fund_requests')
                ->where('status', 'Pending')
                ->where('upi', 0)
                ->count();
            $pendingUpi = DB::table('fund_requests')
                ->where('status', 'Pending')
                ->whereIn('upi', [1, 2])
                ->count();
        } catch (\Throwable $e) {
        }

        try {
            $pendingRefunds = DB::table('reports')
                ->where('transaction_type', 'Refund')
                ->where('status', 'Pending')
                ->count();
        } catch (\Throwable $e) {
        }

        try {
            $q = DB::table('users')->where('deleted_at', '!=', 1);
            if (Schema::hasColumn('users', 'kyc_status')) {
                $kycPending = (clone $q)->where(function ($w) {
                    $w->whereNull('kyc_status')
                        ->orWhereIn('kyc_status', ['0', 'Pending', 'pending', 'Not Verified', 'not_verified']);
                })->whereNotIn('role_id', [1])->count();
            }
        } catch (\Throwable $e) {
        }

        return [
            'recharges' => $pendingRecharges,
            'complaints' => $pendingComplaints,
            'fund' => $pendingFund,
            'upi' => $pendingUpi,
            'refunds' => $pendingRefunds,
            'kyc' => $kycPending,
        ];
    }

    private function emptyPending(): array
    {
        return [
            'recharges' => 0,
            'complaints' => 0,
            'fund' => 0,
            'upi' => 0,
            'refunds' => 0,
            'kyc' => 0,
        ];
    }

    private function serviceCatalog(): array
    {
        return [
            ['key' => 'mobile', 'label' => 'Mobile', 'match' => ['mobile', 'prepaid']],
            ['key' => 'dth', 'label' => 'DTH', 'match' => ['dth']],
            ['key' => 'postpaid', 'label' => 'Postpaid', 'match' => ['postpaid']],
            ['key' => 'bill', 'label' => 'Bill Payment', 'match' => ['bill', 'bbps', 'electricity']],
            ['key' => 'dmt', 'label' => 'Money Transfer (DMT)', 'match' => ['money transfer', 'dmt']],
            ['key' => 'online', 'label' => 'Online Payments', 'match' => ['upi', 'add money', 'online']],
            ['key' => 'pancard', 'label' => 'Pancard', 'match' => ['pan']],
        ];
    }

    private function emptyServiceRows(): array
    {
        $rows = [];
        foreach ($this->serviceCatalog() as $svc) {
            $rows[] = [
                'service' => $svc['label'],
                'success_amount' => 0,
                'success_count' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'failed_amount' => 0,
                'failed_count' => 0,
                'total_amount' => 0,
                'total_count' => 0,
            ];
        }
        return $rows;
    }

    private function todayByService(string $from_date, string $to_date): array
    {
        $rows = $this->emptyServiceRows();
        $index = [];
        foreach ($rows as $i => $row) {
            $index[strtolower($row['service'])] = $i;
        }

        try {
            $hasProvider = Schema::hasTable('providers') && Schema::hasTable('services');
            if ($hasProvider) {
                $stats = DB::table('reports as r')
                    ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
                    ->leftJoin('services as s', 's.id', '=', 'p.service_id')
                    ->whereBetween('r.created_at', [$from_date, $to_date])
                    ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment', 'Money Transfer', 'Upi Add Money'])
                    ->select(
                        's.service_name',
                        'r.transaction_type',
                        'r.status',
                        DB::raw('COUNT(r.id) as cnt'),
                        DB::raw('COALESCE(SUM(r.total_amount),0) as amt')
                    )
                    ->groupBy('s.service_name', 'r.transaction_type', 'r.status')
                    ->get();
            } else {
                $stats = DB::table('reports')
                    ->whereBetween('created_at', [$from_date, $to_date])
                    ->whereIn('transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment', 'Money Transfer', 'Upi Add Money'])
                    ->select(
                        DB::raw('NULL as service_name'),
                        'transaction_type',
                        'status',
                        DB::raw('COUNT(id) as cnt'),
                        DB::raw('COALESCE(SUM(total_amount),0) as amt')
                    )
                    ->groupBy('transaction_type', 'status')
                    ->get();
            }

            foreach ($stats as $stat) {
                $bucket = $this->mapStatToService($stat->service_name, $stat->transaction_type);
                if ($bucket === null || !isset($index[strtolower($bucket)])) {
                    continue;
                }
                $i = $index[strtolower($bucket)];
                $status = strtolower((string) $stat->status);
                $amt = (float) $stat->amt;
                $cnt = (int) $stat->cnt;
                if ($status === 'success') {
                    $rows[$i]['success_amount'] += $amt;
                    $rows[$i]['success_count'] += $cnt;
                } elseif ($status === 'pending') {
                    $rows[$i]['pending_amount'] += $amt;
                    $rows[$i]['pending_count'] += $cnt;
                } elseif (in_array($status, ['failed', 'failure', 'fail'], true)) {
                    $rows[$i]['failed_amount'] += $amt;
                    $rows[$i]['failed_count'] += $cnt;
                }
                $rows[$i]['total_amount'] += $amt;
                $rows[$i]['total_count'] += $cnt;
            }
        } catch (\Throwable $e) {
            return $this->emptyServiceRows();
        }

        return $rows;
    }

    private function mapStatToService(?string $serviceName, ?string $transactionType): ?string
    {
        $hay = strtolower(trim(($serviceName ?? '') . ' ' . ($transactionType ?? '')));
        foreach ($this->serviceCatalog() as $svc) {
            foreach ($svc['match'] as $needle) {
                if ($needle !== '' && str_contains($hay, $needle)) {
                    return $svc['label'];
                }
            }
        }
        if (str_contains($hay, 'recharge')) {
            return 'Mobile';
        }
        return null;
    }

    private function roleLabels(): array
    {
        return [
            3 => 'API',
            4 => 'Master Distributor',
            5 => 'Distributor',
            6 => 'Retailer',
        ];
    }

    private function activeUsersQuery()
    {
        $q = DB::table('users');
        if (Schema::hasColumn('users', 'deleted_at')) {
            $q->where(function ($w) {
                $w->whereNull('deleted_at')
                    ->orWhere('deleted_at', 0)
                    ->orWhere('deleted_at', '!=', 1);
            });
        }
        if (Schema::hasColumn('users', 'status')) {
            $q->where('status', 1);
        }
        return $q;
    }

    private function emptyRoleMoneyRows(bool $withMargin = false): array
    {
        $rows = [];
        foreach ($this->roleLabels() as $label) {
            $row = [
                'usertype' => $label,
                'recharge' => 0,
                'utility' => 0,
                'aeps' => 0,
                'total' => 0,
            ];
            if ($withMargin) {
                $row = [
                    'usertype' => $label,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $rows[] = $row;
        }
        if ($withMargin) {
            $rows[] = ['usertype' => 'Total', 'amount' => 0, 'count' => 0, 'is_total' => true];
            $rows[] = ['usertype' => 'Margin Earned', 'amount' => 0, 'count' => 0, 'is_admin' => true];
        } else {
            $rows[] = [
                'usertype' => 'Total',
                'recharge' => 0,
                'utility' => 0,
                'aeps' => 0,
                'total' => 0,
                'is_total' => true,
            ];
            $rows[] = [
                'usertype' => 'Admin',
                'recharge' => 0,
                'utility' => 0,
                'aeps' => 0,
                'total' => 0,
                'is_admin' => true,
            ];
        }
        return $rows;
    }

    private function emptyRoleCountRows(): array
    {
        $rows = [];
        foreach ($this->roleLabels() as $label) {
            $rows[] = ['usertype' => $label, 'users' => 0];
        }
        $rows[] = ['usertype' => 'Total', 'users' => 0, 'is_total' => true];
        return $rows;
    }

    private function balanceStatistics(): array
    {
        $rows = [];
        $sumRecharge = 0.0;
        $sumUtility = 0.0;
        $sumAeps = 0.0;
        $sumTotal = 0.0;

        foreach ($this->roleLabels() as $roleId => $label) {
            $wallet = 0.0;
            try {
                $wallet = (float) $this->activeUsersQuery()
                    ->where('role_id', $roleId)
                    ->sum('wallet_balance');
            } catch (\Throwable $e) {
                $wallet = 0.0;
            }
            $rows[] = [
                'usertype' => $label,
                'recharge' => round($wallet, 2),
                'utility' => 0,
                'aeps' => 0,
                'total' => round($wallet, 2),
            ];
            $sumRecharge += $wallet;
            $sumTotal += $wallet;
        }

        $adminWallet = 0.0;
        try {
            $adminWallet = (float) DB::table('users')
                ->where('role_id', 1)
                ->orderBy('id')
                ->value('wallet_balance');
        } catch (\Throwable $e) {
            $adminWallet = 0.0;
        }

        $rows[] = [
            'usertype' => 'Total',
            'recharge' => round($sumRecharge, 2),
            'utility' => round($sumUtility, 2),
            'aeps' => round($sumAeps, 2),
            'total' => round($sumTotal, 2),
            'is_total' => true,
        ];
        $rows[] = [
            'usertype' => 'Admin',
            'recharge' => round($adminWallet, 2),
            'utility' => 0,
            'aeps' => 0,
            'total' => round($adminWallet, 2),
            'is_admin' => true,
        ];

        return $rows;
    }

    private function accountStatistics(): array
    {
        $rows = [];
        $total = 0;
        foreach ($this->roleLabels() as $roleId => $label) {
            $count = 0;
            try {
                $count = (int) $this->activeUsersQuery()
                    ->where('role_id', $roleId)
                    ->count();
            } catch (\Throwable $e) {
                $count = 0;
            }
            $rows[] = ['usertype' => $label, 'users' => $count];
            $total += $count;
        }
        $rows[] = ['usertype' => 'Total', 'users' => $total, 'is_total' => true];
        return $rows;
    }

    private function rechargeStatistics(string $from_date, string $to_date): array
    {
        $rows = [];
        $sumAmount = 0.0;
        $sumCount = 0;
        $margin = 0.0;
        $hasCommission = Schema::hasColumn('reports', 'commission');

        foreach ($this->roleLabels() as $roleId => $label) {
            $amount = 0.0;
            $count = 0;
            $roleMargin = 0.0;
            try {
                $base = DB::table('reports as r')
                    ->join('users as u', 'u.id', '=', 'r.user_id')
                    ->whereBetween('r.created_at', [$from_date, $to_date])
                    ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                    ->where('r.status', 'Success')
                    ->where('u.role_id', $roleId);

                $amount = (float) (clone $base)->sum('r.total_amount');
                $count = (int) (clone $base)->count('r.id');
                if ($hasCommission) {
                    $roleMargin = (float) (clone $base)->sum('r.commission');
                }
            } catch (\Throwable $e) {
                $amount = 0.0;
                $count = 0;
                $roleMargin = 0.0;
            }
            $rows[] = [
                'usertype' => $label,
                'amount' => round($amount, 2),
                'count' => $count,
            ];
            $sumAmount += $amount;
            $sumCount += $count;
            $margin += $roleMargin;
        }

        $rows[] = [
            'usertype' => 'Total',
            'amount' => round($sumAmount, 2),
            'count' => $sumCount,
            'is_total' => true,
        ];
        $rows[] = [
            'usertype' => 'Margin Earned',
            'amount' => round($margin, 2),
            'count' => 0,
            'is_admin' => true,
        ];

        return $rows;
    }

    private function topOperators(string $from_date, string $to_date): array
    {
        try {
            $hasProviders = Schema::hasTable('providers');
            $q = DB::table('reports as r')
                ->whereBetween('r.created_at', [$from_date, $to_date])
                ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                ->where('r.status', 'Success');

            if ($hasProviders) {
                $q->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
                    ->select(
                        DB::raw("COALESCE(NULLIF(p.provider_name,''), CONCAT('Operator #', COALESCE(r.provider_id,0)), 'Unknown Operator') as name"),
                        DB::raw('COUNT(r.id) as txns'),
                        DB::raw('COALESCE(SUM(r.total_amount),0) as mrp')
                    )
                    ->groupBy(DB::raw("COALESCE(NULLIF(p.provider_name,''), CONCAT('Operator #', COALESCE(r.provider_id,0)), 'Unknown Operator')"));
            } else {
                $q->select(
                    DB::raw("COALESCE(CONCAT('Operator #', r.provider_id), 'Unknown Operator') as name"),
                    DB::raw('COUNT(r.id) as txns'),
                    DB::raw('COALESCE(SUM(r.total_amount),0) as mrp')
                )
                    ->groupBy('r.provider_id');
            }

            return $q->orderByDesc('mrp')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return [
                        'name' => $row->name ?: 'Unknown Operator',
                        'txns' => (int) $row->txns,
                        'mrp' => (float) $row->mrp,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function topRetailers(string $from_date, string $to_date): array
    {
        return $this->topUsersByRole($from_date, $to_date, 6);
    }

    private function topApiUsers(string $from_date, string $to_date): array
    {
        return $this->topUsersByRole($from_date, $to_date, 3);
    }

    private function topUsersByRole(string $from_date, string $to_date, int $roleId): array
    {
        try {
            $rows = DB::table('reports as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->whereBetween('r.created_at', [$from_date, $to_date])
                ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                ->where('r.status', 'Success')
                ->where('u.role_id', $roleId)
                ->select(
                    'u.id as user_id',
                    'u.outlet_name',
                    'u.first_name',
                    'u.mobile_number',
                    DB::raw('COUNT(r.id) as txns'),
                    DB::raw('COALESCE(SUM(r.total_amount),0) as mrp')
                )
                ->groupBy('u.id', 'u.outlet_name', 'u.first_name', 'u.mobile_number')
                ->orderByDesc('mrp')
                ->limit(5)
                ->get();

            return $rows->map(function ($row) {
                $name = $row->outlet_name ?: ($row->first_name ?: ($row->mobile_number ?: ('User #' . $row->user_id)));
                return [
                    'name' => $name,
                    'txns' => (int) $row->txns,
                    'mrp' => (float) $row->mrp,
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function announcementMessage(): string
    {
        try {
            $row = DB::table('announcements')->where('id', 1)->first(['message']);
            return $row->message ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
