<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$month = isset($_GET['mtd_month']) ? intval($_GET['mtd_month']) : 0;
$year  = isset($_GET['mtd_year']) ? intval($_GET['mtd_year']) : 0;

if ($month < 1 || $month > 12 || $year < 1900) {
    echo json_encode(['success' => false, 'message' => 'Parameter bulan/tahun tidak valid.']);
    exit;
}

$ym = sprintf('%04d-%02d', $year, $month);

// periksa berdasarkan kolom expired_at (ada di schema saat ini).
// Jika ingin check berdasarkan created_at, ubah field di query.
$sql = "SELECT COUNT(*) AS cnt FROM training_codes WHERE DATE_FORMAT(expired_at, '%Y-%m') = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('s', $ym);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $count = intval($row['cnt'] ?? 0);
    echo json_encode(['success' => true, 'found' => $count > 0, 'count' => $count]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan query.']);
}
exit;
?>