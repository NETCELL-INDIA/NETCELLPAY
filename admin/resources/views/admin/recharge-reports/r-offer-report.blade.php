@extends('layouts.master')
@section('title') R-Offer Report @endsection
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container{width:100%!important}
.badge-pill-stat{display:inline-block;min-width:140px;padding:8px 14px;border-radius:999px;font-weight:600;border:2px solid;margin:0 6px 8px 0;background:#fff}
.stat-success{color:#198754;border-color:#198754}.stat-pending{color:#d39e00;border-color:#ffc107}
.stat-failure{color:#dc3545;border-color:#dc3545}.stat-refunded{color:#0d6efd;border-color:#0dcaf0}
.date-note{color:#dc3545;font-size:12px;font-weight:600}
</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">R-Offer Report</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From Date</label><input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To Date</label><input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">API</label><select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label mb-0">User / Client</label><select class="form-select" id="user_id"><option value=""></option></select></div>
            <div class="col-md-2"><label class="form-label mb-0">Operator</label><select class="form-select" id="provider_id"><option value="">Select Operator</option>@foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->provider_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Circle</label><select class="form-select" id="circle_id"><option value="">Select Circle</option>@foreach($circles as $c)<option value="{{ $c->id }}">{{ $c->state_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Status</label><select class="form-select" id="status"><option value="">Select Status</option><option value="Success">Success</option><option value="Pending">Pending</option><option value="Failure">Failure</option><option value="Refunded">Refunded</option></select></div>
            <div class="col-md-2"><label class="form-label mb-0">Number</label><input type="text" class="form-control" id="number"><div class="date-note mt-1">NOTE : Please confirm dates before searching.</div></div>
            <div class="col-md-1"><label class="form-label mb-0">Amount</label><input type="text" class="form-control" id="amount"></div>
            <div class="col-md-2"><label class="form-label mb-0">Roffer Type</label><select class="form-select" id="roffer_type"><option value="All">All R-Offer</option><option value="Roffer">Roffer</option><option value="Plan">Plan</option></select></div>
            <div class="col-md-1"><label class="form-label mb-0">Show</label><select class="form-select" id="show"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select></div>
            <div class="col-md-auto d-flex gap-2"><button type="button" class="btn btn-success" id="btnSearch">Search Transactions</button><button type="button" class="btn btn-info" id="btnDownload">Download</button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Transactions</h6></div>
    <div class="card-body">
        <div class="mb-3" id="summaryPills">
            <span class="badge-pill-stat stat-success">Success : 0.00 0</span>
            <span class="badge-pill-stat stat-pending">Pending : 0.00 0</span>
            <span class="badge-pill-stat stat-failure">Failure : 0.00 0</span>
            <span class="badge-pill-stat stat-refunded">Refunded : 0.00 0</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark"><tr>
                    <th>RECHARGE ID</th><th>DATE &amp; TIME</th><th>USER DETAILS</th><th>OPERATOR</th><th>CIRCLE</th><th>API</th>
                    <th>NUMBER</th><th>AMOUNT</th><th>STATUS</th><th>ROFR CHECK</th><th>ROFR RECV</th>
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
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function payload(){return{_token:csrf,show:$('#show').val(),page:page,from_date:$('#from_date').val(),to_date:$('#to_date').val(),api_id:$('#api_id').val(),user_id:$('#user_id').val(),provider_id:$('#provider_id').val(),circle_id:$('#circle_id').val(),status:$('#status').val(),number:$('#number').val(),amount:$('#amount').val(),roffer_type:$('#roffer_type').val()};}
function load(){ $.post('{{ route("rOfferReportList") }}', payload(), function(res){
    $('#body').html(res.rows); var s=res.summary||{};
    $('#summaryPills').html(
        '<span class="badge-pill-stat stat-success">Success : '+(s.success_amt||'0.00')+' '+(s.success_cnt||0)+'</span>'+
        '<span class="badge-pill-stat stat-pending">Pending : '+(s.pending_amt||'0.00')+' '+(s.pending_cnt||0)+'</span>'+
        '<span class="badge-pill-stat stat-failure">Failure : '+(s.failure_amt||'0.00')+' '+(s.failure_cnt||0)+'</span>'+
        '<span class="badge-pill-stat stat-refunded">Refunded : '+(s.refunded_amt||'0.00')+' '+(s.refunded_cnt||0)+'</span>'
    );
    var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
    $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
    $('#btnPrev').prop('disabled',page<=1); $('#btnNext').prop('disabled',page>=last);
},'json'); }
$('#user_id').select2({placeholder:'Search user by firm name, mobile, email, id.',allowClear:true,ajax:{url:'{{ route("generalRoutingsSearchUsers") }}',dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:(d&&d.results)?d.results:[]};}}});
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#show').on('change', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
$('#btnDownload').on('click', function(){ var f=$('<form method="POST" action="{{ route("rOfferReportDownload") }}">'); $.each(payload(),function(k,v){f.append($('<input type="hidden">').attr('name',k).val(v));}); $('body').append(f);f.submit();f.remove(); });
load();
</script>
@endsection
