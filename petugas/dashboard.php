<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php"); exit();
}

// Statistik
$total_produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM products"))['c'] ?? 0;
$total_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM orders"))['c'] ?? 0;
$total_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'"))['c'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_bayar), 0) as c FROM orders WHERE status = 'paid'"))['c'] ?? 0;

$recent_orders = mysqli_query($koneksi, "SELECT o.*, u.nama_lengkap FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.tanggal_transaksi DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: var(--dark); }
        
        .wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--dark);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }
        
        .brand-text {
            color: white;
            font-size: 20px;
            font-weight: 700;
        }
        
        .sidebar-menu { padding: 20px 15px; }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .menu-item:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .menu-item.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .menu-item i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        
        .logout {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fca5a5;
            text-decoration: none;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i { color: var(--primary); }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.blue { background: #eff6ff; color: var(--primary); }
        .stat-icon.green { background: #f0fdf4; color: var(--success); }
        .stat-icon.yellow { background: #fef3c7; color: var(--warning); }
        .stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .stat-info p {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body { padding: 24px; }
        
        /* Table */
        .table-responsive { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead { background: #f8fafc; }
        
        th {
            padding: 14px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
        }
        
        tr:hover { background: #f8fafc; }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-selesai { background: #d1fae5; color: #065f46; }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-icon">M</div>
                <div class="brand-text">Petugas Panel</div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="kelola_pesanan.php" class="menu-item">
                    <i class="fas fa-shopping-bag"></i> Kelola Pesanan
                </a>
                <a href="kelola_data_user.php" class="menu-item">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <a href="kelola_data_produk.php" class="menu-item">
                    <i class="fas fa-box-open"></i> Kelola Produk
                </a>
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-home"></i>
                    Dashboard
                </h1>
                <div style="color: var(--gray);">
                    <i class="fas fa-calendar"></i> <?= date('d F Y') ?>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_produk) ?></h3>
                        <p>Total Produk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_transaksi) ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_pending) ?></h3>
                        <p>Menunggu Konfirmasi</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock" style="color: var(--warning);"></i>
                            Pesanan Terbaru
                        </h3>
                        <a href="kelola_pesanan.php" class="btn btn-primary">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Pelanggan</th>
                                        <th>Tanggal</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($o = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($o['nama_lengkap'] ?? 'Guest') ?></td>
                                        <td><?= date('d M Y', strtotime($o['tanggal_transaksi'])) ?></td>
                                        <td><strong>Rp <?= number_format($o['total_bayar'], 0, ',', '.') ?></strong></td>
                                        <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt" style="color: var(--warning);"></i>
                            Aksi Cepat
                        </h3>
                    </div>
                    <div class="card-body">
                        <a href="kelola_pesanan.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; justify-content: center;">
                            <i class="fas fa-check-circle"></i> Konfirmasi Pesanan
                        </a>
                        <a href="kelola_data_produk.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; background: var(--success); justify-content: center;">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </a>
                        <a href="laporan.php" class="btn btn-primary" style="width: 100%; background: var(--warning); justify-content: center;">
                            <i class="fas fa-file-alt"></i> Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>