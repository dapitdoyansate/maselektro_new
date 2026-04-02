<?php
/**
 * File: user/checkout_success.php
 * FIXED: Secure + Error Handling + Session Protection
 */

session_start();
require_once '../config.php';

// 🔐 Cek autentikasi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil order_id dari URL dengan sanitasi
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id === 0) {
    header('Location: dashboard.php');
    exit();
}

// ✅ Ambil data order dengan prepared statement + validasi kepemilikan
$stmt_order = mysqli_prepare($koneksi, "
    SELECT id, user_id, total_bayar, tanggal_transaksi, status, nama_penerima, no_telp, alamat, metode_pembayaran
    FROM orders 
    WHERE id = ? AND user_id = ?
");
$user_id = (int)($_SESSION['id'] ?? 0);
mysqli_stmt_bind_param($stmt_order, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);

if (!$result_order || mysqli_num_rows($result_order) === 0) {
    mysqli_stmt_close($stmt_order);
    header('Location: dashboard.php');
    exit();
}

$order = mysqli_fetch_assoc($result_order);
mysqli_stmt_close($stmt_order);

// ✅ Ambil detail order dengan prepared statement (fallback jika tabel belum ada)
$order_items = [];
$check_details = @mysqli_query($koneksi, "SELECT 1 FROM order_details LIMIT 1");

if ($check_details) {
    $stmt_details = mysqli_prepare($koneksi, "
        SELECT od.*, p.nama_produk, p.gambar 
        FROM order_details od 
        INNER JOIN products p ON od.product_id = p.id 
        WHERE od.order_id = ?
    ");
    mysqli_stmt_bind_param($stmt_details, "i", $order_id);
    mysqli_stmt_execute($stmt_details);
    $result_details = mysqli_stmt_get_result($stmt_details);
    
    if ($result_details) {
        while ($item = mysqli_fetch_assoc($result_details)) {
            $order_items[] = $item;
        }
    }
    mysqli_stmt_close($stmt_details);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pesanan Berhasil - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; min-height: 100vh; }
        
        /* Header */
        .header {
            width: 100%; height: 110px; background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            display: flex; align-items: center; justify-content: center; position: relative;
        }
        .header__logo { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); }
        .header__logo img { width: 180px; height: 80px; object-fit: contain; }
        .header__title { font-size: 40px; font-weight: 600; color: #000000; }
        
        /* Main Success Card */
        .success-card {
            width: 1000px; min-height: 600px; background: #ffffff;
            border-radius: 30px; box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.3);
            margin: 68px auto; position: relative; padding: 40px;
        }
        
        .success-icon {
            width: 100px; height: 100px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 34px auto 0;
        }
        .success-icon::after { content: '✓'; font-size: 64px; font-weight: 600; color: #ffffff; }
        
        .success-title {
            font-size: 40px; font-weight: 500; color: #000000;
            text-align: center; margin-top: 41px;
        }
        
        /* Order Items */
        .order-items { margin: 50px 0; }
        .order-item-card {
            width: 100%; min-height: 100px; background: #ffffff;
            border-radius: 5px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            margin: 0 auto 17px; display: flex; align-items: center; padding: 0 40px;
        }
        .order-item__image {
            width: 80px; height: 80px; background: #f3f4f6;
            border-radius: 10px; margin-right: 20px; overflow: hidden; flex-shrink: 0;
        }
        .order-item__image img { width: 100%; height: 100%; object-fit: cover; }
        .order-item__details { flex: 1; }
        .order-item__name { font-size: 18px; font-weight: 600; color: #000000; margin-bottom: 4px; }
        .order-item__qty { font-size: 14px; color: #6b7280; }
        .order-item__price { font-size: 18px; font-weight: 700; color: #000000; }
        .order-item__price span { color: #00d71c; font-weight: 600; }
        
        /* Back to Home */
        .back-home {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 24px; font-weight: 600; color: #000000;
            text-decoration: none; margin-top: 30px; transition: all 0.3s;
        }
        .back-home span { color: #1d4ed8; }
        .back-home:hover { transform: translateY(-3px); text-shadow: 0 4px 12px rgba(29, 78, 216, 0.3); }
        
        /* Order Info */
        .order-info {
            background: #f9fafb; border-radius: 10px; padding: 20px;
            margin: 30px auto; max-width: 600px; text-align: center;
        }
        .order-info__row {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #e5e7eb;
        }
        .order-info__row:last-child { border-bottom: none; }
        .order-info__label { font-size: 14px; color: #6b7280; }
        .order-info__value { font-size: 16px; font-weight: 600; color: #1f2937; }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .success-card, .order-item-card { width: 90%; height: auto; }
            .order-item__name { font-size: 16px; }
            .order-item__price { font-size: 16px; }
            .success-title { font-size: 28px; }
            .back-home { font-size: 18px; position: static; transform: none; display: block; text-align: center; margin: 30px auto; }
            .order-item-card { padding: 0 20px; flex-direction: column; align-items: flex-start; gap: 10px; }
            .order-item__image { margin-right: 0; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__logo">
            <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-25/dfhQ0S5HdV.png" alt="MasElektro Logo">
        </div>
        <h1 class="header__title">Pesanan Berhasil</h1>
    </header>

    <!-- Main Content -->
    <div class="success-card">
        <!-- Success Icon -->
        <div class="success-icon"></div>
        
        <!-- Success Title -->
        <h2 class="success-title">Pesanan Anda berhasil dibuat</h2>
        
        <!-- Order Info -->
        <div class="order-info">
            <div class="order-info__row">
                <span class="order-info__label">Order ID</span>
                <span class="order-info__value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="order-info__row">
                <span class="order-info__label">Tanggal</span>
                <span class="order-info__value"><?php echo date('d M Y, H:i', strtotime($order['tanggal_transaksi'])); ?></span>
            </div>
            <div class="order-info__row">
                <span class="order-info__label">Total Pembayaran</span>
                <span class="order-info__value" style="color: #10b981; font-size: 20px;">Rp. <?php echo number_format($order['total_bayar'], 0, ',', '.'); ?></span>
            </div>
            <div class="order-info__row">
                <span class="order-info__label">Status</span>
                <span class="order-info__value" style="color: #f59e0b;">⏳ <?php echo ucfirst($order['status']); ?></span>
            </div>
            <?php if (!empty($order['metode_pembayaran'])): ?>
            <div class="order-info__row">
                <span class="order-info__label">Metode Pembayaran</span>
                <span class="order-info__value"><?php echo htmlspecialchars(ucfirst($order['metode_pembayaran'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Items -->
        <?php if (!empty($order_items)): ?>
        <div class="order-items">
            <?php foreach ($order_items as $item): ?>
                <div class="order-item-card">
                    <div class="order-item__image">
                        <img src="../uploads/<?php echo htmlspecialchars($item['gambar'] ?? ''); ?>" 
                             alt="<?php echo htmlspecialchars($item['nama_produk']); ?>"
                             onerror="this.src='https://via.placeholder.com/80x80/2563eb/ffffff?text=No+Image'">
                    </div>
                    <div class="order-item__details">
                        <div class="order-item__name">
                            <?php echo htmlspecialchars($item['nama_produk']); ?>
                        </div>
                        <div class="order-item__qty">
                            Qty: <?php echo (int)$item['quantity']; ?>
                        </div>
                    </div>
                    <div class="order-item__price">
                        Rp. <span><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Back to Home -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="riwayat.php" class="back-home" style="margin-right: 20px;">
                <i class="fas fa-box"></i> Lihat Riwayat
            </a>
            <a href="dashboard.php" class="back-home">
                <i class="fas fa-home"></i> Kembali ke Home
            </a>
        </div>
    </div>

    <script>
        // Optional: Show success animation
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.querySelector('.success-icon');
            if (icon) {
                icon.style.animation = 'pulse 0.6s ease-in-out';
            }
        });
    </script>
</body>
</html>