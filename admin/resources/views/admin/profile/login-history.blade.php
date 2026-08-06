@extends('layouts.master')
@section('title') Login History @endsection

@section('css')
<style>
    .rb-profile-page .rb-page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #405189;
        margin-bottom: 1rem;
    }
    .rb-profile-card {
        border: 1px solid #e9ebec;
        border-radius: 0.4rem;
        box-shadow: none;
        overflow: hidden;
    }
    .rb-profile-card .card-header {
        background: #405189 !important;
        color: #fff !important;
        border: 0 !important;
        padding: 0.75rem 1rem;
    }
    .rb-profile-card .card-header .card-title {
        color: #fff !important;
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
    }
    .rb-profile-table thead th {
        background: #405189 !important;
        color: #fff !important;
        border-color: #364574 !important;
        font-weight: 600;
        font-size: 0.82rem;
        white-space: nowrap;
    }
    .rb-profile-table td {
        font-size: 0.8rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .rb-profile-table td small {
        white-space: normal;
        word-break: break-word;
        display: inline-block;
        max-width: 220px;
    }
</style>
@endsection

@section('content')
<div class="rb-profile-page">
    <h2 class="rb-page-title">Login History</h2>

    <div class="card rb-profile-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ri-history-line me-1"></i> Login History
            </h5>
            <a href="{{ route('myProfile') }}" class="btn btn-sm btn-light">
                <i class="ri-arrow-left-line me-1"></i> Back to Profile
            </a>
        </div>
        <div class="card-body p-3">
            <p class="text-muted small mb-3">Latest login sessions with device &amp; browser details</p>
            <div class="table-responsive">
                <table class="table table-bordered table-nowrap mb-0 rb-profile-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Mobile</th>
                            <th>IP Address</th>
                            <th>Device Type</th>
                            <th>Device</th>
                            <th>Browser</th>
                            <th>OS / Platform</th>
                            <th>Login Path</th>
                            <th>Date &amp; Time</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($login_history as $k => $v)
                            @php
                                $ua = $v['user_agent'] ?? '-';
                                $uaShort = strlen($ua) > 48 ? substr($ua, 0, 48) . '…' : $ua;
                            @endphp
                            <tr>
                                <td>{{ $k + 1 }}</td>
                                <td>{{ $v['user_name'] ?: '-' }}</td>
                                <td>{{ $v['mobile_number'] ?: '-' }}</td>
                                <td>{{ $v['ip_address'] ?: '-' }}</td>
                                <td>{{ $v['device_type'] ?: '-' }}</td>
                                <td>{{ $v['device'] ?: '-' }}</td>
                                <td>{{ $v['browser'] ?: '-' }}</td>
                                <td>{{ $v['platform'] ?: '-' }}</td>
                                <td>{{ $v['login_path'] ?: '-' }}</td>
                                <td>{{ $v['created_at'] ?: '-' }}</td>
                                <td title="{{ $ua }}"><small>{{ $uaShort }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No login history found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
