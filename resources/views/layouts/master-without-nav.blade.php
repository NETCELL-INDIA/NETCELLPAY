<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-topbar="light">

    <head>
    <meta charset="utf-8" />
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <!-- Sweet Alert css-->
    <link href="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="{{ URL::asset('assets/css/bootstrap.min.css" rel="stylesheet') }}" type="text/css" />
    <!-- App favicon -->
    @php
        $company = DB::table('companies')->where('status', "1")->where('domain', $_SERVER['HTTP_HOST'])->first();
    @endphp
    <link rel="shortcut icon" href="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_icon}}">
        @include('layouts.head-css')
  </head>

    @yield('body')

    @yield('content')

    @include('layouts.vendor-scripts')
    </body>
</html>
