<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live Recharge Report</title>
    <link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background: #f3f6f9; margin: 0; padding: 12px; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
        .live-toolbar {
            background: #fff;
            border: 1px solid #e9ebec;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .live-toolbar .form-select,
        .live-toolbar .form-control { font-size: 13px; min-height: 34px; }
        .btn-icon {
            width: 34px; height: 34px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { height: 34px; border: 1px solid #ced4da; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 32px; font-size: 13px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 32px; }
        .live-table thead th {
            background: #405189;
            color: #fff;
            font-size: 12px;
            white-space: nowrap;
            vertical-align: middle;
        }
        .live-table td { font-size: 12px; vertical-align: middle; }
        .rpt-status-wrap { display: flex; flex-direction: column; gap: 1px; line-height: 1.15; }
        .rpt-status { font-weight: 800; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; }
        .rpt-status--success { color: #157347; }
        .rpt-status--pending { color: #b78103; }
        .rpt-status--failure { color: #dc3545; }
        .rpt-status--refunded { color: #0d6efd; }
        .rpt-status--muted { color: #6c757d; }
        .rpt-status-id { font-size: 11px; font-weight: 700; color: #495057; }
        .opt-supplier { color: #e6a700; font-weight: 600; }
        .opt-client { color: #0dcaf0; font-weight: 600; }
        .check-row { gap: 18px; }
        .check-row .form-check-label { font-size: 13px; }
    </style>
</head>
<body>
<div class="live-toolbar">
    <div class="row g-2 align-items-end">
        <div class="col-lg-2 col-md-4">
            <label class="form-label mb-1 small fw-semibold">API</label>
            <select class="form-select form-select-sm" id="api_id">
                <option value="">ALL APIS</option>
                @foreach($apis as $a)
                    <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label mb-1 small fw-semibold">User / Client</label>
            <select class="form-select form-select-sm" id="user_id">
                <option value=""></option>
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label mb-1 small fw-semibold">Operator</label>
            <select class="form-select form-select-sm" id="provider_id">
                <option value="">Select Operator</option>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}">{{ $p->provider_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label mb-1 small fw-semibold">Circle</label>
            <select class="form-select form-select-sm" id="circle_id">
                <option value="">Select Circle</option>
                @foreach($circles as $c)
                    <option value="{{ $c->id }}">{{ $c->state_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-4">
            <label class="form-label mb-1 small fw-semibold">Status</label>
            <select class="form-select form-select-sm" id="status">
                <option value="">Select Status</option>
                <option value="Success" style="color:#157347;font-weight:700">SUCCESS</option>
                <option value="Pending" style="color:#b78103;font-weight:700">PENDING</option>
                <option value="Failure" style="color:#dc3545;font-weight:700">FAILURE</option>
                <option value="Refunded" style="color:#0d6efd;font-weight:700">REFUNDED</option>
            </select>
        </div>
        <div class="col-lg-1 col-md-3">
            <label class="form-label mb-1 small fw-semibold">Amount</label>
            <input type="text" class="form-control form-control-sm" id="amount" placeholder="Amount">
        </div>
        <div class="col-lg-auto col-md-3 d-flex gap-1">
            <button type="button" class="btn btn-primary btn-icon" id="btnSearch" title="Search">
                <i class="ri-search-line"></i>
            </button>
            <button type="button" class="btn btn-danger btn-icon" id="btnReset" title="Reset">
                <i class="ri-close-line"></i>
            </button>
        </div>
    </div>
    <div class="d-flex flex-wrap check-row mt-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="keep_same_recharge">
            <label class="form-check-label" for="keep_same_recharge">Keep Same Recharge</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="stop_auto_refresh">
            <label class="form-check-label" for="stop_auto_refresh">Stop Auto Refresh</label>
        </div>
    </div>
</div>

<div class="table-responsive bg-white border rounded">
    <table class="table table-bordered table-striped live-table mb-0">
        <thead>
            <tr>
                <th>Recharge ID</th>
                <th>Date &amp; Time</th>
                <th>User Details</th>
                <th>Operator</th>
                <th>Circle</th>
                <th>Number</th>
                <th>Amount</th>
                <th>Status</th>
                <th>API</th>
                <th>Opt ID / <span class="opt-supplier">Supplier ID</span> / <span class="opt-client">Client ID</span></th>
                <th>Mode</th>
            </tr>
        </thead>
        <tbody id="liveBody">
            <tr><td colspan="11" class="text-center text-muted py-4">Loading...</td></tr>
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-2 px-1">
    <div id="pageInfo" class="text-muted small">Showing 0 to 0 of 0 entries</div>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="{{ URL::asset('assets/js/bootstrap.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var currentPage = 1;
var lastPage = 1;
var autoTimer = null;
var keepNumber = '';
var today = '{{ \Carbon\Carbon::today()->format('Y-m-d') }}';

function filterPayload() {
    return {
        _token: csrf,
        show: 50,
        page: currentPage,
        from_date: today,
        to_date: today,
        api_id: $('#api_id').val(),
        user_id: $('#user_id').val(),
        provider_id: $('#provider_id').val(),
        circle_id: $('#circle_id').val(),
        status: $('#status').val(),
        amount: $('#amount').val(),
        keep_number: ($('#keep_same_recharge').is(':checked') && keepNumber) ? keepNumber : ''
    };
}

function fetchLive(silent) {
    if (!silent) {
        $('#liveBody').html('<tr><td colspan="11" class="text-center text-muted py-4">Loading...</td></tr>');
    }
    $.ajax({
        url: '{{ route("liveRechargeReportsList") }}',
        method: 'POST',
        dataType: 'json',
        data: filterPayload(),
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#liveBody').html('<tr><td colspan="11" class="text-center text-danger">Failed to load</td></tr>');
                return;
            }
            $('#liveBody').html(res.rows);
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#liveBody').html('<tr><td colspan="11" class="text-center text-danger">Failed to load</td></tr>');
        }
    });
}

function startAutoRefresh() {
    if (autoTimer) clearInterval(autoTimer);
    autoTimer = null;
    if (!$('#stop_auto_refresh').is(':checked')) {
        autoTimer = setInterval(function () { fetchLive(true); }, 5000);
    }
}

function resetFilters() {
    $('#api_id').val('');
    $('#user_id').val(null).trigger('change');
    $('#provider_id').val('');
    $('#circle_id').val('');
    $('#status').val('');
    $('#amount').val('');
    $('#keep_same_recharge').prop('checked', false);
    keepNumber = '';
    currentPage = 1;
    fetchLive(false);
}

$(function () {
    $('#user_id').select2({
        placeholder: 'User / Client',
        allowClear: true,
        width: '100%',
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

    $('#btnSearch').on('click', function () {
        currentPage = 1;
        fetchLive(false);
    });
    $('#btnReset').on('click', resetFilters);
    $('#btnPrev').on('click', function () {
        if (currentPage > 1) { currentPage--; fetchLive(false); }
    });
    $('#btnNext').on('click', function () {
        if (currentPage < lastPage) { currentPage++; fetchLive(false); }
    });
    $('#stop_auto_refresh').on('change', startAutoRefresh);
    $('#keep_same_recharge').on('change', function () {
        if (!$(this).is(':checked')) keepNumber = '';
        currentPage = 1;
        fetchLive(false);
    });

    $(document).on('click', '.live-number', function () {
        var num = $(this).closest('tr').data('number') || $(this).text().trim();
        if (!num || num === '-') return;
        if ($('#keep_same_recharge').is(':checked')) {
            keepNumber = num;
            currentPage = 1;
            fetchLive(false);
        }
    });

    fetchLive(false);
    startAutoRefresh();
});
</script>
</body>
</html>
