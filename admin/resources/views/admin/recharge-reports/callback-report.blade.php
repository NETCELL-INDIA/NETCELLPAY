@extends('layouts.master')
@section('title') Callback Report @endsection
@section('css')
<style>
.btn-icon{width:38px;height:38px;padding:0;display:inline-flex;align-items:center;justify-content:center}
.cb-stat{border-radius:10px;padding:14px 16px;border:1px solid #e9ecef}
.cb-stat .n{font-size:1.5rem;font-weight:700;line-height:1.1}
.cb-stat .l{font-size:.8rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em}
.cb-stat.active{outline:2px solid #0d6efd;outline-offset:1px}
</style>
@endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="mb-0">Callback Request — Sent / Received</h4>
</div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><button type="button" class="cb-stat w-100 text-start btn btn-light cb-dir-btn" data-dir="received"><div class="l">Received (Supplier)</div><div class="n text-success" id="statReceived">0</div></button></div>
    <div class="col-md-4"><button type="button" class="cb-stat w-100 text-start btn btn-light cb-dir-btn" data-dir="sent"><div class="l">Sent (Partner)</div><div class="n text-info" id="statSent">0</div></button></div>
    <div class="col-md-4"><button type="button" class="cb-stat w-100 text-start btn btn-light cb-dir-btn" data-dir="error"><div class="l">Errors</div><div class="n text-danger" id="statError">0</div></button></div>
</div>

<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label mb-0">From</label><input type="date" class="form-control" id="from_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To</label><input type="date" class="form-control" id="to_date" value="{{ $defaultDate }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">Direction</label>
                <select class="form-select" id="direction">
                    <option value="all">All</option>
                    <option value="received">Received</option>
                    <option value="sent">Sent</option>
                    <option value="error">Error</option>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">API</label>
                <select class="form-select" id="api_id"><option value="">ALL APIS</option>@foreach($apis as $a)<option value="{{ $a->id }}">{{ $a->api_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-3"><label class="form-label mb-0">Search</label><input type="text" class="form-control" id="search" placeholder="Order / Number / User / Log ID"></div>
            <div class="col-md-auto"><button type="button" class="btn btn-success btn-icon" id="btnSearch" title="Search"><i class="ri-search-line"></i></button></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary d-flex align-items-center justify-content-between">
        <h6 class="mb-0 text-white">Callback List</h6>
        <span class="badge bg-light text-dark" id="dirLabel">All</span>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-1 mb-3">
            <span class="text-muted small">Show</span>
            <select class="form-select form-select-sm" id="show" style="width:auto"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
            <span class="text-muted small">entries</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>TIME</th>
                        <th>DIR</th>
                        <th>ORDER</th>
                        <th>USER</th>
                        <th>OPERATOR / NUMBER</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                        <th>API / TYPE</th>
                        <th>PREVIEW</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cbBody"><tr><td colspan="10" class="text-center text-muted py-4">No data available</td></tr></tbody>
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

<div class="modal fade" id="cbModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Callback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cbDetailBody"><p class="text-muted mb-0">Loading...</p></div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', currentPage=1, lastPage=1;
function payload(){
    return {
        _token: csrf,
        show: $('#show').val(),
        page: currentPage,
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        direction: $('#direction').val(),
        api_id: $('#api_id').val(),
        search: $('#search').val()
    };
}
function updateDirLabel(){
    var v=$('#direction').val(), map={all:'All',received:'Received',sent:'Sent',error:'Error'};
    $('#dirLabel').text(map[v]||'All');
    $('.cb-dir-btn').removeClass('active');
    if(v!=='all'){ $('.cb-dir-btn[data-dir="'+v+'"]').addClass('active'); }
}
function fetchList(){
    updateDirLabel();
    $('#cbBody').html('<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>');
    $.post('{{ route("callbackReportList") }}', payload(), function(res){
        if(!res||res.type!=='success'){
            $('#cbBody').html('<tr><td colspan="10" class="text-center text-danger">Failed to load</td></tr>');
            return;
        }
        $('#cbBody').html(res.rows);
        var p=res.pagination||{};
        currentPage=p.page||1; lastPage=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0)+' entries');
        $('#btnPrev').prop('disabled', currentPage<=1);
        $('#btnNext').prop('disabled', currentPage>=lastPage);
        var s=res.stats||{};
        $('#statReceived').text(s.received||0);
        $('#statSent').text(s.sent||0);
        $('#statError').text(s.error||0);
    },'json').fail(function(){
        $('#cbBody').html('<tr><td colspan="10" class="text-center text-danger">Request failed</td></tr>');
    });
}
$(function(){
    $('#btnSearch').on('click', function(){ currentPage=1; fetchList(); });
    $('#show,#direction,#api_id').on('change', function(){ currentPage=1; fetchList(); });
    $('#search').on('keypress', function(e){ if(e.which===13){ currentPage=1; fetchList(); } });
    $('#btnPrev').on('click', function(){ if(currentPage>1){currentPage--;fetchList();} });
    $('#btnNext').on('click', function(){ if(currentPage<lastPage){currentPage++;fetchList();} });
    $('.cb-dir-btn').on('click', function(){
        var d=$(this).data('dir');
        var cur=$('#direction').val();
        $('#direction').val(cur===d?'all':d);
        currentPage=1; fetchList();
    });
    $(document).on('click','.btn-cb-detail', function(){
        var logId=$(this).data('log-id'), reportId=$(this).data('report-id')||'';
        $('#cbDetailBody').html('<p class="text-muted mb-0">Loading...</p>');
        $('#cbModal').modal('show');
        $.post('{{ route("callbackReportDetail") }}', {_token:csrf, log_id:logId, report_id:reportId}, function(res){
            if(!res||res.type!=='success'){
                $('#cbDetailBody').html('<p class="text-danger mb-0">'+(res&&res.message?res.message:'Failed')+'</p>');
                return;
            }
            $('#cbDetailBody').html(res.html||'');
        },'json').fail(function(){
            $('#cbDetailBody').html('<p class="text-danger mb-0">Request failed</p>');
        });
    });
    $(document).on('click','.btn-cb-resend', function(){
        var rid=$(this).data('report-id'), $btn=$(this), $msg=$('#cbResendMsg');
        if(!rid){ return; }
        $btn.prop('disabled', true);
        $msg.removeClass('text-success text-danger').addClass('text-muted').text('Sending...');
        $.post('{{ route("resendPartnerCallback") }}', {_token:csrf, report_id:rid}, function(res){
            if(res&&res.type==='success'){
                $msg.removeClass('text-muted text-danger').addClass('text-success').text(res.message||'Sent');
                setTimeout(function(){ fetchList(); }, 800);
            } else {
                $msg.removeClass('text-muted text-success').addClass('text-danger').text((res&&res.message)?res.message:'Failed');
            }
        },'json').fail(function(){
            $msg.removeClass('text-muted text-success').addClass('text-danger').text('Request failed');
        }).always(function(){
            $btn.prop('disabled', false);
        });
    });
    fetchList();
});
</script>
@endsection
