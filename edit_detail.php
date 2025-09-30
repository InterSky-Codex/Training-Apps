<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// hanya user "it" yang boleh mengedit detail
if (strtolower($_SESSION['username'] ?? '') !== 'it') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Hanya admin IT yang boleh mengedit.']);
    exit;
}

if (!isset($_SESSION['username'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request method']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$department = trim($_POST['department'] ?? '');
$feedback = trim($_POST['feedback'] ?? '');
$signature = trim($_POST['signature'] ?? '');
// userid not used in DB — ignore if sent

// basic validation
if ($id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Parameter id tidak valid']);
    exit;
}
if ($name === '' || $department === '') {
    echo json_encode(['success'=>false,'message'=>'Nama dan Departemen wajib diisi.']);
    exit;
}

// protect against huge payloads
$sig_len = is_string($signature) ? strlen($signature) : 0;
if ($sig_len > 2000000) { // 2MB limit
    echo json_encode(['success'=>false,'message'=>'Tanda tangan terlalu besar.']);
    exit;
}

// log minimal info for debugging (tidak menyimpan data signature penuh ke log)
error_log("edit_detail called - id={$id} name=".substr($name,0,80)." dept=".substr($department,0,80)." feedback_len=".strlen($feedback)." sig_len={$sig_len}");

// cek apakah id ada
$chk = $conn->prepare("SELECT id FROM users WHERE id = ?");
if ($chk === false) {
    echo json_encode(['success'=>false,'message'=>'DB prepare error: '.$conn->error]);
    exit;
}
$chk->bind_param('i', $id);
$chk->execute();
$resChk = $chk->get_result();
$exists = ($resChk && $resChk->num_rows > 0);
$chk->close();

if (!$exists) {
    echo json_encode(['success'=>false,'message'=>'Record tidak ditemukan untuk id: '.$id]);
    exit;
}

// lakukan update
$upd = $conn->prepare("UPDATE users SET name = ?, department = ?, feedback = ?, signature = ? WHERE id = ?");
if ($upd === false) {
    echo json_encode(['success'=>false,'message'=>'Prepare error: '.$conn->error]);
    exit;
}
$upd->bind_param('ssssi', $name, $department, $feedback, $signature, $id);
$ok = $upd->execute();
$err = $upd->error;
$affected = $upd->affected_rows;
$upd->close();

if ($ok) {
    // affected_rows bisa 0 jika data sama dengan sebelumnya
    if ($affected > 0) {
        echo json_encode(['success'=>true,'message'=>'Peserta berhasil diperbarui.','affected'=>$affected]);
    } else {
        echo json_encode(['success'=>true,'message'=>'Tidak ada perubahan (data sama).','affected'=>$affected]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Gagal update: '.$err]);
}
exit;
?>