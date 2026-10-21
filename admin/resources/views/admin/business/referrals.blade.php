@extends('layouts.master')
@section('title') Refer & Earn @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">Referral Bonus History</h4></div>
    <div class="card-body">
        <p class="text-muted">Bonus uses the existing referral amount setting and is credited when a downline user is created. This page only lists those credits.</p>
        <table class="table table-bordered table-sm">
            <thead><tr><th>ID</th><th>Parent user</th><th>Amount</th><th>Order</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->user_id }}</td>
                    <td>{{ $row->amount }}</td>
                    <td>{{ $row->order_id }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No referral bonus rows yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
