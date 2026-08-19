<?php

namespace App\Http\Controllers\Admin;

use App\Common;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginHistoryReportController extends Controller
{
    public function index(Request $request)
    {
        Common::ensureLoginHistoriesTable();

        $mobile = trim((string) $request->get('mobile', ''));
        $fromDate = $request->get('from_date') ?: Carbon::today()->format('Y-m-d');
        $toDate = $request->get('to_date') ?: Carbon::today()->format('Y-m-d');

        $query = DB::table('login_histories as lh')
            ->leftJoin('users as u', 'u.id', '=', 'lh.user_id')
            ->orderByDesc('lh.id');

        $query->whereBetween('lh.created_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);

        if ($mobile !== '') {
            $query->where('u.mobile_number', 'like', '%'.$mobile.'%');
        }

        $select = [
            'lh.id',
            'lh.ip_address',
            'lh.login_path',
            'lh.created_at',
            'u.first_name',
            'u.middle_name',
            'u.last_name',
            'u.outlet_name',
            'u.mobile_number',
        ];

        foreach (['latitude', 'longitude'] as $column) {
            if (Schema::hasColumn('login_histories', $column)) {
                $select[] = 'lh.'.$column;
            }
        }

        $logs = $query->select($select)->paginate(25)->withQueryString();

        $logs->getCollection()->transform(function ($row) {
            $name = trim(implode(' ', array_filter([
                $row->first_name ?? '',
                $row->middle_name ?? '',
                $row->last_name ?? '',
            ])));
            if ($name === '') {
                $name = $row->outlet_name ?: ('User #'.($row->id ?? ''));
            }

            $lat = $row->latitude ?? null;
            $lng = $row->longitude ?? null;

            $row->display_name = $name;
            $row->maps_url = Common::googleMapsUrl($lat, $lng);
            $row->login_by = strtoupper((string) ($row->login_path ?: 'WEB'));
            $row->display_time = $row->created_at
                ? Carbon::parse($row->created_at)->format('d-m-Y h:i:s A')
                : '-';

            return $row;
        });

        return view('admin.users.login-history', compact('logs', 'mobile', 'fromDate', 'toDate'));
    }
}
