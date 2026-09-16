<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use App\Services\MessageSettingService;
use Illuminate\Http\Request;

class MessageSettingController extends Controller
{
    public function index()
    {
        MessageSettingService::ensureTable();

        return view('admin.extras.message-settings', [
            'items' => MessageSettingService::all(),
            'emailGlobal' => MessageSettingService::emailGloballyEnabled(),
        ]);
    }

    public function save(Request $post)
    {
        MessageSettingService::ensureTable();
        $rows = $post->input('settings', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        MessageSettingService::saveSettings($rows, $post->boolean('email_global'));
        AdminAudit::log('settings', 'message_settings_save', [
            'remark' => 'Notification / message channel toggles updated',
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Notification settings saved.',
        ]);
    }
}
