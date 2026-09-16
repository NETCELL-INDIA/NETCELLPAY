@extends('layouts.master')
@section('title') Api Log Reports @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
<style>
.api-log-table td.api-log-clip{
    max-width:280px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    font-size:.82rem;
    color:#495057;
}
.api-log-pre{
    white-space:pre-wrap;
    word-break:break-word;
    max-height:260px;
    overflow:auto;
    background:#f8f9fa;
    border:1px solid #e9ebec;
    border-radius:.35rem;
    padding:.75rem;
    font-size:.8rem;
    margin:0;
}
.api-log-meta{font-size:.85rem;color:#878a99}
</style>
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Admin Reports @endslot
@slot('title') Api Log Reports @endslot
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
                            <div>
                                <label class="form-label mb-0">Order Id</label>
                                <input type="text" class="form-control" placeholder="Enter Order Id" name="order_id" value="" id="order_id">
                            </div>
                        </div>
                        <div class="col-lg-3">
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
                <h4 class="card-title mb-0 flex-grow-1">Api Log Reports List</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>




<!-- API Log Detail Modal -->
<div id="apiLogDetailModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">API Log Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="api-log-meta mb-3" id="apiLogMeta"></div>
                <h6 class="mb-1">Request URL</h6>
                <pre class="api-log-pre" id="apiLogUrl"></pre>
                <h6 class="mb-1 mt-3">Header</h6>
                <pre class="api-log-pre" id="apiLogHeader"></pre>
                <h6 class="mb-1 mt-3">Request</h6>
                <pre class="api-log-pre" id="apiLogRequest"></pre>
                <h6 class="mb-1 mt-3">Response</h6>
                <pre class="api-log-pre" id="apiLogResponse"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
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

    function prettyLog(raw) {
        raw = raw == null ? '' : String(raw);
        if (!raw) return '—';
        try { return JSON.stringify(JSON.parse(raw), null, 2); } catch (e) { return raw; }
    }

    function decodePayload(b64) {
        try {
            return JSON.parse(decodeURIComponent(escape(atob(b64))));
        } catch (e) {
            try { return JSON.parse(atob(b64)); } catch (e2) { return null; }
        }
    }

    $(document).on('click', '.btn-view-api-log', function () {
        var data = decodePayload($(this).attr('data-payload'));
        if (!data) {
            Error_Msg('Error', 'Unable to open log detail', 'error');
            return;
        }
        $('#apiLogMeta').text(
            (data.txnid || '-') + '  ·  ' + (data.modal || '-') + '  ·  ' + (data.created_at || '-')
        );
        $('#apiLogUrl').text(data.url || '—');
        $('#apiLogHeader').text(prettyLog(data.header));
        $('#apiLogRequest').text(prettyLog(data.request));
        $('#apiLogResponse').text(prettyLog(data.response));
        $('#apiLogDetailModal').modal('show');
    });

    function fetchAllSearch(page, limit) {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        var order_id = $("#order_id").val();
        
        $("#search_btn").text('Please wait...');
        $('#search_btn').prop('disabled', true);
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('apiLogReportsList') }}',
            method: 'post',
            data: {
                order_id,
                from_date : from_date,
                to_date : to_date,
                _token: '{{csrf_token()}}',
                page,
                limit,
            },
            success: function(res) {
                $("#search_btn").text('Search Records');
                $('#search_btn').prop('disabled', false);
                $("#list_result").html(res);
            },
            error: function () {
                $("#search_btn").text('Search Records');
                $('#search_btn').prop('disabled', false);
                $("#list_result").html('<h4 class="text-center text-danger my-3">Failed to load</h4>');
            }
        });
    }
</script>
@endsection
