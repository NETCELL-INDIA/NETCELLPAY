@extends('layouts.master')
@section('title') GST Tax Invoices @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Billing @endslot
@slot('title') GST Tax Invoices @endslot
@endcomponent
<div class="card"><div class="card-body">
<form method="get" class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
    <div class="col-md-2"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ request('name') }}"></div>
    <div class="col-md-2"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="{{ request('mobile') }}"></div>
    <div class="col-md-2"><label class="form-label">Invoice no</label><input name="number" class="form-control" value="{{ request('number') }}"></div>
    <div class="col-md-2"><button class="btn btn-primary">Search</button></div>
</form>
<div class="table-responsive mt-3"><table class="table table-sm table-bordered">
<thead><tr><th>Invoice</th><th>Customer</th><th>GSTIN</th><th>Taxable</th><th>GST</th><th>Total</th><th>Date</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
    <td>{{ $row->invoice_number }}</td>
    <td>{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) }}<br>{{ $row->mobile_number }}</td>
    <td>{{ $row->customer_gstin ?: '-' }}</td>
    <td>₹{{ number_format((float) $row->taxable_amount, 2) }}</td>
    <td>₹{{ number_format((float) $row->total_gst, 2) }}</td>
    <td>₹{{ number_format((float) $row->grand_total, 2) }}</td>
    <td>{{ $row->invoice_date }}</td>
    <td>{{ $row->status }}</td>
    <td>
        <a href="{{ url('admin/billing/invoices/'.$row->id.'/pdf') }}">PDF</a>
        @if($row->share_token)
            <a target="_blank" href="{{ request()->getSchemeAndHttpHost().'/billing/document/'.$row->share_token }}">View</a>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-muted">No GST invoices. A wallet top-up does not create one.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
@endsection
