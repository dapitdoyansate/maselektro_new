<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['login'])) exit();
$cid = (int)($_POST['cart_id']??0); $uid = (int)$_SESSION['id'];

if(isset($_POST['remove'])) {
    mysqli_query($koneksi, "DELETE FROM cart WHERE id=$cid AND user_id=$uid");
} elseif(isset($_POST['change'])) {
    $ch = (int)$_POST['change'];
    $curr = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT quantity FROM cart WHERE id=$cid AND user_id=$uid"));
    if($curr) {
        $nq = $curr['quantity'] + $ch;
        if($nq < 1) mysqli_query($koneksi, "DELETE FROM cart WHERE id=$cid AND user_id=$uid");
        else mysqli_query($koneksi, "UPDATE cart SET quantity=$nq WHERE id=$cid AND user_id=$uid");
    }
}
header('Location: keranjang.php'); exit();
?>