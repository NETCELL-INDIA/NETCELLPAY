@extends('layouts.master')
@section('title') Khatabook @endsection
@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Add ledger entry</h5></div>
            <div class="card-body">
                <p class="small text-muted">This ledger does not change wallet balance.</p>
                <input class="form-control mb-2" id="user_id" placeholder="User ID">
                <select class="form-select mb-2" id="entry_type">
                    <option value="credit">Credit (they owe you)</option>
                    <option value="debit">Debit (they paid)</option>
                </select>
                <input class="form-control mb-2" id="amount" type="number" min="1" step="0.01" placeholder="Amount">
                <input class="form-control mb-2" id="note" placeholder="Note">
                <button class="btn btn-primary" id="save_khata" type="button">Save</button>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Outstanding</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>User</th><th>Due</th></tr></thead>
                    <tbody>
                    @foreach($outstanding as $row)
                        <tr><td>{{ $row->user_id }}</td><td>{{ number_format((float)$row->due, 2) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Credit history</h5></div>
            <div class="card-body">
                <table class="table table-bordered table-sm">
                    <thead><tr><th>Date</th><th>User</th><th>Type</th><th>Amount</th><th>Note</th></tr></thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->created_at }}</td>
                            <td>{{ $row->user_id }}</td>
                            <td>{{ $row->entry_type }}</td>
                            <td>{{ $row->amount }}</td>
                            <td>{{ $row->note }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No entries.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
$('#save_khata').on('click', function () {
    $.post('{{ route('adminKhataSave') }}', {
        _token: '{{ csrf_token() }}',
        user_id: $('#user_id').val(),
        entry_type: $('#entry_type').val(),
        amount: $('#amount').val(),
        note: $('#note').val()
    }, function (res) {
        alert(res.message || 'Done');
        if (res.type === 'success') location.reload();
    });
});
</script>
@endsection
