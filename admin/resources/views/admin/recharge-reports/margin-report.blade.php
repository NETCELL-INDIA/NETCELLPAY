@extends('layouts.master')

@section('title') Margin Report @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container { width: 100% !important; }
    .margin-summary .badge-pill-stat {
        display: inline-block;
        min-width: 140px;
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 600;
        border: 2px solid;
        margin: 0 6px 8px 0;
        background: #fff;
    }
    .stat-mrp { color: #198754; border-color: #198754; }
    .stat-margin { color: #d39e00; border-color: #ffc107; }
    .stat-child { color: #dc3545; border-color: #dc3545; }
    .stat-surcharge { color: #0d6efd; border-color: #0d6efd; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="mb-0">Margin Report</h4>
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
                <input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">To Date</label>
                <input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
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
            <div class="col-md-auto">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="circle_wise" value="1">
                    <label class="form-check-label" for="circle_wise">Circle Wise</label>
                </div>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="button" class="btn btn-success" id="btnSubmit">Submit</button>
                <button type="button" class="btn btn-info" id="btnDownload">Download</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <h6 class="mb-0 text-white">List of Transactions</h6>
    </div>
    <div class="card-body">
        <div class="margin-summary mb-3" id="summaryPills">
            <span class="badge-pill-stat stat-mrp">MRP : 0.00</span>
            <span class="badge-pill-stat stat-margin">Margin : 0.00</span>
            <span class="badge-pill-stat stat-child">Child Margin : 0.00</span>
            <span class="badge-pill-stat stat-surcharge">Surcharge : 0.00</span>
        </div>

        <div class="d-flex align-items-center gap-1 mb-3">
            <span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-muted small">entries</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>SR NO</th>
                        <th>OPERATOR</th>
                        <th>CIRCLE</th>
                        <th>TXNS</th>
                        <th>MRP</th>
                        <th>MARGIN</th>
                        <th>CHILD MARGIN</th>
                        <th>SURCHARGE</th>
                        <th>BONUS</th>
                        <th>R-OFFER</th>
                    </tr>
                </thead>
                <tbody id="marginBody">
                    <tr><td colspan="10" class="text-center text-muted py-4">No data available in table</td></tr>
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
@endsection

@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
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
        user_id: $('#user_id').val(),
        provider_id: $('#provider_id').val(),
        circle_wise: $('#circle_wise').is(':checked') ? 1 : 0
    };
}

function renderSummary(s) {
    $('#summaryPills').html(
        '<span class="badge-pill-stat stat-mrp">MRP : ' + (s.mrp || '0.00') + '</span>' +
        '<span class="badge-pill-stat stat-margin">Margin : ' + (s.margin || '0.00') + '</span>' +
        '<span class="badge-pill-stat stat-child">Child Margin : ' + (s.child_margin || '0.00') + '</span>' +
        '<span class="badge-pill-stat stat-surcharge">Surcharge : ' + (s.surcharge || '0.00') + '</span>'
    );
}

function fetchMargin() {
    $('#marginBody').html('<tr><td colspan="10" class="text-center text-muted py-4">Loading...</td></tr>');
    $.ajax({
        url: '{{ route("marginReportList") }}',
        method: 'POST',
        dataType: 'json',
        data: filterPayload(),
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#marginBody').html('<tr><td colspan="10" class="text-center text-danger">Failed to load</td></tr>');
                return;
            }
            $('#marginBody').html(res.rows);
            renderSummary(res.summary || {});
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#marginBody').html('<tr><td colspan="10" class="text-center text-danger">Failed to load</td></tr>');
        }
    });
}

function downloadCsv() {
    var form = $('<form>', { method: 'POST', action: '{{ route("marginReportDownload") }}' });
    var data = filterPayload();
    $.each(data, function (k, v) {
        form.append($('<input>', { type: 'hidden', name: k, value: v }));
    });
    $('body').append(form);
    form.submit();
    form.remove();
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

    $('#btnSubmit').on('click', function () { currentPage = 1; fetchMargin(); });
    $('#btnDownload').on('click', downloadCsv);
    $('#show').on('change', function () { currentPage = 1; fetchMargin(); });
    $('#btnPrev').on('click', function () { if (currentPage > 1) { currentPage--; fetchMargin(); } });
    $('#btnNext').on('click', function () { if (currentPage < lastPage) { currentPage++; fetchMargin(); } });

    fetchMargin();
});
</script>
@endsection
