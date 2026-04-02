<?php
/**
 * File: user/keranjang.php
 * Deskripsi: Halaman keranjang belanja untuk user
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

// Class CartManager
class CartManager {
    private $db;
    private $userId;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    public function getCartItems() {
        $query = "
            SELECT 
                c.id as cart_id,
                c.product_id,
                c.quantity,
                p.id,
                p.nama_produk,
                p.harga,
                p.gambar,
                p.stok
            FROM cart c
            INNER JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function updateQuantity($productId, $quantity) {
        if ($quantity <= 0) {
            return $this->deleteItem($productId);
        }
        
        $stmt = $this->db->prepare("SELECT stok FROM products WHERE id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product && $quantity > $product['stok']) {
            return ['success' => false, 'message' => 'Stok tidak mencukupi'];
        }
        
        $stmt = $this->db->prepare("
            UPDATE cart 
            SET quantity = ? 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->bind_param('iii', $quantity, $this->userId, $productId);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Quantity berhasil diupdate'];
    }
    
    public function deleteItem($productId) {
        $stmt = $this->db->prepare("
            DELETE FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->bind_param('ii', $this->userId, $productId);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Item berhasil dihapus'];
    }
    
    public function getTotalItems() {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity), 0) as total 
            FROM cart 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $this->userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return (int)$result['total'];
    }
    
    public function calculateTotal() {
        $items = $this->getCartItems();
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['harga'] * $item['quantity'];
        }
        
        return $total;
    }
}

// Helper functions
function formatRupiah($angka) {
    return 'Rp. ' . number_format($angka, 0, ',', '.');
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Inisialisasi CartManager
$cartManager = new CartManager($koneksi, $user_id);
$errorMessage = null;
$successMessage = null;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if ($productId > 0) {
            if ($quantity < 1) {
                $errorMessage = 'Quantity minimal 1';
            } else {
                $result = $cartManager->updateQuantity($productId, $quantity);
                if ($result['success']) {
                    $successMessage = $result['message'];
                } else {
                    $errorMessage = $result['message'];
                }
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId > 0) {
            $result = $cartManager->deleteItem($productId);
            $successMessage = $result['message'];
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
        if ($cartManager->getTotalItems() === 0) {
            $errorMessage = 'Keranjang belanja kosong';
        } else {
            header('Location: checkout.php');
            exit();
        }
    }
}

// Handle GET requests
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($productId > 0) {
        $result = $cartManager->deleteItem($productId);
        $successMessage = $result['message'];
    }
}

// Prepare data for view
$cartItems = $cartManager->getCartItems();
$totalItems = $cartManager->getTotalItems();
$totalHarga = $cartManager->calculateTotal();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Keranjang Belanja - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #dc2626;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header__logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
        }
        
        .logo__icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }
        
        .logo__text {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
        }
        
        .logo__text span {
            color: var(--primary);
        }
        
        .header__title {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }
        
        .badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn--primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn--primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .btn--secondary {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 2px solid var(--gray-200);
        }
        
        .btn--secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .btn--danger {
            background: #fef2f2;
            color: var(--danger);
            border: 2px solid #fecaca;
        }
        
        .btn--danger:hover {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .alert--success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .cart-item {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 150px 1fr auto;
            gap: 1.5rem;
            align-items: center;
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }
        
        .cart-item:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .cart-item__image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .cart-item__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item__details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .item__name {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .item__price {
            font-size: 18px;
            font-weight: 700;
            color: var(--success);
        }
        
        .item__subtotal {
            color: var(--gray-600);
            font-size: 14px;
        }
        
        .cart-item__controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--gray-100);
            padding: 0.5rem;
            border-radius: 10px;
        }
        
        .qty-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            color: var(--primary);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .qty-value {
            font-size: 18px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .cart-summary {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            position: sticky;
            bottom: 2rem;
        }
        
        .summary__row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .summary__total {
            border-bottom: none;
        }
        
        .summary__label {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 500;
        }
        
        .summary__value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .summary__total .summary__value {
            font-size: 28px;
        }
        
        .btn--checkout {
            width: 100%;
            padding: 1rem;
            font-size: 18px;
            margin-top: 1rem;
            justify-content: center;
        }
        
        .summary__note {
            text-align: center;
            margin-top: 1rem;
            color: var(--gray-600);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .empty-cart {
            background: white;
            border-radius: var(--radius);
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .empty-cart__icon {
            font-size: 80px;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }
        
        .empty-cart__title {
            font-size: 24px;
            color: var(--gray-800);
            margin-bottom: 1rem;
        }
        
        .empty-cart__text {
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .header {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }
            
            .header__title {
                font-size: 20px;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .cart-item__image {
                width: 100%;
                height: 200px;
            }
            
            .cart-item__controls {
                align-items: center;
                flex-direction: row;
                justify-content: center;
            }
            
            .container {
                padding: 1rem;
            }
            
            .cart-summary {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                margin: 0 1rem 1rem;
                box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
            }
            
            body {
                padding-bottom: 280px;
            }
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
        
        <h1 class="header__title">
            Keranjang 
            <span class="badge"><?php echo (int)$totalItems; ?> item</span>
        </h1>
        
        <div class="header__actions">
            <!-- FIX: Gunakan href langsung, bukan onclick -->
            <a href="dashboard.php" class="btn btn--secondary" id="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Lanjut Belanja</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <?php if ($totalItems > 0): ?>
            
            <!-- Alert Messages -->
            <?php if ($errorMessage): ?>
                <div class="alert alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo e($errorMessage); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="alert alert--success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo e($successMessage); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Cart Items -->
            <section class="cart-items">
                <?php foreach ($cartItems as $item): 
                    $subtotal = $item['harga'] * $item['quantity'];
                ?>
                    <article class="cart-item" data-product-id="<?php echo (int)$item['product_id']; ?>">
                        <div class="cart-item__image">
                            <img src="../uploads/<?php echo e($item['gambar']); ?>" 
                                 alt="<?php echo e($item['nama_produk']); ?>"
                                 onerror="this.src='https://via.placeholder.com/150x150/2563eb/ffffff?text=No+Image'">
                        </div>
                        
                        <div class="cart-item__details">
                            <h3 class="item__name"><?php echo e($item['nama_produk']); ?></h3>
                            <p class="item__price"><?php echo formatRupiah($item['harga']); ?></p>
                            <p class="item__subtotal">Subtotal: <strong><?php echo formatRupiah($subtotal); ?></strong></p>
                        </div>
                        
                        <div class="cart-item__controls">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                
                                <div class="quantity-control">
                                    <button type="button" class="qty-btn" 
                                            onclick="updateQuantity(<?php echo (int)$item['product_id']; ?>, <?php echo (int)$item['quantity'] - 1; ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    
                                    <span class="qty-value" id="qty-<?php echo (int)$item['product_id']; ?>">
                                        <?php echo (int)$item['quantity']; ?>
                                    </span>
                                    
                                    <button type="button" class="qty-btn"
                                            onclick="updateQuantity(<?php echo (int)$item['product_id']; ?>, <?php echo (int)$item['quantity'] + 1; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <form method="POST" onsubmit="return confirm('Hapus item dari keranjang?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                
                                <button type="submit" class="btn btn--danger">
                                    <i class="fas fa-trash"></i>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
            
            <!-- Cart Summary -->
            <aside class="cart-summary">
                <div class="summary__row">
                    <span class="summary__label">Total Item</span>
                    <span class="summary__value"><?php echo (int)$totalItems; ?> produk</span>
                </div>
                
                <div class="summary__row summary__total">
                    <span class="summary__label">Total Harga</span>
                    <span class="summary__value"><?php echo formatRupiah($totalHarga); ?></span>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="btn btn--primary btn--checkout">
                        <i class="fas fa-credit-card"></i>
                        <span>Proses Checkout</span>
                    </button>
                </form>
                
                <p class="summary__note">
                    <i class="fas fa-shield-alt"></i>
                    Transaksi aman & terpercaya
                </p>
            </aside>
            
        <?php else: ?>
            
            <!-- Empty Cart -->
            <section class="empty-cart">
                <div class="empty-cart__icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-cart__title">Keranjang Belanja Kosong</h2>
                <p class="empty-cart__text">
                    Yuk, mulai belanja produk elektronik favorit Anda!
                </p>
                <a href="dashboard.php" class="btn btn--primary">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Mulai Belanja</span>
                </a>
            </section>
            
        <?php endif; ?>
    </main>

    <script>
        // Function untuk update quantity
        function updateQuantity(productId, newQty) {
            if (newQty < 1) {
                if (confirm('Hapus item dari keranjang?')) {
                    submitForm(productId, 'delete');
                }
                return;
            }
            submitForm(productId, 'update_quantity', newQty);
        }
        
        // Function untuk submit form
        function submitForm(productId, action, quantity) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            let inputs = '<input type="hidden" name="action" value="' + action + '">';
            inputs += '<input type="hidden" name="product_id" value="' + productId + '">';
            
            if (typeof quantity !== 'undefined') {
                inputs += '<input type="hidden" name="quantity" value="' + quantity + '">';
            }
            
            form.innerHTML = inputs;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
            
            // FIX: Pastikan tombol back tidak terganggu oleh event lain
            const btnBack = document.getElementById('btn-back');
            if (btnBack) {
                btnBack.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = 'dashboard.php';
                });
            }
        });
    </script>

</body>
</html>