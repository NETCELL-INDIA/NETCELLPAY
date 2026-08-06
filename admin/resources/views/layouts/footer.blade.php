<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
            Copyright <script>document.write(new Date().getFullYear())</script> © <a id="copyrightName">Made with ❤️ in India.
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block">
                </div>
            </div>
        </div>
    </div>
</footer>
<script>
    
    ajaxCalltopbar();
    setInterval(ajaxCalltopbar, 5000);
    $("#helpSupport").click(function(){
        var modalEl = document.getElementById('helpSupportModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $('#helpSupportModal').modal('show');
        }
    });
    
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

    function logOut() {
        window.location.href = '{{ route('adminLogout') }}';
    }
    
    $(document).on('click', '.LoadWallet', function(e) {
        e.preventDefault();
        var modalEl = document.getElementById('LoadWalletModal');
        if (!modalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $("#LoadWalletModal").modal('show');
        }
    });

    function updateTopbarWallet(balance) {
        var formatted = '₹ ' + Number(balance || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        $('#topbar_wallet_amount').text(formatted);
        $('#topbar_dropdown_wallet').text(formatted);
    }

    $("#admin_load_wallet_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#admin_load_wallet_btn").text('Please wait...');
        $('#admin_load_wallet_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('adminLoadWallet') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#admin_load_wallet_btn").text('Add Balance');
                $('#admin_load_wallet_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg("Success", data.message || "Wallet loaded successfully.", "success");
                if (data.wallet_balance !== undefined) {
                    updateTopbarWallet(data.wallet_balance);
                }
                $("#admin_load_wallet_btn").text('Add Balance');
                $('#admin_load_wallet_btn').prop('disabled', false);
                $("#admin_load_wallet_form")[0].reset();
                var modalEl = document.getElementById('LoadWalletModal');
                if (modalEl && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                } else {
                    $("#LoadWalletModal").modal('hide');
                }
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#admin_load_wallet_btn").text('Add Balance');
                $('#admin_load_wallet_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#admin_load_wallet_btn").text('Add Balance');
            $('#admin_load_wallet_btn').prop('disabled', false);
         }
        });
    });

    
    function ajaxCalltopbar() {
        $.ajax({
            url: '{{ route('topbarCount') }}',
            method: 'post',
            data: {_token: '{{ csrf_token() }}'},
            success: function(data) {
                if(data.type == "success"){
                    $("#TopBarComplaintCount").text(data.data.complaint);
                    $("#TopBarPendingCount").text(data.data.pending);
                    $("#DropComplaintCount").text(data.data.complaint || '');
                    $("#DropPendingCount").text(data.data.pending || '');
                }else{
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
            },
            error: function(err) {
                console.log(err);
                Error_Msg("Oops...", "Something went wrong!", "error");
            }
        });
    }


    </script>

<!-- Sweet Alerts js -->
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- Sweet alert init js-->
<script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
