<?php

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class helpers

{

    

    static function Pt($data){

        echo "<pre>";print_r($data);

    }

    static function Ptd($data){

        echo "<pre>";print_r($data);die;

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

        $refund['opening_balance'] = $user->wallet_balance;

        DB::table('users')->where('id', $report->user_id)->increment('wallet_balance', $report->amount);

        $user = DB::table('users')->where('id',$report->user_id)->first();

        $refund['closing_balance'] = $user->wallet_balance;  

        $refund['transaction_date'] = Carbon::now().":".rand(111,999);

        $refund['created_at'] = Carbon::now();    

        $refund['updated_at'] = Carbon::now();                  

        $report = DB::table('reports')->insertGetId($refund);

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
 
              $update = DB::table('reports')->insertGetId($data);
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

             $report = DB::table('reports')->insertGetId($data);

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

                 $report = DB::table('reports')->insertGetId($data);

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

                     $report = DB::table('reports')->insertGetId($data);

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



    public static function RunApi($api_id,$provider_id,$report_id,$service)
    {
        // Dispatch background job to process API call and fallbacks for reliability.
        try {
    if (class_exists(\App\Jobs\ProcessRecharge::class)) {
        \App\Jobs\ProcessRecharge::dispatch(
            $api_id,
            $provider_id,
            $report_id,
            $service
        );
    }
} catch (\Throwable $e) {
    // If dispatch fails, fall back to synchronous call.
}

        // Return a pending response to the caller. The background job will update the reports row when done.
        $order = DB::table('reports')->where('id', $report_id)->value('order_id');
        $update = [
            'status' => 'Pending',
            'operator_id' => '',
            'remark' => $service . ' Queued for processing',
            'order_id' => $order,
        ];
        return $update;

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

            try {
                DB::table('apilogs')->insert([

                    "url" => $url,

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
        if($route == 'amount_wize'){
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
            $route_api_id = $provider->api_id;
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

if (! function_exists('user_build_serial')) {
    /**
     * Visible deploy marker on user auth pages. Bump on each production release.
     */
    function user_build_serial(): string
    {
        return '20260811-USER-AUTH-001';
    }
}

