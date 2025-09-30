<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cfcy1736_training';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$error = ""; // Inisialisasi variabel error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $training_code = $_POST['training_code'];

    $sql = "SELECT * FROM training_codes WHERE training_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $training_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $training_data = $result->fetch_assoc();
        $current_date = date('Y-m-d H:i:s');

        // Cek apakah training sudah kedaluwarsa
        if ($current_date > $training_data['expired_at']) {
            $_SESSION['message_error'] = 'Maaf training ini sudah tidak berlaku, kamu bisa mengkonfirmasi nya ke HRD';
            header("Location: login_user");
            exit();
        } else {
            $_SESSION['training_code'] = $training_code;
            header("Location: user");
            exit();
        }
    } else {
        $error = "Invalid Training Code";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .login-wrapper {
            display: flex;
            flex-direction: column;
            max-width: 400px;
            width: 100%;
            background: white;
            /* Lebih kontras: lapis shadow lebih kuat + subtle inner highlight */
            box-shadow:
                0 6px 18px rgba(0,0,0,0.18),   /* bayangan utama (lebih gelap) */
                0 18px 40px rgba(0,0,0,0.12),  /* kedalaman kedua */
                0 1px 0 rgba(255,255,255,0.6) inset; /* subtle inner highlight */
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 160ms ease, box-shadow 160ms ease;
            border-radius: 10px;
            overflow: hidden;
            padding: 30px;
            text-align: center;
        }
        /* hover/active state untuk penekanan visual */
        .login-wrapper:hover {
            transform: translateY(-6px);
            box-shadow:
                0 12px 30px rgba(0,0,0,0.22),
                0 30px 60px rgba(0,0,0,0.16),
                0 1px 0 rgba(255,255,255,0.6) inset;
        }
        .login-header {
            font-size: 24px;
            color: #FF6F00;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-input:focus {
            outline: none;
            border-color: #FF6F00;
            box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #FF6F00;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-btn:hover {
            background: #E65C00;
        }
        .error-message {
            color: #FF3B3B;
            font-size: 14px;
            margin-bottom: 15px;
        }
        @media (min-width: 768px) {
            .login-wrapper {
                max-width: 500px;
            }
        }
        .header {
            margin-bottom: 30px;
            text-align: center;
            color: #2c3e50;
        }
        .header img {
            max-width: 270px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="header">
                <img src="logo.png" alt="Logo Harris Hotel">
            </div>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input 
                        type="text" 
                        name="training_code" 
                        class="form-input" 
                        placeholder="Enter Training Code" 
                        required
                    >
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

    <script>
        <?php if (isset($_SESSION['message_error'])): ?>
        Swal.fire({
            title: "Error",
            text: "<?php echo $_SESSION['message_error']; ?>",
            icon: "error",
            timer: 3000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "login_user"; 
        });
        <?php unset($_SESSION['message_error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>