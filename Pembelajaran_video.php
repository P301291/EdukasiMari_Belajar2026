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

// --- 1. KONEKSI DATABASE ---
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "database1"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { 
    die("Koneksi gagal: " . mysqli_connect_error()); 
}

// --- 2. PROTEKSI AKUN (FITUR BARU) ---
// Mengecek apakah user masih terdaftar di database.
if (isset($_SESSION['username'])) {
    $username_sesi = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Sesuaikan nama tabel 'user' dan kolom 'username' dengan database Anda
    $cek_user = mysqli_query($conn, "SELECT * FROM user WHERE username = '$username_sesi'");
    
    // Jika baris data tidak ditemukan (berarti sudah dihapus oleh admin)
    if (mysqli_num_rows($cek_user) == 0) {
        session_destroy();
        echo "<script>
                alert('Sesi Berakhir: Akun Anda telah dihapus atau dinonaktifkan oleh Admin.');
                window.location.href = 'Login_user.php';
              </script>";
        exit();
    }
} else {
    // Jika belum login, arahkan ke halaman login
    header("Location: Login_user.php");
    exit();
}

// --- 3. DATA MATERI ---
$materi_list = [
    ['id' => 1, 'judul' => '01. Pengenalan ELMS Learning', 'video' => 'dQw4w9WgXcQ', 'durasi' => '05:20'],
    ['id' => 2, 'judul' => '02. Setup Lingkungan Kerja', 'video' => 'y881t8ilMyc', 'durasi' => '10:15'],
    ['id' => 3, 'judul' => '03. Memahami Struktur Dasar', 'video' => '1Rs2ND1ryYc', 'durasi' => '15:45'],
    ['id' => 4, 'judul' => '04. Implementasi Desain UI', 'video' => 'TaBWhb5SPfc', 'durasi' => '20:00'],
    ['id' => 5, 'judul' => '05. Finalisasi & Troubleshooting', 'video' => 'jGyYuQf-GeE', 'durasi' => '12:30'],
];

$total_materi = count($materi_list);
$current_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$persentase = round(($current_id / $total_materi) * 100);

$current_materi = $materi_list[0];
foreach ($materi_list as $m) {
    if ($m['id'] == $current_id) { $current_materi = $m; break; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Pembelajaran | ELMS Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { 
            --dark-sidebar: #0f172a; 
            --active-blue: #4f46e5; 
            --bg-gray: #f8fafc;
            --text-gray: #94a3b8;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-gray); display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--dark-sidebar); display: flex; flex-direction: column; color: white; padding: 20px 15px; flex-shrink: 0; }
        .logo-area { padding: 10px 5px 30px 5px; }
        .logo-area h2 { font-size: 18px; font-weight: 800; letter-spacing: 0.5px; }
        
        .user-panel { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .user-icon { width: 35px; height: 35px; background: var(--active-blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .user-info p { font-size: 10px; color: var(--text-gray); font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .user-info h4 { font-size: 13px; font-weight: 700; color: #f8fafc; }
        .status-aktif { font-size: 9px; color: #10b981; display: flex; align-items: center; gap: 4px; margin-top: 2px; }

        .nav-menu { flex: 1; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; text-decoration: none; color: #cbd5e1; font-size: 13px; font-weight: 500; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .nav-link:hover:not(.locked) { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: var(--active-blue); color: white; }
        .nav-link i { font-size: 18px; }

        /* KUNCI MENU */
        .nav-link.locked { 
            opacity: 0.4; 
            cursor: not-allowed; 
            pointer-events: none; 
            filter: grayscale(1);
        }

        .logout-link { color: #ef4444; margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; align-items: center; gap: 12px; text-decoration: none; font-size: 14px; font-weight: 700; padding-left: 15px; }

        /* CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .workspace { display: flex; flex: 1; overflow: hidden; }
        .video-player-area { flex: 1; overflow-y: auto; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        
        .video-wrapper { width: 100%; max-width: 900px; } 
        
        .video-box { background: #000; border-radius: 15px; overflow: hidden; aspect-ratio: 16/9; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; }
        iframe { width: 100%; height: 100%; border: none; }

        .video-navigation { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .btn-nav { display: flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-prev { background: white; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-next { background: var(--active-blue); color: white; border: 1px solid var(--active-blue); }

        .playlist-sidebar { width: 320px; background: white; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; flex-shrink: 0; }
        .playlist-header { padding: 20px; border-bottom: 1px solid #f1f5f9; }
        .playlist-header h3 { font-size: 14px; color: var(--dark-sidebar); }

        .materi-list { flex: 1; overflow-y: auto; }
        .materi-item { display: flex; align-items: center; gap: 12px; padding: 15px 20px; text-decoration: none; color: #64748b; font-size: 12px; border-bottom: 1px solid #f8fafc; }
        .materi-item.active { background: #f1f5f9; color: var(--active-blue); font-weight: 700; border-left: 4px solid var(--active-blue); }
        
        .prog-track { background: #e2e8f0; height: 6px; border-radius: 10px; margin-top: 10px; overflow: hidden; }
        .prog-bar { background: var(--active-blue); height: 100%; width: <?= $persentase ?>%; transition: 0.5s; }
    </style>
</head>
<body>

    <aside class="sidebar">
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
            <a href="#" class="nav-link active"><i class='bx bxs-video'></i> Video Pembelajaran</a>
            <a href="Absensi2.php" class="nav-link locked" id="menu-absensi"><i class='bx bxs-edit-alt'></i> Absensi</a>
            <a href="cetak_kartu.php" class="nav-link locked" id="menu-kartu"><i class='bx bxs-id-card'></i> Cetak Kartu</a>
            <a href="cetak_sertifikat.php" class="nav-link locked" id="menu-sertif"><i class='bx bxs-graduation'></i> Cetak Sertifikat</a>
        </nav>
        <a href="Logout.php" class="logout-link"><i class='bx bx-power-off'></i> Keluar</a>
    </aside>

    <main class="main-content">
        <div class="workspace">
            <div class="video-player-area">
                <div class="video-wrapper">
                    <div style="margin-bottom: 20px; font-size: 12px; color: #64748b;">
                        Dashboard / Kursus / <span style="color: var(--dark-sidebar); font-weight: 700;">Video Player</span>
                    </div>
                    
                    <div class="video-box">
                        <iframe id="video-player" src="https://www.youtube.com/embed/<?= $current_materi['video'] ?>?rel=0&enablejsapi=1" allowfullscreen></iframe>
                    </div>

                    <div style="margin-top: 25px;">
                        <h1 style="font-size: 22px; color: var(--dark-sidebar); font-weight: 800;"><?= $current_materi['judul'] ?></h1>
                        <div id="status-nonton" style="margin-top: 10px; display: inline-block; padding: 5px 12px; background: #fff1f2; color: #e11d48; border-radius: 20px; font-size: 11px; font-weight: 700;">
                            <i class='bx bx-lock-alt'></i> Selesaikan video untuk membuka menu absensi
                        </div>
                    </div>

                    <div class="video-navigation">
                        <?php if($current_id > 1): ?>
                            <a href="?id=<?= $current_id - 1 ?>" class="btn-nav btn-prev">
                                <i class='bx bx-left-arrow-alt'></i> Materi Sebelumnya
                            </a>
                        <?php else: ?>
                            <div></div> 
                        <?php endif; ?>

                        <?php if($current_id < $total_materi): ?>
                            <a href="?id=<?= $current_id + 1 ?>" class="btn-nav btn-next">
                                Materi Selanjutnya <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="playlist-sidebar">
                <div class="playlist-header">
                    <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:800; color:#94a3b8;">
                        <span>PROGRES KURSUS</span>
                        <span><?= $persentase ?>%</span>
                    </div>
                    <div class="prog-track"><div class="prog-bar"></div></div>
                    <h3 style="margin-top: 20px;">Daftar Modul</h3>
                </div>
                <div class="materi-list">
                    <?php foreach($materi_list as $m): ?>
                        <a href="?id=<?= $m['id'] ?>" class="materi-item <?= ($m['id'] == $current_id) ? 'active' : '' ?>">
                            <i class='bx <?= ($m['id'] < $current_id) ? 'bxs-check-circle' : 'bx-play-circle' ?>' 
                               style="<?= ($m['id'] < $current_id) ? 'color: #10b981;' : '' ?>"></i>
                            <div>
                                <p><?= $m['judul'] ?></p>
                                <span style="font-size: 10px; opacity: 0.6;"><?= $m['durasi'] ?> Menit</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </main>

    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        var player;
        function onYouTubeIframeAPIReady() {
            player = new YT.Player('video-player', {
                events: {
                    'onStateChange': onPlayerStateChange
                }
            });
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.ENDED) {
                unlockMenu();
            }
        }

        function unlockMenu() {
            const menuAbsensi = document.getElementById('menu-absensi');
            const menuKartu = document.getElementById('menu-kartu');
            const menuSertif = document.getElementById('menu-sertif');
            const statusLabel = document.getElementById('status-nonton');

            menuAbsensi.classList.remove('locked');
            menuKartu.classList.remove('locked');
            menuSertif.classList.remove('locked');

            statusLabel.style.background = "#dcfce7";
            statusLabel.style.color = "#166534";
            statusLabel.innerHTML = "<i class='bx bx-check-circle'></i> Video selesai! Menu telah terbuka.";
            
            alert("Selamat! Video selesai ditonton. Anda sekarang bisa melakukan absensi.");
        }
    </script>
</body>
</html>