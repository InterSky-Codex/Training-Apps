<?php
session_start();
include 'config.php';

if (!isset($_SESSION['training_code'])) {
    header("Location: login_user.php");
    exit();
}

$training_code = $_SESSION['training_code'];
$query = "SELECT at.title, tc.duration, tc.trainer_name 
          FROM training_codes tc
          LEFT JOIN admin_titles at ON tc.user_id = at.user_id
          WHERE tc.training_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $training_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="logo.png">
    <title>Harris Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-orange: #FF6B35;
            --light-orange: #FDCA40;
            --bg-color: #F7F7F7;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
        }

        .feedback-container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.2);
            border-top: 6px solid var(--primary-orange);
        }

        .form-label {
            color: var(--primary-orange);
            font-weight: 600;
        }

        canvas {
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
            height: 200px;
            touch-action: none; 
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
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="feedback-container">

                    <form action="process_feedback.php" method="POST" onsubmit="return validateForm()">
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="Masukan Nama Anda">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">User   ID</label>
                                <input type="text" name="user_id" class="form-control" required placeholder="Masukan ID Anda" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-12">
                                <label class="form-label">Department</label>
                                <div style="display: flex; flex-wrap: wrap; justify-content: center;">
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="FRONT OFFICE" required id="btnradio1">
                                        <label class="btn btn-outline-secondary" for="btnradio1">FRONT OFFICE</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="HOUSEKEEPING" required id="btnradio2">
                                        <label class="btn btn-outline-secondary" for="btnradio2">HOUSEKEEPING</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="ENGINEERING" required id="btnradio3">
                                        <label class="btn btn-outline-secondary" for="btnradio3">ENGINEERING</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="SALES MARKETING" required id="btnradio5">
                                        <label class="btn btn-outline-secondary" for="btnradio5">SALES MARKETING</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="FOOD AND BEVERAGE" required id="btnradio6">
                                        <label class="btn btn-outline-secondary" for="btnradio6">FOOD AND BEVERAGE</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="HUMAN RESOURCE" required id="btnradio7">
                                        <label class="btn btn-outline-secondary" for="btnradio7">HUMAN RESOURCE</label>
                                    </div>
                                    <div style="margin: 10px;">
                                        <input type="radio" class="btn-check" name="department" value="ADMINISTRATIVE & GENERAL" required id="btnradio8">
                                        <label class="btn btn-outline-secondary" for="btnradio8">ADMINISTRATIVE & GENERAL</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Judul</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($user_data['title']); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Training Code</label>
                                <input type="text" name="training_code" class="form-control" value="<?php echo htmlspecialchars($training_code); ?>" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Feedback</label>
                                <select name="feedback" class="form-select" required>
                                    <option value="">Select Feedback</option>
                                    <option value="Poor">Poor</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Excellent">Excellent</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Duration</label>
                                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($user_data['duration']); ?>" readonly>
                            </div>
                        </div>
                        <div class="signature-container">
                            <label class="form-label">Tanda Tangan</label>
                            <div>
                                <canvas id="signature" width="300" height="200"></canvas>
                            </div>
                            <input type="hidden" name="signature" id="signature-data">
                            <button type="button" class="btn btn-secondary" onclick="clearSignature()">Hapus Tanda Tangan</button>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" id="submit-btn" class="btn btn-primary w-100" disabled>Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var canvas = document.getElementById("signature");
        var ctx = canvas.getContext("2d");
        var isDrawing = false;
        var signatureData = document.getElementById("signature-data");
        var submitButton = document.getElementById("submit-btn");
        var hasDrawn = false; // Variabel status

        function getPosition(e) {
            var rect = canvas.getBoundingClientRect();
            if (e.touches) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            } else {
                return { x: e.clientX - rect.left, y: e.clientY - rect.top };
            }
        }

        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            hasDrawn = true; 
            var pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            submitButton.disabled = false;
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            var pos = getPosition(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.strokeStyle = "black";
            ctx.lineWidth = 2;
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
            signatureData.value = canvas.toDataURL(); 
        }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            signatureData.value = "";
            hasDrawn = false; 
            submitButton.disabled = true; 
        }

        function validateForm() {
            if (!hasDrawn) {
                Swal.fire({
                    title: "Error",
                    text: "Tanda tangan harus diisi!",
                    icon: "error"
                });
                return false; 
            }
            return true; 
        }

        canvas.addEventListener("mousedown", startDrawing);
        canvas.addEventListener("mousemove", draw);
        canvas.addEventListener("mouseup", stopDrawing);
        canvas.addEventListener("mouseleave", stopDrawing);

        canvas.addEventListener("touchstart", startDrawing);
        canvas.addEventListener("touchmove", draw);
        canvas.addEventListener("touchend", stopDrawing);
        <?php if (isset($_SESSION['message'])): ?>
        Swal.fire({
            title: "Info",
            text: "<?php echo $_SESSION['message']; ?>",
            icon: "success",
            willClose: () => {
                setTimeout(() => {
                    window.location.href = "login_user";
                }, 1000);
            }
        });
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>