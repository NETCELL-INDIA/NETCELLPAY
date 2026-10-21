@extends('layouts.master')
@section('title') {{ $title }} @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Reports @endslot
@slot('title') {{ $title }} @endslot
@endcomponent
<div class="card">
    <div class="card-body">
        <div class="row gy-3 mb-3">
            <div class="col-md-2"><label class="form-label mb-0">From</label><input type="date" id="from_date" class="form-control" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">To</label><input type="date" id="to_date" class="form-control" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"></div>
            <div class="col-md-2"><label class="form-label mb-0">Order</label><input type="text" id="order_id" class="form-control"></div>
            <div class="col-md-2"><label class="form-label mb-0">Number</label><input type="text" id="number" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary" id="load_btn" type="button">Search</button></div>
        </div>
        <div id="report_result"></div>
    </div>
</div>
@endsection
@section('script')
<script>
function loadReport(page) {
    $.post('{{ route($listRoute) }}', {
        _token: '{{ csrf_token() }}',
        page: page || 1,
        limit: 20,
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        order_id: $('#order_id').val(),
        number: $('#number').val()
    }, function (html) {
        $('#report_result').html(html);
    }).fail(function () {
        $('#report_result').html('<div class="text-danger">Unable to load report.</div>');
    });
}
$('#load_btn').on('click', function () { loadReport(1); });
loadReport(1);
</script>
@endsection
