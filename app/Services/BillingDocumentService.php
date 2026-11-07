<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BillingDocumentService
{
    public static function ensureTables(): void
    {
        if (! Schema::hasTable('payment_receipts')) {
            Schema::create('payment_receipts', function ($table) {
                $table->id();
                $table->string('receipt_number', 40)->unique();
                $table->unsignedBigInteger('fund_request_id')->unique();
                $table->string('order_id', 80)->unique();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 8)->default('INR');
                $table->string('payment_method', 40)->nullable();
                $table->string('gateway_reference', 80)->nullable();
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->default(0);
                $table->decimal('wallet_credit', 12, 2)->default(0);
                $table->decimal('fee_amount', 12, 2)->default(0);
                $table->dateTime('receipt_date')->nullable();
                $table->string('status', 20)->default('issued');
                $table->string('share_token', 64)->nullable()->unique();
                $table->dateTime('share_expires_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('gst_invoices')) {
            Schema::create('gst_invoices', function ($table) {
                $table->id();
                $table->string('invoice_number', 40)->unique();
                $table->unsignedBigInteger('receipt_id')->nullable()->unique();
                $table->unsignedBigInteger('fund_request_id')->nullable()->unique();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('customer_gstin', 20)->nullable();
                $table->string('supplier_gstin', 20)->nullable();
                $table->string('place_of_supply', 80)->nullable();
                $table->string('customer_state', 80)->nullable();
                $table->string('supply_description', 180)->nullable();
                $table->string('sac_code', 20)->nullable();
                $table->decimal('taxable_amount', 12, 2)->default(0);
                $table->decimal('gst_rate', 6, 2)->default(0);
                $table->decimal('cgst_rate', 6, 2)->default(0);
                $table->decimal('sgst_rate', 6, 2)->default(0);
                $table->decimal('igst_rate', 6, 2)->default(0);
                $table->decimal('cgst_amount', 12, 2)->default(0);
                $table->decimal('sgst_amount', 12, 2)->default(0);
                $table->decimal('igst_amount', 12, 2)->default(0);
                $table->decimal('total_gst', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->string('tax_mode', 20)->default('exclusive');
                $table->string('supply_type', 20)->default('intra');
                $table->unsignedTinyInteger('reverse_charge')->default(0);
                $table->dateTime('invoice_date')->nullable();
                $table->string('status', 20)->default('issued');
                $table->string('share_token', 64)->nullable()->unique();
                $table->dateTime('share_expires_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('gst_settings')) {
            Schema::create('gst_settings', function ($table) {
                $table->id();
                $table->string('company_legal_name', 160)->nullable();
                $table->string('company_address', 255)->nullable();
                $table->string('gstin', 20)->nullable();
                $table->string('state_name', 80)->nullable();
                $table->string('state_code', 4)->nullable();
                $table->string('invoice_prefix', 12)->default('INV');
                $table->string('receipt_prefix', 12)->default('REC');
                $table->string('default_sac', 20)->nullable();
                $table->decimal('default_gst_rate', 6, 2)->default(0);
                $table->decimal('cgst_rate', 6, 2)->default(0);
                $table->decimal('sgst_rate', 6, 2)->default(0);
                $table->decimal('igst_rate', 6, 2)->default(0);
                $table->string('tax_mode', 20)->default('exclusive');
                $table->unsignedTinyInteger('reverse_charge')->default(0);
                $table->unsignedTinyInteger('is_gst_registered')->default(0);
                $table->unsignedTinyInteger('invoice_service_fee')->default(0);
                $table->text('terms')->nullable();
                $table->string('signatory', 120)->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('invoice_sequences')) {
            Schema::create('invoice_sequences', function ($table) {
                $table->id();
                $table->string('document_type', 20);
                $table->string('financial_year', 12);
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
                $table->unique(['document_type', 'financial_year']);
            });
        }
        if (! Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function ($table) {
                $table->id();
                $table->string('note_number', 40)->unique();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->unsignedBigInteger('receipt_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('reason', 255)->nullable();
                $table->string('status', 20)->default('issued');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('billing_audits')) {
            Schema::create('billing_audits', function ($table) {
                $table->id();
                $table->string('action', 40);
                $table->string('document_type', 30)->nullable();
                $table->unsignedBigInteger('document_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('remark', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public static function settings(): object
    {
        self::ensureTables();
        $row = DB::table('gst_settings')->where('id', 1)->first();
        if ($row) {
            return $row;
        }
        $company = DB::table('companies')->where('id', 1)->first();
        DB::table('gst_settings')->insert([
            'id' => 1,
            'company_legal_name' => $company->company_name ?? null,
            'company_address' => $company->company_address ?? null,
            'invoice_prefix' => 'INV',
            'receipt_prefix' => 'REC',
            'tax_mode' => 'exclusive',
            'is_gst_registered' => 0,
            'invoice_service_fee' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return DB::table('gst_settings')->where('id', 1)->first();
    }

    public static function issueForTransferredFund(int $fundId): void
    {
        if ($fundId <= 0) {
            return;
        }
        self::ensureTables();
        $fund = DB::table('fund_requests')->where('id', $fundId)->where('status', 'Transferred')->first();
        if (! $fund || ! in_array((int) $fund->upi, [1, 2], true)) {
            return;
        }
        if (DB::table('payment_receipts')->where('fund_request_id', $fundId)->exists()) {
            return;
        }

        try {
            DB::transaction(function () use ($fund) {
                if (DB::table('payment_receipts')->where('fund_request_id', $fund->id)->lockForUpdate()->exists()) {
                    return;
                }
                $settings = self::settings();
                $user = DB::table('users')->where('id', $fund->user_id)->first();
                $ledger = DB::table('reports')
                    ->where('order_id', $fund->order_id)
                    ->where('transaction_type', 'Upi Add Money')
                    ->orderByDesc('id')
                    ->first();
                $amount = round((float) $fund->amount, 2);
                $fee = self::serviceFee($amount, $settings);
                $now = Carbon::now();
                $receiptNumber = self::nextNumber('receipt', (string) ($settings->receipt_prefix ?: 'REC'));
                $token = Str::random(48);
                $receiptId = DB::table('payment_receipts')->insertGetId([
                    'receipt_number' => $receiptNumber,
                    'fund_request_id' => $fund->id,
                    'order_id' => $fund->order_id,
                    'user_id' => $fund->user_id,
                    'amount' => $amount,
                    'currency' => 'INR',
                    'payment_method' => (int) $fund->upi === 2 ? 'QR' : 'UPI',
                    'gateway_reference' => (string) ($fund->transaction_number ?? ''),
                    'opening_balance' => (float) ($ledger->opening_balance ?? 0),
                    'closing_balance' => (float) ($ledger->closing_balance ?? 0),
                    'wallet_credit' => $amount,
                    'fee_amount' => $fee,
                    'receipt_date' => $fund->decision_date ?: $now,
                    'status' => 'issued',
                    'share_token' => $token,
                    'share_expires_at' => $now->copy()->addDays(7),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                self::audit('issue_receipt', 'receipt', $receiptId, (int) $fund->user_id, $receiptNumber);
                if ($fee > 0) {
                    self::issueGstInvoice($receiptId, $fund, $user, $settings, $fee, $now);
                }
                self::notify($user, $receiptNumber, $amount, (string) $fund->order_id, $token, $fee > 0 ? $receiptId : 0);
            });
        } catch (\Throwable $e) {
            if (! DB::table('payment_receipts')->where('fund_request_id', $fundId)->exists()) {
                \Log::warning('payment receipt skipped: '.$e->getMessage());
            }
        }
    }

    public static function backfillReceipts(int $limit = 100): int
    {
        self::ensureTables();
        $ids = DB::table('fund_requests as f')
            ->leftJoin('payment_receipts as r', 'r.fund_request_id', '=', 'f.id')
            ->whereNull('r.id')
            ->where('f.status', 'Transferred')
            ->whereIn('f.upi', [1, 2])
            ->orderByDesc('f.id')
            ->limit(max(1, min(100, $limit)))
            ->pluck('f.id');
        $count = 0;
        foreach ($ids as $id) {
            $before = DB::table('payment_receipts')->where('fund_request_id', $id)->exists();
            self::issueForTransferredFund((int) $id);
            if (! $before && DB::table('payment_receipts')->where('fund_request_id', $id)->exists()) {
                $count++;
            }
        }

        return $count;
    }

    public static function statusPayload($fund): array
    {
        self::ensureTables();
        $receipt = DB::table('payment_receipts')->where('fund_request_id', $fund->id)->first();
        $invoice = $receipt
            ? DB::table('gst_invoices')->where('receipt_id', $receipt->id)->where('status', 'issued')->first()
            : null;
        $wallet = DB::table('users')->where('id', $fund->user_id)->value('wallet_balance');

        return [
            'amount' => number_format((float) $fund->amount, 2, '.', ''),
            'transaction_id' => (string) $fund->order_id,
            'wallet_balance' => number_format((float) $wallet, 2, '.', ''),
            'receipt_number' => $receipt->receipt_number ?? '',
            'receipt_url' => $receipt ? url('billing/document/'.$receipt->share_token) : '',
            'invoice_number' => $invoice->invoice_number ?? '',
            'invoice_url' => $invoice && $invoice->share_token ? url('billing/document/'.$invoice->share_token) : '',
        ];
    }

    public static function documentByToken(string $token)
    {
        self::ensureTables();
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $now = Carbon::now()->toDateTimeString();
        $receipt = DB::table('payment_receipts')
            ->where('share_token', $token)
            ->where('share_expires_at', '>', $now)
            ->first();
        if ($receipt) {
            return ['type' => 'receipt', 'row' => $receipt];
        }
        $invoice = DB::table('gst_invoices')
            ->where('share_token', $token)
            ->where('status', 'issued')
            ->where('share_expires_at', '>', $now)
            ->first();
        if ($invoice) {
            return ['type' => 'invoice', 'row' => $invoice];
        }

        return null;
    }

    public static function receiptForUser(int $id, int $userId)
    {
        self::ensureTables();

        return DB::table('payment_receipts')->where('id', $id)->where('user_id', $userId)->first();
    }

    public static function invoiceForUser(int $id, int $userId)
    {
        self::ensureTables();

        return DB::table('gst_invoices')->where('id', $id)->where('user_id', $userId)->where('status', 'issued')->first();
    }

    public static function receiptPdf($receipt): string
    {
        $user = DB::table('users')->where('id', $receipt->user_id)->first();
        $company = self::companyBlock();
        $lines = [
            $company['name'],
            'Payment receipt. This is not a GST tax invoice.',
            'Address: '.$company['address'],
            'Phone: '.$company['phone'],
            'Email: '.$company['email'],
            'Receipt No: '.$receipt->receipt_number,
            'Date: '.$receipt->receipt_date,
            'Customer: '.self::personName($user),
            'Mobile: '.($user->mobile_number ?? ''),
            'Customer ID: '.$receipt->user_id,
            'Wallet Account ID: '.$receipt->user_id,
            'Transaction ID: '.$receipt->order_id,
            'Gateway reference: '.($receipt->gateway_reference ?: '-'),
            'Payment method: '.$receipt->payment_method,
            'Amount received: Rs '.number_format((float) $receipt->amount, 2, '.', ''),
            'Status: SUCCESS',
            'Wallet credit: Rs '.number_format((float) $receipt->wallet_credit, 2, '.', ''),
            'Previous wallet balance: Rs '.number_format((float) $receipt->opening_balance, 2, '.', ''),
            'Updated wallet balance: Rs '.number_format((float) $receipt->closing_balance, 2, '.', ''),
            'Description: Wallet top-up',
            'Amount in words: '.self::rupeesInWords((float) $receipt->amount),
            'GST is not charged on this wallet deposit.',
            'Authorised by '.$company['name'],
        ];

        return SimplePdf::render($lines);
    }

    public static function invoicePdf($invoice): string
    {
        $user = DB::table('users')->where('id', $invoice->user_id)->first();
        $company = self::companyBlock();
        $settings = self::settings();
        $lines = [
            $company['name'],
            'GST tax invoice',
            'Address: '.($settings->company_address ?: $company['address']),
            'GSTIN: '.($invoice->supplier_gstin ?: '-'),
            'State: '.trim(($settings->state_name ?? '').' '.($settings->state_code ?? '')),
            'Invoice No: '.$invoice->invoice_number,
            'Date: '.$invoice->invoice_date,
            'Customer: '.self::personName($user),
            'Customer GSTIN: '.($invoice->customer_gstin ?: '-'),
            'Place of supply: '.($invoice->place_of_supply ?: '-'),
            'Description: '.($invoice->supply_description ?: 'Service fee'),
            'SAC: '.($invoice->sac_code ?: '-'),
            'Qty: 1',
            'Taxable value: Rs '.number_format((float) $invoice->taxable_amount, 2, '.', ''),
            'CGST '.$invoice->cgst_rate.'%: Rs '.number_format((float) $invoice->cgst_amount, 2, '.', ''),
            'SGST '.$invoice->sgst_rate.'%: Rs '.number_format((float) $invoice->sgst_amount, 2, '.', ''),
            'IGST '.$invoice->igst_rate.'%: Rs '.number_format((float) $invoice->igst_amount, 2, '.', ''),
            'Total GST: Rs '.number_format((float) $invoice->total_gst, 2, '.', ''),
            'Grand total: Rs '.number_format((float) $invoice->grand_total, 2, '.', ''),
            'Amount in words: '.self::rupeesInWords((float) $invoice->grand_total),
            'Reverse charge: '.((int) $invoice->reverse_charge === 1 ? 'Yes' : 'No'),
            'This invoice is for the service fee only. The wallet top-up is not taxed.',
            'Terms: '.($settings->terms ?: '-'),
            'Authorised signatory: '.($settings->signatory ?: $company['name']),
        ];

        return SimplePdf::render($lines);
    }

    public static function refreshShare($table, int $id): ?string
    {
        self::ensureTables();
        $token = Str::random(48);
        DB::table($table)->where('id', $id)->update([
            'share_token' => $token,
            'share_expires_at' => Carbon::now()->addDays(7),
            'updated_at' => Carbon::now(),
        ]);

        return $token;
    }

    public static function issueCreditNote(int $invoiceId, float $amount, string $reason, int $adminId): array
    {
        self::ensureTables();
        $invoice = DB::table('gst_invoices')->where('id', $invoiceId)->where('status', 'issued')->first();
        if (! $invoice) {
            return ['ok' => false, 'message' => 'Invoice not found.'];
        }
        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > (float) $invoice->grand_total) {
            return ['ok' => false, 'message' => 'Amount must be within the invoice total.'];
        }
        $already = (float) DB::table('credit_notes')->where('invoice_id', $invoiceId)->where('status', 'issued')->sum('amount');
        if ($already + $amount > (float) $invoice->grand_total + 0.001) {
            return ['ok' => false, 'message' => 'Credit notes already cover this invoice.'];
        }
        return DB::transaction(function () use ($invoice, $amount, $reason, $adminId) {
        $number = self::nextNumber('credit', 'CN');
        $id = DB::table('credit_notes')->insertGetId([
            'note_number' => $number,
            'invoice_id' => $invoice->id,
            'receipt_id' => $invoice->receipt_id,
            'user_id' => $invoice->user_id,
            'amount' => $amount,
            'reason' => mb_substr(trim($reason), 0, 255),
            'status' => 'issued',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        self::audit('issue_credit_note', 'credit_note', $id, $adminId, $number.' for '.$invoice->invoice_number);

        return ['ok' => true, 'message' => 'Credit note '.$number.' issued. Wallet was not changed.', 'id' => $id];
        });
    }

    public static function saveSettings(array $input): void
    {
        self::settings();
        $rate = function ($value): float {
            $n = round((float) $value, 2);

            return max(0, min(100, $n));
        };
        DB::table('gst_settings')->where('id', 1)->update([
            'company_legal_name' => mb_substr(trim((string) ($input['company_legal_name'] ?? '')), 0, 160),
            'company_address' => mb_substr(trim((string) ($input['company_address'] ?? '')), 0, 255),
            'gstin' => mb_substr(strtoupper(trim((string) ($input['gstin'] ?? ''))), 0, 20),
            'state_name' => mb_substr(trim((string) ($input['state_name'] ?? '')), 0, 80),
            'state_code' => mb_substr(trim((string) ($input['state_code'] ?? '')), 0, 4),
            'invoice_prefix' => mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($input['invoice_prefix'] ?? 'INV')) ?: 'INV'), 0, 12),
            'receipt_prefix' => mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($input['receipt_prefix'] ?? 'REC')) ?: 'REC'), 0, 12),
            'default_sac' => mb_substr(trim((string) ($input['default_sac'] ?? '')), 0, 20),
            'default_gst_rate' => $rate($input['default_gst_rate'] ?? 0),
            'cgst_rate' => $rate($input['cgst_rate'] ?? 0),
            'sgst_rate' => $rate($input['sgst_rate'] ?? 0),
            'igst_rate' => $rate($input['igst_rate'] ?? 0),
            'tax_mode' => ($input['tax_mode'] ?? '') === 'inclusive' ? 'inclusive' : 'exclusive',
            'reverse_charge' => ! empty($input['reverse_charge']) ? 1 : 0,
            'is_gst_registered' => ! empty($input['is_gst_registered']) ? 1 : 0,
            'invoice_service_fee' => ! empty($input['invoice_service_fee']) ? 1 : 0,
            'terms' => mb_substr(trim((string) ($input['terms'] ?? '')), 0, 2000),
            'signatory' => mb_substr(trim((string) ($input['signatory'] ?? '')), 0, 120),
            'updated_at' => Carbon::now(),
        ]);
    }

    public static function dashboard(string $from, string $to): array
    {
        self::ensureTables();
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';
        $pay = DB::table('fund_requests')->whereIn('upi', [1, 2])->whereBetween('created_at', [$start, $end])->selectRaw("
            SUM(CASE WHEN status = 'Transferred' THEN 1 ELSE 0 END) as success_cnt,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as failed_cnt,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_cnt,
            COUNT(*) as total_cnt,
            SUM(CASE WHEN status = 'Transferred' THEN amount ELSE 0 END) as paid
        ")->first();
        $gst = DB::table('gst_invoices')->where('status', 'issued')->whereBetween('invoice_date', [$start, $end])->selectRaw('
            COUNT(*) as cnt,
            COALESCE(SUM(taxable_amount), 0) as taxable,
            COALESCE(SUM(cgst_amount), 0) as cgst,
            COALESCE(SUM(sgst_amount), 0) as sgst,
            COALESCE(SUM(igst_amount), 0) as igst,
            COALESCE(SUM(total_gst), 0) as gst
        ')->first();
        $receipts = DB::table('payment_receipts')->whereBetween('receipt_date', [$start, $end])->count();
        $credits = (float) DB::table('credit_notes')->where('status', 'issued')->whereBetween('created_at', [$start, $end])->sum('amount');

        return [
            'total_cnt' => (int) ($pay->total_cnt ?? 0),
            'success_cnt' => (int) ($pay->success_cnt ?? 0),
            'failed_cnt' => (int) ($pay->failed_cnt ?? 0),
            'pending_cnt' => (int) ($pay->pending_cnt ?? 0),
            'paid' => (float) ($pay->paid ?? 0),
            'receipts' => (int) $receipts,
            'taxable' => (float) ($gst->taxable ?? 0),
            'cgst' => (float) ($gst->cgst ?? 0),
            'sgst' => (float) ($gst->sgst ?? 0),
            'igst' => (float) ($gst->igst ?? 0),
            'gst' => (float) ($gst->gst ?? 0),
            'invoices' => (int) ($gst->cnt ?? 0),
            'credits' => $credits,
        ];
    }

    public static function financialYear(?Carbon $date = null): string
    {
        $date = $date ?: Carbon::now('Asia/Kolkata');
        $year = (int) $date->format('Y');
        $start = (int) $date->format('n') >= 4 ? $year : $year - 1;

        return $start.'-'.substr((string) ($start + 1), -2);
    }

    private static function issueGstInvoice(int $receiptId, $fund, $user, $settings, float $fee, Carbon $now): void
    {
        if ((int) ($settings->is_gst_registered ?? 0) !== 1 || (int) ($settings->invoice_service_fee ?? 0) !== 1) {
            return;
        }
        $rates = self::taxRates($settings, $user);
        if ($rates['total_rate'] <= 0) {
            return;
        }
        $taxable = $fee;
        if (($settings->tax_mode ?? 'exclusive') === 'inclusive') {
            $taxable = round($fee / (1 + ($rates['total_rate'] / 100)), 2);
        }
        $cgst = round($taxable * $rates['cgst'] / 100, 2);
        $sgst = round($taxable * $rates['sgst'] / 100, 2);
        $igst = round($taxable * $rates['igst'] / 100, 2);
        $totalGst = round($cgst + $sgst + $igst, 2);
        $grand = ($settings->tax_mode ?? 'exclusive') === 'inclusive' ? $fee : round($taxable + $totalGst, 2);
        $number = self::nextNumber('invoice', (string) ($settings->invoice_prefix ?: 'INV'));
        $token = Str::random(48);
        $invoiceId = DB::table('gst_invoices')->insertGetId([
            'invoice_number' => $number,
            'receipt_id' => $receiptId,
            'fund_request_id' => $fund->id,
            'user_id' => $fund->user_id,
            'customer_gstin' => null,
            'supplier_gstin' => $settings->gstin,
            'place_of_supply' => $rates['place'],
            'customer_state' => $rates['customer_state'],
            'supply_description' => 'Add money service fee',
            'sac_code' => $settings->default_sac,
            'taxable_amount' => $taxable,
            'gst_rate' => $rates['total_rate'],
            'cgst_rate' => $rates['cgst'],
            'sgst_rate' => $rates['sgst'],
            'igst_rate' => $rates['igst'],
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'total_gst' => $totalGst,
            'grand_total' => $grand,
            'tax_mode' => $settings->tax_mode ?: 'exclusive',
            'supply_type' => $rates['supply_type'],
            'reverse_charge' => (int) ($settings->reverse_charge ?? 0),
            'invoice_date' => $now,
            'status' => 'issued',
            'share_token' => $token,
            'share_expires_at' => $now->copy()->addDays(7),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::audit('issue_invoice', 'invoice', $invoiceId, (int) $fund->user_id, $number);
    }

    private static function serviceFee(float $amount, $settings): float
    {
        if ((int) ($settings->invoice_service_fee ?? 0) !== 1) {
            return 0.0;
        }
        if (SystemSettingService::get('add_money_charge_status', '0') !== '1') {
            return 0.0;
        }
        $value = (float) SystemSettingService::get('add_money_charge_value', 0);
        if ($value <= 0) {
            return 0.0;
        }
        $fee = SystemSettingService::get('add_money_charge_type', 'fixed') === 'percent'
            ? round($amount * $value / 100, 2)
            : round($value, 2);

        return $fee > 0 ? $fee : 0.0;
    }

    private static function taxRates($settings, $user): array
    {
        $companyState = strtolower(trim((string) ($settings->state_name ?? '')));
        $customerStateRaw = trim((string) ($user->state ?? ''));
        $customerState = strtolower($customerStateRaw);
        $intra = $companyState === '' || $customerState === '' || ctype_digit($customerStateRaw) || $companyState === $customerState;
        $cgst = (float) ($settings->cgst_rate ?? 0);
        $sgst = (float) ($settings->sgst_rate ?? 0);
        $igst = (float) ($settings->igst_rate ?? 0);
        $fallback = (float) ($settings->default_gst_rate ?? 0);
        if ($intra) {
            if ($cgst <= 0 && $sgst <= 0 && $fallback > 0) {
                $cgst = round($fallback / 2, 2);
                $sgst = round($fallback - $cgst, 2);
            }
            $igst = 0;
        } else {
            if ($igst <= 0 && $fallback > 0) {
                $igst = $fallback;
            }
            $cgst = 0;
            $sgst = 0;
        }
        $total = $intra ? round($cgst + $sgst, 2) : round($igst, 2);

        return [
            'intra' => $intra,
            'supply_type' => $intra ? 'intra' : 'inter',
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total_rate' => $total,
            'place' => $intra ? ($settings->state_name ?: 'Company state') : (string) ($user->state ?? ''),
            'customer_state' => (string) ($user->state ?? ''),
        ];
    }

    private static function nextNumber(string $type, string $prefix): string
    {
        $fy = self::financialYear();
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'DOC');
        $row = DB::table('invoice_sequences')->where('document_type', $type)->where('financial_year', $fy)->lockForUpdate()->first();
        if (! $row) {
            DB::table('invoice_sequences')->insert([
                'document_type' => $type,
                'financial_year' => $fy,
                'last_number' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $next = 1;
        } else {
            $next = (int) $row->last_number + 1;
            DB::table('invoice_sequences')->where('id', $row->id)->update([
                'last_number' => $next,
                'updated_at' => Carbon::now(),
            ]);
        }

        return $prefix.'/'.$fy.'/'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private static function notify($user, string $receiptNumber, float $amount, string $orderId, string $token, int $receiptId): void
    {
        if (! $user || ! class_exists('helpers')) {
            return;
        }
        $link = url('billing/document/'.$token);
        $text = 'Dear Customer, your Add Money transaction was successful. Amount: Rs '.number_format($amount, 2, '.', '').'. Transaction ID: '.$orderId.'. Receipt No: '.$receiptNumber.'. Receipt link (valid 7 days): '.$link;
        if ($receiptId > 0) {
            $invoice = DB::table('gst_invoices')->where('receipt_id', $receiptId)->where('status', 'issued')->first();
            if ($invoice && $invoice->share_token) {
                $text .= ' GST invoice '.$invoice->invoice_number.': '.url('billing/document/'.$invoice->share_token);
            }
        }
        try {
            $sms = DB::table('sms_templates')->where('slug', 'fund_receive')->first(['template_id', 'content', 'status']);
            if (\helpers::whatsappEnabled('fund_receive', $sms)) {
                \helpers::sendWhatasappMsg([
                    'mobile_number' => $user->mobile_number,
                    'content' => $text,
                    'template_id' => (string) ($sms->template_id ?? ''),
                    'slug' => 'fund_receive',
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('receipt whatsapp skipped: '.$e->getMessage());
        }
        try {
            $company = DB::table('companies')->where('id', 1)->first();
            if ($company && (int) ($company->email_message ?? 0) === 1 && filter_var($user->email_address ?? '', FILTER_VALIDATE_EMAIL)) {
                Mail::raw($text, function ($message) use ($user, $receiptNumber) {
                    $message->to($user->email_address)->subject('Payment receipt '.$receiptNumber);
                });
            }
        } catch (\Throwable $e) {
            \Log::warning('receipt email skipped: '.$e->getMessage());
        }
        unset($receiptId);
    }

    private static function companyBlock(): array
    {
        $settings = self::settings();
        $company = DB::table('companies')->where('id', 1)->first();

        return [
            'name' => $settings->company_legal_name ?: ($company->company_name ?? 'NETCELL RETAIL INDIA PRIVATE LIMITED'),
            'address' => $settings->company_address ?: ($company->company_address ?? ''),
            'phone' => $company->support_number ?? '',
            'email' => $company->support_email ?? '',
        ];
    }

    private static function personName($user): string
    {
        if (! $user) {
            return '';
        }
        $name = trim((string) ($user->first_name ?? '').' '.(string) ($user->last_name ?? ''));
        if ($name === '' || strcasecmp(trim((string) $user->first_name), trim((string) ($user->last_name ?? ''))) === 0) {
            $name = trim((string) ($user->first_name ?? ''));
        }

        return $name !== '' ? $name : (string) ($user->outlet_name ?? '');
    }

    public static function rupeesInWords(float $amount): string
    {
        $rupees = (int) floor(round($amount, 2));
        $paise = (int) round(($amount - $rupees) * 100);
        $text = 'Rupees '.self::indianWords($rupees);
        if ($paise > 0) {
            $text .= ' and '.self::indianWords($paise).' Paise';
        }

        return $text.' Only';
    }

    private static function indianWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $parts = [];
        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $rest = $number % 100;
        $chunk = function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }

            return trim($tens[intdiv($n, 10)].' '.$ones[$n % 10]);
        };
        if ($crore) {
            $parts[] = $chunk($crore).' Crore';
        }
        if ($lakh) {
            $parts[] = $chunk($lakh).' Lakh';
        }
        if ($thousand) {
            $parts[] = $chunk($thousand).' Thousand';
        }
        if ($hundred) {
            $parts[] = $ones[$hundred].' Hundred';
        }
        if ($rest) {
            $parts[] = $chunk($rest);
        }

        return trim(implode(' ', $parts));
    }

    private static function audit(string $action, string $type, int $id, int $userId, string $remark): void
    {
        try {
            DB::table('billing_audits')->insert([
                'action' => $action,
                'document_type' => $type,
                'document_id' => $id,
                'user_id' => $userId,
                'remark' => mb_substr($remark, 0, 255),
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
