@extends('layouts.master')
@section('title') API Report @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">API Report</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From Date</label><input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To Date</label><input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">API/Supplier</label>
                <select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">Operator</label>
                <select class="form-select" id="provider_id"><option value="">Select Operator</option>@foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->provider_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">Circle</label>
                <select class="form-select" id="circle_id"><option value="">Select Circle</option>@foreach($circles as $c)<option value="{{ $c->id }}">{{ $c->state_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="button" class="btn btn-success" id="btnSubmit">Submit</button>
                <button type="button" class="btn btn-info" id="btnDownload">Download</button>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of Transactions</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>SR NO</th><th>API NAME</th><th>TRANSACTIONS</th><th>MRP</th><th>MARGIN</th><th>AVG MARGIN</th>
                        <th>SURCHARGE</th><th>AVG SURCHARGE</th><th>REFUND MRP</th><th>TOTAL MRP</th><th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="apiBody"><tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}';
function payload(){return{_token:csrf,from_date:$('#from_date').val(),to_date:$('#to_date').val(),api_id:$('#api_id').val(),provider_id:$('#provider_id').val(),circle_id:$('#circle_id').val()};}
function fetchApi(){
    $('#apiBody').html('<tr><td colspan="11" class="text-center text-muted">Loading...</td></tr>');
    $.post('{{ route("apiReportList") }}', payload(), function(res){
        $('#apiBody').html(res.rows||'<tr><td colspan="11" class="text-center">No data</td></tr>');
    },'json').fail(function(){$('#apiBody').html('<tr><td colspan="11" class="text-center text-danger">Failed</td></tr>');});
}
$('#btnSubmit').on('click', fetchApi);
$('#btnDownload').on('click', function(){
    var f=$('<form method="POST" action="{{ route("apiReportDownload") }}">');
    $.each(payload(),function(k,v){f.append($('<input type="hidden">').attr('name',k).val(v));});
    $('body').append(f);f.submit();f.remove();
});
fetchApi();
</script>
@endsection
