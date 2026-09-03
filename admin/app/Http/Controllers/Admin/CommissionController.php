<?php

namespace App\Http\Controllers\Admin;

use App\Common;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionController extends Controller
{
    public function index()
    {
        $schemes = DB::table('schemes')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('scheme_name')
            ->get(['id', 'scheme_name']);

        $services = DB::table('services')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('id')
            ->get(['id', 'service_name']);

        return view('admin.commission.index', compact('schemes', 'services'));
    }

    public function denomination(Request $request)
    {
        Common::ensureDenominationCommissionTable();

        $schemes = DB::table('schemes')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('scheme_name')
            ->get(['id', 'scheme_name']);

        $services = DB::table('services')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('id')
            ->get(['id', 'service_name']);

        return view('admin.commission.denomination', [
            'schemes' => $schemes,
            'services' => $services,
            'selected_scheme_id' => $request->get('scheme_id'),
            'selected_provider_id' => $request->get('provider_id'),
            'selected_service_id' => $request->get('service_id'),
        ]);
    }

    public function denominationProviders(Request $post)
    {
        $serviceId = (int) $post->service_id;
        $providers = DB::table('providers')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->when($serviceId > 0, function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId);
            })
            ->orderBy('provider_name')
            ->get(['id', 'provider_name', 'service_id']);

        return response()->json([
            'type' => 'success',
            'data' => $providers,
        ]);
    }

    public function denominationList(Request $post)
    {
        Common::ensureDenominationCommissionTable();

        $schemeId = (int) $post->scheme_id;
        $providerId = (int) $post->provider_id;
        if ($schemeId < 1 || $providerId < 1) {
            return '<h4 class="text-center text-secondary my-3">Select scheme and provider</h4>';
        }

        $rows = DB::table('scheme_commission_denominations')
            ->where('scheme_id', $schemeId)
            ->where('provider_id', $providerId)
            ->orderBy('min_amount')
            ->orderBy('max_amount')
            ->get();

        if ($rows->count() < 1) {
            return '<h4 class="text-center text-secondary my-3">No denomination slab found. Add From-To amount below.</h4>';
        }

        $output = '<table class="table table-bordered table-nowrap align-middle"><thead><tr>
            <th>From</th><th>To</th>
            <th>MD Type</th><th>MD Val</th>
            <th>DT Type</th><th>DT Val</th>
            <th>RT Type</th><th>RT Val</th>
            <th>AP Type</th><th>AP Val</th>
            <th>Action</th>
        </tr></thead><tbody>';
        foreach ($rows as $row) {
            $output .= '<tr>
                <td>₹ '.number_format((float) $row->min_amount, 2).'</td>
                <td>₹ '.number_format((float) $row->max_amount, 2).'</td>
                <td>'.e((string) $row->md_amount_type).'</td>
                <td>'.e((string) $row->md_amount_value).'</td>
                <td>'.e((string) $row->dt_amount_type).'</td>
                <td>'.e((string) $row->dt_amount_value).'</td>
                <td>'.e((string) $row->rt_amount_type).'</td>
                <td>'.e((string) $row->rt_amount_value).'</td>
                <td>'.e((string) $row->ap_amount_type).'</td>
                <td>'.e((string) $row->ap_amount_value).'</td>
                <td><button type="button" class="btn btn-sm btn-danger deleteDenomination" data-id="'.$row->id.'">Delete</button></td>
            </tr>';
        }
        $output .= '</tbody></table>';

        return $output;
    }

    public function denominationSave(Request $post)
    {
        Common::ensureDenominationCommissionTable();

        $rules = [
            'scheme_id' => 'required|numeric',
            'provider_id' => 'required|numeric',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0',
            'md_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'md_value' => 'required|numeric',
            'dt_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'dt_value' => 'required|numeric',
            'rt_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'rt_value' => 'required|numeric',
            'ap_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'ap_value' => 'required|numeric',
        ];
        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            $error = '';
            foreach ($validator->errors()->messages() as $value) {
                $error = $value[0];
            }

            return response()->json(['type' => 'error', 'message' => $error]);
        }

        $min = (float) $post->min_amount;
        $max = (float) $post->max_amount;
        if ($min > $max) {
            return response()->json(['type' => 'error', 'message' => 'From amount cannot be greater than To amount.']);
        }

        DB::table('scheme_commission_denominations')->insert([
            'scheme_id' => (int) $post->scheme_id,
            'provider_id' => (int) $post->provider_id,
            'min_amount' => $min,
            'max_amount' => $max,
            'md_amount_type' => $post->md_comtype,
            'md_amount_value' => $post->md_value,
            'dt_amount_type' => $post->dt_comtype,
            'dt_amount_value' => $post->dt_value,
            'rt_amount_type' => $post->rt_comtype,
            'rt_amount_value' => $post->rt_value,
            'ap_amount_type' => $post->ap_comtype,
            'ap_amount_value' => $post->ap_value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Denomination commission saved.']);
    }

    public function denominationDelete(Request $post)
    {
        Common::ensureDenominationCommissionTable();
        $id = (int) $post->id;
        if ($id < 1 || ! Schema::hasTable('scheme_commission_denominations')) {
            return response()->json(['type' => 'error', 'message' => 'Invalid record.']);
        }
        DB::table('scheme_commission_denominations')->where('id', $id)->delete();

        return response()->json(['type' => 'success', 'message' => 'Deleted successfully.']);
    }
}
