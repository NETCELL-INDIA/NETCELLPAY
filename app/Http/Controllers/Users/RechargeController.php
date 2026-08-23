<?php



namespace App\Http\Controllers\Users;



use App\Http\Controllers\Controller;

use App\Services\PlanInfoFetchService;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use Session;

use Illuminate\Support\Str;

class RechargeController extends Controller

{

    public function mobileIndex(Request $post)

    {

        return view('users.services.mobile');

    }



    public function dthIndex(Request $post)

    {

        return view('users.services.dth');

    }

    public function postpaidIndex(Request $post)

    {

        return view('users.services.postpaid');

    }





    public function finalApiCheck($post,$user) {

        $array_routes = [];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('routes_settings')) {
                $routes = DB::table('routes_settings')->orderBy('priority', 'ASC')->where('status', 1)->get('route_code');
                foreach ($routes as $route) {
                    $array_routes[] = $route->route_code;
                }
            }
        } catch (\Throwable $e) {
            $array_routes = [];
        }

        if (empty($array_routes)) {
            $array_routes = ['amount_wize', 'user_wize', 'state_wize', 'provider'];
        }

        foreach ($array_routes as $route) {
            $route_api_id = \helpers::checkApis($route, $post, $user);
            if ($route_api_id != 0) {
                return $route_api_id;
            }
        }

        $provider = DB::table('providers')->where('id', $post->provider_id)->first();
        return (int) ($provider->api_id ?? 0);
    }





    public function rechargeCall(Request $post)

    {

        @set_time_limit(120);
        @ignore_user_abort(true);
            	

        $rules = array(

            'provider_id' => 'required|numeric',

            'service_id' => 'required|numeric',

            'state_id' => 'numeric',

            'number' => 'required|min:4|max:20',

            'amount' => 'required|numeric|min:1',

        );

        if (!$post->filled('api_key')) {
            $rules['pin'] = 'required|digits:4';
        }



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
		if(!$post->state_id){

			$post['state_id'] = 40;

        } 
        if (isset($post->api_key)) {

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

            $user = DB::table('users')->where('api_key', $post->api_key)->first();

            if (!$user) {
                return response()->json(array(
                    'status' => 'Failed',
                    'type' => 'error',
                    'message' => 'user not found'
                ));
            }

            $request_order_id = DB::table('reports')->where('user_id', $user->id)->where('request_order_id', $post->request_order_id)->first();

            if ($request_order_id) {

                return response()->json(array(

                    'status' => 'Failed',

                    'type' => 'error',

                    'message' => "request order id already exists."

                ));

            }

            $post['path'] = "Api";

        } else if (isset($post->login_key)) {

            $user = DB::table('users')->where('login_key', $post->login_key)->where('id', $post->user_id)->first();

            $post['path'] = "App";

        } else {

            $user = DB::table('users')->where('id', Session::get('user_id'))->where('login_key', Session::get('login_key'))->first();

            $post['path'] = "Web";

        }

        $stopped = \App\Services\SystemSettingService::blockedMessage();
        if ($stopped) {
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',
                'message' => $stopped
            ));
        }

        //return $user;



        if (!$user) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "user not found"

            ));

        }



        if ($user->status != 1) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Account Not Active.Contact To Admin"

            ));

        }

        //User Wize Service Active/Deactive

        if (($post['path'] ?? '') !== 'Api' && !\helpers::verifyUserPin($user->t_pin, $post->pin)) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Wrong Pin"

            ));

        }



        if ($user->wallet_balance < $post->amount) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "You Have Insufficient your Wallet Balance"

            ));

        }



        if (($user->wallet_balance - $post->amount) > ($user->minium_balance)) {



        } else {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Please contact your admin your minimum maintain balance is " . $user->minium_balance

            ));

        }



        $provider = DB::table('providers')->where('id', $post->provider_id)->first();



        if (!$provider) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Wrong Provider Id"

            ));

        }



        if ($provider->status == 0) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "This time " . $provider->provider_name . " provider deactive please try again."

            ));

        }

        $serviceOff = \App\Services\SystemSettingService::serviceDisabledMessage($provider);
        if ($serviceOff) {
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',
                'message' => $serviceOff
            ));
        }



        if (($provider->provider_down ?? 0) == 1) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "This time " . $provider->provider_name . " provider down please try again."

            ));

        }





        ////Validation Check  Block Amount

        $chk_block_amount = DB::table('amount_blocks')->where('provider_id', $post->provider_id)->where('status', 1)->first();

        if ($chk_block_amount) {

            $amounts = explode(",", $chk_block_amount->amount);

            foreach ($amounts as $amount) {

                if ($post->amount == $amount) {

                    return response()->json(array(

                        'status' => 'Failed',

                        'type' => 'error',

                        'message' => "This amount block please contact your admin."

                    ));

                }

            }

        }

        ////Validation Check  Block Amount



        ///Routes APi Check Start

        

        $api_id = $this->finalApiCheck($post,$user);





        if ($api_id == 0) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "please contact to service provider.Api Connections Error"

            ));

        }

        

        ///Routes APi Check Start









        





        //return $api_id;



        $repeat = \App\Services\SystemSettingService::rechargeRepeatMessage($user->id, $post->number, $post->amount, $post->provider_id);
        if ($repeat) {
            return response()->json(array(
                'status' => 'Failed',
                'type' => 'error',
                'message' => $repeat
            ));
        }

        $inProgress = DB::table('reports')
            ->where('user_id', $user->id)
            ->where('number', $post->number)
            ->where('total_amount', $post->amount)
            ->where('provider_id', $post->provider_id)
            ->whereIn('status', ['Pending', 'Under Process', 'Under Proces', 'Processing'])
            ->where('created_at', '>=', Carbon::now()->subMinutes(3))
            ->orderByDesc('id')
            ->first();

        if ($inProgress) {
            $ageSeconds = Carbon::now()->diffInSeconds(Carbon::parse($inProgress->created_at));

            // Stuck Pending/Processing (>45s): reset and re-trigger on the same report.
            if ($ageSeconds >= 45 && !empty($inProgress->api_id)) {
                if (in_array($inProgress->status, ['Pending', 'Under Process', 'Under Proces', 'Processing'], true)) {
                    DB::table('reports')->where('id', $inProgress->id)->update([
                        'status' => 'Pending',
                        'updated_at' => Carbon::now(),
                    ]);
                }
                \helpers::RunApi($inProgress->api_id, $inProgress->provider_id, $inProgress->id, 'Recharge');
            }

            return response()->json([
                'type' => 'success',
                'status' => $inProgress->status,
                'id' => $inProgress->id,
                'order_id' => $inProgress->order_id,
                'amount' => $inProgress->total_amount,
                'number' => $inProgress->number,
                'operator_id' => $inProgress->operator_id ?? '',
                'remark' => $ageSeconds >= 45 && $inProgress->status === 'Pending'
                    ? 'Retrying recharge. Check report for final status.'
                    : 'Transaction already in progress. Check report for final status.',
                'message' => $ageSeconds >= 45 && $inProgress->status === 'Pending'
                    ? 'Retrying recharge. Check report for final status.'
                    : 'Transaction already in progress. Check report for final status.',
                'commission' => $inProgress->commission ?? 0,
                'date_time' => $inProgress->created_at,
            ]);
        }





        $post['order_id'] = "RC" . date("YmdHis") . rand(11111, 999999) . rand(1, 3) . rand(3, 6) . rand(6, 9);

        $post['commission'] = \helpers::getCommission($post->amount, $user->scheme_id, $post->provider_id, $user->role_id);

        $total_amount = $post->amount;

        $post['user_id'] = $user->id;

        $post['number'] = $post->number;

        $post['amount'] = $post->amount - $post['commission'];

        $post['total_amount'] = $total_amount;

        $post['admin_commission'] = 0;

        $post['api_commission'] = 0;

        $post['fund_type'] = "Debit";

        $txnType = $post->transaction_type ?? 'Recharge';
        if (!in_array($txnType, ['Recharge', 'Bill Pay', 'Bill Payment'], true)) {
            $txnType = 'Recharge';
        }
        $post['transaction_type'] = $txnType;

        $post['transaction_date'] = Carbon::now() . ":" . rand(111, 999);

        $post['created_at'] = Carbon::now();

        $post['updated_at'] = Carbon::now();

        $post['provider_id'] = $post->provider_id;

        $post['service_id'] = $provider->service_id;

        $post['state_id'] = $post->state_id;

        $post['api_id'] = $api_id;

        $post['remark'] = ($txnType === 'Recharge' ? 'Recharge' : 'Bill Pay') . " For Rs. " . $total_amount . " Number " . $post->number;

        $post['status'] = "Pending";



        $post['ip_address'] = \helpers::getIp();

        $post['opening_balance'] = $user->wallet_balance;

        $provider_code = \helpers::ApiProviderCode($api_id, $post->provider_id);



        if ($post['commission'] == 0) {

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Your commission not set. Contact your parent"

            ));

        }



        if ($provider_code == 0 || $provider_code == "") {

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

            if ($debit) {

                $user = DB::table('users')->where('id', $user->id)->first();

                //echo "<pre>";print_r($user);die;

                $post['closing_balance'] = $user->wallet_balance;

                $report = DB::table('reports')->insertGetId(\helpers::filterReportColumns($post->all()));

                DB::commit();







                if ($report) {

                    $api_result = \helpers::RunApi($api_id, $post->provider_id, $report, 'Recharge');

                    if ($api_result) {

                        if ($api_result['status'] == "Success") {

                            $api_result['callback_status'] = 1;

                            DB::table('reports')->where('id', $report)->update($api_result);

                            $set_com = \helpers::SetCommission($report);

                            //return $set_com;die;

                        } else if ($api_result['status'] == "Failed") {

                            //DB::table('users')->where('id', $user->id)->increment('wallet_balance', $post->amount);

                            if ($provider->backup_api_id == 0) {

                                $api_result['callback_status'] = 1;

                                DB::table('reports')->where('id', $report)->update($api_result);

                                \helpers::refund_row($report);

                            } else {

                                DB::table('reports')->where('id', $report)->update(['api_id' => $provider->backup_api_id]);

                                $api_result = \helpers::RunApi($provider->backup_api_id, $post->provider_id, $report, 'Recharge');

                                if ($api_result) {

                                    if ($api_result['status'] == "Success") {

                                        $api_result['callback_status'] = 1;

                                        DB::table('reports')->where('id', $report)->update($api_result);

                                        $set_com = \helpers::SetCommission($report);

                                        //return $set_com;die;

                                    } else if ($api_result['status'] == "Failed") {

                                        if ($provider->backup_api2_id == 0) {

                                            $api_result['callback_status'] = 1;

                                            DB::table('reports')->where('id', $report)->update($api_result);

                                            \helpers::refund_row($report);

                                        } else {

                                            DB::table('reports')->where('id', $report)->update(['api_id' => $provider->backup_api2_id]);

                                            $api_result = \helpers::RunApi($provider->backup_api2_id, $post->provider_id, $report, 'Recharge');

                                            if ($api_result) {

                                                if ($api_result['status'] == "Success") {

                                                    $api_result['callback_status'] = 1;

                                                    DB::table('reports')->where('id', $report)->update($api_result);

                                                    $set_com = \helpers::SetCommission($report);

                                                    //return $set_com;die;

                                                } else if ($api_result['status'] == "Failed") {

                                                    //DB::table('users')->where('id', $user->id)->increment('wallet_balance', $post->amount);

                                                    // DB::table('reports')->where('id', $report)->update($api_result);

                                                    // \helpers::refund_row($report);

                                                    if ($provider->backup_api3_id == 0) {

                                                        $api_result['callback_status'] = 1;

                                                        DB::table('reports')->where('id', $report)->update($api_result);

                                                        \helpers::refund_row($report);

                                                    } else {

                                                        DB::table('reports')->where('id', $report)->update(['api_id' => $provider->backup_api3_id]);

                                                        $api_result = \helpers::RunApi($provider->backup_api3_id, $post->provider_id, $report, 'Recharge');

                                                        if ($api_result) {

                                                            if ($api_result['status'] == "Success") {

                                                                $api_result['callback_status'] = 1;

                                                                DB::table('reports')->where('id', $report)->update($api_result);

                                                                $set_com = \helpers::SetCommission($report);

                                                                //return $set_com;die;

                                                            } else if ($api_result['status'] == "Failed") {

                                                                $api_result['callback_status'] = 1;

                                                                //DB::table('users')->where('id', $user->id)->increment('wallet_balance', $post->amount);

                                                                DB::table('reports')->where('id', $report)->update($api_result);

                                                                \helpers::refund_row($report);

                                                            } else {

                                                                DB::table('reports')->where('id', $report)->update($api_result);

                                                            }



                                                            $data_show['number'] = $post->number;

                                                            $data_show['status'] = $api_result['status'];

                                                            $data_show['amount'] = $total_amount;

                                                            $data_show['order_id'] = $api_result['order_id'];

                                                            if (isset($post->api_key)) {

                                                                $data_show['request_order_id'] = $post->request_order_id;

                                                            }

                                                            $data_show['operator_id'] = $api_result['operator_id'];

                                                            $data_show['type'] = "success";

                                                            $data_show['remark'] = $api_result['remark'];

                                                            $data_show['message'] = $api_result['remark'];

                                                            $data_show['commission'] = 0;

                                                            $data_show['id'] = $report;

                                                            $data_show['date_time'] = Carbon::now();

                                                            if ($api_result['status'] == "Success" || $api_result['status'] == "Pending") {

                                                                $data_show['commission'] = $post->commission;

                                                            }

                                                            return response()->json($data_show);

                                                        }

                                                    }

                                                } else {

                                                    DB::table('reports')->where('id', $report)->update($api_result);

                                                }



                                                $data_show['number'] = $post->number;

                                                $data_show['status'] = $api_result['status'];

                                                $data_show['amount'] = $total_amount;

                                                $data_show['order_id'] = $api_result['order_id'];

                                                if (isset($post->api_key)) {

                                                    $data_show['request_order_id'] = $post->request_order_id;

                                                }

                                                $data_show['operator_id'] = $api_result['operator_id'];

                                                $data_show['type'] = "success";

                                                $data_show['remark'] = $api_result['remark'];

                                                $data_show['message'] = $api_result['remark'];

                                                $data_show['commission'] = 0;

                                                $data_show['id'] = $report;

                                                $data_show['date_time'] = Carbon::now();

                                                if ($api_result['status'] == "Success" || $api_result['status'] == "Pending") {

                                                    $data_show['commission'] = $post->commission;

                                                }

                                                return response()->json($data_show);

                                            }

                                        }

                                    } else {

                                        DB::table('reports')->where('id', $report)->update($api_result);

                                    }



                                    $data_show['number'] = $post->number;

                                    $data_show['status'] = $api_result['status'];

                                    $data_show['amount'] = $total_amount;

                                    $data_show['order_id'] = $api_result['order_id'];

                                    if (isset($post->api_key)) {

                                        $data_show['request_order_id'] = $post->request_order_id;

                                    }

                                    $data_show['operator_id'] = $api_result['operator_id'];

                                    $data_show['type'] = "success";

                                    $data_show['remark'] = $api_result['remark'];

                                    $data_show['message'] = $api_result['remark'];

                                    $data_show['commission'] = 0;

                                    $data_show['id'] = $report;

                                    $data_show['date_time'] = Carbon::now();

                                    if ($api_result['status'] == "Success" || $api_result['status'] == "Pending") {

                                        $data_show['commission'] = $post->commission;

                                    }

                                    return response()->json($data_show);

                                }

                            }





                        } else {

                            DB::table('reports')->where('id', $report)->update($api_result);

                        }





                        $data_show['number'] = $post->number;

                        $data_show['status'] = $api_result['status'];

                        $data_show['amount'] = $total_amount;

                        $data_show['order_id'] = $api_result['order_id'];

                        if (isset($post->api_key)) {

                            $data_show['request_order_id'] = $post->request_order_id;

                        }

                        $data_show['operator_id'] = $api_result['operator_id'];

                        $data_show['type'] = "success";

                        $data_show['remark'] = $api_result['remark'];

                        $data_show['message'] = $api_result['remark'];

                        $data_show['commission'] = 0;

                        $data_show['id'] = $report;

                        $data_show['date_time'] = Carbon::now();

                        if ($api_result['status'] == "Success" || $api_result['status'] == "Pending") {

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

            } else {

                DB::rollback();

                return response()->json(array(

                    'status' => 'Failed',

                    'type' => 'error',

                    'message' => "Internal Server Error U"

                ));

            }

        } catch (\Throwable $th) {

            DB::rollBack();

            try {
                DB::table('apilogs')->insert([
                    'url' => 'rechargeCall-error',
                    'modal' => 'Recharge',
                    'txnid' => $post['order_id'] ?? 'unknown',
                    'header' => json_encode([]),
                    'request' => json_encode($post->except(['pin', '_token'])),
                    'response' => $th->getMessage(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                // ignore logging failure
            }

            return response()->json(array(

                'status' => 'Failed',

                'type' => 'error',

                'message' => "Internal Server Error"

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

        $serviceId = (int) $post->service;
        $serviceIds = ($serviceId === 4) ? [4, 15] : [$serviceId];

        $provider = DB::table('providers')->select('id', 'provider_name')
            ->whereIn('service_id', $serviceIds)
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('provider_name')
            ->get();

        $states = DB::table('states')->select('id', 'state_name')->where('status', 1)->get();

        if ($provider) {

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

            ->whereIn('transaction_type', ['Recharge'])

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

            $i = 1;

            foreach ($list as $list) {

                if ($list->status == "Success") {

                    $bg = "success";

                } elseif ($list->status == "Failed") {

                    $bg = "danger";

                } elseif ($list->status == "Refunded") {

                    $bg = "secondary";

                } else {

                    $bg = "warning";

                }

                if ($list->status == "Success" && $list->complaint_id == 0 && \helpers::reportAllowsComplaint($list)) {

                    $action = '<button type="submit" class="btn btn-secondary" id="receipt_btn" onclick="receiptView(`' . $list->id . '`)"><i class="ri-file-list-3-line"></i> Receipt</button>  <button type="submit" class="btn btn-warning" id="complaint_btn" onclick="complaintView(`' . $list->id . '`,`' . $list->order_id . '`)"><i class="ri-questionnaire-fill"></i> Complaint</button>';

                } else {

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

                ' . $action . '

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

        if (isset($post->api_key)) {

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

            if (!$user) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "user not found"

                ));

            }

            $report_f = DB::table('reports')->where('request_order_id', $post->request_order_id)->where('user_id', $user->id)->first();

            if (!$report_f) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "record not found"

                ));

            }

            $post['id'] = $report_f->id;

        } else {

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

            if (isset($post->login_key)) {

                $user = DB::table('users')->where('id', $post->user_id)->where('login_key', $post->login_key)->first();

            } else {

                $user = DB::table('users')->where('id', Session::get('user_id'))->first();

            }

            if (!$user) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "user not found"

                ));

            }



        }







        if ($user->status != 1) {

            return response()->json(array(

                'type' => 'error',

                'message' => "Account Not Active.Contact To Admin"

            ));

        }



        $reports = DB::table('reports')->select(

            'order_id',

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

            ->join('users', 'users.id', '=', 'reports.user_id')

            ->where('reports.user_id', $user->id)->where('reports.id', $post->id)->first();

        if ($reports) {

            $provider = DB::table('providers')->where('id', $reports->provider_id)->first();

            $service = DB::table('services')->where('id', $reports->service_id)->first();



            $data['type'] = 'success';

            $data['message'] = "Get sucessfuly";

            $data['provider'] = $provider->provider_name . " - " . $service->service_name . " - " . $reports->path;

            $data['provider_name'] = $provider->provider_name;

            $data['data'] = $reports;

        } else {

            $data['type'] = 'error';

            $data['message'] = "Something went wrong!";

        }

        return $data;

        //echo "<pre>";print_r("Shiba");die;

    }





    public function submitRechargeComplaint(Request $post)

    {





        if (isset($post->api_key)) {

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

            if (!$user) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "user not found"

                ));

            }

            $report_f = DB::table('reports')->where('order_id', $post->order_id)->where('user_id', $user->id)->first();

            if (!$report_f || !\helpers::reportAllowsComplaint($report_f)) {
                return response()->json(array(
                    'type' => 'error',
                    'message' => \helpers::complaintBlockMessage($report_f)
                ));
            }

            $post['id'] = $report_f->id;

            $path = "Api";

        } else {

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

            if (isset($post->login_key)) {

                $user = DB::table('users')->where('id', $post->user_id)->where('login_key', $post->login_key)->first();

            } else {

                $user = DB::table('users')->where('id', Session::get('user_id'))->first();

            }



            if (!$user) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "user not found"

                ));

            }

            $path = "Web";

            if ($user->role_id == 3) {

                $path = "Api";

            }



            $check_complaint = DB::table('complaints')->where('report_id', $post->id)->where('status', "Open")->first();



            if ($check_complaint) {

                return response()->json(array(

                    'type' => 'error',

                    'message' => "Complaint Already Submit Contact Service provider."

                ));

            }



        }



        //$user = DB::table('users')->where('id',Session::get('user_id'))->first();

        if ($user->status != 1) {

            return response()->json(array(

                'type' => 'error',

                'message' => "Account Not Active.Contact To Admin"

            ));

        }







        $report = DB::table('reports')
            ->where('user_id', $user->id)
            ->where('id', $post->id)
            ->where('complaint_id', 0)
            ->first();

        if (!$report || !\helpers::reportAllowsComplaint($report)) {
            return response()->json([
                'type' => 'error',
                'message' => \helpers::complaintBlockMessage($report),
            ]);
        }

        if ($report) {

            try {

                $request_id = "SR" . date("YmdHis") . rand(11111, 999999);

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

                if ($complaint) {

                    $api_details = DB::table('apis')->where('id', $report->api_id)->first();

                    $url = $api_details->complaint_api_url;

                    if ($url != 0 || $url != "") {

                        $url = str_replace('{API_USERNAME}', '' . $api_details->api_username . '', $url);

                        $url = str_replace('{API_PASSWORD}', '' . $api_details->api_password . '', $url);

                        $url = str_replace('{API_KEY}', '' . $api_details->api_key . '', $url);

                        $url = str_replace('{ORDER_ID}', '' . $report->order_id . '', $url);

                        $url = str_replace('{SUBJECT}', '' . $post->subject . '', $url);

                        $method = $api_details->complaint_api_method;

                        $header = [];

                        $parameters = "";

                        if (isset($url) && isset($parameters) && isset($method) && isset($header)) {

                            $logFlag = ((int) ($api_details->store_log ?? 0) === 1) ? 'yes' : 'no';
                            $result = \helpers::curl($url, $method, $parameters, $header, $logFlag, "COMPLAINT_URL", $request_id);

                            if ($result['code'] != 200) {

                                return response()->json(array(

                                    'type' => 'success',

                                    'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Normal"

                                ));

                            } else {

                                $data = json_decode($result['response'], true);

                                $status = $api_details->complaint_status_value;

                                if (isset($data[$status])) {

                                    if ($data[$status] == $api_details->complaint_success_value) {

                                        return response()->json(array(

                                            'type' => 'success',

                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.200"

                                        ));

                                    } else if ($data[$status] == $api_details->complaint_failed_value) {

                                        return response()->json(array(

                                            'type' => 'success',

                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.400"

                                        ));

                                    } else {

                                        return response()->json(array(

                                            'type' => 'success',

                                            'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Pending"

                                        ));

                                    }

                                } else {

                                    return response()->json(array(

                                        'type' => 'success',

                                        'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.Api"

                                    ));

                                }

                            }

                        } else {

                            return response()->json(array(

                                'type' => 'success',

                                'message' => "Complaint Submit Successfully up to 3 Working Day Any Response.500"

                            ));

                        }

                    } else {

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

        }

        return response()->json([
            'type' => 'error',
            'message' => 'Unable to submit complaint. Please try again.',
        ]);

    }



    public function RechargeCheckRofferFatch(Request $post)

    {



        $rules = array(

            'provider_id' => 'required|numeric',

            'number' => 'required|digits:10',

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

        try {

        $serviceKey = PlanInfoFetchService::rofferServiceKey((int) $post->provider_id);

        $result = PlanInfoFetchService::fetch($serviceKey, function ($api) use ($post) {

            $provider_code = \helpers::PlanProviderCode($api->id, $post->provider_id);

            if ($provider_code == 0 || $provider_code === '') {
                return null;
            }

            $key = $api->resolved_api_key ?: $api->api_key;

            if ($key === null || $key === '') {
                return null;
            }

            return rtrim($api->api_url, '/') . '/plans.php?apikey=' . urlencode($key) . '&operator=' . urlencode($provider_code) . '&offer=roffer&tel=' . urlencode($post->number);

        }, 'Roffer', 'ROF');

        if ($result) {

            $records = PlanInfoFetchService::extractRecords($result['response'] ?? null);

            if ($records !== []) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'Fatch Successfully',
                    'data' => $records,
                ]);
            }

            $apiMessage = PlanInfoFetchService::responseErrorMessage($result['response'] ?? null);

            return response()->json([
                'type' => 'error',
                'message' => $apiMessage ?: 'No offers found for this number.',
            ]);
        }

        return response()->json(array(

            'type' => 'error',

            'message' => "Unable to fetch offers. Check plan API settings and operator code."

        ));

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch offers. Please try again.',
            ]);
        }

    }



    public function dthInfo(Request $post)

    {



        $rules = array(

            'provider_id' => 'required|numeric',

            'number' => 'required',

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

        try {

        $result = PlanInfoFetchService::fetchDthService('dth_customer', (int) $post->provider_id, (string) $post->number);

        if (empty($result['ok'])) {
            return response()->json([
                'type' => 'error',
                'message' => $result['message'] ?? 'Unable to fetch DTH info. Please try again.',
            ]);
        }

        return response()->json([

            'type' => 'success',

            'message' => 'Fatch Successfully',

            'data' => $result['data'] ?? []

        ]);

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch DTH info. Please try again.',
            ]);
        }

    }



    public function dthPlans(Request $post)
    {
        $rules = [
            'provider_id' => 'required|numeric',
        ];

        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            $error = 'Please select DTH provider.';
            foreach ($validator->errors()->messages() as $value) {
                $error = $value[0];
                break;
            }

            return response()->json([
                'type' => 'error',
                'message' => $error,
            ]);
        }

        try {
            $result = PlanInfoFetchService::fetchDthPlans((int) $post->provider_id);
            if (empty($result['ok'])) {
                return response()->json([
                    'type' => 'error',
                    'message' => $result['message'] ?? 'Unable to fetch DTH plans. Please try again.',
                ]);
            }

            return response()->json([
                'type' => 'success',
                'message' => $result['message'] ?? 'Fatch Successfully',
                'data' => $result['data'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch DTH plans. Please try again.',
            ]);
        }
    }





    public function dthHeavyRefresh(Request $post)

    {



        $rules = array(

            'provider_id' => 'required|numeric',

            'number' => 'required',

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

        try {

        $result = PlanInfoFetchService::fetchDthService('dth_heavy_refresh', (int) $post->provider_id, (string) $post->number);

        if (empty($result['ok'])) {
            return response()->json([
                'type' => 'error',
                'message' => $result['message'] ?? 'Unable to refresh DTH info. Please try again.',
            ]);
        }

        return response()->json([

            'type' => 'success',

            'message' => 'Fatch Successfully',

            'data' => $result['data'] ?? []

        ]);

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to refresh DTH info. Please try again.',
            ]);
        }

    }



    public function RechargeCheckMobile(Request $post)

    {

        $rules = array(

            'number' => 'required|digits:10',

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

        try {

        $result = PlanInfoFetchService::fetchOperatorLookup((string) $post->number);

        if (empty($result['ok'])) {
            return response()->json([
                'type' => 'error',
                'message' => $result['message'] ?? 'Unable to fetch operator details. Please try again.',
            ]);
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $mapped = PlanInfoFetchService::mapHlrToPortal(isset($result['api_id']) ? (int) $result['api_id'] : null, $data);

        if (!$mapped['provider'] || !$mapped['state']) {
            return response()->json([
                'type' => 'error',
                'message' => 'Provider or circle mapping not found. Contact admin.',
            ]);
        }

        $provider_data = $mapped['provider'];
        $state = $mapped['state'];

        return response()->json([

            'type' => 'success',

            'message' => 'Get Successfully',

            'provider_id' => $provider_data->id,

            'provider_name' => $provider_data->provider_name,

            'provider_logo' => $provider_data->provider_logo,

            'state_id' => $state->id,

            'state_name' => $state->state_name

        ]);

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to check mobile number. Please try again.',
            ]);
        }

    }





    public function RechargeCheckPlanFatch(Request $post)

    {









        $rules = array(

            'provider_id' => 'required|numeric',

            'state_id' => 'nullable|numeric',

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

        try {

        $serviceId = (int) DB::table('providers')->where('id', (int) $post->provider_id)->value('service_id');
        if ($serviceId === 2) {
            $result = PlanInfoFetchService::fetchDthPlans((int) $post->provider_id);
            return response()->json([
                'type' => $result['type'],
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
            ]);
        }

        if (!$post->filled('state_id')) {
            return response()->json([
                'type' => 'error',
                'message' => 'Please select circle/state.',
            ]);
        }

        $result = PlanInfoFetchService::fetchMobilePlans((int) $post->provider_id, (int) $post->state_id);

        return response()->json([
            'type' => $result['type'],
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ]);

        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch plans. Please try again.',
            ]);
        }

    }















}

