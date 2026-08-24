@extends('layouts.master')
@section('title') Commission @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Commission @endslot
@slot('title') Commission @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Commission</h4>
            </div>
            <form id="bulkUpdateForm" method="post">
                @csrf
                <div class="card-body">
                    <div class="row align-items-start">
                        <div class="col-sm-2">
                            <label>Scheme</label>
                            <input type="hidden" name="scheme_id" id="scheme_id" value="{{ $schemes->first()->id ?? '' }}">
                            <div class="input-group mb-3">
                                <select class="form-select" id="scheme_select" aria-label="Select Scheme">
                                    @forelse($schemes as $scheme)
                                        <option value="{{ $scheme->id }}">{{ strtoupper($scheme->scheme_name) }}</option>
                                    @empty
                                        <option value="">No Scheme</option>
                                    @endforelse
                                </select>
                                <button type="button" class="btn btn-info" id="newSchemeBtn" title="Create New Scheme"><i class="ri-add-line"></i></button>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label>Service Type</label>
                            <select class="form-select mb-3 service" id="service" aria-label="Select Service">
                                @forelse($services as $service)
                                    <option value="{{ $service->id }}">{{ strtoupper($service->service_name) }}</option>
                                @empty
                                    <option value="">No Service</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>MD Com Type</label>
                            <select class="form-select mb-3" id="md_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent">Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>MD Com Val</label>
                            <input type="text" class="form-control" id="md_value" value="0">
                        </div>
                        <div class="col-sm-1">
                            <label>DT Com Type</label>
                            <select class="form-select mb-3" id="dt_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent">Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>DT Com Val</label>
                            <input type="text" class="form-control" id="dt_value" value="0">
                        </div>
                        <div class="col-sm-1">
                            <label>RT Com Type</label>
                            <select class="form-select mb-3" id="rt_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent">Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>RT Com Val</label>
                            <input type="text" class="form-control" id="rt_value" value="0">
                        </div>
                        <div class="col-sm-1">
                            <label>AP Com Type</label>
                            <select class="form-select mb-3" id="ap_comtype">
                                <option value="Commission Flat">Commission Flat</option>
                                <option value="Commission Percent">Commission Percent</option>
                                <option value="Charge Flat">Charge Flat</option>
                                <option value="Charge Percent">Charge Percent</option>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <label>AP Com Val</label>
                            <input type="text" class="form-control" id="ap_value" value="0">
                        </div>
                        <div class="col-sm-1">
                            <label>Apply All</label>
                            <input type="button" value="Apply" class="form-control btn btn-primary waves-effect waves-light" id="apply_all" />
                        </div>
                    </div>
                    <div class="table-responsive" id="commission_result">
                        <h4 class="text-center text-secondary my-3">No record found</h4>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" id="bulk_update_commiission">Update All</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- New Scheme Modal -->
<div id="newSchemeModal" class="modal" tabindex="-1" aria-labelledby="newSchemeModalLabel" data-bs-backdrop="static" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newSchemeModalLabel">Create New Scheme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="new_scheme_form" method="post">
                @csrf
                <input type="hidden" name="edit_id" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_scheme_name" class="col-form-label">Scheme Name:</label>
                        <input type="text" class="form-control" name="schemeName" id="new_scheme_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Status:</label>
                        <select class="form-select" name="status" id="new_scheme_status">
                            <option value="1" selected>Active</option>
                            <option value="0">Deactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="new_scheme_btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
    function commissionNotify(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: {
                confirmButton: 'btn btn-primary w-xs mt-2',
            },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function fetchCommissionAll() {
        var id = $('#scheme_select').val();
        var service = $('#service').val();
        if (!id || !service) {
            $('#commission_result').html('<h4 class="text-center text-secondary my-3">No record found</h4>');
            return;
        }
        $('#scheme_id').val(id);
        $('#commission_result').html('<h4 class="text-center text-secondary my-3">Loading...</h4>');
        $.ajax({
            url: '{{ route('schemeGetCommission') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}', id: id, service: service },
            success: function(res) {
                $('#commission_result').html(res);
            },
            error: function() {
                $('#commission_result').html('<h4 class="text-center text-danger my-3">Something went wrong!</h4>');
            }
        });
    }

    $('#scheme_select, #service').on('change', function() {
        fetchCommissionAll();
    });

    $('#apply_all').on('click', function() {
        $('select[name="md_comtype[]"]').val($('#md_comtype').val());
        $('input[name="md_value[]"]').val($('#md_value').val());

        $('select[name="dt_comtype[]"]').val($('#dt_comtype').val());
        $('input[name="dt_value[]"]').val($('#dt_value').val());

        $('select[name="rt_comtype[]"]').val($('#rt_comtype').val());
        $('input[name="rt_value[]"]').val($('#rt_value').val());
        $('select[name="ap_comtype[]"]').val($('#ap_comtype').val());
        $('input[name="ap_value[]"]').val($('#ap_value').val());
    });

    $(document).on('click', '.updateCommission', function(e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: '{{ route('schemeSingleUpdateCommission') }}',
            method: 'post',
            data: {
                scheme_id: $('#scheme_id').val(),
                provider_id: $('#' + id + '_provider_id').val(),
                md_comtype: $('#' + id + '_md_comtype').val(),
                md_value: $('#' + id + '_md_value').val(),
                dt_comtype: $('#' + id + '_dt_comtype').val(),
                dt_value: $('#' + id + '_dt_value').val(),
                rt_comtype: $('#' + id + '_rt_comtype').val(),
                rt_value: $('#' + id + '_rt_value').val(),
                ap_comtype: $('#' + id + '_ap_comtype').val(),
                ap_value: $('#' + id + '_ap_value').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(data) {
                if (data.type == 'success') {
                    commissionNotify('Updated', 'Updated Successfully!', 'success');
                } else {
                    commissionNotify('Error', data.message || 'Something went wrong!', 'error');
                }
            },
            error: function() {
                commissionNotify('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $('#bulkUpdateForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        $('#bulk_update_commiission').text('Please wait...').prop('disabled', true);
        $.ajax({
            url: '{{ route('schemeBulkUpdateCommission') }}',
            method: 'post',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(data) {
                $('#bulk_update_commiission').text('Update All').prop('disabled', false);
                if (data.type == 'success') {
                    commissionNotify('Updated', 'Updated Successfully!', 'success');
                    fetchCommissionAll();
                } else {
                    commissionNotify('Error', data.message || 'Something went wrong!', 'error');
                }
            },
            error: function() {
                $('#bulk_update_commiission').text('Update All').prop('disabled', false);
                commissionNotify('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    $('#newSchemeBtn').on('click', function() {
        $('#new_scheme_form')[0].reset();
        $('#newSchemeModal').modal('show');
    });

    $('#new_scheme_form').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        $('#new_scheme_btn').text('Please wait...').prop('disabled', true);
        $.ajax({
            url: '{{ route('schemeUpdate') }}',
            method: 'post',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            success: function(data) {
                $('#new_scheme_btn').text('Save').prop('disabled', false);
                if (data.type == 'success') {
                    $('#newSchemeModal').modal('hide');
                    commissionNotify('Created', 'Scheme Created Successfully!', 'success');
                    if (data.id) {
                        $('#scheme_select').append('<option value="' + data.id + '">' + String(data.scheme_name).toUpperCase() + '</option>');
                        $('#scheme_select').val(data.id);
                        fetchCommissionAll();
                    }
                } else {
                    commissionNotify('Error', data.message || 'Something went wrong!', 'error');
                }
            },
            error: function(xhr) {
                $('#new_scheme_btn').text('Save').prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong!';
                commissionNotify('Oops...', msg, 'error');
            }
        });
    });

    fetchCommissionAll();
</script>
@endsection
