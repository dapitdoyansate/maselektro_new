<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php");
    exit();
}

$laporan = mysqli_query($koneksi, "SELECT t.*, p.nama_produk, p.kategori FROM transaksi t JOIN produk p ON t.id_produk = p.id ORDER BY t.tanggal DESC, t.waktu DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan - Petugas</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2563eb; --bg-body: #f1f5f9; --bg-sidebar: #1e293b; --text-light: #f8fafc; --text-dark: #0f172a; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); display: flex; min-height: 100vh; }
        a { text-decoration: none; }
        .sidebar { width: 280px; background-color: var(--bg-sidebar); position: fixed; top: 0; left: 0; height: 100vh; display: flex; flex-direction: column; transition: transform 0.3s; z-index: 1000; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .brand-logo { width: 32px; height: 32px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .sidebar-title { font-size: 20px; font-weight: 700; color: var(--text-light); }
        .sidebar-menu { padding: 20px 15px; flex: 1; overflow-y: auto; }
        .menu-item { display: flex; align-items: center; padding: 12px 16px; color: #94a3b8; font-size: 15px; font-weight: 500; border-radius: 8px; margin-bottom: 4px; transition: all 0.2s; cursor: pointer; }
        .menu-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .menu-item.active { background-color: var(--primary); color: white; }
        .menu-item i { width: 24px; margin-right: 10px; text-align: center; }
        .dropdown-toggle { justify-content: space-between; }
        .dropdown-toggle::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; transition: transform 0.3s; }
        .dropdown-toggle.active::after { transform: rotate(180deg); }
        .dropdown-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; padding-left: 10px; }
        .dropdown-menu.show { max-height: 500px; padding-top: 5px; }
        .dropdown-item { display: flex; align-items: center; padding: 10px 16px 10px 40px; color: #94a3b8; font-size: 14px; border-radius: 6px; margin-bottom: 2px; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .dropdown-item.active { background-color: rgba(37, 99, 235, 0.2); color: var(--primary); font-weight: 600; }
        .dropdown-item i { width: 20px; margin-right: 8px; font-size: 12px; }
        .dropdown-item.disabled { opacity: 0.5; cursor: not-allowed; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { display: flex; align-items: center; color: #fca5a5; font-weight: 600; transition: color 0.2s; }
        .logout-btn:hover { color: #ef4444; }
        .logout-btn i { margin-right: 10px; font-size: 18px; }
        .main-content { margin-left: 280px; flex: 1; padding: 30px; transition: margin-left 0.3s; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; color: var(--text-dark); }
        .mobile-toggle { display: none; font-size: 24px; color: var(--text-dark); cursor: pointer; background: none; border: none; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 24px; border: 1px solid #e2e8f0; }
        .table-container { overflow-x: auto; margin-top: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .table td { padding: 15px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } .mobile-toggle { display: block; } }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo"><i class="fas fa-user-tag"></i></div>
            <div class="sidebar-title">Petugas Panel</div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <div class="menu-item dropdown-toggle active" onclick="toggleDropdown()">
                <span><i class="fas fa-folder-open"></i> Kelola Data</span>
            </div>
            <div class="dropdown-menu show" id="dropdownMenu">
                <a href="kelola_data_user.php" class="dropdown-item"><i class="fas fa-user"></i> Kelola Data User</a>
                <a href="kelola_data_produk.php" class="dropdown-item"><i class="fas fa-box"></i> Kelola Data Produk</a>
                <a href="kelola_data_transaksi.php" class="dropdown-item"><i class="fas fa-receipt"></i> Kelola Data Transaksi</a>
                <a href="laporan.php" class="dropdown-item active"><i class="fas fa-file-alt"></i> Laporan</a>
                <a href="#" class="dropdown-item disabled"><i class="fas fa-download"></i> Backup Data <i class="fas fa-lock" style="margin-left: auto; font-size: 10px;"></i></a>
                <a href="#" class="dropdown-item disabled"><i class="fas fa-upload"></i> Restore Data <i class="fas fa-lock" style="margin-left: auto; font-size: 10px;"></i></a>
            </div>
        </div>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1 class="page-title">Laporan Transaksi</h1>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Pembeli</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Jml</th>
                            <th>Total</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($laporan)): ?>
                        <tr>
                            <td><?= $row['tanggal'] ?></td>
                            <td><?= $row['waktu'] ?></td>
                            <td><?= htmlspecialchars($row['nama_pembeli']) ?></td>
                            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td>Rp <?= number_format($row['total_harga']) ?></td>
                            <td><?= htmlspecialchars($row['petugas']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const toggle = document.querySelector('.dropdown-toggle');
            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }
        document.querySelectorAll('.dropdown-item.disabled').forEach(item => {
            item.addEventListener('click', function(e) { e.preventDefault(); alert('⛔ Akses Ditolak! Fitur ini hanya untuk Admin.'); });
        });
    </script>
</body>
</html>