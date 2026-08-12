<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Common;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
class AuthController extends Controller
{
    public function Login(Request $request)
    {
        $userId = session('user_id');
        $user = $userId
            ? DB::table('users')->where('id', $userId)->where('role_id', 1)->first()
            : null;

        if (
            $user
            && (int) ($user->status ?? 0) === 1
            && (string) session('login_key') === (string) ($user->login_key ?? '')
        ) {
            return redirect('admin/dashboard');
        }

        if (session()->has('login_key') || session()->has('user_id')) {
            $request->session()->forget(['user_id', 'login_key', 'role_id']);
        }

        $host = $request->getHost();
        $company = DB::table('companies')->where('status', 1)->where('domain', $host)->first();
        if (!$company) {
            \Log::warning("Admin login: companies row not found for host: {$host}");
        }

        $data['name'] = "shiba";
        $data['company'] = $company;
        return view('admin.auth.login', $data);
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->where("role_id",1)->first();
        if($user){
            if(Hash::check($post->password, $user->password)){

                if($user->status==1){
                    $otpLimit = (int) $user->otp_limit;
                    if($otpLimit === 5){
                        $time_diff = strtotime(Carbon::now()) - strtotime($user->otp_created_at);
                        $time_diff_min = $time_diff / 60;
                        if($time_diff_min >= 10){
                            DB::table('users')->where("id",$user->id)->update(['otp_limit' => 0]);
                            $otpLimit = 0;
                        }
                    }
                    if($otpLimit !== 5){
                        try {
                            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            $otpHash = Hash::make($otp);

                            $updateData = [
                                'otp' => $otpHash,
                                'email_otp' => $otpHash,
                                'otp_limit' => $otpLimit + 1,
                                'otp_created_at' => Carbon::now(),
                            ];

                            DB::table('users')->where("id",$user->id)->update($updateData);
                            $data['type'] = 'otp_verify';
                            $data['message'] = 'OTP sent to email and mobile number successfully.';
                            $this->attachLocalOtpHint($data, $otp);

                            if (!app()->environment('local')) {
                                try {
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
                                            Common::sendWhatasappMsg([
                                                'mobile_number' => $user->mobile_number,
                                                'content' => $content,
                                                'template_id' => $sms_tmp->template_id,
                                            ]);
                                        }
                                    }

                                    $company = DB::table('companies')->where('status', "1")->where('domain', request()->getHost())->first();
                                    $company = $company ?: DB::table('companies')->where('status', "1")->first();
                                    if($company && $company->email_message == 1){
                                        $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                                        if ($email_tmp && !empty($user->email_address)) {
                                            $content_email = $email_tmp->content;
                                            $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                                            $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                                            $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                                            $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                                            $content_email = str_replace('{OTP}', $otp, $content_email);
                                            Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                                        }
                                    }
                                } catch (\Throwable $e) {
                                    // OTP is already saved; delivery failures should not block login.
                                }
                            }
                        } catch (\Throwable $e) {
                            return response()->json([
                                'type' => 'error',
                                'message' => 'Unable to process login OTP. Please try again.',
                            ]);
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->where("role_id",1)->first();
        if($user){
            if(Hash::check($post->password, $user->password)){

                if($user->status==1){
                    $otpValid = $this->verifyUnifiedLoginOtp($post, $user);

                    if ($otpValid) {
                        $data['type'] = 'success';
                        $data['message'] = "Login successful.";
                        $loginKey = $user->login_key ?: Str::random(40);
                        DB::table('users')->where('id', $user->id)->update([
                            'login_key' => $loginKey,
                            'otp_limit' => 0,
                            'otp' => null,
                            'email_otp' => null,
                        ]);
                        $post->session()->put('user_id', $user->id);
                        $post->session()->put('login_key', $loginKey);
                        Common::recordLoginHistory((int) $user->id, 'Web');
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


    public function forgotPassword(Request $request)
    {
        $host = $request->getHost();
        $company = DB::table('companies')->where('status', 1)->where('domain', $host)->first();
        if (!$company) {
            $company = DB::table('companies')->where('status', 1)->first();
        }

        return view('admin.auth.forgot-password', ['company' => $company]);
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->where("role_id",1)->first();
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
                    $this->attachLocalOtpHint($data, $otp);
                    if (!app()->environment('local')) {
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
                        $sms = Common::sendWhatasappMsg($msg_data);
                    }
                    }
                    ////Send Whatsapp Message End
                    ////Send Email Start
                    $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
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

        $user = DB::table('users')->where("mobile_number",$post->mobile_number)->where("role_id",1)->first();
        if($user){
            if($user->status==1){
                $otpValid = $this->verifyUnifiedLoginOtp($post, $user);

                if ($otpValid) {
                    ////
                    $random = Str::random(40);
                    $pass_g = Str::random(8);
                    $pass = Hash::make($pass_g);
                    $update = DB::table('users')->where("id",$user->id)->update(['login_key'=>$random,'password'=>$pass]);
                    if($update){
                        $data['type'] = 'success';
                        $data['message'] = "New password send sucessfuly check email & mobile number.";
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
                            if ($sms_tmp->status == 1) {
                                $msg_data = [
                                    'mobile_number' => $post->mobile_number,
                                    'content' => $content,
                                    'template_id' => $sms_tmp->template_id,
                                ];
                                $sms = Common::sendWhatasappMsg($msg_data);
                            }
                        }
                        ////Send Whatsapp Message End
                    } else {
                        $data['type'] = 'error';
                        $data['message'] = "Unable to reset password. Please try again.";
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

    public function Logout(Request $post)
    {
        $post->session()->flush();
        return redirect()->route('loginPage');
    }

    /**
     * Expose OTP on local dev only (SMS/email delivery is skipped there).
     *
     * @param  array<string, mixed>  $data
     */
    private function attachLocalOtpHint(array &$data, string $otp): void
    {
        if (!app()->environment('local')) {
            return;
        }

        $data['local_otp'] = $otp;
        $data['message'] = 'Local dev OTP generated. Use the code shown on screen in both Mobile and Email OTP fields.';
    }

    /**
     * Verify mobile and email OTP fields against the same stored login OTP.
     */
    private function verifyUnifiedLoginOtp(Request $post, object $user): bool
    {
        $mobileOtp = trim((string) $post->mobile_otp);
        $emailOtp = trim((string) $post->email_otp);

        if ($mobileOtp === '' || $emailOtp === '' || $mobileOtp !== $emailOtp || empty($user->otp)) {
            return false;
        }

        if (!empty($user->otp_created_at)) {
            $minutes = (Carbon::now()->timestamp - strtotime((string) $user->otp_created_at)) / 60;
            if ($minutes >= 10) {
                return false;
            }
        }

        return $this->otpMatchesStoredValue($mobileOtp, $user->otp);
    }

    /**
     * Match entered OTP against stored hash (or legacy plain 6-digit value).
     */
    private function otpMatchesStoredValue(string $otp, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        if (Hash::check($otp, $stored)) {
            return true;
        }

        return strlen($stored) === 6 && ctype_digit($stored) && hash_equals($stored, $otp);
    }
}
