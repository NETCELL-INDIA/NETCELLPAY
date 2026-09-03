@extends('layouts.master')
@section('title') Audit Log @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Audit Log</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-2"><label class="form-label">From</label><input type="date" class="form-control" id="from_date" value="{{ date('Y-m-d') }}"></div>
        <div class="col-md-2"><label class="form-label">To</label><input type="date" class="form-control" id="to_date" value="{{ date('Y-m-d') }}"></div>
        <div class="col-md-2"><label class="form-label">Module</label>
            <select class="form-select" id="module">
                <option value="All">All</option>
                <option value="fund">Fund</option>
                <option value="recharge_status">Recharge status</option>
                <option value="routing">Routing</option>
                <option value="kyc">KYC</option>
                <option value="service_lock">Service lock</option>
                <option value="bbps">BBPS</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Search</label><input class="form-control" id="q" placeholder="Admin / action / id"></div>
        <div class="col-md-auto"><button type="button" class="btn btn-success" id="btnSearch">Search</button></div>
    </div>
</div>
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered table-striped table-sm"><thead class="table-dark"><tr>
        <th>Time</th><th>Admin</th><th>Module</th><th>Action</th><th>Ref</th><th>Old</th><th>New</th><th>Remark</th>
    </tr></thead><tbody id="body"></tbody></table>
    <div class="d-flex justify-content-between mt-2">
        <div id="pageInfo" class="text-muted"></div>
        <div class="btn-group"><button class="btn btn-sm btn-outline-secondary" id="btnPrev">Previous</button><button class="btn btn-sm btn-outline-secondary" id="btnNext">Next</button></div>
    </div>
</div></div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function load(){
    $.post('{{ route("adminAuditList") }}', {_token:csrf, page:page, show:25, from_date:$('#from_date').val(), to_date:$('#to_date').val(), module:$('#module').val(), q:$('#q').val()}, function(res){
        $('#body').html(res.rows);
        var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0));
        $('#btnPrev').prop('disabled', page<=1); $('#btnNext').prop('disabled', page>=last);
    }, 'json');
}
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
load();
</script>
@endsection
