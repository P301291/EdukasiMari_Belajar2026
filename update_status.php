<?php
session_start();
// --- KONEKSI DATABASE ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);

if (isset($_SESSION['username']) && isset($_GET['pesan'])) {
    $username = $_SESSION['username'];
    $pesan = mysqli_real_escape_string($conn, $_GET['pesan']);
    
    // Update aktivitas terakhir dan pastikan waktu login diperbarui agar tetap dianggap online
    mysqli_query($conn, "UPDATE user SET last_activity = '$pesan', last_login = NOW(), is_online = 1 WHERE username = '$username'");
}
?>