<?php
session_start();
include '../config.php';

// Cek sesi petugas
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php");
    exit();
}

// Hitung statistik sederhana
$total_produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM produk"))['count'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM transaksi"))['count'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as count FROM transaksi"))['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Petugas</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2563eb; --bg-body: #f1f5f9; --bg-sidebar: #1e293b; --text-light: #f8fafc; --text-dark: #0f172a; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; }
        
        .sidebar { width: 280px; background-color: var(--bg-sidebar); position: fixed; top: 0; left: 0; height: 100vh; display: flex; flex-direction: column; transition: transform 0.3s; z-index: 1000; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .brand-logo { width: 32px; height: 32px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .sidebar-title { font-size: 20px; font-weight: 700; color: var(--text-light); }
        .sidebar-menu { padding: 20px 15px; flex: 1; overflow-y: auto; }
        .menu-item { display: flex; align-items: center; padding: 12px 16px; color: #94a3b8; font-size: 15px; font-weight: 500; border-radius: 8px; margin-bottom: 4px; transition: all 0.2s; cursor: pointer; }
        .menu-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .menu-item.active { background-color: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4); }
        .menu-item i { width: 24px; margin-right: 10px; text-align: center; }
        
        /* Dropdown */
        .dropdown-toggle { justify-content: space-between; }
        .dropdown-toggle::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; border: none; transition: transform 0.3s; }
        .dropdown-toggle.active::after { transform: rotate(180deg); }
        .dropdown-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; padding-left: 10px; }
        .dropdown-menu.show { max-height: 500px; padding-top: 5px; }
        .dropdown-item { display: flex; align-items: center; padding: 10px 16px 10px 40px; color: #94a3b8; font-size: 14px; border-radius: 6px; margin-bottom: 2px; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .dropdown-item.active { background-color: rgba(37, 99, 235, 0.2); color: var(--primary); font-weight: 600; }
        .dropdown-item i { width: 20px; margin-right: 8px; font-size: 12px; }
        .dropdown-item.disabled { opacity: 0.5; cursor: not-allowed; }
        .dropdown-item.disabled:hover { background-color: transparent; color: #94a3b8; }
        
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { display: flex; align-items: center; color: #fca5a5; font-weight: 600; transition: color 0.2s; }
        .logout-btn:hover { color: #ef4444; }
        .logout-btn i { margin-right: 10px; font-size: 18px; }

        .main-content { margin-left: 280px; flex: 1; padding: 30px; transition: margin-left 0.3s; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; color: var(--text-dark); }
        .mobile-toggle { display: none; font-size: 24px; color: var(--text-dark); cursor: pointer; background: none; border: none; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-info h3 { font-size: 24px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
        .stat-info p { font-size: 14px; color: #64748b; }
        
        .bg-blue { background: #eff6ff; color: #2563eb; }
        .bg-green { background: #f0fdf4; color: #10b981; }
        .bg-purple { background: #f5f3ff; color: #8b5cf6; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo"><i class="fas fa-user-tag"></i></div>
            <div class="sidebar-title">Petugas Panel</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            
            <!-- Dropdown Menu -->
            <div class="menu-item dropdown-toggle active" onclick="toggleDropdown()">
                <span><i class="fas fa-folder-open"></i> Kelola Data</span>
            </div>
            <div class="dropdown-menu show" id="dropdownMenu">
                <a href="kelola_data_user.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Kelola Data User
                </a>
                <a href="kelola_data_produk.php" class="dropdown-item">
                    <i class="fas fa-box"></i> Kelola Data Produk
                </a>
                <a href="kelola_data_transaksi.php" class="dropdown-item">
                    <i class="fas fa-receipt"></i> Kelola Data Transaksi
                </a>
                <a href="laporan.php" class="dropdown-item">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
                <!-- Menu Admin Only (Disabled untuk Petugas) -->
                <a href="#" class="dropdown-item disabled" title="Hanya untuk Admin">
                    <i class="fas fa-download"></i> Backup Data <i class="fas fa-lock" style="margin-left: auto; font-size: 10px;"></i>
                </a>
                <a href="#" class="dropdown-item disabled" title="Hanya untuk Admin">
                    <i class="fas fa-upload"></i> Restore Data <i class="fas fa-lock" style="margin-left: auto; font-size: 10px;"></i>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">Dashboard Petugas</h1>
            <div style="width: 24px;"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-box"></i></div>
                <div class="stat-info">
                    <h3><?= $total_produk ?></h3>
                    <p>Total Produk</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?= $total_transaksi ?></h3>
                    <p>Total Transaksi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h3>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 15px; color: var(--text-dark);">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?>! 👋</h3>
            <p style="color: #64748b; line-height: 1.6;">Silakan gunakan menu di samping untuk mengelola transaksi dan melihat laporan stok. 
            <br><strong>⚠️ Catatan:</strong> Menu Backup & Restore hanya dapat diakses oleh Admin.</p>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const toggle = document.querySelector('.dropdown-toggle');
            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // Auto expand dropdown if active item is inside
        document.addEventListener('DOMContentLoaded', function() {
            const activeDropdownItem = document.querySelector('.dropdown-item.active');
            if (activeDropdownItem) {
                document.getElementById('dropdownMenu').classList.add('show');
                document.querySelector('.dropdown-toggle').classList.add('active');
            }
        });

        // Prevent clicking disabled items
        document.querySelectorAll('.dropdown-item.disabled').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                alert('⛔ Akses Ditolak!\n\nFitur ini hanya tersedia untuk Admin.');
            });
        });
    </script>
</body>
</html>