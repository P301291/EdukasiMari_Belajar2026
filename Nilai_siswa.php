<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) { header("location:login.php"); exit(); }

// --- LOGIKA HAPUS (Tetap Sama) ---
if (isset($_POST['delete_selected']) && !empty($_POST['ids'])) {
    $ids = $_POST['ids'];
    $all_id = implode(",", array_map('intval', $ids));
    mysqli_query($conn, "DELETE FROM nilai_ujian WHERE id IN ($all_id)");
    header("Location: Nilai_siswa.php?status=selected_deleted"); exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_all') {
    mysqli_query($conn, "TRUNCATE TABLE nilai_ujian");
    header("Location: Nilai_siswa.php?status=all_deleted"); exit();
}

if (isset($_GET['del_id'])) {
    $id = intval($_GET['del_id']);
    mysqli_query($conn, "DELETE FROM nilai_ujian WHERE id = $id");
    header("Location: Nilai_siswa.php?status=deleted"); exit();
}

// --- PAGINATION & LIMIT ---
$batas = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$data_total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai_ujian");
$data_total_row = mysqli_fetch_assoc($data_total_query);
$total_data = $data_total_row['total'];
$total_halaman = ceil($total_data / $batas);

$query_stats = mysqli_query($conn, "SELECT COUNT(*) as total, AVG(skor) as rata_rata, MAX(skor) as tertinggi, MIN(skor) as terendah FROM nilai_ujian");
$stats = mysqli_fetch_assoc($query_stats);

$sql = "SELECT * FROM nilai_ujian ORDER BY skor DESC LIMIT $halaman_awal, $batas";
$data_nilai = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Siswa | ELMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #4361ee; --sidebar-bg: #111827;
            --body-bg: #f4f7fe; --white: #ffffff; --danger: #ff4757;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--body-bg); transition: 0.3s; }

        /* --- SIDEBAR BERFUNGSI --- */
        .sidebar { 
            position: fixed; left: 0; top: 0; height: 100%; width: 260px; 
            background: var(--sidebar-bg); transition: 0.4s; z-index: 1000; 
        }
        /* Class untuk menyembunyikan sidebar */
        /* Sidebar */
        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: var(--sidebar-bg); transition: 0.4s; z-index: 1000; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
        .sidebar.collapsed { left: -260px; }
        .sidebar .logo { padding: 40px 25px; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar ul { list-style: none; padding: 20px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #a3aed0; text-decoration: none; border-radius: 14px; transition: 0.3s; margin-bottom: 5px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar ul li a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }
        /* --- MAIN CONTENT ADJUSTMENT --- */
        .main-content { margin-left: 260px; padding: 40px; transition: 0.4s; }
        .main-content.expanded { margin-left: 0; }

        /* Tombol Toggle Sidebar */
        .toggle-btn { 
            cursor: pointer; font-size: 2px; color: var(--sidebar-bg); 
            background: var(--white); padding: 5px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* --- UI COMPONENTS --- */
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: 0 4px 25px rgba(0,0,0,0.03); }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 15px; color: #a3aed0; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #f1f4f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f4f9; font-size: 14px; font-weight: 600; }
        
        .btn-del-selected { background: #fee2e2; color: var(--danger); border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-del-selected:hover { background: var(--danger); color: #fff; }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; }
        .page-item { padding: 8px 15px; background: #fff; border-radius: 8px; text-decoration: none; color: #2b3674; font-weight: 700; border: 1px solid #f1f4f9; }
        .page-item.active { background: var(--primary); color: #fff; }

        @media (max-width: 992px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <span>ELMS Mari Belajar</span>
            <i class='bx bx-x' id="close-sidebar" style="cursor:pointer; display:none;"></i>
        </div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php"><i class='bx bxs-lock-open-alt'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php" class="active"><i class='bx bxs-color-fill'></i> Nilai Siswa</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #ff4757;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="content">
        <div class="top-nav">
            <div style="display: flex; align-items: center; gap: 20px;">
            <i class='bx bx-category-alt' id="toggle-sidebar" style="font-size: 28px; cursor: pointer; color: var(--primary);"></i>
                <h1 style="font-size: 20px; font-weight: 850;">Manajemen Nilai</h1>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <select onchange="location = this.value;" style="padding: 8px 12px; border-radius: 10px; border: 1px solid #ddd;">
                    <option value="?limit=5" <?= $batas==5?'selected':'' ?>>5 Baris</option>
                    <option value="?limit=10" <?= $batas==10?'selected':'' ?>>10 Baris</option>
                    <option value="?limit=25" <?= $batas==25?'selected':'' ?>>25 Baris</option>
                    <option value="?limit=30" <?= $batas==30?'selected':'' ?>>30 Baris</option>
                </select>
                <button type="submit" form="formDelete" name="delete_selected" class="btn-del-selected" onclick="return confirm('Hapus terpilih?')">Hapus Terpilih</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><p style="color: #a3aed0; font-size: 12px;">TOTAL</p><h3><?= $stats['total'] ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 12px;">RATA-RATA</p><h3 style="color:var(--primary)"><?= number_format($stats['rata_rata'], 1) ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 12px;">TERTINGGI</p><h3 style="color:#22c55e"><?= round($stats['tertinggi']) ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 12px;">TERENDAH</p><h3 style="color:#ff4757"><?= round($stats['terendah']) ?></h3></div>
        </div>

        <div class="card">
            <form id="formDelete" method="POST">
                <table>
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="checkAll"></th>
                            <th>Siswa</th>
                            <th>Skor</th>
                            <th>Benar</th>
                            <th>Waktu</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_nilai)): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="checkItem"></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= $row['username'] ?>&background=random" width="30" style="border-radius:8px;">
                                    <?= htmlspecialchars($row['username']) ?>
                                </div>
                            </td>
                            <td><b style="color:var(--primary)"><?= round($row['skor']) ?></b></td>
                            <td><b style="color:var(--primary)"><?= round($row['benar']) ?></b></td>
                            <td style="color:#a3aed0"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td style="text-align:center">
                                <a href="?del_id=<?= $row['id'] ?>" style="color:var(--danger)" onclick="return confirm('Hapus?')"><i class='bx bxs-trash'></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </form>

            <div class="pagination">
                <?php for($i=1; $i<=$total_halaman; $i++): ?>
                    <a href="?halaman=<?= $i ?>&limit=<?= $batas ?>" class="page-item <?= ($i==$halaman)?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <script>
        // FUNGSI TOGGLE SIDEBAR (Sama seperti fitur asli)
        const toggleBtn = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        });

        // Fitur Check All
        document.getElementById('checkAll').onclick = function() {
            let items = document.getElementsByClassName('checkItem');
            for (let i of items) i.checked = this.checked;
        }
    </script>
</body>
</html>