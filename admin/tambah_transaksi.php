<?php
session_start();
include '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = mysqli_real_escape_string($koneksi, $_POST['user_id']);
    $total_bayar = mysqli_real_escape_string($koneksi, $_POST['total_bayar']);
    $tanggal_transaksi = mysqli_real_escape_string($koneksi, $_POST['tanggal_transaksi']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);

    $query = "INSERT INTO orders (user_id, total_bayar, tanggal_transaksi, status) VALUES ('$user_id', '$total_bayar', '$tanggal_transaksi', '$status')";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: kelola_data_transaksi.php?status=added");
        exit();
    } else {
        $error = "Gagal menambahkan transaksi: " . mysqli_error($koneksi);
    }
}

// Ambil semua user untuk dropdown
$users = mysqli_query($koneksi, "SELECT id, username FROM users WHERE role = 'user'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Transaksi - Admin Panel</title>
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
            max-width: 500px;
        }
        .form-title { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
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
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Tambah Transaksi Baru</div>
        
        <?php if(isset($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Pilih User</label>
                <select name="user_id" class="form-select" required>
                    <option value="">-- Pilih User --</option>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Total Bayar (Rp)</label>
                <input type="number" name="total_bayar" class="form-input" placeholder="Contoh: 200000" required>
            </div>

            <div class="form-group">
                <label>Tanggal Transaksi</label>
                <input type="date" name="tanggal_transaksi" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-select" required>
                    <option value="pending">Pending</option>
                    <option value="success">Success</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Simpan</button>
        </form>
        <a href="kelola_data_transaksi.php" class="btn-back">← Kembali</a>
    </div>
</body>
</html>