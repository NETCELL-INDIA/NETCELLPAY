<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Session;
class ProfileController extends Controller
{
    public function myProfile(Request $post)
    {
        
        return view('users.profile.my-profile');
    }

    public function myProfileData(Request $post)
    {

        $user = DB::table('users')
        ->select(
            'first_name',
            'middle_name',
            'last_name',
            'outlet_name',
            'date_of_birth',
            'email_address',
            'mobile_number',
            'city',
            'state',
            'district',
            'minium_balance',
            'wallet_balance',
            'profile_pic',
            'kyc_status',
            'minium_balance',
            'callback_url',
            'ip_address',
            'role_name',
            'api_key',
        )
        ->join('roles', 'users.role_id', '=', 'roles.id')
        ->where('users.id',Session::get('user_id'))
        ->first();
        $historyColumns = ['ip_address', 'login_path', 'created_at'];
        try {
            \helpers::ensureLoginHistoryGeoColumns();
            if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', 'latitude')) {
                $historyColumns[] = 'latitude';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', 'longitude')) {
                $historyColumns[] = 'longitude';
            }
        } catch (\Throwable $e) {
        }

        $login_history = DB::table('login_histories')
        ->where("user_id",Session::get('user_id'))
        
        ->take(5)
        ->orderBy('id', 'DESC')
        ->get($historyColumns)
        ->map(function ($row) {
            $row->maps_url = \helpers::googleMapsUrl($row->latitude ?? null, $row->longitude ?? null);
            return $row;
        });

        $company = user_company();

        $announcementRow = DB::table('announcements')
        ->select('message')
        ->where('id', 1)
        ->first();
        $announcements = $announcementRow->message ?? '';

        if ($user) {
            // MySQL decimal columns are returned as strings; expose a numeric wallet for frontend.
            $user->wallet_balance = (float) ($user->wallet_balance ?? 0);
            $user->minium_balance = (float) ($user->minium_balance ?? 0);
            $data['type'] = 'success';
            $data['message'] = "Fatch Sucessfuly";
            $data['data']['user'] = $user;
            $data['data']['login_history'] = $login_history;
            $data['data']['company'] = $company;
            $data['data']['announcements'] = $announcements;
            $data['data']['balance_alert_below'] = (float) \App\Services\SystemSettingService::get('balance_alert_below', 500);
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }

        return response()->json($data);
    }


    public function myProfilePasswordChange(Request $post) {
        $rules = array(
            'current_password' => 'required|min:8',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password|min:8',
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

        $user = DB::table('users')->where("id",Session::get('user_id'))->whereNotIn("role_id",[1,2])->first();
        if(\helpers::verifyUserPassword($post->current_password, $user)){
            $user = DB::table('users')->where('id', $user->id)->update(
                \helpers::userPasswordUpdateFields($post->confirm_password)
            );
            if($user){
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Password Change Successfuly."
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Current password do not match.";
        }
        return $data;
    }

    public function myProfilePinChange(Request $post) {
        $rules = array(
            'current_pin' => 'required|numeric|digits:4',
            'new_pin' => 'required|numeric|digits:4',
            'confirm_pin' => 'required|same:new_pin|numeric|digits:4',
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

        $user = DB::table('users')->where("id",Session::get('user_id'))->whereNotIn("role_id",[1,2])->first();
        if(\helpers::verifyUserPin($user->t_pin, $post->current_pin)){
            $user = DB::table('users')->where('id', $user->id)->update(['t_pin' => \helpers::normalizeUserPin($post->confirm_pin)]);
            if($user){
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Pin Change Successfuly."
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "Current pin do not match.";
        }
        return $data;
    }

    


    public function myProfileCommission(){
        
        $data['services'] = DB::table('services')->where("status",1)->where("deleted_at",0)->get();
        return view('users.profile.my-commission',$data);
    }

    public function myProfileCommissionList(Request $post){
        \helpers::ensureApiPartnerCommissionColumns();
        $user = DB::table('users')->where("id",Session::get('user_id'))->whereNotIn("role_id",[1,2])->first();
        if (! $user) {
            echo '<h4 class="text-center text-secondary my-3">User not found</h4>';
            return;
        }
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

        if($post->service_id == 0){
            $post->service_id = ''; 
        }

        $start= ($page-1) * $limit;
        $total_row = DB::table('providers as p')
        ->leftjoin('scheme_commissions as sc','sc.provider_id','=','p.id',)
        ->leftjoin('services as s','p.service_id','=','s.id',)
        ->select('p.*', 'sc.*', 's.service_name', 'p.status as provider_status', 'p.id as provider_id')
        ->where('p.service_id', 'like', "%$post->service_id%")
        ->where("sc.scheme_id", $user->scheme_id)
        ->where("p.deleted_at", 0)->get();
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
        //echo "<pre>";print_r($table);die;
        $list = DB::table('providers as p')
        ->leftjoin('scheme_commissions as sc','sc.provider_id','=','p.id',)
        ->leftjoin('services as s','p.service_id','=','s.id',)
        ->select('p.*', 'sc.*', 's.service_name', 'p.status as provider_status', 'p.id as provider_id')
        //->where("p.service_id",$post->service_id)
        ->where('p.service_id', 'like', "%$post->service_id%")
        ->where("sc.scheme_id", $user->scheme_id)
        ->where("p.deleted_at", 0)
        ->offset($start)->limit($limit)->get();
        //echo "<pre>";print_r($list);die;
        $list_count = $list->count();
        $output = '';
        if(($list->count() > 0)){
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
            <thead>
              <tr>
                <th>ID</th>
                <th>Provider Details</th>
                <th>Provider Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Minimum To Maximum</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
            foreach ($list as $list) {
                [$amount_type, $amount_value] = \helpers::commissionFieldsForRole($list, $user->role_id);
                $down = (int) ($list->provider_down ?? 0);
                if ($down === 0 && \helpers::isProviderDownForUser($list->provider_id ?? $list->id, $user->id)) {
                    $down = 1;
                }
                $providerStatus = (int) ($list->provider_status ?? 1);
                if($providerStatus !== 1){
                    $bg = "secondary";
                    $st = "DEACTIVE";
                }else if($down == 0){
                    $bg = "success";
                    $st = "ONLINE";
                }else if($down == 1){
                    $bg = "danger";
                    $st = "OFFLINE";
                }else{
                    $bg = "warning";
                    $st = "OTHER";
                }
                $output .= '<tr>
                    <td>' . $i . '</td>
                    <td>' . $list->service_name . ' <br>' . $list->provider_name . '</td>
                    <td>' . $list->provider_id . '</td>
                    <td>' . ($amount_type !== '' ? e($amount_type) : '-') . '</td>
                    <td>' . ($amount_value !== '' ? e($amount_value) : '0') . '</td>
                    <td>' . $list->minium_amount . ' - ' . $list->maxium_amount . '</td>
                    <td>
                        <span class="badge rounded-pill text-bg-' . $bg . '">' . $st . '</span>
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
        }else{
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }

    public function myProfileGenerateKey(Request $post) {
        $user = DB::table('users')->where("id",Session::get('user_id'))->where("login_key",Session::get('login_key'))->whereNotIn("role_id",[1,2])->first();
        if($user){

            $api_key = Str::random(15).rand(11111111,9999999).Session::get('user_id').Str::random(15);
            $user = DB::table('users')->where('id', $user->id)->update(['api_key' => $api_key]);

            if($user){
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Api Key Ganerate Successfuly."
                ));
            }else{
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something went wrong."
                ));
            }
        }else{
            $data['type'] = 'error';
            $data['message'] = "user do not match.";
        }
        return $data;
    }
}
