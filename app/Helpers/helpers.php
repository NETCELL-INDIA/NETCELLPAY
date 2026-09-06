<?php

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

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

    public static function ensureHotIndexes(): void
    {
        try {
            if (\Illuminate\Support\Facades\Cache::get('reports_hot_indexes_ok')) {
                return;
            }
            $indexes = [
                'reports_type_status_created' => 'ALTER TABLE `reports` ADD INDEX `reports_type_status_created` (`transaction_type`, `status`, `created_at`)',
                'reports_user_type_created' => 'ALTER TABLE `reports` ADD INDEX `reports_user_type_created` (`user_id`, `transaction_type`, `created_at`)',
                'reports_order_id' => 'ALTER TABLE `reports` ADD INDEX `reports_order_id` (`order_id`)',
                'reports_callback_pending' => 'ALTER TABLE `reports` ADD INDEX `reports_callback_pending` (`path`, `callback_status`, `status`)',
            ];
            foreach ($indexes as $sql) {
                try {
                    DB::statement($sql);
                } catch (\Throwable $e) {
                }
            }
            \Illuminate\Support\Facades\Cache::put('reports_hot_indexes_ok', 1, 86400);
        } catch (\Throwable $e) {
        }
    }

    public static function apiArrayGet($data, $path)
    {
        if (!is_array($data) || $path === null || $path === '') {
            return null;
        }
        $path = (string) $path;
        if (array_key_exists($path, $data)) {
            return $data[$path];
        }
        $cur = $data;
        foreach (explode('.', $path) as $seg) {
            if ($seg === '' || !is_array($cur) || !array_key_exists($seg, $cur)) {
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
        if (!$api) {
            return true;
        }
        if (!isset($api->{$column})) {
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

    public static function mapApiCallbackStatus($api, $actual): string
    {
        if (self::apiValueMatches($actual, $api->callback_success_value ?? '')
            || self::apiValueMatches($actual, $api->success_value ?? '')) {
            return 'Success';
        }
        if (self::apiValueMatches($actual, $api->callback_failed_value ?? '')
            || self::apiValueMatches($actual, $api->failed_value ?? '')
            || self::apiValueMatches($actual, $api->error_value_response ?? '')) {
            return 'Failed';
        }
        if (self::apiValueMatches($actual, $api->callback_refund_value ?? '')
            || self::apiValueMatches($actual, $api->refund_value ?? '')) {
            return 'Refunded';
        }
        if (self::apiValueMatches($actual, $api->callback_pending_value ?? $api->pending_value ?? '')) {
            return 'Pending';
        }

        return 'Pending';
    }

    public static function isApiPartnerPath($path): bool
    {
        return strcasecmp(trim((string) $path), 'Api') === 0;
    }

    public static function sendApiPartnerRechargeCallback($report): bool
    {
        if (is_numeric($report) || is_string($report)) {
            $report = DB::table('reports')->where('id', $report)->first();
        }
        if (!$report) {
            return false;
        }
        if (! self::isApiPartnerPath($report->path ?? '')) {
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
        $sep = str_contains($base, '?') ? '&' : '?';
        $url = $base.$sep.$query;
        $result = self::curl($url, 'GET', '', [], 'yes', 'USER_RECHARGE_CALLBACK', (string) ($report->order_id ?? $report->id));

        $payload = [
            'callback_status' => 1,
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('reports', 'api_partner_call_back_url')) {
            $payload['api_partner_call_back_url'] = $url;
        }
        if (Schema::hasColumn('reports', 'api_partner_callback_response')) {
            $payload['api_partner_callback_response'] = (string) ($result['response'] ?? $result['error'] ?? '');
        }
        DB::table('reports')->where('id', $report->id)->update($payload);

        return true;
    }

    public static function loginCoordinatesFromRequest($request = null): array
    {
        $req = $request ?: request();
        $latRaw = $req->input('latitude', $req->input('lat'));
        $lngRaw = $req->input('longitude', $req->input('lng', $req->input('long')));

        if (! is_numeric($latRaw) || ! is_numeric($lngRaw)) {
            return [null, null];
        }

        $lat = round((float) $latRaw, 7);
        $lng = round((float) $lngRaw, 7);
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return [null, null];
        }

        return [$lat, $lng];
    }

    public static function googleMapsUrl($lat, $lng): ?string
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat == 0.0 && $lng == 0.0) {
            return null;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($lat.','.$lng);
    }

    public static function ensureLoginHistoryGeoColumns(): void
    {
        try {
            if (! Schema::hasTable('login_histories')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        foreach ([
            'latitude' => 'DECIMAL(10,7) NULL',
            'longitude' => 'DECIMAL(10,7) NULL',
        ] as $column => $definition) {
            try {
                if (! Schema::hasColumn('login_histories', $column)) {
                    DB::statement("ALTER TABLE `login_histories` ADD COLUMN `{$column}` {$definition}");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public static function recordLoginHistory(int $userId, string $loginPath = 'WEB'): void
    {
        try {
            if (! Schema::hasTable('login_histories')) {
                return;
            }
            self::ensureLoginHistoryGeoColumns();
        } catch (\Throwable $e) {
            return;
        }

        $row = [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'login_path' => $loginPath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        [$lat, $lng] = self::loginCoordinatesFromRequest();
        if (Schema::hasColumn('login_histories', 'latitude')) {
            $row['latitude'] = $lat;
        }
        if (Schema::hasColumn('login_histories', 'longitude')) {
            $row['longitude'] = $lng;
        }

        try {
            DB::table('login_histories')->insert($row);
        } catch (\Throwable $e) {
        }
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

        try {
            self::sendApiPartnerRechargeCallback((int) ($refund['parent__Id'] ?? 0) ?: $report);
        } catch (\Throwable $e) {
        }

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

         try {
             self::sendApiPartnerRechargeCallback($report_id_com);
         } catch (\Throwable $e) {
         }

     }

    public static function ensureDenominationCommissionTable(): void
    {
        try {
            if (Schema::hasTable('scheme_commission_denominations')) {
                return;
            }
            Schema::create('scheme_commission_denominations', function ($table) {
                $table->id();
                $table->unsignedBigInteger('scheme_id')->index();
                $table->unsignedBigInteger('provider_id')->index();
                $table->decimal('min_amount', 12, 2)->default(0);
                $table->decimal('max_amount', 12, 2)->default(0);
                $table->string('md_amount_type', 50)->nullable();
                $table->decimal('md_amount_value', 12, 4)->default(0);
                $table->string('dt_amount_type', 50)->nullable();
                $table->decimal('dt_amount_value', 12, 4)->default(0);
                $table->string('rt_amount_type', 50)->nullable();
                $table->decimal('rt_amount_value', 12, 4)->default(0);
                $table->string('ap_amount_type', 50)->nullable();
                $table->decimal('ap_amount_value', 12, 4)->default(0);
                $table->timestamps();
                $table->index(['scheme_id', 'provider_id']);
            });
        } catch (\Throwable $e) {
        }
    }

    public static function denominationCommissionRow($scheme, $provider_id, $amount)
    {
        self::ensureDenominationCommissionTable();
        if (! Schema::hasTable('scheme_commission_denominations')) {
            return null;
        }

        $amount = (float) $amount;

        return DB::table('scheme_commission_denominations')
            ->where('scheme_id', $scheme)
            ->where('provider_id', $provider_id)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->orderByRaw('(max_amount - min_amount) asc')
            ->orderByDesc('id')
            ->first();
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

                $comdata = null;
                if (in_array((int) $role_id, [3, 4, 5, 6], true)) {
                    $comdata = self::denominationCommissionRow($scheme, $provider_id, $amount);
                }
                if (! $comdata) {
                    $comdata = DB::table('scheme_commissions')->where('provider_id', $provider_id)->where('scheme_id', $scheme)->first();
                }

                if ($comdata) {

                    if($role_id== 6){

                        if ($comdata->rt_amount_type == "Commission Percent") {

                            $commission = $amount * $comdata->rt_amount_value / 100;

                        }else{

                            $commission = $comdata->rt_amount_value;

                        }

                    }else if($role_id== 3){

                        $apType = $comdata->ap_amount_type ?? '';
                        $apValue = $comdata->ap_amount_value ?? null;
                        if ($apType === '' || $apType === '0' || $apType === 0) {
                            $apType = $comdata->rt_amount_type;
                            $apValue = $comdata->rt_amount_value;
                        }
                        if ($apType == "Commission Percent") {
                            $commission = $amount * $apValue / 100;
                        } else {
                            $commission = $apValue;
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




    public static function whatsappPublicOrigin(): string
    {
        $origin = rtrim((string) env('APP_URL', ''), '/');
        $origin = preg_replace('#/admin$#i', '', $origin) ?: $origin;
        if ($origin === '') {
            $origin = rtrim((string) env('ADMIN_HOST', ''), '/');
            $origin = preg_replace('#/admin$#i', '', $origin) ?: $origin;
        }
        if ($origin === '' && ! empty($_SERVER['HTTP_HOST'])) {
            $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
            $origin = ($https ? 'https://' : 'http://').$_SERVER['HTTP_HOST'];
        }

        return $origin !== '' ? $origin : 'https://netcellpay.in';
    }

    public static function whatsappPublicFileUrl(string $filename): string
    {
        $filename = ltrim(basename($filename), '/');
        if ($filename === '') {
            return '';
        }

        return self::whatsappPublicOrigin().'/wa-media/'.$filename;
    }

    public static function companyWhatsappLogoUrl(): string
    {
        $company = DB::table('companies')->where('id', 1)->first(['company_logo', 'company_icon']);
        $file = (string) ($company->company_icon ?? $company->company_logo ?? '');
        if ($file === '') {
            return '';
        }

        return self::whatsappPublicFileUrl($file);
    }

    public static function whatsappTemplateImageUrl(string $filename): string
    {
        return self::whatsappPublicFileUrl($filename);
    }

    public static function whatsappPlainMediaUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $base = ltrim(basename($path), '/');
        if ($base !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $base)) {
            return self::whatsappPublicFileUrl($base);
        }
        $cut = strtok($url, '?');

        return $cut !== false ? $cut : $url;
    }

    public static function whatsappEnabled(string $slug, $smsTmp = null): bool
    {
        try {
            if ($slug !== '' && \Illuminate\Support\Facades\Schema::hasTable('whatsapp_templates')) {
                $status = DB::table('whatsapp_templates')->where('slug', $slug)->value('status');
                if ((int) $status === 1) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

            return $smsTmp && (int) $smsTmp->status === 1;
        }

    public static function loginOtpRecentlySent($user, int $seconds = 60): bool
    {
        if (! $user || empty($user->otp) || empty($user->otp_created_at)) {
            return false;
        }
        try {
            return Carbon::parse($user->otp_created_at)->gt(Carbon::now()->subSeconds($seconds));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function sendQueuedWhatsapp(string $slug, $toUserId, string $mobile, string $content, $smsTmp = null): void
    {
        if ($mobile === '' || ! self::whatsappEnabled($slug, $smsTmp)) {
            return;
        }
        self::sendWhatasappMsg([
            'mobile_number' => $mobile,
            'content' => $content,
            'template_id' => (string) ($smsTmp->template_id ?? ''),
            'slug' => $slug,
        ]);
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('messages') || ! $toUserId) {
                return;
            }
            DB::table('messages')->insert([
                'user_id' => 1,
                'to_user_id' => $toUserId,
                'subject' => $slug,
                'msg_source' => 'WHATSAPP',
                'template_id' => (string) ($smsTmp->template_id ?? ''),
                'content' => $content,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function sendWhatasappMsg($data){

        $w_api = DB::table('companies')->where('id', 1)->first(['whatsapp_request_url','whatsapp_api_method']);

        if (!$w_api) {
            return 1;
        }

        $rawUrl = trim((string) ($w_api->whatsapp_request_url ?? ''));

        if ($rawUrl === '' || $rawUrl === '0') {
            return 1;
        }

            $content = (string) ($data['content'] ?? '');
            $templateId = (string) ($data['template_id'] ?? '');
            $slug = (string) ($data['slug'] ?? '');
            $attach = !empty($data['attach_logo']);
            $attachImage = array_key_exists('attach_image', $data) ? !empty($data['attach_image']) : null;
            $imageUrl = (string) ($data['image_url'] ?? '');
            $tpl = null;

            if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_templates')) {
                if ($slug !== '') {
                    $tpl = DB::table('whatsapp_templates')->where('slug', $slug)->first();
                }
                if (!$tpl && $templateId !== '') {
                    $tpl = DB::table('whatsapp_templates')->where('template_id', $templateId)->first();
                }
                if ($tpl && !array_key_exists('attach_logo', $data)) {
                    $attach = (int) ($tpl->attach_logo ?? 0) === 1;
                }
                if ($tpl && $attachImage === null) {
                    $attachImage = (int) ($tpl->attach_image ?? 0) === 1;
                }
                if ($tpl && $content === '' && (int) ($tpl->status ?? 0) === 1) {
                    $content = (string) $tpl->content;
                    $templateId = (string) ($tpl->template_id ?: $templateId);
                }
                if ($imageUrl === '' && $tpl && !empty($tpl->image)) {
                    $imageUrl = self::whatsappTemplateImageUrl((string) $tpl->image);
                }
            }
            if ($imageUrl === '' && !empty($data['image'])) {
                $imageUrl = self::whatsappTemplateImageUrl((string) $data['image']);
            }
            $attachImage = (bool) $attachImage;

            $logoUrl = self::whatsappPlainMediaUrl(self::companyWhatsappLogoUrl());
            $imageUrl = self::whatsappPlainMediaUrl($imageUrl);
            $content = str_replace(['{LOGO}', '{LOGO_URL}', '{IMG}', '{IMAGE}', '{TEMPLATE_IMAGE}'], '', $content);
            $content = trim($content);

            $mediaFiles = [];
            if ($attachImage && $imageUrl !== '') {
                $mediaFiles[] = $imageUrl;
            } elseif ($attach && $logoUrl !== '' && ! in_array($logoUrl, $mediaFiles, true)) {
                $mediaFiles[] = $logoUrl;
            }
            $isOtp = strtolower($slug) === 'otp';
            $mediaCaption = $isOtp ? 'NETCELL PAY' : $content;

            $method = $w_api->whatsapp_api_method ?: 'GET';
            $hasMediaPlaceholder = str_contains($rawUrl, '{IMG}')
                || str_contains($rawUrl, '{IMAGE}')
                || str_contains($rawUrl, '{MEDIA}')
                || str_contains($rawUrl, '{MEDIA_URL}')
                || str_contains($rawUrl, '{FILE}')
                || str_contains($rawUrl, '{LOGO}');

            $buildUrl = function (string $message, string $image, bool $asMedia) use ($rawUrl, $data, $templateId, $logoUrl, $hasMediaPlaceholder) {
                $u = $rawUrl;
                $u = str_replace('{MOB}', (string) ($data['mobile_number'] ?? ''), $u);
                $u = str_replace('{MSG}', urlencode($message), $u);
                $u = str_replace('{TMP_ID}', $templateId, $u);
                $u = str_replace('{TMPID}', $templateId, $u);
                $u = str_replace('{LOGO}', urlencode($asMedia ? $image : $logoUrl), $u);
                $u = str_replace('{IMG}', urlencode($image), $u);
                $u = str_replace('{IMAGE}', urlencode($image), $u);
                $u = str_replace('{MEDIA}', urlencode($image), $u);
                $u = str_replace('{MEDIA_URL}', urlencode($image), $u);
                $u = str_replace('{FILE}', urlencode($image), $u);
                if ($asMedia && $image !== '' && ! $hasMediaPlaceholder) {
                    if (preg_match('/([?&])type=text\b/i', $u)) {
                        $u = preg_replace('/([?&])type=text\b/i', '$1type=image', $u);
                    } elseif (! preg_match('/[?&]type=/i', $u)) {
                        $u .= (str_contains($u, '?') ? '&' : '?').'type=image';
                    }
                    $name = basename((string) (parse_url($image, PHP_URL_PATH) ?: 'netcell.png'));
                    $sep = str_contains($u, '?') ? '&' : '?';
                    $u .= $sep.'file='.rawurlencode($image)
                        .'&media_url='.rawurlencode($image)
                        .'&caption='.rawurlencode($message)
                        .'&filename='.rawurlencode($name)
                        .'&mediatype=image';
                }

                return $u;
            };

            $header = [];
            $parameters = '';
            $sentMedia = false;
            foreach ($mediaFiles as $idx => $img) {
                $mediaUrl = $buildUrl($mediaCaption, $img, true);
                \helpers::curl($mediaUrl, $method, $parameters, $header, 'yes', 'WHATSAPP_URL', 'WAS'.date('YmdHis').rand(11111, 999999).'I'.$idx);
                $sentMedia = true;
            }
            // Image caption already has the message. A second text call duplicated every WhatsApp.
            // OTP still needs a separate text because the image caption is not the OTP.
            if ($content !== '' && (!$sentMedia || $isOtp)) {
                $textUrl = $buildUrl($content, '', false);
                \helpers::curl($textUrl, $method, $parameters, $header, 'yes', 'WHATSAPP_URL', 'WAS'.date('YmdHis').rand(11111, 999999));
            }

            return 0;
    }



    public static function fcmServerKey(): ?string
    {
        $key = trim((string) env('FCM_SERVER_KEY', ''));
        if ($key !== '') {
            return $key;
        }

        try {
            $key = trim((string) \App\Services\SystemSettingService::get('fcm_server_key', ''));
        } catch (\Throwable $e) {
            $key = '';
        }

        return $key !== '' ? $key : null;
    }

    public static function ensureUserPushColumns(): void
    {
        foreach ([
            'fcm_token' => 'TEXT NULL',
            'device_token' => 'TEXT NULL',
        ] as $column => $definition) {
            try {
                if (! Schema::hasColumn('users', $column)) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$column}` {$definition}");
                } else {
                    DB::statement("ALTER TABLE `users` MODIFY COLUMN `{$column}` TEXT NULL");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public static function extractPushTokenFromRequest(Request $request): ?string
    {
        $keys = [
            'fcm_token', 'device_token', 'firebase_token', 'notification_token',
            'push_token', 'gcm_token', 'registration_id', 'firebaseToken', 'fcmToken',
        ];
        foreach ($keys as $key) {
            $value = trim((string) $request->input($key, ''));
            if (self::isLikelyPushToken($value)) {
                return $value;
            }
        }

        foreach (['HTTP_FCM_TOKEN', 'HTTP_DEVICE_TOKEN', 'HTTP_X_FCM_TOKEN', 'HTTP_X_DEVICE_TOKEN'] as $header) {
            $value = trim((string) ($request->server($header) ?? ''));
            if (self::isLikelyPushToken($value)) {
                return $value;
            }
        }

        return null;
    }

    private static function isLikelyPushToken(string $value): bool
    {
        return $value !== '' && strlen($value) >= 40 && ! preg_match('/\s/', $value);
    }

    public static function saveUserPushToken(int $userId, ?string $token): void
    {
        if ($userId <= 0 || ! $token) {
            return;
        }

        self::ensureUserPushColumns();

        try {
            DB::table('users')->where('id', $userId)->update([
                'fcm_token' => $token,
                'device_token' => $token,
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('saveUserPushToken failed: '.$e->getMessage());
        }
    }

    public static function pushNotifyUser(int $userId, string $title, string $body, array $data = [], string $subject = 'app_notification'): bool
    {
        if ($userId <= 0) {
            return false;
        }

        self::ensureUserPushColumns();

        try {
            if (Schema::hasTable('messages')) {
                DB::table('messages')->insert([
                    'user_id' => 1,
                    'to_user_id' => $userId,
                    'subject' => $subject,
                    'msg_source' => 'PUSH',
                    'template_id' => 0,
                    'content' => $body,
                    'status' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('pushNotifyUser message insert failed: '.$e->getMessage());
        }

        try {
            $user = DB::table('users')->where('id', $userId)->first(['fcm_token', 'device_token']);
            $token = $user->fcm_token ?? ($user->device_token ?? null);
            if (! $token) {
                return false;
            }

            return (bool) self::sendFcmNotification($token, $title, $body, $data);
        } catch (\Throwable $e) {
            \Log::warning('pushNotifyUser failed: '.$e->getMessage());

            return false;
        }
    }

    public static function fetchUserNotifications(int $userId, int $limit = 20)
    {
        if ($userId <= 0 || ! Schema::hasTable('messages')) {
            return collect();
        }

        try {
            return DB::table('messages')
                ->where('to_user_id', $userId)
                ->where('msg_source', 'PUSH')
                ->orderByDesc('id')
                ->take($limit)
                ->get(['id', 'subject', 'content', 'status', 'created_at']);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function countUnreadNotifications(int $userId): int
    {
        if ($userId <= 0 || ! Schema::hasTable('messages')) {
            return 0;
        }

        try {
            return (int) DB::table('messages')
                ->where('to_user_id', $userId)
                ->where('msg_source', 'PUSH')
                ->where('status', 0)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function markNotificationsRead(int $userId, ?array $ids = null): void
    {
        if ($userId <= 0 || ! Schema::hasTable('messages')) {
            return;
        }

        try {
            $query = DB::table('messages')
                ->where('to_user_id', $userId)
                ->where('msg_source', 'PUSH')
                ->where('status', 0);

            if (is_array($ids) && count($ids) > 0) {
                $query->whereIn('id', $ids);
            }

            $query->update([
                'status' => 1,
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function sendFcmNotification($token, $title, $body, $data = []){
        try{
            if(!$token) return false;
            $serverKey = self::fcmServerKey();
            if(!$serverKey) return false;

            $payloadData = ['title' => (string) $title, 'body' => (string) $body];
            foreach ($data as $key => $value) {
                $payloadData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
            }

            $payload = [
                'to' => $token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $payloadData,
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

        $connectTimeout = max(3, (int) env('RECHARGE_API_CONNECT_TIMEOUT', 5));
        try {
            $timeout = \App\Services\SystemSettingService::rechargeApiTimeout();
        } catch (\Throwable $e) {
            $timeout = max($connectTimeout, (int) env('RECHARGE_API_TIMEOUT', 30));
        }
        $timeout = max($connectTimeout, $timeout);
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
                $body = $response;
                if (($body === false || $body === null || $body === '') && $err) {
                    $body = json_encode(['curl_error' => $err, 'http_code' => $code], JSON_UNESCAPED_SLASHES);
                } elseif (is_string($body)) {
                    $body = mb_substr($body, 0, 20000);
                }

                DB::table('apilogs')->insert([

                    "url" => self::redactUrlSecrets($url),

                    "modal" => $modal,

                    "txnid" => $txnid,

                    "header" => json_encode($header),

                    "request" => is_string($parameters) || $parameters === null || $parameters === ''
                        ? (string) $parameters
                        : json_encode($parameters),

                    "response" => is_string($body) ? $body : json_encode($body),

                    'created_at' => Carbon::now(),

                    'updated_at' => Carbon::now()

                ]);
            } catch (\Throwable $e) {
                // API logging should never break the primary request flow.
            }

        }



        return ["response" => $response, "error" => $err, 'code' => $code];

    }

    protected static function shouldStoreSupplierApiLog(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || !Schema::hasTable('apis')) {
            return true;
        }

        try {
            $apis = DB::table('apis')
                ->where('deleted_at', '!=', 1)
                ->get(['store_log', 'api_url', 'balance_check_url', 'complaint_api_url']);
        } catch (\Throwable $e) {
            return true;
        }

        $matched = false;
        foreach ($apis as $api) {
            foreach ([$api->api_url ?? '', $api->balance_check_url ?? '', $api->complaint_api_url ?? ''] as $apiUrl) {
                $apiHost = parse_url((string) $apiUrl, PHP_URL_HOST);
                if ($apiHost && strcasecmp($apiHost, $host) === 0) {
                    $matched = true;
                    if ((int) ($api->store_log ?? 0) === 1) {
                        return true;
                    }
                }
            }
        }

        return !$matched;
    }


    public static function isProviderDownForUser($providerId, $userId): bool
    {
        try {
            if (!Schema::hasTable('provider_user_downs')) {
                return false;
            }

            return DB::table('provider_user_downs')
                ->where('provider_id', (int) $providerId)
                ->where('user_id', (int) $userId)
                ->where('status', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
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

    public static function passwordFromMobile($mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if (strlen($digits) >= 8) {
            return substr($digits, -8);
        }

        return str_pad($digits !== '' ? $digits : '0', 8, '0', STR_PAD_LEFT);
    }

    public static function ensureFailToSuccessTable(): void
    {
        if (Schema::hasTable('supplier_fail_to_success')) {
            return;
        }
        Schema::create('supplier_fail_to_success', function ($table) {
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
                'response_time' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function ensureApiPartnerCommissionColumns(): void
    {
        try {
            if (! Schema::hasTable('scheme_commissions')) {
                return;
            }
            if (! Schema::hasColumn('scheme_commissions', 'ap_amount_type')) {
                Schema::table('scheme_commissions', function ($table) {
                    $table->string('ap_amount_type', 50)->nullable();
                });
            }
            if (! Schema::hasColumn('scheme_commissions', 'ap_amount_value')) {
                Schema::table('scheme_commissions', function ($table) {
                    $table->decimal('ap_amount_value', 12, 4)->default(0);
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public static function ensureServiceIconColumn(): void
    {
        try {
            if (! Schema::hasTable('services')) {
                return;
            }
            if (! Schema::hasColumn('services', 'service_icon')) {
                Schema::table('services', function ($table) {
                    $table->string('service_icon', 255)->nullable();
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public static function ensureServiceDownColumn(): void
    {
        try {
            if (! Schema::hasTable('services')) {
                return;
            }
            if (! Schema::hasColumn('services', 'service_down')) {
                Schema::table('services', function ($table) {
                    $table->unsignedTinyInteger('service_down')->default(0);
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public static function serviceIconRelativePath(?string $icon, string $fallback = 'service_icon/mobile_1.png'): string
    {
        $icon = trim((string) $icon);
        if ($icon === '') {
            return $fallback;
        }
        if (preg_match('#^https?://#i', $icon)) {
            $path = parse_url($icon, PHP_URL_PATH) ?: '';
            $path = ltrim((string) $path, '/');
            if (strpos($path, 'admin/') === 0) {
                $path = substr($path, 6);
            }

            return $path !== '' ? $path : $fallback;
        }

        return str_contains($icon, '/') ? ltrim($icon, '/') : 'service_icon/'.$icon;
    }

    public static function syncServiceIconFile(string $relative): void
    {
        $relative = str_replace(['\\', '..'], ['/', ''], ltrim($relative, '/'));
        if ($relative === '') {
            return;
        }
        $userFile = public_path($relative);
        if (is_file($userFile)) {
            return;
        }
        $adminFile = base_path('admin'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if (! is_file($adminFile)) {
            return;
        }
        $dir = dirname($userFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        @copy($adminFile, $userFile);
    }

    public static function servicePublicUrl(?string $icon, string $fallback = 'service_icon/mobile_1.png'): string
    {
        $relative = self::serviceIconRelativePath($icon, $fallback);
        self::syncServiceIconFile($relative);

        $version = '';
        $local = public_path($relative);
        if (is_file($local)) {
            $version = (string) filemtime($local);

            return asset($relative).($version !== '' ? '?v='.$version : '');
        }

        $adminHost = rtrim((string) env('ADMIN_HOST', ''), '/');
        if ($adminHost !== '') {
            return $adminHost.'/'.$relative;
        }

        return asset($relative);
    }

    public static function serviceCatalogItems(string $group = 'recharge', bool $absoluteUrls = false): array
    {
        $items = config('recharge_services.'.$group, []);
        $fallback = $group === 'bbps' ? 'service_logo/10.png' : 'service_icon/mobile_1.png';

        try {
            self::ensureServiceIconColumn();
            self::ensureServiceDownColumn();
            self::ensureCatalogGroupColumn();
            $query = DB::table('services');
            if (Schema::hasColumn('services', 'deleted_at')) {
                $query->where(function ($q) {
                    $q->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
                });
            }
            $cols = ['id', 'service_name', 'service_icon'];
            if (Schema::hasColumn('services', 'status')) {
                $cols[] = 'status';
            }
            if (Schema::hasColumn('services', 'service_down')) {
                $cols[] = 'service_down';
            }
            if (Schema::hasColumn('services', 'sort_order')) {
                $cols[] = 'sort_order';
            }
            if (Schema::hasColumn('services', 'catalog_group')) {
                $cols[] = 'catalog_group';
            }
            $rows = [];
            foreach ($query->get($cols) as $row) {
                $rows[(int) $row->id] = $row;
            }
        } catch (\Throwable $e) {
            return $items;
        }

        $out = [];
        $seen = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $row = $rows[$id] ?? null;
            if (! $row) {
                continue;
            }
            if (isset($row->status) && (int) $row->status !== 1) {
                continue;
            }
            $out[] = self::mergeServiceCatalogItem($item, $row, $group, $fallback, $absoluteUrls);
            $seen[$id] = true;
        }

        foreach ($rows as $id => $row) {
            if (isset($seen[$id])) {
                continue;
            }
            if (isset($row->status) && (int) $row->status !== 1) {
                continue;
            }
            if (self::serviceCatalogGroup($row) !== $group) {
                continue;
            }
            $base = [
                'id' => $id,
                'name' => (string) ($row->service_name ?? 'Service'),
                'route' => 'users/services/bill-payments?id='.$id,
            ];
            if ($group === 'bbps') {
                $base['logo'] = $fallback;
            } else {
                $base['icon'] = $fallback;
                $base['type'] = 'prepaid';
            }
            $out[] = self::mergeServiceCatalogItem($base, $row, $group, $fallback, $absoluteUrls);
        }

        $out = self::excludeLockedServices($out);
        usort($out, function ($a, $b) use ($rows) {
            $ao = (int) (($rows[(int) ($a['id'] ?? 0)]->sort_order ?? $a['id'] ?? 0));
            $bo = (int) (($rows[(int) ($b['id'] ?? 0)]->sort_order ?? $b['id'] ?? 0));
            return $ao <=> $bo;
        });

        return $out;
    }

    public static function serviceCatalogGroup($row): string
    {
        $group = strtolower(trim((string) ($row->catalog_group ?? '')));
        if (in_array($group, ['recharge', 'bbps'], true)) {
            return $group;
        }
        foreach (config('recharge_services.recharge', []) as $item) {
            if ((int) ($item['id'] ?? 0) === (int) ($row->id ?? 0)) {
                return 'recharge';
            }
        }

        return 'bbps';
    }

    public static function ensureCatalogGroupColumn(): void
    {
        try {
            if (! Schema::hasTable('services')) {
                return;
            }
            if (! Schema::hasColumn('services', 'catalog_group')) {
                Schema::table('services', function ($table) {
                    $table->string('catalog_group', 20)->nullable();
                });
            }
            $rechargeIds = [];
            foreach (config('recharge_services.recharge', []) as $item) {
                $rechargeIds[] = (int) ($item['id'] ?? 0);
            }
            $rechargeIds = array_values(array_filter($rechargeIds));
            if ($rechargeIds !== []) {
                DB::table('services')->whereIn('id', $rechargeIds)->where(function ($q) {
                    $q->whereNull('catalog_group')->orWhere('catalog_group', '');
                })->update(['catalog_group' => 'recharge']);
            }
            DB::table('services')->where(function ($q) {
                $q->whereNull('catalog_group')->orWhere('catalog_group', '');
            })->update(['catalog_group' => 'bbps']);
        } catch (\Throwable $e) {
        }
    }

    public static function providersForApp($serviceIds): \Illuminate\Support\Collection
    {
        $serviceIds = is_array($serviceIds) ? $serviceIds : [(int) $serviceIds];
        $activeServiceIds = [];
        try {
            $q = DB::table('services')->whereIn('id', $serviceIds)->where('status', 1);
            if (Schema::hasColumn('services', 'deleted_at')) {
                $q->where(function ($w) {
                    $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
                });
            }
            $activeServiceIds = $q->pluck('id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $activeServiceIds = array_map('intval', $serviceIds);
        }

        if ($activeServiceIds === []) {
            return collect();
        }

        $query = DB::table('providers')
            ->whereIn('service_id', $activeServiceIds)
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            });

        $cols = ['id', 'provider_name', 'provider_logo', 'service_id', 'status', 'provider_down'];
        if (Schema::hasColumn('providers', 'sort_order')) {
            $query->orderBy('providers.sort_order');
        }
        $rows = $query->orderByDesc('status')->orderBy('provider_name')->get($cols);

        return self::decorateProviderLogos($rows);
    }

    public static function isUserServiceLocked($userId, $serviceId): bool
    {
        try {
            if (! Schema::hasTable('user_service_locks')) {
                return false;
            }

            return DB::table('user_service_locks')
                ->where('user_id', (int) $userId)
                ->where('service_id', (int) $serviceId)
                ->where('is_locked', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function lockedServiceIds($userId): array
    {
        try {
            if (! $userId || ! Schema::hasTable('user_service_locks')) {
                return [];
            }

            return DB::table('user_service_locks')
                ->where('user_id', (int) $userId)
                ->where('is_locked', 1)
                ->pluck('service_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function excludeLockedServices(array $out): array
    {
        $uid = \Session::get('user_id');
        if (! $uid) {
            return $out;
        }
        $locked = self::lockedServiceIds($uid);
        if ($locked === []) {
            return $out;
        }

        return array_values(array_filter($out, function ($item) use ($locked) {
            return ! in_array((int) ($item['id'] ?? 0), $locked, true);
        }));
    }

    private static function mergeServiceCatalogItem(array $item, $row, string $group, string $fallback, bool $absoluteUrls): array
    {
        if ($row) {
            $name = trim((string) ($row->service_name ?? ''));
            if ($name !== '') {
                $item['name'] = $name;
            }
            $icon = trim((string) ($row->service_icon ?? ''));
            if ($icon !== '') {
                $path = self::serviceIconRelativePath($icon, $fallback);
                if ($group === 'bbps') {
                    $item['logo'] = $path;
                } else {
                    $item['icon'] = $path;
                }
            }
        }

        $relative = $group === 'bbps'
            ? self::serviceIconRelativePath($item['logo'] ?? '', $fallback)
            : self::serviceIconRelativePath($item['icon'] ?? '', $fallback);
        $url = self::servicePublicUrl($relative, $fallback);
        $item['icon_url'] = $url;
        $item['logo_url'] = $url;
        $item['status'] = $row ? (int) ($row->status ?? 1) : 1;
        $item['service_down'] = $row ? (int) ($row->service_down ?? 0) : 0;
        $item['catalog_group'] = $row ? self::serviceCatalogGroup($row) : $group;
        if ($absoluteUrls) {
            if ($group === 'bbps') {
                $item['logo'] = $url;
            } else {
                $item['icon'] = $url;
            }
        }

        return $item;
    }

    public static function commissionFieldsForRole($row, $roleId): array
    {
        $roleId = (int) $roleId;
        $pick = function ($type, $value) {
            $type = trim((string) ($type ?? ''));
            if ($type === '0') {
                $type = '';
            }
            if ($value === null || $value === '') {
                return [$type, ''];
            }

            return [$type, (string) $value];
        };

        if ($roleId === 3) {
            [$type, $value] = $pick($row->ap_amount_type ?? null, $row->ap_amount_value ?? null);
            if ($type === '') {
                return $pick($row->rt_amount_type ?? null, $row->rt_amount_value ?? null);
            }

            return [$type, $value];
        }
        if ($roleId === 6) {
            return $pick($row->rt_amount_type ?? null, $row->rt_amount_value ?? null);
        }
        if ($roleId === 5) {
            return $pick($row->dt_amount_type ?? null, $row->dt_amount_value ?? null);
        }
        if ($roleId === 4) {
            return $pick($row->md_amount_type ?? null, $row->md_amount_value ?? null);
        }
        if ($roleId === 2) {
            return $pick($row->wt_amount_type ?? null, $row->wt_amount_value ?? null);
        }

        return ['', ''];
    }

    public static function providerLogoUrl(?string $logo): string
    {
        return function_exists('provider_logo_url')
            ? provider_logo_url($logo)
            : (string) $logo;
    }

    public static function decorateProviderLogos($rows)
    {
        return collect($rows)->map(function ($row) {
            $obj = is_object($row) ? clone $row : (object) $row;
            $file = (string) ($obj->provider_logo ?? $obj->p_logo ?? '');
            $url = self::providerLogoUrl($file);
            $obj->provider_logo = $url;
            if (isset($obj->p_logo)) {
                $obj->p_logo = $url;
            }
            $obj->provider_logo_url = $url;
            $obj->provider_down = (int) ($obj->provider_down ?? 0);
            $userId = (int) (Session::get('user_id') ?? 0);
            $obj->user_down = ($userId > 0 && self::isProviderDownForUser($obj->id ?? 0, $userId)) ? 1 : 0;

            return $obj;
        })->values();
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
        return '20260905-WEB-019';
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

if (! function_exists('provider_logo_filename')) {
    function provider_logo_filename(?string $logo): string
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

if (! function_exists('provider_logo_url')) {
    function provider_logo_url(?string $logo): string
    {
        $file = provider_logo_filename($logo);
        if ($file === '') {
            return asset('service_icon/mobile_1.png');
        }

        foreach (['provider_logo', 'bank_logo'] as $folder) {
            if (is_file(public_path($folder.'/'.$file))) {
                return asset($folder.'/'.$file);
            }
        }

        $adminHost = rtrim((string) env('ADMIN_HOST', ''), '/');
        if ($adminHost !== '') {
            return $adminHost.'/provider_logo/'.$file;
        }

        return asset('provider_logo/'.$file);
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

