<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountDeletionAdminController extends Controller
{
    public function index()
    {
        $rows = collect();
        if (Schema::hasTable('account_deletion_requests')) {
            $rows = DB::table('account_deletion_requests')->orderByDesc('id')->limit(200)->get();
        }

        return view('admin.users.account-deletions', compact('rows'));
    }

    public function update(Request $request)
    {
        if (!Schema::hasTable('account_deletion_requests')) {
            return response()->json(['type' => 'error', 'message' => 'No deletion requests yet.']);
        }

        $status = (string) $request->input('status');
        $id = (int) $request->input('id');
        $note = mb_substr(trim((string) $request->input('admin_note', '')), 0, 500);

        if ($status === 'Completed') {
            $result = $this->completeRequest($id, $note);

            return response()->json([
                'type' => $result['ok'] ? 'success' : 'error',
                'message' => $result['message'],
            ]);
        }

        if (!in_array($status, ['Rejected', 'Approved'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Choose Rejected, Approved, or Completed.']);
        }

        $updated = DB::table('account_deletion_requests')
            ->where('id', $id)
            ->whereIn('status', ['Pending Review', 'Approved', 'Processing'])
            ->update([
                'status' => $status,
                'admin_note' => $note !== '' ? $note : null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'type' => $updated ? 'success' : 'error',
            'message' => $updated ? 'Request updated.' : 'This request can no longer be changed.',
        ]);
    }

    /**
     * Remove credentials and profile data. Recharge, wallet, and complaint rows stay for accounting.
     */
    private function completeRequest(int $requestId, string $adminNote): array
    {
        $row = DB::table('account_deletion_requests')->where('id', $requestId)->first();
        if (!$row || !in_array($row->status, ['Pending Review', 'Approved', 'Processing'], true)) {
            return ['ok' => false, 'message' => 'This request cannot be completed.'];
        }

        $user = DB::table('users')->where('id', $row->user_id)->first();
        if (!$user) {
            return ['ok' => false, 'message' => 'User record was not found.'];
        }

        DB::beginTransaction();
        try {
            $payload = [
                'first_name' => 'Deleted',
                'middle_name' => '',
                'last_name' => 'User',
                'outlet_name' => 'Deleted account',
                'email_address' => 'deleted-'.$user->id.'@invalid.local',
                'password' => Hash::make(Str::random(40)),
                'status' => 0,
                'deleted_at' => 1,
                'api_key' => null,
                'otp' => null,
                'email_otp' => null,
                'callback_url' => null,
                'updated_at' => Carbon::now(),
            ];

            foreach ([
                'visible_password' => null,
                't_pin' => null,
                'profile_pic' => null,
                'ip_address' => null,
                'fcm_token' => null,
                'device_token' => null,
            ] as $column => $value) {
                if (Schema::hasColumn('users', $column)) {
                    $payload[$column] = $value;
                }
            }

            DB::table('users')->where('id', $user->id)->update($payload);

            if (Schema::hasTable('login_histories')) {
                DB::table('login_histories')->where('user_id', $user->id)->delete();
            }

            DB::table('account_deletion_requests')->where('id', $row->id)->update([
                'status' => 'Completed',
                'admin_note' => $adminNote !== '' ? $adminNote : null,
                'completed_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'otp_hash' => null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['ok' => false, 'message' => 'Deletion could not be completed. No data was changed.'];
        }

        return ['ok' => true, 'message' => 'Account credentials and profile data were removed. Recharge and wallet records were retained.'];
    }
}
