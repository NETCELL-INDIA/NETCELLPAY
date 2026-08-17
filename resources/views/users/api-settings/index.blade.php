@extends('layouts.master')
@section('title')
API Settings
@endsection

@section('content')
@php
    $apiKey = $user->api_key ?: 'YOUR_API_KEY';
    $rechargeSample = $baseUrl . '/Recharge?api_key=' . urlencode($apiKey) . '&number=9876543210&amount=199&provider_id=1&service_id=1&request_order_id=REQ' . date('YmdHis');
    $statusSample = $baseUrl . '/RechargeStatus?api_key=' . urlencode($apiKey) . '&request_order_id=REQ' . date('YmdHis');
    $complaintSample = $baseUrl . '/RechargeComplaint?api_key=' . urlencode($apiKey) . '&order_id=RC20260817111918870042258&subject=Wrong%20recharge';
@endphp
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">API Partner Integration</h4>
                <p class="text-muted mb-0">GET query-string APIs. PIN is not required. Send a unique <code>request_order_id</code> for every recharge.</p>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ url('users/api-settings/save') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="api_key" value="{{ $user->api_key }}" readonly>
                                <button type="button" class="btn btn-outline-success" id="generate_api_key_btn">Generate Key</button>
                            </div>
                            <small class="text-muted">Keep this secret. Generating a new key disables the old one immediately.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Recharge Callback URL</label>
                            <input type="text" name="callback_url" class="form-control" value="{{ old('callback_url', $user->callback_url) }}" placeholder="https://your-site.com/recharge-callback">
                            <small class="text-muted">We call this as GET: <code>?request_order_id=&amp;status=&amp;amount=&amp;order_id=&amp;operator_id=</code></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Complaint Callback URL</label>
                            <input type="text" name="complaint_callback_url" class="form-control" value="{{ old('complaint_callback_url', $user->complaint_callback_url) }}" placeholder="https://your-site.com/complaint-callback">
                            <small class="text-muted">We call this as GET: <code>?status=&amp;decision_remark=&amp;request_id=&amp;decision_date=</code></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">IP Whitelist</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $user->ip_address) }}" placeholder="203.0.113.10, 203.0.113.11">
                            <small class="text-muted">Local: all IPs allowed. Production: blank / <code>0.0.0.0</code> / <code>*</code> allows all. Otherwise only listed IPs.</small>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save API Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">API Documents</h4>
            </div>
            <div class="card-body">
                <p class="mb-3"><strong>Base URL:</strong> <code>{{ $baseUrl }}</code> &nbsp; <strong>Method:</strong> GET</p>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>API</th>
                                <th>URL</th>
                                <th>Required parameters</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Recharge</td>
                                <td><code>{{ $baseUrl }}/Recharge</code></td>
                                <td>
                                    <code>api_key</code>, <code>number</code>, <code>amount</code>, <code>provider_id</code>, <code>service_id</code>, <code>request_order_id</code><br>
                                    Optional: <code>state_id</code> (default 40)
                                </td>
                            </tr>
                            <tr>
                                <td>Recharge Status</td>
                                <td><code>{{ $baseUrl }}/RechargeStatus</code></td>
                                <td><code>api_key</code>, <code>request_order_id</code> (your ID, not our <code>order_id</code>)</td>
                            </tr>
                            <tr>
                                <td>Recharge Complaint</td>
                                <td><code>{{ $baseUrl }}/RechargeComplaint</code></td>
                                <td><code>api_key</code>, <code>order_id</code> (our RC… ID), <code>subject</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h5 class="mb-2">Sample requests</h5>
                <p class="mb-1"><strong>Recharge</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $rechargeSample }}</pre>
                <p class="mb-1"><strong>Status</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $statusSample }}</pre>
                <p class="mb-1"><strong>Complaint</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $complaintSample }}</pre>

                <h5 class="mb-2">Recharge response</h5>
                <pre class="bg-light p-2 small mb-3">{
  "number": "9876543210",
  "status": "Success | Pending | Failed",
  "amount": 199,
  "order_id": "RC20260817111918870042258",
  "request_order_id": "REQ20260817111918",
  "operator_id": "OP123",
  "type": "success",
  "remark": "...",
  "message": "...",
  "commission": 0
}</pre>
                <p class="text-muted">If <code>type</code> is <code>error</code>, check <code>message</code> (invalid API key, duplicate <code>request_order_id</code>, IP not allowed, wallet, operator, etc.).</p>

                <h5 class="mb-2">Status / receipt response</h5>
                <pre class="bg-light p-2 small mb-3">{
  "type": "success",
  "message": "Get sucessfuly",
  "provider_name": "Airtel",
  "data": {
    "order_id": "RC...",
    "request_order_id": "REQ...",
    "status": "Success",
    "number": "9876543210",
    "amount": "199.00",
    "operator_id": "OP123"
  }
}</pre>

                <h5 class="mb-2">Notes</h5>
                <ul class="mb-0">
                    <li>Do not send <code>pin</code>.</li>
                    <li><code>request_order_id</code> = your unique txn id. Reuse returns “request order id already exists.”</li>
                    <li><code>order_id</code> = our system id (starts with RC…). Use this for complaints.</li>
                    <li><code>service_id</code>: 1 = Mobile, 2 = DTH, 4 = Postpaid.</li>
                    <li>Use <code>provider_id</code> from the operator table below.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Operator IDs (<code>provider_id</code>)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>provider_id</th>
                                <th>Operator</th>
                                <th>service_id</th>
                                <th>Service</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->provider_name }}</td>
                                <td>{{ $p->service_id }}</td>
                                <td>{{ $p->service_name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No active operators found. Contact admin.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('#generate_api_key_btn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Generating...');
        $.ajax({
            url: '{{ url('users/api-settings/generate-key') }}',
            method: 'post',
            data: { _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function (res) {
                if (res.type === 'success' && res.api_key) {
                    $('#api_key').val(res.api_key);
                    if (typeof Error_Msg === 'function') {
                        Error_Msg('Success', res.message, 'success');
                    } else {
                        alert(res.message);
                    }
                    window.location.reload();
                } else if (typeof Error_Msg === 'function') {
                    Error_Msg('Error', res.message || 'Could not generate key', 'error');
                }
            },
            error: function () {
                if (typeof Error_Msg === 'function') {
                    Error_Msg('Error', 'Could not generate key', 'error');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Generate Key');
            }
        });
    });
</script>
@endsection
