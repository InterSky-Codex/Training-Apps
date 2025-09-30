<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// block if role not allowed to delete staff
if (!in_array($_SESSION['role'] ?? 'viewer', ['superadmin','editor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Anda tidak mempunyai izin untuk menghapus staff.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$staff_id = trim($_POST['staff_id'] ?? '');
if ($staff_id === '') {
    echo json_encode(['success' => false, 'message' => 'Parameter staff_id dibutuhkan']);
    exit;
}

$del = $conn->prepare("DELETE FROM staff WHERE ID = ?");
if ($del === false) {
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit;
}
$del->bind_param("s", $staff_id);
$ok = $del->execute();
$err = $del->error;
$del->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Staff berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $err]);
}
exit;
?>