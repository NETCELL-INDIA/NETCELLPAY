<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserCheck
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
        $user = DB::table('users')
            ->where('id', session('user_id'))
            ->where('login_key', session('login_key'))
            ->whereNotIn('role_id', [1, 2])
            ->first();

        if (!$user) {
            $request->session()->forget(['user_id', 'login_key', 'role_id']);

            return redirect('users/login');
        }

        return $next($request);
    }
}
