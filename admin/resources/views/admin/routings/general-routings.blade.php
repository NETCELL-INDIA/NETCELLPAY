@extends('layouts.master')

@section('title')
General Routings
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Routings</h4>
            <button type="button" class="btn btn-primary btn-sm" id="btnAddRouting">
                <i class="ri-add-line"></i> Add Routing
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <h6 class="mb-0 text-white">Filters</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Operator</label>
                <select class="form-select" id="filter_provider">
                    <option value="">Select Operator</option>
                    @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->provider_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Circle</label>
                <select class="form-select" id="filter_circle">
                    <option value="">All Circle</option>
                    @foreach($circles as $c)
                        <option value="{{ $c->id }}">{{ $c->state_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">User / Client</label>
                <select class="form-select" id="filter_user">
                    <option value="">All Users</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">API</label>
                <select class="form-select" id="filter_api">
                    <option value="">ALL APIS</option>
                    @foreach($apis as $a)
                        <option value="{{ $a->id }}">{{ $a->api_name }}</option>
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
        <div class="rb-list-toolbar">
            <div>
                Show
                <select class="form-select form-select-sm d-inline-block mx-1" id="pageSize" style="width:70px">
                    <option value="100" selected>100</option>
                    <option value="50">50</option>
                    <option value="25">25</option>
                </select>
                entries
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0" id="routingTable">
                <thead class="table-dark">
                    <tr>
                        <th>Operator</th>
                        <th>Circle</th>
                        <th>User</th>
                        <th>Amounts</th>
                        <th>Primary APIs</th>
                        <th>Other APIs</th>
                        <th style="min-width:140px">Priority</th>
                        <th style="min-width:120px">Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="routingBody">
                    <tr><td colspan="9" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="routingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Routing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="routingForm">
                    @csrf
                    <input type="hidden" name="id" id="routing_id">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Users</label>
                            <select class="form-select" name="user_id" id="form_user">
                                <option value="0">All Users</option>
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="only_user" id="only_user" value="1">
                                <label class="form-check-label" for="only_user">Tick to apply for selected user only</label>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Operator <span class="text-danger">*</span></label>
                            <select class="form-select" name="provider_id" id="form_provider" required>
                                <option value="">Select Operator</option>
                                @foreach($providers as $p)
                                    <option value="{{ $p->id }}">{{ $p->provider_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Circle</label>
                            <select class="form-select" name="circle_id" id="form_circle">
                                <option value="0">All Circles</option>
                                @foreach($circles as $c)
                                    <option value="{{ $c->id }}">{{ $c->state_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" id="form_priority">
                                <option value="1">1 (High)</option>
                                <option value="2">2 (Medium)</option>
                                <option value="3">3 (Low)</option>
                                <option value="4">4 (Very Low)</option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Amounts <small class="text-muted">(Ex: 1-148,149,150-200)</small></label>
                            <input type="text" class="form-control" name="amounts" id="form_amounts" placeholder="1-148,149,150-200">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Primary Rehit</label>
                            <select class="form-select" name="primary_rehit" id="form_primary_rehit">
                                @for($i=0;$i<=5;$i++)
                                    <option value="{{ $i }}" @selected($i===5)>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Routing Type</label>
                            <select class="form-select" name="routing_type" id="form_routing_type">
                                <option value="PendingCount">Pending Count</option>
                                <option value="OneByOne">One By One / Rotational</option>
                                <option value="LimitRotation">Rotational with Counts</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded" style="background:#3b82f6;">
                                <div class="row g-2">
                                    @for($i=1;$i<=6;$i++)
                                    <div class="col-md-4">
                                        <label class="form-label text-white">Primary API {{ $i }}@if($i===1) <span class="text-warning">*</span>@endif</label>
                                        <select class="form-select" name="primary_api_{{ $i }}" id="form_primary_api_{{ $i }}" @if($i===1) required @endif>
                                            <option value="0">No Primary API</option>
                                            @foreach($apis as $a)
                                                <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Rehit API</label>
                            <select class="form-select" name="rehit_api_id" id="form_rehit_api">
                                <option value="0">No Rehit API</option>
                                @foreach($apis as $a)
                                    <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">After Pending API</label>
                            <select class="form-select" name="pending_api_id" id="form_pending_api">
                                <option value="0">No After Pending API</option>
                                @foreach($apis as $a)
                                    <option value="{{ $a->id }}">{{ $a->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="form_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnSaveRouting">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
<script>
var routingRows = {};
var csrf = '{{ csrf_token() }}';

function moneyLabel() {}

function loadRoutings() {
    $('#routingBody').html('<tr><td colspan="9" class="text-center text-muted">Loading...</td></tr>');
    $.ajax({
        url: '/admin/routings/general/list',
        method: 'POST',
        dataType: 'json',
        data: {
            _token: csrf,
            provider_id: $('#filter_provider').val(),
            circle_id: $('#filter_circle').val(),
            user_id: $('#filter_user').val(),
            api_id: $('#filter_api').val()
        },
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#routingBody').html('<tr><td colspan="9" class="text-center text-danger">Failed to load</td></tr>');
                return;
            }
            if (!res.data.length) {
                $('#routingBody').html('<tr><td colspan="9" class="text-center text-muted">No record found</td></tr>');
                return;
            }
            routingRows = {};
            var html = '';
            res.data.forEach(function (row) {
                routingRows[row.id] = row;
                html += '<tr>' +
                    '<td>' + row.provider_name + '</td>' +
                    '<td>' + row.circle_name + '</td>' +
                    '<td>' + row.user_name + (row.only_user === 'Yes' ? '<br><small><b>(Only User)</b></small>' : '') + '</td>' +
                    '<td>' + (row.amounts || '-') + '</td>' +
                    '<td>' + row.primary_apis + '<br><small><b>Type:</b> ' + row.routing_type + ' | <b>Rehit:</b> ' + row.primary_rehit + '</small></td>' +
                    '<td>' + row.other_apis + '</td>' +
                    '<td><select class="form-select form-select-sm js-priority" data-id="' + row.id + '">' +
                        '<option value="1"' + (row.priority == 1 ? ' selected' : '') + '>1 (High)</option>' +
                        '<option value="2"' + (row.priority == 2 ? ' selected' : '') + '>2 (Medium)</option>' +
                        '<option value="3"' + (row.priority == 3 ? ' selected' : '') + '>3 (Low)</option>' +
                        '<option value="4"' + (row.priority == 4 ? ' selected' : '') + '>4 (Very Low)</option>' +
                    '</select></td>' +
                    '<td><select class="form-select form-select-sm js-status" data-id="' + row.id + '">' +
                        '<option value="Active"' + (row.status === 'Active' ? ' selected' : '') + '>Active</option>' +
                        '<option value="Inactive"' + (row.status === 'Inactive' ? ' selected' : '') + '>Inactive</option>' +
                    '</select></td>' +
                    '<td class="text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-success me-1 js-edit" data-id="' + row.id + '"><i class="ri-pencil-line"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-warning js-delete" data-id="' + row.id + '"><i class="ri-delete-bin-line"></i></button>' +
                    '</td></tr>';
            });
            $('#routingBody').html(html);
        },
        error: function () {
            $('#routingBody').html('<tr><td colspan="9" class="text-center text-danger">Failed to load</td></tr>');
        }
    });
}

function resetForm() {
    $('#routingForm')[0].reset();
    $('#routing_id').val('');
    $('#form_primary_rehit').val('5');
    $('#only_user').prop('checked', false);
}

function openEdit(id) {
    var row = routingRows[id];
    if (!row) return;
    resetForm();
    $('#routing_id').val(row.id);
    $('#form_provider').val(row.provider_id);
    $('#form_circle').val(row.circle_id || 0);
    $('#form_amounts').val(row.amounts === '-' ? '' : row.amounts);
    $('#form_priority').val(row.priority);
    $('#form_primary_rehit').val(row.primary_rehit);
    $('#form_routing_type').val(row.routing_type);
    $('#form_rehit_api').val(row.rehit_api_id || 0);
    $('#form_pending_api').val(row.pending_api_id || 0);
    $('#form_status').val(row.status);
    $('#only_user').prop('checked', row.only_user === 'Yes');
    if (row.user_id) {
        if (!$('#form_user option[value="' + row.user_id + '"]').length) {
            $('#form_user').append('<option value="' + row.user_id + '">' + row.user_name + '</option>');
        }
        $('#form_user').val(row.user_id);
    }
    var ids = String(row.primary_api_ids || '').split(',');
    for (var i = 1; i <= 6; i++) {
        $('#form_primary_api_' + i).val(ids[i - 1] || 0);
    }
    new bootstrap.Modal(document.getElementById('routingModal')).show();
}

function searchUsers(term, $select, selectedId) {
    $.get('/admin/routings/general/search-users', { q: term || '' }, function (res) {
        var html = '<option value="0">All Users</option>';
        (res.results || []).forEach(function (u) {
            html += '<option value="' + u.id + '">' + u.text + '</option>';
        });
        $select.html(html);
        if (selectedId) $select.val(selectedId);
    });
}

$(function () {
    searchUsers('', $('#filter_user'));
    searchUsers('', $('#form_user'));
    loadRoutings();

    $('#filter_provider,#filter_circle,#filter_user,#filter_api').on('change', loadRoutings);

    $('#btnAddRouting').on('click', function () {
        resetForm();
        searchUsers('', $('#form_user'));
        new bootstrap.Modal(document.getElementById('routingModal')).show();
    });

    $('#btnSaveRouting').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/admin/routings/general/save',
            method: 'POST',
            dataType: 'json',
            data: $('#routingForm').serialize(),
            success: function (res) {
                $btn.prop('disabled', false);
                if (res.type === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('routingModal')).hide();
                    if (window.Success_Msg) Success_Msg('Success', res.message); else alert(res.message);
                    loadRoutings();
                } else {
                    if (window.Error_Msg) Error_Msg('Error', res.message || 'Failed'); else alert(res.message || 'Failed');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Validation failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors)[0][0];
                }
                if (window.Error_Msg) Error_Msg('Error', msg); else alert(msg);
            }
        });
    });

    $(document).on('click', '.js-edit', function () { openEdit($(this).data('id')); });
    $(document).on('click', '.js-delete', function () {
        var id = $(this).data('id');
        if (!confirm('Do you want to delete this routing?')) return;
        $.post('/admin/routings/general/delete', { _token: csrf, id: id }, function (res) {
            if (res.type === 'success') loadRoutings();
            else alert(res.message || 'Delete failed');
        });
    });
    $(document).on('change', '.js-priority,.js-status', function () {
        var id = $(this).data('id');
        var payload = { _token: csrf, id: id };
        if ($(this).hasClass('js-priority')) payload.priority = $(this).val();
        if ($(this).hasClass('js-status')) payload.status = $(this).val();
        $.post('/admin/routings/general/update-field', payload);
    });

    // ensure list loads even if other scripts delay ready
    setTimeout(loadRoutings, 100);
});
</script>
@endsection
