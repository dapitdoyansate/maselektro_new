<?php
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Konfirmasi Password tidak sama!";
    } else {
        $check = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username atau Email sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 'user')";
            if (mysqli_query($koneksi, $query)) {
                header("Location: login.php?success=1");
                exit();
            } else {
                $error = "Gagal registrasi: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - MasElektro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 450px; /* Sedikit lebih lebar untuk 4 input */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .logo-area {
            margin-bottom: 20px;
        }

        .logo-area img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .title span {
            color: #2563eb;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
            color: #374151;
        }

        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            background-color: #1d4ed8;
        }

        .alert {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }

        .success {
            background-color: #d1fae5;
            color: #047857;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #a7f3d0;
        }

        .footer-link {
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
        }

        .footer-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <!-- Logo Section -->
        <div class="logo-area">
            <img src="https://cdn-icons-png.flaticon.com/512/2920/2920323.png" alt="MasElektro Logo">
            <div class="title">Daftar <span>Akun</span></div>
            <div class="subtitle">Lengkapi data untuk membuat akun</div>
        </div>

        <!-- Error/Success Message -->
        <?php if(isset($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <div class="success">Registrasi Berhasil! Silakan Login.</div>
        <?php endif; ?>

        <!-- Register Form -->
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-input" placeholder="Username" required autocomplete="off">
            </div>

            <div class="form-group">
                <input type="email" name="email" class="form-input" placeholder="Example@Gmail.com" required autocomplete="off">
            </div>

            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder="Password" required>
            </div>

            <div class="form-group">
                <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn-register">Daftar</button>
        </form>

        <!-- Footer Link -->
        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Login</a>
        </div>
    </div>

</body>
</html>