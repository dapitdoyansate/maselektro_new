<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

$uid = (int)$_SESSION['id'];
$nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$telp = isset($_POST['telp']) ? trim($_POST['telp']) : '';
$alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
$metode = isset($_POST['metode_pembayaran']) ? trim($_POST['metode_pembayaran']) : '';
$total = isset($_POST['total_bayar']) ? (float)$_POST['total_bayar'] : 0;

// Validasi
if(empty($nama) || empty($alamat) || empty($metode)) {
    die("<div style='text-align:center;padding:50px;font-family:Arial;background:#fee2e2;border-radius:12px;margin:50px auto;max-width:600px'><h2 style='color:#dc2626'>❌ Data Tidak Lengkap</h2><p>Silakan lengkapi semua data yang diperlukan.</p><a href='checkout.php' style='display:inline-block;padding:12px 24px;background:#dc2626;color:#fff;text-decoration:none;border-radius:8px;margin-top:20px'>Kembali</a></div>");
}

// Escape data
$nama_esc = mysqli_real_escape_string($koneksi, $nama);
$telp_esc = mysqli_real_escape_string($koneksi, $telp);
$alamat_esc = mysqli_real_escape_string($koneksi, $alamat);
$metode_esc = mysqli_real_escape_string($koneksi, $metode);

// Insert order
$query = "INSERT INTO orders (user_id, total_bayar, tanggal_transaksi, status, nama_penerima, no_telp, alamat, metode_pembayaran) 
          VALUES ($uid, $total, NOW(), 'pending_payment', '$nama_esc', '$telp_esc', '$alamat_esc', '$metode_esc')";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("<div style='text-align:center;padding:50px;font-family:Arial;background:#fee2e2;border-radius:12px;margin:50px auto;max-width:600px'><h2 style='color:#dc2626'>❌ Error Database</h2><p>" . mysqli_error($koneksi) . "</p></div>");
}

$oid = mysqli_insert_id($koneksi);

// Generate VA Number (Hanya untuk Bank)
if (stripos($metode, 'E-Wallet') === false) {
    preg_match('/\(([^)]+)\)/', $metode, $matches);
    $bank = $matches[1] ?? 'BCA';
    $codes = ['BCA' => '70015', 'Mandiri' => '88508', 'BNI' => '88810', 'BRI' => '20017'];
    $va_code = $codes[$bank] ?? '70015';
    $va_number = $va_code . str_pad($oid, 5, '0', STR_PAD_LEFT);
    
    mysqli_query($koneksi, "UPDATE orders SET virtual_account = '$va_number' WHERE id = $oid");
}

// Pindahkan cart ke order_details
$cart_items = mysqli_query($koneksi, "SELECT c.*, p.harga, p.stok FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $uid");

while($item = mysqli_fetch_assoc($cart_items)) {
    $subtotal = $item['quantity'] * $item['harga'];
    
    mysqli_query($koneksi, "INSERT INTO order_details (order_id, product_id, quantity, harga_satuan, subtotal) 
                            VALUES ($oid, {$item['product_id']}, {$item['quantity']}, {$item['harga']}, $subtotal)");
    
    mysqli_query($koneksi, "UPDATE products SET stok = stok - {$item['quantity']} WHERE id = {$item['product_id']}");
}

mysqli_query($koneksi, "DELETE FROM cart WHERE user_id = $uid");

$is_ewallet = (stripos($metode, 'E-Wallet') !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Berhasil - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { max-width: 700px; width: 100%; }
        
        .success-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pop 0.6s ease;
        }
        
        @keyframes pop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .success-icon i { font-size: 40px; }
        .success-header h1 { font-size: 28px; margin-bottom: 10px; }
        .success-header p { opacity: 0.9; }
        
        .order-details { padding: 30px; }
        
        .order-id-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .order-id-box .label { font-size: 13px; color: #059669; margin-bottom: 5px; }
        .order-id-box .value { font-size: 28px; font-weight: 800; color: #059669; font-family: monospace; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #2563eb;
        }
        
        .info-box .label { font-size: 12px; color: #6b7280; margin-bottom: 5px; }
        .info-box .value { font-size: 15px; font-weight: 600; color: #1f2937; }
        
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: <?php echo $is_ewallet ? '#dbeafe' : '#fef3c7'; ?>;
            color: <?php echo $is_ewallet ? '#1e40af' : '#92400e'; ?>;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            box-shadow: 0 4px 15px rgba(37,99,235,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37,99,235,0.4);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        @media(max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1>Order Berhasil!</h1>
                <p>Terima kasih telah berbelanja di MasElektro</p>
            </div>
            
            <div class="order-details">
                <div class="order-id-box">
                    <div class="label">Nomor Order</div>
                    <div class="value">#<?php echo str_pad($oid, 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                
                <div class="info-grid">
                    <div class="info-box">
                        <div class="label">Total Pembayaran</div>
                        <div class="value" style="color: #2563eb">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="label">Metode Pembayaran</div>
                        <div class="value"><?php echo htmlspecialchars($metode); ?></div>
                        <div class="payment-badge">
                            <i class="fas fa-<?php echo $is_ewallet ? 'qrcode' : 'university'; ?>"></i>
                            <?php echo $is_ewallet ? 'E-Wallet' : 'Transfer Bank'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="info-box" style="border-left-color: #10b981; margin-bottom: 0;">
                    <div class="label">Penerima</div>
                    <div class="value"><?php echo htmlspecialchars($nama); ?></div>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 5px;">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($telp); ?><br>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($alamat); ?>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="bill.php?order_id=<?php echo $oid; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Lanjut ke Pembayaran
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>