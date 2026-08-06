@extends('layouts.master')
@section('title') Consumption Report @endsection
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container{width:100%!important}</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Consumption Report</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From Date</label><input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To Date</label><input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">API</label><select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label mb-0">User / Client</label><select class="form-select" id="user_id"><option value=""></option></select>
                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="include_child"><label class="form-check-label" for="include_child">Tick to see child transactions also.</label></div>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">Service Type</label><select class="form-select" id="service_id"><option value="">All Services</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ strtoupper($s->service_name) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Operator</label><select class="form-select" id="provider_id"><option value="">Select Operator</option>@foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->provider_name }}</option>@endforeach</select>
                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="circle_wise"><label class="form-check-label" for="circle_wise">Circle Wise</label></div>
            </div>
            <div class="col-md-auto d-flex gap-2"><button type="button" class="btn btn-success" id="btnSubmit">Submit</button><button type="button" class="btn btn-info" id="btnDownload">Download</button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Transactions</h6></div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-1 mb-3"><span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select>
            <span class="text-muted small">entries</span></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark"><tr><th>SR NO</th><th>OPERATOR</th><th>CIRCLE</th><th>TXNS</th><th>MRP</th></tr></thead>
                <tbody id="body"><tr><td colspan="5" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
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
function payload(){return{_token:csrf,show:$('#show').val(),page:page,from_date:$('#from_date').val(),to_date:$('#to_date').val(),api_id:$('#api_id').val(),user_id:$('#user_id').val(),include_child:$('#include_child').is(':checked')?1:0,service_id:$('#service_id').val(),provider_id:$('#provider_id').val(),circle_wise:$('#circle_wise').is(':checked')?1:0};}
function load(){ $.post('{{ route("consumptionReportList") }}', payload(), function(res){
    $('#body').html(res.rows); var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
    $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
    $('#btnPrev').prop('disabled',page<=1); $('#btnNext').prop('disabled',page>=last);
},'json'); }
$('#user_id').select2({placeholder:'Search user by firm name, mobile, email, id.',allowClear:true,ajax:{url:'{{ route("generalRoutingsSearchUsers") }}',dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:(d&&d.results)?d.results:[]};}}});
$('#btnSubmit').on('click', function(){ page=1; load(); });
$('#show').on('change', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
$('#btnDownload').on('click', function(){ var f=$('<form method="POST" action="{{ route("consumptionReportDownload") }}">'); $.each(payload(),function(k,v){f.append($('<input type="hidden">').attr('name',k).val(v));}); $('body').append(f);f.submit();f.remove(); });
load();
</script>
@endsection
