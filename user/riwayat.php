<?php
/**
 * File: user/riwayat.php
 * Deskripsi: Halaman riwayat pesanan untuk user
 */

session_start();
require_once '../config.php';

// Cek autentikasi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($user_id === 0) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data user
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$user_id'");
$data_user = mysqli_fetch_assoc($query_user);

// Filter & Search
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_clauses = ["o.user_id = '$user_id'"];
if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "o.status = '$status_filter'";
}
if ($search_query) {
    $where_clauses[] = "(o.id LIKE '%$search_query%' OR o.nama_penerima LIKE '%$search_query%')";
}
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Ambil daftar orders
$query_orders = mysqli_query($koneksi, "
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id) as item_count
    FROM orders o
    $where_sql
    ORDER BY o.created_at DESC
");

// Fungsi format status
function getStatusBadge($status) {
    $badges = [
        'pending' => ['bg' => '#fbbf24', 'text' => '#92400e', 'label' => '⏳ Menunggu'],
        'diproses' => ['bg' => '#3b82f6', 'text' => '#1e40af', 'label' => '🔄 Diproses'],
        'dikirim' => ['bg' => '#8b5cf6', 'text' => '#5b21b6', 'label' => '🚚 Dikirim'],
        'selesai' => ['bg' => '#10b981', 'text' => '#065f46', 'label' => '✅ Selesai'],
        'dibatalkan' => ['bg' => '#ef4444', 'text' => '#991b1b', 'label' => '❌ Dibatalkan'],
    ];
    return $badges[$status] ?? $badges['pending'];
}

// Format tanggal
function formatDate($date) {
    return date('d M Y, H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Pesanan - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --warning: #f59e0b; --danger: #dc2626; --info: #3b82f6;
            --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-300: #d1d5db;
            --gray-600: #4b5563; --gray-800: #1f2937; --white: #ffffff;
            --shadow: 0 1px 3px rgba(0,0,0,0.1); --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1); --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-100); color: var(--gray-800); line-height: 1.6; }
        a { text-decoration: none; }
        
        /* Header */
        .header { background: var(--white); padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 100; }
        .header__logo { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
        .logo__icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; font-size: 20px; }
        .logo__text { font-size: 20px; font-weight: 700; color: var(--gray-800); }
        .logo__text span { color: var(--primary); }
        .header__title { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-left: auto; }
        .header__nav { display: flex; gap: 0.75rem; }
        .header__nav a { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; }
        .header__nav .btn-back { background: var(--gray-100); color: var(--gray-600); border: 2px solid var(--gray-200); }
        .header__nav .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .header__nav .btn-logout { background: #fef2f2; color: var(--danger); border: 2px solid #fecaca; }
        .header__nav .btn-logout:hover { background: var(--danger); color: var(--white); border-color: var(--danger); }
        
        /* Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 28px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .page-title i { color: var(--primary); }
        
        /* Filters */
        .filters { background: var(--white); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow); display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
        .filters__search { flex: 1; min-width: 200px; position: relative; }
        .filters__search input { width: 100%; padding: 0.75rem 3rem 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 14px; outline: none; transition: all 0.3s; }
        .filters__search input:focus { border-color: var(--primary); }
        .filters__search i { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); }
        .filters__status { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.5rem 1rem; border: 2px solid var(--gray-200); border-radius: 20px; background: var(--white); font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { border-color: var(--primary); background: var(--primary); color: var(--white); }
        .filter-btn.all { border-color: var(--gray-300); }
        .filter-btn.all:hover, .filter-btn.all.active { border-color: var(--gray-600); background: var(--gray-600); }
        
        /* Orders List */
        .orders-list { display: flex; flex-direction: column; gap: 1rem; }
        .order-card { background: var(--white); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow); transition: all 0.3s; }
        .order-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
        
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200); flex-wrap: wrap; gap: 1rem; }
        .order-id { font-size: 18px; font-weight: 700; color: var(--gray-800); }
        .order-id span { color: var(--primary); }
        .order-date { font-size: 14px; color: var(--gray-600); }
        .order-status { padding: 0.5rem 1rem; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem; }
        
        .order-items { margin-bottom: 1rem; }
        .order-item { display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100); }
        .order-item:last-child { border-bottom: none; }
        .order-item__image { width: 50px; height: 50px; background: var(--gray-100); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .order-item__image img { width: 100%; height: 100%; object-fit: cover; }
        .order-item__details { flex: 1; }
        .order-item__name { font-size: 14px; font-weight: 600; color: var(--gray-800); }
        .order-item__qty { font-size: 12px; color: var(--gray-600); }
        .order-item__price { font-size: 14px; font-weight: 700; color: var(--primary); }
        
        .order-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--gray-200); flex-wrap: wrap; gap: 1rem; }
        .order-total { font-size: 18px; font-weight: 700; color: var(--gray-800); }
        .order-total span { color: var(--primary); font-size: 20px; }
        .order-actions { display: flex; gap: 0.5rem; }
        .btn-detail { padding: 0.5rem 1rem; background: var(--gray-100); color: var(--gray-800); border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        .btn-detail:hover { background: var(--primary); color: var(--white); }
        .btn-reorder { padding: 0.5rem 1rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        .btn-reorder:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); }
        .empty-state i { font-size: 80px; color: var(--gray-300); margin-bottom: 1.5rem; }
        .empty-state h3 { font-size: 24px; color: var(--gray-800); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--gray-600); margin-bottom: 1.5rem; }
        .empty-state .btn { display: inline-block; padding: 0.75rem 2rem; background: var(--primary); color: var(--white); border-radius: 10px; font-weight: 600; transition: all 0.3s; }
        .empty-state .btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; }
        .modal.show { display: flex; }
        .modal__content { background: var(--white); border-radius: var(--radius); padding: 2rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal__close { position: absolute; top: 1rem; right: 1rem; width: 36px; height: 36px; border: none; background: var(--gray-100); border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.3s; }
        .modal__close:hover { background: var(--danger); color: var(--white); }
        .modal__title { font-size: 20px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); }
        .modal__row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100); }
        .modal__row:last-child { border-bottom: none; }
        .modal__label { color: var(--gray-600); }
        .modal__value { font-weight: 600; color: var(--gray-800); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { padding: 1rem; flex-wrap: wrap; }
            .header__title { margin-left: 0; width: 100%; text-align: center; order: 3; }
            .container { padding: 1rem; }
            .filters { flex-direction: column; align-items: stretch; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-footer { flex-direction: column; align-items: flex-start; }
            .order-actions { width: 100%; justify-content: flex-end; }
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
        <h1 class="header__title">Riwayat Pesanan</h1>
        <div class="header__nav">
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
            <a href="../auth/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <h1 class="page-title">
            <i class="fas fa-box-open"></i>
            Riwayat Pesanan Saya
        </h1>
        
        <!-- Filters -->
        <div class="filters">
            <form class="filters__search" method="GET" action="">
                <input type="text" name="search" placeholder="Cari Order ID atau Nama..." value="<?php echo htmlspecialchars($search_query); ?>">
                <i class="fas fa-search"></i>
                <?php if ($status_filter): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
            </form>
            
            <div class="filters__status">
                <a href="?search=<?php echo urlencode($search_query); ?>" class="filter-btn all <?php echo !$status_filter ? 'active' : ''; ?>">Semua</a>
                <a href="?status=pending&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">⏳ Menunggu</a>
                <a href="?status=diproses&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'diproses' ? 'active' : ''; ?>">🔄 Diproses</a>
                <a href="?status=dikirim&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'dikirim' ? 'active' : ''; ?>">🚚 Dikirim</a>
                <a href="?status=selesai&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'selesai' ? 'active' : ''; ?>">✅ Selesai</a>
            </div>
        </div>
        
        <!-- Orders List -->
        <div class="orders-list">
            <?php if ($query_orders && mysqli_num_rows($query_orders) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($query_orders)): 
                    $badge = getStatusBadge($order['status']);
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<span><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span></div>
                                <div class="order-date"><i class="far fa-clock"></i> <?php echo formatDate($order['created_at']); ?></div>
                            </div>
                            <span class="order-status" style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['text']; ?>">
                                <?php echo $badge['label']; ?>
                            </span>
                        </div>
                        
                        <div class="order-items">
                            <?php
                            // Ambil detail order
                            $query_details = mysqli_query($koneksi, "
                                SELECT od.*, p.nama_produk, p.gambar 
                                FROM order_details od 
                                INNER JOIN products p ON od.product_id = p.id 
                                WHERE od.order_id = '{$order['id']}'
                                LIMIT 3
                            ");
                            while ($detail = mysqli_fetch_assoc($query_details)):
                            ?>
                                <div class="order-item">
                                    <div class="order-item__image">
                                        <img src="../uploads/<?php echo htmlspecialchars($detail['gambar'] ?? ''); ?>" 
                                             alt="<?php echo htmlspecialchars($detail['nama_produk']); ?>"
                                             onerror="this.src='https://via.placeholder.com/50x50/2563eb/ffffff?text=No+Image'">
                                    </div>
                                    <div class="order-item__details">
                                        <div class="order-item__name"><?php echo htmlspecialchars($detail['nama_produk']); ?></div>
                                        <div class="order-item__qty">Qty: <?php echo (int)$detail['quantity']; ?></div>
                                    </div>
                                    <div class="order-item__price">Rp. <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></div>
                                </div>
                            <?php endwhile; ?>
                            <?php if ($order['item_count'] > 3): ?>
                                <div style="font-size: 12px; color: var(--gray-600); padding: 0.5rem 0;">
                                    + <?php echo $order['item_count'] - 3; ?> item lainnya...
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                Total: <span>Rp. <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="order-actions">
                                <button class="btn-detail" onclick="showOrderDetail(<?php echo json_encode($order); ?>)">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                <?php if ($order['status'] === 'selesai' || $order['status'] === 'dibatalkan'): ?>
                                    <button class="btn-reorder" onclick="reorderOrder(<?php echo (int)$order['id']; ?>)">
                                        <i class="fas fa-redo"></i> Beli Lagi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>Belum Ada Pesanan</h3>
                    <p>Anda belum memiliki riwayat pesanan. Yuk, mulai belanja!</p>
                    <a href="dashboard.php" class="btn">
                        <i class="fas fa-shopping-bag"></i> Mulai Belanja
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Order Detail Modal -->
    <div class="modal" id="orderModal">
        <div class="modal__content">
            <button class="modal__close" onclick="closeModal()">&times;</button>
            <h2 class="modal__title">📦 Detail Pesanan</h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        // Show Order Detail Modal
        function showOrderDetail(order) {
            const modal = document.getElementById('orderModal');
            const modalBody = document.getElementById('modalBody');
            
            const statusBadges = {
                'pending': '⏳ Menunggu Pembayaran',
                'diproses': '🔄 Sedang Diproses',
                'dikirim': '🚚 Dalam Pengiriman',
                'selesai': '✅ Pesanan Selesai',
                'dibatalkan': '❌ Pesanan Dibatalkan'
            };
            
            modalBody.innerHTML = `
                <div class="modal__row">
                    <span class="modal__label">Order ID</span>
                    <span class="modal__value">#${String(order.id).padStart(6, '0')}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">Tanggal</span>
                    <span class="modal__value">${order.created_at}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">Penerima</span>
                    <span class="modal__value">${order.nama_penerima}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">No. Telepon</span>
                    <span class="modal__value">${order.no_telp}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">Alamat</span>
                    <span class="modal__value">${order.alamat}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">Metode Pembayaran</span>
                    <span class="modal__value">${order.metode_pembayaran.toUpperCase()}</span>
                </div>
                <div class="modal__row">
                    <span class="modal__label">Status</span>
                    <span class="modal__value">${statusBadges[order.status] || order.status}</span>
                </div>
                <div class="modal__row" style="border-top: 2px solid var(--gray-200); padding-top: 1rem; margin-top: 1rem;">
                    <span class="modal__label" style="font-size: 18px; font-weight: 700;">Total Tagihan</span>
                    <span class="modal__value" style="font-size: 20px; color: var(--primary);">Rp. ${parseInt(order.total_harga).toLocaleString('id-ID')}</span>
                </div>
            `;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        // Close Modal
        function closeModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Reorder Function
        function reorderOrder(orderId) {
            if (confirm('Tambahkan semua item dari pesanan ini ke keranjang?')) {
                // Redirect to reorder handler (buat file reorder.php nanti)
                window.location.href = `reorder.php?order_id=${orderId}`;
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>

</body>
</html>