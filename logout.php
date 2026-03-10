<?php
session_start();
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);

if(isset($_SESSION['username'])){
    $u = $_SESSION['username'];
    // Saat logout, status is_online jadi 0 dan last_activity jadi Idle
    mysqli_query($conn, "UPDATE user SET is_online = 0, last_activity = 'Idle' WHERE username = '$u'");
}

session_destroy();
header("location:login_user.php");
?>