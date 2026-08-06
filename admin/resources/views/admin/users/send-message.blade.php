@extends('layouts.master')
@section('title')
    Send Message
@endsection
@section('css')
    <link href="{{ URL::asset('assets/libs/quill/quill.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        Users
        @endslot
        @slot('title')
        Send Message
        @endslot
    @endcomponent
    

    <div class="row mt-2">
        <div class="col-lg-12">
                <div class="justify-content-between d-flex align-items-center mb-3">
                
            </div>
            <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Send Message Text</h4>
                <div class="flex-shrink-0">
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="update()" id="edit_details_btn" >Send Message</button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-3">
                            <input type="hidden" name="id_value" id="id_value" value="0">
                            <div>
                                <label class="form-label mb-0">User Search</label>
                                <input type="text" class="form-control" name="user_id" value="" id="user_id">
                                <div id="user_list">
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label mb-0">Role</label>
                            <select class="form-select mb-3 role_name" aria-label="Default select example" id="role_name">
                                <option selected value="0">All</option>
                                @foreach ($role as $v)
                                <option value="{{$v->id}}">{{$v->role_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label mb-0">Message Source</label>
                            <select class="form-select mb-3 msg_source" aria-label="Default select example" id="msg_source">
                                <option selected value="">Select Message Source</option>
                                    <option value="SMS">SMS</option>
                                    <option value="WHATSAPP">WHATSAPP</option>          
                                    <option value="EMAIL">EMAIL</option>          
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <div>
                                <label class="form-label mb-0">Subject</label>
                                <input type="text" class="form-control" name="subject" value="" id="subject">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div>
                                <label class="form-label mb-0">Template Id</label>
                                <input type="text" class="form-control" name="tmp_id" value="" id="tmp_id">
                            </div>
                        </div>
                    </div>
                    <div class="bubble-editor message_text" style="height: 300px;">
                        
                    </div> <!-- end bubble-editor-->
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
@section('script')
<script>
    function capitalizeFirstLetter(string){
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function Error_Msg(title,text,icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonClass: 'btn btn-primary w-xs mt-2',
            buttonsStyling: false,
            showCloseButton: true
        });
    }


    $('#user_id').on('keyup', function(){
        $('#id_value').val("0");
        search();
    });

    function search(){
        var keyword = $('#user_id').val();
        $.ajax({
            url: '{{ route('rechargeSearchUuser') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}',keyword:keyword},
            success: function(res) {
                $('#user_list').show();
                console.log(res);
                htmlView = "";
                //$('#user_list').empty();d
                for(let i = 0; i < res.users.length; i++){
                    htmlView += '<a onclick="selectValue(`'+res.users[i].id+'`,`'+res.users[i].outlet_name+' | '+res.users[i].first_name+' '+res.users[i].middle_name+' '+res.users[i].last_name+' | '+res.users[i].mobile_number+'`)">'+res.users[i].outlet_name+' | '+res.users[i].first_name+' '+res.users[i].middle_name+' '+res.users[i].last_name+' | '+res.users[i].mobile_number+'</a></br></hr>';
                }
                $('#user_list').html(htmlView);
            }
        });
    }

    function selectValue(id,full_text) {
        $('#id_value').val(id);
        $('#user_id').val(full_text);
        $('#user_list').hide();
    }

    function update() {
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('sendMessageUsers') }}',
          method: 'post',
          data: {
            message_text: $(".message_text").text(),
            subject: $("#subject").val(),
            msg_source: $(".msg_source").val(),
            tmp_id: $("#tmp_id").val(),
            role_id: $("#role_name").val(),
            user_id: $("#id_value").val(),
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#edit_details_btn").text('Send Message');
                $('#edit_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#edit_details_btn").text('Send Message');
                $('#edit_details_btn').prop('disabled', false);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#edit_details_btn").text('Send Message');
                $('#edit_details_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#edit_details_btn").text('Send Message');
            $('#edit_details_btn').prop('disabled', false);
         }
        });
    } 
</script>
    <script src="{{ URL::asset('assets/libs/@ckeditor/@ckeditor.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/quill/quill.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/pages/form-editor.init.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
