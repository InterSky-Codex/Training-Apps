<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['title'])) {
    $_SESSION['error'] = "Parameter title tidak ditemukan.";
    header("Location: index");
    exit();
}

$title = $_GET['title'];
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.department, u.feedback, u.signature, u.created_at, 
           at.title, tc.training_code, tc.trainer_name, tc.userid, tc.duration 
    FROM users u
    LEFT JOIN admin_titles at ON u.id = at.user_id
    LEFT JOIN training_codes tc ON u.id = tc.user_id
    INNER JOIN (
        SELECT userid, MAX(created_at) AS latest
        FROM training_codes
        GROUP BY userid
    ) latest_tc ON tc.userid = latest_tc.userid AND u.created_at = latest_tc.latest
    WHERE at.title LIKE ? AND u.department IS NOT NULL AND u.department <> ''
");

$titleLike = '%' . $title . '%';
$stmt->bind_param("s", $titleLike);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="../../image/favicon-16x16.png">
    <title>Harris Hotel - Detail Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .signature-yes {
            color: white;
            background-color: green;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .signature-no {
            color: white;
            background-color: red;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .table tbody tr {
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="admin_dashboard" class="btn btn-primary mb-4">Back</a>
        <a href="export_detail.php?title=<?php echo urlencode($title); ?>" class="btn btn-success mb-4">Export to Excel</a>

        <?php if ($result->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Departmen</th>
                        <th>Feedback</th>
                        <th>Signature</th>
                        <th>Train Code</th>
                        <th>Trainer</th>
                        <th>Employee Id</th>
                        <th>Duration</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['feedback']); ?></td>
                            <td>
                                <?php if (!empty($row['signature'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['signature']); ?>" alt="Signature" style="width: 80px; height: auto;">
                                <?php else: ?>
                                    <span class="signature-no">Tidak Ada Signature</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['training_code'] ?? 'N/A'); ?></td>
                             <td><?php echo htmlspecialchars($row['trainer_name'] ?? 'N/A'); ?></td>
                              <td><?php echo htmlspecialchars($row['userid'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['duration'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">
                Tidak ada data yang ditemukan untuk title ini.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>