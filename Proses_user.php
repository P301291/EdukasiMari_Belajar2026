<?php 
session_start(); 
include("koneksi.php"); 

// 1. Mengambil data dari form
$username = $_POST['username']; 
$password = $_POST['password']; 

// 2. Gunakan placeholder (?) alih-alih memasukkan variabel langsung
$sql = "SELECT * FROM user WHERE username = ? AND password = ?"; 

// 3. Menyiapkan pernyataan (Prepare)
$stmt = $conn->prepare($sql);

if ($stmt) {
    // 4. Mengikat parameter ("ss" berarti kedua parameter adalah string)
    // Ini mencegah SQL Injection karena data dikirim terpisah dari perintah
    $stmt->bind_param("ss", $username, $password);

    // 5. Menjalankan perintah
    $stmt->execute();

    // 6. Mengambil hasil
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { 
        // Login Berhasil
        $_SESSION['username'] = $username; 
        header("Location: Pembelajaran_video.php"); 
        exit(); // Selalu gunakan exit setelah header location
    } else { 
        // Login Gagal
        echo "<center>Login gagal. <a href='login_user.php'>Coba lagi</a></center>"; 
    } 

    $stmt->close();
} else {
    echo "Terjadi kesalahan pada sistem.";
}

$conn->close(); 
?>