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
@if(empty($schemaReady))
<div class="alert alert-warning">The campaign tables are not on this database yet. From the user site folder run <code>php artisan migrate --force --path=database/migrations/2026_11_26_120000_create_notification_campaigns.php</code></div>
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
    <div class="card-header"><h4 class="card-title mb-0">Campaigns</h4></div>
    <div class="card-body">
        <form method="get" action="{{ route('smartNotifications') }}" class="row gy-2 align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label mb-0" for="q">Title</label>
                <input class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    @foreach(['draft','scheduled','processing','completed','partially_failed','failed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" for="category">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All</option>
                    @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" for="target_type">Audience</label>
                <select class="form-select" id="target_type" name="target_type">
                    <option value="">All</option>
                    @foreach(['all' => 'All users', 'user' => 'Selected user', 'role' => 'Role'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['target_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" for="from">From</label>
                <input class="form-control" type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" for="to">To</label>
                <input class="form-control" type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" type="submit">Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th class="text-end">Recipients</th>
                        <th class="text-end">Success</th>
                        <th class="text-end">Failed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><a href="{{ route('smartNotificationShow', $row->id) }}">{{ $row->title }}</a></td>
                        <td>{{ $row->category }}</td>
                        <td>{{ $row->target_type }}</td>
                        <td>{{ $row->status }}</td>
                        <td class="text-end">{{ number_format((int) $row->total_recipients) }}</td>
                        <td class="text-end">{{ number_format((int) $row->success_count) }}</td>
                        <td class="text-end">{{ number_format((int) $row->failure_count) }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-primary" href="{{ route('smartNotificationEdit', $row->id) }}">Edit</a>
                            @if($row->status !== 'processing')
                            <form method="post" action="{{ route('smartNotificationDelete', $row->id) }}" class="d-inline" onsubmit="return confirm('Delete this notification from the admin list and the app inbox? An alert already shown on a phone cannot be recalled.');">
                                @csrf
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">No campaigns match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $campaigns->links() }}</div>
    </div>
</div>
@endsection
