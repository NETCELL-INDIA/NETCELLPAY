@extends('layouts.master')
@section('title') Complaint Dashboard @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Support Management @endslot
@slot('title') Complaint Dashboard @endslot
@endcomponent
@include('admin.support.complaint-filters', ['action' => route('complaintDeskDashboard')])
@if(empty($ready))
<div class="alert alert-warning">Complaint tables are not on this database yet. Run the local migration <code>2026_11_28_120000_add_complaint_management.php</code> from the user site. Do not run it on production from this PC.</div>
@else
<div class="row">
    @foreach([
        ['total', 'Total Complaints', $counts['total'], '#175CD3'],
        ['new', 'New Complaints', $counts['new'], '#175CD3'],
        ['under_review', 'Under Review', $counts['under_review'], '#6D28D9'],
        ['in_progress', 'In Progress', $counts['in_progress'], '#175CD3'],
        ['waiting', 'Waiting for Customer', $counts['waiting'], '#6D28D9'],
        ['resolved', 'Resolved', $counts['resolved'], '#175CD3'],
        ['rejected', 'Rejected', $counts['rejected'], '#6D28D9'],
        ['closed', 'Closed', $counts['closed'], '#175CD3'],
        ['assigned', 'Assigned', $counts['assigned'], '#6D28D9'],
    ] as $card)
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card" style="border-top: 3px solid {{ $card[3] }};">
            <div class="card-body">
                <p class="text-muted mb-1">{{ $card[1] }}</p>
                <h4 class="mb-0" data-stat="{{ $card[0] }}">{{ number_format($card[2]) }}</h4>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
