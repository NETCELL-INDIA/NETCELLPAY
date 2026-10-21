@extends('layouts.master')
@section('title') TDS / Commission Export @endsection
@section('content')
<div class="card">
    <div class="card-header"><h4 class="mb-0">TDS estimate from successful commission</h4></div>
    <div class="card-body">
        <form class="row gy-2 mb-3" method="get">
            <div class="col-md-2"><input type="date" name="from_date" class="form-control" value="{{ substr($from,0,10) }}"></div>
            <div class="col-md-2"><input type="date" name="to_date" class="form-control" value="{{ substr($to,0,10) }}"></div>
            <div class="col-md-2"><input type="number" name="percent" class="form-control" min="0" max="30" step="0.1" value="{{ $percent }}"></div>
            <div class="col-md-2"><button class="btn btn-primary" type="submit">Show</button></div>
            <div class="col-md-2"><button class="btn btn-success" name="download" value="1" type="submit">Download CSV</button></div>
        </form>
        <p class="small text-muted">This export calculates TDS for accounts. It does not deduct wallet balance.</p>
        <table class="table table-bordered table-sm">
            <thead><tr><th>User</th><th>Commission</th><th>TDS {{ $percent }}%</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->user_id }}</td>
                    <td>{{ number_format((float)$row->commission, 2) }}</td>
                    <td>{{ number_format((float)$row->commission * $percent / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No commission in this range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
