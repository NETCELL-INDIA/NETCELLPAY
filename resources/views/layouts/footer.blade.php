


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

    function dateTime() {
        var data = $.parseJSON(localStorage.getItem("profileData"));
        const myDate = new Date();
        const hrs = myDate.getHours();
        let greet;
        if (hrs < 12)
            greet = 'Good Morning';
        else if (hrs >= 12 && hrs <= 17)
            greet = 'Good Afternoon';
        else if (hrs >= 17 && hrs <= 24)
            greet = 'Good Evening';
        $("#dayMessage").text(greet+", "+data.user.first_name+"!");
        var ndate = new Date();
        var h = ndate.getHours() % 12;
        var format = h >= 12 ? 'PM' : 'AM';
        var m = ndate.getMinutes().toString();
        var s = ndate.getSeconds().toString();
        $('#date_time_footer').text(myDate);
    }

    ajaxCall();
    dateTime();
    setInterval(dateTime, 1000);
    setInterval(ajaxCall, 25000);
    function ajaxCall() {
        $.ajax({
            url: '{{ route('myProfileData') }}',
            method: 'post',
            data: {_token: '{{ csrf_token() }}'},
            success: function(data) {
                if(data.type == "success"){
                    localStorage.setItem("profileData", JSON.stringify(data.data));
                    $(".LoadWallet").html('<i class="mdi mdi-wallet label-icon align-middle rounded-pill fs-16 me-2"></i>₹ '+data.data.user.wallet_balance.toFixed(2));
                    $("#nav_first_name").text("Welcome "+data.data.user.first_name+"!");
                    $("#nav_wallet_balance").text("₹ "+data.data.user.wallet_balance.toFixed(2));
                    admin_url = '{{env('ADMIN_HOST')}}';
                    $("#nav_profile_pic").attr("src", admin_url+"/public/profile_pic/"+data.data.user.profile_pic);

                    ////Help & Support Modal Start
                    $("#sh_comapany_logo").attr("src", admin_url+"/public/company_logo/"+data.data.company.company_logo);
                    $("#sh_support_number").text(data.data.company.support_number);
                    $("#sh_support_number_2").text(data.data.company.support_number_2);
                    $("#sh_support_email").text(data.data.company.support_email);
                    $("#sh_company_address").text(data.data.company.company_address);
                    $("#copyrightName").text(data.data.company.company_name);
                    ////Help & Support Modal End
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
 </script>

<!-- Sweet Alerts js -->
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- Sweet alert init js-->
<script src="{{ URL::asset('assets/js/pages/sweetalerts.init.js') }}"></script>
