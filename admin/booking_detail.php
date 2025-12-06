<?php
// booking_detail.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');

// Cek ID booking
if (!isset($_GET['id'])) {
    $_SESSION['toast_error'] = "ID booking tidak ditemukan.";
    echo "<script>window.location='booking.php';</script>";
    exit;
}

$id_booking = intval($_GET['id']);

// Ambil data booking
$sql = "
    SELECT 
      b.*, 
      u.nama AS nama_user, 
      u.role,
      u.email, 
      u.no_hp,
      l.nama_lapangan, 
      l.harga_per_jam,
      l.harga_per_jam_member,
      admin.nama AS approved_by_name
    FROM booking b
    JOIN users u ON b.id_user = u.id_user
    JOIN lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN users admin ON b.approved_by = admin.id_user
    WHERE b.id_booking = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    $_SESSION['toast_error'] = "Data booking tidak ditemukan.";
    echo "<script>window.location='booking.php';</script>";
    exit;
}

// Ambil jadwal main
$stmt = $conn->prepare("
    SELECT 
        jw.jam_mulai, 
        jw.jam_selesai,
        db.harga
    FROM detail_booking db
    JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
    WHERE db.id_booking = ?
    ORDER BY jw.jam_mulai ASC
");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$jadwal = $stmt->get_result();
$stmt->close();

$total_harga_lapangan = 0;
$jadwal_items = [];
while ($row = $jadwal->fetch_assoc()) {
    $total_harga_lapangan += floatval($row['harga']);
    $jadwal_items[] = $row;
}

$biaya_tambahan = floatval($data['total_amount']) - $total_harga_lapangan;

// Ambil data pembayaran
$stmt = $conn->prepare("
    SELECT 
        p.*,
        verifier.nama AS verified_by_name
    FROM pembayaran p
    LEFT JOIN users verifier ON p.verified_by = verifier.id_user
    WHERE p.booking_id = ?
    ORDER BY p.created_at ASC
");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$pembayaran = $stmt->get_result();
$stmt->close();

$is_walkin = ($data['tipe_booking'] === 'manual');
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.detail-card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 1.5rem;
}

.card-header-gradient {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 1.5rem;
}

.info-card {
    border-radius: 10px;
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}

.status-badge-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-radius: 8px;
    font-weight: 600;
}

.slot-badge {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-box {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.summary-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.summary-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-top: 0.5rem;
}

.table-financial {
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

.table-financial thead th {
    background: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    border-bottom: 2px solid #e3e6f0;
}

.table-financial tbody tr {
    transition: all 0.2s ease;
}

.table-financial tbody tr:hover {
    background: #f8f9fc;
}

.proof-btn {
    transition: all 0.2s ease;
}

.proof-btn:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .summary-value {
        font-size: 1.3rem;
    }
}
</style>

<div class="content-wrapper">
    <section class="content-header mb-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-3 mb-md-0">
                    <h1 style="font-weight: 700; color: #2d3748;">
                        <i class="fas fa-info-circle me-2" style="color: #667eea;"></i>
                        Detail Booking #<?= $id_booking ?>
                    </h1>
                </div>
                <a href="booking.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!empty($_SESSION['toast_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['toast_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['toast_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['toast_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['toast_success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['toast_success']); ?>
            <?php endif; ?>

            <!-- Main Info Card -->
            <div class="card detail-card">
                <div class="card-header card-header-gradient text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i> Informasi Booking
                        </h4>
                        <div class="btn-group">
                            <?php if ($data['status'] !== 'selesai' && $data['status'] !== 'dibatalkan'): ?>
                                <a href="booking_edit.php?id=<?= $id_booking ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            <a href="invoice_booking.php?id=<?= $id_booking ?>" class="btn btn-light btn-sm" target="_blank">
                                <i class="fas fa-print"></i> Cetak
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">

                    <!-- Status Badges -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="row text-center g-3">
                                        <div class="col-md-4">
                                            <div class="summary-label mb-2">Tipe Booking</div>
                                            <?php if ($data['tipe_booking'] === 'member'): ?>
                                                <span class="badge bg-success status-badge-lg">
                                                    <i class="fas fa-crown me-1"></i> Member
                                                </span>
                                            <?php elseif ($data['tipe_booking'] === 'manual'): ?>
                                                <span class="badge bg-info status-badge-lg">
                                                    <i class="fas fa-walking me-1"></i> Walk-In
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary status-badge-lg">
                                                    <i class="fas fa-user me-1"></i> Reguler
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="summary-label mb-2">Status Booking</div>
                                            <?php
                                            $statusClass = [
                                                'menunggu' => 'bg-warning',
                                                'disetujui' => 'bg-primary',
                                                'selesai' => 'bg-success',
                                                'ditolak' => 'bg-danger',
                                                'dibatalkan' => 'bg-secondary',
                                                'hold' => 'bg-dark'
                                            ];
                                            $statusIcon = [
                                                'menunggu' => 'clock',
                                                'disetujui' => 'check-circle',
                                                'selesai' => 'check-double',
                                                'ditolak' => 'times-circle',
                                                'dibatalkan' => 'ban',
                                                'hold' => 'pause'
                                            ];
                                            ?>
                                            <span class="badge <?= $statusClass[$data['status']] ?? 'bg-secondary' ?> status-badge-lg">
                                                <i class="fas fa-<?= $statusIcon[$data['status']] ?? 'question' ?> me-1"></i>
                                                <?= ucfirst($data['status']) ?>
                                            </span>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="summary-label mb-2">Status Pembayaran</div>
                                            <?php
                                            $payClass = [
                                                'belum_bayar' => 'bg-secondary',
                                                'menunggu_verifikasi' => 'bg-warning',
                                                'dp_bayar' => 'bg-info',
                                                'lunas' => 'bg-success',
                                                'dibatalkan' => 'bg-danger'
                                            ];
                                            $payIcon = [
                                                'belum_bayar' => 'wallet',
                                                'menunggu_verifikasi' => 'hourglass-half',
                                                'dp_bayar' => 'coins',
                                                'lunas' => 'check-circle',
                                                'dibatalkan' => 'times'
                                            ];
                                            ?>
                                            <span class="badge <?= $payClass[$data['payment_status']] ?? 'bg-light' ?> status-badge-lg">
                                                <i class="fas fa-<?= $payIcon[$data['payment_status']] ?? 'question' ?> me-1"></i>
                                                <?= ucfirst(str_replace('_', ' ', $data['payment_status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Field Info -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card info-card border-0 shadow-sm h-100">
                                <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #667eea, #764ba2);">
                                    <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i> Data Pemesan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%"><i class="fas fa-user text-primary me-2"></i> Nama</th>
                                            <td><strong><?= htmlspecialchars($data['nama_user']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-envelope text-primary me-2"></i> Email</th>
                                            <td><?= htmlspecialchars($data['email']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-phone text-primary me-2"></i> No HP</th>
                                            <td><?= htmlspecialchars($data['no_hp'] ?: '-') ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-id-badge text-primary me-2"></i> Role</th>
                                            <td><span class="badge bg-primary"><?= ucfirst($data['role']) ?></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card info-card border-0 shadow-sm h-100">
                                <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #11998e, #38ef7d);">
                                    <h6 class="mb-0"><i class="fas fa-basketball-ball me-2"></i> Data Lapangan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%"><i class="fas fa-map-marker-alt text-success me-2"></i> Lapangan</th>
                                            <td><strong><?= htmlspecialchars($data['nama_lapangan']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-calendar-alt text-success me-2"></i> Tanggal</th>
                                            <td><strong><?= date('d F Y', strtotime($data['tanggal'])) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-tag text-success me-2"></i> Harga/Jam</th>
                                            <td>Rp <?= number_format($data['harga_per_jam'], 0, ',', '.') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($data['info_produk'])): ?>
                                <div class="card info-card border-0 shadow-sm mt-3">
                                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #4facfe, #00f2fe);">
                                        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i> Tambahan Produk</h6>
                                    </div>
                                    <div class="card-body bg-light">
                                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($data['info_produk'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Time Slots -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="far fa-clock me-2 text-primary"></i> Slot Waktu Booking
                        </h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($jadwal_items as $j): ?>
                                <span class="badge bg-gradient slot-badge" 
                                      style="background: linear-gradient(90deg, #667eea, #764ba2);" 
                                      title="Harga: Rp <?= number_format($j['harga'], 0, ',', '.') ?>">
                                    <i class="far fa-clock me-1"></i>
                                    <?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Financial Details -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #f093fb, #f5576c);">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Rincian Keuangan</h5>
                        </div>
                        <div class="card-body">

                            <h6 class="mb-3"><i class="fas fa-list-alt me-2"></i> Detail Tagihan</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-financial mb-0">
                                    <tbody>
                                        <tr>
                                            <th width="50%">Total Harga Lapangan</th>
                                            <td class="text-end fw-bold">Rp <?= number_format($total_harga_lapangan, 0, ',', '.') ?></td>
                                        </tr>
                                        
                                        <?php if ($biaya_tambahan > 0 || !empty($data['info_produk'])): ?>
                                        <tr>
                                            <th>
                                                Total Tambahan (Produk/Minuman)
                                                <?php if (!empty($data['info_produk'])): ?>
                                                    <br><small class="text-muted fw-normal fst-italic">
                                                        <?= htmlspecialchars(substr($data['info_produk'], 0, 50)) ?>
                                                        <?= strlen($data['info_produk']) > 50 ? '...' : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </th>
                                            <td class="text-end fw-bold">Rp <?= number_format($biaya_tambahan, 0, ',', '.') ?></td>
                                        </tr>
                                        <?php endif; ?>

                                        <tr class="table-primary">
                                            <th class="fs-5">GRAND TOTAL TAGIHAN</th>
                                            <td class="text-end fw-bold fs-5 text-primary">
                                                Rp <?= number_format($data['total_amount'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Boxes -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="summary-box border border-primary">
                                        <div class="summary-label">Total Tagihan</div>
                                        <div class="summary-value text-primary">
                                            Rp <?= number_format($data['total_amount'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-box border border-info">
                                        <div class="summary-label">Sudah Dibayar (DP)</div>
                                        <div class="summary-value text-info">
                                            Rp <?= number_format($data['dp_amount'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-box border border-danger">
                                        <div class="summary-label">Sisa Kekurangan</div>
                                        <div class="summary-value text-danger">
                                            Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment History -->
                            <h6 class="mb-3"><i class="fas fa-history me-2"></i> Riwayat Pembayaran</h6>
                            <div class="table-responsive">
                                <table class="table table-financial table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Tipe</th>
                                            <th>Nominal</th>
                                            <th>Metode</th>
                                            <th>Status</th>
                                            <th>Diverifikasi Oleh</th>
                                            <th width="10%">Bukti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $pembayaran->data_seek(0);
                                        $no = 1;
                                        if ($pembayaran->num_rows > 0):
                                            while ($p = $pembayaran->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-center fw-semibold"><?= $no++ ?></td>
                                                    <td>
                                                        <?php if ($p['tipe'] === 'DP'): ?>
                                                            <span class="badge bg-info">DP</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Pelunasan</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        Rp <?= number_format($p['amount'], 0, ',', '.') ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?= strtoupper($p['method']) ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($p['status_verifikasi'] == 'valid'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i> Valid
                                                            </span>
                                                        <?php elseif ($p['status_verifikasi'] == 'menunggu'): ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-clock me-1"></i> Menunggu
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i> Invalid
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="small">
                                                        <?php if ($p['verified_by']): ?>
                                                            <i class="fas fa-user-check text-success me-1"></i>
                                                            <?= $p['verified_by_name'] ?><br>
                                                            <small class="text-muted"><?= $p['verified_at'] ?></small>
                                                        <?php else: ?> - <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php
                                                        $bukti_html = '<span class="text-muted">-</span>';
                                                        if (!empty($p['bukti_pembayaran'])) {
                                                            $filename = basename($p['bukti_pembayaran']);
                                                            $path_check = [
                                                                'nested' => __DIR__ . '/../uploads/bukti_pembayaran/' . $filename,
                                                                'root' => __DIR__ . '/../uploads/' . $filename
                                                            ];
                                                            $web_path = '';
                                                            $found = false;
                                                            if (file_exists($path_check['nested'])) {
                                                                $web_path = '../uploads/bukti_pembayaran/' . rawurlencode($filename);
                                                                $found = true;
                                                            } elseif (file_exists($path_check['root'])) {
                                                                $web_path = '../uploads/' . rawurlencode($filename);
                                                                $found = true;
                                                            }
                                                            if ($found) {
                                                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                                                $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                                                                if ($is_image) {
                                                                    $bukti_html = '<button type="button" onclick="showImagePreview(\'' . htmlspecialchars($web_path) . '\')" class="btn btn-sm btn-outline-primary proof-btn"><i class="fas fa-eye"></i> Lihat</button>';
                                                                } else {
                                                                    $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Unduh</a>';
                                                                }
                                                            } else {
                                                                $bukti_html = '<span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Hilang</span>';
                                                            }
                                                        }
                                                        echo $bukti_html;
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                    Belum ada data pembayaran
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- System Log -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #4facfe, #00f2fe);">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Log Sistem</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%">
                                                <i class="far fa-clock text-info me-2"></i> Dibuat
                                            </th>
                                            <td><?= date('d F Y, H:i', strtotime($data['created_at'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <i class="fas fa-sync-alt text-info me-2"></i> Update
                                            </th>
                                            <td><?= date('d F Y, H:i', strtotime($data['updated_at'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <i class="fas fa-user-shield text-info me-2"></i> Disetujui
                                            </th>
                                            <td>
                                                <?php if($data['approved_by_name']): ?>
                                                    <span class="badge bg-success">
                                                        <?= $data['approved_by_name'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($data['alasan_penolakan'])): ?>
                            <div class="col-md-6">
                                <div class="alert alert-danger h-100 d-flex align-items-center">
                                    <div>
                                        <strong>
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            Alasan Penolakan:
                                        </strong><br>
                                        <?= nl2br(htmlspecialchars($data['alasan_penolakan'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="card-footer bg-light p-3">
                    <a href="booking.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(90deg, #667eea, #764ba2);">
                <h5 class="modal-title">
                    <i class="fas fa-image me-2"></i> Bukti Pembayaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 bg-dark">
                <img id="previewImage" src="" class="img-fluid" alt="Bukti Pembayaran" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
function showImagePreview(imageSrc) {
    const modalEl = document.getElementById('imagePreviewModal');
    const imageEl = document.getElementById('previewImage');
    imageEl.src = imageSrc;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>