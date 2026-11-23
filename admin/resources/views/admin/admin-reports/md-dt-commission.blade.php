@extends('layouts.master')
@section('title') MD / DT Commission @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Accounts Reports @endslot
@slot('title') MD / DT Commission @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Date filter</h4>
            </div>
            <div class="card-body">
                <div class="row gy-3 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label mb-0" for="from_date">From</label>
                        <input type="date" class="form-control" id="from_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label mb-0" for="to_date">To</label>
                        <input type="date" class="form-control" id="to_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label mb-0" for="role_id">Role</label>
                        <select class="form-select" id="role_id">
                            <option value="0">MD and DT</option>
                            <option value="5">DT only</option>
                            <option value="4">MD only</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <button type="button" class="btn btn-secondary w-100" id="search_btn" onclick="loadCommission()">Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card"><div class="card-body"><p class="text-muted mb-1">DT commission</p><h4 class="mb-0" id="dt_total">₹ 0.00</h4></div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body"><p class="text-muted mb-1">MD commission</p><h4 class="mb-0" id="md_total">₹ 0.00</h4></div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body"><p class="text-muted mb-1">Total</p><h4 class="mb-0" id="all_total">₹ 0.00</h4></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Who received how much</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>User</th>
                                <th>Mobile</th>
                                <th>ID</th>
                                <th class="text-end">Credits</th>
                                <th class="text-end">Commission</th>
                                <th class="text-end">Reversed</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody id="commission_rows">
                            <tr><td colspan="8" class="text-center text-muted">Search to load commission</td></tr>
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
    function money(value) {
        var amount = Number(value || 0);
        return '₹ ' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function loadCommission() {
        var button = $('#search_btn');
        button.prop('disabled', true).text('Please wait...');
        $.ajax({
            url: '{{ route('mdDtCommissionList') }}',
            method: 'post',
            data: {
                from_date: $('#from_date').val(),
                to_date: $('#to_date').val(),
                role_id: $('#role_id').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                button.prop('disabled', false).text('Search');
                $('#dt_total').text(money(res.dt_total));
                $('#md_total').text(money(res.md_total));
                $('#all_total').text(money(res.total));
                var rows = res.rows || [];
                if (!rows.length) {
                    $('#commission_rows').html('<tr><td colspan="8" class="text-center text-muted">No commission in this date range</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function (row) {
                    html += '<tr>'
                        + '<td>' + $('<div>').text(row.role).html() + '</td>'
                        + '<td>' + $('<div>').text(row.name).html() + '</td>'
                        + '<td>' + $('<div>').text(row.mobile).html() + '</td>'
                        + '<td>' + $('<div>').text(row.id).html() + '</td>'
                        + '<td class="text-end">' + $('<div>').text(row.count).html() + '</td>'
                        + '<td class="text-end">' + money(row.credit) + '</td>'
                        + '<td class="text-end">' + money(row.reverse) + '</td>'
                        + '<td class="text-end"><strong>' + money(row.net) + '</strong></td>'
                        + '</tr>';
                });
                $('#commission_rows').html(html);
            },
            error: function () {
                button.prop('disabled', false).text('Search');
                $('#commission_rows').html('<tr><td colspan="8" class="text-center text-danger">Unable to load commission</td></tr>');
            }
        });
    }

    loadCommission();
</script>
@endsection
