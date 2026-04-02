<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses Tambah Transaksi
if(isset($_POST['submit_transaksi'])){
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];
    $nama_pembeli = $_POST['nama_pembeli'];
    
    $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk WHERE id='$id_produk'"));
    $total_harga = $produk['harga'] * $jumlah;
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    $petugas = $_SESSION['nama'];

    mysqli_query($koneksi, "INSERT INTO transaksi (id_produk, nama_pembeli, jumlah, total_harga, tanggal, waktu, petugas) VALUES ('$id_produk', '$nama_pembeli', '$jumlah', '$total_harga', '$tanggal', '$waktu', '$petugas')");
    
    $stok_baru = $produk['stok'] - $jumlah;
    mysqli_query($koneksi, "UPDATE produk SET stok='$stok_baru' WHERE id='$id_produk'");

    header("Location: kelola_data_transaksi.php?status=success");
}

$produk_list = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0");
$riwayat_transaksi = mysqli_query($koneksi, "SELECT t.*, p.nama_produk FROM transaksi t JOIN produk p ON t.id_produk = p.id ORDER BY t.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transaksi - Petugas</title>
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

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 24px; border: 1px solid #e2e8f0; }
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #334155; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn-submit { width: 100%; background: #2563eb; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }
        .alert { padding: 15px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0; }
        
        .transaction-table { width: 100%; border-collapse: collapse; }
        .transaction-table th { text-align: left; padding: 10px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; font-size: 13px; }
        .transaction-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; }
        .transaction-table tr:last-child td { border-bottom: none; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
            .grid-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo"><i class="fas fa-user-tag"></i></div>
            <div class="sidebar-title">Petugas Panel</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
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
                <a href="kelola_data_transaksi.php" class="dropdown-item active">
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
            <h1 class="page-title">Input Transaksi</h1>
            <div style="width: 24px;"></div>
        </div>

        <?php if(isset($_GET['status'])): ?>
            <div class="alert">✅ Transaksi berhasil disimpan!</div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Form Input -->
            <div class="card">
                <h3 style="margin-bottom: 20px; color: var(--text-dark);"><i class="fas fa-cash-register"></i> Form Kasir</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nama Pembeli</label>
                        <input type="text" name="nama_pembeli" required placeholder="Nama pelanggan">
                    </div>
                    <div class="form-group">
                        <label>Pilih Produk</label>
                        <select name="id_produk" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php while($p = mysqli_fetch_assoc($produk_list)): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nama_produk']) ?> (Stok: <?= $p['stok'] ?>) - Rp <?= number_format($p['harga']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Beli</label>
                        <input type="number" name="jumlah" min="1" required placeholder="Minimal 1">
                    </div>
                    <button type="submit" name="submit_transaksi" class="btn-submit">
                        <i class="fas fa-check-circle"></i> Proses Transaksi
                    </button>
                </form>
            </div>

            <!-- Riwayat Singkat -->
            <div class="card">
                <h3 style="margin-bottom: 20px; color: var(--text-dark);"><i class="fas fa-history"></i> Transaksi Terakhir</h3>
                <?php if(mysqli_num_rows($riwayat_transaksi) > 0): ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($t = mysqli_fetch_assoc($riwayat_transaksi)): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($t['nama_produk']) ?>
                                <br><small style="color:#64748b"><?= $t['jumlah'] ?> x Rp <?= number_format($t['total_harga'] / $t['jumlah']) ?></small>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--primary);">
                                Rp <?= number_format($t['total_harga']) ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>Belum ada transaksi</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Toggle Dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const toggle = document.querySelector('.dropdown-toggle');
            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // Disable Click on Admin Menu Items
        document.querySelectorAll('.dropdown-item.disabled').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                alert('⛔ Akses Ditolak!\n\nFitur ini hanya tersedia untuk Admin.');
            });
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target) && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>