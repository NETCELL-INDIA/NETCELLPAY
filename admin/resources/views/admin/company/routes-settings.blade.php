@extends('layouts.master')
@section('title') Routes Settings @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Company @endslot
@slot('title')Routes Settings @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <form id="bulkUpdateForm" method="post">
                @csrf
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Routes Settings List</h4>
                    <div class="flex-shrink-0">

                    <div class="form-check form-switch form-switch-right form-switch-md">

                        <button type="submit" class="btn btn-primary" id="bulk_update_routes">Update</button>

                    </div>

                </div>
                </div>
                <div class="card-body" id="list_result">
                    <h4 class="text-center text-secondary my-3">No record found</h4>
                </div>
            </form>
        </div>
    </div>
</div>


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
            customClass: {
                confirmButton: 'btn btn-primary w-xs mt-2',
            },
            buttonsStyling: false,
            showCloseButton: true
        });
    }
    function fetchAll() {
        $.ajax({
            url: '{{ route('routeSettingsList') }}',
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
                    order: [0, 'asc']
                });
            }
        });
    }


    $("#bulkUpdateForm").submit(function(e) {

        e.preventDefault();

        const fd = new FormData(this);

        $("#bulk_update_routes").text('Please wait...');

        $('#bulk_update_routes').prop('disabled', true);

        $.ajax({

        url: '{{ route('routesBulkUpdatePriority') }}',

        method: 'post',

        data: fd,

        cache: false,

        contentType: false,

        processData: false,

        dataType: 'json',

        success: function(data) {

            if(data.type=="error"){

                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                $("#bulk_update_routes").text('Save Changes');

                $('#bulk_update_routes').prop('disabled', false);

            }else if(data.type=="success"){  

                Error_Msg("Updated","Updated Successfully!","success");

                fetchAll(1,10);

                $("#bulk_update_routes").text('Save Changes');

                $('#bulk_update_routes').prop('disabled', false);

            }else{

                Error_Msg("Oops...","Something went wrong!","error");

                $("#bulk_update_routes").text('Save Changes');

                $('#bulk_update_routes').prop('disabled', false);

            }

        },

        error: function( jqXhr, textStatus, errorThrown ){

            Error_Msg("Oops...","Something went wrong!","error");

            $("#bulk_update_routes").text('Save Changes');

            $('#bulk_update_routes').prop('disabled', false);

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


<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

@endsection
