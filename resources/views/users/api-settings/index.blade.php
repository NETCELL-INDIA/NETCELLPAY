@extends('layouts.master')
@section('title')
API Settings
@endsection

@section('content')
@php
    $apiKey = $user->api_key ?: 'YOUR_API_KEY';
    $balanceSample = $baseUrl . '/Balance?api_key=' . urlencode($apiKey);
    $rechargeSample = $baseUrl . '/Recharge?api_key=' . urlencode($apiKey) . '&number=9876543210&amount=199&provider_id=1&service_id=1&request_order_id=REQ' . date('YmdHis');
    $statusSample = $baseUrl . '/RechargeStatus?api_key=' . urlencode($apiKey) . '&request_order_id=REQ' . date('YmdHis');
    $complaintSample = $baseUrl . '/RechargeComplaint?api_key=' . urlencode($apiKey) . '&order_id=RC20260817111918870042258&subject=Wrong%20recharge';
    $billFetchSample = $baseUrl . '/BillFetch?api_key=' . urlencode($apiKey) . '&provider_id=10&service_id=5&number=1234567890';
    $billPaySample = $baseUrl . '/BillPay?api_key=' . urlencode($apiKey) . '&number=1234567890&amount=500&provider_id=10&service_id=5&request_order_id=BILL' . date('YmdHis');
    $billStatusSample = $baseUrl . '/BillStatus?api_key=' . urlencode($apiKey) . '&request_order_id=BILL' . date('YmdHis');
    $savedIp = trim((string) ($user->ip_address ?? ''));
    $ipOpen = ($savedIp === '' || in_array($savedIp, ['0.0.0.0', '1.1.1.1'], true));
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
                            <small class="text-muted">We call this as GET: <code>?request_order_id=&amp;status=&amp;amount=&amp;order_id=&amp;operator_id=&amp;balance=</code></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Complaint Callback URL</label>
                            <input type="text" name="complaint_callback_url" class="form-control" value="{{ old('complaint_callback_url', $user->complaint_callback_url) }}" placeholder="https://your-site.com/complaint-callback">
                            <small class="text-muted">We call this as GET: <code>?status=&amp;decision_remark=&amp;request_id=&amp;decision_date=</code></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">IP Whitelist</label>
                            @if($ipOpen)
                                <div class="alert alert-warning py-2">API calls are blocked until you save a server IP. Blank, <code>0.0.0.0</code> and <code>1.1.1.1</code> are not allowed.</div>
                            @endif
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $user->ip_address) }}" placeholder="203.0.113.10, 203.0.113.11">
                            <small class="text-muted d-block mt-1">Browser IP right now: <strong>{{ $clientIp }}</strong>. The API checks the server that calls us, which may be a different IP.</small>
                            <small class="text-muted d-block">Blank is blocked. Enter one IP, or several separated by commas. Use <code>*</code> only if every IP should be allowed.</small>
                            @if(($rejectedIps ?? collect())->count())
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Rejected IP</th>
                                                <th>Reason</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rejectedIps as $ipRow)
                                            <tr>
                                                <td><strong>{{ $ipRow->ip_address }}</strong></td>
                                                <td>{{ $ipRow->reason }}</td>
                                                <td>{{ $ipRow->created_at ? \Carbon\Carbon::parse($ipRow->created_at)->format('d-m-Y h:i A') : '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted">Copy the rejected server IP into the box above and save. The same IP is logged at most once every 15 minutes.</small>
                            @endif
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
                <h4 class="card-title mb-0">Callback log</h4>
                <p class="text-muted mb-0">Last 25 API recharges and bill pays. This list does not move wallet money.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Your ID</th>
                                <th>Our order</th>
                                <th>Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Callback</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($callbackLogs as $log)
                                @php
                                    $sent = (int) ($log->callback_status ?? 0) === 1;
                                    $callbackUrl = trim((string) ($log->api_partner_call_back_url ?? ''));
                                    $callbackResponse = trim(strip_tags((string) ($log->api_partner_callback_response ?? '')));
                                    if (strlen($callbackResponse) > 80) {
                                        $callbackResponse = substr($callbackResponse, 0, 80) . '...';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d-m-Y h:i A') : '-' }}</td>
                                    <td>{{ $log->request_order_id ?: '-' }}</td>
                                    <td>{{ $log->order_id ?: '-' }}</td>
                                    <td>{{ $log->number ?: '-' }}</td>
                                    <td>{{ $log->total_amount ?: '-' }}</td>
                                    <td>{{ $log->status ?: '-' }}</td>
                                    <td>
                                        @if($sent && $callbackUrl !== '')
                                            Sent
                                        @elseif($sent)
                                            Not delivered
                                        @else
                                            Waiting
                                        @endif
                                    </td>
                                    <td>{{ $callbackResponse !== '' ? $callbackResponse : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No API transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Complaint callback log</h4>
                <p class="text-muted mb-0">Last 25 API complaints. Sent means your complaint callback URL returned success. This list does not move wallet money.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Request ID</th>
                                <th>Order</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Callback</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($complaintLogs ?? []) as $log)
                                <tr>
                                    <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d-m-Y h:i A') : '-' }}</td>
                                    <td>{{ $log->request_id ?: '-' }}</td>
                                    <td>{{ $log->order_id ?: '-' }}</td>
                                    <td>{{ $log->subject ?: '-' }}</td>
                                    <td>{{ $log->status ?: '-' }}</td>
                                    <td>{{ (int) ($log->callback_status ?? 0) === 1 ? 'Sent' : 'Waiting' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No API complaints yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                                <td>Balance</td>
                                <td><code>{{ $baseUrl }}/Balance</code></td>
                                <td><code>api_key</code></td>
                            </tr>
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
                            <tr>
                                <td>Bill Fetch</td>
                                <td><code>{{ $baseUrl }}/BillFetch</code></td>
                                <td>
                                    <code>api_key</code>, <code>provider_id</code>, <code>service_id</code>, <code>number</code> (customer account)<br>
                                    Optional extra biller fields: <code>filed_2</code>, <code>filed_3</code>
                                </td>
                            </tr>
                            <tr>
                                <td>Bill Pay</td>
                                <td><code>{{ $baseUrl }}/BillPay</code></td>
                                <td>
                                    <code>api_key</code>, <code>number</code>, <code>amount</code>, <code>provider_id</code>, <code>service_id</code>, <code>request_order_id</code><br>
                                    Same wallet rules as recharge. Send a new <code>request_order_id</code> every time.
                                </td>
                            </tr>
                            <tr>
                                <td>Bill Status</td>
                                <td><code>{{ $baseUrl }}/BillStatus</code></td>
                                <td><code>api_key</code>, <code>request_order_id</code> (your ID from Bill Pay, not our <code>order_id</code>)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h5 class="mb-2">Sample requests</h5>
                <p class="mb-1"><strong>Balance</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $balanceSample }}</pre>
                <p class="mb-1"><strong>Recharge</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $rechargeSample }}</pre>
                <p class="mb-1"><strong>Status</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $statusSample }}</pre>
                <p class="mb-1"><strong>Complaint</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $complaintSample }}</pre>
                <p class="mb-1"><strong>Bill Fetch</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $billFetchSample }}</pre>
                <p class="mb-1"><strong>Bill Pay</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $billPaySample }}</pre>
                <p class="mb-1"><strong>Bill Status</strong></p>
                <pre class="bg-light p-2 small mb-3" style="white-space:pre-wrap;">{{ $billStatusSample }}</pre>

                <h5 class="mb-2">Balance response</h5>
                <pre class="bg-light p-2 small mb-3">{
  "type": "success",
  "message": "Get sucessfuly",
  "balance": 1250.50
}</pre>

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
  "commission": 0,
  "balance": 1250.50
}</pre>
                <p class="text-muted">If <code>type</code> is <code>error</code>, check <code>message</code> (invalid API key, duplicate <code>request_order_id</code>, IP not allowed, wallet, operator, etc.).</p>

                <h5 class="mb-2">Status / receipt response</h5>
                <pre class="bg-light p-2 small mb-3">{
  "type": "success",
  "message": "Get sucessfuly",
  "provider_name": "Airtel",
  "balance": 1250.50,
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
                    <li>Balance URL, Recharge, and Recharge Status all return current wallet as <code>balance</code>.</li>
                    <li><code>request_order_id</code> = your unique txn id. Reuse returns “request order id already exists.”</li>
                    <li><code>order_id</code> = our system id (starts with RC…). Use this for complaints.</li>
                    <li><code>service_id</code>: 1 = Mobile, 2 = DTH, 4 = Postpaid. Other bill operators use the <code>service_id</code> in the bill table below.</li>
                    <li>Bill Fetch does not debit the wallet. Bill Pay does. Bill Status uses your <code>request_order_id</code>.</li>
                    <li>Use <code>provider_id</code> from the operator tables below. Sample bill IDs in the URLs above are examples only.</li>
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

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Bill operator IDs</h4>
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
                            @forelse($billProviders as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->provider_name }}</td>
                                <td>{{ $p->service_id }}</td>
                                <td>{{ $p->service_name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No separate bill operators are active. Use the provider_id and service_id admin has enabled for BBPS.</td>
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
