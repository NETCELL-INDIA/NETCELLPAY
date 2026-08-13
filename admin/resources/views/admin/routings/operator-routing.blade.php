@extends('layouts.master')
@section('title') Operator API Switch @endsection
@section('css')
<style>
.opsw-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.opsw-toolbar h4 { margin: 0; }
.opsw-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.opsw-actions .form-select { min-width: 180px; }
.opsw-card {
    border: 1px solid #e9edf4;
    border-radius: 10px;
    background: #fff;
    margin-bottom: 14px;
    overflow: hidden;
}
.opsw-card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f7;
}
.opsw-name {
    font-size: 16px;
    font-weight: 700;
    color: #1b2559;
    margin: 0;
}
.opsw-orbit {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 180px;
}
.opsw-orbit label {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}
.opsw-body { padding: 16px; }
.opsw-section { margin-bottom: 14px; }
.opsw-section:last-child { margin-bottom: 0; }
.opsw-section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 10px;
}
.opsw-card .form-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}
.opsw-empty {
    text-align: center;
    color: #94a3b8;
    padding: 48px 16px;
}
.opsw-sticky {
    position: sticky;
    bottom: 12px;
    z-index: 5;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 10px 12px;
    background: rgba(255,255,255,.92);
    border: 1px solid #e9edf4;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
}
</style>
@endsection
@section('content')
<div class="opsw-toolbar">
    <h4>Operator API Switch</h4>
    <div class="opsw-actions">
        <select class="form-select" id="service_id">
            @foreach($services as $s)
                <option value="{{ $s->id }}" @selected($s->id == $defaultService)>{{ $s->service_name }}</option>
            @endforeach
        </select>
        <input type="search" class="form-control" id="opSearch" placeholder="Search operator" style="max-width:200px;">
        <button type="button" class="btn btn-light" id="btnReset">Reset</button>
        <button type="button" class="btn btn-primary" id="btnSave">Save</button>
    </div>
</div>

<div id="opList">
    <div class="opsw-empty">Loading...</div>
</div>

<div class="opsw-sticky" id="opSticky" style="display:none;">
    <button type="button" class="btn btn-light" id="btnReset2">Reset</button>
    <button type="button" class="btn btn-primary" id="btnSave2">Save</button>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
<script>
var csrf = '{{ csrf_token() }}';
var apis = @json($apis);
var currentRows = [];

function esc(v) {
    return String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;');
}

function apiOptions(selected, emptyLabel) {
    var html = '<option value="0">' + (emptyLabel || 'No API') + '</option>';
    apis.forEach(function (a) {
        html += '<option value="' + a.id + '"' + (String(selected) === String(a.id) ? ' selected' : '') + '>' + esc(a.api_name) + '</option>';
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

function fieldCol(label, inner, col) {
    return '<div class="' + (col || 'col-md-4 col-lg-2') + '"><label class="form-label">' + label + '</label>' + inner + '</div>';
}

function selectField(field, selected, emptyLabel) {
    return '<select class="form-select field" data-field="' + field + '">' + apiOptions(selected, emptyLabel) + '</select>';
}

function renderRows(rows) {
    currentRows = rows || [];
    if (!currentRows.length) {
        $('#opList').html('<div class="card"><div class="opsw-empty">No operators for this service</div></div>');
        $('#opSticky').hide();
        return;
    }
    var html = '';
    currentRows.forEach(function (row, idx) {
        html += '<div class="opsw-card" data-idx="' + idx + '" data-name="' + esc((row.provider_name || '').toLowerCase()) + '">' +
            '<div class="opsw-card-head">' +
                '<h5 class="opsw-name">' + esc(row.provider_name || '-') + '</h5>' +
                '<div class="opsw-orbit">' +
                    '<label>Primary Orbit</label>' +
                    '<select class="form-select form-select-sm field" data-field="primary_orbit">' + orbitOptions(row.primary_orbit) + '</select>' +
                '</div>' +
            '</div>' +
            '<div class="opsw-body">' +
                '<div class="opsw-section">' +
                    '<div class="opsw-section-title">API routing</div>' +
                    '<div class="row g-3">' +
                        fieldCol('Primary API', selectField('primary_api_1', row.primary_api_1)) +
                        fieldCol('Backup API 1', selectField('primary_api_2', row.primary_api_2)) +
                        fieldCol('Backup API 2', selectField('primary_api_3', row.primary_api_3)) +
                        fieldCol('Backup API 3', selectField('primary_api_4', row.primary_api_4)) +
                        fieldCol('Extra API 5', selectField('primary_api_5', row.primary_api_5)) +
                        fieldCol('Extra API 6', selectField('primary_api_6', row.primary_api_6)) +
                    '</div>' +
                '</div>' +
                '<div class="opsw-section">' +
                    '<div class="opsw-section-title">R-Offer</div>' +
                    '<div class="row g-3">' +
                        fieldCol('Min. R-Offer (%)', '<input type="number" class="form-control field" data-field="min_roffer_pct" value="' + esc(row.min_roffer_pct || 0) + '">', 'col-md-4 col-lg-2') +
                        fieldCol('R-Offer API 1', selectField('roffer_api_1', row.roffer_api_1, 'Select API'), 'col-md-4 col-lg-2') +
                        fieldCol('Extra Params 1', '<input type="text" class="form-control field" data-field="extra_params_1" value="' + esc(row.extra_params_1 || '') + '">', 'col-md-4 col-lg-2') +
                        fieldCol('R-Offer API 2', selectField('roffer_api_2', row.roffer_api_2, 'Select API'), 'col-md-4 col-lg-2') +
                        fieldCol('Extra Params 2', '<input type="text" class="form-control field" data-field="extra_params_2" value="' + esc(row.extra_params_2 || '') + '">', 'col-md-4 col-lg-4') +
                    '</div>' +
                '</div>' +
                '<div class="opsw-section">' +
                    '<div class="opsw-section-title">Rehit / Pending</div>' +
                    '<div class="row g-3">' +
                        fieldCol('Rehit API', selectField('rehit_api_id', row.rehit_api_id), 'col-md-4') +
                        fieldCol('After Pending API', selectField('pending_api_id', row.pending_api_id), 'col-md-4') +
                        fieldCol('Routing Type',
                            '<select class="form-select field" data-field="routing_type">' +
                            '<option value="PendingCount"' + (row.routing_type === 'PendingCount' ? ' selected' : '') + '>Pending count</option>' +
                            '<option value="OneByOne"' + (row.routing_type === 'OneByOne' ? ' selected' : '') + '>One by one</option>' +
                            '<option value="LimitRotation"' + (row.routing_type === 'LimitRotation' ? ' selected' : '') + '>Limit rotation</option>' +
                            '</select>', 'col-md-4') +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    });
    $('#opList').html(html);
    $('#opSticky').show();
    filterOperators();
}

function filterOperators() {
    var q = ($('#opSearch').val() || '').toLowerCase().trim();
    $('.opsw-card').each(function () {
        var name = $(this).data('name') || '';
        $(this).toggle(!q || name.indexOf(q) !== -1);
    });
}

function collectRows() {
    var rows = [];
    $('#opList .opsw-card').each(function () {
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
    $('#opList').html('<div class="opsw-empty">Loading...</div>');
    $('#opSticky').hide();
    $.post('{{ route("operatorRoutingList") }}', { _token: csrf, service_id: $('#service_id').val() }, function (res) {
        if (res && res.type === 'success') renderRows(res.data);
        else $('#opList').html('<div class="opsw-empty text-danger">Failed to load</div>');
    }, 'json').fail(function () {
        $('#opList').html('<div class="opsw-empty text-danger">Failed to load</div>');
    });
}

function saveOperators() {
    $.post('{{ route("operatorRoutingSave") }}', {
        _token: csrf,
        service_id: $('#service_id').val(),
        rows: collectRows()
    }, function (res) {
        var ok = res && res.type === 'success';
        if (window.Swal) {
            Swal.fire({ title: ok ? 'Saved' : 'Failed', text: res.message || (ok ? 'Operator API switch saved' : 'Could not save'), icon: ok ? 'success' : 'error' });
        } else {
            alert(res.message || (ok ? 'Saved' : 'Failed'));
        }
        if (ok) loadOperators();
    }, 'json').fail(function () {
        if (window.Swal) Swal.fire({ title: 'Failed', text: 'Could not save', icon: 'error' });
        else alert('Failed');
    });
}

$('#service_id').on('change', loadOperators);
$('#opSearch').on('input', filterOperators);
$('#btnReset, #btnReset2').on('click', loadOperators);
$('#btnSave, #btnSave2').on('click', saveOperators);

loadOperators();
</script>
@endsection
