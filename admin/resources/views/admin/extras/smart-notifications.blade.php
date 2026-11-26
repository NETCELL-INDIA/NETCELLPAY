@extends('layouts.master')
@section('title') Smart Notifications @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') Smart Notifications @endslot
@endcomponent

@if(session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('smartNotificationCreate') }}" class="btn btn-primary">New campaign</a>
    <a href="{{ url('admin/users/send-message') }}" class="btn btn-outline-secondary">Existing SMS / Notification</a>
    <a href="{{ route('notificationSendReport') }}" class="btn btn-outline-secondary">Existing send report</a>
</div>

<div class="row">
    @foreach([
        ['total_campaigns', 'Total Campaigns', $stats['total_campaigns'], '#175CD3'],
        ['draft_campaigns', 'Draft Campaigns', $stats['draft_campaigns'], '#175CD3'],
        ['processing_campaigns', 'Processing Campaigns', $stats['processing_campaigns'], '#6D28D9'],
        ['completed_campaigns', 'Completed Campaigns', $stats['completed_campaigns'], '#175CD3'],
        ['failed_campaigns', 'Failed Campaigns', $stats['failed_campaigns'], '#6D28D9'],
        ['scheduled_campaigns', 'Scheduled Campaigns', $stats['scheduled_campaigns'], '#6D28D9'],
        ['total_recipients', 'Total Recipients', $stats['total_recipients'], '#175CD3'],
        ['successful_deliveries', 'Successful Deliveries', $stats['successful_deliveries'], '#175CD3'],
        ['failed_deliveries', 'Failed Deliveries', $stats['failed_deliveries'], '#6D28D9'],
    ] as $card)
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card" style="border-top: 3px solid {{ $card[3] }};">
            <div class="card-body">
                <p class="text-muted mb-1">{{ $card[1] }}</p>
                <h4 class="mb-0" data-stat="{{ $card[0] }}">{{ number_format($card[2]) }}</h4>
                @if($card[0] === 'failed_campaigns')
                <p class="mb-0 text-muted">Partially failed: {{ number_format($stats['partially_failed_campaigns']) }}</p>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Latest campaigns</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="text-end">Recipients</th>
                        <th class="text-end">Success</th>
                        <th class="text-end">Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->category }}</td>
                        <td>{{ $row->status }}</td>
                        <td class="text-end">{{ number_format((int) $row->total_recipients) }}</td>
                        <td class="text-end">{{ number_format((int) $row->success_count) }}</td>
                        <td class="text-end">{{ number_format((int) $row->failure_count) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
