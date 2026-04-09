<?php
// File: user/checkout_success.php
// TIDAK ADA SESSION CHECK - LANGSUNG TAMPIL

require_once '../config.php';

// Ambil order_id dari URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id == 0) {
    echo "<h1>Order ID tidak valid</h1>";
    exit;
}

// Ambil data order
$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM orders WHERE id = $order_id"));

if (!$order) {
    echo "<h1>Pesanan tidak ditemukan</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pesanan Berhasil</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f9ff; padding: 40px; text-align: center; }
        .box { background: white; max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .icon { font-size: 80px; color: #10b981; margin-bottom: 20px; }
        h1 { color: #1f2937; margin-bottom: 10px; }
        .info { background: #f9fafb; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: left; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; margin: 5px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">✅</div>
        <h1>Pesanan Berhasil!</h1>
        <p>Terima kasih telah berbelanja</p>
        
        <div class="info">
            <div class="row"><span>Order ID:</span><strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></div>
            <div class="row"><span>Total:</span><strong>Rp. <?php echo number_format($order['total_bayar'], 0, ',', '.'); ?></strong></div>
            <div class="row"><span>Status:</span><strong><?php echo ucfirst($order['status']); ?></strong></div>
        </div>
        
        <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
    </div>
</body>
</html>