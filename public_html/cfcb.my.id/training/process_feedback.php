<?php
session_start();
include 'config.php';

if (!isset($_SESSION['training_code'])) {
    header("Location: login_user");
    exit();
}

$training_code = $_SESSION['training_code'];
$query = "SELECT at.title, tc.duration, tc.trainer_name 
          FROM training_codes tc
          LEFT JOIN admin_titles at ON tc.user_id = at.user_id
          WHERE tc.training_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $training_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    session_destroy();
    header("Location: login");
    exit();
}

$user_data = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $user_id = trim($_POST['user_id']);
    $department = trim($_POST['department']);
    $feedback = $_POST['feedback'];
    $signature = $_POST['signature'] ?? null; // Ambil signature dari input form
    $title = $user_data['title']; 
    $duration = $user_data['duration'];
    $trainer_name = $user_data['trainer_name'];

    // Validasi
    $errors = [];
    if (empty($name)) {
        $errors[] = "Nama harus diisi.";
    }
    if (empty($user_id)) {
        $errors[] = "User  ID harus diisi.";
    }
    if (empty($department)) {
        $errors[] = "Department harus diisi.";
    }
    if (empty($feedback)) {
        $errors[] = "Feedback harus dipilih.";
    }
    if (empty($signature)) { // Validasi untuk tanda tangan
        $errors[] = "Tanda tangan harus diisi.";
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert data ke tabel users
            $insert_query = "INSERT INTO users (idt, name, department, feedback, signature, created_at) 
                             VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssss", $training_code, $name, $department, $feedback, $signature);

            if ($insert_stmt->execute()) {
                // Ambil ID yang baru saja di-insert
                $new_user_id = $conn->insert_id; 

                // Insert ke tabel training_codes
                $training_code_query = "INSERT INTO training_codes (userid, user_id, training_code, duration, trainer_name, created_at) 
                                        VALUES (?, ?, ?, ?, ?, NOW())";
                $training_code_stmt = $conn->prepare($training_code_query);
                               $training_code_stmt->bind_param("issis", $user_id, $new_user_id, $training_code, $duration, $trainer_name);

                if (!$training_code_stmt->execute()) {
                    throw new Exception("Gagal menyimpan training code: " . $training_code_stmt->error);
                }

                // Insert ke tabel admin_titles
                $title_query = "INSERT INTO admin_titles (user_id, title, created_at) 
                                VALUES (?, ?, NOW())";
                $title_stmt = $conn->prepare($title_query);
                $title_stmt->bind_param("is", $new_user_id, $title);

                if (!$title_stmt->execute()) {
                    throw new Exception("Gagal menyimpan title: " . $title_stmt->error);
                }

                $conn->commit();
                $_SESSION['message'] = "Data berhasil disimpan.";
            } else {
                throw new Exception("Gagal menyimpan data user: " . $insert_stmt->error);
            }

            header("Location: user");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            header("Location: user");
            exit();
        }
    } else {
        $_SESSION['errors'] = $errors; // Simpan kesalahan ke session
        header("Location: user"); // Kembali ke halaman form
        exit();
    }
}
?>