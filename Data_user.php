<?php
session_start();

// --- 1. KONEKSI & KONFIGURASI ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "database1"; 
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { die("Koneksi gagal: " . mysqli_connect_error()); }

if (!isset($_SESSION['username'])) { header("location:login.php"); exit(); }

// --- 2. LOGIKA CRUD ---
$notifikasi = "";

// LOGIKA TAMBAH USER
if (isset($_POST['tambah_user'])) {
    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $p = mysqli_real_escape_string($conn, $_POST['password']);
    
    $cek = mysqli_query($conn, "SELECT * FROM user WHERE username = '$u'");
    if (mysqli_num_rows($cek) > 0) {
        $notifikasi = "<div class='alert danger'><i class='bx bx-error-circle'></i> Username sudah ada!</div>";
    } else {
        mysqli_query($conn, "INSERT INTO user (username, password) VALUES ('$u', '$p')");
        header("Location: Data_user.php?status=added"); exit();
    }
}

// Fitur Hapus Satuan
if (isset($_GET['del_id'])) {
    $id = intval($_GET['del_id']);
    mysqli_query($conn, "DELETE FROM user WHERE id = $id");
    header("Location: Data_user.php?status=deleted"); exit();
}

// Fitur Mass Delete
if (isset($_POST['btn_mass_delete'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $all_ids = implode(",", $ids);
        mysqli_query($conn, "DELETE FROM user WHERE id IN ($all_ids)");
        $notifikasi = "<div class='alert danger'><i class='bx bxs-trash'></i> Data massal dimusnahkan!</div>";
    }
}

// Fitur Update User
if (isset($_POST['update_user'])) {
    $id = intval($_POST['id_edit']);
    $u = mysqli_real_escape_string($conn, $_POST['username_edit']);
    $p = mysqli_real_escape_string($conn, $_POST['password_edit']);
    mysqli_query($conn, "UPDATE user SET username='$u', password='$p' WHERE id=$id");
    $notifikasi = "<div class='alert info'><i class='bx bx-refresh'></i> Data berhasil diperbarui!</div>";
}

// Fitur Notifikasi Status
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') $notifikasi = "<div class='alert danger'>Data berhasil dihapus dari sistem!</div>";
    if ($_GET['status'] == 'added') $notifikasi = "<div class='alert success'>Admin baru berhasil didaftarkan!</div>";
}

// --- 3. PAGINATION ---
$view_all = isset($_GET['view']) && $_GET['view'] == 'all';
$limit = 5; 
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$total_query = mysqli_query($conn, "SELECT COUNT(*) as t FROM user");
$total_data = mysqli_fetch_assoc($total_query)['t'];
$pages = ceil($total_data / $limit);
$sql = $view_all ? "SELECT * FROM user ORDER BY id DESC" : "SELECT * FROM user ORDER BY id DESC LIMIT $start, $limit";
$data_admin = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core v3.0 | Ultra Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #4361ee; --sidebar-bg: #0b0f19;
            --body-bg: #f4f7fe; --text-color: #2b3674; --white: #ffffff; --accent: #00d1ff;
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
        .user-nav-profile { display: flex; align-items: center; gap: 12px; padding-left: 20px; border-left: 1px solid #e2e8f0; }
        .user-nav-profile img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--primary); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .card { background: var(--white); border-radius: 24px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid rgba(0,0,0,0.01); }

        input { width: 100%; padding: 14px; border-radius: 15px; border: 1.5px solid #e2e8f0; outline: none; transition: 0.3s; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        td, th { padding: 16px 15px; }
        td { background: #fff; border-top: 1.5px solid #f8fafc; border-bottom: 1.5px solid #f8fafc; font-size: 14px; font-weight: 600; }
        td:first-child { border-radius: 15px 0 0 15px; }
        td:last-child { border-radius: 0 15px 15px 0; }
        .table-avatar { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; }

        .btn { padding: 12px 24px; border-radius: 14px; border: none; cursor: pointer; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-p { background: var(--primary); color: white; }
        .btn-d { background: #fee2e2; color: #ef4444; }
        .btn-s { background: #dcfce7; color: #166534; }
        .alert { padding: 18px; border-radius: 18px; margin-bottom: 25px; font-weight: 700; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.danger { background: #fee2e2; color: #991b1b; }
        .alert.info { background: #e0e7ff; color: #4361ee; }

        .modal { display:none; position:fixed; inset:0; background:rgba(11, 15, 25, 0.85); z-index:2000; align-items:center; justify-content:center; backdrop-filter: blur(8px); }
        .modal-box { background: white; padding: 40px; border-radius: 28px; width: 100%; max-width: 450px; }

        /* --- PRINT CSS --- */
        @media print {
            .sidebar, .top-header, .stats-grid, #registrasiCard, #liveSearch, .btn, #checkAll, 
            th:first-child, td:first-child, th:last-child, td:last-child, .pagination-area {
                display: none !important;
            }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; width: 100% !important; }
            table { border-spacing: 0; width: 100%; border-collapse: collapse; }
            td { border: 1px solid #eee !important; padding: 10px !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 30px; }
            code { background: transparent !important; padding: 0 !important; font-family: monospace; color: #000 !important; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo">ELMS Mari Belajar</div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php" class="active"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php"><i class='bx bxs-file-plus'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php"><i class='bx bxs-bar-chart-alt-2'></i> Nilai Siswa</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #fb7185;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="main">
        <div class="top-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class='bx bx-category-alt' id="toggle-sidebar" style="font-size: 28px; cursor: pointer; color: var(--primary);"></i>
                <h1 style="font-size: 20px; font-weight: 800; margin: 0;">User Management</h1>
            </div>

            <div class="user-nav-profile">
                <div style="text-align: right;">
                    <p style="margin:0; font-size: 14px; font-weight: 700; color: var(--text-color);"><?= $_SESSION['username'] ?></p>
                    <p style="margin:0; font-size: 11px; color: #22c55e; font-weight: 600;">Administrator</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=4361ee&color=fff&bold=true">
            </div>
        </div>

        <?php echo $notifikasi; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0e7ff; color: #4361ee;"><i class='bx bxs-user-voice'></i></div>
                <div><p style="color: #a3aed0; font-size: 12px; font-weight: 700;">TOTAL USER</p><h3 style="font-size: 20px;"><?= $total_data ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #22c55e;"><i class='bx bxs-printer'></i></div>
                <div>
                    <p style="color: #a3aed0; font-size: 12px; font-weight: 700;">LAPORAN</p>
                    <a href="javascript:void(0)" onclick="printAll()" style="text-decoration:none; font-size: 14px; font-weight:800; color: var(--primary);">Cetak Semua User</a>
                </div>
            </div>
        </div>

        <div class="card" id="registrasiCard">
            <h3 style="margin-bottom: 25px;"><i class='bx bxs-plus-circle'></i> Registrasi Akses</h3>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; align-items: end;">
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">USERNAME (NAMA BEBAS)</label>
                    <input type="text" name="username" placeholder="Masukkan nama..." required autocomplete="off">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #a3aed0;">ACCESS KEY (PASSWORD)</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="tambah_user" class="btn btn-p" style="justify-content:center; height: 48px;">Otorisasi Akun</button>
            </form>
        </div>

        <div class="card" id="printableTable">
            <div class="print-header">
                <h2>LAPORAN AKSES USER ELMS</h2>
                <p>Dicetak pada: <?= date('d-m-Y H:i:s') ?></p>
                <hr style="margin: 20px 0;">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
                <div onkeypress="return event.keyCode != 13;"> 
                    <input type="text" id="liveSearch" placeholder="Cari user..." onkeyup="searchTable()" style="width: 300px;">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-d" onclick="submitMassDelete()"><i class='bx bxs-trash-alt'></i> Delete Selected</button>
                </div>
            </div>

            <form method="POST" id="massDeleteForm">
                <input type="hidden" name="btn_mass_delete" value="1">
                <table id="mainTable">
                    <thead>
                        <tr style="text-align: left; color: #a3aed0; font-size: 11px;">
                            <th width="50"><input type="checkbox" id="checkAll"></th>
                            <th>Identity</th>
                            <th>Access Key</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminTableBody">
                        <?php while($row = mysqli_fetch_assoc($data_admin)) { ?>
                        <tr id="row-<?= $row['id'] ?>">
                            <td><input type="checkbox" name="selected_ids[]" value="<?= $row['id'] ?>"></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['username']) ?>&background=random&color=fff&bold=true" class="table-avatar">
                                    <span class="uname"><?= $row['username'] ?></span>
                                </div>
                            </td>
                            <td><code class="upass" style="color: #64748b; background: #f8fafc; padding: 6px 10px; border-radius: 8px;"><?= $row['password'] ?></code></td>
                            <td style="text-align: center;">
                                <button type="button" class="btn" style="background: #f0f9ff; color: #0369a1; padding: 10px;" onclick="printSingle('<?= $row['id'] ?>')"><i class='bx bxs-printer'></i></button>
                                <button type="button" class="btn" style="background: #fffbeb; color: #d97706; padding: 10px;" onclick="openEdit('<?= $row['id'] ?>', '<?= $row['username'] ?>', '<?= $row['password'] ?>')"><i class='bx bxs-edit-alt'></i></button>
                                <a href="?del_id=<?= $row['id'] ?>" class="btn btn-d" style="padding: 10px;" onclick="return confirm('Hapus?')"><i class='bx bxs-trash'></i></a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </form>

            <div class="pagination-area" style="margin-top: 30px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
                <?php if (!$view_all): ?>
                <div style="display: flex; gap: 8px;">
                    <?php for($i=1; $i<=$pages; $i++): ?>
                        <a href="?p=<?= $i ?>" style="text-decoration:none; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid #e2e8f0; background:<?= ($page==$i)?'var(--primary)':'white' ?>; color:<?= ($page==$i)?'white':'var(--text-color)' ?>; font-weight:700;"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <a href="?view=all" class="btn" style="background: #e0e7ff; color: var(--primary); width: 100%; justify-content: center;">Tampilkan Semua Data (<?= $total_data ?>)</a>
                <?php else: ?>
                <a href="Data_user.php" class="btn" style="background: #f1f5f9; color: #64748b; width: 100%; justify-content: center;">Kembali ke Pagination</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="editModal" class="modal">
        <div class="modal-box">
            <h3 style="margin-bottom: 25px; font-weight: 800;">Modifikasi Akses</h3>
            <form method="POST">
                <input type="hidden" name="id_edit" id="id_e">
                <div style="margin-bottom: 20px;">
                    <label style="font-size:11px; font-weight:800; color: #a3aed0;">IDENTITAS (USERNAME)</label>
                    <input type="text" name="username_edit" id="user_e" required>
                </div>
                <div style="margin-bottom: 30px;">
                    <label style="font-size:11px; font-weight:800; color: #a3aed0;">ACCESS KEY (PASSWORD)</label>
                    <input type="text" name="password_edit" id="pass_e" required>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="submit" name="update_user" class="btn btn-p" style="flex:1; justify-content:center;">Update</button>
                    <button type="button" onclick="closeEdit()" class="btn" style="flex:1; background:#f1f5f9; justify-content:center; color: #64748b;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function submitMassDelete() {
            const form = document.getElementById('massDeleteForm');
            const checkboxes = document.getElementsByName('selected_ids[]');
            let checked = false;
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) { checked = true; break; }
            }
            if (!checked) { alert("Pilih data yang ingin dihapus terlebih dahulu!"); return; }
            if (confirm('Hapus data terpilih?')) { form.submit(); }
        }

        function printAll() {
            const isViewAll = <?= json_encode($view_all) ?>;
            if (!isViewAll) {
                if (confirm("Untuk mencetak SELURUH data, sistem harus memuat semua data terlebih dahulu. Lanjutkan?")) {
                    window.location.href = "?view=all&triggerPrint=true";
                }
            } else { window.print(); }
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('triggerPrint') === 'true') { window.print(); }
        }

        function printSingle(id) {
            const row = document.getElementById('row-' + id);
            const name = row.querySelector('.uname').innerText;
            const pass = row.querySelector('.upass').innerText;
            
            const printWin = window.open('', '', 'width=600,height=400');
            printWin.document.write(`
                <html>
                <head>
                    <title>Cetak Password - ${name}</title>
                    <style>
                        body { font-family: sans-serif; padding: 40px; text-align: center; border: 2px dashed #4361ee; margin: 20px; }
                        h2 { color: #4361ee; margin-bottom: 5px; }
                        .box { background: #f4f7fe; padding: 20px; border-radius: 10px; margin-top: 20px; }
                        .label { font-size: 12px; color: #666; font-weight: bold; }
                        .value { font-size: 20px; font-weight: 800; color: #2b3674; margin-bottom: 15px; }
                    </style>
                </head>
                <body>
                    <h2>AKSES LOGIN ELMS</h2>
                    <p>Simpan data ini dengan aman</p>
                    <div class="box">
                        <div class="label">USERNAME</div>
                        <div class="value">${name}</div>
                        <div class="label">PASSWORD / ACCESS KEY</div>
                        <div class="value">${pass}</div>
                    </div>
                </body>
                </html>
            `);
            printWin.document.close();
            printWin.print();
            printWin.close();
        }

        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main');
        document.getElementById('toggle-sidebar').onclick = () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }

        function searchTable() {
            let filter = document.getElementById("liveSearch").value.toUpperCase();
            let tr = document.getElementById("adminTableBody").getElementsByTagName("tr");
            for (let i = 0; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    tr[i].style.display = td.textContent.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        document.getElementById('checkAll').onclick = function() {
            let boxes = document.getElementsByName('selected_ids[]');
            for (let box of boxes) { box.checked = this.checked; }
        }

        function openEdit(id, u, p) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('id_e').value = id;
            document.getElementById('user_e').value = u;
            document.getElementById('pass_e').value = p;
        }
        function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
    </script>
</body>
</html>