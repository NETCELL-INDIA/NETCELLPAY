@extends('layouts.master')

@section('title') Manage Company @endsection

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

@slot('title')Manage Company @endslot

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

                <h4 class="card-title mb-0 flex-grow-1">Company List</h4>

                <!-- <div class="flex-shrink-0">

                    <div class="form-check form-switch form-switch-right form-switch-md">

                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="createNew()">Create New</button>

                    </div>

                </div> -->

            </div>

            <div class="card-body pb-0">
                <div class="alert alert-info py-2 mb-0" style="font-size:.85rem">
                    One company is shown here. Admin (ID 1) and User Portal (ID 2) database rows stay in sync when you save.
                </div>
            </div>

            <div class="card-body pt-2" id="list_result">

                <h4 class="text-center text-secondary my-3">No record found</h4>

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

                    <input type="hidden" name="old_company_logo" id="old_company_logo">

                    <input type="hidden" name="old_company_icon" id="old_company_icon">

                    <input type="hidden" name="old_invoice_logo" id="old_invoice_logo">

                    <div class="live-preview">

                        <div class="row gy-4">

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="domain" class="form-label">Domain: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="domain" id="domain" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="company_name" class="form-label">Company Name: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="company_name" id="company_name" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="support_number" class="form-label">Support No: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="support_number" id="support_number" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="support_number_2" class="form-label">Support No Alt: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="support_number_2" id="support_number_2" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="support_email" class="form-label">Support Email: <a style="color: red">*</a></label>

                                    <input type="eamil" class="form-control" name="support_email" id="support_email" required="required">

                                </div>

                            </div>

                            <!--end col-->

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="company_address" class="form-label">Company Address: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="company_address" id="company_address" required="required">

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="self_register" class="form-label">Self Register: <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 self_register" aria-label="Default select example" name="self_register">

                                        <option selected="">Select Mode</option>

                                        <option value="1">ON</option>

                                        <option value="0">OFF</option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="email_message" class="form-label">Email Message: <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 email_message" aria-label="Default select example" name="email_message">

                                        <option selected="">Select Mode</option>

                                        <option value="1">ON</option>

                                        <option value="0">OFF</option>

                                    </select>

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="company_logo" class="form-label">Company Logo: <a style="color: red">*</a></label>

                                    <input type="file" class="form-control" name="company_logo" id="company_logo" accept="image/png, image/gif, image/jpeg">

                                    

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="company_icon" class="form-label">Company Icon: <a style="color: red">*</a></label>

                                    <input type="file" class="form-control" name="company_icon" id="company_icon" accept="image/png, image/gif, image/jpeg">

                                    

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="invoice_logo" class="form-label">Invoice Logo: <a style="color: red">*</a></label>

                                    <input type="file" class="form-control" name="invoice_logo" id="invoice_logo" accept="image/png, image/gif, image/jpeg">

                                    

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="apk_file_name" class="form-label">App Link: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="apk_file_name" id="apk_file_name" >

                                </div>

                            </div>



                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <img src="" style="height: 60px;" id="company_logo_file">

                                </div>

                            </div>



                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <img src=""  style="height: 60px;" id="company_icon_file">

                                </div>

                            </div>



                            <div class="col-xxl-4 col-md-6">

                                <div>

                                    <img src="" style="height: 60px;" id="invoice_logo_file">

                                </div>

                            </div>





                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway" class="form-label">Payment Gateway UPI : <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 payment_gateway" aria-label="Default select example" name="payment_gateway">

                                        <option selected="">Select Mode</option>

                                        <option value="1">ON</option>

                                        <option value="0">OFF</option>

                                    </select>

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway_min" class="form-label">Payment Gateway UPI (Min): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway_min" id="payment_gateway_min" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway_max" class="form-label">Payment Gateway UPI (Max): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway_max" id="payment_gateway_max" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway_key" class="form-label">Payment Gateway UPI (Key): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway_key" id="payment_gateway_key" required="required">

                                </div>

                            </div>


                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway2" class="form-label">Payment Gateway QR : <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 payment_gateway2" aria-label="Default select example" name="payment_gateway2">

                                        <option selected="">Select Mode</option>

                                        <option value="1">ON</option>

                                        <option value="0">OFF</option>

                                    </select>

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway2_min" class="form-label">Payment Gateway QR (Min): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway2_min" id="payment_gateway2_min" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway2_max" class="form-label">Payment Gateway QR (Max): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway2_max" id="payment_gateway2_max" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="payment_gateway2_key" class="form-label">Payment Gateway QR (Key): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="payment_gateway2_key" id="payment_gateway2_key" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="sms_api_method" class="form-label">API Method (SMS): <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 sms_api_method" aria-label="Default select example" name="sms_api_method">

                                        <option selected="">Select Type</option>

                                        <option value="GET">GET</option>

                                        <option value="POST">POST</option>

                                    </select>

                                </div>

                            </div>



                            <div class="col-xxl-9 col-md-6">

                                <div>

                                    <label for="sms_request_url" class="form-label">Request URL (SMS) - {MSG},{MOB}: <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="sms_request_url" id="sms_request_url" required="required">

                                </div>

                            </div>



                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label for="whatsapp_api_method" class="form-label">API Method (Whatsapp): <a style="color: red">*</a></label>

                                    <select class="form-select mb-3 whatsapp_api_method" aria-label="Default select example" name="whatsapp_api_method">

                                        <option selected="">Select Type</option>

                                        <option value="GET">GET</option>

                                        <option value="POST">POST</option>

                                    </select>

                                </div>

                            </div>



                            <div class="col-xxl-9 col-md-6">

                                <div>

                                    <label for="whatsapp_request_url" class="form-label">Request URL (Whatsapp): <a style="color: red">*</a></label>

                                    <input type="text" class="form-control" name="whatsapp_request_url" id="whatsapp_request_url" required="required">

                                </div>

                            </div>

                            

                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="meta_kewords" class="form-label">Meta Kewords: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Meta Kewords" id="meta_kewords" name="meta_kewords" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="refund_policy" class="form-label">Refund Policy: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Refund Policy" id="refund_policy" name="refund_policy" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="privacy_policy" class="form-label">Privacy Policy: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Privacy Policy" id="privacy_policy" name="privacy_policy" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="terms_and_conditions" class="form-label">Terms And Conditions: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Terms And Conditions" id="terms_and_conditions" name="terms_and_conditions" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="header_value" class="form-label">Header Value: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Header Value" id="header_value" name="header_value" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>



                            <div class="col-xxl-6 col-md-6">

                                <div>

                                    <label for="footer_value" class="form-label">Footer Value: <a style="color: red">*</a></label>

                                    <textarea class="form-control" placeholder="Enter Footer Value" id="footer_value" name="footer_value" rows="3" required="" spellcheck="false"></textarea>

                                </div>

                            </div>

<!-- 



                            

                            <div class="col-xxl-3 col-md-6">

                                <div>

                                    <label class="form-label">Status:</label>

                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">

                                        <option selected="">Select Status</option>

                                        <option value="1">Active</option>

                                        <option value="0">Deactive</option>

                                    </select>

                                </div>

                            </div> -->

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

    

    function fetchAll(page,limit) {

        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $.ajax({

            url: '{{ route('manageCompanyList') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}',page,limit},

            success: function(res) {

                $("#list_result").html(res);

            }

        });

    }



    $(document).on('click', '.editDetails', function(e) {

        e.preventDefault();

        let id = $(this).attr('id');

        $.ajax({

          url: '{{ route('manageCompanyGet') }}',

          method: 'post',

          data: {

            id: id,

            _token: '{{ csrf_token() }}'

          },

          success: function(data) {

            if(data.type=="error"){

                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);

            }else if(data.type=="success"){  

                admin_logo_base = @json(rtrim(admin_asset('company_logo'), '/'));

                $("#edit_id").val(data.data.id);

                $("#domain").val(data.data.domain);

                $("#company_name").val(data.data.company_name);

                $("#support_number").val(data.data.support_number);

                $("#support_number_2").val(data.data.support_number_2);

                $("#support_email").val(data.data.support_email);

                $("#company_address").val(data.data.company_address);

                $(".self_register").val(data.data.self_register).change();

                $(".email_message").val(data.data.email_message).change();

                $("#old_company_logo").val(data.data.company_logo);

                $("#old_company_icon").val(data.data.company_icon);

                $("#old_invoice_logo").val(data.data.invoice_logo);

                $("#apk_file_name").val(data.data.apk_file_name);

                $("#company_logo_file").attr("src", admin_logo_base + "/" + data.data.company_logo);

                $("#company_icon_file").attr("src", admin_logo_base + "/" + data.data.company_icon);

                $("#invoice_logo_file").attr("src", admin_logo_base + "/" + data.data.invoice_logo);

                $(".payment_gateway").val(data.data.payment_gateway).change();

                $("#payment_gateway_min").val(data.data.payment_gateway_min);

                $("#payment_gateway_max").val(data.data.payment_gateway_max);

                $("#payment_gateway_key").val(data.data.payment_gateway_key);


                $(".payment_gateway2").val(data.data.payment_gateway2).change();

                $("#payment_gateway2_min").val(data.data.payment_gateway2_min);

                $("#payment_gateway2_max").val(data.data.payment_gateway2_max);

                $("#payment_gateway2_key").val(data.data.payment_gateway2_key);


                $(".sms_api_method").val(data.data.sms_api_method).change();

                $("#sms_request_url").val(data.data.sms_request_url);

                $(".whatsapp_api_method").val(data.data.whatsapp_api_method).change();

                $("#whatsapp_request_url").val(data.data.whatsapp_request_url);

                $("#meta_kewords").text(data.data.meta_kewords);

                $("#refund_policy").text(data.data.refund_policy);

                $("#privacy_policy").text(data.data.privacy_policy);

                $("#terms_and_conditions").text(data.data.terms_and_conditions);

                $("#header_value").text(data.data.header_value);

                $("#footer_value").text(data.data.footer_value);

                // $("#account_number").val(data.data.account_number);

                // $("#bank_name").val(data.data.bank_name);

                // $("#bank_branch").val(data.data.bank_branch);

                // $("#ifsc_code").val(data.data.ifsc_code);

                // $("#account_type").val(data.data.account_type);

                // $("#old_bank_logo").val(data.data.bank_logo);

                // $("#edit_id").val(data.data.id);

                // $(".status").val(data.data.status).change();

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

          url: '{{ route('manageCompanyUpdate') }}',

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

    

    // function createNew() {

    //     $('#detailsModal').modal('show');

    //     $("#edit_details_form")[0].reset();

    //     $("#edit_id").val(0);

    //     $('#detailsModal').modal('show');

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








@endsection

