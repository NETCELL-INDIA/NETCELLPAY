<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsApiController extends Controller
{
    public function __construct()
    {
        SmsApiService::ensureTable();
    }

    public function index()
    {
        return view('admin.extras.sms-api-list');
    }

    public function fetchAll()
    {
        $list = SmsApiService::all();
        $output = '';

        if ($list->count() > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>#</th>
                <th>API Name</th>
                <th>Method</th>
                <th>Request URL</th>
                <th>Username</th>
                <th>Sender ID</th>
                <th>Primary</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';

            $i = 1;
            foreach ($list as $row) {
                $status = (int) $row->status === 1
                    ? '<span class="badge rounded-pill text-bg-success">Active</span>'
                    : '<span class="badge rounded-pill text-bg-danger">Inactive</span>';
                $primary = (int) $row->is_primary === 1
                    ? '<span class="badge rounded-pill text-bg-primary">Primary</span>'
                    : '<a href="javascript:void(0)" class="badge text-bg-secondary setPrimary" id="' . $row->id . '">Set Primary</a>';
                $url = htmlspecialchars((string) $row->request_url, ENT_QUOTES, 'UTF-8');

                $output .= '<tr>
                <td>' . $i . '</td>
                <td>' . htmlspecialchars($row->api_name, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($row->api_method, ENT_QUOTES, 'UTF-8') . '</td>
                <td style="max-width:280px;word-break:break-all;">' . $url . '</td>
                <td>' . htmlspecialchars((string) ($row->api_username ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars((string) ($row->sender_id ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . $primary . '</td>
                <td>' . $status . '</td>
                <td>
                    <a id="' . $row->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="' . $row->id . '" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
                </td>
              </tr>';
                $i++;
            }

            $output .= '</tbody></table>';
            echo $output;
        } else {
            echo '<h4 class="text-center text-secondary my-3">No SMS API found. Click Create New to add one.</h4>';
        }
    }

    public function getData(Request $post)
    {
        $get = DB::table('sms_apis')->where('id', $post->id)->first();
        if ($get) {
            return [
                'type' => 'success',
                'message' => 'Get successfully',
                'data' => $get,
            ];
        }

        return ['type' => 'error', 'message' => 'Record not found'];
    }

    public function updateData(Request $post)
    {
        $rules = [
            'api_name' => 'required|string|max:120',
            'api_method' => 'required|in:GET,POST',
            'request_url' => 'required|string',
            'status' => 'required|numeric|in:0,1',
        ];

        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $payload = [
            'api_name' => trim($post->api_name),
            'api_method' => $post->api_method,
            'request_url' => trim($post->request_url),
            'api_username' => trim((string) $post->api_username) ?: null,
            'api_password' => trim((string) $post->api_password) ?: null,
            'sender_id' => trim((string) $post->sender_id) ?: null,
            'status' => (int) $post->status,
            'updated_at' => Carbon::now(),
        ];

        if ((int) $post->edit_id === 0) {
            $count = DB::table('sms_apis')->count();
            $payload['is_primary'] = $count === 0 ? 1 : 0;
            $payload['sort_order'] = $count + 1;
            $payload['created_at'] = Carbon::now();
            $ok = DB::table('sms_apis')->insert($payload);
        } else {
            $ok = DB::table('sms_apis')->where('id', (int) $post->edit_id)->update($payload);
        }

        if ($ok) {
            return ['type' => 'success', 'message' => 'SMS API saved successfully'];
        }

        return ['type' => 'error', 'message' => 'Unable to save SMS API'];
    }

    public function deleteData(Request $post)
    {
        $row = DB::table('sms_apis')->where('id', (int) $post->id)->first();
        if (!$row) {
            return ['type' => 'error', 'message' => 'Record not found'];
        }

        DB::table('sms_apis')->where('id', $row->id)->delete();

        if ((int) $row->is_primary === 1) {
            $next = DB::table('sms_apis')->where('status', 1)->orderBy('sort_order')->orderBy('id')->first();
            if ($next) {
                SmsApiService::setPrimary((int) $next->id);
            }
        }

        return ['type' => 'success', 'message' => 'SMS API deleted successfully'];
    }

    public function setPrimary(Request $post)
    {
        $row = DB::table('sms_apis')->where('id', (int) $post->id)->first();
        if (!$row) {
            return ['type' => 'error', 'message' => 'Record not found'];
        }

        SmsApiService::setPrimary((int) $row->id);

        return ['type' => 'success', 'message' => 'Primary SMS API updated'];
    }
}
