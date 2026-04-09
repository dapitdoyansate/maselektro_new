<?php
session_start();
include '../config.php';

// 🔐 Cek Sesi Petugas
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php"); exit();
}

// ✅ PROSES ACC / TOLAK PESANAN
if (isset($_POST['aksi'])) {
    $id = (int)$_POST['id'];
    $aksi = $_POST['aksi'];

    if ($aksi == 'acc') {
        // Ubah status jadi 'paid' (Sudah Dibayar)
        mysqli_query($koneksi, "UPDATE orders SET status = 'paid' WHERE id = $id");
        $pesan = "✅ Pesanan #$id berhasil dikonfirmasi (Sudah Dibayar)!";
    } elseif ($aksi == 'tolak') {
        // Ubah status jadi 'dibatalkan'
        mysqli_query($koneksi, "UPDATE orders SET status = 'dibatalkan' WHERE id = $id");
        
        // Kembalikan stok barang
        $items = mysqli_query($koneksi, "SELECT product_id, quantity FROM order_details WHERE order_id = $id");
        while($item = mysqli_fetch_assoc($items)) {
            mysqli_query($koneksi, "UPDATE products SET stok = stok + {$item['quantity']} WHERE id = {$item['product_id']}");
        }
        $pesan = "❌ Pesanan #$id dibatalkan & stok dikembalikan.";
    }
}

// Ambil Data Pesanan
$orders = mysqli_query($koneksi, "
    SELECT o.*, u.nama_lengkap 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.tanggal_transaksi DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Petugas Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --dark: #1e293b;
            --light: #f8fafc; --gray: #64748b; --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1); --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: var(--dark); }
        .wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar Fix */
        .sidebar { width: 280px; background: var(--dark); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .brand-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 20px; }
        .brand-text { color: white; font-size: 20px; font-weight: 700; }
        .sidebar-menu { padding: 20px 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #94a3b8; text-decoration: none; border-radius: 10px; margin-bottom: 6px; transition: all 0.3s; font-weight: 500; }
        .menu-item:hover { background: rgba(255,255,255,0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .menu-item i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout { display: flex; align-items: center; gap: 10px; color: #fca5a5; text-decoration: none; padding: 12px; border-radius: 10px; transition: all 0.3s; }
        .logout:hover { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        
        /* Main Content */
        .main-content { margin-left: 280px; flex: 1; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: var(--primary); }
        
        /* Card & Table */
        .card { background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 2px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; }
        th { padding: 16px 20px; text-align: left; font-size: 13px; font-weight: 700; color: var(--gray); text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-selesai { background: #d1fae5; color: #065f46; }
        .badge-dibatalkan { background: #fee2e2; color: #991b1b; }
        
        .btn { padding: 8px 16px; border-radius: 6px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-acc { background: #10b981; color: white; }
        .btn-acc:hover { background: #059669; }
        .btn-tolak { background: #ef4444; color: white; margin-left: 5px; }
        .btn-tolak:hover { background: #dc2626; }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- ✅ SIDEBAR YANG SUDAH DIPERBAIKI -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-icon">M</div>
                <div class="brand-text">Petugas Panel</div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="kelola_pesanan.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_pesanan.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-bag"></i> Kelola Pesanan
                </a>
                <a href="kelola_data_user.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_data_user.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <a href="kelola_data_produk.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_data_produk.php' ? 'active' : '' ?>">
                    <i class="fas fa-box-open"></i> Kelola Produk
                </a>
                <a href="laporan.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</h1>
            </div>
            
            <?php if(isset($pesan)): ?>
                <div class="alert alert-success"><?= $pesan ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Daftar Semua Pesanan</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($o = mysqli_fetch_assoc($orders)): 
                                $badge_class = 'badge-' . ($o['status'] ?? 'pending');
                            ?>
                            <tr>
                                <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($o['nama_lengkap'] ?? 'Guest') ?></div>
                                    <small style="color: var(--gray);"><?= htmlspecialchars($o['no_telp'] ?? '') ?></small>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($o['tanggal_transaksi'])) ?></td>
                                <td><strong style="color: var(--primary);">Rp <?= number_format($o['total_bayar'], 0, ',', '.') ?></strong></td>
                                <td><?= htmlspecialchars($o['metode_pembayaran']) ?></td>
                                <td><span class="badge <?= $badge_class ?>"><?= strtoupper($o['status'] ?? 'PENDING') ?></span></td>
                                <td>
                                    <?php if($o['status'] == 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                            <button type="submit" name="aksi" value="acc" class="btn btn-acc" onclick="return confirm('Konfirmasi pesanan ini sudah dibayar?')">
                                                <i class="fas fa-check"></i> ACC
                                            </button>
                                            <button type="submit" name="aksi" value="tolak" class="btn btn-tolak" onclick="return confirm('Batalkan pesanan ini?')">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>