@extends('layouts.master')
@section('title') User List @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />

@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Users @endslot
@slot('title')User List @endslot
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

                        <div class="col-lg-3">
                            <input type="hidden" name="id_value" id="id_value" value="0">
                            <div>
                                <label class="form-label mb-0">User Search</label>
                                <input type="text" class="form-control" name="user_id" value="" id="user_id">
                                <div id="user_list">
                                    
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div>
                                <label class="form-label mb-0">From Wallet </label>
                                <input type="number" class="form-control" placeholder="Minimum Wallet Balance" name="min_wallet" value="0" id="min_wallet">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div>
                                <label class="form-label mb-0">To Wallet </label>
                                <input type="number" class="form-control" placeholder="Maximum Wallet Balance " name="max_wallet" value="0"id="max_wallet">
                            </div>  
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Status </label>
                            <select class="form-select mb-3" name="status_name"  id="status_name">
                                <option selected value="All">All</option>
                                <option value="1">Active</option>
                                <option value="0">Deactive</option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label mb-0">Kyc Status</label>
                            <select class="form-select mb-3" name="status_kyc"  id="status_kyc">
                                <option selected value="All">All</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Pending">Pending</option>
                                <option value="Under Process">Under Process</option>
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
                <h4 class="card-title mb-0 flex-grow-1">User List</h4>
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

<!-- Fund Modals -->
<div id="fundModal" class="modal" tabindex="-1" aria-labelledby="fundModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >Fund Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="fund_details_form">
                    @csrf
                    <input type="hidden" name="id" id="id">
                    <div class="live-preview">
                        <div class="row gy-4">
                            <div class="col-xxl-6 col-md-12">
                                <div>
                                    <label class="col-form-label">Type: <a style="color: red">*</a></label>
                                    <select class="form-select mb-3 type" aria-label="Default select example" name="type" required="required">
                                        <option selected="">Select Type</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6 col-md-12">
                                <div>
                                    <label for="amount" class="col-form-label">Amount: <a style="color: red">*</a></label>
                                    <input type="number" class="form-control" name="amount" id="amount" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="bank_name" class="form-label">Remark: <a style="color: red">*</a></label>
                                    <textarea class="form-control" name="remark" id="remark" required="required"></textarea>
                                </div>
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="fund_details_btn">Save Changes</button>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- Details Modals -->
<div id="detailsModal" class="modal bs-example-modal-lg" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;">
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
                    <input type="hidden" name="old_profile_pic" id="old_profile_pic">
                    <div class="live-preview">
                        <div class="row gy-4">
                            

                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="outlet_name" class="form-label">Outlet Name: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="outlet_name" id="outlet_name" required="required">
                                </div>
                            </div>
                            <div id="user_list_p" style="display:none">
                                        
                                </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="first_name" class="form-label">First Name: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="first_name" id="first_name" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="middle_name" class="form-label">Middle Name: </label>
                                    <input type="text" class="form-control" name="middle_name" id="middle_name" >
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="last_name" class="form-label">Last Name: </label>
                                    <input type="text" class="form-control" name="last_name" id="last_name">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="date_of_birth" class="form-label">Date Of Birth: <a style="color: red">*</a></label>
                                    <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="email_address" class="form-label">Email Address: <a style="color: red">*</a></label>
                                    <input type="email" class="form-control" name="email_address" id="email_address" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="mobile_number" class="form-label">Mobile Number: <a style="color: red">*</a></label>
                                    <input type="number" class="form-control" name="mobile_number" id="mobile_number" required="required">
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label class="form-label">Gender:</label>
                                    <select class="form-select mb-3 gender" aria-label="Default select example" name="gender">
                                        <option selected="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="flat_door_no" class="form-label">Flat Door No: </label>
                                    <input type="text" class="form-control" name="flat_door_no" id="flat_door_no">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="road_street" class="form-label">Road Street: </label>
                                    <input type="text" class="form-control" name="road_street" id="road_street">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="area_locality" class="form-label">Area Locality: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="area_locality" id="area_locality" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="city" class="form-label">City: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="city" id="city" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="state" class="form-label">State: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="state" id="state" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="district" class="form-label">District: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="district" id="district" required="required">
                                </div>
                            </div>

                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="bank_account_number" class="form-label">Bank Account Number: <a style="color: red">*</a></label>
                                    <input type="number" class="form-control" name="bank_account_number" id="bank_account_number" value="0" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-3 col-md-6">
                                <div>
                                    <label for="branch_name" class="form-label">Bank Branch: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label for="ifsc_code" class="form-label">IFSC Code: <a style="color: red">*</a></label>
                                    <input type="text" class="form-control" name="ifsc_code" id="ifsc_code" required="required">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label class="form-label">Account Type: <a style="color: red">*</a></label>
                                    <select class="form-select mb-3 bank_account_type" aria-label="Default select example" name="bank_account_type">
                                        <option selected="">Select Type</option>
                                        <option value="Savings">Savings</option>
                                        <option value="Current">Current</option>
                                        <option value="NRI">NRI</option>
                                        <option value="Salary">Salary</option>
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label class="form-label">Status:</label>
                                    <select class="form-select mb-3 status" aria-label="Default select example" name="status">
                                        <option selected="">Select Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Deactive</option>
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
    fetchAll(1,10);
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
        var user_id = $("#id_value").val();
        var min_wallet = $("#min_wallet").val();
        var max_wallet = $("#max_wallet").val();
        var status = $("#status_name").val();
        var kyc_status = $("#status_kyc").val();

        $("#search_btn").text('Wait...');
        $('#search_btn').prop('disabled', true);
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('userlistList') }}',
            method: 'post',
            data: {
                _token: '{{csrf_token()}}',
                page,
                limit,
                user_id,
                min_wallet,
                max_wallet,
                status,
                kyc_status
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

    $('#user_id').on('keyup', function(){
        $('#id_value').val("0");
        search();
    });

    function search(){
        var keyword = $('#user_id').val();
        $.ajax({
            url: '{{ route('userListSearchUser') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',keyword:keyword},
            success: function(res) {
                $('#user_list').show();
                console.log(res);
                htmlView = "";
                for(let i = 0; i < res.users.length; i++){
                    htmlView += '<a onclick="selectValue(`'+res.users[i].id+'`,`'+res.users[i].outlet_name+' | '+res.users[i].first_name+' '+res.users[i].middle_name+' '+res.users[i].last_name+' | '+res.users[i].mobile_number+'`)">'+res.users[i].outlet_name+' | '+res.users[i].first_name+' '+res.users[i].middle_name+' '+res.users[i].last_name+' | '+res.users[i].mobile_number+'</a></br></hr>';
                }
                $('#user_list').html(htmlView);
            }
        });
    }


    function selectValue(id,full_text) {
        $('#id_value').val(id);
        $('#user_id').val(full_text);
        $('#user_list').hide();
    }

    


    $(document).on('click', '.fundTransfer', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $("#fund_details_form")[0].reset();
        $("#id").val(id);
        $('#fundModal').modal({backdrop: 'static', keyboard: false});
        $('#fundModal').modal('show');
        //alert(id);
    });


    $("#fund_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#fund_details_btn").text('Please wait...');
        $('#fund_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('fundUpdate') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#fund_details_btn").text('Save Changes');
                $('#fund_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated",data.message,"success");
                fetchAll(1,10);
                $("#fund_details_btn").text('Save Changes');
                $('#fund_details_btn').prop('disabled', false);
                $("#edit_details_form")[0].reset();
                $("#fundModal").modal('hide');
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#fund_details_btn").text('Save Changes');
                $('#fund_details_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#fund_details_btn").text('Save Changes');
            $('#fund_details_btn').prop('disabled', false);
         }
        });
    }); 



    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
          url: '{{ route('userlistGet') }}',
          method: 'post',
          data: {
            id: id,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            console.log(data);
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                /////
                $("#parent_id").val(data.data.parent_id);
                $('#parent_id_value').val(data.data.outlet_name+" | "+data.data.first_name+" | "+data.data.middle_name+" | "+data.data.last_name+" | "+data.data.mobile_number+" | ");
                $(".role_id").val(data.data.role_id).change();
                $(".scheme_id").val(data.data.scheme_id).change();
                $("#outlet_name").val(data.data.outlet_name);
                $("#first_name").val(data.data.first_name);
                $("#middle_name").val(data.data.middle_name);
                $("#last_name").val(data.data.last_name);
                $("#date_of_birth").val(data.data.date_of_birth);
                $("#email_address").val(data.data.email_address);
                $("#mobile_number").val(data.data.mobile_number);
                $(".login_type").val(data.data.login_type).change();
                $(".gender").val(data.data.gender).change();
                $("#flat_door_no").val(data.data.flat_door_no);
                $("#road_street").val(data.data.road_street);
                $("#area_locality").val(data.data.area_locality);
                $("#city").val(data.data.city);
                $("#state").val(data.data.state);
                $("#district").val(data.data.district);
                $("#minium_balance").val(data.data.minium_balance);
                $(".kyc_status").val(data.data.kyc_status).change();
                $("#bank_account_number").val(data.data.bank_account_number);
                $("#branch_name").val(data.data.branch_name);
                $("#ifsc_code").val(data.data.ifsc_code);
                $(".bank_account_type").val(data.data.bank_account_type).change();
                $("#ip_address").val(data.data.ip_address);
                $("#callback_url").val(data.data.callback_url);
                ////
                $("#old_profile_pic").val(data.data.profile_pic);
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

    $("#edit_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('userlistUpdate') }}',
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
                Error_Msg("Updated",data.message,"success");
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



    
    
    function createNew() {
        $('#detailsModal').modal('show');
        $("#edit_details_form")[0].reset();
        $("#edit_id").val(0);
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
<script src="{{ URL::asset('assets/libs/prismjs/prism.js') }}"></script>

<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

@endsection
