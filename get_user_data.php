<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed', 
        'details' => $conn->connect_error
    ]);
    exit;
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Unauthorized access',
        'message' => 'Silakan login terlebih dahulu'
    ]);
    exit;
}
try {
    $query = "SELECT 
                u.id AS user_id, 
                tc.training_code, 
                at.title
              FROM users u
              LEFT JOIN training_codes tc ON u.id = tc.user_id
              LEFT JOIN admin_titles at ON u.id = at.user_id
              ORDER BY u.id";

    $result = $conn->query($query);

    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    if (empty($users)) {
        http_response_code(404);
        echo json_encode([
            'error' => 'No users found',
            'message' => 'Tidak ada data user yang ditemukan'
        ]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}