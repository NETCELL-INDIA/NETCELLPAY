<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ApiSettingsController extends Controller
{
    private function denyForDistributionRoles()
    {
        if (in_array((int) Session::get('role_id'), [4, 5, 6], true)) {
            abort(403, 'You are not allowed to access API Configuration.');
        }
    }

    private function currentUser()
    {
        return DB::table('users')
            ->where('id', Session::get('user_id'))
            ->where('login_key', Session::get('login_key'))
            ->whereNotIn('role_id', [1, 2])
            ->first();
    }

    public function index()
    {
        $this->denyForDistributionRoles();

        $user = $this->currentUser();
        abort_unless($user, 403);

        $company = user_company();
        $baseUrl = rtrim((string) request()->getSchemeAndHttpHost(), '/') . '/api/ApiPartner/V1';
        $providers = DB::table('providers as p')
            ->leftJoin('services as s', 's.id', '=', 'p.service_id')
            ->where('p.status', 1)
            ->where(function ($q) {
                $q->where('p.deleted_at', 0)->orWhereNull('p.deleted_at');
            })
            ->whereIn('p.service_id', [1, 2, 4])
            ->orderBy('s.service_name')
            ->orderBy('p.provider_name')
            ->get(['p.id', 'p.provider_name', 'p.service_id', 's.service_name']);

        return view('users.api-settings.index', [
            'company' => $company,
            'user' => $user,
            'baseUrl' => $baseUrl,
            'providers' => $providers,
        ]);
    }

    public function store(Request $request)
    {
        $this->denyForDistributionRoles();

        $user = $this->currentUser();
        abort_unless($user, 403);

        $data = [
            'callback_url' => trim((string) $request->input('callback_url', '')),
            'complaint_callback_url' => trim((string) $request->input('complaint_callback_url', '')),
            'ip_address' => trim((string) $request->input('ip_address', '')),
            'updated_at' => now(),
        ];

        if (in_array($data['ip_address'], ['1.1.1.1', '0.0.0.0'], true)) {
            $data['ip_address'] = '';
        }

        DB::table('users')->where('id', $user->id)->update($data);

        Session::flash('success', 'API settings saved successfully.');

        return redirect()->back();
    }

    public function generateKey(Request $request)
    {
        $this->denyForDistributionRoles();

        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['type' => 'error', 'message' => 'User not found.']);
        }

        $apiKey = Str::random(15) . rand(11111111, 9999999) . $user->id . Str::random(15);
        DB::table('users')->where('id', $user->id)->update([
            'api_key' => $apiKey,
            'updated_at' => now(),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'API key generated successfully.',
            'api_key' => $apiKey,
        ]);
    }
}
