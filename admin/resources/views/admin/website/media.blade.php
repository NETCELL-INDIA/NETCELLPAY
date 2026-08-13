@extends('layouts.master')
@section('title') {{ $title }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Website @endslot
@slot('title'){{ $title }}@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">{{ $title }}</h4>
                <button type="button" class="btn btn-info" onclick="createNew()">Create New</button>
            </div>
            <div class="card-body" id="list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="modal" tabindex="-1" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="edit_details_form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                <input type="hidden" name="kind" value="{{ $kind }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Create {{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image / Photo</label>
                            <input type="file" class="form-control" name="image" id="image" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link URL</label>
                            <input type="text" class="form-control" name="link_url" id="link_url" placeholder="https://">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sort</label>
                            <input type="number" class="form-control" name="sort_order" id="sort_order" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message / Caption</label>
                            <textarea class="form-control" name="body" id="body" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="edit_details_btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
fetchAll();
function Error_Msg(title,text,icon) {
    Swal.fire({ title: title, text: text, icon: icon });
}
function fetchAll() {
    $.post('{{ route('websiteMediaList') }}', { kind: '{{ $kind }}', _token: '{{ csrf_token() }}' }, function (res) {
        $('#list_result').html(res);
    });
}
function createNew() {
    $('#edit_details_form')[0].reset();
    $('#edit_id').val(0);
    $('#detailsModalLabel').text('Create {{ $title }}');
    $('#detailsModal').modal('show');
}
$(document).on('click', '.editData', function () {
    $.post('{{ route('websiteMediaGet') }}', { id: this.id, _token: '{{ csrf_token() }}' }, function (res) {
        if (res.type !== 'success') return Error_Msg('Error', res.message, 'error');
        $('#edit_id').val(res.data.id);
        $('#title').val(res.data.title);
        $('#link_url').val(res.data.link_url);
        $('#body').val(res.data.body);
        $('#sort_order').val(res.data.sort_order);
        $('#status').val(String(res.data.status));
        $('#detailsModalLabel').text('Edit {{ $title }}');
        $('#detailsModal').modal('show');
    });
});
$(document).on('click', '.deleteData', function () {
    var id = this.id;
    Swal.fire({ title: 'Delete this item?', icon: 'warning', showCancelButton: true }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('{{ route('websiteMediaDelete') }}', { id: id, _token: '{{ csrf_token() }}' }, function (data) {
            Error_Msg(data.type, data.message, data.type);
            fetchAll();
        });
    });
});
$('#edit_details_form').on('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    $('#edit_details_btn').prop('disabled', true).text('Saving...');
    $.ajax({
        url: '{{ route('websiteMediaSave') }}',
        method: 'post',
        data: fd,
        processData: false,
        contentType: false,
        success: function (data) {
            $('#edit_details_btn').prop('disabled', false).text('Save');
            Error_Msg(data.type, data.message, data.type);
            if (data.type === 'success') {
                $('#detailsModal').modal('hide');
                fetchAll();
            }
        },
        error: function () {
            $('#edit_details_btn').prop('disabled', false).text('Save');
            Error_Msg('error', 'Unable to save.', 'error');
        }
    });
});
</script>
@endsection
