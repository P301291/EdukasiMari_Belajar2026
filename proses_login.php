<?php 

session_start(); 

include("koneksi.php"); 

$username = $_POST['username']; 

$password = $_POST['password']; 

$sql = "SELECT * FROM login WHERE username='$username' AND password='$password'"; 

$result = $conn->query($sql); 

if ($result->num_rows > 0) { 

 $_SESSION['username'] = $username; 

 header("Location: index.php"); 

} else { 

 echo "<center>Login gagal. <a href='Login.php'><h>Coba lagi</h></a></center>"; 

} 

$conn->close(); 

?>