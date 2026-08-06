<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class RouteSettingController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.company.routes-settings');
    }

    public function fetchAll(Request $post)
    {
        $list = DB::table('routes_settings')->orderBy('id', 'ASC')->get();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Route Name</th>
                <th>Route Code</th>
                <th>Priority</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
				$output .= '<tr>
                <td>
                <input type="hidden" id="' . $list->id . '_route_id" name="_route_id[]" value="' . $list->id . '">
                ' . $i . '
                </td>
                <td>' . ucwords(str_replace("_"," ",$list->route_name)) . '</td>
                <td>' . $list->route_code . '</td>
                <td>
                    <select class="form-select edit_priority_' . $list->id . '" name="edit_priority[]" id="edit_priority">
                        <option ' . ($list->priority == "1" ? "selected" : "") . ' value="1">1</option>
                        <option ' . ($list->priority == "2" ? "selected" : "") . ' value="2">2</option>
                        <option ' . ($list->priority == "3" ? "selected" : "") . ' value="3">3</option>
                        <option ' . ($list->priority == "4" ? "selected" : "") . ' value="4">4</option>
                    </select>
                </td>
                <td>
                    <select class="form-select edit_status_' . $list->id . '" name="edit_status[]" id="edit_status">
                        <option ' . ($list->status == "1" ? "selected" : "") . ' value="1">Active</option>
                        <option ' . ($list->status == "0" ? "selected" : "") . ' value="0">Deactive</option>
                    </select>
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

    public function deleteData(Request $post)
    {
        $delete = DB::table('email_templates')->where('id', $post->id)->update(['deleted_at' => 1]);
        if($delete){
            $data['type'] = 'success';
            $data['message'] = "Delete sucessfuly";
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        
        return $data;

    }

    public function routesBulkUpdatePriority(Request $post)
    {

        //return $post->all();

        foreach ($post->_route_id as $k => $v) {
            //echo "<pre>";print_r($post->wt_comtype[$k]);
            //echo "<pre>";print_r($v);
            DB::table('routes_settings')->updateOrInsert(
                [
                    'id' => $post->_route_id[$k],
                ],
                [
                    'id' => $post->_route_id[$k],
                    'priority' => $post->edit_priority[$k],
                    'status' => $post->edit_status[$k],
                ]
            );
        }
        $message = "Update sucessfuly";
        $data['type'] = 'success';
        $data['message'] = $message;
        return $data;
        
    }

    

    public function getData(Request $post)
    {
        $get = DB::table('email_templates')->where('id', $post->id)->first();
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
            'slug'  => 'required',
            'subject'  => 'required',
            'content'  => 'required',
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
            return response()->json(array(
                'type' => 'error',  
                'message' => "server down."
            ));
        }else{
            $update = DB::table('email_templates')->where('id', $post->edit_id)->update([
                'slug' => $post->slug,
                'subject' => $post->subject,
                'content' => $post->content,
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
