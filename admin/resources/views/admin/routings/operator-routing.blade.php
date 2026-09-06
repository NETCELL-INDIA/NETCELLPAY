@extends('layouts.master')

@section('title') Manage Operator @endsection

@section('css')
<style>
.operator-card {
    border: 1px solid #e9edf4;
    border-radius: 8px;
    box-shadow: none;
}
.operator-card .card-header {
    background: #fff;
    border-bottom: 1px solid #eef2f7;
    padding: 14px 16px;
}
.operator-card .card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1b2559;
}
.operator-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.operator-table td {
    vertical-align: middle;
}
.operator-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}
.operator-status-btn {
    min-width: 58px;
    font-weight: 700;
}
.operator-logo-preview {
    width: 72px;
    height: 72px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #dbe3ef;
    background: #f8fafc;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Routings @endslot
    @slot('title') Manage Operator @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card operator-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Manage Operator</h4>
                <button type="button" class="btn btn-primary" id="addOperatorBtn">
                    <i class="ri-add-line align-bottom me-1"></i> Add Operator
                </button>
            </div>
            <div class="card-body p-0">
                <div class="p-3 border-bottom">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="filter_operator_name" class="form-label">Operator Name</label>
                            <input type="text" class="form-control" id="filter_operator_name" placeholder="Search operator name">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_operator_type" class="form-label">Operator Type</label>
                            <select class="form-select" id="filter_operator_type">
                                <option value="">All Types</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary" id="operatorFilterSearch">
                                <i class="ri-search-line align-bottom me-1"></i> Search
                            </button>
                            <button type="button" class="btn btn-light" id="operatorFilterReset">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered operator-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Logo</th>
                                <th>Operator Name</th>
                                <th>Operator Type</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 110px;" class="text-center">Operator Down</th>
                                <th style="width: 150px;" class="text-center">User Wise Down</th>
                                <th style="width: 130px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="operatorTableBody">
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

<div class="modal fade" id="operatorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operatorModalTitle">Add Operator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="operatorForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="operator_id" id="operator_id" value="0">
                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="operator_name" class="form-label">Operator Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="operator_name" id="operator_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="operator_type" class="form-label">Operator Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="operator_type" id="operator_type" required>
                                <option value="">Select Operator Type</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="1">ON</option>
                                <option value="0">OFF</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="provider_down" class="form-label">Operator Down</label>
                            <select class="form-select" name="provider_down" id="provider_down">
                                <option value="0">UP</option>
                                <option value="1">DOWN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="logo" class="form-label">Operator Logo</label>
                            <input type="file" class="form-control" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif">
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset('assets/images/users/user-dummy-img.jpg') }}" alt="Operator Logo" class="operator-logo-preview" id="logoPreview">
                                <div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="removeLogoBtn" style="display:none;">
                                        <i class="ri-delete-bin-line align-bottom me-1"></i> Remove Logo
                                    </button>
                                    <div class="text-muted small mt-2">PNG, JPG, WEBP, GIF. Maximum 4 MB.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="operatorSaveBtn">Save Operator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="userDownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Wise Down - <span id="userDownOperatorName">Operator</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label for="userDownSearch" class="form-label">Search User</label>
                        <input type="text" class="form-control" id="userDownSearch" placeholder="Name / Outlet / Mobile / User ID">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="userDownSearchBtn">
                            <i class="ri-search-line align-bottom me-1"></i> Search
                        </button>
                    </div>
                    <div class="col-12">
                        <label for="userDownSearchResults" class="form-label">Search Results</label>
                        <select class="form-select" id="userDownSearchResults" size="5"></select>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-success" id="userDownAddBtn">
                            <i class="ri-user-add-line align-bottom me-1"></i> Add User Wise Down
                        </button>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold mb-3">Down Users</h6>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Outlet</th>
                                <th>Mobile</th>
                                <th style="width: 90px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="userDownTableBody">
                            <tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
var csrf = '{{ csrf_token() }}';
var defaultLogo = '{{ admin_asset('assets/images/users/user-dummy-img.jpg') }}';
var operators = {};
var currentDownOperatorId = 0;

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

function statusButton(row) {
    var isOn = Number(row.status) === 1;
    return '<button type="button" class="badge rounded-pill border-0 operator-status-btn ' + (isOn ? 'text-bg-success' : 'text-bg-danger') + ' toggleOperatorStatus" ' +
        'data-operator-id="' + row.operator_id + '" data-status="' + (isOn ? 0 : 1) + '">' + (isOn ? 'ON' : 'OFF') + '</button>';
}

function downButton(row) {
    var isDown = Number(row.provider_down) === 1;
    return '<button type="button" class="badge rounded-pill border-0 operator-status-btn ' + (isDown ? 'text-bg-danger' : 'text-bg-success') + ' toggleOperatorDown" ' +
        'data-operator-id="' + row.operator_id + '" data-down="' + (isDown ? 0 : 1) + '">' + (isDown ? 'DOWN' : 'UP') + '</button>';
}

function renderOperators(rows) {
    operators = {};

    if (!rows || !rows.length) {
        $('#operatorTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No operators found</td></tr>');
        return;
    }

    var html = '';
    rows.forEach(function (row) {
        operators[row.operator_id] = row;
        html += '<tr>' +
            '<td><img src="' + esc(row.logo_url || defaultLogo) + '" class="operator-logo" alt="' + esc(row.operator_name) + '" onerror="this.src=\'' + defaultLogo + '\'"></td>' +
            '<td class="fw-semibold">' + esc(row.operator_name) + '</td>' +
            '<td>' + (row.operator_type ? esc(row.operator_type) : '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-center">' + statusButton(row) + '</td>' +
            '<td class="text-center">' + downButton(row) + '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-primary manageUserDown" data-operator-id="' + row.operator_id + '">' +
                    '<i class="ri-user-settings-line align-bottom me-1"></i> Manage <span class="badge text-bg-danger ms-1">' + (row.user_down_count || 0) + '</span>' +
                '</button>' +
            '</td>' +
            '<td class="text-center text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-success editOperator me-1" data-operator-id="' + row.operator_id + '" title="Edit">' +
                    '<i class="ri-pencil-line"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-danger deleteOperator" data-operator-id="' + row.operator_id + '" title="Delete">' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';
    });

    $('#operatorTableBody').html(html);
}

function loadOperators() {
    $('#operatorTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>');

    $.post('{{ route('operatorRoutingList') }}', {
        _token: csrf,
        operator_name: $('#filter_operator_name').val(),
        operator_type: $('#filter_operator_type').val()
    }, function (res) {
        if (res && res.type === 'success') {
            renderOperators(res.data);
        } else {
            $('#operatorTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load</td></tr>');
        }
    }, 'json').fail(function () {
        $('#operatorTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load</td></tr>');
    });
}

$('#operatorFilterSearch').on('click', loadOperators);

$('#operatorFilterReset').on('click', function () {
    $('#filter_operator_name').val('');
    $('#filter_operator_type').val('');
    loadOperators();
});

$('#filter_operator_name').on('keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        loadOperators();
    }
});

$('#filter_operator_type').on('change', loadOperators);

function resetOperatorForm() {
    $('#operatorForm')[0].reset();
    $('#operator_id').val(0);
    $('#remove_logo').val(0);
    $('#status').val(1);
    $('#provider_down').val(0);
    $('#logoPreview').attr('src', defaultLogo);
    $('#removeLogoBtn').hide();
    $('#operatorModalTitle').text('Add Operator');
}

function setOperatorForm(row) {
    resetOperatorForm();
    $('#operator_id').val(row.operator_id);
    $('#operator_name').val(row.operator_name);
    $('#operator_type').val(row.service_id);
    $('#status').val(String(row.status));
    $('#provider_down').val(String(Number(row.provider_down) === 1 ? 1 : 0));
    $('#logoPreview').attr('src', row.logo_url || defaultLogo);
    $('#removeLogoBtn').toggle(!!row.provider_logo);
    $('#operatorModalTitle').text('Edit Operator');
}

function renderUserDowns(rows) {
    if (!rows || !rows.length) {
        $('#userDownTableBody').html('<tr><td colspan="4" class="text-center text-muted py-3">No down users found</td></tr>');
        return;
    }

    var html = '';
    rows.forEach(function (row) {
        html += '<tr>' +
            '<td>' + esc(row.user_name || ('User #' + row.user_id)) + '</td>' +
            '<td>' + esc(row.outlet_name || '-') + '</td>' +
            '<td>' + esc(row.mobile_number || '-') + '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-danger removeUserDown" data-id="' + row.id + '" title="Remove">' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';
    });

    $('#userDownTableBody').html(html);
}

function loadUserDowns() {
    if (!currentDownOperatorId) {
        return;
    }

    $('#userDownTableBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>');
    $.post('{{ route('operatorRoutingDownUsers') }}', {
        _token: csrf,
        operator_id: currentDownOperatorId
    }, function (res) {
        if (res && res.type === 'success') {
            renderUserDowns(res.data);
        } else {
            $('#userDownTableBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Failed to load</td></tr>');
        }
    }, 'json').fail(function () {
        $('#userDownTableBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Failed to load</td></tr>');
    });
}

function searchUserDownUsers() {
    if (!currentDownOperatorId) {
        return;
    }

    $('#userDownSearchResults').html('<option value="">Searching...</option>');
    $.post('{{ route('operatorRoutingDownUsersSearch') }}', {
        _token: csrf,
        operator_id: currentDownOperatorId,
        q: $('#userDownSearch').val()
    }, function (res) {
        var html = '<option value="">Select User</option>';
        if (res && res.type === 'success' && res.data && res.data.length) {
            res.data.forEach(function (user) {
                html += '<option value="' + user.id + '">' + esc(user.text) + '</option>';
            });
        } else {
            html += '<option value="">No users found</option>';
        }
        $('#userDownSearchResults').html(html);
    }, 'json').fail(function () {
        $('#userDownSearchResults').html('<option value="">Search failed</option>');
    });
}

$('#addOperatorBtn').on('click', function () {
    resetOperatorForm();
    $('#operatorModal').modal('show');
});

$(document).on('click', '.editOperator', function () {
    var row = operators[$(this).data('operator-id')];
    if (!row) {
        showMessage('error', 'Operator details not found.');
        return;
    }

    setOperatorForm(row);
    $('#operatorModal').modal('show');
});

$(document).on('click', '.manageUserDown', function () {
    var row = operators[$(this).data('operator-id')];
    if (!row) {
        showMessage('error', 'Operator details not found.');
        return;
    }

    currentDownOperatorId = row.operator_id;
    $('#userDownOperatorName').text(row.operator_name);
    $('#userDownSearch').val('');
    $('#userDownSearchResults').html('<option value="">Select User</option>');
    $('#userDownModal').modal('show');
    loadUserDowns();
    searchUserDownUsers();
});

$('#userDownSearchBtn').on('click', searchUserDownUsers);

$('#userDownSearch').on('keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        searchUserDownUsers();
    }
});

$('#userDownAddBtn').on('click', function () {
    var userId = $('#userDownSearchResults').val();
    if (!currentDownOperatorId || !userId) {
        showMessage('error', 'Please select a user.');
        return;
    }

    $(this).prop('disabled', true);
    $.post('{{ route('operatorRoutingDownUsersAdd') }}', {
        _token: csrf,
        operator_id: currentDownOperatorId,
        user_id: userId
    }, function (res) {
        showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Saved');
        if (res.type === 'success') {
            loadUserDowns();
            searchUserDownUsers();
            loadOperators();
        }
    }, 'json').fail(function (xhr) {
        showMessage('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not add user wise down.');
    }).always(function () {
        $('#userDownAddBtn').prop('disabled', false);
    });
});

$(document).on('click', '.removeUserDown', function () {
    var id = $(this).data('id');
    if (!id) {
        return;
    }

    $(this).prop('disabled', true);
    $.post('{{ route('operatorRoutingDownUsersRemove') }}', {
        _token: csrf,
        id: id
    }, function (res) {
        showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Removed');
        if (res.type === 'success') {
            loadUserDowns();
            searchUserDownUsers();
            loadOperators();
        }
    }, 'json').fail(function (xhr) {
        showMessage('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not remove user wise down.');
    }).always(function () {
        $('.removeUserDown').prop('disabled', false);
    });
});

$('#logo').on('change', function () {
    var file = this.files && this.files[0];
    if (!file) {
        return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
        $('#logoPreview').attr('src', e.target.result);
        $('#remove_logo').val(0);
        $('#removeLogoBtn').show();
    };
    reader.readAsDataURL(file);
});

$('#removeLogoBtn').on('click', function () {
    $('#logo').val('');
    $('#remove_logo').val(1);
    $('#logoPreview').attr('src', defaultLogo);
    $(this).hide();
});

$('#operatorForm').on('submit', function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    var logoInput = document.getElementById('logo');
    if (logoInput && logoInput.files && logoInput.files[0]) {
        formData.set('logo', logoInput.files[0]);
    } else {
        formData.delete('logo');
    }
    $('#operatorSaveBtn').prop('disabled', true).text('Please wait...');

    $.ajax({
        url: '{{ route('operatorRoutingSave') }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (res) {
            showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Saved');
            if (res.type === 'success') {
                $('#operatorModal').modal('hide');
                loadOperators();
            }
        },
        error: function (xhr) {
            var message = 'Could not save operator.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    var firstError = Object.values(xhr.responseJSON.errors)[0];
                    message = Array.isArray(firstError) ? firstError[0] : firstError;
                } else if (xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
            }
            showMessage('error', message);
        },
        complete: function () {
            $('#operatorSaveBtn').prop('disabled', false).text('Save Operator');
        }
    });
});

$(document).on('click', '.toggleOperatorDown', function () {
    var button = $(this);
    var operatorId = button.data('operator-id');
    var providerDown = button.data('down');

    button.prop('disabled', true);
    $.post('{{ route('operatorRoutingDown') }}', {
        _token: csrf,
        operator_id: operatorId,
        provider_down: providerDown
    }, function (res) {
        showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Operator down updated');
        if (res.type === 'success') {
            loadOperators();
        }
    }, 'json').fail(function () {
        showMessage('error', 'Could not update operator down.');
    }).always(function () {
        button.prop('disabled', false);
    });
});

$(document).on('click', '.toggleOperatorStatus', function () {
    var button = $(this);
    var operatorId = button.data('operator-id');
    var status = button.data('status');

    button.prop('disabled', true);
    $.post('{{ route('operatorRoutingStatus') }}', {
        _token: csrf,
        operator_id: operatorId,
        status: status
    }, function (res) {
        showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Status updated');
        if (res.type === 'success') {
            loadOperators();
        }
    }, 'json').fail(function () {
        showMessage('error', 'Could not update operator status.');
    }).always(function () {
        button.prop('disabled', false);
    });
});

$(document).on('click', '.deleteOperator', function () {
    var operatorId = $(this).data('operator-id');

    var deleteOperator = function () {
        $.post('{{ route('operatorRoutingDelete') }}', {
            _token: csrf,
            operator_id: operatorId
        }, function (res) {
            showMessage(res.type === 'success' ? 'success' : 'error', res.message || 'Deleted');
            if (res.type === 'success') {
                loadOperators();
            }
        }, 'json').fail(function () {
            showMessage('error', 'Could not delete operator.');
        });
    };

    if (window.Swal) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Selected operator will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            customClass: {
                confirmButton: 'btn btn-danger w-xs me-2 mt-2',
                cancelButton: 'btn btn-light w-xs mt-2'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.isConfirmed) {
                deleteOperator();
            }
        });
    } else if (confirm('Delete this operator?')) {
        deleteOperator();
    }
});

loadOperators();
</script>
@endsection
