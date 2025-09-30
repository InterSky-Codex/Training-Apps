<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// block if role not allowed to edit staff
if (!in_array($_SESSION['role'] ?? 'viewer', ['superadmin','editor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Anda tidak mempunyai izin untuk mengedit staff.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$staff_id = trim($_POST['staff_id'] ?? '');
$staff_name = trim($_POST['staff_name'] ?? '');
$staff_department = trim($_POST['staff_department'] ?? '');

if ($staff_id === '' || $staff_name === '' || $staff_department === '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Semua field harus diisi.']);
    exit;
}

if (!preg_match('/^\d{5}$/', $staff_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Staff ID tidak valid.']);
    exit;
}

$upd = $conn->prepare("UPDATE staff SET Nama = ?, Departmen = ? WHERE ID = ?");
if ($upd === false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit;
}
$upd->bind_param("sss", $staff_name, $staff_department, $staff_id);
$ok = $upd->execute();
$err = $upd->error;
$upd->close();

if ($ok) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Staff berhasil diperbarui.']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $err]);
}
exit;
?>