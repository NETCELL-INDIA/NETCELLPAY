    <footer class="nc-footer">
        <div class="nc-container">
            <div class="nc-footer-grid">
                <div class="nc-footer-brand">
                    <a class="nc-brand nc-brand-footer" href="{{ url('/') }}">
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
                    <p>Simple, secure digital payment services that help retailers and businesses serve customers better.</p>
                    <div class="nc-footer-trust">
                        <span><i class="fas fa-shield-alt"></i> Secure platform</span>
                        <span><i class="fas fa-headset"></i> Dedicated support</span>
                    </div>
                </div>

                <div class="nc-footer-column">
                    <h3>Company</h3>
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ url('/about-us') }}">About us</a>
                    <a href="{{ url('/services') }}">Services</a>
                    <a href="{{ url('/contact-us') }}">Contact</a>
                </div>

                <div class="nc-footer-column">
                    <h3>Legal</h3>
                    <a href="{{ url('/privacy-policy') }}">Privacy policy</a>
                    <a href="{{ url('/term-and-condition') }}">Terms & conditions</a>
                    <a href="{{ url('/refunds') }}">Refund & cancellation</a>
                    <a href="{{ url('/users/login') }}">Customer login</a>
                </div>

                <div class="nc-footer-column nc-footer-contact">
                    <h3>Contact us</h3>
                    @if(!empty($company->support_number_2) || !empty($company->support_number))
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $company->support_number_2 ?: $company->support_number) }}">
                            <i class="fas fa-phone"></i>
                            <span>{{ $company->support_number_2 ?: $company->support_number }}</span>
                        </a>
                    @endif
                    @if(!empty($company->support_email))
                        <a href="mailto:{{ $company->support_email }}">
                            <i class="fas fa-envelope"></i><span>{{ $company->support_email }}</span>
                        </a>
                    @endif
                    @if(!empty($company->company_address))
                        <p><i class="fas fa-map-marker-alt"></i><span>{{ $company->company_address }}</span></p>
                    @endif
                </div>
            </div>

            @if(!empty($company->google_map_url))
                <div class="nc-footer-map">
                    <iframe src="{{ $company->google_map_url }}" title="{{ $company->company_name }} location"
                        width="100%" height="220" style="border:0;" allowfullscreen loading="lazy"></iframe>
                </div>
            @endif

            <div class="nc-footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $company->company_name }}. All rights reserved.</p>
                <p>Built for a smarter digital India.</p>
            </div>
        </div>
    </footer>

    <script src="{{ URL::asset('web_template/js/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/bootstrap.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/jquery.appear.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/jquery.easing.min.js') }}"></script>
    <script src="{{ URL::asset('web_template/js/wow.min.js') }}"></script>
    <script>
        (function () {
            var toggle = document.querySelector('.nc-menu-toggle');
            var navigation = document.getElementById('nc-navigation');
            var header = document.getElementById('site-header');

            if (toggle && navigation) {
                toggle.addEventListener('click', function () {
                    var isOpen = navigation.classList.toggle('is-open');
                    toggle.classList.toggle('is-open', isOpen);
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            }

            window.addEventListener('scroll', function () {
                if (header) {
                    header.classList.toggle('is-scrolled', window.scrollY > 12);
                }
            }, { passive: true });
        }());
    </script>
</body>
</html>