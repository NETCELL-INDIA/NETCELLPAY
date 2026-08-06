@extends('layouts.master')

@section('title') Send SMS Report @endsection

@section('content')
<div class="sms-report-page">

@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') Send SMS Report @endslot
@endcomponent

<div class="card sms-filter-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">Search</h4>
    </div>
    <div class="card-body py-2 px-3">
        <div class="sms-filter-grid">
            <div class="sms-filter-field">
                <label class="form-label">Mobile No.</label>
                <input type="text" class="form-control form-control-sm" id="mobile" maxlength="10" placeholder="Enter mobile no.">
            </div>
            <div class="sms-filter-field sms-filter-field--btn">
                <button type="button" class="btn btn-primary btn-sm w-100" id="btnSearch">
                    <i class="ri-search-line"></i> Search
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card sms-list-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">SMS Logs</h4>
    </div>
    <div class="card-body py-2 px-3">
        <div class="sms-list-toolbar">
            <div class="sms-list-toolbar__left">
                <label class="sms-list-toolbar__label">Show</label>
                <select class="form-select form-select-sm" id="show">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="sms-list-toolbar__label">entries</span>
            </div>
        </div>

        <div class="table-responsive sms-list-table-wrap">
            <table class="table table-sm table-hover sms-list-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mobile</th>
                        <th>API</th>
                        <th>Message</th>
                        <th>Result</th>
                        <th>Date</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody id="smsBody">
                    <tr><td colspan="7" class="text-center text-muted py-3">No SMS records found</td></tr>
                </tbody>
            </table>
        </div>

        <div class="sms-list-footer">
            <span id="pageInfo" class="sms-list-count">Showing 0 to 0 of 0 entries</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="btnPrev">Prev</button>
                <button type="button" class="btn btn-outline-secondary" id="btnNext">Next</button>
            </div>
        </div>
    </div>
</div>

</div>

<div class="modal fade" id="smsResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">SMS API Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2">
                <pre id="smsResultBody" class="sms-result-pre mb-0"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
var csrf = '{{ csrf_token() }}';
var currentPage = 1;
var lastPage = 1;

function fetchSmsReport() {
    $('#smsBody').html('<tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>');
    $.ajax({
        url: '{{ route('sendSmsReportList') }}',
        method: 'POST',
        dataType: 'json',
        data: {
            _token: csrf,
            show: $('#show').val(),
            page: currentPage,
            mobile: $('#mobile').val()
        },
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#smsBody').html('<tr><td colspan="7" class="text-center text-danger py-3">Failed to load</td></tr>');
                return;
            }
            $('#smsBody').html(res.rows);
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#smsBody').html('<tr><td colspan="7" class="text-center text-danger py-3">Failed to load</td></tr>');
        }
    });
}

$(function () {
    $('#btnSearch').on('click', function () {
        currentPage = 1;
        fetchSmsReport();
    });

    $('#mobile').on('keypress', function (e) {
        if (e.which === 13) {
            currentPage = 1;
            fetchSmsReport();
        }
    });

    $('#show').on('change', function () {
        currentPage = 1;
        fetchSmsReport();
    });

    $('#btnPrev').on('click', function () {
        if (currentPage > 1) {
            currentPage--;
            fetchSmsReport();
        }
    });

    $('#btnNext').on('click', function () {
        if (currentPage < lastPage) {
            currentPage++;
            fetchSmsReport();
        }
    });

    $(document).on('click', '.btn-view-sms-result', function () {
        $('#smsResultBody').text($(this).data('result') || '');
        $('#smsResultModal').modal('show');
    });

    fetchSmsReport();
});
</script>
@endsection
