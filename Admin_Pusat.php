<?php
session_start();

// --- 1. KONFIGURASI DATABASE ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "database1"; 

$conn = mysqli_connect($host, $user, $pass, $db);

// --- 2. LOGIKA PROSES (TAMBAH, UPDATE, HAPUS) ---
$notifikasi = "";

// A. PROSES TAMBAH USER BARU
if (isset($_POST['tambah_user'])) {
    $user_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $pass_baru = mysqli_real_escape_string($conn, $_POST['password']);
    
    $sql_tambah = "INSERT INTO user (username, password) VALUES ('$user_baru', '$pass_baru')";
    if (mysqli_query($conn, $sql_tambah)) {
        $notifikasi = "<div class='alert success'>User <b>$user_baru</b> berhasil ditambahkan!</div>";
    }
}

// B. PROSES UPDATE PASSWORD USER
if (isset($_POST['update_password'])) {
    $id_edit = mysqli_real_escape_string($conn, $_POST['id_user']);
    $pass_edit = mysqli_real_escape_string($conn, $_POST['password_baru']);
    
    $sql_update = "UPDATE user SET password = '$pass_edit' WHERE id = '$id_edit'";
    if (mysqli_query($conn, $sql_update)) {
        $notifikasi = "<div class='alert success'>Password ID #$id_edit berhasil diubah!</div>";
    }
}

// C. PROSES HAPUS USER
if (isset($_GET['delete_id'])) {
    $id_target = mysqli_real_escape_string($conn, $_GET['delete_id']);
    if (mysqli_query($conn, "DELETE FROM user WHERE id = '$id_target'")) {
        $notifikasi = "<div class='alert error'>User ID #$id_target telah dihapus dari sistem.</div>";
    }
}

// --- 3. KEAMANAN SESSION ---
if (!isset($_SESSION['username'])) {
    header("location:login.php"); exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Admin Panel 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary: #4e73df; --success: #1cc88a; --danger: #e74c3c;
            --bg-body: #f8f9fc; --bg-card: #ffffff; --text-main: #2e3b4e;
            --sidebar-bg: #1a1c2d; --border: #e3e6f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; }

        /* SIDEBAR (TIDAK DIHILANGKAN) */
        .sidebar { width: 280px; background: var(--sidebar-bg); color: #fff; padding: 25px 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .brand { font-size: 1.4rem; font-weight: 800; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .nav-label { font-size: 0.65rem; color: rgba(255,255,255,0.3); text-transform: uppercase; margin: 20px 0 10px 10px; letter-spacing: 1.5px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: rgba(255,255,255,0.6); text-decoration: none; border-radius: 10px; margin-bottom: 5px; font-size: 0.85rem; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-item.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2); }

        /* MAIN CONTENT */
        .main-panel { margin-left: 280px; padding: 30px; width: calc(100% - 280px); }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); border: 1px solid var(--border); }

        /* FORM TAMBAH */
        .form-box { background: #f8f9fc; padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 1px dashed var(--primary); }
        .flex-form { display: flex; gap: 15px; align-items: flex-end; }
        .input-group { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        input { padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* TABEL */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid var(--border); font-size: 0.75rem; color: #888; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .pass-label { background: #eee; padding: 4px 10px; border-radius: 6px; font-family: 'Courier New', monospace; font-weight: 600; }

        /* ACTION BUTTONS */
        .btn-update { background: var(--success); color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 0.75rem; }
        .btn-del { color: var(--danger); text-decoration: none; font-size: 0.8rem; border: 1px solid var(--danger); padding: 5px 10px; border-radius: 5px; transition: 0.3s; }
        .btn-del:hover { background: var(--danger); color: white; }

        /* ALERTS */
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand"><span class="material-symbols-rounded">school</span> ELMS CORE</div>
        
        <div class="nav-label">Dashboard</div>
        <a href="?page=dashboard" class="nav-item <?php echo $page=='dashboard'?'active':''; ?>">
            <span class="material-symbols-rounded">grid_view</span> Ringkasan Utama
        </a>

        <div class="nav-label">Manajemen Akademik</div>
        <a href="?page=siswa" class="nav-item <?php echo $page=='siswa'?'active':''; ?>">
            <span class="material-symbols-rounded">group</span> Data Siswa / User
        </a>
        <a href="?page=materi" class="nav-item <?php echo $page=='materi'?'active':''; ?>">
            <span class="material-symbols-rounded">library_books</span> Materi & Modul
        </a>
        <a href="?page=kuis" class="nav-item <?php echo $page=='kuis'?'active':''; ?>">
            <span class="material-symbols-rounded">quiz</span> Bank Soal & Kuis
        </a>

        <div class="nav-label">Evaluasi & Hasil</div>
        <a href="?page=nilai" class="nav-item <?php echo $page=='nilai'?'active':''; ?>">
            <span class="material-symbols-rounded">monitoring</span> Laporan Nilai
        </a>
        <a href="?page=sertifikat" class="nav-item <?php echo $page=='sertifikat'?'active':''; ?>">
            <span class="material-symbols-rounded">workspace_premium</span> Sertifikat Digital
        </a>

        <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="login.php" class="nav-item" style="color: #ff7675;">
                <span class="material-symbols-rounded">logout</span> Keluar Sistem
            </a>
        </div>
    </aside>

    <main class="main-panel">
        <header style="margin-bottom: 30px;">
            <h2 style="font-weight: 800;">Manajemen User E-Learning</h2>
            <p style="color: #888; font-size: 0.9rem;">Kelola login, update password, dan monitoring akses user.</p>
        </header>

        <?php echo $notifikasi; ?>

        <div class="card">
            <div class="form-box">
                <h4 style="margin-bottom: 15px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:var(--primary)">person_add</span> Tambah User Baru
                </h4>
                <form method="POST" class="flex-form">
                    <div class="input-group">
                        <label style="font-size: 0.75rem; font-weight:700;">Username</label>
                        <input type="text" name="username" placeholder="Contoh: budi_elearning" required>
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.75rem; font-weight:700;">Password</label>
                        <input type="text" name="password" placeholder="Sandi default..." required>
                    </div>
                    <button type="submit" name="tambah_user" class="btn-primary">Simpan User</button>
                </form>
            </div>

            

            <h4 style="margin-bottom: 10px;">Daftar Akun User (Database: database1)</h4>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Password (Tampil)</th>
                        <th style="text-align: right;">Opsi & Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($conn, "SELECT * FROM user ORDER BY id DESC");
                    if (mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)) {
                            echo "<tr>
                                    <td>#".$row['id']."</td>
                                    <td><b style='color:var(--primary)'>".htmlspecialchars($row['username'])."</b></td>
                                    <td><span class='pass-label'>".$row['password']."</span></td>
                                    <td style='text-align: right;'>
                                        <form method='POST' style='display:inline-flex; gap: 5px;'>
                                            <input type='hidden' name='id_user' value='".$row['id']."'>
                                            <input type='text' name='password_baru' placeholder='Ganti sandi...' style='padding: 5px; font-size: 0.75rem; width: 120px;'>
                                            <button type='submit' name='update_password' class='btn-update'>Update</button>
                                        </form>
                                        <span style='margin: 0 10px; color: #ddd;'>|</span>
                                        <a href='?page=$page&delete_id=".$row['id']."' 
                                           class='btn-del' 
                                           onclick='return confirm(\"Hapus user ini?\")'>Hapus</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:#aaa;'>Data user belum tersedia.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>