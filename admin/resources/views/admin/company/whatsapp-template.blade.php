@extends('layouts.master')
@section('title') WhatsApp Template List @endsection
@section('css')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') WhatsApp Template List @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">WhatsApp Template List</h4>
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">Create New</button>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.85rem">
                    Placeholders: <code>{NAME}</code> <code>{OTP}</code> <code>{MOBILE}</code> <code>{PASSWORD}</code> <code>{PIN}</code>
                    <code>{LOGO}</code> / <code>{LOGO_URL}</code>.
                    Enable <strong>Company logo</strong> and/or upload a <strong>manual image</strong> per template (each can be On/Off).
                    Placeholders: <code>{LOGO}</code> company logo, <code>{IMG}</code> template image.
                </p>
                <div id="list_result">
                    <h4 class="text-center text-secondary my-3">Loading...</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="modal" tabindex="-1" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Create Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id" value="0">
                    <div class="mb-3">
                        <label>Category <span class="text-danger">*</span></label>
                        <select class="form-control" name="slug" id="slug" required>
                            <option value="">Select Category</option>
                            @forelse($categories as $item)
                                <option value="{{ $item->slug }}">{{ $item->category_name }}</option>
                            @empty
                                <option value="otp">OTP</option>
                                <option value="create_user">Create User</option>
                                <option value="fund_receive">Fund Receive</option>
                                <option value="fund_reverse">Fund Reverse</option>
                                <option value="forgot_password">Forgot Password</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Template ID <span class="text-danger">*</span></label>
                        <input type="text" name="template_id" id="template_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="msg_content" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="attach_logo" id="attach_logo" value="1">
                        <label class="form-check-label" for="attach_logo">Send with company logo</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="attach_image" id="attach_image" value="1">
                        <label class="form-check-label" for="attach_image">Send with template image</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template image (manual)</label>
                        <input type="file" class="form-control" name="image" id="image" accept="image/png,image/jpeg,image/webp,image/gif">
                        <div class="mt-2 d-none" id="imagePreviewWrap">
                            <img src="" alt="" id="imagePreview" style="max-height:72px;max-width:160px;object-fit:contain;border:1px solid #e9ebec;border-radius:8px;background:#fff;padding:4px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">Remove image</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select class="form-select status" name="status" id="status">
                            <option value="1">Active</option>
                            <option value="0">Deactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="edit_details_btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="sendModal" class="modal" tabindex="-1" aria-hidden="true" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="send_id">
                <div class="mb-3">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="send_mobile" maxlength="12" placeholder="10-digit mobile">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="send_attach_logo" value="1">
                    <label class="form-check-label" for="send_attach_logo">Send with company logo</label>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="send_attach_image" value="1">
                    <label class="form-check-label" for="send_attach_image">Send with template image</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="btnDoSend"><i class="ri-whatsapp-line"></i> Send</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
function notify(title, text, icon) {
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
        buttonsStyling: false,
        showCloseButton: true
    });
}

function fetchAll() {
    $.ajax({
        url: '{{ route("whatsappTemplateList") }}',
        method: 'post',
        data: { _token: '{{ csrf_token() }}' },
        success: function (res) {
            $('#list_result').html(res);
            if ($('#scroll-vertical').length) {
                new DataTable('#scroll-vertical', { paging: true, pageLength: 10, order: [] });
            }
        }
    });
}

$('#btnCreate').on('click', function () {
    $('#edit_details_form')[0].reset();
    $('#edit_id').val(0);
    $('#attach_logo').prop('checked', false);
    $('#attach_image').prop('checked', false);
    $('#remove_image').prop('checked', false);
    $('#imagePreview').attr('src', '');
    $('#imagePreviewWrap').addClass('d-none');
    $('#detailsModalLabel').text('Create Template');
    $('#detailsModal').modal('show');
});

$(document).on('click', '.editDetails', function (e) {
    e.preventDefault();
    $.post('{{ route("whatsappTemplateGet") }}', { id: this.id, _token: '{{ csrf_token() }}' }, function (data) {
        if (data.type !== 'success') {
            notify('Error', data.message, 'error');
            return;
        }
        $('#slug').val(data.data.slug);
        $('#template_id').val(data.data.template_id);
        $('#msg_content').val(data.data.content);
        $('#edit_id').val(data.data.id);
        $('#status').val(String(data.data.status));
        $('#attach_logo').prop('checked', Number(data.data.attach_logo) === 1);
        $('#attach_image').prop('checked', Number(data.data.attach_image) === 1);
        $('#remove_image').prop('checked', false);
        $('#image').val('');
        if (data.data.image_url) {
            $('#imagePreview').attr('src', data.data.image_url);
            $('#imagePreviewWrap').removeClass('d-none');
        } else {
            $('#imagePreview').attr('src', '');
            $('#imagePreviewWrap').addClass('d-none');
        }
        $('#detailsModalLabel').text('Edit Template');
        $('#detailsModal').modal('show');
    });
});

$(document).on('click', '.deleteData', function (e) {
    e.preventDefault();
    var id = this.id;
    Swal.fire({
        title: 'Delete this template?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.post('{{ route("whatsappTemplateDelete") }}', { id: id, _token: '{{ csrf_token() }}' }, function (data) {
            notify(data.type === 'success' ? 'Deleted' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
            if (data.type === 'success') fetchAll();
        });
    });
});

$(document).on('click', '.sendTemplate', function (e) {
    e.preventDefault();
    var id = this.id;
    $.post('{{ route("whatsappTemplateGet") }}', { id: id, _token: '{{ csrf_token() }}' }, function (data) {
        if (data.type !== 'success') {
            notify('Error', data.message, 'error');
            return;
        }
        $('#send_id').val(id);
        $('#send_mobile').val('');
        $('#send_attach_logo').prop('checked', Number(data.data.attach_logo) === 1);
        $('#send_attach_image').prop('checked', Number(data.data.attach_image) === 1);
        $('#sendModal').modal('show');
    });
});

$('#btnDoSend').on('click', function () {
    var btn = $(this);
    var mobile = $('#send_mobile').val();
    if (!mobile) {
        notify('Error', 'Enter mobile number', 'error');
        return;
    }
    btn.prop('disabled', true).text('Sending...');
    $.post('{{ route("whatsappTemplateSend") }}', {
        _token: '{{ csrf_token() }}',
        id: $('#send_id').val(),
        mobile: mobile,
        attach_logo: $('#send_attach_logo').is(':checked') ? 1 : 0,
        attach_image: $('#send_attach_image').is(':checked') ? 1 : 0
    }, function (data) {
        btn.prop('disabled', false).html('<i class="ri-whatsapp-line"></i> Send');
        notify(data.type === 'success' ? 'Sent' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
        if (data.type === 'success') $('#sendModal').modal('hide');
    }).fail(function () {
        btn.prop('disabled', false).html('<i class="ri-whatsapp-line"></i> Send');
        notify('Error', 'Unable to send', 'error');
    });
});

$('#edit_details_form').on('submit', function (e) {
    e.preventDefault();
    var btn = $('#edit_details_btn');
    btn.prop('disabled', true).text('Please wait...');
    var fd = new FormData(this);
    if (!$('#attach_logo').is(':checked')) {
        fd.set('attach_logo', '0');
    }
    if (!$('#attach_image').is(':checked')) {
        fd.set('attach_image', '0');
    }
    $.ajax({
        url: '{{ route("whatsappTemplateUpdate") }}',
        method: 'post',
        data: fd,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (data) {
            btn.prop('disabled', false).text('Save Changes');
            notify(data.type === 'success' ? 'Saved' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
            if (data.type === 'success') {
                $('#detailsModal').modal('hide');
                fetchAll();
            }
        },
        error: function () {
            btn.prop('disabled', false).text('Save Changes');
            notify('Error', 'Unable to save', 'error');
        }
    });
});

fetchAll();

$('#image').on('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    $('#remove_image').prop('checked', false);
    $('#imagePreview').attr('src', URL.createObjectURL(file));
    $('#imagePreviewWrap').removeClass('d-none');
});
</script>
@endsection
