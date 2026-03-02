<?php
session_start();
?>
<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ELMS Mari Belajar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root {
            --elms-primary: #4361ee;
            --elms-secondary: #3f37c9;
            --elms-dark: #080710;
            --elms-text-muted: #a3aed0;
            --elms-bg-card: rgba(255, 255, 255, 0.08);
            --elms-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body {
            background-color: var(--elms-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }

        /* --- BRANDING HEADER --- */
        .brand-header {
            text-align: center;
            margin-bottom: 40px;
            z-index: 10;
            animation: fadeInDown 0.8s ease;
        }
        .brand-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -1px;
        }
        .brand-header h1 span {
            color: var(--elms-primary);
        }
        .brand-header p {
            color: var(--elms-text-muted);
            font-size: 14px;
            font-weight: 500;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* --- LOADING SCREEN --- */
        #loading-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--elms-dark); display: flex; justify-content: center;
            align-items: center; z-index: 10000; transition: 0.6s ease;
        }
        .spinner {
            width: 60px; height: 60px; border: 4px solid rgba(67, 97, 238, 0.1);
            border-top: 4px solid var(--elms-primary); border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        /* --- DECORATION --- */
        .background-shapes { position: fixed; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .shape { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.3; }
        .shape-1 { width: 400px; height: 400px; background: var(--elms-primary); top: -150px; left: -100px; }
        .shape-2 { width: 300px; height: 300px; background: #ff4757; bottom: -100px; right: -50px; }

        /* --- MAIN WRAPPER --- */
        .elms-wrapper {
            display: flex;
            flex-direction: row;
            gap: 25px;
            width: 100%;
            max-width: 1000px;
            z-index: 10;
            animation: fadeInUp 0.8s ease;
        }

        /* --- GUIDE PANEL --- */
        .elms-guide {
            flex: 1.2;
            background: var(--elms-bg-card);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            border: 1px solid var(--elms-border);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .elms-guide h3 { font-size: 22px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .elms-guide h3 i { color: var(--elms-primary); }

        .guide-item { display: flex; gap: 15px; margin-bottom: 20px; }
        .icon-box { 
            min-width: 45px; height: 45px; background: rgba(67, 97, 238, 0.1); 
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            color: var(--elms-primary); font-size: 20px;
        }
        .guide-text b { display: block; font-size: 15px; color: #fff; margin-bottom: 2px; }
        .guide-text p { font-size: 13px; color: var(--elms-text-muted); line-height: 1.4; }

        /* --- LOGIN CARD --- */
        .elms-login-card {
            width: 400px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            border-radius: 30px;
            padding: 45px;
            border: 1px solid var(--elms-border);
            color: white;
        }
        .elms-login-card h2 { font-size: 24px; font-weight: 700; text-align: center; margin-bottom: 30px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: var(--elms-text-muted); margin-bottom: 8px; text-transform: uppercase; }
        
        .input-box { position: relative; }
        .input-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--elms-primary); }
        .elms-input {
            width: 100%; height: 50px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px;
            padding-left: 45px; color: #fff; outline: none; transition: 0.3s;
        }
        .elms-input:focus { border-color: var(--elms-primary); background: rgba(67, 97, 238, 0.05); }

        .elms-btn {
            width: 100%; height: 50px; background: var(--elms-primary); color: #fff;
            border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .elms-btn:hover { background: var(--elms-secondary); transform: translateY(-2px); }

        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--elms-text-muted); text-decoration: none; font-size: 13px; }

        /* --- ANIMATIONS --- */
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .elms-wrapper { flex-direction: column-reverse; align-items: center; }
            .elms-guide, .elms-login-card { width: 100%; max-width: 450px; }
            .brand-header h1 { font-size: 26px; }
        }
    </style>
</head> 
<body> 

    <div id="loading-screen"><div class="spinner"></div></div>

    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="brand-header">
        <h1>ELMS <span>MARI BELAJAR</span></h1>
        <p>Platform Ujian Digital Terintegrasi</p>
    </div>

    <div class="elms-wrapper">
        <div class="elms-guide">
            <h3><i class='bx bxs-bulb'></i> Panduan Login</h3>
            <div class="guide-item">
                <div class="icon-box"><i class='bx bxs-user-pin'></i></div>
                <div class="guide-text">
                    <b>ID Pengguna</b>
                    <p>Gunakan Username yang terdaftar di database.</p>
                </div>
            </div>
            <div class="guide-item">
                <div class="icon-box"><i class='bx bxs-lock-open'></i></div>
                <div class="guide-text">
                    <b>Keamanan Sandi</b>
                    <p>Password bersifat rahasia, pastikan tidak ada orang lain yang melihat.</p>
                </div>
            </div>
            <div class="guide-item">
                <div class="icon-box"><i class='bx bxs-info-circle'></i></div>
                <div class="guide-text">
                    <b>Kendala Teknis</b>
                    <p>Jika gagal login, segera hubungi proktor atau admin</p>
                </div>
            </div>
        </div>

        <div class="elms-login-card">
            <form action="proses_user.php" method="post"> 
                <h2>Masuk Peserta</h2> 

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-box">
                        <i class='bx bxs-user'></i>
                        <input type="text" name="username" class="elms-input" placeholder="Ketik Username..." required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-box">
                        <i class='bx bxs-lock-alt'></i>
                        <input type="password" name="password" class="elms-input" placeholder="Ketik Password..." required>
                    </div>
                </div>

                <button type="submit" class="elms-btn">Mulai Belajar Sekarang</button> 

                <a href="Login.php" class="back-link">
                    <i class='bx bx-left-arrow-alt'></i> Login Admin 
                </a>
            </form>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                const ls = document.getElementById('loading-screen');
                ls.style.opacity = '0';
                setTimeout(() => ls.style.display = 'none', 600);
            }, 600);
        });
    </script>
</body> 
</html>