@extends('layouts.master')
@section('title') Fund Credit / Debit @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">Fund Credit / Debit</h4></div></div></div>
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary"><h6 class="mb-0 text-white">Transfer / Reverse</h6></div>
            <div class="card-body">
                <p class="text-muted small">Same PIN-protected wallet move used on Users list. Transfer = credit user. Reverse = debit user.</p>
                <div class="mb-3">
                    <label class="form-label">Search user (mobile / name / ID)</label>
                    <input type="text" class="form-control" id="user_q" placeholder="Type at least 3 characters">
                    <div id="user_results" class="list-group mt-2"></div>
                </div>
                <form id="fundForm">
                    @csrf
                    <input type="hidden" name="id" id="user_id">
                    <div class="alert alert-light border" id="user_box" style="display:none">
                        <strong id="u_name"></strong><br>
                        <span id="u_meta" class="text-muted"></span><br>
                        Wallet: <strong id="u_wallet"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="type">
                                <option value="Transfer">Credit (Transfer)</option>
                                <option value="Reverse">Debit (Reverse)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="amount" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PIN</label>
                            <input type="password" class="form-control" name="t_pin" id="t_pin" maxlength="4" inputmode="numeric" required autocomplete="off" oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remark</label>
                            <textarea class="form-control" name="remark" id="remark" required maxlength="50"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3" id="btnSave">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}';
var timer=null;
$('#user_q').on('keyup', function(){
    clearTimeout(timer);
    var q=$(this).val().trim();
    if(q.length<2){ $('#user_results').empty(); return; }
    timer=setTimeout(function(){
        $.post('{{ route("fundCreditDebitSearch") }}', {_token:csrf, q:q}, function(res){
            var html='';
            (res.data||[]).forEach(function(u){
                html+='<a href="javascript:void(0)" class="list-group-item list-group-item-action pick-user" data-id="'+u.id+'" data-name="'+String(u.name).replace(/"/g,'&quot;')+'" data-outlet="'+String(u.outlet||'').replace(/"/g,'&quot;')+'" data-mobile="'+u.mobile+'" data-wallet="'+u.wallet_text+'">'+u.name+' — '+u.mobile+' — '+u.wallet_text+'</a>';
            });
            $('#user_results').html(html||'<div class="text-muted p-2">No user</div>');
        }, 'json');
    }, 250);
});
$(document).on('click', '.pick-user', function(){
    var $el=$(this);
    $('#user_id').val($el.data('id'));
    $('#u_name').text($el.data('name')+($el.data('outlet')?' / '+$el.data('outlet'):''));
    $('#u_meta').text($el.data('mobile')+' · ID '+$el.data('id'));
    $('#u_wallet').text($el.data('wallet'));
    $('#user_box').show();
    $('#user_results').empty();
    $('#user_q').val($el.data('mobile'));
});
$('#fundForm').on('submit', function(e){
    e.preventDefault();
    if(!$('#user_id').val()){ Error_Msg('Error','Select a user','error'); return; }
    if(String($('#t_pin').val()).length!==4){ Error_Msg('Error','Enter 4-digit PIN','error'); return; }
    $('#btnSave').prop('disabled', true).text('Please wait...');
    $.ajax({
        url:'{{ route("fundUpdate") }}',
        method:'post',
        data: new FormData(this),
        processData:false, contentType:false, dataType:'json',
        success:function(data){
            Error_Msg(data.type==='success'?'Updated':'Error', data.message, data.type==='success'?'success':'error');
            $('#btnSave').prop('disabled', false).text('Submit');
            if(data.type==='success'){ $('#fundForm')[0].reset(); $('#user_box').hide(); $('#user_id').val(''); }
        },
        error:function(){ Error_Msg('Error','Request failed','error'); $('#btnSave').prop('disabled', false).text('Submit'); }
    });
});
</script>
@endsection
