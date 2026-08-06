<!doctype html >
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="horizontal" data-layout-style="default" data-layout-position="fixed" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-layout-width="fluid" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | {{ $company->company_name ?? 'Admin' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Force top (horizontal) menu — clear any saved vertical layout --}}
    <script>
        (function () {
            try {
                sessionStorage.setItem('data-layout', 'horizontal');
                var def = sessionStorage.getItem('defaultAttribute');
                if (def) {
                    var o = JSON.parse(def);
                    o['data-layout'] = 'horizontal';
                    sessionStorage.setItem('defaultAttribute', JSON.stringify(o));
                }
            } catch (e) {}
        })();
    </script>
    <!-- App favicon -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <!-- Sweet Alert css-->
    <link href="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    @if(!empty($company->company_icon))
        <link rel="shortcut icon" href="{{ env('APP_URL') }}/company_logo/{{ $company->company_icon }}">
    @else
        <link rel="shortcut icon" href="{{ URL::asset('assets/images/favicon.ico') }}">
    @endif
    @include('layouts.head-css')
</head>

@section('body')
    @include('layouts.body')
@show
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
</body>

</html>
