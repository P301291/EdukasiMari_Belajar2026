<!DOCTYPE html> 

<html lang="en"> 

<head> 
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no"><!--Code Responsipe-->
    <link rel="stylesheet" href="css/style_login.css">

 <title>Login</title> 
</head> 
<body> 
<div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

 <form action="proses_login.php" method="post"> 
 <center><h2>Login E-Learning</h2></center> 

 <label for="username">Username:</label> 
 <input type="text" id="username" name="username" required><br> 
 <label for="password">Password :</label> 
 <input type="password" id="password" name="password" required><br> 
 <input type="submit" class="button" value="Login"> 
 <br>
<a href="Beranda.php">Kembali</a>
<div class="social">
          <div class="go">Google</div>
          <div class="fb">Facebook</div>
        </div>
    </form>
</body> 
<script src="js/script.js"></script>
</html>