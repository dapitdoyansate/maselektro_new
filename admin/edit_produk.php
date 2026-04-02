<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? '';
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM products WHERE id = '$id'"));

if (!$produk) {
    header("Location: kelola_data_produk.php");
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
        
        // Hapus gambar lama
        if ($produk['gambar'] != 'default.jpg' && file_exists($upload_dir . $produk['gambar'])) {
            unlink($upload_dir . $produk['gambar']);
        }
    } else {
        $gambar_name = $produk['gambar'];
    }

    $query = "UPDATE products SET nama_produk='$nama_produk', harga='$harga', stok='$stok', deskripsi='$deskripsi', gambar='$gambar_name' WHERE id='$id'";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: kelola_data_produk.php?status=updated");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Produk - Admin Panel</title>
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
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #6b7280; text-decoration: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .current-image { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Edit Produk</div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="nama_produk" class="form-input" value="<?php echo htmlspecialchars($produk['nama_produk']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" class="form-input" value="<?php echo $produk['harga']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" class="form-input" value="<?php echo $produk['stok']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-textarea"><?php echo htmlspecialchars($produk['deskripsi']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Gambar Produk Saat Ini</label><br>
                <img src="../uploads/<?php echo $produk['gambar']; ?>" alt="Produk" class="current-image">
            </div>

            <div class="form-group">
                <label>Upload Gambar Baru</label>
                <input type="file" name="gambar" class="form-input" accept="image/*">
                <small style="color: #6b7280;">Kosongkan jika tidak ingin mengubah gambar</small>
            </div>

            <button type="submit" class="btn-submit">Update</button>
        </form>
        <a href="kelola_data_produk.php" class="btn-back">← Kembali</a>
    </div>
</body>
</html>