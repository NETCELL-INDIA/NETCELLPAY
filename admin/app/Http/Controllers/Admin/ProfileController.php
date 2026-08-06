<?php

namespace App\Http\Controllers\Admin;

use App\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;
class ProfileController extends Controller
{

    public function Logout(Request $post)
    {
        $post->session()->flush();
        return redirect('admin/login');
    }

    public function myProfile(Request $post)
    {
        return view('admin.profile.my-profile');
    }

    public function changePassword(Request $post)
    {
        return view('admin.profile.change-password');
    }

    public function loginHistory(Request $post)
    {
        $login_history = $this->fetchLoginHistory(50);
        return view('admin.profile.login-history', compact('login_history'));
    }

    protected function fetchLoginHistory($limit = 50)
    {
        return DB::table('login_histories as lh')
            ->leftJoin('users as u', 'u.id', '=', 'lh.user_id')
            ->where('lh.user_id', Session::get('user_id'))
            ->orderByDesc('lh.id')
            ->take($limit)
            ->get([
                'lh.id',
                'lh.user_id',
                'lh.ip_address',
                'lh.login_path',
                'lh.user_agent',
                'lh.browser',
                'lh.platform',
                'lh.device_type',
                'lh.device',
                'lh.created_at',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.outlet_name',
                'u.mobile_number',
            ])
            ->map(function ($row) {
                $name = trim(implode(' ', array_filter([
                    $row->first_name ?? '',
                    $row->middle_name ?? '',
                    $row->last_name ?? '',
                ])));
                if ($name === '') {
                    $name = $row->outlet_name ?: ('User #' . ($row->user_id ?? ''));
                }
                return [
                    'id' => (int) ($row->id ?? 0),
                    'user_name' => $name,
                    'mobile_number' => $row->mobile_number ?? '',
                    'ip_address' => $row->ip_address ?? '',
                    'login_path' => $row->login_path ?? '',
                    'user_agent' => $row->user_agent ?? '',
                    'browser' => $row->browser ?? '',
                    'platform' => $row->platform ?? '',
                    'device_type' => $row->device_type ?? '',
                    'device' => $row->device ?? '',
                    'created_at' => $row->created_at ?? '',
                ];
            })
            ->values();
    }

    public function myProfileData(Request $post)
    {
        try {
            $user = DB::table('users')
                ->select(
                    'users.first_name',
                    'users.middle_name',
                    'users.last_name',
                    'users.outlet_name',
                    'users.date_of_birth',
                    'users.email_address',
                    'users.mobile_number',
                    'users.city',
                    'users.state',
                    'users.district',
                    'users.minium_balance',
                    'users.wallet_balance',
                    'users.profile_pic',
                    'users.kyc_status',
                    'users.callback_url',
                    'users.ip_address',
                    'roles.role_name'
                )
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', Session::get('user_id'))
                ->first();

            $login_history = $this->fetchLoginHistory(50);

            $company = null;
            try {
                $company = Common::getCompanyByHost();
            } catch (\Throwable $e) {
            }

            $announcements = '';
            try {
                $announcements = optional(DB::table('announcements')->where('id', 1)->first())->message ?? '';
            } catch (\Throwable $e) {
            }

            if ($user) {
                return [
                    'type' => 'success',
                    'message' => 'Fetch Successfully',
                    'data' => [
                        'user' => $user,
                        'login_history' => $login_history,
                        'company' => $company,
                        'announcements' => $announcements,
                    ],
                ];
            }

            return [
                'type' => 'error',
                'message' => 'Something went wrong!',
            ];
        } catch (\Throwable $e) {
            return [
                'type' => 'error',
                'message' => 'Failed to load profile data',
            ];
        }
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

        $user = DB::table('users')->where("id",Session::get('user_id'))->where("role_id",1)->first();
        if(Hash::check($post->current_password, $user->password)){
            $password = Hash::make($post->confirm_password);
            $user = DB::table('users')->where('id', $user->id)->update(['password' => $password]);
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

}
