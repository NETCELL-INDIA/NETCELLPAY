<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{$subject}}</title>
<style>


body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
  }


  .container {
    max-width: 800px;
    margin: auto;
    padding: 20px;
    background-color: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  button {
    background-color: #008CBA; /* Blue */
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
  }


  button:hover {
    background-color: #005f73;
  }
</style>
</head>
<body>


<!-- <header>
  <h1>{{$subject}}</h1>
</header> -->


<div class="container">
  <p>{{$body}}</p>
</div>


</body>
</html>