<?php
session_start();

// Generate Captcha Sederhana
if (!isset($_POST['submit'])) {
    $angka1 = rand(1, 9);
    $angka2 = rand(1, 9);
    $_SESSION['captcha_result'] = $angka1 + $angka2;
}
?>
<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <title>Login Admin | ELMS Modern Side-Layout</title> 
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root {
            --primary: #4361ee;
            --bg:rgb(217, 221, 216);
            --text: #2b3674;
            --white: #ffffff;
            --accent: #ff9f43;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: var(--bg);
            background: radial-gradient(circle at 0% 0%, rgba(10, 122, 6, 0.1) 0%, transparent 100%),
                        radial-gradient(circle at 100% 100%, rgba(20, 161, 20, 0.05) 0%, transparent 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* --- LOADING SCREEN --- */
        #loading-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #ffffff; display: flex; flex-direction: column; justify-content: center;
            align-items: center; z-index: 9999;
        }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid #f3f3f3; border-top: 5px solid var(--primary);
            border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- MAIN CONTAINER --- */
        .main-container {
            display: flex;
            background: var(--white);
            width: 100%;
            max-width: 900px;
            border-radius: 35px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(43, 54, 116, 0.15);
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- SIDE INFO (Petunjuk) --- */
        .side-info {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            background-color: var(--primary);
            padding: 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .side-info h3 { font-size: 24px; font-weight: 800; margin-bottom: 20px; }
        .guide-item { display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-start; }
        .guide-item i { font-size: 24px; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 12px; }
        .guide-item div h4 { font-size: 15px; margin-bottom: 5px; font-weight: 700; }
        .guide-item div p { font-size: 13px; opacity: 0.8; line-height: 1.5; }

        /* --- LOGIN SECTION --- */
        .login-section {
            flex: 1.2;
            padding: 50px;
            background: var(--white);
        }

        .brand-header { margin-bottom: 35px; }
        .brand-header h2 { font-size: 28px; font-weight: 800; color: var(--text); }
        .brand-header p { color: #a3aed0; font-size: 14px; margin-top: 5px; }

        .input-group { margin-bottom: 22px; position: relative; }
        .input-group label { display: block; font-weight: 700; font-size: 13px; color: var(--text); margin-bottom: 10px; }
        .input-group i.left-icon { position: absolute; left: 18px; top: 43px; color: var(--primary); font-size: 20px; z-index: 5; }
        
        input {
            width: 100%; padding: 15px 15px 15px 52px; border-radius: 18px;
            border: 2px solid #f1f4f9; outline: none; transition: 0.3s;
            background: #f8faff; color: var(--text); font-weight: 600; font-size: 14px;
        }

        input:focus { border-color: var(--primary); background: white; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.05); }

        /* CAPTCHA STYLING */
        .captcha-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f4f9;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 15px;
        }
        .captcha-question {
            font-weight: 800;
            color: var(--primary);
            font-size: 16px;
            background: white;
            padding: 5px 15px;
            border-radius: 10px;
            border: 1px dashed var(--primary);
        }

        /* --- TOGGLE PASSWORD EYE --- */
        .toggle-password {
            position: absolute; right: 18px; top: 43px; 
            cursor: pointer; color: #a3aed0; font-size: 20px; transition: 0.3s;
        }
        .toggle-password:hover { color: var(--primary); }

        .btn-login {
            width: 100%; padding: 18px; border-radius: 18px; border: none;
            background: var(--primary); color: white; font-weight: 800;
            font-size: 16px; cursor: pointer; transition: 0.3s;
            margin-top: 5px; box-shadow: 0 15px 30px rgba(67, 97, 238, 0.3);
        }

        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(67, 97, 238, 0.4); }

        .back-to-user {
            display: block; text-align: center; margin-top: 25px;
            text-decoration: none; color: var(--primary); font-size: 14px; font-weight: 800;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container { flex-direction: column; margin: 10px; border-radius: 25px; }
            .side-info { padding: 30px; order: 2; }
            .login-section { padding: 35px; order: 1; }
        }
    </style>
</head> 
<body> 

    <div id="loading-screen">
        <div class="spinner"></div>
    </div>

    <div class="main-container" id="main-content" style="display: none;">
        
        <div class="side-info">
            <h3>Pusat Bantuan Admin</h3>
            <div class="guide-item">
                <i class='bx bx-fingerprint'></i>
                <div>
                    <h4>Login Terenkripsi</h4>
                    <p>Gunakan kredensial resmi yang sudah terdaftar di sistem pusat ELMS.</p>
                </div>
            </div>
            <div class="guide-item">
                <i class='bx bx-lock-alt'></i>
                <div>
                    <h4>Keamanan Sandi</h4>
                    <p>Pastikan tidak ada orang lain yang melihat saat Anda mengetikkan kata sandi.</p>
                </div>
            </div>
            <div class="guide-item">
                <i class='bx bx-support'></i>
                <div>
                    <h4>Masalah Akses?</h4>
                    <p>Hubungi Super Admin jika akun Anda terkunci atau lupa password.</p>
                </div>
            </div>
        </div>

        <div class="login-section">
            <div class="brand-header">
                <h2>ELMS <span>Mari Belajar</span></h2>
                <p>Silahkan masuk ke Dashboard Admin</p>
            </div>

            <form action="proses_login.php" method="post"> 
                <div class="input-group">
                    <label>Username</label>
                    <i class='bx bx-user left-icon'></i>
                    <input type="text" name="username" placeholder="Masukkan username" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <i class='bx bx-lock-alt left-icon'></i>
                    <input type="password" id="passwordField" name="password" placeholder="Masukkan password" required>
                    <i class='bx bx-hide toggle-password' id="toggleIcon" onclick="togglePassword()"></i>
                </div>

                <div class="input-group">
                    <label>Verifikasi Keamanan (Captcha)</label>
                    <div class="captcha-box">
                        <span class="captcha-question"><?php echo $angka1 . " + " . $angka2; ?> = ?</span>
                        <input type="number" name="captcha_answer" placeholder="Hasil" required 
                               style="padding: 12px 15px; border-radius: 12px; flex: 1;">
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-login">Masuk Sekarang</button> 
            </form>

            <a href="Login_user.php" class="back-to-user">
                <i class='bx bx-arrow-back'></i> Login sebagai Peserta
            </a>
        </div>

    </div>

    <script>
        // Fungsi Lihat Password
        function togglePassword() {
            const passwordField = document.getElementById("passwordField");
            const toggleIcon = document.getElementById("toggleIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.replace("bx-hide", "bx-show");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.replace("bx-show", "bx-hide");
            }
        }

        // Loading Screen Script
        window.addEventListener('load', function() {
            const loader = document.getElementById('loading-screen');
            const content = document.getElementById('main-content');
            
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                    content.style.display = 'flex';
                }, 500);
            }, 800);
        });
    </script>
</body> 
</html>