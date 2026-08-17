@extends('layouts.master')
@section('title') Complaints @endsection
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
@slot('li_1') Support @endslot
@slot('title')Complaints @endslot
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
                        
                        <div class="col-lg-3">
                            <div>
                                <label class="form-label mb-0">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="" id="from_date" placeholder="All days">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div>
                                <label class="form-label mb-0">To Date </label>
                                <input type="date" class="form-control" name="to_date" value="" id="to_date" placeholder="All days">
                            </div>  
                        </div>
                        <div class="col-lg-2">
                            <div>
                                <label class="form-label mb-0">Request Id</label>
                                <input type="text" class="form-control" placeholder="Enter Request Id" name="request_id" value="" id="request_id">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Status </label>
                            <select class="form-select mb-3" name="status_type" id="status_type">
                                <option selected value="Pending" style="color:#b78103;font-weight:700">PENDING</option>
                                <option value="All">ALL</option>
                                <option value="Success" style="color:#157347;font-weight:700">SUCCESS</option>
                                <option value="Failure" style="color:#dc3545;font-weight:700">FAILURE</option>
                                <option value="Refunded" style="color:#0d6efd;font-weight:700">REFUNDED</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
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
                <h4 class="card-title mb-0 flex-grow-1">Complaint List</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body">
                <div class="text-muted mb-2" style="font-size:.8rem">Pending complaints (Open / Under Review) load for all days. Use dates only if you want to search history.</div>
                <div id="list_result">
                    <h4 class="text-center text-secondary my-3">No record found</h4>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Report Modals -->
<div id="reportModal" class="modal" tabindex="-1" aria-labelledby="reportModalLabel" data-bs-backdrop="static"
    data-bs-keyboard="false" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body" id="report_result">
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <!-- <button type="submit" class="btn btn-primary" id="complaint_now_btn" onclick="complaintSubmit()">Submit</button> -->
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@endsection
@section('script')
<script>
    fetchAll(1,10);
    function complaintPayload(page, limit) {
        return {
            from_date: $("#from_date").val() || '',
            to_date: $("#to_date").val() || '',
            request_id: $("#request_id").val() || '',
            status: $("#status_type").val() || 'Pending',
            page: page || 1,
            limit: limit || 10,
            tbl_type: 0,
            _token: '{{csrf_token()}}'
        };
    }
    function fetchAllSearch() {
        $("#search_btn").text('Please wait...');
        $('#search_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('ComplaintsList') }}',
            method: 'post',
            data: complaintPayload(1, 10),
            success: function(res) {
                $("#search_btn").text('Search Records');
                $('#search_btn').prop('disabled', false);
                $("#list_result").html(res);
            },
            error: function() {
                $("#search_btn").text('Search Records');
                $('#search_btn').prop('disabled', false);
            }
        });
    }

    function reportsView(id){
        //alert(id);
        $.ajax({
            url: '{{ route('ComplaintsGetReport') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}', id},
            success: function(res) {
                //console.log(res);
                $("#report_result").html(res);
                $('#reportModal').modal('show');
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
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('ComplaintsList') }}',
            method: 'post',
            data: complaintPayload(page, limit),
            success: function(res) {
                $("#list_result").html(res);
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



<!--jquery cdn-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!--select2 cdn-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ URL::asset('/assets/js/pages/select2.init.js') }}">

@endsection
