<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live Recharge Report</title>
    <link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --live-blue: #405189;
            --live-blue-2: #4f63a8;
            --live-success: #0ab39c;
            --live-page: #eef2f7;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--live-page);
            margin: 0;
            padding: 16px;
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
            color: #212529;
        }
        .live-page { max-width: 100%; }

        .live-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .live-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
            min-width: 0;
        }
        .live-brand h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--live-blue);
            letter-spacing: .02em;
        }
        .live-pulse {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #e8f6ee;
            color: #146c43;
            border: 1px solid #b7e4c7;
            border-radius: 999px;
            padding: 4px 10px 4px 8px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .live-pulse.is-paused {
            background: #fff3cd;
            color: #856404;
            border-color: #ffe69c;
        }
        .live-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34,197,94,.7);
            animation: livePulse 1.4s infinite;
        }
        .live-pulse.is-paused .live-dot {
            background: #f59e0b;
            animation: none;
            box-shadow: none;
        }
        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,.55); }
            70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
        }

        .live-top-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        .live-top-filters select {
            min-height: 34px;
            height: 34px;
            min-width: 160px;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid #d5deea;
            background: #fff;
            padding: 0 10px;
        }
        .live-top-filters select:focus {
            border-color: var(--live-blue);
            box-shadow: 0 0 0 3px rgba(64,81,137,.14);
            outline: none;
        }

        .live-card {
            background: #fff;
            border: 1px solid #dce3ee;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(64,81,137,.08);
            overflow: hidden;
            margin-bottom: 14px;
        }
        .live-card-head {
            background: linear-gradient(90deg, var(--live-blue) 0%, var(--live-blue-2) 100%);
            color: #fff;
            padding: 10px 16px;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .live-toolbar {
            padding: 14px 16px 16px;
            background: #f7f9fc;
        }
        .live-grid {
            display: grid;
            grid-template-columns: minmax(150px,1.1fr) minmax(190px,1.4fr) minmax(150px,1.1fr) minmax(140px,1fr) minmax(130px,.9fr) 110px auto;
            gap: 12px;
            align-items: end;
        }
        .live-field { min-width: 0; }
        .live-field label {
            display: block;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #5b6b8c;
            margin-bottom: 6px;
        }
        .live-field .form-select,
        .live-field .form-control {
            min-height: 38px;
            height: 38px;
            font-size: 0.82rem;
            border-radius: 8px;
            border: 1px solid #d5deea;
            background: #fff;
        }
        .live-field .form-select:focus,
        .live-field .form-control:focus {
            border-color: var(--live-blue);
            box-shadow: 0 0 0 3px rgba(64,81,137,.14);
        }
        .live-actions {
            display: flex;
            gap: 8px;
        }
        .live-actions .btn {
            min-height: 38px;
            height: 38px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-search {
            background: var(--live-blue);
            border-color: var(--live-blue);
            color: #fff;
        }
        .btn-search:hover { background: #364574; color: #fff; }
        .btn-reset {
            background: #fff;
            border: 1px solid #e9a0a0;
            color: #dc3545;
        }
        .btn-reset:hover { background: #fdecee; color: #b02a37; }

        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single {
            height: 38px !important;
            min-height: 38px !important;
            border: 1px solid #d5deea !important;
            border-radius: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            font-size: 0.82rem !important;
            padding-left: 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .live-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-left: 4px;
        }
        .live-summary span {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid;
            white-space: nowrap;
        }
        .sum-success { background: #e8f6ee; color: #146c43; border-color: #198754; }
        .sum-pending { background: #fff8e1; color: #9a7400; border-color: #ffc107; }
        .sum-failure { background: #fdecee; color: #b02a37; border-color: #dc3545; }
        .sum-refunded { background: #e7f1ff; color: #0a58ca; border-color: #0d6efd; }

        .live-table-wrap {
            margin: 0;
            border: 1px solid #e4eaf3;
            border-radius: 10px;
            overflow: auto;
        }
        .live-table {
            width: 100%;
            margin: 0;
            font-size: 0.78rem;
        }
        .live-table thead th {
            background: var(--live-blue);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            white-space: nowrap;
            vertical-align: middle;
            padding: 10px 12px;
            border-color: rgba(255,255,255,.12);
        }
        .live-table tbody td {
            vertical-align: middle;
            padding: 9px 12px;
            border-color: #eef1f5;
            background: #fff;
        }
        .live-table tbody tr:nth-child(even) td { background: #f8fafc; }
        .live-table tbody tr:hover td { background: #eef2ff; }
        .live-id { font-weight: 700; color: var(--live-blue); display: block; }
        .live-id-sub { font-size: 0.68rem; color: #878a99; }
        .live-user { font-weight: 600; }
        .live-user-meta { display: block; font-size: 0.68rem; color: #878a99; font-weight: 500; }
        .live-amt { font-weight: 700; color: var(--live-success); white-space: nowrap; }
        .live-number { cursor: pointer; font-weight: 700; color: #364574; }
        .live-number:hover { text-decoration: underline; }
        .rpt-status-wrap { display: flex; flex-direction: column; gap: 1px; line-height: 1.15; }
        .rpt-status { font-weight: 800; font-size: 0.78rem; letter-spacing: .03em; text-transform: uppercase; }
        .rpt-status--success { color: #157347; }
        .rpt-status--pending { color: #b78103; }
        .rpt-status--failure { color: #dc3545; }
        .rpt-status--refunded { color: #0d6efd; }
        .rpt-status--muted { color: #6c757d; }
        .rpt-status-id { font-size: 0.68rem; font-weight: 700; color: #495057; }
        .opt-supplier { color: #d39e00; font-weight: 700; }
        .opt-client { color: #0dcaf0; font-weight: 700; }

        .live-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px 14px;
            flex-wrap: wrap;
        }
        .live-foot .text-muted { font-size: 0.78rem; }
        .live-foot .btn {
            min-width: 88px;
            border-radius: 8px;
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .live-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .live-actions { grid-column: span 3; }
        }
        @media (max-width: 768px) {
            .live-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .live-actions { grid-column: span 2; width: 100%; }
            .live-actions .btn { flex: 1; }
        }
    </style>
</head>
<body>
<div class="live-page">
    <div class="live-top">
        <div class="live-brand">
            <h1>Live Recharge Report</h1>
            <span class="live-pulse" id="livePulse"><span class="live-dot"></span><span id="livePulseText">Live</span></span>
            <div class="live-summary" id="summaryPills">
                <span class="sum-success">SUCCESS: 0.00 (0)</span>
                <span class="sum-pending">PENDING: 0.00 (0)</span>
                <span class="sum-failure">FAILURE: 0.00 (0)</span>
                <span class="sum-refunded">REFUNDED: 0.00 (0)</span>
            </div>
        </div>
        <div class="live-top-filters">
            <select id="provider_id" title="Operator">
                <option value="">All Operators</option>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}">{{ $p->provider_name }}</option>
                @endforeach
            </select>
            <select id="status" title="Status">
                <option value="">All Status</option>
                <option value="Success" style="color:#157347;font-weight:700">SUCCESS</option>
                <option value="Pending" style="color:#b78103;font-weight:700">PENDING</option>
                <option value="Failure" style="color:#dc3545;font-weight:700">FAILURE</option>
                <option value="Refunded" style="color:#0d6efd;font-weight:700">REFUNDED</option>
            </select>
        </div>
    </div>

    <div class="live-card">
        <div class="live-table-wrap">
            <table class="table live-table mb-0">
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

        <div class="live-foot">
            <div id="pageInfo" class="text-muted">Showing 0 to 0 of 0 entries</div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="{{ URL::asset('assets/js/bootstrap.min.js') }}"></script>
<script>
var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var currentPage = 1;
var lastPage = 1;
var autoTimer = null;
var today = '{{ \Carbon\Carbon::today()->format('Y-m-d') }}';

function filterPayload() {
    return {
        _token: csrf,
        show: 50,
        page: currentPage,
        from_date: today,
        to_date: today,
        provider_id: $('#provider_id').val(),
        status: $('#status').val()
    };
}

function renderSummary(s) {
    if (!s) return;
    $('#summaryPills').html(
        '<span class="sum-success">SUCCESS: ' + s.success_amt + ' (' + s.success_cnt + ')</span>' +
        '<span class="sum-pending">PENDING: ' + s.pending_amt + ' (' + s.pending_cnt + ')</span>' +
        '<span class="sum-failure">FAILURE: ' + s.failure_amt + ' (' + s.failure_cnt + ')</span>' +
        '<span class="sum-refunded">REFUNDED: ' + s.refunded_amt + ' (' + s.refunded_cnt + ')</span>'
    );
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
            renderSummary(res.summary || {});
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

$(function () {
    $('#provider_id, #status').on('change', function () {
        currentPage = 1;
        fetchLive(false);
    });
    $('#btnPrev').on('click', function () {
        if (currentPage > 1) { currentPage--; fetchLive(false); }
    });
    $('#btnNext').on('click', function () {
        if (currentPage < lastPage) { currentPage++; fetchLive(false); }
    });

    fetchLive(false);
    autoTimer = setInterval(function () { fetchLive(true); }, 5000);
});
</script>
</body>
</html>
