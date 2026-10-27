@extends('layouts.master')
@section('title') Account Deletion Requests @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">Account Deletion Requests</h4></div>
    <div class="card-body">
        <p class="text-muted">Completed means profile data and login credentials were removed. Recharge and wallet rows are kept for accounting. Reject does not change the user account.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->public_id }}</td>
                        <td>{{ $row->user_id }}</td>
                        <td>{{ $row->mobile_number }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->created_at }}</td>
                        <td>
                            @if(in_array($row->status, ['Pending Review', 'Approved', 'Processing'], true))
                                <button type="button" class="btn btn-sm btn-success deletion-action" data-id="{{ $row->id }}" data-status="Completed">Complete deletion</button>
                                <button type="button" class="btn btn-sm btn-danger deletion-action" data-id="{{ $row->id }}" data-status="Rejected">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
$('.deletion-action').on('click', function () {
    var status = $(this).data('status');
    if (status === 'Completed' && !confirm('Remove this user\'s login and profile data? Recharge records will stay.')) {
        return;
    }
    $.post('{{ route('adminAccountDeletionUpdate') }}', {
        _token: '{{ csrf_token() }}',
        id: $(this).data('id'),
        status: status
    }, function (res) {
        alert(res.message || 'Done');
        if (res.type === 'success') location.reload();
    });
});
</script>
@endsection
