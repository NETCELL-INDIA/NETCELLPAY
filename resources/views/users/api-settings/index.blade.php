@extends('layouts.master')
@section('title')
API Settings
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">API Configuration</h4>
                    <p class="text-muted mb-0">Manage recharge API credentials from the admin panel.</p>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ url('users/api-settings/save') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Provider Name</label>
                            <input type="text" name="provider_name" class="form-control" value="{{ old('provider_name', $setting->provider_name ?? 'Recharge API') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Name</label>
                            <input type="text" name="api_name" class="form-control" value="{{ old('api_name', $setting->api_name ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API URL</label>
                            <input type="text" name="api_url" class="form-control" value="{{ old('api_url', $setting->api_url ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Key</label>
                            <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $setting->api_key ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Username</label>
                            <input type="text" name="api_username" class="form-control" value="{{ old('api_username', $setting->api_username ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Password</label>
                            <input type="password" name="api_password" class="form-control" value="{{ old('api_password', $setting->api_password ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $setting->notes ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ !empty($setting->status) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Enable API</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-recharge">Save API Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
