@php
    $npIcon = !empty($company?->company_icon)
        ? admin_company_logo($company->company_icon)
        : null;
    $npFav = $npIcon ?: admin_asset('assets/images/favicon-32.png');
    $npTouch = $npIcon ?: admin_asset('assets/images/apple-touch-icon.png');
@endphp
<link rel="icon" type="image/png" sizes="32x32" href="{{ $npFav }}">
<link rel="shortcut icon" type="image/png" href="{{ $npFav }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $npTouch }}">
