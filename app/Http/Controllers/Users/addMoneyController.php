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
class addMoneyController extends Controller
{
    public function addMoneyRequestSubmit(Request $post)
    {

        $company = $this->gatewayCompany();
        $stopped = \App\Services\SystemSettingService::blockedMessage();
        if ($stopped) {
            return response()->json(array(
                'type' => 'error',
                'message' => $stopped
            ));
        }
        //return $company;
        if(! $company || (int) $company->payment_gateway !== 1){
            return response()->json(array(
                'type' => 'error',  
                'message' => "this service is disable. contact service provider"
            ));
        }

        $key = $company->payment_gateway_key;
        $minimum = $company->payment_gateway_min;
        $maximum = $company->payment_gateway_max;


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
                'upi' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            if($update){
                $content = json_encode(array(
                    "scannerIncluded"=> true,
                    "orderId"=> $order_id,
                    "apiKey"=> $key,
                    "amount"=> (int)$post->amount, 
                    "paymentNote"=> "Upi Add Money",
                    "customerName"=> trim((string) ($user->outlet_name ?: trim($user->first_name.' '.$user->last_name))) ?: 'NETCELL PAY',
                    "customerEmail"=> $user->email_address,
                    "customerNumber"=> $user->mobile_number,
                    "redirectUrl"=> route('AddMoneyCheckStatus',$order_id),
                ));
                $url = "https://pay.onegateway.in/payment/initiate";
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
                $payUrl = '';
                if (is_array($response)) {
                    $payUrl = (string) ($response['data']['paymentUrl'] ?? $response['data']['payment_url'] ?? $response['paymentUrl'] ?? '');
                }
                $started = is_array($response) && (($response['success'] ?? false) === true || ($response['success'] ?? '') === 'true') && $payUrl !== '';
                if($started){
                    $data['pay_url'] = $payUrl;
                    $data['type'] = 'success';
                    $data['message'] =  "Link generate sucessfuly.";
                    return response()->json($data);
                }else{
                    $gatewayMessage = is_array($response) ? (string) ($response['msg'] ?? $response['message'] ?? '') : '';
                    if ($gatewayMessage === '') {
                        $gatewayMessage = 'Payment link was not returned. Check the UPI key in Payment Gateway settings.';
                    }
                    $update_d['status'] = "Rejected";
                    $update_d['decision_by'] = 1;
                    $update_d['decision_remark'] = $gatewayMessage;
                    $update_d['decision_date'] = Carbon::now();
                    DB::table('fund_requests')->where('order_id', $order_id)->update($update_d);
                    return response()->json(array(
                        'type' => 'error',  
                        'message' => $gatewayMessage
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

        $lookupId = trim((string) ($order_id !== '' ? $order_id : $post->input('order_id')));
        if($lookupId !== ''){
            $report = DB::table('fund_requests')->where('order_id', $lookupId)->first();
            if($report){
                if($report->status == "Transferred"){
                    try {
                        \App\Services\BillingDocumentService::issueForTransferredFund((int) $report->id);
                    } catch (\Throwable $e) {
                    }
                    $data = \App\Services\BillingDocumentService::statusPayload($report);
                    $data['status'] = "Success";
                    $data['type'] = 'success';
                    $data['message'] =  "Payment successful. Wallet has been credited.";
                    return response()->json($data);
                }else if($report->status == "Rejected"){
                    $data['status'] = "Failed";
                    $data['type'] = 'success';
                    $data['message'] =  "transaction is failed.";
                    return response()->json($data);
                }else if($report->status == "Pending"){
                    $data['status'] = "Pending";
                    $data['type'] = 'success';
                    $data['message'] =  "Waiting for the payment company to confirm this payment.";
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

//      'order_id' => 'IPU20240529213438892403',
//      'status' => 'SUCCESS',
//      'gateway_txn' => '6657521674662',
//      'remark1' => 'IPU20240529213438892403',
        // Log::info($post->all());
        // Log::info(file_get_contents("php://input"));
       // echo "<pre>";print_r($post->all());die;

       if($post->ip() !="164.52.218.75"){
            Log::warning('UPI add-money callback rejected', [
                'ip' => $post->ip(),
                'order' => $this->callbackValue($post, ['orderId', 'order_id', 'remark1']),
            ]);
            return response()->json(array(
                'type' => 'error',  
                'message' => "please point ip address."
            ));
       }
        $orderId = $this->callbackValue($post, ['orderId', 'order_id', 'remark1']);
        $status = strtolower($this->callbackValue($post, ['status']));
        $utr = $this->callbackValue($post, ['utr', 'UTR', 'gateway_txn', 'txn_id']);
        if($orderId !== ''){
            $report = DB::table('fund_requests')->where('order_id', $orderId)->where('status', 'Pending')->where('upi', 1)->first();
            if($report){
                if(in_array($status, ['success', 'successful', 'captured', 'paid', 'completed'], true)){
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
                        $update['transaction_number'] =  $utr !== '' ? $utr : $orderId;
                        $update['decision_date'] = Carbon::now();
                        $fundRequestId = (int) $report->id;
                        $report = DB::table('fund_requests')->where('id', $fundRequestId)->update($update);
                        ///Status Update in Fund Request End
                        DB::commit();
                        try {
                            \App\Services\BillingDocumentService::issueForTransferredFund($fundRequestId);
                        } catch (\Throwable $e) {
                        }
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
                    
                }else if(in_array($status, ['failed', 'failure', 'fail', 'cancelled', 'canceled'], true)){
                    $update['status'] = "Rejected";
                    $update['decision_by'] = 1;
                    $update['transaction_number'] = $orderId;
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

    private function gatewayCompany()
    {
        $company = DB::table('companies')->where('id', 1)->first();
        if ($company) {
            return $company;
        }
        $host = (string) (request()->getHost() ?: ($_SERVER['HTTP_HOST'] ?? ''));
        $company = DB::table('companies')->where('status', '1')->where('domain', $host)->first();

        return $company ?: DB::table('companies')->where('status', '1')->first();
    }

    private function callbackValue(Request $post, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $post->input($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }
        $raw = json_decode((string) $post->getContent(), true);
        if (is_array($raw)) {
            foreach ($keys as $key) {
                if (isset($raw[$key]) && is_scalar($raw[$key]) && trim((string) $raw[$key]) !== '') {
                    return trim((string) $raw[$key]);
                }
            }
        }

        return '';
    }


}
