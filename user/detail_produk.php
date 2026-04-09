<?php
session_start();
require_once '../config.php';

// ✅ FIX: Izinkan guest view (tidak redirect ke login)
$is_logged_in = isset($_SESSION['login']) && $_SESSION['role'] === 'user';
$user_id = $is_logged_in ? (int)$_SESSION['id'] : 0;

// Ambil product_id dari URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) { header('Location: dashboard.php'); exit(); }

// Ambil data produk (bisa diakses guest)
$product = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM products WHERE id = $product_id"));
if (!$product) { header('Location: dashboard.php'); exit(); }

// Cart count HANYA untuk user login
$cart_count = 0;
if ($is_logged_in) {
    $cart_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(quantity),0) as total FROM cart WHERE user_id = $user_id"))['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['nama_produk']); ?> - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#2563eb;--primary-dark:#1d4ed8;--success:#10b981;--danger:#dc2626;--gray-100:#f3f4f6;--gray-200:#e5e7eb;--gray-300:#d1d5db;--gray-600:#4b5563;--gray-800:#1f2937;--white:#ffffff;--shadow:0 1px 3px rgba(0,0,0,0.1);--shadow-lg:0 10px 15px rgba(0,0,0,0.1);--radius:12px}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
        body{background:var(--gray-100);color:var(--gray-800);line-height:1.6}
        
        /* Header */
        .header{background:var(--white);padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--shadow);position:sticky;top:0;z-index:100}
        .header__logo{display:flex;align-items:center;gap:0.75rem;cursor:pointer}
        .logo__icon{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:20px}
        .logo__text{font-size:20px;font-weight:700;color:var(--gray-800)}.logo__text span{color:var(--primary)}
        .header__actions{display:flex;align-items:center;gap:1rem}
        .header__cart{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;background:var(--gray-100);border:2px solid var(--gray-200);border-radius:var(--radius);cursor:pointer;transition:all 0.3s;text-decoration:none;color:var(--gray-600);font-weight:500;position:relative}
        .header__cart:hover{border-color:var(--primary);color:var(--primary)}
        .header__cart .badge{position:absolute;top:-8px;right:-8px;background:var(--danger);color:var(--white);font-size:12px;font-weight:600;padding:2px 8px;border-radius:12px;min-width:20px;text-align:center}
        .header__profile{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);border-radius:var(--radius);cursor:pointer;transition:all 0.3s;text-decoration:none;font-weight:500}
        .header__logout{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;background:#fef2f2;color:var(--danger);border:2px solid #fecaca;border-radius:var(--radius);cursor:pointer;transition:all 0.3s;text-decoration:none;font-weight:500}
        
        /* Main Content */
        .container{max-width:1200px;margin:2rem auto;padding:0 2rem}
        .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--primary);text-decoration:none;margin-bottom:2rem;font-weight:600}
        .back-link:hover{gap:12px}
        
        /* Product Detail Grid */
        .product-detail{background:var(--white);border-radius:var(--radius);padding:2.5rem;box-shadow:var(--shadow);display:grid;grid-template-columns:1fr 1.2fr;gap:3rem}
        .img-section{text-align:center}
        .img-main{width:100%;max-width:450px;height:400px;object-fit:contain;background:var(--gray-100);border-radius:var(--radius);margin-bottom:1rem}
        .stock-badge{display:inline-block;padding:0.5rem 1.25rem;background:var(--success);color:var(--white);border-radius:20px;font-size:14px;font-weight:600}
        .stock-badge.low{background:var(--danger)}
        .info-section h1{font-size:28px;font-weight:700;margin-bottom:1rem;color:var(--gray-800)}
        .price{font-size:32px;font-weight:800;color:var(--primary);margin-bottom:1.5rem}
        .desc{background:var(--gray-100);padding:1.5rem;border-radius:var(--radius);margin-bottom:1.5rem}
        .desc h3{margin-bottom:0.75rem;color:var(--gray-800);display:flex;align-items:center;gap:8px}
        .desc p{color:var(--gray-600);line-height:1.8;white-space:pre-wrap}
        .specs{margin-bottom:1.5rem}
        .specs h3{margin-bottom:1rem;color:var(--gray-800);display:flex;align-items:center;gap:8px}
        .spec-row{display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid var(--gray-200)}
        .spec-row:last-child{border-bottom:none}
        .spec-label{color:var(--gray-600)}
        .spec-value{font-weight:600;color:var(--gray-800)}
        .actions{display:flex;gap:1rem;margin-top:2rem;align-items:center}
        .qty-selector{display:flex;align-items:center;gap:0.5rem;background:var(--gray-100);padding:0.5rem;border-radius:var(--radius)}
        .qty-btn{width:40px;height:40px;border:none;background:var(--white);border-radius:8px;cursor:pointer;font-size:18px;color:var(--primary);transition:all 0.3s}
        .qty-btn:hover{background:var(--primary);color:var(--white)}
        .qty-input{width:50px;text-align:center;font-weight:700;font-size:16px;border:none;background:transparent}
        .btn-add{flex:1;padding:1rem 2rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);border:none;border-radius:var(--radius);font-size:16px;font-weight:700;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:8px}
        .btn-add:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
        .btn-add:disabled{background:var(--gray-300);cursor:not-allowed;transform:none}
        .toast{position:fixed;bottom:20px;right:20px;background:var(--white);padding:1rem 1.5rem;border-radius:var(--radius);box-shadow:var(--shadow-lg);display:none;align-items:center;gap:0.75rem;z-index:1000;border-left:4px solid var(--success)}
        .toast.show{display:flex;animation:slideIn 0.3s ease}
        .toast.error{border-left-color:var(--danger)}
        .toast i{font-size:20px}
        .toast.success i{color:var(--success)}
        .toast.error i{color:var(--danger)}
        @keyframes slideIn{from{transform:translateX(400px)}to{transform:translateX(0)}}
        @media(max-width:900px){.product-detail{grid-template-columns:1fr}.img-main{height:300px}.actions{flex-direction:column}.price{font-size:24px}}
        @media(max-width:768px){.header{flex-wrap:wrap;gap:1rem;padding:1rem}.header__profile span,.header__cart span,.header__logout span{display:none}}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__logo" onclick="window.location.href='dashboard.php'">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </div>
        <div class="header__actions">
            <?php if ($is_logged_in): ?>
                <!-- ✅ Tampilan untuk USER LOGIN -->
                <a href="keranjang.php" class="header__cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                    <?php if($cart_count > 0): ?><span class="badge"><?php echo $cart_count; ?></span><?php endif; ?>
                </a>
                <a href="profile.php" class="header__profile">
                    <i class="fas fa-user-circle"></i>
                    <span>Profil Customer</span>
                </a>
                <a href="../auth/logout.php" class="header__logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <!-- ✅ Tampilan untuk GUEST (belum login) -->
                <a href="keranjang.php" class="header__cart" onclick="requireLogin(event); return false;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                </a>
                <a href="../auth/login.php" class="header__profile">
                    <i class="fas fa-user-circle"></i>
                    <span>Login</span>
                </a>
                <a href="../auth/register.php" class="btn" style="background:var(--success);color:white;padding:0.5rem 1rem;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Katalog</a>
        
        <div class="product-detail">
            <!-- Image Section -->
            <div class="img-section">
                <?php if(!empty($product['gambar']) && file_exists('../uploads/'.$product['gambar'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($product['gambar']); ?>" alt="<?php echo htmlspecialchars($product['nama_produk']); ?>" class="img-main">
                <?php else: ?>
                    <div class="img-main" style="display:flex;align-items:center;justify-content:center;color:var(--gray-300);font-size:60px"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <div class="stock-badge <?php echo $product['stok'] < 5 ? 'low' : ''; ?>">
                    <i class="fas fa-box"></i> Stok: <?php echo (int)$product['stok']; ?> unit
                </div>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <h1><?php echo htmlspecialchars($product['nama_produk']); ?></h1>
                <div class="price">Rp. <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                
                <div class="desc">
                    <h3><i class="fas fa-info-circle" style="color:var(--primary)"></i> Deskripsi Produk</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['deskripsi'] ?? 'Tidak ada deskripsi tersedia.')); ?></p>
                </div>

                <div class="specs">
                    <h3><i class="fas fa-list" style="color:var(--primary)"></i> Spesifikasi</h3>
                    <div class="spec-row"><span class="spec-label">Kategori</span><span class="spec-value">Elektronik</span></div>
                    <div class="spec-row"><span class="spec-label">Kondisi</span><span class="spec-value">Baru</span></div>
                    <div class="spec-row"><span class="spec-label">Garansi</span><span class="spec-value">Resmi</span></div>
                    <div class="spec-row"><span class="spec-label">Pengiriman</span><span class="spec-value">Seluruh Indonesia</span></div>
                </div>

                <form id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="actions">
                        <div class="qty-selector">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                            <input type="number" id="qty" name="quantity" value="1" min="1" max="<?php echo $product['stok']; ?>" class="qty-input" readonly>
                            <button type="button" class="qty-btn" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                        </div>
                        <!-- ✅ FIX: Tombol dengan cek login -->
                        <button type="submit" class="btn-add" 
                                <?php echo (!$is_logged_in || $product['stok'] == 0) ? 'disabled' : ''; ?>
                                onclick="<?php echo !$is_logged_in ? 'requireLogin(event); return false;' : ''; ?>">
                            <i class="fas fa-cart-plus"></i> 
                            <?php 
                            if (!$is_logged_in) echo 'Login untuk Beli';
                            elseif ($product['stok'] == 0) echo 'Stok Habis';
                            else echo 'Tambah ke Keranjang'; 
                            ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMsg"></span>
    </div>

    <script>
        // ✅ FIX: Function untuk meminta login saat guest
        function requireLogin(e) {
            if (e) e.preventDefault();
            if (confirm('🔐 Anda harus login terlebih dahulu untuk menambahkan ke keranjang.\n\nKlik OK untuk login, atau Cancel untuk tetap browsing.')) {
                const currentUrl = window.location.href;
                window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(currentUrl);
            }
        }

        // Change quantity
        function changeQty(change) {
            const input = document.getElementById('qty');
            let newVal = parseInt(input.value) + change;
            const max = parseInt(input.max);
            if (newVal < 1) newVal = 1;
            if (newVal > max) newVal = max;
            input.value = newVal;
        }

        // Add to cart via AJAX
        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn-add');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', this.querySelector('[name="product_id"]').value);
            
            fetch('dashboard.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                // ✅ FIX: Handle jika butuh login
                if (data.require_login) {
                    requireLogin(null);
                    return;
                }
                
                if (data.success) {
                    showToast(data.message, 'success');
                    const badge = document.querySelector('.header__cart .badge');
                    if (badge) badge.textContent = data.cart_count;
                    else {
                        const cartIcon = document.querySelector('.header__cart');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge';
                        newBadge.textContent = data.cart_count;
                        cartIcon.appendChild(newBadge);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                showToast('❌ Terjadi kesalahan', 'error');
            });
        });

        // Show toast
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMsg');
            toastMsg.textContent = msg;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>