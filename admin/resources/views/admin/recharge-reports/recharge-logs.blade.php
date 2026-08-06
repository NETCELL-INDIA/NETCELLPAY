@extends('layouts.master')
@section('title') Recharge Logs @endsection
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container{width:100%!important}.btn-icon{width:38px;height:38px;padding:0;display:inline-flex;align-items:center;justify-content:center}</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">API Requests Logs</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">Select Date</label><input type="date" class="form-control" id="from_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-3"><label class="form-label mb-0">User / Client</label><select class="form-select" id="user_id"><option value=""></option></select></div>
            <div class="col-md-2"><label class="form-label mb-0">API</label>
                <select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">Type</label>
                <select class="form-select" id="type"><option value="All">All Types</option><option value="Recharge">Recharge</option><option value="Bill">Bill</option><option value="Status">Status</option></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">Recharge ID</label><input type="text" class="form-control" id="recharge_id"></div>
            <div class="col-md-2"><label class="form-label mb-0">Client ID/Number</label><input type="text" class="form-control" id="client_number"></div>
            <div class="col-md-auto"><button type="button" class="btn btn-success btn-icon" id="btnSearch"><i class="ri-search-line"></i></button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Requests</h6></div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-1 mb-3">
            <span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
            <span class="text-muted small">entries</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>RECHARGE ID</th><th>CLIENT ID</th><th>TYPE</th><th>REQUEST/RESPONSE</th><th>REQ. / RESP. TIME</th><th>DIFF</th></tr>
                </thead>
                <tbody id="logsBody"><tr><td colspan="6" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
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
<div class="modal fade" id="logModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Request / Response</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><h6>Request</h6><pre id="logReq" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre><h6>Response</h6><pre id="logRes" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre></div>
</div></div></div>
@endsection
@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var csrf='{{ csrf_token() }}', currentPage=1, lastPage=1;
function payload(){return{_token:csrf,show:$('#show').val(),page:currentPage,from_date:$('#from_date').val(),user_id:$('#user_id').val(),api_id:$('#api_id').val(),type:$('#type').val(),recharge_id:$('#recharge_id').val(),client_number:$('#client_number').val()};}
function fetchLogs(){
    $('#logsBody').html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    $.post('{{ route("rechargeLogsList") }}', payload(), function(res){
        if(!res||res.type!=='success'){ $('#logsBody').html('<tr><td colspan="6" class="text-center text-danger">Failed</td></tr>'); return; }
        $('#logsBody').html(res.rows); var p=res.pagination||{}; currentPage=p.page||1; lastPage=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
        $('#btnPrev').prop('disabled', currentPage<=1); $('#btnNext').prop('disabled', currentPage>=lastPage);
    },'json');
}
$(function(){
    $('#user_id').select2({placeholder:'Search user by firm name, mobile, email, id...',allowClear:true,ajax:{url:'{{ route("generalRoutingsSearchUsers") }}',dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:(d&&d.results)?d.results:[]};}}});
    $('#btnSearch').on('click', function(){ currentPage=1; fetchLogs(); });
    $('#show').on('change', function(){ currentPage=1; fetchLogs(); });
    $('#btnPrev').on('click', function(){ if(currentPage>1){currentPage--;fetchLogs();} });
    $('#btnNext').on('click', function(){ if(currentPage<lastPage){currentPage++;fetchLogs();} });
    $(document).on('click','.btn-view-log', function(){ $('#logReq').text($(this).data('req')||''); $('#logRes').text($(this).data('res')||''); $('#logModal').modal('show'); });
    fetchLogs();
});
</script>
@endsection
