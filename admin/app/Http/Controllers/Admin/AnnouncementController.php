<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class AnnouncementController extends Controller
{
    public function index(Request $post)
    {
        $this->ensureAnnouncement();
        $data['result'] = optional(DB::table('announcements')->where('id', 1)->first())->message ?? '';
        return view('admin.system.announcement',$data);
    }

    public function getData(Request $post)
    {
        $this->ensureAnnouncement();
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

        $this->ensureAnnouncement();
        DB::table('announcements')->updateOrInsert(
            ['id' => 1],
            [
                'message' => $post->announcement,
                'updated_at' => Carbon::now(),
            ]
        );

        return [
            'type' => 'success',
            'message' => 'Update sucessfuly',
        ];
    }

    private function ensureAnnouncement(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function ($table) {
                $table->id();
                $table->longText('message')->nullable();
                $table->timestamps();
            });
        }

        if (!DB::table('announcements')->where('id', 1)->exists()) {
            DB::table('announcements')->insert([
                'id' => 1,
                'message' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

}

    
