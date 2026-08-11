@extends('layouts.master')

@section('title') Plan/Circle/DTH Info Fetch API Settings @endsection

@section('content')

<style>
    .plan-api-page-title {
        color: #0d6efd;
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .plan-api-table {
        margin-bottom: 0;
    }
    .plan-api-table th,
    .plan-api-table td {
        vertical-align: middle;
        padding: 0.55rem;
    }
    .plan-api-table .service-col {
        min-width: 240px;
        font-weight: 600;
        background: #fff;
    }
    .plan-api-table .routing-row {
        background-color: #d4edda !important;
    }
    .plan-api-table .routing-row .service-col {
        background-color: #d4edda;
    }
    .plan-api-table thead .primary-head {
        background-color: #0d6efd;
        color: #fff;
        text-align: center;
        font-weight: 600;
    }
    .plan-api-table thead .backup-head {
        background-color: #dc3545;
        color: #fff;
        text-align: center;
        font-weight: 600;
    }
    .plan-api-table thead .sub-head {
        background-color: #f8f9fa;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
    }
    .plan-api-table .primary-cell .form-control,
    .plan-api-table .primary-cell .form-select {
        border-color: #9ec5fe;
        background-color: #f8fbff;
    }
    .plan-api-table .backup-cell .form-control,
    .plan-api-table .backup-cell .form-select {
        border-color: #f1aeb5;
        background-color: #fff8f8;
    }
    .plan-api-table input.form-control,
    .plan-api-table select.form-select {
        min-width: 150px;
        font-size: 13px;
    }
    .plan-api-actions .btn {
        min-width: 90px;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="plan-api-page-title">Plan/Circle/DTH Info Fetch API Settings</div>

        <div class="table-responsive">
            <table class="table table-bordered plan-api-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="service-col">Service</th>
                        <th colspan="3" class="primary-head">Primary</th>
                        <th colspan="3" class="backup-head">Backup</th>
                    </tr>
                    <tr>
                        <th class="sub-head">API</th>
                        <th class="sub-head">Username</th>
                        <th class="sub-head">Password</th>
                        <th class="sub-head">API</th>
                        <th class="sub-head">Username</th>
                        <th class="sub-head">Password</th>
                    </tr>
                </thead>
                <tbody id="settings_body">
                    @foreach($settings as $row)
                    <tr class="{{ $row->is_routing ? 'routing-row' : '' }}" data-service-key="{{ $row->service_key }}" data-is-routing="{{ $row->is_routing ? '1' : '0' }}">
                        <td class="service-col">{{ $row->service_label }}</td>
                        <td class="primary-cell">
                            <select class="form-select primary-api">
                                <option value="">Select Provider</option>
                                @foreach($apis as $api)
                                <option value="{{ $api->id }}" @selected((int)$row->primary_api_id === (int)$api->id)>{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="primary-cell">
                            <input type="text" class="form-control primary-username" value="{{ $row->primary_username ?? '' }}" placeholder="Username / API Key">
                        </td>
                        <td class="primary-cell">
                            <input type="text" class="form-control primary-password" value="{{ $row->primary_password ?? '' }}" placeholder="Password">
                        </td>
                        <td class="backup-cell">
                            <select class="form-select backup-api">
                                <option value="">Select Provider</option>
                                <option value="0" @selected((int)$row->backup_api_id === 0)>Stop R-Offer Check</option>
                                @foreach($apis as $api)
                                <option value="{{ $api->id }}" @selected((int)$row->backup_api_id === (int)$api->id)>{{ $api->api_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="backup-cell">
                            <input type="text" class="form-control backup-username" value="{{ $row->backup_username ?? '' }}" placeholder="Username / API Key">
                        </td>
                        <td class="backup-cell">
                            <input type="text" class="form-control backup-password" value="{{ $row->backup_password ?? '' }}" placeholder="Password">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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

    function applyApiCredentials($row, side, apiId, force) {
        if (!apiId || apiId === '0' || !apiCatalog[apiId]) {
            if (force) {
                $row.find('.' + side + '-username').val('');
                $row.find('.' + side + '-password').val('');
            }
            return;
        }

        const api = apiCatalog[apiId];
        const username = api.api_key || api.api_username || '';
        const password = api.api_password || api.api_key || '';

        if (force || $row.find('.' + side + '-username').val() === '') {
            $row.find('.' + side + '-username').val(username);
        }
        if (force || $row.find('.' + side + '-password').val() === '') {
            $row.find('.' + side + '-password').val(password);
        }
    }

    $(document).on('change', '.primary-api', function () {
        applyApiCredentials($(this).closest('tr'), 'primary', $(this).val(), true);
    });

    $(document).on('change', '.backup-api', function () {
        const apiId = $(this).val();
        const $row = $(this).closest('tr');
        if (apiId === '0') {
            $row.find('.backup-username').val('');
            $row.find('.backup-password').val('');
            return;
        }
        applyApiCredentials($row, 'backup', apiId, true);
    });

    function collectRows() {
        const rows = [];
        $('#settings_body tr').each(function (index) {
            const $row = $(this);
            const serviceKey = $row.data('service-key');
            rows.push({
                service_key: serviceKey,
                service_label: serviceLabels[serviceKey] || serviceKey,
                is_routing: routingKeys.includes(serviceKey) ? 1 : 0,
                sort_order: index + 1,
                primary_api_id: $row.find('.primary-api').val(),
                primary_username: $row.find('.primary-username').val(),
                primary_password: $row.find('.primary-password').val(),
                backup_api_id: $row.find('.backup-api').val(),
                backup_username: $row.find('.backup-username').val(),
                backup_password: $row.find('.backup-password').val(),
            });
        });
        return rows;
    }

    $('#save_settings_btn').on('click', function () {
        $.ajax({
            url: '{{ route('planCircleDthApiSave') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                rows: collectRows()
            },
            success: function (res) {
                if (res.type === 'success') {
                    notify('Success', res.message, 'success');
                } else {
                    notify('Error', res.message || 'Save failed', 'error');
                }
            },
            error: function () {
                notify('Error', 'Unable to save settings.', 'error');
            }
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
            if (!result.isConfirmed) {
                return;
            }
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
                error: function () {
                    notify('Error', 'Unable to reset settings.', 'error');
                }
            });
        });
    });
</script>
@endsection
