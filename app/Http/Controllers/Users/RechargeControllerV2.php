<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\PlanInfoFetchService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
use Illuminate\Support\Str;
class RechargeControllerV2 extends Controller
{
    public function mobileIndex(Request $post)
    {
        return view('users.services.mobile');
    }

    public function dthIndex(Request $post)
    {
        return view('users.services.dth');
    }


    public function rechargeCall(Request $post)
    {
        $rules = array(
            'provider_id' => 'required|numeric',
            'service_id' => 'required|numeric',
            'state_id' => 'numeric',
            'number'  => 'required|min:8|max:12',
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|digits:4'
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => $error
            ));
        }
        if(isset($post->api_key)){
            $rules = array(
                'request_order_id' => 'required',
            );
            $validator = \Validator::make($post->all(), array_reverse($rules));
            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    $error = $value[0];
                }
                return response()->json(array(
                    'status' => 'Failed',
                    'type' => 'error',  
                    'message' => $error
                ));
            }
            $user = DB::table('users')->where('api_key',$post->api_key)->first();
            $request_order_id = DB::table('reports')->where('user_id', $user->id)->where('request_order_id', $post->request_order_id)->first();
            if($request_order_id){
                return response()->json(array(
                    'status' => 'Failed',
                    'type' => 'error',  
                    'message' => "request order id already exists."
                )); 
            }
            $post['path'] = "Api";
        }else if(isset($post->login_key)){
            $user = DB::table('users')->where('login_key',$post->login_key)->where('id',$post->user_id)->first();
            $post['path'] = "App";
        }else{
            $user = DB::table('users')->where('id',Session::get('user_id'))->where('login_key',Session::get('login_key'))->first();
            $post['path'] = "Web";
        }

        //return $user;
        
        if(!$user){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "user not found"
            )); 
        }

        if($user->status != 1){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Account Not Active.Contact To Admin"
            )); 
        }
        //User Wize Service Active/Deactive
        if(!\helpers::verifyUserPin($user->t_pin, $post->pin)){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Wrong Pin"
            )); 
        }

        if($user->wallet_balance < $post->amount){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error', 
                'message' => "You Have Insufficient your Wallet Balance"
            ));
        }

        if(($user->wallet_balance - $post->amount) > ($user->minium_balance)){
                    
        }else{
           return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Please contact your admin your minimum maintain balance is ".$user->minium_balance
            )); 
        }

        $provider = DB::table('providers')->where('id', $post->provider_id)->first();

        if(!$provider){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Wrong Provider Id"
            )); 
        }

        if($provider->provider_down == 1){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "This time ".$provider->provider_name." provider down please try again."
            ));
        }

        $chk_block_amount = DB::table('amount_blocks')->where('provider_id',$post->provider_id)->first();

       // return $chk_block_amount;
        if($chk_block_amount){
            if($chk_block_amount->amount == $post->amount){
                return response()->json(array(
                    'status' => 'Failed',
                    'type' => 'error',  
                    'message' => "This amount block please contact your admin."
                ));
            } 
        }

        $previouspayment = DB::table('reports')->where('user_id', $user->id)->where('number', $post->number)->where('total_amount', $post->amount)->where('provider_id', $post->provider_id)->whereBetween('created_at', [Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')])->count();
        $previouspayment = 0;
        if($previouspayment != 0){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Same Transaction Repeat"
            ));            
        }


        $post['order_id'] = "RC".date("YmdHis").rand(11111, 999999).rand(1,3).rand(3,6).rand(6,9);

        // Log incoming recharge request for traceability
        try {
            DB::table('apilogs')->insert([
                'url' => 'recharge_request_v2',
                'modal' => 'RechargeRequest',
                'txnid' => $post['order_id'],
                'header' => json_encode($_SERVER),
                'request' => json_encode($post->all()),
                'response' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failure
        }
        
        $post['commission'] = \helpers::getCommission($post->amount, $user->scheme_id, $post->provider_id, $user->role_id);
        $total_amount = $post->amount;
        $post['user_id'] = $user->id;
        $post['number'] = $post->number;
        $post['amount'] = $post->amount - $post['commission'];
        $post['total_amount'] = $total_amount;  
        $post['admin_commission'] =  0;
        $post['api_commission'] = 0;
        $post['fund_type'] = "Debit";
        $post['transaction_type'] = "Recharge";
        $post['transaction_date'] = Carbon::now().":".rand(111,999);
        $post['created_at'] = Carbon::now();
        $post['updated_at'] = Carbon::now();
        $post['provider_id'] = $post->provider_id;
        $post['service_id'] = $provider->service_id;
        $post['api_id'] = $provider->api_id;
        $post['remark'] = "Recharge For Rs. ".$total_amount." Number ".$post->number;
        $post['status'] = "Pending";

        $post['ip_address'] = \helpers::getIp();
        $post['opening_balance'] = $user->wallet_balance;
        $provider_code = \helpers::ApiProviderCode($provider->api_id, $post->provider_id);
        
        if($post['commission'] == 0){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Your commission not set. Contact your parent"
            ));
        }

        if($provider_code == 0 ||$provider_code == ""){
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',  
                'message' => "Please contact admin error operator code"
            ));
        }
        //unset($post['state_id']);
        unset($post['pin']);
        unset($post['_token']);
        unset($post['api_key']);
        unset($post['login_key']);
        //unset($post['user_id']);
        try {
            DB::beginTransaction();
            $debit = DB::table('users')->where('id', $user->id)->decrement('wallet_balance', $post->amount);
            if($debit){
                $user = DB::table('users')->where('id',$user->id)->first();
                //echo "<pre>";print_r($user);die;
                $post['closing_balance'] = $user->wallet_balance;
                $report = DB::table('reports')->insertGetId($post->all());
                DB::commit();
                


                if($report){
                    $api_result = \helpers::RunApi($provider->api_id,$post->provider_id,$report,'Recharge');
                    if($api_result){
                        if($api_result['status'] == "Success"){
                            DB::table('reports')->where('id', $report)->update($api_result);
                            $set_com = \helpers::SetCommission($report);
                           //return $set_com;die;
                        }else if($api_result['status'] == "Failed"){  
                            //DB::table('users')->where('id', $user->id)->increment('wallet_balance', $post->amount);
                            if($provider->backup_api_id == 0){
                                DB::table('reports')->where('id', $report)->update($api_result);
                                \helpers::refund_row($report);
                            }else{
                                DB::table('reports')->where('id', $report)->update(['api_id'=>$provider->backup_api_id]);
                                $api_result = \helpers::RunApi($provider->backup_api_id,$post->provider_id,$report,'Recharge');
                                if($api_result){
                                    if($api_result['status'] == "Success"){
                                        DB::table('reports')->where('id', $report)->update($api_result);
                                        $set_com = \helpers::SetCommission($report);
                                    //return $set_com;die;
                                    }else if($api_result['status'] == "Failed"){  
                                        //DB::table('users')->where('id', $user->id)->increment('wallet_balance', $post->amount);
                                            DB::table('reports')->where('id', $report)->update($api_result);
                                            \helpers::refund_row($report);
                                    }else{
                                        DB::table('reports')->where('id', $report)->update($api_result);
                                    }

                                    $data_show['number'] = $post->number;
                                    $data_show['status'] = $api_result['status'];
                                    $data_show['amount'] = $total_amount;
                                    $data_show['order_id'] = $api_result['order_id'];
                                    if(isset($post->api_key)){
                                        $data_show['request_order_id'] = $post->request_order_id;
                                    }
                                    $data_show['operator_id'] = $api_result['operator_id'];
                                    $data_show['type'] = "success";
                                    $data_show['remark'] = $api_result['remark'];
                                    $data_show['message'] = $api_result['remark'];
                                    $data_show['commission'] = 0;
                                    $data_show['id'] = $report;
                                    $data_show['date_time'] = Carbon::now();
                                    if($api_result['status'] == "Success" ||$api_result['status'] == "Pending"){
                                        $data_show['commission'] = $post->commission;
                                    }
                                    return response()->json($data_show);
                                }
                            }

                            
                        }else{
                            DB::table('reports')->where('id', $report)->update($api_result);
                        }


                        $data_show['number'] = $post->number;
                        $data_show['status'] = $api_result['status'];
                        $data_show['amount'] = $total_amount;
                        $data_show['order_id'] = $api_result['order_id'];
                        if(isset($post->api_key)){
                            $data_show['request_order_id'] = $post->request_order_id;
                        }
                        $data_show['operator_id'] = $api_result['operator_id'];
                        $data_show['type'] = "success";
                        $data_show['remark'] = $api_result['remark'];
                        $data_show['message'] = $api_result['remark'];
                        $data_show['commission'] = 0;
                        $data_show['id'] = $report;
                        $data_show['date_time'] = Carbon::now();
                        if($api_result['status'] == "Success" ||$api_result['status'] == "Pending"){
                            $data_show['commission'] = $post->commission;
                        }
                        return response()->json($data_show);
                    }

                    //echo "<pre>";print_r($api_result);die;
                }


                // return response()->json(array(
                //     'type' => 'error',   
                //     'message' => "Transaction Is Hold Contact to Admin."
                // ));
            }else{
                DB::rollback();
                return response()->json(array(
                    'status' => 'Failed',
                    'type' => 'error',   
                    'message' => "Internal Server Error U"
                ));
            }
        } catch (\Throwable $th) {
            //DB::rollback();
          return $th->getMessage();
            return response()->json(array(
                'type' => 'error',   
                'message' => "Internal Server Error C"
            ));
        }
        
        //Ptd($post->all());
        //echo "<pre>";print_r($post->all());die;
    }

    public function providerStateList(Request $post)
    {
        $rules = array(
            'service' => 'required|numeric',
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
        $provider = DB::table('providers')->select('id','provider_name')->where('service_id',$post->service)->where('deleted_at', '!=' , 1)->where('status',1)->get();
        $states = DB::table('states')->select('id','state_name')->where('status',1)->get();
        if($provider){
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['provider'] = $provider;
            $data['state'] = $states;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }



    public function fetchAll(Request $post)
    {
        $table = "reports";
        $list = DB::table($table)
        ->where('user_id', Session::get('user_id'))
        ->whereIn('transaction_type',['Recharge'])
        ->orderBy('id', 'DESC')->take(5)->get();
        //echo "<pre>";print_r($list);die;
        $output = '';
		if ($list->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Transaction Details</th>
                <th>Order Id</th>
                <th>Operator Id</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Amount</th>
                <th>Commission/Surcharge</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
                if($list->status == "Success"){
                    $bg = "success";
                }elseif ($list->status == "Failed") {
                    $bg = "danger";
                }elseif ($list->status == "Refunded") {
                    $bg = "secondary";
                }else{
                    $bg = "warning";
                }
                if($list->status == "Success" && $list->complaint_id == 0){
                    $action = '<button type="submit" class="btn btn-secondary" id="receipt_btn" onclick="receiptView(`' . $list->id . '`)"><i class="ri-file-list-3-line"></i> Receipt</button>  <button type="submit" class="btn btn-warning" id="complaint_btn" onclick="complaintView(`' . $list->id . '`,`' . $list->order_id . '`)"><i class="ri-questionnaire-fill"></i> Complaint</button>';
                }else{
                    $action = '<h5>--</h5>';
                }

                $provider = DB::table('providers')->where('id', $list->provider_id)->first();
                $service = DB::table('services')->where('id', $list->service_id)->first();

				$output .= '<tr>
                <td>' . $i . '</td>
                <td>
                    ' . $list->transaction_date . ' </br>
                    Number : ' . $list->number . ' </br>
                    ' . Str::of($provider->provider_name)->upper() . 
                    ' - ' . Str::of($service->service_name)->upper() . 
                    ' - ' . Str::of($list->path)->upper() . '</br>
                </td>
                <td>' . $list->order_id . '</td>
                <td>' . $list->operator_id . '</td>
                <td>
                    <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->status . '</span>
                </td>
                <td style="font-size: 18px;"> ₹ ' . $list->total_amount . '</td> 
                <td style="font-size: 18px;"> ₹ ' . $list->amount . '</td> 
                <td style="font-size: 18px;"> ₹ ' . $list->commission . '</td> 
                <td>
                ' . $action .'
                </td>
              </tr>';
              $i++;
			}
			$output .= '</tbody></table>';
			echo $output;
		} else {
			echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
		}
        
    }


    public function getRechargeReciept(Request $post)
    {
        if(isset($post->api_key)){
            $rules = array(
                'request_order_id' => 'required',
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
            $user = DB::table('users')->where('api_key', $post->api_key)->first();
            if(!$user){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "user not found"
                )); 
            }
            $report_f = DB::table('reports')->where('request_order_id', $post->request_order_id)->where('user_id', $user->id)->first();
            if(!$report_f){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "record not found"
                )); 
            }
            $post['id'] = $report_f->id;
        }else{
            $rules = array(
                'id' => 'required|numeric',
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
            if(isset($post->login_key)){
                $user = DB::table('users')->where('id',$post->user_id)->where('login_key',$post->login_key)->first();
            }else{
                $user = DB::table('users')->where('id',Session::get('user_id'))->first();
            }
            if(!$user){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "user not found"
                )); 
            }

        }
        

        
        if($user->status != 1){
            return response()->json(array(
                'type' => 'error',  
                'message' => "Account Not Active.Contact To Admin"
            )); 
        }

        $reports = DB::table('reports')->select( 'order_id',
            'reports.created_at',
            'reports.transaction_type',
            'reports.remark',
            'reports.path',
            'reports.status',
            'reports.operator_id',
            'reports.total_amount',
            'reports.amount',
            'reports.provider_id',
            'reports.service_id',
            'reports.request_order_id',
            'reports.commission',
            'reports.number',
            'users.mobile_number',
            'users.email_address',
            'users.outlet_name',
            'users.first_name',
            'users.middle_name',
            'users.last_name',
        )
        ->join('users','users.id','=','reports.user_id')
        ->where('reports.user_id', $user->id)->where('reports.id', $post->id)->first();
        if($reports){
            $provider = DB::table('providers')->where('id', $reports->provider_id)->first();
            $service = DB::table('services')->where('id', $reports->service_id)->first();

            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['provider'] = $provider->provider_name." - ".$service->service_name." - ".$reports->path;
            $data['provider_name'] = $provider->provider_name;
            $data['data'] = $reports;
        }else{
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
       //echo "<pre>";print_r("Shiba");die;
    }

 
    public function submitRechargeComplaint(Request $post)
    {


        if(isset($post->api_key)){
            $rules = array(
                'order_id' => 'required',
                'subject' => 'required',
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
            $user = DB::table('users')->where('api_key', $post->api_key)->first();
            if(!$user){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "user not found"
                )); 
            }
            $report_f = DB::table('reports')->where('order_id', $post->order_id)->where('user_id', $user->id)->where('status', "Success")->first();
            if(!$report_f){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "record not found"
                )); 
            }
            $post['id'] = $report_f->id;
            $path = "Api";
        }else{
            $rules = array(
                'id' => 'required|numeric',
                'subject' => 'required',
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
            if(isset($post->login_key)){
                $user = DB::table('users')->where('id',$post->user_id)->where('login_key',$post->login_key)->first();
            }else{
                $user = DB::table('users')->where('id',Session::get('user_id'))->first();
            }
            
            if(!$user){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "user not found"
                )); 
            }
            $path = "Web";
            if($user->role_id == 3){
                $path = "Api";
            }
            
            $check_complaint = DB::table('complaints')->where('report_id', $post->id)->where('status', "Open")->first();
            
            if($check_complaint){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Complaint Already Submit Contact Service provider."
                )); 
            }

        }

        //$user = DB::table('users')->where('id',Session::get('user_id'))->first();
        if($user->status != 1){
            return response()->json(array(
                'type' => 'error',  
                'message' => "Account Not Active.Contact To Admin"
            )); 
        }

        

        $report = DB::table('reports')->where('user_id', $user->id)->where('id', $post->id)->where('complaint_id', 0)->first();
        if($report){
            try {
                $request_id = "SR".date("YmdHis").rand(11111, 999999);
                $complaint = DB::table('complaints')->insertGetId([

                    'user_id' => $user->id,
                    'service_id' => $report->service_id,
                    'order_id' => $report->order_id,
                    'report_id' => $report->id,
                    'request_id' => $request_id,
                    'subject' => $post->subject,
                    'status' => "Open",
                    'path' => $path,
                    'callback_status' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                DB::table('reports')->where('id', $post->id)->update([
                    'complaint_id' => $complaint
                ]);
                if($complaint){
                    $api_details = DB::table('apis')->where('id', $report->api_id)->first();
                    $url = $api_details->complaint_api_url;
                    if($url !=0 || $url !=""){
                        $url = str_replace('{API_USERNAME}', '' . $api_details->api_username . '', $url);
                        $url = str_replace('{API_PASSWORD}', '' . $api_details->api_password . '', $url);
                        $url = str_replace('{API_KEY}', '' . $api_details->api_key . '', $url);
                        $url = str_replace('{ORDER_ID}', '' . $report->order_id . '', $url);
                        $url = str_replace('{SUBJECT}', '' . $post->subject . '', $url);
                        $method = $api_details->complaint_api_method;
                        $header = [];
                        $parameters = "";
                        if(isset($url) && isset($parameters) && isset($method) && isset($header)){
                            $result = \helpers::curl($url, $method, $parameters, $header, "yes", "COMPLAINT_URL", $request_id);
                            if($result['code'] != 200){
                                return response()->json(array(
                                    'type' => 'success',  
                                    'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Normal"
                                )); 
                            }else{
                                $data= json_decode($result['response'],true);
                                $status = $api_details->complaint_status_value;
                                if(isset($data[$status])){
                                    if($data[$status] == $api_details->complaint_success_value){
                                        return response()->json(array(
                                            'type' => 'success',  
                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.200"
                                        ));
                                    }else if($data[$status] == $api_details->complaint_failed_value){
                                        return response()->json(array(
                                            'type' => 'success',  
                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.400"
                                        ));
                                    }else{
                                        return response()->json(array(
                                            'type' => 'success',  
                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Pending"
                                        ));
                                    }
                                }else{
                                    return response()->json(array(
                                        'type' => 'success',  
                                        'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Api"
                                    ));
                                }
                            }
                        } else{
                            return response()->json(array(
                                'type' => 'success',  
                                'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.500"
                            ));
                        }
                    }else{
                        return response()->json(array(
                            'type' => 'success',  
                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Normal"
                        ));
                    }
                }
            } catch (\Throwable $th) {
                return response()->json(array(
                    'type' => 'error',  
                    'message' => $th
                )); 
            }
            
            
        }else{
            $data['type'] = 'error';
            $data['message'] = "record not found";
        }
        return $data;
    }

    public function RechargeCheckRofferFatch(Request $post)
    {

        $rules = array(
            'provider_id' => 'required|numeric',
            'number'  => 'required|digits:10',
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

        // $user = DB::table('users')->where('id',Session::get('user_id'))->first();
        // if($user->status != 1){
        //     return response()->json(array(
        //         'type' => 'error',  
        //         'message' => "Account Not Active.Contact To Admin"
        //     )); 
        // }
        $serviceKey = PlanInfoFetchService::rofferServiceKey((int) $post->provider_id);
        $result = PlanInfoFetchService::fetch($serviceKey, function ($api) use ($post) {
            $provider_code = \helpers::ApiProviderCode($api->id, $post->provider_id);
            $key = $api->resolved_api_key ?: $api->api_key;
            return rtrim($api->api_url, '/') . '/plans.php?apikey=' . urlencode($key) . '&operator=' . urlencode($provider_code) . '&offer=roffer&tel=' . urlencode($post->number);
        }, 'Roffer', 'ROF');
        //echo "<pre>";print_r($result);die;
        if($result){
            $data= json_decode($result['response'],true);
            return response()->json([
                'type'=> 'success',
                'message'=>'Fatch Successfully',
                'data' => $data['records']
            ]);
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong S"
            ));
        }
   }

   public function dthInfo(Request $post)
    {

        $rules = array(
            'provider_id' => 'required|numeric',
            'number'  => 'required',
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
        $result = PlanInfoFetchService::fetch('dth_customer', function ($api) use ($post) {
            $provider_code = \helpers::ApiProviderCode($api->id, $post->provider_id);
            $key = $api->resolved_api_key ?: $api->api_key;
            return rtrim($api->api_url, '/') . '/Dthinfo.php?apikey=' . urlencode($key) . '&operator=' . urlencode($provider_code) . '&offer=roffer&tel=' . urlencode($post->number);
        }, 'DTH INFO', 'ROF');
        //echo "<pre>";print_r($result);die;
        if($result){
            $data= json_decode($result['response'],true);
            return response()->json([
                'type'=> 'success',
                'message'=>'Fatch Successfully',
                'data' => $data['records']
            ]);
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong S"
            ));
        }
   }


   public function dthHeavyRefresh(Request $post)
    {

        $rules = array(
            'provider_id' => 'required|numeric',
            'number'  => 'required',
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
        $result = PlanInfoFetchService::fetch('dth_heavy_refresh', function ($api) use ($post) {
            $provider_code = \helpers::ApiProviderCode($api->id, $post->provider_id);
            $key = $api->resolved_api_key ?: $api->api_key;
            return rtrim($api->api_url, '/') . '/Dthheavy.php?apikey=' . urlencode($key) . '&operator=' . urlencode($provider_code) . '&offer=roffer&tel=' . urlencode($post->number);
        }, 'DTH INFO', 'ROF');
        //echo "<pre>";print_r($result);die;
        if($result){
            $data= json_decode($result['response'],true);
            return response()->json([
                'type'=> 'success',
                'message'=>'Fatch Successfully',
                'data' => $data['records']
            ]);
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong S"
            ));
        }
   }

   public function RechargeCheckMobile(Request $post)
   {
        $rules = array(
            'number'  => 'required|digits:10',
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
        $result = PlanInfoFetchService::fetch('hlr', function ($api) use ($post) {
            return rtrim($api->api_url, '/') . '/Mobile/OperatorFetchNew?ApiUserID=' . urlencode($api->resolved_username) . '&ApiPassword=' . urlencode($api->resolved_password) . '&Mobileno=' . urlencode($post->number);
        }, 'CHECK_MOBILE', 'CMN');
        if($result){
            $data= json_decode($result['response'],true);
           // return $data;
            $provider  =  DB::table('api_provider_codes')->where('api_id', $result['api_id'])->where('provider_code', $data['OpCode'])->first();
            $provider_data  =  DB::table('providers')->where('id', $provider->provider_id)->first();
            $state = DB::table('states')->where('plan_api_code', $data['CircleCode'])->first();
            return response()->json([
                'type' => 'success',  
                'message'=>'Get Successfully',
                'provider_id' => $provider_data->id,
                'provider_name' => $provider_data->provider_name,
                'provider_logo' => $provider_data->provider_logo,
                'state_id' => $state->id,
                'state_name' => $state->state_name
            ]); 
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong S"
            ));
        }
   }


   public function RechargeCheckPlanFatch(Request $post)
    {


        

        $rules = array(
            'provider_id' => 'required|numeric',
            'state_id' => 'required|numeric',
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

        // $user = DB::table('users')->where('id',Session::get('user_id'))->first();
        // if($user->status != 1){
        //     return response()->json(array(
        //         'type' => 'error',  
        //         'message' => "Account Not Active.Contact To Admin"
        //     )); 
        // }
        $serviceKey = PlanInfoFetchService::planServiceKey((int) $post->provider_id);
        $state = DB::table('states')->where('id', $post->state_id)->first();
        $state_code = str_replace(" ","%20",$state->mplan_state_code);
        $result = PlanInfoFetchService::fetch($serviceKey, function ($api) use ($post, $state_code) {
            $provider_code = \helpers::ApiProviderCode($api->id, $post->provider_id);
            $key = $api->resolved_api_key ?: $api->api_key;
            return rtrim($api->api_url, '/') . '/plans.php?apikey=' . urlencode($key) . '&operator=' . urlencode($provider_code) . '&cricle=' . $state_code;
        }, 'Plans', 'ROP');
        //echo "<pre>";print_r($result);die;
        if($result){
            $data= json_decode($result['response'],true);
            return response()->json([
                'type'=> 'success',
                'message'=>'Fatch Successfully',
                'data' => $data['records']
            ]);
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong S"
            ));
        }
   }




    

    
}
