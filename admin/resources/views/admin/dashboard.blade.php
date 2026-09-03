@extends('layouts.master')

@section('title')
Dashboard
@endsection

@section('css')
<style>
    .dash-page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #405189;
        margin-bottom: 1.25rem;
    }
    .pending-card {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        padding: 1rem 1.15rem 0.85rem;
        height: 100%;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        position: relative;
        overflow: hidden;
        display: block;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    a.pending-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(16, 24, 40, 0.1);
        border-color: #cfd8e3;
        color: inherit;
        text-decoration: none;
    }
    .pending-card::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 3px;
    }
    .pending-card.cyan::after { background: #22d3ee; }
    .pending-card.orange::after { background: #fb923c; }
    .pending-card.green::after { background: #22c55e; }
    .pending-card.red::after { background: #ef4444; }
    .pending-card.blue::after { background: #2563eb; }
    .pending-card .label {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }
    .pending-card .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }
    .dash-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        height: 100%;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }
    .dash-panel .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .dash-panel .panel-head h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }
    .dash-panel .refresh-btn {
        border: 0;
        background: transparent;
        color: #64748b;
        padding: 0.15rem 0.35rem;
    }
    .dash-table {
        width: 100%;
        margin: 0;
        font-size: 0.82rem;
    }
    .dash-table th {
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        white-space: nowrap;
        border-color: #e2e8f0;
        vertical-align: middle;
    }
    .dash-table th.success-h { color: #16a34a; }
    .dash-table th.pending-h { color: #ea580c; }
    .dash-table th.failure-h { color: #dc2626; }
    .dash-table th.total-h { color: #0891b2; }
    .dash-table td {
        border-color: #eef2f7;
        vertical-align: middle;
        color: #334155;
    }
    .dash-table tbody tr:nth-child(even) > td { background: #fafbfc; }
    .dash-table tbody tr.row-total > td {
        background-color: #16a34a !important;
        color: #ffffff !important;
        border-color: #15803d !important;
        font-weight: 700;
    }
    .dash-table tbody tr.row-admin > td {
        background-color: #1e3a8a !important;
        color: #ffffff !important;
        border-color: #1e40af !important;
        font-weight: 700;
    }
    #contribution-chart {
        min-height: 260px;
    }
    .contribution-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-top: 0.75rem;
        font-size: 0.8rem;
        color: #475569;
    }
    .contribution-legend span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .contribution-legend i {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
</style>
@endsection

@section('content')
<div class="row mb-2">
    <div class="col-12">
        <h2 class="dash-page-title mb-0">Dashboard</h2>
    </div>
</div>

<div class="row g-3 mb-3" id="pending-cards">
    <div class="col-md col-6">
        <a class="pending-card cyan" href="{{ URL::asset('admin/recharge-reports/pending-report') }}">
            <div class="label">Pending Recharges</div>
            <div class="value" id="pending_recharges">0</div>
        </a>
    </div>
    <div class="col-md col-6">
        <a class="pending-card orange" href="{{ URL::asset('admin/support/complaint') }}">
            <div class="label">Pending Complains</div>
            <div class="value" id="pending_complaints">0</div>
        </a>
    </div>
    <div class="col-md col-6">
        <a class="pending-card green" href="{{ URL::asset('admin/fund/fund-request?status=Pending') }}">
            <div class="label">Pending Payments</div>
            <div class="value" id="pending_fund">0</div>
        </a>
    </div>
    <div class="col-md col-6">
        <a class="pending-card red" href="{{ URL::asset('admin/recharge-reports/refund-report') }}">
            <div class="label">Pending Refunds</div>
            <div class="value" id="pending_refunds">0</div>
        </a>
    </div>
    <div class="col-md col-6">
        <a class="pending-card blue" href="{{ URL::asset('admin/users/kyc') }}">
            <div class="label">KYC Requests</div>
            <div class="value" id="pending_kyc">0</div>
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Today's Statistics</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()" title="Refresh">
                    <i class="ri-refresh-line"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0" id="today-stats-table">
                    <thead>
                        <tr>
                            <th>SR NO</th>
                            <th>SERVICE</th>
                            <th class="success-h">SUCCESS</th>
                            <th class="pending-h">PENDING</th>
                            <th class="failure-h">FAILURE</th>
                            <th class="total-h">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody id="today-stats-body">
                        <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Today's Contribution</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()" title="Refresh">
                    <i class="ri-refresh-line"></i>
                </button>
            </div>
            <div id="contribution-chart"></div>
            <div class="contribution-legend" id="contribution-legend"></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Balance Statistics</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2">USERTYPE</th>
                            <th colspan="3" class="text-center">BALANCE</th>
                            <th rowspan="2">TOTAL</th>
                        </tr>
                        <tr>
                            <th>RECHARGE</th>
                            <th>UTILITY</th>
                            <th>AEPS</th>
                        </tr>
                    </thead>
                    <tbody id="balance-stats-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Account Statistics</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th>USERTYPE</th>
                            <th>USERS</th>
                        </tr>
                    </thead>
                    <tbody id="account-stats-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Recharge Statistics</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th>USERTYPE</th>
                            <th>RECHARGES</th>
                        </tr>
                    </thead>
                    <tbody id="recharge-stats-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Top Operators</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th>OPERATOR</th>
                            <th>TXNS</th>
                            <th>MRP</th>
                        </tr>
                    </thead>
                    <tbody id="top-operators-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Top Retailers (Recharge)</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>TXNS</th>
                            <th>MRP</th>
                        </tr>
                    </thead>
                    <tbody id="top-retailers-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dash-panel">
            <div class="panel-head">
                <h5>Top API Users (Recharge)</h5>
                <button type="button" class="refresh-btn" onclick="fetchAllSearch()"><i class="ri-refresh-line"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm dash-table mb-0">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>TXNS</th>
                            <th>MRP</th>
                        </tr>
                    </thead>
                    <tbody id="top-api-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<p class="text-muted small mb-0" id="dash-announcement"></p>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    var contributionChart = null;
    var chartColors = ['#3b82f6', '#14b8a6', '#f97316', '#ef4444', '#8b5cf6', '#0ea5e9', '#06b6d4'];

    function money(n) {
        n = Number(n || 0);
        return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function moneyCount(amount, count) {
        return money(amount) + ' (' + Number(count || 0) + ')';
    }

    function fetchAllSearch() {
        var now = new Date();
        var today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
        var from_date = today;
        var to_date = today;

        $.ajax({
            url: '{{ route('dashboardReportsList') }}',
            method: 'post',
            data: {
                from_date: from_date,
                to_date: to_date,
                _token: '{{ csrf_token() }}',
            },
            success: function (res) {
                try {
                    renderPending(res.pending || {});
                    renderTodayStats(res.today_by_service || []);
                    renderBalance(res.balances || []);
                    renderAccounts(res.accounts || []);
                    renderRecharges(res.recharges || []);
                    renderTop('#top-operators-body', res.top_operators || []);
                    renderTop('#top-retailers-body', res.top_retailers || []);
                    renderTop('#top-api-body', res.top_api_users || []);
                    $("#dash-announcement").text(res.announcement || '');
                } catch (e) {
                    console.error('dashboard render error', e);
                }
                try {
                    renderContribution(res.today_by_service || []);
                } catch (e) {
                    console.error('dashboard chart error', e);
                }
            },
            error: function (err) {
                console.log(err);
            }
        });
    }

    function renderPending(p) {
        $("#pending_recharges").text(p.recharges || 0);
        $("#pending_complaints").text(p.complaints || 0);
        $("#pending_fund").text(p.fund || 0);
        $("#pending_refunds").text(p.refunds || 0);
        $("#pending_kyc").text(p.kyc || 0);
    }

    function renderTodayStats(rows) {
        var html = '';
        if (!rows.length) {
            html = '<tr><td colspan="6" class="text-center text-muted">No record found</td></tr>';
        } else {
            rows.forEach(function (row, idx) {
                html += '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + row.service + '</td>' +
                    '<td>' + moneyCount(row.success_amount, row.success_count) + '</td>' +
                    '<td>' + moneyCount(row.pending_amount, row.pending_count) + '</td>' +
                    '<td>' + moneyCount(row.failed_amount, row.failed_count) + '</td>' +
                    '<td>' + moneyCount(row.total_amount, row.total_count) + '</td>' +
                    '</tr>';
            });
        }
        $("#today-stats-body").html(html);
    }

    function renderContribution(rows) {
        var labels = [];
        var series = [];
        var legend = '';
        var colorIdx = 0;
        (rows || []).forEach(function (row) {
            var val = Number(row.success_amount || 0);
            if (val > 0) {
                labels.push(row.service);
                series.push(val);
                legend += '<span><i style="background:' + chartColors[colorIdx % chartColors.length] + '"></i>' +
                    row.service + ' (' + money(val) + ')</span>';
                colorIdx++;
            }
        });
        if (!series.length) {
            labels = ['No Success'];
            series = [1];
            legend = '<span class="text-muted">No successful transactions in selected dates</span>';
        }
        $("#contribution-legend").html(legend);

        var allPlaceholder = labels.length === 1 && labels[0] === 'No Success';
        var options = {
            chart: { type: 'donut', height: 280 },
            labels: labels,
            series: series,
            colors: chartColors,
            legend: { show: false },
            dataLabels: { enabled: !allPlaceholder },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Success',
                                formatter: function () {
                                    if (allPlaceholder) return money(0);
                                    var sum = series.reduce(function (a, b) { return a + b; }, 0);
                                    return money(sum);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return allPlaceholder ? money(0) : money(val);
                    }
                }
            }
        };

        if (contributionChart) {
            contributionChart.updateOptions(options);
            contributionChart.updateSeries(series);
        } else if (typeof ApexCharts !== 'undefined') {
            contributionChart = new ApexCharts(document.querySelector("#contribution-chart"), options);
            contributionChart.render();
        } else {
            $("#contribution-chart").html('<p class="text-muted text-center my-4">Chart library not loaded</p>');
        }
    }

    function renderTop(selector, rows) {
        var html = '';
        var list = (rows || []).slice(0, 5);
        if (!list.length) {
            html = '<tr><td colspan="3" class="text-center text-muted">No record found</td></tr>';
        } else {
            list.forEach(function (row) {
                html += '<tr><td>' + (row.name || '-') + '</td><td>' +
                    Number(row.txns || 0) + '</td><td>' +
                    money(row.mrp) + '</td></tr>';
            });
        }
        $(selector).html(html);
    }

    function renderBalance(rows) {
        var html = '';
        (rows || []).forEach(function (row) {
            var cls = row.is_total ? ' class="row-total"' : (row.is_admin ? ' class="row-admin"' : '');
            html += '<tr' + cls + '>' +
                '<td>' + row.usertype + '</td>' +
                '<td>' + money(row.recharge) + '</td>' +
                '<td>' + money(row.utility) + '</td>' +
                '<td>' + money(row.aeps) + '</td>' +
                '<td>' + money(row.total) + '</td>' +
                '</tr>';
        });
        $("#balance-stats-body").html(html || '<tr><td colspan="5" class="text-center text-muted">No data</td></tr>');
    }

    function renderAccounts(rows) {
        var html = '';
        (rows || []).forEach(function (row) {
            var cls = row.is_total ? ' class="row-total"' : '';
            html += '<tr' + cls + '><td>' + row.usertype + '</td><td>' + Number(row.users || 0) + '</td></tr>';
        });
        $("#account-stats-body").html(html || '<tr><td colspan="2" class="text-center text-muted">No data</td></tr>');
    }

    function renderRecharges(rows) {
        var html = '';
        (rows || []).forEach(function (row) {
            var cls = row.is_total ? ' class="row-total"' : (row.is_admin ? ' class="row-admin"' : '');
            var value = money(row.amount);
            if (!row.is_admin) {
                value += ' (' + Number(row.count || 0) + ')';
            }
            html += '<tr' + cls + '><td>' + row.usertype + '</td><td>' + value + '</td></tr>';
        });
        $("#recharge-stats-body").html(html || '<tr><td colspan="2" class="text-center text-muted">No data</td></tr>');
    }

    fetchAllSearch();
</script>
@endsection
