<?php

namespace App\Http\Controllers\Users;

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
class UserListController extends Controller
{

    public function index(Request $post)
    {
        ///$data['scheme'] = DB::table('schemes')->where('deleted_at', '!=' , 1)->where('status', 1)->get();
        $data['role'] = DB::table('roles')->where('status', 1)->get();
        //$data['user'] = DB::table('users')->where('deleted_at', '!=' , 1)->orderBy('id', 'ASC')->get();
        return view('users.users.list', $data);
    }


    public function userListSearchUser(Request $post)
    {
        if($post->keyword != ''){
            $user = DB::table('users')
           ->where('deleted_at', '!=' , 1)
            ->where('mobile_number','LIKE','%'.$post->keyword.'%')
            ->where('parent_id', Session::get('user_id'))
            ->get(['id','first_name','middle_name','last_name','outlet_name','mobile_number']);
            
        }
        return response()->json([
            'users' => $user
        ]);
    }

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
            return '<h4 class="text-center text-secondary my-3">'.$error.'</h4>';
        }


        $query = DB::table('users')->where('deleted_at', '!=' , 1);


        if($post->user_id != 0){
            $query->where('id', $post->user_id);
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
        $start= ($page-1) * $limit;
        $total_row = $query->where('parent_id', Session::get('user_id'))->get();
        //return $total_row;
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
        $list = $query->where('parent_id', Session::get('user_id'))->orderBy('id', 'DESC')
        ->offset($start)
        ->limit($limit)
        ->get();
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
                <th>Profile</th>
                <th>Full Name</th>
                <th>Outlet Name</th>
                <th>Role</th>
                <th>Parent Name</th>
                <th>Email Address</th>
                <th>Mobile Number</th>
                <th>Wallet Balance</th>
                <th>Minimum Balance</th>
                <th>Register By</th>
                <th>Status</th>
                <th>Kyc Status</th>
                <th>Created at</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
                $role = DB::table('roles')->where('id', $list->role_id)->first(['role_name'])->role_name;
                
                $user = DB::table('users')->where('id', $list->parent_id)->first();
                if(!$role){
                    $role = '';
                }
                if($user){
                    $parent = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' | ' . $user->mobile_number;
                }else{
                    $parent = '-';
                }
                if($list->status=="1"){
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                }else{
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }

                if($list->kyc_status == "Approved"){
                    $bg = "success";
                    $inr = "green";
                }elseif ($list->kyc_status == "Rejected") {
                    $bg = "danger";
                    $inr = "red";
                }else if ($list->kyc_status == "Pending"){
                    $bg = "warning";
                    $inr = "black";
                }else if ($list->kyc_status == "Under Process"){
                    $bg = "primary";
                    $inr = "blue";        
                }else{
                    $bg = "dark";
                    $inr = "black";
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td><img src="'.env('ADMIN_HOST')."/profile_pic/".$list->profile_pic.'" class="avatar-xs rounded-3 me-2"></td>
                <td>' . $list->first_name . ' ' . $list->middle_name . ' ' . $list->last_name . '</td>
                <td>' . $list->outlet_name . '</td>
                <td>' . $role . '</td>
                <td>' . $parent . '</td>
                <td>' . $list->email_address . '</td>
                <td>' . $list->mobile_number . '</td>
                <td style="color: green;font-size: 18px;"> ₹ ' . $list->wallet_balance . '</td>
                <td style="color: red;font-size: 18px;"> ₹ ' . $list->minium_balance . '</td>
                <td>' . $list->register_by . '</td>
                <td>' .  $status . '</td>
                <td> <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->kyc_status . '</span></td>
                <td>' . $list->created_at . '</td>
                <td>
                    <a id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="' . $list->id . '" class="badge text-bg-info fundTransfer"><i class="ri-wallet-3-fill align-bottom"></i> Transfer</a>
                    
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

    public function getData(Request $post)
    {
        $get = DB::table('users')->where('id', $post->id)->first();
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
            $user_f = DB::table('users')->where('id', Session::get('user_id'))->first();
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
                    'parent_id'  => Session::get('user_id'),
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
        $user = DB::table('users')->where('id', Session::get('user_id'))->first();
        

        if($post->type == "Transfer"){
            if($user->wallet_balance < $post->amount){
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "You Have Insufficient your Wallet Balance"
                ));
            }
            DB::beginTransaction();

            try {
                $user = DB::table('users')->where('id', Session::get('user_id'))->first();
                //$by = $user->outlet_name;
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
                    'credit_user_id' => Session::get('user_id'),
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
                $BY = DB::table('users')->where('id', Session::get('user_id'))->first(['id','first_name','mobile_number']);
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
                return $e->getMessage();
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something Went Wrong."
                ));
            }
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something Went Wrong.Status"
            ));
        }
    }
}
