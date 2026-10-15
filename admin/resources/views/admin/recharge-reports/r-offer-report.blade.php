@extends('layouts.master')
@section('title') R-Offer Report @endsection
@section('css')
<style>
.badge-pill-stat{display:inline-block;min-width:140px;padding:8px 14px;border-radius:999px;font-weight:600;border:2px solid;margin:0 6px 8px 0;background:#fff}
.stat-success{color:#198754;border-color:#198754}
.stat-failure{color:#dc3545;border-color:#dc3545}
.date-note{color:#dc3545;font-size:12px;font-weight:600}
</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">R-Offer Report</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <p class="text-muted small mb-3">Shows R-Offer check logs from Plan / Roffer API (not recharge transactions). Use Plan Logs Report for Plans / HLR / DTH.</p>
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From Date</label><input type="date" class="form-control" id="from_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To Date</label><input type="date" class="form-control" id="to_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-3"><label class="form-label mb-0">Number</label><input type="text" class="form-control" id="number" placeholder="Mobile number"><div class="date-note mt-1">NOTE : Confirm dates before searching.</div></div>
            <div class="col-md-1"><label class="form-label mb-0">Show</label><select class="form-select" id="show"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select></div>
            <div class="col-md-auto d-flex gap-2">
                <button type="button" class="btn btn-success" id="btnSearch">Search</button>
                <button type="button" class="btn btn-info" id="btnDownload">Download</button>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">List of R-Offer Checks</h6></div>
    <div class="card-body">
        <div class="mb-3" id="summaryPills">
            <span class="badge-pill-stat stat-success">Offers Found : 0</span>
            <span class="badge-pill-stat stat-failure">No Offers : 0</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark"><tr>
                    <th>TXN ID</th><th>DATE &amp; TIME</th><th>NUMBER</th><th>OPERATOR</th><th>TYPE</th>
                    <th>STATUS</th><th>OFFERS</th><th>ACTION</th>
                </tr></thead>
                <tbody id="body"><tr><td colspan="8" class="text-center text-muted py-4">No data available in table</td></tr></tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <div id="pageInfo" class="text-muted">Showing 0 to 0 of 0 entries</div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev">Previous</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNext">Next</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">R-Offer Request / Response</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <h6>Request</h6>
                <pre id="logReq" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre>
                <h6>Response</h6>
                <pre id="logRes" class="bg-light p-2" style="white-space:pre-wrap;max-height:220px;overflow:auto"></pre>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function payload(){
    return {_token:csrf, show:$('#show').val(), page:page, from_date:$('#from_date').val(), to_date:$('#to_date').val(), number:$('#number').val()};
}
function load(){
    $.post('{{ route("rOfferReportList") }}', payload(), function(res){
        $('#body').html(res.rows);
        var s=res.summary||{};
        $('#summaryPills').html(
            '<span class="badge-pill-stat stat-success">Offers Found : '+(s.success_cnt||0)+'</span>'+
            '<span class="badge-pill-stat stat-failure">No Offers : '+(s.failure_cnt||0)+'</span>'
        );
        var p=res.pagination||{};
        page=p.page||1; last=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
        $('#btnPrev').prop('disabled', page<=1);
        $('#btnNext').prop('disabled', page>=last);
    }, 'json').fail(function(){
        $('#body').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load</td></tr>');
    });
}
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#show').on('change', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
$('#btnDownload').on('click', function(){
    var f=$('<form method="POST" action="{{ route("rOfferReportDownload") }}">');
    $.each(payload(), function(k,v){ f.append($('<input type="hidden">').attr('name',k).val(v)); });
    $('body').append(f); f.submit(); f.remove();
});
function logText(raw){
    raw = raw == null ? '' : String(raw);
    try { return JSON.stringify(JSON.parse(raw), null, 2); } catch (e) { return raw; }
}
$(document).on('click', '.btn-view-roffer', function(){
    var $btn = $(this);
    $('#logReq').text(logText($btn.attr('data-req')));
    $('#logRes').text(logText($btn.attr('data-res')));
    $('#logModal').modal('show');
});
load();
</script>
@endsection
