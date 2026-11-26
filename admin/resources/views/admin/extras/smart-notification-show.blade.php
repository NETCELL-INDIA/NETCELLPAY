@extends('layouts.master')
@section('title') Campaign {{ $campaign->id }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') Campaign {{ $campaign->id }} @endslot
@endcomponent

@if(session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('smartNotifications') }}" class="btn btn-outline-secondary">Back</a>
    @if(in_array($campaign->status, ['draft', 'scheduled'], true))
    <form method="post" action="{{ route('smartNotificationCancel', $campaign->id) }}">
        @csrf
        <button class="btn btn-danger" type="submit">Cancel schedule</button>
    </form>
    @endif
    @if(in_array($campaign->status, ['failed', 'partially_failed'], true))
    <form method="post" action="{{ route('smartNotificationRetry', $campaign->id) }}">
        @csrf
        <button class="btn btn-primary" type="submit">Retry failed recipients</button>
    </form>
    @endif
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border-top: 3px solid #175CD3;">
            <div class="card-body">
                <h4 class="mb-1">{{ $campaign->title }}</h4>
                <p class="mb-3">{{ $campaign->message }}</p>
                <div class="row gy-2">
                    <div class="col-md-4"><span class="text-muted">Status</span><div>{{ $campaign->status }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Category</span><div>{{ $campaign->category }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Audience</span><div>{{ $audience }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Scheduled</span><div>{{ $campaign->scheduled_at ?: '-' }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Created</span><div>{{ $campaign->created_at }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Created by</span><div>{{ trim((string) $campaign->created_by_name) !== '' ? $campaign->created_by_name : 'Admin '.$campaign->created_by }}</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card" style="border-top: 3px solid #6D28D9;">
            <div class="card-body">
                <p class="mb-1">Recipients {{ number_format((int) $campaign->total_recipients) }}</p>
                <p class="mb-1">Sent {{ number_format((int) $campaign->success_count) }}</p>
                <p class="mb-1">Failed {{ number_format((int) $campaign->failure_count) }}</p>
                <p class="mb-0">Skipped {{ number_format((int) $campaign->skipped_count) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Recipients</h4></div>
    <div class="card-body">
        <p class="text-muted">Sent notifications cannot be recalled. Retry sends only to people whose delivery failed or is still pending.</p>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Detail</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipients as $row)
                    <tr>
                        <td>{{ $row->first_name ?: ('User '.$row->to_user_id) }}</td>
                        <td>{{ $row->mobile_number }}</td>
                        <td>
                            @if((int) $row->status === 1) Sent
                            @elseif((int) $row->status === 2) No token
                            @elseif((int) $row->status === 3) Failed
                            @else Pending
                            @endif
                        </td>
                        <td>{{ $row->delivery_error }}</td>
                        <td>{{ $row->updated_at }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No delivery rows yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $recipients->links() }}</div>
    </div>
</div>
@endsection
