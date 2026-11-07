<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\BillingDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        BillingDocumentService::ensureTables();
        $userId = (int) Session::get('user_id');
        $receipts = DB::table('payment_receipts')->where('user_id', $userId)->orderByDesc('id')->limit(100)->get();
        $invoices = DB::table('gst_invoices')->where('user_id', $userId)->where('status', 'issued')->orderByDesc('id')->limit(100)->get()->keyBy('receipt_id');

        return view('users.fund.receipts', compact('receipts', 'invoices'));
    }

    public function receiptPdf(int $id)
    {
        $row = BillingDocumentService::receiptForUser($id, (int) Session::get('user_id'));
        abort_unless($row, 404);

        return $this->pdf(BillingDocumentService::receiptPdf($row), $row->receipt_number);
    }

    public function invoicePdf(int $id)
    {
        $row = BillingDocumentService::invoiceForUser($id, (int) Session::get('user_id'));
        abort_unless($row, 404);

        return $this->pdf(BillingDocumentService::invoicePdf($row), $row->invoice_number);
    }

    public function shared(Request $request, string $token)
    {
        $found = BillingDocumentService::documentByToken($token);
        abort_unless($found, 404);
        if ($request->query('download') === '1') {
            if ($found['type'] === 'receipt') {
                return $this->pdf(BillingDocumentService::receiptPdf($found['row']), $found['row']->receipt_number);
            }

            return $this->pdf(BillingDocumentService::invoicePdf($found['row']), $found['row']->invoice_number);
        }

        return view('users.fund.billing-document', [
            'kind' => $found['type'],
            'row' => $found['row'],
            'token' => $token,
        ]);
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
