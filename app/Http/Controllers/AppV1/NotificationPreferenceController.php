<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use App\Services\NotificationConsentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request, NotificationConsentService $consent)
    {
        $userId = $this->userIdFromLoginKey($request);
        if ($userId <= 0 || ! $consent->ready()) {
            return $this->error($userId <= 0 ? 'invaild login details' : 'Notification preferences are not available.');
        }

        return response()->json([
            'type' => 'success',
            'success' => true,
            'message' => 'Fetch Successfully',
            'data' => $consent->current($userId),
        ]);
    }

    public function update(Request $request, NotificationConsentService $consent)
    {
        $userId = $this->userIdFromLoginKey($request);
        if ($userId <= 0 || ! $consent->ready()) {
            return $this->error($userId <= 0 ? 'invaild login details' : 'Notification preferences are not available.');
        }

        $raw = $request->input('promotional_push_consent');
        if (! is_bool($raw) && $raw !== 0 && $raw !== 1) {
            return $this->error('promotional_push_consent must be true or false.');
        }

        $saved = $consent->update($userId, $raw === true || $raw === 1, 'app');

        return response()->json([
            'type' => 'success',
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => $saved,
        ]);
    }

    private function userIdFromLoginKey(Request $request): int
    {
        $loginKey = trim((string) $request->input('login_key'));
        if ($loginKey === '') {
            return 0;
        }

        return (int) DB::table('users')
            ->where('login_key', $loginKey)
            ->whereNotIn('role_id', [1, 2])
            ->value('id');
    }

    private function error(string $message)
    {
        return response()->json([
            'type' => 'error',
            'success' => false,
            'message' => $message,
        ]);
    }
}
