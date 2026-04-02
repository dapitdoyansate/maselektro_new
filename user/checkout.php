<?php
/**
 * File: user/checkout.php
 * FIXED: Secure + Error Handling + No Loading Issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1); // ⚠️ Matikan di production, gunakan error_log()

session_start();
require_once '../config.php';

// Cek koneksi database
if (!$koneksi) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('❌ Koneksi database gagal. Silakan coba lagi.');
}

// Cek autentikasi user
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)($_SESSION['id'] ?? 0);
if ($user_id === 0) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data user dengan prepared statement
$stmt_user = mysqli_prepare($koneksi, "SELECT nama_lengkap, username, no_hp, alamat FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$data_user = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt_user);

$cart_items = [];
$total_bayar = 0;
$error_stok = null;

// Ambil cart items dengan prepared statement
$stmt_cart = mysqli_prepare($koneksi, "
    SELECT c.*, p.nama_produk, p.harga, p.gambar, p.stok
    FROM cart c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
mysqli_stmt_bind_param($stmt_cart, "i", $user_id);
mysqli_stmt_execute($stmt_cart);
$result_cart = mysqli_stmt_get_result($stmt_cart);

while ($item = mysqli_fetch_assoc($result_cart)) {
    if ($item['quantity'] > $item['stok']) {
        $error_stok = "Stok {$item['nama_produk']} tidak mencukupi!";
    }
    $item['subtotal'] = $item['harga'] * $item['quantity'];
    $total_bayar += $item['subtotal'];
    $cart_items[] = $item;
}
mysqli_stmt_close($stmt_cart);

if (empty($cart_items)) {
    header('Location: dashboard.php');
    exit();
}

$errorMessage = null;

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'm-banking');
    
    // Validasi input
    if (empty($nama_penerima) || empty($no_telp) || empty($alamat)) {
        $errorMessage = 'Semua field wajib diisi';
    } elseif ($error_stok) {
        $errorMessage = $error_stok;
    } else {
        mysqli_begin_transaction($koneksi);
        
        try {
            // ✅ Insert order dengan prepared statement
            $stmt_order = mysqli_prepare($koneksi, "
                INSERT INTO orders (user_id, total_bayar, tanggal_transaksi, status, nama_penerima, no_telp, alamat, metode_pembayaran) 
                VALUES (?, ?, NOW(), 'pending', ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt_order, "idssss", $user_id, $total_bayar, $nama_penerima, $no_telp, $alamat, $metode_pembayaran);
            
            if (!mysqli_stmt_execute($stmt_order)) {
                throw new Exception('Gagal menyimpan pesanan: ' . mysqli_stmt_error($stmt_order));
            }
            
            $order_id = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_order);
            
            // Insert order details (jika tabel ada)
            $check_details = @mysqli_query($koneksi, "SELECT 1 FROM order_details LIMIT 1");
            if ($check_details) {
                $stmt_detail = mysqli_prepare($koneksi, "
                    INSERT INTO order_details (order_id, product_id, quantity, harga_satuan, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_update_stok = mysqli_prepare($koneksi, "
                    UPDATE products SET stok = stok - ? WHERE id = ?
                ");
                
                foreach ($cart_items as $item) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    $harga = (float)$item['harga'];
                    $subtotal = (float)$item['subtotal'];
                    
                    mysqli_stmt_bind_param($stmt_detail, "iiiid", $order_id, $pid, $qty, $harga, $subtotal);
                    mysqli_stmt_execute($stmt_detail);
                    
                    mysqli_stmt_bind_param($stmt_update_stok, "ii", $qty, $pid);
                    mysqli_stmt_execute($stmt_update_stok);
                }
                mysqli_stmt_close($stmt_detail);
                mysqli_stmt_close($stmt_update_stok);
            }
            
            // Hapus cart
            $stmt_clear = mysqli_prepare($koneksi, "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt_clear, "i", $user_id);
            mysqli_stmt_execute($stmt_clear);
            mysqli_stmt_close($stmt_clear);
            
            mysqli_commit($koneksi);
            
            // ✅ Redirect dengan session flash message
            $_SESSION['success_message'] = 'Pesanan berhasil dibuat!';
            header("Location: checkout_success.php?order_id=" . (int)$order_id);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            error_log('Checkout error: ' . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --danger: #dc2626; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-600: #4b5563; --gray-800: #1f2937; --white: #ffffff;
            --shadow: 0 1px 3px rgba(0,0,0,0.1); --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-100); color: var(--gray-800); line-height: 1.6; }
        .header { background: var(--white); padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 100; }
        .header__logo { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; text-decoration: none; }
        .logo__icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; font-size: 20px; }
        .logo__text { font-size: 20px; font-weight: 700; color: var(--gray-800); }
        .logo__text span { color: var(--primary); }
        .header__title { font-size: 28px; font-weight: 700; color: var(--gray-800); margin-left: auto; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; display: grid; grid-template-columns: 1fr 400px; gap: 2rem; }
        .checkout-form { background: var(--white); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); }
        .form-section { margin-bottom: 2rem; }
        .form-section__title { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); display: flex; align-items: center; gap: 0.5rem; }
        .form-section__title i { color: var(--primary); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--gray-800); margin-bottom: 0.5rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .payment-methods { display: flex; flex-direction: column; gap: 0.75rem; }
        .payment-option { display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 2px solid var(--gray-200); border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .payment-option:hover, .payment-option.active { border-color: var(--primary); background: rgba(37, 99, 235, 0.05); }
        .payment-option input { width: auto; }
        .payment-option__icon { width: 40px; height: 40px; background: var(--gray-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .payment-option__label { font-size: 16px; font-weight: 500; color: var(--gray-800); }
        .payment-option.coming-soon { opacity: 0.6; cursor: not-allowed; }
        .order-summary { background: var(--white); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); height: fit-content; position: sticky; top: 100px; }
        .order-summary__title { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); }
        .order-items { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; max-height: 300px; overflow-y: auto; }
        .order-item { display: flex; gap: 1rem; padding: 0.75rem; background: var(--gray-100); border-radius: 10px; }
        .order-item__image { width: 60px; height: 60px; background: var(--gray-200); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .order-item__image img { width: 100%; height: 100%; object-fit: cover; }
        .order-item__details { flex: 1; }
        .order-item__name { font-size: 14px; font-weight: 600; color: var(--gray-800); }
        .order-item__qty { font-size: 12px; color: var(--gray-600); }
        .order-item__price { font-size: 14px; font-weight: 700; color: var(--primary); }
        .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200); }
        .summary-row.total { font-size: 20px; font-weight: 700; color: var(--primary); padding-top: 1rem; border-top: 2px solid var(--gray-200); }
        .checkout-btn { width: 100%; padding: 1rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); border: none; border-radius: 10px; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 1.5rem; }
        .checkout-btn:hover { transform: translateY(-2px); }
        .checkout-btn:disabled { background: var(--gray-300); cursor: not-allowed; }
        .alert { padding: 1rem 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
        .alert--error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert--success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <a href="dashboard.php" class="header__logo">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </a>
        <h1 class="header__title">Checkout</h1>
    </header>

    <main class="container">
        <form method="POST" action="" class="checkout-form" id="checkoutForm">
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert--success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>
            
            <section class="form-section">
                <h2 class="form-section__title"><i class="fas fa-user"></i> Data Pembeli</h2>
                <div class="form-group">
                    <label for="nama_penerima">Nama Penerima *</label>
                    <input type="text" id="nama_penerima" name="nama_penerima" value="<?php echo htmlspecialchars($data_user['nama_lengkap'] ?? $data_user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="no_telp">No. Telepon *</label>
                    <input type="tel" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($data_user['no_hp'] ?? ''); ?>" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat *</label>
                    <textarea id="alamat" name="alamat" required><?php echo htmlspecialchars($data_user['alamat'] ?? ''); ?></textarea>
                </div>
            </section>
            
            <section class="form-section">
                <h2 class="form-section__title"><i class="fas fa-credit-card"></i> Metode Pembayaran</h2>
                <div class="payment-methods">
                    <label class="payment-option active">
                        <input type="radio" name="metode_pembayaran" value="m-banking" checked>
                        <div class="payment-option__icon"><i class="fas fa-mobile-alt" style="color:var(--primary)"></i></div>
                        <span class="payment-option__label">M-Banking</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="metode_pembayaran" value="e-wallet">
                        <div class="payment-option__icon"><i class="fas fa-wallet" style="color:var(--success)"></i></div>
                        <span class="payment-option__label">E-Wallet</span>
                    </label>
                    <label class="payment-option coming-soon">
                        <input type="radio" name="metode_pembayaran" value="cod" disabled>
                        <div class="payment-option__icon"><i class="fas fa-truck" style="color:var(--gray-600)"></i></div>
                        <span class="payment-option__label">COD (Segera)</span>
                    </label>
                </div>
            </section>
            
            <button type="submit" name="checkout" class="checkout-btn" id="checkoutBtn">
                <i class="fas fa-lock"></i> Buat Pesanan Sekarang
            </button>
        </form>
        
        <aside class="order-summary">
            <h2 class="order-summary__title">📦 Ringkasan Pesanan</h2>
            <div class="order-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div class="order-item__image">
                            <img src="../uploads/<?php echo htmlspecialchars($item['gambar'] ?? ''); ?>" onerror="this.src='https://via.placeholder.com/60/2563eb/ffffff?text=No'">
                        </div>
                        <div class="order-item__details">
                            <div class="order-item__name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="order-item__qty">Qty: <?php echo (int)$item['quantity']; ?></div>
                        </div>
                        <div class="order-item__price">Rp. <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary-row"><span>Subtotal</span><span>Rp. <?php echo number_format($total_bayar, 0, ',', '.'); ?></span></div>
            <div class="summary-row total"><span>Total</span><span>Rp. <?php echo number_format($total_bayar, 0, ',', '.'); ?></span></div>
        </aside>
    </main>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option:not(.coming-soon)').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input').checked = true;
            });
        });

        // Form submit handling - FIX: re-enable button on error
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('checkoutBtn');
            const originalText = btn.innerHTML;
            
            // Disable button to prevent double submit
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            // Jika ada error server, form akan reload dan button akan di-enable ulang oleh browser
            // Tidak perlu preventDefault agar redirect PHP bisa berjalan
        });

        // Jika halaman dimuat dengan error message, pastikan button tidak disabled
        <?php if (isset($errorMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('checkoutBtn');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Buat Pesanan Sekarang';
        });
        <?php endif; ?>
    </script>
</body>
</html>