@extends('layouts.master')
@section('title') Recharge Reports @endsection
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
@slot('title')Recharge Reports @endslot
@endcomponent
<style>
        .receipt {
            --vz-modal-width: 900px;
        }
    </style>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Filter</h4>
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
                                <label class="form-label mb-0">Req Order Id</label>
                                <input type="text" class="form-control" placeholder="Enter Order Id" name="req_order_id" value="" id="req_order_id">
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
                                <label class="form-label mb-0">Mobile/DTH Number</label>
                                <input type="number" class="form-control" placeholder="Enter Number" name="number_text" value="" id="number_text">
                            </div>
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
                                <button type="button" id="search_btn" class="form-control btn btn-secondary bg-gradient waves-effect waves-light" onclick="fetchAll(1,10)">Search</button>
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
                <h4 class="card-title mb-0 flex-grow-1">Recharge Reports List</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No records found</h4>
            </div>
        </div>
    </div>
</div>


    <!-- Details Modals -->
    <div id="receiptModal" class="modal flip receipt" tabindex="-1" aria-labelledby="receiptModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Recharge Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
                </div>
                <div class="modal-body receipt_modal_body">
                    <table width="100%">
                        <tbody>
                            <tr>
                                <td>
                                    <table width="100%" style="border: 1px solid black">
                                        <tbody>
                                            @php
                                            $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
                                            @endphp
                                            <tr>
                                                <td style="width:50%"><img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" style="height: 60px;"></td>
                                                <td style="width:50%;"> <strong
                                                        style="display: inline-block;overflow: hidden;white-space: nowrap;float: right;padding-right: 10px;">{{$company->company_name}}</strong><br> <strong
                                                        style="float: right;padding-right: 10px;">Email: {{$company->support_email}}</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" style="border: 1px solid black">
                                        <tbody>
                                            <tr>
                                                <td
                                                    style="width:33.33% ; border: 1px solid black;text-align: left;padding-left: 10px;">
                                                    <strong>Agent Name : 
                                                    {{DB::table('users')->where('id',Session::get('user_id'))->first()->first_name}}
                                                        
                                                    {{DB::table('users')->where('id',Session::get('user_id'))->first()->last_name}}
                                                    </strong><br>
                                                    <strong>Agent Id : {{DB::table('users')->where('id',Session::get('user_id'))->first()->mobile_number}}</strong><br>
                                                    <strong>Order Id : <strong id="rc_order_id"></strong></strong><br>
                                                </td>
                                                <td style="width:33.33% ; border: 1px solid black">
                                                    <h3 class="" style="margin-top: 15px;text-align: center;text-transform: uppercase;" id="rc_transaction_type" ></h3>
                                                </td>
                                                <td style="width:33.33% ; border: 1px solid black;text-align: right;padding-right: 10px;">
                                                    <div> <span>Date &amp; Time</span><br>
                                                     <strong id="rc_date_time"></strong> 
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" style="text-align: center;border: 1px solid black">
                                        <tbody>
                                            <tr>
                                                <td style="width:100%; border: 1px solid black; margin:5px">
                                                    <strong id="rc_remark"></strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" style="border: 1px solid black">
                                        <tbody>
                                            <tr class="">
                                                <th style="text-align: center;border: 1px solid black">Provider</th>
                                                <th style="text-align: center;border: 1px solid black"
                                                    class="text-center">Status</th>
                                                <th style="text-align: center;border: 1px solid black"
                                                    class="text-center">Operator Id </th>
                                                <th style="text-align: center;border: 1px solid black"
                                                    class="text-center">Amount</th>
                                            </tr>
                                            <tr>
                                                <td width="40%" style="text-align: center;border: 1px solid black">
                                                    <p class="font-weight-semibold mb-1" style="text-transform: uppercase;" id="rc_provider"></p>
                                                </td>
                                                <td width="10%" style="text-align: center;border: 1px solid black">
                                                    <p class="font-weight-semibold mb-1" style="text-transform: uppercase;" id="rc_status"></p>
                                                </td>
                                                <td width="15%" style="text-align: center;border: 1px solid black">
                                                    <p class="font-weight-semibold mb-1" id="rc_operator_id"></p>
                                                </td>
                                                <td width="15%" style="text-align: center;border: 1px solid black">
                                                    <p class="font-weight-semibold mb-1" id="rc_amount"></p>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" style="border: 1px solid black">
                                        <tbody>
                                            <tr>
                                                <td><span style="color: red;">Receipt was created on a computer and is
                                                        valid without the signature and seal.</span> </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary" id="receipt_print_btn"
                        onclick="receiptPrint()"><i class="ri-printer-line align-bottom me-1"></i> Print Receipt</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

        <!-- Details Modals -->
    <div id="complaintModal" class="modal" tabindex="-1" aria-labelledby="complaintModalLabel" data-bs-backdrop="static"
    data-bs-keyboard="false" aria-hidden="true" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="complaintModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
                </div>
                <div class="modal-body">
                    <form>
                        @csrf
                        <input type="hidden" name="id" id="cs_id">
                        <div class="mb-3">
                            <label>Subject : <span class="text-danger">*</span></label>
                            <textarea name="subject" id="cs_subject" class="form-control" required=""></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="complaint_now_btn" onclick="complaintSubmit()">Submit</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

@endsection
@section('script')
<script>
    fetchAll(1,10);
    function fetchAllSearch() {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        var tbl_type = $("#tbl_type").val();
        $("#search_btn").text('Loading...');
        $('#search_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('rechargeReportsList') }}',
            method: 'post',
            data: {
                from_date : from_date,
                to_date : to_date,
                tbl_type : tbl_type,
                page : 1,
                limit : 10,
                _token: '{{csrf_token()}}',
            },
            success: function(res) {
                $("#search_btn").text('Search');
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
        var req_order_id = $("#req_order_id").val();
        var order_id = $("#order_id").val();
        var number = $("#number_text").val();
        // var order_id = $("#order_id").val();
        // var fund_type = $("#fund_type").val();
        // var tr_type = $("#tr_type").val();
        var tbl_type = $("#tbl_type").val();
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('rechargeReportsList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',from_date,to_date,order_id,req_order_id,number,page,limit},
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


    function receiptView(id) {
        $("#receipt_btn").text('Loading...');
        $('#receipt_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('serviceRechargeGetReciept') }}',
            method: 'post',
            data: {
                id,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if(res.type == "success"){
                    $("#receipt_btn").text('Receipt');
                    $('#receipt_btn').prop('disabled', false);
                    $("#rc_order_id").text(res.data.order_id);
                    $("#rc_date_time").text(res.data.created_at);
                    $("#rc_transaction_type").text(res.data.transaction_type);
                    $("#rc_remark").text(res.data.remark);
                    $("#rc_provider").text(res.provider);
                    $("#rc_status").text(res.data.status);
                    $("#rc_operator_id").text(res.data.operator_id);
                    $("#rc_amount").text("₹ "+res.data.total_amount);
                    $('#receiptModal').modal('show');
                }else{
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
                
            }
        });
    }


    function complaintView(id,order_id) {
        $("#complaintModalLabel").text("Order ID : "+order_id);
        $("#cs_id").val(id);
        $('#complaintModal').modal('show');
    }

    function complaintSubmit() {
        var id = $("#cs_id").val();
        var subject = $("#cs_subject").val();
        $("#complaint_now_btn").text('Loading...');
        $('#complaint_now_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('serviceRechargeComplaint') }}',
            method: 'post',
            data: {
                id,subject,
                _token: '{{ csrf_token() }}',
            },
            success: function(data) {
                $("#complaint_now_btn").text('Submit');
                $('#complaint_now_btn').prop('disabled', false);
                if(data.type == "success"){
                    $('#complaintModal').modal('hide');
                    fetchAll(1,10);
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }else{
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
            },
            error: function(err) {
                console.log(err);
                Error_Msg("Oops...", "Something went wrong!", "error");
                $("#complaint_now_btn").text('Submit');
                $('#complaint_now_btn').prop('disabled', false);
            }
        });
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

<!--jquery cdn-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!--select2 cdn-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ URL::asset('/assets/js/pages/select2.init.js') }}">

@endsection
