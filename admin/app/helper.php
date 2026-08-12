<?php
//namespace App\Helpers;
 
use Illuminate\Http\Request;
use App\Models\Scheme;
use App\Models\SchemeCommission;
use App\Models\Api;
use App\Models\ApiProviderCode;
use App\Models\Apilog;
use App\Models\User;
use App\Models\Report;
use App\Models\ApiCommission;
use App\Models\Provider;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Models\Message;
use Illuminate\Support\Str;
class Helper {


    public static function pt($data)
     {
        return $data;
     }
    
     public static function refund_row($report){
        
        $report = DB::table('reports')->where('id', $report)->first();
        $user = DB::table('users')->where('id',$report->user_id)->first();
        //\helpers::Ptd($user);
        $refund['user_id'] = $user->id;
        $refund['parent__Id'] = $report->id;
        $refund['order_id'] = $report->order_id;
        $refund['number'] = $report->number;
        $refund['amount'] = $report->amount;
        $refund['total_amount'] = $report->total_amount;  
        $refund['admin_commission'] = $report->admin_commission;
        $refund['api_commission'] = $report->api_commission;
        $refund['commission'] = $report->commission;
        $refund['fund_type'] = "Credit";
        $refund['transaction_type'] = "Refund";
        $refund['provider_id'] = $report->provider_id;
        $refund['service_id'] = $report->service_id;
        $refund['api_id'] = $report->api_id;
        $refund['remark'] = "Refund For Rs. ".$report->total_amount." Number ".$report->number;
        $refund['status'] = "Success";
        $refund['path'] = $report->path;
        $refund['ip_address'] = $report->ip_address;
        $refund['opening_balance'] = $report->closing_balance;
        $refund['closing_balance'] = $user->wallet_balance;  
        $refund['transaction_date'] = Carbon::now().":".rand(111,999);
        $refund['created_at'] = Carbon::now();    
        $refund['updated_at'] = Carbon::now();                  
        $report = DB::table('reports')->insertGetId($refund);
        return $report;
     }

    public static function apiLog($url, $modal, $txnid, $header, $request, $response)
    {
        try {
            $apiresponse = Apilog::create([
                "url" => $url,
                "modal" => $modal,
                "txnid" => $txnid,
                "header" => $header,
                "request" => $request,
                "response" => $response
            ]);
        } catch (\Exception $e) {
            $apiresponse = "error";
        }
        return $apiresponse;
    }

    public static function mail($view, $data, $mailto, $name, $mailvia, $namevia, $subject)
    {
        \Mail::send($view,$data,
            function($message) use ($mailto, $subject) {
                $message->to($mailto)->subject($subject);
            }
        );
        // if (\Mail::failures()) {
        //     return "fail";
        // }
         return "success";
    }


    public static function send_report_template_mail($slug,$fund_type,$amount,$by,$current_balance,$to_user_id)
        {
            $user_id = Company::where('domain',request()->server->get('SERVER_NAME'))->first()->user_id;
            $template = EmailTemplate::where('user_id',$user_id)->where('slug',$slug)->first();
            $user_email = User::where('id',$to_user_id)->first();
            $content = $template->content;
            $content = str_replace('{NAME}', '' . $user_email->first_name . '', $content);
            $content = str_replace('{TYPE}', '' . $fund_type . '', $content);
            $content = str_replace('{AMOUNT}', '' . $amount . '', $content);
            $content = str_replace('{BY}', '' . $by . '', $content);
            $content = str_replace('{CURRENT_BALANCE}', '' . $current_balance . '', $content);
            Message::create([
                        'user_id' => $user_id,
                        'to_user_id' => $to_user_id,
                        'msg_source' => "Email",
                        'subject' => $template->subject,
                        'content' => $content,
                        'sms_file' => "",
                        'status' => '1',
            ]);
            $view = "emails.test";
             $subject = $template->subject;
             $name = "";
             $mailvia = "";
             $namevia = "";
             $data_c['subject'] = $template->subject;
             $data_c['content'] = $content;
            \helper::mail($view, $data_c, $user_email->email_address, $name, $mailvia, $namevia, $subject);
        }

        public static function send_password_template_mail($slug,$password,$to_user_id)
        {
            $user_id = Company::where('domain',request()->server->get('SERVER_NAME'))->first()->user_id;
            $template = EmailTemplate::where('user_id',$user_id)->where('slug',$slug)->first();
            $user_email = User::where('id',$to_user_id)->first();
            $content = $template->content;
            $content = str_replace('{NAME}', '' . $user_email->first_name . '', $content);
            $content = str_replace('{PASSWORD}', '' . $password . '', $content);
            Message::create([
                        'user_id' => $user_id,
                        'to_user_id' => $to_user_id,
                        'msg_source' => "Email",
                        'subject' => $template->subject,
                        'content' => $content,
                        'sms_file' => "",
                        'status' => '1',
            ]);
            $view = "emails.test";
             $subject = $template->subject;
             $name = "";
             $mailvia = "";
             $namevia = "";
             $data_c['subject'] = $template->subject;
             $data_c['content'] = $content;
            \helper::mail($view, $data_c, $user_email->email_address, $name, $mailvia, $namevia, $subject);
        }

        public static function send_otp_template_mail($slug,$otp,$to_user_id)
        {
            $user_id = Company::where('domain',request()->server->get('SERVER_NAME'))->first()->user_id;
            $template = EmailTemplate::where('user_id',$user_id)->where('slug',$slug)->first();
            $user_email = User::where('id',$to_user_id)->first();
            $content = $template->content;
            $content = str_replace('{NAME}', '' . $user_email->first_name . '', $content);
            $content = str_replace('{OTP}', '' . $otp . '', $content);
            Message::create([
                        'user_id' => $user_id,
                        'to_user_id' => $to_user_id,
                        'msg_source' => "Email",
                        'subject' => $template->subject,
                        'content' => $content,
                        'sms_file' => "",
                        'status' => '1',
            ]);
            $view = "emails.test";
             $subject = $template->subject;
             $name = "";
             $mailvia = "";
             $namevia = "";
             $data_c['subject'] = $template->subject;
             $data_c['content'] = $content;
            \helper::mail($view, $data_c, $user_email->email_address, $name, $mailvia, $namevia, $subject);
        }

        public static function send_user_register_template_mail($slug,$password,$to_user_id)
        {
            $user_id = Company::where('domain',request()->server->get('SERVER_NAME'))->first()->user_id;
            $template = EmailTemplate::where('user_id',$user_id)->where('slug',$slug)->first();
            $user_email = User::where('id',$to_user_id)->first();
            $content = $template->content;
            $content = str_replace('{NAME}', '' . $user_email->first_name . '', $content);
            $content = str_replace('{MOBILE}', '' . $user_email->mobile_number . '', $content);
            $content = str_replace('{PASSWORD}', '' . $password . '', $content);
            Message::create([
                        'user_id' => $user_id,
                        'to_user_id' => $to_user_id,
                        'msg_source' => "Email",
                        'subject' => $template->subject,
                        'content' => $content,
                        'sms_file' => "",
                        'status' => '1',
            ]);
            $view = "emails.test";
             $subject = $template->subject;
             $name = "";
             $mailvia = "";
             $namevia = "";
             $data_c['subject'] = $template->subject;
             $data_c['content'] = $content;
            \helper::mail($view, $data_c, $user_email->email_address, $name, $mailvia, $namevia, $subject);
        }
    

    public static function send_sms($slug,$tmp_id,$subject,$content,$fund_type,$amount,$by,$current_balance,$otp,$mobile_number,$password,$to_user_id)
    {
        $company = Company::where('domain', $_SERVER['SERVER_NAME'])->first();
        $user = User::where('id',$to_user_id)->first();
        if($slug!=""){
            $template = SmsTemplate::where('user_id',$company->user_id)->where('slug',$slug)->first();            
            $content = $template->content;
            $content = str_replace('{TYPE}', '' . $fund_type . '', $content);
            $content = str_replace('{AMOUNT}', '' . $amount . '', $content);
            $content = str_replace('{BY}', '' . $by . '', $content);
            $content = str_replace('{CURRENT_BALANCE}', '' . $current_balance . '', $content);
            $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
            $content = str_replace('{OTP}', '' . $otp . '', $content);
            $content = str_replace('{MOBILE}', '' . $mobile_number . '', $content);
            $content = str_replace('{PASSWORD}', '' . $password . '', $content);
            $message_categories = DB::table('message_categories')->where('slug',$slug)->first(); 
            $subject = $message_categories->category_name;
            $tmp_id = $template->template_id;
        }
        Message::create([
                        'user_id' => 1,
                        'to_user_id' => $to_user_id,
                        'msg_source' => "Sms",
                        'subject' => $subject,
                        'content' => $content,
                        'sms_file' => "",
                        'status' => '1',
        ]);
        $order_id = "MSG".rand(1111111111, 9999999999);
        $header = [];

        $smsApi = \App\Services\SmsApiService::resolveForSend();
        if ($smsApi) {
            $url = \App\Services\SmsApiService::buildUrl($smsApi, (string) $mobile_number, (string) $content, (string) $tmp_id);
            $method = $smsApi->api_method ?: 'GET';
            \helper::curl($url, $method, "", $header, "yes", "MESSAGE", $order_id);

            return "success";
        }

        if (isset($company->sms_request_url) && $company->sms_request_url !== '') {
            $url = $company->sms_request_url;
            $url = str_replace('{MOB}', '' . $mobile_number . '', $url);
            $url = str_replace('{MSG}', '' . urlencode($content) . '', $url);
            $url = str_replace('{TMPID}', '' . $tmp_id . '', $url);
            \helper::curl($url, $company->sms_api_method, "", $header, "yes", "MESSAGE", $order_id);

            return "success";
        }

        return "fail";
    }

    public static function notification($heading, $body, $user_id)
    {
        $create['user_id'] = $user_id;
        $create['heading'] = $heading;
        $create['body'] = $body;

        $action = Notification::create($create);
        if($action){
            return "success";
        }
        return "fail";
    }

    public static function statementLog($data)
    {
        $action = AccountStatement::create($data);
        if($action){
            return $action->id;
        }else{
            return "fail";
        }
    }

    

    public static function Transaction_Refund($report)
    { 
        $report = Report::where('id', $report->id)->first();
        $user = User::where('id', $report->user_id)->first();
        $report->status ="Failed";        
        $report->update();
        User::where('id', $user->id)->increment('wallet_balance', $report->amount);
        $insert = [
                'parent__Id' => $report->parent__Id,
                'user_id' => $report->user_id,
                'credit_user_id' => $report->credit_user_id,
                'debit_user_id' => $report->debit_user_id,
                'number' => $report->number,
                'optional_1' => $report->optional_1,
                'optional_2' => $report->optional_2,
                'optional_3' => $report->optional_3,
                'optional_4' => $report->optional_4,
                'optional_5' => $report->optional_5,
                'amount' => $report->amount,
                'total_amount' => $report->total_amount,
                'commission' => $report->commission,
                'admin_commission' => $report->admin_commission,
                'api_commission' => $report->api_commission,
                'api_charges' => $report->api_charges,
                'charges' => $report->charges,
                'admin_charges' => $report->admin_charges,
                'fund_type' => "Credit",
                'transaction_type' => "Refund",
                'provider_id' => $report->provider_id,
                'service_id' => $report->service_id,
                'api_id' => $report->api_id,
                'api_id_2' => $report->api_id_2,
                'remark' => "Refund ".$report->remark,
                'order_id' => $report->order_id,
                'operator_id' => $report->operator_id,
                'api_operator_id' => $report->api_operator_id,
                'api_2_operator_id' => $report->api_2_operator_id,
                'status' => "Success",
                'gst' => $report->adminprofit,
                'tds' => $report->adminprofit,
                'complaint_id' => 0,
                'path' => $report->path,
                'ip_address' => $report->ip_address,
                'opening_balance' => $user->wallet_balance,
                'closing_balance' => $user->wallet_balance + $report->amount
            ];
            Report::create($insert);
            $parent__Id = $report->parent__Id;
            if($parent__Id!=0){
                $report = Report::where('parent__Id', $parent__Id)->first();
                $report =  \helper:: Transaction_Refund_Commission($report);
                $parent__Id = $report->parent__Id;
                if($parent__Id!=0){
                    $report = Report::where('parent__Id', $parent__Id)->first();
                    $report =  \helper:: Transaction_Refund_Commission($report);
                    $parent__Id = $report->parent__Id;
                    if($parent__Id!=0){
                        $report = Report::where('parent__Id', $parent__Id)->first();
                        $report =  \helper:: Transaction_Refund_Commission($report);
                        $parent__Id = $report->parent__Id;  
                        if($parent__Id!=0){
                            $report = Report::where('parent__Id', $parent__Id)->first();
                            $report =  \helper:: Transaction_Refund_Commission($report);
                            $parent__Id = $report->parent__Id;   
                        }
                    }
                }
            }
    }

    public static function Transaction_Refund_Commission($report)
    {
    //echo "<pre>";print_r($report);die; 
        $report = Report::where('id', $report->id)->first();
        $user = User::where('id', $report->user_id)->first();
        //$report->status ="Failed";        
        //$report->update();
        User::where('id', $user->id)->decrement('wallet_balance', $report->amount);
        $insert = [
                'parent__Id' => $report->parent__Id,
                'user_id' => $report->user_id,
                'credit_user_id' => $report->credit_user_id,
                'debit_user_id' => $report->debit_user_id,
                'number' => $report->number,
                'optional_1' => $report->optional_1,
                'optional_2' => $report->optional_2,
                'optional_3' => $report->optional_3,
                'optional_4' => $report->optional_4,
                'optional_5' => $report->optional_5,
                'amount' => $report->amount,
                'total_amount' => $report->total_amount,
                'commission' => $report->commission,
                'admin_commission' => $report->admin_commission,
                'api_commission' => $report->api_commission,
                'api_charges' => $report->api_charges,
                'charges' => $report->charges,
                'admin_charges' => $report->admin_charges,
                'fund_type' => "Debit",
                'transaction_type' => "Reverse Commission",
                'provider_id' => $report->provider_id,
                'service_id' => $report->service_id,
                'api_id' => $report->api_id,
                'api_id_2' => $report->api_id_2,
                'remark' => "Reverse ".$report->remark,
                'order_id' => $report->order_id,
                'operator_id' => $report->operator_id,
                'api_operator_id' => $report->api_operator_id,
                'api_2_operator_id' => $report->api_2_operator_id,
                'status' => "Success",
                'gst' => $report->adminprofit,
                'tds' => $report->adminprofit,
                'complaint_id' => 0,
                'path' => $report->path,
                'ip_address' => $report->ip_address,
                'opening_balance' => $user->wallet_balance,
                'closing_balance' => $user->wallet_balance - $report->amount
            ];
            Report::create($insert);
            return $report;
       //echo "<pre>";print_r($report);die;
    }
    public static function SetCommission($report)
    {
        ///1 Level Commission
        $report = Report::where('id', $report->report_id)->first();
        $user = User::where('id', $report->user_id)->first();

        if($user->parent_id !=0){
            $parent = User::where('id', $user->parent_id)->first();
            $commission_user = \helper::getCommission($report->total_amount, $user->scheme_id, $report->provider_id);
            $commission_parent = \helper::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id);
            $commission =  $commission_parent - $commission_user;
            $data['user_id'] = $parent->id;
            $data['parent__Id'] = $report->id;
            $data['order_id'] = $report->order_id;
            $data['number'] = $report->number;
            $data['amount'] = $commission;
            $data['total_amount'] = $report->total_amount;  
            //$data['admin_commission'] = $report->admin_commission;
            //$data['api_commission'] = $report->api_commission;
            $data['commission'] = $commission;
            $data['fund_type'] = "Credit";
            $data['transaction_type'] = "Commission";
            $data['provider_id'] = $report->provider_id;
            $data['service_id'] = $report->service_id;
            $data['api_id'] = $report->api_id;
            $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;
            $data['status'] = "Success";
            $data['path'] = $report->path;
            $data['ip_address'] = $report->ip_address;
            $data['opening_balance'] = $parent->wallet_balance;
            User::where('id', $parent->id)->increment('wallet_balance', $commission);
            $parent = User::where('id', $parent->id)->first();
            $data['closing_balance'] = $parent->wallet_balance;                    
            $report = Report::create($data);
            ///2 Level Commission
            $report = Report::where('id', $report->id)->first();
            $user = User::where('id', $report->user_id)->first();
            if($user->parent_id !=0){
                $parent = User::where('id', $user->parent_id)->first();
                $commission_user = \helper::getCommission($report->total_amount, $user->scheme_id, $report->provider_id);
                $commission_parent = \helper::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id);
                $commission =  $commission_parent - $commission_user;
                $data['user_id'] = $parent->id;
                $data['parent__Id'] = $report->id;
                $data['order_id'] = $report->order_id;
                $data['number'] = $report->number;
                $data['amount'] = $commission;
                $data['total_amount'] = $report->total_amount;  
                //$data['admin_commission'] = $report->admin_commission;
                //$data['api_commission'] = $report->api_commission;
                $data['commission'] = $commission;
                $data['fund_type'] = "Credit";
                $data['transaction_type'] = "Commission";
                $data['provider_id'] = $report->provider_id;
                $data['service_id'] = $report->service_id;
                $data['api_id'] = $report->api_id;
                $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;
                $data['status'] = "Success";
                $data['path'] = $report->path;
                $data['ip_address'] = $report->ip_address;
                $data['opening_balance'] = $parent->wallet_balance;
                User::where('id', $parent->id)->increment('wallet_balance', $commission);
                $parent = User::where('id', $parent->id)->first();
                $data['closing_balance'] = $parent->wallet_balance;                    
                $report = Report::create($data);
                ///3 Level Commission
                $report = Report::where('id', $report->id)->first();
                $user = User::where('id', $report->user_id)->first();
                if($user->parent_id !=0){
                    $parent = User::where('id', $user->parent_id)->first();
                    $commission_user = \helper::getCommission($report->total_amount, $user->scheme_id, $report->provider_id);
                    $commission_parent = \helper::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id);
                    $commission =  $commission_parent - $commission_user;
                    $data['user_id'] = $parent->id;
                    $data['parent__Id'] = $report->id;
                    $data['order_id'] = $report->order_id;
                    $data['number'] = $report->number;
                    $data['amount'] = $commission;
                    $data['total_amount'] = $report->total_amount;  
                    //$data['admin_commission'] = $report->admin_commission;
                    //$data['api_commission'] = $report->api_commission;
                    $data['commission'] = $commission;
                    $data['fund_type'] = "Credit";
                    $data['transaction_type'] = "Commission";
                    $data['provider_id'] = $report->provider_id;
                    $data['service_id'] = $report->service_id;
                    $data['api_id'] = $report->api_id;
                    $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;
                    $data['status'] = "Success";
                    $data['path'] = $report->path;
                    $data['ip_address'] = $report->ip_address;
                    $data['opening_balance'] = $parent->wallet_balance;
                    User::where('id', $parent->id)->increment('wallet_balance', $commission);
                    $parent = User::where('id', $parent->id)->first();
                    $data['closing_balance'] = $parent->wallet_balance;                    
                    $report = Report::create($data);
                    ///4 Level Commission
                    $report = Report::where('id', $report->id)->first();
                    $user = User::where('id', $report->user_id)->first();

                      
                }  
            }
        }
        //return $parent;
        if($user->parent_id ==1){
            $parent = User::where('id', $user->parent_id)->first();
            $commission_user = \helper::getAdminCommission($report->total_amount, $report->provider_id);
            $commission_parent = \helper::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id);
            $commission =  $commission_user - $commission_parent;
            $data['user_id'] = $parent->id;
            $data['parent__Id'] = $report->id;
            $data['order_id'] = $report->order_id;
            $data['number'] = $report->number;
            $data['amount'] = $commission;
            $data['total_amount'] = $report->total_amount;  
            //$data['admin_commission'] = $report->admin_commission;
            //$data['api_commission'] = $report->api_commission;
            $data['commission'] = $commission;
            $data['fund_type'] = "Credit";
            $data['transaction_type'] = "Commission";
            $data['provider_id'] = $report->provider_id;
            $data['service_id'] = $report->service_id;
            $data['api_id'] = $report->api_id;
            $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;
            $data['status'] = "Success";
            $data['path'] = $report->path;
            $data['ip_address'] = $report->ip_address;
            $data['opening_balance'] = $parent->wallet_balance;
            User::where('id', $parent->id)->increment('wallet_balance', $commission);
            $parent = User::where('id', $parent->id)->first();
            $data['closing_balance'] = $parent->wallet_balance;                    
            $report = Report::create($data);
            ///4 Level Commission
            $report = Report::where('id', $report->id)->first();
            $user = User::where('id', $report->user_id)->first();  
        }
    }
    public static function getCommission($amount, $scheme, $provider_id)
    {
        $myscheme = Scheme::where('id', $scheme)->first();
        
        if($myscheme && $myscheme->status == "1"){
            $comdata = SchemeCommission::where('provider_id', $provider_id)->where('scheme_id', $scheme)->first();
            if ($comdata) {
                if ($comdata->amount_type == "Commission Percent") {
                    $commission = $amount * $comdata->amount_value / 100;
                }else{
                    $commission = $comdata->amount_value;
                }

                if($commission == null){
                    $commission = 0;
                }
            }else{
                $commission = 0;
            }
        }else{
            $commission = 0;
        }
        
        return $commission;
    }

    public static function getApiCommission($amount, $api_id, $provider_id)
    {
        $api = Api::where('id', $api_id)->first();
        
        if($api && $api->status == "1"){
            $apicom = ApiCommission::where('provider_id', $provider_id)->where('api_id', $api_id)->first();
            if ($apicom) {
                if ($apicom->amount_type == "Commission Percent") {
                    $commission = $amount * $apicom->amount_value / 100;
                }else{
                    $commission = $apicom->amount_value;
                }

                if($commission == null){
                    $commission = 0;
                }
            }else{
                $commission = 0;
            }
        }else{
            $commission = 0;
        }
        
        return $commission;
    }

    public static function getAdminCommission($amount, $provider_id)
    {
        $provider = Provider::where('id', $provider_id)->first();
        
        if($provider && $provider->status == "1"){
            $admincom = Provider::where('id', $provider_id)->first();
            if ($admincom) {
                if ($admincom->amount_type == "Commission Percent") {
                    $commission = $amount * $admincom->amount_value / 100;
                }else{
                    $commission = $admincom->amount_value;
                }

                if($commission == null){
                    $commission = 0;
                }
            }else{
                $commission = 0;
            }
        }else{
            $commission = 0;
        }
        
        return $commission;
    }

    public static function getIp()
    {
        return request()->ip();
    }

    public static function getDmtCommission($amount, $role, $slab)
    {
        $commission = DmtCharge::where('provider_id', $slab)->first([$role.' as value', 'type']);  
        
        if($commission->type == 'flat'){
            return $commission->value;
        } else{
            return ($commission->value/100) * $amount;
        }        
    }

    public static function curl($url, $method = 'GET', $parameters = null, $header = [], $log = "no", $modal = "none", $txnid = "none")
    {   
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_TIMEOUT, 180);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if($parameters != ""){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        }

        if(sizeof($header) > 0){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if($log != "no"){
            Apilog::create([
                "url" => $url,
                "modal" => $modal,
                "txnid" => $txnid,
                "header" => $header,
                "request" => $parameters,
                "response" => $response
            ]);
        }

        return ["response" => $response, "error" => $err, 'code' => $code];
    }
    public static function ApiProviderCode($api_id, $provider_id)
    {
        $api = Api::where('id', $api_id)->first();
        
        if($api && $api->status == "1"){
            $api_provider_code = ApiProviderCode::where('provider_id', $provider_id)->where('api_id', $api_id)->first();
            if ($api_provider_code) {
                
                $provider_code = $api_provider_code->provider_code;

                if($provider_code == null){
                    $provider_code = 0;
                }
            }else{
                $provider_code = 0;
            }
        }else{
            $provider_code = 0;
        }
        
        return $provider_code;
    }
    
    

    public static function getTds($amount)
    {
        return $amount*5/100;
    }

    public static function callback($report, $product)
    {
        switch ($product) {
            case 'utipancard':
            case 'recharge':
                $report = Report::where('id', $report->id)->first();
                $apitxnid = $report->apitxnid;
                $refno = $report->refno;
                break;

            case 'utiid':
                $report = Utiid::where('id', $report->id)->first();
                $apitxnid = $report->vleid;
                $refno = $report->remark;
                break;
        }

        if($report->status == "success"){
            $status = "success";
        }elseif($report->status == "reversed"){
            $status = "failed";
        }else{
            $status = "unknown";
        }
        

        if($status != "unknown"){
            $url = $report->user->callbackurl."?txnid=".$apitxnid."&status=".$report->status."&refno=".$refno."&product=".$product;
            $result = \Myhelper::curl($url, "GET", "", [], "no", "", "");
            Callbackresponse::create([
                'url' => $url,
                'response' => ($result['response'] != '') ? $result['response'] : $result['error'],
                'status' => $result['code'],
                'product' => $product,
                'user_id' => $report->user_id,
                'transaction_id' => $report->id
            ]);
        }

    }

    public static function FormValidator($rules, $post)
    {
        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'status' => 'ERR',  
                'message' => $error
            ));
        }else{
            return "no";
        }
    }
}

if (! function_exists('admin_asset')) {
    /**
     * Build a public asset URL for the admin app, including /admin on Hostinger.
     */
    function admin_asset(string $path): string
    {
        $path = ltrim($path, '/');

        if (! app()->runningInConsole() && ! empty($_SERVER['SCRIPT_NAME'])) {
            $scriptName = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);

            if (preg_match('#(/admin)/public/index\.php$#', $scriptName, $matches)) {
                return rtrim(request()->getSchemeAndHttpHost(), '/').$matches[1].'/'.$path;
            }
        }

        $configuredPath = parse_url((string) config('app.url'), PHP_URL_PATH) ?: '';

        if ($configuredPath !== '' && $configuredPath !== '/') {
            return rtrim((string) config('app.url'), '/').'/'.$path;
        }

        return asset($path);
    }
}

if (! function_exists('admin_company_logo')) {
    /**
     * Public URL for files stored in admin/public/company_logo/.
     */
    function admin_company_logo(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        return admin_asset('company_logo/'.ltrim($filename, '/'));
    }
}

if (! function_exists('admin_slider_image_dir')) {
    function admin_slider_image_dir(): string
    {
        $dir = public_path('slider_image');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}

if (! function_exists('admin_slider_image_disk_path')) {
    /**
     * Resolve an uploaded slider image from admin/public or legacy locations.
     */
    function admin_slider_image_disk_path(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $filename = basename($filename);
        $candidates = [
            public_path('slider_image/'.$filename),
            base_path('public/slider_image/'.$filename),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'slider_image'.DIRECTORY_SEPARATOR.$filename,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}

if (! function_exists('admin_slider_image')) {
    /**
     * Public URL for files stored in admin/public/slider_image/.
     */
    function admin_slider_image(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        return admin_asset('slider_image/'.basename($filename));
    }
}

if (! function_exists('admin_build_serial')) {
    /**
     * Visible deploy marker on admin auth pages. Bump on each production release.
     */
    function admin_build_serial(): string
    {
        return '20260812-RECHARGE-001';
    }
}

if (! function_exists('normalize_user_pin')) {
    function normalize_user_pin($pin): string
    {
        $digits = preg_replace('/\D/', '', (string) $pin);

        return str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('ensure_user_visible_password_column')) {
    function ensure_user_visible_password_column(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'visible_password')) {
            return;
        }

        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('visible_password', 255)->nullable()->after('password');
        });
    }
}

if (! function_exists('user_password_update_fields')) {
    function user_password_update_fields(string $plain): array
    {
        ensure_user_visible_password_column();

        return [
            'password' => \Illuminate\Support\Facades\Hash::make($plain),
            'visible_password' => $plain,
            'updated_at' => now(),
        ];
    }
}