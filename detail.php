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

// determine if there are rows for nicer UI
$hasRows = ($result && $result->num_rows > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="logo.png">
    <title>Harris Hotel - Detail Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .signature-yes { color: white; background-color: green; padding: 5px 10px; border-radius: 5px; }
        .signature-no { color: white; background-color: red; padding: 5px 10px; border-radius: 5px; }
        .table tbody tr { border-bottom: 1px solid #dee2e6; }
        /* ukuran visual canvas diset di CSS, ukuran piksel sesuaikan di JS */
        #signature-pad { width: 100%; height: 200px; border:1px solid #ccc; display:block; touch-action: none; }
        .empty-state { color: #6c757d; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <a href="admin_dashboard" class="btn btn-outline-secondary">Back</a>
                <?php if ($hasRows): ?>
                    <a href="export_detail.php?title=<?php echo urlencode($title); ?>" class="btn btn-success">Export to Excel</a>
                <?php else: ?>
                    <button class="btn btn-success" disabled title="Tidak ada data untuk diexport">Export to Excel</button>
                <?php endif; ?>
            </div>
            <!-- Tombol Tambah Peserta dihapus (fitur disabled) -->
        </div>

        <?php if ($hasRows): ?>
            <table class="table table-sm">
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
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="participants-tbody">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?php echo intval($row['id']); ?>">
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
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <?php if (strtolower($_SESSION['username'] ?? '') === 'it'): ?>
                                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?php echo intval($row['id']); ?>"
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                            data-userid="<?php echo htmlspecialchars($row['userid'] ?? ''); ?>"
                                            data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                            data-feedback="<?php echo htmlspecialchars($row['feedback']); ?>"
                                            data-sign="<?php echo htmlspecialchars($row['signature']); ?>">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?php echo intval($row['id']); ?>">Hapus</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="No permission">No permission</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center my-5 empty-state">
                <div class="mb-3">
                    <i class="bi bi-inbox" style="font-size:64px;"></i>
                </div>
                <h4 class="mb-1">Tidak ada peserta untuk training ini</h4>
                <p class="mb-3">Tidak ditemukan data peserta untuk title: <strong><?php echo htmlspecialchars($title); ?></strong></p>
                <div>
                    <a href="admin_dashboard" class="btn btn-primary me-2">Kembali ke Dashboard</a>
                    <a href="admin_dashboard" class="btn btn-outline-secondary">Lihat Semua Training</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Add/Edit Participant -->
    <div class="modal fade" id="participantModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="participant-form">
            <div class="modal-header">
              <h5 class="modal-title" id="participantModalTitle">Edit Peserta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="p_id" value="">
                <!-- mode dihapus; modal hanya dipakai untuk edit -->
                <!-- <input type="hidden" name="mode" id="p_mode" value="add"> -->
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="p_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="userid" id="p_userid" class="form-control" placeholder="Masukkan Employee ID">
                </div>

                <div class="mb-3">
                    <label class="form-label">Departemen</label>
                    <select name="department" id="p_dept" class="form-select" required>
                        <option value="">Pilih Departemen</option>
                        <option>FRONT OFFICE</option>
                        <option>HOUSEKEEPING</option>
                        <option>ENGINEERING</option>
                        <option>SALES MARKETING</option>
                        <option>FOOD AND BEVERAGE</option>
                        <option>HUMAN RESOURCE</option>
                        <option>ADMINISTRATIVE & GENERAL</option>
                        <option>OTHER</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Feedback</label>
                    <select name="feedback" id="p_feedback" class="form-select" required>
                        <option value="">Pilih Feedback</option>
                        <option value="Poor">Poor</option>
                        <option value="Medium">Medium</option>
                        <option value="Excellent">Excellent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanda Tangan</label>
                    <div class="border p-2">
                        <canvas id="signature-pad"></canvas>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="clear-signature">Hapus Tanda Tangan</button>
                    <!-- hidden field holds the current signature (base64) that will be posted -->
                    <input type="hidden" name="signature" id="p_signature">
                </div>
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" id="p_save_btn">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- libs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <script>
    (function(){
        const participantModalEl = document.getElementById('participantModal');
        const participantModal = new bootstrap.Modal(participantModalEl);
        const form = document.getElementById('participant-form');
        const canvas = document.getElementById("signature-pad");
        const signatureInput = document.getElementById("p_signature");
        let signaturePad = null;

        // buka modal edit
        document.body.addEventListener('click', function(e){
            const editBtn = e.target.closest && e.target.closest('.btn-edit');
            if (editBtn) {
                document.getElementById('participantModalTitle').textContent = 'Edit Peserta';
                document.getElementById('p_id').value = editBtn.dataset.id;
                document.getElementById('p_name').value = editBtn.dataset.name || '';
                document.getElementById('p_userid').value = editBtn.dataset.userid || '';
                document.getElementById('p_dept').value = editBtn.dataset.dept || '';
                document.getElementById('p_feedback').value = editBtn.dataset.feedback || '';
                const signToLoad = editBtn.dataset.sign || '';
                signatureInput.value = signToLoad || '';
                // inisialisasi signaturePad saat diperlukan
                try { signaturePad = new (window.SignaturePad || SignaturePad)(canvas); } catch(e){ signaturePad = null; }
                // jika ada signature base64, tampilkan di canvas (opsional)
                if (signaturePad && signToLoad && signToLoad.startsWith('data:image')) {
                    const img = new Image();
                    img.onload = function(){
                        signaturePad.clear();
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0,0,canvas.width,canvas.height);
                        // draw image scaled to canvas
                        const ratio = Math.min(canvas.width / img.width, canvas.height / img.height);
                        const w = img.width * ratio, h = img.height * ratio;
                        ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, w, h);
                    };
                    img.src = signToLoad;
                }
                participantModal.show();
            }

            // delete handler: konfirmasi lalu panggil delete_detail.php (POST)
            const delBtn = e.target.closest && e.target.closest('.btn-delete');
            if (delBtn) {
                const id = delBtn.dataset.id;
                if (!id) {
                    Swal.fire('Error', 'ID tidak ditemukan.', 'error');
                    return;
                }
                Swal.fire({
                    title: 'Hapus data?',
                    text: 'Data peserta akan dihapus permanen. Lanjutkan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(async (res) => {
                    if (!res.isConfirmed) return;
                    try {
                        const resp = await fetch('delete_detail.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({ id: id })
                        });
                        const text = await resp.text();
                        let data;
                        try { data = JSON.parse(text); } catch(e) { data = { success: resp.ok, message: text }; }
                        if (resp.ok && data && data.success) {
                            Swal.fire('Berhasil', data.message || 'Record dihapus.', 'success').then(()=>{
                                const row = document.querySelector('tr[data-id="'+id+'"]');
                                if (row) row.remove();
                                else location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', data?.message || ('HTTP ' + resp.status), 'error');
                        }
                    } catch (err) {
                        console.error('delete error', err);
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            }
        });

        // submit edit form
        form && form.addEventListener('submit', async function(e){
            e.preventDefault();
            try {
                if (!signaturePad) {
                    try { signaturePad = new (window.SignaturePad || SignaturePad)(canvas); } catch(e){ signaturePad = null; }
                }
                if (signaturePad && !signaturePad.isEmpty()) {
                    try { signatureInput.value = signaturePad.toDataURL("image/png"); } catch(e){}
                }
            } catch(err){
                console.error('prepare signature error', err);
            }

            const fm = new FormData(form);
            const url = './edit_detail.php';
            try {
                const resp = await fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fm });
                const text = await resp.text();
                let data;
                try { data = JSON.parse(text); } catch(e) { data = { success: resp.ok, message: text }; }
                if (resp.ok && data && data.success) {
                    participantModal.hide();
                    Swal.fire('Sukses', data.message || 'Tersimpan', 'success').then(()=> location.reload());
                } else {
                    Swal.fire('Error', data?.message || ('HTTP ' + resp.status), 'error');
                }
            } catch(err){
                console.error('save request failed', err);
                Swal.fire('Error', 'Request gagal. Lihat console (F12).', 'error');
            }
        });
    })();
    </script>
</body>
</html>
