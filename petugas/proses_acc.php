<?php
session_start();
include '../config.php';

// Cek sesi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php"); exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

if ($id > 0) {
    if ($aksi == 'acc') {
        // ✅ ACC: Ubah status jadi 'paid'
        mysqli_query($koneksi, "UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = $id");
        header("Location: dashboard.php?msg=paid");
        exit();
        
    } elseif ($aksi == 'tolak') {
        // ❌ TOLAK: Ubah status jadi 'cancelled' DAN KEMBALIKAN STOK
        
        // 1. Ambil detail produk dalam order
        $detail = mysqli_query($koneksi, "SELECT product_id, quantity FROM order_details WHERE order_id = $id");
        while($d = mysqli_fetch_assoc($detail)) {
            // 2. Kembalikan stok ke database
            mysqli_query($koneksi, "UPDATE products SET stok = stok + {$d['quantity']} WHERE id = {$d['product_id']}");
        }
        
        // 3. Batalkan order
        mysqli_query($koneksi, "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = $id");
        header("Location: dashboard.php?msg=cancelled");
        exit();
    }
}

// Jika tidak valid, kembali ke dashboard
header("Location: dashboard.php");
exit();
?>