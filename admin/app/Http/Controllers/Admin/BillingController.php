<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BillingDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BillingController extends Controller
{
    public function dashboard(Request $request)
    {
        [$from, $to] = $this->dates($request);
        $stats = BillingDocumentService::dashboard($from, $to);

        return view('admin.billing.dashboard', compact('stats', 'from', 'to'));
    }

    public function receipts(Request $request)
    {
        BillingDocumentService::ensureTables();
        [$from, $to] = $this->dates($request);
        $query = DB::table('payment_receipts as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->whereBetween('r.receipt_date', [$from.' 00:00:00', $to.' 23:59:59']);
        $this->applyFilters($query, $request, 'r.receipt_number', 'r.order_id');
        $rows = $query->orderByDesc('r.id')->limit(200)->get([
            'r.*', 'u.first_name', 'u.last_name', 'u.mobile_number', 'u.outlet_name',
        ]);

        return view('admin.billing.receipts', compact('rows', 'from', 'to'));
    }

    public function invoices(Request $request)
    {
        BillingDocumentService::ensureTables();
        [$from, $to] = $this->dates($request);
        $query = DB::table('gst_invoices as g')
            ->leftJoin('users as u', 'u.id', '=', 'g.user_id')
            ->whereBetween('g.invoice_date', [$from.' 00:00:00', $to.' 23:59:59']);
        $this->applyFilters($query, $request, 'g.invoice_number', 'g.fund_request_id');
        if ($request->get('gst_status') === 'issued') {
            $query->where('g.status', 'issued');
        }
        $rows = $query->orderByDesc('g.id')->limit(200)->get([
            'g.*', 'u.first_name', 'u.last_name', 'u.mobile_number',
        ]);

        return view('admin.billing.invoices', compact('rows', 'from', 'to'));
    }

    public function creditNotes()
    {
        BillingDocumentService::ensureTables();
        $notes = DB::table('credit_notes as c')
            ->leftJoin('gst_invoices as g', 'g.id', '=', 'c.invoice_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->orderByDesc('c.id')
            ->limit(200)
            ->get(['c.*', 'g.invoice_number', 'u.first_name', 'u.mobile_number']);
        $invoices = DB::table('gst_invoices')->where('status', 'issued')->orderByDesc('id')->limit(100)->get(['id', 'invoice_number', 'grand_total']);

        return view('admin.billing.credit-notes', compact('notes', 'invoices'));
    }

    public function storeCreditNote(Request $request)
    {
        $result = BillingDocumentService::issueCreditNote(
            (int) $request->input('invoice_id'),
            (float) $request->input('amount'),
            (string) $request->input('reason'),
            (int) Session::get('user_id')
        );

        return redirect('admin/billing/credit-notes')->with('billing_message', $result['message']);
    }

    public function settings()
    {
        $settings = BillingDocumentService::settings();

        return view('admin.billing.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        BillingDocumentService::saveSettings($request->all());

        return redirect('admin/billing/settings')->with('billing_message', 'GST settings saved.');
    }

    public function report(Request $request)
    {
        BillingDocumentService::ensureTables();
        [$from, $to] = $this->dates($request);
        $rows = DB::table('payment_receipts')->whereBetween('receipt_date', [$from.' 00:00:00', $to.' 23:59:59'])->orderByDesc('id')->limit(4000)->get();
        $csv = "receipt_number,order_id,user_id,amount,wallet_credit,fee_amount,method,date,status\n";
        foreach ($rows as $row) {
            $line = [$row->receipt_number, $row->order_id, $row->user_id, $row->amount, $row->wallet_credit, $row->fee_amount, $row->payment_method, $row->receipt_date, $row->status];
            $csv .= implode(',', array_map(function ($value) {
                $value = str_replace(["\r", "\n"], ' ', (string) $value);
                if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                    $value = "'".$value;
                }

                return '"'.str_replace('"', '""', $value).'"';
            }, $line))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="billing-receipts-'.$from.'-to-'.$to.'.csv"',
        ]);
    }

    public function receiptPdf(int $id)
    {
        BillingDocumentService::ensureTables();
        $row = DB::table('payment_receipts')->where('id', $id)->first();
        abort_unless($row, 404);

        return $this->pdf(BillingDocumentService::receiptPdf($row), $row->receipt_number);
    }

    public function invoicePdf(int $id)
    {
        BillingDocumentService::ensureTables();
        $row = DB::table('gst_invoices')->where('id', $id)->first();
        abort_unless($row, 404);

        return $this->pdf(BillingDocumentService::invoicePdf($row), $row->invoice_number);
    }

    public function resend(int $id)
    {
        BillingDocumentService::ensureTables();
        $row = DB::table('payment_receipts')->where('id', $id)->first();
        abort_unless($row, 404);
        BillingDocumentService::refreshShare('payment_receipts', $id);
        $fresh = DB::table('payment_receipts')->where('id', $id)->first();
        $user = DB::table('users')->where('id', $row->user_id)->first();
        if ($user) {
            try {
                \App\Common::sendWhatasappMsg([
                    'mobile_number' => $user->mobile_number,
                    'content' => 'Receipt '.$fresh->receipt_number.' for Rs '.number_format((float) $fresh->amount, 2, '.', '').'. Link valid 7 days: '.request()->getSchemeAndHttpHost().'/billing/document/'.$fresh->share_token,
                    'slug' => 'fund_receive',
                ]);
            } catch (\Throwable $e) {
            }
        }

        return redirect('admin/billing/receipts')->with('billing_message', 'Receipt link refreshed. WhatsApp is sent only when that channel is already on.');
    }

    public function backfill()
    {
        $count = BillingDocumentService::backfillReceipts(100);

        return redirect('admin/billing')->with('billing_message', $count.' payment receipt'.($count === 1 ? '' : 's').' created. Wallet balances were not changed.');
    }

    private function dates(Request $request): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $from = (string) $request->get('from_date');
        $to = (string) $request->get('to_date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = $today;
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $from;
        }

        return [$from, $to];
    }

    private function applyFilters($query, Request $request, string $numberColumn, string $txnColumn): void
    {
        $mobile = preg_replace('/\D/', '', (string) $request->get('mobile'));
        if ($mobile !== '') {
            $query->where('u.mobile_number', 'like', '%'.$mobile.'%');
        }
        $name = trim((string) $request->get('name'));
        if ($name !== '') {
            $query->where('u.first_name', 'like', '%'.$name.'%');
        }
        $number = trim((string) $request->get('number'));
        if ($number !== '') {
            $query->where($numberColumn, $number);
        }
        $txn = trim((string) $request->get('transaction_id'));
        if ($txn !== '') {
            $query->where($txnColumn, $txn);
        }
        $method = trim((string) $request->get('method'));
        if ($method !== '' && $numberColumn === 'r.receipt_number') {
            $query->where('r.payment_method', $method);
        }
    }

    private function pdf(string $body, string $name)
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace('/', '-', $name)) ?: 'document';

        return response($body, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safe.'.pdf"',
        ]);
    }
}
