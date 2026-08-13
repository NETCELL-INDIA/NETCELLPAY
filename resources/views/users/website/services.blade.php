
@include('users.website.header')
<div class="breadcrumb-area shadow dark text-center bg-fixed text-light" style="background-image: url({{ URL::asset('web_template/img/banner/2.jpg') }});">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Services</h1>
                <ul class="breadcrumb">
                    <li><a href="home"><i class="fas fa-home"></i> Home</a></li>

                    <li class="active">Services</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@php $cmsPage = website_page('services'); @endphp
@if(website_page_text($cmsPage, 'body') !== '')
<div class="popular-courses default-padding">
    <div class="container" style="padding:40px 0;white-space:pre-wrap;">
        @if(website_page_text($cmsPage, 'heading') !== '')
            <h2>{{ $cmsPage->heading }}</h2>
        @endif
        {{ $cmsPage->body }}
    </div>
</div>
@else
<div class="popular-courses about-area-back circle bg-gray carousel-shadow default-padding">
    <div class="container">
        <div class="row">
            <div class="site-heading text-center">
                <div class="col-md-8 col-md-offset-2">
                    <h2>Popular Services</h2>

                </div>
            </div>
        </div>
        <div class="row">
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/01.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>Recharges : Prepaid <br><small>  Mobile | DTH | Data Card</small></h4>

                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/02.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>Bill Payments : BBPS <br><small>  Phone | Electricity | Gas | Water</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/03.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>FasTag <br><small> FasTag</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/04.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>Insurance <br><small> Insurance</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/06.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>MicroATM <br><small> MicroATM</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/08.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>PAN Card Center <br><small> PAN Card Center</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/07.png') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>Billpayment <br><small> Billpayment</small></h4>
                    </div>
                </div>
            </div>
            <div class="product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <div class="product-thumb">
                    <div class="image">
                        <a href="#">
                            <img src="{{ URL::asset('web_template/img/blog/10.jpg') }}" class="img-responsive" alt="img" title="img">
                        </a>
                    </div>
                    <div class="caption">
                        <h4>Money Transfer <br><small> Money Transfer </small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@include('users.website.footer')