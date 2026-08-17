@extends('layouts.master')

@php $manualOnly = !empty($manualOnly); @endphp
@section('title') {{ $manualOnly ? 'Manual Recharge Report' : 'Recharge Report' }} @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="recharge-report-page">

@component('components.breadcrumb')
@slot('li_1') Reports @endslot
@slot('title') {{ $manualOnly ? 'Manual Recharge Report' : 'Recharge Report' }} @endslot
@endcomponent

<div class="card recharge-filter-card">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Filters</h4>
        <span class="recharge-filter-hint">Confirm dates, then Search</span>
    </div>
    <div class="card-body">
        <form id="rechargeFilterForm" onsubmit="return false;">
            <div class="recharge-filter-grid recharge-filter-grid--row1">
                <div class="recharge-filter-field recharge-filter-field--xs">
                    <label class="form-label" for="show">Show</label>
                    <select class="form-select form-select-sm" id="show">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="from_date">From</label>
                    <input type="date" class="form-control form-control-sm" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="to_date">To</label>
                    <input type="date" class="form-control form-control-sm" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="api_id">API</label>
                    <select class="form-select form-select-sm" id="api_id">
                        <option value="">All APIs</option>
                        @foreach($apis as $a)
                            <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="recharge-filter-field recharge-filter-field--user">
                    <div class="recharge-filter-label-row">
                        <label class="form-label" for="user_id">User / Client</label>
                        <label class="recharge-child-check" for="include_child">
                            <input class="form-check-input" type="checkbox" id="include_child" value="1">
                            <span>Include child</span>
                        </label>
                    </div>
                    <select class="form-select form-select-sm" id="user_id">
                        <option value="">Search user...</option>
                    </select>
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="service_id">Service</label>
                    <select class="form-select form-select-sm" id="service_id">
                        <option value="">All</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}">{{ strtoupper($s->service_name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="provider_id">Operator</label>
                    <select class="form-select form-select-sm" id="provider_id">
                        <option value="">All</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}" data-service="{{ $p->service_id }}">{{ $p->provider_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="circle_id">Circle</label>
                    <select class="form-select form-select-sm" id="circle_id">
                        <option value="">All</option>
                        @foreach($circles as $c)
                            <option value="{{ $c->id }}">{{ $c->state_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="recharge-filter-grid recharge-filter-grid--row2">
                <input type="hidden" id="status" value="">
                <div class="recharge-filter-field recharge-filter-field--search">
                    <label class="form-label" for="search_text">Search</label>
                    <input type="text" class="form-control form-control-sm" id="search_text" placeholder="Number / Ref / Operator ID / Client ID">
                </div>
                <div class="recharge-filter-field recharge-filter-field--xs">
                    <label class="form-label" for="amount">Amount</label>
                    <input type="text" class="form-control form-control-sm" id="amount" placeholder="Amt">
                </div>
                <div class="recharge-filter-field">
                    <label class="form-label" for="mode">Mode</label>
                    <select class="form-select form-select-sm" id="mode" @if($manualOnly) disabled @endif>
                        @if($manualOnly)
                            <option value="Manual" selected>MANUAL</option>
                        @else
                            <option value="">All</option>
                            <option value="WEB">WEB</option>
                            <option value="APP">APP</option>
                            <option value="API">API</option>
                            <option value="Manual">MANUAL</option>
                            <option value="Credit">Credit</option>
                            <option value="Debit">Debit</option>
                        @endif
                    </select>
                </div>
                <div class="recharge-filter-field recharge-filter-field--xs">
                    <label class="form-label" for="tbl_type">Type</label>
                    <select class="form-select form-select-sm" id="tbl_type">
                        <option value="0" selected>Current</option>
                        <option value="1">Backup</option>
                    </select>
                </div>
                <div class="recharge-filter-field recharge-filter-field--actions">
                    <label class="form-label">&nbsp;</label>
                    <div class="recharge-filter-actions">
                        <button type="button" class="btn btn-primary" id="btnSearch">
                            <i class="ri-search-line"></i> Search
                        </button>
                        <button type="button" class="btn btn-success" id="btnDownload">
                            <i class="ri-download-line"></i> CSV
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card recharge-list-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">{{ $manualOnly ? 'Manual Transactions' : 'Transactions' }}</h4>
    </div>
    <div class="card-body py-2 px-3">
        <div class="recharge-summary" id="summaryPills">
            <span class="recharge-summary__item recharge-summary__item--success">SUCCESS: 0.00 (0)</span>
            <span class="recharge-summary__item recharge-summary__item--pending">PENDING: 0.00 (0)</span>
            <span class="recharge-summary__item recharge-summary__item--failure">FAILURE: 0.00 (0)</span>
            <span class="recharge-summary__item recharge-summary__item--refunded">REFUNDED: 0.00 (0)</span>
        </div>

        <div class="table-responsive recharge-list-table-wrap">
            <table class="table table-sm table-hover recharge-list-table mb-0" id="rechargeTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Operator</th>
                        <th>Circle</th>
                        <th>Number</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>API</th>
                        <th>IDs</th>
                        <th>Mode</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="rechargeBody">
                    <tr><td colspan="12" class="text-center text-muted py-3">No data available</td></tr>
                </tbody>
            </table>
        </div>

        <div class="recharge-list-footer">
            <span id="pageInfo" class="recharge-list-count">Showing 0 to 0 of 0 entries</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="btnPrev">Prev</button>
                <button type="button" class="btn btn-outline-secondary" id="btnNext">Next</button>
            </div>
        </div>
    </div>
</div>

</div>

{{-- Complaint Modal --}}
<div id="complaintModal" class="modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="complaintModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cs_id">
                <table class="table table-bordered border-secondary table-nowrap">
                    <tbody>
                        <tr><th>Request ID :</th><td><span class="text-info" id="cs_request_id"></span></td></tr>
                        <tr><th>Date & Time :</th><td><span class="text-info" id="cs_created_at"></span></td></tr>
                        <tr><th>Status :</th><td><span class="text-info" id="cs_status"></span></td></tr>
                        <tr><th>Subject :</th><td><span class="text-info" id="cs_subject"></span></td></tr>
                    </tbody>
                </table>
                <div class="mb-3">
                    <label>Remark : <span class="text-danger">*</span></label>
                    <textarea id="cs_remark" class="form-control" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Status : <span class="text-danger">*</span></label>
                    <select class="form-select" id="csu_status">
                        <option value="">Select Status</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Sloved">Sloved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="complaint_now_btn" onclick="complaintSubmit()">Submit</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Status Modal --}}
<div id="editStatusModal" class="modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="es_id">
                <div class="mb-3">
                    <label>Operator Id : <span class="text-danger">*</span></label>
                    <input type="text" id="es_operator_id" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Status : <span class="text-danger">*</span></label>
                    <select class="form-select" id="es_status"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editStatus_now_btn" onclick="editStatusSubmit()">Submit</button>
            </div>
        </div>
    </div>
</div>

{{-- API Log Modal --}}
<div id="checkApiLogModal" class="modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkApiLogModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="api_log_data"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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

function filterPayload(extra) {
    return Object.assign({
        _token: csrf,
        show: $('#show').val(),
        page: currentPage,
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        api_id: $('#api_id').val(),
        user_id: $('#user_id').val(),
        include_child: $('#include_child').is(':checked') ? 1 : 0,
        service_id: $('#service_id').val(),
        provider_id: $('#provider_id').val(),
        circle_id: $('#circle_id').val(),
        status: $('#status').val(),
        search_text: $('#search_text').val(),
        amount: $('#amount').val(),
        mode: $('#mode').val(),
        manual_only: {{ $manualOnly ? '1' : '0' }},
        tbl_type: $('#tbl_type').val()
    }, extra || {});
}

function renderSummary(s) {
    $('#summaryPills').html(
        '<span class="recharge-summary__item recharge-summary__item--success">SUCCESS: ' + s.success_amt + ' (' + s.success_cnt + ')</span>' +
        '<span class="recharge-summary__item recharge-summary__item--pending">PENDING: ' + s.pending_amt + ' (' + s.pending_cnt + ')</span>' +
        '<span class="recharge-summary__item recharge-summary__item--failure">FAILURE: ' + s.failure_amt + ' (' + s.failure_cnt + ')</span>' +
        '<span class="recharge-summary__item recharge-summary__item--refunded">REFUNDED: ' + s.refunded_amt + ' (' + s.refunded_cnt + ')</span>'
    );
}

function fetchAllSearch() {
    $('#rechargeBody').html('<tr><td colspan="12" class="text-center text-muted py-4">Loading...</td></tr>');
    $.ajax({
        url: '{{ route("rechargeReportsListModern") }}',
        method: 'POST',
        dataType: 'json',
        data: filterPayload(),
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#rechargeBody').html('<tr><td colspan="12" class="text-center text-danger">Failed to load</td></tr>');
                return;
            }
            $('#rechargeBody').html(res.rows);
            renderSummary(res.summary || {});
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#rechargeBody').html('<tr><td colspan="12" class="text-center text-danger">Failed to load</td></tr>');
        }
    });
}

function downloadCsv() {
    var form = $('<form>', { method: 'POST', action: '{{ route("rechargeReportsDownloadModern") }}' });
    var data = filterPayload();
    $.each(data, function (k, v) {
        form.append($('<input>', { type: 'hidden', name: k, value: v }));
    });
    $('body').append(form);
    form.submit();
    form.remove();
}

function editStatus(id, status, operator_id) {
    $('#es_id').val(id);
    $('#es_operator_id').val(operator_id || '');
    var current = status || '';
    if (current === 'Failure') current = 'Failed';
    if (current === 'Under Process' || current === 'Under Proces') current = 'Pending';
    var opts = [
        { v: 'Pending', t: 'PENDING' },
        { v: 'Success', t: 'SUCCESS' },
        { v: 'Failed', t: 'FAILURE' }
    ];
    var html = '';
    opts.forEach(function (o) {
        html += '<option value="' + o.v + '"' + (current === o.v ? ' selected' : '') + '>' + o.t + '</option>';
    });
    $('#es_status').html(html);
    $('#editStatusModalLabel').text('Edit Status');
    $('#editStatusModal').modal('show');
}

function editStatusSubmit() {
    var id = $('#es_id').val();
    var operator_id = $('#es_operator_id').val();
    var status = $('#es_status').val();
    if (!status) {
        if (typeof Error_Msg === 'function') Error_Msg('Error', 'Please select status.', 'error');
        return;
    }
    $('#editStatus_now_btn').text('Please wait...').prop('disabled', true);
    $.ajax({
        url: '{{ route("updateStatus") }}',
        method: 'post',
        data: { id: id, operator_id: operator_id, status: status, _token: csrf },
        success: function (data) {
            $('#editStatus_now_btn').text('Submit').prop('disabled', false);
            if (data.type == 'success') {
                $('#editStatusModal').modal('hide');
                if (typeof Error_Msg === 'function') Error_Msg('Success', data.message, 'success');
                fetchAllSearch();
            } else if (typeof Error_Msg === 'function') {
                Error_Msg('Error', data.message || 'Failed', 'error');
            }
        },
        error: function () {
            $('#editStatus_now_btn').text('Submit').prop('disabled', false);
            if (typeof Error_Msg === 'function') Error_Msg('Oops...', 'Something went wrong!', 'error');
        }
    });
}

function complaintSubmit() {
    var id = $('#cs_id').val();
    var remark = $('#cs_remark').val();
    var status = $('#csu_status').val();
    $('#complaint_now_btn').text('Please wait...').prop('disabled', true);
    $.ajax({
        url: '{{ route("updateComplaint") }}',
        method: 'post',
        data: { id: id, remark: remark, status: status, _token: csrf },
        success: function (data) {
            $('#complaint_now_btn').text('Submit').prop('disabled', false);
            if (data.type == 'success') {
                $('#complaintModal').modal('hide');
                if (typeof Error_Msg === 'function') Error_Msg('Success', data.message, 'success');
                fetchAllSearch();
            } else if (typeof Error_Msg === 'function') {
                Error_Msg('Error', data.message || 'Failed', 'error');
            }
        },
        error: function () {
            $('#complaint_now_btn').text('Submit').prop('disabled', false);
        }
    });
}

$(function () {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('status')) {
        $('#status').val(urlParams.get('status'));
    }

    $('#user_id').select2({
        placeholder: 'Search user...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('.recharge-report-page'),
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

    $('#service_id').on('change', function () {
        var sid = $(this).val();
        $('#provider_id option').each(function () {
            var optSid = $(this).data('service');
            if (!$(this).val()) { $(this).show(); return; }
            if (!sid || String(optSid) === String(sid)) $(this).show();
            else $(this).hide();
        });
        $('#provider_id').val('');
    });

    $('#btnSearch').on('click', function () { currentPage = 1; fetchAllSearch(); });
    $('#btnDownload').on('click', downloadCsv);
    $('#btnPrev').on('click', function () { if (currentPage > 1) { currentPage--; fetchAllSearch(); } });
    $('#btnNext').on('click', function () { if (currentPage < lastPage) { currentPage++; fetchAllSearch(); } });
    $('#show').on('change', function () { currentPage = 1; fetchAllSearch(); });

    $(document).on('click', '.editComplaint', function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: '{{ route("getComplaint") }}',
            method: 'post',
            data: { id: id, _token: csrf },
            success: function (data) {
                if (data.error == 0) {
                    $('#cs_request_id').text(data.data.request_id);
                    $('#cs_created_at').text(data.data.created_at);
                    $('#cs_status').text(data.data.status);
                    $('#cs_subject').text(data.data.subject);
                    $('#cs_id').val(data.data.id);
                    $('#complaintModalLabel').text('Edit Complaint');
                    $('#complaintModal').modal('show');
                }
            }
        });
    });

    $(document).on('click', '.checkApilog', function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: '{{ route("checkApiLog") }}',
            method: 'post',
            data: { _token: csrf, id: id },
            success: function (res) {
                if (res.type == 'success') {
                    var html = '';
                    for (var i = 0; i < res.data.length; i++) {
                        html += '<div style="border:2px solid #865ce2;padding:5px;border-radius:11px;margin-bottom:10px;">' +
                            '<h6 style="color:blue;">API LOG : ' + (i + 1) + '</h6>' +
                            '<span>Order Id :</span><p class="text-info">' + res.data[i].txnid + '</p>' +
                            '<span>Date & Time :</span><p class="text-info">' + res.data[i].created_at + '</p>' +
                            '<span>Header :</span><p class="text-info">' + res.data[i].header + '</p>' +
                            '<span>Type :</span><p class="text-info">' + res.data[i].modal + '</p>' +
                            '<span>Post Data :</span><p class="text-info">' + res.data[i].request + '</p>' +
                            '<span>Request URL :</span><p class="text-info">' + res.data[i].url + '</p>' +
                            '<span>Response Data :</span><p class="text-info">' + res.data[i].response + '</p></div>';
                    }
                    $('#api_log_data').html(html || '<p class="text-muted">No logs</p>');
                    $('#checkApiLogModalLabel').text('Api Logs');
                    $('#checkApiLogModal').modal('show');
                } else if (typeof Error_Msg === 'function') {
                    Error_Msg('Oops...', res.message || 'No logs', 'error');
                }
            }
        });
    });

    fetchAllSearch();
});
</script>
@endsection
