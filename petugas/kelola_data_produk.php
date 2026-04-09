<?php
session_start();
include '../config.php';

// 🔐 Cek Sesi Petugas
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php"); exit();
}

// ✅ PROSES SIMPAN/UPDATE PRODUK
if (isset($_POST['simpan'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    
    // Handle Upload Gambar
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    $gambar_baru = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $ekstensi = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar_baru = 'produk_' . time() . '.' . $ekstensi;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../uploads/$gambar_baru");
        
        // Hapus gambar lama jika ada
        if (!empty($gambar_lama) && file_exists("../uploads/$gambar_lama")) {
            unlink("../uploads/$gambar_lama");
        }
    } else {
        $gambar_baru = $gambar_lama;
    }
    
    if ($id > 0) {
        // Update Produk
        mysqli_query($koneksi, "UPDATE products SET 
            nama_produk='$nama', deskripsi='$deskripsi', harga='$harga', 
            stok='$stok', gambar='$gambar_baru' WHERE id=$id");
        $pesan = "✅ Produk berhasil diupdate!";
    } else {
        // Tambah Produk Baru
        mysqli_query($koneksi, "INSERT INTO products (nama_produk, deskripsi, harga, stok, gambar) 
            VALUES ('$nama', '$deskripsi', '$harga', '$stok', '$gambar_baru')");
        $pesan = "✅ Produk baru berhasil ditambahkan!";
    }
}

// ✅ HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM products WHERE id=$id"));
    if ($cek['gambar'] && file_exists("../uploads/" . $cek['gambar'])) {
        unlink("../uploads/" . $cek['gambar']);
    }
    mysqli_query($koneksi, "DELETE FROM products WHERE id=$id");
    header("Location: kelola_data_produk.php?msg=deleted");
    exit();
}

// Mode Edit
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM products WHERE id=" . (int)$_GET['edit']));
}

// Ambil Data Produk
$produk = mysqli_query($koneksi, "SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Petugas Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --danger: #ef4444; --dark: #1e293b; --gray: #64748b; --border: #e2e8f0;
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
        
        /* Grid Layout */
        .grid-layout { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
        
        /* Card */
        .card { background: white; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 2px solid var(--border); }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 24px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
        
        /* Form */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; color: var(--gray); }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 2px solid var(--border); border-radius: 8px; font-size: 14px; transition: 0.2s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .img-preview { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; background: #f8fafc; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: var(--gray); text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .prod-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: #f1f5f9; }
        .stok-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .stok-aman { background: #d1fae5; color: #065f46; }
        .stok-habis { background: #fee2e2; color: #991b1b; }
        
        /* Buttons */
        .btn { padding: 10px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 10px; font-size: 12px; }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .grid-layout { grid-template-columns: 1fr; }
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
                <h1 class="page-title"><i class="fas fa-box-open"></i> Kelola Data Produk</h1>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-error">✅ Produk berhasil dihapus!</div>
            <?php endif; ?>
            <?php if(isset($pesan)): ?>
                <div class="alert alert-success"><?= $pesan ?></div>
            <?php endif; ?>
            
            <div class="grid-layout">
                <!-- Form Tambah/Edit -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= isset($edit) ? 'edit' : 'plus-circle' ?>"></i>
                            <?= isset($edit) ? 'Edit' : 'Tambah' ?> Produk
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if(isset($edit)): ?>
                                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                                <input type="hidden" name="gambar_lama" value="<?= $edit['gambar'] ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Nama Produk *</label>
                                <input type="text" name="nama" value="<?= $edit['nama_produk'] ?? '' ?>" required placeholder="Contoh: Laptop ASUS VivoBook">
                            </div>
                            
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" placeholder="Spesifikasi atau keterangan produk..."><?= $edit['deskripsi'] ?? '' ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Harga (Rp) *</label>
                                <input type="number" name="harga" value="<?= $edit['harga'] ?? '' ?>" required min="0" step="1000">
                            </div>
                            
                            <div class="form-group">
                                <label>Stok *</label>
                                <input type="number" name="stok" value="<?= $edit['stok'] ?? '' ?>" required min="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Gambar Produk</label>
                                <?php if(isset($edit) && !empty($edit['gambar'])): ?>
                                    <img src="../uploads/<?= $edit['gambar'] ?>" class="img-preview" alt="Preview">
                                <?php endif; ?>
                                <input type="file" name="gambar" accept="image/*">
                                <small style="color: var(--gray);">Format: JPG, PNG, WEBP. Kosongkan jika tidak diganti.</small>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" name="simpan" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <?php if(isset($edit)): ?>
                                    <a href="kelola_data_produk.php" class="btn btn-primary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Daftar Produk -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Daftar Produk (<?= mysqli_num_rows($produk) ?>)</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama Produk</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = mysqli_fetch_assoc($produk)): ?>
                                <tr>
                                    <td><img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>" class="prod-img" onerror="this.src='https://via.placeholder.com/50?text=No'"></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                        <small style="color: var(--gray); font-size: 12px;"><?= substr(htmlspecialchars($p['deskripsi']), 0, 40) ?>...</small>
                                    </td>
                                    <td><strong>Rp <?= number_format($p['harga'], 0, ',', '.') ?></strong></td>
                                    <td>
                                        <span class="stok-badge <?= $p['stok'] > 0 ? 'stok-aman' : 'stok-habis' ?>">
                                            <?= $p['stok'] > 0 ? $p['stok'] . ' Unit' : 'Habis' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus produk <?= htmlspecialchars($p['nama_produk']) ?>?')" style="margin-left: 5px;">
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