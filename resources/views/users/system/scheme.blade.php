@extends('layouts.master')
@section('title') Scheme @endsection
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
@slot('title')Scheme @endslot
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
                <h4 class="card-title mb-0 flex-grow-1">Scheme List</h4>
                <div class="flex-shrink-0">
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create New</button>
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
<div id="detailsModal" class="modal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Create Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label for="schemeName" class="col-form-label">Scheme Name:</label>
                        <input type="text" class="form-control" name="schemeName" id="schemeName" required="required">
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Status:</label>
                        <select class="form-select mb-3 status" aria-label="Default select example" name="status">
                            <option selected="">Select Status</option>
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
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<!-- Commission Details Modals -->
<div id="CommissionDetailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="CommissionDetailsModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkUpdateForm" method="post">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="CommissionDetailsModalLabel">Commission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <div class="row align-items-start">
                    <div class="col-sm-2">
                        <label> Service Type</label>
                        <input type="hidden" name="scheme_id" id="scheme_id" value="">
                        <select class="form-select mb-3 service" aria-label="Default select example" name="service">
                           
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <label> WT Com Type</label>
                        <select class="form-select mb-3 wt_comtype" aria-label="Default select example" id="wt_comtype">
                            <option selected="">Select Type</option>
                            <option value="Commission Flat">Commission Flat</option>
                            <option value="Commission Percent" >Commission Percent</option>
                            <option value="Charge Flat">Charge Flat</option>
                            <option value="Charge Percent">Charge Percent</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <label> WT Com Val</label>
                        <input type="text" class="form-control" id="wt_value" required="required" value="0">
                    </div>  
                    <div class="col-sm-1">
                        <label> MD Com Type</label>
                        <select class="form-select mb-3 md_comtype" aria-label="Default select example" id="md_comtype">
                            <option selected="">Select Amount Type</option>
                            <option value="Commission Flat">Commission Flat</option>
                            <option value="Commission Percent" >Commission Percent</option>
                            <option value="Charge Flat">Charge Flat</option>
                            <option value="Charge Percent">Charge Percent</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <label> MD Com Val</label>
                        <input type="text" class="form-control" id="md_value" required="required" value="0">
                    </div>  
                    <div class="col-sm-1">
                        <label> DT Com Type</label>
                        <select class="form-select mb-3 dt_comtype" aria-label="Default select example" id="dt_comtype">
                            <option selected="">Select Amount Type</option>
                            <option value="Commission Flat">Commission Flat</option>
                            <option value="Commission Percent">Commission Percent</option>
                            <option value="Charge Flat">Charge Flat</option>
                            <option value="Charge Percent">Charge Percent</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <label> DT Com Val</label>
                        <input type="text" class="form-control" id="dt_value" required="required" value="0">
                    </div> 
                    <div class="col-sm-1">
                        <label > RT Com Type</label>
                        <select class="form-select mb-3 rt_comtype" aria-label="Default select example" id="rt_comtype">
                            <option selected="">Select Amount Type</option>
                            <option value="Commission Flat">Commission Flat</option>
                            <option value="Commission Percent" >Commission Percent</option>
                            <option value="Charge Flat">Charge Flat</option>
                            <option value="Charge Percent">Charge Percent</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <label > RT Com Val</label>
                        <input type="text" class="form-control" id="rt_value" required="required" value="0">
                    </div> 
                    <div class="col-sm-2">
                        <label >Apply All</label>
                        <input type="button" value="Apply" class="form-control btn btn-primary waves-effect waves-light" id="apply_all"/>
                    </div>   
                </div>
                </br>
                <div class="card-body" id="commission_result">
                    <h4 class="text-center text-secondary my-3">No record found</h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="bulk_update_commiission">Update All</button>
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
            url: '{{ route('schemeList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                $("#list_result").html(res);
                //$("#commission_result").html(res);
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
              url: '{{ route('schemeDelete') }}',
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
    $(document).on('click', '.updateCommission', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        var provider_id = $("#"+id+"_provider_id").val();
        var scheme_id = $("#scheme_id").val();
        var wt_comtype = $("#"+id+"_wt_comtype").val();
        var wt_value = $("#"+id+"_wt_value").val();

        var md_comtype = $("#"+id+"_md_comtype").val();
        var md_value = $("#"+id+"_md_value").val();

        var dt_comtype = $("#"+id+"_dt_comtype").val();
        var dt_value = $("#"+id+"_dt_value").val();

        var rt_comtype = $("#"+id+"_rt_comtype").val();
        var rt_value = $("#"+id+"_rt_value").val();
        $.ajax({
          url: '{{ route('schemeSingleUpdateCommission') }}',
          method: 'post',
          data: {
            scheme_id: scheme_id,
            provider_id: provider_id,

            wt_comtype: wt_comtype,
            wt_value: wt_value,

            md_comtype: md_comtype,
            md_value: md_value,

            dt_comtype: dt_comtype,
            dt_value: dt_value,

            rt_comtype: rt_comtype,
            rt_value: rt_value,

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
    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
          url: '{{ route('schemeGet') }}',
          method: 'post',
          data: {
            id: id,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                $("#schemeName").val(data.data.scheme_name);
                $("#edit_id").val(data.data.id);
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

    $(document).on('click', '.setCommission', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $("#scheme_id").val(id);
        fatchApiAndService();
        fetchCommissionAll(id, 1);
        $('#CommissionDetailsModal').modal('show');
    });

    $('#apply_all').click(function(){
        $('select[name="wt_comtype[]"]').val($("#wt_comtype").val());
        $('input[name="wt_value[]"]').val($("#wt_value").val());

        $('select[name="md_comtype[]"]').val($("#md_comtype").val());
        $('input[name="md_value[]"]').val($("#md_value").val());

        $('select[name="dt_comtype[]"]').val($("#dt_comtype").val());
        $('input[name="dt_value[]"]').val($("#dt_value").val());

        $('select[name="rt_comtype[]"]').val($("#rt_comtype").val());
        $('input[name="rt_value[]"]').val($("#rt_value").val());
       
        //alert('Does this work?');
    });


    $(".service").change(function(){
        var id = $("#scheme_id").val();
        fetchCommissionAll(id, this.value);
    });

    function fetchCommissionAll(id, service) {
        if(id == ""){
            id = 1;
        }
        var service = service;
        $("#preloader").attr("style", "display:block");
        $.ajax({
            url: '{{ route('schemeGetCommission') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}', id, service},
            success: function(res) {
                // var html = ""; 
                // $.each(res.data, function (k, v) {
                //     html = '<tr><td>'+ v.provider_name +'</td>'+
                //     '<td>'+ v.provider_name +'</td>'+
                //     '<td>'+ v.provider_name +'</td>';
                //     $("#commission_result").append(html);
                // });
                $("#commission_result").html(res);
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
    

    $("#edit_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('schemeUpdate') }}',
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

    $("#bulkUpdateForm").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#bulk_update_commiission").text('Please wait...');
        $('#bulk_update_commiission').prop('disabled', true);
        $.ajax({
          url: '{{ route('schemeBulkUpdateCommission') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#bulk_update_commiission").text('Save Changes');
                $('#bulk_update_commiission').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
                fetchAll();
                $("#bulk_update_commiission").text('Save Changes');
                $('#bulk_update_commiission').prop('disabled', false);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#bulk_update_commiission").text('Save Changes');
                $('#bulk_update_commiission').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#bulk_update_commiission").text('Save Changes');
            $('#bulk_update_commiission').prop('disabled', false);
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
