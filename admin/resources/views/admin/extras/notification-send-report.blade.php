@extends('layouts.master')

@section('title') Notification Send Report @endsection

@section('content')
<div class="sms-report-page">

@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') Notification Send Report @endslot
@endcomponent

<div class="card sms-filter-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">Search</h4>
    </div>
    <div class="card-body py-2 px-3">
        <div class="sms-filter-grid">
            <div class="sms-filter-field">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control form-control-sm" id="from_date">
            </div>
            <div class="sms-filter-field">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control form-control-sm" id="to_date">
            </div>
            <div class="sms-filter-field">
                <label class="form-label">Mobile / User</label>
                <input type="text" class="form-control form-control-sm" id="mobile" placeholder="Mobile or name">
            </div>
            <div class="sms-filter-field">
                <label class="form-label">Status</label>
                <select class="form-select form-select-sm" id="status">
                    <option value="all" selected>All</option>
                    <option value="1">Push sent</option>
                    <option value="2">No app token</option>
                    <option value="3">Failed</option>
                    <option value="0">Inbox saved</option>
                </select>
            </div>
            <div class="sms-filter-field sms-filter-field--btn">
                <button type="button" class="btn btn-primary btn-sm w-100" id="btnSearch">
                    <i class="ri-search-line"></i> Search
                </button>
            </div>
            <div class="sms-filter-field sms-filter-field--btn">
                <button type="button" class="btn btn-danger btn-sm w-100" id="btnDeleteFiltered">
                    Delete by date / user
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card sms-list-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">App Notification Logs</h4>
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
                        <th>Action</th>
                        <th>User</th>
                        <th>Mobile</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Result</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="notifyBody">
                    <tr><td colspan="8" class="text-center text-muted py-3">No notification records found</td></tr>
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

<div class="modal fade" id="editNotifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">This updates the in-app inbox text. An alert already shown on a phone cannot be changed, and this does not send it again.</p>
                <input type="hidden" id="editNotifyId">
                <div class="mb-3">
                    <label class="form-label" for="editNotifySubject">Title</label>
                    <input class="form-control" id="editNotifySubject" maxlength="120">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="editNotifyContent">Message</label>
                    <textarea class="form-control" id="editNotifyContent" maxlength="1000" rows="5"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveNotify">Save</button>
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

function fetchNotifyReport() {
    $('#notifyBody').html('<tr><td colspan="8" class="text-center text-muted py-3">Loading...</td></tr>');
    $.ajax({
        url: '{{ route('notificationSendReportList') }}',
        method: 'POST',
        dataType: 'json',
        data: {
            _token: csrf,
            show: $('#show').val(),
            page: currentPage,
            mobile: $('#mobile').val(),
            status: $('#status').val(),
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val()
        },
        success: function (res) {
            if (!res || res.type !== 'success') {
                $('#notifyBody').html('<tr><td colspan="8" class="text-center text-danger py-3">Failed to load</td></tr>');
                return;
            }
            $('#notifyBody').html(res.rows);
            var p = res.pagination || {};
            currentPage = p.page || 1;
            lastPage = p.last_page || 1;
            $('#pageInfo').text('Showing ' + (p.from || 0) + ' to ' + (p.to || 0) + ' of ' + (p.total || 0) + ' entries');
            $('#btnPrev').prop('disabled', currentPage <= 1);
            $('#btnNext').prop('disabled', currentPage >= lastPage);
        },
        error: function () {
            $('#notifyBody').html('<tr><td colspan="8" class="text-center text-danger py-3">Failed to load</td></tr>');
        }
    });
}

$(function () {
    $('#btnDeleteFiltered').on('click', function () {
        var fromDate = $('#from_date').val();
        var toDate = $('#to_date').val();
        var user = $.trim($('#mobile').val());
        if (!fromDate && !toDate && !user) {
            Swal.fire({ title: 'Choose a filter', text: 'Enter a date, a date range, or a user before deleting.', icon: 'warning' });
            return;
        }
        var scope = [];
        if (fromDate || toDate) {
            scope.push((fromDate || '...') + ' to ' + (toDate || '...'));
        }
        if (user) {
            scope.push('user "' + user + '"');
        }
        Swal.fire({
            title: 'Delete these notifications?',
            text: 'This deletes the app inbox notifications for ' + scope.join(' and ') + '. The campaign record stays. An alert already shown on a phone cannot be recalled.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger w-xs mt-2',
                cancelButton: 'btn btn-light w-xs mt-2 ms-2',
            },
            buttonsStyling: false
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '{{ route('notificationSendReportDeleteFiltered') }}',
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: csrf,
                    from_date: fromDate,
                    to_date: toDate,
                    mobile: user,
                    status: $('#status').val()
                },
                success: function (res) {
                    Swal.fire({
                        title: res && res.type === 'success' ? 'Deleted' : 'Error',
                        text: (res && res.message) || 'Done',
                        icon: res && res.type === 'success' ? 'success' : 'error'
                    });
                    if (res && res.type === 'success') {
                        currentPage = 1;
                        fetchNotifyReport();
                    }
                },
                error: function () {
                    Swal.fire({ title: 'Error', text: 'Unable to delete', icon: 'error' });
                }
            });
        });
    });
    $('#btnSearch').on('click', function () {
        currentPage = 1;
        fetchNotifyReport();
    });
    $('#mobile').on('keypress', function (e) {
        if (e.which === 13) {
            currentPage = 1;
            fetchNotifyReport();
        }
    });
    $('#show, #status').on('change', function () {
        currentPage = 1;
        fetchNotifyReport();
    });
    $('#btnPrev').on('click', function () {
        if (currentPage > 1) {
            currentPage--;
            fetchNotifyReport();
        }
    });
    $('#btnNext').on('click', function () {
        if (currentPage < lastPage) {
            currentPage++;
            fetchNotifyReport();
        }
    });
    $(document).on('click', '.btn-edit-notify', function () {
        var id = $(this).data('id');
        if (!id) {
            return;
        }
        $.ajax({
            url: '{{ route('notificationSendReportGet') }}',
            method: 'POST',
            dataType: 'json',
            data: { _token: csrf, id: id },
            success: function (res) {
                if (!res || res.type !== 'success') {
                    Swal.fire({ title: 'Error', text: (res && res.message) || 'Unable to open this notification', icon: 'error' });
                    return;
                }
                $('#editNotifyId').val(res.id);
                $('#editNotifySubject').val(res.subject || '');
                $('#editNotifyContent').val(res.content || '');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editNotifyModal')).show();
            },
            error: function () {
                Swal.fire({ title: 'Error', text: 'Unable to open this notification', icon: 'error' });
            }
        });
    });
    $('#btnSaveNotify').on('click', function () {
        $.ajax({
            url: '{{ route('notificationSendReportUpdate') }}',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: csrf,
                id: $('#editNotifyId').val(),
                subject: $('#editNotifySubject').val(),
                content: $('#editNotifyContent').val()
            },
            success: function (res) {
                if (!res || res.type !== 'success') {
                    Swal.fire({ title: 'Error', text: (res && res.message) || 'Unable to save', icon: 'error' });
                    return;
                }
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editNotifyModal')).hide();
                Swal.fire({ title: 'Saved', text: res.message || 'Updated', icon: 'success' });
                fetchNotifyReport();
            },
            error: function () {
                Swal.fire({ title: 'Error', text: 'Unable to save', icon: 'error' });
            }
        });
    });
    $(document).on('click', '.btn-del-notify', function () {
        var id = $(this).data('id');
        var one = String($(this).data('scope')) === 'one';
        if (!id) {
            return;
        }
        Swal.fire({
            title: 'Delete this notification?',
            text: one
                ? 'This removes this inbox copy only. Other recipients and the campaign record stay. An alert already shown on a phone cannot be recalled.'
                : 'This removes the same wrong APP NOTIFICATION for all users in that send.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger w-xs mt-2',
                cancelButton: 'btn btn-light w-xs mt-2 ms-2',
            },
            buttonsStyling: false
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '{{ route('notificationSendReportDelete') }}',
                method: 'POST',
                dataType: 'json',
                data: { _token: csrf, id: id },
                success: function (res) {
                    Swal.fire({
                        title: res.type === 'success' ? 'Deleted' : 'Error',
                        text: res.message || 'Done',
                        icon: res.type === 'success' ? 'success' : 'error',
                        customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
                        buttonsStyling: false
                    });
                    if (res.type === 'success') {
                        fetchNotifyReport();
                    }
                },
                error: function () {
                    Swal.fire({
                        title: 'Error',
                        text: 'Unable to delete',
                        icon: 'error',
                        customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
                        buttonsStyling: false
                    });
                }
            });
        });
    });

    fetchNotifyReport();
});
</script>
@endsection
