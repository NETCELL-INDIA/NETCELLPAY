@extends('layouts.master')

@section('title') Provider @endsection

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

@slot('title')Provider @endslot

@endcomponent



<div class="row">

    <div class="col-lg-12">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1">Provider List</h4>

                <div class="flex-shrink-0">

                    <div class="form-check form-switch form-switch-right form-switch-md">

                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create New</button>

                    </div>

                </div>

            </div>

            <div class="card-body" id="list_result">

                <h4 class="text-center text-secondary my-3">Loading...</h4>

            </div>

        </div>

    </div>

</div>





<!-- Details Modals -->

<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel" data-bs-backdrop="static" aria-hidden="true" style="display: none;">

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

                    <input type="hidden" name="old_provider_logo" id="old_provider_logo">

                    <div class="live-preview">

                        <div class="row gy-4">

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="provider_name" class="form-label">Provider Name: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="provider_name" id="provider_name" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Select Service: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="service_name" id="service_name" required="required"> -->

                                    <select class="form-select mb-3 service_name" aria-label="Default select example" name="service_name">

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Select Api: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="api_name" id="api_name" required="required"> -->

                                    <select class="form-select mb-3 api_name" aria-label="Default select example" name="api_name">

                                    </select>

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Select Backup 1 Api: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="backup_api_name" id="backup_api_name" required="required"> -->

                                    <select class="form-select mb-3 backup_api_name" aria-label="Default select example" name="backup_api_name">

                                    </select>

                                </div>

                            </div>

                            <!--end col-->


                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Select Backup 2 Api: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="backup_api_name" id="backup_api_name" required="required"> -->

                                    <select class="form-select mb-3 backup_api2_name" aria-label="Default select example" name="backup_api2_name">

                                    </select>

                                </div>

                            </div>


                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Select Backup 3 Api: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="backup_api_name" id="backup_api_name" required="required"> -->

                                    <select class="form-select mb-3 backup_api3_name" aria-label="Default select example" name="backup_api3_name">

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="minium_amount" class="form-label">Minium Amount: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="minium_amount" id="minium_amount" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="maxium_amount" class="form-label">Maxium Amount: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="maxium_amount" id="maxium_amount" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label  class="form-label">Offline/Online: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="provider_down" id="provider_down" required="required"> -->

                                    <select class="form-select mb-3 provider_down" aria-label="Default select example" name="provider_down">

                                        <option selected="">Select </option>

                                        <option value="1">Offline</option>

                                        <option value="0">Online</option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Commission Type: <a style="color: red">*</a></label>

                                    <!-- <input type="text" class="form-control" name="amount_type" id="amount_type" required="required"> -->

                                    <select class="form-select mb-3 amount_type" aria-label="Default select example" name="amount_type">

                                        <option selected="">Select Commission Type</option>

                                        <option value="Commission Flat">Commission Flat</option>

                                        <option value="Commission Percent" selected="">Commission Percent</option>

                                        <option value="Charge Flat">Charge Flat</option>

                                        <option value="Charge Percent">Charge Percent</option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label  class="form-label">Commission Value: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="amount_value" id="amount_value" required="required">

                                </div>

                            </div>

                            

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label  class="form-label">Provider Logo: <a style="color: red">*</a></label>

                                    <input type="file" class="form-control" name="provider_logo" id="provider_logo" accept="image/png, image/gif, image/jpeg">

                                    

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Status:</label>

                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">

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

    fatchApiAndService();

    fetchAll(1,10);

    function capitalizeFirstLetter(string){

        return string.charAt(0).toUpperCase() + string.slice(1);

    }



    function Error_Msg(title,text,icon) {

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

       // $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $("#preloader").attr("style", "display:block");

        $.ajax({

            url: '{{ route('providersList') }}',

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

                $("#preloader").hide(); 

            }

        });

    }



    function fatchApiAndService() {

        $("#preloader").attr("style", "display:block");

        $.ajax({

            url: '{{ route('apiAndService') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}'},

            success: function(res) {

                $('.api_name').empty();

                $('.backup_api_name').empty();

                $('.service_name').empty();

                $.each(res.apis, function(k,v) {

                    $('.backup_api_name').append('<option value="' + v.id + '">' + v.api_name.toUpperCase() + '</option>');

                    $('.api_name').append('<option value="' + v.id + '">' + v.api_name.toUpperCase() + '</option>');

                    $('.backup_api2_name').append('<option value="' + v.id + '">' + v.api_name.toUpperCase() + '</option>');

                    $('.backup_api3_name').append('<option value="' + v.id + '">' + v.api_name.toUpperCase() + '</option>');

                });

                $.each(res.service, function(k,v) {

                    $('.service_name').append('<option value="' + v.id + '">' + v.service_name.toUpperCase() + '</option>');

                });

                $("#preloader").hide();

                option = '<option value="0">NO API</option>';

                $('.backup_api_name').append(option);

                $('.api_name').append(option);

                $('.backup_api2_name').append(option);

                $('.backup_api3_name').append(option);

            }

        });

    }



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

              url: '{{ route('providersDelete') }}',

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

        $("#preloader").attr("style", "display:block");

        

        $.ajax({

          url: '{{ route('providersGet') }}',

          method: 'post',

          data: {

            id: id,

            _token: '{{ csrf_token() }}'

          },

          success: function(data) {

            $('#preloader').hide();

            if(data.type=="error"){

                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

            }else if(data.type=="success"){  

                //console.log(data.data.service_id);

                

                $("#provider_name").val(data.data.provider_name);

                $('.service_name').val(data.data.service_id).change();

                $('.api_name').val(data.data.api_id).change();

                $('.backup_api_name').val(data.data.backup_api_id).change();

                $('.backup_api2_name').val(data.data.backup_api2_id).change();

                $('.backup_api3_name').val(data.data.backup_api3_id).change();

                $("#minium_amount").val(data.data.minium_amount);

                $("#maxium_amount").val(data.data.maxium_amount);

                $(".provider_down").val(data.data.provider_down).change();

                $(".amount_type").val(data.data.amount_type).change();

                $("#amount_value").val(data.data.amount_value);

                $("#old_provider_logo").val(data.data.provider_logo);

                $("#edit_id").val(data.data.id);

                $(".status").val(data.data.status).change();

                $('#detailsModalLabel').text('Edit Details');

                $('#detailsModal').modal('show');

            }else{

                Error_Msg("Oops...","Something went wrong!","error");

            }

          },

          error: function( jqXhr, textStatus, errorThrown ){

            $('#preloader').hide();

            Error_Msg("Oops...","Something went wrong!","error");

         }

        });

    });



    $("#edit_details_form").submit(function(e) {

        e.preventDefault();

        const fd = new FormData(this);

        $("#edit_details_btn").text('Please wait...');

        $('#edit_details_btn').prop('disabled', true);

        $.ajax({

          url: '{{ route('providersUpdate') }}',

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

                fetchAll(1,10);

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

        fatchApiAndService();

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








@endsection

