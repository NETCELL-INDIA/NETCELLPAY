<?php

namespace App\Http\Controllers\Admin;

use App\Common;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Mail\SendEmail;
class UserListController extends Controller
{

    public function sendMessage()
    {
        $data['role'] = DB::table('roles')->where('status', 1)->get();
        return view('admin.users.send-message', $data);
    }

    public function sendMessageUsers(Request $post)
    {
        $rules = array(
            'msg_source' => 'required|IN:SMS,EMAIL,WHATSAPP,NOTIFICATION',
            'user_id' => 'numeric',
            'role_id' => 'numeric',
            'message_text' => 'required',
            'subject' => 'required|string',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json([
                'message' => $error,
                'type' => 'error',
            ]);
        }

        

        if ((int) $post->user_id !== 0) {
            $users = DB::table('users')
            ->where('status', 1)
            ->where('deleted_at',0)
            ->whereNotIn('role_id', [1, 2])
            ->where('id',$post->user_id)
            ->get(['id','first_name','email_address','mobile_number']);
        } elseif ((int) $post->role_id !== 0) {
            $users = DB::table('users')
            ->where('status', 1)
            ->where('deleted_at',0)
            ->where('role_id',$post->role_id)
            ->get(['id','first_name','email_address','mobile_number']);
        } else {
            $users = DB::table('users')
            ->where('status', 1)
            ->where('deleted_at',0)
            ->whereNotIn('role_id', [1, 2])
            ->get(['id','first_name','email_address','mobile_number']);
        }

        if($users){
            $num['mobile'] = [];
            $num['email'] = [];
            foreach ($users as $user){ 
                $num['mobile'][] = $user->mobile_number;
                $num['email'][] = $user->email_address;
            }

                
            if($post->msg_source == "SMS"){
                $mobiles = '';
                foreach ($num['mobile'] as $mobile){
                    $mobiles .= $mobile."|";
                }
                return $mobiles;
            }else if($post->msg_source == "NOTIFICATION"){
                if (! Common::fcmServerKey()) {
                    return response()->json([
                        'message' => 'FCM Server Key missing. Save it in System Setting → Pusher setting → FCM Server Key, then try again.',
                        'type' => 'error',
                    ]);
                }

                $sent = 0;
                $noToken = 0;
                $failed = 0;
                $title = trim((string) $post->subject);
                $body = trim(strip_tags((string) $post->message_text));
                foreach ($users as $user) {
                    $result = Common::pushNotifyUser((int) $user->id, $title, $body, [], $title);
                    if ($result === true) {
                        $sent++;
                    } elseif ($result === 'no_token') {
                        $noToken++;
                    } else {
                        $failed++;
                    }
                }

                $parts = ["Push sent to {$sent} user(s)"];
                if ($noToken) {
                    $parts[] = "{$noToken} have no app notification token. User must open the Netcell Pay Android app once (after this update) so FCM token is saved";
                }
                if ($failed) {
                    $parts[] = "{$failed} failed to send";
                }

                return response()->json([
                    'message' => implode('. ', $parts).'.',
                    'type' => $sent > 0 ? 'success' : 'error',
                ]);
            }else if($post->msg_source == "WHATSAPP"){
                $mobiles = '';
                foreach ($num['mobile'] as $mobile){
                    $mobiles .= "91".$mobile."|";
                }
                $w_api = DB::table('companies')->where('id', 1)->first(['whatsapp_request_url','whatsapp_api_method']);
                $url = $w_api->whatsapp_request_url;
                if($url !=0 || $url !=""){
                    $url = str_replace('{MOB}', '' . $mobiles . '', $url);
                    $url = str_replace('{MSG}', '' . urlencode($post->message_text) . '', $url);
                    $url = str_replace('{TMP_ID}', '' . $post->tmp_id . '', $url);
                    $method = $w_api->whatsapp_api_method;
                    $header = [];
                    $parameters = "";
                    $request_id = "WAS".date("YmdHis").rand(11111, 999999);
                    $curl = Common::curl($url, $method, $parameters, $header, "yes", "WHATSAPP_URL", $request_id);
                    return response()->json([
                        'message' => "whatsapp sms send successfully", 
                        'type' => 'success',
                    ]);
                }else{
                    return response()->json([
                        'message' => "something went wrong",
                        'type' => 'error',
                    ]);
                }
            }else if($post->msg_source == "EMAIL"){
                $body = $post->message_text;
                $subject = $post->subject;
                $view = 'admin.emails.welcome';
                foreach ($num['email'] as $email){
                    Mail::to($email)->send(new SendEmail($body,$subject,$view));
                }
                return response()->json([
                    'message' => 'Email sent successfully',
                    'type' => 'success',
                ]);
            }
            return response()->json([
                'message' => "Message sent successfully",
                'type' => 'success',
            ]);
        }else{
            return response()->json([
                'message' => "users not found",
                'type' => 'error',
            ]);
        }
    }
    

    public function index(Request $post)
    {
        $data['scheme'] = DB::table('schemes')->where('deleted_at', '!=' , 1)->where('status', 1)->get();
        $data['role'] = DB::table('roles')->where('status', 1)->get();
        $data['parents'] = DB::table('users as u')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->whereIn('u.role_id', [1, 4, 5])
            ->where('u.deleted_at', 0)
            ->orderBy('u.role_id')
            ->orderBy('u.outlet_name')
            ->get([
                'u.id',
                'u.outlet_name',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.mobile_number',
                'u.role_id',
                'r.role_name',
            ]);
        return view('admin.users.list', $data);
    }

    public function parentListSearchUuser(Request $post)
    {
        $users = collect();
        $keyword = trim((string) $post->keyword);
        if ($keyword !== '') {
            $users = DB::table('users')
                ->whereIn('role_id', [1, 4, 5])
                ->where('deleted_at', 0)
                ->where(function ($q) use ($keyword) {
                    $q->where('mobile_number', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email_address', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('outlet_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('first_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
                })
                ->limit(25)
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'outlet_name', 'mobile_number']);
        }
        return response()->json([
            'users' => $users
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


        $query = DB::table('users')->where('deleted_at', 0);


        if($post->user_id != 0){
            $query->where('id', $post->user_id);
        }

        $keyword = trim((string) $post->input('keyword', ''));
        if ($keyword !== '') {
            $userIdFromCode = Common::findUserIdByCode($keyword);
            if ($userIdFromCode) {
                $query->where('id', $userIdFromCode);
            } else {
                $query->where(function ($q) use ($keyword) {
                    $q->where('mobile_number', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email_address', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('outlet_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('first_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
                    if (ctype_digit($keyword)) {
                        $q->orWhere('id', (int) $keyword);
                    }
                });
            }
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
        $total_row = $query->get();
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
        $list = $query->orderBy('id', 'DESC')
        ->offset($start)
        ->limit($limit)
        ->get();
        $list_count = $list->count();
        $output = '';
		if ($list->count() > 0) {
			$output .= '<div class="users-list-wrap">';
            $output .= '<div class="users-list-toolbar">
                <div class="users-list-toolbar__left">
                    <label class="users-list-toolbar__label">Show</label>
                    <select class="form-select form-select-sm page_limit" aria-label="Page Limit" id="page_limit">
                        <option ' . ($limit == "5" ? "selected" : "") . ' value="5">5</option>
                        <option ' . ($limit == "10" ? "selected" : "") . ' value="10">10</option>
                        <option ' . ($limit == "15" ? "selected" : "") . ' value="15">15</option>
                        <option ' . ($limit == "25" ? "selected" : "") . ' value="25">25</option>
                        <option ' . ($limit == "50" ? "selected" : "") . ' value="50">50</option>
                    </select>
                    <span class="users-list-toolbar__label">entries</span>
                </div>
                <div class="users-list-toolbar__right">
                    <input type="text" class="form-control form-control-sm" id="searchValueTable" placeholder="Search in table...">
                </div>
            </div>';
            $output .= '<div class="table-responsive users-list-table-wrap">
            <table class="table table-sm table-hover users-list-table mb-0" id="pagination_table"><thead>
              <tr>
                <th class="col-id">#</th>
                <th class="col-code">User ID</th>
                <th class="col-name">Name</th>
                <th class="col-mobile">Mobile</th>
                <th class="col-role">Role</th>
                <th class="col-wallet text-end">Wallet</th>
                <th class="col-status">Status</th>
                <th class="col-date">Created</th>
                <th class="col-action text-center">Action</th>
              </tr>
            </thead>
            <tbody>';
            $i = $start + 1;
            $roleSeqMap = Common::getUserRoleSequencesForIds($list->pluck('id')->all());
			foreach ($list as $row) {
                $roleRow = DB::table('roles')->where('id', $row->role_id)->first();
                $role = $roleRow->role_name ?? '';

                $userCode = Common::buildUserCode(
                    (int) $row->role_id,
                    $roleSeqMap[(int) $row->id] ?? Common::getUserRoleSequence((int) $row->id, (int) $row->role_id)
                );

                $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                if ($fullName === '') {
                    $fullName = $row->outlet_name ?? '-';
                }

                if ((string) $row->status === '1') {
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                } else {
                    $status = '<span class="badge rounded-pill text-bg-danger">Inactive</span>';
                }

                $created = $row->created_at
                    ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y')
                    : '-';

                $wallet = '₹ ' . number_format((float) $row->wallet_balance, 2);

				$output .= '<tr>
                <td>' . $i . '</td>
                <td><span class="users-code">' . e($userCode) . '</span></td>
                <td class="users-td-name">
                    <span class="users-name">' . e($fullName) . '</span>' .
                    (!empty($row->outlet_name) ? '<span class="users-outlet">' . e($row->outlet_name) . '</span>' : '') . '
                </td>
                <td>' . e($row->mobile_number) . '</td>
                <td>' . e($role) . '</td>
                <td class="text-end users-wallet">' . $wallet . '</td>
                <td>' . $status . '</td>
                <td class="users-date">' . $created . '</td>
                <td>
                    <div class="users-action-btns">
                    <a id="' . $row->id . '" class="btn btn-soft-info editDetails" title="View"><i class="ri-eye-line"></i></a>
                    <a id="' . $row->id . '" class="btn btn-soft-primary editDetails" title="Edit"><i class="ri-pencil-line"></i></a>
                    <a id="' . $row->id . '" class="btn btn-soft-success fundTransfer" title="Fund"><i class="ri-wallet-3-line"></i></a>
                    <a id="' . $row->id . '" class="btn btn-soft-warning resetPassword" title="Reset Password" data-user-name="' . e($fullName) . '" data-user-mobile="' . e($row->mobile_number) . '" data-user-pin="' . e($row->t_pin ?? '') . '"><i class="ri-lock-password-line"></i></a>
                    <a id="' . $row->id . '" class="btn btn-soft-danger deleteData" title="Delete"><i class="ri-delete-bin-line"></i></a>
                    </div>
                </td>
              </tr>';
              $i++;
			}
			$output .= '</tbody></table></div>';
            $output .= '<div class="users-list-footer">
                <span class="users-list-count">Showing ' . ($start + 1) . ' to ' . ($start + $list_count) . ' of ' . $total_row_count . ' entries</span>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item ' . ($page == 1 || $page == 0 ? "disabled" : "") . '">
                            <a class="page-link" href="javascript:void(0)" onclick="tableSearch(' . ($page - 1) . ')">Prev</a>
                        </li>
                        ' . $page_link . '
                        <li class="page-item ' . ($page + 1 == $i1 ? "disabled" : "") . '">
                            <a class="page-link" href="javascript:void(0)" onclick="tableSearch(' . ($page + 1 == $i1 ? $page : $page + 1) . ')">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>';

            $output .= '</div>';
			echo $output;
		} else {
			echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
		}
        
    }

    public function deleteData(Request $post)
    {
        $delete = DB::table('users')->where('id', $post->id)->update(['deleted_at' => 1]);
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
        ensure_user_visible_password_column();

        $get = DB::table('users')->where('id', $post->id)->first();
        if($get){
            unset($get->password, $get->login_key, $get->otp, $get->email_otp);
            $parent = null;
            if ((int) ($get->parent_id ?? 0) > 0) {
                $parent = DB::table('users as u')
                    ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
                    ->where('u.id', $get->parent_id)
                    ->first([
                        'u.id',
                        'u.outlet_name',
                        'u.first_name',
                        'u.last_name',
                        'u.mobile_number',
                        'u.role_id',
                        'r.role_name',
                    ]);
            }
            return response()->json([
                'type' => 'success',
                'message' => 'Get sucessfuly',
                'data' => $get,
                'parent' => $parent,
            ]);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'User not found.',
        ]);
    }


    public function updateData(Request $post)
    {
        $bankEnabled = $post->input('bank_details_enabled', '0') === '1';
        $editId = (int) $post->edit_id;
        $isEdit = $editId > 0;

        $mobileRules = 'required|digits:10';
        $emailRules = 'required|email';
        if ($isEdit) {
            $mobileRules .= '|unique:users,mobile_number,' . $editId;
            $emailRules .= '|unique:users,email_address,' . $editId;
        } else {
            $mobileRules .= '|unique:users,mobile_number';
            $emailRules .= '|unique:users,email_address';
        }

        $rules = array(
            'parent_id'  => 'required|numeric',
            'role_id'  => 'required|numeric',
            'scheme_id'  => 'required|numeric',
            'outlet_name'  => 'required|string|max:70',
            'first_name'  => 'required|string|max:70',
            'date_of_birth'  => 'required',
            'mobile_number' => $mobileRules,
            'email_address' => $emailRules,
            'login_type'  => 'required|in:PASSWORD,OTP',
            'gender'  => 'required|string|max:70',
            'flat_door_no'  => '',
            'road_street'  => '',
            'area_locality'  => 'required',
            'city'  => 'required',
            'state'  => 'required',
            'district'  => 'required',
            'minium_balance'  => 'required|numeric',
            'kyc_status'  => 'required',
            'bank_account_number'  => $bankEnabled ? 'required|numeric|min:8' : 'nullable',
            'branch_name'  => $bankEnabled ? 'required' : 'nullable',
            'ifsc_code'  => $bankEnabled ? 'required' : 'nullable',
            'bank_account_type'  => $bankEnabled ? 'required' : 'nullable',
            'ip_address'  => '',
            'callback_url'  => '',
            'complaint_callback_url'  => '',
            'profile_pic' => 'nullable|mimes:jpeg,jpg,png|max:2048',
            'status' => 'required|numeric|digits:1',
        );

        if ($isEdit && $post->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $bankAccountNumber = $bankEnabled ? $post->bank_account_number : 0;
        $branchName = $bankEnabled ? (string) $post->branch_name : '';
        $ifscCode = $bankEnabled ? (string) $post->ifsc_code : '';
        $bankAccountType = $bankEnabled ? (string) $post->bank_account_type : 'Savings';
        $ipAddress = trim((string) $post->ip_address);
        if (in_array($ipAddress, ['1.1.1.1', '0.0.0.0'], true)) {
            $ipAddress = '';
        }

        $validator = \Validator::make($post->all(), array_reverse($rules), [
            'mobile_number.unique' => 'This mobile number is already registered.',
            'email_address.unique' => 'This email address is already registered.',
        ]);
        if ($validator->fails()) {
            if ($validator->errors()->has('mobile_number')) {
                $duplicateResponse = $this->duplicateUserResponse('mobile_number', $post->mobile_number, $isEdit ? $editId : 0);
                if ($duplicateResponse) {
                    return $duplicateResponse;
                }
            }
            if ($validator->errors()->has('email_address')) {
                $duplicateResponse = $this->duplicateUserResponse('email_address', $post->email_address, $isEdit ? $editId : 0);
                if ($duplicateResponse) {
                    return $duplicateResponse;
                }
            }
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',
                'message' => $error
            ));
        }

        $dob = $this->normalizeDateOfBirth($post->date_of_birth);
        if ($dob === '') {
            return response()->json([
                'type' => 'error',
                'message' => 'Please enter a valid Date of Birth.',
            ]);
        }

        $parentId = (int) $post->parent_id;
        if ($parentId > 0) {
            $existingParentId = $isEdit
                ? (int) DB::table('users')->where('id', $editId)->value('parent_id')
                : 0;
            if ($parentId !== $existingParentId) {
                $parentOk = DB::table('users')
                    ->where('id', $parentId)
                    ->whereIn('role_id', [1, 4, 5])
                    ->where('deleted_at', 0)
                    ->exists();
                if (!$parentOk) {
                    return response()->json([
                        'type' => 'error',
                        'message' => 'Parent must be Admin, Master Distributor, or Distributor.',
                    ]);
                }
            }
        }

        if($post->edit_id==0){
            if($post->profile_pic){
                $profilePic = csrf_token().time().'.'.$post->profile_pic->extension();  
                $post->profile_pic->move(public_path('profile_pic'), $profilePic);
            }else{
                $profilePic = "avatar-2.png";
            }
            try {
                $this->ensureUserSaveColumns();
                $g_pass = Str::random(4).random_int(1000, 9999).Str::random(2);
                $t_pin = normalize_user_pin(random_int(0, 9999));
                $apiKey = ((int) $post->role_id === 3)
                    ? (Str::random(15) . rand(11111111, 9999999) . Str::random(15))
                    : null;
                $row = array_merge([
                    'parent_id'  => $post->parent_id,
                    'role_id'  => $post->role_id,
                    'scheme_id'  => $post->scheme_id,
                    'outlet_name'  => $post->outlet_name,
                    'first_name'  => $post->first_name,
                    'middle_name'  => '',
                    'last_name'  => '',
                    'date_of_birth'  => $dob,
                    'mobile_number' => $post->mobile_number,
                    'email_address' => $post->email_address,
                    't_pin' => $t_pin,
                    'login_type'  => $post->login_type,
                    'gender'  => $post->gender,
                    'flat_door_no'  => $post->flat_door_no ?: '',
                    'road_street'  => $post->road_street ?: '',
                    'pincode'  => preg_replace('/\D+/', '', (string) $post->pincode),
                    'area_locality'  => $post->area_locality,
                    'city'  => $post->city,
                    'state'  => $post->state,
                    'register_by'  => "Admin",
                    'district'  => $post->district,
                    'minium_balance'  => $post->minium_balance,
                    'kyc_status'  => $post->kyc_status,
                    'bank_account_number'  => $bankAccountNumber,
                    'branch_name'  => $branchName,
                    'ifsc_code'  => $ifscCode,
                    'bank_account_type'  => $bankAccountType,
                    'ip_address'  => $ipAddress,
                    'callback_url'  => $post->callback_url,
                    'complaint_callback_url'  => $post->complaint_callback_url,
                    'api_key' => $apiKey,
                    'profile_pic' => $profilePic,
                    'status' => $post->status,
                    'wallet_balance' => 0,
                    'otp_limit' => 0,
                    'otp_created_at' => null,
                    'deleted_at' => 0,
                    'login_key' => Str::random(40),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ], user_password_update_fields($g_pass));

                DB::table('users')->insert(filter_users_columns($row));

                $sent = false;
                try {
                    $sent = $this->sendCreatedUserCredentials($post, $g_pass, $t_pin);
                } catch (\Throwable $notifyError) {
                    Log::warning('Create user notify failed: '.$notifyError->getMessage());
                }

                $successMessage = 'User registered successfully.';
                if ($post->boolean('auto_send')) {
                    $successMessage .= $sent
                        ? ' Password sent to mobile/email: '.$g_pass
                        : ' Password: '.$g_pass;
                }

                return response()->json(array(
                    'type' => "success",  
                    'message' => $successMessage
                ));
            } catch (\Exception $e) {
                Log::error('Create user failed: '.$e->getMessage());
                return response()->json(
                    $this->formatDbSaveErrorResponse($e, $post->mobile_number, $post->email_address, 0)
                );
            }
        } else {
            if($post->profile_pic){
                $profilePic = csrf_token().time().'.'.$post->profile_pic->extension();  
                $post->profile_pic->move(public_path('profile_pic'), $profilePic);
            }else{
                $profilePic = $post->old_profile_pic;
            }

            try {
                $this->ensureUserSaveColumns();
                $updateFields = [
                    'parent_id'  => $post->parent_id,
                    'role_id'  => $post->role_id,
                    'scheme_id'  => $post->scheme_id,
                    'outlet_name'  => $post->outlet_name,
                    'first_name'  => $post->first_name,
                    'date_of_birth'  => $dob,
                    'mobile_number' => $post->mobile_number,
                    'email_address' => $post->email_address,
                    'login_type'  => $post->login_type,
                    'gender'  => $post->gender,
                    'flat_door_no'  => $post->flat_door_no ?: '',
                    'road_street'  => $post->road_street ?: '',
                    'pincode'  => preg_replace('/\D+/', '', (string) $post->pincode),
                    'area_locality'  => $post->area_locality,
                    'city'  => $post->city,
                    'state'  => $post->state,
                    'district'  => $post->district,
                    'minium_balance'  => $post->minium_balance,
                    'kyc_status'  => $post->kyc_status,
                    'bank_account_number'  => $bankAccountNumber,
                    'branch_name'  => $branchName,
                    'ifsc_code'  => $ifscCode,
                    'bank_account_type'  => $bankAccountType,
                    'ip_address'  => $ipAddress,
                    'callback_url'  => $post->callback_url,
                    'complaint_callback_url'  => $post->complaint_callback_url,
                    'profile_pic' => $profilePic,
                    'status' => $post->status,
                    'updated_at' => Carbon::now()
                ];

                if ((int) $post->role_id === 3) {
                    $existingApiUser = DB::table('users')->where('id', $editId)->first(['api_key']);
                    if ($existingApiUser && empty($existingApiUser->api_key)) {
                        $updateFields['api_key'] = Str::random(15) . rand(11111111, 9999999) . $editId . Str::random(15);
                    }
                }

                if ($post->filled('password')) {
                    $updateFields = array_merge(
                        $updateFields,
                        user_password_update_fields((string) $post->password)
                    );
                }

                $update = DB::table('users')->where('id', $post->edit_id)->update(filter_users_columns($updateFields));
                $message = "Update sucessfuly";
            } catch (\Exception $e) {
                Log::error('Update user failed: '.$e->getMessage());
                return response()->json(
                    $this->formatDbSaveErrorResponse($e, $post->mobile_number, $post->email_address, $editId)
                );
            }
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


    public function resetPassword(Request $post)
    {
        $rules = array(
            'id'  => 'required|numeric',
            'password' => 'required|string|min:8|confirmed',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules), [
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',  
                'message' => $error
            ));
        }

        try {
            ensure_user_visible_password_column();

            $g_pass = (string) $post->password;
            $user = DB::table('users')->where('id', $post->id)->first();
            if (!$user) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'User not found.',
                ]);
            }

            DB::table('users')->where('id', $post->id)->update(user_password_update_fields($g_pass));
            $user = DB::table('users')->where('id', $post->id)->first();

            try {
                ////Send Whatsapp Message Start
                $slug = 'forgot_password';
                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                if ($sms_tmp) {
                    $content = $sms_tmp->content;
                    $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
                    $content = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content);
                    $content = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content);
                    $content = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content);
                    $content = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content);
                    $content = str_replace('{PASSWORD}', '' . $g_pass . '', $content);
                    $content = str_replace('{PIN}', '' . $user->t_pin . '', $content);
                    if ($sms_tmp->status == 1) {
                        $msg_data = [
                            'mobile_number' => $user->mobile_number,
                            'content' => $content,
                            'template_id' => $sms_tmp->template_id,
                        ];
                        Common::sendWhatasappMsg($msg_data);
                    }
                }
                ////Send Whatsapp Message End
                ////Send Email Start
                $company = Common::getCompanyByHost();
                if (!empty($company) && $company->email_message == 1 && !empty($user->email_address)) {
                    $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                    if ($email_tmp) {
                        $content_email = $email_tmp->content;
                        $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                        $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                        $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                        $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                        $content_email = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content_email);
                        $content_email = str_replace('{PASSWORD}', '' . $g_pass . '', $content_email);
                        $content_email = str_replace('{PIN}', '' . $user->t_pin . '', $content_email);
                        Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject, $content_email));
                    }
                }
                ////Send Email End
            } catch (\Throwable $e) {
                // Password is already updated; notification failures should not block reset.
            }

            return response()->json(array(
                'type' => 'success',
                'message' => "Password reset successfully."
            ));
        } catch (\Throwable $e) {
            return response()->json(array(
                'type' => 'error',
                'message' => 'Password reset failed. Please try again.',
            ));
        }
    }


    public function resetPIN(Request $post)
    {
        $rules = array(
            'id'  => 'required|numeric',
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
        //$g_pass = Str::random(8);
        $pin = normalize_user_pin(random_int(0, 9999));
        $user = DB::table('users')->where('id', $post->id)->update(['t_pin' => $pin]);
        $user = DB::table('users')->where('id', $post->id)->first();
        if($user){
            ////Send Whatsapp Message Start
            $slug = 'forgot_pin';
            $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
            if ($sms_tmp) {
                $content = $sms_tmp->content;
                $content = str_replace('{NAME}', '' . $user->first_name . '', $content);
                $content = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content);
                $content = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content);
                $content = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content);
                $content = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content);
                $content = str_replace('{PIN}', '' . $pin . '', $content);
                if ($sms_tmp->status == 1) {
                    $msg_data = [
                        'mobile_number' => $user->mobile_number,
                        'content' => $content,
                        'template_id' => $sms_tmp->template_id,
                    ];
                    Common::sendWhatasappMsg($msg_data);
                }
            }
            ////Send Whatsapp Message End
            
            ////Send Email Start
            $company = Common::getCompanyByHost();
            if (!empty($company) && $company->email_message == 1) {
                $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
                if ($email_tmp) {
                    $content_email = $email_tmp->content;
                    $content_email = str_replace('{NAME}', '' . $user->first_name . '', $content_email);
                    $content_email = str_replace('{MIDDLE_NAME}', '' . $user->middle_name . '', $content_email);
                    $content_email = str_replace('{LAST_NAME}', '' . $user->last_name . '', $content_email);
                    $content_email = str_replace('{OUTLET_NAME}', '' . $user->outlet_name . '', $content_email);
                    $content_email = str_replace('{MOBILE}', '' . $user->mobile_number . '', $content_email);
                    $content_email = str_replace('{PIN}', '' . $pin . '', $content_email);
                    Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject, $content_email));
                }
            }
            ////Send Email End
            return response()->json(array(
                'type' => 'success',  
                'message' => "PIN Reset Successfuly."
            ));
        }else{
            return response()->json(array(
                'type' => 'error',  
                'message' => "Something went wrong."
            ));
        }
        

    }


    public function fundUpdate(Request $post)
    {
        $rules = array(
            'id'  => 'required|numeric',
            'type'  => 'required|in:Transfer,Reverse',
            'amount' => 'required|numeric|gt:0',
            'remark' => 'required|max:50|string',
            't_pin' => 'required|digits:4',
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
        if (!$user) {
            return response()->json([
                'type' => 'error',
                'message' => 'User not found.',
            ]);
        }
        if (! verify_user_pin($user->t_pin ?? '', $post->t_pin)) {
            return response()->json([
                'type' => 'error',
                'message' => 'Invalid PIN.',
            ]);
        }
        $isAdmin = (int) ($user->role_id ?? 0) === 1;

        if($post->type == "Transfer"){
            if (!$isAdmin && (float) $user->wallet_balance < (float) $post->amount){
                return response()->json(array(
                    'type' => 'error',
                    'message' => 'Insufficient wallet balance. Available: ₹ ' . number_format((float) $user->wallet_balance, 2),
                ));
            }
            DB::beginTransaction();

            try {
                $user = DB::table('users')->where('id', Session::get('user_id'))->first();
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
                    'closing_balance' => $isAdmin ? $user->wallet_balance : $user->wallet_balance - $post->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                if (!$isAdmin) {
                    DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance - $post->amount]);
                }
                
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
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('messages')) {
                        $slug = 'fund_receive';
                        $type = 'Credit';
                        $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                        if ($sms_tmp) {
                            $content = $sms_tmp->content;
                            $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                            $content = str_replace('{TYPE}', '' . $type . '', $content);
                            $content = str_replace('{AMOUNT}', '' . $post->amount . '', $content);
                            $content = str_replace('{BY}', '' . $BY->first_name . '', $content);
                            $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                            if ($sms_tmp->status == 1) {
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
                        }
                    }
                } catch (\Throwable $smsError) {
                    // Fund transfer must not fail because SMS queue is unavailable.
                    \Log::warning('fund transfer sms skipped: '.$smsError->getMessage());
                }
                //////Send Sms By Cron Job End
                DB::commit();
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Fund Transfer Successfully"
                ));
            } catch (\Exception $e) {
                DB::rollback();
                \Log::error('fund transfer failed: '.$e->getMessage());
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something Went Wrong."
                ));
            }
        }else{
            $targetUser = DB::table('users')->where('id', $post->id)->first();
            if (!$targetUser) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'User not found.',
                ]);
            }
            if ((float) $targetUser->wallet_balance < (float) $post->amount) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'User has insufficient wallet balance. Available: ₹ ' . number_format((float) $targetUser->wallet_balance, 2),
                ]);
            }
            ///Fund Debit User Reports
            DB::beginTransaction();
            try {
                $user = $targetUser;
                $by = $user->outlet_name;
                ///Report by first debiter by
                $order_id = "FND".rand(111111111,999999999);
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => '0',
                    'debit_user_id' => Session::get('user_id'),
                    'amount' => $post->amount,
                    'total_amount' => $post->amount,
                    'fund_type' => "Debit",
                    'transaction_type' => "Reverse Money",
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
                $BY = DB::table('users')->where('id', Session::get('user_id'))->first(['id','first_name','mobile_number']);
                $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance - $post->amount]);
                $CURRENT_BALANCE = DB::table('users')->where('id', $user_data->id)->first();
                //////Send Sms By Cron Job Start
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('messages')) {
                        $slug = 'fund_reverse';
                        $type = 'Debit';
                        $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
                        if ($sms_tmp) {
                            $content = $sms_tmp->content;
                            $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                            $content = str_replace('{TYPE}', '' . $type . '', $content);
                            $content = str_replace('{AMOUNT}', '' . $post->amount . '', $content);
                            $content = str_replace('{BY}', '' . $BY->first_name . '', $content);
                            $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                            if ($sms_tmp->status == 1) {
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
                        }
                    }
                } catch (\Throwable $smsError) {
                    \Log::warning('fund reverse sms skipped: '.$smsError->getMessage());
                }
                //////Send Sms By Cron Job End
                ///Report by second reciver by
                $user = DB::table('users')->where('id', Session::get('user_id'))->first();
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => $post->id,
                    'debit_user_id' => '0',
                    'amount' => $post->amount,
                    'total_amount' => $post->amount,
                    'fund_type' => "Credit",
                    'transaction_type' => "Money Reverse",
                    'remark' => $post->remark,
                    'order_id' => $order_id,
                    'status' => "Success",
                    'opening_balance' => $user->wallet_balance,
                    'closing_balance' => $user->wallet_balance + $post->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()

                ]);
                $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance + $post->amount]);
                DB::commit();
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Fund Reverse Successfully"
                ));
            } catch (\Exception $e) {
                DB::rollback();
                \Log::error('fund reverse failed: '.$e->getMessage());
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something Went Wrong."
                ));
            }
        }
    }

    /**
     * Lookup Indian PIN code → area, city, state, district
     */
    public function lookupPincode(Request $post)
    {
        $pin = preg_replace('/\D+/', '', (string) $post->input('pincode', ''));
        if (strlen($pin) !== 6) {
            return response()->json([
                'type' => 'error',
                'message' => 'Enter a valid 6-digit PIN code.',
            ]);
        }

        try {
            $url = 'https://api.postalpincode.in/pincode/' . $pin;
            $json = null;

            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                ]);
                $raw = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);
                if ($raw === false) {
                    return response()->json([
                        'type' => 'error',
                        'message' => 'PIN lookup failed: ' . ($err ?: 'network error'),
                    ]);
                }
                $json = json_decode($raw, true);
            } else {
                $raw = @file_get_contents($url);
                $json = $raw ? json_decode($raw, true) : null;
            }

            if (!is_array($json) || empty($json[0]) || ($json[0]['Status'] ?? '') !== 'Success') {
                return response()->json([
                    'type' => 'error',
                    'message' => $json[0]['Message'] ?? 'No records found for this PIN.',
                ]);
            }

            $offices = $json[0]['PostOffice'] ?? [];
            if (!is_array($offices) || !count($offices)) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'No post offices found for this PIN.',
                ]);
            }

            // Prefer a Delivery branch if available
            $primary = $offices[0];
            foreach ($offices as $office) {
                if (($office['DeliveryStatus'] ?? '') === 'Delivery') {
                    $primary = $office;
                    break;
                }
            }

            $localities = [];
            foreach ($offices as $office) {
                $name = trim((string) ($office['Name'] ?? ''));
                if ($name !== '' && !in_array($name, $localities, true)) {
                    $localities[] = $name;
                }
            }

            $district = trim((string) ($primary['District'] ?? ''));
            $state = trim((string) ($primary['State'] ?? ''));
            $block = trim((string) ($primary['Block'] ?? ''));
            $city = ($block !== '' && strtoupper($block) !== 'NA') ? $block : $district;

            return response()->json([
                'type' => 'success',
                'message' => 'PIN details loaded',
                'data' => [
                    'pincode' => $pin,
                    'area_locality' => trim((string) ($primary['Name'] ?? '')),
                    'city' => $city,
                    'state' => $state,
                    'district' => $district,
                    'localities' => $localities,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to fetch PIN details. Try again.',
            ]);
        }
    }

    protected function userPublicSummary($user)
    {
        if (!$user) {
            return null;
        }

        $userCode = Common::buildUserCode((int) $user->role_id, Common::getUserRoleSequence((int) $user->id, (int) $user->role_id));
        $name = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));

        return [
            'id' => (int) $user->id,
            'user_code' => $userCode,
            'outlet_name' => $user->outlet_name ?? '',
            'name' => $name !== '' ? $name : ($user->outlet_name ?? '-'),
            'mobile_number' => $user->mobile_number ?? '',
            'email_address' => $user->email_address ?? '',
            'status' => (string) ($user->status ?? ''),
            'deleted' => (int) ($user->deleted_at ?? 0) === 1,
        ];
    }

    protected function duplicateUserResponse(string $field, $value, int $excludeId = 0)
    {
        $query = DB::table('users')->where($field, $value);
        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->first();
        if (!$existing) {
            return null;
        }

        $summary = $this->userPublicSummary($existing);
        $label = $field === 'mobile_number' ? 'mobile number' : 'email address';
        $message = 'This ' . $label . ' is already registered to ' . $summary['outlet_name']
            . ' (' . $summary['user_code'] . ') - ' . $summary['name']
            . ' - ' . $summary['mobile_number'] . '.';

        if ($summary['deleted']) {
            $message .= ' This user is marked as deleted.';
        }

        return response()->json([
            'type' => 'error',
            'message' => $message,
            'existing_user' => $summary,
            'duplicate_field' => $field,
        ]);
    }

    protected function formatDbSaveErrorResponse(\Throwable $e, $mobileNumber = null, $emailAddress = null, int $excludeId = 0)
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'users_mobile_unique') || (str_contains($msg, 'mobile_number') && str_contains($msg, 'Duplicate entry'))) {
            $duplicateResponse = $this->duplicateUserResponse('mobile_number', $mobileNumber, $excludeId);
            if ($duplicateResponse) {
                return $duplicateResponse->getData(true);
            }
            return [
                'type' => 'error',
                'message' => 'This mobile number is already registered.',
            ];
        }
        if (str_contains($msg, 'users_email_unique') || (str_contains($msg, 'email_address') && str_contains($msg, 'Duplicate entry'))) {
            $duplicateResponse = $this->duplicateUserResponse('email_address', $emailAddress, $excludeId);
            if ($duplicateResponse) {
                return $duplicateResponse->getData(true);
            }
            return [
                'type' => 'error',
                'message' => 'This email address is already registered.',
            ];
        }
        if (str_contains($msg, 'Duplicate entry')) {
            return [
                'type' => 'error',
                'message' => 'Duplicate record found. Please use a different mobile number or email.',
            ];
        }

        if (preg_match("/Unknown column '([^']+)'/", $msg, $m)) {
            return [
                'type' => 'error',
                'message' => 'Unable to save because a required database field is missing: '.$m[1].'.',
            ];
        }

        if (preg_match("/Incorrect (?:date|datetime) value: '([^']*)' for column [`']?date_of_birth/i", $msg, $m)
            || preg_match("/Incorrect (?:date|datetime) value:.*[`']date_of_birth[`']/i", $msg)) {
            return [
                'type' => 'error',
                'message' => 'Please enter a valid Date of Birth (DD-MM-YYYY).',
            ];
        }

        if (preg_match("/Field '([^']+)' doesn't have a default value/i", $msg, $m)) {
            return [
                'type' => 'error',
                'message' => 'Unable to save user. Missing value for '.$m[1].'.',
            ];
        }

        if (str_contains($msg, 'Incorrect integer value') || str_contains($msg, 'cannot be null')) {
            return [
                'type' => 'error',
                'message' => 'Some required details are missing or invalid. Please check PIN code, KYC, and bank fields.',
            ];
        }

        return [
            'type' => 'error',
            'message' => 'Something went wrong while saving. Please try again.',
        ];
    }

    protected function normalizeDateOfBirth($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(['/', '.', '–', '—'], '-', $value);

        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function ensureUserSaveColumns(): void
    {
        ensure_user_visible_password_column();

        $columns = [
            'complaint_callback_url' => 'VARCHAR(255) NULL',
            'callback_url' => 'VARCHAR(255) NULL',
            'api_key' => 'VARCHAR(191) NULL',
            'pincode' => 'VARCHAR(10) NULL',
            'login_key' => 'VARCHAR(191) NULL',
            'otp_limit' => 'INT NULL DEFAULT 0',
            'wallet_balance' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'deleted_at' => 'TINYINT NOT NULL DEFAULT 0',
        ];

        foreach ($columns as $column => $definition) {
            try {
                if (! Schema::hasColumn('users', $column)) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$column}` {$definition}");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    protected function sendCreatedUserCredentials(Request $post, string $password, string $pin): bool
    {
        if (! $post->boolean('auto_send')) {
            return false;
        }

        $user_data = DB::table('users')->where('mobile_number', $post->mobile_number)->first();
        if (! $user_data) {
            return false;
        }

        $sent = false;
        $slug = 'create_user';
        $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);
        if ($sms_tmp) {
            $content = $sms_tmp->content;
            $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
            $content = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content);
            $content = str_replace('{PASSWORD}', '' . $password . '', $content);
            $content = str_replace('{PIN}', '' . $pin . '', $content);
            if ((int) $sms_tmp->status === 1) {
                Common::sendWhatasappMsg([
                    'mobile_number' => $post->mobile_number,
                    'content' => $content,
                    'template_id' => $sms_tmp->template_id,
                ]);
                $sent = true;
            }
        }

        $company = Common::getCompanyByHost();
        if (!empty($company) && (int) $company->email_message === 1) {
            $email_tmp = DB::table('email_templates')->where('slug', $slug)->first(['subject','content','status']);
            if ($email_tmp && !empty($user_data->email_address)) {
                $content_email = $email_tmp->content;
                $content_email = str_replace('{NAME}', '' . $user_data->first_name . '', $content_email);
                $content_email = str_replace('{MOBILE}', '' . $user_data->mobile_number . '', $content_email);
                $content_email = str_replace('{PASSWORD}', '' . $password . '', $content_email);
                $content_email = str_replace('{PIN}', '' . $pin . '', $content_email);
                Mail::to(strtolower($user_data->email_address))->queue(new SendEmail($email_tmp->subject, $content_email));
                $sent = true;
            }
        }

        return $sent;
    }
}
