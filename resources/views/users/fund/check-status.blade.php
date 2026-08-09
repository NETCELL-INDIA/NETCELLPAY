<html>

<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Status | {{$order_id}}</title>
</head>
<style>
    body {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
    }

    h1 {
        color: #88B04B;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-weight: 900;
        font-size: 40px;
        margin-bottom: 10px;
    }

    p {
        color: #404F5E;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-size: 20px;
        margin: 0;
    }

    i {
        color: #9ABC66;
        font-size: 100px;
        line-height: 200px;
        margin-left: -15px;
    }

    .card {
        background: white;
        padding: 60px;
        border-radius: 4px;
        box-shadow: 0 2px 3px #C8D0D8;
        display: inline-block;
        margin: 0 auto;
    }

    .button {
  background-color: #04AA6D; /* Green */
  border: none;
  color: white;
  padding: 8px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}

.button1 {border-radius: 15px;}

@keyframes time {
  to {
    transform: rotate(360deg);
  }
}
</style>

<body>
    <div class="card" >
        <div id="icon">
            <i class="fa fa-spinner" style="font-size: 200px;color:#efd518;animation: time 2s infinite linear;transform: rotate(100deg);"></i>
        </div>
        <h1 id="status">Processing</h1>
        <p id="message">Loading...</p>
    </br>
        <button class="button button1" onclick="homePage()">Back To Home</button>
    </div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
    function homePage(){
       // window.location.replace("{{route('dashboardPage') }}");
    }
    checkStatus();
    setInterval(checkStatus, 5000);
function checkStatus() {
        $.ajax({
            url: '{{ route('AddMoneyStatus',$order_id) }}',
            method: 'post',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if(res.type == "success"){
                    
                    if(res.status == "Success"){
                        $('#status').text(res.status);
                        $('#message').text(res.message);
                        $('#icon').html('<i class="fa fa-check" style="font-size: 200px;color:green;"></i>');
                        document.getElementById("status").style.color="green";
                    }else if(res.status == "Failed"){
                        $('#status').text(res.status);
                        $('#message').text(res.message);
                        $('#icon').html('<i class="fa fa-times" style="font-size: 200px;color:red;"></i>');
                        document.getElementById("status").style.color="red";
                    }
                }else{
                    $('#status').text(res.type);
                    $('#message').text(res.message);
                    $('#icon').html('<i class="fa fa-times" style="font-size: 200px;color:red;"></i>');
                    document.getElementById("status").style.color="red";
                }
                //console.log(res);
            }
        });
    }
</script>

</html>