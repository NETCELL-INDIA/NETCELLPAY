@extends('layouts.master')
@section('title') Operator API Switch @endsection
@section('css')
<style>
.op-routing-wrap { overflow-x: auto; }
.op-routing-table { min-width: 1400px; }
.op-routing-table th, .op-routing-table td { vertical-align: middle; font-size: 12px; }
.op-routing-table .form-select, .op-routing-table .form-control { font-size: 12px; min-width: 120px; }
.op-name { font-weight: 700; font-size: 14px; }
.op-orbit label { font-size: 11px; margin-bottom: 2px; }
</style>
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Operator API Switch</h4>
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0 fw-semibold">Select Service Type</label>
                <select class="form-select" id="service_id" style="width:auto;min-width:160px;">
                    @foreach($services as $s)
                        <option value="{{ $s->id }}" @selected($s->id == $defaultService)>{{ strtolower($s->service_name) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <h6 class="mb-0 text-white">List</h6>
    </div>
    <div class="card-body">
        <div class="op-routing-wrap">
            <table class="table table-bordered op-routing-table mb-0" id="opTable">
                <thead class="table-dark">
                    <tr>
                        <th>Operator</th>
                        <th>R-Offer</th>
                        <th>Primary API</th>
                        <th>Backup API 1</th>
                        <th>Backup API 2</th>
                        <th>Backup API 3</th>
                        <th>Extra API 5</th>
                        <th>Extra API 6</th>
                        <th>Rehit / Pending</th>
                    </tr>
                </thead>
                <tbody id="opBody">
                    <tr><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="button" class="btn btn-primary" id="btnSave">Save</button>
            <button type="button" class="btn btn-danger" id="btnReset">Reset</button>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
<script>
var csrf = '{{ csrf_token() }}';
var apis = @json($apis);
var currentRows = [];

function apiOptions(selected) {
    var html = '<option value="0">NO API</option>';
    apis.forEach(function (a) {
        html += '<option value="' + a.id + '"' + (String(selected) === String(a.id) ? ' selected' : '') + '>' + a.api_name + '</option>';
    });
    return html;
}

function rofferApiOptions(selected) {
    var html = '<option value="0">Select API</option>';
    apis.forEach(function (a) {
        html += '<option value="' + a.id + '"' + (String(selected) === String(a.id) ? ' selected' : '') + '>' + a.api_name + '</option>';
    });
    return html;
}

function orbitOptions(selected) {
    var html = '';
    for (var i = 1; i <= 10; i++) {
        html += '<option value="' + i + '"' + (parseInt(selected, 10) === i ? ' selected' : '') + '>' + i + '</option>';
    }
    return html;
}

function renderRows(rows) {
    currentRows = rows || [];
    if (!currentRows.length) {
        $('#opBody').html('<tr><td colspan="9" class="text-center text-muted py-4">No operators for this service</td></tr>');
        return;
    }
    var html = '';
    currentRows.forEach(function (row, idx) {
        html += '<tr data-idx="' + idx + '">' +
            '<td><div class="op-name">' + (row.provider_name || '-') + '</div>' +
            '<div class="op-orbit mt-2"><label>Primary Orbit</label>' +
            '<select class="form-select form-select-sm field" data-field="primary_orbit">' + orbitOptions(row.primary_orbit) + '</select></div></td>' +
            '<td>' +
            '<label class="small">Min. R-Offer (%)</label><input type="number" class="form-control form-control-sm field mb-1" data-field="min_roffer_pct" value="' + (row.min_roffer_pct || 0) + '">' +
            '<label class="small">R-Offer API 1</label><select class="form-select form-select-sm field mb-1" data-field="roffer_api_1">' + rofferApiOptions(row.roffer_api_1) + '</select>' +
            '<label class="small">Extra Params 1</label><input type="text" class="form-control form-control-sm field mb-1" data-field="extra_params_1" value="' + (row.extra_params_1 || '') + '">' +
            '<label class="small">R-Offer API 2</label><select class="form-select form-select-sm field mb-1" data-field="roffer_api_2">' + rofferApiOptions(row.roffer_api_2) + '</select>' +
            '<label class="small">Extra Params 2</label><input type="text" class="form-control form-control-sm field" data-field="extra_params_2" value="' + (row.extra_params_2 || '') + '">' +
            '</td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_1">' + apiOptions(row.primary_api_1) + '</select></td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_2">' + apiOptions(row.primary_api_2) + '</select></td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_3">' + apiOptions(row.primary_api_3) + '</select></td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_4">' + apiOptions(row.primary_api_4) + '</select></td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_5">' + apiOptions(row.primary_api_5) + '</select></td>' +
            '<td><select class="form-select form-select-sm field" data-field="primary_api_6">' + apiOptions(row.primary_api_6) + '</select></td>' +
            '<td>' +
            '<label class="small">Rehit API</label><select class="form-select form-select-sm field mb-1" data-field="rehit_api_id">' + apiOptions(row.rehit_api_id) + '</select>' +
            '<label class="small">After Pending API</label><select class="form-select form-select-sm field mb-1" data-field="pending_api_id">' + apiOptions(row.pending_api_id) + '</select>' +
            '<label class="small">Routing Type</label><select class="form-select form-select-sm field" data-field="routing_type">' +
            '<option value="PendingCount"' + (row.routing_type === 'PendingCount' ? ' selected' : '') + '>pending count</option>' +
            '<option value="OneByOne"' + (row.routing_type === 'OneByOne' ? ' selected' : '') + '>one by one</option>' +
            '<option value="LimitRotation"' + (row.routing_type === 'LimitRotation' ? ' selected' : '') + '>limit rotation</option>' +
            '</select></td></tr>';
    });
    $('#opBody').html(html);
}

function collectRows() {
    var rows = [];
    $('#opBody tr').each(function () {
        var idx = $(this).data('idx');
        if (idx === undefined) return;
        var base = currentRows[idx] || {};
        var row = {
            provider_id: base.provider_id,
            provider_name: base.provider_name
        };
        $(this).find('.field').each(function () {
            row[$(this).data('field')] = $(this).val();
        });
        rows.push(row);
    });
    return rows;
}

function loadOperators() {
    $('#opBody').html('<tr><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr>');
    $.post('{{ route("operatorRoutingList") }}', { _token: csrf, service_id: $('#service_id').val() }, function (res) {
        if (res && res.type === 'success') renderRows(res.data);
        else $('#opBody').html('<tr><td colspan="9" class="text-center text-danger">Failed to load</td></tr>');
    }, 'json');
}

$('#service_id').on('change', loadOperators);
$('#btnReset').on('click', loadOperators);
$('#btnSave').on('click', function () {
    $.post('{{ route("operatorRoutingSave") }}', {
        _token: csrf,
        service_id: $('#service_id').val(),
        rows: collectRows()
    }, function (res) {
        alert(res.message || (res.type === 'success' ? 'Saved' : 'Failed'));
        if (res.type === 'success') loadOperators();
    }, 'json');
});

loadOperators();
</script>
@endsection
