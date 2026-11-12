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


    public static function ensureOperatorAmountColumns(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('providers')) {
            return;
        }

        $schema = \Illuminate\Support\Facades\Schema::class;
        $addMin = ! $schema::hasColumn('providers', 'min_amount');
        $addMax = ! $schema::hasColumn('providers', 'max_amount');
        if ($addMin) {
            $schema::table('providers', function ($table) {
                $table->decimal('min_amount', 12, 2)->default(1);
            });
        }
        if ($addMax) {
            $schema::table('providers', function ($table) {
                $table->decimal('max_amount', 12, 2)->default(10000);
            });
        }
        if ($addMin && $schema::hasColumn('providers', 'minium_amount')) {
            DB::statement('UPDATE providers SET min_amount = CASE WHEN minium_amount > 0 THEN minium_amount ELSE 1 END');
        }
        if ($addMax && $schema::hasColumn('providers', 'maxium_amount')) {
            DB::statement('UPDATE providers SET max_amount = CASE WHEN maxium_amount > 0 THEN maxium_amount ELSE 10000 END');
        }
        if ($addMin || $addMax) {
            DB::statement('UPDATE providers SET max_amount = min_amount WHERE max_amount < min_amount');
        }
    }

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
        $api = DB::table('apis')->where('id', $api_id)->first();

        if ($api && (int) $api->status === 1) {
            $apicom = DB::table('api_commissions')
                ->where('provider_id', $provider_id)
                ->where('api_id', $api_id)
                ->first();
            if ($apicom) {
                if (($apicom->amount_type ?? '') == 'Commission Percent') {
                    $commission = $amount * $apicom->amount_value / 100;
                } else {
                    $commission = $apicom->amount_value;
                }

                if ($commission == null) {
                    $commission = 0;
                }
            } else {
                $commission = 0;
            }
        } else {
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

    public static function ensureFailToSuccessTable(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('supplier_fail_to_success')) {
            return;
        }
        \Illuminate\Support\Facades\Schema::create('supplier_fail_to_success', function ($table) {
            $table->id();
            $table->string('recharge_id', 100)->nullable();
            $table->unsignedBigInteger('report_id')->nullable()->index();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('number', 30)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('last_api_id')->nullable();
            $table->unsignedBigInteger('response_api_id')->nullable();
            $table->text('response')->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamp('recharge_time')->nullable();
            $table->timestamp('response_time')->nullable();
            $table->timestamps();
        });
    }

    public static function recordFailToSuccess($report, string $source = 'manual', string $response = '', string $remark = ''): void
    {
        try {
            self::ensureFailToSuccessTable();
            if (is_numeric($report) || is_string($report)) {
                $report = DB::table('reports')->where('id', $report)->first();
            }
            if (! $report) {
                return;
            }
            if (DB::table('supplier_fail_to_success')->where('report_id', $report->id)->exists()) {
                return;
            }
            DB::table('supplier_fail_to_success')->insert([
                'recharge_id' => $report->order_id,
                'report_id' => $report->id,
                'provider_id' => $report->provider_id,
                'number' => $report->number,
                'amount' => $report->amount,
                'last_api_id' => $report->api_id,
                'response_api_id' => $report->api_id,
                'response' => $response !== '' ? $response : $source,
                'remark' => $remark !== '' ? $remark : 'Failed/Refunded changed to Success',
                'recharge_time' => $report->created_at,
                'response_time' => \Carbon\Carbon::now(),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function sanitizeOperatorId($raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || strcasecmp($raw, 'null') === 0) {
            return '';
        }
        $raw = trim($raw, " \t\n\r\0\x0B\"',");
        if (preg_match('/^([A-Za-z0-9][A-Za-z0-9._-]{0,120})/', $raw, $m)) {
            return $m[1];
        }

        return mb_substr($raw, 0, 120);
    }

    public static function isUnusableCallbackUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }
        $bad = [
            'callbackurl.com', 'www.callbackurl.com',
            'example.com', 'www.example.com',
            'test.com', 'www.test.com',
            'localhost', '127.0.0.1',
            'yourdomain.com', 'your-callback-url.com',
        ];

        return in_array($host, $bad, true);
    }

    public static function sendApiPartnerRechargeCallback($report, bool $force = false): bool
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
        if ((int) ($report->callback_status ?? 0) === 1 && ! $force) {
            return false;
        }

        $user = DB::table('users')->where('id', $report->user_id)->first(['callback_url', 'wallet_balance']);
        $base = trim((string) ($user->callback_url ?? ''));
        if ($user === null || $base === '' || ! filter_var($base, FILTER_VALIDATE_URL) || self::isUnusableCallbackUrl($base)) {
            $reason = ($user === null || $base === '')
                ? 'Partner callback URL missing'
                : (! filter_var($base, FILTER_VALIDATE_URL)
                    ? 'Partner callback URL invalid'
                    : 'Partner callback URL is a placeholder ('.$base.') — update user Callback URL');
            try {
                DB::table('apilogs')->insert([
                    'url' => $base !== '' ? $base : '(empty)',
                    'modal' => 'USER_RECHARGE_CALLBACK',
                    'txnid' => (string) ($report->order_id ?? $report->id),
                    'header' => json_encode([]),
                    'request' => 'SKIP '.$reason,
                    'response' => json_encode(['curl_error' => $reason, 'http_code' => 0], JSON_UNESCAPED_SLASHES),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
            return false;
        }

        $operatorId = self::sanitizeOperatorId($report->operator_id ?? '');
        if ($operatorId === '' && ! empty($report->api_operator_id)) {
            $operatorId = self::sanitizeOperatorId($report->api_operator_id);
        }
        $orderId = (string) ($report->order_id ?? '');
        if ($operatorId !== '' && $orderId !== '' && strcasecmp($operatorId, $orderId) === 0) {
            $alt = self::sanitizeOperatorId($report->api_operator_id ?? '');
            $operatorId = ($alt !== '' && strcasecmp($alt, $orderId) !== 0) ? $alt : '';
        }

        $query = http_build_query([
            'request_order_id' => (string) ($report->request_order_id ?? ''),
            'status' => (string) $report->status,
            'amount' => (string) ($report->total_amount ?? $report->amount),
            'order_id' => $orderId,
            'operator_id' => $operatorId,
            'balance' => round((float) ($user->wallet_balance ?? 0), 2),
        ]);
        $url = $base.(str_contains($base, '?') ? '&' : '?').$query;
        $result = self::curl($url, 'GET', '', [], 'yes', 'USER_RECHARGE_CALLBACK', (string) ($report->order_id ?? $report->id), true);
        $respBody = $result['response'] ?? null;
        $respErr = trim((string) ($result['error'] ?? ''));
        $respCode = (int) ($result['code'] ?? 0);
        $delivered = ($respErr === '' && $respCode >= 200 && $respCode < 300);
        if ($respBody === false || $respBody === null || $respBody === '') {
            $storedResp = $respErr !== ''
                ? json_encode(['curl_error' => $respErr, 'http_code' => $respCode], JSON_UNESCAPED_SLASHES)
                : json_encode(['http_code' => $respCode], JSON_UNESCAPED_SLASHES);
        } else {
            $storedResp = is_string($respBody) ? $respBody : json_encode($respBody);
        }

        try {
            DB::table('reports')->where('id', $report->id)->update([
                'callback_status' => $delivered ? 1 : 0,
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }

        try {
            $extra = [];
            if ($delivered && \Illuminate\Support\Facades\Schema::hasColumn('reports', 'api_partner_call_back_url')) {
                $extra['api_partner_call_back_url'] = mb_substr($url, 0, 1900);
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('reports', 'api_partner_callback_response')) {
                $extra['api_partner_callback_response'] = mb_substr((string) $storedResp, 0, 20000);
            }
            if ($extra !== []) {
                $extra['updated_at'] = Carbon::now();
                DB::table('reports')->where('id', $report->id)->update($extra);
            }
        } catch (\Throwable $e) {
        }

        return $delivered;
    }

    public static function curl($url, $method = 'GET', $parameters = null, $header = [], $log = "no", $modal = "none", $txnid = "none", $followRedirects = false)
    {   
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        if ($followRedirects) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }
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
        if ($log != 'no') {
            try {
                $body = $response;
                if (($body === false || $body === null || $body === '') && $err) {
                    $body = json_encode(['curl_error' => $err, 'http_code' => $code], JSON_UNESCAPED_SLASHES);
                } elseif (is_string($body)) {
                    $body = mb_substr($body, 0, 20000);
                }
                // For GET partner callbacks, store URL query as request when body is empty
                $req = is_array($parameters) ? json_encode($parameters) : (string) $parameters;
                if (trim($req) === '' && is_string($url) && str_contains($url, '?')) {
                    $req = 'GET '.$url;
                }
                $redact = static function (string $s): string {
                    return (string) preg_replace(
                        '/((?:api[_-]?key|api[_-]?password|apitoken|username|userid|token|password|ApiPassword|Tokenid|Token|UserID))=[^&]*/i',
                        '$1=[REDACTED]',
                        $s
                    );
                };
                DB::table('apilogs')->insert([
                    'url' => $redact($url),
                    'modal' => $modal,
                    'txnid' => $txnid,
                    'header' => is_array($header) ? json_encode($header) : (string) $header,
                    'request' => $redact($req),
                    'response' => (string) $body,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return ["response" => $response, "error" => $err, 'code' => $code];
    }
    /**
     * Replace API URL placeholders including retailer identity (for backup/failover).
     */
    public static function applyApiUrlPlaceholders(string $url, $api, $report, $providerCode = '', $stateCode = '', array $extra = []): string
    {
        $user = null;
        if ($report && ! empty($report->user_id)) {
            try {
                $user = DB::table('users')->where('id', (int) $report->user_id)
                    ->first(['outlet_name', 'mobile_number', 'first_name', 'last_name']);
            } catch (\Throwable $e) {
                $user = null;
            }
        }
        $outlet = trim((string) ($user->outlet_name ?? ''));
        $mobile = trim((string) ($user->mobile_number ?? ''));
        $agent = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($agent === '') {
            $agent = $outlet !== '' ? $outlet : $mobile;
        }
        $replacements = [
            '{API_USERNAME}' => (string) ($api->api_username ?? ''),
            '{API_PASSWORD}' => (string) ($api->api_password ?? ''),
            '{API_KEY}' => (string) ($api->api_key ?? ''),
            '{NUMBER}' => (string) ($report->number ?? ''),
            '{MN}' => (string) ($report->number ?? ''),
            '{PROVIDER_CODE}' => (string) $providerCode,
            '{STATE_CODE}' => (string) $stateCode,
            '{AMOUNT}' => (string) ($report->total_amount ?? $report->amount ?? ''),
            '{ORDER_ID}' => (string) ($report->order_id ?? ''),
            '{REQUEST_ORDER_ID}' => (string) ($report->request_order_id ?? $report->order_id ?? ''),
            '{OUTLET_NAME}' => $outlet,
            '{USER_MOBILE}' => $mobile,
            '{USER_ID}' => (string) ($report->user_id ?? ''),
            '{AGENT_NAME}' => $agent,
            '{REQID}' => self::outboundRequestId($api, $report),
            '{SUBJECT}' => rawurlencode((string) ($extra['subject'] ?? '')),
        ];
        if (self::isRealChargeApi($api, $url)) {
            $replacements['{ORDER_ID}'] = $replacements['{REQID}'];
        }

        return str_replace(array_keys($replacements), array_values($replacements), $url);
    }

    public static function isRealChargeApi($api, string $url = ''): bool
    {
        $hay = strtolower(trim((string) ($api->api_name ?? '').' '.(string) ($api->api_url ?? '').' '.$url));

        return str_contains($hay, 'realcharge')
            || str_contains($hay, 'reqid=')
            || str_contains($hay, '{reqid}');
    }

    public static function realChargeRequestId($report): string
    {
        $id = (int) ($report->id ?? 0);
        if ($id < 1) {
            return 'RC'.strtoupper(substr(md5((string) ($report->order_id ?? '0')), 0, 18));
        }
        $code = 'RC'.(string) $id;

        return strlen($code) <= 20 ? $code : substr($code, 0, 20);
    }

    public static function loggedSupplierReqId($report): string
    {
        $orderId = trim((string) ($report->order_id ?? ''));
        if ($orderId === '' || ! \Illuminate\Support\Facades\Schema::hasTable('apilogs')) {
            return '';
        }
        try {
            $rows = DB::table('apilogs')->where('txnid', $orderId)->orderBy('id')->limit(8)->get(['url', 'request']);
        } catch (\Throwable $e) {
            return '';
        }
        foreach ($rows as $row) {
            $hay = (string) ($row->url ?? '').' '.(string) ($row->request ?? '');
            if (preg_match('/(?:^|[?&])reqid=([^&\s]+)/i', $hay, $m)) {
                $req = rawurldecode($m[1]);
                if ($req !== '' && $req[0] !== '{' && stripos($req, 'redact') === false && stripos($req, 'hidden') === false) {
                    return substr($req, 0, 40);
                }
            }
        }

        return '';
    }

    public static function outboundRequestId($api, $report): string
    {
        $logged = self::loggedSupplierReqId($report);
        if ($logged !== '') {
            return $logged;
        }
        if (self::isRealChargeApi($api, '')) {
            return self::realChargeRequestId($report);
        }

        return (string) ($report->order_id ?? '');
    }

    public static function realChargeDerivedUrl($api, string $method): ?string
    {
        $template = trim((string) ($api->api_url ?? ''));
        if (! self::isRealChargeApi($api, $template)) {
            return null;
        }
        $parts = parse_url($template);
        if (! is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }
        if (! preg_match('#/apiservice\.asmx/#i', (string) $parts['path'])) {
            return null;
        }
        $path = preg_replace('#/[^/?]+$#', '/'.$method, (string) $parts['path']);
        if (! is_string($path) || strcasecmp($path, (string) $parts['path']) === 0) {
            return null;
        }
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $token = '{API_KEY}';
        foreach ($query as $key => $value) {
            if (strcasecmp((string) $key, 'apiToken') === 0 && (string) $value !== '') {
                $token = (string) $value;
                break;
            }
        }
        $pairs = ['apiToken='.$token, 'reqid={REQID}'];
        if (strcasecmp($method, 'RegisterComplaint') === 0) {
            $pairs[] = 'reason={SUBJECT}';
        }
        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$parts['host'].$port.$path.'?'.implode('&', $pairs);
    }

    public static function supplierPayload(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [];
        }
        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }
        if ($body[0] !== '<') {
            return [];
        }
        $out = [];
        $stripped = preg_replace('/\sxmlns(?::[A-Za-z0-9_]+)?="[^"]*"/i', '', $body) ?? $body;
        $xml = @simplexml_load_string($stripped, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml) {
            $children = $xml->children();
            if (count($children) === 0) {
                foreach ($xml->getDocNamespaces(true) as $ns) {
                    $children = $xml->children($ns);
                    if (count($children) > 0) {
                        break;
                    }
                }
            }
            foreach ($children as $child) {
                $name = (string) $child->getName();
                $value = trim((string) $child);
                $out[$name] = $value;
                $out[strtolower($name)] = $value;
            }
            if ($out === []) {
                $text = trim((string) $xml);
                if ($text !== '') {
                    $out['message'] = $text;
                    $out['status'] = $text;
                }
            }
        }
        if (! isset($out['status']) && preg_match_all('/<([A-Za-z0-9_:-]+)>([^<]*)<\/\1>/', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                if (str_contains($name, ':')) {
                    $name = substr($name, (int) strrpos($name, ':') + 1);
                }
                $value = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_XML1, 'UTF-8'));
                $out[$name] = $value;
                $out[strtolower($name)] = $value;
            }
        }

        return $out;
    }

    public static function isRealChargePayload(array $data): bool
    {
        $keys = array_change_key_case($data, CASE_LOWER);

        return array_key_exists('status', $keys)
            && (array_key_exists('reqid', $keys) || array_key_exists('field1', $keys) || array_key_exists('ec', $keys));
    }

    public static function mapRealChargeStatus($actual): ?string
    {
        $word = strtoupper(trim((string) $actual));
        if (in_array($word, ['SUCCESS', 'SUCCESSFUL', 'SUCCESSFULL'], true)) {
            return 'Success';
        }
        if (in_array($word, ['FAILED', 'FAILURE', 'FAIL', 'REFUND', 'REFUNDED'], true)) {
            return 'Failed';
        }
        if (in_array($word, ['PENDING', 'PROCESS', 'PROCESSING', 'ACCEPTED'], true)) {
            return 'Pending';
        }

        return null;
    }

    public static function realChargeStatusDecision($api, $report, array $data, $actual): ?string
    {
        if (! self::isRealChargeApi($api, '') && ! self::isRealChargePayload($data)) {
            return null;
        }
        $remark = strtolower(trim((string) ($data['remark'] ?? $data['message'] ?? $data['Message'] ?? '')));
        if (preg_match('/not found|invalid req|no record|no transaction|does not exist|no recharge/', $remark)) {
            return 'Pending';
        }
        $forced = self::mapRealChargeStatus($actual);
        $responseReq = strtoupper(trim((string) ($data['reqid'] ?? $data['REQID'] ?? '')));
        $sent = strtoupper(self::outboundRequestId($api, $report));
        if ($responseReq !== '' && $sent !== '' && $responseReq !== $sent) {
            return 'Pending';
        }
        if ($forced === 'Failed' && $responseReq === '') {
            return 'Pending';
        }

        return $forced ?? 'Pending';
    }

    public static function isMroboticsApi($api, string $url = ''): bool
    {
        $hay = strtolower(trim((string) ($api->api_name ?? '').' '.(string) ($api->api_url ?? '').' '.$url));

        return str_contains($hay, 'mrobotics');
    }

    public static function isMroboticsPayload(array $data): bool
    {
        $keys = array_change_key_case($data, CASE_LOWER);

        return array_key_exists('status', $keys)
            && (array_key_exists('tnx_id', $keys) || array_key_exists('lapu_id', $keys) || array_key_exists('response', $keys));
    }

    public static function mroboticsSessionMessage(string $text): bool
    {
        $text = strtolower($text);

        return str_contains($text, 'session is not valid') || str_contains($text, 'please login again');
    }

    public static function mroboticsOutcome(array $data): ?array
    {
        if (! self::isMroboticsPayload($data)) {
            return null;
        }
        $keys = array_change_key_case($data, CASE_LOWER);
        $statusWord = strtolower(trim((string) ($keys['status'] ?? '')));
        $reason = trim((string) ($keys['response'] ?? $keys['message'] ?? $keys['errormessage'] ?? ''));
        $tnx = trim((string) ($keys['tnx_id'] ?? $keys['txn_id'] ?? ''));
        $lapu = trim((string) ($keys['lapu_id'] ?? ''));
        $providerTxn = trim((string) ($keys['opid'] ?? $keys['operator_id'] ?? $keys['operatorid'] ?? $keys['provider_txn'] ?? $tnx));
        $session = self::mroboticsSessionMessage($reason.' '.$statusWord);
        $status = 'Pending';
        $final = false;
        if (! $session && in_array($statusWord, ['success', 'successful', 'successfull'], true)) {
            $status = 'Success';
        } elseif (! $session && in_array($statusWord, ['failure', 'failed', 'fail'], true)) {
            $status = 'Failed';
            $final = true;
        }

        return [
            'status' => $status,
            'session' => $session,
            'final_failure' => $final,
            'reason' => $reason,
            'tnx_id' => $tnx,
            'lapu_id' => $lapu,
            'provider_txn' => $providerTxn,
        ];
    }

    public static function mroboticsRemark(string $prefix, array $outcome): string
    {
        $parts = [];
        if (($outcome['reason'] ?? '') !== '') {
            $parts[] = $outcome['reason'];
        }
        if (($outcome['tnx_id'] ?? '') !== '') {
            $parts[] = 'tnx_id='.$outcome['tnx_id'];
        }
        if (($outcome['lapu_id'] ?? '') !== '') {
            $parts[] = 'lapu_id='.$outcome['lapu_id'];
        }
        if (($outcome['provider_txn'] ?? '') !== '' && ($outcome['provider_txn'] ?? '') !== ($outcome['tnx_id'] ?? '')) {
            $parts[] = 'provider_txn='.$outcome['provider_txn'];
        }
        $text = trim($prefix.($parts !== [] ? ' - '.implode(' | ', $parts) : ''));

        return mb_substr($text, 0, 240);
    }

    public static function alertMroboticsSession($report, string $reason): void
    {
        $orderId = trim((string) ($report->order_id ?? ''));
        $text = 'MRobotics session expired. Contact MRobotics to login again. Do not send this recharge again until they restore the session.'
            .($orderId !== '' ? ' Order '.$orderId.'.' : '')
            .($reason !== '' ? ' '.$reason : '');
        $text = mb_substr($text, 0, 500);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('apilogs')) {
                DB::table('apilogs')->insert([
                    'url' => 'mrobotics-session',
                    'modal' => 'MROBOTICS_SESSION',
                    'txnid' => $orderId !== '' ? $orderId : (string) ($report->id ?? ''),
                    'header' => '',
                    'request' => '',
                    'response' => $text,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            }
        } catch (\Throwable $e) {
        }
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('messages')) {
                return;
            }
            DB::table('messages')->insert([
                'user_id' => 1,
                'to_user_id' => 1,
                'subject' => 'MRobotics session expired',
                'msg_source' => 'SYSTEM',
                'template_id' => '',
                'content' => $text,
                'status' => 0,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function ApiProviderCode($api_id, $provider_id)
    {
        // Use query builder — App\Models\Api / ApiProviderCode are not present in this app.
        $api = DB::table('apis')->where('id', $api_id)->first();

        if ($api && (int) $api->status === 1) {
            $api_provider_code = DB::table('api_provider_codes')
                ->where('provider_id', $provider_id)
                ->where('api_id', $api_id)
                ->first();
            if ($api_provider_code && $api_provider_code->provider_code !== null && $api_provider_code->provider_code !== '') {
                return $api_provider_code->provider_code;
            }
        }

        return 0;
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

    public static function isHardApiReject($actual, $payload = null): bool
    {
        $status = strtolower(trim((string) $actual));
        $msg = '';
        if (is_array($payload)) {
            $msg = strtolower((string) ($payload['message'] ?? $payload['Message'] ?? $payload['msg'] ?? $payload['error'] ?? ''));
        } elseif (is_string($payload)) {
            $msg = strtolower($payload);
        }
        if ($msg !== '') {
            foreach ([
                'insufficient balance',
                'insufficient fund',
                'low balance',
                'not enough balance',
                'request rejected',
                'balance is 0',
                'zero balance',
            ] as $needle) {
                if (str_contains($msg, $needle)) {
                    return true;
                }
            }
        }

        return in_array($status, ['error', 'failed', 'failure', 'fail'], true);
    }

    public static function mapApiLiveStatus($api, $actual, $payload = null): ?string
    {
        if (self::apiValueMatches($actual, $api->success_value ?? '')) {
            return self::apiSwitchOn($api, 'success_switch') ? 'Success' : 'Pending';
        }
        if (self::isHardApiReject($actual, $payload)) {
            return 'Failed';
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

if (! function_exists('user_portal_base_url')) {
    /**
     * Public user/portal origin for supplier callbacks (USER_HOST).
     * Always strips /admin paths — never returns admin panel URL.
     */
    function user_portal_base_url(): string
    {
        foreach ([
            (string) env('USER_HOST', ''),
            (string) config('app.user_host', ''),
        ] as $raw) {
            $host = self_normalize_portal_base_url($raw);
            if ($host !== '') {
                return $host;
            }
        }

        // Last resort: APP_URL origin only — normalize drops /admin path.
        return self_normalize_portal_base_url((string) config('app.url', ''));
    }
}

if (! function_exists('self_normalize_portal_base_url')) {
    /**
     * Keep scheme + host (+ port) only. Drops /admin and any other path.
     */
    function self_normalize_portal_base_url(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $raw)) {
            $raw = 'https://'.$raw;
        }

        $parts = parse_url($raw);
        if (! is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'https';
        }

        $base = $scheme.'://'.$parts['host'];
        if (! empty($parts['port'])) {
            $base .= ':'.$parts['port'];
        }

        return $base;
    }
}

if (! function_exists('user_portal_callback_url')) {
    function user_portal_callback_url(string $path, $apiId = null): string
    {
        $base = user_portal_base_url();
        $path = trim($path);
        $path = '/'.ltrim($path, '/');
        $path = preg_replace('#^/admin/#i', '/', $path) ?? $path;

        if ($apiId !== null && $apiId !== '') {
            $path = rtrim($path, '/').'/'.rawurlencode((string) $apiId);
        }

        $url = $base === '' ? $path : ($base.$path);

        // Final hard guard — callback URLs must never include /admin/.
        $url = preg_replace('#/admin(?=/recharge-callback|/complaint-callback)#i', '', $url) ?? $url;
        $url = preg_replace('#(?<!:)/{2,}#', '/', $url) ?? $url;
        $url = preg_replace('#^(https?:)/+#i', '$1//', $url) ?? $url;

        if (stripos($url, '/admin/') !== false) {
            $url = str_ireplace('/admin/', '/', $url);
            $url = preg_replace('#(?<!:)/{2,}#', '/', $url) ?? $url;
            $url = preg_replace('#^(https?:)/+#i', '$1//', $url) ?? $url;
        }

        return $url;
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
        return '20261018-WEB-036';
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

if (! function_exists('user_password_from_mobile')) {
    function user_password_from_mobile($mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if (strlen($digits) >= 8) {
            return substr($digits, -8);
        }

        return str_pad($digits !== '' ? $digits : '0', 8, '0', STR_PAD_LEFT);
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

if (! function_exists('admin_redact_secrets')) {
    function admin_redact_secrets(string $text): string
    {
        $text = preg_replace(
            '/((?:^|[?&\s])(?:api[_-]?token|apiToken|apitoken|token|password|passwd|apikey|api_key|key|secret)=)[^&\s<"\']+/i',
            '$1[hidden]',
            $text
        ) ?? $text;

        return $text;
    }
}

if (! function_exists('admin_is_mrobotics_lapu')) {
    function admin_is_mrobotics_lapu(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return str_contains($host, 'mrobotics.') && str_contains($path, 'lapu_balance');
    }
}

if (! function_exists('admin_mrobotics_api_token')) {
    /**
     * @return array{token: string, error: string}
     */
    function admin_mrobotics_api_token(object $api, string $template): array
    {
        $token = trim((string) ($api->api_key ?? ''));
        if ($token === '' && str_contains($template, '{API_PASSWORD}')) {
            $token = trim((string) ($api->api_password ?? ''));
        }
        if ($token === '') {
            return ['token' => '', 'error' => 'MRobotics API token is empty. Save the provider token in API Key.'];
        }
        if (str_contains($token, '{') || str_contains($token, '}')) {
            return ['token' => '', 'error' => 'MRobotics API token is still a placeholder. Save the provider token in API Key.'];
        }
        if (preg_match('/%(?![0-9A-Fa-f]{2})/', $token)) {
            return ['token' => '', 'error' => 'MRobotics API token is not encoded correctly. Save the full provider token again.'];
        }
        if (str_contains($token, '%')) {
            $decoded = rawurldecode($token);
            if ($decoded === '' || preg_match('/%(?![0-9A-Fa-f]{2})/', $decoded) || str_contains($decoded, '%')) {
                return ['token' => '', 'error' => 'MRobotics API token is not encoded correctly. Save the full provider token again.'];
            }
            $token = $decoded;
        }
        $token = trim($token);
        if ($token === '' || preg_match('/\s/u', $token) || str_ends_with($token, '...') || str_ends_with($token, '…')) {
            return ['token' => '', 'error' => 'MRobotics API token looks empty or truncated. Save the full provider token in API Key.'];
        }

        return ['token' => $token, 'error' => ''];
    }
}

if (! function_exists('admin_mrobotics_lapu_id')) {
    function admin_mrobotics_lapu_id(string $template): string
    {
        $query = [];
        parse_str((string) parse_url($template, PHP_URL_QUERY), $query);
        foreach ($query as $key => $value) {
            if (strcasecmp((string) $key, 'lapu_id') === 0 && preg_match('/^[1-9][0-9]*$/', trim((string) $value))) {
                return (string) (int) $value;
            }
        }

        return '1';
    }
}

if (! function_exists('admin_fetch_live_balance')) {
    /**
     * @return array{ok: bool, message: string, balance: string, raw: string, log_url: string, log_request: string}
     */
    function admin_fetch_live_balance(object $api): array
    {
        $template = trim((string) ($api->balance_check_url ?? ''));
        if ($template === '' || $template === '0') {
            return ['ok' => false, 'message' => 'Balance check URL not set', 'balance' => '', 'raw' => '', 'log_url' => '', 'log_request' => ''];
        }
        if (admin_is_mrobotics_lapu($template)) {
            return admin_fetch_mrobotics_lapu_balance($api, $template);
        }

        $url = str_replace(
            ['{API_USERNAME}', '{API_PASSWORD}', '{API_KEY}'],
            [(string) ($api->api_username ?? ''), (string) ($api->api_password ?? ''), (string) ($api->api_key ?? '')],
            $template
        );
        $method = strtoupper(trim((string) ($api->api_method ?: 'GET')));
        if (! in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }
        $body = admin_balance_http($url, $method, '', []);
        $parsed = admin_live_balance($body['response'] !== '' ? $body['response'] : ($body['error'] ?: 'Empty response'));

        return [
            'ok' => true,
            'message' => '',
            'balance' => $parsed['balance'],
            'raw' => $parsed['raw'],
            'log_url' => admin_redact_secrets($url),
            'log_request' => '',
        ];
    }
}

if (! function_exists('admin_fetch_mrobotics_lapu_balance')) {
    function admin_fetch_mrobotics_lapu_balance(object $api, string $template): array
    {
        $token = admin_mrobotics_api_token($api, $template);
        if ($token['error'] !== '') {
            return ['ok' => false, 'message' => $token['error'], 'balance' => $token['error'], 'raw' => $token['error'], 'log_url' => '', 'log_request' => ''];
        }
        $lapuId = admin_mrobotics_lapu_id($template);
        $scheme = 'https';
        $host = (string) parse_url($template, PHP_URL_HOST);
        $path = (string) (parse_url($template, PHP_URL_PATH) ?: '/api/lapu_balance');
        $url = $scheme.'://'.$host.$path;
        $form = http_build_query([
            'api_token' => $token['token'],
            'lapu_id' => $lapuId,
        ], '', '&', PHP_QUERY_RFC3986);
        $result = admin_balance_http($url, 'POST', $form, ['Content-Type: application/x-www-form-urlencoded']);
        $response = $result['response'] !== '' ? $result['response'] : ($result['error'] ?: 'Empty response');
        $parsed = admin_live_balance($response);
        $json = json_decode($response, true);
        $reportedMethod = is_array($json) ? trim((string) ($json['current_method'] ?? '')) : '';
        $raw = $parsed['raw'];
        if ($reportedMethod !== '' && ! str_contains(strtolower($raw), 'current_method')) {
            $raw = 'current_method: '.$reportedMethod.'; '.$raw;
        }

        return [
            'ok' => true,
            'message' => '',
            'balance' => $parsed['balance'],
            'raw' => mb_substr(admin_redact_secrets($raw), 0, 500),
            'log_url' => $url,
            'log_request' => 'lapu_id='.$lapuId.'; content-type=application/x-www-form-urlencoded',
        ];
    }
}

if (! function_exists('admin_balance_http')) {
    function admin_balance_http(string $url, string $method, string $body, array $headers): array
    {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $body;
            if (defined('CURL_REDIR_POST_ALL')) {
                $options[CURLOPT_POSTREDIR] = CURL_REDIR_POST_ALL;
            }
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'response' => ($response !== false && $response !== '') ? (string) $response : '',
            'error' => (string) $error,
        ];
    }
}

if (! function_exists('admin_live_balance')) {
    /**
     * Balance cell text for the API Live Balance table.
     * JSON stays as-is for Bizpay. RealCharge Wallet XML uses Wallet.main when status is SUCCESS.
     *
     * @return array{balance: string, raw: string}
     */
    function admin_live_balance(string $raw): array
    {
        $raw = trim($raw);
        $safeRaw = mb_substr(admin_redact_secrets($raw), 0, 500);

        $json = json_decode($raw, true);
        if (is_array($json)) {
            $safeRaw = mb_substr(admin_redact_secrets(admin_balance_json_text($json)), 0, 500);
            $found = admin_live_balance_numeric($json, ['balance', 'Balance', 'wallet', 'Wallet', 'available_balance', 'AvailableBalance', 'bal', 'amount']);
            if ($found !== null) {
                return ['balance' => '₹ '.number_format($found, 2), 'raw' => $safeRaw];
            }
            $message = '';
            foreach (['message', 'Message', 'errorMessage', 'remark'] as $messageKey) {
                if (isset($json[$messageKey]) && is_string($json[$messageKey]) && trim($json[$messageKey]) !== '') {
                    $message = trim($json[$messageKey]);
                    break;
                }
            }
            if ($message === '' && isset($json['error']) && is_string($json['error'])) {
                $message = trim($json['error']);
            }
            if ($message !== '') {
                return ['balance' => $message, 'raw' => $safeRaw];
            }
        }

        if (preg_match('/"(?:balance|wallet|available_balance)"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)/i', $raw, $m)) {
            return ['balance' => '₹ '.number_format((float) $m[1], 2), 'raw' => $safeRaw];
        }

        $wallet = admin_realcharge_wallet($raw);
        if ($wallet !== null) {
            $status = strtoupper(trim((string) ($wallet['status'] ?? '')));
            $remark = trim((string) ($wallet['remark'] ?? ''));
            $summary = 'status='.$status
                .'; main='.(string) ($wallet['main'] ?? '')
                .'; dmr='.(string) ($wallet['dmr'] ?? '')
                .($remark !== '' ? '; remark='.$remark : '');
            if ($status === 'SUCCESS' && isset($wallet['main']) && is_numeric($wallet['main'])) {
                return ['balance' => '₹ '.number_format((float) $wallet['main'], 2), 'raw' => $summary];
            }
            if ($status !== '' && $status !== 'SUCCESS') {
                return ['balance' => $remark !== '' ? $remark : 'Balance check failed', 'raw' => $summary];
            }

            return ['balance' => 'Balance response could not be read', 'raw' => $summary];
        }

        if ($raw !== '' && $raw[0] === '<') {
            return ['balance' => 'Balance response could not be read', 'raw' => $safeRaw];
        }

        return ['balance' => 'See response', 'raw' => $safeRaw];
    }
}

if (! function_exists('admin_balance_json_text')) {
    function admin_balance_json_text(array $data): string
    {
        $clean = admin_balance_hide_secrets($data);
        $text = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($text) ? $text : '';
    }
}

if (! function_exists('admin_balance_hide_secrets')) {
    function admin_balance_hide_secrets(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/token|password|secret|api[_-]?key/i', $key)) {
                $out[$key] = '[hidden]';
                continue;
            }
            $out[$key] = is_array($value) ? admin_balance_hide_secrets($value) : $value;
        }

        return $out;
    }
}

if (! function_exists('admin_live_balance_numeric')) {
    function admin_live_balance_numeric(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float) $data[$key];
            }
        }
        foreach ($data as $val) {
            if (is_array($val)) {
                $n = admin_live_balance_numeric($val, $keys);
                if ($n !== null) {
                    return $n;
                }
            }
        }

        return null;
    }
}

if (! function_exists('admin_realcharge_wallet')) {
    function admin_realcharge_wallet(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '' || $raw[0] !== '<') {
            return null;
        }

        try {
            $stripped = preg_replace('/\sxmlns(?::[A-Za-z0-9_]+)?="[^"]*"/i', '', $raw) ?? $raw;
            $xml = @simplexml_load_string($stripped, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml) {
                $node = null;
                if (strcasecmp($xml->getName(), 'Wallet') === 0) {
                    $node = $xml;
                } else {
                    foreach ($xml->children() as $child) {
                        if (strcasecmp($child->getName(), 'Wallet') === 0) {
                            $node = $child;
                            break;
                        }
                    }
                }
                if ($node) {
                    $out = [];
                    foreach ($node->children() as $child) {
                        $out[strtolower($child->getName())] = trim((string) $child);
                    }
                    if (isset($out['status']) || isset($out['main'])) {
                        return $out;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        if (! preg_match('/<Wallet\b[^>]*>(.*?)<\/Wallet>/is', $raw, $block)) {
            return null;
        }
        $out = [];
        if (preg_match_all('/<([A-Za-z0-9_:-]+)>([^<]*)<\/\1>/', $block[1], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = strtolower($match[1]);
                if (str_contains($name, ':')) {
                    $name = substr($name, (int) strrpos($name, ':') + 1);
                }
                $out[$name] = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
        }

        return isset($out['status']) || isset($out['main']) ? $out : null;
    }
}

if (! function_exists('admin_can')) {
    function admin_can(string $key): bool
    {
        return \App\Services\AdminMenuService::can($key);
    }
}