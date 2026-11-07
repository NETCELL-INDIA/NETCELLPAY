<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use App\Services\BillingDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function receipts(Request $request)
    {
        $userId = (int) $request->input('user_id');
        BillingDocumentService::ensureTables();
        $rows = DB::table('payment_receipts')->where('user_id', $userId)->orderByDesc('id')->limit(100)->get();
        $invoices = DB::table('gst_invoices')->where('user_id', $userId)->where('status', 'issued')->get()->keyBy('receipt_id');
        $data = [];
        foreach ($rows as $row) {
            $invoice = $invoices->get($row->id);
            $data[] = [
                'receipt_id' => (int) $row->id,
                'receipt_number' => $row->receipt_number,
                'transaction_id' => $row->order_id,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'payment_method' => $row->payment_method,
                'status' => 'SUCCESS',
                'date' => (string) $row->receipt_date,
                'receipt_url' => url('billing/document/'.$row->share_token),
                'invoice_id' => $invoice ? (int) $invoice->id : 0,
                'invoice_number' => $invoice->invoice_number ?? '',
                'invoice_url' => $invoice && $invoice->share_token ? url('billing/document/'.$invoice->share_token) : '',
            ];
        }

        return response()->json(['type' => 'success', 'message' => 'Fetch Successfully', 'data' => $data]);
    }

    public function receiptDownload(Request $request, int $id)
    {
        $row = BillingDocumentService::receiptForUser($id, (int) $request->input('user_id'));
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Receipt not found.'], 404);
        }

        $name = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace('/', '-', $row->receipt_number));

        return response(BillingDocumentService::receiptPdf($row), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"',
        ]);
    }

    public function invoiceDownload(Request $request, int $id)
    {
        $row = BillingDocumentService::invoiceForUser($id, (int) $request->input('user_id'));
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Invoice not found.'], 404);
        }

        $name = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace('/', '-', $row->invoice_number));

        return response(BillingDocumentService::invoicePdf($row), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"',
        ]);
    }
}
