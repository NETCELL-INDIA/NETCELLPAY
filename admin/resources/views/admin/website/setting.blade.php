@extends('layouts.master')
@section('title') Website Setting @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Website @endslot
@slot('title')Website Setting@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title mb-0 flex-grow-1">Website Setting</h4>
                <button type="button" class="btn btn-info" id="save_btn" onclick="saveSetting()">Save Changes</button>
            </div>
            <div class="card-body">
                <form id="setting_form">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label">Company / Website Name</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $company->company_name ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Number</label>
                            <input type="text" class="form-control" name="support_number" value="{{ $company->support_number ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Email</label>
                            <input type="email" class="form-control" name="support_email" value="{{ $company->support_email ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="company_address" value="{{ $company->company_address ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Meta Keywords</label>
                            <textarea class="form-control" name="meta_kewords" rows="2">{{ $company->meta_kewords ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Header Text</label>
                            <textarea class="form-control" name="header_value" rows="3">{{ $company->header_value ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Footer Text</label>
                            <textarea class="form-control" name="footer_value" rows="3">{{ $company->footer_value ?? '' }}</textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function saveSetting() {
    $('#save_btn').prop('disabled', true).text('Saving...');
    $.post('{{ route('websiteSettingSave') }}', $('#setting_form').serialize(), function (data) {
        $('#save_btn').prop('disabled', false).text('Save Changes');
        Swal.fire({ title: data.type, text: data.message, icon: data.type });
    }).fail(function () {
        $('#save_btn').prop('disabled', false).text('Save Changes');
        Swal.fire({ title: 'error', text: 'Unable to save.', icon: 'error' });
    });
}
</script>
@endsection
