<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// block if role not allowed to edit training codes
if (!in_array($_SESSION['role'] ?? 'viewer', ['superadmin','editor'])) {
    $_SESSION['error'] = 'Unauthorized: Anda tidak mempunyai izin untuk mengubah data.';
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['training_code'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $trainer_name = $_POST['trainer_name'] ?? '';
    $userid_trainer = $_POST['userid_trainer'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $expired_days = intval($_POST['expired_days'] ?? 0);

    if ($code === '') {
        $_SESSION['error'] = 'Training code tidak ditemukan.';
        header('Location: admin_dashboard.php');
        exit;
    }

    // ambil user_id terkait training_code
    $stmt = $conn->prepare("SELECT user_id FROM training_codes WHERE training_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $user_id = $row['user_id'] ?? null;

    // update training_codes (tidak ada kolom title di sini)
    if ($expired_days > 0) {
        $expired_at = date('Y-m-d H:i:s', strtotime("+{$expired_days} days"));
        $stmt = $conn->prepare("UPDATE training_codes SET trainer_name=?, userid_trainer=?, duration=?, expired_at=? WHERE training_code=?");
        $stmt->bind_param("ssiss", $trainer_name, $userid_trainer, $duration, $expired_at, $code);
    } else {
        $stmt = $conn->prepare("UPDATE training_codes SET trainer_name=?, userid_trainer=?, duration=? WHERE training_code=?");
        $stmt->bind_param("ssis", $trainer_name, $userid_trainer, $duration, $code);
    }

    $ok1 = $stmt->execute();
    $stmt->close();

    $ok2 = true;
    // update title di tabel admin_titles jika ada user_id
    if ($user_id !== null && $title !== '') {
        $stmt2 = $conn->prepare("UPDATE admin_titles SET title = ? WHERE user_id = ?");
        $stmt2->bind_param("si", $title, $user_id);
        $ok2 = $stmt2->execute();
        $stmt2->close();
    }

    if ($ok1 && $ok2) {
        $_SESSION['success'] = 'Data training berhasil diperbarui.';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui data.';
    }

    header('Location: admin_dashboard.php');
    exit;
}

// GET: tampilkan form (ambil title dari admin_titles lewat join)
$code = $_GET['code'] ?? '';
if ($code === '') {
    $_SESSION['error'] = 'Training code tidak ditemukan.';
    header('Location: admin_dashboard.php');
    exit;
}

$stmt = $conn->prepare("SELECT tc.training_code, tc.trainer_name, tc.userid_trainer, tc.duration, tc.expired_at, tc.user_id, at.title 
                        FROM training_codes tc 
                        LEFT JOIN admin_titles at ON tc.user_id = at.user_id 
                        WHERE tc.training_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$training = $result->fetch_assoc();
$stmt->close();

if (!$training) {
    $_SESSION['error'] = 'Record tidak ditemukan.';
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Edit Training Code</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3>Edit Training Code: <?php echo htmlspecialchars($training['training_code']); ?></h3>
    <form method="POST" class="mt-3">
        <input type="hidden" name="training_code" value="<?php echo htmlspecialchars($training['training_code']); ?>">
        <div class="mb-3">
            <label class="form-label">Judul Training</label>
            <input name="title" class="form-control" value="<?php echo htmlspecialchars($training['title'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Trainer Name</label>
            <input name="trainer_name" class="form-control" value="<?php echo htmlspecialchars($training['trainer_name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Employee Id</label>
            <input name="userid_trainer" class="form-control" value="<?php echo htmlspecialchars($training['userid_trainer'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input name="duration" class="form-control" value="<?php echo htmlspecialchars($training['duration'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Expired (in days) — isi untuk set ulang expired</label>
            <input name="expired_days" type="number" min="0" class="form-control" placeholder="Kosongkan untuk tidak mengubah" value="">
        </div>
        <a href="admin_dashboard.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
</body>
</html>