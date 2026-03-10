<?php
session_start();

// --- 1. KONEKSI & KONFIGURASI ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { die("Koneksi gagal: " . mysqli_connect_error()); }

if (!isset($_SESSION['username'])) { header("location:login.php"); exit(); }

// --- 2. LOGIKA CLOCK & OPERASIONAL ---
date_default_timezone_set('Asia/Jakarta');
$jam_sekarang = (int)date('H');
// Pendaftaran dibuka jam 06:00 sampai 17:59 (Tutup jam 18:00)
$is_open = ($jam_sekarang >= 6 && $jam_sekarang < 18); 

// --- 3. LOGIKA CRUD SISWA ELMS ---
$notifikasi = "";

// A. Tambah Siswa Baru
if (isset($_POST['tambah_siswa'])) {
    if ($is_open) {
        $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        
        if (strpos($email, '@gmail.com') !== false) {
            $query = "INSERT INTO siswa_elms (nisn, nama, email, kelas, status_elms) 
                      VALUES ('$nisn', '$nama', '$email', '$kelas', 'Pending')";
            if(mysqli_query($conn, $query)) {
                header("Location: Data_Siswa.php?status=added"); exit();
            }
        } else {
            $notifikasi = "<div class='alert danger'><i class='bx bx-error-circle'></i> Registrasi gagal! Gunakan alamat Gmail.</div>";
        }
    } else {
        $notifikasi = "<div class='alert danger'><i class='bx bx-time-five'></i> Maaf, pendaftaran sudah ditutup (18:00 - 06:00 WIB).</div>";
    }
}

// B. Hapus Satu Siswa
if (isset($_GET['del_id'])) {
    $id = intval($_GET['del_id']);
    mysqli_query($conn, "DELETE FROM siswa_elms WHERE id = $id");
    header("Location: Data_Siswa.php?status=deleted"); exit();
}

// C. Hapus Massal
if (isset($_POST['btn_mass_delete'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $all_ids = implode(",", $ids);
        mysqli_query($conn, "DELETE FROM siswa_elms WHERE id IN ($all_ids)");
        $notifikasi = "<div class='alert danger'><i class='bx bxs-trash'></i> Data massal berhasil dihapus!</div>";
    }
}

// D. Update Data Siswa
if (isset($_POST['update_siswa'])) {
    $id = intval($_POST['id_edit']);
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn_edit']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_edit']);
    $status = mysqli_real_escape_string($conn, $_POST['status_edit']);
    
    mysqli_query($conn, "UPDATE siswa_elms SET nisn='$nisn', nama='$nama', status_elms='$status' WHERE id=$id");
    $notifikasi = "<div class='alert info'><i class='bx bx-refresh'></i> Profil siswa berhasil diperbarui!</div>";
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') $notifikasi = "<div class='alert danger'>Data pendaftar berhasil dihapus!</div>";
    if ($_GET['status'] == 'added') $notifikasi = "<div class='alert success'>Siswa berhasil terdaftar di program ELMS!</div>";
}

// --- 4. LOGIKA PERHITUNGAN JUMLAH PER KELAS ---
$count_per_kelas = [];
$res_kelas = mysqli_query($conn, "SELECT kelas, COUNT(*) as jumlah FROM siswa_elms GROUP BY kelas");
while($row_k = mysqli_fetch_assoc($res_kelas)) {
    $count_per_kelas[$row_k['kelas']] = $row_k['jumlah'];
}
$list_semua_kelas = ['VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'Kuliah'];

// --- 5. DATA, PAGINATION & ALL PAGE ---
$view_all = isset($_GET['view']) && $_GET['view'] == 'all';
$limit = 3; 
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$total_query = mysqli_query($conn, "SELECT COUNT(*) as t FROM siswa_elms");
$total_data = mysqli_fetch_assoc($total_query)['t'];
$pages = ceil($total_data / $limit);

$sql = $view_all ? "SELECT * FROM siswa_elms ORDER BY id DESC" : "SELECT * FROM siswa_elms ORDER BY id DESC LIMIT $start, $limit";
$data_siswa = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELMS Management | Data Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #4361ee; --sidebar-bg: #0b0f19;
            --body-bg: #f4f7fe; --text-color: #2b3674; --white: #ffffff; --accent: #00d1ff;
            --danger: #ef4444; --success: #22c55e;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--body-bg); color: var(--text-color); }

        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: var(--sidebar-bg); transition: 0.4s; z-index: 1000; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
        .sidebar.collapsed { left: -260px; }
        .sidebar .logo { padding: 40px 25px; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar ul { list-style: none; padding: 20px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #a3aed0; text-decoration: none; border-radius: 14px; transition: 0.3s; margin-bottom: 5px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar ul li a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }

        .main-content { margin-left: 260px; padding: 35px; transition: 0.4s; }
        .main-content.expanded { margin-left: 0; }

        .top-header { display: flex; justify-content: space-between; align-items: center; background: var(--white); padding: 15px 25px; border-radius: 20px; margin-bottom: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .card { background: var(--white); border-radius: 24px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid rgba(0,0,0,0.01); }
        
        .grid-kelas { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 15px; margin-top: 15px; }
        .item-kelas { background: #f8fafc; padding: 15px; border-radius: 16px; text-align: center; border: 1px solid #e2e8f0; }
        .item-kelas h4 { font-size: 11px; color: #a3aed0; margin-bottom: 5px; }
        .item-kelas p { font-size: 18px; font-weight: 800; color: var(--primary); }

        input, select { width: 100%; padding: 14px; border-radius: 15px; border: 1.5px solid #e2e8f0; outline: none; transition: 0.3s; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        td, th { padding: 16px 15px; text-align: left; }
        td { background: #fff; border-top: 1.5px solid #f8fafc; border-bottom: 1.5px solid #f8fafc; font-size: 14px; font-weight: 600; }
        td:first-child { border-radius: 15px 0 0 15px; }
        td:last-child { border-radius: 0 15px 15px 0; }
        
        .badge { padding: 6px 12px; border-radius: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }

        .btn { padding: 12px 24px; border-radius: 14px; border: none; cursor: pointer; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-p { background: var(--primary); color: white; }
        .btn-d { background: #fee2e2; color: #ef4444; }
        .alert { padding: 18px; border-radius: 18px; margin-bottom: 25px; font-weight: 700; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.danger { background: #fee2e2; color: #991b1b; }
        .alert.info { background: #e0e7ff; color: #4361ee; }

        .modal { display:none; position:fixed; inset:0; background:rgba(11, 15, 25, 0.85); z-index:2000; align-items:center; justify-content:center; backdrop-filter: blur(8px); }
        .modal-box { background: white; padding: 40px; border-radius: 28px; width: 100%; max-width: 450px; }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo"><i class='bx bxs-graduation' style="color: var(--accent);"></i>ELMS Mari Belajar</div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php" class="active"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php"><i class='bx bxs-file-plus'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php"><i class='bx bxs-bar-chart-alt-2'></i> Nilai Siswa</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #fb7185;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="main">
        <div class="top-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class='bx bx-category-alt' id="toggle-sidebar" style="font-size: 28px; cursor: pointer; color: var(--primary);"></i>
                <h1 style="font-size: 20px; font-weight: 800;">Manajemen Pendaftar ELMS</h1>
            </div>
            <div id="realtime-clock" style="font-weight: 700; color: var(--primary); background: #e0e7ff; padding: 8px 15px; border-radius: 12px;">
                Waktu: --:--:-- WIB
            </div>
        </div>

        <?php echo $notifikasi; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0e7ff; color: #4361ee;"><i class='bx bxs-user-plus'></i></div>
                <div><p style="color: #a3aed0; font-size: 12px; font-weight: 700;">TOTAL PENDAFTAR</p><h3><?= $total_data ?> Siswa</h3></div>
            </div>
            
            <div class="stat-card">
                <?php if ($is_open): ?>
                    <div class="stat-icon" style="background: #dcfce7; color: var(--success);"><i class='bx bxs-check-circle'></i></div>
                    <div>
                        <p style="color: #a3aed0; font-size: 12px; font-weight: 700;">PROGRAM STATUS</p>
                        <h3 style="color: var(--success);">Pendaftaran Dibuka</h3>
                    </div>
                <?php else: ?>
                    <div class="stat-icon" style="background: #fee2e2; color: var(--danger);"><i class='bx bxs-x-circle'></i></div>
                    <div>
                        <p style="color: #a3aed0; font-size: 12px; font-weight: 700;">PROGRAM STATUS</p>
                        <h3 style="color: var(--danger);">Pendaftaran Ditutup</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 10px;"><i class='bx bxs-bar-chart-alt-2'></i> Distribusi Peserta</h3>
            <div class="grid-kelas">
                <?php foreach($list_semua_kelas as $kls): ?>
                <div class="item-kelas">
                    <h4><?= strtoupper($kls) ?></h4>
                    <p><?= isset($count_per_kelas[$kls]) ? $count_per_kelas[$kls] : 0 ?> <span style="font-size: 10px; font-weight: 600;">Org</span></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($is_open): ?>
        <div class="card">
            <h3 style="margin-bottom: 20px;"><i class='bx bxs-edit'></i> Form Registrasi Program</h3>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">NISN</label>
                    <input type="text" name="nisn" placeholder="Nomor Induk Siswa" required>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">NAMA LENGKAP</label>
                    <input type="text" name="nama" placeholder="Nama sesuai ijazah" required>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">EMAIL GMAIL</label>
                    <input type="email" name="email" placeholder="siswa@gmail.com" required pattern=".+@gmail\.com">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">KELAS / JENJANG</label>
                    <select name="kelas" required>
                        <option value="">Pilih Jenjang</option>
                        <option value="VII">Kelas VII</option><option value="VIII">Kelas VIII</option><option value="IX">Kelas IX</option>
                        <option value="X">Kelas X</option><option value="XI">Kelas XI</option><option value="XII">Kelas XII</option>
                        <option value="Kuliah">Kuliah</option>
                    </select>
                </div>
                <button type="submit" name="tambah_siswa" class="btn btn-p" style="justify-content:center; height: 48px;">Daftarkan Siswa</button>
            </form>
        </div>
        <?php else: ?>
        <div class="card" style="border: 2px dashed #ef4444; background: #fff8f8; text-align: center; padding: 50px;">
            <i class='bx bx-time-five' style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
            <h2 style="color: #ef4444; font-weight: 800;">Pendaftaran Sedang Ditutup</h2>
            <p style="color: #a3aed0; font-weight: 600;">Sistem pendaftaran otomatis tutup pada pukul 18:00 WIB dan buka kembali pukul 06:00 WIB.</p>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
                    <input type="text" id="liveSearch" placeholder="Cari NISN atau Nama..." onkeyup="searchTable()" style="width: 250px;">
                    <button type="submit" name="btn_mass_delete" class="btn btn-d" onclick="return confirm('Hapus siswa terpilih?')"><i class='bx bxs-trash-alt'></i> Hapus Terpilih</button>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr style="color: #a3aed0; font-size: 11px; text-transform: uppercase;">
                                <th width="40"><input type="checkbox" id="checkAll"></th>
                                <th>Identitas Siswa</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="siswaTableBody">
                            <?php while($row = mysqli_fetch_assoc($data_siswa)) { 
                                $badgeClass = ($row['status_elms'] == 'Aktif') ? 'badge-active' : 'badge-pending';
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_ids[]" value="<?= $row['id'] ?>"></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['nama']) ?>&background=random&color=fff&bold=true" style="width: 38px; height: 38px; border-radius: 10px;">
                                        <div>
                                            <div style="font-weight: 700;"><?= $row['nama'] ?></div>
                                            <div style="font-size: 11px; color: #a3aed0;">NISN: <?= $row['nisn'] ?> | <?= $row['email'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $row['kelas'] ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= $row['status_elms'] ?></span></td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn" style="background: #fffbeb; color: #d97706; padding: 10px;" 
                                            onclick="openEdit('<?= $row['id'] ?>', '<?= $row['nisn'] ?>', '<?= $row['nama'] ?>', '<?= $row['status_elms'] ?>')">
                                        <i class='bx bxs-pencil'></i>
                                    </button>
                                    <a href="?del_id=<?= $row['id'] ?>" class="btn btn-d" style="padding: 10px;" onclick="return confirm('Hapus?')">
                                        <i class='bx bxs-trash'></i>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 30px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
                    <?php if (!$view_all): ?>
                        <div style="display: flex; gap: 8px;">
                            <?php for($i=1; $i<=$pages; $i++): ?>
                                <a href="?p=<?= $i ?>" style="text-decoration:none; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid #e2e8f0; background:<?= ($page==$i)?'var(--primary)':'white' ?>; color:<?= ($page==$i)?'white':'var(--text-color)' ?>; font-weight:700;"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                        <a href="?view=all" class="btn" style="background: #e0e7ff; color: var(--primary); width: 100%; justify-content: center;">Tampilkan Semua Pendaftar (<?= $total_data ?>)</a>
                    <?php else: ?>
                        <a href="Data_Siswa.php" class="btn" style="background: #f1f5f9; color: #64748b; width: 100%; justify-content: center;">Kembali ke Halaman Berurut</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>

    <div id="editModal" class="modal">
        <div class="modal-box">
            <h3 style="margin-bottom: 25px; font-weight: 800;">Update Profil Siswa</h3>
            <form method="POST">
                <input type="hidden" name="id_edit" id="id_e">
                <div style="margin-bottom: 15px;">
                    <label style="font-size:11px; font-weight:800; color: #a3aed0;">NISN</label>
                    <input type="text" name="nisn_edit" id="nisn_e" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="font-size:11px; font-weight:800; color: #a3aed0;">NAMA LENGKAP</label>
                    <input type="text" name="nama_edit" id="nama_e" required>
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size:11px; font-weight:800; color: #a3aed0;">STATUS PROGRAM</label>
                    <select name="status_edit" id="status_e">
                        <option value="Pending">Pending</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_siswa" class="btn btn-p" style="flex:1; justify-content:center;">Simpan</button>
                    <button type="button" onclick="closeEdit()" class="btn" style="flex:1; background:#f1f5f9; justify-content:center; color: #64748b;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Fungsi Jam Real-Time ---
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const clockElement = document.getElementById('realtime-clock');
            if (clockElement) {
                clockElement.innerHTML = `Waktu: ${hours}:${minutes}:${seconds} WIB`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock(); // Panggil langsung agar tidak delay

        // --- Sidebar Toggle ---
        document.getElementById('toggle-sidebar').onclick = () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('main').classList.toggle('expanded');
        }

        // --- Live Search ---
        function searchTable() {
            let filter = document.getElementById("liveSearch").value.toUpperCase();
            let tr = document.getElementById("siswaTableBody").getElementsByTagName("tr");
            for (let i = 0; i < tr.length; i++) {
                let text = tr[i].textContent.toUpperCase();
                tr[i].style.display = text.indexOf(filter) > -1 ? "" : "none";
            }
        }

        // --- Checkbox All ---
        document.getElementById('checkAll').onclick = function() {
            let boxes = document.getElementsByName('selected_ids[]');
            for (let box of boxes) { box.checked = this.checked; }
        }

        // --- Modal Logic ---
        function openEdit(id, nisn, nama, status) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('id_e').value = id;
            document.getElementById('nisn_e').value = nisn;
            document.getElementById('nama_e').value = nama;
            document.getElementById('status_e').value = status;
        }
        function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
    </script>
</body>
</html>