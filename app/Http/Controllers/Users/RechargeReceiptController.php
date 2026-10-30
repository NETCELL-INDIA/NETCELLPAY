<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\SimplePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RechargeReceiptController extends Controller
{
    public function download(Request $request, int $id)
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            $userId = (int) $request->input('user_id');
        }
        if ($userId <= 0 || $id <= 0) {
            abort(404);
        }

        $report = DB::table('reports')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereIn('transaction_type', ['Recharge', 'Bill Pay'])
            ->first();
        if (! $report) {
            abort(404);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        $provider = DB::table('providers')->where('id', $report->provider_id)->value('provider_name');
        $company = DB::table('companies')->where('status', 1)->orderBy('id')->first();

        $agent = trim((string) (($user->outlet_name ?? '') !== '' ? $user->outlet_name : trim(($user->first_name ?? '').' '.($user->last_name ?? ''))));
        $lines = [
            (string) ($company->company_name ?? 'NETCELL RETAIL INDIA PRIVATE LIMITED'),
            'Recharge receipt',
            'Agent: '.$agent,
            'Agent mobile: '.($user->mobile_number ?? ''),
            'Order ID: '.($report->order_id ?? ''),
            'Date: '.($report->created_at ?? ''),
            'Number: '.($report->number ?? ''),
            'Operator: '.($provider ?: '-'),
            'Operator ID: '.($report->operator_id ?: '-'),
            'Status: '.($report->status ?? ''),
            'Amount: Rs '.number_format((float) ($report->total_amount ?? $report->amount), 2, '.', ''),
            'Remark: '.($report->remark ?? ''),
            'Support: '.($company->support_number ?? ''),
            'This is a transaction receipt. It is not a tax invoice.',
        ];

        $pdf = SimplePdf::render($lines);
        $name = 'receipt-'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($report->order_id ?: $id)).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}
