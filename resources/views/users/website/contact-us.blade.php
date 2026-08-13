@include('users.website.header')

<div class="breadcrumb-area shadow dark text-center bg-fixed text-light" style="background-image: url({{ URL::asset('web_template/img/banner/2.jpg') }});">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Contact Us</h1>
                <ul class="breadcrumb">
                    <li><a href="home"><i class="fas fa-home"></i> Home</a></li>

                    <li class="active">Contact</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@php $cmsPage = website_page('contact-us'); @endphp
@if(website_page_text($cmsPage, 'body') !== '')
<div class="contact-info-area default-padding">
    <div class="container" style="padding:40px 0;white-space:pre-wrap;">
        @if(website_page_text($cmsPage, 'heading') !== '')
            <h2>{{ $cmsPage->heading }}</h2>
        @endif
        {{ $cmsPage->body }}
    </div>
</div>
@endif
<div class="contact-info-area default-padding">
    <div class="container">
        <div class="row">
            <!-- Start Contact Info -->
            <div class="contact-info">
                <div class="col-md-4 col-sm-4">
                    <div class="item">
                        <div class="icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="info">
                            <h4>Call Us</h4>
                            <span>{{$company->support_number}}</span>
                            <span>{{$company->support_number_2}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="item">
                        <div class="icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info">
                            <h4>Address</h4>
                            <span>{{$company->company_address}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="item">
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info">
                            <h4>Email Us</h4>
                            <span>{{$company->support_email}}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Contact Info -->

            <div class="seperator col-md-12">
                <span class="border"></span>
            </div>

            <!-- Start Maps & Contact Form -->
            <div class="maps-form">
                <div class="col-md-6 maps">
                    <h3>Our Location</h3>
                    <div class="google-maps">
                        <iframe src="{{$company->google_map_url}}" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
                <div class="col-md-6 form">
                    <div class="heading">
                        <h3>Contact Us</h3>

                    </div>
                    <form action="#" method="POST" class="contact-form">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="form-group">
                                    <input class="form-control" id="name" name="name" placeholder="Name" type="text">
                                    <span class="alert-error"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="form-group">
                                    <input class="form-control" id="email" name="email" placeholder="Email*" type="email">
                                    <span class="alert-error"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="form-group">
                                    <input class="form-control" id="phone" name="phone" placeholder="Phone" type="text">
                                    <span class="alert-error"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="form-group comments">
                                    <textarea class="form-control" id="comments" name="comments" placeholder="Tell Me About Services *"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <button type="submit" name="submit" id="submit">
                                    Send Message <i class="fa fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Alert Message -->
                        <div class="col-md-12 alert-notification">
                            <div id="message" class="alert-msg"></div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- End Maps & Contact Form -->

        </div>
    </div>
</div>
<!-- End Contact Info -->

@include('users.website.footer')