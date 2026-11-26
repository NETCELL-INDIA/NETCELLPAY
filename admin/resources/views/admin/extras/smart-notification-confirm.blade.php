@extends('layouts.master')
@section('title') Confirm campaign @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') Confirm campaign @endslot
@endcomponent

<div class="card" style="border-top: 3px solid #6D28D9;">
    <div class="card-header"><h4 class="card-title mb-0">Check this before it is saved</h4></div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-4"><p class="text-muted mb-1">Title</p><h5 class="mb-0">{{ $draft['input']['title'] }}</h5></div>
            <div class="col-md-4"><p class="text-muted mb-1">Category</p><h5 class="mb-0">{{ $categoryLabel }}</h5></div>
            <div class="col-md-4"><p class="text-muted mb-1">Priority</p><h5 class="mb-0">{{ $draft['input']['priority'] }}</h5></div>
            <div class="col-12"><p class="text-muted mb-1">Message</p><p class="mb-0">{{ $draft['input']['message'] }}</p></div>
            <div class="col-md-4"><p class="text-muted mb-1">Audience</p><h5 class="mb-0">{{ $audience }}</h5>@if($userLabel)<p class="mb-0">{{ $userLabel }}</p>@endif</div>
            <div class="col-md-4"><p class="text-muted mb-1">Eligible recipients</p><h4 class="mb-0" data-eligible="{{ (int) $draft['summary']['eligible'] }}">{{ number_format($draft['summary']['eligible']) }}</h4></div>
            <div class="col-md-4"><p class="text-muted mb-1">With an FCM token</p><h4 class="mb-0">{{ number_format($draft['summary']['with_token']) }}</h4></div>
        </div>
        <div class="alert alert-warning mt-3 mb-0">
            @if($consentApplies)
            General, Offers, and Announcements go only to users who allowed promotional notifications.
            @else
            This category is transactional or security. Promotional consent does not block it.
            @endif
            Users without a token are skipped. This does not change wallet or recharge status.
            @if($draft['input']['send_mode'] === 'schedule')
            Saving a schedule does not send it. The clock job that sends scheduled campaigns is not part of this screen.
            @else
            Send now calls the existing user-app campaign command. If that queue is sync, delivery runs in this request.
            @endif
        </div>
        <form method="post" action="{{ route('smartNotificationConfirm') }}" class="mt-3">
            @csrf
            <input type="hidden" name="campaign_uuid" value="{{ $draft['uuid'] }}">
            <button class="btn btn-primary" type="submit" @disabled($draft['summary']['eligible'] < 1)>
                {{ $draft['input']['send_mode'] === 'schedule' ? 'Save schedule' : 'Send campaign' }}
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('smartNotificationCreate') }}">Back</a>
        </form>
    </div>
</div>
@endsection
