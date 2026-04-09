<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php'); exit();
}

$user_id = (int)$_SESSION['id'];

// Ambil SEMUA pesanan user (termasuk yang sudah di-ACC)
$orders = mysqli_query($koneksi, "
    SELECT * FROM orders 
    WHERE user_id = $user_id 
    ORDER BY tanggal_transaksi DESC
");

if (!$orders) {
    die("Query Error: " . mysqli_error($koneksi));
}

$total = mysqli_num_rows($orders);
$pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM orders WHERE user_id=$user_id AND status='pending_payment'"));
$paid = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM orders WHERE user_id=$user_id AND status='paid'"));
$completed = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM orders WHERE user_id=$user_id AND status='completed'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f3f4f6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .btn { padding: 10px 20px; background: #f3f4f6; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat { background: white; padding: 20px; border-radius: 12px; }
        .stat h3 { font-size: 28px; color: #2563eb; }
        .stat p { color: #6b7280; font-size: 14px; }
        .box { background: white; padding: 25px; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-size: 13px; color: #6b7280; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-pending_payment { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📜 Riwayat Pesanan Saya</h1>
            <a href="dashboard.php" class="btn">← Kembali</a>
        </div>
        
        <div class="stats">
            <div class="stat"><h3><?= $total ?></h3><p>Total Pesanan</p></div>
            <div class="stat"><h3 style="color: #f59e0b;"><?= $pending ?></h3><p>Menunggu Pembayaran</p></div>
            <div class="stat"><h3 style="color: #10b981;"><?= $paid + $completed ?></h3><p>Sudah Dibayar & Selesai</p></div>
        </div>
        
        <div class="box">
            <h3 style="margin-bottom: 20px;">📋 Daftar Pesanan Anda</h3>
            
            <?php if($total > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($o = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('d M Y H:i', strtotime($o['tanggal_transaksi'])) ?></td>
                        <td><strong>Rp <?= number_format($o['total_bayar'], 0, ',', '.') ?></strong></td>
                        <td><?= htmlspecialchars($o['metode_pembayaran']) ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-' . ($o['status'] ?? 'pending_payment');
                            $status_text = strtoupper(str_replace('_', ' ', $o['status'] ?? 'pending_payment'));
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #6b7280;">Belum ada pesanan</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>