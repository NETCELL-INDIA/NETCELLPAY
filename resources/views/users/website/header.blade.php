<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="We are providing  Online Mobile Recharge Portal Service.
We are a website and software development company, dedicated to creating innovative solutions that empower businesses to thrive in the digital world. Our expertise lies in delivering cutting-edge technology and exceptional user experiences.
At our website and software development company, we combine technical expertise with creative flair to build custom websites and software solutions tailored to your unique business needs. With a focus on quality, scalability, and user-centric design, we strive to exceed expectations and drive your digital success.">

    <!-- ========== Page Title ========== -->
    <title>{{$company->company_name}}</title>

    <!-- ========== Favicon Icon ========== -->
    <link rel="shortcut icon" href="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_icon}}" type="image/x-icon">

    <!-- ========== Start Stylesheet ========== -->
    <link href="{{ URL::asset('web_template/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/font-awesome.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/flaticon-set.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/magnific-popup.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/owl.carousel.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/owl.theme.default.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/animate.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/bootsnav.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('web_template/css/style.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('web_template/css/responsive.css') }}" rel="stylesheet" />
    <!-- ========== End Stylesheet ========== -->
    <!-- ========== Google Fonts ========== -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800" rel="stylesheet">
<!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16658014418">
  </script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'AW-16658014418');
  </script>
</head>

<body>

    <!-- Preloader Start -->
    <div class="se-pre-con"></div>
    <!-- Preloader Ends -->
    <!-- Start Header Top
    ============================================= -->
    <div class="top-bar-area address-two-lines bg-dark text-light">
        <div class="container">
            <div class="row">
                <div class="col-md-8 address-info">
                    <div class="info box">
                        <ul>

                            <li>
                                <span><i class="fas fa-envelope-open"></i> Email: {{$company->support_email}} </span>
                            </li>
                            <li>
                                <span><i class="fas fa-phone"></i> Contact: {{$company->support_number}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Header Top -->
    <!-- Header
    ============================================= -->
    <header id="home">

        <!-- Start Navigation -->
        <nav class="navbar navbar-default navbar-sticky bootsnav">

            <!-- Start Top Search -->
            <div class="container">
                <div class="row">
                    <div class="top-search">
                        <div class="input-group">
                            <form action="#">
                                <input type="text" name="text" class="form-control" placeholder="Search">
                                <button type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Top Search -->

            <div class="container">

                <!-- Start Atribute Navigation -->
                <!-- End Atribute Navigation -->
                <!-- Start Header Navigation -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                        <i class="fa fa-bars"></i>
                    </button>
                    <a class="navbar-brand cusnavbar-brand" href="home">
                        <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" class="logo" alt="Logo" style="width: auto;height: 60px;">
                    </a>
                </div>
                <!-- End Header Navigation -->
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="nav navbar-nav navbar-right" data-in="#" data-out="#">
                        <li>
                            <a href="home">Home</a>
                        </li>
                        <li>
                            <a href="about-us">About</a>
                        </li>
                        <li>
                            <a href="services">Services</a>
                        </li>

                        <li>
                            <a href="contact-us">contact</a>
                        </li>
                        <li>
                            <a href="users/login">Login</a>
                        </li>
                        <li class="last-list-sec">
                            <a href="{{$company->apk_file_name}}"><i class="fab fa-android"></i>Download App</a>
                        </li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div>

        </nav>
        <!-- End Navigation -->

    </header>
    <!-- End Header -->

