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

    public static function isApiPartnerPath($path): bool
    {
        return strcasecmp(trim((string) $path), 'Api') === 0;
    }

    public static function rechargePendingStatuses(): array
    {
        return ['Pending', 'Under Process', 'Under Proces', 'Processing'];
    }

    public static function sendApiPartnerRechargeCallback($report): bool
    {
        if (is_numeric($report) || is_string($report)) {
            $report = DB::table('reports')->where('id', $report)->first();
        }
        if (! $report || ! self::isApiPartnerPath($report->path ?? '')) {
            return false;
        }
        if (in_array((string) $report->status, self::rechargePendingStatuses(), true)) {
            return false;
        }
        if ((int) ($report->callback_status ?? 0) === 1) {
            return false;
        }

        $user = DB::table('users')->where('id', $report->user_id)->first(['callback_url', 'wallet_balance']);
        $base = trim((string) ($user->callback_url ?? ''));
        if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL)) {
            DB::table('reports')->where('id', $report->id)->update(['callback_status' => 1]);

            return false;
        }

        $query = http_build_query([
            'request_order_id' => (string) ($report->request_order_id ?? ''),
            'status' => (string) $report->status,
            'amount' => (string) ($report->total_amount ?? $report->amount),
            'order_id' => (string) ($report->order_id ?? ''),
            'operator_id' => (string) ($report->operator_id ?? ''),
            'balance' => round((float) ($user->wallet_balance ?? 0), 2),
        ]);
        $url = $base.(str_contains($base, '?') ? '&' : '?').$query;
        $result = self::curl($url, 'GET', '', [], 'yes', 'USER_RECHARGE_CALLBACK', (string) ($report->order_id ?? $report->id));
        $payload = [
            'callback_status' => 1,
            'updated_at' => Carbon::now(),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('reports', 'api_partner_call_back_url')) {
            $payload['api_partner_call_back_url'] = $url;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('reports', 'api_partner_callback_response')) {
            $payload['api_partner_callback_response'] = (string) ($result['response'] ?? $result['error'] ?? '');
        }
        DB::table('reports')->where('id', $report->id)->update($payload);

        return true;
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

    public static function ApiStateCode($api_id, $state_id)
    {
        $api = DB::table('apis')->where('id', $api_id)->first();
        if ($api && (int) $api->status === 1) {
            $row = DB::table('api_state_codes')->where('state_id', $state_id)->where('api_id', $api_id)->first();
            if ($row && $row->state_code !== null && $row->state_code !== '') {
                return $row->state_code;
            }
        }

        return 0;
    }

    public static function apiArrayGet($data, $path)
    {
        if (! is_array($data) || $path === null || $path === '') {
            return null;
        }
        $path = (string) $path;
        if (array_key_exists($path, $data)) {
            return $data[$path];
        }
        $cur = $data;
        foreach (explode('.', $path) as $seg) {
            if ($seg === '' || ! is_array($cur) || ! array_key_exists($seg, $cur)) {
                return null;
            }
            $cur = $cur[$seg];
        }

        return $cur;
    }

    public static function apiValueMatches($actual, $configured): bool
    {
        if ($actual === null || $configured === null || $configured === '') {
            return false;
        }
        $actual = trim((string) $actual);
        if ($actual === '') {
            return false;
        }
        foreach (preg_split('/\s*,\s*/', (string) $configured) as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            if (strcasecmp($actual, $part) === 0) {
                return true;
            }
            if (is_numeric($actual) && is_numeric($part) && (float) $actual == (float) $part) {
                return true;
            }
        }

        return false;
    }

    public static function apiSwitchOn($api, string $column): bool
    {
        if (! $api) {
            return true;
        }
        if (! isset($api->{$column})) {
            return true;
        }

        return (int) $api->{$column} === 1;
    }

    public static function mapApiLiveStatus($api, $actual): ?string
    {
        if (self::apiValueMatches($actual, $api->success_value ?? '')) {
            return self::apiSwitchOn($api, 'success_switch') ? 'Success' : 'Pending';
        }
        if (self::apiValueMatches($actual, $api->failed_value ?? '')
            || self::apiValueMatches($actual, $api->error_value_response ?? '')) {
            return self::apiSwitchOn($api, 'failure_switch') ? 'Failed' : 'Pending';
        }
        if (self::apiValueMatches($actual, $api->refund_value ?? '')) {
            return self::apiSwitchOn($api, 'failure_switch') ? 'Failed' : 'Pending';
        }
        if (self::apiSwitchOn($api, 'pending_switch') && self::apiValueMatches($actual, $api->pending_value ?? '')) {
            return 'Pending';
        }

        return null;
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

        $filename = ltrim($filename, '/');
        $path = public_path('company_logo/'.$filename);
        $url = admin_asset('company_logo/'.$filename);
        if (is_file($path)) {
            $url .= '?v='.filemtime($path);
        }

        return $url;
    }
}

if (! function_exists('admin_provider_logo_filename')) {
    function admin_provider_logo_filename(?string $logo): string
    {
        $logo = trim(str_replace('\\', '/', (string) $logo));
        if ($logo === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $logo)) {
            $path = parse_url($logo, PHP_URL_PATH) ?: '';

            return basename($path);
        }

        return basename($logo);
    }
}

if (! function_exists('admin_provider_logo_directories')) {
    function admin_provider_logo_directories(): array
    {
        $dirs = [public_path('provider_logo')];
        $userDir = dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'provider_logo';
        if ($userDir !== $dirs[0]) {
            $dirs[] = $userDir;
        }

        return array_values(array_unique($dirs));
    }
}

if (! function_exists('admin_provider_logo_delete')) {
    function admin_provider_logo_delete(?string $logo): void
    {
        $file = admin_provider_logo_filename($logo);
        if ($file === '' || $file === 'provider_logo.png') {
            return;
        }

        foreach (admin_provider_logo_directories() as $dir) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $legacy = public_path('bank_logo'.DIRECTORY_SEPARATOR.$file);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }
}

if (! function_exists('admin_provider_logo_store')) {
    function admin_provider_logo_store(\Illuminate\Http\UploadedFile $file, ?string $oldLogo = null): string
    {
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'png';
        }

        $name = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
        $bytes = (string) file_get_contents($file->getRealPath());

        foreach (admin_provider_logo_directories() as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, $bytes);
        }

        if (! empty($oldLogo)) {
            admin_provider_logo_delete($oldLogo);
        }

        return $name;
    }
}

if (! function_exists('admin_provider_logo_url')) {
    function admin_provider_logo_url(?string $logo): string
    {
        $file = admin_provider_logo_filename($logo);
        if ($file === '') {
            return admin_asset('assets/images/users/user-dummy-img.jpg');
        }

        foreach (['provider_logo', 'bank_logo'] as $folder) {
            if (is_file(public_path($folder.'/'.$file))) {
                return admin_asset($folder.'/'.$file);
            }
        }

        $userPath = dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'provider_logo'.DIRECTORY_SEPARATOR.$file;
        if (is_file($userPath)) {
            $userHost = rtrim((string) env('USER_HOST', ''), '/');
            if ($userHost !== '') {
                return $userHost.'/provider_logo/'.$file;
            }
        }

        return admin_asset('provider_logo/'.$file);
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

if (! function_exists('website_media_dir')) {
    function website_media_dir(): string
    {
        $dir = public_path('website_media');
        if (! is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (\Throwable $e) {
                // Keep going; upload will report a clear error if the folder is missing.
            }
        }

        return $dir;
    }
}

if (! function_exists('website_media_disk_path')) {
    function website_media_disk_path(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $filename = basename($filename);
        $candidates = [
            public_path('website_media/'.$filename),
            base_path('public/website_media/'.$filename),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}

if (! function_exists('website_media_admin_url')) {
    function website_media_admin_url(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        return admin_asset('website_media/'.basename($filename));
    }
}

if (! function_exists('admin_build_serial')) {
    /**
     * Visible deploy marker on admin auth pages. Bump on each production release.
     */
    function admin_build_serial(): string
    {
        return '20260813-WEB-006';
    }
}

if (! function_exists('report_status_html')) {
    function report_status_html($status, $id = null): string
    {
        $raw = trim((string) $status);
        $key = strtolower($raw);
        $label = $raw !== '' ? strtoupper($raw) : '-';
        $cls = 'muted';

        if ($key === 'success') {
            $label = 'SUCCESS';
            $cls = 'success';
        } elseif (in_array($key, ['pending', 'under proces', 'under process', 'processing'], true)) {
            $label = 'PENDING';
            $cls = 'pending';
        } elseif (in_array($key, ['failed', 'failure'], true)) {
            $label = 'FAILURE';
            $cls = 'failure';
        } elseif (in_array($key, ['refunded', 'refund'], true)) {
            $label = 'REFUNDED';
            $cls = 'refunded';
        }

        $html = '<div class="rpt-status-wrap">';
        $html .= '<span class="rpt-status rpt-status--' . $cls . '">' . e($label) . '</span>';
        if ($id !== null && $id !== '') {
            $html .= '<span class="rpt-status-id">ID: ' . e($id) . '</span>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (! function_exists('ensure_api_partner_commission_columns')) {
    function ensure_api_partner_commission_columns(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('scheme_commissions')) {
            return;
        }
        if (! \Illuminate\Support\Facades\Schema::hasColumn('scheme_commissions', 'ap_amount_type')) {
            \Illuminate\Support\Facades\Schema::table('scheme_commissions', function ($table) {
                $table->string('ap_amount_type', 50)->nullable();
            });
        }
        if (! \Illuminate\Support\Facades\Schema::hasColumn('scheme_commissions', 'ap_amount_value')) {
            \Illuminate\Support\Facades\Schema::table('scheme_commissions', function ($table) {
                $table->decimal('ap_amount_value', 12, 4)->default(0);
            });
        }
    }
}

if (! function_exists('normalize_user_pin')) {
    function normalize_user_pin($pin): string
    {
        $digits = preg_replace('/\D/', '', (string) $pin);

        return str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('verify_user_pin')) {
    function verify_user_pin($stored, $entered): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        return hash_equals(normalize_user_pin($stored), normalize_user_pin($entered));
    }
}

if (! function_exists('ensure_user_visible_password_column')) {
    function ensure_user_visible_password_column(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'visible_password')) {
                return;
            }

            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `users` ADD COLUMN `visible_password` VARCHAR(255) NULL");
        } catch (\Throwable $e) {
        }
    }
}

if (! function_exists('user_password_update_fields')) {
    function user_password_update_fields(string $plain): array
    {
        ensure_user_visible_password_column();

        $fields = [
            'password' => \Illuminate\Support\Facades\Hash::make($plain),
            'updated_at' => now(),
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'visible_password')) {
                $fields['visible_password'] = $plain;
            }
        } catch (\Throwable $e) {
        }

        return $fields;
    }
}

if (! function_exists('filter_users_columns')) {
    function filter_users_columns(array $data): array
    {
        static $columns = null;

        if ($columns === null) {
            try {
                $columns = array_flip(\Illuminate\Support\Facades\Schema::getColumnListing('users'));
            } catch (\Throwable $e) {
                $columns = [];
            }
        }

        if ($columns === []) {
            return $data;
        }

        return array_intersect_key($data, $columns);
    }
}

if (! function_exists('email_brand')) {
    function email_brand(): array
    {
        $company = null;
        try {
            $host = '';
            try {
                $host = (string) request()->getHost();
            } catch (\Throwable $e) {
            }
            $query = \Illuminate\Support\Facades\DB::table('companies')->where('status', '1');
            if ($host !== '') {
                $company = (clone $query)->where('domain', $host)->first();
            }
            $company = $company ?: $query->first();
        } catch (\Throwable $e) {
        }

        $name = $company?->company_name ?: 'NETCELL PAY';
        $supportEmail = (string) ($company?->support_email ?? '');
        $supportPhone = (string) ($company?->support_number ?? '');
        $domain = (string) ($company?->domain ?: 'netcellpay.in');
        $website = $domain;
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://'.$website;
        }

        $logo = '';
        $logoFile = (string) ($company?->company_logo ?? '');
        if ($logoFile !== '') {
            if (function_exists('admin_company_logo')) {
                $logo = (string) admin_company_logo($logoFile);
            } else {
                $adminHost = rtrim((string) env('ADMIN_HOST', ''), '/');
                if ($adminHost !== '') {
                    $logo = $adminHost.'/company_logo/'.ltrim($logoFile, '/');
                }
            }
        }

        return [
            'name' => $name,
            'logo' => $logo,
            'support_email' => $supportEmail,
            'support_phone' => $supportPhone,
            'website' => $website,
            'year' => date('Y'),
        ];
    }
}

if (! function_exists('email_body_html')) {
    function email_body_html(?string $msg): string
    {
        $msg = trim((string) $msg);
        if ($msg === '') {
            return '';
        }

        if ($msg !== strip_tags($msg)) {
            return preg_replace('#<(script|iframe|object|embed)[^>]*>.*?</\1>#is', '', $msg) ?: '';
        }

        $html = nl2br(e($msg), false);
        $html = preg_replace(
            '/(OTP(?:\s*(?:is|:|-))?\s*)(\d{4,8})/i',
            '$1<span style="display:inline-block;margin:10px 0;padding:12px 20px;border-radius:14px;background:#f4f1ff;border:1px solid #ddd8ff;color:#34308f;font-size:26px;font-weight:800;letter-spacing:6px;line-height:1;">$2</span>',
            $html,
            1
        );

        return $html;
    }
}

if (! function_exists('recharge_report_money')) {
    function recharge_report_money($value): string
    {
        $v = (float) $value;
        if (abs($v - round($v)) < 0.00001) {
            return number_format($v, 0);
        }

        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}

if (! function_exists('admin_can')) {
    function admin_can(string $key): bool
    {
        return \App\Services\AdminMenuService::can($key);
    }
}