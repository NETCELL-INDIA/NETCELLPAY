<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ApiSettingsController extends Controller
{
    private function denyForDistributionRoles()
    {
        if (in_array((int) Session::get('role_id'), [4, 5, 6], true)) {
            abort(403, 'You are not allowed to access API Configuration.');
        }
    }

    public function index()
    {
        $this->denyForDistributionRoles();

        $company = DB::table('companies')
            ->where('status', '1')
            ->where('domain', $_SERVER['HTTP_HOST'] ?? '')
            ->first();

        $companyId = $company->id ?? 0;
        $setting = DB::table('api_settings')
            ->where('company_id', $companyId)
            ->first();

        return view('users.api-settings.index', compact('company', 'setting'));
    }

    public function store(Request $request)
    {
        $this->denyForDistributionRoles();

        $company = DB::table('companies')
            ->where('status', '1')
            ->where('domain', $_SERVER['HTTP_HOST'] ?? '')
            ->first();

        $companyId = $company->id ?? 0;

        $data = [
            'company_id' => $companyId,
            'provider_name' => $request->input('provider_name', 'Recharge API'),
            'api_name' => $request->input('api_name'),
            'api_url' => $request->input('api_url'),
            'api_key' => $request->input('api_key'),
            'api_username' => $request->input('api_username'),
            'api_password' => $request->input('api_password'),
            'status' => $request->filled('status') ? 1 : 0,
            'notes' => $request->input('notes'),
            'updated_at' => now(),
        ];

        $existing = DB::table('api_settings')
            ->where('company_id', $companyId)
            ->where('provider_name', $data['provider_name'])
            ->first();

        if ($existing) {
            DB::table('api_settings')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('api_settings')->insert($data);
        }

        Session::flash('success', 'API settings saved successfully.');

        return redirect()->back();
    }
}
