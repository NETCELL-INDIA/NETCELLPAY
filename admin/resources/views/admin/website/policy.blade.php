@extends('layouts.master')
@section('title') Web Policy @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Website @endslot
@slot('title')Web Policy@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title mb-0 flex-grow-1">Web Policy</h4>
                <button type="button" class="btn btn-info" id="save_btn" onclick="savePolicy()">Save Changes</button>
            </div>
            <div class="card-body">
                <form id="policy_form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Privacy Policy</label>
                        <textarea class="form-control" name="privacy_policy" rows="8">{{ $company->privacy_policy ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms &amp; Conditions</label>
                        <textarea class="form-control" name="terms_and_conditions" rows="8">{{ $company->terms_and_conditions ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Policy</label>
                        <textarea class="form-control" name="refund_policy" rows="8">{{ $company->refund_policy ?? '' }}</textarea>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function savePolicy() {
    $('#save_btn').prop('disabled', true).text('Saving...');
    $.post('{{ route('websitePolicySave') }}', $('#policy_form').serialize(), function (data) {
        $('#save_btn').prop('disabled', false).text('Save Changes');
        Swal.fire({ title: data.type, text: data.message, icon: data.type });
    }).fail(function () {
        $('#save_btn').prop('disabled', false).text('Save Changes');
        Swal.fire({ title: 'error', text: 'Unable to save.', icon: 'error' });
    });
}
</script>
@endsection
