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
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);

if (!isset($_SESSION['username'])) {
    die("Silahkan login terlebih dahulu untuk mencetak sertifikat.");
}

$username = $_SESSION['username'];

// --- 2. AMBIL DATA NILAI TERBARU ---
$query = "SELECT * FROM nilai_ujian WHERE username = '$username' ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Anda belum memiliki nilai ujian!'); window.location='ujian.php';</script>";
    exit;
}

// Logika Predikat
$nilai = $data['skor'];
if ($nilai >= 90) { $predikat = "Sangat Memuaskan (Distinction)"; }
elseif ($nilai >= 80) { $predikat = "Memuaskan (Merit)"; }
elseif ($nilai >= 70) { $predikat = "Cukup (Pass)"; }
else { $predikat = "Kurang (Fail)"; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat_<?= $username ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Pengaturan Dasar Browser */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f5f5f5; font-family: 'Montserrat', sans-serif; display: flex; justify-content: center; padding: 20px; }

        /* STANDAR KERTAS A4 LANDSCAPE */
        .certificate-container {
            width: 297mm;
            height: 210mm;
            padding: 12mm;
            background: white;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* Bingkai Luar Tebal */
        .border-outer {
            border: 8px solid #1a237e;
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 40px;
        }

        /* Bingkai Dalam Emas */
        .border-inner {
            border: 2px solid #c5a059;
            height: calc(100% - 20px);
            width: calc(100% - 20px);
            position: absolute;
            top: 10px;
            left: 10px;
            pointer-events: none;
        }

        /* Konten Sertifikat */
        .header h1 { font-family: 'Cinzel', serif; font-size: 52px; color: #1a237e; margin: 0; text-align: center; }
        .header p { letter-spacing: 8px; font-weight: 700; color: #c5a059; margin-top: 5px; text-align: center; font-size: 14px; }

        .content { text-align: center; margin-top: 40px; flex-grow: 1; }
        .content .text { font-size: 18px; color: #555; margin-bottom: 10px; }
        
        .name { 
            font-family: 'Great Vibes', cursive; 
            font-size: 60px; 
            color: #1a237e; 
            margin: 15px 0;
            border-bottom: 2px solid #c5a059;
            display: inline-block;
            min-width: 500px;
        }

        .subject { font-weight: 700; font-size: 22px; color: #333; margin: 10px 0; }

        .score-box { margin-top: 25px; font-size: 16px; color: #444; }
        .score-box b { color: #1a237e; font-size: 20px; }

        /* Footer & Tanda Tangan */
        .footer { 
            width: 90%; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            margin-top: 30px; 
        }

        .info-left { text-align: left; font-size: 12px; color: #666; line-height: 1.6; }
        
        .signature-area { text-align: center; width: 250px; }
        .signature-role { font-weight: 700; font-size: 16px; margin-bottom: 70px; color: #333; }
        .signature-name { font-weight: 700; font-size: 14px; border-bottom: 1.5px solid #333; display: inline-block; padding: 0 15px; color: #1a237e; }
        .signature-title { font-size: 12px; margin-top: 5px; color: #555; font-weight: 600; }

        /* CSS KHUSUS PRINT */
        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { padding: 0; background: none; }
            .no-print { display: none; }
            .certificate-container { box-shadow: none; margin: 0; border: none; }
        }

        .btn-float {
            position: fixed; top: 20px; right: 20px;
            padding: 12px 24px; background: #1a237e; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-weight: 700;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000;
        }
    </style>
</head>
<body>

    <button class="btn-float no-print" onclick="window.print()">
        <i class='bx bx-printer'></i> CETAK SEKARANG (A4)
    </button>

    <div class="certificate-container">
        <div class="border-outer">
            <div class="border-inner"></div>
            
            <div class="header">
                <h1>SERTIFIKAT</h1>
                <p>PENGHARGAAN MARI BELAJAR</p>
            </div>

            <div class="content">
                <div class="text">Diberikan secara hormat kepada:</div>
                <div class="name"><?= $username ?></div>
                <div class="text">Telah menyelesaikan ujian kompetensi bidang:</div>
                <div class="subject">ILMU TEKNOLOGI</div>
                
                <div class="score-box">
                    Nilai Akhir: <b><?= $nilai ?></b> &nbsp; | &nbsp; Predikat: <i><?= $predikat ?></i>
                </div>
            </div>

            <div class="footer">
                <div class="info-left">
                    <p><strong>ID Sertifikat:</strong> ELMS/<?= date('Y') ?>/0<?= $data['id'] ?></p>
                    <p><strong>Tanggal Terbit:</strong> <?= date('d F Y', strtotime($data['tanggal'])) ?></p>
                    <p><i>Dicetak secara otomatis oleh Sistem ELMS Learning</i></p>
                </div>
            
                <div class="signature-area">
                    <div class="signature-role">Ketua,</div>
                    <div class="signature-name">Candra Argadinata, S.Kom.</div><br>
                    <div class="signature-title">Ketua Mari Belajar</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>