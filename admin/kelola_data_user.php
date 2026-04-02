<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cegah admin menghapus dirinya sendiri
    if ($id != $_SESSION['id']) {
        mysqli_query($koneksi, "DELETE FROM users WHERE id = '$id'");
        header("Location: kelola_user.php?status=deleted");
        exit();
    }
}

// Ambil data user dari database
$query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Data User - Admin Panel</title>
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

        .menu-item i { margin-right: 10px; }

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
            background-color: #1d4ed8;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .btn-add:hover { background-color: #1e40af; }

        /* Table */
        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background-color: rgba(217, 217, 217, 0.5);
            display: grid;
            grid-template-columns: 80px 200px 250px 120px 150px;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-row {
            display: grid;
            grid-template-columns: 80px 200px 250px 120px 150px;
            padding: 15px 20px;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
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

        /* Role Badge */
        .role-badge {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .role-admin { background-color: #2563eb; color: #ffffff; }
        .role-petugas { background-color: #00d71c; color: #000000; }
        .role-user { background-color: #e02a2a; color: #ffffff; }

        /* Action Buttons */
        .btn-action {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
        }

        .btn-edit {
            background-color: #d9d9d9;
            color: #000000;
        }

        .btn-delete {
            background-color: #d9d9d9;
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
                grid-template-columns: 60px 150px 200px 100px 120px;
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .table-container { overflow-x: auto; }
            .table-header, .table-row {
                grid-template-columns: repeat(5, minmax(100px, 1fr));
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
            <a href="kelola_data_user.php" class="menu-item active">👥 Kelola Data User</a>
            <a href="kelola_data_petugas.php" class="menu-item">👨‍💼 Kelola Data Petugas</a>
            <a href="kelola_data_produk.php" class="menu-item">📦 Kelola Data Produk</a>
            <a href="kelola_data_transaksi.php" class="menu-item">💳 Kelola Data Transaksi</a>
            <a href="laporan.php" class="menu-item">📄 Laporan</a>
            <a href="backup_data.php" class="menu-item">💾 Backup Data</a>
            <a href="restore_data.php" class="menu-item">🔄 Restore Data</a>
        </div>

        <a href="../logout.php" class="logout-btn">
            <img src="https://cdn-icons-png.flaticon.com/512/2920/2920323.png" alt="Logout">
            Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <div class="header-title">Kelola Data User</div>
            <!-- <a href="tambah_user.php" class="btn-add">Tambah User+</a> -->
        </div>

        <!-- Alert -->
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'deleted'): ?>
                <div class="alert alert-success">User berhasil dihapus!</div>
            <?php elseif($_GET['status'] == 'updated'): ?>
                <div class="alert alert-success">User berhasil diupdate!</div>
            <?php elseif($_GET['status'] == 'added'): ?>
                <div class="alert alert-success">User berhasil ditambahkan!</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>ID</div>
                <div>Nama</div>
                <div>Email</div>
                <div>Role</div>
                <div>Aksi</div>
            </div>

            <?php while($row = mysqli_fetch_assoc($query)): ?>
            <div class="table-row">
                <div class="table-cell"><?php echo $row['id']; ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($row['username']); ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($row['email']); ?></div>
                <div class="table-cell">
                    <?php 
                    $role_class = '';
                    if($row['role'] == 'admin') $role_class = 'role-admin';
                    elseif($row['role'] == 'petugas') $role_class = 'role-petugas';
                    else $role_class = 'role-user';
                    ?>
                    <span class="role-badge <?php echo $role_class; ?>">
                        <?php echo ucfirst($row['role']); ?>
                    </span>
                </div>
                <div class="table-cell">
                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">✏️ Edit</a>
                    <?php if($row['id'] != $_SESSION['id']): ?>
                        <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus user ini?')">🗑️ Hapus</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    </div>

</body>
</html>