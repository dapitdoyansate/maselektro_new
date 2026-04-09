<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_id = (int)($_POST['order_id'] ?? 0);
$user_id = (int)$_SESSION['id'];

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
    exit();
}

// Update status ke 'paid'
mysqli_query($koneksi, "
    UPDATE orders 
    SET status = 'paid', updated_at = NOW() 
    WHERE id = $order_id AND user_id = $user_id AND status = 'pending_payment'
");

if (mysqli_affected_rows($koneksi) > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengkonfirmasi pembayaran']);
}
?>