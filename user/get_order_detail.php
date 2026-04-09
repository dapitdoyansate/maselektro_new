<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login']);
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
    exit();
}

$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM orders WHERE id = $order_id"));

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
    exit();
}

$items = [];
$q = mysqli_query($koneksi, "SELECT od.*, p.nama_produk FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = $order_id");
while($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
?>