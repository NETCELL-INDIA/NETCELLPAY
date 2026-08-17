


<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
            Copyright <script>document.write(new Date().getFullYear())</script> © <a id="copyrightName"> Made with ❤️ in India.
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block" id="date_time_footer">
                    
                </div>
            </div>
        </div>
    </div>
</footer>
<script>

    $("#helpSupport").click(function(){
        $('#helpSupportModal').modal('show');
    });
    $("#addMoney").click(function(){
        $("#addMoney_details_form")[0].reset();
        $('#addMoneyModal').modal({backdrop: 'static', keyboard: false});
        $('#addMoneyModal').modal('show');
    });


    $("#addMoney_details_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#addMoney_details_btn").text('Please wait...');
        $('#addMoney_details_btn').prop('disabled', true);
        $.ajax({
          url: '{{ route('allUpiAddMoneyRequestSubmit') }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(data) {
            if(data.type=="error"){
                Error_Msg(capitalizeFirstLetter(data.type),data.message,data.type);
                $("#addMoney_details_btn").text('Submit');
                $('#addMoney_details_btn').prop('disabled', false);
            }else if(data.type=="success"){  
                Error_Msg(data.type,data.message,"success");
                $("#addMoney_details_btn").text('Submit');
                $('#addMoney_details_btn').prop('disabled', false);
                $("#addMoney_details_form")[0].reset();
                $("#addMoneyModal").modal('hide');
                window.location.replace(data.pay_url);
            }else{
                Error_Msg("Oops...","Something went wrong!","error");
                $("#addMoney_details_btn").text('Submit');
                $('#addMoney_details_btn').prop('disabled', false);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            Error_Msg("Oops...","Something went wrong!","error");
            $("#addMoney_details_btn").text('Submit');
            $('#addMoney_details_btn').prop('disabled', false);
         }
        });
    }); 

    function parseDateTime(dt) {
        
        return dt;
    }

    function updateProfileUI(user) {
        if (!user) return;
        var fullName = [user.first_name, user.last_name].filter(Boolean).join(' ') || '—';
        $(".user-name-text").text(user.first_name || '—');
        $(".user-name-sub-text").text(user.role_name || '—');
        $("#nav_first_name").text("Welcome " + (user.first_name || '') + "!");
        $("#nav_full_name").text(fullName);
        $("#nav_role_name").text(user.role_name || '—');
        $("#nav_outlet_name").text(user.outlet_name || '—');
        $("#nav_first_name_val").text(user.first_name || '—');
        $("#nav_last_name").text(user.last_name || '—');
        $("#nav_mobile_number").text(user.mobile_number ? ('+91-' + user.mobile_number) : '—');
        $("#nav_email_address").text(user.email_address || '—');
        if ($("#dp_outlet_name").length) {
            $("#dp_outlet_name").text(user.outlet_name || '—');
        }
    }

    function dateTime() {
        var raw = localStorage.getItem("profileData");
        var data = raw ? $.parseJSON(raw) : null;
        const myDate = new Date();
        const hrs = myDate.getHours();
        let greet;
        if (hrs < 12)
            greet = 'Good Morning';
        else if (hrs >= 12 && hrs <= 17)
            greet = 'Good Afternoon';
        else if (hrs >= 17 && hrs <= 24)
            greet = 'Good Evening';
        if (data && data.user && data.user.first_name && $("#dayMessage").length) {
            $("#dayMessage").text(greet+", "+data.user.first_name+"!");
        }
        $('#date_time_footer').text(myDate);
    }

    ajaxCall();
    (function () {
        var raw = localStorage.getItem("profileData");
        if (!raw) return;
        try {
            var data = $.parseJSON(raw);
            if (data && data.user) updateProfileUI(data.user);
        } catch (e) {}
    })();
    dateTime();
    setInterval(dateTime, 1000);
    setInterval(ajaxCall, 25000);
    function ajaxCall() {
        function formatWalletBalance(value) {
            if (value === null || value === undefined || value === '') {
                return (0).toFixed(2);
            }
            var n = Number(value);
            if (!Number.isFinite(n)) {
                n = 0;
            }
            return n.toFixed(2);
        }
        $.ajax({
            url: '{{ route('myProfileData') }}',
            method: 'get',
            dataType: 'json',
            success: function(data) {
                if(data.type == "success"){
                    localStorage.setItem("profileData", JSON.stringify(data.data));
                    var u = data.data.user;
                    var walletText = formatWalletBalance(u && u.wallet_balance);
                    $(".LoadWallet").html('<span class="wallet-icon-wrap"><i class="mdi mdi-wallet"></i></span><span class="text-start"><span class="d-block wallet-label">Wallet Balance</span><span class="d-block wallet-amount">₹ '+walletText+'</span></span>');
                    $("#nav_wallet_balance").text("₹ "+walletText);
                    var alertBelow = Number((data.data && data.data.balance_alert_below) || 0);
                    $(".nc-wallet-btn").toggleClass("is-low-balance", alertBelow > 0 && Number(u.wallet_balance) < alertBelow);
                    admin_url = '{{env('ADMIN_HOST')}}';
                    var profilePic = admin_url+"/public/profile_pic/"+u.profile_pic;
                    $("#nav_profile_pic").attr("src", profilePic);
                    $("#nav_profile_pic_menu").attr("src", profilePic);
                    updateProfileUI(u);
                    if ($("#da_announcements_data").length) {
                        $("#da_announcements_data").text(data.data.announcements || '');
                    }
                    if (typeof dateTime === 'function') {
                        dateTime();
                    }

                    ////Help & Support Modal Start
                    $("#sh_comapany_logo").attr("src", admin_url+"/public/company_logo/"+(data.data.company && data.data.company.company_logo ? data.data.company.company_logo : ''));
                    $("#sh_support_number").text((data.data.company && data.data.company.support_number) || '');
                    $("#sh_support_number_2").text((data.data.company && data.data.company.support_number_2) || '');
                    $("#sh_support_email").text((data.data.company && data.data.company.support_email) || '');
                    $("#sh_company_address").text((data.data.company && data.data.company.company_address) || '');
                    $("#copyrightName").text((data.data.company && data.data.company.company_name) || '');
                    ////Help & Support Modal End
                }else{
                    Error_Msg(capitalizeFirstLetter(data.type), data.message, data.type);
                }
            },
            error: function(err) {
                console.log(err);
            }
        });
    }

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
 </script>

<!-- Sweet Alerts js -->
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- Sweet alert init js-->
<script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
