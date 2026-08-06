<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class AdminCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = session('user_id');
        if (!$userId) {
            return redirect()->route('loginPage');
        }

        $query = DB::table('users')->where('id', $userId);
        if (Schema::hasColumn('users', 'role_id')) {
            $query->where('role_id', 1);
        }
        $user = $query->first();

        if (!$user || (int) ($user->status ?? 0) !== 1) {
            $request->session()->flush();
            return redirect()->route('loginPage');
        }

        $sessionLoginKey = session('login_key');
        if (
            Schema::hasColumn('users', 'login_key')
            && !empty($user->login_key)
            && (string) $sessionLoginKey !== (string) $user->login_key
        ) {
            $request->session()->flush();
            return redirect()->route('loginPage');
        }

        return $next($request);
    }
}
