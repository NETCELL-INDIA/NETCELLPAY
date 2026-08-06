<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AppUserCheck
{
    
    public function handle(Request $post, Closure $next)
    {
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
