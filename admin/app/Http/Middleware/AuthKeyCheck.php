<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AuthKeyCheck
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
        $rules = array(
            'mobile'  => 'required|numeric|digits:10',
            'auth_key'  => 'required',
        );

        $validator = \Validator::make($request->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'error' => 1,  
                'error_msg' => $error
            ));
        }
        

        $user = DB::table('users')->where("mobile_number",$request->mobile)->where("login_key",$request->auth_key)->where('role_id','!=',1)->first();
        if($user){
            if ($user->login_key == $request->auth_key) {
                return $next($request,$user);
            }else{
                $errors = [
                    'error' => 1,
                    'error_msg' => "auth key do not match"
                ];
            }
        }else{
            $errors = [
                'error' => 1,
                'error_msg' => "user details not found"
            ];
        }
        return $next($request,$user);
        
    }
}
