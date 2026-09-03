@extends('layouts.master')
@section('title') Company Logos @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Website @endslot
@slot('title') Company Logos @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title mb-0 flex-grow-1">Which logo goes where</h4>
                <a href="{{ URL::asset('admin/company/manage-company') }}" class="btn btn-sm btn-soft-secondary">Company Profile</a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Use a <strong>wide wordmark</strong> for headers/login, a <strong>square round icon</strong> for favicon and WhatsApp, and a separate file for invoices. PNG with transparent background is best.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach($slots as $slot)
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $slot['title'] }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-3" style="min-height:120px;background:#f3f6f9;border:1px dashed #dbe0e4;border-radius:10px;padding:12px;">
                        @if($slot['url'])
                            <img src="{{ $slot['url'] }}" alt="" id="preview_{{ $slot['key'] }}"
                                style="{{ $slot['shape'] === 'square' ? 'width:96px;height:96px;' : 'max-width:100%;height:72px;' }}object-fit:contain;background:transparent;">
                        @else
                            <span class="text-muted" id="preview_empty_{{ $slot['key'] }}">No image</span>
                            <img src="" alt="" id="preview_{{ $slot['key'] }}" class="d-none"
                                style="{{ $slot['shape'] === 'square' ? 'width:96px;height:96px;' : 'max-width:100%;height:72px;' }}object-fit:contain;">
                        @endif
                    </div>
                    <p class="mb-2"><span class="badge text-bg-light">Current file size</span> <strong>{{ $slot['pixels'] }}</strong></p>
                    <p class="mb-1"><strong>Used for</strong></p>
                    <ul class="text-muted small ps-3 mb-3">
                        @foreach($slot['used_for'] as $use)
                            <li>{{ $use }}</li>
                        @endforeach
                    </ul>
                    <p class="mb-1"><strong>On screen</strong></p>
                    <p class="text-muted small">{{ $slot['display'] }}</p>
                    <p class="mb-1"><strong>Upload size</strong></p>
                    <p class="text-muted small mb-1">{{ $slot['upload'] }}</p>
                    <p class="small mb-3"><strong>Ratio:</strong> {{ $slot['ratio'] }}</p>
                    <form class="logo-upload-form" data-slot="{{ $slot['key'] }}">
                        @csrf
                        <input type="hidden" name="slot" value="{{ $slot['key'] }}">
                        <input type="file" class="form-control mb-2" name="image" accept="image/png,image/jpeg,image/webp" required>
                        <button type="submit" class="btn btn-primary w-100">Save this logo</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('script')
<script src="{{ admin_asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
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
$('.logo-upload-form').on('submit', function (e) {
    e.preventDefault();
    var form = this;
    var btn = $(form).find('button[type="submit"]');
    var fd = new FormData(form);
    btn.prop('disabled', true).text('Saving...');
    $.ajax({
        url: '{{ route("companyLogosUpdate") }}',
        method: 'post',
        data: fd,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (data) {
            btn.prop('disabled', false).text('Save this logo');
            notify(data.type === 'success' ? 'Saved' : 'Error', data.message, data.type === 'success' ? 'success' : 'error');
            if (data.type === 'success' && data.url) {
                var slot = $(form).data('slot');
                $('#preview_' + slot).attr('src', data.url).removeClass('d-none');
                $('#preview_empty_' + slot).addClass('d-none');
                setTimeout(function () { location.reload(); }, 800);
            }
        },
        error: function () {
            btn.prop('disabled', false).text('Save this logo');
            notify('Error', 'Unable to save', 'error');
        }
    });
});
</script>
@endsection
