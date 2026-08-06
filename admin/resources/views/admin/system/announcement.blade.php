@extends('layouts.master')
@section('title')
    Announcement
@endsection
@section('css')
    <link href="{{ URL::asset('assets/libs/quill/quill.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        System
        @endslot
        @slot('title')
        Announcement
        @endslot
    @endcomponent
    

    <div class="row mt-2">
        <div class="col-lg-12">
                <div class="justify-content-between d-flex align-items-center mb-3">
                
            </div>
            <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Announcement Details</h4>
                <div class="flex-shrink-0">
                    <div class="form-check form-switch form-switch-right form-switch-md">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="update()" id="edit_details_btn" >Save Changes</button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                 
                    <div class="bubble-editor Announcement" style="height: 300px;">
                        {{$result}}
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

    function fetchAll() {
        $.ajax({
            url: '{{ route('announcementGet') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                //console.log(res);
                $(".Announcement").text(res.data.message)
            }
        });
    }

    function update() {
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('announcementUpdate') }}',
          method: 'post',
          data: {
            announcement: $(".Announcement").text(),
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
                //fetchAll();
                location.reload();
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#edit_details_btn").text('Save Changes');
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
