@php
    $npCompany = $company ?? null;
    if (empty($npCompany)) {
        $npCompany = function_exists('user_company') ? user_company() : null;
    }
    $npAdminHost = rtrim((string) env('ADMIN_HOST', ''), '/');
    $npIconFile = $npCompany->company_icon ?? null;
    $npFav = $npIconFile
        ? $npAdminHost.'/company_logo/'.$npIconFile.'?v=hd2'
        : URL::asset('assets/images/favicon-32.png');
    $npTouch = $npIconFile
        ? $npAdminHost.'/company_logo/'.$npIconFile.'?v=hd2'
        : URL::asset('assets/images/apple-touch-icon.png');
@endphp
<link rel="icon" type="image/png" sizes="32x32" href="{{ $npFav }}">
<link rel="shortcut icon" type="image/png" href="{{ $npFav }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $npTouch }}">
