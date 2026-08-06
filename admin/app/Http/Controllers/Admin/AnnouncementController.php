<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class AnnouncementController extends Controller
{
    public function index(Request $post)
    {
        $data['result'] = DB::table('announcements')->where('id', 1)->first()->message;
        return view('admin.system.announcement',$data);
    }

    public function getData(Request $post)
    {
        $get = DB::table('announcements')->where('id', 1)->first();
        // echo "<pre>";
        // print_r($get);
        // die;
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
            'announcement'  => 'required',
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

        $update = DB::table('announcements')->where('id', 1)->update([
            'message' => $post->announcement,
            'updated_at' => Carbon::now()
        ]);
        $message = "Update sucessfuly";
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

    
