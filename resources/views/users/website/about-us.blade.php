@include('users.website.header')
<div class="breadcrumb-area shadow dark text-center bg-fixed text-light" style="background-image: url({{ URL::asset('web_template/img/banner/2.jpg') }});">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>About Us</h1>
                <ul class="breadcrumb">
                    <li><a href="home"><i class="fas fa-home"></i> Home</a></li>

                    <li class="active">About Us</li>
                </ul>
            </div>
        </div>
    </div>
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

@include('users.website.footer')