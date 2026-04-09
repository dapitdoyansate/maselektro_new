<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login'])) { header('Location: ../auth/login.php'); exit(); }

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id === 0) { header('Location: dashboard.php'); exit(); }

$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM orders WHERE id = $order_id"));
if (!$order || $order['user_id'] != $_SESSION['id']) { header('Location: dashboard.php'); exit(); }

$metode = $order['metode_pembayaran'] ?? '';
$is_ewallet = (stripos($metode, 'E-Wallet') !== false);

if ($is_ewallet) {
    $amount = number_format($order['total_bayar'], 0, '', '');
    // Generate QR Code (Tanpa label QRIS di tengah)
    $qr_data = "00020101021226" . strlen("ID.CO.MASELEKTRO.ORDER{$order_id}") . "0016ID.CO.MASELEKTRO0120ORDER{$order_id}52045812530336054{$amount}5802ID5912MasElektro6008Jakarta62070303A016304";
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qr_data);
}

preg_match('/\(([^)]+)\)/', $metode, $matches);
$bank_name = $matches[1] ?? 'BCA';
$expired_time = time() + (30 * 60);

$items = [];
$res = mysqli_query($koneksi, "SELECT od.*, p.nama_produk FROM order_details od JOIN products p ON od.product_id=p.id WHERE od.order_id=$order_id");
while($r = mysqli_fetch_assoc($res)) $items[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --p: #2563eb; --s: #10b981; --w: #f59e0b; --g1: #f3f4f6; --g2: #e5e7eb; --g6: #4b5563; --g8: #1f2937; --sh: 0 4px 6px -1px rgba(0,0,0,0.1); --r: 12px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--g1); color: var(--g8); padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        .header { text-align: center; padding: 2rem; background: linear-gradient(135deg, var(--p), #1d4ed8); color: #fff; border-radius: 16px; margin-bottom: 2rem; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        
        .card { background: #fff; border-radius: var(--r); padding: 25px; margin-bottom: 20px; box-shadow: var(--sh); }
        .card-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--g8); display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--g1); padding-bottom: 15px; }
        .card-title i { color: var(--p); }
        
        .timer-box { background: #fffbeb; border: 2px solid var(--w); border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px; }
        .timer-label { font-size: 14px; color: #92400e; margin-bottom: 5px; font-weight: 600; }
        .timer-countdown { font-size: 36px; font-weight: 800; color: #92400e; font-family: monospace; }
        
        /* ✅ QRIS LAYOUT BARU */
        .qris-container {
            background: #ffffff;
            border: 1px solid var(--g2);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .qris-wrapper {
            display: inline-block;
            padding: 20px;
            background: #fff;
            border: 2px dashed var(--p);
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
        }
        .qris-wrapper img {
            width: 250px;
            height: 250px;
            display: block;
        }
        .qris-total {
            font-size: 28px;
            font-weight: 800;
            color: var(--p);
            margin-top: 10px;
            padding: 12px 25px;
            background: var(--g1);
            border-radius: 10px;
            display: inline-block;
        }
        
        .bank-info { background: #eff6ff; border-left: 4px solid var(--p); padding: 20px; border-radius: 8px; }
        .bank-info strong { color: var(--p); }
        
        .btn { width: 100%; padding: 15px; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-success { background: var(--s); color: #fff; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); }
        .btn-back { background: var(--g2); color: var(--g8); }
        
        .si { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--g2); }
        .total-box { background: var(--g1); padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; }
        .total-amount { font-size: 28px; font-weight: 800; color: var(--p); }
        
        .step-list { text-align: left; margin: 15px 0 0 20px; line-height: 1.6; color: var(--g6); }
        .step-list li { margin-bottom: 5px; }
        
        @media(max-width: 600px) { .qris-wrapper img { width: 220px; height: 220px; } .qris-total { font-size: 22px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Pembayaran</h1>
            <p>Selesaikan pembayaran sebelum waktu habis</p>
        </div>
        
        <!-- TIMER -->
        <div class="timer-box">
            <div class="timer-label"><i class="fas fa-clock"></i> Batas Pembayaran</div>
            <div class="timer-countdown" id="countdown">30:00</div>
        </div>
        
        <?php if($is_ewallet): ?>
        <!-- QRIS SECTION -->
        <div class="card">
            <div class="card-title"><i class="fas fa-qrcode"></i> Scan QRIS Pembayaran</div>
            
            <div class="qris-container">
                <div class="qris-wrapper">
                    <img src="<?php echo $qr_url; ?>" alt="QR Code Pembayaran">
                </div>
                <div>
                    <div style="font-size:13px; color:var(--g6); margin-bottom:8px;">Total Pembayaran</div>
                    <div class="qris-total">Rp <?php echo number_format($order['total_bayar'], 0, ',', '.'); ?></div>
                </div>
            </div>
            
            <div class="bank-info" style="margin-top: 20px; background: #f0fdf4; border-color: var(--s);">
                <strong style="color:var(--s)"><i class="fas fa-info-circle"></i> Cara Bayar:</strong>
                <ol class="step-list">
                    <li>Buka aplikasi e-wallet (Dana, GoPay, OVO, ShopeePay)</li>
                    <li>Pilih menu <strong>"Scan QR"</strong></li>
                    <li>Arahkan kamera ke kode di atas</li>
                    <li>Konfirmasi pembayaran sesuai nominal</li>
                </ol>
            </div>
        </div>
        <?php else: ?>
        <!-- BANK TRANSFER SECTION -->
        <div class="card">
            <div class="card-title"><i class="fas fa-university"></i> Transfer Bank</div>
            <div class="bank-info">
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 13px; color: var(--g6);">Bank</div>
                    <div style="font-size: 20px; font-weight: 800; color: var(--p);"><?php echo htmlspecialchars($bank_name); ?></div>
                </div>
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 13px; color: var(--g6);">Nomor Virtual Account</div>
                    <div style="font-size: 24px; font-weight: 800; font-family: monospace; letter-spacing: 2px; cursor: pointer;" onclick="navigator.clipboard.writeText(this.textContent); alert('✅ Tersalin!')">
                        <?php echo $order['virtual_account'] ?? '70015' . str_pad($order_id, 5, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div style="font-size: 12px; color: var(--g6); margin-top: 5px;">Klik nomor di atas untuk menyalin</div>
                </div>
                <div>
                    <div style="font-size: 13px; color: var(--g6);">Jumlah Transfer</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--p);">Rp <?php echo number_format($order['total_bayar'], 0, ',', '.'); ?></div>
                    <div style="font-size: 12px; color: var(--w); margin-top: 5px;"><i class="fas fa-exclamation-triangle"></i> Transfer SESUAI nominal!</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- DETAIL PESANAN -->
        <div class="card">
            <div class="card-title"><i class="fas fa-shopping-bag"></i> Detail Pesanan</div>
            <?php foreach($items as $item): ?>
            <div class="si">
                <div><strong><?php echo htmlspecialchars($item['nama_produk']); ?></strong><br><small style="color:var(--g6)">Qty: <?php echo $item['quantity']; ?></small></div>
                <div style="font-weight:700">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
            </div>
            <?php endforeach; ?>
            <div class="total-box">
                <div style="font-size: 14px; color: var(--g6); margin-bottom: 5px;">Total Pembayaran</div>
                <div class="total-amount">Rp <?php echo number_format($order['total_bayar'], 0, ',', '.'); ?></div>
            </div>
        </div>
        
        <button class="btn btn-success" id="btnPay" onclick="confirmPayment()">
            <i class="fas fa-check-circle"></i> Saya Sudah Bayar
        </button>
        <button class="btn btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-home"></i> Kembali ke Dashboard
        </button>
    </div>

    <script>
        const expiredTime = <?php echo $expired_time; ?>;
        let timerInterval;
        updateCountdown();
        timerInterval = setInterval(updateCountdown, 1000);
        
        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = expiredTime - now;
            if (remaining <= 0) {
                document.getElementById('countdown').textContent = '00:00';
                document.getElementById('btnPay').disabled = true;
                clearInterval(timerInterval);
                return;
            }
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            document.getElementById('countdown').textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        
        function confirmPayment() {
            if (!confirm('Apakah Anda sudah menyelesaikan pembayaran?')) return;
            const btn = document.getElementById('btnPay');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';
            fetch('confirm_payment.php', { method: 'POST', body: 'order_id=<?php echo $order_id; ?>' })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.href = 'checkout_success.php?order_id=<?php echo $order_id; ?>';
                else { alert('❌ ' + (data.message || 'Gagal')); btn.disabled = false; btn.innerHTML = 'Saya Sudah Bayar'; }
            });
        }
    </script>
</body>
</html>