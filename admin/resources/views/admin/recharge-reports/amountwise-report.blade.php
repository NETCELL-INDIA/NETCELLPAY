@extends('layouts.master')
@section('title') Recharge Report (Amountwise) @endsection
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container{width:100%!important}</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0 text-danger">Recharge Report (Amountwise)</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From Date</label><input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To Date</label><input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">API</label><select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label mb-0">User / Client</label><select class="form-select" id="user_id"><option value=""></option></select></div>
            <div class="col-md-2"><label class="form-label mb-0">Service Type</label><select class="form-select" id="service_id"><option value="">All Services</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ strtoupper($s->service_name) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Operator</label><select class="form-select" id="provider_id"><option value="">Select Operator</option>@foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->provider_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Circle</label><select class="form-select" id="circle_id"><option value="">Select Circle</option>@foreach($circles as $c)<option value="{{ $c->id }}">{{ $c->state_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label mb-0">Status</label><select class="form-select" id="status"><option value="">Select Status</option><option value="Success">Success</option><option value="Pending">Pending</option><option value="Failure">Failure</option><option value="Refunded">Refunded</option></select></div>
            <div class="col-md-auto"><label class="form-label mb-0 d-block">View By</label>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="view_circle"><label class="form-check-label" for="view_circle">Circle</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="view_api"><label class="form-check-label" for="view_api">API</label></div>
            </div>
            <div class="col-md-auto d-flex gap-2"><button type="button" class="btn btn-success" id="btnSearch">Search Transactions</button><button type="button" class="btn btn-info" id="btnDownload">Download</button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Transactions</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark"><tr><th>SR NO</th><th>OPERATOR</th><th>CIRCLE</th><th>API</th><th>STATUS</th><th>AMOUNT</th><th>NO OF TXNS</th><th>TOTAL MRP</th><th>PERCENTAGE</th></tr></thead>
                <tbody id="body"><tr><td colspan="9" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
            </table>
        </div>
        <div class="text-muted mt-2" id="pageInfo">Showing 0 to 0 of 0 entries</div>
    </div>
</div>
@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var csrf='{{ csrf_token() }}';
function payload(){return{_token:csrf,from_date:$('#from_date').val(),to_date:$('#to_date').val(),api_id:$('#api_id').val(),user_id:$('#user_id').val(),service_id:$('#service_id').val(),provider_id:$('#provider_id').val(),circle_id:$('#circle_id').val(),status:$('#status').val(),view_circle:$('#view_circle').is(':checked')?1:0,view_api:$('#view_api').is(':checked')?1:0};}
function load(){ $.post('{{ route("amountwiseReportList") }}', payload(), function(res){ $('#body').html(res.rows); $('#pageInfo').text('Showing results'); },'json'); }
$('#user_id').select2({placeholder:'Search user by firm name, mobile, email, id.',allowClear:true,ajax:{url:'{{ route("generalRoutingsSearchUsers") }}',dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:(d&&d.results)?d.results:[]};}}});
$('#btnSearch').on('click', load);
$('#btnDownload').on('click', function(){ var f=$('<form method="POST" action="{{ route("amountwiseReportDownload") }}">'); $.each(payload(),function(k,v){f.append($('<input type="hidden">').attr('name',k).val(v));}); $('body').append(f);f.submit();f.remove(); });
load();
</script>
@endsection
