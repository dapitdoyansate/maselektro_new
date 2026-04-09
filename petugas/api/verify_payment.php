<?php
session_start();
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'petugas') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$order_id = (int)($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($order_id === 0 || !in_array($action, ['confirm', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if ($action === 'confirm') {
    // ✅ UPDATE status jadi 'paid' - ini otomatis muncul di riwayat user
    mysqli_query($koneksi, "UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = $order_id AND status = 'pending_payment'");
    echo json_encode(['success' => true, 'message' => 'Pembayaran dikonfirmasi']);
} else {
    // Batalkan & kembalikan stok
    $items = mysqli_query($koneksi, "SELECT product_id, quantity FROM order_details WHERE order_id = $order_id");
    while($item = mysqli_fetch_assoc($items)) {
        mysqli_query($koneksi, "UPDATE products SET stok = stok + {$item['quantity']} WHERE id = {$item['product_id']}");
    }
    mysqli_query($koneksi, "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = $order_id");
    echo json_encode(['success' => true, 'message' => 'Order dibatalkan']);
}
?>