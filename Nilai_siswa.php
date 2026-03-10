<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) { header("location:login.php"); exit(); }

// --- LOGIKA HAPUS ---
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

// --- LOGIKA PENCARIAN ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if (!empty($search)) {
    $where_clause = " WHERE username LIKE '%$search%' ";
}

// --- PAGINATION & LIMIT ---
$limit_param = isset($_GET['limit']) ? $_GET['limit'] : 5;
$is_show_all = ($limit_param == 'all');

$batas = $is_show_all ? 999999 : (int)$limit_param; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// Hitung total berdasarkan pencarian
$data_total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai_ujian $where_clause");
$data_total_row = mysqli_fetch_assoc($data_total_query);
$total_data = $data_total_row['total'];
$total_halaman = $is_show_all ? 1 : ceil($total_data / $batas);

// Statistik tetap diambil dari seluruh data (atau ganti $where_clause jika ingin statistik per pencarian)
$query_stats = mysqli_query($conn, "SELECT COUNT(*) as total, AVG(skor) as rata_rata, MAX(skor) as tertinggi, MIN(skor) as terendah FROM nilai_ujian $where_clause");
$stats = mysqli_fetch_assoc($query_stats);

// Logika Query Data
$is_print_all = isset($_GET['action']) && $_GET['action'] == 'print_all';

if ($is_print_all || $is_show_all) {
    $sql = "SELECT * FROM nilai_ujian $where_clause ORDER BY skor DESC";
} else {
    $sql = "SELECT * FROM nilai_ujian $where_clause ORDER BY skor DESC LIMIT $halaman_awal, $batas";
}
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

        /* SIDEBAR */
        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: var(--sidebar-bg); transition: 0.4s; z-index: 1000; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
        .sidebar.collapsed { left: -260px; }
        .sidebar .logo { padding: 40px 25px; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar ul { list-style: none; padding: 20px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #a3aed0; text-decoration: none; border-radius: 14px; transition: 0.3s; margin-bottom: 5px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar ul li a.active { background: var(--primary); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }
        
        /* CONTENT */
        .main-content { margin-left: 260px; padding: 40px; transition: 0.4s; }
        .main-content.expanded { margin-left: 0; }

        /* HEADER SECTION */
        .top-nav { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            background: var(--white);
            padding: 15px 25px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        .nav-left { display: flex; align-items: center; gap: 15px; }
        .nav-right { display: flex; align-items: center; gap: 10px; }

        /* SEARCH BOX */
        .search-container {
            display: flex;
            background: #f4f7fe;
            padding: 5px 15px;
            border-radius: 12px;
            align-items: center;
            border: 1.5px solid #eef0f7;
        }
        .search-container input {
            border: none;
            background: transparent;
            padding: 8px;
            outline: none;
            font-weight: 600;
            color: #2b3674;
            width: 150px;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: 0 4px 25px rgba(0,0,0,0.03); }

        /* TABLE & BUTTONS */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 15px; color: #a3aed0; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #f1f4f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f4f9; font-size: 14px; font-weight: 600; }
        
        .btn-action { padding: 10px 16px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-del-selected { background: #fee2e2; color: var(--danger); }
        .btn-del-selected:hover { background: var(--danger); color: #fff; }
        .btn-print { background: var(--primary); color: #fff; }
        .btn-print:hover { opacity: 0.9; }

        .limit-select { 
            padding: 9px 12px; 
            border-radius: 12px; 
            border: 1.5px solid #eef0f7; 
            font-weight: 700; 
            color: #2b3674; 
            outline: none; 
            cursor: pointer;
            font-size: 13px;
            background: #f8fafc;
        }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; }
        .page-item { padding: 8px 15px; background: #fff; border-radius: 8px; text-decoration: none; color: #2b3674; font-weight: 700; border: 1px solid #f1f4f9; }
        .page-item.active { background: var(--primary); color: #fff; }

        @media print {
            .sidebar, .top-nav, .pagination, .bx, th:last-child, td:last-child, th:first-child, td:first-child, .search-container {
                display: none !important;
            }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; }
            body { background: white !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <span>ELMS Mari Belajar</span>
        </div>
        <ul>
            <li><a href="Dashboard.php"><i class='bx bxs-grid-alt'></i> Dashboard</a></li>
            <li><a href="Data_user.php"><i class='bx bxs-user'></i> Data User</a></li>
            <li><a href="Data_Siswa.php"><i class='bx bxs-user-rectangle'></i> Data Siswa</a></li>
            <li><a href="Input_soal.php"><i class='bx bxs-file-plus'></i> Input Soal</a></li>
            <li><a href="Nilai_siswa.php" class="active"><i class='bx bxs-bar-chart-alt-2'></i> Nilai Siswa</a></li>
            <li style="margin-top: 40px;"><a href="login.php" style="color: #ff4757;"><i class='bx bx-power-off'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content" id="content">
        <div class="top-nav">
            <div class="nav-left">
                <i class='bx bx-menu-alt-left' id="toggle-sidebar" style="font-size: 24px; cursor: pointer; color: var(--primary);"></i>
                <h1 style="font-size: 18px; font-weight: 800; color: #1b2559;">Data Nilai</h1>
                
                <form method="GET" action="" class="search-container">
                    <i class='bx bx-search' style="color: #a3aed0;"></i>
                    <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= htmlspecialchars($search) ?>">
                    <?php if(!empty($search)): ?>
                        <a href="Nilai_siswa.php" style="color: #a3aed0;"><i class='bx bx-x'></i></a>
                    <?php endif; ?>
                    <input type="hidden" name="limit" value="<?= $limit_param ?>">
                </form>
            </div>
            
            <div class="nav-right">
                <select class="limit-select" onchange="location = this.value;">
                    <option value="?limit=5&search=<?= $search ?>" <?= $limit_param=='5'?'selected':'' ?>>5 Baris</option>
                    <option value="?limit=10&search=<?= $search ?>" <?= $limit_param=='10'?'selected':'' ?>>10 Baris</option>
                    <option value="?limit=25&search=<?= $search ?>" <?= $limit_param=='25'?'selected':'' ?>>25 Baris</option>
                    <option value="?limit=all&search=<?= $search ?>" <?= $limit_param=='all'?'selected':'' ?>>Semua</option>
                </select>

                <button type="submit" form="formDelete" name="delete_selected" class="btn-action btn-del-selected" onclick="return confirm('Hapus data yang dipilih?')">
                    <i class='bx bx-trash-alt'></i> Hapus
                </button>

                <a href="?action=print_all&search=<?= $search ?>" class="btn-action btn-print">
                    <i class='bx bx-printer'></i> Cetak Semua
                </a>
            </div>
        </div>

        <div class="print-header">
            <h2 style="font-weight: 800; color: #111827;">LAPORAN NILAI SISWA ELMS</h2>
            <p>Tanggal Cetak: <?= date('d F Y') ?></p>
            <?php if(!empty($search)): ?> <p>Filter Pencarian: "<?= htmlspecialchars($search) ?>"</p> <?php endif; ?>
            <hr style="margin: 15px 0;">
        </div>

        <div class="stats-grid">
            <div class="stat-card"><p style="color: #a3aed0; font-size: 11px; font-weight:700;">TOTAL DATA</p><h3><?= $stats['total'] ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 11px; font-weight:700;">RATA-RATA</p><h3 style="color:var(--primary)"><?= number_format($stats['rata_rata'], 1) ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 11px; font-weight:700;">TERTINGGI</p><h3 style="color:#22c55e"><?= round($stats['tertinggi']) ?></h3></div>
            <div class="stat-card"><p style="color: #a3aed0; font-size: 11px; font-weight:700;">TERENDAH</p><h3 style="color:#ff4757"><?= round($stats['terendah']) ?></h3></div>
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
                            <th>Tanggal</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($data_nilai) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($data_nilai)): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="checkItem"></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <img src="https://ui-avatars.com/api/?name=<?= $row['username'] ?>&background=random" width="28" style="border-radius:6px;">
                                        <span class="nama-siswa"><?= htmlspecialchars($row['username']) ?></span>
                                    </div>
                                </td>
                                <td><b class="skor-siswa" style="color:var(--primary)"><?= round($row['skor']) ?></b></td>
                                <td><b class="benar-siswa" style="color:var(--primary)"><?= round($row['benar']) ?></b></td>
                                <td style="color:#a3aed0; font-size:12px;"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                <td style="text-align:center">
                                    <a href="javascript:void(0)" onclick="printRow('<?= $row['id'] ?>')" style="color:var(--primary); margin-right:12px; font-size:16px;"><i class='bx bx-printer'></i></a>
                                    <a href="?del_id=<?= $row['id'] ?>&search=<?= $search ?>" style="color:var(--danger); font-size:16px;" onclick="return confirm('Hapus?')"><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#a3aed0;">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <?php if(!$is_show_all && $total_halaman > 1): ?>
            <div class="pagination">
                <?php for($i=1; $i<=$total_halaman; $i++): ?>
                    <a href="?halaman=<?= $i ?>&limit=<?= $limit_param ?>&search=<?= $search ?>" class="page-item <?= ($i==$halaman)?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        });

        document.getElementById('checkAll').onclick = function() {
            let items = document.getElementsByClassName('checkItem');
            for (let i of items) i.checked = this.checked;
        }

        function printRow(id) {
            const row = document.getElementById('row-' + id);
            const nama = row.querySelector('.nama-siswa').innerText;
            const skor = row.querySelector('.skor-siswa').innerText;
            const benar = row.querySelector('.benar-siswa').innerText;

            const printWindow = window.open('', '', 'height=500,width=700');
            printWindow.document.write('<html><head><title>Hasil - ' + nama + '</title>');
            printWindow.document.write('<style>body{font-family:sans-serif; padding:40px; text-align:center;} .card{border:2px solid #4361ee; border-radius:15px; padding:20px;} .skor{font-size:40px; color:#4361ee;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="card">');
            printWindow.document.write('<h2>HASIL UJIAN</h2><p>' + nama + '</p>');
            printWindow.document.write('<p>Skor Akhir:</p><strong class="skor">' + skor + '</strong>');
            printWindow.document.write('<p>Benar: ' + benar + '</p>');
            printWindow.document.write('</div></body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        <?php if($is_print_all): ?>
        window.onload = function() {
            window.print();
            setTimeout(() => { window.location.href = 'Nilai_siswa.php?search=<?= $search ?>'; }, 1000);
        }
        <?php endif; ?>
    </script>
</body>
</html>