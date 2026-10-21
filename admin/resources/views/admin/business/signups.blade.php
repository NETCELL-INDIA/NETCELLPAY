@extends('layouts.master')
@section('title') Signup / KYC Requests @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">Signup / KYC Requests</h4></div>
    <div class="card-body">
        <table class="table table-bordered table-sm">
            <thead><tr><th>ID</th><th>Name</th><th>Mobile</th><th>Outlet</th><th>Status</th><th>Docs</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                    <td>{{ $row->mobile_number }}</td>
                    <td>{{ $row->outlet_name }}</td>
                    <td>{{ $row->kyc_status }}</td>
                    <td>
                        @foreach(($docs[$row->id] ?? []) as $doc)
                            <div>{{ $doc->doc_type }} — {{ $doc->status }}</div>
                        @endforeach
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success status-btn" data-id="{{ $row->id }}" data-status="Approved">Approve</button>
                        <button class="btn btn-sm btn-danger status-btn" data-id="{{ $row->id }}" data-status="Rejected">Reject</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No pending signups.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@section('script')
<script>
$('.status-btn').on('click', function () {
    $.post('{{ route('adminSignupStatus') }}', {
        _token: '{{ csrf_token() }}',
        user_id: $(this).data('id'),
        kyc_status: $(this).data('status')
    }, function (res) {
        alert(res.message || 'Done');
        if (res.type === 'success') location.reload();
    });
});
</script>
@endsection
