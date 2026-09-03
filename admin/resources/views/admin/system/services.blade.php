@extends('layouts.master')
@section('title') Services @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
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
                <h4 class="card-title mb-0 flex-grow-1">Operator Type / Services</h4>
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
                    <h5 class="modal-title" id="detailsModalLabel">Edit Operator Type</h5>
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
                        <label class="col-form-label">Status:</label>
                        <select class="form-select" name="status" id="status">
                            <option value="1">Active</option>
                            <option value="0">Deactive</option>
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
    fetchAll(1,10);
    function tableSearch(page) {
        limit = $('#page_limit').val();
        page = page;
        fetchAll(page,limit);
    }
    $(document).on('change','#page_limit',function(){
        page = 1;
        limit = $('#page_limit').val();
        fetchAll(page,limit);
    });

    $(document).on('keyup','#searchValueTable',function(){
        var value = $( this ).val();
        if (this.value.length < 1) {
            $("#pagination_table tr").css("display", "");
        } else {
            $("#pagination_table tbody tr:not(:contains('"+this.value+"'))").css("display", "none");
            $("#pagination_table tbody tr:contains('"+this.value+"')").css("display", "");
        }
        //console.log(search);
    });
    function fetchAll(page,limit) {
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('servicesList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',page,limit},
            success: function(res) {
                $("#list_result").html(res);
                // var table = new DataTable('#scroll-vertical', {
                //     "scrollY": "250px",
                //     "scrollCollapse": true,
                //     "paging": false
                // });
                // $('#example').DataTable({
                //     order: [0, 'desc']
                // });
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

    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: '{{ route('servicesGet') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', id: id },
            success: function(data) {
                if (data.type === 'success') {
                    $('#edit_id').val(data.data.id);
                    $('#service_name').val(data.data.service_name);
                    $('#status').val(String(data.data.status));
                    $('#service_icon').val('');
                    $('#iconPreview').attr('src', data.data.icon_url || '{{ asset('assets/images/users/user-dummy-img.jpg') }}');
                    $('#detailsModal').modal('show');
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
                    Error_Msg('Updated', data.message, 'success');
                    fetchAll(1, $('#page_limit').val() || 10);
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
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

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
