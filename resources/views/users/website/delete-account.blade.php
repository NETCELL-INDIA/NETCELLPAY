@include('users.website.header')

<main class="nc-contact-page">
    <section class="nc-contact-hero">
        <div class="nc-container">
            <span class="nc-eyebrow"><i class="fas fa-user-shield"></i> Account privacy</span>
            <h1>Request Account Deletion</h1>
            <p>
                {{ $legalCompany }} operates NETCELL PAY. Use this page to request deletion of your
                NETCELL PAY account and associated personal data, including after you uninstall the app.
            </p>
        </div>
    </section>

    <section class="nc-section nc-contact-section">
        <div class="nc-container">
            <div class="nc-contact-layout">
                <aside class="nc-contact-aside">
                    <div class="nc-contact-info-card">
                        <h2>How this works</h2>
                        <ol>
                            <li>Submit the mobile number registered on NETCELL PAY.</li>
                            <li>Enter the verification code sent to that account’s WhatsApp and email.</li>
                            <li>An administrator reviews the verified request.</li>
                            <li>When the request is completed, login credentials and profile details are removed.</li>
                        </ol>
                        <p>A request is not the same as a completed deletion. We do not delete an account from an unverified mobile number or email address.</p>
                    </div>
                    <div class="nc-contact-info-card">
                        <h2>What we retain</h2>
                        <p>
                            Recharge, wallet, payment, complaint, and tax records stay in the system because they are required for accounting and dispute handling.
                            Personal profile fields, passwords, API keys, and login history are removed when an administrator completes the request.
                        </p>
                        <p>
                            <a href="{{ url('/privacy-policy') }}">Privacy Policy</a><br>
                            Support: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                            @if(!empty($company->support_number))
                                <br>Phone: {{ $company->support_number }}
                            @endif
                        </p>
                    </div>
                </aside>

                <div class="nc-contact-form-card">
                    @if(session('deletion_success'))
                        <div class="alert alert-success" role="status">{{ session('deletion_success') }}</div>
                    @endif
                    @if(session('deletion_notice'))
                        <div class="alert alert-info" role="status">{{ session('deletion_notice') }}</div>
                    @endif
                    @if(session('deletion_error'))
                        <div class="alert alert-danger" role="alert">{{ session('deletion_error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <h2>Deletion request</h2>
                    <form method="post" action="{{ route('website.delete-account.submit') }}" id="delete-account-form">
                        @csrf
                        <div style="position:absolute;left:-9999px" aria-hidden="true">
                            <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="mb-3">
                            <label for="country_code">Country code</label>
                            <input class="form-control" id="country_code" name="country_code" value="{{ old('country_code', '+91') }}" inputmode="tel" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile_number">Registered mobile number</label>
                            <input class="form-control" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" inputmode="numeric" maxlength="10" placeholder="10-digit mobile number" required>
                        </div>
                        <div class="mb-3">
                            <label for="email">Registered email, if available</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Optional">
                        </div>
                        <div class="mb-3">
                            <label for="reason">Reason for deletion (optional)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="1000">{{ old('reason') }}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_delete" value="1" id="confirm_delete" {{ old('confirm_delete') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="confirm_delete">I want to request deletion of my NETCELL PAY account and eligible personal data.</label>
                        </div>
                        <button class="btn btn-primary" type="submit" id="delete-submit">Submit Request</button>
                    </form>

                    <hr>
                    <h2>Verify ownership</h2>
                    <form method="post" action="{{ route('website.delete-account.verify') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="otp">6-digit verification code</label>
                            <input class="form-control" id="otp" name="otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
                        </div>
                        <button class="btn btn-outline-primary" type="submit">Verify code</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
<script>
document.getElementById('delete-account-form').addEventListener('submit', function () {
    var button = document.getElementById('delete-submit');
    button.disabled = true;
    button.textContent = 'Submitting...';
});
</script>
@include('users.website.footer')
