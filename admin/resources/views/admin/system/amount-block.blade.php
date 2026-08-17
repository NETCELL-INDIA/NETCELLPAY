@extends('layouts.master')
@section('title') Amount Block @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet"
    type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') System @endslot
@slot('title')Amount Block @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Amount Block List</h4>
                <div class="flex-shrink-0">
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create
                            New</button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>


<!-- Details Modals -->
<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel"
    aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Create Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="live-preview">
                        <div class="row gy-4">
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label class="form-label mb-0">Services</label>
                                    <select class="form-select mb-3" name="service_id" id="service_id">
                                        <option selected value="0">All</option>
                                        <option value="1">MOBILE</option>
                                        <option value="2">DTH</option>
                                        <option value="4">POSTPAID</option>
                                        <!-- <option value="14">GOOGLE PLAY</option> -->
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label class="form-label mb-0">Providers</label>
                                    <select class="form-select mb-3 provider_id" name="provider_id" id="provider_id">
                                        <option selected value="0">All</option>

                                    </select>
                                    <input type="hidden" name="edit_provider_id" id="edit_provider_id">
                                </div>
                            </div>
                             <!--end col-->
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="amount_block" class="form-label">Amount: <a style="color: red">*</a> e.g 100,200,300</label>
                                    <input type="text" class="form-control" name="amount_block" id="amount_block" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label class="form-label">Status:</label>
                                    <select class="form-select mb-3 status" aria-label="Default select example"
                                        name="status">
                                        <option selected="">Select Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Deactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--end row-->
                    </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="edit_details_btn">Save Changes</button>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@endsection
@section('script')
<script>
    $(document).on('change', '#page_limit', function () {
        page = 1;
        limit = $('#page_limit').val();
        fetchAll(page, limit);
    });

    $(document).on('keyup', '#searchValueTable', function () {
        var value = $(this).val();
        if (this.value.length < 1) {
            $("#pagination_table tr").css("display", "");
        } else {
            $("#pagination_table tbody tr:not(:contains('" + this.value + "'))").css("display", "none");
            $("#pagination_table tbody tr:contains('" + this.value + "')").css("display", "");
        }
        //console.log(search);
    });

    fetchAll(1, 10);
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function Error_Msg(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: {
                confirmButton: 'btn btn-primary w-xs mt-2',
            },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function tableSearch(page) {
        limit = $('#page_limit').val();
        page = page;
        fetchAll(page, limit);
    }

    function fetchAll(page, limit) {
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('amountBlockList') }}',
            method: 'post',
            data: { _token: '{{csrf_token()}}', page, limit },
            success: function (res) {
                $("#list_result").html(res);
            }
        });
    }

    $(document).on('click', '.deleteData', function (e) {
        e.preventDefault();
        let id = $(this).attr('id');
        let csrf = '{{ csrf_token() }}';
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('amountBlockDelete') }}',
                    method: 'post',
                    data: {
                        id: id,
                        _token: csrf
                    },
                    success: function (data) {
                        if (data.type == "error") {
                            Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                        } else if (data.type == "success") {
                            Swal.fire(
                                'Deleted!',
                                data.message,
                                'success'
                            )
                            fetchAll(1, 10);
                        } else {
                            Error_Msg("Oops...", "Something went wrong!", "error");
                        }

                    },
                    error: function (jqXhr, textStatus, errorThrown) {
                        Error_Msg("Oops...", "Something went wrong!", "error");
                    }
                });
            }
        })
    });

    $(document).on('click', '.editDetails', function (e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
            url: '{{ route('amountBlockGet') }}',
            method: 'post',
            data: {
                id: id,
                _token: '{{ csrf_token() }}'
            },
            success: function (data) {
                if (data.type == "error") {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                } else if (data.type == "success") {
                   // onChangeProvider(data.data.service_id);
                    $("#edit_id").val(data.data.id);
                    $("#service_id").val(data.data.service_id).change();
                    $("#amount_block").val(data.data.amount);
                    $(".status").val(data.data.status).change();
                    $("#provider_id").val(data.data.provider_id).change();
                    $("#edit_provider_id").val(data.data.provider_id);
                    $('#detailsModalLabel').text('Edit Details');
                    $('#detailsModal').modal('show');
                } else {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                }
            },
            error: function (jqXhr, textStatus, errorThrown) {
                Error_Msg("Oops...", "Something went wrong!", "error");
            }
        });
    });

    $("#edit_details_form").submit(function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('amountBlockUpdate') }}',
            method: 'post',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (data) {
                if (data.type == "error") {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                    $("#edit_details_btn").text('Save Changes');
                    $('#edit_details_btn').prop('disabled', false);
                } else if (data.type == "success") {
                    Error_Msg("Updated", "Updated Successfully!", "success");
                    fetchAll(1, 10);
                    $("#edit_details_btn").text('Save Changes');
                    $('#edit_details_btn').prop('disabled', false);
                    $("#edit_details_form")[0].reset();
                    $("#detailsModal").modal('hide');
                } else {
                    Error_Msg("Oops...", "Something went wrong!", "error");
                    $("#edit_details_btn").text('Save Changes');
                    $('#edit_details_btn').prop('disabled', false);
                }
            },
            error: function (jqXhr, textStatus, errorThrown) {
                Error_Msg("Oops...", "Something went wrong!", "error");
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }
        });
    });

    $(document).on('change','#service_id',function(){
        var service_id = $("#service_id").val();
        onChangeProvider(service_id);
    });

    function onChangeProvider(service_id) {
        $.ajax({
            url: '{{ route('rechargeGetProvider') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',service_id},
            success: function(res) {
                //$('#user_list').show();
                console.log(res.data);
                
                $('#provider_id').empty();
                htmlView = '<option selected value="0">All</option>';
                for(let i = 0; i < res.data.length; i++){
                    htmlView += '<option value="'+res.data[i].id+'">'+res.data[i].provider_name+'</option>';
                }
                $('#provider_id').append(htmlView);
            }
        });
    }

    function createNew() {
        $('#detailsModal').modal('show');
        $("#edit_details_form")[0].reset();
        $("#edit_id").val(0);
        $('#detailsModal').modal('show');
    } 
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

{{--
<script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}



@endsection