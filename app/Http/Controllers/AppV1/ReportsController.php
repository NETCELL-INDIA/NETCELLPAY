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
        ->where('reports.user_id', $post->user_id)->whereBetween('reports.created_at', [$from_date,$to_date])->whereIn('reports.transaction_type',['Recharge']);

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
            //'reports.number',
            'reports.amount',
            //'reports.status',
            'reports.closing_balance',
            //'reports.provider_id',
            //'reports.number',
            'reports.total_amount',
            'reports.order_id',
            'reports.remark',
            'reports.opening_balance',
            'reports.transaction_type',
            'reports.fund_type',
            'reports.transaction_date',
            //'providers.provider_logo',
            //'providers.provider_name',
            //'providers.id as p_id',
            'u.id as u_id',
            'u.outlet_name as u_outlet_name',
            'u.mobile_number as u_mobile_number',
            'u.first_name as u_first_name',
            'u.last_name as u_last_name',
            'u.middle_name as u_middle_name',
            'u.email_address as u_email_address',
            ///
            'd.id as d_id',
            'd.outlet_name as d_outlet_name',
            'd.mobile_number as d_mobile_number',
            'd.first_name as d_first_name',
            'd.last_name as d_last_name',
            'd.middle_name as d_middle_name',
            'd.email_address as d_email_address',
            ///
            'c.id as c_id',
            'c.outlet_name as c_outlet_name',
            'c.mobile_number as c_mobile_number',
            'c.first_name as c_first_name',
            'c.last_name as c_last_name',
            'c.middle_name as c_middle_name',
            'c.email_address as c_email_address',
        )
        //->join('providers','providers.id','=','reports.provider_id')
        ->join('users as u','u.id','=','reports.user_id')
        ->join('users as d','d.id','=','reports.debit_user_id')
        ->join('users as c','c.id','=','reports.credit_user_id')
        ->where('reports.user_id', $post->user_id)->whereBetween('reports.created_at', [$from_date,$to_date])
        //->whereIn('reports.transaction_type',['Recharge'])
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


    public function fetchAllFund(Request $post)
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
            //'reports.number',
            'reports.amount',
            //'reports.status',
            'reports.closing_balance',
            //'reports.provider_id',
            //'reports.number',
            'reports.total_amount',
            'reports.order_id',
            'reports.remark',
            'reports.opening_balance',
            'reports.transaction_type',
            'reports.fund_type',
            'reports.transaction_date',
            //'providers.provider_logo',
            //'providers.provider_name',
            //'providers.id as p_id',
            'u.id as u_id',
            'u.outlet_name as u_outlet_name',
            'u.mobile_number as u_mobile_number',
            'u.first_name as u_first_name',
            'u.last_name as u_last_name',
            'u.middle_name as u_middle_name',
            'u.email_address as u_email_address',
            ///
            'd.id as d_id',
            'd.outlet_name as d_outlet_name',
            'd.mobile_number as d_mobile_number',
            'd.first_name as d_first_name',
            'd.last_name as d_last_name',
            'd.middle_name as d_middle_name',
            'd.email_address as d_email_address',
            ///
            'c.id as c_id',
            'c.outlet_name as c_outlet_name',
            'c.mobile_number as c_mobile_number',
            'c.first_name as c_first_name',
            'c.last_name as c_last_name',
            'c.middle_name as c_middle_name',
            'c.email_address as c_email_address',
        )
        //->join('providers','providers.id','=','reports.provider_id')
        ->join('users as u','u.id','=','reports.user_id')
        ->join('users as d','d.id','=','reports.debit_user_id')
        ->join('users as c','c.id','=','reports.credit_user_id')
        ->where('reports.user_id', $post->user_id)->whereBetween('reports.created_at', [$from_date,$to_date])
        ->whereIn('reports.transaction_type',['Transfer Money','Receive Money','Upi Add Money','Self Money','Money Reverse','Reverse Money'])
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
            $query->where('reports.order_id', $post->order_id);
        }

        $start= ($page-1) * $limit;



        $list =  $query->orderBy('reports.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
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
        ->join('users as d','d.id','=','complaints.decision_by')
        ->join('services as s','s.id','=','complaints.service_id')
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
        ->join('users as d','d.id','=','fund_requests.decision_by')
        ->join('banks as b','b.id','=','fund_requests.bank_id')
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
            $query->where('fund_requests.request_id', $post->order_id);
        }
        $start= ($page-1) * $limit;



        $list =  $query->orderBy('fund_requests.id', 'DESC')->offset($start)->limit($limit)->get();



        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $list
        ));
    }



    function daybookReports(Request $post) {

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
       }

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
        $data_n['rc_commission'] = $Report_Success_Commission->sum('commission') + $Report_Pending_Commission->sum('commission') + $Report_Parent_Commission->sum('amount') - $Report_Parent_Reverse_Commission->sum('amount');
        
        $data_n['rc_complaint_hit'] = $Total_Complaints_Count->count();
        $data_n['wallet_balance'] = $user->wallet_balance;

        return response()->json(array(
            'type' => "success",  
            'message' => "Fetch Successfuly.",
            'data' => $data_n
        ));
    }


    

}
