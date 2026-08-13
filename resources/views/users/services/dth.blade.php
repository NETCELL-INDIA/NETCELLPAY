@extends('layouts.master')

@section('title')

    DTH Recharge

@endsection

@section('css')

    <!--datatable css-->

    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />

    <!--datatable responsive css-->

    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet"

        type="text/css" />

    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@endsection

@section('content')

    @component('components.breadcrumb')

        @slot('li_1')

            Services

        @endslot

        @slot('title')

            DTH Recharge

        @endslot

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

                    <h4 class="card-title mb-0 flex-grow-1">DTH Recharge</h4>

                    <div class="flex-shrink-0">

                    </div>

                </div>

                <div class="card-body">

                    <form id="pay_form">

                        <div class="row gy-3">



                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0">DTH Number</label>

                                    <input type="number" class="form-control" name="number" value="" id="number" placeholder="Enter DTH Number">

                                </div>

                            </div>

                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0">Provider </label>

                                    <select class="form-control provider_id" name="provider_id" id="provider_id">

                                        <option value="" selected>Select Provider</option>

                                    </select>

                                </div>

                            </div>

                            

                            <div class="col-lg-2">

                                <label class="form-label mb-0">Amount </label>

                                <input type="number" class="form-control" name="amount_i" id="amount_i" placeholder="Enter Amount">

                            </div>

                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0"></label>

                                    <button type="button" onclick="getDthInfo()" id="get_DthInfo_btn"

                                        class="form-control btn btn-secondary bg-gradient waves-effect waves-light">DTH Info</button>

                                </div>

                            </div>

                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0"></label>

                                    <button type="button" onclick="getDthPlans()" id="get_DthPlans_btn"

                                        class="form-control btn btn-secondary bg-gradient waves-effect waves-light">Plan Details</button>

                                </div>

                            </div>

                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0"></label>

                                    <button type="button" onclick="HeavyRefresh()" id="get_HeavyRefresh_btn"

                                        class="form-control btn btn-secondary bg-gradient waves-effect waves-light">Heavy Refresh</button>

                                </div>

                            </div>

                            

                            <div class="col-lg-2">

                                <div>

                                    <label class="form-label mb-0"></label>

                                    <button type="button" id="recharge_btn" onclick="rechargeNow()"

                                        class="form-control btn btn-warning bg-gradient waves-effect waves-light">Recharge

                                        Now</button>

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

                    <h4 class="card-title mb-0 flex-grow-1">Last 5 Transactions</h4>

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

    <div id="detailsModal" class="modal" tabindex="-1" aria-labelledby="detailsModalLabel" data-bs-backdrop="static"

        data-bs-keyboard="false" aria-hidden="true" style="display: none;">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="detailsModalLabel">Confirm Details</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

                </div>

                <div class="modal-body">

                    <form>

                        @csrf

                        <table class="table table-success table-striped align-middle table-nowrap mb-0">

                            <thead>

                                <tr>

                                    <th>DTH Number : </th>

                                    <th id="cd_number"></th>

                                </tr>

                            </thead>

                            <thead>

                                <tr>

                                    <th>Provider Name : </th>

                                    <th id="cd_provider"></th>



                                </tr>

                            </thead>

                            <thead>

                                <tr>

                                    <th>Amount : </th>

                                    <th id="cd_amount"></th>

                                </tr>

                            </thead>



                        </table>

                        <div class="mb-3">

                            <label>Enter PIN <span class="text-danger">*</span></label>

                            <input type="number" name="t_pin" id="t_pin" class="form-control" required="">

                        </div>

                    </form>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                    <button type="submit" class="btn btn-primary" id="recharge_now_btn"

                        onclick="rechargeConfirm()">Confirm Now</button>

                </div>

            </div><!-- /.modal-content -->

        </div><!-- /.modal-dialog -->

    </div><!-- /.modal -->





    <div id="dthInfoModal" class="modal" tabindex="-1" aria-labelledby="dthInfoModalLabel" data-bs-backdrop="static"

        data-bs-keyboard="false" aria-hidden="true" style="display: none;">

        <div class="modal-dialog modal-dialog-scrollable">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="dthInfoModalLabel">DTH Customer Info</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

                </div>

                <div class="modal-body">

                    <table class="table table-bordered m-0">

                        <tbody id="dth_info_details"></tbody>

                    </table>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                </div>

            </div>

        </div>

    </div>



    <div id="dthPlansModal" class="modal receipt" tabindex="-1" aria-labelledby="dthPlansModalLabel" data-bs-backdrop="static"

        data-bs-keyboard="false" aria-hidden="true" style="display: none;">

        <div class="modal-dialog modal-dialog-scrollable">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="dthPlansModalLabel">DTH Plan Details</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

                </div>

                <div class="modal-body">

                    <div id="dth_plan_tab" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>

                    <table class="table table-bordered m-0 mt-2">

                        <thead>

                            <tr>

                                <th>Details</th>

                                <th>Validity</th>

                                <th>Amount</th>

                            </tr>

                        </thead>

                        <tbody id="dth_plan_details"></tbody>

                    </table>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

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

        fetchAll();

        fatchProviderAndState();



        function fetchAll() {

            $.ajax({

                url: '{{ route('serviceRechargeReportsList') }}',

                method: 'post',

                data: {

                    _token: '{{ csrf_token() }}'

                },

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



        function fatchProviderAndState() {

            $("#preloader").attr("style", "display:block");

            $.ajax({

                url: '{{ route('servivceProviderStateList') }}',

                method: 'post',

                data: {

                    service: 2,

                    _token: '{{ csrf_token() }}'

                },

                success: function(res) {

                    //$('#provider_id').empty();

                    $.each(res.provider, function(k, v) {

                        $('#provider_id').append('<option value="' + v.id + '">' + v.provider_name

                            .toUpperCase() + '</option>');

                    });

                    $("#preloader").hide();

                }

            });

        }







        function dthInfoLabel(key) {
            return String(key).replace(/_/g, ' ').replace(/\b\w/g, function(ch) {
                return ch.toUpperCase();
            });
        }

        function showDthInfo(data) {
            var rows = '';
            var monthly = '';
            $.each(data || {}, function(key, val) {
                if (val === null || val === '' || typeof val === 'object') {
                    return;
                }
                var label = dthInfoLabel(key);
                rows += '<tr><th style="width:40%">' + $('<div>').text(label).html() + '</th><td>' + $('<div>').text(val).html() + '</td></tr>';
                if (!monthly && /monthly|amount|rs/i.test(String(key)) && $.isNumeric(val)) {
                    monthly = val;
                }
            });
            if (!rows) {
                Error_Msg("Oops...", "DTH customer details not found for this number.", "error");
                return;
            }
            $('#dth_info_details').html(rows);
            $('#dthInfoModal').modal('show');
            if (monthly) {
                $("#amount_i").val(monthly);
            }
        }

        function getDthInfo() {
            var number = $("#number").val();
            var provider_id = $("#provider_id").val();
            if (number == "") {
                Error_Msg("Oops...", "Please Enter DTH Number", "error");
                return;
            }
            if (provider_id == "") {
                Error_Msg("Oops...", "Please Select Provider", "error");
                return;
            }
            $("#get_DthInfo_btn").text('Wait...');
            $('#get_DthInfo_btn').prop('disabled', true);
            $.ajax({
                url: '{{ route('serviceDthInfo') }}',
                method: 'post',
                data: {
                    provider_id: provider_id,
                    number: number,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    $("#get_DthInfo_btn").text('DTH Info');
                    $('#get_DthInfo_btn').prop('disabled', false);
                    if (data.type == "success") {
                        showDthInfo(data.data);
                    } else {
                        Error_Msg("DTH Info", data.message || "Unable to fetch DTH info.", "error");
                    }
                },
                error: function() {
                    $("#get_DthInfo_btn").text('DTH Info');
                    $('#get_DthInfo_btn').prop('disabled', false);
                    Error_Msg("DTH Info", "Unable to fetch DTH info.", "error");
                }
            });
        }

        function SelectDthAmount(amount) {
            $("#amount_i").val(amount);
            $('#dthPlansModal').modal('hide');
        }

        function DthPlanShow(id) {
            $('.dth_list_tr').hide();
            $('.dth_list_' + id).show();
        }

        function getDthPlans() {
            var provider_id = $("#provider_id").val();
            if (provider_id == "") {
                Error_Msg("Oops...", "Please Select Provider", "error");
                return;
            }
            $("#get_DthPlans_btn").text('Wait...');
            $('#get_DthPlans_btn').prop('disabled', true);
            $.ajax({
                url: '{{ route('serviceDthPlans') }}',
                method: 'post',
                data: {
                    provider_id: provider_id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    $("#get_DthPlans_btn").text('Plan Details');
                    $('#get_DthPlans_btn').prop('disabled', false);
                    if (data.type == "success") {
                        var html_tab = '';
                        $('#dth_plan_tab').empty();
                        $('#dth_plan_details').empty();
                        var firstTab = '';
                        $.each(data.data || {}, function(key, list) {
                            var tab_val = String(key).replace(/[^a-zA-Z0-9_-]/g, '_');
                            if (!firstTab) firstTab = tab_val;
                            html_tab = '<button type="button" class="btn btn-sm btn-primary" onclick="DthPlanShow(\'' + tab_val + '\')">' + $('<div>').text(key).html() + '</button>';
                            $('#dth_plan_tab').append(html_tab);
                            $.each(list || [], function(_, val) {
                                var row = '<tr class="dth_list_tr dth_list_' + tab_val + '" onclick="SelectDthAmount(\'' + (val.rs || '') + '\')">'
                                    + '<td>' + $('<div>').text(val.desc || '').html() + '</td>'
                                    + '<td>' + $('<div>').text(val.validity || '').html() + '</td>'
                                    + '<td><a style="font-size:18px;font-weight:bold;background:#0c007d;border-radius:5px;padding:6px 10px;color:#fff;">' + $('<div>').text(val.rs || '').html() + '</a></td>'
                                    + '</tr>';
                                $('#dth_plan_details').append(row);
                            });
                        });
                        if (!$('#dth_plan_details tr').length) {
                            Error_Msg("Plan Details", "No DTH plans found for this operator.", "error");
                            return;
                        }
                        if (firstTab) DthPlanShow(firstTab);
                        $('#dthPlansModal').modal('show');
                    } else {
                        Error_Msg("Plan Details", data.message || "Unable to fetch DTH plans.", "error");
                    }
                },
                error: function() {
                    $("#get_DthPlans_btn").text('Plan Details');
                    $('#get_DthPlans_btn').prop('disabled', false);
                    Error_Msg("Plan Details", "Unable to fetch DTH plans.", "error");
                }
            });
        }

        function HeavyRefresh() {
            var number = $("#number").val();
            var provider_id = $("#provider_id").val();
            if (number == "") {
                Error_Msg("Oops...", "Please Enter DTH Number", "error");
                return;
            }
            if (provider_id == "") {
                Error_Msg("Oops...", "Please Select Provider", "error");
                return;
            }
            $("#get_HeavyRefresh_btn").text('Wait...');
            $('#get_HeavyRefresh_btn').prop('disabled', true);
            $.ajax({
                url: '{{ route('serviceDthHeavyRefresh') }}',
                method: 'post',
                data: {
                    provider_id: provider_id,
                    number: number,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    $("#get_HeavyRefresh_btn").text('Heavy Refresh');
                    $('#get_HeavyRefresh_btn').prop('disabled', false);
                    if (data.type == "success") {
                        showDthInfo(data.data);
                    } else {
                        Error_Msg("Heavy Refresh", data.message || "Unable to refresh DTH info.", "error");
                    }
                },
                error: function() {
                    $("#get_HeavyRefresh_btn").text('Heavy Refresh');
                    $('#get_HeavyRefresh_btn').prop('disabled', false);
                    Error_Msg("Heavy Refresh", "Unable to refresh DTH info.", "error");
                }
            });
        }

        function rechargeNow() {

            amount = $("#amount_i").val();

            number = $("#number").val();

            service_id = 1;

            provider_id = $("#provider_id").val();

            if (number == "") {

                Error_Msg("Oops...", "Please Enter DTH Number", "error");

            } else if (provider_id == "") {

                Error_Msg("Oops...", "Please Select Provider", "error");

            } else if (amount == "") {

                Error_Msg("Oops...", "Please Enter Amount", "error");

            } else {

                $("#cd_number").text(number);

                $("#cd_provider").text($("#provider_id :selected").text());

                $("#cd_amount").text(amount);

                $('#detailsModalLabel').text('Confirm Details');

                $('#detailsModal').modal('show');

            }

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

                        fetchAll();

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







        function receiptPrint() {

            $('.receipt_modal_body').find('.modal-body').print();

        }





        function rechargeConfirm() {

            var number = $("#number").val();

            var service_id = 2;

            var provider_id = $("#provider_id").val();

            var amount = $("#amount_i").val();

            var pin = $("#t_pin").val();

            $("#recharge_now_btn").text('Loading...');

            $('#recharge_now_btn').prop('disabled', true);

            $.ajax({

                url: '{{ route('serviceRecharge') }}',

                method: 'post',

                data: {

                    number: number,

                    service_id: service_id,

                    provider_id: provider_id,

                    amount: amount,

                    pin: pin,

                    _token: '{{ csrf_token() }}',

                },

                success: function(data) {

                    $("#recharge_now_btn").text('Confirm');

                    $('#recharge_now_btn').prop('disabled', false);

                    fetchAll();

                    //console.log(data); 

                    if(data.type == "success"){
                        if (data.status == "Success") {
                            $('#detailsModal').modal('hide');

                            Error_Msg("Success", data.remark, "success");

                            receiptView(data.id);

                            $("#pay_form")[0].reset();

                        } else if (data.status == "Failed") {

                            $('#detailsModal').modal('hide');

                            Error_Msg("Failed", data.remark, "error");

                            receiptView(data.id);

                            $("#pay_form")[0].reset();

                        } else if (data.status == "Pending") {

                            $('#detailsModal').modal('hide');

                            Error_Msg("Pending", data.remark, "info");

                            receiptView(data.id);

                            $("#pay_form")[0].reset();
                        }
                    } else {

                        Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);

                    }

                },

                error: function(err) {

                    console.log(err);

                    Error_Msg("Oops...", "Something went wrong!", "error");

                    $("#recharge_now_btn").text('Confirm');

                    $('#recharge_now_btn').prop('disabled', false);

                }

            });

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



    {{-- <script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}



    <script src="{{ URL::asset('assets/js/app.min.js') }}"></script>



    <!--jquery cdn-->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"

        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!--select2 cdn-->

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ URL::asset('assets/js/pages/select2.init.js') }}">

    @endsection

