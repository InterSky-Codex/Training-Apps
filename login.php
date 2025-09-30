<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // validasi captcha
    if (!isset($_POST['captcha']) || !isset($_SESSION['captcha_code']) || strcasecmp(trim($_POST['captcha']), $_SESSION['captcha_code']) !== 0) {
        $error = "Captcha tidak cocok. Silakan coba lagi.";
        // hilangkan kode lama agar tidak bisa dipakai ulang
        unset($_SESSION['captcha_code']);
    } else {
        // hapus captcha agar tidak dapat dipakai ulang
        unset($_SESSION['captcha_code']);
        // lanjutkan validasi user/password
        $query = "SELECT * FROM login WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);

        if ($user && $password == $user['password']) {
            $_SESSION['username'] = $username;
            // simpan role user ke session (dibaca dari DB kolom `role`)
            $_SESSION['role'] = $user['role'] ?? 'viewer';
            header("Location: admin_dashboard");
            exit();
        } else {
            $error = "Username atau password salah";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="logo.png">
    <title>Harris Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .login-title {
            color: #FF6B00;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .form-control {
            border-color: #e0e0e0;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #FF6B00;
            box-shadow: none;
        }

        .btn-login {
            background-color: #FF6B00;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #FF8C00;
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
        .header {
            margin-bottom:30px;
            text-align: center;
            color: #2c3e50;
        }
        .header img{
            max-width: 350px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="login-container">
          <div class="header">
            <img src="logo.png" alt="Logo Harris Hotel">
        </div>

        <?php 
        if (isset($error)) {
            echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>";
        }
        ?>

        <form method="POST">
            <div class="mb-3">
                <input 
                    type="text" 
                    name="username" 
                    class="form-control" 
                    placeholder="Username" 
                    required
                >
            </div>
            <div class="mb-4">
                <input 
                    type="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Password" 
                    required
                >
            </div>

            <div class="mb-3 text-center">
                <img src="captcha.php" alt="captcha" style="height:48px;border:1px solid #ddd;border-radius:4px;">
                <div class="form-text">Klik gambar untuk mereset captcha.</div>
            </div>
            <div class="mb-3">
                <input type="text" name="captcha" class="form-control" placeholder="Masukkan kode captcha" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Login
            </button>
        </form>
    <script>
        // klik gambar untuk refresh captcha
        document.addEventListener('click', function(e){
            if (e.target && e.target.tagName === 'IMG' && e.target.src && e.target.src.indexOf('captcha.php') !== -1) {
                e.target.src = 'captcha.php?ts=' + Date.now();
            }
        });
    </script>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>