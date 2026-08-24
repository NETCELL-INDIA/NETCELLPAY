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

    public function pinReset(Request $post)
    {
        return view('admin.profile.pin-reset');
    }

    public function pinResetChange(Request $post) {
        $rules = array(
            'current_password' => 'required|min:8',
            'new_pin' => 'required|digits:4',
            'confirm_pin' => 'required|same:new_pin|digits:4',
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
        if (! $user) {
            return response()->json([
                'type' => 'error',
                'message' => 'User not found.',
            ]);
        }

        if (! Hash::check($post->current_password, $user->password)) {
            return response()->json([
                'type' => 'error',
                'message' => 'Current password do not match.',
            ]);
        }

        $updated = DB::table('users')->where('id', $user->id)->update([
            't_pin' => normalize_user_pin($post->new_pin),
            'updated_at' => Carbon::now(),
        ]);

        if ($updated) {
            return response()->json([
                'type' => 'success',
                'message' => 'PIN Reset Successfuly.',
            ]);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Something went wrong.',
        ]);
    }

    public function loginHistory(Request $post)
    {
        $login_history = Common::fetchLoginHistoryForUser((int) Session::get('user_id'), 50)->values()->all();

        return view('admin.profile.login-history', compact('login_history'));
    }

    protected function fetchLoginHistory($limit = 50)
    {
        return Common::fetchLoginHistoryForUser((int) Session::get('user_id'), $limit);
    }

    public function myProfileData(Request $post)
    {
        try {
            $userId = (int) Session::get('user_id');
            $user = Common::fetchProfileUser($userId);
            $login_history = Common::fetchLoginHistoryForUser($userId, 50)->values()->all();

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
                if (isset($user->wallet_balance)) {
                    $user->wallet_balance = (float) $user->wallet_balance;
                }
                if (isset($user->minium_balance)) {
                    $user->minium_balance = (float) $user->minium_balance;
                }

                return response()->json([
                    'type' => 'success',
                    'message' => 'Fetch Successfully',
                    'data' => [
                        'user' => $user,
                        'login_history' => $login_history,
                        'company' => $company,
                        'announcements' => $announcements,
                    ],
                ]);
            }

            return response()->json([
                'type' => 'error',
                'message' => 'Profile not found.',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('myProfileData failed: '.$e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'Failed to load profile data',
            ]);
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

        $user = DB::table('users')->where('id', Session::get('user_id'))->first();
        if (! $user) {
            return response()->json([
                'type' => 'error',
                'message' => 'User not found.',
            ]);
        }

        if (! Hash::check($post->current_password, $user->password)) {
            return response()->json([
                'type' => 'error',
                'message' => 'Current password do not match.',
            ]);
        }

        $updated = DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($post->confirm_password),
            'updated_at' => Carbon::now(),
        ]);

        if ($updated) {
            return response()->json([
                'type' => 'success',
                'message' => 'Password Change Successfuly.',
            ]);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Something went wrong.',
        ]);
    }

}
