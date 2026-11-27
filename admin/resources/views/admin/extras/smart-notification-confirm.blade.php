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
            <div class="col-md-4"><p class="text-muted mb-1">Total users</p><h4 class="mb-0" data-total-users="{{ (int) $draft['summary']['total_users'] }}">{{ number_format($draft['summary']['total_users']) }}</h4></div>
            <div class="col-md-4"><p class="text-muted mb-1">Consent approved</p><h4 class="mb-0" data-consent-approved="{{ (int) $draft['summary']['consent_approved'] }}">{{ number_format($draft['summary']['consent_approved']) }}</h4></div>
            <div class="col-md-4"><p class="text-muted mb-1">Consent not approved</p><h4 class="mb-0" data-consent-missing="{{ (int) $draft['summary']['consent_not_approved'] }}">{{ number_format($draft['summary']['consent_not_approved']) }}</h4></div>
            <div class="col-md-4"><p class="text-muted mb-1">No device token</p><h4 class="mb-0" data-no-token="{{ (int) $draft['summary']['no_token'] }}">{{ number_format($draft['summary']['no_token']) }}</h4></div>
            <div class="col-md-4"><p class="text-muted mb-1">Estimated eligible recipients</p><h4 class="mb-0" data-eligible="{{ (int) $draft['summary']['final_recipients'] }}">{{ number_format($draft['summary']['final_recipients']) }}</h4></div>
        </div>
        <div class="alert alert-warning mt-3 mb-0">
            Only users who have explicitly enabled promotional notifications will receive this campaign.
            Users without a token are skipped. There is no override for consent. This does not change wallet or recharge status.
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
