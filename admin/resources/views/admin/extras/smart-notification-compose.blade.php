@extends('layouts.master')
@section('title') New campaign @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') New campaign @endslot
@endcomponent

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card" style="border-top: 3px solid #175CD3;">
    <div class="card-header"><h4 class="card-title mb-0">Notification</h4></div>
    <div class="card-body">
        <form method="post" action="{{ route('smartNotificationPreview') }}">
            @csrf
            <div class="row gy-3">
                <div class="col-md-6">
                    <label class="form-label" for="title">Title</label>
                    <input class="form-control" id="title" name="title" maxlength="120" required value="{{ old('title') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        @foreach($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="priority">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        @foreach(['normal' => 'Normal', 'high' => 'High', 'low' => 'Low'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" maxlength="1000" required>{{ old('message') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="image_url">Image URL (optional)</label>
                    <input class="form-control" id="image_url" name="image_url" maxlength="500" placeholder="https://" value="{{ old('image_url') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="deep_link">Deep link (optional)</label>
                    <input class="form-control" id="deep_link" name="deep_link" maxlength="500" placeholder="https://netcellpay.in/..." value="{{ old('deep_link') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="expires_date">Expiry date</label>
                    <input class="form-control" type="date" id="expires_date" name="expires_date" value="{{ old('expires_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="expires_time">Expiry time</label>
                    <input class="form-control" type="time" id="expires_time" name="expires_time" value="{{ old('expires_time') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="audience">Audience</label>
                    <select class="form-select" id="audience" name="audience" required>
                        <option value="all" @selected(old('audience', 'all') === 'all')>All eligible users</option>
                        <option value="user" @selected(old('audience') === 'user')>Selected user</option>
                        <option value="role:6" @selected(old('audience') === 'role:6')>Retailer</option>
                        <option value="role:5" @selected(old('audience') === 'role:5')>Distributor</option>
                        <option value="role:4" @selected(old('audience') === 'role:4')>Master Distributor</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mobile_number">User mobile</label>
                    <input class="form-control" id="mobile_number" name="mobile_number" inputmode="numeric" value="{{ old('mobile_number') }}" placeholder="Required for Selected user">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="send_mode">When</label>
                    <select class="form-select" id="send_mode" name="send_mode">
                        <option value="now" @selected(old('send_mode', 'now') === 'now')>Send now</option>
                        <option value="schedule" @selected(old('send_mode') === 'schedule')>Schedule</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="schedule_date">Schedule date</label>
                    <input class="form-control" type="date" id="schedule_date" name="schedule_date" value="{{ old('schedule_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="schedule_time">Schedule time</label>
                    <input class="form-control" type="time" id="schedule_time" name="schedule_time" value="{{ old('schedule_time') }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Review recipients</button>
                    <a class="btn btn-outline-secondary" href="{{ route('smartNotifications') }}">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
