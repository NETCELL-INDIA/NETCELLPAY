<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-topbar="light">

    <head>
    <meta charset="utf-8" />
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
    </script>
    <link href="{{ admin_asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ admin_asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="{{ admin_asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ admin_asset('assets/css/admin-auth.css') }}?v=20260810" rel="stylesheet" type="text/css" />
    @if(!empty($company?->company_icon))
        <link rel="shortcut icon" href="{{ env('ADMIN_HOST') }}/company_logo/{{ $company->company_icon }}">
    @else
        <link rel="shortcut icon" href="{{ admin_asset('assets/images/favicon.ico') }}">
    @endif
    @yield('css')
  </head>

    @yield('body')

    @yield('content')

    @include('layouts.vendor-scripts')
    </body>
</html>
