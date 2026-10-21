@extends('layouts.master')
@section('title') {{ $title }} @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">{{ $title }}</h4></div>
    <div class="card-body">
        @if($kind === 'aeps')
            <div class="alert alert-info">AEPS cash withdrawal runs only after an NPCI/bank supplier API is configured. This report lists AEPS transactions already stored. No biometric request is sent from this page.</div>
        @else
            <div class="alert alert-info">DMT sends money only through a configured money-transfer supplier API. This report lists existing Money Transfer / DMT rows. It does not start a new transfer.</div>
        @endif
        <table class="table table-bordered table-sm">
            <thead><tr><th>Date</th><th>User</th><th>Type</th><th>Number</th><th>Amount</th><th>Status</th><th>Order</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->created_at }}</td>
                    <td>{{ $row->user_id }}</td>
                    <td>{{ $row->transaction_type }}</td>
                    <td>{{ $row->number }}</td>
                    <td>{{ $row->amount }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->order_id }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
