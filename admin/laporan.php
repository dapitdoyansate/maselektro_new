<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil parameter tab aktif
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'transaksi';

// Query berdasarkan tab yang dipilih
if ($active_tab == 'transaksi') {
    $query = mysqli_query($koneksi, "
        SELECT orders.*, users.username as user_name 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.id 
        ORDER BY orders.id DESC
    ");
    $table_headers = ['ID', 'User', 'Total', 'Status', 'Tanggal'];
    $title = 'Laporan Transaksi';
} elseif ($active_tab == 'penjualan') {
    $query = mysqli_query($koneksi, "
        SELECT orders.*, users.username as user_name 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.id 
        WHERE orders.status = 'success'
        ORDER BY orders.id DESC
    ");
    $table_headers = ['ID', 'User', 'Total', 'Status', 'Tanggal'];
    $title = 'Laporan Penjualan';
} else {
    $query = mysqli_query($koneksi, "SELECT * FROM products ORDER BY id DESC");
    $table_headers = ['ID', 'Nama Produk', 'Harga', 'Stok', 'Keterangan'];
    $title = 'Laporan Stok';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan - Admin Panel</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; min-height: 100vh; }
        a { text-decoration: none; }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background-color: #435663;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        .sidebar-title span { color: #2563eb; }

        .sidebar-menu { padding: 20px 0; }

        .menu-item {
            display: block;
            padding: 15px 20px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .menu-item:hover { background-color: rgba(255,255,255,0.1); }

        .menu-item.active {
            background-color: #2563eb;
            box-shadow: 0 2px 3px rgba(0,0,0,0.3);
        }

        /* Dropdown */
        .dropdown-toggle {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-right: 20px;
        }

        .dropdown-toggle::after {
            content: '▼';
            font-size: 12px;
            transition: transform 0.3s;
        }

        .dropdown-toggle.active::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: rgba(0,0,0,0.2);
        }

        .dropdown-menu.show {
            max-height: 500px;
        }

        .dropdown-item {
            display: block;
            padding: 12px 20px 12px 40px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .dropdown-item:hover {
            background-color: rgba(255,255,255,0.15);
            color: #2563eb;
        }

        .dropdown-item.active {
            background-color: #2563eb;
            color: #ffffff;
        }

        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 20px;
            color: #ff0000;
            font-weight: 800;
            font-size: 18px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .logout-btn img { width: 24px; height: 24px; margin-right: 10px; }

        /* Main Content */
        .main-content {
            margin-left: 300px;
            flex: 1;
            padding: 20px;
        }

        /* Header */
        .header {
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Tabs */
        .tabs-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }

        .tab-btn.active {
            background-color: #2563eb;
            color: #ffffff;
        }

        .tab-btn:not(.active) {
            background-color: #ffffff;
            color: #0f172a;
        }

        .tab-btn:hover:not(.active) {
            background-color: #f3f4f6;
        }

        /* Report Container */
        .report-container {
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            padding: 20px;
            min-height: 600px;
        }

        .report-header {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            margin-bottom: 15px;
        }

        .report-title {
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
        }

        /* Table */
        .table-header {
            background-color: rgba(217, 217, 217, 0.5);
            display: grid;
            grid-template-columns: 80px 150px 180px 150px 150px;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            border-radius: 5px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 80px 150px 180px 150px 150px;
            padding: 15px 20px;
            align-items: center;
            border: 1px solid #000000;
            background-color: #ffffff;
            margin-top: 1px;
            transition: background 0.3s;
        }

        .table-row:hover { background-color: #f9fafb; }

        .table-cell {
            font-size: 14px;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .status-success { background-color: #9dfbb4; color: #00d71c; }
        .status-pending { background-color: #fbbf24; color: #000000; }
        .status-cancelled { background-color: #fecaca; color: #e02a2a; }

        /* Stock Badge */
        .stock-badge {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .stock-available { background-color: #9dfbb4; color: #00d71c; }
        .stock-low { background-color: #fbbf24; color: #000000; }
        .stock-out { background-color: #fecaca; color: #e02a2a; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 250px; }
            .main-content { margin-left: 250px; }
            .table-header, .table-row {
                grid-template-columns: 60px 120px 150px 120px 120px;
                font-size: 12px;
            }
            .tabs-container {
                flex-direction: column;
            }
            .tab-btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .report-container { overflow-x: auto; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Admin <span>Panel</span></div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            
            <!-- Dropdown Menu -->
            <div class="dropdown-toggle menu-item" onclick="toggleDropdown()">
                📁 Kelola Data
            </div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="kelola_data_user.php" class="dropdown-item">👥 Kelola Data User</a>
                <a href="kelola_data_petugas.php" class="dropdown-item">👨‍💼 Kelola Data Petugas</a>
                <a href="kelola_data_produk.php" class="dropdown-item">📦 Kelola Data Produk</a>
                <a href="kelola_data_transaksi.php" class="dropdown-item">💳 Kelola Data Transaksi</a>
                <a href="laporan.php" class="dropdown-item active">📄 Laporan</a>
                <a href="backup_data.php" class="dropdown-item">💾 Backup Data</a>
                <a href="restore_data.php" class="dropdown-item">🔄 Restore Data</a>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/EifoE2bUkC.png" alt="Logout">
            Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <span class="header-title">Laporan</span>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
            <a href="?tab=transaksi" class="tab-btn <?php echo $active_tab == 'transaksi' ? 'active' : ''; ?>">
                Laporan Transaksi
            </a>
            <a href="?tab=penjualan" class="tab-btn <?php echo $active_tab == 'penjualan' ? 'active' : ''; ?>">
                Laporan Penjualan
            </a>
            <a href="?tab=stok" class="tab-btn <?php echo $active_tab == 'stok' ? 'active' : ''; ?>">
                Laporan Stok
            </a>
        </div>

        <!-- Report Container -->
        <div class="report-container">
            <div class="report-header">
                <span class="report-title"><?php echo $title; ?></span>
            </div>

            <!-- Table Header -->
            <div class="table-header">
                <?php foreach($table_headers as $header): ?>
                    <div><?php echo $header; ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Table Rows -->
            <?php 
            while($row = mysqli_fetch_assoc($query)): 
            ?>
            <div class="table-row">
                <div class="table-cell"><?php echo $row['id']; ?></div>
                
                <?php if($active_tab == 'stok'): ?>
                    <div class="table-cell"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                    <div class="table-cell">Rp. <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                    <div class="table-cell">
                        <?php 
                        $stock_class = '';
                        if($row['stok'] > 20) $stock_class = 'stock-available';
                        elseif($row['stok'] > 0) $stock_class = 'stock-low';
                        else $stock_class = 'stock-out';
                        ?>
                        <span class="stock-badge <?php echo $stock_class; ?>">
                            <?php echo $row['stok']; ?>
                        </span>
                    </div>
                    <div class="table-cell"><?php echo substr($row['deskripsi'], 0, 30); ?>...</div>
                <?php else: ?>
                    <div class="table-cell"><?php echo htmlspecialchars($row['user_name'] ?? 'Guest'); ?></div>
                    <div class="table-cell">Rp. <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></div>
                    <div class="table-cell">
                        <?php 
                        $status_class = '';
                        if($row['status'] == 'success') $status_class = 'status-success';
                        elseif($row['status'] == 'pending') $status_class = 'status-pending';
                        else $status_class = 'status-cancelled';
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                    <div class="table-cell"><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>

    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const toggle = document.querySelector('.dropdown-toggle');
            
            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // Auto expand dropdown if active item is inside
        document.addEventListener('DOMContentLoaded', function() {
            const activeDropdownItem = document.querySelector('.dropdown-item.active');
            if (activeDropdownItem) {
                document.getElementById('dropdownMenu').classList.add('show');
                document.querySelector('.dropdown-toggle').classList.add('active');
            }
        });
    </script>

</body>
</html> 