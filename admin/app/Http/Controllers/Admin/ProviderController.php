<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class ProviderController extends Controller
{
    public function index(Request $post)
    {
        //$data['service'] = DB::table('providers')->where('deleted_at', '!=' , 1)->where('status',1)->get();
        //$data['apis'] = DB::table('providers')->where('deleted_at', '!=' , 1)->where('status',1)->get();
        //return $data['service'];
        return view('admin.system.provider');
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
        $total_row = DB::table('providers')->where('deleted_at', '!=' , 1)->get();
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

        $list = DB::table('providers')->where('deleted_at', '!=' , 1)->orderBy('id', 'ASC')->offset($start)->limit($limit)->get();
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
                <th>Icon</th>
                <th>Name</th>
                <th>Service</th>
                <th>API</th>
                <th>Backup 1 API</th>
                <th>Backup 2 API</th>
                <th>Backup 3 API</th>
                <th>Minimum</th>
                <th>Maximum</th>
                <th>Online/Offline</th>
                <th>Com Type</th>
                <th>Com Value</th>
                <th>Status</th>
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
                if($list->provider_down=="0"){
                    $provider_down = '<span class="badge rounded-pill text-bg-success">Online</span>';
                }else{
                    $provider_down = '<span class="badge rounded-pill text-bg-danger">Offline</span>';
                }
                if($list->api_id==0){
                    $api_name = "NO API";
                }else{
                    $api_name = DB::table('apis')->where('id', $list->api_id)->first()->api_name;
                }
                
                if($list->backup_api_id==0){
                    $backup_api_name = "NO API";
                }else{
                    $backup_api_name = DB::table('apis')->where('id', $list->backup_api_id)->first()->api_name;
                }

                if($list->backup_api2_id==0){
                    $backup_api2_name = "NO API";
                }else{
                    $backup_api2_name = DB::table('apis')->where('id', $list->backup_api2_id)->first()->api_name;
                }

                if($list->backup_api3_id==0){
                    $backup_api3_name = "NO API";
                }else{
                    $backup_api3_name = DB::table('apis')->where('id', $list->backup_api3_id)->first()->api_name;
                }
                
                
                
				$output .= '<tr>
                <td>' . $i . '</td>
                <td><img src="'.asset("provider_logo/".$list->provider_logo).'" class="avatar-xs rounded-3 me-2"></td>
                <td>' . $list->provider_name . '</td>
                <td>' . DB::table('services')->where('id', $list->service_id)->first()->service_name . '</td>
                <td>' . $api_name . '</td>
                <td>' . $backup_api_name . '</td>
                <td>' . $backup_api2_name . '</td>
                <td>' . $backup_api3_name . '</td>
                <td>' . $list->minium_amount . '</td>
                <td>' . $list->maxium_amount . '</td>
                <td>' . $provider_down . '</td>
                <td>' . $list->amount_type . '</td>
                <td>' . $list->amount_value . '</td>
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
        $delete = DB::table('providers')->where('id', $post->id)->update(['deleted_at' => 1]);
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
        $get = DB::table('providers')->where('id', $post->id)->first();
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

    public function getDataService()
    {
        $service = DB::table('services')->select('id','service_name')->where('deleted_at', '!=' , 1)->where('status',1)->get();
        $states = DB::table('states')->select('id','state_name')->get();
        $apis = DB::table('apis')->select('id','api_name')->where('deleted_at', '!=' , 1)->where('status',1)->get();
        if($apis){
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['apis'] = $apis;
            $data['service'] = $service;
            $data['states'] = $states;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }


    public function updateData(Request $post)
    {
        $rules = array(
            'provider_name'  => 'required',
            'service_name'  => 'required|numeric',
            'api_name'  => 'required|numeric',
            'backup_api_name'  => 'required|numeric',
            'backup_api2_name'  => 'required|numeric',
            'backup_api3_name'  => 'required|numeric',
            'minium_amount'  => 'required',
            'maxium_amount'  => 'required',
            'provider_down'  => 'required|numeric',
            'amount_type'  => 'required',
            'amount_value'  => 'required',
            'provider_logo' => 'mimes:jpeg,jpg,png|max:10000',
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
        //return $post->amount_type;
        if($post->edit_id==0){
            if($post->provider_logo){
                $provider_logo = csrf_token().time().'.'.$post->provider_logo->extension();  
                $post->provider_logo->move(public_path('bank_logo'), $provider_logo);
                //$post->bank_logo->storeAs('public/bank_logo', $bankLogo);
                //return $bankLogo;
            }else{
                $provider_logo = "provider_logo.png";
            }
            $update = DB::table('providers')->insert([
                'provider_name' => $post->provider_name,
                'service_id' => $post->service_name,
                'api_id' => $post->api_name,
                'backup_api_id' => $post->backup_api_name,
                'backup_api2_id' => $post->backup_api2_name,
                'backup_api3_id' => $post->backup_api3_name,
                'minium_amount' => $post->minium_amount,
                'maxium_amount' => $post->maxium_amount,
                'provider_down' => $post->provider_down,
                'amount_type' => $post->amount_type,
                'amount_value' => $post->amount_value,
                'provider_logo' => $provider_logo,
                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            //return $update;
            $message = "Create sucessfuly";
        }else{
            if($post->provider_logo){
                $provider_logo = csrf_token().time().'.'.$post->provider_logo->extension();  
                $post->provider_logo->move(public_path('provider_logo'), $provider_logo);
               // $post->bank_logo->storeAs('public/bank_logo', $bankLogo);
                //return $bankLogo;
            }else{
                $provider_logo = $post->old_provider_logo;
            }
            $update = DB::table('providers')->where('id', $post->edit_id)->update([
                'provider_name' => $post->provider_name,
                'service_id' => $post->service_name,
                'api_id' => $post->api_name,
                'backup_api_id' => $post->backup_api_name,
                'backup_api2_id' => $post->backup_api2_name,
                'backup_api3_id' => $post->backup_api3_name,
                'minium_amount' => $post->minium_amount,
                'maxium_amount' => $post->maxium_amount,
                'provider_down' => $post->provider_down,
                'amount_type' => $post->amount_type,
                'amount_value' => $post->amount_value,
                'provider_logo' => $provider_logo,
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
