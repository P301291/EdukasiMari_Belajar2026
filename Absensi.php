<?php
session_start();

// --- 1. KONEKSI & KONFIGURASI ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { die("Koneksi gagal: " . mysqli_connect_error()); }

if (!isset($_SESSION['username'])) { header("location:login.php"); exit(); }

// --- 2. LOGIKA ABSENSI & HAPUS ---
$notifikasi = "";

// A. Proses Absen (Input Kehadiran)
if (isset($_POST['submit_absen'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $mapel = mysqli_real_escape_string($conn, $_POST['mapel']);
    $waktu = date('H:i:s');
    $tanggal = date('Y-m-d');

    $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE nama='$nama' AND tanggal='$tanggal' AND mapel='$mapel'");
    if (mysqli_num_rows($cek) > 0) {
        $notifikasi = "<div class='alert danger'><i class='bx bx-error-circle'></i> Siswa ini sudah absen untuk mapel tersebut hari ini!</div>";
    } else {
        $query = "INSERT INTO absensi (nama, kelas, mapel, waktu_absen, tanggal, status) 
                  VALUES ('$nama', '$kelas', '$mapel', '$waktu', '$tanggal', 'Hadir')";
        if (mysqli_query($conn, $query)) {
            $notifikasi = "<div class='alert success'><i class='bx bx-check-double'></i> Absensi berhasil disimpan!</div>";
        }
    }
}

// B. Proses Hapus Satuan
if (isset($_GET['hapus_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_id']);
    mysqli_query($conn, "DELETE FROM absensi WHERE id='$id'");
    header("Location: absensi.php");
    exit();
}

// C. Proses Hapus Semua
if (isset($_POST['hapus_semua'])) {
    mysqli_query($conn, "DELETE FROM absensi");
    $notifikasi = "<div class='alert success'><i class='bx bx-trash'></i> Semua riwayat absensi telah dibersihkan!</div>";
}

// D. Proses Hapus Terpilih (Bulk Delete)
if (isset($_POST['hapus_pilihan']) && !empty($_POST['pilih_id'])) {
    $ids = implode(",", array_map('intval', $_POST['pilih_id']));
    if (mysqli_query($conn, "DELETE FROM absensi WHERE id IN ($ids)")) {
        $notifikasi = "<div class='alert success'><i class='bx bx-check-double'></i> Data terpilih berhasil dihapus.</div>";
    }
}

// --- 3. KONFIGURASI PAGINATION (5 DATA PER HALAMAN) ---
$limit = 4; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

$total_data_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi");
$total_data = mysqli_fetch_assoc($total_data_query)['total'];
$total_halaman = ceil($total_data / $limit);

$data_absen = mysqli_query($conn, "SELECT * FROM absensi ORDER BY id DESC LIMIT $offset, $limit");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi | ELMS Mari Belajar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #4361ee; --sidebar-bg: #0b0f19;
            --body-bg: #f4f7fe; --text-color: #2b3674; --white: #ffffff; --danger: #ff4d4d;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--body-bg); color: var(--text-color); }

        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: var(--sidebar-bg); z-index: 1000; }
        .sidebar .logo { padding: 40px 25px; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar ul { list-style: none; padding: 20px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #a3aed0; text-decoration: none; border-radius: 14px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar ul li a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }

        .main-content { margin-left: 260px; padding: 35px; }
        .top-header { display: flex; justify-content: space-between; align-items: center; background: var(--white); padding: 15px 25px; border-radius: 20px; margin-bottom: 35px; }
        .card { background: var(--white); border-radius: 24px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.03); margin-bottom: 25px; }

        input, select { width: 100%; padding: 14px; border-radius: 15px; border: 1.5px solid #e2e8f0; outline: none; margin-top: 8px; }
        .btn { padding: 10px 20px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-p { background: var(--primary); color: white; width: 100%; margin-top: 20px; justify-content: center; }
        .btn-danger { background: var(--danger); color: white; text-decoration: none; padding: 8px 10px; border-radius: 8px; font-size: 16px; }
        .btn-outline-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 13px; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        td, th { padding: 15px; text-align: left; }
        td { background: #f8fafc; border-radius: 10px; font-size: 14px; }
        
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination a { padding: 8px 14px; background: white; border-radius: 10px; text-decoration: none; color: var(--text-color); border: 1px solid #e2e8f0; font-weight: 600; }
        .pagination a.active { background: var(--primary); color: white; border-color: var(--primary); }

        .alert { padding: 18px; border-radius: 18px; margin-bottom: 25px; font-weight: 700; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">ELMS Mari Belajar</div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php"><i class='bx bxs-file-plus'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php"><i class='bx bxs-bar-chart-alt-2'></i> Nilai Siswa</a></li>
            <li><a href="absensi.php" class="active"><i class='bx bxs-spreadsheet'></i> Absensi</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #fb7185;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <h1 style="font-size: 20px; font-weight: 800;">Manajemen Absensi</h1>
            <div style="font-size: 14px; font-weight: 700; color: var(--primary);"><i class='bx bx-calendar'></i> <?= date('d F Y') ?></div>
        </div>

        <?php echo $notifikasi; ?>

        <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px;">
            <div class="card" style="height: fit-content;">
                <h3 style="margin-bottom: 20px;"><i class='bx bxs-edit'></i> Form Hadir</h3>
                <form method="POST">
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">NAMA LENGKAP SISWA</label>
                    <input type="text" name="nama" placeholder="Ketik nama siswa..." required>
                    
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0; display:block; margin-top:15px;">KELAS</label>
                    <select name="kelas" required>
                        <option value="">- Pilih Kelas -</option>
                        <option value="Kelas 1">Kelas 1</option>
                        <option value="Kelas 2">Kelas 2</option>
                        <option value="Kelas 3">Kleas 3</option>
                    </select>

                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0; display:block; margin-top:15px;">MATA PELAJARAN</label>
                    <input type="text" name="mapel" placeholder="Contoh: Pemrograman Web" required>
                    
                    <button type="submit" name="submit_absen" class="btn btn-p">
                        <i class='bx bxs-paper-plane'></i> Simpan Absensi
                    </button>
                </form>
            </div>

            <div class="card">
                <form method="POST">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class='bx bx-list-check'></i> Riwayat Absensi</h3>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="hapus_pilihan" class="btn btn-outline-danger" onclick="return confirm('Hapus data yang dicentang?')">
                                <i class='bx bx-check-square'></i> Hapus Terpilih
                            </button>
                            <button type="submit" name="hapus_semua" class="btn btn-outline-danger" style="background: var(--danger); color: white;" onclick="return confirm('Kosongkan semua data riwayat?')">
                                <i class='bx bx-trash'></i> Hapus Semua
                            </button>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr style="font-size: 11px; color: #a3aed0; text-transform: uppercase; letter-spacing: 1px;">
                                    <th width="40"><input type="checkbox" id="selectAll"></th>
                                    <th>Siswa</th>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Waktu</th>
                                    <th width="50">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($data_absen) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($data_absen)) { ?>
                                    <tr>
                                        <td><input type="checkbox" name="pilih_id[]" value="<?= $row['id'] ?>" class="checkItem"></td>
                                        <td style="font-weight: 700; color: var(--text-color);"><?= $row['nama'] ?></td>
                                        <td><?= $row['kelas'] ?></td>
                                        <td><?= $row['mapel'] ?></td>
                                        <td><span style="font-weight: 800; color: var(--primary);"><?= $row['waktu_absen'] ?></span></td>
                                        <td>
                                            <a href="absensi.php?hapus_id=<?= $row['id'] ?>" 
                                               class="btn-danger" 
                                               onclick="return confirm('Hapus data <?= $row['nama'] ?>?')">
                                                <i class='bx bx-x'></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #a3aed0;">Belum ada riwayat absensi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <?php if($total_halaman > 1): ?>
                <div class="pagination">
                    <?php for($i=1; $i<=$total_halaman; $i++): ?>
                        <a href="?halaman=<?= $i ?>" class="<?= ($i == $halaman) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Fitur Check All
        document.getElementById('selectAll').onclick = function() {
            var checkboxes = document.querySelectorAll('.checkItem');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        }
    </script>
</body>
</html>