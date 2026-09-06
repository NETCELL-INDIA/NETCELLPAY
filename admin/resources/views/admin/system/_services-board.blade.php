@if($services->isEmpty())
    <h4 class="text-center text-secondary my-3">No record found</h4>
@else
<div class="svc-board">
    @foreach($services as $service)
        @php
            $isOn = (string) $service->status === '1';
            $isDown = (int) ($service->service_down ?? 0) === 1;
        @endphp
        <div class="svc-block">
            <div class="svc-head">
                <div class="svc-order">
                    <span class="svc-num">{{ $service->position }}</span>
                    <div class="svc-move">
                        <button type="button" class="btn btn-sm btn-light moveItem" data-type="service" data-id="{{ $service->id }}" data-direction="up" @disabled($service->is_first) title="Move up">
                            <i class="ri-arrow-up-s-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light moveItem" data-type="service" data-id="{{ $service->id }}" data-direction="down" @disabled($service->is_last) title="Move down">
                            <i class="ri-arrow-down-s-line"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="svc-icon-btn editDetails" id="{{ $service->id }}" title="Set icon">
                    <img src="{{ $service->icon_url }}" alt="">
                    <span>Set Icon</span>
                </button>
                <div class="svc-title">
                    <small>SERVICE</small>
                    <h3>{{ $service->service_name }}</h3>
                    <p>{{ $service->operators->count() }} Operator{{ $service->operators->count() === 1 ? '' : 's' }} · {{ (($service->catalog_group ?? '') === 'recharge') ? 'App: Recharge' : 'App: Bill Payments' }}</p>
                </div>
                <div class="svc-flags">
                    <button type="button" class="badge rounded-pill border-0 toggleServiceStatus {{ $isOn ? 'text-bg-success' : 'text-bg-danger' }}" data-id="{{ $service->id }}" data-status="{{ $isOn ? 0 : 1 }}">{{ $isOn ? 'ON' : 'OFF' }}</button>
                    <button type="button" class="badge rounded-pill border-0 toggleServiceDown {{ $isDown ? 'text-bg-danger' : 'text-bg-success' }}" data-id="{{ $service->id }}" data-down="{{ $isDown ? 0 : 1 }}">{{ $isDown ? 'DOWN' : 'UP' }}</button>
                    <button type="button" class="btn btn-sm btn-outline-primary editDetails" id="{{ $service->id }}">
                        <i class="ri-pencil-line"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger deleteService" data-id="{{ $service->id }}" data-name="{{ $service->service_name }}">
                        <i class="ri-delete-bin-line"></i> Delete
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 svc-ops">
                    <thead>
                        <tr>
                            <th style="width:70px">No.</th>
                            <th>Operator</th>
                            <th style="width:90px">Status</th>
                            <th style="width:90px">Down</th>
                            <th style="width:110px" class="text-center">Move</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($service->operators as $oi => $op)
                            @php
                                $opOn = (int) ($op->status ?? 0) === 1;
                                $opDown = (int) ($op->provider_down ?? 0) === 1;
                                $logo = function_exists('admin_provider_logo_url')
                                    ? admin_provider_logo_url((string) ($op->provider_logo ?? ''))
                                    : asset('assets/images/users/user-dummy-img.jpg');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $oi + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $logo }}" alt="" class="svc-op-logo">
                                        <span>{{ $op->provider_name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge {{ $opOn ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $opOn ? 'ON' : 'OFF' }}</span></td>
                                <td><span class="badge {{ $opDown ? 'text-bg-danger' : 'text-bg-success' }}">{{ $opDown ? 'DOWN' : 'UP' }}</span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-light moveItem" data-type="operator" data-id="{{ $op->id }}" data-direction="up" @disabled($oi === 0)><i class="ri-arrow-up-s-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-light moveItem" data-type="operator" data-id="{{ $op->id }}" data-direction="down" @disabled($oi === $service->operators->count() - 1)><i class="ri-arrow-down-s-line"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted py-3">No operators in this service.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endif
