<?php
session_start();
include '../config.php';

// 🔐 Cek Sesi Petugas
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php"); exit();
}

// ✅ HAPUS USER
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $id AND role = 'user'");
    header("Location: kelola_data_user.php?msg=deleted");
    exit();
}

// ✅ TAMBAH/EDIT USER
if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Edit User
        $id = (int)$_POST['id'];
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', email='$email', password='$hash', no_hp='$no_hp', alamat='$alamat' WHERE id=$id");
        } else {
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', email='$email', no_hp='$no_hp', alamat='$alamat' WHERE id=$id");
        }
        $pesan = "✅ Data user berhasil diupdate!";
    } else {
        // Tambah User Baru
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, password, no_hp, alamat, role) VALUES ('$nama', '$email', '$hash', '$no_hp', '$alamat', 'user')");
        $pesan = "✅ User baru berhasil ditambahkan!";
    }
}

// Ambil Data User
$users = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'user' ORDER BY id DESC");

// Mode Edit
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $id"));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Petugas Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --dark: #1e293b;
            --light: #f8fafc; --gray: #64748b; --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: var(--dark); }
        .wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--dark); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .brand-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 20px; }
        .brand-text { color: white; font-size: 20px; font-weight: 700; }
        .sidebar-menu { padding: 20px 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #94a3b8; text-decoration: none; border-radius: 10px; margin-bottom: 6px; transition: all 0.3s; font-weight: 500; }
        .menu-item:hover { background: rgba(255,255,255,0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .menu-item i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout { display: flex; align-items: center; gap: 10px; color: #fca5a5; text-decoration: none; padding: 12px; border-radius: 10px; transition: all 0.3s; }
        .logout:hover { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        
        /* Main Content */
        .main-content { margin-left: 280px; flex: 1; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: var(--primary); }
        
        /* Card & Form */
        .card { background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 20px 24px; border-bottom: 2px solid var(--border); }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 24px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; }
        th { padding: 16px 20px; text-align: left; font-size: 13px; font-weight: 700; color: var(--gray); text-transform: uppercase; }
        td { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid var(--border); }
        tr:hover { background: #f8fafc; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 2px solid var(--border); border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-icon">M</div>
                <div class="brand-text">Petugas Panel</div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="kelola_pesanan.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_pesanan.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-bag"></i> Kelola Pesanan
                </a>
                <a href="kelola_data_user.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_data_user.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <a href="kelola_data_produk.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kelola_data_produk.php' ? 'active' : '' ?>">
                    <i class="fas fa-box-open"></i> Kelola Produk
                </a>
                <a href="laporan.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-users"></i> Kelola Data User</h1>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success">✅ User berhasil dihapus!</div>
            <?php endif; ?>
            <?php if(isset($pesan)): ?>
                <div class="alert alert-success"><?= $pesan ?></div>
            <?php endif; ?>
            
            <div class="grid-2">
                <!-- Form Tambah/Edit User -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= isset($edit) ? 'edit' : 'user-plus' ?>"></i>
                            <?= isset($edit) ? 'Edit' : 'Tambah' ?> User
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if(isset($edit)): ?>
                                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="nama" value="<?= $edit['nama_lengkap'] ?? '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" value="<?= $edit['email'] ?? '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Password <?= !isset($edit) ? '*' : '(Kosongkan jika tidak diubah)' ?></label>
                                <input type="password" name="password" <?= !isset($edit) ? 'required' : '' ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="text" name="no_hp" value="<?= $edit['no_hp'] ?? '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" rows="3"><?= $edit['alamat'] ?? '' ?></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="simpan" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <?php if(isset($edit)): ?>
                                    <a href="kelola_data_user.php" class="btn btn-primary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Daftar User -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Daftar User</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No. HP</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($u = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['no_hp'] ?? '-') ?></td>
                                    <td>
                                        <a href="?edit=<?= $u['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?hapus=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user <?= htmlspecialchars($u['nama_lengkap']) ?>?')" style="margin-left: 5px;">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>