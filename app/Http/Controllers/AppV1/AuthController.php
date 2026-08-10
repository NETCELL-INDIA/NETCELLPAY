<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
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

    function homeData(Request $post){
       



        // if($post->from_date){
        //     $from_date = $post->from_date." 00:00:00";
        //     $to_date = $post->to_date." 23:59:59";
        // }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
       // }

        $report_s = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Success')
        ->get();
    $report_p = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Pending')
        ->get();
    $report_f = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Failed')
        ->get();
    $report_r = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Refund')
        ->where('status', 'Success')
        ->get();
    $report_receive_money = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Receive Money')
        ->where('status', 'Success')
        ->get();
    $report_upi_add_money = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
                ->where('user_id',$post->user_id)
                ->where('transaction_type', 'Upi Add Money')
                ->where('status', 'Success')
                ->get();        
    $tranaction_reports = DB::table('reports')->where('user_id',$post->user_id)
        ->where('transaction_type','Recharge')
        ->orderBy('created_at', 'DESC')->take(5)
        ->get();  
    // $fund_request = FundRequest::where('user_id',$post->user_id)
    //         ->orderBy('created_at', 'DESC')->take(5)
    //         ->get(); 
    $fund_reports = DB::table('reports')->where('user_id',$post->user_id)
            ->whereIn('transaction_type',['Transfer Money','Receive Money','Self Money','Money Reverse','Reverse Money'])
            ->orderBy('created_at', 'DESC')->take(5)
            ->get();
    $user = DB::table('users')->where('id',$post->user_id)->first(); 
 //   if($user->role_id==3||$user->role_id==6){

        $Report_Success_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Success')
        ->get();
        $Report_Pending_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Pending')
        ->get();
        $Report_Parent_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Commission')
        ->where('status', 'Success')
        ->get();
        $Report_Parent_Reverse_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',$post->user_id)
        ->where('transaction_type', 'Reverse Commission')
        ->where('status', 'Success')
        ->get();

        $Total_Complaints_Count = DB::table('complaints')
        //->whereBetween('created_at', [$from_date,$to_date])
        ->whereIn('status', ['Open','Under Review'])
        ->where('user_id',$post->user_id)
        ->get();
        //echo "<pre>";print_r($Report_Success->sum('commission'));die;

   // }       
       // echo "<pre>";print_r($user);die; 
        $data_n['rc_success_amount'] = $report_s->sum('total_amount');
        $data_n['rc_success_hit'] = $report_s->count();
        $data_n['rc_pending_amount'] = $report_p->sum('total_amount');
        $data_n['rc_pending_hit'] = $report_p->count();
        $data_n['rc_failed_amount'] = $report_f->sum('total_amount');
        $data_n['rc_failed_hit'] = $report_f->count();
        $data_n['rc_refund_amount'] = $report_r->sum('total_amount');
        $data_n['rc_refund_hit'] = $report_r->count();
        $data_n['rc_receive_money'] = $report_receive_money->sum('total_amount');
        $data_n['rc_upi_add_money'] = $report_upi_add_money->sum('total_amount');

        $data_n['rc_receive_money'] = $data_n['rc_receive_money'] + $data_n['rc_upi_add_money'];
        $data_n['rc_commission'] = $Report_Success_Commission->sum('commission') + $Report_Pending_Commission->sum('commission') + $Report_Parent_Commission->sum('amount') + $Report_Parent_Reverse_Commission->sum('amount');
        
        $data_n['rc_complaint_hit'] = $Total_Complaints_Count->count();

        $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();

        $compan_data['domain'] = $company->domain;
        $compan_data['company_name'] = $company->company_name;
        $compan_data['support_number'] = $company->support_number;
        $compan_data['support_number_2'] = $company->support_number_2;
        $compan_data['support_email'] = $company->support_email;
        $compan_data['refund_policy'] = $company->refund_policy;
        $compan_data['terms_and_conditions'] = $company->terms_and_conditions;
        $compan_data['privacy_policy'] = $company->privacy_policy;
        $compan_data['company_address'] = $company->company_address;

        $errors = [
            'name' => $user->first_name." ".$user->last_name,
            'admin_url' => env('ADMIN_HOST'),
            'day_book' => $data_n,
            'company_data' => $compan_data,
            'type' => 'success',
            'message' => 'Fatch Sucessfuly',
            'wallet_balance' =>  round($user->wallet_balance,2),
            'shop_name' =>  $user->outlet_name,
            'mobile' =>  $user->mobile_number,
            'email' =>  $user->email_address,
            'profile' =>  env('ADMIN_HOST')."/profile_pic/".$user->profile_pic,
            'announcement' =>   DB::table('announcements')->find(1)->message,
            'sliders' =>   DB::table('sliders')->where('status',1)->where('deleted_at',0)->get(),
            'parent_id' =>  $user->parent_id,
            'whatsapp' =>'whatsapp://send/?phone=+919337692413&text=Hii',
            //   'pay_method' => [
            //   [
            //   'title'=> "PAY VIA UPI APP",
            //   'url' => "payment_one_gateway",
            //   'back_path' => 'done',
            //   'r_type' => '_system' 
            // ],
            // [
            //   'title'=> "PAY VIA QR CODE",
            //   'url' => "payment_upi_gateway",
            //   'back_path' => 'done',
            //   'r_type' => '_system' 
            // ],],
          
        ];
        return response()->json($errors);
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
            if(Hash::check($post->password, $user->password)){

                if($user->status==1){
                    if($user->login_type=="OTP"){
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
                            $update = DB::table('users')->where("id",$user->id)->update([
                                'otp' => $otpHash,
                                'email_otp' => $otpHash,
                                'otp_limit' => $user->otp_limit + 1,
                                'otp_created_at' => Carbon::now(),
                            ]);
                            ////Send Whatsapp Message Start
                            $slug = 'otp';
                            $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
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
                                $sms = \helpers::sendWhatasappMsg($msg_data);
                            }
                            ////Send Whatsapp Message End
                            ////Send Email Start
                            $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                            if($company->email_message == 1){
                                $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                                $content_email = $email_tmp->content;
                                $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                                $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                                $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                                $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                                $content_email = str_replace('{OTP}', $otp, $content_email);
                                Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                            }
                            ////Send Email End
                        }else{
                            $data['type'] = 'error';
                            $data['message'] = "otp limit exhausted login after 10 minutes.";
                            
                        }
                    }else{
                        
                        ////
                        $random = Str::random(40);
                        $update = DB::table('users')->where("id",$user->id)->update(['login_key'=>$random]);
                        ///
                        $user = DB::table('users')->where("id",$user->id)->first();
                        $data['data'] = [
                            'parent_id' =>  $user->parent_id,
                            'login_key' => $user->login_key,
                            'user_id' =>  $user->id,
                            'role_id' => $user->role_id,
                            'states' =>   DB::table('states')->where('status',1)->get(['id','state_name']),
                            'mobile_provider' =>   DB::table('providers')->where('status',1)->where('service_id',1)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                            'postpaid_provider' =>   DB::table('providers')->where('status',1)->where('service_id',4)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                            'dth_provider' =>   DB::table('providers')->where('status',1)->where('service_id',2)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                        ];
                        $data['type'] = 'success';
                        $data['message'] = "Login Sucessfuly";
                        // 
                        // $post->session()->put('user_id', $user->id);
                        // $post->session()->put('login_key', $user->login_key);
                        // $post->session()->put('role_id', $user->role_id);
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
        return $data;
    }

    public function resetPassword(Request $post)
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
                    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otpHash = Hash::make($otp);
                    $data['type'] = 'otp_verify';
                    $data['message'] = 'OTP sent to email and mobile number successfully.';
                    $update = DB::table('users')->where("id",$user->id)->update([
                        'otp' => $otpHash,
                        'email_otp' => $otpHash,
                        'otp_limit' => $user->otp_limit + 1,
                        'otp_created_at' => Carbon::now(),
                    ]);
                    ////Send Whatsapp Message Start
                    $slug = 'otp';
                    $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
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
                        $sms = \helpers::sendWhatasappMsg($msg_data);
                    }
                    ////Send Whatsapp Message End
                    ////Send Email Start
                    $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                    if($company->email_message == 1){
                        $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                        $content_email = $email_tmp->content;
                        $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                        $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                        $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                        $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                        $content_email = str_replace('{OTP}', $otp, $content_email);
                        Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                    }
                    ////Send Email End
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


    public function resetPasswordOtp(Request $post)
    {

        $rules = array(
            'mobile_number'  => 'required|numeric|digits:10',
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
                if($user->status==1){
                    if($this->verifyMobileLoginOtp($post->otp, $user)){
                        $password_g = Str::random(8);
                        $password = Hash::make($password_g);
                        $user_data = DB::table('users')->where('id', $user->id)->first();
                        DB::table('users')->where('id', $user->id)->update([
                            'password' => $password,
                            'otp' => null,
                            'email_otp' => null,
                            'otp_limit' => 0,
                        ]);
                        
                         ////Send Whatsapp Message Start
                         $slug = 'forgot_password';
                         $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                         $content = $sms_tmp->content;
                         $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                         $content = str_replace('{MIDDLE_NAME}', '' . $user_data->middle_name . '', $content);
                         $content = str_replace('{LAST_NAME}', '' . $user_data->last_name . '', $content);
                         $content = str_replace('{OUTLET_NAME}', '' . $user_data->outlet_name . '', $content);
                         $content = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content);
                         $content = str_replace('{PASSWORD}', '' . $password_g . '', $content);
                         $content = str_replace('{PIN}', '' . $user_data->t_pin . '', $content);
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
                             $content_email = str_replace('{MIDDLE_NAME}', '' . $user_data->middle_name . '', $content_email);
                             $content_email = str_replace('{LAST_NAME}', '' . $user_data->last_name . '', $content_email);
                             $content_email = str_replace('{OUTLET_NAME}', '' . $user_data->outlet_name . '', $content_email);
                             $content_email = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content_email);
                             $content_email = str_replace('{PASSWORD}', '' . $password_g . '', $content_email);
                             $content_email = str_replace('{PIN}', '' . $user_data->t_pin . '', $content_email);
                            Mail::to(strtolower($user_data->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                        }
                        ////Send Email End
                        return response()->json(array(
                            'type' => 'success',  
                            'message' => 'New Password check Email & Mobile Send Successfuly.'
                        ));
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
            $data['message'] = "Invalid mobile number";
        }
        return $data;
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
            if(Hash::check($post->password, $user->password)){

                if($user->status==1){
                    if($this->verifyMobileLoginOtp($post->otp, $user)){
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
                        $data['data'] = [
                            'parent_id' =>  $user->parent_id,
                            'login_key' => $user->login_key,
                            'user_id' =>  $user->id,
                            'role_id' => $user->role_id,
                            'states' =>   DB::table('states')->where('status',1)->get(['id','state_name']),
                            'mobile_provider' =>   DB::table('providers')->where('status',1)->where('service_id',1)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                            'postpaid_provider' =>   DB::table('providers')->where('status',1)->where('service_id',4)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                            'dth_provider' =>   DB::table('providers')->where('status',1)->where('service_id',2)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                        ];
                        $data['type'] = 'success';
                        $data['message'] = "Login Sucessfuly";
                        // 
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
        return $data;
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
                    $t_pin = rand(1111,9999);
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
                    ////
                    $random = Str::random(40);
                    $update = DB::table('users')->where("id",$user->id)->update(['login_key'=>$random]);
                    ///
                    $user = DB::table('users')->where("id",$user->id)->first();
                    $data_home = [
                        'parent_id' =>  $user->parent_id,
                        'login_key' => $user->login_key,
                        'user_id' =>  $user->id,
                        'role_id' => $user->role_id,
                        'states' =>   DB::table('states')->where('status',1)->get(['id','state_name']),
                        'mobile_provider' =>   DB::table('providers')->where('status',1)->where('service_id',1)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                        'postpaid_provider' =>   DB::table('providers')->where('status',1)->where('service_id',4)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                        'dth_provider' =>   DB::table('providers')->where('status',1)->where('service_id',2)->where('deleted_at',0)->get(['id','provider_name','provider_logo']),
                    ];
                    // 
                    DB::table('login_histories')->insert([
                        'user_id' => $user->id,
                        'ip_address' => request()->ip(),
                        'login_path' => "Web",
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
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
                        'data' => $data_home,  
                        'type' => "success",  
                        'message' => "Register sucessfuly.Login Details send email,whatsapp & sms"
                    ));
                } catch (\Exception $e) {
                   // return $e->getMessage();
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

    public function myProfile(Request $post) {
        $user = DB::table('users')->where("id",$post->user_id)->first();
        if($user){
            return response()->json(array(
                'type' => "success",  
                'message' => "Fatch Successfuly.",
                'data' => [
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'mobile_number' => $user->mobile_number,
                    'email_address' => $user->email_address,
                    'profile_pic' => $user->profile_pic,
                    'state' => $user->state,
                    'city' => $user->city,
                    'outlet_name' => $user->outlet_name,
                ]
            ));
        }else{
            return response()->json(array(
                'type' => "error",  
                'message' => "something went wrong."
            ));
        }
    }


    function myCommission(Request $post) {
        $user = DB::table('users')->where("id",$post->user_id)->first();


        $service = DB::table('services')->where('status',1)->where('deleted_at',0)->get(['id','service_name']);
        //return count($service);
        foreach ($service as $s_list) { 
            $list[$s_list->service_name][] = DB::table('providers')
            ->where('providers.status',1)
            ->where('providers.service_id',$s_list->id)
            ->where('providers.deleted_at',0)
            ->join('scheme_commissions as s','s.provider_id','=','providers.id')
            ->where('s.scheme_id','=', $user->scheme_id)
            ->get([
                'providers.id as p_id',
                'providers.provider_name as p_name',
              	'providers.provider_logo as p_logo',
                's.scheme_id',
                ////
                's.md_amount_type',
                's.md_amount_value',
                's.dt_amount_type',
                's.dt_amount_value',
                's.rt_amount_type',
                's.rt_amount_value',
            ]);
        }
        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }


    public function myProfilePasswordChange(Request $post) {
        $rules = array(
            'current_password' => 'required|min:8',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password|min:8',
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

        $user = DB::table('users')->where("id",$post->user_id)->first();
        if(Hash::check($post->current_password, $user->password)){
            $password = Hash::make($post->confirm_password);
            $user = DB::table('users')->where('id', $user->id)->update(['password' => $password]);
            if($user){
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Password Change Successfuly."
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Current password do not match.";
        }
        return $data;
    }


    public function generatePin(Request $post) {
            $t_pin = rand(1111,9999);
            $user = DB::table('users')->where('id', $post->user_id)->update(['t_pin' => $t_pin]);
            if($user){
                $user_data = DB::table('users')->where('id', $post->user_id)->first();
                ////Send Whatsapp Message Start
                $slug = 'forgot_pin';
                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                $content = $sms_tmp->content;
                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                $content = str_replace('{MIDDLE_NAME}', '' . $user_data->middle_name . '', $content);
                $content = str_replace('{LAST_NAME}', '' . $user_data->last_name . '', $content);
                $content = str_replace('{OUTLET_NAME}', '' . $user_data->outlet_name . '', $content);
                $content = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content);
                $content = str_replace('{PIN}', '' . $t_pin . '', $content);
                if($sms_tmp->status == 1){
                    $msg_data = [
                        'mobile_number' => $user_data->mobile_number,
                        'content' => $content,
                        'template_id' => $sms_tmp->template_id,
                    ];
                    $sms = \helpers::sendWhatasappMsg($msg_data);
                    //return $sms;
                }
                ////Send Whatsapp Message End
                ////Send Email Start
                    $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                    if($company->email_message == 1){
                        $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                        $content_email = $email_tmp->content;
                        $content_email = str_replace('{NAME}', '' . $user_data->first_name . '', $content_email);
                        $content_email = str_replace('{MIDDLE_NAME}', '' . $user_data->middle_name . '', $content_email);
                        $content_email = str_replace('{LAST_NAME}', '' . $user_data->last_name . '', $content_email);
                        $content_email = str_replace('{OUTLET_NAME}', '' . $user_data->outlet_name . '', $content_email);
                        $content_email = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content_email);
                        $content_email = str_replace('{PIN}', '' . $t_pin . '', $content_email);
                        Mail::to(strtolower($user_data->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                    }
                    ////Send Email End
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Pin Change Successfuly new pin send email,whatsapp & sms"
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
            return $data;
    }

    public function myProfilePinChange(Request $post) {
        $rules = array(
            'current_pin' => 'required|numeric|digits:4',
            'new_pin' => 'required|numeric|digits:4',
            'confirm_pin' => 'required|same:new_pin|numeric|digits:4',
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

        $user = DB::table('users')->where("id",$post->user_id)->first();
        if($user->t_pin == $post->current_pin){
            $user = DB::table('users')->where('id', $user->id)->update(['t_pin' => $post->confirm_pin]);
            if($user){
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Pin Change Successfuly."
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Current pin do not match.";
        }
        return $data;
    }

    private function verifyMobileLoginOtp(string $enteredOtp, object $user): bool
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

}
