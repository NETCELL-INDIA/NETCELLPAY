@extends('layouts.master')
@section('title') {{ $pageTitle }} @endsection
@section('css')
<style>
    .ss-layout { display: grid; grid-template-columns: 250px 1fr; gap: 16px; align-items: start; }
    .ss-nav {
        background: #fff;
        border: 1px solid #e9ebec;
        border-radius: 10px;
        overflow: hidden;
    }
    .ss-nav h5 {
        margin: 0;
        padding: 14px 16px;
        background: #405189;
        color: #fff;
        font-size: .85rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .ss-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        color: #495057;
        text-decoration: none;
        font-size: .84rem;
        font-weight: 600;
        border-bottom: 1px solid #f1f3f5;
    }
    .ss-nav a i { font-size: 1.05rem; color: #878a99; }
    .ss-nav a:hover { background: #f8f9fa; color: #405189; }
    .ss-nav a.active {
        background: #e8f8ee;
        color: #146c43;
    }
    .ss-nav a.active i { color: #22c55e; }
    .ss-card .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ss-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 20px;
    }
    .ss-field label {
        font-size: .78rem;
        font-weight: 700;
        color: #405189;
        margin-bottom: 6px;
        display: block;
    }
    .ss-input {
        display: flex;
        align-items: center;
        border: 1px solid #d5deea;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }
    .ss-input i {
        width: 38px;
        text-align: center;
        color: #405189;
        font-size: 1.05rem;
    }
    .ss-input .form-control {
        border: 0 !important;
        box-shadow: none !important;
        min-height: 40px;
    }
    .ss-switch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e9ebec;
        border-radius: 10px;
        padding: 12px 14px;
        background: #f8fafc;
        margin-top: 8px;
    }
    .ss-switch span { font-weight: 700; color: #405189; }
    @media (max-width: 992px) {
        .ss-layout { grid-template-columns: 1fr; }
        .ss-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') System @endslot
@slot('title') System Setting @endslot
@endcomponent

<div class="ss-layout">
    <div class="ss-nav">
        <h5>System Setting</h5>
        <a href="{{ URL::asset('admin/system-settings/system') }}" class="{{ $page === 'system' ? 'active' : '' }}">
            <i class="ri-settings-3-line"></i> System setting
        </a>
        <a href="{{ URL::asset('admin/system-settings/service') }}" class="{{ $page === 'service' ? 'active' : '' }}">
            <i class="ri-settings-4-line"></i> Service setting
        </a>
        <a href="{{ URL::asset('admin/system-settings/account-activation') }}" class="{{ $page === 'account-activation' ? 'active' : '' }}">
            <i class="ri-money-rupee-circle-line"></i> Account activation charge
        </a>
        <a href="{{ URL::asset('admin/system-settings/add-money-charge') }}" class="{{ $page === 'add-money-charge' ? 'active' : '' }}">
            <i class="ri-settings-5-line"></i> Add money charge setting
        </a>
        <a href="{{ URL::asset('admin/system-settings/payment-gateway') }}" class="{{ $page === 'payment-gateway' ? 'active' : '' }}">
            <i class="ri-bank-card-line"></i> Payment gateway setting
        </a>
        <a href="{{ URL::asset('admin/system-settings/min-add-money') }}" class="{{ $page === 'min-add-money' ? 'active' : '' }}">
            <i class="ri-currency-line"></i> Minimum add money &amp; signup
        </a>
        <a href="{{ URL::asset('admin/system-settings/pusher') }}" class="{{ $page === 'pusher' ? 'active' : '' }}">
            <i class="ri-broadcast-line"></i> Pusher setting
        </a>
    </div>

    <div class="card ss-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ strtoupper($pageTitle) }}</h4>
            <button type="button" class="btn btn-success" id="ssSaveBtn">Save Setting</button>
        </div>
        <div class="card-body">
            <form id="ssForm">
                @csrf
                <input type="hidden" name="page" value="{{ $page }}">

                @if($page === 'system')
                    <div class="ss-grid">
                        @include('admin.system-settings._field', ['name' => 'fund_interval_minute', 'label' => 'Fund Interval(Minute)', 'icon' => 'ri-time-line', 'value' => $settings['fund_interval_minute']])
                        @include('admin.system-settings._field', ['name' => 'interval_recharge_minute', 'label' => 'Interval Recharge(Minute)', 'icon' => 'ri-time-line', 'value' => $settings['interval_recharge_minute']])
                        @include('admin.system-settings._field', ['name' => 'min_fund_transfer', 'label' => 'Minimum Fund Transfer', 'icon' => 'ri-money-rupee-circle-line', 'value' => $settings['min_fund_transfer']])
                        @include('admin.system-settings._field', ['name' => 'max_fund_transfer', 'label' => 'Maximum Fund Transfer', 'icon' => 'ri-money-rupee-circle-line', 'value' => $settings['max_fund_transfer']])
                        @include('admin.system-settings._field', ['name' => 'balance_alert_below', 'label' => 'Balance Alert Below Then', 'icon' => 'ri-alarm-warning-line', 'value' => $settings['balance_alert_below']])
                        @include('admin.system-settings._field', ['name' => 'referral_amount', 'label' => 'Referral Amount', 'icon' => 'ri-gift-line', 'value' => $settings['referral_amount']])
                        @include('admin.system-settings._field', ['name' => 'wrong_login_attempt', 'label' => 'Wrong Login Attempt', 'icon' => 'ri-error-warning-line', 'value' => $settings['wrong_login_attempt']])
                        @include('admin.system-settings._field', ['name' => 'max_payout_account', 'label' => 'Add maximum payout account', 'icon' => 'ri-bank-line', 'value' => $settings['max_payout_account']])
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <div class="ss-switch">
                                <span><i class="ri-close-circle-line text-danger me-1"></i> Stop All Transactions</span>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="stop_all_transactions" value="0">
                                    <input class="form-check-input" type="checkbox" name="stop_all_transactions" value="1" {{ $settings['stop_all_transactions'] == '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ss-switch">
                                <span><i class="ri-smartphone-line text-warning me-1"></i> App Without Login</span>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="app_without_login" value="0">
                                    <input class="form-check-input" type="checkbox" name="app_without_login" value="1" {{ $settings['app_without_login'] == '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($page === 'service')
                    <p class="text-muted mb-3">Turn services ON / OFF for users.</p>
                    <div class="row g-3">
                        @forelse($services as $svc)
                            <div class="col-md-6">
                                <div class="ss-switch">
                                    <span>{{ strtoupper($svc->service_name) }}</span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="service_status[]" value="{{ $svc->id }}" {{ (int)$svc->status === 1 ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">No services found.</div>
                        @endforelse
                    </div>
                @elseif($page === 'account-activation')
                    <div class="ss-grid">
                        @include('admin.system-settings._field', ['name' => 'activation_charge', 'label' => 'Account Activation Charge', 'icon' => 'ri-money-rupee-circle-line', 'value' => $settings['activation_charge']])
                    </div>
                    <div class="ss-switch mt-3">
                        <span>Enable activation charge</span>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="activation_charge_status" value="0">
                            <input class="form-check-input" type="checkbox" name="activation_charge_status" value="1" {{ $settings['activation_charge_status'] == '1' ? 'checked' : '' }}>
                        </div>
                    </div>
                @elseif($page === 'add-money-charge')
                    <div class="ss-grid">
                        <div class="ss-field">
                            <label>Charge Type</label>
                            <select class="form-select" name="add_money_charge_type">
                                <option value="fixed" {{ $settings['add_money_charge_type'] === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="percent" {{ $settings['add_money_charge_type'] === 'percent' ? 'selected' : '' }}>Percent</option>
                            </select>
                        </div>
                        @include('admin.system-settings._field', ['name' => 'add_money_charge_value', 'label' => 'Charge Value', 'icon' => 'ri-percent-line', 'value' => $settings['add_money_charge_value']])
                    </div>
                    <div class="ss-switch mt-3">
                        <span>Enable add money charge</span>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="add_money_charge_status" value="0">
                            <input class="form-check-input" type="checkbox" name="add_money_charge_status" value="1" {{ $settings['add_money_charge_status'] == '1' ? 'checked' : '' }}>
                        </div>
                    </div>
                @elseif($page === 'payment-gateway')
                    <div class="ss-grid">
                        <div class="ss-field">
                            <label>Payment Gateway UPI</label>
                            <select class="form-select" name="payment_gateway">
                                <option value="1" {{ (int)($company->payment_gateway ?? 0) === 1 ? 'selected' : '' }}>ON</option>
                                <option value="0" {{ (int)($company->payment_gateway ?? 0) === 0 ? 'selected' : '' }}>OFF</option>
                            </select>
                        </div>
                        @include('admin.system-settings._field', ['name' => 'payment_gateway_min', 'label' => 'UPI Min', 'icon' => 'ri-arrow-down-line', 'value' => $company->payment_gateway_min ?? ''])
                        @include('admin.system-settings._field', ['name' => 'payment_gateway_max', 'label' => 'UPI Max', 'icon' => 'ri-arrow-up-line', 'value' => $company->payment_gateway_max ?? ''])
                        @include('admin.system-settings._field', ['name' => 'payment_gateway_key', 'label' => 'UPI Key', 'icon' => 'ri-key-2-line', 'value' => $company->payment_gateway_key ?? ''])
                        <div class="ss-field">
                            <label>Payment Gateway QR</label>
                            <select class="form-select" name="payment_gateway2">
                                <option value="1" {{ (int)($company->payment_gateway2 ?? 0) === 1 ? 'selected' : '' }}>ON</option>
                                <option value="0" {{ (int)($company->payment_gateway2 ?? 0) === 0 ? 'selected' : '' }}>OFF</option>
                            </select>
                        </div>
                        @include('admin.system-settings._field', ['name' => 'payment_gateway2_min', 'label' => 'QR Min', 'icon' => 'ri-arrow-down-line', 'value' => $company->payment_gateway2_min ?? ''])
                        @include('admin.system-settings._field', ['name' => 'payment_gateway2_max', 'label' => 'QR Max', 'icon' => 'ri-arrow-up-line', 'value' => $company->payment_gateway2_max ?? ''])
                        @include('admin.system-settings._field', ['name' => 'payment_gateway2_key', 'label' => 'QR Key', 'icon' => 'ri-key-2-line', 'value' => $company->payment_gateway2_key ?? ''])
                    </div>
                @elseif($page === 'min-add-money')
                    <div class="ss-grid">
                        @include('admin.system-settings._field', ['name' => 'min_add_money', 'label' => 'Minimum Add Money', 'icon' => 'ri-money-rupee-circle-line', 'value' => $settings['min_add_money']])
                        @include('admin.system-settings._field', ['name' => 'min_signup_amount', 'label' => 'Minimum Signup Amount', 'icon' => 'ri-user-add-line', 'value' => $settings['min_signup_amount']])
                    </div>
                @elseif($page === 'pusher')
                    <div class="ss-grid">
                        @include('admin.system-settings._field', ['name' => 'pusher_app_id', 'label' => 'Pusher App ID', 'icon' => 'ri-fingerprint-line', 'value' => $settings['pusher_app_id']])
                        @include('admin.system-settings._field', ['name' => 'pusher_key', 'label' => 'Pusher Key', 'icon' => 'ri-key-2-line', 'value' => $settings['pusher_key']])
                        @include('admin.system-settings._field', ['name' => 'pusher_secret', 'label' => 'Pusher Secret', 'icon' => 'ri-lock-password-line', 'value' => $settings['pusher_secret']])
                        @include('admin.system-settings._field', ['name' => 'pusher_cluster', 'label' => 'Pusher Cluster', 'icon' => 'ri-global-line', 'value' => $settings['pusher_cluster']])
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$('#ssSaveBtn').on('click', function () {
    var btn = $(this);
    btn.prop('disabled', true).text('Saving...');
    $.ajax({
        url: '{{ route("systemSettingSave") }}',
        method: 'post',
        data: $('#ssForm').serialize(),
        success: function (data) {
            btn.prop('disabled', false).text('Save Setting');
            Swal.fire({ title: data.type === 'success' ? 'Success' : 'Error', text: data.message, icon: data.type === 'success' ? 'success' : 'error' });
        },
        error: function () {
            btn.prop('disabled', false).text('Save Setting');
            Swal.fire({ title: 'Error', text: 'Unable to save setting', icon: 'error' });
        }
    });
});
</script>
@endsection
