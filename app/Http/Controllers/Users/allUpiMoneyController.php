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

        $company = $this->gatewayCompany();
        $stopped = \App\Services\SystemSettingService::blockedMessage();
        if ($stopped) {
            return response()->json(array(
                'type' => 'error',
                'message' => $stopped
            ));
        }
        //return $company;
        if(! $company || (int) $company->payment_gateway2 !== 1){
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

        return response()->json(array(
            'type' => 'error',
            'message' => 'QR payment is not available. Use UPI.'
        ));
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
        return view("users.fund.qr-code-check-status", $data);
    }

    public function AddMoneyCallback(Request $post)
    {
        
        // Log::info($post->all());
        // Log::info($post->ip());
        // Log::info(file_get_contents("php://input"));
       // echo "<pre>";print_r($post->all());die;
       if($post->ip() !="198.38.88.206"){
            Log::warning('QR add-money callback rejected', [
                'ip' => $post->ip(),
                'order' => $this->callbackValue($post, ['order_id', 'orderId']),
            ]);
            return response()->json(array(
                'type' => 'error',  
                'message' => "please point ip address."
            ));
       }
        $orderId = $this->callbackValue($post, ['order_id', 'orderId']);
        $status = strtoupper($this->callbackValue($post, ['status']));
        if($orderId !== ''){
            $report = DB::table('fund_requests')->where('order_id', $orderId)->where('status', 'Pending')->where('upi', 2)->first();
            if($report){
                if(in_array($status, ['SUCCESS', 'SUCCESSFUL', 'CAPTURED', 'PAID', 'COMPLETED'], true)){
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
                        $update['transaction_number'] =  $orderId;
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
                    
                }else if(in_array($status, ['FAILED', 'FAILURE', 'FAIL', 'CANCELLED', 'CANCELED'], true)){
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
