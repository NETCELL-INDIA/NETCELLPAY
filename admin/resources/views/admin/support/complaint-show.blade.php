@extends('layouts.master')
@section('title') Complaint {{ $complaint->request_id }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Support Management @endslot
@slot('title') {{ $complaint->request_id }} @endslot
@endcomponent
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<p class="text-muted">Changing a complaint does not credit a wallet, refund a payment, or change the recharge status.</p>
<div class="row">
    @foreach(['customer' => 'Customer Details', 'ticket' => 'Complaint Details', 'transaction' => 'Transaction Details'] as $key => $heading)
    <div class="col-lg-4">
        <div class="card" style="border-top: 3px solid #175CD3;">
            <div class="card-header"><h4 class="card-title mb-0">{{ $heading }}</h4></div>
            <div class="card-body">
                @foreach($sections[$key] as $label => $value)
                <p class="mb-1"><span class="text-muted">{{ $label }}</span><br>{{ $value }}</p>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@foreach($sections['specific'] as $heading => $fields)
<div class="card" style="border-top: 3px solid #6D28D9;">
    <div class="card-header"><h4 class="card-title mb-0">{{ $heading }}</h4></div>
    <div class="card-body">
        @foreach($fields as $label => $value)
        <p class="mb-1"><span class="text-muted">{{ $label }}</span><br>{{ $value }}</p>
        @endforeach
    </div>
</div>
@endforeach
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Customer messages</h4></div>
            <div class="card-body">
                @forelse($messages as $message)
                    @if($message->visibility === 'customer')
                    <p class="mb-2"><strong>{{ $message->sender_role }}</strong> {{ $message->created_at }}<br>{{ $message->body }}</p>
                    @endif
                @empty
                <p class="text-muted">No customer messages recorded.</p>
                @endforelse
                @if($ready)
                <form method="post" action="{{ route('complaintDeskReply', $complaint->id) }}" class="mt-3">
                    @csrf
                    <textarea class="form-control mb-2" name="body" rows="3" placeholder="Reply to the customer"></textarea>
                    <button class="btn btn-primary" type="submit">Send Reply</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card" style="border-top: 3px solid #175CD3;">
            <div class="card-header"><h4 class="card-title mb-0">Resolve this complaint</h4></div>
            <div class="card-body">
                <p class="text-muted">Choose one. The wallet is not refunded and the recharge status stays the same.</p>
                <form method="post" action="{{ route('complaintDeskResolve', $complaint->id) }}" class="mb-4">
                    @csrf
                    <label class="form-label">Resolved</label>
                    <textarea class="form-control mb-2" name="remarks" rows="2" placeholder="What was done? Optional."></textarea>
                    <button class="btn btn-success" type="submit">Mark Resolved</button>
                </form>
                <form method="post" action="{{ route('complaintDeskReject', $complaint->id) }}">
                    @csrf
                    <label class="form-label">Rejected</label>
                    <textarea class="form-control mb-2" name="remarks" rows="2" placeholder="Why is this rejected?" required></textarea>
                    <button class="btn btn-danger" type="submit">Reject</button>
                </form>
            </div>
        </div>
        @if(count($attachments))
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Attachments</h4></div>
            <div class="card-body">
                @forelse($attachments as $file)
                <p class="mb-1"><a href="{{ route('complaintDeskDownload', $file->id) }}">{{ $file->original_name }}</a> <span class="text-muted">{{ $file->visibility }}</span></p>
                @empty
                @endforelse
            </div>
        </div>
        @endif
        @if(count($history))
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Status history</h4></div>
            <div class="card-body">
                @forelse($history as $event)
                <p class="mb-1">{{ $event->created_at }}: {{ $event->previous_status }} → {{ $event->new_status }} by admin {{ $event->admin_id }}. {{ $event->remarks }}</p>
                @empty
                @endforelse
            </div>
        </div>
        @endif
        @if(count($feedback))
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Feedback</h4></div>
            <div class="card-body">
                @forelse($feedback as $item)
                <p class="mb-1">Rating {{ $item->rating ?? 'Not recorded' }}: {{ $item->comment ?: 'Not recorded' }}</p>
                @empty
                @endforelse
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
