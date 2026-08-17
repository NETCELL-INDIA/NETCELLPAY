@extends('layouts.master')
@section('title') Email Template @endsection
@section('css')
<!--datatable css-->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<!--datatable responsive css-->
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Company @endslot
@slot('title')Email Template @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Email Template List</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3" style="border-radius:12px">
                    Recipients now get a modern branded email (header, card, support footer). Edit the message text here; the stylish layout is applied automatically when the mail is sent.
                </div>
                <div id="list_result">
                    <h4 class="text-center text-secondary my-3">No record found</h4>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Details Modals -->
<div id="detailsModal" class="modal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Edit Email Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="edit_details_form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label>Category<span class="text-danger">*</span></label>
                                <select class="form-control" name="slug" id="slug">
                                    <option selected>Select Category</option>
                                    @foreach($categories as $item)
                                    <option value="{{$item->slug}}">{{$item->category_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="subject" class="form-control" required="">
                            </div>
                            <div class="mb-3">
                                <label>Content <span class="text-danger">*</span></label>
                                <textarea name="content" id="msg_content" class="form-control" rows="8" required="" placeholder="Dear {NAME}, your OTP is {OTP}"></textarea>
                                <div class="form-text">Use placeholders like {NAME}, {OTP}, {AMOUNT}. Recipients will see this text inside the modern email card.</div>
                            </div>
                            <div class="mb-0">
                                <label class="col-form-label">Status:</label>
                                <select class="form-select status" aria-label="Default select example" name="status">
                                    <option selected="">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Deactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <label class="form-label">Live preview (how the recipient sees it)</label>
                            <div id="email_live_preview" style="background:#eef2ff;border-radius:16px;padding:18px;min-height:420px;"></div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="edit_details_btn">Save Changes</button>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@endsection
@section('script')
<script>
    fetchAll();
    function capitalizeFirstLetter(string){
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function Error_Msg(title,text,icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: {
                confirmButton: 'btn btn-primary w-xs mt-2',
            },
            buttonsStyling: false,
            showCloseButton: true
        });
    }
    function fetchAll() {
        $.ajax({
            url: '{{ route('emailTemplateList') }}',
            method: 'post',
            data: {_token: '{{csrf_token()}}'},
            success: function(res) {
                $("#list_result").html(res);
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

    var emailBrand = @json($brand ?? ['name' => 'NETCELL PAY', 'logo' => '', 'support_email' => '', 'support_phone' => '', 'website' => '', 'year' => date('Y')]);

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function renderEmailPreview() {
        var subject = $('#subject').val() || 'Notification';
        var content = $('#msg_content').val() || '';
        var body = escapeHtml(content).replace(/\n/g, '<br>');
        body = body.replace(/(OTP(?:\s*(?:is|:|-))?\s*)(\d{4,8}|\{OTP\})/i, '$1<span style="display:inline-block;margin:10px 0;padding:10px 16px;border-radius:12px;background:#f4f1ff;color:#34308f;font-size:22px;font-weight:800;letter-spacing:4px;">$2</span>');
        var logo = emailBrand.logo
            ? '<img src="'+emailBrand.logo+'" alt="" width="40" height="40" style="border-radius:10px;background:#fff">'
            : '';
        var support = '';
        if (emailBrand.support_email) {
            support += ' at <strong>'+escapeHtml(emailBrand.support_email)+'</strong>';
        }
        if (emailBrand.support_phone) {
            support += ' / '+escapeHtml(emailBrand.support_phone);
        }
        $('#email_live_preview').html(
            '<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(23,27,61,.12)">' +
                '<div style="background:linear-gradient(135deg,#171b3d 0%,#34308f 58%,#00a892 140%);padding:22px 24px;color:#fff">' +
                    logo +
                    '<div style="margin-top:10px;font-weight:800;font-size:18px">'+escapeHtml(emailBrand.name)+'</div>' +
                    '<div style="opacity:.8;font-size:12px">Secure payments. Instant updates.</div>' +
                '</div>' +
                '<div style="height:4px;background:#00bfa6"></div>' +
                '<div style="padding:24px">' +
                    '<div style="font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:#00a892">'+escapeHtml(emailBrand.name)+' Notification</div>' +
                    '<div style="margin:8px 0 14px;font-size:20px;font-weight:800;color:#171b3d">'+escapeHtml(subject)+'</div>' +
                    '<div style="font-size:15px;line-height:1.7;color:#334155">'+body+'</div>' +
                    '<div style="margin-top:16px;padding:12px 14px;background:#f8fafc;border:1px solid #e8eef7;border-radius:12px;color:#64748b;font-size:12px">Need help? Contact support'+support+'</div>' +
                '</div>' +
                '<div style="background:#f4f7fb;padding:14px;text-align:center;color:#94a3b8;font-size:11px">&copy; '+escapeHtml(String(emailBrand.year || ''))+' '+escapeHtml(emailBrand.name)+'</div>' +
            '</div>'
        );
    }

    $(document).on('input change', '#subject, #msg_content', renderEmailPreview);

    $(document).on('click', '.deleteData', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        let csrf = '{{ csrf_token() }}';
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: '{{ route('emailTemplateDelete') }}',
              method: 'post',
              data: {
                id: id,
                _token: csrf
              },
              success: function(data) {
                if(data.type=="error"){
                    Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                }else if(data.type=="success"){
                   Swal.fire(
                        'Deleted!',
                        data.message,
                        'success'
                    )
                    fetchAll();
                }else{
                    Error_Msg("Oops...","Something went wrong!","error");
                }
                
              },
              error: function( jqXhr, textStatus, errorThrown ){
                Error_Msg("Oops...","Something went wrong!","error");
            }
            });
          }
        })
    });

    $(document).on('click', '.editDetails', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
          url: '{{ route('emailTemplateGet') }}',
          method: 'post',
          data: {
            id: id,
            _token: '{{ csrf_token() }}'
          },
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
            }else if(data.type=="success"){  
                $("#slug").val(data.data.slug).change();
                $("#subject").val(data.data.subject);
                $("#msg_content").val(data.data.content);
                $("#edit_id").val(data.data.id);
                $(".status").val(data.data.status).change();
                $('#detailsModalLabel').text('Edit Email Template');
                $('#detailsModal').modal('show');
                renderEmailPreview();
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
         }
        });
    });

    $("#edit_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_details_btn").text('Please wait...');
        $('#edit_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('emailTemplateUpdate') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Updated","Updated Successfully!","success");
                fetchAll();
                $("#edit_details_btn").text('Save Changes');
                $('#edit_details_btn').prop('disabled', false);
                $("#edit_details_form")[0].reset();
                $("#detailsModal").modal('hide');
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



@endsection
