<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['id'];
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $user_id"));
if (!$user) { header('Location: ../auth/login.php'); exit(); }

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    
    if (empty($nama)) {
        $message = 'Nama lengkap wajib diisi';
        $message_type = 'error';
    } else {
        $nama_esc = mysqli_real_escape_string($koneksi, $nama);
        $email_esc = mysqli_real_escape_string($koneksi, $email);
        $no_hp_esc = mysqli_real_escape_string($koneksi, $no_hp);
        $alamat_esc = mysqli_real_escape_string($koneksi, $alamat);
        
        mysqli_query($koneksi, "
            UPDATE users 
            SET nama_lengkap = '$nama_esc', 
                email = '$email_esc', 
                no_hp = '$no_hp_esc', 
                alamat = '$alamat_esc'
            WHERE id = $user_id
        ");
        
        $message = '✅ Profil berhasil diperbarui!';
        $message_type = 'success';
        $user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $user_id"));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#2563eb;--primary-dark:#1d4ed8;--success:#10b981;--danger:#dc2626;--gray-100:#f3f4f6;--gray-200:#e5e7eb;--gray-600:#4b5563;--gray-800:#1f2937;--white:#fff;--shadow:0 1px 3px rgba(0,0,0,0.1);--radius:12px}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
        body{background:var(--gray-100);color:var(--gray-800);line-height:1.6}
        
        .header{background:var(--white);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow);position:sticky;top:0;z-index:100}
        .header__logo{display:flex;align-items:center;gap:0.75rem;text-decoration:none;color:var(--gray-800)}
        .logo__icon{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px}
        .logo__text{font-size:20px;font-weight:700;color:var(--gray-800)}.logo__text span{color:var(--primary)}
        .header__back{color:var(--primary);text-decoration:none;font-weight:600;font-size:14px;display:flex;align-items:center;gap:6px;transition:.2s}
        .header__back:hover{color:var(--primary-dark);gap:8px}
        
        .container{max-width:700px;margin:2rem auto;padding:0 1.5rem}
        .page-title{font-size:22px;font-weight:700;margin-bottom:1.5rem}
        
        .card{background:var(--white);border-radius:var(--radius);padding:2rem;box-shadow:var(--shadow);margin-bottom:1.5rem}
        .card-title{font-size:18px;font-weight:700;margin-bottom:1.5rem;color:var(--gray-800);display:flex;align-items:center;gap:8px}
        
        .form-group{margin-bottom:1.5rem}
        .form-group label{display:block;font-size:14px;font-weight:600;color:var(--gray-800);margin-bottom:0.5rem}
        .form-group input,.form-group textarea{width:100%;padding:12px;border:2px solid var(--gray-200);border-radius:8px;font-size:14px;outline:none;transition:all 0.3s}
        .form-group input:focus,.form-group textarea:focus{border-color:var(--primary)}
        .form-group textarea{min-height:100px;resize:vertical}
        
        .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.3s;text-decoration:none}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-dark);transform:translateY(-2px)}
        .btn-secondary{background:var(--gray-200);color:var(--gray-800)}
        .btn-secondary:hover{background:var(--gray-300)}
        .btn-group{display:flex;gap:10px;margin-top:1rem;flex-wrap:wrap}
        
        .alert{padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;font-weight:500;display:flex;align-items:center;gap:8px}
        .alert-success{background:#d1fae5;color:#065f46}
        .alert-error{background:#fee2e2;color:#991b1b}
        
        .user-badge{display:inline-block;padding:4px 12px;background:var(--primary);color:#fff;border-radius:20px;font-size:12px;font-weight:600;margin-top:0.5rem}
        
        @media(max-width:600px){.btn-group{flex-direction:column}.header{padding:1rem}}
    </style>
</head>
<body>
    <!-- ✅ HEADER DENGAN TOMBOL KEMBALI KE DASHBOARD -->
    <header class="header">
        <a href="dashboard.php" class="header__logo">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </a>
        <a href="dashboard.php" class="header__back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </header>

    <div class="container">
        <h1 class="page-title"><i class="fas fa-user"></i> Profil Saya</h1>
        
        <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-user-edit"></i> Informasi Profil</div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled style="background:var(--gray-100);color:var(--gray-600)">
                    <small style="color:var(--gray-600)">Username tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="email@contoh.com">
                </div>
                
                <div class="form-group">
                    <label>No. Telepon *</label>
                    <input type="tel" name="no_hp" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" placeholder="08xxxxxxxxxx" required>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="alamat" required><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                </div>
                
                <!-- ✅ TOMBOL AKSI DENGAN LABEL LENGKAP -->
                <div class="btn-group">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-info-circle"></i> Info Akun</div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-200)">
                <span style="color:var(--gray-600)">Role</span>
                <span class="user-badge"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-200)">
                <span style="color:var(--gray-600)">Terdaftar</span>
                <span><?php echo isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : date('d M Y'); ?></span>
            </div>
        </div>
        
        <a href="../auth/logout.php" class="btn btn-secondary" style="width:100%;justify-content:center">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</body>
</html>