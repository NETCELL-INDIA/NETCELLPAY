@extends('layouts.master')
@section('title') Consent history @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') Promotional consent history @endslot
@endcomponent

<div class="card" style="border-top: 3px solid #175CD3;">
    <div class="card-header"><h4 class="card-title mb-0">Consent changes</h4></div>
    <div class="card-body">
        <p class="text-muted">This page only shows changes the user made. It cannot turn consent on or off.</p>
        @if($logs === null)
        <div class="alert alert-warning mb-0">The consent history table is not on this database yet.</div>
        @else
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label mb-0" for="user_id">User ID</label>
                <input class="form-control" id="user_id" name="user_id" value="{{ $filters['user_id'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" for="name">Name</label>
                <input class="form-control" id="name" name="name" value="{{ $filters['name'] ?? '' }}">
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
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Previous</th>
                        <th>New</th>
                        <th>Action</th>
                        <th>Source</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $row)
                    <tr>
                        <td>{{ $row->user_id }}</td>
                        <td>{{ $row->first_name ?: '-' }}</td>
                        <td>{{ $row->consent_type }}</td>
                        <td>{{ (int) $row->previous_value === 1 ? 'On' : 'Off' }}</td>
                        <td>{{ (int) $row->new_value === 1 ? 'On' : 'Off' }}</td>
                        <td>{{ $row->action }}</td>
                        <td>{{ $row->source }}</td>
                        <td>{{ $row->created_at }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No consent changes match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
