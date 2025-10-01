<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// authentication
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// authorization: only 'it' can delete
if (strtolower($_SESSION['username'] ?? '') !== 'it') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Hanya admin IT yang boleh menghapus.']);
    exit;
}

// only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// validate input
$inputId = $_POST['id'] ?? null;
if ($inputId === null || !is_numeric($inputId) || intval($inputId) <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter id tidak valid.']);
    exit;
}
$id = intval($inputId);

// fetch existing signature (so we can remove file if applicable)
$signature = null;
$stmt = $conn->prepare("SELECT signature FROM users WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $signature = $row['signature'] ?? null;
    }
    $stmt->close();
}

// begin transaction to keep DB consistent
$conn->begin_transaction();

try {
    // delete related admin_titles first (if any)
    $del_at = $conn->prepare("DELETE FROM admin_titles WHERE user_id = ?");
    if ($del_at === false) {
        throw new Exception('Prepare error (admin_titles): ' . $conn->error);
    }
    $del_at->bind_param("i", $id);
    if (!$del_at->execute()) {
        $del_at->close();
        throw new Exception('Execute error (admin_titles): ' . $del_at->error);
    }
    $del_at->close();

    // delete the user/participant row
    $del = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($del === false) {
        throw new Exception('Prepare error (users): ' . $conn->error);
    }
    $del->bind_param("i", $id);
    if (!$del->execute()) {
        throw new Exception('Execute error (users): ' . $del->error);
    }
    $affected = $del->affected_rows;
    $del->close();

    if ($affected <= 0) {
        // nothing deleted
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record tidak ditemukan atau sudah dihapus.']);
        exit;
    }

    // attempt to remove signature file if it appears to be a server file path
    if (!empty($signature)) {
        // only delete files stored in the uploads directory to avoid accidental removals
        $uploadsDir = realpath(__DIR__ . '/uploads');
        if ($uploadsDir !== false) {
            $uploadsDir = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            // signature may be a full URL or relative path or data URI
            // if data URI -> skip deletion
            if (stripos($signature, 'data:image') === false) {
                // extract basename and construct safe path
                $pathPart = parse_url($signature, PHP_URL_PATH) ?: $signature;
                $basename = basename($pathPart);
                $candidate = $uploadsDir . $basename;
                // verify file is inside uploads dir
                $real = realpath($candidate);
                if ($real && strpos($real, $uploadsDir) === 0 && is_file($real)) {
                    @unlink($real); // suppress errors, deletion optional
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Record berhasil dihapus.']);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    exit;
}
?>