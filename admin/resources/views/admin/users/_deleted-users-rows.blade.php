@php
    $listCount = $list->count();
    $totalPages = max(1, (int) ceil($total / $limit));
@endphp

@if($listCount === 0)
    <h4 class="text-center text-secondary my-3">No deleted users</h4>
@else
<div class="users-list-wrap">
    <div class="users-list-toolbar">
        <div class="users-list-toolbar__left">
            <label class="users-list-toolbar__label">Show</label>
            <select class="form-select form-select-sm page_limit" id="page_limit">
                @foreach([5,10,15,25,50] as $n)
                    <option value="{{ $n }}" @selected((int)$limit === $n)>{{ $n }}</option>
                @endforeach
            </select>
            <span class="users-list-toolbar__label">entries</span>
        </div>
    </div>
    <div class="table-responsive users-list-table-wrap">
        <table class="table table-sm table-hover users-list-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-end">Wallet</th>
                    <th>Created</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($list as $i => $row)
                    @php
                        $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                        if ($fullName === '') {
                            $fullName = $row->outlet_name ?? '-';
                        }
                    @endphp
                    <tr>
                        <td>{{ $start + $i + 1 }}</td>
                        <td>
                            <span class="users-name">{{ $fullName }}</span>
                            @if(!empty($row->outlet_name))
                                <span class="users-outlet d-block">{{ $row->outlet_name }}</span>
                            @endif
                        </td>
                        <td>{{ $row->mobile_number }}</td>
                        <td>{{ $row->email_address ?: '-' }}</td>
                        <td>{{ $row->role_name ?: '-' }}</td>
                        <td class="text-end">₹ {{ number_format((float) $row->wallet_balance, 2) }}</td>
                        <td>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">
                            <button type="button" id="{{ $row->id }}" class="btn btn-sm btn-success restoreData">
                                <i class="ri-refresh-line"></i> Restore
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="users-list-footer">
        <span class="users-list-count">Showing {{ $start + 1 }} to {{ $start + $listCount }} of {{ $total }} entries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="javascript:void(0)" onclick="tableSearch({{ $page - 1 }})">Prev</a>
                </li>
                @for($p = 1; $p <= $totalPages; $p++)
                    <li class="page-item">
                        <a href="javascript:void(0)" class="page-link {{ $page == $p ? 'active' : '' }}" @if($page != $p) onclick="tableSearch({{ $p }})" @endif>{{ $p }}</a>
                    </li>
                @endfor
                <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="javascript:void(0)" onclick="tableSearch({{ $page + 1 }})">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
@endif
