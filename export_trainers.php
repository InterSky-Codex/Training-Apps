<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Not authenticated';
    exit;
}

// allow viewer/editor/superadmin to export trainers
$role = $_SESSION['role'] ?? 'viewer';
if (!in_array($role, ['viewer','editor','superadmin'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Unauthorized';
    exit;
}

// read params
$type = $_GET['type'] ?? 'all';

try {
    if ($type === 'mtd') {
        $m = intval($_GET['mtd_month'] ?? 0);
        $y = intval($_GET['mtd_year'] ?? 0);
        if ($m < 1 || $m > 12 || $y < 2000) throw new Exception('Invalid mtd params');
        $stmt = $conn->prepare("SELECT DISTINCT tc.trainer_name, tc.userid_trainer, tc.training_code, COALESCE(at.title,'') AS title, tc.created_at, tc.expired_at
                                FROM training_codes tc
                                LEFT JOIN admin_titles at ON tc.user_id = at.user_id
                                WHERE YEAR(tc.created_at) = ? AND MONTH(tc.created_at) = ?
                                ORDER BY tc.trainer_name ASC");
        $stmt->bind_param("ii", $y, $m);
    } elseif ($type === 'ytd') {
        $s = $_GET['ytd_start_date'] ?? '';
        $e = $_GET['ytd_end_date'] ?? '';
        $sd = date_create($s);
        $ed = date_create($e);
        if (!$sd || !$ed) throw new Exception('Invalid ytd dates');
        $start = $sd->format('Y-m-d');
        $end = $ed->format('Y-m-d');
        $stmt = $conn->prepare("SELECT DISTINCT tc.trainer_name, tc.userid_trainer, tc.training_code, COALESCE(at.title,'') AS title, tc.created_at, tc.expired_at
                                FROM training_codes tc
                                LEFT JOIN admin_titles at ON tc.user_id = at.user_id
                                WHERE DATE(tc.created_at) BETWEEN ? AND ?
                                ORDER BY tc.trainer_name ASC");
        $stmt->bind_param("ss", $start, $end);
    } else {
        // all
        $stmt = $conn->prepare("SELECT DISTINCT tc.trainer_name, tc.userid_trainer, tc.training_code, COALESCE(at.title,'') AS title, tc.created_at, tc.expired_at
                                FROM training_codes tc
                                LEFT JOIN admin_titles at ON tc.user_id = at.user_id
                                ORDER BY tc.trainer_name ASC");
    }

    if ($stmt && !$stmt->execute()) {
        throw new Exception('Query error: ' . $stmt->error);
    }

    $res = $stmt ? $stmt->get_result() : null;

    // prepare CSV
    $filename = 'trainers_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    // output BOM for Excel UTF-8
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    // header row
    fputcsv($out, ['Trainer Name','Employee Id','Training Code','Title','Created At','Expired At']);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['trainer_name'],
                $row['userid_trainer'] ?? '',
                $row['training_code'] ?? '',
                $row['title'] ?? '',
                $row['created_at'] ?? '',
                $row['expired_at'] ?? ''
            ]);
        }
    }

    if ($stmt) $stmt->close();
    if (is_resource($out)) fclose($out);
    exit;

} catch (Exception $ex) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Error: ' . $ex->getMessage();
    exit;
}
?>