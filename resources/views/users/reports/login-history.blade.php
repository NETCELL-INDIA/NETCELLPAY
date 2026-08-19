@extends('layouts.master')
@section('title') Login History @endsection

@section('css')
<style>
    .lh-page-title { font-size: 1.35rem; font-weight: 700; color: #405189; margin-bottom: 1rem; }
    .lh-map-pin {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #e03131;
        font-size: 1.35rem;
        line-height: 1;
        text-decoration: none;
    }
    .lh-map-pin:hover { color: #c92a2a; }
    .lh-map-pin.is-disabled { color: #adb5bd; pointer-events: none; }
    .lh-search-btn { min-width: 110px; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="lh-page-title">Login History</h4>
    </div>
</div>

<div class="card">
    <div class="card-body py-3">
        <form method="get" action="{{ route('loginHistory') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-0">Mobile Number</label>
                <input type="text" name="mobile" class="form-control" maxlength="10" placeholder="Enter Mobile" value="{{ $mobile }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-success lh-search-btn">
                    <i class="ri-search-line me-1"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-nowrap mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>IP Address</th>
                        <th>Login By</th>
                        <th>Date Time</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th class="text-center">Location</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $row)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                            <td>{{ $row->display_name ?: '-' }}</td>
                            <td>{{ $row->mobile_number ?: '-' }}</td>
                            <td>{{ $row->ip_address ?: '-' }}</td>
                            <td>{{ $row->login_by }}</td>
                            <td>{{ $row->display_time }}</td>
                            <td>{{ $row->latitude ?: '-' }}</td>
                            <td>{{ $row->longitude ?: '-' }}</td>
                            <td class="text-center">
                                @if(!empty($row->maps_url))
                                    <a class="lh-map-pin" href="{{ $row->maps_url }}" target="_blank" rel="noopener noreferrer" title="Open map">
                                        <i class="ri-map-pin-2-fill"></i>
                                    </a>
                                @else
                                    <span class="lh-map-pin is-disabled" title="Location not available">
                                        <i class="ri-map-pin-2-fill"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No login history found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $logs->firstItem() ?: 0 }} to {{ $logs->lastItem() ?: 0 }} of {{ $logs->total() }} entries
            </div>
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
