<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ApiPartnerCheck
{
    
    public function handle(Request $post, Closure $next)
    {
        $rules = array(
            'api_key' => 'required',
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
        $user = DB::table('users')->where("api_key",$post->api_key)->whereNotIn("role_id",[1,2])->first();
        if(!$user){
            return response()->json(array(
                'type' => 'error',  
                'message' => "invaild api key"
            )); 
        }

        if (!self::isIpAllowed((string) $user->ip_address, (string) \helpers::getIp())) {
            return response()->json(array(
                'type' => 'error',  
                'message' => "invaild ip address your ip is ".\helpers::getIp(),
            )); 
        }
        // //return "shiba";
        return $next($post);
    }

    protected static function isIpAllowed(string $configuredIp, string $clientIp): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        $configuredIp = trim($configuredIp);
        if ($configuredIp === '' || in_array($configuredIp, ['0.0.0.0', '1.1.1.1', '*'], true)) {
            return true;
        }

        $allowed = array_filter(array_map('trim', explode(',', $configuredIp)));
        return in_array($clientIp, $allowed, true);
    }
}
