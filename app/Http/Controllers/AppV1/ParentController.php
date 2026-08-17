<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use DateTime;
use Session;
use DataTables;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
class ParentController extends Controller
{


    public function fetchAll(Request $post)
    {
        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
            'user_id' => 'numeric',
            'role_id' => 'numeric',
            'parent_id' => 'numeric',
            'min_wallet' => 'numeric',
            'max_wallet' => 'numeric',
            'status' => 'required',
            'kyc_status' => 'required',
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


        


        $query = DB::table('users')->where('deleted_at', '!=' , 1);


        if($post->s_user_id != 0){
            $query->where('id', $post->s_user_id);
        }

        if($post->max_wallet != 0){
            $query->whereBetween('wallet_balance', [round($post->min_wallet,2),round($post->max_wallet,2)]);
        }


        if($post->role_id == 0){
            $query->where('role_id', '!=' , 1);
        }else{
            $query->where('role_id', '!=' , 1)->where('role_id', $post->role_id);
        }

        if($post->parent_id != 0){
            $query->where('parent_id', $post->parent_id);
        }

        if($post->status !="All"){
            $query->where('status', $post->status);
        }

        if($post->kyc_status !="All"){
            $query->where('kyc_status', $post->kyc_status);
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
        $limit = 300;
        $start= ($page-1) * $limit;
        $total_row = $query->where('parent_id', $post->user_id)->get();
        //return $total_row;
        $total_row_count = $total_row->count();
        $total_pages = ceil($total_row_count / $limit);
        $list = $query->where('parent_id', $post->user_id)->orderBy('id', 'DESC')
        ->offset($start)
        ->limit($limit)
        ->get(['id','first_name','middle_name','last_name','outlet_name','email_address','mobile_number','wallet_balance','city','parent_id','role_id','status']);
        $list_count = $list->count();
        return response()->json(array(
            'type' => 'success',  
            'message' => "Fetch Successfully",
            'data' => $list
        ));
    }


    public function userlistUpdate(Request $post)
    {
           
        $rules = array(
            'outlet_name'  => 'required|string|max:70',
            'first_name'  => 'required|string|max:70',
            'middle_name'  => 'string|max:70',
            'last_name'  => 'string|max:70',
            'date_of_birth'  => 'required',
            'mobile_number' => 'required|unique:users,mobile_number|digits:10',
            'email_address' => 'required|email|unique:users,email_address',
            'gender'  => 'required|string|max:70',
            'flat_door_no'  => '',
            'road_street'  => '',
            'area_locality'  => 'required',
            'city'  => 'required',
            'state'  => 'required',
            'district'  => 'required',
            'bank_account_number'  => 'required|numeric|min:8',
            'branch_name'  => 'required',
            'ifsc_code'  => 'required',
            'bank_account_type'  => 'required',
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

        //echo "<pre>";print_r($post->all());die;
        if($post->edit_id==0){
            $user_f = DB::table('users')->where('id', $post->user_id)->first();
            if($user_f->role_id == 4){
                $role_id = 5;
            }else if($user_f->role_id == 5){
                $role_id = 6;
            }else{
                $data['type'] = 'error';
                $data['message'] = "Not Alowed Role Id Permission.";
                return $data;
            }

            $profilePic = "avatar-2.png";

            try {
                $g_pass = Str::random(8);
                $password = Hash::make($g_pass);
                $t_pin = \helpers::normalizeUserPin(random_int(0, 9999));
                $update = DB::table('users')->insert([
                    'parent_id'  => $post->user_id,
                    'role_id'  => $role_id,
                    'scheme_id'  => $user_f->scheme_id,
                    'outlet_name'  => $post->outlet_name,
                    'first_name'  => $post->first_name,
                    'middle_name'  => $post->middle_name,
                    'last_name'  => $post->last_name,
                    'date_of_birth'  => $post->date_of_birth,
                    'mobile_number' => $post->mobile_number,
                    'email_address' => $post->email_address,
                    'password' => $password,
                    't_pin' => $t_pin,
                    'login_type'  => "OTP",
                    'gender'  => $post->gender,
                    'flat_door_no'  => $post->flat_door_no,
                    'road_street'  => $post->road_street,
                    'area_locality'  => $post->area_locality,
                    'city'  => $post->city,
                    'state'  => $post->state,
                    'register_by'  => $user_f->mobile_number,
                    'district'  => $post->district,
                    'minium_balance'  => 0,
                    'kyc_status'  => "Pending",
                    'bank_account_number'  => $post->bank_account_number,
                    'branch_name'  => $post->branch_name,
                    'ifsc_code'  => $post->ifsc_code,
                    'bank_account_type'  => $post->bank_account_type,
                    'ip_address'  => '',
                    'callback_url'  => '',
                    'profile_pic' => $profilePic,
                    'status' => $post->status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                ////Send Whatsapp Message Start
                $user_data = DB::table('users')->where('mobile_number', $post->mobile_number)->first();
                $slug = 'create_user';
                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                $content = $sms_tmp->content;
                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                $content = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content);
                $content = str_replace('{PASSWORD}', '' . $g_pass . '', $content);
                $content = str_replace('{PIN}', '' . $t_pin . '', $content);
                if($sms_tmp->status == 1){
                    $msg_data = [
                        'mobile_number' => $post->mobile_number,
                        'content' => $content,
                        'template_id' => $sms_tmp->template_id,
                    ];
                    $sms = \helpers::sendWhatasappMsg($msg_data);
                }
                ////Send Whatsapp Message End
                ////Send Email Start
                $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                if($company->email_message == 1){
                    $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                    $content_email = $email_tmp->content;
                    $content_email = str_replace('{NAME}', '' . $user_data->first_name . '', $content_email);
                    $content_email = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content_email);
                    $content_email = str_replace('{PASSWORD}', '' . $g_pass . '', $content_email);
                    $content_email = str_replace('{PIN}', '' . $t_pin . '', $content_email);
                    Mail::to(strtolower($user_data->email_address))->queue(new SendEmail($email_tmp->subject,$content_email));
                }
                ////Send Email End
                try {
                    \App\Services\SystemSettingService::creditReferral((int) $post->user_id, (int) $user_data->id);
                } catch (\Throwable $e) {
                }
                return response()->json(array(
                    'type' => "success",  
                    'message' => "Register sucessfuly.Login Details send email,whatsapp & sms"
                ));
            } catch (\Exception $e) {
                $data['type'] = 'error';
                $data['message'] =  $e->getMessage();
                return $data;
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Not Alowed Edit Permission.";
            return $data;
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



    public function fundUpdate(Request $post)
    {
        $rules = array(
            'id'  => 'required|numeric',
            'type'  => 'required|in:Transfer',
            'amount' => 'required|numeric|gt:0',
            'remark' => 'required|max:50|string',
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
        $user = DB::table('users')->where('id', $post->user_id)->first();
        $fundBlock = \App\Services\SystemSettingService::fundTransferMessage($user, $post->amount);
        if ($fundBlock) {
            return response()->json(array(
                'type' => 'error',
                'message' => $fundBlock
            ));
        }

        if($post->type == "Transfer"){
            if($user->wallet_balance < $post->amount){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "You Have Insufficient your Wallet Balance"
                ));
            }
            DB::beginTransaction();

            try {
                $user = DB::table('users')->where('id', $post->user_id)->first();
                $by = $user->outlet_name;
                ///Report by first crediter by
                $order_id = "FND".rand(111111111,999999999);
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => '0',
                    'debit_user_id' => $post->id,
                    'amount' => $post->amount,
                    'total_amount' => $post->amount,
                    'fund_type' => "Debit",
                    'transaction_type' => "Transfer Money",
                    'remark' => $post->remark,
                    'order_id' => $order_id,
                    'status' => "Success",
                    'opening_balance' => $user->wallet_balance,
                    'closing_balance' => $user->wallet_balance - $post->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                $user_data = DB::table('users')->where('id', $user->id)->first(['id','first_name','mobile_number']);
                $BY = DB::table('users')->where('id', $post->id)->first(['id','first_name','mobile_number']);
                $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance - $post->amount]);

                $CURRENT_BALANCE = DB::table('users')->where('id', $user_data->id)->first();
                //////Send Sms By Cron Job Start
                  
                $slug = 'fund_transfer';
                $type = 'Debit';
                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                $content = $sms_tmp->content;
                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                $content = str_replace('{TYPE}', '' . $type . '', $content);
                $content = str_replace('{AMOUNT}', '' . $post->amount . '', $content);
                $content = str_replace('{BY}', '' . $BY->first_name . '', $content);
                $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                if($sms_tmp->status == 1){
                    DB::table('messages')->insert([
                        'user_id' => 1,
                        'to_user_id' => $user_data->id,
                        'subject' => $slug,
                        'msg_source' => "SMS",
                        'template_id' => $sms_tmp->template_id,
                        'content' => $content,
                        'status' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
                //////Send Sms By Cron Job End
                
                ///Report by first reciver by
                $user = DB::table('users')->where('id', $post->id)->first();     
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => $post->user_id,
                    'debit_user_id' => 0,
                    'amount' => $post->amount,
                    'total_amount' => $post->amount,
                    'fund_type' => "Credit",
                    'transaction_type' => "Receive Money",
                    'remark' => $post->remark,
                    'order_id' => $order_id,
                    'status' => "Success",
                    'opening_balance' => $user->wallet_balance,
                    'closing_balance' => $user->wallet_balance + $post->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                $user_data = DB::table('users')->where('id', $user->id)->first(['id','first_name','mobile_number']);
                $BY = DB::table('users')->where('id', $post->user_id)->first(['id','first_name','mobile_number']);
                $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance + $post->amount]);
                $CURRENT_BALANCE = DB::table('users')->where('id', $user_data->id)->first();
                //////Send Sms By Cron Job Start
                $slug = 'fund_receive';
                $type = 'Credit';
                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                $content = $sms_tmp->content;
                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                $content = str_replace('{TYPE}', '' . $type . '', $content);
                $content = str_replace('{AMOUNT}', '' . $post->amount . '', $content);
                $content = str_replace('{BY}', '' . $BY->first_name . '', $content);
                $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                if($sms_tmp->status == 1){
                    DB::table('messages')->insert([
                        'user_id' => 1,
                        'to_user_id' => $user_data->id,
                        'subject' => $slug,
                        'msg_source' => "SMS",
                        'template_id' => $sms_tmp->template_id,
                        'content' => $content,
                        'status' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
                //////Send Sms By Cron Job End
                DB::commit();
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Fund Transfer Successfully"
                ));
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something Went Wrong.",
                    //'error' => $e->getMessage()
                ));
            }
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong.Status"
            ));
        }
    }


    public function userListSearchUser(Request $post)
    {
        if($post->mobile_number != ''){
            $user = DB::table('users')
           ->where('deleted_at', '!=' , 1)
            ->where('mobile_number','LIKE','%'.$post->mobile_number.'%')
            ->where('parent_id', $post->user_id)
            ->get(['id','first_name','middle_name','last_name','outlet_name','email_address','mobile_number','wallet_balance','city','parent_id','role_id','status']);
            
        }
        return response()->json([
            'users' => $user
        ]);
    }

    public function userListSearchUserName(Request $post)
    {
        if($post->mobile_number != ''){
            $user = DB::table('users')
           ->where('deleted_at', '!=' , 1)
           ->where('parent_id', $post->user_id)
            ->where(function ($query) use ($post) {
                $query->where('first_name','LIKE','%'.$post->mobile_number.'%');
                $query->orWhere('outlet_name','LIKE','%'.$post->mobile_number.'%');
                $query->orWhere('mobile_number','LIKE','%'.$post->mobile_number.'%');
            })
            ->get(['id','first_name','middle_name','last_name','outlet_name','email_address','mobile_number','wallet_balance','city','parent_id','role_id','status']);
            
        }
        return response()->json([
            'users' => $user
        ]);
    }


    public function bankList(Request $post)
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
            return response()->json(array(
                'type' => 'error',  
                'message' => $error
            ));
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
        $total_row = DB::table('banks')->where('deleted_at', '!=', 1)->where('user_id', $post->user_id)->get();
        $total_row_count = $total_row->count();
        $total_pages = ceil($total_row_count / $limit);
        $list = DB::table('banks')->where('deleted_at', '!=' , 1)->where('user_id', $post->user_id)->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
        $list_count = $list->count();
        return response()->json(array(
            'type' => 'success',  
            'message' => "Fetch Successfully",
            'data' => $list
        ));
    }
        
    
}