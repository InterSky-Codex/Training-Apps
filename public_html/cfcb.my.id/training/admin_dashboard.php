<?php
session_start();
include 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

function generateRandomTrainingCode() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomString = '';
    for ($i = 0; $i < 6; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

$query = "SELECT u.*, at.title 
          FROM users u 
          LEFT JOIN admin_titles at ON u.id = at.user_id 
          ORDER BY u.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="../../image/favicon-16x16.png">
    <title>Harris Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar-nav .nav-link {
            color: var(--primary-orange) !important;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--secondary-orange) !important;
        }

        .page-section {
            display: none;
        }

        .page-section.active {
            display: block;
        }

        .alert {
            transition: opacity 0.5s;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="logout">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container mt-5">
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success" id="success-alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger" id="error-alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

    <center><h2 class="mb-4">Welcome Admin</h2></center>

    <div class="mb-4 text-center">
        <button class="btn btn-primary me-2" id="show-data">Data</button>
        <button class="btn btn-success" id="show-add-user">Add New Training Code</button>
    </div>
    
    <div id="data-section" class="page-section active">
    <div class="container">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Export Semua Data Training</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Form MTD -->
                    <div class="col-md-6">
                        <form action="export_excel.php" method="GET" class="mb-4">
                            <h6>Month-To-Date (MTD)</h6>
                            <div class="mb-3">
                                <label for="mtd_month" class="form-label">Select Month:</label>
                                <select name="mtd_month" id="mtd_month" class="form-select" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Export MTD</button>
                        </form>
                    </div>

                    <!-- Form YTD -->
                    <div class="col-md-6">
                        <form action="export_excel_ytd.php" method="GET">
                            <h6>Year-To-Date (YTD)</h6>
                            <div class="mb-3">
                                <label for="ytd_start_date" class="form-label">Select Start Date:</label>
                                <input type="date" name="ytd_start_date" id="ytd_start_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="ytd_end_date" class="form-label">Select End Date:</label>
                                <input type="date" name="ytd_end_date" id="ytd_end_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Export YTD</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-striped mt-4">
    <thead>
        <tr>
            <th class="text-center">Code</th>
            <th class="text-center">Title</th>
            <th class="text-center">Trainer</th>
            <th class="text-center">Employee Id</th>
            <th class="text-center">Expired At</th>
            <th class="text-center">Status</th>
             <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $training_query = "SELECT tc.training_code, tc.trainer_name, tc.userid_trainer, at.title, tc.expired_at 
                           FROM training_codes tc 
                           JOIN admin_titles at ON tc.user_id = at.user_id 
                           ORDER BY tc.expired_at DESC";
        $training_result = $conn->query($training_query);
        
        $titles = []; 
        while ($training_row = $training_result->fetch_assoc()):
            $expired_at = $training_row['expired_at'];
            $current_date = date('Y-m-d H:i:s');
            $status = ($current_date < $expired_at) ? "Active" : "Done";
            $status_color = ($status == "Active") ? "green" : "red";
            $remaining_days = ($status == "Active") ? floor((strtotime($expired_at) - strtotime($current_date)) / (60 * 60 * 24)) : 0;

            if (!in_array($training_row['title'], $titles)) {
                $titles[] = $training_row['title'];
        ?>
        <tr>
            <td class="text-center"><?php echo htmlspecialchars($training_row['training_code']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($training_row['title']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($training_row['trainer_name']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($training_row['userid_trainer']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($expired_at); ?></td>
            <td class="text-center" style="color: <?php echo $status_color; ?>;">
                <?php echo $status; ?>
                <?php if ($status == "Active"): ?>
                    (<?php echo $remaining_days; ?> days remaining)
                <?php endif; ?>
            </td>
            <td class="text-center">
                <a href="detail?title=<?php echo $training_row['title']; ?>" class="btn btn-primary btn-sm">Detail</a>
            </td>
        </tr>
        <?php 
            } 
        endwhile; ?>
    </tbody>
</table>
    </div>
</div>

    <div id="add-user-section" class="page-section">
        <form action="add_training_code" method="POST" class="form mt-4">
            <div class="mb-3">
                <label for="training_code" class="form-label">Training Code</label>
                <input type="text" class="form-control" id="training_code" name="training_code" 
                       value="<?php echo generateRandomTrainingCode(); ?>" required readonly>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Judul Training</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="trainer_name" class="form-label">Trainer Name</label>
                <input type="text" class="form-control" id="trainer_name" name="trainer_name" required>
            </div>
            <div class="mb-3">
                <label for="userid_trainer" class="form-label">Employee Id</label>
                <input type="text" class="form-control" id="userid_trainer" name="userid_trainer" required>
            </div>
            <div class="mb-3">
                <label for="duration" class="form-label">Duration (in Minutes)</label>
                <select class="form-select" id="duration" name="duration" required>
                    <option value="">Select Duration</option>
                    <option value="30">30 Minutes</option>
                    <option value="60">60 Minutes</option>
                    <option value="90">90 Minutes</option>
                    <option value="120">120 Minutes</option>
                    <option value="150">150 Minutes</option>
                    <option value="180">180 Minutes</option>
                    <option value="260">260 Minutes</option>
                    <option value="720">12 Jam</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="expired_days" class="form-label">Expired (in Days)</label>
                <select class="form-select" id="expired_days" name="expired_days" required>
                    <option value="">Select Expiration Days</option>
                    <option value="1">1 Day</option>
                    <option value="3">3 Days</option>
                    <option value="5">5 Days</option>
                    <option value="7">7 Days</option>
                    <option value="10">10 Days</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Add Training Code</button>
        </form>
    </div>
</div>
<script>
window.onload = function () {
    Swal.fire({
        title: "Peringatan!",
        text: "Jangan Membagikan Code Jika Bukan Anda Yang Membuatnya!",
        icon: "warning",
        confirmButtonText: "Mengerti",
        allowOutsideClick: false
    });
};
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = 0;
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        });

        document.getElementById('show-data').addEventListener('click', function () {
            document.getElementById('data-section').classList.add('active');
            document.getElementById('add-user-section').classList.remove('active');
        });

        document.getElementById('show-add-user').addEventListener('click', function () {
            document.getElementById('data-section').classList.remove('active');
            document.getElementById('add-user-section').classList.add('active');
        });
    });
</script>

</body>
</html>