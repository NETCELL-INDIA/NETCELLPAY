@extends('layouts.master')
@section('title') Payment Receipts @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Billing @endslot
@slot('title') Payment Receipts @endslot
@endcomponent
@if(session('billing_message'))<div class="alert alert-info">{{ session('billing_message') }}</div>@endif
<div class="card"><div class="card-body">
<form method="get" class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
    <div class="col-md-2"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ request('name') }}"></div>
    <div class="col-md-2"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="{{ request('mobile') }}"></div>
    <div class="col-md-2"><label class="form-label">Receipt no</label><input name="number" class="form-control" value="{{ request('number') }}"></div>
    <div class="col-md-2"><label class="form-label">Transaction</label><input name="transaction_id" class="form-control" value="{{ request('transaction_id') }}"></div>
    <div class="col-md-2"><label class="form-label">Method</label>
        <select name="method" class="form-select"><option value="">All</option><option value="UPI" {{ request('method')==='UPI'?'selected':'' }}>UPI</option><option value="QR" {{ request('method')==='QR'?'selected':'' }}>QR</option></select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary">Search</button></div>
</form>
<div class="table-responsive mt-3"><table class="table table-sm table-bordered">
<thead><tr><th>Receipt</th><th>Customer</th><th>Mobile</th><th>Transaction</th><th>Method</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
    <td>{{ $row->receipt_number }}</td>
    <td>{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) }}</td>
    <td>{{ $row->mobile_number }}</td>
    <td>{{ $row->order_id }}</td>
    <td>{{ $row->payment_method }}</td>
    <td>₹{{ number_format((float) $row->amount, 2) }}</td>
    <td>{{ $row->receipt_date }}</td>
    <td>{{ $row->status }}</td>
    <td>
        <a href="{{ url('admin/billing/receipts/'.$row->id.'/pdf') }}">PDF</a>
        @if($row->share_token)
            <a target="_blank" href="{{ request()->getSchemeAndHttpHost().'/billing/document/'.$row->share_token }}">View</a>
        @endif
        <form method="post" action="{{ url('admin/billing/receipts/'.$row->id.'/resend') }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0">Resend</button></form>
    </td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-muted">No receipts in this range.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
@endsection
