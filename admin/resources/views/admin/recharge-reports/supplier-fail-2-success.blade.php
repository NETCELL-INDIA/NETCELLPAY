@extends('layouts.master')
@section('title') Recharge Fail To Success @endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="mb-1">Recharge Fail To Success</h4>
        </div>
    </div>
</div>

<div class="alert alert-info py-2 mb-3">
    API / admin ಮೊದಲು <strong>Failed</strong> (wallet refund) ಆದ recharge ನಂತರ <strong>Success</strong> ಆದಾಗ ಇಲ್ಲಿ ಬರುತ್ತದೆ.
    Recharge Report ನಲ್ಲಿ Failed → Success update ಮಾಡಿದರೆ ಇಲ್ಲಿ record ಆಗುತ್ತದೆ.
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-0">From Date</label>
                <input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->subDays(30)->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">To Date</label>
                <input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0">API</label>
                <select class="form-select" id="api_id">
                    <option value="">All APIs</option>
                    @foreach($apis as $a)
                        <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0">Recharge ID / Number</label>
                <input type="text" class="form-control" id="recharge_id" placeholder="Order ID or mobile">
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-primary" id="btnSearch"><i class="ri-search-line"></i> Search</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0">Fail → Success list</h6></div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-1 mb-3">
            <span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto">
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
                        <th>Recharge ID</th>
                        <th>User</th>
                        <th>Operator</th>
                        <th>Number</th>
                        <th>Amount</th>
                        <th>Change</th>
                        <th>API</th>
                        <th>Remark</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody id="f2sBody">
                    <tr><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr>
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
<script>
var csrf = '{{ csrf_token() }}', currentPage = 1, lastPage = 1;
function payload() {
    return {
        _token: csrf,
        show: $('#show').val(),
        page: currentPage,
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        api_id: $('#api_id').val(),
        recharge_id: $('#recharge_id').val()
    };
}
function fetchF2S() {
    $('#f2sBody').html('<tr><td colspan="9" class="text-center text-muted">Loading...</td></tr>');
    $.post('{{ route("supplierFail2SuccessList") }}', payload(), function (res) {
        if (!res || res.type !== 'success') {
            $('#f2sBody').html('<tr><td colspan="9" class="text-center text-danger">Failed to load</td></tr>');
            return;
        }
        $('#f2sBody').html(res.rows);
        var p = res.pagination || {};
        currentPage = p.page || 1;
        lastPage = p.last_page || 1;
        $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
        $('#btnPrev').prop('disabled', currentPage <= 1);
        $('#btnNext').prop('disabled', currentPage >= lastPage);
    }, 'json').fail(function () {
        $('#f2sBody').html('<tr><td colspan="9" class="text-center text-danger">Failed to load</td></tr>');
    });
}
$('#btnSearch').on('click', function () { currentPage = 1; fetchF2S(); });
$('#show').on('change', function () { currentPage = 1; fetchF2S(); });
$('#btnPrev').on('click', function () { if (currentPage > 1) { currentPage--; fetchF2S(); } });
$('#btnNext').on('click', function () { if (currentPage < lastPage) { currentPage++; fetchF2S(); } });
fetchF2S();
</script>
@endsection
