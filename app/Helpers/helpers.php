<?php

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use Illuminate\Http\Request;

class helpers

{

    

    static function Pt($data){

        echo "<pre>";print_r($data);

    }

    static function Ptd($data){

        echo "<pre>";print_r($data);die;

    }

    /**
     * Keep only columns that exist on reports (avoids insert/update SQL errors on older DBs).
     */
    public static function filterReportColumns(array $data): array
    {
        static $columns = null;

        if ($columns === null) {
            $columns = array_flip(Schema::getColumnListing('reports'));
        }

        return array_intersect_key($data, $columns);
    }



    public static function refund_row($report){

        

        $report = DB::table('reports')->where('id', $report)->first();

        if (!$report) {
            return 0;
        }

        // Prevent double wallet credit when ProcessRecharge and rechargeCall both refund.
        $alreadyRefunded = DB::table('reports')
            ->where('order_id', $report->order_id)
            ->where('transaction_type', 'Refund')
            ->where('status', 'Success')
            ->exists();

        if ($alreadyRefunded) {
            return 0;
        }

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

        $refund['opening_balance'] = $user->wallet_balance;

        DB::table('users')->where('id', $report->user_id)->increment('wallet_balance', $report->amount);

        $user = DB::table('users')->where('id',$report->user_id)->first();

        $refund['closing_balance'] = $user->wallet_balance;  

        $refund['transaction_date'] = Carbon::now().":".rand(111,999);

        $refund['created_at'] = Carbon::now();    

        $refund['updated_at'] = Carbon::now();                  

        $report = DB::table('reports')->insertGetId(self::filterReportColumns($refund));

        return $report;

     }



     public function ReverseCommission($report)
     {
         $report = DB::table('reports')->where('id', $report)->first();
 
         $reports = DB::table('reports')->where('order_id', $report->order_id)->where('total_amount', $report->total_amount)->where('transaction_type', 'Commission')->get();
         if(!$reports){
             return 0;
         }
         //echo "<pre>";print_r($reports);die;
         foreach($reports as $report){
             $user = DB::table('users')->where('id', $report->user_id)->first();
             $data['user_id'] = $report->user_id;
 
              $data['parent__Id'] = $report->id;
 
              $data['order_id'] = $report->order_id;
 
              $data['number'] = $report->number;
 
              $data['amount'] = $report->amount;
 
              $data['total_amount'] = $report->total_amount;  
 
              $data['commission'] = $report->commission;
 
              $data['fund_type'] = "Debit";
 
              $data['transaction_type'] = "Reverse Commission";
 
              $data['provider_id'] = $report->provider_id;
 
              $data['service_id'] = $report->service_id;
 
              $data['api_id'] = $report->api_id;
 
              $data['remark'] = "Reverse Commission For Rs. ".$report->total_amount." Number ".$report->number." Recharge Refunded";
 
              $data['status'] = "Success";
 
              $data['path'] = $report->path;
 
              $data['ip_address'] = $report->ip_address;
 
              $data['opening_balance'] = $user->wallet_balance;
 
              $data['transaction_date'] = Carbon::now().":".rand(111,999);
 
              $data['created_at'] = Carbon::now();
 
              $data['updated_at'] = Carbon::now();
 
              DB::table('users')->where('id', $user->id)->decrement('wallet_balance', $report->commission);
 
              $user = DB::table('users')->where('id', $user->id)->first();
 
              $data['closing_balance'] = $user->wallet_balance;
 
              $update = DB::table('reports')->insertGetId(self::filterReportColumns($data));
         }
         return 0;
     }



     public static function SetCommission($report)

     {

        $report_id_com = $report;

         ///1 Level Commission

         $report = DB::table('reports')->where('id', $report)->first();

         $user = DB::table('users')->where('id', $report->user_id)->first();
         $scheme_id = $user->scheme_id;
 

         if($user->parent_id !=0 && $user->parent_id !=1){

             $parent = DB::table('users')->where('id', $user->parent_id)->first();

             //$commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

             //$commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

             //$commission =  $commission_parent - $commission_user;
             $commission_parent = \helpers::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
             $commission =  $commission_parent;

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

             $data['transaction_date'] = Carbon::now().":".rand(111,999);

             $data['created_at'] = Carbon::now();

             $data['updated_at'] = Carbon::now();

             DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

             $parent = DB::table('users')->where('id', $parent->id)->first();

             $data['closing_balance'] = $parent->wallet_balance;                    

             $report = DB::table('reports')->insertGetId(self::filterReportColumns($data));

             ////

             DB::table('reports')->where('id', $report_id_com)->update(['dt_commission' => $commission]);

             ///

             ///2 Level Commission

             $report = DB::table('reports')->where('id', $report)->first();

             $user = DB::table('users')->where('id', $report->user_id)->first();

             if($user->parent_id !=0 && $user->parent_id !=1){

                 $parent = DB::table('users')->where('id', $user->parent_id)->first();

                //  $commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

                //  $commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

                //  $commission =  $commission_parent - $commission_user;

                $commission_parent = \helpers::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
                $commission =  $commission_parent;

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

                 $data['transaction_date'] = Carbon::now().":".rand(111,999);

                 $data['created_at'] = Carbon::now();

                 $data['updated_at'] = Carbon::now();

                 DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

                 $parent = DB::table('users')->where('id', $parent->id)->first();

                 $data['closing_balance'] = $parent->wallet_balance;                    

                 $report = DB::table('reports')->insertGetId(self::filterReportColumns($data));

                 ////

                DB::table('reports')->where('id', $report_id_com)->update(['md_commission' => $commission]);

                ///

                 ///3 Level Commission

                 $report = DB::table('reports')->where('id', $report)->first();

                 $user = DB::table('users')->where('id', $report->user_id)->first();

                 if($user->parent_id !=0 && $user->parent_id !=1){

                     $parent = DB::table('users')->where('id', $user->parent_id)->first();

                    //  $commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

                    //  $commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

                    //  $commission =  $commission_parent - $commission_user;

                    $commission_parent = \helpers::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
                    $commission =  $commission_parent;

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

                     $data['transaction_date'] = Carbon::now().":".rand(111,999);

                     $data['created_at'] = Carbon::now();

                     $data['updated_at'] = Carbon::now();

                     DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

                     $parent = DB::table('users')->where('id', $parent->id)->first();

                     $data['closing_balance'] = $parent->wallet_balance;                    

                     $report = DB::table('reports')->insertGetId(self::filterReportColumns($data));

                     ////

                    DB::table('reports')->where('id', $report_id_com)->update(['wt_commission' => $commission]);

                    ///

                     ///4 Level Commission

                     $report = DB::table('reports')->where('id', $report)->first();

                     $user = DB::table('users')->where('id', $report->user_id)->first();     

                 }  

             }

         }

     }



    public static function getCommission($amount, $scheme, $provider_id, $role_id)

    {

        $commission = 0;

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('schemes') || !\Illuminate\Support\Facades\Schema::hasTable('scheme_commissions')) {
                return 0;
            }

            $myscheme = DB::table('schemes')->where('id', $scheme)->first();

            

            if($myscheme && $myscheme->status == "1"){

                $comdata = DB::table('scheme_commissions')->where('provider_id', $provider_id)->where('scheme_id', $scheme)->first();

                if ($comdata) {

                    if($role_id== 3 || $role_id== 6){

                        if ($comdata->rt_amount_type == "Commission Percent") {

                            $commission = $amount * $comdata->rt_amount_value / 100;

                        }else{

                            $commission = $comdata->rt_amount_value;

                        }

                    }else if($role_id== 5){

                        if ($comdata->dt_amount_type == "Commission Percent") {

                            $commission = $amount * $comdata->dt_amount_value / 100;

                        }else{

                            $commission = $comdata->dt_amount_value;

                        }

                    }else if($role_id== 4){

                        if ($comdata->md_amount_type == "Commission Percent") {

                            $commission = $amount * $comdata->md_amount_value / 100;

                        }else{

                            $commission = $comdata->md_amount_value;

                        }

                    }else if($role_id== 2){

                        if ($comdata->wt_amount_type == "Commission Percent") {

                            $commission = $amount * $comdata->wt_amount_value / 100;

                        }else{

                            $commission = $comdata->wt_amount_value;

                        }

                    }else{

                        $commission = 0;

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
        } catch (\Throwable $e) {
            return 0;
        }

        

        return $commission;

    }



    public static function ApiProviderCode($api_id, $provider_id)

    {

        $api = DB::table('apis')->where('id', $api_id)->first();

        

        if($api && $api->status == "1"){

            $api_provider_code = DB::table('api_provider_codes')->where('provider_id', $provider_id)->where('api_id', $api_id)->first();

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

    /**
     * Operator code for plan/roffer APIs (MPlan etc.), with fallback across plan API mappings.
     */
    public static function PlanProviderCode($api_id, $provider_id)
    {
        $code = self::ApiProviderCode($api_id, $provider_id);
        if ($code !== 0 && $code !== '' && $code !== null) {
            return $code;
        }

        $planApiIds = array_values(array_unique(array_filter([
            (int) $api_id,
            6, 7, 27, 30,
        ])));

        foreach ($planApiIds as $tryApiId) {
            $row = DB::table('api_provider_codes')
                ->where('provider_id', $provider_id)
                ->where('api_id', $tryApiId)
                ->first();

            if ($row && $row->provider_code !== null && $row->provider_code !== '' && $row->provider_code !== '0') {
                return $row->provider_code;
            }
        }

        return 0;
    }


    public static function ApiStateCode($api_id, $state_id)

    {

        $api = DB::table('apis')->where('id', $api_id)->first();

        

        if($api && $api->status == "1"){

            $api_state_code = DB::table('api_state_codes')->where('state_id', $state_id)->where('api_id', $api_id)->first();

            if ($api_state_code) {

                

                $state_code = $api_state_code->state_code;



                if($state_code == null){

                    $state_code = 0;

                }

            }else{

                $state_code = 0;

            }

        }else{

            $state_code = 0;

        }

        

        return $state_code;

    }



    public static function getIp()

    {

        return request()->ip();

    }



    /**
     * Safe recharge timing log — never includes credentials, PINs, or full customer numbers.
     */
    public static function logRechargeTiming(array $payload): void
    {
        try {
            DB::table('apilogs')->insert([
                'url' => 'recharge-timing',
                'modal' => 'RechargeTiming',
                'txnid' => (string) ($payload['order_ref'] ?? 'unknown'),
                'header' => json_encode([]),
                'request' => json_encode($payload),
                'response' => json_encode($payload['result'] ?? []),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // never break recharge flow for logging
        }
    }

    public static function maskOrderId(?string $orderId): string
    {
        if (!$orderId || strlen($orderId) < 8) {
            return '***';
        }

        return substr($orderId, 0, 4) . '***' . substr($orderId, -4);
    }

    /**
     * Strip credential-like query params before persisting outbound API URLs.
     */
    public static function redactUrlSecrets(string $url): string
    {
        return (string) preg_replace(
            '/((?:api[_-]?key|api[_-]?password|apitoken|username|userid|token|password|ApiPassword|Tokenid|Token|UserID))=[^&]*/i',
            '$1=[REDACTED]',
            $url
        );
    }

    /** @return list<string> */
    public static function rechargePendingStatuses(): array
    {
        return ['Pending', 'Under Process', 'Under Proces', 'Processing'];
    }

    public static function reportAllowsComplaint(?object $report): bool
    {
        if (!$report) {
            return false;
        }

        if ((int) ($report->complaint_id ?? 0) !== 0) {
            return false;
        }

        return strcasecmp(trim((string) ($report->status ?? '')), 'Success') === 0;
    }

    public static function complaintBlockMessage(?object $report): string
    {
        if (!$report) {
            return 'Record not found.';
        }

        $status = trim((string) ($report->status ?? ''));

        if (in_array($status, self::rechargePendingStatuses(), true)) {
            return 'Recharge is still pending. Complaint can be submitted only after recharge is successful.';
        }

        if ((int) ($report->complaint_id ?? 0) !== 0) {
            return 'Complaint already submitted for this recharge.';
        }

        if (strcasecmp($status, 'Success') !== 0) {
            return 'Complaint is allowed only for successful recharges.';
        }

        return 'Unable to submit complaint for this recharge.';
    }

    public static function closeOpenComplaintsForReport(int $reportId, string $remark, string $status = 'Closed'): void
    {
        if ($reportId <= 0) {
            return;
        }

        $complaints = DB::table('complaints')
            ->where('report_id', $reportId)
            ->whereIn('status', ['Open', 'Under Review'])
            ->get(['id']);

        if ($complaints->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        foreach ($complaints as $complaint) {
            DB::table('complaints')->where('id', $complaint->id)->update([
                'decision_by' => 1,
                'decision_remark' => $remark,
                'status' => $status,
                'decision_date' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('reports')->where('id', $reportId)->update(['complaint_id' => 0]);
    }



    public static function RunApi($api_id,$provider_id,$report_id,$service)
    {
        @set_time_limit(120);
        @ignore_user_abort(true);

        $report_id = (int) $report_id;

        try {
            if (class_exists(\App\Jobs\ProcessRecharge::class)) {
                (new \App\Jobs\ProcessRecharge($api_id, $provider_id, $report_id, $service))->handle();
            }
        } catch (\Throwable $e) {
            // ProcessRecharge logs failures; return latest report row below.
        }

        $fresh = DB::table('reports')->where('id', $report_id)->first();

        return [
            'status' => $fresh->status ?? 'Pending',
            'operator_id' => $fresh->operator_id ?? '',
            'remark' => $fresh->remark ?? ($service . ' Pending'),
            'order_id' => $fresh->order_id ?? '',
        ];
    }

    /**
     * Safe request metadata for apilogs — never includes $_SERVER env secrets.
     */
    public static function safeRequestMeta(): array
    {
        return [
            'ip' => request()->ip(),
            'method' => request()->method(),
            'path' => request()->path(),
            'host' => request()->getHost(),
            'user_agent' => substr((string) request()->userAgent(), 0, 200),
            'referer' => substr((string) request()->header('referer', ''), 0, 200),
        ];
    }




    public static function sendWhatasappMsg($data){

        //return $data['mobile_number'];

        //user_id,msg_slug,

        $w_api = DB::table('companies')->where('id', 1)->first(['whatsapp_request_url','whatsapp_api_method']);

        $url = $w_api->whatsapp_request_url;

        if($url !=0 || $url !=""){

            $url = str_replace('{MOB}', '' . $data['mobile_number'] . '', $url);

            $url = str_replace('{MSG}', '' . urlencode($data['content']) . '', $url);

            $url = str_replace('{TMP_ID}', '' . $data['template_id'] . '', $url);

            $method = $w_api->whatsapp_api_method;

            $header = [];

            $parameters = "";

            $request_id = "WAS".date("YmdHis").rand(11111, 999999);

            $curl = \helpers::curl($url, $method, $parameters, $header, "yes", "WHATSAPP_URL", $request_id);

            return 0;

        }else{

            return 1;

        }

    }



    public static function sendFcmNotification($token, $title, $body, $data = []){
        // Try to send push notification using FCM. Server key in env FCM_SERVER_KEY
        try{
            if(!$token) return false;
            $serverKey = env('FCM_SERVER_KEY');
            if(!$serverKey) return false;
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default'
                ],
                'data' => (object)$data
            ];
            $json = json_encode($payload);
            $headers = [
                'Content-Type: application/json',
                'Authorization: key=' . $serverKey
            ];
            $result = \helpers::curl('https://fcm.googleapis.com/fcm/send', 'POST', $json, $headers, 'yes', 'FCM', time());
            return $result;
        } catch(\Throwable $e){
            return false;
        }
    }


    public static function curl($url , $method='GET', $parameters = null, $header = [], $log="no", $modal="none", $txnid="none")
    {   

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

        curl_setopt($curl, CURLOPT_ENCODING, "");

        $connectTimeout = max(3, (int) env('RECHARGE_API_CONNECT_TIMEOUT', 10));
        $timeout = max($connectTimeout, (int) env('RECHARGE_API_TIMEOUT', 30));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

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

            try {
                DB::table('apilogs')->insert([

                    "url" => self::redactUrlSecrets($url),

                    "modal" => $modal,

                    "txnid" => $txnid,

                    "header" => json_encode($header),

                    "request" => json_encode($parameters),

                    "response" => $response,

                    'created_at' => Carbon::now(),

                    'updated_at' => Carbon::now()

                ]);
            } catch (\Throwable $e) {
                // API logging should never break the primary request flow.
            }

        }



        return ["response" => $response, "error" => $err, 'code' => $code];

    }


    public static function checkApis($route,$post,$user){
        $route_api_id = 0;
        try {
            if($route == 'amount_wize'){
                if (!\Illuminate\Support\Facades\Schema::hasTable('amount_wize_switch')) {
                    return 0;
                }
                $chk_amount_wize = DB::table('amount_wize_switch')->where('provider_id', $post->provider_id)->where('status', 1)->first();
                if ($chk_amount_wize) {
                    $amounts = explode(",", $chk_amount_wize->amount);
                    foreach ($amounts as $amount) {
                        if ($post->amount == $amount) {
                            $route_api_id = $chk_amount_wize->api_id;
                        }
                    }
                }
            }else if($route == 'user_wize'){
                if (!\Illuminate\Support\Facades\Schema::hasTable('user_wize_switch')) {
                    return 0;
                }
                $chk_user_wize_switch = DB::table('user_wize_switch')->where('provider_id', $post->provider_id)->where('user_id', $user->id)->where('state_id', $post->state_id)->where('status', 1)->first();
                if ($chk_user_wize_switch) {
                    
                    $amounts = explode(",", $chk_user_wize_switch->amount);
                    foreach ($amounts as $amount) {
                        if ($post->amount == $amount) {
                            $route_api_id = $chk_user_wize_switch->api_id;
                        }
                    }
                    if($chk_user_wize_switch->amount == 0 ||$chk_user_wize_switch->amount == ""){
                        $route_api_id = $chk_user_wize_switch->api_id;
                    }
                }
            }else if($route == 'state_wize'){
                if (!\Illuminate\Support\Facades\Schema::hasTable('state_wize_switch')) {
                    return 0;
                }
                $chk_state_wize_switch = DB::table('state_wize_switch')->where('provider_id', $post->provider_id)->where('state_id', $post->state_id)->where('status', 1)->first();
                if ($chk_state_wize_switch) {
                    $amounts = explode(",", $chk_state_wize_switch->amount);
                    foreach ($amounts as $amount) {
                        if ($post->amount == $amount) {
                            $route_api_id = $chk_state_wize_switch->api_id;
                        }
                    }
                    if($chk_state_wize_switch->amount == 0 ||$chk_state_wize_switch->amount == ""){
                        $route_api_id = $chk_state_wize_switch->api_id;
                    }
                }
            }else{
                $provider = DB::table('providers')->where('id', $post->provider_id)->first();
                $route_api_id = (int) ($provider->api_id ?? 0);
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $route_api_id;
    }

    public static function normalizeUserPin($pin): string
    {
        $digits = preg_replace('/\D/', '', (string) $pin);

        return str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
    }

    public static function verifyUserPin($stored, $entered): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        return hash_equals(self::normalizeUserPin($stored), self::normalizeUserPin($entered));
    }

    /**
     * Verify user password with bcrypt, legacy plain-text, or visible_password fallback.
     * Re-hashes legacy/plain matches automatically.
     */
    public static function verifyUserPassword(string $plain, object $user): bool
    {
        $plain = (string) $plain;
        $hash = (string) ($user->password ?? '');

        if ($hash !== '' && str_starts_with($hash, '$2y$') && Hash::check($plain, $hash)) {
            return true;
        }

        if ($hash !== '' && !str_starts_with($hash, '$2y$') && hash_equals($hash, $plain)) {
            DB::table('users')->where('id', $user->id)->update([
                'password' => Hash::make($plain),
                'visible_password' => $plain,
                'updated_at' => now(),
            ]);

            return true;
        }

        $visible = (string) ($user->visible_password ?? '');
        if ($visible === '' && !empty($user->id)) {
            $visible = (string) DB::table('users')->where('id', $user->id)->value('visible_password');
        }

        if ($visible !== '' && hash_equals($visible, $plain)) {
            DB::table('users')->where('id', $user->id)->update([
                'password' => Hash::make($plain),
                'visible_password' => $plain,
                'updated_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public static function ensureVisiblePasswordColumn(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'visible_password')) {
            return;
        }

        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('visible_password', 255)->nullable()->after('password');
        });
    }

    public static function userPasswordUpdateFields(string $plain): array
    {
        self::ensureVisiblePasswordColumn();

        return [
            'password' => Hash::make($plain),
            'visible_password' => $plain,
            'updated_at' => now(),
        ];
    }

}

if (! function_exists('user_company')) {
    function user_company(): ?object
    {
        static $company = false;
        if ($company !== false) {
            return $company;
        }

        $hosts = [];
        try {
            $hosts[] = request()->getHttpHost();
            $hosts[] = request()->getHost();
        } catch (\Throwable $e) {
        }
        $hosts[] = $_SERVER['HTTP_HOST'] ?? null;
        $hosts[] = $_SERVER['SERVER_NAME'] ?? null;
        $hosts = array_values(array_unique(array_filter($hosts)));

        foreach ($hosts as $host) {
            $row = \Illuminate\Support\Facades\DB::table('companies')->where('status', '1')->where('domain', $host)->first();
            if ($row) {
                $company = $row;
                return $company;
            }
            $bare = explode(':', (string) $host)[0];
            if ($bare !== $host) {
                $row = \Illuminate\Support\Facades\DB::table('companies')->where('status', '1')->where('domain', $bare)->first();
                if ($row) {
                    $company = $row;
                    return $company;
                }
            }
            $row = \Illuminate\Support\Facades\DB::table('companies')->where('status', '1')->where('domain', 'like', $bare . '%')->first();
            if ($row) {
                $company = $row;
                return $company;
            }
        }

        $company = \Illuminate\Support\Facades\DB::table('companies')->where('status', '1')->first();
        return $company;
    }
}

if (! function_exists('user_build_serial')) {
    /**
     * Visible deploy marker on user auth pages. Bump on each production release.
     */
    function user_build_serial(): string
    {
        return '20260813-WEB-005';
    }
}

if (! function_exists('website_page')) {
    function website_page(string $slug): ?object
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('website_pages')) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('website_pages')->where('slug', $slug)->first();
    }
}

if (! function_exists('website_page_text')) {
    function website_page_text(?object $page, string $field = 'body'): string
    {
        if (!$page) {
            return '';
        }

        return trim((string) ($page->{$field} ?? ''));
    }
}

if (! function_exists('website_media_url')) {
    function website_media_url(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $base = rtrim((string) env('ADMIN_HOST', ''), '/');
        if ($base === '') {
            $base = rtrim(url('/admin'), '/');
        }

        return $base.'/website_media/'.rawurlencode(basename($filename));
    }
}

if (! function_exists('website_media_items')) {
    function website_media_items(string $kind): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('website_media')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('website_media')
            ->where('kind', $kind)
            ->where('status', 1)
            ->where('deleted_at', '!=', 1)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->all();
    }
}

if (! function_exists('admin_slider_image_url')) {
    /**
     * Public slider image URL served from the admin app.
     */
    function admin_slider_image_url(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $base = rtrim((string) env('ADMIN_HOST', ''), '/');
        if ($base === '') {
            $base = rtrim(url('/admin'), '/');
        }

        return $base.'/slider_image/'.rawurlencode(basename($filename));
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
            $query = DB::table('companies')->where('status', '1');
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
            $adminHost = rtrim((string) env('ADMIN_HOST', ''), '/');
            if ($adminHost !== '') {
                $logo = $adminHost.'/company_logo/'.ltrim($logoFile, '/');
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

