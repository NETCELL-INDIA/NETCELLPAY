<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
use Illuminate\Support\Str;
class ReportsController extends Controller
{

    public function fetchAllRecharge(Request $post)
    {
        try {
            $this->mergeJsonBody($post);

            $userId = $this->resolveAppUserId($post);
            if ($userId <= 0) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user_id or login_key is required.',
                ], 200);
            }

            [$page, $limit] = $this->resolveAppPaging($post, 50, 50);
            [$from_date, $to_date] = $this->resolveAppDateRange(
                $post->input('from_date'),
                $post->input('to_date'),
                0
            );

            $query = DB::table('reports')
                ->select(
                    'reports.id',
                    'reports.number',
                    'reports.amount',
                    'reports.status',
                    'reports.closing_balance',
                    'reports.provider_id',
                    'reports.total_amount',
                    'reports.order_id',
                    'reports.operator_id',
                    'reports.opening_balance',
                    'reports.commission',
                    'reports.path',
                    'reports.created_at',
                    'providers.provider_logo',
                    'providers.provider_name',
                    'providers.id as p_id',
                    'users.id as u_id',
                    'users.outlet_name',
                    'users.mobile_number',
                    'users.first_name',
                    'users.last_name',
                    'users.email_address'
                )
                ->leftJoin('providers', 'providers.id', '=', 'reports.provider_id')
                ->leftJoin('users', 'users.id', '=', 'reports.user_id')
                ->where('reports.user_id', $userId)
                ->whereBetween('reports.created_at', [$from_date, $to_date])
                ->whereIn('reports.transaction_type', ['Recharge']);

            if ($post->filled('order_id')) {
                $query->where('reports.order_id', $post->order_id);
            }
            if ($post->filled('number')) {
                $query->where('reports.number', $post->number);
            }
            if ($post->filled('req_order_id')) {
                $query->where('reports.request_order_id', $post->req_order_id);
            }

            $start = ($page - 1) * $limit;
            $list = $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();

            return response()->json([
                'type' => 'success',
                'message' => 'Fetch Successfuly.',
                'data' => $list,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch recharge reports. Please try again.',
            ], 200);
        }
    }


    public function fetchAllBillPayment(Request $post)
    {

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
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

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "reports";
        } 
        
        $query = DB::table('reports')
        ->select(
            'reports.id',
            'reports.number',
            'reports.amount',
            'reports.status',
            'reports.closing_balance',
            'reports.provider_id',
            'reports.number',
            'reports.total_amount',
            'reports.order_id',
            'reports.operator_id',
            'reports.opening_balance',
            'reports.commission',
            'reports.path',
            'reports.created_at',
            'providers.provider_logo',
            'providers.provider_name',
            'providers.id as p_id',
            'users.id as u_id',
            'users.outlet_name',
            'users.mobile_number',
            'users.first_name',
            'users.last_name',
            'users.middle_name',
            'users.email_address',
        )
        ->join('providers','providers.id','=','reports.provider_id')
        ->join('users','users.id','=','reports.user_id')
        ->where('reports.user_id', $post->user_id)->whereBetween('reports.created_at', [$from_date,$to_date])->whereIn('reports.transaction_type',['Bill Payment','Bill Pay']);

        if($post->page){
            $page = $post->page; 
        }else{
            $page = 1; 
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit; 
            }else{
                $limit = 10;  
            }
        }else{
            $limit = 10; 
        }

        if($post->order_id != ""){
            $query->where('reports.order_id', $post->order_id);
        }

        if($post->number != ""){
            $query->where('reports.number', $post->number);
        }
        if($post->req_order_id != ""){
            $query->where('reports.request_order_id', $post->req_order_id);
        }
        $start= ($page-1) * $limit;



        $list =  $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }


    public function fetchAllMoneyTransfer(Request $post)
    {

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
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

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "reports";
        } 
        
        $query = DB::table('reports')
        ->select(
            'reports.id',
            'reports.number',
            'reports.amount',
            'reports.status',
            'reports.closing_balance',
            'reports.provider_id',
            'reports.number',
            'reports.total_amount',
            'reports.order_id',
            'reports.operator_id',
            'reports.opening_balance',
            'reports.commission',
            'reports.path',
            'reports.created_at',
            'providers.provider_logo',
            'providers.provider_name',
            'providers.id as p_id',
            'users.id as u_id',
            'users.outlet_name',
            'users.mobile_number',
            'users.first_name',
            'users.last_name',
            'users.middle_name',
            'users.email_address',
        )
        ->join('providers','providers.id','=','reports.provider_id')
        ->join('users','users.id','=','reports.user_id')
        ->where('reports.user_id', $post->user_id)->whereBetween('reports.created_at', [$from_date,$to_date])->whereIn('reports.transaction_type',['Money Transfer']);

        if($post->page){
            $page = $post->page; 
        }else{
            $page = 1; 
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit; 
            }else{
                $limit = 10;  
            }
        }else{
            $limit = 10; 
        }

        if($post->order_id != ""){
            $query->where('reports.order_id', $post->order_id);
        }

        if($post->number != ""){
            $query->where('reports.number', $post->number);
        }
        if($post->req_order_id != ""){
            $query->where('reports.request_order_id', $post->req_order_id);
        }
        $start= ($page-1) * $limit;



        $list =  $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }


    public function fetchAllAccount(Request $post)
    {
        try {
            $this->mergeJsonBody($post);

            $userId = $this->resolveAppUserId($post);
            if ($userId <= 0) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user_id or login_key is required.',
                ], 200);
            }

            [$page, $limit] = $this->resolveAppPaging($post, 50, 200);
            [$from_date, $to_date] = $this->resolveAppDateRange(
                $post->input('from_date'),
                $post->input('to_date'),
                30
            );

            $query = DB::table('reports')
                ->select(
                    'reports.id',
                    'reports.amount',
                    'reports.status',
                    'reports.closing_balance',
                    'reports.total_amount',
                    'reports.order_id',
                    'reports.remark',
                    'reports.opening_balance',
                    'reports.transaction_type',
                    'reports.fund_type',
                    'reports.transaction_date',
                    'reports.created_at',
                    'reports.number',
                    'u.id as u_id',
                    'u.outlet_name as u_outlet_name',
                    'u.mobile_number as u_mobile_number',
                    'u.first_name as u_first_name',
                    'u.last_name as u_last_name',
                    'u.email_address as u_email_address',
                    'd.id as d_id',
                    'd.outlet_name as d_outlet_name',
                    'd.mobile_number as d_mobile_number',
                    'd.first_name as d_first_name',
                    'd.last_name as d_last_name',
                    'c.id as c_id',
                    'c.outlet_name as c_outlet_name',
                    'c.mobile_number as c_mobile_number',
                    'c.first_name as c_first_name',
                    'c.last_name as c_last_name'
                )
                ->leftJoin('users as u', 'u.id', '=', 'reports.user_id')
                ->leftJoin('users as d', 'd.id', '=', 'reports.debit_user_id')
                ->leftJoin('users as c', 'c.id', '=', 'reports.credit_user_id')
                ->where('reports.user_id', $userId)
                ->whereBetween('reports.created_at', [$from_date, $to_date]);

            if ($post->filled('order_id')) {
                $query->where('reports.order_id', $post->order_id);
            }
            if ($post->filled('number')) {
                $query->where('reports.number', $post->number);
            }
            if ($post->filled('req_order_id')) {
                $query->where('reports.request_order_id', $post->req_order_id);
            }

            $start = ($page - 1) * $limit;
            $list = $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();

            return response()->json([
                'type' => 'success',
                'message' => 'Account reports fetched successfully',
                'data' => $list,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch account reports. Please try again.',
            ], 200);
        }
    }


    public function fetchAllFund(Request $post)
    {
        try {
            $this->mergeJsonBody($post);

            $userId = $this->resolveAppUserId($post);
            if ($userId <= 0) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user_id or login_key is required.',
                ], 200);
            }

            [$page, $limit] = $this->resolveAppPaging($post, 50, 200);
            [$from_date, $to_date] = $this->resolveAppDateRange(
                $post->input('from_date'),
                $post->input('to_date'),
                0
            );

            $query = DB::table('reports')
                ->select(
                    'reports.id',
                    'reports.amount',
                    'reports.status',
                    'reports.closing_balance',
                    'reports.total_amount',
                    'reports.order_id',
                    'reports.remark',
                    'reports.opening_balance',
                    'reports.transaction_type',
                    'reports.fund_type',
                    'reports.transaction_date',
                    'reports.created_at',
                    'u.id as u_id',
                    'u.outlet_name as u_outlet_name',
                    'u.mobile_number as u_mobile_number',
                    'u.first_name as u_first_name',
                    'u.last_name as u_last_name',
                    'u.email_address as u_email_address',
                    'd.id as d_id',
                    'd.outlet_name as d_outlet_name',
                    'd.mobile_number as d_mobile_number',
                    'd.first_name as d_first_name',
                    'd.last_name as d_last_name',
                    'd.email_address as d_email_address',
                    'c.id as c_id',
                    'c.outlet_name as c_outlet_name',
                    'c.mobile_number as c_mobile_number',
                    'c.first_name as c_first_name',
                    'c.last_name as c_last_name',
                    'c.email_address as c_email_address'
                )
                ->leftJoin('users as u', 'u.id', '=', 'reports.user_id')
                ->leftJoin('users as d', 'd.id', '=', 'reports.debit_user_id')
                ->leftJoin('users as c', 'c.id', '=', 'reports.credit_user_id')
                ->where('reports.user_id', $userId)
                ->whereBetween('reports.created_at', [$from_date, $to_date])
                ->whereIn('reports.transaction_type', [
                    'Transfer Money', 'Receive Money', 'Upi Add Money',
                    'Self Money', 'Money Reverse', 'Reverse Money',
                ]);

            if ($post->filled('order_id')) {
                $query->where('reports.order_id', $post->order_id);
            }

            $start = ($page - 1) * $limit;
            $list = $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();

            foreach ($list as $row) {
                if (empty($row->d_id)) {
                    $row->d_outlet_name = $row->d_outlet_name ?: 'Admin';
                    $row->d_first_name = $row->d_first_name ?: 'Admin';
                    $row->d_mobile_number = $row->d_mobile_number ?: '-';
                }
                if (empty($row->c_id)) {
                    $row->c_outlet_name = $row->c_outlet_name ?: 'Admin';
                    $row->c_first_name = $row->c_first_name ?: 'Admin';
                    $row->c_mobile_number = $row->c_mobile_number ?: '-';
                }
            }

            return response()->json([
                'type' => 'success',
                'message' => 'Fund reports fetched successfully',
                'data' => $list,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch fund reports. Please try again.',
            ], 200);
        }
    }


    public function fetchAllComplaints(Request $post)
    {

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
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

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "complaints";
        } 
        
        $query = DB::table('complaints')
        ->select(
            'complaints.id',
            'complaints.request_id',
            'complaints.created_at',
            'complaints.decision_date',
            'complaints.decision_remark',
            'complaints.status',
            'complaints.report_id',
            ///
            'd.id as d_id',
            'd.outlet_name as d_outlet_name',
            'd.mobile_number as d_mobile_number',
            'd.first_name as d_first_name',
            'd.last_name as d_last_name',
            'd.middle_name as d_middle_name',
            'd.email_address as d_email_address',
            ///
            's.service_name as service_name',
        )
        ->leftJoin('users as d','d.id','=','complaints.decision_by')
        ->leftJoin('services as s','s.id','=','complaints.service_id')
        ->where('complaints.user_id', $post->user_id)->whereBetween('complaints.created_at', [$from_date,$to_date])
        ;

        if($post->page){
            $page = $post->page; 
        }else{
            $page = 1; 
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit; 
            }else{
                $limit = 10;  
            }
        }else{
            $limit = 10; 
        }

        if($post->request_id != ""){
            $query->where('complaints.request_id', $post->request_id);
        }
        $start= ($page-1) * $limit;



        $list =  $query->orderBy('complaints.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }

    public function fetchAllFundRequest(Request $post)
    {

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
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

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "fund_requests";
        } 
        
        $query = DB::table('fund_requests')
        ->select(
            'fund_requests.id',
            'fund_requests.status',
            'fund_requests.created_at',
            'fund_requests.decision_date',
            'fund_requests.request_date',
            'fund_requests.order_id',
            'fund_requests.transaction_number',
            'fund_requests.transfer_mode',
            'fund_requests.decision_remark',
            'fund_requests.remark',
            'fund_requests.amount',
            ///
            'd.id as d_id',
            'd.outlet_name as d_outlet_name',
            'd.mobile_number as d_mobile_number',
            'd.first_name as d_first_name',
            'd.last_name as d_last_name',
            'd.middle_name as d_middle_name',
            'd.email_address as d_email_address',
            ///
            'b.account_name as account_name',
            'b.account_number as account_number',
            'b.bank_name as bank_name',
            'b.account_type as account_type',
        )
        ->leftJoin('users as d','d.id','=','fund_requests.decision_by')
        ->leftJoin('banks as b','b.id','=','fund_requests.bank_id')
        ->where('fund_requests.user_id', $post->user_id)->whereBetween('fund_requests.created_at', [$from_date,$to_date])
        ;

        if($post->page){
            $page = $post->page; 
        }else{
            $page = 1; 
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit; 
            }else{
                $limit = 10;  
            }
        }else{
            $limit = 10; 
        }

        if($post->order_id != ""){
            $query->where('fund_requests.order_id', $post->order_id);
        }
        $start= ($page-1) * $limit;



        $list =  $query->orderBy('fund_requests.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }



    function daybookReports(Request $post)
    {
        try {
            $this->mergeJsonBody($post);

            $userId = $this->resolveAppUserId($post);
            if ($userId <= 0) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user_id or login_key is required.',
                ], 200);
            }

            [$from_date, $to_date] = $this->resolveAppDateRange(
                $post->input('from_date'),
                $post->input('to_date'),
                0
            );

            $user = DB::table('users')->where('id', $userId)->first();
            if (! $user) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'User not found.',
                ], 200);
            }

            $base = function () use ($from_date, $to_date, $userId) {
                return DB::table('reports')
                    ->where('user_id', $userId)
                    ->whereBetween('created_at', [$from_date, $to_date]);
            };

            $report_s = $base()->where('transaction_type', 'Recharge')->where('status', 'Success')->get();
            $report_p = $base()->where('transaction_type', 'Recharge')->where('status', 'Pending')->get();
            $report_f = $base()->where('transaction_type', 'Recharge')->where('status', 'Failed')->get();
            $report_r = $base()->where('transaction_type', 'Refund')->where('status', 'Success')->get();
            $report_receive_money = $base()->where('transaction_type', 'Receive Money')->where('status', 'Success')->get();
            $report_upi_add_money = $base()->where('transaction_type', 'Upi Add Money')->where('status', 'Success')->get();
            $Report_Success_Commission = $report_s;
            $Report_Pending_Commission = $report_p;
            $Report_Parent_Commission = $base()->where('transaction_type', 'Commission')->where('status', 'Success')->get();
            $Report_Parent_Reverse_Commission = $base()->where('transaction_type', 'Reverse Commission')->where('status', 'Success')->get();
            $Total_Complaints_Count = DB::table('complaints')
                ->whereIn('status', ['Open', 'Under Review'])
                ->where('user_id', $userId)
                ->count();

            $receive = (float) $report_receive_money->sum('total_amount') + (float) $report_upi_add_money->sum('total_amount');

            $data_n = [
                'rc_success_amount' => (float) $report_s->sum('total_amount'),
                'rc_success_hit' => $report_s->count(),
                'rc_pending_amount' => (float) $report_p->sum('total_amount'),
                'rc_pending_hit' => $report_p->count(),
                'rc_failed_amount' => (float) $report_f->sum('total_amount'),
                'rc_failed_hit' => $report_f->count(),
                'rc_refund_amount' => (float) $report_r->sum('total_amount'),
                'rc_refund_hit' => $report_r->count(),
                'rc_receive_money' => $receive,
                'rc_upi_add_money' => (float) $report_upi_add_money->sum('total_amount'),
                'rc_commission' => (float) $Report_Success_Commission->sum('commission')
                    + (float) $Report_Pending_Commission->sum('commission')
                    + (float) $Report_Parent_Commission->sum('amount')
                    - (float) $Report_Parent_Reverse_Commission->sum('amount'),
                'rc_complaint_hit' => (int) $Total_Complaints_Count,
                'wallet_balance' => (float) ($user->wallet_balance ?? 0),
            ];

            return response()->json([
                'type' => 'success',
                'message' => 'Fetch Successfuly.',
                'data' => $data_n,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch daybook reports. Please try again.',
            ], 200);
        }
    }

    private function mergeJsonBody(Request $post): void
    {
        if (! empty($post->all()) && ($post->filled('login_key') || $post->filled('user_id'))) {
            return;
        }
        $json = json_decode($post->getContent(), true);
        if (is_array($json)) {
            $post->merge($json);
        }
    }

    private function resolveAppUserId(Request $post): int
    {
        $userId = (int) $post->input('user_id');
        if ($userId <= 0 && $post->filled('login_key')) {
            $userId = (int) DB::table('users')->where('login_key', $post->login_key)->value('id');
        }

        return $userId;
    }

    /** @return array{0:int,1:int} */
    private function resolveAppPaging(Request $post, int $defaultLimit = 50, int $maxLimit = 200): array
    {
        $page = max(1, (int) ($post->input('page') ?: 1));
        $asked = (int) ($post->input('limit') ?: $defaultLimit);
        $limit = ($asked > 0 && $asked <= $maxLimit) ? $asked : $defaultLimit;

        return [$page, $limit];
    }

    /**
     * Accept Y-m-d, d-m-Y, d/m/Y (common Android formats).
     * @return array{0:string,1:string}
     */
    private function resolveAppDateRange($fromRaw, $toRaw, int $defaultDaysBack = 0): array
    {
        $from = $this->parseAppDate($fromRaw, false);
        $to = $this->parseAppDate($toRaw !== null && $toRaw !== '' ? $toRaw : $fromRaw, true);

        if ($from === null && $to === null) {
            if ($defaultDaysBack > 0) {
                $from = Carbon::now()->subDays($defaultDaysBack)->format('Y-m-d').' 00:00:00';
            } else {
                $from = Carbon::today()->format('Y-m-d').' 00:00:00';
            }
            $to = Carbon::today()->format('Y-m-d').' 23:59:59';
        } elseif ($from === null) {
            $from = Carbon::parse(substr($to, 0, 10))->format('Y-m-d').' 00:00:00';
        } elseif ($to === null) {
            $to = Carbon::parse(substr($from, 0, 10))->format('Y-m-d').' 23:59:59';
        }

        if (strcmp($from, $to) > 0) {
            [$from, $to] = [$to, $from];
            // ensure end-of-day on the later bound
            $to = Carbon::parse(substr($to, 0, 10))->format('Y-m-d').' 23:59:59';
            $from = Carbon::parse(substr($from, 0, 10))->format('Y-m-d').' 00:00:00';
        }

        return [$from, $to];
    }

    private function parseAppDate($raw, bool $endOfDay): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || strcasecmp($raw, 'null') === 0 || strcasecmp($raw, 'undefined') === 0) {
            return null;
        }

        // Keep only date part if a time is already appended incorrectly
        $dateOnly = preg_replace('/\s+.*$/', '', $raw);
        $dateOnly = str_replace(['.', '\\'], ['/', '/'], (string) $dateOnly);

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'm/d/Y'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $dateOnly);
                if ($dt instanceof Carbon && $dt->format($fmt) === $dateOnly) {
                    return $endOfDay
                        ? $dt->format('Y-m-d').' 23:59:59'
                        : $dt->format('Y-m-d').' 00:00:00';
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $dt = Carbon::parse($raw);

            return $endOfDay
                ? $dt->format('Y-m-d').' 23:59:59'
                : $dt->format('Y-m-d').' 00:00:00';
        } catch (\Throwable $e) {
            return null;
        }
    }
}
