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

                <h4 class="text-center text-secondary my-3">No record found</h4>

            </div>

        </div>

    </div>

</div>



<!-- API Logs Modal -->
<div class="modal fade" id="apiRequestLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Api request and response log list <small class="text-muted" id="apiLogsModalApiName"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Latest 15 records</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Order Id</th>
                                <th>Date &amp; Time</th>
                                <th>Type</th>
                                <th>URL</th>
                                <th>Request</th>
                                <th>Response</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="apiRequestLogsBody">
                            <tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="apiRequestLogDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">API Log Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">URL</label>
                <pre class="bg-light border rounded p-2 small" id="apiLogDetailUrl" style="white-space:pre-wrap;word-break:break-word;"></pre>
                <label class="form-label fw-semibold">Header</label>
                <pre class="bg-light border rounded p-2 small" id="apiLogDetailHeader" style="white-space:pre-wrap;word-break:break-word;"></pre>
                <label class="form-label fw-semibold">Request</label>
                <pre class="bg-light border rounded p-2 small" id="apiLogDetailRequest" style="white-space:pre-wrap;word-break:break-word;"></pre>
                <label class="form-label fw-semibold">Response</label>
                <pre class="bg-light border rounded p-2 small" id="apiLogDetailResponse" style="white-space:pre-wrap;word-break:break-word;"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Details Modals -->

<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel" data-bs-backdrop="static"  aria-hidden="true" style="display: none;">

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

                                <label class="badge text-bg-secondary">Recharge Callback Url: {{ env('USER_HOST')}}/recharge-callback/<label class="call_id"></label></label>

                            </div>

                            <div class="col-xxl-6 col-md-6">

                                <label class="badge text-bg-secondary">Complaint Callback Url: {{ env('USER_HOST')}}/complaint-callback/<label class="call_id"></label></label>

                            </div>

                                

                                

                                

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

                                    <input type="text" class="form-control" name="api_password" id="api_password" placeholder="Optional">

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

                                    <label for="api_url" class="col-form-label">Api URL: {API_USERNAME},{API_KEY},{NUMBER},{AMOUNT},{PROVIDER_CODE},{ORDER_ID},{STATE_CODE}</label>

                                    <textarea class="form-control" name="api_url" id="api_url"></textarea>

                                </div>

                            </div>

                            <div class="col-xxl-12 col-md-12">

                                <div>

                                    <label for="balance_check_url" class="col-form-label">Balance Check URL: {API_USERNAME},{API_PASSWORD},{API_KEY}</label>

                                    <textarea class="form-control" name="balance_check_url" id="balance_check_url"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-12 col-md-12">

                                <div>

                                    <label for="complaint_api_url" class="col-form-label">Complaint Api URL: {ORDER_ID},{SUBJECT}</label>

                                    <textarea class="form-control" name="complaint_api_url" id="complaint_api_url"></textarea>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_api_method" class="col-form-label">Complaint Api Method:</label>

                                    <select name="complaint_api_method" id="complaint_api_method" class="form-control">

                                        <option value="POST">POST</option>

                                        <option value="GET" selected="">GET</option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_status_value" class="col-form-label">Complaint Status Value:</label>

                                    <input type="text" class="form-control" name="complaint_status_value" id="complaint_status_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_success_value" class="col-form-label">Complaint Success Value:</label>

                                    <input type="text" class="form-control" name="complaint_success_value" id="complaint_success_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_failed_value" class="col-form-label">Complaint Failed Value:</label>

                                    <input type="text" class="form-control" name="complaint_failed_value" id="complaint_failed_value" required="required">

                                </div>

                            </div>

                            <h6>Callback Setting (Complaint Api)</h6>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="complaint_callback_status_value" class="col-form-label">Status Value:</label>

                                    <input type="text" class="form-control" name="complaint_callback_status_value" id="complaint_callback_status_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="complaint_callback_success_value" class="col-form-label">Success Value:</label>

                                    <input type="text" class="form-control" name="complaint_callback_success_value" id="complaint_callback_success_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="complaint_callback_failed_value" class="col-form-label">Failed Value:</label>

                                    <input type="text" class="form-control" name="complaint_callback_failed_value" id="complaint_callback_failed_value" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_callback_remark" class="col-form-label">Callback Remark:</label>

                                    <input type="text" class="form-control" name="complaint_callback_remark" id="complaint_callback_remark" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="complaint_callback_api_method" class="col-form-label">Api Method:</label>

                                    <select name="complaint_callback_api_method" id="complaint_callback_api_method" class="form-control">

                                        <option value="POST">POST</option>

                                        <option value="GET" selected="">GET</option>

                                    </select>

                                </div>

                            </div>





                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="status_value" class="col-form-label">Recharge Status Value:</label>

                                    <input type="text" class="form-control" name="status_value" id="status_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="success_value" class="col-form-label">Recharge Success Value:</label>

                                    <input type="text" class="form-control" name="success_value" id="success_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-1 col-md-6">

                                <div>

                                    <label for="failed_value" class="col-form-label">Recharge Failed Value:</label>

                                    <input type="text" class="form-control" name="failed_value" id="failed_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-1 col-md-6">

                                <div>

                                    <label for="refund_value" class="col-form-label">Recharge Refund Value:</label>

                                    <input type="text" class="form-control" name="refund_value" id="refund_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="pending_value" class="col-form-label">Recharge Pending Value:</label>

                                    <input type="text" class="form-control" name="pending_value" id="pending_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="error_value" class="col-form-label">Recharge Error Value:</label>

                                    <input type="text" class="form-control" name="error_value" id="error_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="error_value_response" class="col-form-label">Recharge Error Value(Response):</label>

                                    <input type="text" class="form-control" name="error_value_response" id="error_value_response" required="required">

                                </div>

                            </div>









                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="order_id_value" class="col-form-label">Recharge Order Id Value :</label>

                                    <input type="text" class="form-control" name="order_id_value" id="order_id_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="operator_id_value" class="col-form-label">Recharge Operator Id Value:</label>

                                    <input type="text" class="form-control" name="operator_id_value" id="operator_id_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="api_method" class="col-form-label">Recharge Api Method:</label>

                                    <select name="api_method" id="api_method" class="form-control">

                                        <option value="POST">POST</option>

                                        <option value="GET" selected="">GET</option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="api_format" class="col-form-label">Recharge Api Format:</label>

                                    <select name="api_format" id="api_format" class="form-control">

                                        <option value="JSON" selected="">JSON</option>

                                        <option value="XML">XML</option>

                                        <option value="TEXT">TEXT</option>

                                    </select>

                                </div>

                            </div>

                            

                            <h6>Callback Setting (Recharge Api)</h6>

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

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="callback_refund_value" class="col-form-label">Recharge Refund Value:</label>

                                    <input type="text" class="form-control" name="callback_refund_value" id="callback_refund_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

                                <div>

                                    <label for="callback_order_id_value" class="col-form-label">Order Id Value:</label>

                                    <input type="text" class="form-control" name="callback_order_id_value" id="callback_order_id_value" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-2 col-md-6">

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

                            <div class="col-xxl-3 col-md-6">

                                <div class="form-check mt-4">

                                    <input class="form-check-input" type="checkbox" name="store_log" id="store_log" value="1">

                                    <label class="form-check-label" for="store_log">API Store Log</label>

                                    <small class="d-block text-muted">Tick to save and show this API request/response only.</small>

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

                    <h4 class="text-center text-secondary my-3">No record found</h4>

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



<!-- State Code Details Modals -->

<div id="StateCodeDetailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="StateCodeDetailsModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="display: none;">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="bulkUpdateFormState" method="post">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title" id="StateCodeDetailsModalLabel">State Code Details</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>

            </div>

            <div class="modal-body">
            <input type="hidden" name="state_api_id" id="state_api_id" value="">
                <div class="card-body" id="state_code_result">

                    <h4 class="text-center text-secondary my-3">No record found</h4>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                <button type="submit" class="btn btn-primary" id="bulk_update_state_code">Update All</button>

            </div>  

            </form>

        </div><!-- /.modal-content -->

    </div><!-- /.modal-dialog -->

</div><!-- /.modal -->



@endsection

@section('script')

<script>

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

        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $.ajax({

            url: '{{ route('apisList') }}',

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


    $(document).on('click', '.setStateCode', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        $("#state_api_id").val(id);

        //fatchApiAndService();

        fetchStateCodeAll(id);

        $('#StateCodeDetailsModal').modal('show');

    });


    function fetchStateCodeAll(id) {

        if(id == ""){

            id = 1;

        }
        $("#preloader").attr("style", "display:block");

        $.ajax({

            url: '{{ route('apisGetStateCode') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}', id},

            success: function(res) {

                $("#state_code_result").html(res);

                $("#preloader").hide();

            }

        });

    }


    $(document).on('click', '.updateStateCode', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        var state_id = $("#"+id+"_state_id").val();

        var state_code = $("#"+id+"_state_code").val();

        var api_id = $("#state_api_id").val();



        $.ajax({

        url: '{{ route('apisSingleUpdateStateCode') }}',

        method: 'post',

        data: {

            api_id,state_id,state_code,

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



    $("#bulkUpdateFormState").submit(function(e) {

        e.preventDefault();

        const fd = new FormData(this);

        $("#bulk_update_state_code").text('Please wait...');

        $('#bulk_update_state_code').prop('disabled', true);

        $.ajax({

        url: '{{ route('apisBulkUpdateStateCode') }}',

        method: 'post',

        data: fd,

        cache: false,

        contentType: false,

        processData: false,

        dataType: 'json',

        success: function(data) {

            if(data.type=="error"){

                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

                $("#bulk_update_state_code").text('Save Changes');

                $('#bulk_update_state_code').prop('disabled', false);

            }else if(data.type=="success"){  

                Error_Msg("Updated","Updated Successfully!","success");

                fetchAll(1,10);

                $("#bulk_update_state_code").text('Save Changes');

                $('#bulk_update_state_code').prop('disabled', false);

            }else{

                Error_Msg("Oops...","Something went wrong!","error");

                $("#bulk_update_state_code").text('Save Changes');

                $('#bulk_update_state_code').prop('disabled', false);

            }

        },

        error: function( jqXhr, textStatus, errorThrown ){

            Error_Msg("Oops...","Something went wrong!","error");

            $("#bulk_update_state_code").text('Save Changes');

            $('#bulk_update_state_code').prop('disabled', false);

        }

        });

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

    var apiRequestLogsCache = [];

    function clipApiText(str, len) {
        str = String(str == null ? '' : str).replace(/\s+/g, ' ').trim();
        if (!str || str === '""' || str === '[]') return '-';
        return str.length > len ? str.slice(0, len) + '…' : str;
    }

    function escapeApiHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function prettyApiJson(str) {
        try {
            return JSON.stringify(typeof str === 'string' ? JSON.parse(str) : str, null, 2);
        } catch (e) {
            return String(str == null ? '' : str);
        }
    }

    $(document).on('click', '.viewApiLogs', function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        var name = $(this).data('name') || '';
        $('#apiLogsModalApiName').text(name ? '· ' + name : '');
        $('#apiRequestLogsBody').html('<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>');
        var modalEl = document.getElementById('apiRequestLogsModal');
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }

        $.ajax({
            url: '{{ route('apisRequestLogs') }}',
            method: 'post',
            data: { id: id, _token: '{{ csrf_token() }}' },
            success: function (res) {
                apiRequestLogsCache = (res && res.data) ? res.data : [];
                if (res && res.api_name) {
                    $('#apiLogsModalApiName').text('· ' + res.api_name + ' · Latest 15');
                }
                var html = '';
                if (!apiRequestLogsCache.length) {
                    html = '<tr><td colspan="8" class="text-center text-muted">No API logs found</td></tr>';
                } else {
                    apiRequestLogsCache.forEach(function (row, idx) {
                        html += '<tr>' +
                            '<td>' + (idx + 1) + '</td>' +
                            '<td>' + escapeApiHtml(row.txnid || '-') + '</td>' +
                            '<td>' + escapeApiHtml(row.created_at || '-') + '</td>' +
                            '<td>' + escapeApiHtml(row.modal || '-') + '</td>' +
                            '<td title="' + escapeApiHtml(row.url) + '">' + escapeApiHtml(clipApiText(row.url, 42)) + '</td>' +
                            '<td title="' + escapeApiHtml(row.request) + '">' + escapeApiHtml(clipApiText(row.request, 36)) + '</td>' +
                            '<td title="' + escapeApiHtml(row.response) + '">' + escapeApiHtml(clipApiText(row.response, 36)) + '</td>' +
                            '<td><button type="button" class="btn btn-sm btn-soft-info py-0 px-2" onclick="showApiRequestLogDetail(' + idx + ')">View</button></td>' +
                            '</tr>';
                    });
                }
                $('#apiRequestLogsBody').html(html);
            },
            error: function () {
                $('#apiRequestLogsBody').html('<tr><td colspan="8" class="text-center text-danger">Failed to load logs</td></tr>');
            }
        });
    });

    window.showApiRequestLogDetail = function (idx) {
        var row = apiRequestLogsCache[idx];
        if (!row) return;
        $('#apiLogDetailUrl').text(row.url || '-');
        $('#apiLogDetailHeader').text(prettyApiJson(row.header || '-'));
        $('#apiLogDetailRequest').text(prettyApiJson(row.request || '-'));
        $('#apiLogDetailResponse').text(prettyApiJson(row.response || '-'));
        var modalEl = document.getElementById('apiRequestLogDetailModal');
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
    };





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

        $("#bulk_update_provider_code").text('Please wait...');

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

                fetchAll(1,10);

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

                    fetchAll(1,10);

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

                $(".call_id").text(data.data.id);



                ///

                $("#api_username").val(data.data.api_username);

                $("#api_password").val(data.data.api_password);

                $("#api_key").val(data.data.api_key);

                $("#api_url").val(data.data.api_url);

                $("#complaint_api_url").val(data.data.complaint_api_url);

                $("#balance_check_url").val(data.data.balance_check_url);

                $("#status_value").val(data.data.status_value);

                ///

                $("#complaint_api_method").val(data.data.complaint_api_method).change();

                $("#complaint_status_value").val(data.data.success_value);

                $("#complaint_success_value").val(data.data.success_value);

                $("#complaint_failed_value").val(data.data.success_value);

                ///

                $("#refund_value").val(data.data.refund_value);

                $("#callback_refund_value").val(data.data.callback_refund_value);

                $("#complaint_callback_status_value").val(data.data.complaint_callback_status_value);

                $("#complaint_callback_success_value").val(data.data.complaint_callback_success_value);

                $("#complaint_callback_failed_value").val(data.data.complaint_callback_failed_value);

                $("#complaint_callback_remark").val(data.data.complaint_callback_remark);

                $("#complaint_callback_api_method").val(data.data.complaint_callback_api_method).change();

                ///

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

                $("#store_log").prop('checked', Number(data.data.store_log) === 1);





                ///

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

        $("#edit_details_btn").text('Please wait...');

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

            var msg = (jqXhr.responseJSON && jqXhr.responseJSON.message) ? jqXhr.responseJSON.message : "Something went wrong!";

            Error_Msg("Oops...", msg, "error");

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








@endsection

