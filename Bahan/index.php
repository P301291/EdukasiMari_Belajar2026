<?php
session_start();

// --- 1. FITUR KEAMANAN & AUTO-LOGOUT ---
$timeout_duration = 1200; // 20 Menit
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=expired");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("location:login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Dashboard 2026 | Mari Belajar</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root {
            --primary-color: #4e73df;
            --bg-body: #f8f9fc;
            --bg-card: #ffffff;
            --text-main: #333333;
            --text-muted: #6e707e;
            --sidebar-bg: #11101d;
            --sidebar-text: #ffffff;
            --border-color: #e3e6f0;
        }

        /* DARK MODE VARIABLES */
        [data-theme="dark"] {
            --bg-body: #0b0a12;
            --bg-card: #1d1b31;
            --text-main: #edeff2;
            --text-muted: #a1a1a1;
            --sidebar-bg: #000000;
            --border-color: #2d2b48;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); transition: all 0.3s ease; }

        /* LOADING SCREEN */
        #loading-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-body); display: flex; justify-content: center; align-items: center;
            z-index: 9999; transition: opacity 0.5s;
        }
        .spinner { width: 45px; height: 45px; border: 5px solid var(--border-color); border-top: 5px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* LAYOUT */
        .dashboard-container { display: flex; min-height: 100vh; }

        /* SIDEBAR 2026 */
        .dashboard-sidebar { width: 260px; background: var(--sidebar-bg); color: var(--sidebar-text); padding: 20px; display: flex; flex-direction: column; transition: 0.3s; }
        .dashboard-brand { display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 700; margin-bottom: 30px; color: #fff; text-decoration: none; }
        
        .dashboard-nav { flex: 1; }
        .dashboard-nav-item { 
            display: flex; align-items: center; gap: 12px; padding: 12px 15px; 
            color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 10px; margin-bottom: 5px; transition: 0.2s;
        }
        .dashboard-nav-item:hover, .dashboard-nav-item.active { background: var(--primary-color); color: #fff; }

        /* MAIN CONTENT */
        .dashboard-main { flex: 1; padding: 25px; }
        .dashboard-header { 
            display: flex; justify-content: space-between; align-items: center; 
            background: var(--bg-card); padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* WIDGETS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--bg-card); padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-label { font-size: 0.9rem; color: var(--text-muted); }
        .stat-value { font-size: 1.5rem; font-weight: 700; margin: 10px 0; }

        /* PROGRESS BAR */
        .progress-container { background: var(--border-color); border-radius: 10px; height: 8px; margin: 10px 0; }
        .progress-fill { background: var(--primary-color); height: 100%; border-radius: 10px; transition: width 1s; }

        /* TOGGLE THEME */
        #theme-toggle { 
            cursor: pointer; display: flex; align-items: center; gap: 8px; 
            background: var(--bg-body); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--border-color);
        }

        /* TABLE */
        .dashboard-table-container { background: var(--bg-card); padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; color: var(--text-muted); font-size: 0.85rem; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px 12px; border-bottom: 1px solid var(--border-color); }

        .btn-action { background: var(--primary-color); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; text-decoration: none; font-size: 0.85rem; }
    </style>
</head>
<body>

    <div id="loading-screen"><div class="spinner"></div></div>

    <div class="dashboard-container">
        <aside class="dashboard-sidebar">
            <a href="#" class="dashboard-brand">
                <i class='bx bxs-graduation'></i> <span>MARI BELAJAR</span>
            </a>
            <nav class="dashboard-nav">
                <a href="#" class="dashboard-nav-item active">
                    <span class="material-symbols-rounded">space_dashboard</span>
                    <span class="nav-label">Beranda Belajar</span>
                </a>
                <a href="#" class="dashboard-nav-item">
                    <span class="material-symbols-rounded">import_contacts</span>
                    <span class="nav-label">Materi Saya</span>
                </a>
                <a href="#" class="dashboard-nav-item">
                    <span class="material-symbols-rounded">video_camera_front</span>
                    <span class="nav-label">Kelas Live</span>
                </a>
                <a href="#" class="dashboard-nav-item">
                    <span class="material-symbols-rounded">assignment</span>
                    <span class="nav-label">Tugas & Proyek</span>
                </a>
                <a href="#" class="dashboard-nav-item">
                    <span class="material-symbols-rounded">military_tech</span>
                    <span class="nav-label">Sertifikat</span>
                </a>
                <a href="#" class="dashboard-nav-item">
                    <span class="material-symbols-rounded">forum</span>
                    <span class="nav-label">Diskusi</span>
                </a>
            </nav>
            <div style="margin-top: auto; padding-top: 20px;">
                <a href="login_user.php" class="dashboard-nav-item" style="color: #ff7675;">
                    <span class="material-symbols-rounded">logout</span>
                    <span class="nav-label">Keluar</span>
                </a>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h2 style="font-size: 1.2rem;">Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Ayo lanjutkan target belajar hari ini.</p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div id="theme-toggle">
                        <span class="material-symbols-rounded" id="theme-icon" style="font-size: 1.2rem;">dark_mode</span>
                        <span id="theme-text" style="font-size: 0.85rem; font-weight: 500;">Dark</span>
                    </div>
                    <div class="user-avatar" style="width: 40px; height: 40px; background: #eee; border-radius: 50%; overflow: hidden;">
                        <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['username']; ?>&background=random" alt="User" style="width: 100%;">
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Progress Keseluruhan</span>
                        <div class="stat-value">68%</div>
                        <div class="progress-container"><div class="progress-fill" style="width: 68%;"></div></div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Kursus Aktif</span>
                        <div class="stat-value">4 Materi</div>
                        <p style="font-size: 0.75rem; color: #2ecc71;">+2 modul baru tersedia</p>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Poin Belajar</span>
                        <div class="stat-value">1,250 XP</div>
                        <p style="font-size: 0.75rem; color: var(--primary-color);">Peringkat 5 di kelas</p>
                    </div>
                </div>

                <div class="dashboard-table-container">
                    <h3 style="margin-bottom: 15px;">Lanjutkan Pembelajaran Terakhir</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>MATA PELAJARAN</th>
                                <th>MODUL</th>
                                <th>PROGRESS</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><b>Web Dev: PHP & MySQL</b></td>
                                <td style="color: var(--text-muted);">Dasar Autentikasi</td>
                                <td style="width: 200px;">
                                    <div class="progress-container"><div class="progress-fill" style="width: 85%;"></div></div>
                                    <small>85%</small>
                                </td>
                                <td><a href="#" class="btn-action">Lanjutkan</a></td>
                            </tr>
                            <tr>
                                <td><b>UI/UX Modern 2026</b></td>
                                <td style="color: var(--text-muted);">Neuro-Design</td>
                                <td style="width: 200px;">
                                    <div class="progress-container"><div class="progress-fill" style="width: 30%; background: #f1c40f;"></div></div>
                                    <small>30%</small>
                                </td>
                                <td><a href="#" class="btn-action">Lanjutkan</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Loading Screen Logic
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loading-screen');
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }, 800);
        });

        // Dark Mode Logic
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        const htmlElement = document.documentElement;

        // Cek LocalStorage
        if (localStorage.getItem('theme') === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
            themeIcon.innerText = 'light_mode';
            themeText.innerText = 'Light';
        }

        themeToggle.addEventListener('click', () => {
            if (htmlElement.getAttribute('data-theme') === 'dark') {
                htmlElement.removeAttribute('data-theme');
                themeIcon.innerText = 'dark_mode';
                themeText.innerText = 'Dark';
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-theme', 'dark');
                themeIcon.innerText = 'light_mode';
                themeText.innerText = 'Light';
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>