<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
class AuthController extends Controller
{


    public function add_user_by_arrray(Request $post)
    {
    }

    public function Login()
    {
        if(session('login_key')!=""){
            return redirect('users/dashboard');
        }
        //return session('login_key');
        // dd("shiba");
        $data['name'] = "shiba"; 
        return view('users.auth.login', $data);
    }

    public function LoginCheck(Request $post)
    {

        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
            'password' => 'required|string|min:8',
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->whereNotIn("role_id",[1,2])->first();
        if($user){
            if(\helpers::verifyUserPassword($post->password, $user)){

                if($user->status==1){
                    if(strtoupper((string) $user->login_type) === 'OTP'){
                        if($user->otp_limit == 5){
                            $time_diff = strtotime(Carbon::now()) - strtotime($user->otp_created_at);
                            $time_diff_min = $time_diff / 60;
                            if($time_diff_min >= 10){
                                $otp_limit = 0;
                                DB::table('users')->where("id",$user->id)->update(['otp_limit'=>$otp_limit]);
                            }
                        }
                        if($user->otp_limit!=5){
                            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            $otpHash = Hash::make($otp);
                            $data['type'] = 'otp_verify';
                            $data['message'] = 'OTP sent to email and mobile number successfully.';
                            if (app()->environment('local')) {
                                $data['local_otp'] = $otp;
                                $data['message'] = 'Local dev OTP generated. Use the code shown on screen.';
                            }
                            $update = DB::table('users')->where("id",$user->id)->update([
                                'otp' => $otpHash,
                                'email_otp' => $otpHash,
                                'otp_limit' => $user->otp_limit + 1,
                                'otp_created_at' => Carbon::now(),
                            ]);
                            try {
                                ////Send Whatsapp Message Start
                                $slug = 'otp';
                                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                                if ($sms_tmp) {
                                    $content = $sms_tmp->content;
                                    $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
                                    $content = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content);
                                    $content = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content);
                                    $content = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content);
                                    $content = str_replace('{OTP}', $otp, $content);
                                    if($sms_tmp->status == 1){
                                        $msg_data = [
                                            'mobile_number' => $user->mobile_number,
                                            'content' => $content,
                                            'template_id' => $sms_tmp->template_id,
                                        ];
                                        \helpers::sendWhatasappMsg($msg_data);
                                    }
                                }
                                ////Send Whatsapp Message End
                                ////Send Email Start
                                $company = DB::table('companies')->where('status', "1")->where('domain', request()->getHost())->first();
                                $company = $company ?: DB::table('companies')->where('status', "1")->first();
                                if($company && $company->email_message == 1){
                                    $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                                    if ($email_tmp) {
                                        $content_email = $email_tmp->content;
                                        $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                                        $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                                        $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                                        $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                                        $content_email = str_replace('{OTP}', $otp, $content_email);
                                        Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                                    }
                                }
                                ////Send Email End
                            } catch (\Throwable $e) {
                                // OTP is already saved; delivery failures should not block login.
                            }
                        }else{
                            $data['type'] = 'error';
                            $data['message'] = "otp limit exhausted login after 10 minutes.";
                            
                        }
                    }else{
                        $data['type'] = 'success';
                        $data['message'] = "Login Sucessfuly";
                        ////
                        $random = Str::random(40);
                        $update = DB::table('users')->where("id",$user->id)->update(['login_key'=>$random]);
                        ///
                        $user = DB::table('users')->where("id",$user->id)->first();
                        $post->session()->put('user_id', $user->id);
                        $post->session()->put('login_key', $user->login_key);
                        $post->session()->put('role_id', $user->role_id);
                        DB::table('login_histories')->insert([
                            'user_id' => $user->id,
                            'ip_address' => request()->ip(),
                            'login_path' => "Web",
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }
                    //$post->session()->flush();
                    //return  session('login_key');
                }else{
                    $data['type'] = 'error';
                    $data['message'] = "Account not active contact service provider.";
                }
                
            }else{
                $data['type'] = 'error';
                $data['message'] = "password do not match";
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Invalid mobile number";
        }
        return response()->json($data);
    }


    public function checkLoginOtp(Request $post)
    {

        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
            'password' => 'required|string|min:8',
            'otp' => 'required|numeric|digits:6',
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->whereNotIn("role_id",[1,2])->first();
        if($user){
            if(\helpers::verifyUserPassword($post->password, $user)){

                if($user->status==1){
                    if($this->verifyUserLoginOtp($post->otp, $user)){
                        $data['type'] = 'success';
                        $data['message'] = "Login Sucessfuly";
                        ////
                        $random = Str::random(40);
                        $update = DB::table('users')->where("id",$user->id)->update([
                            'login_key' => $random,
                            'otp' => null,
                            'email_otp' => null,
                            'otp_limit' => 0,
                        ]);
                        ///
                        $user = DB::table('users')->where("id",$user->id)->first();
                        $post->session()->put('user_id', $user->id);
                        $post->session()->put('login_key', $user->login_key);
                        $post->session()->put('role_id', $user->role_id);
                        DB::table('login_histories')->insert([
                            'user_id' => $user->id,
                            'ip_address' => request()->ip(),
                            'login_path' => "Web",
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }else{
                        $data['type'] = 'error';
                        $data['message'] = "Wrong otp.";
                    }
                    
                    //$post->session()->flush();
                    //return  session('login_key');
                }else{
                    $data['type'] = 'error';
                    $data['message'] = "Account not active contact service provider.";
                }
                
            }else{
                $data['type'] = 'error';
                $data['message'] = "password do not match";
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Invalid mobile number";
        }
        return response()->json($data);
    }


    public function forgotPassword()
    {
        return view('users.auth.forgot-password');
    }


    public function sendOtpForgotPassword(Request $post)
    {

        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->whereNotIn("role_id",[1,2])->first();
        if($user){
            if($user->status==1){
                if($user->otp_limit == 5){
                    $time_diff = strtotime(Carbon::now()) - strtotime($user->otp_created_at);
                    $time_diff_min = $time_diff / 60;
                    if($time_diff_min >= 10){
                        $otp_limit = 0;
                        DB::table('users')->where("id",$user->id)->update(['otp_limit'=>$otp_limit]);
                    }
                }
                if($user->otp_limit!=5){
                    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otpHash = Hash::make($otp);
                    $update = DB::table('users')->where("id",$user->id)->update([
                        'otp' => $otpHash,
                        'email_otp' => $otpHash,
                        'otp_limit' => $user->otp_limit + 1,
                        'otp_created_at' => Carbon::now(),
                    ]);
                    $data['type'] = 'otp_verify';
                    $data['message'] = 'OTP sent to email and mobile number successfully.';
                    try {
                        ////Send Whatsapp Message Start
                        $slug = 'otp';
                        $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                        if ($sms_tmp) {
                            $content = $sms_tmp->content;
                            $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
                            $content = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content);
                            $content = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content);
                            $content = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content);
                            $content = str_replace('{OTP}', $otp, $content);
                            if($sms_tmp->status == 1){
                                $msg_data = [
                                    'mobile_number' => $user->mobile_number,
                                    'content' => $content,
                                    'template_id' => $sms_tmp->template_id,
                                ];
                                \helpers::sendWhatasappMsg($msg_data);
                            }
                        }
                        //Send Whatsapp Message End
                        ////Send Email Start
                        $company = DB::table('companies')->where('status', "1")->where('domain', request()->getHost())->first();
                        $company = $company ?: DB::table('companies')->where('status', "1")->first();
                        if($company && $company->email_message == 1){
                            $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                            if ($email_tmp) {
                                $content_email = $email_tmp->content;
                                $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                                $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                                $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                                $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                                $content_email = str_replace('{OTP}', $otp, $content_email);
                                Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                            }
                        }
                        ////Send Email End
                    } catch (\Throwable $e) {
                        // OTP is already saved; delivery failures should not block recovery.
                    }
                }else{
                    $data['type'] = 'error';
                    $data['message'] = "otp limit exhausted login after 10 minutes.";
                }
            }else{
                $data['type'] = 'error';
                $data['message'] = "Account not active contact service provider.";
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Invalid mobile number";
        }
        return $data;
    }


    public function verifyOtpForgotPassword(Request $post)
    {

        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
            'email_otp' => 'required|numeric|digits:6',
            'mobile_otp' => 'required|numeric|digits:6',
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->whereNotIn("role_id",[1,2])->first();
        if($user){
            if($user->status==1){
                if($this->verifyUserDualOtp($post->mobile_otp, $post->email_otp, $user)){
                    ////
                    $random = Str::random(40);
                    $pass_g = Str::random(8);
                    $pass = Hash::make($pass_g);
                    $update = DB::table('users')->where("id",$user->id)->update(['login_key'=>$random,'password'=>$pass]);
                    if($update){
                        $data['type'] = 'success';
                        $data['message'] = "New password send sucessfuly check email & mobile number.";
                        try {
                            ////Send Whatsapp Message Start
                            $slug = 'forgot_password';
                            $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                            if ($sms_tmp) {
                                $content = $sms_tmp->content;
                                $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
                                $content = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content);
                                $content = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content);
                                $content = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content);
                                $content = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content);
                                $content = str_replace('{PASSWORD}', '' . $pass_g . '', $content);
                                $content = str_replace('{PIN}', '' . $user->t_pin . '', $content);
                                if($sms_tmp->status == 1){
                                    $msg_data = [
                                        'mobile_number' => $post->mobile_number,
                                        'content' => $content,
                                        'template_id' => $sms_tmp->template_id,
                                    ];
                                    \helpers::sendWhatasappMsg($msg_data);
                                }
                            }
                            ////Send Whatsapp Message End
                            ////Send Email Start
                            $company = DB::table('companies')->where('status', "1")->where('domain', request()->getHost())->first();
                            $company = $company ?: DB::table('companies')->where('status', "1")->first();
                            if($company && $company->email_message == 1){
                                $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                                if ($email_tmp) {
                                    $content_email = $email_tmp->content;
                                    $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                                    $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                                    $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                                    $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                                    $content_email = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content_email);
                                    $content_email = str_replace('{PASSWORD}', '' . $pass_g . '', $content_email);
                                    $content_email = str_replace('{PIN}', '' . $user->t_pin . '', $content_email);
                                    Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                                }
                            }
                            ////Send Email End
                        } catch (\Throwable $e) {
                            // Password is already reset; delivery failures should not block recovery.
                        }
                    }else{
                        $data['type'] = 'error';
                        $data['message'] = "Invalid mobile number";
                    }
                    ///
                }else{
                    $data['type'] = 'error';
                    $data['message'] = "Wrong otp.";
                }
            }else{
                $data['type'] = 'error';
                $data['message'] = "Account not active contact service provider.";
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Invalid mobile number";
        }
        return $data;
    }


    public function userRegister()
    {
        $data['name'] = "shiba"; 
        return view('users.auth.register', $data);
    }

    public function sendOtpUserRegister(Request $post)
    {
        $rules = array(
            'first_name'  => 'required|string',
            'last_name'  => 'required|string',
            'mobile_number'  => 'required|numeric|digits:10|unique:users,mobile_number',
            'email_address'  => 'required|email|unique:users,email_address',
            'city_name'  => 'required|string',
            
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => "error",  
                'message' => $error
            ));
        }

        $g_otp = random_int(100000, 999999);
        $h_otp = Hash::make($g_otp);
        $token = Str::random(45).random_int(1000, 9999).random_int(100000, 999999).random_int(1000, 9999).Str::random(45);
        $register = DB::table('user_register_otp')->insert([
            'mobile_number' => $post->mobile_number,
            'email_address' => $post->email_address,
            'outlet_name' => "",
            'first_name' => $post->first_name,
            'last_name' => $post->last_name,
            'city' => $post->city_name,
            'otp' => $h_otp,
            'token' => $token,
            'ip_address' => request()->ip(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        if($register){
            $errors = [
                'type' => "otp_verify",  
                'token' => $token,
                'mobile' => $post->mobile_number,
                'message' => "OTP send sucessfuly check email & mobile number.",
            ];

            ////Send Whatsapp Message Start
            $slug = 'otp';
            $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
            $content = $sms_tmp->content;
            $content = str_replace('{NAME}', '' . $post->first_name . '', $content);
            $content = str_replace('{MIDDLE_NAME}', '' . $post->middle_name . '', $content);
            $content = str_replace('{LAST_NAME}', '' . $post->last_name . '', $content);
            $content = str_replace('{OUTLET_NAME}', '' . $post->outlet_name . '', $content);
            $content = str_replace('{OTP}', '' . $g_otp . '', $content);
            if($sms_tmp->status == 1){
                $msg_data = [
                    'mobile_number' => $post->mobile_number,
                    'content' => $content,
                    'template_id' => $sms_tmp->template_id,
                ];
                $sms = \helpers::sendWhatasappMsg($msg_data);
               
            }
            ////Send Whatsapp Message End
            ////Send Email Start
            $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
            if($company->email_message == 1){
                $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                $content_email = $email_tmp->content;
                $content_email = str_replace('{NAME}', '' . $post->first_name . '', $content_email);
                $content_email = str_replace('{MIDDLE_NAME}', '' . $post->middle_name . '', $content_email);
                $content_email = str_replace('{LAST_NAME}', '' . $post->last_name . '', $content_email);
                $content_email = str_replace('{OUTLET_NAME}', '' . $post->outlet_name . '', $content_email);
                $content_email = str_replace('{OTP}', '' . $g_otp . '', $content_email);
                Mail::to(strtolower($post->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
            }
            ////Send Email End
            return response()->json($errors);
        }else{
            return response()->json(array(
                'type' => "error",  
                'message' => "something went wrong."
            ));
        }

    }

    public function verifyOtpUserRegister(Request $post)
    {
        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
            'otp'  => 'required|numeric|digits:6',
            'token'  => 'required',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => "error",  
                'message' => $error
            ));
        }

        $user_data = DB::table('user_register_otp')->where('mobile_number', $post->mobile_number)->where('token', $post->token)->orderBy('id', 'DESC')->first();
        if($user_data){
            if (Hash::check($post->otp, $user_data->otp)) {
                try {
                    $g_pass = Str::random(8);
                    $password = Hash::make($g_pass);
                    $t_pin = \helpers::normalizeUserPin(random_int(0, 9999));
                    $update = DB::table('users')->insertGetId([
                        'parent_id'  => 1,
                        'role_id'  => 6,
                        'scheme_id'  => 1,
                        'outlet_name'  => $user_data->outlet_name,
                        'first_name'  => $user_data->first_name,
                        'middle_name'  => "",
                        'last_name'  => $user_data->last_name,
                        'date_of_birth'  => "",
                        'mobile_number' => $user_data->mobile_number,
                        'email_address' => $user_data->email_address,
                        'password' => $password,
                        't_pin' => $t_pin,
                        'login_type'  => "OTP",
                        'gender'  => "Male",
                        'flat_door_no'  => "",
                        'road_street'  => "",
                        'area_locality'  => "",
                        'city'  => $user_data->city,
                        'state'  => "",
                        'register_by'  => "Website",
                        'district'  => "",
                        'minium_balance'  => 0,
                        'kyc_status'  => "Pending",
                        'bank_account_number'  => "",
                        'branch_name'  => "",
                        'ifsc_code'  => "",
                        'bank_account_type'  => "Savings",
                        'ip_address'  => '',
                        'callback_url'  => '',
                        'profile_pic' => "avatar-2.png",
                        'status' => 1,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                    $user = DB::table('users')->where("id",$update)->first();

                    ////Send Whatsapp Message Start
                    $slug = 'create_user';
                    $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                    $content = $sms_tmp->content;
                    $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                    $content = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content);
                    $content = str_replace('{PASSWORD}', '' . $g_pass . '', $content);
                    $content = str_replace('{PIN}', '' . $t_pin . '', $content);
                    if($sms_tmp->status == 1){
                        $msg_data = [
                            'mobile_number' => $post->mobile_number,
                            'content' => $content,
                            'template_id' => $sms_tmp->template_id,
                        ];
                        $sms = \helpers::sendWhatasappMsg($msg_data);
                        
                    }
                    ////Send Whatsapp Message End
                    ////Send Email Start
                    $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                    if($company->email_message == 1){
                        $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                        $content_email = $email_tmp->content;
                        $content_email = str_replace('{NAME}', '' . $user_data->first_name . '', $content_email);
                        $content_email = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content_email);
                        $content_email = str_replace('{PASSWORD}', '' . $g_pass . '', $content_email);
                        $content_email = str_replace('{PIN}', '' . $t_pin . '', $content_email);
                        Mail::to(strtolower($user_data->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                    }
                    ////Send Email End
                    return response()->json(array(
                        'type' => "success",  
                        'message' => "Register sucessfuly.Login Details send email,whatsapp & sms."
                    ));
                } catch (\Exception $e) {
                   //return $e->getMessage();
                    return response()->json(array(
                        'type' => "error",  
                        'message' => "something went wrong."
                    ));
                }
            }else{
                return response()->json(array(
                    'type' => "error",  
                    'message' => "otp do not match."
                ));
            }
        }else{
            return response()->json(array(
                'type' => "error",  
                'message' => "something went wrong."
            ));
        }
    }

    public function Logout(Request $post)
    {
        $post->session()->flush();
        return redirect('users/login');
    }

    private function verifyUserLoginOtp(string $enteredOtp, object $user): bool
    {
        $otp = trim($enteredOtp);
        if ($otp === '' || empty($user->otp)) {
            return false;
        }

        if (!empty($user->otp_created_at)) {
            $minutes = (Carbon::now()->timestamp - strtotime((string) $user->otp_created_at)) / 60;
            if ($minutes >= 10) {
                return false;
            }
        }

        if (Hash::check($otp, $user->otp)) {
            return true;
        }

        return strlen((string) $user->otp) === 6
            && ctype_digit((string) $user->otp)
            && hash_equals((string) $user->otp, $otp);
    }

    private function verifyUserDualOtp(string $mobileOtp, string $emailOtp, object $user): bool
    {
        $mobileOtp = trim($mobileOtp);
        $emailOtp = trim($emailOtp);

        if ($mobileOtp === '' || $emailOtp === '' || $mobileOtp !== $emailOtp) {
            return false;
        }

        return $this->verifyUserLoginOtp($mobileOtp, $user);
    }


}
