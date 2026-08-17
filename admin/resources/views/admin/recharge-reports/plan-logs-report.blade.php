@extends('layouts.master')
@section('title') Plan / ROffer / HLR Requests Logs @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Plan / ROffer / HLR Requests Logs</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">Select Date</label><input type="date" class="form-control" id="from_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">Type</label>
                <select class="form-select" id="type"><option value="All">All Types</option><option value="Plans">Plans</option><option value="Roffer">Roffer</option><option value="CHECK_MOBILE">HLR / Mobile</option><option value="DTH">DTH</option></select>
            </div>
            <div class="col-md-3"><label class="form-label mb-0">Recharge Number</label><input type="text" class="form-control" id="number"></div>
            <div class="col-md-auto"><button type="button" class="btn btn-success" id="btnSearch" style="width:38px;height:38px;padding:0"><i class="ri-search-line"></i></button></div>
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
                <thead class="table-dark"><tr><th>RECHARGE ID</th><th>TYPE</th><th>REQUEST/RESPONSE</th><th>REQ. / RESP. TIME</th><th>DIFF</th></tr></thead>
                <tbody id="body"><tr><td colspan="5" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <div id="pageInfo" class="text-muted">Showing 0 to 0 of 0 entries</div>
            <div class="btn-group"><button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button><button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="logModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Request / Response</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><h6>Request</h6><pre id="logReq" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre><h6>Response</h6><pre id="logRes" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre></div>
</div></div></div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function payload(){return{_token:csrf,show:$('#show').val(),page:page,from_date:$('#from_date').val(),type:$('#type').val(),number:$('#number').val()};}
function load(){ $.post('{{ route("planLogsReportList") }}', payload(), function(res){
    $('#body').html(res.rows); var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
    $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
    $('#btnPrev').prop('disabled',page<=1); $('#btnNext').prop('disabled',page>=last);
},'json'); }
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#show').on('change', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
$(document).on('click','.btn-view-plan-log', function(){ $('#logReq').text($(this).data('req')||''); $('#logRes').text($(this).data('res')||''); $('#logModal').modal('show'); });
load();
</script>
@endsection
