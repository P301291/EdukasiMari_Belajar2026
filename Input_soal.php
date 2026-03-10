<?php
include 'koneksi.php';
session_start(); // Pastikan session dimulai agar selaras dengan dashboard

$notifikasi = "";

// --- 1. LOGIKA HAPUS SEMUA SOAL ---
if (isset($_POST['konfirmasi_hapus_total'])) {
    $query_reset = "TRUNCATE TABLE ujian_soal"; 
    if (mysqli_query($conn, $query_reset)) {
        $notifikasi = "<div class='alert danger'><i class='bx bxs-trash-alt'></i> <strong>Sistem Dibersihkan:</strong> Seluruh data soal telah dihapus.</div>";
    } else {
        $notifikasi = "<div class='alert danger'>Gagal menghapus: " . mysqli_error($conn) . "</div>";
    }
}

// --- 2. LOGIKA SIMPAN SOAL ---
if (isset($_POST['simpan_soal'])) {
    $pertanyaan = mysqli_real_escape_string($conn, trim($_POST['pertanyaan']));
    $pil_a = mysqli_real_escape_string($conn, trim($_POST['pil_a']));
    $pil_b = mysqli_real_escape_string($conn, trim($_POST['pil_b']));
    $pil_c = mysqli_real_escape_string($conn, trim($_POST['pil_c']));
    $pil_d = mysqli_real_escape_string($conn, trim($_POST['pil_d']));
    $jawaban = $_POST['jawaban_benar'];

    if (empty($pertanyaan) || empty($pil_a) || empty($pil_b) || empty($pil_c) || empty($pil_d) || empty($jawaban)) {
        $notifikasi = "<div class='alert danger'><i class='bx bxs-error-circle'></i> <strong>Gagal:</strong> Mohon isi semua kolom.</div>";
    } else {
        $query = "INSERT INTO ujian_soal (pertanyaan, pil_a, pil_b, pil_c, pil_d, jawaban_benar) 
                  VALUES ('$pertanyaan', '$pil_a', '$pil_b', '$pil_c', '$pil_d', '$jawaban')";

        if (mysqli_query($conn, $query)) {
            $notifikasi = "<div class='alert success'><i class='bx bxs-check-shield'></i> <strong>Berhasil!</strong> Soal ditambahkan.</div>";
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
    <title>Input Soal | ELMS Mari Belajar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { 
            --primary: #4361ee; --sidebar-bg: #0b0f19;
            --body-bg: #f4f7fe; --text-color: #2b3674; --white: #ffffff; 
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--body-bg); color: var(--text-color); }

        /* Sidebar Selaras Dashboard */
        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: var(--sidebar-bg); transition: 0.4s; z-index: 1000; }
        .sidebar.collapsed { left: -260px; }
        .sidebar .logo { padding: 40px 25px; color: #fff; font-weight: 800; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar ul { list-style: none; padding: 20px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #a3aed0; text-decoration: none; border-radius: 14px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar ul li a.active { background: var(--primary); color: #fff; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }
        .sidebar ul li a:hover:not(.active) { background: rgba(255,255,255,0.1); color: #fff; }

        .main-content { margin-left: 260px; padding: 35px; transition: 0.4s; }
        .main-content.expanded { margin-left: 0; }

        /* Top Header Selaras Dashboard */
        .top-header { display: flex; justify-content: space-between; align-items: center; background: var(--white); padding: 15px 25px; border-radius: 20px; margin-bottom: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .card { background: var(--white); border-radius: 24px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 25px; }
        
        label { display: block; font-weight: 700; margin-bottom: 10px; font-size: 11px; color: #a3aed0; text-transform: uppercase; letter-spacing: 1px; }
        textarea, input, select { 
            width: 100%; padding: 14px; border-radius: 15px; border: 1.5px solid #e2e8f0; 
            margin-bottom: 20px; outline: none; transition: 0.3s; font-size: 14px;
        }
        textarea:focus, input:focus { border-color: var(--primary); }

        .grid-layout { display: grid; grid-template-columns: 1fr 300px; gap: 25px; }
        .grid-opsi { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn-submit { 
            background: var(--primary); color: white; border: none; padding: 16px; 
            border-radius: 16px; font-weight: 700; cursor: pointer; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2); }

        .guide-card { background: #f8faff; padding: 25px; border-radius: 24px; border: 1px solid #e2e8f0; }
        .guide-item { display: flex; gap: 12px; margin-bottom: 15px; font-size: 13px; color: #4a5568; line-height: 1.4; }
        .guide-item i { color: var(--primary); font-size: 16px; }

        .danger-zone { margin-top: 20px; padding: 20px; border-radius: 20px; border: 2px dashed #fee2e2; text-align: center; }
        .btn-danger { background: none; color: #ef4444; border: 1.5px solid #ef4444; padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 12px; }
        .btn-danger:hover { background: #ef4444; color: white; }

        .alert { padding: 18px; border-radius: 18px; margin-bottom: 25px; font-weight: 700; }
        .success { background: #dcfce7; color: #166534; }
        .danger { background: #fee2e2; color: #991b1b; }
        
        .badge-count { background: var(--primary); color: white; padding: 5px 12px; border-radius: 10px; font-size: 12px; }

        @media (max-width: 1100px) { .grid-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo">ELMS Mari Belajar</div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php" class="active"><i class='bx bxs-file-plusn'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php"><i class='bx bxs-bar-chart-alt-2'></i> Nilai Siswa</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #fb7185;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="main">
        <div class="top-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class='bx bx-category-alt' id="toggle-sidebar" style="font-size: 28px; cursor: pointer; color: var(--primary);"></i>
                <h1 style="font-size: 20px; font-weight: 800;">Manajemen Bank Soal</h1>
            </div>
            <div style="font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <span class="badge-count">Kapasitas: <?= $total_soal ?>/100</span>
                Admin System
            </div>
        </div>

        <?php echo $notifikasi; ?>

        <div class="grid-layout">
            <div class="main-form">
                <div class="card">
                    <h3 style="margin-bottom: 25px; font-weight: 800;"><i class='bx bx-plus-circle'></i> Tambah Soal Baru</h3>
                    <form method="POST" id="formSoal">
                        <label>Pertanyaan Soal</label>
                        <textarea name="pertanyaan" rows="3" placeholder="Tuliskan pertanyaan ujian di sini..." required></textarea>

                        <label>Opsi Jawaban</label>
                        <div class="grid-opsi">
                            <div><input type="text" name="pil_a" placeholder="Pilihan A" required></div>
                            <div><input type="text" name="pil_b" placeholder="Pilihan B" required></div>
                            <div><input type="text" name="pil_c" placeholder="Pilihan C" required></div>
                            <div><input type="text" name="pil_d" placeholder="Pilihan D" required></div>
                        </div>

                        <label>Kunci Jawaban Benar</label>
                        <select name="jawaban_benar" required>
                            <option value="">-- Tentukan Kunci Jawaban --</option>
                            <option value="A">Opsi A</option>
                            <option value="B">Opsi B</option>
                            <option value="C">Opsi C</option>
                            <option value="D">Opsi D</option>
                        </select>

                        <button type="submit" name="simpan_soal" class="btn-submit">
                            <i class='bx bxs-save'></i> Simpan ke Database
                        </button>
                    </form>
                </div>
            </div>

            <div class="side-info">
                <div class="guide-card">
                    <h4 style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class='bx bxs-info-circle'></i> Panduan
                    </h4>
                    <div class="guide-item">
                        <i class='bx bx-check-double'></i>
                        <span>Pastikan pertanyaan jelas dan tidak rancu.</span>
                    </div>
                    <div class="guide-item">
                        <i class='bx bx-check-double'></i>
                        <span>Input otomatis akan dibersihkan dari spasi berlebih.</span>
                    </div>
                    <div class="guide-item">
                        <i class='bx bx-check-double'></i>
                        <span>Periksa kembali kunci jawaban sebelum menyimpan.</span>
                    </div>

                    <div class="danger-zone">
                        <p style="font-size: 10px; font-weight: 800; color: #ef4444; margin-bottom: 10px;">MAINTENANCE DATABASE</p>
                        <form method="POST" onsubmit="return confirm('⚠️ PERINGATAN: Seluruh soal akan dihapus permanen. Lanjutkan?')">
                            <button type="submit" name="konfirmasi_hapus_total" class="btn-danger">
                                <i class='bx bxs-eraser'></i> Kosongkan Soal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer style="text-align: center; margin-top: 40px; color: #a3aed0; font-size: 12px; font-weight: 600;">
            &copy; 2026 ELMS Mari Belajar | Candra Argadinata, S.Kom.
        </footer>
    </main>

    <script>
        // Toggle Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main');
        const toggleBtn = document.getElementById('toggle-sidebar');

        toggleBtn.onclick = () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        };

        // Client Side Validation
        document.getElementById('formSoal').onsubmit = function() {
            let textarea = this.querySelector('textarea[name="pertanyaan"]');
            if (textarea.value.trim().length === 0) {
                alert("Pertanyaan tidak boleh kosong!");
                return false;
            }
            return true;
        };
    </script>
</body>
</html>