<?php
session_start();
include '../config.php';

// Cek apakah sudah login dan role-nya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses Restore
if (isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
        $allowed = ['sql'];
        $filename = $_FILES['backup_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $backup_dir = '../backups/';
            // Pastikan folder backups ada
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0777, true);
            }
            
            $filepath = $backup_dir . $filename;
            
            // Upload file ke folder backups
            if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $filepath)) {
                // Baca file SQL
                $sql = file_get_contents($filepath);
                
                // Eksekusi query SQL (multi query)
                if (mysqli_multi_query($koneksi, $sql)) {
                    // Catat riwayat restore
                    $file_size = filesize($filepath) / 1024 / 1024;
                    $tanggal = date('Y-m-d');
                    $waktu = date('H:i');
                    
                    mysqli_query($koneksi, "INSERT INTO restore_history (filename, file_size, tanggal, waktu, status) VALUES ('$filename', '$file_size', '$tanggal', '$waktu', 'Success')");
                    
                    header("Location: restore_data.php?status=success&t=".time());
                    exit();
                } else {
                    header("Location: restore_data.php?status=error&msg=".urlencode(mysqli_error($koneksi))."&t=".time());
                    exit();
                }
            } else {
                header("Location: restore_data.php?status=upload_error&t=".time());
                exit();
            }
        } else {
            header("Location: restore_data.php?status=invalid_file&t=".time());
            exit();
        }
    } else {
        header("Location: restore_data.php?status=no_file&t=".time());
        exit();
    }
}

// Ambil riwayat restore
$restore_history = mysqli_query($koneksi, "SELECT * FROM restore_history ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Restore Data - Admin Panel</title>
    
    <!-- Anti Cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #475569;
            --bg-body: #f1f5f9;
            --bg-sidebar: #1e293b;
            --text-light: #f8fafc;
            --text-dark: #0f172a;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px;
            background-color: var(--bg-sidebar);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-light);
        }

        .sidebar-menu {
            padding: 20px 15px;
            flex: 1;
            overflow-y: auto;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #94a3b8;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .menu-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .menu-item.active { background-color: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4); }
        .menu-item i { width: 24px; margin-right: 10px; text-align: center; }

        /* Dropdown */
        .dropdown-toggle { justify-content: space-between; }
        .dropdown-toggle::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; border: none; transition: transform 0.3s; }
        .dropdown-toggle.active::after { transform: rotate(180deg); }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding-left: 10px;
        }
        .dropdown-menu.show { max-height: 500px; padding-top: 5px; }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 16px 10px 40px;
            color: #94a3b8;
            font-size: 14px;
            border-radius: 6px;
            margin-bottom: 2px;
        }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: var(--text-light); }
        .dropdown-item.active { background-color: rgba(37, 99, 235, 0.2); color: var(--primary); font-weight: 600; }
        .dropdown-item i { width: 20px; margin-right: 8px; font-size: 12px; }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            color: #fca5a5;
            font-weight: 600;
            transition: color 0.2s;
        }
        .logout-btn:hover { color: var(--danger); }
        .logout-btn i { margin-right: 10px; font-size: 18px; }

        /* --- Main Content --- */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
            transition: margin-left 0.3s;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .mobile-toggle {
            display: none;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
            background: none;
            border: none;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        /* Warning Section */
        .warning-box {
            background-color: #fef2f2;
            border-left: 5px solid var(--danger);
            padding: 20px;
            border-radius: 8px;
            display: flex;
            gap: 15px;
            align-items: start;
        }
        .warning-icon { color: var(--danger); font-size: 24px; margin-top: 2px; }
        .warning-content h4 { color: #991b1b; margin-bottom: 5px; font-size: 16px; }
        .warning-content p { color: #7f1d1d; font-size: 14px; line-height: 1.5; }

        /* Upload Area */
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background-color: #f8fafc;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        .upload-area:hover { border-color: var(--primary); background-color: #eff6ff; }
        .upload-area.has-file { border-color: var(--success); background-color: #f0fdf4; }

        .upload-icon-wrapper {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .upload-icon { font-size: 24px; color: var(--primary); }
        .upload-text { font-size: 18px; font-weight: 600; color: var(--text-dark); }
        .upload-subtext { font-size: 14px; color: #64748b; margin-top: 5px; }
        
        input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-info {
            margin-top: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            color: var(--success);
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Buttons */
        .btn-restore {
            width: 100%;
            background-color: var(--primary);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-restore:hover { background-color: var(--primary-dark); }
        .btn-restore:disabled { background-color: #94a3b8; cursor: not-allowed; }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

        /* Table */
        .table-container { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; background: #f8fafc; color: #475569; font-weight: 600; font-size: 14px; border-bottom: 2px solid #e2e8f0; }
        .table td { padding: 15px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: 14px; }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background: #f8fafc; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #059669; }
        .badge-failed { background: #fee2e2; color: #dc2626; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <div class="sidebar-title">Admin Panel</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            
            <div class="menu-item dropdown-toggle active" onclick="toggleDropdown()">
                <span><i class="fas fa-folder-open"></i> Kelola Data</span>
            </div>
            <div class="dropdown-menu show" id="dropdownMenu">
                <a href="kelola_data_user.php" class="dropdown-item"><i class="fas fa-user"></i> Data User</a>
                <a href="kelola_data_petugas.php" class="dropdown-item"><i class="fas fa-user-tie"></i> Data Petugas</a>
                <a href="kelola_data_produk.php" class="dropdown-item"><i class="fas fa-box"></i> Data Produk</a>
                <a href="kelola_data_transaksi.php" class="dropdown-item"><i class="fas fa-receipt"></i> Data Transaksi</a>
                <a href="laporan.php" class="dropdown-item"><i class="fas fa-file-alt"></i> Laporan</a>
                <a href="backup_data.php" class="dropdown-item"><i class="fas fa-download"></i> Backup Data</a>
                <a href="restore_data.php" class="dropdown-item active"><i class="fas fa-upload"></i> Restore Data</a>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mobile Header -->
        <div class="page-header">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">Restore Database</h1>
            <div style="width: 24px;"></div> <!-- Spacer -->
        </div>

        <!-- Alerts -->
        <?php if(isset($_GET['status'])): ?>
            <?php 
            $msg = ''; $type = '';
            if($_GET['status'] == 'success') { $msg = 'Restore berhasil! Data telah dikembalikan.'; $type = 'success'; }
            elseif($_GET['status'] == 'error') { $msg = 'Restore gagal! ' . (isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Terjadi kesalahan.'); $type = 'error'; }
            elseif($_GET['status'] == 'upload_error') { $msg = 'Gagal mengupload file backup.'; $type = 'error'; }
            elseif($_GET['status'] == 'invalid_file') { $msg = 'File harus berformat .sql!'; $type = 'warning'; }
            elseif($_GET['status'] == 'no_file') { $msg = 'Pilih file backup terlebih dahulu!'; $type = 'warning'; }
            ?>
            <div class="alert alert-<?php echo $type; ?>">
                <i class="fas fa-<?php echo $type == 'success' ? 'check-circle' : ($type == 'error' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- Warning Box -->
        <div class="card">
            <div class="warning-box">
                <div class="warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="warning-content">
                    <h4>Peringatan Keras!</h4>
                    <p>Proses ini akan menimpa seluruh data sistem saat ini. Pastikan file backup (.sql) yang Anda pilih benar dan aman. Data lama akan hilang.</p>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="card">
            <form action="" method="POST" enctype="multipart/form-data" id="restoreForm">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon-wrapper">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    </div>
                    <div class="upload-text">Pilih File Backup</div>
                    <div class="upload-subtext">Format file harus .sql</div>
                    <div class="file-info" id="fileInfo">
                        <i class="fas fa-file-code"></i> <span id="fileNameDisplay">filename.sql</span>
                    </div>
                    <input type="file" name="backup_file" id="backup_file" accept=".sql" required onchange="updateFileName()">
                </div>

                <button type="submit" name="restore" class="btn-restore" id="restoreBtn" disabled>
                    <i class="fas fa-sync-alt"></i> Mulai Restore Sekarang
                </button>
            </form>
        </div>

        <!-- History Section -->
        <div class="card">
            <h3 style="margin-bottom: 20px; color: var(--text-dark); font-size: 18px;">📜 Riwayat Restore Terakhir</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Ukuran</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($restore_history) > 0):
                            while($row = mysqli_fetch_assoc($restore_history)): 
                        ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['filename']); ?></td>
                            <td><?php echo number_format($row['file_size'], 2); ?> MB</td>
                            <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['waktu']; ?> WIB</td>
                            <td>
                                <span class="badge <?php echo $row['status'] == 'Success' ? 'badge-success' : 'badge-failed'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">
                                <i class="fas fa-history" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Belum ada riwayat restore
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Dropdown Logic
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const toggle = document.querySelector('.dropdown-toggle');
            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // File Upload UI Logic
        function updateFileName() {
            const input = document.getElementById('backup_file');
            const uploadArea = document.getElementById('uploadArea');
            const fileInfo = document.getElementById('fileInfo');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const restoreBtn = document.getElementById('restoreBtn');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileNameDisplay.textContent = file.name;
                uploadArea.classList.add('has-file');
                fileInfo.style.display = 'flex';
                restoreBtn.disabled = false;
            } else {
                uploadArea.classList.remove('has-file');
                fileInfo.style.display = 'none';
                restoreBtn.disabled = true;
            }
        }

        // Confirmation Alert
        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            const confirmed = confirm('⚠️ PERINGATAN KERAS!\n\nApakah Anda yakin ingin melakukan restore?\n\nSemua data saat ini akan DIHAPUS dan diganti dengan data backup.\n\nProses ini tidak dapat dibatalkan.');
            if (!confirmed) {
                e.preventDefault();
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target) && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>

</body>
</html>