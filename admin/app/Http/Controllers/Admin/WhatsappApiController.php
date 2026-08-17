<?php

namespace App\Http\Controllers\Admin;

use App\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsappApiController extends Controller
{
    public function index()
    {
        $this->ensureColumns();
        $company = DB::table('companies')->where('id', 1)->first([
            'whatsapp_api_method',
            'whatsapp_request_url',
        ]);

        return view('admin.extras.whatsapp-api', [
            'method' => $company->whatsapp_api_method ?? 'GET',
            'url' => $company->whatsapp_request_url ?? '',
        ]);
    }

    public function save(Request $post)
    {
        $this->ensureColumns();

        $validator = \Validator::make($post->all(), [
            'whatsapp_api_method' => 'required|in:GET,POST',
            'whatsapp_request_url' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        DB::table('companies')->where('id', 1)->update([
            'whatsapp_api_method' => $post->whatsapp_api_method,
            'whatsapp_request_url' => trim($post->whatsapp_request_url),
            'updated_at' => now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'WhatsApp API saved successfully']);
    }

    public function test(Request $post)
    {
        $this->ensureColumns();

        $mobile = preg_replace('/\D+/', '', (string) $post->mobile);
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        }
        if (strlen($mobile) !== 10) {
            return response()->json(['type' => 'error', 'message' => 'Enter a valid 10-digit mobile number']);
        }

        $message = trim((string) $post->message);
        if ($message === '') {
            $message = 'Netcell Pay WhatsApp API test message';
        }

        $company = DB::table('companies')->where('id', 1)->first([
            'whatsapp_api_method',
            'whatsapp_request_url',
        ]);
        $url = trim((string) ($company->whatsapp_request_url ?? ''));
        if ($url === '' || $url === '0') {
            return response()->json(['type' => 'error', 'message' => 'WhatsApp Request URL is not configured']);
        }

        $url = str_replace('{MOB}', $mobile, $url);
        $url = str_replace('{MSG}', urlencode($message), $url);
        $url = str_replace('{TMP_ID}', trim((string) ($post->tmp_id ?? '')), $url);
        $url = str_replace('{TMPID}', trim((string) ($post->tmp_id ?? '')), $url);

        $method = $company->whatsapp_api_method ?: 'GET';
        $requestId = 'WTEST'.date('YmdHis').rand(1111, 9999);
        $result = Common::curl($url, $method, '', [], 'yes', 'WHATSAPP_URL', $requestId);

        $code = (int) ($result['code'] ?? 0);
        $err = (string) ($result['error'] ?? '');
        $response = (string) ($result['response'] ?? '');
        if (strlen($response) > 800) {
            $response = substr($response, 0, 800).'...';
        }

        if ($err !== '') {
            return response()->json([
                'type' => 'error',
                'message' => 'WhatsApp test failed: '.$err,
                'http_code' => $code,
                'response' => $response,
            ]);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Test message sent to '.$mobile,
            'http_code' => $code,
            'response' => $response !== '' ? $response : 'No response body',
        ]);
    }

    private function ensureColumns(): void
    {
        if (!Schema::hasColumn('companies', 'whatsapp_api_method')) {
            Schema::table('companies', function ($table) {
                $table->string('whatsapp_api_method', 10)->nullable();
            });
        }
        if (!Schema::hasColumn('companies', 'whatsapp_request_url')) {
            Schema::table('companies', function ($table) {
                $table->text('whatsapp_request_url')->nullable();
            });
        }
    }
}
