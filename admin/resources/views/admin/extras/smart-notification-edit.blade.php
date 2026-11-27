@extends('layouts.master')
@section('title') Edit notification {{ $campaign->id }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') Edit notification {{ $campaign->id }} @endslot
@endcomponent

@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card" style="border-top: 3px solid #175CD3;">
    <div class="card-body">
        <p class="text-muted">This changes the notification text stored for the app inbox. An alert already shown on a phone cannot be changed, and this does not send the notification again.</p>
        <form method="post" action="{{ route('smartNotificationUpdate', $campaign->id) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input class="form-control" id="title" name="title" maxlength="120" required value="{{ old('title', $campaign->title) }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" id="message" name="message" maxlength="1000" rows="5" required>{{ old('message', $campaign->message) }}</textarea>
            </div>
            @if(in_array($campaign->status, ['draft', 'scheduled'], true))
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="schedule_date">Schedule date</label>
                    <input class="form-control" type="date" id="schedule_date" name="schedule_date" value="{{ old('schedule_date', $campaign->scheduled_at ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="schedule_time">Schedule time</label>
                    <input class="form-control" type="time" id="schedule_time" name="schedule_time" value="{{ old('schedule_time', $campaign->scheduled_at ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('H:i') : '') }}">
                </div>
            </div>
            <p class="text-muted">Leave the schedule blank to keep the current time. Filling both fields schedules this notification.</p>
            @endif
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="{{ route('smartNotificationShow', $campaign->id) }}">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
