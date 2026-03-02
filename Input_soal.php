<?php
include 'koneksi.php';

$notifikasi = "";

// --- 1. LOGIKA HAPUS SEMUA SOAL ---
if (isset($_POST['konfirmasi_hapus_total'])) {
    $query_reset = "TRUNCATE TABLE ujian_soal"; 
    if (mysqli_query($conn, $query_reset)) {
        $notifikasi = "<div class='alert danger'><i class='bx bxs-trash-alt'></i> <strong>Sistem Dibersihkan:</strong> Seluruh data soal telah berhasil dihapus secara permanen.</div>";
    } else {
        $notifikasi = "<div class='alert danger'>Gagal menghapus: " . mysqli_error($conn) . "</div>";
    }
}


// --- 2. LOGIKA SIMPAN SOAL (DIPERBAIKI) ---
if (isset($_POST['simpan_soal'])) {
    // Menggunakan trim() untuk memastikan tidak ada spasi kosong yang tersimpan
    // trim() juga akan membersihkan input jika user tidak sengaja memasukkan spasi saja
    $pertanyaan = mysqli_real_escape_string($conn, trim($_POST['pertanyaan']));
    $pil_a = mysqli_real_escape_string($conn, trim($_POST['pil_a']));
    $pil_b = mysqli_real_escape_string($conn, trim($_POST['pil_b']));
    $pil_c = mysqli_real_escape_string($conn, trim($_POST['pil_c']));
    $pil_d = mysqli_real_escape_string($conn, trim($_POST['pil_d']));
    $jawaban = $_POST['jawaban_benar'];

    // Validasi tambahan: Pastikan setelah di-trim data tidak kosong
    if (empty($pertanyaan) || empty($pil_a) || empty($pil_b) || empty($pil_c) || empty($pil_d) || empty($jawaban)) {
        $notifikasi = "<div class='alert danger'><i class='bx bxs-error-circle'></i> <strong>Gagal:</strong> Mohon isi semua kolom dengan benar. Input tidak boleh hanya berisi spasi.</div>";
    } else {
        $query = "INSERT INTO ujian_soal (pertanyaan, pil_a, pil_b, pil_c, pil_d, jawaban_benar) 
                  VALUES ('$pertanyaan', '$pil_a', '$pil_b', '$pil_c', '$pil_d', '$jawaban')";

        if (mysqli_query($conn, $query)) {
            $notifikasi = "<div class='alert success'><i class='bx bxs-check-shield'></i> <strong>Berhasil!</strong> Soal baru telah ditambahkan ke bank soal.</div>";
        } else {
            $notifikasi = "<div class='alert danger'>Gagal simpan: " . mysqli_error($conn) . "</div>";
        }
    }
}

$res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian_soal");
$total_soal = mysqli_fetch_assoc($res_count)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Soal | ELMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary: #4361ee; --bg: #f4f7fe; --text: #2b3674; --danger: #ff4757; --accent: #ff9f43; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); padding: 30px; }
        
        .layout-wrapper { display: grid; grid-template-columns: 1fr 320px; gap: 30px; max-width: 1200px; margin: auto; }
        
        .page-header { grid-column: span 2; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { font-weight: 800; font-size: 24px; }
        
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid rgba(0,0,0,0.01); }
        
        label { display: block; font-weight: 700; margin-bottom: 10px; font-size: 13px; color: #a3aed0; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* CSS Perbaikan Textarea: Menghilangkan spasi default */
        textarea, input, select { 
            width: 100%; padding: 14px; border-radius: 14px; border: 1.5px solid #e2e8f0; 
            margin-bottom: 20px; outline: none; transition: 0.3s; font-family: inherit;
        }
        textarea:focus, input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
        
        .grid-opsi { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-submit { 
            background: var(--primary); color: white; border: none; padding: 18px; 
            border-radius: 16px; font-weight: 700; cursor: pointer; width: 100%; font-size: 15px;
            display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2); }

        .sidebar-guide { position: sticky; top: 30px; height: fit-content; }
        .guide-card { background: #fff; padding: 25px; border-radius: 24px; border: 1px solid #e2e8f0; }
        .guide-title { display: flex; align-items: center; gap: 10px; font-weight: 800; color: var(--primary); margin-bottom: 15px; font-size: 15px; }
        .guide-list { list-style: none; }
        .guide-item { display: flex; gap: 12px; margin-bottom: 18px; font-size: 13px; line-height: 1.5; color: #4a5568; }
        .guide-item i { color: var(--accent); font-size: 18px; margin-top: 2px; }
        .guide-item b { color: var(--text); }

        .danger-zone { background: #fff5f5; border: 2px dashed #feb2b2; padding: 20px; border-radius: 20px; text-align: center; }
        .btn-danger { background: none; color: var(--danger); border: 1.5px solid var(--danger); padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 13px; }
        .btn-danger:hover { background: var(--danger); color: white; }

        .alert { padding: 18px; border-radius: 16px; margin-bottom: 25px; font-weight: 600; animation: fadeIn 0.4s; }
        .success { background: #dcfce7; color: #166534; border-left: 5px solid #22c55e; }
        .danger { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .badge { background: var(--primary); color: white; padding: 6px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 992px) {
            .layout-wrapper { grid-template-columns: 1fr; }
            .sidebar-guide { position: static; }
        }
    </style>
</head>
<body>

<div class="layout-wrapper">
    <div class="page-header">
        <div>
            <h2><i class='bx bxs-layer-plus'></i> Input Soal Ujian</h2>
            <p style="color: #a3aed0; font-size: 14px; margin-top: 5px;">Sistem Manajemen ELMS v3.0</p>
        </div>
        <span class="badge">Total: <?= $total_soal ?> / 100 Soal</span>
    </div>

    <div class="main-content">
        <?= $notifikasi ?>

        <div class="card">
            <form method="POST" id="formSoal">
                <label>Pertanyaan Soal</label>
                <textarea name="pertanyaan" rows="4" placeholder="Contoh: Apa ibukota dari negara Indonesia?" required></textarea>

                <label>Pilihan Jawaban (Multiple Choice)</label>
                <div class="grid-opsi">
                    <div><input type="text" name="pil_a" placeholder="Opsi A" required></div>
                    <div><input type="text" name="pil_b" placeholder="Opsi B" required></div>
                    <div><input type="text" name="pil_c" placeholder="Opsi C" required></div>
                    <div><input type="text" name="pil_d" placeholder="Opsi D" required></div>
                </div>

                <label>Kunci Jawaban</label>
                <select name="jawaban_benar" required>
                    <option value="">-- Pilih Jawaban Benar --</option>
                    <option value="A">Opsi A</option>
                    <option value="B">Opsi B</option>
                    <option value="C">Opsi C</option>
                    <option value="D">Opsi D</option>
                </select>

                <button type="submit" name="simpan_soal" class="btn-submit">
                    <i class='bx bxs-save'></i> Simpan ke Bank Soal
                </button>
            </form>
        </div>

        <div class="danger-zone">
            <p style="font-size: 12px; font-weight: 800; color: #c53030; margin-bottom: 10px;">ZONA PEMBERSIHAN DATA</p>
            <form method="POST" onsubmit="return confirm('⚠️ Hapus SEMUA soal permanen?')">
                <button type="submit" name="konfirmasi_hapus_total" class="btn-danger">
                    <i class='bx bxs-trash'></i> Kosongkan Database Soal
                </button>
            </form>
        </div>
        
        <a href="Dashboard.php" style="display:block; text-align:center; color:#a3aed0; text-decoration:none; font-size:13px; font-weight:700; margin-top: 25px;">
            <i class='bx bx-arrow-back'></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="sidebar-guide">
        <div class="guide-card">
            <div class="guide-title">
                <i class='bx bxs-info-circle'></i> Petunjuk Pengisian
            </div>
            
            <ul class="guide-list">
                <li class="guide-item">
                    <i class='bx bxs-edit'></i>
                    <span><b>Isi Pertanyaan:</b> Gunakan kalimat tanya yang jelas dan tidak bermakna ganda.</span>
                </li>
                <li class="guide-item">
                    <i class='bx bxs-grid-alt'></i>
                    <span><b>Pilihan Ganda:</b> Pastikan semua opsi (A, B, C, D) terisi dengan jawaban yang berbeda.</span>
                </li>
                <li class="guide-item">
                    <i class='bx bxs-key'></i>
                    <span><b>Kunci Jawaban:</b> Pilih opsi yang benar sesuai dengan data yang diinput di atasnya.</span>
                </li>
                <li class="guide-item">
                    <i class='bx bxs-check-circle'></i>
                    <span><b>Verifikasi:</b> Tekan tombol "Simpan" dan pastikan muncul notifikasi sukses berwarna hijau.</span>
                </li>
                <li class="guide-item">
                    <i class='bx bxs-lock'></i>
                    <span><b>Keamanan:</b> Jangan menggunakan simbol khusus yang berlebihan (seperti kutip dua ganda) jika tidak perlu.</span>
                </li>
            </ul>

            <div style="background: #f8faff; padding: 15px; border-radius: 15px; border: 1px dashed #cbd5e0; margin-top: 10px;">
                <p style="font-size: 11px; color: #718096; font-weight: 700; text-align: center;">
                    Tips: Gunakan <b>CTRL + Enter</b> untuk memindahkan fokus antar kolom dengan cepat.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Validasi Sisi Klien untuk memastikan tidak ada spasi kosong yang dikirim
    document.getElementById('formSoal').onsubmit = function() {
        let textarea = this.querySelector('textarea[name="pertanyaan"]');
        if (textarea.value.trim().length === 0) {
            alert("Pertanyaan tidak boleh kosong atau hanya berisi spasi!");
            return false;
        }
        return true;
    };
</script>

</body>
</html>