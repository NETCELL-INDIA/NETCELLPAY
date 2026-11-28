@extends('layouts.master')
@section('title') {{ $title }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Support Management @endslot
@slot('title') {{ $title }} @endslot
@endcomponent
@include('admin.support.complaint-filters', ['action' => url()->current()])
@if(empty($ready))
<div class="alert alert-warning">Complaint tables are not ready on this database.</div>
@else
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Operator</th>
                        <th>Transaction</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td><a href="{{ route('complaintDeskShow', $row->id) }}">{{ $row->request_id }}</a></td>
                        <td>{{ $row->created_at }}</td>
                        <td>{{ $row->outlet_name ?: ($row->first_name ?: 'Not recorded') }}<br><span class="text-muted">{{ $row->mobile_number ?: '' }}</span></td>
                        <td>{{ $row->subject }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->provider_name ?: 'Not recorded' }}</td>
                        <td>{{ $row->txn_status ?: 'Not recorded' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No complaints match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div>
</div>
@endif
@endsection
