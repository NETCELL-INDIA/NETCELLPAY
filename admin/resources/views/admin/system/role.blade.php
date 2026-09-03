@extends('layouts.master')
@section('title') Role @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') System @endslot
@slot('title')Role / Permissions @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Role List</h4>
                <button type="button" class="btn btn-info" id="createRoleBtn">Create Role</button>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>

<div id="roleModal" class="modal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="roleForm">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">Create Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="role_name" id="role_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="1">Active</option>
                            <option value="0">Deactive</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin" value="1">
                        <label class="form-check-label" for="is_admin">Can login to Admin panel (employee)</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">AP / MD / DT / RT roles cannot login to admin. Tick this only for office staff. After save, set Menu Permission.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="roleSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="permissionModal" class="modal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="permissionForm">
                @csrf
                <input type="hidden" name="id" id="permission_role_id">
                <div class="modal-header">
                    <h5 class="modal-title">Menu Permission — <span id="permission_role_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="permission_body" style="max-height:70vh;overflow:auto;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="permissionSaveBtn">Save Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
    function roleNotify(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function fetchAll() {
        $('#list_result').html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('roleList') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                $('#list_result').html(res);
            }
        });
    }

    $('#createRoleBtn').on('click', function() {
        $('#roleForm')[0].reset();
        $('#edit_id').val(0);
        $('#roleModalTitle').text('Create Role');
        $('#roleModal').modal('show');
    });

    $(document).on('click', '.editRole', function() {
        var id = $(this).data('id');
        $.post('{{ route('roleGet') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data.type !== 'success') {
                roleNotify('Error', data.message || 'Failed', 'error');
                return;
            }
            $('#edit_id').val(data.data.id);
            $('#role_name').val(data.data.role_name);
            $('#status').val(String(data.data.status));
            $('#is_admin').prop('checked', String(data.data.is_admin) === '1');
            if ([3,4,5,6].indexOf(parseInt(data.data.id, 10)) !== -1) {
                $('#is_admin').prop('checked', false).prop('disabled', true);
            } else {
                $('#is_admin').prop('disabled', false);
            }
            $('#roleModalTitle').text('Edit Role');
            $('#roleModal').modal('show');
        });
    });

    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        if (!$('#is_admin').is(':checked')) {
            fd.set('is_admin', '0');
        }
        $('#roleSaveBtn').prop('disabled', true).text('Please wait...');
        $.ajax({
            url: '{{ route('roleUpdate') }}',
            method: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(data) {
                $('#roleSaveBtn').prop('disabled', false).text('Save');
                if (data.type === 'success') {
                    $('#roleModal').modal('hide');
                    roleNotify('Saved', data.message, 'success');
                    fetchAll();
                    if (data.open_permission && data.id) {
                        openPermissions(data.id);
                    }
                } else {
                    roleNotify('Error', data.message || 'Failed', 'error');
                }
            },
            error: function() {
                $('#roleSaveBtn').prop('disabled', false).text('Save');
                roleNotify('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $(document).on('click', '.deleteRole', function() {
        var id = $(this).data('id');
        $.post('{{ route('roleDelete') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data.type === 'success') {
                fetchAll();
            } else {
                roleNotify('Error', data.message || 'Delete failed', 'error');
            }
        });
    });

    function openPermissions(id) {
        $.post('{{ route('rolePermissionsGet') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data.type !== 'success') {
                roleNotify('Error', data.message || 'Failed', 'error');
                return;
            }
            $('#permission_role_id').val(id);
            $('#permission_role_name').text(data.role_name);
            var html = '';
            var keys = data.keys || [];
            $.each(data.catalog || [], function(_, group) {
                html += '<div class="mb-3 border rounded p-2">';
                if (group.children && group.children.length) {
                    html += '<label class="fw-semibold"><input type="checkbox" class="form-check-input me-1 group-check"> ' + group.label + '</label>';
                    html += '<div class="ms-3 mt-2">';
                    $.each(group.children, function(_, child) {
                        var checked = keys.indexOf(child.key) !== -1 ? 'checked' : '';
                        html += '<div class="form-check"><input class="form-check-input menu-key" type="checkbox" name="keys[]" value="' + child.key + '" ' + checked + '> <label class="form-check-label">' + child.label + '</label></div>';
                    });
                    html += '</div>';
                } else {
                    var checked = keys.indexOf(group.key) !== -1 ? 'checked' : '';
                    html += '<div class="form-check"><input class="form-check-input menu-key" type="checkbox" name="keys[]" value="' + group.key + '" ' + checked + '> <label class="form-check-label">' + group.label + '</label></div>';
                }
                html += '</div>';
            });
            $('#permission_body').html(html);
            $('#permissionModal').modal('show');
        });
    }

    $(document).on('click', '.setPermission', function() {
        openPermissions($(this).data('id'));
    });

    $(document).on('change', '.group-check', function() {
        $(this).closest('.mb-3').find('.menu-key').prop('checked', $(this).is(':checked'));
    });

    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        $('#permissionSaveBtn').prop('disabled', true).text('Please wait...');
        $.ajax({
            url: '{{ route('rolePermissionsSave') }}',
            method: 'post',
            data: $(this).serialize(),
            success: function(data) {
                $('#permissionSaveBtn').prop('disabled', false).text('Save Permission');
                if (data.type === 'success') {
                    $('#permissionModal').modal('hide');
                    roleNotify('Saved', data.message, 'success');
                } else {
                    roleNotify('Error', data.message || 'Failed', 'error');
                }
            },
            error: function() {
                $('#permissionSaveBtn').prop('disabled', false).text('Save Permission');
                roleNotify('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    fetchAll();
</script>
@endsection
