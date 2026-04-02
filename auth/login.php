<?php
/**
 * File: auth/login.php
 * Deskripsi: Halaman login multi-role (admin, petugas, user)
 */

session_start();
require_once '../config.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    $role = $_SESSION['role'] ?? '';
    
    switch ($role) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'petugas':
            header('Location: ../petugas/dashboard.php');
            break;
        case 'user':
            header('Location: ../user/dashboard.php');
            break;
        default:
            session_destroy();
            header('Location: login.php');
    }
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = true;
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Cek password (MD5 sesuai database Anda)
            $password_hash = md5($password);
            
            if ($password_hash === $row['password']) {
                // Set session dengan benar
                $_SESSION['login'] = true;
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nama'] = $row['nama_lengkap'] ?? $row['username'];
                $_SESSION['role'] = $row['role']; // Pastikan ini tersimpan
                
                // Debug: Cek role yang tersimpan
                // echo "Role: " . $_SESSION['role']; exit();
                
                // Redirect berdasarkan role
                switch ($row['role']) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'petugas':
                        header('Location: ../petugas/dashboard.php');
                        break;
                    case 'user':
                        header('Location: ../user/dashboard.php');
                        break;
                    default:
                        session_destroy();
                        $error = true;
                }
                exit();
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - MasElektro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --gray-100: #f3f4f6;
            --gray-200: #e5e7eb; --gray-600: #4b5563; --gray-800: #1f2937;
            --white: #ffffff; --shadow: 0 4px 6px rgba(0,0,0,0.1); --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: var(--white); width: 100%; max-width: 420px; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); text-align: center; }
        .logo-area { margin-bottom: 25px; }
        .logo-area img { width: 70px; height: 70px; object-fit: contain; margin-bottom: 15px; }
        .title { font-size: 26px; font-weight: 700; color: var(--gray-800); margin-bottom: 8px; }
        .title span { color: var(--primary); }
        .subtitle { font-size: 14px; color: var(--gray-600); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--gray-800); }
        .form-input { width: 100%; padding: 14px 16px; font-size: 14px; border: 2px solid var(--gray-200); border-radius: 10px; outline: none; transition: all 0.3s; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); font-size: 16px; font-weight: 600; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4); }
        .alert { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5; }
        .info-box { margin-top: 30px; padding: 20px; background: var(--gray-100); border-radius: 10px; border: 1px solid var(--gray-200); }
        .info-box h4 { font-size: 14px; color: var(--gray-800); margin-bottom: 12px; font-weight: 600; }
        .info-box p { font-size: 12px; color: var(--gray-600); margin-bottom: 6px; text-align: left; }
        .info-box code { background: var(--gray-200); padding: 2px 6px; border-radius: 4px; font-family: 'Consolas', monospace; color: var(--gray-800); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-area">
            <img src="https://cdn-icons-png.flaticon.com/512/2920/2920323.png" alt="MasElektro Logo">
            <div class="title">Mas<span>Elektro</span></div>
            <div class="subtitle">Silakan login untuk melanjutkan</div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                Username atau Password salah!
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-input" placeholder="Masukkan username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-input" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn-login">Masuk</button>
        </form>
        
        <div class="info-box">
            <h4>📋 Informasi Login</h4>
            <p>👤 Admin: <code>admin</code> / <code>admin123</code></p>
            <p>👨‍💼 Petugas: <code>petugas</code> / <code>password</code></p>
            <p>🛒 User: <code>customer</code> / <code>customer123</code></p>
        </div>
    </div>
</body>
</html>