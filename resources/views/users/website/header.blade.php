<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $company->company_name }} provides secure digital payments, recharges, bill payments and business banking services across India.">
    <meta name="theme-color" content="#111936">

    <title>{{ $company->company_name }} | Digital Payments for Every Business</title>

    @if(!empty($company->company_icon))
        <link rel="shortcut icon" href="{{ rtrim(env('ADMIN_HOST'), '/') }}/company_logo/{{ $company->company_icon }}" type="image/x-icon">
    @endif

    <link href="{{ URL::asset('web_template/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/animate.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/style.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/responsive.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/netcell-site.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16658014418"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-16658014418');
    </script>
</head>

<body class="nc-site">
    <div class="nc-announcement">
        <div class="nc-container nc-announcement-inner">
            <p><span class="nc-status-dot"></span> {{ !empty($company->header_value) ? $company->header_value : 'Fast, secure and trusted digital payment services' }}</p>
            <div class="nc-contact-links">
                @if(!empty($company->support_email))
                    <a href="mailto:{{ $company->support_email }}">
                        <i class="fas fa-envelope"></i>{{ $company->support_email }}
                    </a>
                @endif
                @if(!empty($company->support_number))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $company->support_number) }}">
                        <i class="fas fa-phone"></i>{{ $company->support_number }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <header class="nc-header" id="site-header">
        <div class="nc-container nc-navbar">
            <a class="nc-brand" href="{{ url('/') }}" aria-label="{{ $company->company_name }} home">
                @if(!empty($company->company_logo))
                    <img src="{{ rtrim(env('ADMIN_HOST'), '/') }}/company_logo/{{ $company->company_logo }}"
                        alt="{{ $company->company_name }}"
                        onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                    <span class="nc-brand-fallback" hidden>
                        <span class="nc-brand-symbol"><i class="fas fa-bolt"></i></span>
                        <span>{{ $company->company_name }}</span>
                    </span>
                @else
                    <span class="nc-brand-symbol"><i class="fas fa-bolt"></i></span>
                    <span>{{ $company->company_name }}</span>
                @endif
            </a>

            <button class="nc-menu-toggle" type="button" aria-controls="nc-navigation" aria-expanded="false">
                <span></span><span></span><span></span>
                <span class="sr-only">Toggle navigation</span>
            </button>

            <nav class="nc-navigation" id="nc-navigation" aria-label="Primary navigation">
                <a class="{{ request()->is('/') || request()->is('home') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>
                <a class="{{ request()->is('about-us') ? 'active' : '' }}" href="{{ url('/about-us') }}">About</a>
                <a class="{{ request()->is('services') ? 'active' : '' }}" href="{{ url('/services') }}">Services</a>
                <a class="{{ request()->is('contact-us') ? 'active' : '' }}" href="{{ url('/contact-us') }}">Contact</a>
            </nav>

            <div class="nc-nav-actions">
                @if(!empty($company->apk_file_name))
                    <a class="nc-app-link" href="{{ $company->apk_file_name }}" target="_blank" rel="noopener">
                        <i class="fab fa-android"></i><span>Get App</span>
                    </a>
                @endif
                <a class="nc-login-link" href="{{ url('/users/login') }}">
                    Login <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </header>

