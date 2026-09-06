@extends('layouts.master')
@section('title') Services @endsection
@section('css')
<style>
.svc-board { display: flex; flex-direction: column; gap: 1rem; }
.svc-block { border: 1px solid #e6ebf2; border-radius: 10px; overflow: hidden; background: #fff; }
.svc-head { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; background: #f4f7fb; border-bottom: 1px solid #e6ebf2; }
.svc-order { display: flex; align-items: center; gap: 0.4rem; }
.svc-num { min-width: 28px; height: 28px; border-radius: 50%; background: #405189; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; }
.svc-move { display: flex; flex-direction: column; }
.svc-move .btn { padding: 0 4px; line-height: 1; }
.svc-icon-btn { border: 1px dashed #b7c2d4; background: #fff; border-radius: 10px; padding: 4px 8px 6px; text-align: center; min-width: 72px; }
.svc-icon-btn img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; display: block; margin: 0 auto 4px; }
.svc-icon-btn span { font-size: 0.68rem; font-weight: 700; color: #405189; }
.svc-title { flex: 1; min-width: 0; }
.svc-title small { display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: .06em; color: #6b7790; }
.svc-title h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #1b2559; }
.svc-title p { margin: 0; font-size: 0.78rem; color: #6b7790; }
.svc-flags { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.svc-ops { font-size: 0.86rem; }
.svc-op-logo { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; }
@media (max-width: 768px) {
    .svc-head { flex-wrap: wrap; }
}
</style>
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') System @endslot
@slot('title')Services @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Services</h4>
                <button type="button" class="btn btn-primary btn-sm" id="addServiceBtn">
                    <i class="ri-add-line"></i> Add Service
                </button>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="modal" tabindex="-1" aria-labelledby="detailsModalLabel" data-bs-backdrop="static" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <img src="{{ asset('assets/images/users/user-dummy-img.jpg') }}" alt="Icon" id="iconPreview" class="rounded-3" style="width:72px;height:72px;object-fit:cover;border:1px solid #dbe3ef;">
                    </div>
                    <div class="mb-3">
                        <label for="service_name" class="col-form-label">Name:</label>
                        <input type="text" class="form-control" name="service_name" id="service_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_icon" class="col-form-label">Icon:</label>
                        <input type="file" class="form-control" name="service_icon" id="service_icon" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif">
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label" for="catalog_group">App section:</label>
                        <select class="form-select" name="catalog_group" id="catalog_group">
                            <option value="recharge">Recharge</option>
                            <option value="bbps">Bill Payments (BBPS)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label" for="service_status">Status:</label>
                        <select class="form-select" name="status" id="service_status">
                            <option value="1">ON</option>
                            <option value="0">OFF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Service Down:</label>
                        <select class="form-select" name="service_down" id="service_down">
                            <option value="0">UP</option>
                            <option value="1">DOWN</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="edit_details_btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
    fetchAll();
    function fetchAll() {
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('servicesList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                $("#list_result").html(res);
            }
        });
    }

    function Error_Msg(title,text,icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    var dummyIcon = '{{ asset('assets/images/users/user-dummy-img.jpg') }}';

    function openServiceForm(data) {
        $('#edit_id').val(data && data.id ? data.id : 0);
        $('#service_name').val(data ? (data.service_name || '') : '');
        $('#service_status').val(data ? String(data.status) : '1');
        $('#service_down').val(data && Number(data.service_down) === 1 ? '1' : '0');
        $('#catalog_group').val(data && data.catalog_group === 'recharge' ? 'recharge' : 'bbps');
        $('#service_icon').val('');
        $('#iconPreview').attr('src', (data && data.icon_url) ? data.icon_url : dummyIcon);
        $('#detailsModalLabel').text(data && data.id ? 'Edit Service' : 'Add Service');
        $('#edit_details_btn').text(data && data.id ? 'Save Changes' : 'Add Service');
        $('#detailsModal').modal('show');
    }

    $('#addServiceBtn').on('click', function () {
        openServiceForm(null);
    });

    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: '{{ route('servicesGet') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', id: id },
            success: function(data) {
                if (data.type === 'success') {
                    openServiceForm(data.data);
                } else {
                    Error_Msg('Error', data.message || 'Something went wrong!', 'error');
                }
            },
            error: function() {
                Error_Msg('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $('#service_icon').on('change', function() {
        if (this.files && this.files[0]) {
            $('#iconPreview').attr('src', URL.createObjectURL(this.files[0]));
        }
    });

    $('#edit_details_form').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        $('#edit_details_btn').text('Please wait...').prop('disabled', true);
        $.ajax({
            url: '{{ route('servicesUpdate') }}',
            method: 'post',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(data) {
                $('#edit_details_btn').text('Save Changes').prop('disabled', false);
                if (data.type === 'success') {
                    $('#detailsModal').modal('hide');
                    Error_Msg('Success', data.message, 'success');
                    fetchAll();
                } else {
                    Error_Msg('Error', data.message || 'Something went wrong!', 'error');
                }
            },
            error: function() {
                $('#edit_details_btn').text('Save Changes').prop('disabled', false);
                Error_Msg('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $(document).on('click', '.toggleServiceStatus', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.post('{{ route("servicesStatus") }}', {
            _token: '{{ csrf_token() }}',
            id: btn.data('id'),
            status: btn.data('status')
        }, function (data) {
            Error_Msg(data.type === 'success' ? 'Updated' : 'Error', data.message || 'Failed', data.type === 'success' ? 'success' : 'error');
            if (data.type === 'success') {
                fetchAll();
            }
        }, 'json').always(function () {
            btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.toggleServiceDown', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.post('{{ route("servicesDown") }}', {
            _token: '{{ csrf_token() }}',
            id: btn.data('id'),
            service_down: btn.data('down')
        }, function (data) {
            Error_Msg(data.type === 'success' ? 'Updated' : 'Error', data.message || 'Failed', data.type === 'success' ? 'success' : 'error');
            if (data.type === 'success') {
                fetchAll();
            }
        }, 'json').always(function () {
            btn.prop('disabled', false);
        });
    });

    function deleteService(id, confirmWithOperators) {
        $.post('{{ route("servicesDelete") }}', {
            _token: '{{ csrf_token() }}',
            id: id,
            confirm_with_operators: confirmWithOperators ? 1 : 0
        }, function (data) {
            if (data.type === 'confirm') {
                Swal.fire({
                    title: 'Delete service?',
                    text: data.message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        deleteService(id, true);
                    }
                });
                return;
            }
            if (data.type === 'success') {
                Error_Msg('Deleted', data.message, 'success');
                fetchAll();
            } else {
                Error_Msg('Error', data.message || 'Failed', 'error');
            }
        }, 'json').fail(function () {
            Error_Msg('Oops...', 'Something went wrong!', 'error');
        });
    }

    $(document).on('click', '.deleteService', function () {
        var id = $(this).data('id');
        var name = $(this).data('name') || 'this service';
        Swal.fire({
            title: 'Delete service?',
            text: name + ' will be removed from user web and app.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                deleteService(id, false);
            }
        });
    });

    $(document).on('click', '.moveItem', function () {
        var btn = $(this);
        if (btn.prop('disabled')) return;
        btn.prop('disabled', true);
        $.post('{{ route("servicesMove") }}', {
            _token: '{{ csrf_token() }}',
            type: btn.data('type'),
            id: btn.data('id'),
            direction: btn.data('direction')
        }, function (data) {
            if (data.type === 'success') {
                fetchAll();
            } else {
                Error_Msg('Error', data.message || 'Failed', 'error');
                btn.prop('disabled', false);
            }
        }, 'json').fail(function () {
            Error_Msg('Oops...', 'Something went wrong!', 'error');
            btn.prop('disabled', false);
        });
    });
</script>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

{{-- <script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}



@endsection
