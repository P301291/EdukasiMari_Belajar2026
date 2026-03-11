<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- 1. KONEKSI DATABASE ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);

// --- 2. LOGIKA CLEANUP (Otomatis Offline jika 40 detik pasif) ---
mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE is_online = 1 AND last_login < (NOW() - INTERVAL 40 SECOND)");

// --- 3. LOGIKA PAGINATION & SEARCH ---
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$limit = isset($_GET['limit']) ? $limit = $_GET['limit'] : 5; 
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($page - 1) * (is_numeric($limit) ? (int)$limit : 0);

$limit_sql = ($limit == 'all') ? "" : "LIMIT $offset, " . (int)$limit;
$sql = "SELECT * FROM user WHERE username LIKE '%$search%' ORDER BY is_online DESC, last_login DESC $limit_sql";
$data_admin = mysqli_query($conn, $sql);

$online_result = mysqli_query($conn, "SELECT COUNT(*) as t FROM user WHERE is_online = 1");
$total_online = mysqli_fetch_assoc($online_result)['t'];

function getHariIndo($date) {
    $hari = date('l', strtotime($date));
    $map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    return $map[$hari];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ELMS | Pro Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { 
            --primary: #4361ee; --sidebar-bg: #0b0f19; --body-bg: #f4f7fe; 
            --text-color: #2b3674; --white: #ffffff; --card-bg: #ffffff;
            --border: #e2e8f0; --muted: #a3aed0; --danger: #ff4d4d; --success: #22c55e;
        }
        [data-theme="dark"] {
            --body-bg: #0b1437; --text-color: #ffffff; --white: #111c44; --card-bg: #111c44; --border: #1b254b; --muted: #707eae;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        body { background: var(--body-bg); color: var(--text-color); min-height: 100vh; display: flex; overflow-x: hidden; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); position: fixed; left: 0; top: 0; z-index: 1000; }
        .sidebar.collapsed { left: -260px; }
        .sidebar-brand { padding: 40px 25px; color: #fff; font-weight: 800; font-size: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { list-style: none; padding: 20px 15px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: #a3aed0; text-decoration: none; padding: 14px 18px; border-radius: 15px; font-weight: 600; margin-bottom: 5px; }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar-menu a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }

        /* CONTENT */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 35px; min-height: 100vh; }
        .main-content.expanded { margin-left: 0; width: 100%; }
        .top-header { display: flex; justify-content: space-between; align-items: center; background: var(--white); padding: 18px 25px; border-radius: 20px; margin-bottom: 30px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .icon-btn { cursor: pointer; width: 45px; height: 45px; background: var(--body-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid var(--border); color: var(--primary); }
        
        /* TABLE REFINED */
        .card { background: var(--card-bg); border-radius: 28px; padding: 30px; border: 1px solid var(--border); box-shadow: 0 20px 40px rgba(0,0,0,0.03); margin-bottom: 30px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; table-layout: fixed; }
        th { text-align: left; padding: 12px 20px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800; }
        td { padding: 18px 20px; background: var(--white); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-weight: 700; font-size: 14px; vertical-align: middle; }
        td:first-child { border-radius: 18px 0 0 18px; border-left: 1px solid var(--border); width: 25%; }
        td:last-child { border-radius: 0 18px 18px 0; border-right: 1px solid var(--border); width: 20%; text-align: right; }

        /* STATUS PILLS */
        .status-pill { padding: 8px 14px; border-radius: 12px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; }
        .status-online { background: #dcfce7; color: #15803d; }
        .status-offline { background: #f1f5f9; color: #64748b; }
        .status-finished { background: #e0f2fe; color: #0369a1; }
        .activity-badge { background: #eef2ff; color: #4361ee; padding: 8px 14px; border-radius: 10px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; }
        .activity-danger { background: #ffebeb; color: var(--danger); border: 1.5px solid #ffcaca; }

        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.7; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.7; } }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">ELMS <span style="color:var(--primary)">ADMIN PRO</span></div>
        <ul class="sidebar-menu">
            <li><a href="Dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
            <li><a href="Monitoring_akun.php" class="active"><i class='bx bxs-zap'></i> Monitoring User</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user-detail'></i> Data User</a></li>
            <li><a href="login.php"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="main-content">
        <div class="top-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="icon-btn" id="sidebar-toggle"><i class='bx bx-menu-alt-left'></i></div>
                <div>
                    <h1 style="font-size: 18px; font-weight: 800;">Real-time Monitoring</h1>
                    <p style="font-size: 12px; color: var(--muted); font-weight: 600;">Protokol: <span style="color:var(--danger)">DETEKSI PINDAH TAB & ALARM</span></p>
                </div>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="icon-btn" id="theme-btn"><i class='bx bx-moon' id="theme-icon"></i></div>
                <div style="background: #dcfce7; color: #15803d; padding: 10px 20px; border-radius: 15px; font-weight: 800; font-size: 13px;">
                    ● <?= $total_online ?> User Aktif
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; gap: 20px; flex-wrap: wrap;">
                <form method="GET" style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-weight: 700; font-size: 13px; color: var(--muted);">Limit:</span>
                    <select name="limit" class="icon-btn" style="width: auto; padding: 0 15px; font-size: 13px; font-weight: 800;" onchange="this.form.submit()">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="all" <?= $limit == 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                </form>
                <form method="GET" style="flex: 1; max-width: 400px; position: relative;">
                    <i class='bx bx-search' style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 20px;"></i>
                    <input type="text" name="cari" placeholder="Cari nama user..." value="<?= htmlspecialchars($search) ?>" 
                           style="width: 100%; padding: 14px 20px 14px 50px; border-radius: 15px; border: 1px solid var(--border); background: var(--body-bg); color: var(--text-color); font-weight: 600; outline: none;">
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Identitas User</th>
                            <th>Status Pengerjaan</th>
                            <th>Aktivitas Terakhir</th>
                            <th>Waktu Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_admin)): 
                            $cheat_keywords = ['Pindah Tab', 'Minimize', 'Buka Web', 'Copy', 'Paste'];
                            $is_cheating = false;
                            foreach($cheat_keywords as $key) { if(stripos($row['last_activity'], $key) !== false) { $is_cheating = true; break; } }
                        ?>
                        <tr style="<?= $is_cheating ? 'border-left: 5px solid var(--danger); background: rgba(255, 77, 77, 0.04);' : '' ?>">
                            <td style="padding-left: 25px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 38px; height: 38px; background: <?= $is_cheating ? 'var(--danger)' : 'var(--primary)' ?>; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                        <i class='bx <?= $is_cheating ? 'bxs-volume-full' : 'bxs-user' ?>'></i>
                                    </div>
                                    <div>
                                        <span style="<?= $is_cheating ? 'color:var(--danger); font-weight:800;' : '' ?>"><?= $row['username'] ?></span>
                                        <?php if($is_cheating): ?> <span style="color:var(--danger); font-size:10px; font-weight:800; animation: blink 0.8s infinite; display:block;">⚠️ ALARM BERBUNYI</span> <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if($row['last_activity'] == 'Selesai'): ?>
                                    <div class="status-pill status-finished"><i class='bx bx-check-shield'></i> Selesai</div>
                                <?php elseif($row['is_online'] == 1): ?>
                                    <div class="status-pill status-online">
                                        <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; display: inline-block; animation: pulse 1s infinite;"></span>
                                        Mengerjakan
                                    </div>
                                <?php else: ?>
                                    <div class="status-pill status-offline">Offline</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="activity-badge <?= $is_cheating ? 'activity-danger' : '' ?>">
                                    <i class='bx <?= $is_cheating ? 'bx-alarm-exclamation' : 'bx-radio-circle-marked' ?>'></i> 
                                    <?= $row['last_activity'] ?>
                                </span>
                            </td>
                            <td style="padding-right: 25px;">
                                <div style="font-weight: 800; color: var(--text-color); font-size: 14px;"><?= date('H:i:s', strtotime($row['last_login'])) ?> WIB</div>
                                <div style="font-size: 11px; color: var(--muted); font-weight: 600;"><?= getHariIndo($row['last_login']) . ", " . date('d M Y', strtotime($row['last_login'])) ?></div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="info-section">
            <h2 style="font-weight: 800; font-size: 22px; margin-bottom: 5px;">Pusat Informasi & Panduan Teknis</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h4><i class='bx bxs-volume-full'></i> Alarm Perangkat</h4>
                    <li><i class='bx bx-check-circle' style="color:var(--primary)"></i> HP/Laptop siswa akan berbunyi jika pindah tab.</li>
                    <li><i class='bx bx-check-circle' style="color:var(--primary)"></i> Admin dapat melihat icon speaker merah berkedip.</li>
                </div>
                <div class="info-card">
                    <h4><i class='bx bxs-bolt'></i> Sinkronisasi</h4>
                    <li><i class='bx bx-check-circle' style="color:var(--primary)"></i> Dashboard refresh otomatis setiap 5 detik.</li>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Script Sidebar & Theme (Sesuai kode asli)
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        document.getElementById('sidebar-toggle').onclick = () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        };

        const themeIcon = document.getElementById('theme-icon');
        if (localStorage.getItem('theme') === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            themeIcon.className = 'bx bx-sun';
        }
        document.getElementById('theme-btn').onclick = () => {
            if (document.body.hasAttribute('data-theme')) {
                document.body.removeAttribute('data-theme');
                themeIcon.className = 'bx bx-moon';
                localStorage.setItem('theme', 'light');
            } else {
                document.body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'bx bx-sun';
                localStorage.setItem('theme', 'dark');
            }
        };

        setInterval(() => {
            if(document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>