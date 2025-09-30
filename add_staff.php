<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// blokir operasi jika role tidak berhak menambahkan staff
// role yang diizinkan untuk menambah: 'superadmin' atau 'editor'
if (($_SESSION['role'] ?? 'viewer') !== 'superadmin' && ($_SESSION['role'] ?? 'viewer') !== 'editor') {
    $msg = 'Unauthorized: Anda tidak mempunyai izin untuk menambah staff.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $msg = 'Invalid request.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}

$staff_id = trim($_POST['staff_id'] ?? '');
$staff_name = trim($_POST['staff_name'] ?? '');
$staff_department = trim($_POST['staff_department'] ?? '');

// validasi dasar
if ($staff_id === '' || $staff_name === '' || $staff_department === '') {
    $msg = 'Semua field harus diisi.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}

if (!preg_match('/^\d{5}$/', $staff_id)) {
    $msg = 'Staff ID harus 5 digit angka.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}

// cek duplikat
$chk = $conn->prepare("SELECT COUNT(*) FROM staff WHERE ID = ?");
$chk->bind_param("s", $staff_id);
$chk->execute();
$chk->bind_result($count);
$chk->fetch();
$chk->close();

if ($count > 0) {
    $msg = 'Staff ID sudah ada.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}

// insert
$ins = $conn->prepare("INSERT INTO staff (ID, Nama, Departmen) VALUES (?, ?, ?)");
if ($ins === false) {
    $msg = 'Prepare error: ' . $conn->error;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
    header('Location: admin_dashboard.php');
    exit;
}
$ins->bind_param("sss", $staff_id, $staff_name, $staff_department);
$ok = $ins->execute();
$err = $ins->error;
$ins->close();

if ($ok) {
    $msg = 'Staff berhasil ditambahkan.';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }
    $_SESSION['success'] = $msg;
} else {
    $msg = 'Gagal menambahkan staff: ' . $err;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error'] = $msg;
}

header('Location: admin_dashboard.php');
exit;
?>