<?php

namespace App\Http\Middleware;

use App\Services\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AppUserCheck
{
    private const GUEST_PATHS = [
        'api/v1/services-list',
        'api/v1/service-providers',
        'api/v1/check-number',
        'api/v1/check-roffer',
        'api/v1/check-view-plan',
        'api/v1/dth-info',
        'api/v1/dth-plans',
        'api/v1/bill-params',
        'api/v1/bill-fetch',
    ];

    public function handle(Request $post, Closure $next)
    {
        $isGuestPath = in_array(trim($post->path(), '/'), self::GUEST_PATHS, true);
        $hasLogin = $post->filled('login_key') || $post->filled('user_id');

        if (SystemSettingService::isOn('app_without_login') && $isGuestPath && !$hasLogin) {
            return $next($post);
        }

        $rules = array(
            'login_key' => 'required',
            'user_id' => 'required',
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
        $user = DB::table('users')->where("login_key",$post->login_key)->where("id",$post->user_id)->whereNotIn("role_id",[1,2])->first();
        if(!$user){
            return response()->json(array(
                'type' => 'error',  
                'message' => "invaild login details"
            )); 
        }
        // //return "shiba";
        return $next($post);
    }
}
