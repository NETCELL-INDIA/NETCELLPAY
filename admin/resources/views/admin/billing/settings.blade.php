@extends('layouts.master')
@section('title') GST Settings @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Billing @endslot
@slot('title') GST and company billing settings @endslot
@endcomponent
@if(session('billing_message'))<div class="alert alert-info">{{ session('billing_message') }}</div>@endif
<div class="alert alert-warning">GST is not calculated on the wallet deposit. An invoice is created only when GST registration is ON, the service-fee invoice switch is ON, Add Money charge is ON, and the configured rate is above zero. The invoice taxable value is that service fee.</div>
<div class="card"><div class="card-body">
<form method="post" action="{{ url('admin/billing/settings') }}" class="row g-3">
    @csrf
    <div class="col-md-6"><label class="form-label">Legal name</label><input name="company_legal_name" class="form-control" value="{{ $settings->company_legal_name }}"></div>
    <div class="col-md-6"><label class="form-label">GSTIN</label><input name="gstin" class="form-control" maxlength="20" value="{{ $settings->gstin }}"></div>
    <div class="col-md-12"><label class="form-label">Registered address</label><input name="company_address" class="form-control" value="{{ $settings->company_address }}"></div>
    <div class="col-md-4"><label class="form-label">State</label><input name="state_name" class="form-control" value="{{ $settings->state_name }}"></div>
    <div class="col-md-2"><label class="form-label">State code</label><input name="state_code" class="form-control" value="{{ $settings->state_code }}"></div>
    <div class="col-md-3"><label class="form-label">Invoice prefix</label><input name="invoice_prefix" class="form-control" value="{{ $settings->invoice_prefix }}"></div>
    <div class="col-md-3"><label class="form-label">Receipt prefix</label><input name="receipt_prefix" class="form-control" value="{{ $settings->receipt_prefix }}"></div>
    <div class="col-md-3"><label class="form-label">Default SAC</label><input name="default_sac" class="form-control" value="{{ $settings->default_sac }}"></div>
    <div class="col-md-3"><label class="form-label">GST rate %</label><input name="default_gst_rate" type="number" step="0.01" min="0" max="100" class="form-control" value="{{ $settings->default_gst_rate }}"></div>
    <div class="col-md-2"><label class="form-label">CGST %</label><input name="cgst_rate" type="number" step="0.01" min="0" max="100" class="form-control" value="{{ $settings->cgst_rate }}"></div>
    <div class="col-md-2"><label class="form-label">SGST %</label><input name="sgst_rate" type="number" step="0.01" min="0" max="100" class="form-control" value="{{ $settings->sgst_rate }}"></div>
    <div class="col-md-2"><label class="form-label">IGST %</label><input name="igst_rate" type="number" step="0.01" min="0" max="100" class="form-control" value="{{ $settings->igst_rate }}"></div>
    <div class="col-md-3"><label class="form-label">Price mode</label>
        <select name="tax_mode" class="form-select">
            <option value="exclusive" {{ $settings->tax_mode === 'exclusive' ? 'selected' : '' }}>Tax exclusive</option>
            <option value="inclusive" {{ $settings->tax_mode === 'inclusive' ? 'selected' : '' }}>Tax inclusive</option>
        </select>
    </div>
    <div class="col-md-6"><label class="form-label">Signatory</label><input name="signatory" class="form-control" value="{{ $settings->signatory }}"></div>
    <div class="col-md-12"><label class="form-label">Terms</label><textarea name="terms" class="form-control" rows="3">{{ $settings->terms }}</textarea></div>
    <div class="col-md-12">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_gst_registered" value="1" {{ (int) $settings->is_gst_registered === 1 ? 'checked' : '' }}><label class="form-check-label">GST registered</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="invoice_service_fee" value="1" {{ (int) $settings->invoice_service_fee === 1 ? 'checked' : '' }}><label class="form-check-label">Raise a GST invoice on the Add Money service fee</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="reverse_charge" value="1" {{ (int) $settings->reverse_charge === 1 ? 'checked' : '' }}><label class="form-check-label">Reverse charge applicable</label></div>
    </div>
    <div class="col-md-12"><button class="btn btn-primary">Save</button></div>
</form>
</div></div>
@endsection
