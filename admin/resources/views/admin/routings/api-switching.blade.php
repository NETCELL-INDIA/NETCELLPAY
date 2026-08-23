@extends('layouts.master')

@section('title') API Switching @endsection

@section('css')
<style>
.api-switch-card {
    border: 1px solid #e9edf4;
    border-radius: 8px;
    box-shadow: none;
}
.api-switch-card .card-header {
    background: #fff;
    border-bottom: 1px solid #eef2f7;
    padding: 14px 16px;
}
.api-switch-card .card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1b2559;
}
.api-switch-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.api-switch-table td {
    vertical-align: middle;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Routings @endslot
    @slot('title') API Switching @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card api-switch-card">
            <div class="card-header">
                <h4 class="card-title mb-0">API Switching</h4>
            </div>
            <div class="card-body">
                <form id="apiSwitchForm">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="operator_id" class="form-label">Operator <span class="text-danger">*</span></label>
                            <select class="form-select" name="operator_id" id="operator_id" required>
                                <option value="">Select Operator</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}">{{ $operator->provider_name }}{{ $operator->operator_type ? ' - ' . $operator->operator_type : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="api_id" class="form-label">Primary API</label>
                            <select class="form-select" name="api_id" id="api_id">
                                <option value="0">No API</option>
                                @foreach($apis as $api)
                                    <option value="{{ $api->id }}">{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="backup_api_id" class="form-label">Backup API 1</label>
                            <select class="form-select" name="backup_api_id" id="backup_api_id">
                                <option value="0">No Backup API</option>
                                @foreach($apis as $api)
                                    <option value="{{ $api->id }}">{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="backup_api2_id" class="form-label">Backup API 2</label>
                            <select class="form-select" name="backup_api2_id" id="backup_api2_id">
                                <option value="0">No Backup API</option>
                                @foreach($apis as $api)
                                    <option value="{{ $api->id }}">{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="backup_api3_id" class="form-label">Backup API 3</label>
                            <select class="form-select" name="backup_api3_id" id="backup_api3_id">
                                <option value="0">No Backup API</option>
                                @foreach($apis as $api)
                                    <option value="{{ $api->id }}">{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary" id="apiSwitchSaveBtn">Save API Switching</button>
                            <button type="button" class="btn btn-light" id="apiSwitchResetBtn">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card api-switch-card">
            <div class="card-header">
                <h4 class="card-title mb-0">Operator API Mapping</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered api-switch-table mb-0">
                        <thead>
                            <tr>
                                <th>Operator</th>
                                <th>Operator Type</th>
                                <th>Primary API</th>
                                <th>Backup API 1</th>
                                <th>Backup API 2</th>
                                <th>Backup API 3</th>
                                <th style="width: 120px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="apiSwitchTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
var csrf = '{{ csrf_token() }}';
var mappings = {};

function esc(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showMessage(type, message) {
    if (window.Swal) {
        Swal.fire({
            title: type === 'success' ? 'Success' : 'Error',
            text: message,
            icon: type,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false
        });
    } else {
        alert(message);
    }
}

function apiName(value) {
    return value ? esc(value) : '<span class="text-muted">No API</span>';
}

function renderMappings(rows) {
    mappings = {};

    if (!rows || !rows.length) {
        $('#apiSwitchTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No operators found</td></tr>');
        return;
    }

    var html = '';
    rows.forEach(function (row) {
        mappings[row.operator_id] = row;
        html += '<tr>' +
            '<td class="fw-semibold">' + esc(row.operator_name) + '</td>' +
            '<td>' + (row.operator_type ? esc(row.operator_type) : '<span class="text-muted">-</span>') + '</td>' +
            '<td>' + apiName(row.api_name) + '</td>' +
            '<td>' + apiName(row.backup_api_1_name) + '</td>' +
            '<td>' + apiName(row.backup_api_2_name) + '</td>' +
            '<td>' + apiName(row.backup_api_3_name) + '</td>' +
            '<td class="text-center text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-success editMapping me-1" data-operator-id="' + row.operator_id + '" title="Edit">' +
                    '<i class="ri-pencil-line"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-danger deleteMapping" data-operator-id="' + row.operator_id + '" title="Clear">' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';
    });

    $('#apiSwitchTableBody').html(html);
}

function loadMappings() {
    $('#apiSwitchTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>');

    $.post('{{ route('apiSwitchingList') }}', { _token: csrf }, function (res) {
        if (res && res.type === 'success') {
            renderMappings(res.data);
        } else {
            $('#apiSwitchTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load</td></tr>');
        }
    }, 'json').fail(function () {
        $('#apiSwitchTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load</td></tr>');
    });
}

function resetApiSwitchForm() {
    $('#apiSwitchForm')[0].reset();
    $('#operator_id').val('');
    $('#api_id, #backup_api_id, #backup_api2_id, #backup_api3_id').val(0);
    $('#apiSwitchSaveBtn').text('Save API Switching');
}

function setApiSwitchForm(row) {
    $('#operator_id').val(row.operator_id);
    $('#api_id').val(row.api_id || 0);
    $('#backup_api_id').val(row.backup_api_id || 0);
    $('#backup_api2_id').val(row.backup_api2_id || 0);
    $('#backup_api3_id').val(row.backup_api3_id || 0);
    $('#apiSwitchSaveBtn').text('Update API Switching');
}

$('#operator_id').on('change', function () {
    var row = mappings[$(this).val()];
    if (row) {
        setApiSwitchForm(row);
    } else {
        $('#api_id, #backup_api_id, #backup_api2_id, #backup_api3_id').val(0);
        $('#apiSwitchSaveBtn').text('Save API Switching');
    }
});

$('#apiSwitchResetBtn').on('click', resetApiSwitchForm);

$('#apiSwitchForm').on('submit', function (e) {
    e.preventDefault();

    if (!$('#operator_id').val()) {
        showMessage('error', 'Please select operator.');
        return;
    }

    $('#apiSwitchSaveBtn').prop('disabled', true).text('Please wait...');

    $.post('{{ route('apiSwitchingSave') }}', $(this).serialize(), function (res) {
        showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Saved');
        if (res.type === 'success') {
            loadMappings();
        }
    }, 'json').fail(function (xhr) {
        var message = 'Could not save API switching.';
        if (xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                var firstError = Object.values(xhr.responseJSON.errors)[0];
                message = Array.isArray(firstError) ? firstError[0] : firstError;
            } else if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
        }
        showMessage('error', message);
    }).always(function () {
        $('#apiSwitchSaveBtn').prop('disabled', false).text($('#operator_id').val() ? 'Update API Switching' : 'Save API Switching');
    });
});

$(document).on('click', '.editMapping', function () {
    var row = mappings[$(this).data('operator-id')];
    if (!row) {
        showMessage('error', 'Mapping details not found.');
        return;
    }

    setApiSwitchForm(row);
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

$(document).on('click', '.deleteMapping', function () {
    var operatorId = $(this).data('operator-id');

    var clearMapping = function () {
        $.post('{{ route('apiSwitchingDelete') }}', {
            _token: csrf,
            operator_id: operatorId
        }, function (res) {
            showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Cleared');
            if (res.type === 'success') {
                resetApiSwitchForm();
                loadMappings();
            }
        }, 'json').fail(function () {
            showMessage('error', 'Could not clear API switching.');
        });
    };

    if (window.Swal) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Primary and backup APIs will be cleared for this operator.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear it',
            customClass: {
                confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                cancelButton: 'btn btn-danger w-xs mt-2'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.isConfirmed) {
                clearMapping();
            }
        });
    } else if (confirm('Clear API switching for this operator?')) {
        clearMapping();
    }
});

loadMappings();
</script>
@endsection
