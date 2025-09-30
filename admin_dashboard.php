<?php
session_start();
include 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// helper safe htmlspecialchars
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// current user role
$role = $_SESSION['role'] ?? 'viewer';

// changed: pastikan user "it" selalu jadi superadmin (defensive)
if (isset($_SESSION['username']) && strtolower($_SESSION['username']) === 'it') {
    $_SESSION['role'] = 'superadmin';
    $role = 'superadmin';
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

// departments list (used in modal default)
$deps = [
    'FRONT OFFICE',
    'HOUSEKEEPING',
    'ENGINEERING',
    'SALES MARKETING',
    'FOOD AND BEVERAGE',
    'HUMAN RESOURCE',
    'ADMINISTRATIVE & GENERAL',
    'OTHER'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="logo.png">
    <title>Harris Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar-nav .nav-link { color: var(--primary-orange) !important; transition: all 0.3s ease; }
        .navbar-nav .nav-link:hover { color: var(--secondary-orange) !important; }

        .page-section { display: none; }
        .page-section.active { display: block; }
        .alert { transition: opacity 0.5s; }

        .action-cell .btn { min-width: 52px; display: inline-flex; align-items: center; justify-content: center; }
        .action-cell .btn i { font-size: 1rem; }
        @media (max-width: 575.98px) {
            .action-cell .btn { min-width: 44px; padding-left: .45rem; padding-right: .45rem; }
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
        <button type="button" class="btn btn-primary me-2" id="show-data">Data Training</button>

        <?php if (in_array($role, ['superadmin','editor'])): ?>
            <button type="button" class="btn btn-success me-2" id="show-add-user">Add New Training Code</button>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin','editor','viewer'])): ?>
            <button type="button" class="btn btn-outline-secondary" id="show-staff-data">Data Staff</button>
        <?php endif; ?>
    </div>
    
    <div id="data-section" class="page-section active">
    <div class="container">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Export Semua Data Training</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Filter sebelum export -->
                    <div class="col-12 mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label small">Filter Departemen</label>
                                <select id="filter_dept" class="form-select">
                                    <option value="">Semua Departemen</option>
                                    <?php foreach ($deps as $d): ?>
                                        <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Filter Trainer</label>
                                <input id="filter_trainer" type="text" class="form-control" placeholder="Nama trainer">
                            </div>
                        </div>
                    </div>

                    <!-- Form MTD -->
                    <div class="col-md-6">
                        <form action="export_excel.php" method="GET" class="mb-4" id="mtd-form">
                            <h6>Month-To-Date (MTD)</h6>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="mtd_month" class="form-label">Select Month:</label>
                                    <select name="mtd_month" id="mtd_month" class="form-select" required>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($i == date('n')) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label for="mtd_year" class="form-label">Year:</label>
                                    <select name="mtd_year" id="mtd_year" class="form-select" required>
                                        <?php
                                        $currentYear = intval(date('Y'));
                                        for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++):
                                        ?>
                                            <option value="<?= $y ?>" <?= ($y == $currentYear) ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">Export akan dilakukan untuk: <span id="mtd-display"><?= date('F Y') ?></span></small>
                            </div>

                            <!-- sertakan filter fields juga dalam form supaya submit membawa filter -->
                            <input type="hidden" name="department" id="mtd_department" value="">
                            <input type="hidden" name="trainer_name" id="mtd_trainer_name" value="">
                            <input type="hidden" name="title" id="mtd_title" value="">
                            <input type="hidden" name="status" id="mtd_status" value="">

                            <button type="submit" class="btn btn-primary">Export MTD</button>
                        </form>
                    </div>

                    <!-- Form YTD -->
                    <div class="col-md-6">
                        <form action="export_excel_ytd.php" method="GET" id="ytd-form">
                            <h6>Year-To-Date (YTD)</h6>
                            <div class="mb-3">
                                <label for="ytd_start_date" class="form-label">Select Start Date:</label>
                                <input type="date" name="ytd_start_date" id="ytd_start_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="ytd_end_date" class="form-label">Select End Date:</label>
                                <input type="date" name="ytd_end_date" id="ytd_end_date" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">Range yang dipilih: <span id="ytd-display">-</span></small>
                            </div>

                            <!-- filter untuk YTD dihapus: tidak lagi mengirim department/trainer/title/status -->

                            <button type="submit" class="btn btn-primary">Export YTD</button>
                        </form>
                    </div>
                </div>

                <!-- Export Trainer Names -->
                <div class="mt-3">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="export_trainers.php?type=all" class="btn btn-outline-success btn-sm" title="Export semua nama trainer">Export Trainer Names (All)</a>

                        <form id="export-trainers-mtd" action="export_trainers.php" method="GET" class="d-inline-block">
                            <input type="hidden" name="type" value="mtd">
                            <input type="hidden" name="mtd_month" id="export_mtd_month" value="<?= date('n') ?>">
                            <input type="hidden" name="mtd_year" id="export_mtd_year" value="<?= date('Y') ?>">
                            <!-- hidden filters for trainers export -->
                            <input type="hidden" name="department" id="export_trainers_department" value="">
                            <input type="hidden" name="trainer_name" id="export_trainers_trainer_name" value="">
                            <input type="hidden" name="title" id="export_trainers_title" value="">
                            <input type="hidden" name="status" id="export_trainers_status" value="">
                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Export trainer untuk bulan/taun yang dipilih di MTD">Export Trainer (MTD)</button>
                        </form>

                        <form id="export-trainers-ytd" action="export_trainers.php" method="GET" class="d-inline-block">
                            <input type="hidden" name="type" value="ytd">
                            <input type="hidden" name="ytd_start_date" id="export_ytd_start" value="">
                            <input type="hidden" name="ytd_end_date" id="export_ytd_end" value="">
                            <!-- hidden filters for trainers export (YTD) dihapus -->
                            <button type="submit" class="btn btn-outline-primary btn-sm" id="export-trainers-ytd-btn" disabled title="Pilih range terlebih dahulu untuk export YTD">Export Trainer (YTD)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Preview (replaces modal preview for easier editing) -->
        <div id="preview-inline" class="card mt-3" style="display:none;">
            <div class="card-header">
                <strong>Preview Data yang Akan Dieksport</strong>
                <small id="preview-meta" class="text-muted ms-2"></small>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive" style="max-height:50vh; overflow:auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Trainer</th>
                                <th>Employee Id</th>
                                <th>Created At</th>
                                <th>Expired At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="preview-body-inline">
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 d-flex justify-content-end gap-2">
                    <button id="cancel-preview-btn" class="btn btn-secondary btn-sm">Batal</button>
                    <button id="confirm-export-inline-btn" class="btn btn-primary btn-sm">Konfirmasi & Export</button>
                </div>
            </div>
        </div>
        
        <table class="table table-striped mt-4">
    <thead>
        <tr>
            <th class="text-center">Code</th>
            <th class="text-center">Title</th>
            <th class="text-center"><?php
                function sort_link($col, $label) {
                    $current = $_GET['sort_by'] ?? 'expired';
                    $dir = $_GET['sort_dir'] ?? 'desc';
                    $newDir = 'desc';
                    $arrow = '';
                    if ($current === $col) {
                        if (strtolower($dir) === 'asc') { $newDir = 'desc'; $arrow = ' ↑'; }
                        else { $newDir = 'asc'; $arrow = ' ↓'; }
                    }
                    $qs = http_build_query(array_merge($_GET, ['sort_by' => $col, 'sort_dir' => $newDir]));
                    return "<a href=\"?{$qs}\">{$label}{$arrow}</a>";
                }
                echo sort_link('trainer', 'Trainer');
            ?></th>
            <th class="text-center">Employee Id</th>
            <th class="text-center"><?php echo sort_link('expired', 'Expired At'); ?></th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $allowedSort = ['trainer' => 'tc.trainer_name','expired' => 'tc.expired_at'];
        $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'expired';
        $sort_dir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';
        if (!array_key_exists($sort_by, $allowedSort)) $sort_by = 'expired';
        $order_sql = $allowedSort[$sort_by] . ' ' . $sort_dir;

        $training_query = "SELECT tc.training_code, tc.trainer_name, tc.userid_trainer, at.title, tc.expired_at 
                                   FROM training_codes tc 
                                   JOIN admin_titles at ON tc.user_id = at.user_id 
                                   ORDER BY " . $order_sql;
        $training_result = $conn->query($training_query);
        
        $titles = []; 
        if ($training_result) {
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
            <td class="text-center"><?php echo h($training_row['training_code']); ?></td>
            <td class="text-center"><?php echo h($training_row['title']); ?></td>
            <td class="text-center"><?php echo h($training_row['trainer_name']); ?></td>
            <td class="text-center"><?php echo h($training_row['userid_trainer']); ?></td>
            <td class="text-center"><?php echo h($expired_at); ?></td>
            <td class="text-center" style="color: <?php echo $status_color; ?>;">
                <?php echo $status; ?>
                <?php if ($status == "Active"): ?>(<?php echo $remaining_days; ?> days remaining)<?php endif; ?>
            </td>
            <td class="text-center">
                <div class="d-flex justify-content-center flex-wrap gap-1 action-cell">
                    <a href="detail?title=<?php echo urlencode($training_row['title']); ?>" class="btn btn-primary btn-sm" title="Detail">
                        <i class="bi bi-eye"></i><span class="d-none d-md-inline ms-1">Detail</span>
                    </a>

                    <?php if (in_array($role, ['superadmin','editor'])): ?>
                        <a href="edit_training_code.php?code=<?php echo urlencode($training_row['training_code']); ?>" class="btn btn-warning btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i><span class="d-none d-md-inline ms-1">Edit</span>
                        </a>

                        <button type="button" class="btn btn-danger btn-sm delete-btn" data-code="<?php echo htmlspecialchars($training_row['training_code']); ?>" title="Delete">
                            <i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Delete</span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled title="No permission">
                            <i class="bi bi-lock"></i><span class="d-none d-md-inline ms-1">No permission</span>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php 
                }
            endwhile;
        }
        ?>
    </tbody>
</table>
    </div>
</div>

    <?php if (in_array($role, ['superadmin','editor'])): ?>
    <div id="add-user-section" class="page-section">
        <form action="add_training_code.php" method="POST" class="form mt-4">
            <div class="mb-3">
                <label for="training_code" class="form-label">Training Code</label>
                <input type="text" class="form-control" id="training_code" name="training_code" value="<?php echo generateRandomTrainingCode(); ?>" required readonly>
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
    <?php endif; ?>

    <?php if (in_array($role, ['superadmin','editor','viewer'])): ?>
    <div id="staff-data-section" class="page-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Staff</h5>
                <div>
                    <?php if (in_array($role, ['superadmin','editor'])): ?>
                        <button class="btn btn-sm btn-success" id="btn-open-add">Tambah Staff</button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-success" id="btn-open-add" style="display:none;">Tambah Staff</button>
                    <?php endif; ?>
                    <a href="#" class="btn btn-sm btn-secondary" id="cancel-staff-list" style="display:none;">Kembali</a>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $staff_q = $conn->query("SELECT ID, Nama, Departmen FROM staff ORDER BY ID ASC");
                        if ($staff_q && $staff_q->num_rows > 0):
                            while ($s = $staff_q->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo h($s['ID']); ?></td>
                            <td><?php echo h($s['Nama']); ?></td>
                            <td><?php echo h($s['Departmen']); ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <?php if (in_array($role, ['superadmin','editor'])): ?>
                                        <button class="btn btn-sm btn-primary btn-edit-staff" data-id="<?php echo htmlspecialchars($s['ID']); ?>" data-name="<?php echo htmlspecialchars($s['Nama']); ?>" data-dept="<?php echo htmlspecialchars($s['Departmen']); ?>">Edit</button>
                                        <button class="btn btn-sm btn-danger btn-delete-staff" data-id="<?php echo htmlspecialchars($s['ID']); ?>">Hapus</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="No permission">No permission</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="4" class="text-center">Belum ada data staff.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Add/Edit Staff -->
    <div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="staff-form">
            <div class="modal-header">
              <h5 class="modal-title" id="staffModalTitle">Tambah Staff</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Staff ID (5 digit)</label>
                    <input type="text" name="staff_id" id="modal_staff_id" class="form-control" pattern="\d{5}" maxlength="5" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="staff_name" id="modal_staff_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Departemen</label>
                    <select name="staff_department" id="modal_staff_dept" class="form-select" required>
                        <?php foreach ($deps as $d) {
                            echo "<option value=\"" . htmlspecialchars($d) . "\">" . htmlspecialchars($d) . "</option>";
                        } ?>
                    </select>
                </div>
                <input type="hidden" name="mode" id="modal_mode" value="add">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" id="modal_save_btn">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Preview Export Modal -->
    <div class="modal fade" id="previewExportModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Preview Data yang Akan Dieksport</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><small>Menampilkan hingga 1000 baris. Total ditemukan: <strong id="preview-total">0</strong></small></p>
            <div class="table-responsive" style="max-height:60vh; overflow:auto;">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Trainer</th>
                    <th>Employee Id</th>
                    <th>Created At</th>
                    <th>Expired At</th>
                  </tr>
                </thead>
                <tbody id="preview-body"></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="button" class="btn btn-primary" id="confirm-export-btn">Konfirmasi & Export</button>
          </div>
        </div>
      </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // non-blocking warning toast instead of modal alert so buttons remain clickable
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'warning',
        title: 'Peringatan!',
        text: 'Jangan Membagikan Code Jika Bukan Anda Yang Membuatnya!',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const $ = id => document.getElementById(id);
    const previewInline = $('preview-inline');
    const previewBodyInline = $('preview-body-inline');
    const previewMeta = $('preview-meta');
    const confirmExportInlineBtn = $('confirm-export-inline-btn');
    const cancelPreviewBtn = $('cancel-preview-btn');
 
    const sections = { data: $('data-section'), addUser: $('add-user-section'), staffData: $('staff-data-section') };
 
    function showSection(name) { Object.values(sections).forEach(s => { if (s) s.classList.remove('active'); }); if (sections[name]) sections[name].classList.add('active'); }

    const btnData = $('show-data'), btnAddUser = $('show-add-user'), btnStaffData = $('show-staff-data');
    if (btnData) btnData.addEventListener('click', () => showSection('data'));
    if (btnAddUser) btnAddUser.addEventListener('click', () => showSection('addUser'));
    if (btnStaffData) btnStaffData.addEventListener('click', () => showSection('staffData'));

    // delete training code
    document.body.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.delete-btn');
        if (!btn) return;
        const code = btn.dataset.code;
        Swal.fire({
            title: 'Hapus Training Code?',
            text: `Konfirmasi menghapus code ${code}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete_training_code.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({ training_code: code })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'Gagal', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Terjadi kesalahan saat menghapus.', 'error'));
            }
        });
    });

    // staff modal handlers
    const staffModalEl = document.getElementById('staffModal');
    const staffModal = new bootstrap.Modal(staffModalEl);
    const btnOpenAdd = document.getElementById('btn-open-add');
    const staffForm = document.getElementById('staff-form');

    if (btnOpenAdd) {
        btnOpenAdd.addEventListener('click', function(){
            document.getElementById('staffModalTitle').textContent = 'Tambah Staff';
            document.getElementById('modal_mode').value = 'add';
            document.getElementById('modal_staff_id').readOnly = false;
            document.getElementById('modal_staff_id').value = '';
            document.getElementById('modal_staff_name').value = '';
            // escape output
            document.getElementById('modal_staff_dept').value = '<?php echo h($deps[0]); ?>';
            staffModal.show();
        });
    }

    document.body.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.btn-edit-staff');
        if (btn) {
            const id = btn.dataset.id, name = btn.dataset.name, dept = btn.dataset.dept;
            document.getElementById('staffModalTitle').textContent = 'Edit Staff';
            document.getElementById('modal_mode').value = 'edit';
            document.getElementById('modal_staff_id').value = id;
            document.getElementById('modal_staff_id').readOnly = true;
            document.getElementById('modal_staff_name').value = name;
            document.getElementById('modal_staff_dept').value = dept;
            staffModal.show();
        }
    });

    document.body.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.btn-delete-staff');
        if (!btn) return;
        const id = btn.dataset.id;
        Swal.fire({
            title: 'Hapus staff?',
            text: `Yakin ingin menghapus staff ${id}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus'
        }).then((res) => {
            if (!res.isConfirmed) return;
            fetch('delete_staff.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ staff_id: id })
            })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    Swal.fire('Deleted', j.message, 'success').then(()=> location.reload());
                } else {
                    Swal.fire('Error', j.message || 'Gagal menghapus', 'error');
                }
            })
            .catch(()=> Swal.fire('Error', 'Terjadi kesalahan', 'error'));
        });
    });

    if (staffForm) {
        staffForm.addEventListener('submit', function(e){
            e.preventDefault();
            const fm = new FormData(staffForm);
            const mode = fm.get('mode') === 'edit' ? 'edit' : 'add';
            const url = (mode === 'add') ? 'add_staff.php' : 'edit_staff.php';
            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fm
            })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    staffModal.hide();
                    Swal.fire('Sukses', j.message, 'success').then(()=> location.reload());
                } else {
                    Swal.fire('Error', j.message || 'Gagal menyimpan', 'error');
                }
            })
            .catch(()=> Swal.fire('Error', 'Terjadi kesalahan', 'error'));
        });
    }

    // Preview export logic
    const mtdForm = document.getElementById('mtd-form');
    const ytdForm = document.getElementById('ytd-form');
    const previewModalEl = document.getElementById('previewExportModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const previewBody = document.getElementById('preview-body');
    const previewTotal = document.getElementById('preview-total');
    let pendingSubmitForm = null;

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"'`=\/]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c]; });
    }

    function renderPreview(rows, total, metaLabel) {
        previewBodyInline.innerHTML = '';
        previewMeta.textContent = `${metaLabel} — Total ditemukan: ${total}`;
        if (!rows || rows.length === 0) {
            previewBodyInline.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>';
            previewInline.style.display = 'block';
            return;
        }
        const frag = document.createDocumentFragment();
        rows.forEach(r => {
            const tr = document.createElement('tr');
            // include an Edit action that links to your edit page (so user can correct before exporting)
            tr.innerHTML = `<td>${escapeHtml(r.training_code)}</td>
                            <td>${escapeHtml(r.title)}</td>
                            <td>${escapeHtml(r.trainer_name)}</td>
                            <td>${escapeHtml(r.userid_trainer)}</td>
                            <td>${escapeHtml(r.created_at)}</td>
                            <td>${escapeHtml(r.expired_at)}</td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="edit_training_code.php?code=${encodeURIComponent(r.training_code)}">Edit</a>
                            </td>`;
            frag.appendChild(tr);
        });
        previewBodyInline.appendChild(frag);
        previewInline.style.display = 'block';
    }

    async function fetchPreview(params) {
        const qs = new URLSearchParams(params).toString();
        const res = await fetch('preview_export.php?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return res.json();
    }

    if (mtdForm) {
        mtdForm.addEventListener('submit', function(e){
            e.preventDefault();
            const month = document.getElementById('mtd_month')?.value;
            const year  = document.getElementById('mtd_year')?.value;
            if (!month || !year) { Swal.fire('Error', 'Pilih bulan dan tahun terlebih dahulu.', 'error'); return; }
            Swal.showLoading();
            fetchPreview({ type: 'mtd', mtd_month: month, mtd_year: year })
                .then(data => {
                    Swal.close();
                    if (!data || !data.success) { Swal.fire('Error', data?.message || 'Gagal mengambil preview.', 'error'); return; }
                    renderPreview(data.rows, data.total, `MTD ${document.getElementById('mtd-display')?.textContent || (month + ' ' + year)}`);
                    pendingSubmitForm = mtdForm;
                })
                .catch(()=> { Swal.close(); Swal.fire('Error', 'Terjadi kesalahan saat mengambil preview.', 'error'); });
        });
    }

    if (ytdForm) {
        ytdForm.addEventListener('submit', function(e){
            e.preventDefault();
            const s = document.getElementById('ytd_start_date')?.value;
            const eDate = document.getElementById('ytd_end_date')?.value;
            if (!s || !eDate) { Swal.fire('Error', 'Pilih start dan end date.', 'error'); return; }
            Swal.showLoading();

            // DIUBAH: YTD tidak mengirimkan filter apapun
            fetchPreview({
                type: 'ytd',
                ytd_start_date: s,
                ytd_end_date: eDate
                // Semua parameter filter dihapus
            })
                .then(data => {
                    Swal.close();
                    if (!data || !data.success) { Swal.fire('Error', data?.message || 'Gagal mengambil preview.', 'error'); return; }
                    // tampilkan range yang dipilih di preview
                    const disp = `${s} → ${eDate}`;
                    const ytdDisplayEl = document.getElementById('ytd-display');
                    if (ytdDisplayEl) ytdDisplayEl.textContent = disp;
                    renderPreview(data.rows, data.total, `YTD ${disp}`);
                    pendingSubmitForm = ytdForm;
                })
                .catch(()=> { Swal.close(); Swal.fire('Error', 'Terjadi kesalahan saat mengambil preview.', 'error'); });
        });
    }

    if (confirmExportInlineBtn) {
        confirmExportInlineBtn.addEventListener('click', function(){
            if (!pendingSubmitForm) { previewInline.style.display = 'none'; return; }
            previewInline.style.display = 'none';
            pendingSubmitForm.submit();
        });
    }
    if (cancelPreviewBtn) {
        cancelPreviewBtn.addEventListener('click', function(){
            previewInline.style.display = 'none';
            previewBodyInline.innerHTML = '';
            previewMeta.textContent = '';
            pendingSubmitForm = null;
        });
    }

    // sync trainer export hidden inputs with visible controls
     const mtdMonth = document.getElementById('mtd_month');
     const mtdYear = document.getElementById('mtd_year');
     const exportMtdMonth = document.getElementById('export_mtd_month');
     const exportMtdYear = document.getElementById('export_mtd_year');
     if (mtdMonth && exportMtdMonth) { exportMtdMonth.value = mtdMonth.value; mtdMonth.addEventListener('change', () => exportMtdMonth.value = mtdMonth.value); }
     if (mtdYear && exportMtdYear) { exportMtdYear.value = mtdYear.value; mtdYear.addEventListener('change', () => exportMtdYear.value = mtdYear.value); }

    const ytdStart = document.getElementById('ytd_start_date');
    const ytdEnd = document.getElementById('ytd_end_date');
    const exportYtdStart = document.getElementById('export_ytd_start');
    const exportYtdEnd = document.getElementById('export_ytd_end');
    const exportYtdBtn = document.getElementById('export-trainers-ytd-btn');
    function updateYtdExportState(){
        if (ytdStart && ytdEnd && exportYtdStart && exportYtdEnd && exportYtdBtn) {
            exportYtdStart.value = ytdStart.value;
            exportYtdEnd.value = ytdEnd.value;
            exportYtdBtn.disabled = !(ytdStart.value && ytdEnd.value);
        }
    }
    if (ytdStart) ytdStart.addEventListener('change', updateYtdExportState);
    if (ytdEnd) ytdEnd.addEventListener('change', updateYtdExportState);
    updateYtdExportState();
    
    // hide modal preview if still present (we use inline)
    try {
        const pm = document.getElementById('previewExportModal');
        if (pm) {
            // jika ada instance bootstrap, panggil hide() agar backdrop dihapus oleh Bootstrap
            try {
                const inst = bootstrap.Modal.getInstance(pm);
                if (inst) inst.hide();
            } catch(_) {}
            // fallback: force-hide element dan set atribut aksesibilitas
            pm.classList.remove('show');
            pm.style.display = 'none';
            pm.setAttribute('aria-hidden', 'true');
        }

        // Hapus backdrop yang tersisa dan bersihkan kelas pada body (mengembalikan klik pada elemen di halaman)
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
    } catch(e){}

    // selesai cleanup dan DOMContentLoaded handler
});
</script>

</body>