@extends('layouts.master')

@section('title') Pending Report @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container { width: 100% !important; }
    .btn-icon { width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="mb-0">Pending Report</h4>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <h6 class="mb-0 text-white">Filters</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-0">From Date</label>
                <input type="date" class="form-control" id="from_date" value="" placeholder="All days">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">To Date</label>
                <input type="date" class="form-control" id="to_date" value="" placeholder="All days">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">API</label>
                <select class="form-select" id="api_id">
                    <option value="">ALL APIS</option>
                    @foreach($apis as $a)
                        <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0">User / Client</label>
                <select class="form-select" id="user_id">
                    <option value=""></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">Operator</label>
                <select class="form-select" id="provider_id">
                    <option value="">Select Operator</option>
                    @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->provider_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">Number / Ref No</label>
                <input type="text" class="form-control" id="search_text" placeholder="Number / Ref No">
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-success btn-icon" id="btnSearch" title="Search">
                    <i class="ri-search-line"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <h6 class="mb-0 text-white">List of Transactions</h6>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <select class="form-select" id="rehit_api_id" style="width:auto;min-width:180px;">
                    <option value="">Select Rehit API</option>
                    @foreach($apis as $a)
                        <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary" id="btnRehit">Rehit</button>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Show</span>
                    <select class="form-select form-select-sm" id="show" style="width:auto;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-muted small">entries</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" id="btnBulkSuccess">BULK SUCCESS</button>
                <button type="button" class="btn btn-danger" id="btnBulkFailure">BULK FAILURE</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                        <th>RECHARGE ID</th>
                        <th>DATE &amp; TIME</th>
                        <th>USER DETAILS</th>
                        <th>OPERATOR</th>
                        <th>NUMBER</th>
                        <th>MRP</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                        <th>API</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="pendingBody">
                    <tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="pageInfo" class="text-muted">Showing 0 to 0 of 0 entries</div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary">
                <h6 class="mb-0 text-white">API Wise Statistics</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>SR.NO</th>
                                <th>API</th>
                                <th>DETAILS</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="apiStatsBody">
                            <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary">
                <h6 class="mb-0 text-white">Operator Wise Statistics</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>SR.NO</th>
                                <th>OPERATOR</th>
                                <th>PENDING</th>
                            </tr>
                        </thead>
                        <tbody id="operatorStatsBody">
                            <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var csrf = '{{ csrf_token() }}';
var currentPage = 1;
var lastPage = 1;

function filterPayload() {
    return {
        _token: csrf,
        show: $('#show').val(),
        page: currentPage,
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        api_id: $('#api_id').val(),
        user_id: $('#user_id').val(),
        provider_id: $('#provider_id').val(),
        search_text: $('#search_text').val()
    };
}

function selectedIds() {
    return $('.row-check:checked').map(function () { return $(this).val(); }).get();
}

function fetchPending() {
    $('#pendingBody').html('<tr><td colspan="11" class="text-center text-muted py-4">Loading...</td></tr>');
    $.ajax({
        url: '{{ route("pendingReportList") }}',
        method: 'POST',
        dataType: 'json',
        data: filterPayload(),
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#pendingBody').html('<tr><td colspan="11" class="text-center text-danger">Failed to load</td></tr>');
                return;
            }
            $('#pendingBody').html(res.rows);
            $('#apiStatsBody').html(res.api_stats || '');
            $('#operatorStatsBody').html(res.operator_stats || '');
            $('#checkAll').prop('checked', false);
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#pendingBody').html('<tr><td colspan="11" class="text-center text-danger">Failed to load</td></tr>');
        }
    });
}

function bulkStatus(status) {
    var ids = selectedIds();
    if (!ids.length) {
        alert('Select at least one transaction');
        return;
    }
    if (!confirm('Mark ' + ids.length + ' transaction(s) as ' + status + '?')) return;
    $.ajax({
        url: '{{ route("pendingReportBulkStatus") }}',
        method: 'POST',
        dataType: 'json',
        data: { _token: csrf, ids: ids, status: status },
        success: function (res) {
            alert(res.message || (res.type === 'success' ? 'Done' : 'Failed'));
            if (res.type === 'success') fetchPending();
        }
    });
}

$(function () {
    $('#user_id').select2({
        placeholder: 'Search user by firm name, mobile, email, id.',
        allowClear: true,
        ajax: {
            url: '{{ route("generalRoutingsSearchUsers") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) {
                return { results: (data && data.results) ? data.results : [] };
            }
        }
    });

    $('#btnSearch').on('click', function () { currentPage = 1; fetchPending(); });
    $('#show').on('change', function () { currentPage = 1; fetchPending(); });
    $('#btnPrev').on('click', function () { if (currentPage > 1) { currentPage--; fetchPending(); } });
    $('#btnNext').on('click', function () { if (currentPage < lastPage) { currentPage++; fetchPending(); } });
    $('#btnBulkSuccess').on('click', function () { bulkStatus('Success'); });
    $('#btnBulkFailure').on('click', function () { bulkStatus('Failed'); });

    $('#checkAll').on('change', function () {
        $('.row-check').prop('checked', $(this).is(':checked'));
    });

    $('#btnRehit').on('click', function () {
        var ids = selectedIds();
        var apiId = $('#rehit_api_id').val();
        if (!ids.length) { alert('Select at least one transaction'); return; }
        if (!apiId) { alert('Select Rehit API'); return; }
        $.ajax({
            url: '{{ route("pendingReportRehit") }}',
            method: 'POST',
            dataType: 'json',
            data: { _token: csrf, ids: ids, rehit_api_id: apiId },
            success: function (res) {
                alert(res.message || 'Done');
                if (res.type === 'success') fetchPending();
            }
        });
    });

    $(document).on('click', '.btn-resend', function () {
        var id = $(this).data('id');
        var $btn = $(this);
        if (!confirm('Resend this recharge to its API?')) return;
        $btn.prop('disabled', true).text('...');
        $.ajax({
            url: '{{ route("pendingReportResend") }}',
            method: 'POST',
            dataType: 'json',
            data: { _token: csrf, id: id },
            success: function (res) {
                alert(res.message || (res.type === 'success' ? 'Done' : 'Failed'));
                if (res.type === 'success') fetchPending();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed';
                alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text('Resend');
            }
        });
    });

    $(document).on('click', '.btn-mark', function () {
        var id = $(this).data('id');
        var status = $(this).data('status');
        if (!confirm('Mark as ' + status + '?')) return;
        $.ajax({
            url: '{{ route("pendingReportBulkStatus") }}',
            method: 'POST',
            dataType: 'json',
            data: { _token: csrf, ids: [id], status: status },
            success: function (res) {
                if (res.type === 'success') fetchPending();
                else alert(res.message || 'Failed');
            }
        });
    });

    fetchPending();
});
</script>
@endsection
