@extends('layouts.master')

@section('title') Bill Payments @endsection

@section('css')
<style>
    .br_ui { border: 3px solid #e9ebec; border-radius: 16px; transition: .2s; }
    .br_ui:hover, .br_ui.active { border-color: #405189; box-shadow: 0 4px 14px rgba(64,81,137,.15); }
    .pointer { cursor: pointer; }
</style>
<link href="{{ URL::asset('/assets/libs/choices.js/choices.js.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Services @endslot
@slot('title') Bill Payments @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">BBPS Categories</h4></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($bbps_categories as $cat)
                    <div class="col-6 col-md-4 col-lg-3 pointer" onclick="selectService({{ $cat['id'] }}, '{{ $cat['name'] }}')">
                        <div class="card-body text-center br_ui {{ (int)$service_id === (int)$cat['id'] ? 'active' : '' }}">
                            <img src="{{ $cat['logo_url'] ?? URL::asset($cat['logo']) }}" alt="{{ $cat['name'] }}" style="height:56px" onerror="this.src='{{ URL::asset('service_logo/10.png') }}'">
                            <h6 class="mt-2 mb-0">{{ $cat['name'] }}</h6>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0" id="service_name">{{ $service ?: 'Select Service' }}</h4></div>
            <div class="card-body">
                <input type="hidden" id="service_id" value="{{ $service_id }}">
                <div class="mb-3">
                    <label class="form-label text-muted">Provider</label>
                    <select class="form-control" id="provider_id">
                        <option value="">Select Provider</option>
                        @foreach ($providers as $list)
                            <option value="{{ $list->id }}">{{ $list->provider_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="params_div"></div>
                <div id="bill_info" class="alert alert-info d-none"></div>
                <button type="button" id="bill_fatch" onclick="billFatch()" class="btn btn-warning w-100 mb-2" style="display:none">Fetch Bill</button>
                <button type="button" id="bill_pay" onclick="openPayModal()" class="btn btn-success w-100" style="display:none">Pay Bill</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirm Bill Payment</h5></div>
            <div class="modal-body">
                <p>Account: <strong id="pay_number"></strong></p>
                <p>Amount: <strong id="pay_amount"></strong></p>
                <label class="form-label">Transaction PIN</label>
                <input type="password" maxlength="4" class="form-control" id="t_pin">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="pay_now_btn" onclick="payBillNow()">Pay Now</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    var billData = null;
    var inputCount = 0;

    function selectService(id, name) {
        window.location = '{{ url('users/services/bill-payments') }}?id=' + id + '&name=' + encodeURIComponent(name);
    }

    $("#provider_id").on("change", function () {
        $("#params_div").empty();
        $("#bill_info").addClass('d-none').text('');
        $('#bill_fatch').hide();
        $('#bill_pay').hide();
        billData = null;

        if (!this.value) return;

        $.ajax({
            url: '{{ route('fetchProviderParams') }}',
            method: 'post',
            data: { id: this.value, _token: '{{ csrf_token() }}' },
            success: function (data) {
                if (data.type !== 'success') {
                    return Error_Msg(data.type, data.message, data.type);
                }
                var params = JSON.parse(data.biller.biller_data);
                var paramsData = [];
                for (var i = 1; i <= 5; i++) {
                    var label = params.data['label_' + i];
                    if (!label) continue;
                    paramsData.push({
                        param: label,
                        regex: params.data['regex_' + i] || '',
                        option: params.data['regex_' + i + '_autocomplete_values'] || []
                    });
                }
                inputCount = parseInt(params.input_count || paramsData.length || 1);
                var html = '';
                for (var j = 0; j < inputCount; j++) {
                    if (!paramsData[j]) continue;
                    var field = 'filed_' + j;
                    var inputHtml;
                    if (paramsData[j].regex) {
                        inputHtml = '<input type="text" class="form-control bill-field" name="' + field + '" id="' + field + '" pattern="' + paramsData[j].regex + '" required placeholder="' + paramsData[j].param + '">';
                    } else if (paramsData[j].option && paramsData[j].option.length) {
                        var opts = '<option value="">Select ' + paramsData[j].param + '</option>';
                        paramsData[j].option.forEach(function (o) {
                            opts += '<option value="' + o.value + '">' + o.display_name + '</option>';
                        });
                        inputHtml = '<select class="form-control bill-field" name="' + field + '" id="' + field + '">' + opts + '</select>';
                    } else {
                        inputHtml = '<input type="text" class="form-control bill-field" name="' + field + '" id="' + field + '" required placeholder="' + paramsData[j].param + '">';
                    }
                    html += '<div class="mb-3"><label class="form-label text-muted">' + paramsData[j].param + '</label>' + inputHtml + '</div>';
                }
                $("#params_div").html(html);
                $('#bill_fatch').show();
            },
            error: function () {
                Error_Msg('Oops...', 'Something went wrong!', 'error');
            }
        });
    });

    function collectFields() {
        var fields = {};
        $('.bill-field').each(function () {
            fields[$(this).attr('name')] = $(this).val();
        });
        return fields;
    }

    function billFatch() {
        $('#bill_fatch').text('Fetching...').prop('disabled', true);
        var payload = collectFields();
        payload.provider_id = $('#provider_id').val();
        payload.service_id = $('#service_id').val();
        payload._token = '{{ csrf_token() }}';

        $.ajax({
            url: '{{ route('fetchBill') }}',
            method: 'post',
            data: payload,
            success: function (data) {
                $('#bill_fatch').text('Fetch Bill').prop('disabled', false);
                if (data.type === 'success') {
                    billData = data.data;
                    var info = 'Amount: ₹' + billData.amount;
                    if (billData.customer_name) info += '<br>Customer: ' + billData.customer_name;
                    if (billData.due_date) info += '<br>Due: ' + billData.due_date;
                    $('#bill_info').removeClass('d-none').html(info);
                    $('#bill_pay').show();
                } else {
                    Error_Msg(data.type || 'error', data.message, 'error');
                }
            },
            error: function () {
                $('#bill_fatch').text('Fetch Bill').prop('disabled', false);
                Error_Msg('Oops...', 'Bill fetch failed!', 'error');
            }
        });
    }

    function openPayModal() {
        if (!billData) return;
        $('#pay_number').text(billData.number);
        $('#pay_amount').text('₹ ' + billData.amount);
        $('#payModal').modal('show');
    }

    function payBillNow() {
        $('#pay_now_btn').text('Processing...').prop('disabled', true);
        $.ajax({
            url: '{{ route('payBill') }}',
            method: 'post',
            data: {
                number: billData.number,
                amount: billData.amount,
                service_id: $('#service_id').val(),
                provider_id: $('#provider_id').val(),
                state_id: 40,
                pin: $('#t_pin').val(),
                transaction_type: 'Bill Pay',
                _token: '{{ csrf_token() }}'
            },
            success: function (data) {
                $('#pay_now_btn').text('Pay Now').prop('disabled', false);
                $('#payModal').modal('hide');
                if (data.type === 'success') {
                    Error_Msg(data.status, data.remark || data.message, data.status === 'Success' ? 'success' : (data.status === 'Pending' ? 'info' : 'error'));
                    billData = null;
                    $('#bill_info').addClass('d-none');
                    $('#bill_pay').hide();
                    $('#params_div').empty();
                    $('#provider_id').val('');
                } else {
                    Error_Msg(data.type || 'Failed', data.message, 'error');
                }
            },
            error: function () {
                $('#pay_now_btn').text('Pay Now').prop('disabled', false);
                Error_Msg('Oops...', 'Payment failed!', 'error');
            }
        });
    }
</script>
<script src="{{ URL::asset('/assets/libs/choices.js/choices.js.min.js') }}"></script>
@endsection
