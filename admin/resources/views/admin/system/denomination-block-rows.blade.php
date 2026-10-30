<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead>
            <tr>
                @if($mode === 'user')<th>Retailer</th>@endif
                <th>Operator</th>
                <th>Service</th>
                <th>Amounts</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                @if($mode === 'user')
                    <td>{{ $row->outlet_name ?: $row->first_name ?: ('User '.$row->user_id) }}<br><small>{{ $row->mobile_number }}</small></td>
                @endif
                <td>{{ $row->provider_name ?: ('Operator '.$row->provider_id) }}</td>
                <td>{{ $row->service_name ?: '-' }}</td>
                <td>{{ $row->amounts }}</td>
                <td>{{ (int) $row->status === 1 ? 'Active' : 'Inactive' }}</td>
                <td class="text-nowrap">
                    <button type="button" class="btn btn-sm btn-primary block-edit"
                        data-id="{{ $row->id }}"
                        data-provider="{{ $row->provider_id }}"
                        data-operator="{{ $row->provider_name }}"
                        data-amounts="{{ $row->amounts }}"
                        data-status="{{ $row->status }}"
                        data-mobile="{{ $mode === 'user' ? ($row->mobile_number ?? '') : '' }}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger block-delete" data-id="{{ $row->id }}">Delete</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No blocks yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
