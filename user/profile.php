<?php
/**
 * File: user/profile.php
 * Deskripsi: Halaman pengelolaan profil user
 * Theme: Match dengan checkout.php MasElektro
 */

error_reporting(E_ALL);
ini_set('display_errors', 1); // ⚠️ Matikan di production

session_start();
require_once '../config.php';

// 🔐 Cek autentikasi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)($_SESSION['id'] ?? 0);
if ($user_id === 0) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data user saat ini
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
}

$successMessage = null;
$errorMessage = null;

// 🔁 Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    
    if (empty($nama_lengkap)) {
        $errorMessage = 'Nama lengkap wajib diisi';
    } else {
        $stmt_update = mysqli_prepare($koneksi, "
            UPDATE users 
            SET nama_lengkap = ?, email = ?, no_hp = ?, alamat = ?, updated_at = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt_update, "ssssi", $nama_lengkap, $email, $no_hp, $alamat, $user_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            // Refresh data user
            $user['nama_lengkap'] = $nama_lengkap;
            $user['email'] = $email;
            $user['no_hp'] = $no_hp;
            $user['alamat'] = $alamat;
            $successMessage = '✅ Profil berhasil diperbarui!';
            $_SESSION['success_message'] = $successMessage;
            header("Location: profile.php");
            exit();
        } else {
            $errorMessage = 'Gagal memperbarui profil: ' . mysqli_stmt_error($stmt_update);
        }
        mysqli_stmt_close($stmt_update);
    }
}

// 🔐 Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $errorMessage = 'Semua field password wajib diisi';
    } elseif ($password_baru !== $konfirmasi_password) {
        $errorMessage = 'Konfirmasi password tidak cocok';
    } elseif (strlen($password_baru) < 6) {
        $errorMessage = 'Password baru minimal 6 karakter';
    } else {
        // Verifikasi password lama
        $password_lama_hash = md5($password_lama); // ⚠️ Gunakan password_hash() di production!
        
        if ($password_lama_hash === $user['password']) {
            $password_baru_hash = md5($password_baru); // ⚠️ Gunakan password_hash() di production!
            
            $stmt_pwd = mysqli_prepare($koneksi, "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt_pwd, "si", $password_baru_hash, $user_id);
            
            if (mysqli_stmt_execute($stmt_pwd)) {
                $successMessage = '✅ Password berhasil diubah!';
                $_SESSION['success_message'] = $successMessage;
                header("Location: profile.php");
                exit();
            } else {
                $errorMessage = 'Gagal mengubah password';
            }
            mysqli_stmt_close($stmt_pwd);
        } else {
            $errorMessage = 'Password lama tidak sesuai';
        }
    }
}

// 🖼️ Proses upload foto profil (opsional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo']) && !empty($_FILES['foto_profil']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['foto_profil']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../uploads/profiles/' . $new_filename;
        
        // Buat folder jika belum ada
        if (!is_dir('../uploads/profiles')) {
            mkdir('../uploads/profiles', 0755, true);
        }
        
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
            $stmt_photo = mysqli_prepare($koneksi, "UPDATE users SET foto_profil = ? WHERE id = ?");
            $foto_url = 'profiles/' . $new_filename;
            mysqli_stmt_bind_param($stmt_photo, "si", $foto_url, $user_id);
            mysqli_stmt_execute($stmt_photo);
            mysqli_stmt_close($stmt_photo);
            
            $user['foto_profil'] = $foto_url;
            $successMessage = '✅ Foto profil berhasil diperbarui!';
            $_SESSION['success_message'] = $successMessage;
            header("Location: profile.php");
            exit();
        } else {
            $errorMessage = 'Gagal mengupload foto';
        }
    } else {
        $errorMessage = 'Format foto tidak didukung (jpg, jpeg, png, gif)';
    }
}

// Ambil flash message dari session
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981;
            --danger: #dc2626; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-600: #4b5563; --gray-800: #1f2937; --white: #ffffff;
            --shadow: 0 1px 3px rgba(0,0,0,0.1); --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-100); color: var(--gray-800); line-height: 1.6; }
        
        /* Header - Sama dengan checkout.php */
        .header { background: var(--white); padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 100; }
        .header__logo { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; text-decoration: none; }
        .logo__icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; font-size: 20px; }
        .logo__text { font-size: 20px; font-weight: 700; color: var(--gray-800); }
        .logo__text span { color: var(--primary); }
        .header__title { font-size: 28px; font-weight: 700; color: var(--gray-800); margin-left: auto; }
        
        /* Container */
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        
        /* Profile Card */
        .profile-card { background: var(--white); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); margin-bottom: 2rem; }
        .profile-header { display: flex; align-items: center; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid var(--gray-200); margin-bottom: 2rem; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 36px; font-weight: 700; overflow: hidden; flex-shrink: 0; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info h2 { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-bottom: 0.25rem; }
        .profile-info p { color: var(--gray-600); font-size: 14px; }
        .profile-info .badge { display: inline-block; padding: 0.25rem 0.75rem; background: var(--primary); color: var(--white); border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 0.5rem; }
        
        /* Form Sections */
        .form-section { margin-bottom: 2.5rem; }
        .form-section__title { font-size: 20px; font-weight: 700; color: var(--gray-800); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); display: flex; align-items: center; gap: 0.5rem; }
        .form-section__title i { color: var(--primary); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--gray-800); margin-bottom: 0.5rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group input:disabled { background: var(--gray-100); cursor: not-allowed; }
        .form-hint { font-size: 12px; color: var(--gray-600); margin-top: 0.25rem; }
        
        /* Photo Upload */
        .photo-upload { display: flex; align-items: center; gap: 1rem; }
        .photo-upload__preview { width: 80px; height: 80px; border-radius: 12px; background: var(--gray-100); overflow: hidden; flex-shrink: 0; }
        .photo-upload__preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-upload__actions { flex: 1; }
        .photo-upload__input { display: none; }
        .photo-upload__label { display: inline-block; padding: 0.5rem 1rem; background: var(--gray-100); border: 2px solid var(--gray-200); border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .photo-upload__label:hover { border-color: var(--primary); background: rgba(37, 99, 235, 0.05); }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn--primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); }
        .btn--primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .btn--secondary { background: var(--gray-100); color: var(--gray-800); border: 2px solid var(--gray-200); }
        .btn--secondary:hover { border-color: var(--primary); background: rgba(37, 99, 235, 0.05); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-group { display: flex; gap: 1rem; margin-top: 1rem; }
        
        /* Alerts */
        .alert { padding: 1rem 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
        .alert--success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert--error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
            .btn-group { flex-direction: column; }
            .header { padding: 1rem; }
            .container { padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Header - Sama dengan checkout.php -->
    <header class="header">
        <a href="dashboard.php" class="header__logo">
            <div class="logo__icon">M</div>
            <div class="logo__text">Mas<span>Elektro</span></div>
        </a>
        <h1 class="header__title">Profil Saya</h1>
    </header>

    <main class="container">
        <!-- Flash Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert--success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert--error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($user['foto_profil']); ?>" 
                             alt="<?php echo htmlspecialchars($user['nama_lengkap']); ?>"
                             onerror="this.parentElement.innerHTML='<?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>'">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>
                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                    <span class="badge">
                        <i class="fas fa-user"></i> <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                    </span>
                </div>
            </div>

            <!-- Form Update Profil -->
            <form method="POST" action="" enctype="multipart/form-data">
                <section class="form-section">
                    <h3 class="form-section__title"><i class="fas fa-user-edit"></i> Informasi Profil</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <div class="form-hint">Username tidak dapat diubah</div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="email@contoh.com">
                        </div>
                        <div class="form-group">
                            <label for="no_hp">No. Telepon</label>
                            <input type="tel" id="no_hp" name="no_hp" 
                                   value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group full-width">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="update_profile" class="btn btn--primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="dashboard.php" class="btn btn--secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </section>

                <!-- Upload Foto Profil -->
                <section class="form-section">
                    <h3 class="form-section__title"><i class="fas fa-image"></i> Foto Profil</h3>
                    <div class="photo-upload">
                        <div class="photo-upload__preview">
                            <?php if (!empty($user['foto_profil'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($user['foto_profil']); ?>" 
                                     alt="Foto Profil"
                                     onerror="this.src='https://via.placeholder.com/80/2563eb/ffffff?text=<?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>'">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--primary);color:white;font-weight:700;font-size:24px;">
                                    <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="photo-upload__actions">
                            <label class="photo-upload__label" for="foto_profil">
                                <i class="fas fa-upload"></i> Pilih Foto
                            </label>
                            <input type="file" id="foto_profil" name="foto_profil" class="photo-upload__input" accept="image/*">
                            <div class="form-hint">Maksimal 2MB • Format: JPG, PNG, GIF</div>
                        </div>
                    </div>
                    <button type="submit" name="upload_photo" class="btn btn--secondary" style="margin-top:1rem;">
                        <i class="fas fa-cloud-upload-alt"></i> Upload Foto
                    </button>
                </section>

                <!-- Ganti Password -->
                <section class="form-section">
                    <h3 class="form-section__title"><i class="fas fa-lock"></i> Ganti Password</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password_lama">Password Lama *</label>
                            <input type="password" id="password_lama" name="password_lama" required>
                        </div>
                        <div class="form-group">
                            <label for="password_baru">Password Baru *</label>
                            <input type="password" id="password_baru" name="password_baru" required minlength="6">
                            <div class="form-hint">Minimal 6 karakter</div>
                        </div>
                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password Baru *</label>
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn--primary">
                        <i class="fas fa-key"></i> Ubah Password
                    </button>
                </section>
            </form>
        </div>
    </main>

    <script>
        // Preview foto sebelum upload
        document.getElementById('foto_profil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran foto maksimal 2MB');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.photo-upload__preview');
                    preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Validasi password match sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const pwdBaru = document.getElementById('password_baru').value;
            const pwdKonf = document.getElementById('konfirmasi_password').value;
            
            if (pwdBaru && pwdKonf && pwdBaru !== pwdKonf) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                document.getElementById('konfirmasi_password').focus();
            }
        });
    </script>
</body>
</html>