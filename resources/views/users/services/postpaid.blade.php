@extends('layouts.master')

@section('title') Postpaid Recharge @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Services @endslot
@slot('title') Postpaid Recharge @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Postpaid Bill Payment</h4></div>
            <div class="card-body">
                <form id="pay_form">
                    <div class="row gy-3">
                        <div class="col-lg-3">
                            <label class="form-label mb-0">Mobile Number</label>
                            <input type="number" class="form-control" id="number" placeholder="Enter Postpaid Number">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label mb-0">Provider</label>
                            <select class="form-control" id="provider_id">
                                <option value="">Select Provider</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Circle</label>
                            <select class="form-control" id="state_id">
                                <option value="">Select Circle</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Amount</label>
                            <input type="number" class="form-control" id="amount_i" placeholder="Amount">
                        </div>
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" class="btn btn-warning w-100" onclick="rechargeNow()">Pay Now</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirm Postpaid Payment</h5></div>
            <div class="modal-body">
                <p>Number: <strong id="cd_number"></strong></p>
                <p>Provider: <strong id="cd_provider"></strong></p>
                <p>Amount: <strong id="cd_amount"></strong></p>
                <label class="form-label">Transaction PIN</label>
                <input type="password" maxlength="4" class="form-control" id="t_pin" placeholder="4 digit PIN">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="recharge_now_btn" onclick="rechargeConfirm()">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    var service_id = 4;

    $(function () {
        fatchProviderAndState();
    });

    function fatchProviderAndState() {
        $.ajax({
            url: '{{ route('servivceProviderStateList') }}',
            method: 'post',
            data: { service: service_id, _token: '{{ csrf_token() }}' },
            success: function (res) {
                $('#provider_id').empty().append('<option value="">Select Provider</option>');
                $.each(res.provider || [], function (k, v) {
                    var providerName = String(v.provider_name || '');
                    var isDisabled = false;

                    if (Number(v.status) !== 1) {
                        providerName += ' (OFF)';
                        isDisabled = true;
                    } else if (Number(v.provider_down) === 1 || Number(v.user_down) === 1) {
                        providerName += ' (DOWN)';
                        isDisabled = true;
                    }

                    $('#provider_id').append($('<option>').val(v.id).text(providerName).prop('disabled', isDisabled));
                });
                $('#state_id').empty().append('<option value="">Select Circle</option>');
                $.each(res.state || [], function (k, v) {
                    $('#state_id').append('<option value="' + v.id + '">' + v.state_name + '</option>');
                });
            }
        });
    }

    function rechargeNow() {
        var number = $('#number').val();
        var provider_id = $('#provider_id').val();
        var amount = $('#amount_i').val();
        if (!number) return Error_Msg('Oops...', 'Please enter mobile number', 'error');
        if (!provider_id) return Error_Msg('Oops...', 'Please select provider', 'error');
        if (!amount) return Error_Msg('Oops...', 'Please enter amount', 'error');
        $('#cd_number').text(number);
        $('#cd_provider').text($('#provider_id option:selected').text());
        $('#cd_amount').text(amount);
        $('#detailsModal').modal('show');
    }

    function rechargeConfirm() {
        $.ajax({
            url: '{{ route('serviceRecharge') }}',
            method: 'post',
            data: {
                number: $('#number').val(),
                service_id: service_id,
                provider_id: $('#provider_id').val(),
                state_id: $('#state_id').val() || 40,
                amount: $('#amount_i').val(),
                pin: $('#t_pin').val(),
                transaction_type: 'Bill Pay',
                _token: '{{ csrf_token() }}'
            },
            success: function (data) {
                $('#detailsModal').modal('hide');
                if (data.type === 'success') {
                    Error_Msg(data.status, data.remark || data.message, data.status === 'Success' ? 'success' : (data.status === 'Pending' ? 'info' : 'error'));
                    $('#pay_form')[0].reset();
                } else {
                    Error_Msg(data.type || 'Failed', data.message, 'error');
                }
            },
            error: function () {
                Error_Msg('Oops...', 'Something went wrong!', 'error');
            }
        });
    }
</script>
@endsection
