<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComplaintThreadController extends Controller
{
    public function thread(Request $request)
    {
        $complaint = $this->ownedComplaint($request);
        if (! $complaint) {
            return $this->error('Complaint not found.');
        }
        $messages = DB::table('complaint_messages')
            ->where('complaint_id', $complaint->id)
            ->where('visibility', 'customer')
            ->orderBy('id')
            ->get(['id', 'sender_role', 'body', 'created_at']);

        return response()->json([
            'type' => 'success',
            'success' => true,
            'message' => 'Fetch Successfully',
            'data' => [
                'ticket' => $complaint->request_id,
                'status' => $complaint->status,
                'messages' => $messages,
            ],
        ]);
    }

    public function reply(Request $request)
    {
        $complaint = $this->ownedComplaint($request);
        if (! $complaint) {
            return $this->error('Complaint not found.');
        }
        if (in_array(strtoupper((string) $complaint->status), ['CLOSED', 'REJECTED'], true)) {
            return $this->error('This complaint is closed.');
        }
        $body = trim(strip_tags((string) $request->input('message')));
        if ($body === '' || mb_strlen($body) > 1000) {
            return $this->error('Message is required and must be under 1000 characters.');
        }
        $existing = DB::table('complaint_messages')
            ->where('complaint_id', $complaint->id)
            ->where('sender_role', 'customer')
            ->where('body', $body)
            ->where('created_at', '>=', Carbon::now()->subMinute())
            ->exists();
        if ($existing) {
            return $this->error('This reply was already received.');
        }
        DB::table('complaint_messages')->insert([
            'complaint_id' => $complaint->id,
            'sender_role' => 'customer',
            'sender_id' => (int) $complaint->user_id,
            'body' => $body,
            'visibility' => 'customer',
            'delivery_status' => null,
            'delivery_error' => null,
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'type' => 'success',
            'success' => true,
            'message' => 'Reply saved.',
            'data' => ['ticket' => $complaint->request_id],
        ]);
    }

    private function ownedComplaint(Request $request): ?object
    {
        if (! Schema::hasTable('complaints') || ! Schema::hasTable('complaint_messages')) {
            return null;
        }
        $id = (int) $request->input('complaint_id');
        $userId = (int) $request->input('user_id');
        if ($id < 1 || $userId < 1) {
            return null;
        }

        return DB::table('complaints')->where('id', $id)->where('user_id', $userId)->first();
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
