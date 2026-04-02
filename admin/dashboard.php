<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data dinamis dari database
$query_users = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$data_users = mysqli_fetch_assoc($query_users);
$total_users = $data_users['total'];

$query_products = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products");
if($query_products){
    $data_products = mysqli_fetch_assoc($query_products);
    $total_products = $data_products['total'];
} else {
    $total_products = 0;
}

$query_payments = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders");
if($query_payments){
    $data_payments = mysqli_fetch_assoc($query_payments);
    $total_payments = $data_payments['total'];
} else {
    $total_payments = 0;
}

$tanggal_hari_ini = date('d F Y');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - MasElektro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap" />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        a { text-decoration: none; }
        
        .main-container {
            width: 100%;
            max-width: 1400px;
            height: auto;
            min-height: 100vh;
            background-color: #ffffff;
            position: relative;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: #435663;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin: 16px 0 0 11px;
        }

        .sidebar-title span {
            color: #2563eb;
        }

        .sidebar-menu {
            margin-top: 40px;
            padding: 0;
        }

        .sidebar-item {
            display: block;
            height: 45px;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            padding-left: 20px;
            white-space: nowrap;
            line-height: 45px;
            transition: background 0.3s;
        }

        .sidebar-item:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar-item.active {
            background-color: #2563eb;
            box-shadow: 0 2px 3px 0 rgba(0, 0, 0, 0.3);
        }

        /* Dropdown Menu */
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

        /* Logout Button */
        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .logout-btn span {
            font-size: 20px;
            font-weight: 800;
            color: #ff0000;
            margin-right: 10px;
        }

        .logout-icon {
            width: 30px;
            height: 30px;
            background-position: center;
            background-image: url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/ZW70dKRzLg.png);
            background-size: cover;
            background-repeat: no-repeat;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px;
        }

        /* Header */
        .header {
            width: 100%;
            height: 50px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            padding-left: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .header-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-text {
            font-size: 40px;
            font-weight: 700;
            color: #000000;
        }

        .welcome-text span {
            color: #2563eb;
        }

        /* Stats Cards */
        .stats-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .stat-card {
            width: 280px;
            height: 150px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.15);
            padding: 20px;
            position: relative;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .stat-label {
            position: absolute;
            top: 25px;
            left: 80px;
            font-size: 18px;
            font-weight: 700;
            color: #000000;
        }

        .stat-value {
            position: absolute;
            bottom: 30px;
            left: 80px;
            font-size: 32px;
            font-weight: 800;
            color: #2563eb;
        }

        /* Backup Info */
        .backup-info {
            width: 100%;
            max-width: 500px;
            height: 35px;
            background-color: rgba(217, 217, 217, 0.4);
            border-radius: 5px;
            border: 1px solid rgba(0, 0, 0, 0.8);
            box-shadow: 8px 8px 4px 0 rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            padding-left: 15px;
            font-size: 18px;
            font-weight: 500;
            color: #000000;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
            .stats-container {
                flex-direction: column;
                align-items: center;
            }
            .welcome-text {
                font-size: 28px;
            }
        }
    </style>
  </head>
  <body>
    <div class="main-container">
      
      <!-- Sidebar -->
      <div class="sidebar">
        <div class="sidebar-title">Admin <span>Panel</span></div>
        
        <div class="sidebar-menu">
          <!-- Dashboard Link -->
          <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            📊 Dashboard
          </a>

          <!-- Dropdown Menu -->
          <div class="dropdown-toggle sidebar-item" onclick="toggleDropdown()">
            📁 Kelola Data
          </div>
          <div class="dropdown-menu" id="dropdownMenu">
            <a href="kelola_data_user.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_data_user.php' ? 'active' : ''; ?>">
                👥 Kelola Data User
            </a>
            <a href="kelola_data_petugas.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_data_petugas.php' ? 'active' : ''; ?>">
                👨‍💼 Kelola Data Petugas
            </a>
            <a href="kelola_data_produk.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_data_produk.php' ? 'active' : ''; ?>">
                📦 Kelola Data Produk
            </a>
            <a href="kelola_data_transaksi.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_data_transaksi.php' ? 'active' : ''; ?>">
                💳 Kelola Data Transaksi
            </a>
            <a href="laporan.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                📄 Laporan
            </a>
            <a href="backup_data.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'backup_data.php' ? 'active' : ''; ?>">
                💾 Backup Data
            </a>
            <a href="restore_data.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'restore_data.php' ? 'active' : ''; ?>">
                🔄 Restore Data
            </a>
          </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
          <span>Logout</span>
          <div class="logout-icon"></div>
        </a>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        
        <!-- Header -->
        <div class="header">
          <span class="header-title">Dashboard</span>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
          <div class="welcome-text">Selamat Datang, <span><?php echo $_SESSION['username']; ?></span></div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
          <!-- Total User -->
          <div class="stat-card">
            <div class="stat-icon" style="background-image: url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/92Owuhudhc.png);"></div>
            <div class="stat-label">Total User</div>
            <div class="stat-value"><?php echo number_format($total_users); ?></div>
          </div>

          <!-- Total Produk -->
          <div class="stat-card">
            <div class="stat-icon" style="background-image: url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/7EyzS9dQNc.png);"></div>
            <div class="stat-label">Total Produk</div>
            <div class="stat-value"><?php echo number_format($total_products); ?></div>
          </div>

          <!-- Total Payment -->
          <div class="stat-card">
            <div class="stat-icon" style="background-image: url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/hRtmVVBnFt.png);"></div>
            <div class="stat-label">Total Payment</div>
            <div class="stat-value"><?php echo number_format($total_payments); ?></div>
          </div>
        </div>

        <!-- Backup Info -->
        <div class="backup-info">
          Backup Terakhir: <?php echo $tanggal_hari_ini; ?>
        </div>

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