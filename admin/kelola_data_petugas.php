<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Hapus Petugas
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = '$id' AND role = 'petugas'");
    header("Location: kelola_data_petugas.php?status=deleted&t=".time());
    exit();
}

// Ambil data petugas dari database
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'petugas' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Data Petugas - Admin Panel</title>
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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .btn-add {
            background-color: #2563eb;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .btn-add:hover { background-color: #1d4ed8; }

        /* Table */
        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 4px rgba(0,0,0,0.25);
            overflow: hidden;
        }

        .table-header {
            background-color: rgba(217, 217, 217, 0.5);
            display: grid;
            grid-template-columns: 80px 150px 250px 150px;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
            color: #000000;
            border-bottom: 1px solid #000000;
        }

        .table-row {
            display: grid;
            grid-template-columns: 80px 150px 250px 150px;
            padding: 15px 20px;
            align-items: center;
            border-bottom: 1px solid #000000;
            transition: background 0.3s;
        }

        .table-row:hover { background-color: #f9fafb; }

        .table-cell {
            font-size: 16px;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Action Buttons */
        .btn-action {
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            background-color: #d9d9d9;
            color: #000000;
        }

        .btn-action:hover { background-color: #b0b0b0; }

        .btn-delete {
            color: #ff0000;
        }

        .btn-delete:hover { background-color: #fecaca; }

        /* Alert */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 250px; }
            .main-content { margin-left: 250px; }
            .table-header, .table-row {
                grid-template-columns: 60px 120px 200px 120px;
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .table-container { overflow-x: auto; }
            .header {
                flex-direction: column;
                gap: 15px;
            }
            .btn-add {
                width: 100%;
                text-align: center;
            }
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
                <a href="kelola_data_petugas.php" class="dropdown-item active">👨‍ Kelola Data Petugas</a>
                <a href="kelola_data_produk.php" class="dropdown-item">📦 Kelola Data Produk</a>
                <a href="kelola_data_transaksi.php" class="dropdown-item">💳 Kelola Data Transaksi</a>
                <a href="laporan.php" class="dropdown-item">📄 Laporan</a>
                <a href="backup_data.php" class="dropdown-item">💾 Backup Data</a>
                <a href="restore_data.php" class="dropdown-item">🔄 Restore Data</a>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/Wu2Dm0rDF7.png" alt="Logout">
            Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <span class="header-title">Kelola Data Petugas</span>
            <a href="tambah_petugas.php" class="btn-add">Tambah Petugas+</a>
        </div>

        <!-- Alert -->
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'deleted'): ?>
                <div class="alert alert-success">✅ Petugas berhasil dihapus!</div>
            <?php elseif($_GET['status'] == 'updated'): ?>
                <div class="alert alert-success">✅ Petugas berhasil diupdate!</div>
            <?php elseif($_GET['status'] == 'added'): ?>
                <div class="alert alert-success">✅ Petugas berhasil ditambahkan!</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>ID</div>
                <div>Nama</div>
                <div>Username</div>
                <div>Aksi</div>
            </div>

            <?php 
            while($row = mysqli_fetch_assoc($query)): 
            ?>
            <div class="table-row">
                <div class="table-cell"><?php echo $row['id']; ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($row['username']); ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($row['email']); ?></div>
                <div class="table-cell">
                    <a href="edit_petugas.php?id=<?php echo $row['id']; ?>" class="btn-action">✏️ Edit</a>
                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus petugas ini?')">🗑️ Hapus</a>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
            <div class="table-row">
                <div class="table-cell" colspan="4" style="text-align: center; color: #9ca3af;">Belum ada data petugas</div>
            </div>
            <?php endif; ?>
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