@extends('layouts.master')
@section('title')All Services @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Services @endslot
@slot('title')All Services @endslot
@endcomponent


<div class="row">
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #e8bc52;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/mobile_1.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Mobile Recharge</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->


    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #13c56b;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/smartphone.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Postpaid Recharge</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #55a4fc;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/smart-tv.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">DTH Recharge</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/aeps.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">AEPS</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/online-transfer.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Payout</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/dmt.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Money Transfer</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
</div>
<div class="row">

<div class="col-xl-2 col-md-6">
        <!-- card -->
    <div class="card" style="background: #fabe2c;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/flash.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Electricity Bill Pay</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #50bfed;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/faucet.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Water Bill Pay</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #e19843;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/camping-gas.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Gas Bill Pay</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #a8ffaa;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/telephone.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Landline Bill Pay</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ffdc88;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/cable-tv.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Cable Tv Pay</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ed9c9c;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/gas-tank.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Book Cylinder Pay</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    
</div>
<div class="row">
<div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/health-insurance.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Insurance Bill Pay</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/credit-card.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Credit Card Bill Pay</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/student.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Education Fees Pay</h5>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #a8ffaa;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/rent.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Rent Payment</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ffdc88;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/play-google.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Google Play Recharge</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-2 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ed9c9c;">
            <div class="card-body" style="text-align: center;">
                <img class="img-thumbnail rounded-circle avatar-xl" alt="200x200" src="{{ URL::asset('/service_icon/shield.png') }}" data-holder-rendered="true">
                <h5 class="mt-3">Insurance Premium</h5>
            </div><!-- end card body --> 
        </div><!-- end card -->
    </div><!-- end col -->

    
</div>
@endsection
@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

{{-- <script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}


<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

<!--jquery cdn-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!--select2 cdn-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ URL::asset('/assets/js/pages/select2.init.js') }}">

@endsection
