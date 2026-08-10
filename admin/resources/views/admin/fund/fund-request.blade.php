@extends('layouts.master')

@section('title') Fund Request @endsection

@section('css')

<!--datatable css-->

<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<!--datatable responsive css-->

<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />

<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />

@endsection

@section('content')

@component('components.breadcrumb')

@slot('li_1') Fund @endslot

@slot('title')Fund Request @endslot

@endcomponent



<div class="row">

    <div class="col-lg-12">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1">Filters</h4>

                <div class="flex-shrink-0">

                </div>

            </div>

            <div class="card-body">

                <form action="#">

                    <div class="row gy-3">

                        

                        

                        <div class="col-lg-2">

                            <div>

                                <label class="form-label mb-0">From Date</label>

                                <input type="date" class="form-control" name="from_date" value="{{\Carbon\Carbon::today()->format('Y-m-d')}}" id="from_date">

                            </div>

                        </div>

                        <div class="col-lg-2">

                            <div>

                                <label class="form-label mb-0">To Date </label>

                                <input type="date" class="form-control" name="to_date" value="{{\Carbon\Carbon::today()->format('Y-m-d')}}"id="to_date">

                            </div>  

                        </div>

                        <div class="col-lg-2">

                            <div>

                                <label class="form-label mb-0">Order Id</label>

                                <input type="text" class="form-control" placeholder="Enter Order Id" name="order_id" value="" id="order_id">

                            </div>

                        </div>

                        <div class="col-lg-2">

                            <div>

                                <label class="form-label mb-0">Search User</label>

                                <input type="text" class="form-control" id="inputUsername" placeholder="Enter Mobile Number" onkeyup="fatchUsername(this.value);" />

                                <input type="hidden" name="inputUsername_id_value" id="inputUsername_id_value" value="0" />

                                <div id="user_list">



                                </div>

                            </div>

                        </div>

                        <div class="col-lg-1">

                            <label class="form-label mb-0">Path </label>

                            <select class="form-select mb-3" name="req_path"  id="req_path">

                                <option selected value="">All </option>
                                <option value="0">Fund Request</option>
                                <option value="1">One Gateway</option>
                                <option value="2">All Upi Gateway</option>

                            </select>

                        </div>

                        <div class="col-lg-1">

                            <label class="form-label mb-0">Status </label>

                            <select class="form-select mb-3" name="status_type" id="status_type">

                                <option selected value="All">All</option>

                                <option value="Pending">Pending</option>

                                <option value="Transferred">Transferred</option>

                                <option value="Rejected">Rejected</option>

                            </select>

                        </div>

                        <div class="col-lg-1">

                            <label class="form-label mb-0">Type </label>

                            <select class="form-select mb-3" name="tbl_type"  id="tbl_type">

                                <option selected value="0">Current </option>

                                <option value="1">Backup</option>

                            </select>

                        </div>

                        <div class="col-lg-1">

                            <div>

                                <label class="form-label mb-0"></label>

                                <button type="button" id="search_btn" class="form-control btn btn-secondary bg-gradient waves-effect waves-light" onclick="fetchAllSearch(1,10)">Search Records</button>

                            </div>  

                        </div>

                    </div>                          

                </form>

            </div>

        </div>

    </div>

</div>



<div class="row">

    <div class="col-lg-12">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1">Fund Request List</h4>

                <div class="flex-shrink-0">

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

                <form action="#" method="POST" id="edit_details_form">

                    <input type="hidden" name="edit_id" id="edit_id">

                    @csrf

                    <div class="live-preview">

                        <div class="row gy-4">

                            

                            <!--end col-->

                            <div class="col-xxl-12 col-md-6">

                                <div>

                                    <label for="remark" class="form-label">Remark: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="remark" id="remark" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-12 col-md-12">

                                <div>

                                    <label class="form-label">Status:</label>

                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">

                                        <option selected="">Select Status</option>

                                        <option value="Approved">Approved</option>

                                        <option value="Rejected">Rejected</option>

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

var urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('status')){
       $("#status_type").val(urlParams.get('status')).change();
    }
    if(urlParams.has('upi')){
       $("#req_path").val(urlParams.get('upi')).change();
    }

    fetchAllSearch(1,10);



    function tableSearch(page) {

        limit = $('#page_limit').val();

        page = page;

        fetchAllSearch(page,limit);

    }

    $(document).on('change','#page_limit',function(){

        page = 1;

        limit = $('#page_limit').val();

        fetchAllSearch(page,limit);

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



    function fetchAllSearch(page, limit) {

        var from_date = $("#from_date").val();

        var to_date = $("#to_date").val();

        var tbl_type = $("#tbl_type").val();

        var status = $("#status_type").val();

        var user_id = $("#inputUsername_id_value").val();

        var order_id = $("#order_id").val();

        var path = $("#req_path").val();

        

        $("#search_btn").text('Please wait...');

        $('#search_btn').prop('disabled', true);

        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $.ajax({

            url: '{{ route('fundRequestList') }}',

            method: 'post',

            data: {

                status,

                user_id,

                order_id,

                from_date : from_date,

                to_date : to_date,

                tbl_type : tbl_type,

                _token: '{{csrf_token()}}',

                path,

                page,

                limit,

            },

            success: function(res) {

                $("#search_btn").text('Search Records');

                $('#search_btn').prop('disabled', false);

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



    // function fetchAll() {

    //     $.ajax({

    //         url: '{{ route('fundRequestList') }}',

    //         method: 'post',

    //         data: {_token: '{{csrf_token()}}'},

    //         success: function(res) {

                

    //             $("#list_result").html(res);

    //             var table = new DataTable('#scroll-vertical', {

    //                 "scrollY": "250px",

    //                 "scrollCollapse": true,

    //                 "paging": false

    //             });

    //             $('#example').DataTable({

    //                 order: [0, 'desc']

    //             });

    //         }

    //     });

    // }

    $(document).on('click', '.editDetails', function(e) {

        e.preventDefault();      

        let id = $(this).attr('id');

        $("#edit_id").val(id);

        $('#detailsModalLabel').text('Edit Details');

        $('#detailsModal').modal('show');

    });



    $("#edit_details_form").submit(function(e) {

        e.preventDefault();

        const fd = new FormData(this);

        $("#edit_details_btn").text('Please wait...');

        $('#edit_details_btn').prop('disabled', true);

        $.ajax({

          url: '{{ route('fundRequestUpdate') }}',

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

                Error_Msg(data.type,data.message,"success");

                fetchAllSearch(1,10);

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



    function fatchUsername(keyword) {

        //var keyword = $('#inputUsername').val();

        if (keyword.length > 5) {

          $.ajax({

            url: '{{ route('fundRequestSearchUser') }}',

            method: 'post',

            data: { _token: '{{csrf_token()}}', keyword: keyword },

            success: function (res) {

              $('#user_list').show();

              console.log(res);

              htmlView = "";

              $('#user_list').empty();

              for (let i = 0; i < res.users.length; i++) {

                

                htmlView += '<a onclick="selectValue(`' + res.users[i].id + '`,`' + res.users[i].outlet_name + ' | ' + res.users[i].first_name + ' ' + res.users[i].middle_name + ' ' + res.users[i].last_name + ' | ' + res.users[i].mobile_number + '`)">' + res.users[i].outlet_name + ' | ' + res.users[i].first_name + ' ' + res.users[i].middle_name + ' ' + res.users[i].last_name + ' | ' + res.users[i].mobile_number + '</a></br></hr>';

              }

              $('#user_list').html(htmlView);

            }

          });

        } else {

          $('#user_list').empty();

          $('#user_list').hide();

          //$('#inputUsername').val(0)

        }

      }







    function selectValue(id, full_text) {

        $('#inputUsername_id_value').val(id);

        $('#inputUsername').val(full_text);

        $('#user_list').hide();

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

