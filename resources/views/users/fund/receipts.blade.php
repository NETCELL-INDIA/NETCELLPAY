@extends('layouts.master')
@section('title') Payment Receipts @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Fund @endslot
@slot('title') Payment Receipts @endslot
@endcomponent
<div class="card"><div class="card-body">
<p class="text-muted">A payment receipt is for the wallet top-up. A GST invoice appears only when a taxable service fee applies.</p>
<div class="table-responsive"><table class="table table-sm table-bordered">
<thead><tr><th>Date</th><th>Receipt</th><th>Transaction</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($receipts as $row)
@php $invoice = $invoices->get($row->id); @endphp
<tr>
    <td>{{ $row->receipt_date }}</td>
    <td>{{ $row->receipt_number }}</td>
    <td>{{ $row->order_id }}</td>
    <td>{{ $row->payment_method }}</td>
    <td>₹{{ number_format((float) $row->amount, 2) }}</td>
    <td>SUCCESS</td>
    <td>
        <a href="{{ url('users/fund/receipts/'.$row->id.'/pdf') }}">Receipt PDF</a>
        @if($invoice)
            <a href="{{ url('users/fund/invoices/'.$invoice->id.'/pdf') }}">GST invoice</a>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="7" class="text-center text-muted">No payment receipts yet.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
@endsection
