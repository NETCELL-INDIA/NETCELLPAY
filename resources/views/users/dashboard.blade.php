@extends('layouts.master')
@section('title')
Dashboard
@endsection
@section('css')
<link href="{{ URL::asset('assets/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/libs/swiper/swiper.min.css') }}" rel="stylesheet" type="text/css" /> <!--datatable
    responsive css--> <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css"
    rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endsection @section('content') 
    <style> 
    * {box-sizing: border-box;}
      /* body {font-family: Verdana, sans-serif;} */
    .mySlides {display: none;}
    img {vertical-align: middle;}

    /* Slideshow container */
    .slideshow-container {
      max-width: 1000px;
      position: relative;
      margin: auto;
    }

    /* Caption text */
    .text {
      font-weight: bold;
      color: #363d48;
      font-size: 15px;
      padding: 8px 12px;
      position: absolute;
      bottom: 8px;
      width: 100%;
      text-align: center;
    }

    /* Number text (1/3 etc) */
    .numbertext {
    font-weight: bold;
    color: #000;
    font-size: 18px;
    padding: 8px 12px;
    position: absolute;
    top: 0;
    }

    /* The dots/bullets/indicators */
    .dot {
      height: 15px;
      width: 15px;
      margin: 0 2px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.6s ease;
    }

    .active, .dot:hover {
      background-color: #717171;
    }

    /* Fading animation */
    .fade {
      -webkit-animation-name: fade;
      -webkit-animation-duration: 1.5s;
      animation-name: fade;
      animation-duration: 1.5s;
    }

    @-webkit-keyframes fade {
      from {opacity: .4} 
      to {opacity: 1}
    }

    @keyframes fade {
      from {opacity: .4} 
      to {opacity: 1}
    }

    /* On smaller screens, decrease text size */
    @media only screen and (max-width: 300px) {
      .text {font-size: 11px}
    }

    /* Next & previous buttons */
    .prev, .next {
      background: #363d48;
      cursor: pointer;
      position: absolute;
      top: 50%;
      width: auto;
      padding: 16px;
      margin-top: -22px;
      color: white;
      font-weight: bold;
      font-size: 18px;
      transition: 0.6s ease;
      border-radius: 0 3px 3px 0;
      user-select: none;
    }

    /* Position the "next button" to the right */
    .next {
      right: 0;
      border-radius: 3px 0 0 3px;
     }

    /* On hover, add a black background color with a little bit see-through 
    */
    .prev:hover, .next:hover {
      background-color: rgba(0,0,0,0.8);
    }
     </style>
<div class="row mb-3 pb-1">
    <div class="col-12">
        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
            <div class="flex-grow-1">
                <h4 class="fs-16 mb-1" id="dayMessage"></h4>
                <p class="text-muted mb-0">Here's what's happening with your store
                    today.</p>
            </div>
            <div class="mt-3 mt-lg-0">
                <form action="javascript:void(0);">
                    <div class="row g-3 mb-0 align-items-center">
                        <div class="col-sm-auto">
                            <div class="input-group">
                                <input type="date" class="form-control" name="from_date"
                                    value="{{\Carbon\Carbon::today()->format('Y-m-d')}}" id="from_date">
                                <a style="padding: 6px;">To</a>
                                <input type="date" class="form-control" name="to_date"
                                    value="{{\Carbon\Carbon::today()->format('Y-m-d')}}" id="to_date">

                            </div>

                        </div>
                        <!--end col-->
                        <div class="col-auto">
                            <button type="button" class="btn btn-success waves-effect waves-light" id="search_btn"
                                onclick="fetchAllSearch()"><i class="ri-search-line align-middle me-1"></i>
                                Search</button>
                        </div>
                        <!--end col-->

                    </div>
                    <!--end row-->
                </form>
            </div>
        </div><!-- end card header -->
    </div>
    <!--end col-->
</div>
<!--end row-->
<div class="row">
    <div class="col-xxl-12">
        <div class="card" style="padding: 8px;">
            <marquee style="font-size: 20px;color: red;" id="da_announcements_data">

            </marquee>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xxl-4">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">My Profile</h4>
            </div><!-- end card header -->
            <div class="card-body pt-3">
                <table class="table table-success table-striped align-middle table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th>Outlet Name : </th>
                            <th id="dp_outlet_name"></th>
                        </tr>
                        <tr>
                            <th>Role Name : </th>
                            <th id="dp_role_name"></th>
                        </tr>
                        <tr>
                            <th>First Name : </th>
                            <th id="dp_first_name"></th>
                        </tr>
                        <tr>
                            <th>Last Name : </th>
                            <th id="dp_last_name"></th>
                        </tr>
                        <tr>
                            <th>Mobile Number : </th>
                            <th id="dp_mobile_number"></th>
                        </tr>
                        <tr>
                            <th>Email Address : </th>
                            <th id="dp_email_address"></th>
                        </tr>
                    </tbody>
                </table>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xxl-8">
        <div class="card card-height-100">
            <div class="card-body p-0">
                <div class="slideshow-container">
                @php
                    $i=1;
                    $t = count($slider_list);
                @endphp
                @foreach($slider_list as $list)
                    <div class="mySlides">
                        <div class="numbertext">{{$i}} / {{$t}}</div>
                        <img src="{{env('ADMIN_HOST')}}/slider_image/{{$list->image}}" style="width:100%;height: 352px;border-radius: 5px;">
                        <div class="text">{{$list->title}}</div>
                    </div>
                    @php
                        $i++;
                    @endphp
                @endforeach
                    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                    <a class="next" onclick="plusSlides(1)">&#10095;</a>
                </div>
                <div style="text-align:center;display:none;">
                @php
                    $i=1;
                @endphp
                @foreach($slider_list as $list)
                    <span class="dot" onclick="currentSlide({{$i}})"></span>
                    @php
                        $i++;
                    @endphp
                @endforeach
                </div>

            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
</div><!-- end row -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #a8ffaa;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Success</p>
                    </div>
                    <div class="flex-shrink-0">
                        <h5 class="text-success fs-20 mb-0" id="rc_success_hit">0</h5>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_success_amount">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-success-subtle rounded">
                            <i class="bx bxs-check-circle text-success" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ffdc88;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Pending</p>
                    </div>
                    <div class="flex-shrink-0">
                        <h5 class="text-warning fs-20 mb-0" id="rc_pending_hit">0</h5>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_pending_amount">0</span></h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-info-subtle rounded fs-3">
                            <i class=" bx bxs-time-five text-warning" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #ed9c9c;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Failed</p>
                    </div>
                    <div class="flex-shrink-0">
                        <h5 class="text-danger fs-20 mb-0" id="rc_failed_hit">0</h5>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_failed_amount">0</span>
                        </h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-warning-subtle rounded fs-3">
                            <i class="bx bxs-x-circle text-danger" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b2a0da;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Refund</p>
                    </div>
                    <div class="flex-shrink-0">
                        <h5 class="text-secondary fs-20 mb-0" id="rc_refund_hit">0</h5>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_refund_amount">0</span>
                        </h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-warning-subtle rounded fs-3">
                            <i class=" bx bxs-bank text-secondary" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
</div>
<div class="row">
    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b6e1ef;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Turnover</p>
                    </div>

                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_turnover_amount">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-success-subtle rounded">
                            <i class="bx bx-trending-up text-info" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b6e1ef;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Commission</p>
                    </div>

                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_commission_amount">0</span></h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-info-subtle rounded fs-3">
                            <i class="bx bxs-offer text-info" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b6e1ef;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Receive Money</p>
                    </div>

                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">₹ <span id="rc_receive_amount">0</span>
                        </h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-warning-subtle rounded fs-3">
                            <i class="bx bxs-left-down-arrow-circle text-info"
                                style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->

    <div class="col-xl-3 col-md-6">
        <!-- card -->
        <div class="card" style="background: #b6e1ef;">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                            Total Open/Under Review Complaints</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span id="rc_complaint_hit">0</span>
                        </h4>

                    </div>
                    <div class="flex-shrink-0">
                        <span class="bg-warning-subtle rounded fs-3">
                            <i class="bx bxs-help-circle text-info" style="font-size: 4.5rem !important;"></i>
                        </span>
                    </div>
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Provider Sale Reports List</h4>
                <div class="flex-shrink-0">
                </div>
            </div>
            <div class="card-body" id="provider_list_result">
                <h4 class="text-center text-secondary my-3">No record found</h4>
            </div>
        </div>
    </div>
</div>


@endsection
@section('script')
<script>
    var slideIndex = 0;
    showSlides(slideIndex);

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
      if (n > slides.length) {slideIndex = 1}    
      if (n < 1) {slideIndex = slides.length}
      for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none"; 

      } 
  
        slideIndex++;
      if (slideIndex > slides.length) {slideIndex = 1}    
      for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
  
      }
 
      slides[slideIndex-1].style.display = "block";  
      dots[slideIndex-1].className += " active";
      setTimeout(showSlides, 5000); // Change image every 10 seconds
    }
</script>
<script>

    var data = $.parseJSON(localStorage.getItem("profileData"));
    $("#dp_first_name").text(data.user.first_name);
    $("#dp_middle_name").text(data.user.middle_name);
    $("#dp_last_name").text(data.user.last_name);
    $("#dp_outlet_name").text(data.user.outlet_name);
    $("#dp_role_name").text(data.user.role_name);
    $("#dp_mobile_number").text("+91-" + data.user.mobile_number);
    $("#dp_email_address").text(data.user.email_address);
    $("#da_announcements_data").text(data.announcements);

    fetchAllSearch();
    function fetchAllSearch() {
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();
        //var tbl_type = $("#tbl_type").val();
        $("#search_btn").html('<span class="d-flex align-items-center"><span class="spinner-border flex-shrink-0" role="status"><span class="visually-hidden">Loading...</span></span><span class="flex-grow-1 ms-2">Loading...</span></span>');
        $('#search_btn').prop('disabled', true);
        $.ajax({
            url: '{{ route('dashboardReportsList') }}',
            method: 'post',
            data: {
                from_date: from_date,
                to_date: to_date,
                _token: '{{csrf_token()}}',
            },
            success: function (res) {
                if (res.rc_reports) {
                    $("#rc_success_hit").text(res.rc_reports.rc_success_hit);
                    $("#rc_success_amount").text(res.rc_reports.rc_success_amount.toFixed(2));
                    $("#rc_pending_hit").text(res.rc_reports.rc_pending_hit);
                    $("#rc_pending_amount").text(res.rc_reports.rc_pending_amount.toFixed(2));
                    $("#rc_failed_hit").text(res.rc_reports.rc_failed_hit);
                    $("#rc_failed_amount").text(res.rc_reports.rc_failed_amount.toFixed(2));
                    $("#rc_refund_hit").text(res.rc_reports.rc_refund_hit);
                    $("#rc_refund_amount").text(res.rc_reports.rc_refund_amount.toFixed(2));
                    $("#rc_turnover_amount").text(res.rc_reports.rc_success_amount.toFixed(2));
                    $("#rc_commission_amount").text(res.rc_reports.rc_commission.toFixed(2));
                    //$("#rc_success_amount").text(res.rc_reports.rc_success_amount);
                    $("#rc_receive_amount").text(res.rc_reports.rc_receive_money.toFixed(2));
                    //$("#rc_complaint_hit").text(0);
                    $("#rc_complaint_hit").text(res.rc_reports.rc_complaint_hit);
                }
                $("#search_btn").html('<i class="ri-search-line align-middle me-1"></i>Search');
                $('#search_btn').prop('disabled', false);
                $("#provider_list_result").html(res.provider_list);
                $("#slider_list_carousel_indicators").html(res.slider_list_carousel_indicators);
                $("#slider_list_carousel_inner").html(res.slider_list_carousel_inner);
                var table = new DataTable('#scroll-vertical', {
                    "scrollY": "250px",
                    "scrollCollapse": true,
                    "paging": false
                });
                $('#example').DataTable({
                    order: [0, 'desc']
                });
            }
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

<!-- apexcharts -->
<script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('/assets/libs/jsvectormap/jsvectormap.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/swiper/swiper.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/pages/dashboard-crm.init.js') }}"></script>
<script src="{{ URL::asset('/assets/js/pages/dashboard-ecommerce.init.js') }}"></script>
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection