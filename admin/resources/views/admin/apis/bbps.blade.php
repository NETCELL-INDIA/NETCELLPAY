@extends('layouts.master')
@section('title') BBPS Biller / Fetch API @endsection
@section('content')
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="mb-0">BBPS Biller &amp; Fetch API</h4></div></div></div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Bill Fetch APIs</h6></div>
    <div class="card-body">
        <form id="bbpsSet" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Bill Fetch API</label>
                <select class="form-select" name="bbps_fetch_api_id" id="bbps_fetch_api_id">
                    @foreach($apis as $api)
                        <option value="{{ $api->id }}" {{ (string)$fetchApi === (string)$api->id ? 'selected' : '' }}>{{ $api->api_name }} (#{{ $api->id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Biller Params / Operator-code API</label>
                <select class="form-select" name="bbps_params_api_id" id="bbps_params_api_id">
                    @foreach($apis as $api)
                        <option value="{{ $api->id }}" {{ (string)$paramsApi === (string)$api->id ? 'selected' : '' }}>{{ $api->api_name }} (#{{ $api->id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto"><button class="btn btn-success" type="submit">Save API Settings</button></div>
        </form>
        <p class="text-muted small mt-2 mb-0">Fetch API = live bill amount. Params API = operator code mapping used to load biller fields.</p>
    </div>
</div>
<div class="card">
    <div class="card-header bg-primary"><h6 class="mb-0 text-white">Billers</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3"><label class="form-label">Service</label>
                <select class="form-select" id="service_id"><option value="0">All</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->service_name }}</option>@endforeach</select>
            </div>
            <div class="col-md-4"><label class="form-label">Search</label><input class="form-control" id="q" placeholder="Biller name"></div>
            <div class="col-md-auto"><button type="button" class="btn btn-success" id="btnSearch">Search</button></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped"><thead class="table-dark"><tr>
                <th>ID</th><th>Biller</th><th>Service</th><th>Operator code</th><th>Params</th><th>Status</th><th></th>
            </tr></thead><tbody id="body"></tbody></table>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <div id="pageInfo" class="text-muted"></div>
            <div class="btn-group"><button class="btn btn-sm btn-outline-secondary" id="btnPrev">Previous</button><button class="btn btn-sm btn-outline-secondary" id="btnNext">Next</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="billerModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Biller parameters</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form id="billerForm"><div class="modal-body">
        <input type="hidden" name="provider_id" id="provider_id">
        <p id="biller_name" class="fw-semibold"></p>
        <div class="mb-2"><label class="form-label">Biller ID / Operator code</label><input class="form-control" name="biller_id" id="biller_id" required></div>
        <div class="mb-2"><label class="form-label">Category ID</label><input class="form-control" name="category_id" id="category_id"></div>
        <div class="mb-2"><label class="form-label">Biller data (JSON)</label><textarea class="form-control font-monospace" name="biller_data" id="biller_data" rows="10"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-success" type="submit">Save</button></div></form>
</div></div></div>
@endsection
@section('script')
<script>
var csrf='{{ csrf_token() }}', page=1, last=1;
function load(){
    $.post('{{ route("adminBbpsList") }}', {_token:csrf, page:page, show:10, service_id:$('#service_id').val(), q:$('#q').val()}, function(res){
        $('#body').html(res.rows);
        var p=res.pagination||{}; page=p.page||1; last=p.last_page||1;
        $('#pageInfo').text('Showing '+(p.from||0)+' to '+(p.to||0)+' of '+(p.total||0));
        $('#btnPrev').prop('disabled', page<=1); $('#btnNext').prop('disabled', page>=last);
    }, 'json');
}
$('#btnSearch').on('click', function(){ page=1; load(); });
$('#btnPrev').on('click', function(){ if(page>1){page--;load();} });
$('#btnNext').on('click', function(){ if(page<last){page++;load();} });
$('#bbpsSet').on('submit', function(e){
    e.preventDefault();
    $.post('{{ route("adminBbpsSaveSettings") }}', {_token:csrf, bbps_fetch_api_id:$('#bbps_fetch_api_id').val(), bbps_params_api_id:$('#bbps_params_api_id').val()}, function(res){
        Error_Msg(res.type==='success'?'Saved':'Error', res.message, res.type==='success'?'success':'error');
    }, 'json');
});
$(document).on('click', '.btn-bbps-edit', function(){
    $.post('{{ route("adminBbpsGetBiller") }}', {_token:csrf, id:$(this).data('id')}, function(res){
        if(res.type!=='success'){ Error_Msg('Error', res.message, 'error'); return; }
        var d=res.data; $('#provider_id').val(d.provider_id); $('#biller_name').text(d.provider_name);
        $('#biller_id').val(d.biller_id); $('#category_id').val(d.category_id); $('#biller_data').val(d.biller_data);
        $('#billerModal').modal('show');
    }, 'json');
});
$('#billerForm').on('submit', function(e){
    e.preventDefault();
    $.post('{{ route("adminBbpsSaveBiller") }}', $(this).serialize()+'&_token='+csrf, function(res){
        Error_Msg(res.type==='success'?'Saved':'Error', res.message, res.type==='success'?'success':'error');
        if(res.type==='success'){ $('#billerModal').modal('hide'); load(); }
    }, 'json');
});
load();
</script>
@endsection
