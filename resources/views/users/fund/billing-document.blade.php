<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $kind === 'invoice' ? $row->invoice_number : $row->receipt_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; margin: 24px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 6px 0; vertical-align: top; }
        .muted { color: #667; }
        .actions { margin: 16px 0; }
        a.btn { display: inline-block; margin-right: 8px; padding: 8px 12px; background: #0f3d8a; color: #fff; text-decoration: none; border-radius: 6px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <a class="btn" href="{{ url('billing/document/'.$token) }}?download=1">Download PDF</a>
        <a class="btn" href="javascript:window.print()">Print</a>
    </div>
    @if($kind === 'invoice')
        <h1>GST tax invoice</h1>
        <p class="muted">This invoice is for the service fee only. The wallet deposit is not taxed.</p>
        <table>
            <tr><td>Invoice</td><td>{{ $row->invoice_number }}</td></tr>
            <tr><td>Date</td><td>{{ $row->invoice_date }}</td></tr>
            <tr><td>Description</td><td>{{ $row->supply_description }}</td></tr>
            <tr><td>SAC</td><td>{{ $row->sac_code ?: '-' }}</td></tr>
            <tr><td>Taxable value</td><td>Rs {{ number_format((float) $row->taxable_amount, 2) }}</td></tr>
            <tr><td>CGST</td><td>Rs {{ number_format((float) $row->cgst_amount, 2) }}</td></tr>
            <tr><td>SGST</td><td>Rs {{ number_format((float) $row->sgst_amount, 2) }}</td></tr>
            <tr><td>IGST</td><td>Rs {{ number_format((float) $row->igst_amount, 2) }}</td></tr>
            <tr><td>Total GST</td><td>Rs {{ number_format((float) $row->total_gst, 2) }}</td></tr>
            <tr><td>Grand total</td><td>Rs {{ number_format((float) $row->grand_total, 2) }}</td></tr>
            <tr><td>Place of supply</td><td>{{ $row->place_of_supply }}</td></tr>
            <tr><td>Reverse charge</td><td>{{ (int) $row->reverse_charge === 1 ? 'Yes' : 'No' }}</td></tr>
        </table>
    @else
        <h1>Payment receipt</h1>
        <p class="muted">Wallet top-up receipt. This is not a GST tax invoice.</p>
        <table>
            <tr><td>Receipt</td><td>{{ $row->receipt_number }}</td></tr>
            <tr><td>Date</td><td>{{ $row->receipt_date }}</td></tr>
            <tr><td>Transaction</td><td>{{ $row->order_id }}</td></tr>
            <tr><td>Method</td><td>{{ $row->payment_method }}</td></tr>
            <tr><td>Gateway reference</td><td>{{ $row->gateway_reference ?: '-' }}</td></tr>
            <tr><td>Amount received</td><td>Rs {{ number_format((float) $row->amount, 2) }}</td></tr>
            <tr><td>Wallet credit</td><td>Rs {{ number_format((float) $row->wallet_credit, 2) }}</td></tr>
            <tr><td>Previous balance</td><td>Rs {{ number_format((float) $row->opening_balance, 2) }}</td></tr>
            <tr><td>Updated balance</td><td>Rs {{ number_format((float) $row->closing_balance, 2) }}</td></tr>
            <tr><td>Status</td><td>SUCCESS</td></tr>
        </table>
    @endif
</body>
</html>
