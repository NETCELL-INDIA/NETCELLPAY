@extends('layouts.master')
@section('title') SMS API List @endsection
@section('css')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') SMS API List @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">SMS API List</h4>
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create New</button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Use placeholders in Request URL: <code>{MOB}</code>, <code>{MSG}</code>, <code>{TMPID}</code>, <code>{USER}</code>, <code>{PASS}</code>, <code>{SENDER}</code>.
                    Primary API is used for OTP and system SMS. Company Settings SMS URL is used as fallback if no primary API is set.
                </p>
                <div id="list_result">
                    <h4 class="text-center text-secondary my-3">Loading...</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="modal" tabindex="-1" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">SMS API Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit_details_form">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">API Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="api_name" id="api_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="api_method" id="api_method">
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Request URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="request_url" id="request_url" placeholder="https://example.com/sms?mobile={MOB}&message={MSG}&template={TMPID}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Username / API Key</label>
                            <input type="text" class="form-control" name="api_username" id="api_username">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <input type="text" class="form-control" name="api_password" id="api_password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sender ID</label>
                            <input type="text" class="form-control" name="sender_id" id="sender_id">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="edit_details_btn">Save Changes</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function notify(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function createNew() {
        $('#edit_id').val(0);
        $('#api_name').val('');
        $('#api_method').val('GET');
        $('#request_url').val('');
        $('#api_username').val('');
        $('#api_password').val('');
        $('#sender_id').val('');
        $('#status').val('1');
        $('#detailsModalLabel').text('Create SMS API');
        $('#detailsModal').modal('show');
    }

    function fetchAll() {
        $('#list_result').html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('smsApiList') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                $('#list_result').html(res);
                if ($('#scroll-vertical').length) {
                    new DataTable('#scroll-vertical', {
                        scrollY: '420px',
                        scrollCollapse: true,
                        paging: false
                    });
                }
            }
        });
    }

    fetchAll();

    $(document).on('click', '.editDetails', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('smsApiGet') }}',
            method: 'post',
            data: { id: $(this).attr('id'), _token: '{{ csrf_token() }}' },
            success: function (data) {
                if (data.type !== 'success') {
                    notify('Error', data.message, 'error');
                    return;
                }
                $('#edit_id').val(data.data.id);
                $('#api_name').val(data.data.api_name);
                $('#api_method').val(data.data.api_method);
                $('#request_url').val(data.data.request_url);
                $('#api_username').val(data.data.api_username || '');
                $('#api_password').val(data.data.api_password || '');
                $('#sender_id').val(data.data.sender_id || '');
                $('#status').val(String(data.data.status));
                $('#detailsModalLabel').text('Edit SMS API');
                $('#detailsModal').modal('show');
            }
        });
    });

    $('#edit_details_btn').on('click', function () {
        $.ajax({
            url: '{{ route('smsApiUpdate') }}',
            method: 'post',
            data: $('#edit_details_form').serialize(),
            success: function (data) {
                if (data.type === 'success') {
                    notify('Success', data.message, 'success');
                    $('#detailsModal').modal('hide');
                    fetchAll();
                } else {
                    notify('Error', data.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.deleteData', function (e) {
        e.preventDefault();
        const id = $(this).attr('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This SMS API will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '{{ route('smsApiDelete') }}',
                method: 'post',
                data: { id: id, _token: '{{ csrf_token() }}' },
                success: function (data) {
                    if (data.type === 'success') {
                        notify('Deleted', data.message, 'success');
                        fetchAll();
                    } else {
                        notify('Error', data.message, 'error');
                    }
                }
            });
        });
    });

    $(document).on('click', '.setPrimary', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('smsApiSetPrimary') }}',
            method: 'post',
            data: { id: $(this).attr('id'), _token: '{{ csrf_token() }}' },
            success: function (data) {
                if (data.type === 'success') {
                    notify('Success', data.message, 'success');
                    fetchAll();
                } else {
                    notify('Error', data.message, 'error');
                }
            }
        });
    });
</script>
@endsection
