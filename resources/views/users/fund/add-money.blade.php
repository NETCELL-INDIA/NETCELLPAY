@extends('layouts.master')
@section('title') Add Money @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Fund @endslot
@slot('title') Add Money @endslot
@endcomponent

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Bank details</h4>
            </div>
            <div class="card-body">
                @forelse($banks as $bank)
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold">{{ $bank['bank_name'] }} — {{ $bank['bank_branch'] }}</div>
                        <div>A/c name: {{ $bank['account_name'] }}</div>
                        <div>A/c no: {{ $bank['account_number'] }}</div>
                        <div>IFSC: {{ $bank['ifsc_code'] }}</div>
                        <div>Type: {{ $bank['account_type'] }}</div>
                    </div>
                @empty
                    <div class="text-muted mb-0">Bank details are not added yet. Add them in Admin → System → Banks and keep status ON.</div>
                @endforelse
                <a href="{{ url('users/fund/fund-request') }}" class="btn btn-light">Fund Request</a>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Add Money Online</h4>
            </div>
            <div class="card-body">
                @if(!$upiOn)
                    <div class="alert alert-warning mb-0">Payment gateway is off. Use Fund Request, or contact support.</div>
                @else
                    <form id="addMoneyPageForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Payment method</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gateway" id="gwUpi" value="upi" checked>
                                <label class="form-check-label" for="gwUpi">
                                    UPI — ₹{{ $company->payment_gateway_min }} to ₹{{ $company->payment_gateway_max }}
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" min="1" step="1" required placeholder="Enter amount">
                        </div>
                        <button type="submit" class="btn btn-primary" id="addMoneyPageBtn">Pay now</button>
                        <a href="{{ url('users/fund/fund-request') }}" class="btn btn-light">Fund Request</a>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Recent online payments</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent as $row)
                                <tr>
                                    <td>{{ $row->order_id }}</td>
                                    <td>₹{{ number_format((float) $row->amount, 2) }}</td>
                                    <td>{{ (int) $row->upi === 2 ? 'QR' : 'UPI' }}</td>
                                    <td>{{ $row->status }}</td>
                                    <td>{{ $row->created_at }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center">No online payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
$('#addMoneyPageForm').on('submit', function (e) {
    e.preventDefault();
    var url = '{{ route('addMoneyRequestSubmit') }}';
    var btn = $('#addMoneyPageBtn');
    btn.prop('disabled', true).text('Please wait...');
    $.ajax({
        url: url,
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (data) {
            btn.prop('disabled', false).text('Pay now');
            if (data.type === 'success' && data.pay_url) {
                window.location.href = data.pay_url;
                return;
            }
            Error_Msg(data.type === 'error' ? 'Error' : 'Done', data.message || 'Unable to start payment.', data.type || 'error');
        },
        error: function () {
            btn.prop('disabled', false).text('Pay now');
            Error_Msg('Error', 'Unable to start payment.', 'error');
        }
    });
});
</script>
@endsection
