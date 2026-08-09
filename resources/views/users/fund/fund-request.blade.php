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
                <h4 class="card-title mb-0 flex-grow-1">Filter</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body">
                <form action="#">
                    <div class="row gy-3">
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
                        <div class="col-lg-3">
                            <label class="form-label mb-0">Type </label>
                            <select class="form-select mb-3" name="tbl_type"  id="tbl_type">
                                <option selected value="0">Current </option>
                                <option value="1">Backup</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <div>
                                <label class="form-label mb-0"></label>
                                <button type="button" id="search_btn" class="form-control btn btn-secondary bg-gradient waves-effect waves-light" onclick="fetchAllSearch()">Search</button>
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
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="FundRequest()">New Fund Request</button>
                    </div>
                </div>
            </div>    
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No records found</h4>
            </div>
        </div>
    </div>
</div>




<!-- Details Modals -->
<div id="detailsModal" class="modal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Fund Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="edit_details_form">
                    <input type="hidden" name="edit_id" id="edit_id">
                    @csrf
                    <div class="live-preview">
                        <div class="row gy-4">
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Bank Account: <a style="color: red">*</a></label>
                                    <select class="form-select mb-3 bank_id" aria-label="Default select example" name="bank_id" id="bank_id">   
                                    </select>
                                </div>
                            </div>

                            <div class="col-xxl-12 col-md-12" style="display: none;" id="bank_div">
                                <div>
                                    <div class="card">
                                    <div class="row">
                                        <div class="col-4">
                                            <img src="" class="card-img-top" alt="Bank Logo" style="justify-items: center;padding: 5px;" id="bank_logo">
                                        </div>
                                        <div class="col-8">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item" id="bank_name"></li>
                                            <li class="list-group-item" id="account_name"></li>
                                            <li class="list-group-item" id="account_number"></li>
                                            <li class="list-group-item" id="account_type"></li>
                                        
                                        </ul>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Transfer Mode: <a style="color: red">*</a></label>
                                    <select class="form-select mb-3 transfer_mode" aria-label="Default select example" name="transfer_mode" id="transfer_mode">   
                                    <option selected>Select Mode</option>
                                        <option value="IMPS">IMPS</option>
                                        <option value="NEFT">NEFT</option>
                                        <option value="RTGS">RTGS</option>
                                        <option value="UPI">UPI</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Payment Gateway">Payment Gateway</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Enter Amount: <a style="color: red">*</a></label>
                                    <input type="number" name="amount" class="form-control" required="">
                                </div>
                            </div>

                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Trasaction/Utr no.: <a style="color: red">*</a></label>
                                    <input type="text" name="transaction_number" class="form-control" required="">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-12 col-md-6">
                                <div>
                                    <label for="remark" class="form-label">Remark: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="remark" id="remark" required="required">
                                </div>
                            </div>

                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label class="form-label">Slip/Screenshot: <a style="color: red">(Optional)</a></label>
                                    <input type="file" name="slip_image" class="form-control" >
                                </div>
                            </div>
                            <!--end col-->
                            
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
    fetchAll(1,10);
    fatchBankList();
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

    function fetchAllSearch() {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        var tbl_type = $("#tbl_type").val();
        $("#search_btn").text('Loading...');
        $('#search_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('fundRequestList') }}',
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
        // var order_id = $("#order_id").val();
        // var fund_type = $("#fund_type").val();
        // var tr_type = $("#tr_type").val();
        var tbl_type = $("#tbl_type").val();
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('fundRequestList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',from_date,tbl_type,to_date,page,limit},
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

    function fatchBankList() {
        $("#preloader").attr("style", "display:block");
        $.ajax({
            url: '{{ route('fundRequestBankList') }}',
            method: 'post',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                console.log(res);
                localStorage.setItem('fund_banks', JSON.stringify(res.banks));

                $('#bank_id').empty();
                $('#bank_id').append('<option selected="">Select Bank Account</option>');
                $.each(res.banks, function(k, v) {
                    $('#bank_id').append('<option value="' + v.id + '">' + v.bank_name
                        .toUpperCase() + '</option>');
                });
                
                // //$('#state_id').empty();
                // $.each(res.state, function(k, v) {
                //     $('#state_id').append('<option value="' + v.id + '">' + v.state_name
                //         .toUpperCase() + '</option>');
                // });
                $("#preloader").hide();
            }
        });
    }

    $('#bank_id').on('change', function() {
        var id = $('#bank_id').val();
        url = '{{env('ADMIN_HOST')}}';
        const data = localStorage.getItem('fund_banks');
        var jsonobj = $.parseJSON(data);
        bank = getObjects(jsonobj,id);

        $("#bank_logo").attr("src", url+"/public/bank_logo/"+bank.bank_logo);
        $("#bank_name").text(bank.bank_name+" - "+bank.bank_branch);
        $("#account_name").text("A/c Name : "+bank.account_name);
        $("#account_number").text("A/c No. : "+bank.account_number);
        $("#account_type").text("Type : "+bank.account_type+", IFSC : "+bank.ifsc_code);
        $("#bank_div").show();
        //console.log(bank);
    });

    function getObjects(obj,val) {
        var newObj = false; 
        $.each(obj, function(){
            var testObject = this; 
            $.each(testObject, function(k,v){
                if(val == v ){
                    newObj = testObject;
                }
            });
        });
        return newObj;
    }

    $("#edit_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Loading...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('fundRequestSubmit') }}',
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

    function FundRequest() {
        $('#detailsModal').modal('show');
        $("#edit_details_form")[0].reset();
        $('#detailsModal').modal({backdrop: 'static', keyboard: false});
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
