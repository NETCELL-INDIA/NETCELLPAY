@extends('layouts.master')

@section('title') Plan/Circle/DTH Info Fetch API Settings @endsection

@section('content')

<style>
    .plan-api-page-title { color: #0d6efd; font-size: 1.35rem; font-weight: 700; margin-bottom: 0.35rem; }
    .plan-api-hint { color: #6c757d; font-size: 13px; margin-bottom: 1rem; }
    .plan-svc-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 0.85rem;
        background: #fff;
        overflow: hidden;
    }
    .plan-svc-card.routing { border-left: 4px solid #198754; }
    .plan-svc-card.disabled-svc { opacity: 0.72; background: #f8f9fa; }
    .plan-svc-head {
        display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;
        padding: 0.65rem 0.85rem; background: #f8f9fa; border-bottom: 1px solid #e9ecef;
    }
    .plan-svc-title { font-weight: 700; min-width: 220px; flex: 1; }
    .plan-svc-meta { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .plan-svc-meta .form-control, .plan-svc-meta .form-select { font-size: 13px; min-width: 90px; }
    .plan-api-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(180px, 1fr));
        gap: 0.65rem;
        padding: 0.75rem;
    }
    @media (max-width: 1200px) {
        .plan-api-grid { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
    }
    @media (max-width: 700px) {
        .plan-api-grid { grid-template-columns: 1fr; }
    }
    .plan-api-slot { border-radius: 6px; padding: 0.55rem; border: 1px solid #e9ecef; }
    .plan-api-slot.s1 { background: #f8fbff; border-color: #9ec5fe; }
    .plan-api-slot.s2 { background: #fff8f8; border-color: #f1aeb5; }
    .plan-api-slot.s3 { background: #fffbf0; border-color: #ffc107; }
    .plan-api-slot.s4 { background: #f3faf3; border-color: #75b798; }
    .plan-api-slot h6 { font-size: 12px; font-weight: 700; margin: 0 0 0.4rem; text-transform: uppercase; }
    .plan-api-slot .form-control, .plan-api-slot .form-select { font-size: 13px; margin-bottom: 0.35rem; }
    .plan-stats { font-size: 12px; color: #6c757d; }
    .plan-stats .ok { color: #198754; font-weight: 600; }
    .plan-stats .fail { color: #dc3545; font-weight: 600; }
    .plan-api-actions .btn { min-width: 90px; }
</style>

<div class="row">
    <div class="col-12">
        <div class="plan-api-page-title">Plan/Circle/DTH Info Fetch API Settings</div>
        <div class="plan-api-hint">
            API 1→2→3→4 failover (PlanConnect / MPlan / PlanAPI + extras). Enable/Disable, Cache (seconds), Timeout, and Test per service.
            Empty backup = auto next provider. Stop = no further failover.
        </div>

        <div id="settings_body">
            @foreach($settings as $row)
            <div class="plan-svc-card {{ $row->is_routing ? 'routing' : '' }} {{ empty($row->is_enabled) ? 'disabled-svc' : '' }}"
                 data-service-key="{{ $row->service_key }}"
                 data-is-routing="{{ $row->is_routing ? '1' : '0' }}">
                <div class="plan-svc-head">
                    <div class="plan-svc-title">{{ $row->service_label }}</div>
                    <div class="plan-svc-meta">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input is-enabled" type="checkbox" role="switch" @checked(!empty($row->is_enabled))>
                            <label class="form-check-label">Enabled</label>
                        </div>
                        <div>
                            <label class="form-label mb-0 small">Cache (sec)</label>
                            <input type="number" min="0" max="86400" class="form-control form-control-sm cache-ttl" value="{{ (int)($row->cache_ttl ?? 0) }}" style="width:90px">
                        </div>
                        <div>
                            <label class="form-label mb-0 small">Timeout</label>
                            <input type="number" min="5" max="120" class="form-control form-control-sm timeout-seconds" value="{{ (int)($row->timeout_seconds ?? 30) }}" style="width:80px">
                        </div>
                        <div class="plan-stats">
                            <span class="ok">OK {{ (int)($row->success_count ?? 0) }}</span> /
                            <span class="fail">Fail {{ (int)($row->fail_count ?? 0) }}</span>
                            @if(!empty($row->last_message))
                                <div class="text-truncate" style="max-width:220px" title="{{ $row->last_message }}">{{ $row->last_message }}</div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary test-service-btn">Test</button>
                    </div>
                </div>
                <div class="plan-api-grid">
                    @php
                        $slots = [
                            ['side' => 'primary', 'cls' => 's1', 'title' => 'API 1 (Primary)', 'stop' => false],
                            ['side' => 'backup', 'cls' => 's2', 'title' => 'API 2 (Backup)', 'stop' => true],
                            ['side' => 'backup2', 'cls' => 's3', 'title' => 'API 3 (Backup)', 'stop' => true],
                            ['side' => 'backup3', 'cls' => 's4', 'title' => 'API 4 (Backup)', 'stop' => true],
                        ];
                    @endphp
                    @foreach($slots as $slot)
                        @php
                            $side = $slot['side'];
                            $apiIdKey = $side.'_api_id';
                            $userKey = $side.'_username';
                            $passKey = $side.'_password';
                            $currentId = $row->{$apiIdKey} ?? null;
                        @endphp
                        <div class="plan-api-slot {{ $slot['cls'] }}">
                            <h6>{{ $slot['title'] }}</h6>
                            <select class="form-select form-select-sm {{ $side }}-api">
                                @if($slot['stop'])
                                    <option value="">Auto (next provider)</option>
                                    <option value="0" @selected(isset($currentId) && (int)$currentId === 0)>Stop</option>
                                @else
                                    <option value="">Select Provider</option>
                                @endif
                                @foreach($apis as $api)
                                    <option value="{{ $api->id }}" @selected(isset($currentId) && (int)$currentId === (int)$api->id)>{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control form-control-sm {{ $side }}-username" value="{{ $row->{$userKey} ?? '' }}" placeholder="Username / API Key">
                            <input type="text" class="form-control form-control-sm {{ $side }}-password" value="{{ $row->{$passKey} ?? '' }}" placeholder="Password">
                        </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-3 d-flex gap-2 plan-api-actions">
            <button type="button" class="btn btn-primary" id="save_settings_btn">Save</button>
            <button type="button" class="btn btn-danger" id="reset_settings_btn">Reset</button>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    const serviceLabels = @json(collect($settings)->pluck('service_label', 'service_key'));
    const routingKeys = @json(collect($settings)->where('is_routing', true)->pluck('service_key')->values());
    const apiCatalog = @json($apiCatalog);
    const sides = ['primary', 'backup', 'backup2', 'backup3'];

    function notify(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
            buttonsStyling: false,
            showCloseButton: true
        });
    }

    function applyApiCredentials($card, side, apiId, force) {
        if (!apiId || apiId === '0' || !apiCatalog[apiId]) {
            if (force) {
                $card.find('.' + side + '-username').val('');
                $card.find('.' + side + '-password').val('');
            }
            return;
        }
        const api = apiCatalog[apiId];
        const username = api.api_key || api.api_username || '';
        const password = api.api_password || api.api_key || '';
        if (force || $card.find('.' + side + '-username').val() === '') {
            $card.find('.' + side + '-username').val(username);
        }
        if (force || $card.find('.' + side + '-password').val() === '') {
            $card.find('.' + side + '-password').val(password);
        }
    }

    sides.forEach(function (side) {
        $(document).on('change', '.' + side + '-api', function () {
            const apiId = $(this).val();
            const $card = $(this).closest('.plan-svc-card');
            if (apiId === '0' || apiId === '') {
                $card.find('.' + side + '-username').val('');
                $card.find('.' + side + '-password').val('');
                return;
            }
            applyApiCredentials($card, side, apiId, true);
        });
    });

    $(document).on('change', '.is-enabled', function () {
        $(this).closest('.plan-svc-card').toggleClass('disabled-svc', !this.checked);
    });

    function collectRows() {
        const rows = [];
        $('#settings_body .plan-svc-card').each(function (index) {
            const $card = $(this);
            const serviceKey = $card.data('service-key');
            rows.push({
                service_key: serviceKey,
                service_label: serviceLabels[serviceKey] || serviceKey,
                is_routing: routingKeys.includes(serviceKey) ? 1 : 0,
                sort_order: index + 1,
                is_enabled: $card.find('.is-enabled').is(':checked') ? 1 : 0,
                cache_ttl: $card.find('.cache-ttl').val(),
                timeout_seconds: $card.find('.timeout-seconds').val(),
                primary_api_id: $card.find('.primary-api').val(),
                primary_username: $card.find('.primary-username').val(),
                primary_password: $card.find('.primary-password').val(),
                backup_api_id: $card.find('.backup-api').val(),
                backup_username: $card.find('.backup-username').val(),
                backup_password: $card.find('.backup-password').val(),
                backup2_api_id: $card.find('.backup2-api').val(),
                backup2_username: $card.find('.backup2-username').val(),
                backup2_password: $card.find('.backup2-password').val(),
                backup3_api_id: $card.find('.backup3-api').val(),
                backup3_username: $card.find('.backup3-username').val(),
                backup3_password: $card.find('.backup3-password').val(),
            });
        });
        return rows;
    }

    $('#save_settings_btn').on('click', function () {
        $.ajax({
            url: '{{ route('planCircleDthApiSave') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', rows: collectRows() },
            success: function (res) {
                notify(res.type === 'success' ? 'Success' : 'Error', res.message || 'Save failed', res.type === 'success' ? 'success' : 'error');
            },
            error: function () { notify('Error', 'Unable to save settings.', 'error'); }
        });
    });

    $('#reset_settings_btn').on('click', function () {
        Swal.fire({
            title: 'Reset to defaults?',
            text: 'All Plan/Circle/DTH API settings will be restored to default values.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reset'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '{{ route('planCircleDthApiReset') }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    if (res.type === 'success') {
                        notify('Success', res.message, 'success');
                        setTimeout(function () { window.location.reload(); }, 800);
                    } else {
                        notify('Error', res.message || 'Reset failed', 'error');
                    }
                },
                error: function () { notify('Error', 'Unable to reset settings.', 'error'); }
            });
        });
    });

    $(document).on('click', '.test-service-btn', function () {
        const $card = $(this).closest('.plan-svc-card');
        const serviceKey = $card.data('service-key');
        const $btn = $(this);
        $btn.prop('disabled', true).text('...');
        $.ajax({
            url: '{{ route('planCircleDthApiTest') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', service_key: serviceKey },
            success: function (res) {
                let detail = res.message || '';
                if (res.chain && res.chain.length) {
                    detail += '\n' + res.chain.map(function (c) {
                        return c.label + ': ' + (c.api_name || '-') + (c.ready ? ' ✓' : '');
                    }).join('\n');
                }
                notify(res.type === 'success' ? 'Test OK' : 'Test Failed', detail, res.type === 'success' ? 'success' : 'error');
            },
            error: function () { notify('Error', 'Unable to test service.', 'error'); },
            complete: function () { $btn.prop('disabled', false).text('Test'); }
        });
    });
</script>
@endsection
