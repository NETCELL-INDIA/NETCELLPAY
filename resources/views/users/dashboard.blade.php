@extends('layouts.master')
@section('title')
Dashboard
@endsection
@section('css')
<link href="{{ URL::asset('assets/css/dashboard-modern.css') }}?v={{ user_build_serial() }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/libs/swiper/swiper.min.css') }}" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="dash-page">
    <div class="dash-outlet-bar mb-3">
        <div class="dash-outlet-icon">
            <i class="ri-store-2-line"></i>
        </div>
        <h3 id="dp_outlet_name">—</h3>
    </div>

    @if(!empty($show_balance_alert))
    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
        <i class="ri-error-warning-line me-2 fs-16"></i>
        <span>Wallet balance is below ₹ {{ number_format((float) $balance_alert, 2) }}. Please add money.</span>
    </div>
    @endif

    @if(!empty($is_retailer))
    <div class="dash-recharge-panel mb-3">
        <div class="dash-recharge-head">
            <h4>Recharge</h4>
            <p>Retailer account — recharge services only. Tap a service to start.</p>
        </div>
        <div class="dash-recharge-grid">
            @foreach($recharge_services as $svc)
            <a class="dash-recharge-card" href="{{ url($svc['route']) }}">
                <span class="dash-recharge-icon">
                    <img src="{{ URL::asset($svc['icon']) }}" alt="{{ $svc['name'] }}" onerror="this.src='{{ URL::asset('service_icon/mobile_1.png') }}'">
                </span>
                <strong>{{ $svc['name'] }}</strong>
                <span>Tap to recharge</span>
            </a>
            @endforeach
        </div>
        <div class="dash-recharge-example">
            <h5>Example — how to recharge</h5>
            <ol>
                <li>Tap <b>Mobile Recharge</b>.</li>
                <li>Enter a 10-digit number. Example: <code>9876543210</code></li>
                <li>Select operator and circle. Example: <b>Airtel</b> + <b>Karnataka</b>.</li>
                <li>Choose a plan or type amount. Example: <b>₹199</b>.</li>
                <li>Tap <b>Recharge</b>. Wallet must have enough balance.</li>
            </ol>
        </div>
    </div>
    @endif

    @if(count($slider_list) > 0)
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="dash-card dash-slider-wrap">
                <div class="dash-card-body p-2">
                    <div class="slideshow-container">
                        @php $i = 1; $t = count($slider_list); @endphp
                        @foreach($slider_list as $list)
                            <div class="mySlides fade">
                                <div class="numbertext">{{ $i }} / {{ $t }}</div>
                                <img src="{{ admin_slider_image_url($list->image) }}" alt="{{ $list->title }}">
                                <div class="text">{{ $list->title }}</div>
                            </div>
                            @php $i++; @endphp
                        @endforeach
                        <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                        <a class="next" onclick="plusSlides(1)">&#10095;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="dash-stat-grid">
        <div class="dash-stat-card is-success">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Success</p>
                <p class="dash-stat-hit" id="rc_success_hit">0</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_success_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-checkbox-circle-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-pending">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Pending</p>
                <p class="dash-stat-hit" id="rc_pending_hit">0</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_pending_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-time-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-failed">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Failed</p>
                <p class="dash-stat-hit" id="rc_failed_hit">0</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_failed_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-close-circle-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-refund">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Refund</p>
                <p class="dash-stat-hit" id="rc_refund_hit">0</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_refund_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-refund-2-fill"></i></span>
            </div>
        </div>
    </div>

    <div class="dash-stat-grid">
        <div class="dash-stat-card is-info">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Turnover</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_turnover_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-line-chart-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-info">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Commission</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_commission_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-percent-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-info">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Total Receive Money</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount">₹ <span id="rc_receive_amount">0</span></p>
                <span class="dash-stat-icon"><i class="ri-arrow-down-circle-fill"></i></span>
            </div>
        </div>

        <div class="dash-stat-card is-info">
            <div class="dash-stat-top">
                <p class="dash-stat-label">Open / Under Review Complaints</p>
            </div>
            <div class="dash-stat-bottom">
                <p class="dash-stat-amount"><span id="rc_complaint_hit">0</span></p>
                <span class="dash-stat-icon"><i class="ri-question-answer-fill"></i></span>
            </div>
        </div>
    </div>

    <div class="dash-card dash-reports-card mt-1">
        <div class="dash-card-head">
            <h4>Provider Sale Reports List</h4>
        </div>
        <div class="dash-card-body" id="provider_list_result">
            <h4 class="text-center text-secondary my-3">No records found</h4>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    var slideIndex = 0;
    if (document.getElementsByClassName("mySlides").length) {
        showSlides(slideIndex);
    }

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    function showSlides(n) {
        var i;
        var slides = document.getElementsByClassName("mySlides");
        var dots = document.getElementsByClassName("dot");
        if (!slides.length) return;
        if (n > slides.length) { slideIndex = 1; }
        if (n < 1) { slideIndex = slides.length; }
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slideIndex++;
        if (slideIndex > slides.length) { slideIndex = 1; }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        if (dots[slideIndex - 1]) {
            dots[slideIndex - 1].className += " active";
        }
        setTimeout(showSlides, 5000);
    }
</script>
<script>
    fetchAllSearch();

    function fetchAllSearch() {
        var today = new Date().toISOString().slice(0, 10);
        var from_date = $("#from_date").val() || today;
        var to_date = $("#to_date").val() || today;
        $.ajax({
            url: '{{ route('dashboardReportsList') }}',
            method: 'post',
            dataType: 'json',
            data: {
                from_date: from_date,
                to_date: to_date,
                _token: '{{ csrf_token() }}',
            },
            success: function (res) {
                function money(v) {
                    var n = Number(v);
                    if (!Number.isFinite(n)) n = 0;
                    return n.toFixed(2);
                }
                if (res.rc_reports) {
                    $("#rc_success_hit").text(res.rc_reports.rc_success_hit || 0);
                    $("#rc_success_amount").text(money(res.rc_reports.rc_success_amount));
                    $("#rc_pending_hit").text(res.rc_reports.rc_pending_hit || 0);
                    $("#rc_pending_amount").text(money(res.rc_reports.rc_pending_amount));
                    $("#rc_failed_hit").text(res.rc_reports.rc_failed_hit || 0);
                    $("#rc_failed_amount").text(money(res.rc_reports.rc_failed_amount));
                    $("#rc_refund_hit").text(res.rc_reports.rc_refund_hit || 0);
                    $("#rc_refund_amount").text(money(res.rc_reports.rc_refund_amount));
                    $("#rc_turnover_amount").text(money(res.rc_reports.rc_success_amount));
                    $("#rc_commission_amount").text(money(res.rc_reports.rc_commission));
                    $("#rc_receive_amount").text(money(res.rc_reports.rc_receive_money));
                    $("#rc_complaint_hit").text(res.rc_reports.rc_complaint_hit || 0);
                }
                $("#provider_list_result").html(res.provider_list || '<h4 class="text-center text-secondary my-3">No records found</h4>');
                if ($('#scroll-vertical').length && $.fn.DataTable) {
                    if ($.fn.DataTable.isDataTable('#scroll-vertical')) {
                        $('#scroll-vertical').DataTable().destroy();
                    }
                    new DataTable('#scroll-vertical', {
                        scrollY: "250px",
                        scrollCollapse: true,
                        paging: false
                    });
                }
            },
            error: function () {}
        });
    }
</script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
