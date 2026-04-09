<?php
/**
 * File: user/dashboard.php
 * Deskripsi: Halaman dashboard/katalog produk untuk user
 */

session_start();
require_once '../config.php';

$is_logged_in = isset($_SESSION['login']) && $_SESSION['role'] === 'user';
$user_id = $is_logged_in ? (int)$_SESSION['id'] : 0;

$data_user = null;
if ($is_logged_in && $user_id > 0) {
    $query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$user_id'");
    $data_user = mysqli_fetch_assoc($query_user);
}

$cart_count = 0;
if ($is_logged_in) {
    $check_cart = @mysqli_query($koneksi, "SELECT 1 FROM cart LIMIT 1");
    if ($check_cart) {
        $q_cart = mysqli_query($koneksi, "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = '$user_id'");
        if ($q_cart) {
            $cart_data = mysqli_fetch_assoc($q_cart);
            $cart_count = (int)$cart_data['total'];
        }
    }
}

// Handle AJAX Add to Cart
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['success' => false, 'message' => '🔐 Silakan login terlebih dahulu!', 'require_login' => true]);
        exit();
    }
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($product_id > 0) {
        $check_stock = mysqli_query($koneksi, "SELECT stok, nama_produk FROM products WHERE id = '$product_id'");
        $product = mysqli_fetch_assoc($check_stock);
        
        if ($product) {
            $check_cart_item = mysqli_query($koneksi, "SELECT quantity FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'");
            
            if (mysqli_num_rows($check_cart_item) > 0) {
                mysqli_query($koneksi, "UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = '$product_id'");
            } else {
                mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)");
            }
            
            $new_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = '$user_id'"))['total'];
            
            echo json_encode(['success' => true, 'message' => '✅ ' . $product['nama_produk'] . ' ditambahkan!', 'cart_count' => (int)$new_count]);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Produk tidak ditemukan']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ Invalid product']);
    }
    exit();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = $search_query ? "WHERE nama_produk LIKE '%$search_query%' OR deskripsi LIKE '%$search_query%'" : '';
$query_produk = mysqli_query($koneksi, "SELECT * FROM products $where_clause ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --danger: #dc2626; --warning: #f59e0b; --gray-50: #f9fafb;
            --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-300: #d1d5db;
            --gray-600: #4b5563; --gray-800: #1f2937; --gray-900: #111827;
            --white: #ffffff; --shadow: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1); --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 12px; --transition: all 0.2s ease;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-50); color: var(--gray-800); line-height: 1.6; }
        a { text-decoration: none; }
        
        /* ✅ HEADER PROFESIONAL */
        .header {
            background: var(--white);
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 72px;
            transition: var(--transition);
        }
        
        .header.scrolled {
            padding: 0.5rem 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        /* Logo */
        .header__logo {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 0;
        }
        
        .logo__icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: var(--transition);
        }
        
        .header__logo:hover .logo__icon {
            transform: scale(1.05);
        }
        
        .logo__text {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -0.5px;
        }
        
        .logo__text span {
            color: var(--primary);
        }
        
        /* Search Bar */
        .header__search {
            flex: 1;
            max-width: 520px;
            margin: 0 3rem;
            position: relative;
        }
        
        .header__search input {
            width: 100%;
            padding: 12px 48px 12px 20px;
            border: 2px solid var(--gray-200);
            border-radius: 14px;
            font-size: 14px;
            background: var(--gray-50);
            transition: var(--transition);
            color: var(--gray-800);
        }
        
        .header__search input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .header__search input::placeholder {
            color: var(--gray-400);
        }
        
        .header__search button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header__search button:hover {
            background: var(--primary-dark);
            transform: translateY(-50%) scale(1.05);
        }
        
        /* Actions */
        .header__actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Cart Button */
        .header__cart {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--gray-50);
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray-700);
            font-weight: 500;
            font-size: 14px;
            position: relative;
        }
        
        .header__cart:hover {
            background: var(--white);
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .header__cart i {
            font-size: 18px;
        }
        
        .header__cart .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: var(--danger);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 10px;
            min-width: 22px;
            text-align: center;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Profile Button */
        .header__profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .header__profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .header__profile i {
            font-size: 18px;
        }
        
        .header__profile.secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            box-shadow: none;
        }
        
        .header__profile.secondary:hover {
            background: var(--gray-200);
            color: var(--gray-900);
        }
        
        /* Logout Button */
        .header__logout {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #fef2f2;
            color: var(--danger);
            border: 2px solid #fecaca;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 14px;
        }
        
        .header__logout:hover {
            background: var(--danger);
            color: var(--white);
            border-color: var(--danger);
            transform: translateY(-2px);
        }
        
        .header__logout i {
            font-size: 16px;
        }
        
        /* Register Button */
        .header__register {
            padding: 10px 20px;
            background: var(--success);
            color: var(--white);
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .header__register:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        /* Mobile Menu Toggle */
        .mobile-toggle {
            display: none;
            font-size: 24px;
            color: var(--gray-700);
            cursor: pointer;
            background: none;
            border: none;
            padding: 8px;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .mobile-toggle:hover {
            background: var(--gray-100);
            color: var(--primary);
        }
        
        /* ✅ PROMO BANNER */
        .promo-banner {
            position: relative;
            width: 100%;
            max-width: 1400px;
            margin: 24px auto 32px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            height: 400px;
            cursor: pointer;
        }
        
        .promo-slider { position: relative; width: 100%; height: 100%; }
        
        .promo-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 40px 60px;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            color: #fff;
        }
        
        .promo-slide.active { opacity: 1; }
        
        .promo-content { flex: 1; max-width: 55%; animation: slideInLeft 0.8s ease; }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .promo-badge {
            display: inline-block;
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .promo-title {
            font-size: 44px;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .promo-desc {
            font-size: 18px;
            margin-bottom: 25px;
            opacity: 0.95;
        }
        
        .promo-image {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }
        
        .promo-image i {
            font-size: 200px;
            opacity: 0.2;
            color: #fff;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .promo-dots {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .dot.active {
            background: #fff;
            width: 35px;
            border-radius: 6px;
        }
        
        .dot:hover { background: #fff; }
        
        /* Container & Content */
        .container { max-width: 1400px; margin: 0 auto; padding: 0 2rem 2rem; }
        
        .section__title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section__title i { color: var(--primary); }
        
        /* Products Grid */
        .products__grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        /* Product Card */
        .product__card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-100);
        }
        
        .product__card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .product__image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .product__image img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        
        .product__card:hover .product__image img {
            transform: scale(1.1);
        }
        
        .product__badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .product__info {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product__name {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }
        
        .product__price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }
        
        .product__btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: auto;
        }
        
        .product__btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .product__btn:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty__state {
            text-align: center;
            padding: 60px 2rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            grid-column: 1/-1;
        }
        
        .empty__state i {
            font-size: 72px;
            color: var(--gray-300);
            margin-bottom: 20px;
        }
        
        .empty__state h3 {
            font-size: 22px;
            color: var(--gray-800);
            margin-bottom: 8px;
        }
        
        .empty__state p {
            color: var(--gray-600);
            margin-bottom: 24px;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--white);
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
            border-left: 4px solid var(--success);
            min-width: 300px;
        }
        
        .toast.show { transform: translateX(0); }
        .toast i { font-size: 20px; color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast.error i { color: var(--danger); }
        
        /* Search Info */
        .search-info {
            background: var(--white);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }
        
        .search-info .clear-search {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .search-info .clear-search:hover { text-decoration: underline; }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .products__grid { grid-template-columns: repeat(3, 1fr); }
        }
        
        @media (max-width: 992px) {
            .header { padding: 0 1.5rem; }
            .header__search { margin: 0 1.5rem; }
            .promo-banner { margin: 20px 15px 28px; height: 360px; }
            .promo-slide { padding: 30px 40px; flex-direction: column; text-align: center; }
            .promo-content { max-width: 100%; margin-bottom: 20px; }
            .promo-title { font-size: 32px; }
            .promo-desc { font-size: 16px; }
            .promo-image i { font-size: 140px; }
            .container { padding: 0 1.5rem 2rem; }
            .products__grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
        }
        
        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .header__search { order: 3; width: 100%; margin: 12px 0 0; max-width: none; }
            .header__actions { gap: 8px; }
            .header__cart span, .header__profile span, .header__logout span { display: none; }
            .header__profile, .header__cart, .header__logout { padding: 10px 14px; }
            .promo-banner { height: auto; min-height: 300px; border-radius: 16px; }
            .promo-slide { padding: 25px 20px; }
            .promo-title { font-size: 26px; }
            .promo-image { display: none; }
            .products__grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 480px) {
            .header { padding: 0 1rem; height: 68px; }
            .logo__text { font-size: 18px; }
            .logo__icon { width: 38px; height: 38px; font-size: 18px; }
            .header__search input { padding: 10px 44px 10px 16px; font-size: 13px; }
            .header__search button { width: 36px; height: 36px; }
            .products__grid { grid-template-columns: 1fr; }
            .product__image { height: 180px; }
            .product__name { font-size: 14px; min-height: 40px; }
            .product__price { font-size: 17px; }
            .promo-title { font-size: 24px; }
            .promo-desc { font-size: 14px; }
        }
    </style>
</head>
<body>

    <!-- ✅ HEADER PROFESIONAL -->
    <header class="header" id="mainHeader">
        <button class="mobile-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header__logo" onclick="window.location.href='dashboard.php'">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </div>
        
        <form class="header__search" method="GET" action="">
            <input type="text" name="search" placeholder="Cari produk elektronik..." value="<?php echo htmlspecialchars($search_query); ?>" id="search-input" autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        
        <div class="header__actions">
            <?php if ($is_logged_in): ?>
                <!-- ✅ USER LOGGED IN -->
                <a href="keranjang.php" class="header__cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                    <?php if($cart_count > 0): ?>
                        <span class="badge" id="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="riwayat.php" class="header__profile secondary">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>
                <a href="profile.php" class="header__profile">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars(explode(' ', $data_user['nama_lengkap'] ?? 'User')[0]); ?></span>
                </a>
                <a href="../auth/logout.php" class="header__logout" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <!-- ✅ GUEST USER -->
                <a href="keranjang.php" class="header__cart" onclick="requireLogin(event); return false;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                </a>
                <a href="../auth/login.php" class="header__profile">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="../auth/register.php" class="header__register">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- ✅ PROMO BANNER -->
    <div class="promo-banner" onclick="scrollToProducts()">
        <div class="promo-slider">
            <div class="promo-slide active" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="promo-content">
                    <div class="promo-badge">🔥 Promo Spesial</div>
                    <h2 class="promo-title">Diskon Hingga 50%</h2>
                    <p class="promo-desc">Untuk Produk Laptop & Smartphone Pilihan</p>
                </div>
                <div class="promo-image"><i class="fas fa-laptop"></i></div>
            </div>
            <div class="promo-slide" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="promo-content">
                    <div class="promo-badge">⚡ Flash Sale</div>
                    <h2 class="promo-title">Gratis Ongkir</h2>
                    <p class="promo-desc">Tanpa Minimum Pembelian Hari Ini!</p>
                </div>
                <div class="promo-image"><i class="fas fa-shipping-fast"></i></div>
            </div>
            <div class="promo-slide" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="promo-content">
                    <div class="promo-badge">🎁 Bonus Menarik</div>
                    <h2 class="promo-title">Beli Laptop Dapat Mouse</h2>
                    <p class="promo-desc">Untuk Pembelian Laptop Merek Tertentu</p>
                </div>
                <div class="promo-image"><i class="fas fa-mouse"></i></div>
            </div>
            <div class="promo-slide" style="background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);">
                <div class="promo-content">
                    <div class="promo-badge">💳 Cicilan 0%</div>
                    <h2 class="promo-title">Bayar Lebih Ringan</h2>
                    <p class="promo-desc">Cicilan 0% Sampai 12 Bulan</p>
                </div>
                <div class="promo-image"><i class="fas fa-credit-card"></i></div>
            </div>
        </div>
        <div class="promo-dots">
            <span class="dot active" onclick="event.stopPropagation(); currentSlide(0)"></span>
            <span class="dot" onclick="event.stopPropagation(); currentSlide(1)"></span>
            <span class="dot" onclick="event.stopPropagation(); currentSlide(2)"></span>
            <span class="dot" onclick="event.stopPropagation(); currentSlide(3)"></span>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container" id="products">
        <?php if ($search_query): ?>
            <div class="search-info">
                <span>Hasil pencarian: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></span>
                <a href="dashboard.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
            </div>
        <?php endif; ?>
        
        <h2 class="section__title">
            <i class="fas fa-fire"></i>
            <?php echo $search_query ? 'Hasil Pencarian' : 'Produk Tersedia'; ?>
        </h2>
        
        <div class="products__grid">
            <?php if ($query_produk && mysqli_num_rows($query_produk) > 0): ?>
                <?php while ($produk = mysqli_fetch_assoc($query_produk)): ?>
                    <div class="product__card" onclick="window.location.href='detail_produk.php?id=<?php echo $produk['id']; ?>'">
                        <div class="product__image">
                            <span class="product__badge">🔥 TERLARIS</span>
                            <img src="../uploads/<?php echo htmlspecialchars($produk['gambar'] ?? ''); ?>" 
                                 alt="<?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk'); ?>"
                                 onerror="this.src='https://via.placeholder.com/200x180/1e3a8a/ffffff?text=<?php echo urlencode($produk['nama_produk'] ?? 'Produk'); ?>'">
                        </div>
                        <div class="product__info">
                            <h3 class="product__name"><?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk Tanpa Nama'); ?></h3>
                            <p class="product__price">Rp <?php echo number_format($produk['harga'] ?? 0, 0, ',', '.'); ?></p>
                            <button class="product__btn" 
                                    onclick="<?php echo $is_logged_in ? 'event.stopPropagation(); addToCart('.(int)$produk['id'].', \''.addslashes($produk['nama_produk']).'\')' : 'requireLogin(event)'; ?>">
                                <i class="fas fa-cart-plus"></i> <?php echo $is_logged_in ? 'Beli' : 'Login'; ?>
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
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            if (window.scrollY > 10) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Promo Banner Slider
        let currentIndex = 0;
        const slides = document.querySelectorAll('.promo-slide');
        const dots = document.querySelectorAll('.dot');
        
        function showSlide(index) {
            slides.forEach((slide, i) => { slide.classList.remove('active'); dots[i].classList.remove('active'); });
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }
        function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; showSlide(currentIndex); }
        function currentSlide(index) { currentIndex = index; showSlide(currentIndex); }
        setInterval(nextSlide, 5000);
        
        // Scroll to Products
        function scrollToProducts() {
            document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Login requirement
        function requireLogin(e) {
            if (e) e.preventDefault();
            if (confirm('🔐 Anda harus login terlebih dahulu.\n\nKlik OK untuk login.')) {
                window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(window.location.href);
            }
        }
        
        // Add to Cart
        function addToCart(productId, productName) {
            const btn = event.target.closest('.product__btn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add_to_cart&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data.require_login) { requireLogin(null); return; }
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success && data.cart_count !== undefined) {
                    document.getElementById('cart-badge').textContent = data.cart_count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.innerHTML = originalText;
                showToast('❌ Terjadi kesalahan!', 'error');
            });
        }
        
        // Toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').textContent = message;
            toast.className = 'toast ' + type;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Search
        document.getElementById('search-input').addEventListener('focus', function() {
            if (this.value) this.select();
        });
        
        // Mobile menu toggle (placeholder)
        function toggleMobileMenu() {
            // Implement mobile menu if needed
            alert('Menu mobile akan diimplementasikan');
        }
    </script>
</body>
</html>