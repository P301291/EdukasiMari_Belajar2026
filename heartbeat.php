<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "database1");

if (isset($_SESSION['username'])) {
    $u = $_SESSION['username'];
    $page = isset($_GET['page']) ? mysqli_real_escape_string($conn, $_GET['page']) : 'Active';
    
    // Query ini yang menyalakan status Online di database
    mysqli_query($conn, "UPDATE user SET last_login = NOW(), is_online = 1, last_activity = '$page' WHERE username = '$u'");
}
?>