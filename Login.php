<?php
// Aktifkan session jika diperlukan di masa depan
// session_start();

// Simulasi loading (Hapus sleep(1) ini jika sudah masuk tahap produksi)
sleep(1); 
?>
<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <title>Login Admin | ELMS</title> 
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root {
            --primary: #4361ee;
            --bg: #f4f7fe;
            --text: #2b3674;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: var(--bg);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* --- LOADING SCREEN --- */
        #loading-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #ffffff; display: flex; justify-content: center;
            align-items: center; z-index: 9999; transition: opacity 0.5s ease;
        }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid #f3f3f3; border-top: 5px solid var(--primary);
            border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- BACKGROUND DECORATION --- */
        .shape {
            height: 200px; width: 200px; position: absolute; border-radius: 50%;
        }
        .shape:first-child {
            background: linear-gradient(#1845ad, #23a2f6);
            left: -80px; top: -80px;
        }
        .shape:last-child {
            background: linear-gradient(to right, #ff512f, #f09819);
            right: -80px; bottom: -80px;
        }

        /* --- LOGIN CARD --- */
        .login-card {
            width: 400px; background: rgba(255, 255, 255, 0.9);
            padding: 50px 35px; border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            z-index: 10; border: 1px solid rgba(255,255,255,0.5);
            animation: slideUp 0.8s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card h2 {
            font-weight: 800; color: var(--text);
            text-align: center; margin-bottom: 30px; letter-spacing: -1px;
        }

        .input-group { margin-bottom: 20px; position: relative; }
        .input-group i {
            position: absolute; left: 15px; top: 43px; color: #a3aed0;
        }

        label {
            display: block; font-weight: 700; font-size: 13px;
            color: #a3aed0; margin-bottom: 8px; margin-left: 5px;
        }

        input {
            width: 100%; padding: 14px 14px 14px 45px; border-radius: 16px;
            border: 1.5px solid #e2e8f0; outline: none; transition: 0.3s;
            background: white; color: var(--text); font-weight: 600;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        .btn-login {
            width: 100%; padding: 16px; border-radius: 16px; border: none;
            background: var(--primary); color: white; font-weight: 700;
            font-size: 15px; cursor: pointer; transition: 0.3s;
            margin-top: 10px; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
        }

        .btn-login:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .back-link {
            display: block; text-align: center; margin-top: 25px;
            text-decoration: none; color: #a3aed0; font-size: 14px; font-weight: 600;
        }

        /* Social Login Buttons */
        .social { margin-top: 30px; display: flex; gap: 10px; }
        .social div {
            width: 50%; padding: 10px; border-radius: 12px; background: #fff;
            border: 1px solid #e2e8f0; text-align: center; cursor: pointer;
            font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .social div:hover { background: #f8f9ff; }
    </style>
</head> 
<body> 

    <div id="loading-screen">
        <div class="spinner"></div>
    </div>

    <div class="shape"></div>
    <div class="shape"></div>

    <div class="login-card" id="main-content" style="display: none;">
        <form action="proses_login.php" method="post"> 
            <h2>Login Admin</h2> 

            <div class="input-group">
                <label>Username</label>
                <i class='bx bxs-user-circle'></i>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <i class='bx bxs-lock-alt'></i>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn-login">Masuk Sekarang</button> 

            <div class="social">
                <div class="go"><i class='bx bxl-google'></i> Google</div>
                <div class="fb"><i class='bx bxl-facebook-circle'></i> Facebook</div>
            </div>

            <a href="Login_user.php" class="back-link"><i class='bx bx-left-arrow-alt'></i> Kembali ke login User</a>
        </form>
    </div>

    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('loading-screen');
            const content = document.getElementById('main-content');
            
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                    content.style.display = 'block';
                }, 500);
            }, 800);
        });
    </script>
</body> 
</html>