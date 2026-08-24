@extends('layouts.master')
@section('title') Rehit Recharge Logs @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Rehit Recharge Logs</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">Select Date</label><input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-3"><label class="form-label mb-0">Recahrge ID</label><input type="text" class="form-control" id="recharge_id"></div>
            <div class="col-md-auto"><button type="button" class="btn btn-success" id="btnSearch">Search</button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Requests</h6></div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-1 mb-3"><span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select>
            <span class="text-muted small">entries</span></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark"><tr>
                    <th>RECHARGE ID</th><th>DATE &amp; TIME</th><th>USER</th><th>OPERATOR</th><th>NUMBER</th>
                    <th>MRP</th><th>AMOUNT</th><th>STATUS</th><th>API</th><th>OPT ID / SUPPLIER ID</th><th>MODE / IP</th>
                </tr></thead>
                <tbody id="body"><tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <div id="pageInfo" class="text-muted">Showing 0 to 0 of 0 entries</div>
            <div class="btn-group"><button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button><button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button></div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function payload(){return{_token:csrf,show:$('#show').val(),page:page,from_date:$('#from_date').val(),recharge_id:$('#recharge_id').val()};}
function load(){ $.post('{{ route("rehitHistoryList") }}', payload(), function(res){
    $('#body').html(res.rows); var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
    $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
    $('#btnPrev').prop('disabled',page<=1); $('#btnNext').prop('disabled',page>=last);
},'json'); }
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#show').on('change', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
load();
</script>
@endsection
