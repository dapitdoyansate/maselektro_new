<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses Backup
if (isset($_GET['action']) && $_GET['action'] == 'backup') {
    $backup_dir = '../backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $tables = array();
    $result = mysqli_query($koneksi, 'SHOW TABLES');
    while($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $return = "";
    foreach($tables as $table) {
        $result = mysqli_query($koneksi, 'SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);
        
        $return .= 'DROP TABLE IF EXISTS '.$table.';';
        $row2 = mysqli_fetch_row(mysqli_query($koneksi, 'SHOW CREATE TABLE '.$table));
        $return .= "\n\n".$row2[1].";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while($row = mysqli_fetch_row($result)) {
                $return .= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                    if ($j<($num_fields-1)) { $return .= ','; }
                }
                $return .= ");\n";
            }
        }
        $return.="\n\n\n";
    }
    
    $filename = 'backup_'.date('Y-m-d_H-i-s').'.sql';
    $handle = fopen($backup_dir.$filename,'w+');
    fwrite($handle,$return);
    fclose($handle);
    
    // Simpan riwayat backup ke database
    $file_size = filesize($backup_dir.$filename) / 1024 / 1024; // MB
    $tanggal = date('Y-m-d');
    $waktu = date('H:i');
    
    mysqli_query($koneksi, "INSERT INTO backup_history (filename, file_size, tanggal, waktu, status) VALUES ('$filename', '$file_size', '$tanggal', '$waktu', 'Success')");
    
    // FIX: Tambahkan timestamp untuk mencegah cache
    header("Location: backup_data.php?status=success&t=".time());
    exit();
}

// Hapus Backup
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $backup = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM backup_history WHERE id = '$id'"));
    
    if ($backup) {
        unlink('../backups/'.$backup['filename']);
        mysqli_query($koneksi, "DELETE FROM backup_history WHERE id = '$id'");
    }
    // FIX: Tambahkan timestamp untuk mencegah cache
    header("Location: backup_data.php?status=deleted&t=".time());
    exit();
}

// Ambil data backup terakhir
$last_backup = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM backup_history ORDER BY id DESC LIMIT 1"));

// Ambil riwayat backup
$backup_history = mysqli_query($koneksi, "SELECT * FROM backup_history ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Backup Data - Admin Panel</title>
    
    <!-- FIX: Meta tag untuk mencegah cache browser -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
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

        /* Backup Info Section */
        .backup-info-section {
            background-color: #eeeeee;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 4px #000000;
            margin-bottom: 20px;
        }

        .backup-info-title {
            font-size: 18px;
            font-weight: 600;
            color: #000000;
        }

        /* Backup Cards */
        .backup-cards {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
        }

        .backup-card {
            width: 100%;
            max-width: 450px;
            height: 120px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            padding: 20px 30px;
            display: inline-block;
            vertical-align: top;
            margin-right: 20px;
        }

        .backup-card.blue {
            background-color: #2563eb;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
        }

        .backup-card.blue:hover {
            background-color: #1d4ed8;
        }

        .backup-card-info {
            font-size: 16px;
            font-weight: 500;
            color: #000000;
            margin-bottom: 10px;
        }

        .backup-card-btn {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            height: 100%;
        }

        /* History Section */
        .history-section {
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            padding: 30px;
            min-height: 500px;
        }

        .history-title {
            font-size: 24px;
            font-weight: 600;
            color: #000000;
            text-align: center;
            margin-bottom: 20px;
        }

        /* History Table */
        .history-header {
            background-color: #d9d9d9;
            display: grid;
            grid-template-columns: 150px 150px 200px 150px 100px;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            border-radius: 5px;
        }

        .history-row {
            display: grid;
            grid-template-columns: 150px 150px 200px 150px 100px;
            padding: 15px 20px;
            align-items: center;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .history-row:hover { background-color: #f9fafb; }

        .history-cell {
            font-size: 14px;
            color: #374151;
            white-space: nowrap;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            background-color: #9dfbb4;
            color: #000000;
        }

        .btn-delete {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background-color: #fecaca;
            color: #e02a2a;
        }

        .btn-delete:hover { background-color: #fca5a5; }

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
            .backup-cards {
                flex-direction: column;
            }
            .backup-card {
                width: 100%;
                margin-bottom: 20px;
            }
            .history-header, .history-row {
                grid-template-columns: 120px 120px 150px 120px 80px;
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .history-section { overflow-x: auto; }
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
                <a href="kelola_data_petugas.php" class="dropdown-item">👨‍ Kelola Data Petugas</a>
                <a href="kelola_data_produk.php" class="dropdown-item">📦 Kelola Data Produk</a>
                <a href="kelola_data_transaksi.php" class="dropdown-item">💳 Kelola Data Transaksi</a>
                <a href="laporan.php" class="dropdown-item">📄 Laporan</a>
                <a href="backup_data.php" class="dropdown-item active">💾 Backup Data</a>
                <a href="restore_data.php" class="dropdown-item">🔄 Restore Data</a>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/PxQhfQyM4N.png" alt="Logout">
            Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <span class="header-title">Backup Data</span>
        </div>

        <!-- Backup Info -->
        <div class="backup-info-section">
            <span class="backup-info-title">📋 Informasi Backup</span>
        </div>

        <!-- Alert -->
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'success'): ?>
                <div class="alert alert-success">✅ Backup berhasil dibuat!</div>
            <?php elseif($_GET['status'] == 'deleted'): ?>
                <div class="alert alert-success">✅ Backup berhasil dihapus!</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Backup Cards -->
        <div class="backup-cards">
            <!-- Last Backup Info -->
            <div class="backup-card">
                <div class="backup-card-info">
                    <div>Terakhir Backup: <?php echo $last_backup ? date('d F Y', strtotime($last_backup['tanggal'])) : 'Belum ada backup'; ?></div>
                    <!-- <div style="margin-top: 10px;">Ukuran File: <?php echo $last_backup ? number_format($last_backup['file_size'], 2) . ' MB' : '0 MB'; ?></div> -->
                </div>
            </div>

            <!-- Backup Button -->
            <a href="?action=backup" class="backup-card blue" onclick="return confirm('Yakin ingin backup data sekarang?')">
                <div class="backup-card-btn">
                    💾 Backup Data Sekarang
                </div>
            </a>
        </div>

        <!-- History Section -->
        <div class="history-section">
            <div class="history-title">📜 Riwayat Backup</div>

            <!-- Table Header -->
            <div class="history-header">
                <div>Tanggal</div>
                <div>Waktu</div>
                <div>Ukuran</div>
                <div>Status</div>
                <div>Aksi</div>
            </div>

            <!-- Table Rows -->
            <?php 
            while($row = mysqli_fetch_assoc($backup_history)): 
            ?>
            <div class="history-row">
                <div class="history-cell"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></div>
                <div class="history-cell"><?php echo $row['waktu']; ?></div>
                <div class="history-cell"><?php echo number_format($row['file_size'], 2); ?> MB</div>
                <div class="history-cell">
                    <span class="status-badge"><?php echo $row['status']; ?></span>
                </div>
                <div class="history-cell">
                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus backup ini?')">🗑️ Hapus</a>
                </div>
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