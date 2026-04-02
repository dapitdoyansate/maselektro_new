<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Upload gambar
    $gambar = $_FILES['gambar']['name'];
    $tmp_name = $_FILES['gambar']['tmp_name'];
    
    if ($gambar) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $gambar_name = time() . '_' . $gambar;
        move_uploaded_file($tmp_name, $upload_dir . $gambar_name);
    } else {
        $gambar_name = 'default.jpg';
    }

    $query = "INSERT INTO products (nama_produk, harga, stok, deskripsi, gambar) VALUES ('$nama_produk', '$harga', '$stok', '$deskripsi', '$gambar_name')";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: kelola_data_produk.php?status=added");
        exit();
    } else {
        $error = "Gagal menambahkan produk: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Produk - Admin Panel</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        .form-title { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus, .form-textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-textarea { height: 100px; resize: vertical; }
        .form-input[type="file"] { padding: 10px; }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-submit:hover { background-color: #1d4ed8; }
        .alert { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #6b7280; text-decoration: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Tambah Produk Baru</div>
        
        <?php if(isset($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="nama_produk" class="form-input" placeholder="Contoh: Asus Vivobook Go 14" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" class="form-input" placeholder="Contoh: 5889000" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" class="form-input" placeholder="Contoh: 100" required>
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-textarea" placeholder="Deskripsi produk..."></textarea>
            </div>

            <div class="form-group">
                <label>Gambar Produk</label>
                <input type="file" name="gambar" class="form-input" accept="image/*">
            </div>

            <button type="submit" class="btn-submit">Simpan</button>
        </form>
        <a href="kelola_data_produk.php" class="btn-back">← Kembali</a>
    </div>
</body>
</html>