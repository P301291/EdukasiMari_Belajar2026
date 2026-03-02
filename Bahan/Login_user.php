<?php
// Simulasi loading lambat selama 3 detik
// Hapus atau komentari baris ini jika digunakan di produksi ganti lahgi ini (session_start();)
sleep(3);
?>
<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no"><!--Code Responsipe-->
    <link rel="stylesheet" href="css/style_login.css">
    <style>
        /* 1. CSS untuk Overlay Loading */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff; /* Warna background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Pastikan paling atas */
            transition: opacity 0.5s ease;
        }

        /* 2. CSS untuk Spinner/Animasi */
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db; /* Warna spinner */
            border-radius: 50%;
            animation: spin 2s linear infinite;
        }

        /* 3. Animasi Putar */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(390deg); }
        }

        /* Konten Utama (Disembunyikan saat loading) */
     
    </style>

 <title>Login</title> 
</head> 
<body> 
<div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div id="loading-screen">
        <div class="spinner"></div>
    </div>

    <!-- Konten Utama -->
    <div id="main-content">
     
    </div>
    <script>
        // 4. JavaScript untuk menyembunyikan loading setelah konten dimuat
        window.addEventListener('load', function() {
            var loadingScreen = document.getElementById('loading-screen');
            var mainContent = document.getElementById('main-content');
            
            // Beri sedikit delay agar transisi mulus
            setTimeout(function() {
                loadingScreen.style.opacity = '0';
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                    mainContent.style.display = 'block';
                }, 500); // Waktu transisi opacity
            }, 500);
        });
    </script>
          <!-- Search Container -->
 <form action="proses_user.php" method="post"> 
 <center><h2>Login User</h2></center> 

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