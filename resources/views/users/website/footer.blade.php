<!-- Start Footer
    ============================================= -->
    <footer class="bg-dark default-padding-top text-light">
        <div class="container">
            <div class="row">
                <div class="f-items">
                    <div class="col-md-4 item">
                        <div class="f-item">
                            <img src="{{env('ADMIN_HOST')}}/company_logo/{{$company->company_logo}}" alt="Logo" style="width: auto;height: 60px;">
                            <p>
                                Welcome at {{$company->company_name}} to join a life long business opportunity with us Recharge is stands among best Online Mobile Recharge Service Provider including all major mobile operators and DTH recharge.
                            </p>

                            <div class="subscribe">
                                <form action="#">
                                    <div class="input-group stylish-input-group">
                                        <input type="email" placeholder="Enter your e-mail here" class="form-control" name="email">
                                        <button type="submit">
                                            <i class="fa fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-1 col-sm-6 item">
                        <div class="f-item link">
                            <h4>Support</h4>
                            <ul>
                                <li>
                                    <a href="home">Home</a>
                                </li>
                                <li>
                                    <a href="about-us">About</a>
                                </li>
                                <li>
                                    <a href="services">Service</a>
                                </li>
                                <li>
                                    <a href="contact-us">Contact</a>
                                </li>

                            </ul>
                        </div>
                    </div>
                    <div class="col-md-5 item">
                        <div class="f-item address">
                            <h4>Address</h4>
                            <ul>
                                <li>
                                    <i class="fas fa-map"></i>
                                    <p>Contact <span>{{$company->support_number_2}}</span></p>
                                </li>
                                <li>
                                    <i class="fas fa-envelope"></i>
                                    <p>Email <span><a href="#">{{$company->support_email}}</a></span></p>
                                </li>
                                <li>
                                    <i class="fas fa-map"></i>
                                    <p>Office <span>{{$company->company_address}}</span></p>
                                    <p></p>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <h4 class="map-sec">Reach Out</h4>
                        <iframe src="{{$company->google_map_url}}" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
        <!-- Start Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-6">

                            <p>&copy; Copyright 2021. All Rights Reserved by <a href="#">{{$company->company_name}}</a></p>
                        </div>
                        <div class="col-md-6 text-right link">
                            <ul>
                                <li>
                                    <a href="privacy-policy">Privacy Policy</a>
                                </li>

                                <li>
                                    <a href="term-and-condition">Term & Condition</a>
                                </li>

                                <li>
                                    <a href="refunds">Refund & Cancellation</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Footer Bottom -->
    </footer>
    <!-- End Footer -->
    <!-- jQuery Frameworks
    
    ============================================= -->
    <script src="{{ URL::asset('web_template/js/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/bootstrap.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/equal-height.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/jquery.appear.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/jquery.easing.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/modernizr.custom.13711.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/owl.carousel.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/wow.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/isotope.pkgd.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/count-to.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/bootsnav.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/main.js') }}"></script>

</body>
</html>