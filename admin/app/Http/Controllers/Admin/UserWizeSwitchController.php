<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
class UserWizeSwitchController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.user-wize-switch');
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
        $total_row = DB::table('user_wize_switch')->get();
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

        $list = DB::table('user_wize_switch')->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
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
            $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
                <th>ID</th>
                <th>User Details</th>
                <th>Provider</th>
                <th>Service</th>
                <th>State</th>
                <th>API</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {

                $user_dt = DB::table('users')->where('id', $list->user_id)->first();

                if($user_dt){

                    $user_dt_first_name = $user_dt->first_name;

                    $user_dt_middle_name = $user_dt->middle_name;

                    $user_dt_last_name = $user_dt->last_name;

                    $user_dt_outlet_name = $user_dt->outlet_name;

                    $user_dt_mobile_number = $user_dt->mobile_number;

                }else{

                    $user_dt_first_name = "-";

                    $user_dt_middle_name = "";

                    $user_dt_last_name = "";

                    $user_dt_outlet_name = "-";

                    $user_dt_mobile_number = "-";

                }

                if($list->status=="1"){
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                }else{
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                 <td>

                    Name : '.$user_dt_first_name.' '.$user_dt_middle_name.' '.$user_dt_last_name.' </br>

                    Outlet Name : ' . $user_dt_outlet_name . ' </br>

                    Mobile No. : ' . $user_dt_mobile_number . ' </br>

                </td>
                <td>' . DB::table('providers')->where('id',$list->provider_id)->first()->provider_name . '</td>
                <td>' . DB::table('services')->where('id',$list->service_id)->first()->service_name . '</td>
                <td>' . DB::table('states')->where('id',$list->state_id)->first()->state_name . '</td>
                <td>' . DB::table('apis')->where('id',$list->api_id)->first()->api_name . '</td>
                <td>' . $list->amount . '</td>
                <td>' . $status . '</td>
                <td>
                    <a id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="' . $list->id . '" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
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
        $delete = DB::table('user_wize_switch')->where('id', '=',$post->id)->delete();
        if($delete){
            $data['type'] = 'success';
            $data['message'] = "Delete sucessfuly";
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        
        return $data;

    }

    public function getData(Request $post)
    {
        $get = DB::table('user_wize_switch')
        ->join('providers','providers.id' , '=', 'user_wize_switch.provider_id')
        ->where('user_wize_switch.id', $post->id)
        ->first([
            'providers.provider_name',
            'user_wize_switch.id',
            'user_wize_switch.user_id',
            'user_wize_switch.provider_id',
            'user_wize_switch.service_id',
            'user_wize_switch.amount',
            'user_wize_switch.api_id',
            'user_wize_switch.state_id',
            'user_wize_switch.status',
            'user_wize_switch.created_at',
            'user_wize_switch.updated_at',
        ]);
        
        if($get){
            $user_dt = DB::table('users')->where('id', $get->user_id)->first();
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['data'] = $get;
            $data['username'] = $user_dt->first_name.' '.$user_dt->middle_name.' '.$user_dt->last_name.' | '.$user_dt->outlet_name . ' | '.$user_dt->mobile_number;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }


    public function updateData(Request $post)
    {
        
       // return $post->account_type;
        if($post->edit_id==0){
            $rules = array(
                'id_value'  => 'required|numeric',
                'service_id'  => 'required|numeric',
                'provider_id'  => 'required|numeric',
                'api_id'  => 'required|numeric',
                'state_id'  => 'required|numeric',
                'amount_switch'  => 'required',
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


            $exit = DB::table('user_wize_switch')->where('user_id',$post->id_value)->where('service_id',$post->service_id)->where('provider_id',$post->provider_id)->where('state_id',$post->state_id)->first();
            if($exit){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "alerady add please edit."
                ));
            }
            $update = DB::table('user_wize_switch')->insert([
                'user_id' => $post->id_value,
                'service_id' => $post->service_id,
                'provider_id' => $post->provider_id,        
                'api_id' => $post->api_id,        
                'state_id' => $post->state_id,        
                'amount' => $post->amount_switch,
                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            //return $update;
            $message = "Create sucessfuly";
        }else{
            $rules = array(
                'edit_id'  => 'required|numeric',
                'amount_switch'  => 'required',
                'api_id'  => 'required|numeric',
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

            $update = DB::table('user_wize_switch')->where('id', $post->edit_id)->update([
                'api_id' => $post->api_id,
                'amount' => $post->amount_switch,
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
