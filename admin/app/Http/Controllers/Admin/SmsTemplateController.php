<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class SmsTemplateController extends Controller
{
    public function index(Request $post)
    {
        $data['categories'] = DB::table('message_categories')->get();
        return view('admin.company.sms-template', $data);
    }

    public function fetchAll(Request $post)
    {
        $list = DB::table('sms_templates')->orderBy('id', 'DESC')->get();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Template ID</th>
                <th>Content</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
                if($list->status=="1"){
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                }else{
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>' . ucwords(str_replace("_"," ",$list->slug)) . '</td>
                <td>' . $list->template_id . '</td>
                <td>' . $list->content . '</td>
                <td>' . $status . '</td>
                <td>
                    <a id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="' . $list->id . '" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
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
        $delete = DB::table('sms_templates')->where('id', $post->id)->delete();
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
        $get = DB::table('sms_templates')->where('id', $post->id)->first();
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
            'template_id'  => 'required',
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
            $update = DB::table('sms_templates')->insert([
                'slug' => $post->slug,
                'template_id' => $post->template_id,
                'content' => $post->content,
                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $message = "Create sucessfuly";
        }else{
            $update = DB::table('sms_templates')->where('id', $post->edit_id)->update([
                'slug' => $post->slug,
                'template_id' => $post->template_id,
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
