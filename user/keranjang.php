<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php'); exit();
}
$uid = (int)$_SESSION['id'];

// ✅ HANYA 1x QUERY, SIMPAN KE ARRAY AGAR TIDAK DOUBLE RENDER
$cart_res = mysqli_query($koneksi, "SELECT c.id as cart_id, c.product_id, c.quantity, p.nama_produk, p.harga, p.gambar FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=$uid ORDER BY c.id DESC");
$cart_items = mysqli_fetch_all($cart_res, MYSQLI_ASSOC);

$total = 0; $count = 0;
foreach($cart_items as &$item) {
    $item['subtotal'] = $item['harga'] * $item['quantity'];
    $total += $item['subtotal'];
    $count += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--p:#2563eb;--pd:#1d4ed8;--s:#10b981;--d:#dc2626;--g1:#f3f4f6;--g2:#e5e7eb;--g6:#4b5563;--g8:#1f2937;--sh:0 1px 3px rgba(0,0,0,.1);--r:12px}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
        body{background:var(--g1);color:var(--g8);line-height:1.6}
        .header{background:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--sh);position:sticky;top:0;z-index:100}
        .logo{display:flex;align-items:center;gap:.75rem;text-decoration:none;color:var(--g8)}
        .logo i{font-size:28px;color:var(--p)}
        .logo span{font-weight:700;font-size:20px}
        .btn{padding:.75rem 1.5rem;border:none;border-radius:var(--r);font-weight:600;cursor:pointer;transition:.3s;text-decoration:none;font-size:14px;display:inline-flex;align-items:center;gap:.5rem}
        .btn-p{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff}
        .btn-p:hover{transform:translateY(-2px)}
        .btn-s{background:var(--g1);color:var(--g6);border:2px solid var(--g2)}
        .btn-s:hover{border-color:var(--p);color:var(--p)}
        .btn-d{background:#fef2f2;color:var(--d);border:2px solid #fecaca}
        .container{max-width:1100px;margin:2rem auto;padding:0 1.5rem}
        .cart-list{display:flex;flex-direction:column;gap:1rem}
        .cart-item{background:#fff;padding:1.25rem;border-radius:var(--r);box-shadow:var(--sh);display:grid;grid-template-columns:100px 1fr auto;gap:1.25rem;align-items:center}
        .cart-img{width:100px;height:100px;background:var(--g1);border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center}
        .cart-img img{width:100%;height:100%;object-fit:cover}
        .cart-info h3{font-size:16px;font-weight:600;margin-bottom:4px}
        .cart-info p{color:var(--g6);font-size:14px}
        .qty-box{display:flex;align-items:center;gap:.5rem;background:var(--g1);padding:.4rem;border-radius:8px;margin-top:8px;width:fit-content}
        .qty-btn{width:28px;height:28px;border:none;background:#fff;border-radius:6px;cursor:pointer;color:var(--p)}
        .qty-val{min-width:30px;text-align:center;font-weight:600}
        .cart-actions{display:flex;flex-direction:column;gap:.75rem;align-items:flex-end}
        .subtotal{font-weight:700;color:var(--g8);font-size:16px}
        .summary{background:#fff;padding:1.5rem;border-radius:var(--r);margin-top:2rem;box-shadow:var(--sh);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
        .total-label{color:var(--g6)}
        .total-price{font-size:24px;font-weight:700;color:var(--p)}
        .empty{text-align:center;padding:4rem 2rem;background:#fff;border-radius:var(--r)}
        @media(max-width:768px){.cart-item{grid-template-columns:1fr;text-align:center}.cart-actions{align-items:center}.summary{flex-direction:column;text-align:center}}
    </style>
</head>
<body>
<header class="header">
    <a href="dashboard.php" class="logo"><i class="fas fa-bolt"></i><span>MasElektro</span></a>
    <a href="dashboard.php" class="btn btn-s"><i class="fas fa-arrow-left"></i> Lanjut Belanja</a>
</header>

<div class="container">
    <h1 style="margin-bottom:1.5rem">🛒 Keranjang Belanja <span style="color:var(--p)">(<?php echo $count; ?> item)</span></h1>

    <?php if(empty($cart_items)): ?>
        <div class="empty">
            <i class="fas fa-shopping-cart" style="font-size:64px;color:var(--g2);margin-bottom:1rem"></i>
            <h2>Keranjang Kosong</h2>
            <p style="color:var(--g6);margin:1rem 0 2rem">Yuk isi keranjang dengan produk favoritmu!</p>
            <a href="dashboard.php" class="btn btn-p">Belanja Sekarang</a>
        </div>
    <?php else: ?>
        <div class="cart-list">
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item">
                <div class="cart-img">
                    <?php if(!empty($item['gambar']) && file_exists('../uploads/'.$item['gambar'])): ?>
                        <img src="../uploads/<?php echo $item['gambar']; ?>" alt="<?php echo $item['nama_produk']; ?>">
                    <?php else: ?>
                        <i class="fas fa-image" style="color:#9ca3af;font-size:24px"></i>
                    <?php endif; ?>
                </div>
                <div class="cart-info">
                    <h3><?php echo htmlspecialchars($item['nama_produk']); ?></h3>
                    <p>Rp <?php echo number_format($item['harga'],0,',','.'); ?></p>
                    <div class="qty-box">
                        <button class="qty-btn" onclick="updQty(<?php echo $item['cart_id']; ?>,-1)"><i class="fas fa-minus"></i></button>
                        <span class="qty-val"><?php echo $item['quantity']; ?></span>
                        <button class="qty-btn" onclick="updQty(<?php echo $item['cart_id']; ?>,1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                <div class="cart-actions">
                    <div class="subtotal">Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></div>
                    <button class="btn btn-d" onclick="rmItem(<?php echo $item['cart_id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <div>
                <div class="total-label">Total Pembayaran</div>
                <div class="total-price">Rp <?php echo number_format($total,0,',','.'); ?></div>
            </div>
            <!-- ✅ LANGSUNG KIRIM KE CHECKOUT.PHP -->
            <form method="POST" action="checkout.php">
                <input type="hidden" name="total_bayar" value="<?php echo $total; ?>">
                <button type="submit" class="btn btn-p" style="padding:1rem 2.5rem;font-size:16px">Proses Checkout <i class="fas fa-arrow-right"></i></button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function updQty(cid, ch){
    let f=document.createElement('form');f.method='POST';f.action='update_cart.php';
    f.innerHTML=`<input type="hidden" name="cart_id" value="${cid}"><input type="hidden" name="change" value="${ch}">`;
    document.body.appendChild(f);f.submit();
}
function rmItem(cid){
    if(!confirm('Hapus item ini?'))return;
    let f=document.createElement('form');f.method='POST';f.action='update_cart.php';
    f.innerHTML=`<input type="hidden" name="cart_id" value="${cid}"><input type="hidden" name="remove" value="1">`;
    document.body.appendChild(f);f.submit();
}
</script>
</body>
</html>