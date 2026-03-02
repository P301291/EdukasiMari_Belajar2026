<?php
session_start();
include 'koneksi.php';

// 1. Proteksi Login
if (!isset($_SESSION['username'])) { 
    header("location:login.php"); 
    exit(); 
}

$username = $_SESSION['username'];
$user_photo = isset($_SESSION['photo']) ? $_SESSION['photo'] : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=4361ee&color=fff&bold=true";

// 2. Validasi Apakah Sudah Ujian (Sistem Kunci)
$cek_sudah_ujian = mysqli_query($conn, "SELECT * FROM nilai_ujian WHERE username = '$username'");
$data_nilai = mysqli_fetch_assoc($cek_sudah_ujian);
$sudah_selesai = mysqli_num_rows($cek_sudah_ujian) > 0;

// 3. Handler Simpan Nilai (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_simpan'])) {
    if ($sudah_selesai) { header("HTTP/1.1 403 Forbidden"); exit(); }
    $skor = mysqli_real_escape_string($conn, $_POST['skor']);
    $benar = mysqli_real_escape_string($conn, $_POST['benar']);
    $query = "INSERT INTO nilai_ujian (username, skor, benar, tanggal) VALUES ('$username', '$skor', '$benar', NOW())";
    if (mysqli_query($conn, $query)) { echo "success"; } else { header("HTTP/1.1 500 Internal Server Error"); }
    exit();
}

// 4. Ambil Data Soal
$semua_soal = [];
if (!$sudah_selesai) {
    $query_soal = mysqli_query($conn, "SELECT * FROM ujian_soal LIMIT 100");
    while($row = mysqli_fetch_assoc($query_soal)) { $semua_soal[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ELMS Mari Belajar | Secured Mobile Exam</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { 
            --p: #4361ee; --s: #3f37c9; --bg: #f8faff; --white: #ffffff; 
            --dark: #2b3674; --text-light: #a3aed0; --success: #22c55e; --danger: #ff4757;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--dark); min-height: 100vh; user-select: none; -webkit-tap-highlight-color: transparent; }

        /* --- HEADER --- */
        header {
            background: var(--white); padding: 12px 20px; display: flex; 
            justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); z-index: 100; position: sticky; top: 0;
        }
        .logo-text { font-weight: 800; color: var(--p); font-size: 16px; }
        .user-pill { display: flex; align-items: center; gap: 8px; padding: 5px 12px; background: #f1f4f9; border-radius: 50px; }
        .user-pill img { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; }
        .user-name { font-size: 12px; font-weight: 800; }

        /* --- MODAL STYLE (Notifikasi Sesuai Sistem) --- */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.9);
            z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px;
            backdrop-filter: blur(10px);
        }
        .modal-card {
            background: var(--white); padding: 30px; border-radius: 30px;
            width: 100%; max-width: 500px; text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        .guide-step { display: flex; align-items: flex-start; gap: 15px; text-align: left; margin-bottom: 20px; }
        .step-icon { background: #f1f4f9; color: var(--p); padding: 10px; border-radius: 12px; font-size: 24px; }
        .step-text h4 { font-size: 14px; font-weight: 800; margin-bottom: 2px; }
        .step-text p { font-size: 12px; color: var(--text-light); line-height: 1.4; }

        /* --- LAYOUT UTAMA --- */
        .main-wrapper { display: flex; flex-direction: column; } 
        .exam-area { flex: 1; padding: 15px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 800px; }

        .soal-card { 
            background: var(--white); padding: 25px; border-radius: 20px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.02); display: none; border: 1px solid #edf2f7; margin-bottom: 20px;
        }
        .soal-card.active { display: block; animation: fadeIn 0.3s ease-out; }
        
        /* Fix Tampilan Rumus & Kode */
        .pertanyaan-text { 
            font-size: 17px; line-height: 1.6; font-weight: 700; 
            white-space: pre-wrap; word-wrap: break-word; margin-bottom: 20px; 
        }

        .opsi { 
            border: 2px solid #f1f4f9; padding: 15px; border-radius: 12px; 
            cursor: pointer; margin-bottom: 12px; transition: 0.2s; font-weight: 600; font-size: 14px;
            white-space: pre-wrap; word-wrap: break-word;
        }
        .opsi.selected { background: var(--p); color: white; border-color: var(--p); }

        /* --- SIDEBAR & NAV --- */
        .sidebar { width: 100%; background: var(--white); padding: 25px; border-top: 1px solid #eee; }
        .timer-box { background: #f1f4f9; padding: 15px; border-radius: 15px; text-align: center; margin-bottom: 20px; }
        .nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 8px; }
        .nav-btn { height: 42px; display: flex; align-items: center; justify-content: center; background: #f1f4f9; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 13px; }
        .nav-btn.done { background: var(--success); color: white; }
        .nav-btn.current { border: 2px solid var(--p); color: var(--p); background: #fff; }

        .action-bar { 
            display: flex; justify-content: space-between; gap: 10px; 
            padding: 15px; background: var(--white); position: sticky; bottom: 0; border-top: 1px solid #eee;
        }
        .btn-nav-action { flex: 1; padding: 14px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }

        @media (min-width: 992px) {
            .main-wrapper { flex-direction: row; height: calc(100vh - 70px); }
            .exam-area { padding: 40px; overflow-y: auto; }
            .sidebar { width: 350px; border-top: none; border-left: 1px solid #eee; }
            .action-bar { position: static; background: transparent; padding: 20px 0; border: none; }
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body oncontextmenu="return false;">

    <?php if (!$sudah_selesai): ?>
    <div id="modalGuide" class="modal-overlay">
        <div class="modal-card">
            <div style="background: #eef2ff; width: 60px; height: 60px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class='bx bxs-bulb' style="font-size: 30px; color: var(--p);"></i>
            </div>
            <h2 style="font-weight: 800; margin-bottom: 5px;">Siap Ujian, <?= $username ?>?</h2>
            <p style="color: var(--text-light); font-size: 13px; margin-bottom: 25px;">Baca petunjuk berikut agar pengerjaan lancar.</p>

            <div class="guide-step">
                <div class="step-icon"><i class='bx bx-time-five'></i></div>
                <div class="step-text">
                    <h4>Waktu Terbatas</h4>
                    <p>Anda memiliki waktu 60 menit untuk menyelesaikan semua soal.</p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-icon"><i class='bx bx-select-multiple'></i></div>
                <div class="step-text">
                    <h4>Akses</h4>
                    <p>Peserta hanya dikasih satu kali akses</p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-icon"><i class='bx bx-select-multiple'></i></div>
                <div class="step-text">
                    <h4>Pengiriman Soal</h4>
                    <p>Setelah klik kirim akan otomatis terkunci.</p>
                </div>
            </div>

            <button onclick="mulaiUjian()" class="btn-nav-action" style="background: var(--p); color: white; width: 100%;">
                Mulai Ujian Sekarang <i class='bx bx-right-arrow-alt'></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($sudah_selesai): ?>
    <div class="modal-overlay">
        <div class="modal-card">
            <i class='bx bxs-lock-alt' style="font-size: 60px; color: var(--danger); margin-bottom: 15px;"></i>
            <h2 style="font-weight: 800;">Akses Terkunci</h2>
            <p style="font-size: 14px; color: var(--text-light); margin-bottom: 20px;">Halo <b><?= $username ?></b>, Anda telah menyelesaikan ujian ini.</p>
            <div style="background: #f8faff; padding: 25px; border-radius: 20px; border: 1px solid #eee; margin-bottom: 25px;">
                <p style="font-size: 11px; font-weight: 800; letter-spacing: 1px;">HASIL AKHIR</p>
                <h1 style="font-size: 50px; color: var(--dark);"><?= round($data_nilai['skor']) ?></h1>
                <p style="color: var(--success); font-weight: 700; font-size: 14px;">Benar: <?= $data_nilai['benar'] ?></p>
            </div>
            <a href="login_user.php" class="btn-nav-action" style="background: var(--dark); color: white; text-decoration: none;">
                <i class='bx bx-log-out'></i> Keluar Aplikasi
            </a>
        </div>
    </div>
    <?php endif; ?>

    <header>
        <div class="logo-text">ELMS <span>MARI BELAJAR</span></div>
        <div class="user-pill">
            <span class="user-name"><?= $username ?></span>
            <img src="<?= $user_photo ?>">
        </div>
    </header>

    <div class="main-wrapper">
        <div class="exam-area">
            <?php if (!$sudah_selesai): ?>
                <div class="container">
                    <div id="soalWrapper"></div>
                    <div class="action-bar">
                        <button onclick="prevSoal()" class="btn-nav-action" style="background: #f1f4f9;"><i class='bx bx-chevron-left'></i> Back</button>
                        <button onclick="nextSoal()" id="btnNext" class="btn-nav-action" style="background: var(--dark); color: white;">Next <i class='bx bx-chevron-right'></i></button>
                        <button onclick="finishExam()" id="btnFinish" class="btn-nav-action" style="background: var(--success); color: white; display:none;"><i class='bx bx-send'></i> Selesai</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sidebar">
            <div class="timer-box">
                <p style="font-size: 10px; font-weight: 800; color: var(--text-light);">SISA WAKTU</p>
                <h2 id="timer">60:00</h2>
            </div>
            <div class="nav-grid" id="navGrid"></div>
            <div style="margin-top: 30px; text-align: center; border-top: 1px solid #f1f4f9; padding-top: 20px;">
                <p style="font-size: 10px; color: var(--text-light); font-weight: 700;">&copy; <?= date('Y') ?> ELMS SYSTEM</p>
            </div>
        </aside>
    </div>
    
    <script>
        const dataSoal = <?= json_encode($semua_soal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let jawabanUser = new Array(dataSoal.length).fill(null);
        let currentIndex = 0;
        let isStarted = false;

        function escapeHTML(text) {
            if (!text) return "";
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function mulaiUjian() {
            document.getElementById('modalGuide').style.display = 'none';
            isStarted = true;
        }

        function renderUjian() {
            if(dataSoal.length === 0) return;
            const wrapper = document.getElementById('soalWrapper');
            const nav = document.getElementById('navGrid');
            dataSoal.forEach((s, i) => {
                wrapper.innerHTML += `
                    <div class="soal-card ${i === 0 ? 'active' : ''}" id="card-${i}">
                        <p style="color:var(--p); font-weight:800; font-size:11px; margin-bottom:10px;">SOAL NOMOR ${i + 1}</p>
                        <div class="pertanyaan-text">${escapeHTML(s.pertanyaan)}</div>
                        <div style="margin-top:20px;">
                            <div class="opsi" id="opsi-${i}-A" onclick="pilihJawaban(${i}, 'A')">A. ${escapeHTML(s.pil_a)}</div>
                            <div class="opsi" id="opsi-${i}-B" onclick="pilihJawaban(${i}, 'B')">B. ${escapeHTML(s.pil_b)}</div>
                            <div class="opsi" id="opsi-${i}-C" onclick="pilihJawaban(${i}, 'C')">C. ${escapeHTML(s.pil_c)}</div>
                            <div class="opsi" id="opsi-${i}-D" onclick="pilihJawaban(${i}, 'D')">D. ${escapeHTML(s.pil_d)}</div>
                        </div>
                    </div>
                `;
                nav.innerHTML += `<div class="nav-btn" id="nav-${i}" onclick="jumpTo(${i})">${i + 1}</div>`;
            });
            updateNav();
        }

        function pilihJawaban(idx, p) {
            jawabanUser[idx] = p;
            const card = document.getElementById(`card-${idx}`);
            card.querySelectorAll('.opsi').forEach(o => o.classList.remove('selected'));
            document.getElementById(`opsi-${idx}-${p}`).classList.add('selected');
            updateNav();
        }

        function jumpTo(idx) {
            document.querySelectorAll('.soal-card').forEach(c => c.classList.remove('active'));
            currentIndex = idx;
            document.getElementById(`card-${idx}`).classList.add('active');
            updateNav();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateNav() {
            dataSoal.forEach((_, i) => {
                const n = document.getElementById(`nav-${i}`);
                if(!n) return;
                n.className = 'nav-btn';
                if(i === currentIndex) n.classList.add('current');
                if(jawabanUser[i]) n.classList.add('done');
            });
            document.getElementById('btnFinish').style.display = (currentIndex === dataSoal.length - 1) ? 'flex' : 'none';
            document.getElementById('btnNext').style.display = (currentIndex === dataSoal.length - 1) ? 'none' : 'flex';
        }

        function nextSoal() { if(currentIndex < dataSoal.length - 1) jumpTo(currentIndex + 1); }
        function prevSoal() { if(currentIndex > 0) jumpTo(currentIndex - 1); }

        async function finishExam() {
            // NOTIFIKASI SESUAI SISTEM ASLI
            if(!confirm("Kumpulkan jawaban sekarang? Ujian akan langsung dikunci.")) return;
            
            let benar = 0;
            dataSoal.forEach((s, i) => { if(jawabanUser[i] === s.jawaban_benar) benar++; });
            const skor = (benar / dataSoal.length) * 100;

            const fd = new FormData();
            fd.append('proses_simpan', '1');
            fd.append('skor', skor);
            fd.append('benar', benar);
            
            const res = await fetch(window.location.href, { method: 'POST', body: fd });
            if(res.ok) window.location.reload(); 
        }

        let t = 3600;
        setInterval(() => {
            if(!isStarted) return;
            let m = Math.floor(t/60), s = t%60;
            const timerEl = document.getElementById('timer');
            if(timerEl) timerEl.innerText = `${m}:${s<10?'0':''}${s}`;
            if(t-- <= 0) finishExam();
        }, 1000);

        window.onload = renderUjian;
    </script>
</body>
</html>