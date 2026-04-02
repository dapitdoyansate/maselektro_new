<?php
/**
 * File: user/dashboard.php
 * Deskripsi: Halaman dashboard/katalog produk untuk user
 * Fitur: Add to cart dengan notifikasi + Search produk
 */

session_start();
require_once '../config.php';

// Cek autentikasi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil user_id dari session
$user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($user_id === 0) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data user
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$user_id'");
$data_user = mysqli_fetch_assoc($query_user);

// Hitung cart count (dengan fallback aman)
$cart_count = 0;
$check_cart = @mysqli_query($koneksi, "SELECT 1 FROM cart LIMIT 1");
if ($check_cart) {
    $q_cart = mysqli_query($koneksi, "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = '$user_id'");
    if ($q_cart) {
        $cart_data = mysqli_fetch_assoc($q_cart);
        $cart_count = (int)$cart_data['total'];
    }
}

// Handle AJAX Add to Cart
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    header('Content-Type: application/json');
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($product_id > 0) {
        // Cek stok produk
        $check_stock = mysqli_query($koneksi, "SELECT stok, nama_produk FROM products WHERE id = '$product_id'");
        $product = mysqli_fetch_assoc($check_stock);
        
        if ($product) {
            // Cek apakah sudah ada di cart
            $check_cart_item = mysqli_query($koneksi, "SELECT quantity FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'");
            
            if (mysqli_num_rows($check_cart_item) > 0) {
                // Update quantity
                mysqli_query($koneksi, "UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = '$product_id'");
            } else {
                // Insert baru
                mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)");
            }
            
            // Hitung ulang cart count
            $new_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = '$user_id'"))['total'];
            
            echo json_encode([
                'success' => true,
                'message' => '✅ ' . $product['nama_produk'] . ' ditambahkan ke keranjang!',
                'cart_count' => (int)$new_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Produk tidak ditemukan']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ Invalid product']);
    }
    exit();
}

// Handle Search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = $search_query ? "WHERE nama_produk LIKE '%$search_query%' OR deskripsi LIKE '%$search_query%'" : '';

// Ambil produk dari database
$query_produk = mysqli_query($koneksi, "SELECT * FROM products $where_clause ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --danger: #dc2626; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-600: #4b5563; --gray-800: #1f2937;
            --white: #ffffff; --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1); --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--gray-100); color: var(--gray-800); line-height: 1.6; }
        
        /* Header */
        .header { background: var(--white); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 100; }
        .header__logo { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
        .logo__icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; font-size: 20px; }
        .logo__text { font-size: 20px; font-weight: 700; color: var(--gray-800); }
        .logo__text span { color: var(--primary); }
        .header__search { flex: 1; max-width: 500px; margin: 0 2rem; position: relative; }
        .header__search input { width: 100%; padding: 0.75rem 3rem 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius); font-size: 14px; outline: none; transition: all 0.3s; }
        .header__search input:focus { border-color: var(--primary); }
        .header__search button { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: var(--primary); border: none; width: 36px; height: 36px; border-radius: 8px; color: var(--white); cursor: pointer; transition: all 0.3s; }
        .header__search button:hover { background: var(--primary-dark); }
        .header__actions { display: flex; align-items: center; gap: 1rem; }
        .header__cart { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: var(--gray-100); border: 2px solid var(--gray-200); border-radius: var(--radius); cursor: pointer; transition: all 0.3s; text-decoration: none; color: var(--gray-600); font-weight: 500; position: relative; }
        .header__cart:hover { border-color: var(--primary); color: var(--primary); }
        .header__cart .badge { position: absolute; top: -8px; right: -8px; background: var(--danger); color: var(--white); font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 12px; min-width: 20px; text-align: center; }
        .header__profile { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); border-radius: var(--radius); cursor: pointer; transition: all 0.3s; text-decoration: none; font-weight: 500; }
        .header__profile:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .header__logout { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #fef2f2; color: var(--danger); border: 2px solid #fecaca; border-radius: var(--radius); cursor: pointer; transition: all 0.3s; text-decoration: none; font-weight: 500; }
        .header__logout:hover { background: var(--danger); color: var(--white); border-color: var(--danger); }
        
        /* Banner */
        .banner { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 3rem 2rem; margin: 1.5rem 2rem; border-radius: 20px; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; box-shadow: var(--shadow-lg); }
        .banner::before { content: ''; position: absolute; top: -50%; right: -10%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%; }
        .banner__content { position: relative; z-index: 1; }
        .banner__title { font-size: 42px; font-weight: 700; color: var(--white); margin-bottom: 0.5rem; }
        .banner__subtitle { color: rgba(255,255,255,0.9); font-size: 16px; font-weight: 400; }
        .banner__image { position: relative; z-index: 1; width: 300px; height: 250px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect fill='%23ffffff' fill-opacity='0.1' rx='20' x='20' y='80' width='80' height='100'/%3E%3Crect fill='%23ffffff' fill-opacity='0.15' rx='15' x='100' y='60' width='70' height='90'/%3E%3Ccircle fill='%23ffffff' fill-opacity='0.2' cx='135' cy='40' r='25'/%3E%3C/svg%3E") no-repeat center; background-size: contain; }
        
        /* Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 2rem 2rem; }
        .section__title { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .section__title i { color: var(--primary); }
        
        /* Products Grid */
        .products__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .product__card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); transition: all 0.3s; cursor: pointer; }
        .product__card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .product__image { width: 100%; height: 220px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
        .product__image img { max-width: 80%; max-height: 80%; object-fit: contain; transition: transform 0.3s; }
        .product__card:hover .product__image img { transform: scale(1.1); }
        .product__badge { position: absolute; top: 12px; left: 12px; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: var(--white); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; z-index: 2; }
        .product__info { padding: 1.25rem; }
        .product__name { font-size: 16px; font-weight: 600; color: var(--gray-800); margin-bottom: 0.5rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product__price { font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 1rem; }
        .product__btn { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; display: block; text-align: center; }
        .product__btn:hover { background: linear-gradient(135deg, var(--primary-dark), #1e40af); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .product__btn:disabled { background: var(--gray-300); cursor: not-allowed; transform: none; }
        
        /* Empty State */
        .empty__state { text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); grid-column: 1/-1; }
        .empty__state i { font-size: 80px; color: var(--gray-300); margin-bottom: 1.5rem; }
        .empty__state h3 { font-size: 24px; color: var(--gray-800); margin-bottom: 0.5rem; }
        .empty__state p { color: var(--gray-600); margin-bottom: 1.5rem; }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--white);
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
            border-left: 4px solid var(--success);
        }
        .toast.show { transform: translateX(0); }
        .toast i { font-size: 20px; color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast.error i { color: var(--danger); }
        
        /* Search Results Info */
        .search-info {
            background: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        .search-info .clear-search {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-wrap: wrap; gap: 1rem; padding: 1rem; }
            .header__search { order: 3; width: 100%; margin: 0; max-width: none; }
            .banner { margin: 1rem; padding: 2rem 1.5rem; flex-direction: column; text-align: center; }
            .banner__image { margin-top: 1.5rem; width: 200px; height: 180px; }
            .container { padding: 0 1rem 1.5rem; }
            .products__grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .header__actions { flex-wrap: wrap; justify-content: center; }
            .header__profile span, .header__cart span, .header__logout span { display: none; }
        }
        @media (max-width: 480px) {
            .products__grid { grid-template-columns: 1fr; }
            .banner__title { font-size: 28px; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="header__logo" onclick="window.location.href='dashboard.php'">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </div>
        
        <form class="header__search" method="GET" action="">
            <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search_query); ?>" id="search-input" autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        
        <div class="header__actions">
            <a href="keranjang.php" class="header__cart">
                <i class="fas fa-shopping-cart"></i>
                <span>Keranjang</span>
                <span class="badge" id="cart-badge"><?php echo $cart_count; ?></span>
            </a>
            <a href="profile.php" class="header__profile">
                <i class="fas fa-user-circle"></i>
                <span>Profil Customer</span>
            </a>
            <a href="../auth/logout.php" class="header__logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Banner -->
    <div class="banner">
        <div class="banner__content">
            <h1 class="banner__title">MasElektro</h1>
            <p class="banner__subtitle">Pusat Elektronik Terlengkap & Terpercaya</p>
        </div>
        <div class="banner__image"></div>
    </div>

    <!-- Main Content -->
    <main class="container">
        
        <!-- Search Results Info -->
        <?php if ($search_query): ?>
            <div class="search-info">
                <span>Hasil pencarian untuk: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></span>
                <a href="dashboard.php" class="clear-search">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        <?php endif; ?>
        
        <h2 class="section__title">
            <i class="fas fa-fire"></i>
            <?php echo $search_query ? 'Hasil Pencarian' : 'Produk Tersedia'; ?>
        </h2>
        
        <div class="products__grid">
            <?php if ($query_produk && mysqli_num_rows($query_produk) > 0): ?>
                <?php while ($produk = mysqli_fetch_assoc($query_produk)): ?>
                    <div class="product__card">
                        <div class="product__image">
                            <span class="product__badge">TERLARIS</span>
                            <img src="../uploads/<?php echo htmlspecialchars($produk['gambar'] ?? ''); ?>" 
                                 alt="<?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk'); ?>"
                                 onerror="this.src='https://via.placeholder.com/300x250/1e3a8a/ffffff?text=<?php echo urlencode($produk['nama_produk'] ?? 'Produk'); ?>'">
                        </div>
                        <div class="product__info">
                            <h3 class="product__name"><?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk Tanpa Nama'); ?></h3>
                            <p class="product__price"><?php echo 'Rp. ' . number_format($produk['harga'] ?? 0, 0, ',', '.'); ?></p>
                            <button class="product__btn" onclick="addToCart(<?php echo (int)$produk['id']; ?>, '<?php echo addslashes($produk['nama_produk']); ?>')">
                                <i class="fas fa-cart-plus"></i> Beli
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty__state">
                    <i class="fas fa-search"></i>
                    <h3>Produk Tidak Ditemukan</h3>
                    <p>Tidak ada produk yang cocok dengan pencarian "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <a href="dashboard.php" class="product__btn" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-arrow-left"></i> Lihat Semua Produk
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message"></span>
    </div>

    <script>
        // Add to Cart dengan AJAX
        function addToCart(productId, productName) {
            const btn = event.target.closest('.product__btn');
            const originalText = btn.innerHTML;
            
            // Disable button & show loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            
            // AJAX Request
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add_to_cart&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                // Show toast notification
                showToast(data.message, data.success ? 'success' : 'error');
                
                // Update cart badge if success
                if (data.success && data.cart_count !== undefined) {
                    document.getElementById('cart-badge').textContent = data.cart_count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.innerHTML = originalText;
                showToast('❌ Terjadi kesalahan, coba lagi!', 'error');
            });
        }
        
        // Show Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = 'toast ' + type;
            toast.classList.add('show');
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Search: Auto-submit on Enter (already handled by form)
        // Clear search input on focus if it has value
        document.getElementById('search-input').addEventListener('focus', function() {
            if (this.value && this.value !== '<?php echo addslashes($search_query); ?>') {
                this.select();
            }
        });
    </script>

</body>
</html>