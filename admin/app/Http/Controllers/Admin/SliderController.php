<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class SliderController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.slider');
    }

    public function fetchAll(Request $post)
    {
        $list = DB::table('sliders')->where('deleted_at', '!=' , 1)->orderBy('id', 'DESC')->get();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Image</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
                
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>' . $list->title . '</td>
                <td><img src="'.e(admin_slider_image($list->image)).'" class="avatar-xs rounded-3 me-2" style="height: 120px;width: 200px;"></td>
                
                <td>
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
        $delete = DB::table('sliders')->where('id', $post->id)->update(['deleted_at' => 1]);
        if($delete){
            $data['type'] = 'success';
            $data['message'] = "Delete sucessfuly";
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        
        return $data;

    }

    public function updateData(Request $post)
    {
        $rules = array(
            'slider_title'  => 'required',
            'slider_image' => 'required|mimes:jpeg,jpg,png|max:2048',
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
        if($post->slider_title){
                $slider_image = csrf_token().time().'.'.$post->slider_image->extension();  
                $post->slider_image->move(admin_slider_image_dir(), $slider_image);
            $update = DB::table('sliders')->insert([
                'user_id' => 1,
                'title' => $post->slider_title,
                'image' => $slider_image,
                'status' => $post->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            //return $update;
            $message = "Create sucessfuly";
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

    public function showImage(string $filename)
    {
        $filename = basename($filename);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($filename === '' || ! in_array($extension, $allowed, true)) {
            abort(404);
        }

        $path = admin_slider_image_disk_path($filename);
        if (! $path) {
            abort(404);
        }

        return response()->file($path);
    }
}
