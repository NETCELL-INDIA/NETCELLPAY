@extends('layouts.master')

@section('title')
{{ $title }}
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ $title }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">{{ $section ?? 'Menu' }}</li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 text-white">Filters</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" value="{{ date('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" value="{{ date('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" placeholder="Keyword..." disabled>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success me-1" disabled>
                            <i class="ri-search-line align-bottom me-1"></i> Search
                        </button>
                        <button type="button" class="btn btn-secondary" disabled>Reset</button>
                    </div>
                </div>
                @if(!empty($description))
                    <p class="text-muted mt-3 mb-0 small">{{ $description }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- List --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 text-white">List</h5>
            </div>
            <div class="card-body">
                <div class="rb-list-toolbar">
                    <div>
                        Show
                        <select class="form-select form-select-sm d-inline-block mx-1" style="width:70px" disabled>
                            <option>100</option>
                            <option>50</option>
                            <option>25</option>
                        </select>
                        entries
                    </div>
                    <div>
                        <button type="button" class="btn btn-info btn-sm" disabled>
                            <i class="ri-download-2-line align-bottom me-1"></i> Download
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7">
                                    <div class="rb-empty-state">
                                        <i class="ri-inbox-2-line d-block"></i>
                                        <div class="fw-semibold text-dark mb-1">{{ $title }}</div>
                                        <div>Menu ready in Rambhiya layout. Full data &amp; actions will be wired next for this module.</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
