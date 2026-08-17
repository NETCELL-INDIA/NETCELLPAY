@extends('layouts.master')
@section('title') WhatsApp API @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') WhatsApp API @endslot
@endcomponent

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">WhatsApp API</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Use placeholders in Request URL: <code>{MOB}</code>, <code>{MSG}</code>, <code>{TMP_ID}</code>.
                    This same API is used for login OTP / user messages.
                </p>
                <form id="whatsappApiForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">API Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="whatsapp_api_method" id="whatsapp_api_method">
                                <option value="GET" @selected(($method ?? '') === 'GET')>GET</option>
                                <option value="POST" @selected(($method ?? '') === 'POST')>POST</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Request URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="whatsapp_request_url" id="whatsapp_request_url" value="{{ $url }}" placeholder="https://example.com/wa?mobile={MOB}&message={MSG}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" id="btnSaveApi">Save API</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Test Message</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="test_mobile" maxlength="12" placeholder="10-digit mobile">
                </div>
                <div class="mb-3">
                    <label class="form-label">Template ID</label>
                    <input type="text" class="form-control" id="test_tmp_id" placeholder="Optional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="test_message" rows="3" placeholder="Netcell Pay WhatsApp API test message"></textarea>
                </div>
                <button type="button" class="btn btn-success" id="btnTestSend">
                    <i class="ri-whatsapp-line"></i> Send Test
                </button>
                <pre class="mt-3 mb-0 p-2 bg-light border rounded d-none" id="testResult" style="white-space:pre-wrap;font-size:.78rem;max-height:180px;overflow:auto;"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
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

$('#btnSaveApi').on('click', function () {
    var btn = $(this);
    btn.prop('disabled', true).text('Saving...');
    $.ajax({
        url: '{{ route("whatsappApiSave") }}',
        method: 'post',
        data: $('#whatsappApiForm').serialize(),
        success: function (data) {
            btn.prop('disabled', false).text('Save API');
            notify(data.type === 'success' ? 'Success' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
        },
        error: function () {
            btn.prop('disabled', false).text('Save API');
            notify('Error', 'Unable to save WhatsApp API', 'error');
        }
    });
});

$('#btnTestSend').on('click', function () {
    var btn = $(this);
    var mobile = $('#test_mobile').val();
    if (!mobile) {
        notify('Error', 'Enter mobile number', 'error');
        return;
    }
    btn.prop('disabled', true).text('Sending...');
    $('#testResult').addClass('d-none').text('');
    $.ajax({
        url: '{{ route("whatsappApiTest") }}',
        method: 'post',
        data: {
            _token: '{{ csrf_token() }}',
            mobile: mobile,
            tmp_id: $('#test_tmp_id').val(),
            message: $('#test_message').val()
        },
        success: function (data) {
            btn.prop('disabled', false).html('<i class="ri-whatsapp-line"></i> Send Test');
            notify(data.type === 'success' ? 'Success' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
            if (data.response) {
                $('#testResult').removeClass('d-none').text('HTTP ' + (data.http_code || '-') + '\n' + data.response);
            }
        },
        error: function () {
            btn.prop('disabled', false).html('<i class="ri-whatsapp-line"></i> Send Test');
            notify('Error', 'Unable to send test message', 'error');
        }
    });
});
</script>
@endsection
