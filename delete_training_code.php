<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// block if role not allowed to delete training codes
if (!in_array($_SESSION['role'] ?? 'viewer', ['superadmin','editor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Anda tidak mempunyai izin untuk menghapus training code.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['training_code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = $_POST['training_code'];

// prepared statement
$stmt = $conn->prepare("DELETE FROM training_codes WHERE training_code = ?");
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $code);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Training code berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data.']);
}
$stmt->close();
$conn->close();
exit;
?>