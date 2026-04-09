<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php'); exit();
}

$uid = (int)$_SESSION['id'];
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_lengkap, no_hp, alamat FROM users WHERE id=$uid"));

$res_items = mysqli_query($koneksi, "SELECT c.quantity, p.nama_produk, p.harga FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=$uid");
$items = [];
$total = 0;

while($row = mysqli_fetch_assoc($res_items)) {
    $row['subtotal'] = $row['harga'] * $row['quantity'];
    $total += $row['subtotal'];
    $items[] = $row;
}

if(empty($items)) { header('Location: keranjang.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --p: #2563eb; --pd: #1d4ed8; --g1: #f3f4f6; --g2: #e5e7eb; --g6: #4b5563; --g8: #1f2937; --sh: 0 4px 6px -1px rgba(0,0,0,0.1); --r: 12px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--g1); color: var(--g8); padding: 20px; }
        .wrap { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; }
        .box { background: #fff; padding: 25px; border-radius: var(--r); box-shadow: var(--sh); }
        h2 { margin-bottom: 20px; color: var(--g8); font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--g1); padding-bottom: 15px; }
        h2 i { color: var(--p); }
        .fg { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: var(--g6); }
        input, textarea { width: 100%; padding: 12px; border: 2px solid var(--g2); border-radius: 8px; font-size: 14px; transition: 0.3s; }
        input:focus, textarea:focus { border-color: var(--p); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        /* ✅ PRO PAYMENT SELECTION */
        .pay-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .pay-card { border: 2px solid var(--g2); border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 8px; }
        .pay-card:hover { border-color: var(--p); background: #eff6ff; transform: translateY(-2px); }
        .pay-card.active { border-color: var(--p); background: #eff6ff; box-shadow: 0 4px 12px rgba(37,99,235,0.15); }
        .pay-card i { font-size: 24px; color: var(--p); }
        .pay-card .title { font-weight: 700; font-size: 15px; }
        .pay-card .desc { font-size: 11px; color: var(--g6); }
        .pay-card input { display: none; }
        
        /* Sub-options */
        .pay-sub { display: none; background: var(--g1); padding: 15px; border-radius: 12px; margin-bottom: 20px; animation: fadeIn 0.3s; }
        .pay-sub.show { display: block; }
        .pay-sub h4 { font-size: 14px; margin-bottom: 10px; color: var(--g6); }
        .sub-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .sub-item { padding: 8px 15px; background: #fff; border: 1px solid var(--g2); border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 500; transition: 0.2s; }
        .sub-item:hover { border-color: var(--p); color: var(--p); }
        .sub-item.active { background: var(--p); color: #fff; border-color: var(--p); }
        
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, var(--p), var(--pd)); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(37,99,235,0.3); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,99,235,0.4); }
        .si { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--g2); font-size: 14px; }
        .tot { font-size: 24px; font-weight: 800; color: var(--p); margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--g2); display: flex; justify-content: space-between; }
        .back { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: var(--g6); text-decoration: none; font-weight: 600; transition: 0.2s; }
        .back:hover { color: var(--p); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @media(max-width: 768px) { .wrap { grid-template-columns: 1fr; } .pay-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div style="max-width: 1000px; margin: 0 auto;">
    <a href="keranjang.php" class="back"><i class="fas fa-arrow-left"></i> Kembali ke Keranjang</a>
</div>

<div class="wrap">
    <!-- FORM DATA -->
    <div class="box">
        <h2><i class="fas fa-truck-fast"></i> Informasi Pengiriman</h2>
        <form method="POST" action="process_checkout.php" id="checkoutForm">
            
            <div class="fg">
                <label>Nama Penerima</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>" required>
            </div>
            <div class="fg">
                <label>No. Telepon</label>
                <input type="tel" name="telp" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" required>
            </div>
            <div class="fg">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="3" required><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
            </div>
            
            <h2 style="margin-top: 30px;"><i class="fas fa-wallet"></i> Metode Pembayaran</h2>
            
            <!-- ✅ CARD SELECTION -->
            <div class="pay-grid">
                <label class="pay-card active" onclick="selectType('bank', this)">
                    <input type="radio" name="type" value="bank" checked>
                    <i class="fas fa-university"></i>
                    <div class="title">Transfer Bank</div>
                    <div class="desc">BCA, Mandiri, BNI, BRI</div>
                </label>
                <label class="pay-card" onclick="selectType('ewallet', this)">
                    <input type="radio" name="type" value="ewallet">
                    <i class="fas fa-qrcode"></i>
                    <div class="title">E-Wallet / QRIS</div>
                    <div class="desc">Dana, GoPay, OVO, ShopeePay</div>
                </label>
            </div>
            
            <!-- Sub Options: Bank -->
            <div class="pay-sub show" id="sub-bank">
                <h4>Pilih Bank:</h4>
                <div class="sub-list">
                    <div class="sub-item active" onclick="selectMethod('M-Banking (BCA)', this)">BCA</div>
                    <div class="sub-item" onclick="selectMethod('M-Banking (Mandiri)', this)">Mandiri</div>
                    <div class="sub-item" onclick="selectMethod('M-Banking (BNI)', this)">BNI</div>
                    <div class="sub-item" onclick="selectMethod('M-Banking (BRI)', this)">BRI</div>
                </div>
            </div>
            
            <!-- Sub Options: E-Wallet -->
            <div class="pay-sub" id="sub-ewallet">
                <h4>Pilih E-Wallet:</h4>
                <div class="sub-list">
                    <div class="sub-item" onclick="selectMethod('E-Wallet (Dana)', this)">Dana</div>
                    <div class="sub-item" onclick="selectMethod('E-Wallet (GoPay)', this)">GoPay</div>
                    <div class="sub-item" onclick="selectMethod('E-Wallet (OVO)', this)">OVO</div>
                    <div class="sub-item" onclick="selectMethod('E-Wallet (ShopeePay)', this)">ShopeePay</div>
                </div>
            </div>
            
            <input type="hidden" name="metode_pembayaran" id="metode_lengkap" value="M-Banking (BCA)">
            <input type="hidden" name="total_bayar" value="<?php echo $total; ?>">
            
            <button type="submit" class="btn"><i class="fas fa-lock"></i> Konfirmasi & Bayar Sekarang</button>
        </form>
    </div>

    <!-- SUMMARY -->
    <div class="box" style="height: fit-content;">
        <h2><i class="fas fa-receipt"></i> Ringkasan Pesanan</h2>
        <?php foreach($items as $i): ?>
        <div class="si">
            <div><strong><?php echo htmlspecialchars($i['nama_produk']); ?></strong><br><small style="color:var(--g6)">Qty: <?php echo $i['quantity']; ?></small></div>
            <div style="font-weight:700">Rp <?php echo number_format($i['subtotal'],0,',','.'); ?></div>
        </div>
        <?php endforeach; ?>
        <div class="tot">
            <span>Total Bayar</span>
            <span>Rp <?php echo number_format($total,0,',','.'); ?></span>
        </div>
    </div>
</div>

<script>
function selectType(type, el) {
    // Update cards
    document.querySelectorAll('.pay-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    
    // Show sub options
    document.querySelectorAll('.pay-sub').forEach(s => s.classList.remove('show'));
    document.getElementById('sub-' + type).classList.add('show');
    
    // Reset selected method to first item in that group
    const firstItem = document.querySelector('#sub-' + type + ' .sub-item');
    selectMethod(firstItem.textContent === 'BCA' ? 'M-Banking (BCA)' : 'E-Wallet (Dana)', firstItem);
}

function selectMethod(value, el) {
    document.querySelectorAll('.sub-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('metode_lengkap').value = value;
}
</script>
</body>
</html>