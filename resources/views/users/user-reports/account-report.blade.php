@extends('layouts.master')
@section('title') Account Reports @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') User Reports @endslot
@slot('title')Account Reports @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Search</h4>
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
                                <label class="form-label mb-0">Order Id </label>
                                <input type="text" class="form-control" name="order_id" value=""id="order_id" placeholder="Order Id">
                            </div>  
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Transaction Type </label>
                            <select class="form-select mb-3" name="tr_type"  id="tr_type">
                                <option selected value="">---Select---</option>
                                <option value="Recharge">Recharge</option>
                                <option value="Commission">Commission</option>
                                <option value="Refund">Refund</option>
                                <option value="Money Transfer">Money Transfer</option>
                                <option value="Reverse Commission">Reverse Commission</option>
                                <option value="Receive Money">Receive Money</option>
                                <option value="Transfer Money">Transfer Money</option>
                                <option value="Upi Add Money">Upi Add Money</option>
                                <option value="Self Money">Self Money</option>
                                <option value="Money Reverse">Money Reverse</option>
                                <option value="Reverse Money">Reverse Money</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Credit/Debit </label>
                            <select class="form-select mb-3" name="fund_type"  id="fund_type">
                                <option selected value="">---Select---</option>
                                <option value="Credit">Credit</option>
                                <option value="Debit">Debit</option>
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
                                <button type="button" id="search_btn" class="form-control btn btn-secondary bg-gradient waves-effect waves-light" onclick="fetchAllSearch()">Search Records</button>
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
                <h4 class="card-title mb-0 flex-grow-1">Account Reports List</h4>
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
    fetchAll(1,10);
    

    function fetchAllSearch() {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();

        var order_id = $("#order_id").val();
        var fund_type = $("#fund_type").val();
        var tr_type = $("#tr_type").val();


        var tbl_type = $("#tbl_type").val();

        $("#search_btn").text('Please wait...');
        $('#search_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('accountReportsList') }}',
            method: 'post',
            data: {
                from_date : from_date,
                to_date : to_date,
                order_id : order_id,
                fund_type : fund_type,
                tr_type : tr_type,
                tbl_type : tbl_type,
                page : 1,
                limit : 10,
                _token: '{{csrf_token()}}',
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
    function tableSearch(page) {
        limit = $('#page_limit').val();
        page = page;
        fetchAll(page,limit);
    }

    function fetchAll(page,limit) {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        var order_id = $("#order_id").val();
        var fund_type = $("#fund_type").val();
        var tr_type = $("#tr_type").val();
        var tbl_type = $("#tbl_type").val();
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('accountReportsList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',from_date,to_date,order_id,fund_type,tr_type,tbl_type,page,limit},
            success: function(res) {
                $("#list_result").html(res);
            }
        });
    }

    // function fetchAll() {
    //     $.ajax({
    //         url: '{{ route('accountReportsList') }}',
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

<!--jquery cdn-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!--select2 cdn-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ URL::asset('/assets/js/pages/select2.init.js') }}">

@endsection
