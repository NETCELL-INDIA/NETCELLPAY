@extends('layouts.master')
@section('title') Billing Dashboard @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Billing @endslot
@slot('title') Billing Dashboard @endslot
@endcomponent
@if(session('billing_message'))<div class="alert alert-info">{{ session('billing_message') }}</div>@endif
<div class="card mb-3"><div class="card-body">
<form method="get" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
    <div class="col-md-2"><button class="btn btn-primary">Search</button></div>
</form>
<p class="text-muted mt-3 mb-0">Wallet top-up amounts are not treated as GST turnover. GST totals below are only the service-fee invoices.</p>
</div></div>
<div class="row g-3">
    @foreach([
        'Add money transactions' => $stats['total_cnt'],
        'Successful payments' => $stats['success_cnt'],
        'Failed payments' => $stats['failed_cnt'],
        'Pending payments' => $stats['pending_cnt'],
        'Payment amount' => '₹'.number_format($stats['paid'], 2),
        'Receipts generated' => $stats['receipts'],
        'GST taxable value' => '₹'.number_format($stats['taxable'], 2),
        'CGST' => '₹'.number_format($stats['cgst'], 2),
        'SGST' => '₹'.number_format($stats['sgst'], 2),
        'IGST' => '₹'.number_format($stats['igst'], 2),
        'Total GST' => '₹'.number_format($stats['gst'], 2),
        'Credit notes' => '₹'.number_format($stats['credits'], 2),
    ] as $label => $value)
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ $label }}</div><h4 class="mb-0">{{ $value }}</h4></div></div></div>
    @endforeach
</div>
<form method="post" action="{{ url('admin/billing/receipts/backfill') }}" class="mt-3">
    @csrf
    <button class="btn btn-outline-secondary">Create receipts for past successful payments</button>
</form>
@endsection
