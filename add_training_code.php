<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// block if role not allowed to add training code
if (!in_array($_SESSION['role'] ?? 'viewer', ['superadmin','editor'])) {
    $_SESSION['error'] = 'Unauthorized: Anda tidak mempunyai izin untuk menambah training code.';
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_code = trim($_POST['training_code']);
    $title = trim($_POST['title']); 
    $trainer_name = trim($_POST['trainer_name']);
    $userid_trainer = trim($_POST['userid_trainer']); 
    $duration = (int)$_POST['duration'];
    $expired_days = (int)$_POST['expired_days']; 

    if (empty($training_code) || empty($title) || empty($trainer_name) || empty($userid_trainer) || empty($duration) || empty($expired_days)) {
        $_SESSION['error'] = "Training Code, Title, Trainer Name, Duration, dan Expiration Days tidak boleh kosong.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // Insert trainer into users table
        $insert_user = $conn->prepare("INSERT INTO users (name, created_at) VALUES (?, NOW())");
        $insert_user->bind_param("s", $trainer_name);
        if (!$insert_user->execute()) {
            throw new Exception("Gagal menambahkan trainer: " . $insert_user->error);
        }
        
        $user_id = $conn->insert_id;
        // Hitung tanggal kedaluwarsa dengan validasi
        if ($expired_days > 0) {
            $expired_at = date('Y-m-d H:i:s', strtotime("+$expired_days days"));
        } else {
            $_SESSION['error'] = "Expired days harus lebih besar dari 0.";
            header("Location: admin_dashboard.php");
            exit();
        }

        // Simpan data ke database
        $stmt_code = $conn->prepare("INSERT INTO training_codes (user_id, training_code, trainer_name, userid_trainer, duration, expired_at, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt_code === false) {
            throw new Exception("Prepare error: " . $conn->error);
        }
        $stmt_code->bind_param("isssis", $user_id, $training_code, $trainer_name, $userid_trainer, $duration, $expired_at);
        if (!$stmt_code->execute()) {
            throw new Exception("Gagal menambahkan training code: " . $stmt_code->error);
        }
        
        $combined_title = $title . " - " . $training_code;
        $stmt_title = $conn->prepare("INSERT INTO admin_titles (user_id, title, created_at) VALUES (?, ?, NOW())");
        if ($stmt_title === false) {
            throw new Exception("Prepare error: " . $conn->error);
        }
        $stmt_title->bind_param("is", $user_id, $combined_title);

        if (!$stmt_title->execute()) {
            throw new Exception("Gagal menambahkan title: " . $stmt_title->error);
        }
        
        $conn->commit();
        $_SESSION['success'] = "Training Code, Title, dan Trainer Name berhasil ditambahkan.";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    } finally {
        if (isset($stmt_code) && $stmt_code !== false) {
            $stmt_code->close();
        }
        if (isset($stmt_title) && $stmt_title !== false) {
            $stmt_title->close();
        }
        if (isset($insert_user) && $insert_user !== false) {
            $insert_user->close();
        }
    }
    
    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>