@extends('layouts.master')
@section('title') Complaint Reports @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Support Management @endslot
@slot('title') Complaint Reports @endslot
@endcomponent
@include('admin.support.complaint-filters', ['action' => route('complaintDeskReports'), 'showPeriod' => true])
@if(empty($ready))
<div class="alert alert-warning">Complaint tables are not ready on this database.</div>
@else
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="mb-0">Rows in this report: {{ number_format($total) }}. Average resolution time: {{ $average_minutes === null ? 'Not recorded' : $average_minutes.' minutes' }}.</p>
    <a class="btn btn-primary" href="{{ route('complaintDeskExport', request()->query()) }}">Export CSV</a>
</div>
<p class="text-muted">Excel export is not available because no spreadsheet library is installed. CSV opens in Excel.</p>
<div class="row">
    @foreach(['category' => 'Category-wise', 'operator' => 'Operator-wise', 'provider' => 'Provider-wise', 'staff' => 'Staff-wise'] as $key => $label)
    <div class="col-md-6">
        <div class="card" style="border-top: 3px solid #175CD3;">
            <div class="card-header"><h4 class="card-title mb-0">{{ $label }}</h4></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    @forelse($summary[$key] as $name => $count)
                    <tr><td>{{ $name }}</td><td class="text-end">{{ number_format($count) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">Not recorded</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
