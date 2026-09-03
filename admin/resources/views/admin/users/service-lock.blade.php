@extends('layouts.master')
@section('title') User Service Lock @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">User-wise Service Lock</h4></div></div></div>
<div class="card">
    <div class="card-body">
        <p class="text-muted small">Tick a service to <strong>lock (OFF)</strong> for that user. Unticked = ON. Recharge / bill pay is blocked when locked.</p>
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6"><label class="form-label">Search user</label><input class="form-control" id="user_q" placeholder="Mobile / name / ID"></div>
        </div>
        <div id="user_results" class="list-group mb-3"></div>
        <div id="lockBox" style="display:none">
            <h6 id="lockUser"></h6>
            <input type="hidden" id="lock_user_id">
            <div id="svcList" class="row g-2"></div>
            <button type="button" class="btn btn-success mt-3" id="btnSaveLock">Save Locks</button>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', timer=null;
$('#user_q').on('keyup', function(){
    clearTimeout(timer);
    var q=$(this).val().trim();
    if(q.length<2){ $('#user_results').empty(); return; }
    timer=setTimeout(function(){
        $.post('{{ route("fundCreditDebitSearch") }}', {_token:csrf, q:q}, function(res){
            var html='';
            (res.data||[]).forEach(function(u){
                html+='<a href="javascript:void(0)" class="list-group-item list-group-item-action pick" data-id="'+u.id+'">'+u.name+' — '+u.mobile+'</a>';
            });
            $('#user_results').html(html);
        }, 'json');
    }, 250);
});
function loadUser(id){
    $.post('{{ route("userServiceLockLoad") }}', {_token:csrf, user_id:id}, function(res){
        if(res.type!=='success'){ Error_Msg('Error', res.message, 'error'); return; }
        $('#lock_user_id').val(res.user.id); $('#lockUser').text(res.user.label); $('#lockBox').show();
        var html='';
        (res.services||[]).forEach(function(s){
            html+='<div class="col-md-4"><label class="border rounded p-2 w-100"><input type="checkbox" class="form-check-input me-2 svc-lock" value="'+s.id+'" '+(s.locked?'checked':'')+'> '+s.name+' <small class="text-muted">(lock)</small></label></div>';
        });
        $('#svcList').html(html); $('#user_results').empty();
    }, 'json');
}
$(document).on('click', '.pick', function(){ loadUser($(this).data('id')); });
$('#btnSaveLock').on('click', function(){
    var locked=[]; $('.svc-lock:checked').each(function(){ locked.push($(this).val()); });
    $.post('{{ route("userServiceLockSave") }}', {_token:csrf, user_id:$('#lock_user_id').val(), locked:locked}, function(res){
        Error_Msg(res.type==='success'?'Saved':'Error', res.message, res.type==='success'?'success':'error');
    }, 'json');
});
</script>
@endsection
