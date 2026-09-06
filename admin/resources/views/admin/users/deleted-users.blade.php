@extends('layouts.master')
@section('title') User Delete @endsection

@section('content')
<div class="users-list-page">
@component('components.breadcrumb')
@slot('li_1') Users @endslot
@slot('title') User Delete @endslot
@endcomponent

<div class="card users-filter-card">
    <div class="card-header align-items-center d-flex py-2">
        <h4 class="card-title mb-0 flex-grow-1">Deleted Users</h4>
        <a href="{{ URL::asset('admin/users/list') }}" class="btn btn-sm btn-soft-primary">Back to List</a>
    </div>
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div style="min-width:220px;flex:1">
                <label class="form-label mb-1">Search</label>
                <input type="text" class="form-control form-control-sm" id="deletedKeyword" placeholder="ID / mobile / name / email">
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btnDeletedSearch">
                <i class="ri-search-line"></i> Search
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body py-2 px-3" id="deletedUsersBody">
        <p class="text-muted mb-0">Loading...</p>
    </div>
</div>
</div>
@endsection

@section('script')
<script>
var csrf = '{{ csrf_token() }}';
var currentPage = 1;
var currentLimit = 10;

function fetchDeleted(page, limit) {
    currentPage = page || 1;
    currentLimit = limit || currentLimit;
    $('#deletedUsersBody').html('<p class="text-muted mb-0">Loading...</p>');
    $.ajax({
        url: '{{ route("userlistDeletedList") }}',
        method: 'post',
        data: {
            _token: csrf,
            page: currentPage,
            limit: currentLimit,
            keyword: $('#deletedKeyword').val()
        },
        success: function (html) {
            $('#deletedUsersBody').html(html);
        },
        error: function () {
            $('#deletedUsersBody').html('<p class="text-danger mb-0">Failed to load</p>');
        }
    });
}

function tableSearch(page) {
    fetchDeleted(page, $('#page_limit').val() || currentLimit);
}

$(function () {
    fetchDeleted(1, 10);
    $('#btnDeletedSearch').on('click', function () { fetchDeleted(1, currentLimit); });
    $('#deletedKeyword').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            fetchDeleted(1, currentLimit);
        }
    });
    $(document).on('change', '#page_limit', function () {
        fetchDeleted(1, $(this).val());
    });
    $(document).on('click', '.restoreData', function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        Swal.fire({
            title: 'Restore this user?',
            text: 'User will appear again in Create / List Users.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '{{ route("userlistRestore") }}',
                method: 'post',
                data: { id: id, _token: csrf },
                success: function (data) {
                    if (data.type === 'success') {
                        Swal.fire('Restored', data.message, 'success');
                        fetchDeleted(currentPage, currentLimit);
                    } else {
                        Error_Msg('Error', data.message || 'Failed', 'error');
                    }
                },
                error: function () {
                    Error_Msg('Oops...', 'Something went wrong!', 'error');
                }
            });
        });
    });
});
</script>
@endsection
