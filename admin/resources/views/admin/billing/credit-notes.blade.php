@extends('layouts.master')
@section('title') Credit Notes @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Billing @endslot
@slot('title') Credit Notes @endslot
@endcomponent
@if(session('billing_message'))<div class="alert alert-info">{{ session('billing_message') }}</div>@endif
<div class="alert alert-warning">A credit note records the document only. It does not change the wallet. Use Fund Credit / Debit if the balance must move.</div>
<div class="card mb-3"><div class="card-body">
<form method="post" action="{{ url('admin/billing/credit-notes') }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-md-4"><label class="form-label">Invoice</label>
        <select name="invoice_id" class="form-select" required>
            <option value="">Select</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} — ₹{{ number_format((float) $invoice->grand_total, 2) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Reason</label><input name="reason" class="form-control" maxlength="255" required></div>
    <div class="col-md-2"><button class="btn btn-primary">Issue credit note</button></div>
</form>
</div></div>
<div class="card"><div class="card-body table-responsive">
<table class="table table-sm table-bordered">
<thead><tr><th>Note</th><th>Invoice</th><th>Customer</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
<tbody>
@forelse($notes as $note)
<tr>
    <td>{{ $note->note_number }}</td>
    <td>{{ $note->invoice_number }}</td>
    <td>{{ $note->first_name }}<br>{{ $note->mobile_number }}</td>
    <td>₹{{ number_format((float) $note->amount, 2) }}</td>
    <td>{{ $note->reason }}</td>
    <td>{{ $note->created_at }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted text-center">No credit notes.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
