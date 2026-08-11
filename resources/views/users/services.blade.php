@extends('layouts.master')
@section('title') All Services @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Services @endslot
@slot('title') All Services @endslot
@endcomponent

<div class="row g-3">
    @foreach(config('recharge_services.recharge', []) as $svc)
    <div class="col-xl-3 col-md-4 col-sm-6">
        <a href="{{ url($svc['route']) }}" class="text-decoration-none text-dark">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center py-4">
                    <img class="rounded-circle mb-3" src="{{ URL::asset($svc['icon']) }}" alt="{{ $svc['name'] }}" style="width:72px;height:72px;object-fit:cover" onerror="this.src='{{ URL::asset('service_icon/mobile_1.png') }}'">
                    <h5 class="mb-0">{{ $svc['name'] }}</h5>
                </div>
            </div>
        </a>
    </div>
    @endforeach

    <div class="col-xl-3 col-md-4 col-sm-6">
        <a href="{{ url('users/services/bill-payments') }}" class="text-decoration-none text-dark">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center py-4">
                    <img class="rounded-circle mb-3" src="{{ URL::asset('service_icon/flash.png') }}" alt="Bill Payments" style="width:72px;height:72px;object-fit:cover">
                    <h5 class="mb-0">Bill Payments (BBPS)</h5>
                    <small class="text-muted">Electricity, Water, Gas & more</small>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mt-2">
    @foreach(config('recharge_services.bbps', []) as $cat)
    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
        <a href="{{ url('users/services/bill-payments?id=' . $cat['id'] . '&name=' . urlencode($cat['name'])) }}" class="text-decoration-none text-dark">
            <div class="card h-100 border">
                <div class="card-body text-center p-3">
                    <img src="{{ URL::asset($cat['logo']) }}" alt="{{ $cat['name'] }}" style="height:48px" onerror="this.src='{{ URL::asset('service_logo/10.png') }}'">
                    <h6 class="mt-2 mb-0 small">{{ $cat['name'] }}</h6>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection
