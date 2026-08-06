<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class ApiController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.api');
    }

    public function fetchAll(Request $post)
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
            return '<h4 class="text-center text-secondary my-3">'.$error.'</h4>';
        }

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
        $start= ($page-1) * $limit;
        $total_row = DB::table('apis')->where('deleted_at', '!=' , 1)->get();
        $total_row_count = $total_row->count();
        $total_pages = ceil($total_row_count / $limit);
        $page_link = '';
        for ($i1=1; $i1<=$total_pages; $i1++) {
            if($page == $i1){
                $act = "active";
                $d = "";
            }else{
                $act = "";
                $d = 'onclick="tableSearch('.$i1.')"';
            }
            $page_link .= '<li class="page-item "><a href="javascript:void(0)" class="page-link '.$act.'" '.$d.'>'.$i1.'</a></li>';
        };

        $list = DB::table('apis')->where('deleted_at', '!=' , 1)->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
        $list_count = $list->count();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<div class="table-responsive">';
            $output .= '<div class="row">
                    <div class="col-sm-1">
                        <select class="form-select mb-3 page_limit" aria-label="Page Limit" id="page_limit">
                            <option ' . ($limit == "5" ? "selected" : "") . ' value="5">5</option>
                            <option ' . ($limit == "10" ? "selected" : "") . ' value="10" >10</option>
                            <option ' . ($limit == "15" ? "selected" : "") . ' value="15">15</option>
                            <option ' . ($limit == "25" ? "selected" : "") . ' value="25">25</option>
                            <option ' . ($limit == "50" ? "selected" : "") . ' value="50">50</option>
                        </select>
                    </div>
                    <div class="col-sm-9">
                        </br>
                    </div>
                    
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="searchValueTable" placeholder="Enter Search Value">
                    </div>
                </div><br>';
            $output .= '<table class="table table-bordered table-nowrap align-middle" id="pagination_table"><thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Username</th>
                <th>Password</th>
                <th>Status</th>
                <th>API Balance</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {
                if($list->status=="1"){
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                }else{
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
                $pass = $list->api_password ? str_repeat('x', min(30, max(8, strlen((string) $list->api_password)))) : '-';
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>' . e($list->api_name) . '</td>
                <td>' . e($list->api_username) . '</td>
                <td>' . e($pass) . '</td>
                <td>' . $status . '</td>
                <td>
                    <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                        <a href="javascript:void(0)" id="' . $list->id . '" class="btn btn-sm btn-outline-success checkLiveBalance">Get Balance</a>
                        <a href="javascript:void(0)" id="' . $list->id . '" data-name="' . e($list->api_name) . '" class="btn btn-sm btn-outline-success viewApiLogs">Logs</a>
                    </div>
                </td>
                <td>
                    <a id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="' . $list->id . '" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
                    <a id="' . $list->id . '" class="badge text-bg-primary setProviderCode"><i class="ri-settings-3-fill align-bottom"></i> Set Provider Code</a>
                    <a id="' . $list->id . '" class="badge text-bg-secondary setStateCode"><i class="ri-settings-3-fill align-bottom"></i> Set State Code</a>
                </td>
              </tr>';
              $i++;
			}
			$output .= '</tbody></table>';
            $output .= '<div class="row">
                <div class="col-sm-2">
                        <span>Showing '.($start + 1).' to '.($start + $list_count).' of '.$list_count.' entries ('.$total_row_count.' entries)</span>
                </div>
                <div class="col-sm-6">
                   <br>
                </div>
                <div class="col-sm-4">
                        <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <li class="page-item '.($page  == 1 || $page  == 0 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page - 1).')">← &nbsp; Prev</a>
                            </li>
                                '.$page_link.'
                            <li class="page-item '.($page + 1 == $i1 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page + 1 == $i1 ? $page : $page + 1).')">Next &nbsp; →</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div><br>';

            $output .= '</div>';
			echo $output;
		} else {
			echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
		}
        
    }

    public function deleteData(Request $post)
    {
        $delete = DB::table('apis')->where('id', $post->id)->update(['deleted_at' => 1]);
        if($delete){
            $data['type'] = 'success';
            $data['message'] = "Delete sucessfuly";
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        
        return $data;

    }


    public function getStateCodeData(Request $post)
    {

        $rules = array(
            'id' => 'required|numeric'
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

       $states = DB::table('states')->get();
       foreach ($states as $key => $state) {
            $state_code = DB::table('api_state_codes')->where('state_id', $state->id)->where('api_id', $post->id)->first();
            if($state_code){
                $state->state_code = $state_code->state_code;
            } else{
                $state->state_code = 0;
                
            }
        }
        $output = '';
		if ($states->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>State Name</th>
                <th>State Code</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($states as $list) {
				$output .= '<tr id="' . $list->id . '_row">
                <td>' . $i . '</td>
                <td>
                <input type="hidden" id="' . $list->id . '_state_id" name="state_id[]" value="' . $list->id . '">
                ' . $list->state_name . '
                </td>
               
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_state_code" name="state_code[]" value="' . $list->state_code . '" required="required">
                </td>
                <td>
                    <button class="btn btn-primary waves-effect waves-light updateStateCode" id="' . $list->id . '">Update</button>
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



    public function SingleUpdateStateCode(Request $post)
    {
        $rules = array(
            'state_id'  => 'required',
            'api_id'  => 'required',
            'state_code' => 'required',
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
            DB::table('api_state_codes')->updateOrInsert([
                'api_id'      => $post->api_id,
                'state_id' => $post->state_id,
                ],
                [
                'state_id' => $post->state_id,
                'api_id'      => $post->api_id,
                'state_code'   => $post->state_code,
            ]);
            $message = "Update sucessfuly";
            $data['type'] = 'success';
            $data['message'] =  $message;
        } catch (\Throwable $th) {
            $data['type'] = 'error';
            $data['message'] = $th->errorInfo;
        }
        return $data;
    }


    public function BulkUpdateStateCode(Request $post)
    {
        foreach ($post->state_id as $k => $v) {
            DB::table('api_state_codes')->updateOrInsert([
                'api_id'      => $post->state_api_id,
                'state_id' => $post->state_id[$k],
                ],
                [
                'state_id' => $post->state_id[$k],
                'api_id'      => $post->state_api_id,
                'state_code'   => $post->state_code[$k],
            ]);  
        } 
        $message = "Update sucessfuly";
        $data['type'] = 'success';
        $data['message'] =  $message;  
        return $data;
    }


    public function getProviderCodeData(Request $post)
    {

        $rules = array(
            'id' => 'required|numeric',
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

       $providers = DB::table('providers')->where('deleted_at', '!=' , 1)->where('service_id', $post->service)->where('status', 1)->get();
       foreach ($providers as $key => $provider) {
            $provider_code = DB::table('api_provider_codes')->where('provider_id', $provider->id)->where('api_id', $post->id)->first();
            if($provider_code){
                $provider->provider_code = $provider_code->provider_code;
            } else{
                $provider->provider_code = 0;
                
            }
        }
        $output = '';
		if ($providers->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Provider Name</th>
                <th>Provider Code</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($providers as $list) {
				$output .= '<tr id="' . $list->id . '_row">
                <td>' . $i . '</td>
                <td>
                <input type="hidden" id="' . $list->id . '_provider_id" name="provider_id[]" value="' . $list->id . '">
                ' . $list->provider_name . '
                </td>
               
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_provider_code" name="provider_code[]" value="' . $list->provider_code . '" required="required">
                </td>
                <td>
                    <button class="btn btn-primary waves-effect waves-light updateProviderCode" id="' . $list->id . '">Update</button>
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





    public function SingleUpdateProviderCode(Request $post)
    {
        $rules = array(
            'provider_id'  => 'required',
            'api_id'  => 'required',
            'provider_code' => 'required',
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
            DB::table('api_provider_codes')->updateOrInsert([
                'api_id'      => $post->api_id,
                'provider_id' => $post->provider_id,
                ],
                [
                'provider_id' => $post->provider_id,
                'api_id'      => $post->api_id,
                'provider_code'   => $post->provider_code,
            ]);
            $message = "Update sucessfuly";
            $data['type'] = 'success';
            $data['message'] =  $message;
        } catch (\Throwable $th) {
            $data['type'] = 'error';
            $data['message'] = $th->errorInfo;
        }
        return $data;
    }


    public function BulkUpdateProviderCode(Request $post)
    {
        foreach ($post->provider_id as $k => $v) {
            DB::table('api_provider_codes')->updateOrInsert([
                'api_id'      => $post->api_id,
                'provider_id' => $post->provider_id[$k],
                ],
                [
                'provider_id' => $post->provider_id[$k],
                'api_id'      => $post->api_id,
                'provider_code'   => $post->provider_code[$k],
            ]);  
        } 
        $message = "Update sucessfuly";
        $data['type'] = 'success';
        $data['message'] =  $message;  
        return $data;
    }


    public function getData(Request $post)
    {
        $get = DB::table('apis')->where('id', $post->id)->first();
        if($get){
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['data'] = $get;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }

    public function CheckLiveBalance(Request $post)
    {
        $get = DB::table('apis')->where('id', $post->id)->first();

       
        if($get){
            //echo "<pre>";print_r($get->balance_check_url);die;

            if($get->balance_check_url){
                $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => $get->balance_check_url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => $get->api_method,
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                ///echo "<pre>";print_r($response);die;
                $data['type'] = 'success';
                $data['message'] = $response;
            }else{
                $data['type'] = 'error';
                $data['message'] = "Something went wrong!";
            }
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }

    public function apiRequestLogs(Request $post)
    {
        $api = DB::table('apis')->where('id', $post->id)->where('deleted_at', '!=', 1)->first();
        if (!$api) {
            return response()->json(['type' => 'error', 'message' => 'API not found', 'data' => []]);
        }

        if (!Schema::hasTable('apilogs')) {
            return response()->json([
                'type' => 'success',
                'api_name' => $api->api_name,
                'data' => [],
            ]);
        }

        $hosts = [];
        foreach ([$api->api_url ?? '', $api->balance_check_url ?? ''] as $url) {
            $host = parse_url((string) $url, PHP_URL_HOST);
            if ($host) {
                $hosts[] = $host;
            }
        }
        $hosts = array_values(array_unique($hosts));

        $q = DB::table('apilogs')->orderByDesc('id');
        if (!empty($hosts)) {
            $q->where(function ($w) use ($hosts, $api) {
                foreach ($hosts as $host) {
                    $w->orWhere('url', 'like', '%' . $host . '%');
                }
                if (!empty($api->api_username)) {
                    $w->orWhere('url', 'like', '%' . $api->api_username . '%');
                    $w->orWhere('request', 'like', '%' . $api->api_username . '%');
                }
            });
        }

        $rows = $q->limit(15)
            ->get(['id', 'txnid', 'modal', 'url', 'header', 'request', 'response', 'created_at']);

        if ($rows->isEmpty() && !empty($hosts)) {
            $rows = DB::table('apilogs')
                ->orderByDesc('id')
                ->limit(15)
                ->get(['id', 'txnid', 'modal', 'url', 'header', 'request', 'response', 'created_at']);
        }

        return response()->json([
            'type' => 'success',
            'api_name' => $api->api_name,
            'data' => $rows,
        ]);
    }

    

    public function updateData(Request $post)
    {
        $rules = array(
            'apiName'  => 'required',

            'api_username' => 'required',
            'api_password' => 'required',
            'api_key' => 'required',
            'api_url' => 'required',
            'balance_check_url' => 'required',
            'complaint_api_url' => 'required',

            'complaint_api_method' => 'required',
            'complaint_status_value' => 'required',
            'refund_value' => 'required',
            
            'complaint_success_value' => 'required',
            'complaint_failed_value' => 'required',
            'complaint_callback_status_value' => 'required',
            'complaint_callback_success_value' => 'required',
            'complaint_callback_failed_value' => 'required',
            'complaint_callback_remark' => 'required',
            //'complaint_callback_api_method' => 'required',


            'status_value' => 'required',
            'success_value' => 'required',
            'failed_value' => 'required',
            'pending_value' => 'required',
            'error_value' => 'required',
            'error_value_response' => 'required',
            'order_id_value' => 'required',
            'operator_id_value' => 'required',
            'api_method' => 'required',
            'api_format' => 'required',
            'callback_status_value' => 'required',
            'callback_success_value' => 'required',
            'callback_failed_value' => 'required',
            'callback_order_id_value' => 'required',
            'callback_operator_id_value' => 'required',
            'callback_remark' => 'required',
            'callback_api_method' => 'required',
            'callback_refund_value' => 'required',
            'api_type' => 'required',

            'status' => 'required|numeric|digits:1',
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
        if($post->edit_id==0){
            $update = DB::table('apis')->insert([
                'api_name' => $post->apiName,
               
                'complaint_api_url' => $post->complaint_api_url,
                'complaint_api_method' => $post->complaint_api_method,
                'complaint_status_value' => $post->complaint_status_value,
                'complaint_success_value' => $post->complaint_success_value,
                'complaint_failed_value' => $post->complaint_failed_value,
                'complaint_callback_status_value' => $post->complaint_callback_status_value,
                'complaint_callback_success_value' => $post->complaint_callback_success_value,
                'complaint_callback_failed_value' => $post->complaint_callback_failed_value,
                'complaint_callback_remark' => $post->complaint_callback_remark,
                'complaint_callback_api_method' => $post->complaint_callback_api_method,
                'refund_value' => $post->refund_value,
                'callback_refund_value' => $post->callback_refund_value,

                
                'api_username' => $post->api_username,
                'api_password' => $post->api_password,
                'api_key' => $post->api_key,
                'api_url' => $post->api_url,
                'balance_check_url' => $post->balance_check_url,
                'status_value' => $post->status_value,
                'success_value' => $post->success_value,
                'failed_value' => $post->failed_value,
                'pending_value' => $post->pending_value,
                'error_value' => $post->error_value,
                'error_value_response' => $post->error_value_response,
                'order_id_value' => $post->order_id_value,
                'operator_id_value' => $post->operator_id_value,
                'api_method' => $post->api_method,
                'api_format' => $post->api_format,
                'callback_status_value' => $post->callback_status_value,
                'callback_success_value' => $post->callback_success_value,
                'callback_failed_value' => $post->callback_failed_value,
                'callback_order_id_value' => $post->callback_order_id_value,
                'callback_operator_id_value' => $post->callback_operator_id_value,
                'callback_remark' => $post->callback_remark,
                'callback_api_method' => $post->callback_api_method,
                'api_type' => $post->api_type,

                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            $message = "Create sucessfuly";
        }else{
            $update = DB::table('apis')->where('id', $post->edit_id)->update([
                'api_name' => $post->apiName,

                    
                    'complaint_api_url' => $post->complaint_api_url,
                    'complaint_api_method' => $post->complaint_api_method,
                    'complaint_status_value' => $post->complaint_status_value,
                    'complaint_success_value' => $post->complaint_success_value,
                    'complaint_failed_value' => $post->complaint_failed_value,
                    'complaint_callback_status_value' => $post->complaint_callback_status_value,
                    'complaint_callback_success_value' => $post->complaint_callback_success_value,
                    'complaint_callback_failed_value' => $post->complaint_callback_failed_value,
                    'complaint_callback_remark' => $post->complaint_callback_remark,
                    'complaint_callback_api_method' => $post->complaint_callback_api_method,
                    'refund_value' => $post->refund_value,
                    'callback_refund_value' => $post->callback_refund_value,

                    
                'api_username' => $post->api_username,
                'api_password' => $post->api_password,
                'api_key' => $post->api_key,
                'api_url' => $post->api_url,
                'balance_check_url' => $post->balance_check_url,
                'status_value' => $post->status_value,
                'success_value' => $post->success_value,
                'failed_value' => $post->failed_value,
                'pending_value' => $post->pending_value,
                'error_value' => $post->error_value,
                'error_value_response' => $post->error_value_response,
                'order_id_value' => $post->order_id_value,
                'operator_id_value' => $post->operator_id_value,
                'api_method' => $post->api_method,
                'api_format' => $post->api_format,
                'callback_status_value' => $post->callback_status_value,
                'callback_success_value' => $post->callback_success_value,
                'callback_failed_value' => $post->callback_failed_value,
                'callback_order_id_value' => $post->callback_order_id_value,
                'callback_operator_id_value' => $post->callback_operator_id_value,
                'callback_remark' => $post->callback_remark,
                'callback_api_method' => $post->callback_api_method,
                'api_type' => $post->api_type,


                'status' => $post->status,
                'updated_at' => Carbon::now()
            ]);
            $message = "Update sucessfuly";
        }
        if($update){
            $data['type'] = 'success';
            $data['message'] =  $message;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }
}
