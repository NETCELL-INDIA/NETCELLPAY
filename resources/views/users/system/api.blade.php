@extends('layouts.master')
@section('title') Apis @endsection
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
@slot('title')Apis @endslot
@endcomponent
<style>
.modal-lg, .modal-xl {
    --vz-modal-width: 1500px;
}
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Api List</h4>
                <div class="flex-shrink-0">
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create New</button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No records found</h4>
            </div>
        </div>
    </div>
</div>


<!-- Details Modals -->
<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" style="display: none;">
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
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="apiName" class="col-form-label">Api Name:</label>
                                    <input type="text" class="form-control" name="apiName" id="apiName" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_username" class="col-form-label">Api Username:</label>
                                    <input type="text" class="form-control" name="api_username" id="api_username" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_password" class="col-form-label">Api Password:</label>
                                    <input type="text" class="form-control" name="api_password" id="api_password" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_key" class="col-form-label">Api Key:</label>
                                    <input type="text" class="form-control" name="api_key" id="api_key" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="api_url" class="col-form-label">Api URL:</label>
                                    <textarea class="form-control" name="api_url" id="api_url"></textarea>
                                </div>
                            </div>
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="balance_check_url" class="col-form-label">Balance Check URL:</label>
                                    <textarea class="form-control" name="balance_check_url" id="balance_check_url"></textarea>
                                </div>
                            </div>


                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="status_value" class="col-form-label">Status Value:</label>
                                    <input type="text" class="form-control" name="status_value" id="status_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="success_value" class="col-form-label">Success Value:</label>
                                    <input type="text" class="form-control" name="success_value" id="success_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="failed_value" class="col-form-label">Failed Value:</label>
                                    <input type="text" class="form-control" name="failed_value" id="failed_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="pending_value" class="col-form-label">Pending Value:</label>
                                    <input type="text" class="form-control" name="pending_value" id="pending_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="error_value" class="col-form-label">Error Value:</label>
                                    <input type="text" class="form-control" name="error_value" id="error_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="error_value_response" class="col-form-label">Error Value(Response):</label>
                                    <input type="text" class="form-control" name="error_value_response" id="error_value_response" required="required">
                                </div>
                            </div>




                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="order_id_value" class="col-form-label">Order Id Value :</label>
                                    <input type="text" class="form-control" name="order_id_value" id="order_id_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="operator_id_value" class="col-form-label">Operator Id Value:</label>
                                    <input type="text" class="form-control" name="operator_id_value" id="operator_id_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_method" class="col-form-label">Api Method:</label>
                                    <select name="api_method" id="api_method" class="form-control">
                                        <option value="POST">POST</option>
                                        <option value="GET" selected="">GET</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_format" class="col-form-label">Api Format:</label>
                                    <select name="api_format" id="api_format" class="form-control">
                                        <option value="JSON" selected="">JSON</option>
                                        <option value="XML">XML</option>
                                        <option value="TEXT">TEXT</option>
                                    </select>
                                </div>
                            </div>
                            
                            <h6>Callback Setting</h6>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="callback_status_value" class="col-form-label">Status Value:</label>
                                    <input type="text" class="form-control" name="callback_status_value" id="callback_status_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="callback_success_value" class="col-form-label">Success Value:</label>
                                    <input type="text" class="form-control" name="callback_success_value" id="callback_success_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div>
                                    <label for="callback_failed_value" class="col-form-label">Failed Value:</label>
                                    <input type="text" class="form-control" name="callback_failed_value" id="callback_failed_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="callback_order_id_value" class="col-form-label">Order Id Value:</label>
                                    <input type="text" class="form-control" name="callback_order_id_value" id="callback_order_id_value" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="callback_operator_id_value" class="col-form-label">Operator Id Value:</label>
                                    <input type="text" class="form-control" name="callback_operator_id_value" id="callback_operator_id_value" required="required">
                                </div>
                            </div>


                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="callback_remark" class="col-form-label">Callback Remark:</label>
                                    <input type="text" class="form-control" name="callback_remark" id="callback_remark" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="callback_api_method" class="col-form-label">Api Method:</label>
                                    <select name="callback_api_method" id="callback_api_method" class="form-control">
                                        <option value="POST">POST</option>
                                        <option value="GET" selected="">GET</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="api_type" class="col-form-label">Api Type:</label>
                                    <select name="api_type" id="api_type" class="form-control">
                                            <option value="recharge" selected="">Recharge</option>
                                            <option value="biillpay">Bill Pay</option>
                                            <option value="moneytransfer">Money Transfer</option>
                                            <option value="aeps">Aeps</option>
                                            <option value="others">Others</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label class="col-form-label">Status:</label>
                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">
                                        <option selected="">Select Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Deactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
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

<!-- Provider Code Details Modals -->
<div id="ProviderCodeDetailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="ProviderCodeDetailsModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkUpdateForm" method="post">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="ProviderCodeDetailsModalLabel">Provider Code Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <div class="row align-items-start">
                    <div class="col-sm-4">
                        <label> Service Type</label>
                        <input type="hidden" name="api_id" id="api_id" value="">
                        <select class="form-select mb-3 service" aria-label="Default select example" name="service">
                           
                        </select>
                    </div>
                </div>

                </br>
                <div class="card-body" id="provider_code_result">
                    <h4 class="text-center text-secondary my-3">No records found</h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="bulk_update_provider_code">Update All</button>
            </div>  
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@endsection
@section('script')
<script>
    fetchAll();
    function capitalizeFirstLetter(string){
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function Error_Msg(title,text,icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonClass: 'btn btn-primary w-xs mt-2',
            buttonsStyling: false,
            showCloseButton: true
        });
    }
    function fetchAll() {
        $.ajax({
            url: '{{ route('apisList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                $("#list_result").html(res);
                var table = new DataTable('#scroll-vertical', {
                    "scrollY": "250px",
                    "scrollCollapse": true,
                    "paging": false
                });
                $('#example').DataTable({
                    order: [0, 'desc']
                });
            }
        });
    }

    $(document).on('click', '.setProviderCode', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $("#api_id").val(id);
        fatchApiAndService();
        fetchProviderCodeAll(id, 1);
        $('#ProviderCodeDetailsModal').modal('show');
    });


    $(".service").change(function(){
        var id = $("#api_id").val();
        fetchProviderCodeAll(id, this.value);
    });


    function fetchProviderCodeAll(id, service) {
        if(id == ""){
            id = 1;
        }
        var service = service;
        $("#preloader").attr("style", "display:block");
        $.ajax({
            url: '{{ route('apisGetProviderCode') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}', id, service},
            success: function(res) {
                $("#provider_code_result").html(res);
                $("#preloader").hide();
            }
        });
    }

    function fatchApiAndService() {
        //$("#preloader").attr("style", "display:block");
        $.ajax({
            url: '{{ route('apiAndService') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                $('.service').empty();
                $.each(res.service, function(k,v) {
                    $('.service').append('<option value="' + v.id + '">' + v.service_name.toUpperCase() + '</option>');
                });
                //$("#preloader").hide();
            }
        });
    }



    $(document).on('click', '.checkLiveBalance', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        var api_id = $("#api_id").val();

        $.ajax({
          url: '{{ route('apisCheckLiveBalance') }}',
          method: 'post',
          data: {
            id,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
         }
        });
    });


    $(document).on('click', '.updateProviderCode', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        var provider_id = $("#"+id+"_provider_id").val();
        var provider_code = $("#"+id+"_provider_code").val();
        var api_id = $("#api_id").val();

        $.ajax({
          url: '{{ route('apisSingleUpdateProviderCode') }}',
          method: 'post',
          data: {
            api_id,provider_id,provider_code,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
         }
        });
    });


    $("#bulkUpdateForm").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#bulk_update_provider_code").text('Loading...');
        $('#bulk_update_provider_code').prop('disabled', true);
        $.ajax({
          url: '{{ route('apisBulkUpdateProviderCode') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#bulk_update_provider_code").text('Save Changes');
                $('#bulk_update_provider_code').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
                fetchAll();
                $("#bulk_update_provider_code").text('Save Changes');
                $('#bulk_update_provider_code').prop('disabled', false);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#bulk_update_provider_code").text('Save Changes');
                $('#bulk_update_provider_code').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#bulk_update_provider_code").text('Save Changes');
            $('#bulk_update_provider_code').prop('disabled', false);
         }
        });
    });

    $(document).on('click', '.deleteData', function(e) {
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
              url: '{{ route('apisDelete') }}',
              method: 'post',
              data: {
                id: id,
                _token: csrf
              },
              success: function(data) {
                if(data.type=="error"){
                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                }else if(data.type=="success"){
                   Swal.fire(
                        'Deleted!',
                        data.message,
                        'success'
                    )
                    fetchAll();
                }else{
                    Error_Msg("Oops...","Something went wrong!","error");
                }
                
              },
              error: function( jqXhr, textStatus, errorThrown ){
                Error_Msg("Oops...","Something went wrong!","error");
            }
            });
          }
        })
    });

    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
          url: '{{ route('apisGet') }}',
          method: 'post',
          data: {
            id: id,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                $("#apiName").val(data.data.api_name);
                $("#edit_id").val(data.data.id);

                ///
                $("#api_username").val(data.data.api_username);
                $("#api_password").val(data.data.api_password);
                $("#api_key").val(data.data.api_key);
                $("#api_url").text(data.data.api_url);
                $("#balance_check_url").text(data.data.balance_check_url);
                $("#status_value").val(data.data.status_value);
                $("#success_value").val(data.data.success_value);
                $("#failed_value").val(data.data.failed_value);
                $("#pending_value").val(data.data.pending_value);
                $("#error_value").val(data.data.error_value);
                $("#error_value_response").val(data.data.error_value_response);
                // 
                $("#order_id_value").val(data.data.order_id_value);
                $("#operator_id_value").val(data.data.operator_id_value);
                $("#api_method").val(data.data.api_method).change();
                $("#api_format").val(data.data.api_format).change();
                //
                $("#callback_status_value").val(data.data.callback_status_value);
                $("#callback_success_value").val(data.data.callback_success_value);
                $("#callback_failed_value").val(data.data.callback_failed_value);
                $("#callback_order_id_value").val(data.data.callback_order_id_value);
                $("#callback_operator_id_value").val(data.data.callback_operator_id_value);
                $("#callback_remark").val(data.data.callback_remark);
                $("#callback_api_method").val(data.data.callback_api_method).change();
                $("#api_type").val(data.data.api_type).change();
                // $("#edit_id").val(data.data.id);
                // $("#edit_id").val(data.data.id);
                ///
                $(".status").val(data.data.status).change();
                $('#detailsModalLabel').text('Edit Details');
                $('#detailsModal').modal('show');
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
         }
        });
    });

    $("#edit_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Loading...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('apisUpdate') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
                fetchAll();
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
                $("#edit_details_form")[0].reset();
                $("#detailsModal").modal('hide');
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#edit_details_btn").text('Save Changes');
            $('#edit_details_btn').prop('disabled', false);
         }
        });
    }); 
    
    function createNew() {
        $('#detailsModal').modal('show');
        $("#edit_details_form")[0].reset();
        $("#edit_id").val(0);
        $('#detailsModal').modal('show');
    } 
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


<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

@endsection
