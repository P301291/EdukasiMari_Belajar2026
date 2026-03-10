<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);

// --- 1. LOGIKA CLEANUP (Otomatis Offline) ---
mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE last_login < (NOW() - INTERVAL 40 SECOND)");

// --- 2. LOGIKA PAGINATION & SEARCH ---
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$limit = isset($_GET['limit']) ? $_GET['limit'] : 5; 
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($page - 1) * (is_numeric($limit) ? (int)$limit : 0);

$limit_sql = ($limit == 'all') ? "" : "LIMIT $offset, " . (int)$limit;
$sql = "SELECT * FROM user WHERE username LIKE '%$search%' ORDER BY is_online DESC, last_login DESC $limit_sql";
$data_admin = mysqli_query($conn, $sql);

$online_result = mysqli_query($conn, "SELECT COUNT(*) as t FROM user WHERE is_online = 1");
$total_online = mysqli_fetch_assoc($online_result)['t'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ELMS | Pro Monitoring</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { 
            --primary: #4361ee; 
            --sidebar-bg: #0b0f19; 
            --body-bg: #f4f7fe; 
            --text-color: #2b3674; 
            --white: #ffffff; 
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --muted: #a3aed0;
        }

        [data-theme="dark"] {
            --body-bg: #0b1437;
            --text-color: #ffffff;
            --white: #111c44;
            --card-bg: #111c44;
            --border: #1b254b;
            --muted: #707eae;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: background 0.3s, border 0.3s; }
        body { background: var(--body-bg); color: var(--text-color); min-height: 100vh; display: flex; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); position: fixed; left: 0; top: 0; z-index: 1000; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.collapsed { left: -260px; }
        .sidebar-brand { padding: 40px 25px; color: #fff; font-weight: 800; font-size: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { list-style: none; padding: 20px 15px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: #a3aed0; text-decoration: none; padding: 14px 18px; border-radius: 15px; font-weight: 600; margin-bottom: 5px; }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar-menu a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }

        /* CONTENT */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 35px; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .main-content.expanded { margin-left: 0; width: 100%; }

        .top-header { display: flex; justify-content: space-between; align-items: center; background: var(--white); padding: 18px 25px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid var(--border); }
        
        .icon-btn { cursor: pointer; width: 45px; height: 45px; background: var(--body-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid var(--border); color: var(--primary); }
        
        .card { background: var(--card-bg); border-radius: 28px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid var(--border); }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 0 15px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; background: var(--white); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-weight: 700; font-size: 14px; }
        td:first-child { border-radius: 15px 0 0 15px; }
        td:last-child { border-radius: 0 15px 15px 0; }
        
        .status-pill { padding: 6px 12px; border-radius: 10px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .status-online { background: #dcfce7; color: #15803d; }
        .status-offline { background: #f1f5f9; color: #64748b; }
        .dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 1.5s infinite; }
        .activity-badge { background: #eef2ff; color: #4361ee; padding: 6px 12px; border-radius: 8px; font-size: 12px; }
        
        .form-control { padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--white); color: var(--text-color); outline: none; font-weight: 600; }

        /* INFO SECTION - ENHANCED */
        .info-section { background: var(--white); border-radius: 28px; padding: 35px; border-left: 8px solid var(--primary); box-shadow: 0 20px 40px rgba(0,0,0,0.03); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px; }
        .info-card { background: var(--body-bg); padding: 25px; border-radius: 22px; border: 1px solid var(--border); position: relative; overflow: hidden; }
        .info-card h4 { color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; }
        .info-card ul { list-style: none; }
        .info-card ul li { color: var(--muted); font-size: 13.5px; line-height: 1.8; margin-bottom: 10px; display: flex; align-items: flex-start; gap: 8px; }
        .info-card ul li i { color: var(--primary); margin-top: 4px; }

        @keyframes pulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }
        @media (max-width: 992px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">ELMS <span style="color:var(--primary)">MARI BELAJAR</span></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
            <li><a href="monitoring.php" class="active"><i class='bx bxs-zap'></i> Monitoring User</a></li>
            <li><a href="data_user.php"><i class='bx bxs-user-detail'></i> Data User</a></li>
            <li><a href="Nilai_Siswa.php"><i class='bx bxs-cog'></i> Nilai Siswa</a></li>
            <li><a href="login.php" style="color:#fb7185; margin-top:20px;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="main-content">
        <div class="top-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="icon-btn" id="sidebar-toggle"><i class='bx bx-menu-alt-left'></i></div>
                <div>
                    <h1 style="font-size: 18px; font-weight: 800;">Real-time Monitoring</h1>
                    <p style="font-size: 12px; color: var(--muted); font-weight: 600;">Pantau aktivitas pengerjaan user secara live</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="icon-btn" id="theme-btn"><i class='bx bx-moon' id="theme-icon"></i></div>
                <div style="background: #dcfce7; color: #15803d; padding: 10px 20px; border-radius: 15px; font-weight: 800; font-size: 13px; border: 1px solid rgba(21,128,61,0.1);">
                    <i class='bx bxs-bolt-circle'></i> <?= $total_online ?> User Online
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; gap: 15px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-weight: 700; font-size: 14px; color: var(--muted);">Tampilkan</span>
                    <form method="GET">
                        <select name="limit" class="form-control" onchange="this.form.submit()">
                            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 baris</option>
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 baris</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 baris</option>
                            <option value="all" <?= $limit == 'all' ? 'selected' : '' ?>>Semua</option>
                        </select>
                    </form>
                </div>
                <form method="GET" style="width: 320px; max-width: 100%; position: relative;">
                    <i class='bx bx-search' style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--muted);"></i>
                    <input type="text" name="cari" placeholder="Cari nama user..." value="<?= htmlspecialchars($search) ?>" class="form-control" style="width: 100%; padding-left: 45px;">
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status Pengerjaan</th>
                            <th>Status Akses</th>
                            <th>Jam Update Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_admin)): ?>
                        <tr>
                            <td><?= $row['username'] ?></td>
                            <td>
                                <?php if($row['is_online'] == 1): ?>
                                    <div class="status-pill status-online"><span class="dot"></span> Sedang Mengerjakan</div>
                                <?php else: ?>
                                    <div class="status-pill status-offline"><i class='bx bx-moon'></i> Tidak Aktif</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="activity-badge"><i class='bx bx-file-find'></i> <?= $row['last_activity'] ?></span></td>
                            <td style="color: var(--muted); font-size: 13px;"><?= date('H:i:s', strtotime($row['last_login'])) ?> WIB</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="info-section">
            <h2 style="font-weight: 800; margin-bottom: 5px; font-size: 22px;">Pusat Informasi ELMS MARI BELAJAR</h2>
            <p style="color: var(--muted); font-weight: 600; font-size: 14px;">Dokumentasi sistem monitoring dan teknologi yang digunakan</p>
            
            <div class="info-grid">
                <div class="info-card">
                    <h4><i class='bx bx-book-open'></i> Panduan Operasional</h4>
                    <ul>
                        <li><i class='bx bx-check-double'></i> <b>Manajemen Sidebar:</b> Gunakan tombol di pojok kiri atas untuk menyembunyikan menu samping guna mendapatkan pandangan tabel yang lebih luas dan fokus.</li>
                        <li><i class='bx bx-check-double'></i> <b>Filter Pencarian:</b> Sistem mendukung pencarian *Real-time*. Cukup ketik nama user dan tekan enter untuk menyaring data secara instan.</li>
                        <li><i class='bx bx-check-double'></i> <b>Indikator Online:</b> Label "Sedang Mengerjakan" dengan titik hijau berkedip menandakan sistem mendeteksi pergerakan mouse atau keyboard dari sisi user.</li>
                        <li><i class='bx bx-check-double'></i> <b>Kenyamanan Visual:</b> Aktifkan Mode Gelap (Dark Mode) melalui ikon bulan di header untuk menjaga kesehatan mata saat monitoring di malam hari.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h4><i class='bx bx-bolt-circle'></i> Kecanggihan Teknologi</h4>
                    <ul>
                        <li><i class='bx bxs-zap'></i> <b>Smart Heartbeat Tracking:</b> Menggunakan algoritma deteksi aktivitas yang mengirimkan sinyal ke server setiap kali ada interaksi fisik pada browser user.</li>
                        <li><i class='bx bxs-zap'></i> <b>Auto-Cleanup Logic:</b> Sistem otomatis melakukan sanitasi data setiap 40 detik untuk memastikan user yang menutup tab tanpa logout tidak dianggap online selamanya.</li>
                        <li><i class='bx bxs-zap'></i> <b>Async Data Synchronization:</b> Tabel melakukan penyegaran data otomatis di latar belakang tanpa mengganggu input yang sedang Admin ketik di kolom pencarian.</li>
                        <li><i class='bx bxs-zap'></i> <b>Hybrid Layout Engine:</b> Dibangun dengan arsitektur CSS modern yang memungkinkan transisi *smooth* saat perubahan tema atau pergeseran sidebar.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        // SIDEBAR TOGGLE
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const sidebarToggle = document.getElementById('sidebar-toggle');

        sidebarToggle.onclick = () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        };

        // THEME TOGGLE
        const themeBtn = document.getElementById('theme-btn');
        const themeIcon = document.getElementById('theme-icon');
        const currentTheme = localStorage.getItem('theme') || 'light';

        if (currentTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            themeIcon.className = 'bx bx-sun';
        }

        themeBtn.onclick = () => {
            let theme = document.body.getAttribute('data-theme');
            if (theme === 'dark') {
                document.body.removeAttribute('data-theme');
                themeIcon.className = 'bx bx-moon';
                localStorage.setItem('theme', 'light');
            } else {
                document.body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'bx bx-sun';
                localStorage.setItem('theme', 'dark');
            }
        };

        // AUTO REFRESH PREVENT ON INPUT
        setInterval(() => {
            if(document.activeElement.tagName !== 'INPUT') {
                // Gunakan cara refresh yang lebih elegan tanpa 'flicker' parah jika memungkinkan
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>