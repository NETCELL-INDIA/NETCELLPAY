@extends('layouts.master')

@section('title') My Commission @endsection

@section('css')

<!--datatable css-->

<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<!--datatable responsive css-->

<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />

<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@endsection

@section('content')

@component('components.breadcrumb')

@slot('li_1') Profile @endslot

@slot('title') My Commission @endslot

@endcomponent





<div class="row">

    <div class="col-lg-12">

        <div class="card">

            <div class="card-header align-items-center d-flex">

                <h4 class="card-title mb-0 flex-grow-1">Commission List</h4>

                <div class="flex-shrink-0">

                    <div class="form-check form-switch form-switch-right form-switch-md">

                        <select class="form-select" name="service_id"  id="service_id" style="text-transform: uppercase;">

                            <option selected value="0">ALL</option>
                            @foreach ($services as $item)
                                <option value="{{$item->id}}">{{$item->service_name}}</option>
                            @endforeach
                        </select>

                    </div>

                </div>

            </div>

            <div class="card-body" id="list_result">

                <h4 class="text-center text-secondary my-3">No records found</h4>

            </div>

        </div>

    </div>

</div>



@endsection

@section('script')

<script>
    fetchAll(1,10);
    function fetchAllSearch() {
        $("#search_btn").text('Loading...');

        $('#search_btn').prop('disabled', true);

        $.ajax({

            url: '{{ route('myProfileCommissionList') }}',

            method: 'post',

            data: {
                page : 1,

                limit : 10,

                _token: '{{csrf_token()}}',

            },

            success: function(res) {

                $("#search_btn").text('Search');

                $('#search_btn').prop('disabled', false);

                $("#list_result").html(res);
            }

        });

    }



    $(document).on('change','#page_limit',function(){

        page = 1;

        limit = $('#page_limit').val();

        fetchAll(page,limit);

    });



    $(document).on('keyup','#searchValueTable',function(){

        var value = $( this ).val();

        if (this.value.length < 1) {

            $("#pagination_table tr").css("display", "");

        } else {

            $("#pagination_table tbody tr:not(:contains('"+this.value+"'))").css("display", "none");

            $("#pagination_table tbody tr:contains('"+this.value+"')").css("display", "");

        }

        //console.log(search);

    });

    function tableSearch(page) {

        limit = $('#page_limit').val();

        page = page;

        fetchAll(page,limit);

    }



    function fetchAll(page,limit) {
        var service_id = $("#service_id").val();
        $("#list_result").html('<h4 class="text-center text-secondary my-3">Loading...</h4>');

        $.ajax({

            url: '{{ route('myProfileCommissionList') }}',

            method: 'post',

            data: {_token: '{{csrf_token()}}',page,limit,service_id},

            success: function(res) {

                $("#list_result").html(res);

            }

        });

    }

    $(document).on('change','#service_id',function(){
        fetchAll(1,10);
    });

</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>



<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>



{{-- <script src="{{ URL::asset('assets/js/pages/datatables.init.js') }}"></script> --}}





<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>



<!--jquery cdn-->

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<!--select2 cdn-->

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="{{ URL::asset('/assets/js/pages/select2.init.js') }}">



@endsection

