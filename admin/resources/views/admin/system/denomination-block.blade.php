@extends('layouts.master')
@section('title') {{ $title }} @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">{{ $title }}</h4></div>
    <div class="card-body">
        <p class="text-muted">Enter the amounts that must not be recharged, separated by commas. Example: 10,20,239. The wallet is not debited when an amount is blocked.</p>
        <form id="blockForm" class="row g-3">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            @if($mode === 'user')
            <div class="col-md-3">
                <label class="form-label">Retailer mobile</label>
                <input type="text" class="form-control" name="mobile_number" id="mobile_number" maxlength="10" inputmode="numeric" required>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Service</label>
                <select class="form-select" id="service_id">
                    <option value="">Select</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Operator</label>
                <select class="form-select" name="provider_id" id="provider_id" required>
                    <option value="">Select</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amounts</label>
                <input type="text" class="form-control" name="amounts" id="amounts" placeholder="10,20,239" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary" id="saveBtn" type="submit">Save</button>
            </div>
        </form>
        <div class="mt-4" id="blockList">Loading...</div>
    </div>
</div>
@endsection
@section('script')
<script>
function loadBlocks() {
    $.post('{{ route('denomBlockList') }}', { _token: '{{ csrf_token() }}', mode: '{{ $mode }}' }, function (html) {
        $('#blockList').html(html);
    });
}
loadBlocks();

$('#service_id').on('change', function () {
    $.post('{{ route('rechargeGetProvider') }}', { _token: '{{ csrf_token() }}', service_id: $(this).val() }, function (res) {
        var html = '<option value="">Select</option>';
        var rows = (res && res.data) ? res.data : [];
        rows.forEach(function (row) {
            html += '<option value="' + row.id + '">' + row.provider_name + '</option>';
        });
        $('#provider_id').html(html);
    });
});

$('#blockForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $('#saveBtn');
    btn.prop('disabled', true);
    $.post('{{ route('denomBlockSave') }}', $(this).serialize()).done(function (res) {
        alert(res.message || 'Saved');
        if (res.type === 'success') {
            $('#edit_id').val(0);
            $('#amounts').val('');
            loadBlocks();
        }
    }).fail(function () {
        alert('Could not save');
    }).always(function () {
        btn.prop('disabled', false);
    });
});

$(document).on('click', '.block-edit', function () {
    $('#edit_id').val($(this).data('id'));
    $('#amounts').val($(this).data('amounts'));
    $('#status').val(String($(this).data('status')));
    if ($('#mobile_number').length) {
        $('#mobile_number').val($(this).data('mobile'));
    }
    $('#provider_id').html('<option value="' + $(this).data('provider') + '" selected>' + $(this).data('operator') + '</option>');
    window.scrollTo(0, 0);
});

$(document).on('click', '.block-delete', function () {
    if (!confirm('Delete this block?')) return;
    $.post('{{ route('denomBlockDelete') }}', { _token: '{{ csrf_token() }}', mode: '{{ $mode }}', id: $(this).data('id') })
        .done(function (res) {
            alert(res.message || 'Deleted');
            loadBlocks();
        });
});
</script>
@endsection
