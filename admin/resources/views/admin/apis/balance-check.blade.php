@extends('layouts.master')
@section('title') API Balance Check @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box d-flex justify-content-between align-items-center"><h4 class="mb-0">API Live Balance</h4><button type="button" class="btn btn-success" id="btnAll">Check All</button></div></div></div>
<div class="card"><div class="card-body table-responsive">
    <p class="text-muted small">Uses each API’s Balance Check URL (same as Add/List APIs). Set the URL on the API if Check is disabled.</p>
    <table class="table table-bordered table-striped"><thead class="table-dark"><tr>
        <th>ID</th><th>API</th><th>Type</th><th>Status</th><th>URL</th><th>Balance</th><th>Response</th><th></th>
    </tr></thead><tbody id="body"></tbody></table>
</div></div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}';
function load(){ $.post('{{ route("apiBalanceList") }}', {_token:csrf}, function(res){ $('#body').html(res.rows); }, 'json'); }
function checkOne(id, $btn){
    var $tr=$('tr[data-id="'+id+'"]');
    $tr.find('.bal-cell').text('Checking...');
    $.post('{{ route("apiBalanceCheck") }}', {_token:csrf, id:id}, function(res){
        if(res.type!=='success'){ $tr.find('.bal-cell').text('Error'); Error_Msg('Error', res.message, 'error'); return; }
        $tr.find('.bal-cell').text(res.balance);
        $tr.find('.bal-raw').text(res.raw);
        if($btn) $btn.prop('disabled', false);
    }, 'json').fail(function(){ $tr.find('.bal-cell').text('Failed'); });
}
$(document).on('click', '.btn-check-bal', function(){ var $b=$(this); $b.prop('disabled', true); checkOne($b.data('id'), $b); });
$('#btnAll').on('click', function(){
    $('.btn-check-bal:not(:disabled)').each(function(i){ var id=$(this).data('id'); setTimeout(function(){ checkOne(id); }, i*400); });
});
load();
</script>
@endsection
