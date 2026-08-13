@include('users.website.header')

@php
    $siteBanners = website_media_items('banner');
    $siteAds = website_media_items('ad');
    $homePage = website_page('home');
    $homeHeading = website_page_text($homePage, 'heading');
    $homeBody = website_page_text($homePage, 'body');
@endphp

<main class="nc-home">
    @if(count($siteBanners))
    <section class="nc-site-banners">
        <div class="nc-container">
            @foreach($siteBanners as $banner)
                @php $src = website_media_url($banner->image); @endphp
                @if($src)
                    @if(!empty($banner->link_url))
                        <a href="{{ $banner->link_url }}" class="nc-site-banner-item">
                            <img src="{{ $src }}" alt="{{ $banner->title }}">
                        </a>
                    @else
                        <div class="nc-site-banner-item">
                            <img src="{{ $src }}" alt="{{ $banner->title }}">
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </section>
    @endif

    <section class="nc-hero">
        <div class="nc-hero-glow nc-hero-glow-one"></div>
        <div class="nc-hero-glow nc-hero-glow-two"></div>
        <div class="nc-container nc-hero-grid">
            <div class="nc-hero-content">
                <span class="nc-eyebrow"><i class="fas fa-bolt"></i> One platform. Endless possibilities.</span>
                <h1>{!! $homeHeading !== '' ? e($homeHeading) : 'Powering every payment, <span>every business.</span>' !!}</h1>
                <p>{{ $homeBody !== '' ? $homeBody : ($company->company_name.' brings recharges, bill payments, money transfers and assisted banking together in one fast, secure platform.') }}</p>
                <div class="nc-hero-actions">
                    <a class="nc-button nc-button-primary" href="{{ url('/users/login') }}">
                        Get started <i class="fas fa-arrow-right"></i>
                    </a>
                    <a class="nc-button nc-button-ghost" href="{{ url('/services') }}">
                        Explore services
                    </a>
                </div>
                <div class="nc-hero-proof">
                    <div class="nc-avatar-stack" aria-hidden="true">
                        <span>NP</span><span>₹</span><span>24</span>
                    </div>
                    <p><strong>Trusted operations</strong><br>Built for retailers and growing businesses</p>
                </div>
            </div>

            <div class="nc-hero-visual" aria-label="Digital payment dashboard preview">
                <div class="nc-dashboard-card">
                    <div class="nc-dashboard-head">
                        <div>
                            <small>Available services</small>
                            <strong>All-in-one dashboard</strong>
                        </div>
                        <span class="nc-live-pill"><i></i> Live</span>
                    </div>
                    <div class="nc-balance-card">
                        <div>
                            <small>Fast settlement</small>
                            <strong>Secure & reliable</strong>
                        </div>
                        <span class="nc-balance-icon"><i class="fas fa-wallet"></i></span>
                    </div>
                    <div class="nc-dashboard-services">
                        <div><span><i class="fas fa-mobile-alt"></i></span><small>Recharge</small></div>
                        <div><span><i class="fas fa-file-invoice"></i></span><small>BBPS</small></div>
                        <div><span><i class="fas fa-fingerprint"></i></span><small>AEPS</small></div>
                        <div><span><i class="fas fa-exchange-alt"></i></span><small>Transfer</small></div>
                    </div>
                    <div class="nc-activity-card">
                        <div class="nc-activity-icon"><i class="fas fa-check"></i></div>
                        <div><strong>Transaction successful</strong><small>Processed securely in seconds</small></div>
                        <span>Done</span>
                    </div>
                </div>
                <div class="nc-float-card nc-float-security">
                    <i class="fas fa-shield-alt"></i>
                    <div><strong>Protected</strong><small>Secure transactions</small></div>
                </div>
                <div class="nc-float-card nc-float-support">
                    <i class="fas fa-headset"></i>
                    <div><strong>Here to help</strong><small>Dedicated support</small></div>
                </div>
            </div>
        </div>
    </section>

    <section class="nc-trust-strip">
        <div class="nc-container nc-trust-grid">
            <div><strong>24/7</strong><span>Platform access</span></div>
            <div><strong>10+</strong><span>Digital services</span></div>
            <div><strong>Fast</strong><span>Transaction processing</span></div>
            <div><strong>Secure</strong><span>Business operations</span></div>
        </div>
    </section>

    <section class="nc-section nc-services-section" id="services">
        <div class="nc-container">
            <div class="nc-section-heading">
                <div>
                    <span class="nc-kicker">Everything you need</span>
                    <h2>Financial services, simplified.</h2>
                </div>
                <p>Offer more services to your customers and manage every transaction from one unified platform.</p>
            </div>

            <div class="nc-service-grid">
                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/blog/01.png') }}" alt="Mobile and DTH recharge">
                    </div>
                    <span class="nc-service-icon nc-icon-purple"><i class="fas fa-mobile-alt"></i></span>
                    <h3>Mobile & DTH Recharge</h3>
                    <p>Instant prepaid, postpaid, DTH and data card recharges across leading operators.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/blog/02.png') }}" alt="Bharat Bill Payment System">
                    </div>
                    <span class="nc-service-icon nc-icon-teal"><i class="fas fa-file-invoice"></i></span>
                    <h3>Bill Payments</h3>
                    <p>Pay electricity, water, gas, broadband and other bills through BBPS.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/about/2.png') }}" alt="Aadhaar enabled payment service">
                    </div>
                    <span class="nc-service-icon nc-icon-blue"><i class="fas fa-fingerprint"></i></span>
                    <h3>AEPS Banking</h3>
                    <p>Enable Aadhaar-authenticated cash withdrawal, balance enquiry and mini statements.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/blog/10.jpg') }}" alt="Domestic money transfer">
                    </div>
                    <span class="nc-service-icon nc-icon-orange"><i class="fas fa-exchange-alt"></i></span>
                    <h3>Money Transfer</h3>
                    <p>Send money quickly to bank accounts across India through a simple assisted flow.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/blog/03.png') }}" alt="FASTag recharge">
                    </div>
                    <span class="nc-service-icon nc-icon-pink"><i class="fas fa-car"></i></span>
                    <h3>FASTag Recharge</h3>
                    <p>Recharge FASTag accounts and keep highway travel seamless for your customers.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="nc-service-card">
                    <div class="nc-service-image">
                        <img src="{{ URL::asset('web_template/img/blog/06.png') }}" alt="Micro ATM">
                    </div>
                    <span class="nc-service-icon nc-icon-green"><i class="fas fa-credit-card"></i></span>
                    <h3>Micro ATM</h3>
                    <p>Bring convenient card-based cash and banking services closer to every customer.</p>
                    <a href="{{ url('/services') }}">Learn more <i class="fas fa-arrow-right"></i></a>
                </article>
            </div>
        </div>
    </section>

    <section class="nc-section nc-value-section">
        <div class="nc-container nc-value-grid">
            <div class="nc-value-visual">
                <div class="nc-orbit nc-orbit-one"></div>
                <div class="nc-orbit nc-orbit-two"></div>
                <div class="nc-phone-frame">
                    <div class="nc-phone-top"><span></span></div>
                    <div class="nc-phone-screen">
                        <div class="nc-phone-brand">
                            <span class="nc-brand-symbol"><i class="fas fa-bolt"></i></span>
                            <strong>{{ $company->company_name }}</strong>
                        </div>
                        <small>Quick services</small>
                        <div class="nc-phone-services">
                            <span><i class="fas fa-mobile-alt"></i>Recharge</span>
                            <span><i class="fas fa-bolt"></i>Electricity</span>
                            <span><i class="fas fa-fingerprint"></i>AEPS</span>
                            <span><i class="fas fa-university"></i>Banking</span>
                        </div>
                        <div class="nc-phone-success">
                            <i class="fas fa-check-circle"></i>
                            <div><strong>Simple. Fast. Secure.</strong><small>Everything in one place</small></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nc-value-content">
                <span class="nc-kicker">Built for your growth</span>
                <h2>A better way to run your digital services business.</h2>
                <p>Spend less time switching between systems and more time serving customers. {{ $company->company_name }} gives your business the tools to operate with confidence.</p>
                <div class="nc-benefit-list">
                    <div>
                        <span><i class="fas fa-bolt"></i></span>
                        <div><h3>Fast transactions</h3><p>Responsive workflows designed to help you serve customers without delay.</p></div>
                    </div>
                    <div>
                        <span><i class="fas fa-chart-line"></i></span>
                        <div><h3>One clear dashboard</h3><p>Track activity and manage multiple services from a single account.</p></div>
                    </div>
                    <div>
                        <span><i class="fas fa-user-shield"></i></span>
                        <div><h3>Secure by design</h3><p>Account controls and protected transaction flows keep operations safer.</p></div>
                    </div>
                </div>
                <a class="nc-text-link" href="{{ url('/about-us') }}">Discover our platform <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <section class="nc-section nc-how-section">
        <div class="nc-container">
            <div class="nc-centered-heading">
                <span class="nc-kicker">Get started easily</span>
                <h2>From login to transaction in three steps.</h2>
                <p>A focused workflow that keeps everyday financial services straightforward.</p>
            </div>
            <div class="nc-steps-grid">
                <article>
                    <span class="nc-step-number">01</span>
                    <div class="nc-step-icon"><i class="fas fa-user-check"></i></div>
                    <h3>Access your account</h3>
                    <p>Sign in securely using your registered mobile number and password.</p>
                </article>
                <article>
                    <span class="nc-step-number">02</span>
                    <div class="nc-step-icon"><i class="fas fa-th-large"></i></div>
                    <h3>Choose a service</h3>
                    <p>Select recharge, BBPS, AEPS, money transfer or another available service.</p>
                </article>
                <article>
                    <span class="nc-step-number">03</span>
                    <div class="nc-step-icon"><i class="fas fa-check-double"></i></div>
                    <h3>Complete instantly</h3>
                    <p>Enter the details, confirm the transaction and receive its status in real time.</p>
                </article>
            </div>
        </div>
    </section>

    @if(count($siteAds))
    <section class="nc-section nc-site-ads">
        <div class="nc-container">
            <div class="nc-section-heading">
                <div>
                    <span class="nc-kicker">Offers</span>
                    <h2>Latest ads &amp; photos</h2>
                </div>
            </div>
            <div class="nc-site-ads-grid">
                @foreach($siteAds as $ad)
                    @php $src = website_media_url($ad->image); @endphp
                    @if($src)
                        @if(!empty($ad->link_url))
                            <a class="nc-site-ad-card" href="{{ $ad->link_url }}">
                                <img src="{{ $src }}" alt="{{ $ad->title }}">
                                <strong>{{ $ad->title }}</strong>
                            </a>
                        @else
                            <div class="nc-site-ad-card">
                                <img src="{{ $src }}" alt="{{ $ad->title }}">
                                <strong>{{ $ad->title }}</strong>
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="nc-section nc-security-section">
        <div class="nc-container nc-security-card">
            <div class="nc-security-copy">
                <span class="nc-eyebrow nc-eyebrow-light"><i class="fas fa-lock"></i> Security first</span>
                <h2>Your business deserves confidence with every transaction.</h2>
                <p>We combine controlled access, secure workflows and dependable infrastructure to protect your everyday operations.</p>
                <div class="nc-security-points">
                    <span><i class="fas fa-check"></i> Protected account access</span>
                    <span><i class="fas fa-check"></i> Transparent transaction status</span>
                    <span><i class="fas fa-check"></i> Reliable platform support</span>
                </div>
            </div>
            <div class="nc-shield-visual">
                <div class="nc-shield-ring"><i class="fas fa-shield-alt"></i></div>
                <span class="nc-security-tag nc-tag-one"><i class="fas fa-lock"></i> Encrypted</span>
                <span class="nc-security-tag nc-tag-two"><i class="fas fa-check-circle"></i> Verified</span>
            </div>
        </div>
    </section>

    <section class="nc-section nc-final-cta">
        <div class="nc-container nc-cta-card">
            <div>
                <span class="nc-kicker">Ready when you are</span>
                <h2>Move your business forward with {{ $company->company_name }}.</h2>
                <p>Log in to access your services or speak with our team to learn how the platform can support your business.</p>
            </div>
            <div class="nc-cta-actions">
                <a class="nc-button nc-button-light" href="{{ url('/users/login') }}">Login to dashboard</a>
                <a class="nc-button nc-button-outline-light" href="{{ url('/contact-us') }}">Contact us</a>
            </div>
        </div>
    </section>
</main>

@include('users.website.footer')
