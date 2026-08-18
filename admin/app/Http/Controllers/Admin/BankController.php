<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
class BankController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.bank');
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
        $total_row = DB::table('banks')->where('deleted_at', '!=', 1)->where('user_id', Session::get('user_id'))->get();
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

        $list = DB::table('banks')->where('deleted_at', '!=' , 1)->where('user_id', Session::get('user_id'))->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
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
                <th>Bank</th>
                <th>Name</th>
                <th>Number</th>
                <th>IFSC</th>
                <th>Type</th>
                <th>Branch</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Updated at</th>
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
				$output .= '<tr>
                <td>' . $i . '</td>
                <td><img src="'.asset("bank_logo/".$list->bank_logo).'" class="avatar-xs rounded-3 me-2"></td>
                <td>' . $list->bank_name . '</td>
                <td>' . $list->account_name . '</td>
                <td>' . $list->account_number . '</td>
                <td>' . $list->ifsc_code . '</td>
                <td>' . $list->account_type . '</td>
                <td>' . $list->bank_branch . '</td>
                <td>' . $status . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->updated_at . '</td>
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
        $delete = DB::table('banks')->where('id', $post->id)->update(['deleted_at' => 1]);
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
        $get = DB::table('banks')->where('id', $post->id)->first();
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


    public function updateData(Request $post)
    {
        $rules = array(
            'account_name'  => 'required',
            'account_number'  => 'required|numeric|min:8',
            'bank_name'  => 'required',
            'bank_branch'  => 'required',
            'ifsc_code'  => 'required',
            'account_type'  => 'required',
            'bank_logo' => 'mimes:jpeg,jpg,png|max:10000',
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
       // return $post->account_type;
        if($post->edit_id==0){
            if($post->bank_logo){
                $bankLogo = csrf_token().time().'.'.$post->bank_logo->extension();  
                $post->bank_logo->move(public_path('bank_logo'), $bankLogo);
                //$post->bank_logo->storeAs('public/bank_logo', $bankLogo);
                //return $bankLogo;
            }else{
                $bankLogo = "bank_logo.png";
            }
            $update = DB::table('banks')->insert([
                'user_id' => 1,
                'account_name' => $post->account_name,
                'account_number' => $post->account_number,
                'bank_name' => $post->bank_name,
                'bank_branch' => $post->bank_branch,
                'ifsc_code' => $post->ifsc_code,
                'account_type' => $post->account_type,
                'bank_logo' => $bankLogo,
                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            //return $update;
            $message = "Create sucessfuly";
        }else{
            if($post->bank_logo){
                $bankLogo = csrf_token().time().'.'.$post->bank_logo->extension();  
                $post->bank_logo->move(public_path('bank_logo'), $bankLogo);
               // $post->bank_logo->storeAs('public/bank_logo', $bankLogo);
                //return $bankLogo;
            }else{
                $bankLogo = $post->old_bank_logo;
            }
            $update = DB::table('banks')->where('id', $post->edit_id)->update([
                'account_name' => $post->account_name,
                'account_number' => $post->account_number,
                'bank_name' => $post->bank_name,
                'bank_branch' => $post->bank_branch,
                'ifsc_code' => $post->ifsc_code,
                'account_type' => $post->account_type,
                'bank_logo' => $bankLogo,
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

    public function banks_list($id)
    {
        try {
            $banks = DB::table('banks')
                ->where('deleted_at', '!=', 1)
                ->where('user_id', (int) $id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->get([
                    'id',
                    'account_name',
                    'account_number',
                    'bank_name',
                    'bank_branch',
                    'ifsc_code',
                    'account_type',
                    'bank_logo',
                    'status',
                ]);

            return response()->json([
                'type' => 'success',
                'data' => $banks,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('banks_list failed: '.$e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch banks.',
            ], 500);
        }
    }
}
