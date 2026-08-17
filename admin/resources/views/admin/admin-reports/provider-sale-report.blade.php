@extends('layouts.master')
@section('title') Provider Sale Reports @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Admin Reports @endslot
@slot('title') Provider Sale Reports @endslot
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
                        
                        
                        
                        
                        <div class="col-lg-4">
                            <div>
                                <label class="form-label mb-0">Search User</label>
                                <input type="text" class="form-control" id="inputUsername" placeholder="Enter Mobile Number" onkeyup="fatchUsername(this.value);" />
                                <input type="hidden" name="inputUsername_id_value" id="inputUsername_id_value" value="0" />
                                <div id="user_list">

                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div>
                                <label class="form-label mb-0">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="{{\Carbon\Carbon::today()->format('Y-m-d')}}" id="from_date">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div>
                                <label class="form-label mb-0">To Date </label>
                                <input type="date" class="form-control" name="to_date" value="{{\Carbon\Carbon::today()->format('Y-m-d')}}"id="to_date">
                            </div>  
                        </div>
                        <div class="col-lg-2">
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
                <h4 class="card-title mb-0 flex-grow-1">Provider Sale Reports List</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>


@endsection
@section('script')
<script>
    fetchAllSearch();
    $(document).on('keyup','#searchValueTable',function(){
        var value = $( this ).val();
        if (this.value.length < 1) {
            $("#pagination_table tr").css("display", "");
        } else {
            $("#pagination_table tbody tr:not(:contains('"+this.value+"'))").css("display", "none");
            $("#pagination_table tbody tr:contains('"+this.value+"')").css("display", "");
        }
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

    function fetchAllSearch() {
        var user_id = $("#inputUsername_id_value").val();
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        $("#search_btn").text('Please wait...');
        $('#search_btn').prop('disabled', true);
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('providerSaleReportsList') }}',
            method: 'post',
            data: {
                user_id,
                from_date : from_date,
                to_date : to_date,
                _token: '{{csrf_token()}}',
            },
            success: function(res) {
                $("#search_btn").text('Search Records');
                $('#search_btn').prop('disabled', false);
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
              var users = (typeof adminUsersFromRes === 'function') ? adminUsersFromRes(res) : ((res && res.users) ? res.users : []);
              for (let i = 0; i < users.length; i++) {
                
                htmlView += '<a onclick="selectValue(`' + users[i].id + '`,`' + users[i].outlet_name + ' | ' + users[i].first_name + ' ' + users[i].middle_name + ' ' + users[i].last_name + ' | ' + users[i].mobile_number + '`)">' + users[i].outlet_name + ' | ' + users[i].first_name + ' ' + users[i].middle_name + ' ' + users[i].last_name + ' | ' + users[i].mobile_number + '</a></br></hr>';
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



@endsection
