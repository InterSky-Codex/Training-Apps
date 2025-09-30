<?php
session_start();
include 'config.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    $conn->begin_transaction();

    try {
        $stmt_title = $conn->prepare("DELETE FROM admin_titles WHERE user_id = ?");
        $stmt_title->bind_param("i", $user_id);
        $stmt_title->execute();

        $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();

        $conn->commit();

        $_SESSION['message'] = "User berhasil dihapus!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Gagal menghapus user: " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = "ID tidak valid!";
}

header("Location: admin_dashboard.php");
exit();