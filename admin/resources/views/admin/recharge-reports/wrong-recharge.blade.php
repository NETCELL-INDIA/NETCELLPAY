@extends('layouts.master')
@section('title') Wrong Number Requests @endsection
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Wrong number requests</h4>
        <div>
            <a class="btn btn-sm {{ $done ? 'btn-outline-primary' : 'btn-primary' }}" href="{{ url('admin/recharge-reports/wrong-recharge') }}">Pending</a>
            <a class="btn btn-sm {{ $done ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ url('admin/recharge-reports/wrong-recharge?status=done') }}">Processed</a>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">Approve only records the request. Refund credits the original recharge back to the retailer wallet and reverses commission. It does not send a recharge to the correct number.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Retailer</th>
                        <th>Order</th>
                        <th>Wrong number</th>
                        <th>Correct number</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->public_id }}<br><small>{{ $row->created_at }}</small></td>
                        <td>{{ $row->outlet_name ?: $row->first_name ?: ('User '.$row->user_id) }}<br><small>{{ $row->mobile_number }}</small></td>
                        <td>{{ $row->order_id }}</td>
                        <td>{{ $row->wrong_number }}</td>
                        <td>{{ $row->correct_number }}</td>
                        <td>₹{{ $row->amount }}</td>
                        <td>{{ $row->reason }}</td>
                        <td>{{ $row->status }}</td>
                        <td class="text-nowrap">
                            @if(in_array($row->status, ['Pending', 'Approved'], true))
                                @if($row->status === 'Pending')
                                    <button type="button" class="btn btn-sm btn-primary w2r-action" data-id="{{ $row->id }}" data-action="Approved">Approve</button>
                                @endif
                                <button type="button" class="btn btn-sm btn-danger w2r-action" data-id="{{ $row->id }}" data-action="Rejected">Reject</button>
                                <button type="button" class="btn btn-sm btn-warning w2r-action" data-id="{{ $row->id }}" data-action="Refunded">Refund wallet</button>
                            @else
                                {{ $row->admin_note }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
$(document).on('click', '.w2r-action', function () {
    var action = $(this).data('action');
    var text = 'Reject this request? Wallet will not change.';
    if (action === 'Approved') {
        text = 'Approve for follow-up? Wallet will not change and no new recharge will be sent.';
    }
    if (action === 'Refunded') {
        text = 'Refund the original recharge to the retailer wallet and reverse commission? A recharge to the correct number will NOT be sent.';
    }
    if (!confirm(text)) {
        return;
    }
    var btn = $(this);
    btn.prop('disabled', true);
    $.post('{{ route('adminWrongRechargeUpdate') }}', {
        _token: '{{ csrf_token() }}',
        id: btn.data('id'),
        action: action
    }).done(function (res) {
        alert(res.message || 'Updated');
        if (res.type === 'success') {
            window.location.reload();
        } else {
            btn.prop('disabled', false);
        }
    }).fail(function () {
        alert('Could not update the request');
        btn.prop('disabled', false);
    });
});
</script>
@endsection
