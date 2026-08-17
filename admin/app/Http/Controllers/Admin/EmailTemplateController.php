<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class EmailTemplateController extends Controller
{
    public function index(Request $post)
    {
        $data['categories'] = collect();
        try {
            $data['categories'] = DB::table('message_categories')->get();
        } catch (\Throwable $e) {
        }
        $data['brand'] = function_exists('email_brand') ? email_brand() : [
            'name' => 'NETCELL PAY',
            'logo' => '',
            'support_email' => '',
            'support_phone' => '',
            'website' => 'https://netcellpay.in',
            'year' => date('Y'),
        ];
        return view('admin.company.email-template', $data);
    }

    public function fetchAll(Request $post)
    {
        $list = DB::table('email_templates')->where('deleted_at', '!=' , 1)->where('user_id', 1)->orderBy('id', 'DESC')->get();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Subject</th>
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
                <td>' . $list->subject . '</td>
                <td>' . e(\Illuminate\Support\Str::limit(strip_tags((string) $list->content), 70)) . '</td>
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
