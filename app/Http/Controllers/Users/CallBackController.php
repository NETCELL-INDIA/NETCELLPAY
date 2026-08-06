<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class CallBackController extends Controller
{
    public function rechargeCallback(Request $post,$api_id){
        // Log incoming callback payload for debugging/traceability
        try {
            DB::table('apilogs')->insert([
                'url' => '/recharge-callback/' . $api_id,
                'modal' => 'RechargeCallback',
                'txnid' => '',
                'header' => json_encode($_SERVER),
                'request' => json_encode($_REQUEST),
                'response' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        if(!$_REQUEST){
            return response()->json(array(
                'type' => 'error',  
                'message' => "param not found "
            ));
        }

        $api = DB::table('apis')->where('id',$api_id)->first();
        if(!$api){
            return response()->json(array(
                'type' => 'error',  
                'message' => "not found api."
            ));
        }

        $status = $api->callback_status_value;
        $success = $api->callback_success_value;
        $failed = $api->callback_failed_value;
        $refund = $api->callback_refund_value;
        $operator_id = $api->callback_operator_id_value;
        $order_id = $api->callback_order_id_value;

        $data = json_decode(json_encode($_REQUEST), true);

        // Determine intended update status from callback payload
        $update = [];
        if(isset($data[$status]) && $data[$status] == $success){
            $update['status'] = "Success";
            $update['operator_id'] = $data[$operator_id] ?? '';
            $update['callback_response'] = json_encode($_REQUEST);
        } else if(isset($data[$status]) && $data[$status] == $failed) {
            $update['status'] = "Failed";
            $update['operator_id'] = $data[$operator_id] ?? '';
            $update['callback_response'] = json_encode($_REQUEST);
        } else if(isset($data[$status]) && $data[$status] == $refund) {
            $update['status'] = "Refunded";
            $update['operator_id'] = $data[$operator_id] ?? '';
            $update['callback_response'] = json_encode($_REQUEST);
        } else {
            $update['status'] = "Pending";
            $update['callback_response'] = json_encode($_REQUEST);
        }

        // Use transaction + row lock to avoid races between worker job and callback
        try {
            DB::beginTransaction();

            $report = DB::table('reports')
                ->where('order_id', $data[$order_id] ?? '')
                ->where('api_id', $api_id)
                ->whereIn('status', ['Pending', 'Processing'])
                ->lockForUpdate()
                ->first();

            if(!$report){
                DB::commit();
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "record not found or already finalized."
                ));
            }

            // If callback indicates Refunded and there's a successful parent recharge, handle specially
            if($update['status'] == "Refunded"){
                // If the matched report is not Success, look for success parent with same order id
                $successReport = DB::table('reports')->where('order_id', $data[$order_id] ?? '')->where('status', 'Success')->where('parent__id', 0)->first();
                if($successReport){
                    DB::table('reports')->where('id', $successReport->id)->update($update);
                    DB::commit();
                    \helpers::refund_row($successReport->id);
                    \helpers::ReverseCommission($successReport->id);

                    $c_report = DB::table('complaints')->where('order_id', $data[$order_id] ?? '')->where('status', 'Open')->first();
                    if($c_report){
                        DB::table('complaints')->where('id', $c_report->id)->update([
                            'decision_by' => 1,
                            'decision_remark' => "Recharge Refunded",
                            'status' => "Sloved",
                            'decision_date' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        // Attempt to clear complaint link if present
                        if(isset($c_report->report_id)){
                            DB::table('reports')->where('id', $c_report->report_id)->update(['complaint_id' => 0]);
                        }
                    }

                    return response()->json(array('type' => 'success', 'message' => 'recharge refunded processed'));
                }
            }

            // For normal Success / Failed / Pending updates: update the currently locked report
            DB::table('reports')->where('id', $report->id)->update($update + ['updated_at' => Carbon::now(), 'callback_response' => ($update['callback_response'] ?? null)]);

            DB::commit();

            if($update['status'] == 'Success'){
                \helpers::SetCommission($report->id);
                return response()->json(array('type' => 'success', 'message' => 'status updated to Success'));
            } else if($update['status'] == 'Failed'){
                \helpers::refund_row($report->id);
                return response()->json(array('type' => 'success', 'message' => 'status updated to Failed and refunded'));
            } else {
                return response()->json(array('type' => 'success', 'message' => 'status left as Pending'));
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            // Log callback processing error
            try {
                DB::table('apilogs')->insert([
                    'url' => '/recharge-callback-error/' . $api_id,
                    'modal' => 'RechargeCallbackError',
                    'txnid' => $data[$order_id] ?? '',
                    'header' => json_encode($_SERVER),
                    'request' => json_encode($_REQUEST),
                    'response' => $e->getMessage(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $x) {
                // ignore
            }
            return response()->json(array('type' => 'error', 'message' => 'callback processing error'));
        }

    }
   
}
