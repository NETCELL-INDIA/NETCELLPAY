
@include('users.website.header')
<div id="myCarousel" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
        <li data-target="#myCarousel" data-slide-to="3"></li>
        <li data-target="#myCarousel" data-slide-to="4"></li>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner">
        <div class="item active cus-item">
            <img src="{{ URL::asset('web_template/img/banner/banner4.jpg') }}" alt="Los Angeles">
        </div>
        <div class="item cus-item">
            <img src="{{ URL::asset('web_template/img/banner/banner3.jpg') }}" alt="New York">
        </div>
        <div class="item cus-item">
            <img src="{{ URL::asset('web_template/img/banner/banner2.jpg') }}" alt="New York">
        </div>
        <div class="item cus-item">
            <img src="{{ URL::asset('web_template/img/banner/banner1.jpg') }}" alt="New York">
        </div>
    </div>

    <!-- Left and right controls -->
    <a class="left carousel-control" href="#myCarousel" data-slide="prev">
        <span class="fa fa-angle-left"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#myCarousel" data-slide="next">
        <span class="fa fa-angle-right"></span>
        <span class="sr-only">Next</span>
    </a>
</div>
<div class="about-area about-area-back default-padding">
    <div class="container">
        <div class="row">
            <div class="about-info">
                <div class="col-md-6 thumb">
                    <img src="{{ URL::asset('web_template/img/about/1.png') }}" alt="Thumb">
                </div>
                <div class="col-md-6 info">
                    <h5>WELCOME TO {{$company->company_name}}</h5>
                    <h2>Money Transfer</h2>
                    <p>
                    {{$company->company_name}} offers the fastest and easiest way of money transfer to more than 400 banks in India. Based on the IMPS technology, our safe, instant and easy domestic money remittance (DMR) service allows you to send money to any bank account in India.
                    </p>

                    <a href="#" class="btn btn-dark border btn-md">Read More</a>
                </div>
            </div>
            <div class="about-info">
                <div class="col-md-6 info info1">
                    <h2>Aadhaar Enabled Payment System</h2>
                    <p>
                        {{$company->company_name}} AEPS service enables our customers for hassel free, secure and biometric authenticated cash withdraw & balance enquiry from their aadhaar linked bank accounts.
                    </p>

                    <a href="#" class="btn btn-dark border btn-md">Read More</a>
                </div>
                <div class="col-md-6 thumb">
                    <img src="{{ URL::asset('web_template/img/about/2.png') }}" alt="Thumb" style="float: right;">
                </div>
            </div>
            <div class="about-info">
                <div class="col-md-6 thumb">
                    <img src="{{ URL::asset('web_template/img/about/3.png') }}" alt="Thumb">
                </div>
                <div class="col-md-6 info info1">
                    <h2>Prepaid Cards</h2>
                    <p>
                        {{$company->company_name}} prepaid cards can be used for your daily transactions. {{$company->company_name}} prepaid cards are ideal way of payments for shopping at Shops, Malls, Movies, Restaurants etc. and online payments.
                    </p>

                    <a href="#" class="btn btn-dark border btn-md">Read More</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="wcs-area bg-dark  text-light">
    <div class="container-full">
        <div class="row">
            <div class="col-md-6 thumb bg-cover" style="background-image: url({{ URL::asset('web_template/img/banner/16.jpg') }});background-size: cover;background-repeat: no-repeat;"></div>
            <div class="col-md-6 content">
                <div class="site-heading text-left">
                    <h2>Our Services</h2>

                </div>

                <!-- item -->
                <div class="item">
                    <div class="icon">
                        <img src="{{ URL::asset('web_template/img/banner/recharge.png') }}">
                    </div>
                    <div class="info">
                        <h4>
                            <a href="#">Recharge Api (DTH, Mobile, Ticket Booking)</a>
                        </h4>
                        <p> API Makes Shows Your Business Environment smoother and enables your whole framework clients to investigate extra Services For same Resources </p>
                    </div>
                </div>
                <div class="item">
                    <div class="icon">
                        <img src="{{ URL::asset('web_template/img/banner/bill.png') }}">
                    </div>
                    <div class="info">
                        <h4>
                            <a href="#">DMT (Domestic Money Transfer)</a>
                        </h4>
                        <p>Domestic Money Transfer (DMT) benefit is an enormous market in India which got much more lift with current increment in advanced exchanges.</p>
                    </div>
                </div>
                <div class="item">
                    <div class="icon">
                        <img src="{{ URL::asset('web_template/img/banner/aeps.png') }}">
                    </div>
                    <div class="info">
                        <h4>
                            <a href="#">AEPS - Aadhaar Enabled Payment System</a>
                        </h4>
                        <p>Domestic Money Transfer (DMT) benefit is an enormous market in India which got much more lift with current increment in advanced exchanges.</p>
                    </div>
                </div>

                <div class="item">
                    <div class="icon">
                        <img src="{{ URL::asset('web_template/img/banner/bbps.png') }}">
                    </div>
                    <div class="info">
                        <h4>
                            <a href="#">Bharat Bill Payment System (BBPS)</a>
                        </h4>
                        <p>Bharat Bill Payment System is a stage for a wide range of bill installments. National Payments Corporation of India has created it to... </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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


@include('users.website.footer')


    