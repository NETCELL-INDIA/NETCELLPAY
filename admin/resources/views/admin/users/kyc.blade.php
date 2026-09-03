@extends('layouts.master')
@section('title') Manage KYC @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Manage KYC</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Filters</h6></div>
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label">KYC Status</label>
            <select class="form-select" id="kyc_status">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="All">All</option>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Search</label><input class="form-control" id="q" placeholder="Mobile / name / ID"></div>
        <div class="col-md-2"><label class="form-label">Show</label><select class="form-select" id="show"><option>10</option><option>25</option><option>50</option></select></div>
        <div class="col-md-auto"><button type="button" class="btn btn-success" id="btnSearch">Search</button></div>
    </div>
</div>
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped"><thead class="table-dark"><tr>
            <th>ID</th><th>Name</th><th>Mobile</th><th>City / State</th><th>KYC</th><th>Remark</th><th></th>
        </tr></thead><tbody id="body"></tbody></table>
        <div class="d-flex justify-content-between mt-2">
            <div id="pageInfo" class="text-muted"></div>
            <div class="btn-group"><button class="btn btn-sm btn-outline-secondary" id="btnPrev">Previous</button><button class="btn btn-sm btn-outline-secondary" id="btnNext">Next</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="kycModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">KYC Review</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="kyc_id">
        <div class="row">
            <div class="col-md-8" id="kyc_info"></div>
            <div class="col-md-4 text-center" id="kyc_pic"></div>
        </div>
        <h6 class="mt-3">Documents</h6>
        <div id="kyc_docs" class="d-flex flex-wrap gap-2 mb-3"></div>
        <form id="docForm" class="row g-2 align-items-end mb-3">
            <div class="col-md-4"><select class="form-select" name="doc_type"><option value="pan">PAN</option><option value="aadhaar_front">Aadhaar Front</option><option value="aadhaar_back">Aadhaar Back</option><option value="shop">Shop</option><option value="other">Other</option></select></div>
            <div class="col-md-5"><input type="file" class="form-control" name="file" required></div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100" type="submit">Upload</button></div>
        </form>
        <label class="form-label">Remark (required for Reject)</label>
        <textarea class="form-control" id="kyc_remark"></textarea>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-st="Pending">Pending</button>
        <button type="button" class="btn btn-danger" data-st="Rejected">Reject</button>
        <button type="button" class="btn btn-success" data-st="Approved">Approve</button>
    </div>
</div></div></div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function payload(){ return {_token:csrf, page:page, show:$('#show').val(), kyc_status:$('#kyc_status').val(), q:$('#q').val()}; }
function load(){
    $.post('{{ route("adminKycList") }}', payload(), function(res){
        $('#body').html(res.rows);
        var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0));
        $('#btnPrev').prop('disabled', page<=1); $('#btnNext').prop('disabled', page>=last);
    }, 'json');
}
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
function openKyc(id){
    $.post('{{ route("adminKycDetail") }}', {_token:csrf, id:id}, function(res){
        if(res.type!=='success'){ Error_Msg('Error', res.message, 'error'); return; }
        var d=res.data; $('#kyc_id').val(d.id); $('#kyc_remark').val(d.kyc_remark||'');
        $('#kyc_info').html('<p class="mb-1"><strong>'+d.name+'</strong> ('+d.outlet+')</p><p class="mb-1">'+d.mobile+' / '+d.email+'</p><p class="mb-1">'+d.address+'</p><p class="mb-1">'+d.city+', '+d.district+', '+d.state+'</p><p class="mb-1">A/C '+ (d.bank||'-') +' IFSC '+(d.ifsc||'-')+'</p><p>Status: <strong>'+d.kyc_status+'</strong></p>');
        $('#kyc_pic').html(d.profile_pic?'<img src="'+d.profile_pic+'" class="img-fluid rounded" style="max-height:160px">':'No photo');
        var docs='';
        (d.docs||[]).forEach(function(x){ docs+='<a class="btn btn-sm btn-soft-info" target="_blank" href="'+x.url+'">'+x.doc_type+'</a>'; });
        $('#kyc_docs').html(docs||'<span class="text-muted">No extra documents. Use profile photo + details, or upload below.</span>');
        $('#kycModal').modal('show');
    }, 'json');
}
$(document).on('click', '.btn-kyc-view', function(){ openKyc($(this).data('id')); });
$('.modal-footer [data-st]').on('click', function(){
    var st=$(this).data('st');
    $.post('{{ route("adminKycDecide") }}', {_token:csrf, id:$('#kyc_id').val(), kyc_status:st, kyc_remark:$('#kyc_remark').val()}, function(res){
        Error_Msg(res.type==='success'?'Updated':'Error', res.message, res.type==='success'?'success':'error');
        if(res.type==='success'){ $('#kycModal').modal('hide'); load(); }
    }, 'json');
});
$('#docForm').on('submit', function(e){
    e.preventDefault();
    var fd=new FormData(this); fd.append('_token', csrf); fd.append('id', $('#kyc_id').val());
    $.ajax({url:'{{ route("adminKycUpload") }}', method:'post', data:fd, processData:false, contentType:false, dataType:'json', success:function(res){
        Error_Msg(res.type==='success'?'Updated':'Error', res.message, res.type==='success'?'success':'error');
        if(res.type==='success'){ openKyc($('#kyc_id').val()); }
    }});
});
load();
</script>
@endsection
