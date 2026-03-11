<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "database1");

if (isset($_SESSION['username'])) {
    $u = $_SESSION['username'];
    // Ambil status dari sinyal JavaScript (Active atau Idle)
    $status_fisik = isset($_GET['status']) ? $_GET['status'] : 'Idle';
    
    // Jika user sedang aktif (gerak mouse/ketik), is_online tetap 1
    // Jika user tidak gerak (Idle), is_online jadi 0 tapi last_activity tetap "Active"
    $is_online = ($status_fisik == 'Active') ? 1 : 0;

    mysqli_query($conn, "UPDATE user SET 
        last_login = NOW(), 
        is_online = $is_online, 
        last_activity = 'Active' 
        WHERE username = '$u'");
}
?>

<script>
let userStatus = "Active";
let idleTimer;

// Fungsi untuk lapor ke server
function sendHeartbeat() {
    fetch('heartbeat.php?status=' + userStatus);
}

// Fungsi deteksi aktifitas (gerak mouse, tekan tombol, scroll)
function resetIdleTimer() {
    userStatus = "Active";
    clearTimeout(idleTimer);
    // Jika dalam 30 detik tidak ada gerakan, status berubah jadi Idle
    idleTimer = setTimeout(() => {
        userStatus = "Idle";
    }, 30000); 
}

// Event listener untuk aktifitas fisik
window.onload = resetIdleTimer;
window.onmousemove = resetIdleTimer;
window.onmousedown = resetIdleTimer;
window.ontouchstart = resetIdleTimer;
window.onclick = resetIdleTimer;
window.onkeypress = resetIdleTimer;

// Kirim laporan ke server setiap 10 detik
setInterval(sendHeartbeat, 10000);
</script>
<?php

date_default_timezone_set('Asia/Jakarta');

// --- 1. KONEKSI & KONFIGURASI ---
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db = "database1"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { 
    die("Koneksi gagal: " . mysqli_connect_error()); 
}

// --- 2. PROTEKSI AKUN (FITUR KEAMANAN) ---
// Mengecek apakah user masih ada di database. Jika dihapus admin, otomatis logout.
if (isset($_SESSION['username'])) {
    $username_sesi = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Sesuaikan nama tabel 'user' dan kolom 'username' dengan database Anda
    $cek_user_aktif = mysqli_query($conn, "SELECT * FROM user WHERE username = '$username_sesi'");
    
    if (mysqli_num_rows($cek_user_aktif) == 0) {
        session_destroy();
        echo "<script>
                alert('Akses Ditolak! Akun Anda telah dihapus atau dinonaktifkan oleh Admin.');
                window.location.href = 'login_user.php';
              </script>";
        exit();
    }
} else {
    header("Location: login_user.php");
    exit();
}

// --- 3. LOGIKA PEMBATASAN WAKTU ABSENSI ---
$jam_sekarang = (int)date('H');
$is_closed = ($jam_sekarang >= 22 || $jam_sekarang < 1); 

// --- 4. LOGIKA PROSES ABSENSI ---
$notifikasi = "";
$show_exam_button = false;

if (isset($_POST['submit_absen'])) {
    if ($is_closed) {
        $notifikasi = "<div class='alert danger'><i class='bx bx-time-five'></i> Maaf, absensi hanya dibuka pukul 06:00 s/d 22:00 WIB.</div>";
    } else {
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $mapel = mysqli_real_escape_string($conn, $_POST['mapel']);
        $waktu = date('H:i:s');
        $tanggal = date('Y-m-d');

        // Cek apakah sudah absen hari ini untuk mapel yang sama
        $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE nama='$nama' AND tanggal='$tanggal' AND mapel='$mapel'");
        
        if (mysqli_num_rows($cek) > 0) {
            $notifikasi = "<div class='alert danger'><i class='bx bx-error-circle'></i> Anda sudah melakukan absensi hari ini.</div>";
            $show_exam_button = true;
        } else {
            $query = "INSERT INTO absensi (nama, kelas, mapel, waktu_absen, tanggal, status) 
                      VALUES ('$nama', '$kelas', '$mapel', '$waktu', '$tanggal', 'Hadir')";
            if (mysqli_query($conn, $query)) {
                $notifikasi = "<div class='alert success'><i class='bx bx-check-double'></i> Absensi berhasil direkam!</div>";
                $show_exam_button = true;
            }
        }
    }
}

// --- 5. LOGIKA PENCARIAN & PAGINATION ---
$search = "";
$where_query = "";
if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_query = " WHERE nama LIKE '%$search%' OR mapel LIKE '%$search%' OR kelas LIKE '%$search%' ";
}

$limit = 3; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman - 1) * $limit;

// Hitung total data untuk pagination
$total_data_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi $where_query");
$total_res = mysqli_fetch_assoc($total_data_query);
$total_halaman = ceil($total_res['total'] / $limit);

// Ambil data absensi
$data_absen = mysqli_query($conn, "SELECT * FROM absensi $where_query ORDER BY id DESC LIMIT $offset, $limit");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi | ELMS Learning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --dark-sidebar: #0f172a;
            --active-blue: #4f46e5;
            --bg-gray: #f8fafc;
            --text-gray: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-gray); display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { 
            width: 260px; background: var(--dark-sidebar); display: flex; flex-direction: column;
            color: white; padding: 20px 15px; position: fixed; height: 100vh; transition: 0.3s; z-index: 1000;
        }
        .logo-area { padding: 10px 5px 30px 5px; }
        .logo-area h2 { font-size: 18px; font-weight: 800; letter-spacing: 0.5px; color: #fff; }

        .user-panel { 
            background: rgba(255,255,255,0.05); border-radius: 12px; padding: 15px; 
            display: flex; align-items: center; gap: 12px; margin-bottom: 30px;
        }
        .user-icon { 
            width: 35px; height: 35px; background: var(--active-blue); 
            border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .user-info p { font-size: 10px; color: var(--text-gray); font-weight: 600; text-transform: uppercase; margin: 0; }
        .user-info h4 { font-size: 13px; font-weight: 700; color: #f8fafc; margin: 0; }
        .status-aktif { font-size: 9px; color: var(--success); display: flex; align-items: center; gap: 4px; margin-top: 2px; }

        .nav-menu { flex: 1; }
        .nav-link { 
            display: flex; align-items: center; gap: 12px; padding: 12px 15px; text-decoration: none; 
            color: #cbd5e1; font-size: 13px; font-weight: 500; border-radius: 10px; margin-bottom: 5px; transition: 0.2s;
        }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: var(--active-blue); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

        .logout-link { 
            color: var(--danger); margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); 
            padding-top: 20px; display: flex; align-items: center; gap: 12px; text-decoration: none; font-size: 14px; font-weight: 700; padding-left: 15px;
        }

        /* CONTENT */
        .main-content { flex: 1; margin-left: 260px; padding: 30px; transition: 0.3s; width: calc(100% - 260px); }
        .top-header { 
            display: flex; justify-content: space-between; align-items: center; 
            background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 30px; border: 1px solid #e2e8f0;
        }

        .grid-container { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; }
        .card { background: white; border-radius: 15px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .card h3 { font-size: 15px; font-weight: 800; color: var(--dark-sidebar); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .search-box { display: flex; gap: 10px; margin-bottom: 20px; background: #f1f5f9; padding: 10px; border-radius: 12px; }
        .search-box input { flex: 1; border: 1px solid #cbd5e1; padding: 8px 15px; border-radius: 8px; outline: none; }
        .btn-search { background: var(--dark-sidebar); color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; outline: none; font-size: 14px; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--active-blue); }

        .btn { padding: 12px 25px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; justify-content: center; font-size: 14px; text-decoration: none; }
        .btn-p { background: var(--active-blue); color: white; width: 100%; }
        .btn-success { background: var(--success); color: white; width: 100%; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase; padding: 12px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px 12px; font-size: 13px; color: #475569; border-bottom: 1px solid #f1f5f9; }

        .pagination { display: flex; gap: 8px; margin-top: 20px; justify-content: center; }
        .pagination a { padding: 8px 15px; background: white; border: 1px solid #e2e8f0; text-decoration: none; color: #64748b; border-radius: 8px; font-size: 13px; font-weight: 600; }
        .pagination a.active { background: var(--active-blue); color: white; border-color: var(--active-blue); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 992px) {
            .sidebar { left: -260px; }
            .main-content { margin-left: 0; width: 100%; }
            .grid-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo-area"><h2>ELMS MARI BELAJAR</h2></div>

        <div class="user-panel">
            <div class="user-icon"><i class='bx bxs-user'></i></div>
            <div class="user-info">
                <p>Logged in as:</p>
                <h4><?= htmlspecialchars($_SESSION['username']); ?></h4>
                <div class="status-aktif">● Aktif</div>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="Pembelajaran_video.php" class="nav-link"><i class='bx bxs-video'></i> Video Pembelajaran</a>
            <a href="#" class="nav-link active"><i class='bx bxs-edit-alt'></i> Absensi Ujian</a>
            <a href="cetak_kartu.php" class="nav-link"><i class='bx bxs-id-card'></i> Cetak Kartu</a>
            <a href="cetak_sertifikat.php" class="nav-link locked"><i class='bx bxs-graduation'></i> Cetak Sertifikat</a>
        </nav>

        <a href="login_user.php" class="logout-link"><i class='bx bx-power-off'></i> Keluar</a>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <h1 style="font-size: 18px; font-weight: 800; color: var(--dark-sidebar);">Presensi Peserta</h1>
            <div style="font-size: 12px; font-weight: 700; color: var(--active-blue); background: #f0f3ff; padding: 8px 15px; border-radius: 8px;">
                <i class='bx bx-calendar-event'></i> <?= date('d M Y') ?> | <span id="clock"><?= date('H:i') ?></span> WIB
            </div>
        </div>

        <?= $notifikasi; ?>

        <?php if ($show_exam_button): ?>
            <div class="card" style="border: 2px solid var(--success); background: #f0fff4; text-align: center;">
                <h4 style="color: var(--success); margin-bottom: 10px;">Akses Ujian Tersedia</h4>
                <a href="ujian.php" class="btn btn-success"><i class='bx bx-rocket'></i> MULAI KERJAKAN SOAL SEKARANG</a>
            </div>
        <?php endif; ?>

        <div class="grid-container">
            <div class="card">
                <h3><i class='bx bxs-edit-alt' style="color: var(--active-blue);"></i> Form Absensi</h3>
                <?php if ($is_closed): ?>
                    <div style="text-align: center; padding: 30px; background: #fff1f2; border-radius: 12px; border: 2px dashed #fecaca;">
                        <i class='bx bxs-moon' style="font-size: 40px; color: var(--danger);"></i>
                        <h4 style="margin-top: 10px; color: var(--danger);">Sistem Sedang Offline</h4>
                        <p style="font-size: 12px; color: #991b1b;">Absensi dibuka pukul 06:00 - 22:00 WIB.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['username']); ?>" required readonly style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label>Kelas Pelatihan</label>
                            <select name="kelas" required>
                                <option value="">-- Pilih Kelas --</option>
                                <option value="Kelas 1">Kelas 1</option>
                                <option value="Kelas 2">Kelas 2</option>
                                <option value="Kelas 3">Kelas 3</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mata Pelajaran / Modul</label>
                            <input type="text" name="mapel" placeholder="Contoh: Web Development Dasar" required>
                        </div>
                        <button type="submit" name="submit_absen" class="btn btn-p">
                            <i class='bx bx-check-shield'></i> Konfirmasi Kehadiran
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3><i class='bx bx-time' style="color: #f59e0b;"></i> Info Operasional</h3>
                <div style="padding: 15px; background: #fffbeb; border-radius: 10px; border: 1px solid #fef3c7;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px;">
                        <span>Status Sistem:</span>
                        <b style="color: <?= $is_closed ? 'var(--danger)' : 'var(--success)' ?>"><?= $is_closed ? 'OFFLINE' : 'ONLINE' ?></b>
                    </div>
                    <p style="font-size: 12px; color: #92400e; line-height: 1.5;">Data absensi akan tercatat secara permanen di server.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
                <h3><i class='bx bx-list-ul' style="color: var(--active-blue);"></i> Riwayat Kehadiran</h3>
                <form method="GET" class="search-box" style="margin-bottom: 0;">
                    <input type="text" name="search" placeholder="Cari nama atau mapel..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search"><i class='bx bx-search'></i> Cari</button>
                    <?php if($search != ""): ?>
                        <a href="?" class="btn" style="background: #cbd5e1; padding: 8px 12px; border-radius: 8px; font-size: 12px; color: #334155;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Mata Pelajaran</th>
                            <th>Waktu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($data_absen) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($data_absen)) { ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700;"><?= htmlspecialchars($row['nama']) ?></div>
                                    <div style="font-size: 11px; color: var(--text-gray);"><?= htmlspecialchars($row['kelas']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['mapel']) ?></td>
                                <td><?= date('H:i', strtotime($row['waktu_absen'])) ?> WIB</td>
                                <td><span style="color: var(--success); font-weight: 700;">● Hadir</span></td>
                            </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color: var(--text-gray); padding: 30px;">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_halaman > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_halaman; $i++): ?>
                    <a href="?halaman=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= ($i == $halaman) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clock').textContent = hours + ':' + minutes;
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>