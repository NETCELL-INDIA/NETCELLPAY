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
                    <p class="mb-2"><strong>{{ $message->sender_role }}</strong> {{ $message->created_at }}
                        @if($message->delivery_status) <span class="text-muted">Push: {{ $message->delivery_status }}</span>@endif
                        <br>{{ $message->body }}</p>
                    @endif
                @empty
                <p class="text-muted">No customer messages recorded.</p>
                @endforelse
                <form method="post" action="{{ route('complaintDeskReply', $complaint->id) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <textarea class="form-control mb-2" name="body" rows="3" placeholder="Reply to the customer"></textarea>
                    <input class="form-control mb-2" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf">
                    <button class="btn btn-primary" type="submit">Send Reply</button>
                </form>
                <form method="post" action="{{ route('complaintDeskRequestInfo', $complaint->id) }}" class="mt-3">
                    @csrf
                    <textarea class="form-control mb-2" name="body" rows="2" placeholder="What should the customer send?"></textarea>
                    <button class="btn btn-outline-primary" type="submit">Request More Information</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Internal notes</h4></div>
            <div class="card-body">
                <p class="text-muted">Internal notes are not shown to the customer.</p>
                @foreach($messages as $message)
                    @if($message->visibility === 'internal')
                    <p class="mb-2"><strong>Staff {{ $message->sender_id }}</strong> {{ $message->created_at }}<br>{{ $message->body }}</p>
                    @endif
                @endforeach
                <form method="post" action="{{ route('complaintDeskNote', $complaint->id) }}">
                    @csrf
                    <textarea class="form-control mb-2" name="body" rows="2" placeholder="Internal note"></textarea>
                    <button class="btn btn-secondary" type="submit">Add Internal Note</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Actions</h4></div>
            <div class="card-body">
                <form method="post" action="{{ route('complaintDeskAssign', $complaint->id) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Assign to staff</label>
                    <select class="form-select mb-2" name="staff_id">
                        @foreach($staff as $person)
                        <option value="{{ $person->id }}">{{ $person->outlet_name ?: $person->first_name }} (#{{ $person->id }})</option>
                        @endforeach
                    </select>
                    <input class="form-control mb-2" name="remarks" placeholder="Remarks">
                    <button class="btn btn-primary" type="submit">Assign</button>
                </form>
                <form method="post" action="{{ route('complaintDeskPriority', $complaint->id) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Priority</label>
                    <select class="form-select mb-2" name="priority">
                        @foreach(['low','normal','high','urgent'] as $priority)
                        <option value="{{ $priority }}" @selected(($complaint->priority ?: 'normal') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Change Priority</button>
                </form>
                <form method="post" action="{{ route('complaintDeskStatus', $complaint->id) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Status</label>
                    <select class="form-select mb-2" name="status">
                        @foreach(['NEW','UNDER REVIEW','ASSIGNED','IN PROGRESS','WAITING FOR CUSTOMER','RESOLVED','REJECTED','CLOSED'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                    <input class="form-control mb-2" name="remarks" placeholder="Remarks. Required when rejecting.">
                    <button class="btn btn-outline-primary" type="submit">Change Status</button>
                </form>
                <form method="post" action="{{ route('complaintDeskAttachment', $complaint->id) }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <input type="hidden" name="visibility" value="internal">
                    <input class="form-control mb-2" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf" required>
                    <input class="form-control mb-2" name="body" placeholder="File note">
                    <button class="btn btn-outline-secondary" type="submit">Upload Supporting Document</button>
                </form>
                <form method="post" action="{{ route('complaintDeskResolve', $complaint->id) }}" class="d-inline">@csrf<button class="btn btn-success" type="submit">Mark as Resolved</button></form>
                <form method="post" action="{{ route('complaintDeskReject', $complaint->id) }}" class="d-inline">@csrf<input name="remarks" class="form-control d-inline w-auto" placeholder="Rejection reason" required><button class="btn btn-danger" type="submit">Reject</button></form>
                <form method="post" action="{{ route('complaintDeskClose', $complaint->id) }}" class="d-inline">@csrf<button class="btn btn-dark" type="submit">Close</button></form>
                @if($canReopen)
                <form method="post" action="{{ route('complaintDeskReopen', $complaint->id) }}" class="d-inline">@csrf<button class="btn btn-warning" type="submit">Reopen</button></form>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Attachments</h4></div>
            <div class="card-body">
                @forelse($attachments as $file)
                <p class="mb-1"><a href="{{ route('complaintDeskDownload', $file->id) }}">{{ $file->original_name }}</a> <span class="text-muted">{{ $file->visibility }}</span></p>
                @empty
                <p class="text-muted">No attachment recorded.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Status history</h4></div>
            <div class="card-body">
                @forelse($history as $event)
                <p class="mb-1">{{ $event->created_at }}: {{ $event->previous_status }} → {{ $event->new_status }} by admin {{ $event->admin_id }}. {{ $event->remarks }}</p>
                @empty
                <p class="text-muted">No status change recorded yet.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Feedback</h4></div>
            <div class="card-body">
                @forelse($feedback as $item)
                <p class="mb-1">Rating {{ $item->rating ?? 'Not recorded' }}: {{ $item->comment ?: 'Not recorded' }}</p>
                @empty
                <p class="text-muted">No feedback recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
