@extends('layouts.master')
@section('title') Online Payments @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Payments @endslot
@slot('title') Online Payments @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">UPI and QR add money</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">This list only shows gateway payments. It does not credit or debit any wallet.</p>
                <form method="get" action="{{ url('admin/fund/online-payments') }}">
                    <div class="row gy-3">
                        <div class="col-lg-2">
                            <label class="form-label mb-0">From</label>
                            <input type="date" class="form-control" name="from_date" value="{{ $from }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">To</label>
                            <input type="date" class="form-control" name="to_date" value="{{ $to }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Status</label>
                            <select class="form-select" name="status">
                                @foreach(['All','Pending','Success','Failed'] as $item)
                                    <option value="{{ $item }}" {{ $status === $item ? 'selected' : '' }}>{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Order id</label>
                            <input type="text" class="form-control" name="order_id" value="{{ $orderId }}" maxlength="80">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Mobile</label>
                            <input type="text" class="form-control" name="mobile" value="{{ $mobile }}" maxlength="15">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
                <div class="row g-2 mt-3">
                    <div class="col-md-4"><div class="border border-warning text-warning rounded p-2"><b>Pending:</b> ₹{{ number_format((float) ($summary->pending_amt ?? 0), 2) }} ({{ (int) ($summary->pending_cnt ?? 0) }})</div></div>
                    <div class="col-md-4"><div class="border border-success text-success rounded p-2"><b>Success:</b> ₹{{ number_format((float) ($summary->success_amt ?? 0), 2) }} ({{ (int) ($summary->success_cnt ?? 0) }})</div></div>
                    <div class="col-md-4"><div class="border border-danger text-danger rounded p-2"><b>Failed:</b> ₹{{ number_format((float) ($summary->failed_amt ?? 0), 2) }} ({{ (int) ($summary->failed_cnt ?? 0) }})</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Order</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                @php
                                    $label = $row->status === 'Transferred' ? 'Success' : ($row->status === 'Rejected' ? 'Failed' : $row->status);
                                    $tone = $label === 'Success' ? 'success' : ($label === 'Failed' ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td>{{ $row->created_at }}</td>
                                    <td>
                                        {{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) }}<br>
                                        {{ $row->outlet_name }}<br>
                                        {{ $row->mobile_number }}
                                    </td>
                                    <td>{{ $row->order_id }}</td>
                                    <td>{{ (int) $row->upi === 2 ? 'QR' : 'UPI' }}</td>
                                    <td>₹{{ number_format((float) $row->amount, 2) }}</td>
                                    <td><span class="badge bg-{{ $tone }}">{{ $label }}</span></td>
                                    <td>{{ $row->decision_remark ?: $row->remark }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No online payments in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2 mb-0">Showing the latest 200 rows.</p>
            </div>
        </div>
    </div>
</div>
@endsection
