<?php
session_start();
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// allow viewer/editor/superadmin to preview/export
$role = $_SESSION['role'] ?? 'viewer';
if (!in_array($role, ['viewer','editor','superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';

try {
    if ($type === 'mtd') {
        $m = intval($_GET['mtd_month'] ?? 0);
        $y = intval($_GET['mtd_year'] ?? 0);
        if ($m < 1 || $m > 12 || $y < 2000) {
            throw new Exception('Parameter bulan/tahun tidak valid.');
        }
        $stmt = $conn->prepare("SELECT tc.training_code, COALESCE(at.title,'') AS title, tc.trainer_name, tc.userid_trainer, tc.created_at, tc.expired_at
                                FROM training_codes tc
                                LEFT JOIN admin_titles at ON tc.user_id = at.user_id
                                WHERE YEAR(tc.created_at) = ? AND MONTH(tc.created_at) = ?
                                ORDER BY tc.created_at DESC
                                LIMIT 1000");
        $stmt->bind_param("ii", $y, $m);
    } elseif ($type === 'ytd') {
        $s = $_GET['ytd_start_date'] ?? '';
        $e = $_GET['ytd_end_date'] ?? '';
        $sd = date_create($s);
        $ed = date_create($e);
        if (!$sd || !$ed) {
            throw new Exception('Format tanggal tidak valid.');
        }
        $start = $sd->format('Y-m-d');
        $end = $ed->format('Y-m-d');
        $stmt = $conn->prepare("SELECT tc.training_code, COALESCE(at.title,'') AS title, tc.trainer_name, tc.userid_trainer, tc.created_at, tc.expired_at
                                FROM training_codes tc
                                LEFT JOIN admin_titles at ON tc.user_id = at.user_id
                                WHERE DATE(tc.created_at) BETWEEN ? AND ?
                                ORDER BY tc.created_at DESC
                                LIMIT 1000");
        $stmt->bind_param("ss", $start, $end);
    } else {
        throw new Exception('Parameter type harus diisi (mtd|ytd).');
    }

    if (!$stmt->execute()) {
        throw new Exception('Query error: ' . $stmt->error);
    }
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();

    // also return total count (without limit) — small optimization: estimate count
    // do a quick count query
    if ($type === 'mtd') {
        $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM training_codes WHERE YEAR(created_at)=? AND MONTH(created_at)=?");
        $cntStmt->bind_param("ii", $y, $m);
        $cntStmt->execute();
        $cntRes = $cntStmt->get_result()->fetch_assoc();
        $total = intval($cntRes['cnt'] ?? count($rows));
        $cntStmt->close();
    } else {
        $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM training_codes WHERE DATE(created_at) BETWEEN ? AND ?");
        $cntStmt->bind_param("ss", $start, $end);
        $cntStmt->execute();
        $cntRes = $cntStmt->get_result()->fetch_assoc();
        $total = intval($cntRes['cnt'] ?? count($rows));
        $cntStmt->close();
    }

    echo json_encode(['success' => true, 'total' => $total, 'rows' => $rows]);
    exit;

} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    exit;
}
?>