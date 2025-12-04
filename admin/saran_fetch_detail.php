<?php
// saran_fetch_detail.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$id_saran = $_GET['id'] ?? null;

if (!is_numeric($id_saran)) {
    http_response_code(400);
    echo '<div class="alert alert-danger text-center">ID Saran tidak valid.</div>';
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM saran WHERE id_saran = ?");
    $stmt->bind_param("i", $id_saran);
    $stmt->execute();
    $result = $stmt->get_result();
    $saran = $result->fetch_assoc();
    $stmt->close();

    if (!$saran) {
        http_response_code(404);
        echo '<div class="alert alert-warning text-center">Saran tidak ditemukan.</div>';
        exit;
    }

    // Pemrosesan Data untuk Tampilan
    $is_anonim = $saran['is_anonim'] == 1;
    $nama_tampil = $is_anonim ? 'Anonim' : htmlspecialchars($saran['nama']);
    $email_tampil = $is_anonim ? 'Tidak ditampilkan' : htmlspecialchars($saran['email']);
    $anonim_badge = $is_anonim ? '<span class="badge bg-secondary"><i class="fas fa-mask"></i> Ya</span>' : '<span class="badge bg-success"><i class="fas fa-user"></i> Tidak</span>';
    
?>

<div class="row">
    <div class="col-md-6">
        <p class="mb-1 text-muted"><strong><i class="fas fa-calendar-alt"></i> Tanggal Kirim:</strong></p>
        <p class="fw-bold"><?= date('d F Y', strtotime($saran['created_at'])) ?> <small class="text-muted">(Pukul <?= date('H:i:s', strtotime($saran['created_at'])) ?>)</small></p>
    </div>
    <div class="col-md-6 text-md-end">
        <p class="mb-1 text-muted"><strong><i class="fas fa-tag"></i> Anonim:</strong></p>
        <p><?= $anonim_badge ?></p>
    </div>
</div>

<hr>

<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card bg-light shadow-sm p-3 h-100">
            <p class="mb-1 text-primary"><strong><i class="fas fa-user-circle"></i> Pengirim:</strong></p>
            <h5 class="mb-0 fw-bold"><?= $nama_tampil ?></h5>
            <small class="text-muted"><?= $email_tampil ?></small>
        </div>
    </div>
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card bg-light shadow-sm p-3 text-center h-100">
            <p class="mb-1 text-primary"><strong><i class="fas fa-stream"></i> Kategori:</strong></p>
            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($saran['kategori']) ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light shadow-sm p-3 text-center h-100">
            <p class="mb-1 text-primary"><strong><i class="fas fa-star"></i> Rating:</strong></p>
            <h5 class="mb-0 text-warning fw-bold">
                <?php for ($i = 0; $i < $saran['rating']; $i++): ?>
                    <i class="fas fa-star"></i>
                <?php endfor; ?>
                (<?= $saran['rating'] ?>/5)
            </h5>
        </div>
    </div>
</div>

<div class="card shadow border-0 border-left-lg" style="border-left: 5px solid #0e5c91 !important;">
    <div class="card-header bg-primary text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3) !important;">
        <h6 class="mb-0"><i class="fas fa-comment-dots"></i> Isi Pesan/Saran Lengkap</h6>
    </div>
    <div class="card-body">
        <p style="white-space: pre-wrap; font-size: 1.1rem; line-height: 1.6;"><?= htmlspecialchars($saran['pesan']) ?></p>
    </div>
</div>
<?php
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger text-center">Kesalahan Server: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>