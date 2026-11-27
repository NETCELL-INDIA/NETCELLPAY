<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationConsentController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('notification_consent_logs')) {
            return view('admin.extras.notification-consent', [
                'logs' => null,
                'filters' => [],
            ]);
        }

        $filters = $request->only(['user_id', 'name', 'from', 'to']);
        $query = DB::table('notification_consent_logs as logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->select([
                'logs.id',
                'logs.user_id',
                'logs.consent_type',
                'logs.previous_value',
                'logs.new_value',
                'logs.action',
                'logs.source',
                'logs.created_at',
                'users.first_name',
            ]);

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $query->where('logs.user_id', $userId);
        }
        $name = trim((string) ($filters['name'] ?? ''));
        if ($name !== '') {
            $query->where('users.first_name', 'like', '%'.addcslashes($name, '%_\\').'%');
        }
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $query->where('logs.created_at', '>=', $from.' 00:00:00');
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $query->where('logs.created_at', '<=', $to.' 23:59:59');
        }

        return view('admin.extras.notification-consent', [
            'logs' => $query->orderByDesc('logs.id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
