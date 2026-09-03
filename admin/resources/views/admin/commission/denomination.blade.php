@extends('layouts.master')
@section('title') Denomination Commission @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Commission @endslot
@slot('title') Denomination Commission @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Denomination Wise Commission</h4>
                <a href="{{ url('admin/commission') }}" class="btn btn-sm btn-soft-primary">Scheme Commission</a>
            </div>
            <div class="card-body">
                <p class="text-muted">Set different MD / DT / RT / AP commission for amount ranges (example: 10-49, 99-99, 100-499). If no slab matches, the normal scheme commission is used.</p>
                <div class="row">
                    <div class="col-sm-3">
                        <label>Scheme</label>
                        <select class="form-select mb-3" id="scheme_id">
                            @forelse($schemes as $scheme)
                                <option value="{{ $scheme->id }}" {{ (string) $selected_scheme_id === (string) $scheme->id ? 'selected' : '' }}>{{ strtoupper($scheme->scheme_name) }}</option>
                            @empty
                                <option value="">No Scheme</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Service Type</label>
                        <select class="form-select mb-3" id="service_id">
                            @forelse($services as $service)
                                <option value="{{ $service->id }}" {{ (string) $selected_service_id === (string) $service->id ? 'selected' : '' }}>{{ strtoupper($service->service_name) }}</option>
                            @empty
                                <option value="">No Service</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label>Provider</label>
                        <select class="form-select mb-3" id="provider_id">
                            <option value="">Select Provider</option>
                        </select>
                    </div>
                </div>
                <div id="denomination_result">
                    <h4 class="text-center text-secondary my-3">Select scheme and provider</h4>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Add Denomination Slab</h4>
            </div>
            <div class="card-body">
                <form id="denominationForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-sm-2">
                            <label>From Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control mb-3" name="min_amount" id="min_amount" required>
                        </div>
                        <div class="col-sm-2">
                            <label>To Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control mb-3" name="max_amount" id="max_amount" required>
                        </div>
                        <div class="col-sm-2">
                            <label>MD Type</label>
                            <select class="form-select mb-3" name="md_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent" selected>Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>MD Val</label>
                            <input type="text" class="form-control mb-3" name="md_value" value="0" required>
                        </div>
                        <div class="col-sm-2">
                            <label>DT Type</label>
                            <select class="form-select mb-3" name="dt_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent" selected>Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>DT Val</label>
                            <input type="text" class="form-control mb-3" name="dt_value" value="0" required>
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-sm-2">
                            <label>RT Type</label>
                            <select class="form-select mb-3" name="rt_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent" selected>Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>RT Val</label>
                            <input type="text" class="form-control mb-3" name="rt_value" value="0" required>
                        </div>
                        <div class="col-sm-2">
                            <label>AP Type</label>
                            <select class="form-select mb-3" name="ap_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent" selected>Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>AP Val</label>
                            <input type="text" class="form-control mb-3" name="ap_value" value="0" required>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary mb-3" id="saveDenominationBtn">Save Slab</button>
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
    var presetProviderId = '{{ $selected_provider_id }}';

    function denomNotify(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function loadProviders(thenFetch) {
        var serviceId = $('#service_id').val();
        $.ajax({
            url: '{{ route('adminDenominationProviders') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', service_id: serviceId },
            success: function(res) {
                var html = '<option value="">Select Provider</option>';
                if (res.data) {
                    $.each(res.data, function(k, v) {
                        html += '<option value="' + v.id + '">' + String(v.provider_name).toUpperCase() + '</option>';
                    });
                }
                $('#provider_id').html(html);
                if (presetProviderId) {
                    $('#provider_id').val(presetProviderId);
                    presetProviderId = '';
                }
                if (thenFetch) {
                    fetchDenominations();
                }
            }
        });
    }

    function fetchDenominations() {
        var schemeId = $('#scheme_id').val();
        var providerId = $('#provider_id').val();
        if (!schemeId || !providerId) {
            $('#denomination_result').html('<h4 class="text-center text-secondary my-3">Select scheme and provider</h4>');
            return;
        }
        $('#denomination_result').html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('adminDenominationList') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', scheme_id: schemeId, provider_id: providerId },
            success: function(res) {
                $('#denomination_result').html(res);
            },
            error: function() {
                $('#denomination_result').html('<h4 class="text-center text-danger my-3">Something went wrong!</h4>');
            }
        });
    }

    $('#service_id').on('change', function() {
        loadProviders(true);
    });
    $('#scheme_id, #provider_id').on('change', fetchDenominations);

    $('#denominationForm').on('submit', function(e) {
        e.preventDefault();
        var schemeId = $('#scheme_id').val();
        var providerId = $('#provider_id').val();
        if (!schemeId || !providerId) {
            denomNotify('Error', 'Select scheme and provider first.', 'error');
            return;
        }
        var data = $(this).serializeArray();
        data.push({ name: 'scheme_id', value: schemeId });
        data.push({ name: 'provider_id', value: providerId });
        $('#saveDenominationBtn').prop('disabled', true).text('Please wait...');
        $.ajax({
            url: '{{ route('adminDenominationSave') }}',
            method: 'post',
            data: data,
            success: function(res) {
                $('#saveDenominationBtn').prop('disabled', false).text('Save Slab');
                if (res.type === 'success') {
                    denomNotify('Saved', res.message, 'success');
                    fetchDenominations();
                } else {
                    denomNotify('Error', res.message || 'Something went wrong!', 'error');
                }
            },
            error: function() {
                $('#saveDenominationBtn').prop('disabled', false).text('Save Slab');
                denomNotify('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $(document).on('click', '.deleteDenomination', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ route('adminDenominationDelete') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', id: id },
            success: function(res) {
                if (res.type === 'success') {
                    fetchDenominations();
                } else {
                    denomNotify('Error', res.message || 'Delete failed', 'error');
                }
            }
        });
    });

    loadProviders(true);
</script>
@endsection
