<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use DateTime;
use Session;
use Log;
class allUpiMoneyController extends Controller
{
    public function addMoneyRequestSubmit(Request $post)
    {

        $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
        //return $company;
        if($company->payment_gateway2 != 1){
            return response()->json(array(
                'type' => 'error',  
                'message' => "this service is disable. contact service provider"
            ));
        }

        $key = $company->payment_gateway2_key;
        $minimum = $company->payment_gateway2_min;
        $maximum = $company->payment_gateway2_max;


        $rules = array(
            'amount'  => 'required|numeric|min:'.$minimum.'|max:'.$maximum.'',
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

        $order_id = "PG".date("YmdHis").rand(11111, 999999).rand(1,3).rand(3,6).rand(6,9);;
        if(isset($post->login_key)){
            $user = DB::table('users')->where('login_key',$post->login_key)->where('id',$post->user_id)->first();
        }else{
            $user = DB::table('users')->where('id',Session::get('user_id'))->first();
        }
        
        if($user){
            $update = DB::table('fund_requests')->insert([
                'user_id' => $user->id,
                'request_to' => 1,
                'bank_id' => 1,
                'amount' => $post->amount,
                'request_date' => Carbon::now(),
                'status' => "Pending",
                'transfer_mode' => "UPI",
                'transaction_number' => "",
                'remark' => "Pay Via Instant Money",
                'slip_image' => "slip_image.png",
                'order_id' => $order_id,
                'upi' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            if($update){
                $content = json_encode(array(
                    "token"=> $key,
                    "order_id"=> $order_id,
                    "txn_amount"=> $post->amount, 
                    "txn_note"=> "Upi Add Money",
                    "product_name"=> "Recharge Wallet",
                    "customer_name"=> $user->first_name,
                    "customer_email"=> $user->email_address,
                    "customer_mobile"=> $user->mobile_number,
                    "callback_url"=> route('ApiRedirectAllUpiGateway'),
                ));
                $url = "https://allapi.in/order/create";
                 $curl = curl_init($url);
                 curl_setopt($curl, CURLOPT_HEADER, false);
                 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($curl, CURLOPT_HTTPHEADER,
                        array("Content-type: application/json"));
                 curl_setopt($curl, CURLOPT_POST, true);
                 curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
                 $json_response = curl_exec($curl);
                 $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                //  echo "<pre>";print_r($content);
                // echo "<pre>";print_r($json_response);
                // echo "<pre>";print_r($status);
                // die;
                if($json_response == ''){
                    $update_d['status'] = "Rejected";
                    $update_d['decision_by'] = 1;
                    $update_d['decision_remark'] = "Server is down.please try again.Blank";
                    $update_d['decision_date'] = Carbon::now();
                    DB::table('fund_requests')->where('order_id', $order_id)->update($update_d);
                    return response()->json(array(
                        'type' => 'error',  
                        'message' => "Server is down.please try again.Blank"
                    ));
                }

                if ( $status != 200 ) {
                    $update_d['status'] = "Rejected";
                    $update_d['decision_by'] = 1;
                    $update_d['decision_remark'] = "Server is down.please try again.400";
                    $update_d['decision_date'] = Carbon::now();
                    DB::table('fund_requests')->where('order_id', $order_id)->update($update_d);
                    return response()->json(array(
                        'type' => 'error',  
                        'message' => "Server is down.please try again.400"
                    ));
                }
                curl_close($curl);
                $response = json_decode($json_response, true);
                if($response["status"] == true){    
                    $data['pay_url'] = $response["results"]["payment_url"];
                    $data['type'] = 'success';
                    $data['message'] =  "Link generate sucessfuly.";
                    return response()->json($data);
                }else{
                    $update_d['status'] = "Rejected";
                    $update_d['decision_by'] = 1;
                    $update_d['decision_remark'] = $response['message'];
                    $update_d['decision_date'] = Carbon::now();
                    DB::table('fund_requests')->where('order_id', $order_id)->update($update_d);
                    return response()->json(array(
                        'type' => 'error',  
                        'message' => $response['message']
                    ));
                }
            }else{
                $data['type'] = 'error';
                $data['message'] = "Something went wrong!";
                return response()->json($data);
            }
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "user not found."
            ));
        }
        //echo "<pre>";print_r($post->all());die;
    }

    public function AddMoneyStatus(Request $post,$order_id)
    {  

        if($order_id){
            $report = DB::table('fund_requests')->where('order_id', $post->order_id)->first();
            if($report){
                if($report->status == "Transferred"){
                    $data['status'] = "Success";
                    $data['type'] = 'success';
                    $data['message'] =  "wallet has been credited.";
                    return response()->json($data);
                }else if($report->status == "Rejected"){
                    $data['status'] = "Failed";
                    $data['type'] = 'success';
                    $data['message'] =  "transaction is failed.";
                    return response()->json($data);
                }else if($report->status == "Pending"){
                    $data['status'] = "Pending";
                    $data['type'] = 'success';
                    $data['message'] =  "transaction is hold contact service provider.";
                    return response()->json($data);
                }else{
                    return response()->json(array(
                        'type' => 'error',  
                        'message' => "status not found."
                    ));
                }    
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "record not found."
                ));
            }
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "order id not found."
            ));
        }
    }

    public function AddMoneyCheckStatus(Request $post,$order_id)
    {
        $data['order_id'] = $order_id;
        return view("users.fund.check-status", $data);
    }

    public function AddMoneyCallback(Request $post)
    {
       if($post->ip() !="101.53.145.104"){
            return response()->json(array(
                'type' => 'error',  
                'message' => "please point ip address."
            ));
       }
        if($post->order_id){
            $report = DB::table('fund_requests')->where('order_id', $post->order_id)->where('status', 'Pending')->first();
            if($report){
                if($post->status == "Success"){
                    DB::beginTransaction();
                    try {
                        ///Report by first reciver by
                        $user = DB::table('users')->where('id', $report->user_id)->first();     
                        DB::table('reports')->insert([
                            'user_id' => $user->id,
                            'credit_user_id' => 1,
                            'debit_user_id' => 0,
                            'amount' => $report->amount,
                            'total_amount' => $report->amount,
                            'fund_type' => "Credit",
                            'transaction_type' => "Upi Add Money",
                            'remark' => "upi add money sucessfuly",
                            'order_id' => $report->order_id,
                            'status' => "Success",
                            'opening_balance' => $user->wallet_balance,
                            'closing_balance' => $user->wallet_balance + $report->amount,
                            'transaction_date' => Carbon::now().":".rand(111,999),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        $user_data = DB::table('users')->where('id', $user->id)->first(['id','first_name','mobile_number']);
                        $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance + $report->amount]);
                        $CURRENT_BALANCE = DB::table('users')->where('id', $user_data->id)->first();
                        
                        
                        //////Send Sms By Cron Job Start
                        $slug = 'fund_receive';
                        $type = 'Credit';
                        $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                        $content = $sms_tmp->content;
                        $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                        $content = str_replace('{TYPE}', '' . $type . '', $content);
                        $content = str_replace('{AMOUNT}', '' . $report->amount . '', $content);
                        $content = str_replace('{BY}', 'Payment Gateway', $content);
                        $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                        \helpers::sendQueuedWhatsapp($slug, $user_data->id, (string) $user_data->mobile_number, $content, $sms_tmp);
                        //////Send Sms By Cron Job End
                        ///Status Update in Fund Request Start
                        $update['status'] = "Transferred";
                        $update['decision_by'] = 1;
                        $update['decision_remark'] = "upi add money sucessfuly";
                        $update['transaction_number'] =  $post->utr_number;
                        $update['decision_date'] = Carbon::now();
                        $report = DB::table('fund_requests')->where('id', $report->id)->update($update);
                        ///Status Update in Fund Request End
                        DB::commit();
                        return response()->json(array(
                            'type' => 'success',  
                            'message' => "Your wallet has been credited."
                        ));
                    } catch (\Exception $e) {
                        DB::rollback();
                        return response()->json(array(
                            'type' => 'error',  
                            'message' => "Something Went Wrong."
                        ));
                    }
                    
                }else if($post->status == "Failed"){
                    $update['status'] = "Rejected";
                    $update['decision_by'] = 1;
                    $update['transaction_number'] = $post->order_id;
                    $update['decision_remark'] = "instant add money rejected";
                    $update['decision_date'] = Carbon::now();
                    $report = DB::table('fund_requests')->where('id', $report->id)->update($update);
                    return response()->json(array(
                        'type' => 'success',  
                        'message' => "Your transaction is failed."
                    ));
                }else{
                    return response()->json(array(
                        'type' => 'warning',  
                        'message' => "Your transaction is hold contact service provider."
                    ));
                }
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "record not found."
                ));
            }
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "order id not found."
            ));
        }
    }


}
