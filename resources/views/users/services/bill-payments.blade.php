@extends('layouts.master')

@section('title') Bill Payments @endsection

@section('css')
<style>
    .br_ui {
        border: 4px solid gray;
        border-radius: 30px;
    }

    .pointer {
        cursor: pointer;
    }
</style>

<link href="{{ URL::asset('/assets/libs/choices.js/choices.js.min.css') }}" rel="stylesheet" />
@endsection

@section('content')

@component('components.breadcrumb')

@slot('li_1') Services @endslot

@slot('title') Bill Payments @endslot

@endcomponent
<div class="row">

    <div class="col-lg-8">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1">Bill Payments</h4>

            </div>

            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-3 pointer" onclick="selectService(3,'Electricity')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/10.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Electricity</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(5,'Water')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/9.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Water</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(6,'FasTag')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/5.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">FasTag</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(7,'Cable Tv')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/6.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Cable Tv</h5>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-3 pointer" onclick="selectService(9,'Book Cylinder')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/7.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Book Cylinder</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(10,'Piped Gas')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/8.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Piped Gas</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(15,'Postpaid')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/3.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Postpaid</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(16,'Wifi/Landline')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/11.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Wifi/Landline</h5>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-3 pointer" onclick="selectService(14,'Broadband')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/23.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Broadband</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(17,'Housing Society')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/24.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Housing Society</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(12,'Credit Card')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/15.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Credit Card</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(8,'Insurance Pay')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/16.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Insurance</h5>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-3 pointer" onclick="selectService(4,'Loan Repayment')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/19.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Loan Repayment</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(11,'Subscription Fees')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/20.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Subscription Fees</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(21,'Google Play')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/21.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Google Play</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 pointer" onclick="selectService(13,'Municipal Taxes')">
                        <div class="card-body text-center br_ui">
                            <div class="avatar-md mb-2 mx-auto">
                                <img src="{{ URL::asset('service_logo/22.png')}}" alt="" style="height:70px">
                            </div>
                            <h5 id="candidate-name" class="mb-0">Municipal Taxes</h5>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="col-lg-4">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1" id="service_name">{{$service ? $service : "Select Service"}}
                </h4>

            </div>

            <div class="card-body">
                <div class="col-lg-12 col-md-12">
                    <div class="mb-3">
                        <label for="provider_id" class="form-label text-muted">Provider : </label>
                        <select class="form-control" data-choices name="provider_id" id="provider_id">
                            <option value="">Select Provider</option>
                            @foreach ($providers as $list)
                                <option value="{{$list->id}}">{{$list->provider_name}}</option>
                            @endforeach

                        </select>
                    </div>
                </div>
                <div id="params_div"></div>

                <div class="col-lg-12 col-md-12">
                    <div class="mb-3">
                        <button type="button" id="bill_fatch" onclick="billFatch()"
                            class="form-control btn btn-warning bg-gradient waves-effect waves-light" style="display:none">Bill Fetch Now</button>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection

@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
    function selectService(id, name) {
        window.location = 'bill-payments?id=' + id + '&name=' + name;

    }

    ///readonly disabled
    $("#provider_id").on("change", function (e) {
        $("#params_div").empty();
        $('#bill_fatch').hide();
        $.ajax({

            url: '{{ route('fetchProviderParams') }}',

            method: 'post',

            data: {

                id: e.target.value,

                _token: '{{ csrf_token() }}'

            },

            success: function (data) {
                if (data.type == "success") {
                    params = JSON.parse(data.biller.biller_data);
                    console.log(params);
                    var param = {}; // my object
                    var params_data =  []; // my array
                    param = {param:params.data.label_1,regex:params.data.regex_1,option:params.data.regex_1_autocomplete_values}
                    params_data.push(param);
                    param = {param:params.data.label_2,regex:params.data.regex_2,option:params.data.regex_2_autocomplete_values}
                    params_data.push(param);
                    param = {param:params.data.label_3,regex:params.data.regex_3,option:params.data.regex_3_autocomplete_values}
                    params_data.push(param);
                    param = {param:params.data.label_4,regex:params.data.regex_4,option:params.data.regex_4_autocomplete_values}
                    params_data.push(param);
                    param = {param:params.data.label_5,regex:params.data.regex_5,option:params.data.regex_5_autocomplete_values}
                    params_data.push(param);
                    console.log(params_data);
                    /////
                    
                    html = '';
                    count = parseInt(params.input_count);
                    for (i = 0; i < parseInt(count); ++i) {
                        if(params_data[i].regex){
                            regex = '<input type="text" class="form-control" name="' + "filed_"+i + '" value="" id="' + "filed_"+i + '"  pattern="'+params_data[i].regex+'" required placeholder="' + params_data[i].param +'">';
                        }else{
                            options = '';
                            for (i2 = 0; i2 < params_data[i].option.length; ++i2) {
                                options += '<option value="' + params_data[i].option[i2].value + '">' + params_data[i].option[i2].display_name + '</option>';
                            }
                            regex = '<select class="form-control" data-choices name="' + "filed_"+i + '" id="' + "filed_"+i + '">'+
                            '<option value="">Select ' + params_data[i].param + '</option>'+
                                options +
                            '</select>';
                        }
                        html += '<div class="col-lg-12 col-md-12">' +
                            '<div class="mb-3">' +
                            '<label for="' + "filed_"+i + '" class="form-label text-muted">' + params_data[i].param +'</label>' +
                             regex +
                            '</div>' +
                            '</div>';
                       //    $("#params_div").append(html); 
                    }
                    $("#params_div").append(html); 
                    $('#bill_fatch').show();
                } else {
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
            },
            error: function (err) {
                Error_Msg("Oops...", "Something went wrong!", "error");

                $("#get_roffer_btn").text('Plans');

                $('#get_roffer_btn').prop('disabled', false);

            }

        });

    });
</script>

<script src="{{ URL::asset('/assets/libs/prismjs/prismjs.min.js') }}"></script>
<script src="{{ URL::asset('/assets/libs/choices.js/choices.js.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>

@endsection