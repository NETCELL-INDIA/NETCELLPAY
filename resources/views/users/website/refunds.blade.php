@include('users.website.header')

<div class="breadcrumb-area shadow dark text-center bg-fixed text-light" style="background-image: url({{ URL::asset('web_template/img/banner/2.jpg') }});">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Refund & Cancellation</h1>
                <ul class="breadcrumb">
                    <li><a href="home"><i class="fas fa-home"></i> Home</a></li>

                    <li class="active">Refund & Cancellation</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@php $cmsPage = website_page('refunds'); $cmsBody = website_page_text($cmsPage) ?: trim((string) ($company->refund_policy ?? '')); @endphp
@if($cmsBody !== '')
<div class="about-area default-padding">
    <div class="container" style="padding:40px 0;white-space:pre-wrap;">{{ $cmsBody }}</div>
</div>
@else
<div class="about-area default-padding">
    <div class="container">
        <div class="outter-section">
            <div class="container">
                <div class="row">

                    <h4>Refund and Cancellation</h4>
                    <p>
                        Our focus is complete customer satisfaction. In the event, if you are displeased with the services provided, we will refund back the money, provided the reasons are genuine and proved after investigation. Please read the fine prints of each deal before buying it, it provides all the details about the services or the product you purchase.
                        In case of dissatisfaction from our services, clients have the liberty to cancel their projects and request a refund from us. Our Policy for the cancellation and refund will be as follows:
                    </p>

                    <h4>Cancellation Policy</h4>
                    <p>For Cancellations please contact the us at {{$company->support_email}} Requests received later than 7 business days prior to the end of the current service period will be treated as cancellation of services for the next service period.</p>

                    <h4>Refund Policy</h4>


                    <p>
                        We will try our best to provide best service to our user, In case any client is not completely satisfied with our service we can provide a refund.
                        If paid by credit card, refunds will be issued to the original credit card provided at the time of purchase and in case of payment gateway name payments refund will be made to the same account.
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>
@endif
@include('users.website.footer')