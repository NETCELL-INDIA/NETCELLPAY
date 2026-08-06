<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LoginHistoryController extends Controller
{
    public function index()
    {
        $logs = DB::table('login_histories')
            ->join('users', 'users.id', '=', 'login_histories.user_id')
            ->select(
                'users.first_name',
                'users.last_name',
                'users.mobile_number',
                'login_histories.ip_address',
                'login_histories.login_path',
                'login_histories.created_at'
            )
            ->orderBy('login_histories.id', 'DESC')
            ->paginate(50);

        return view('users.reports.login-history', compact('logs'));
    }
}