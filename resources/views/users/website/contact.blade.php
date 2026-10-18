@include('users.website.header')

@php
    $displayEmail = $supportEmail ?? 'support@netcellpay.in';
    $legal = $legalCompany ?? 'NETCELL RETAIL INDIA PRIVATE LIMITED';
    $product = $productName ?? 'NETCELL Multi Recharge';
@endphp

<main class="nc-contact-page">
    <section class="nc-contact-hero">
        <div class="nc-hero-glow nc-hero-glow-one"></div>
        <div class="nc-hero-glow nc-hero-glow-two"></div>
        <div class="nc-container">
            <span class="nc-eyebrow"><i class="fas fa-headset"></i> Contact &amp; Support</span>
            <h1>We’re here to help your business run smoothly.</h1>
            <p>
                Reach the NETCELL PAY support team for {{ $product }}, recharges, account access,
                payments, and technical help. This page is also used for app support verification.
            </p>
        </div>
    </section>

    <section class="nc-section nc-contact-section">
        <div class="nc-container">
            <div class="nc-contact-layout">
                <aside class="nc-contact-aside">
                    <div class="nc-contact-info-card">
                        <h2>Contact information</h2>
                        <dl class="nc-contact-dl">
                            <div>
                                <dt>Company</dt>
                                <dd>{{ $legal }}</dd>
                            </div>
                            <div>
                                <dt>Brand</dt>
                                <dd>NETCELL PAY</dd>
                            </div>
                            <div>
                                <dt>Product</dt>
                                <dd>{{ $product }}</dd>
                            </div>
                            <div>
                                <dt>Support email</dt>
                                <dd>
                                    <a href="mailto:{{ $displayEmail }}">{{ $displayEmail }}</a>
                                </dd>
                            </div>
                            <div>
                                <dt>Website</dt>
                                <dd>
                                    <a href="https://netcellpay.in" rel="noopener">https://netcellpay.in</a>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="nc-contact-info-card">
                        <h2>Support categories</h2>
                        <ul class="nc-support-list">
                            @foreach($categories as $label)
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>{{ $label }}</span></li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="nc-contact-info-card nc-contact-tip">
                        <h2>Need a quick reply?</h2>
                        <p>
                            Email us at
                            <a href="mailto:{{ $displayEmail }}">{{ $displayEmail }}</a>
                            with your registered mobile number and order ID (if related to a transaction).
                        </p>
                    </div>
                </aside>

                <div class="nc-contact-form-card">
                    <div class="nc-contact-form-head">
                        <span class="nc-kicker">Send a message</span>
                        <h2>Contact support</h2>
                        <p>Fill in the form below. Required fields are marked with an asterisk (*).</p>
                    </div>

                    @if(session('contact_success'))
                        <div class="nc-alert nc-alert-success" role="status">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <div>{{ session('contact_success') }}</div>
                        </div>
                    @endif

                    @if(session('contact_error'))
                        <div class="nc-alert nc-alert-error" role="alert">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <div>{{ session('contact_error') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="nc-alert nc-alert-error" role="alert">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <div>
                                <strong>Please fix the highlighted fields.</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('website.contact.submit') }}" class="nc-contact-form" id="ncContactForm" novalidate>
                        @csrf
                        {{-- Honeypot --}}
                        <div class="nc-hp" aria-hidden="true">
                            <label for="website_url">Website</label>
                            <input type="text" name="website_url" id="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="nc-form-grid">
                            <div class="nc-field">
                                <label for="full_name">Full name <span aria-hidden="true">*</span></label>
                                <input type="text" id="full_name" name="full_name" maxlength="120" required
                                    value="{{ old('full_name') }}"
                                    class="{{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                                    autocomplete="name">
                            </div>

                            <div class="nc-field">
                                <label for="email">Email address <span aria-hidden="true">*</span></label>
                                <input type="email" id="email" name="email" maxlength="190" required
                                    value="{{ old('email') }}"
                                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                    autocomplete="email">
                            </div>

                            <div class="nc-field nc-field-full">
                                <label for="subject">Subject <span aria-hidden="true">*</span></label>
                                <input type="text" id="subject" name="subject" maxlength="180" required
                                    value="{{ old('subject') }}"
                                    class="{{ $errors->has('subject') ? 'is-invalid' : '' }}">
                            </div>

                            <div class="nc-field nc-field-full">
                                <label for="category">Support category <span aria-hidden="true">*</span></label>
                                <select id="category" name="category" required class="{{ $errors->has('category') ? 'is-invalid' : '' }}">
                                    <option value="">Select a category</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="nc-field nc-field-full">
                                <label for="message">Message <span aria-hidden="true">*</span></label>
                                <textarea id="message" name="message" rows="6" maxlength="5000" required
                                    class="{{ $errors->has('message') ? 'is-invalid' : '' }}"
                                    placeholder="Describe your issue. Include registered mobile number and order ID when relevant.">{{ old('message') }}</textarea>
                            </div>
                        </div>

                        <div class="nc-form-actions">
                            <button type="submit" class="nc-button nc-button-primary" id="ncContactSubmit">
                                <span class="nc-btn-label">Submit message</span>
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                            <a class="nc-button nc-button-ghost" href="mailto:{{ $displayEmail }}">
                                Email support
                            </a>
                        </div>

                        @unless($mailConfigured)
                            <p class="nc-form-note">
                                Messages are saved securely for our support team. You can also reach us directly at
                                <a href="mailto:{{ $displayEmail }}">{{ $displayEmail }}</a>.
                            </p>
                        @endunless
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var form = document.getElementById('ncContactForm');
    var btn = document.getElementById('ncContactSubmit');
    if (!form || !btn) return;
    form.addEventListener('submit', function () {
        if (!form.checkValidity()) return;
        btn.disabled = true;
        btn.classList.add('is-loading');
        var label = btn.querySelector('.nc-btn-label');
        if (label) label.textContent = 'Sending…';
    });
}());
</script>

@include('users.website.footer')
