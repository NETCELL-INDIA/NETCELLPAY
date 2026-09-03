<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\AdminMenuService;
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
            $query->whereIn('role_id', AdminMenuService::adminRoleIds());
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

        if (! session()->has('role_id')) {
            session()->put('role_id', (int) $user->role_id);
        } else {
            session()->put('role_id', (int) $user->role_id);
        }

        if (! AdminMenuService::canAccessPath($request->path())) {
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->json(['type' => 'error', 'message' => 'You are not allowed to access this page.'], 403);
            }
            abort(403, 'You are not allowed to access this page.');
        }

        return $next($request);
    }
}
