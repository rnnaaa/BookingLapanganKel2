<?php
// saran_fetch_detail.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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
    
    // Mapping bulan Indonesia
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $tanggal = date('d', strtotime($saran['created_at']));
    $nama_bulan = $bulan[(int)date('m', strtotime($saran['created_at']))];
    $tahun = date('Y', strtotime($saran['created_at']));
    $waktu = date('H:i:s', strtotime($saran['created_at']));
    
?>

<style>
.detail-header-section {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.05) 0%, rgba(33, 150, 243, 0.05) 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(14, 92, 145, 0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.info-card:hover {
    box-shadow: 0 4px 15px rgba(14, 92, 145, 0.15);
    transform: translateY(-3px);
}

.info-card-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 15px;
    box-shadow: 0 4px 12px rgba(14, 92, 145, 0.3);
}

.info-card-label {
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-card-value {
    color: #0e5c91;
    font-size: 1.1rem;
    font-weight: 700;
}

.info-card-sub {
    color: #868e96;
    font-size: 0.9rem;
    margin-top: 5px;
}

.badge-anonim-detail {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.badge-anonim-yes {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
}

.badge-anonim-no {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.rating-display {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
    border-radius: 12px;
    padding: 15px;
    text-align: center;
}

.rating-stars-large {
    color: #ffc107;
    font-size: 1.5rem;
    letter-spacing: 5px;
}

.rating-number {
    color: #ff9800;
    font-size: 1.8rem;
    font-weight: 700;
}

.message-card {
    background: white;
    border-radius: 15px;
    border-left: 5px solid #2196f3;
    box-shadow: 0 4px 20px rgba(14, 92, 145, 0.1);
    overflow: hidden;
}

.message-card-header {
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
}

.message-card-body {
    padding: 25px;
}

.message-text {
    white-space: pre-wrap;
    font-size: 1.05rem;
    line-height: 1.8;
    color: #495057;
}

.divider-gradient {
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #2196f3 50%, transparent 100%);
    margin: 25px 0;
}
</style>

<!-- Header Section -->
<div class="detail-header-section">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                <div class="info-card-icon me-3">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <div class="info-card-label">Tanggal & Waktu Pengiriman</div>
                    <div class="info-card-value">
                        <?= $tanggal ?> <?= $nama_bulan ?> <?= $tahun ?>
                    </div>
                    <div class="info-card-sub">
                        <i class="fas fa-clock me-1"></i> Pukul <?= $waktu ?> WIB
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if ($is_anonim): ?>
                <span class="badge badge-anonim-detail badge-anonim-yes">
                    <i class="fas fa-mask me-2"></i> Pengirim Anonim
                </span>
            <?php else: ?>
                <span class="badge badge-anonim-detail badge-anonim-no">
                    <i class="fas fa-user-check me-2"></i> Pengirim Teridentifikasi
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Info Cards Row -->
<div class="row g-4 mb-4">
    <!-- Card Pengirim -->
    <div class="col-md-6">
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="info-card-label">Nama Pengirim</div>
            <div class="info-card-value"><?= $nama_tampil ?></div>
            <div class="info-card-sub">
                <i class="fas fa-envelope me-1"></i> <?= $email_tampil ?>
            </div>
        </div>
    </div>

    <!-- Card Kategori -->
    <div class="col-md-3">
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-stream"></i>
            </div>
            <div class="info-card-label">Kategori Feedback</div>
            <div class="info-card-value"><?= htmlspecialchars($saran['kategori']) ?></div>
        </div>
    </div>

    <!-- Card Rating -->
    <div class="col-md-3">
        <div class="info-card">
            <div class="rating-display">
                <div class="info-card-label mb-2">Rating Kepuasan</div>
                <div class="rating-stars-large mb-2">
                    <?php for ($i = 0; $i < $saran['rating']; $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                    <?php for ($i = $saran['rating']; $i < 5; $i++): ?>
                        <i class="far fa-star"></i>
                    <?php endfor; ?>
                </div>
                <div class="rating-number"><?= $saran['rating'] ?><span style="font-size: 1.2rem; color: #ff9800;">/5</span></div>
            </div>
        </div>
    </div>
</div>

<div class="divider-gradient"></div>

<!-- Message Card -->
<div class="message-card">
    <div class="message-card-header">
        <i class="fas fa-comment-dots me-2"></i> Isi Pesan / Saran Lengkap
    </div>
    <div class="message-card-body">
        <div class="message-text"><?= htmlspecialchars($saran['pesan']) ?></div>
    </div>
</div>

<!-- Footer Info -->
<div class="text-center mt-4 pt-3 border-top">
    <small class="text-muted">
        <i class="fas fa-info-circle me-1"></i> 
        Data ini disimpan secara aman dan digunakan untuk meningkatkan layanan kami
    </small>
</div>

<?php
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Kesalahan Server: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
}
?>