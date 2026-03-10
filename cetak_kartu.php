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

include 'koneksi.php';

// Proteksi Login
if (!isset($_SESSION['username'])) { 
    header("location:login.php"); 
    exit(); 
}

$username = $_SESSION['username'];

// Ambil data siswa dari database (sesuaikan nama tabel Anda)
// Di sini saya asumsikan ada tabel 'users' atau 'siswa'
$query = mysqli_query($conn, "SELECT * FROM user WHERE username = '$username'");
$user = mysqli_fetch_assoc($query);

// Data dummy jika foto atau NIS kosong
$nis = isset($user['nis']) ? $user['nis'] : "KURSUS KOMPUTER";
$photo = isset($_SESSION['photo']) ? $_SESSION['photo'] : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=4361ee&color=fff&size=128";
$sekolah = "Mari Belajar";
$alamat = "Jl. Raya Pendidikan No. 123, Jakarta";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Pelajar - <?= $username ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f0f2f5; display: flex; flex-direction: column; align-items: center; padding: 50px; }

        /* Style Kartu */
        .card-container {
            width: 85.6mm; /* Ukuran Standar ID Card (ISO 7810) */
            height: 56mm;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            background-image: linear-gradient(135deg, #ffffff 0%, #f1f4f9 100%);
        }

        /* Dekorasi Background */
        .card-container::before {
            content: ""; position: absolute; top: -20px; right: -20px;
            width: 100px; height: 100px; background: #4361ee;
            border-radius: 50%; opacity: 0.1;
        }

        .header {
            background:rgb(40, 146, 13); color: white; padding: 4px;
            text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .header h2 { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .header p { font-size: 6px; opacity: 0.9; }

        .content { display: flex; padding: 23px; gap: 15px; align-items: center; height: 70%; }
        
        .photo-box {
            width: 22mm; height: 28mm;
            border: 2px solid #4361ee; border-radius: 6px;
            overflow: hidden; background: #eee;
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; }

        .info { flex: 1; }
        .info-row { margin-bottom: 5px; }
        .label { font-size: 7px; color: #a3aed0; font-weight: 700; text-transform: uppercase; }
        .value { font-size: 11px; color: #2b3674; font-weight: 800; }

        .footer-card {
            position: absolute; bottom: 0; width: 100%;
            background:rgb(13, 167, 33); color: white;
            padding: 3px; font-size: 12px; text-align: center; font-weight: 600;
        }

        .btn-print {
            margin-top: 30px; padding: 12px 25px;
            background: #4361ee; color: white; border: none;
            border-radius: 10px; cursor: pointer; font-weight: 700;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        /* SETTING KHUSUS CETAK */
        @media print {
            body { background: white; padding: 0; }
            .btn-print { display: none; }
            .card-container { box-shadow: none; border: 1px solid #000; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="card-container" id="printableCard">
        <div class="header">
            <div>
                <h2>KARTU PELAJAR ELMS</h2>
                <p><h2><?= $sekolah ?></h2></p>
            </div>
        </div>

        <div class="content">
            <div class="photo-box">
                <img src="<?= $photo ?>" alt="Foto Siswa">
            </div>
            <div class="info">
                <div class="info-row">
                    <p class="label">Nama Lengkap</p>
                    <p class="value"><?= strtoupper($username) ?></p>
                </div>
                <div class="info-row">
                    <p class="label">PESERTA</p>
                    <p class="value"><?= $nis ?></p>
                </div>
                <div class="info-row">
                    <p class="label">Status</p>
                    <p class="value" style="color: #22c55e;">AKTIF</p>
                </div>
            </div>
        </div>

        <div class="footer-card">
            Validitas kartu berlaku selama menjadi siswa aktif
        </div>
    </div>

    <button class="btn-print" onclick="window.print()">
        <i class='#'></i> Cetak Kartu Sekarang
    </button>
    <button class="btn-print" onclick="window.location.href='absensi2.php'">Kembali</button>
    <p style="margin-top: 15px; font-size: 12px; color: #666;">
        Tip: Gunakan kertas <i>PVC Card</i> atau kertas foto tebal untuk hasil maksimal.
    </p>

</body>
</html>