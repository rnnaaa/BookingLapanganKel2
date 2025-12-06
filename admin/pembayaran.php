<?php
// admin/pembayaran.php

// 1. Panggil auth_check paling atas
require_once 'auth_check.php'; 

// 2. Bersihkan error session lama jika ada
if (isset($_SESSION['error']) && (stripos($_SESSION['error'], 'habis') !== false || stripos($_SESSION['error'], 'login') !== false)) {
    unset($_SESSION['error']);
}

require_once __DIR__ . '/../config/database.php';

// =================================================================================
// LOGIKA FILTER & DATE RANGE
// =================================================================================
$filter_start  = $_GET['start_date'] ?? date('Y-m-01'); 
$filter_end    = $_GET['end_date']   ?? date('Y-m-t');  
$filter_status = $_GET['status']     ?? 'all';          

$whereClause = "WHERE p.created_at BETWEEN '$filter_start 00:00:00' AND '$filter_end 23:59:59'";

if ($filter_status !== 'all') {
    $whereClause .= " AND p.status_verifikasi = '$filter_status'";
}

// =================================================================================
// HITUNG STATISTIK DASHBOARD
// =================================================================================
$whereStats = "WHERE p.created_at BETWEEN '$filter_start 00:00:00' AND '$filter_end 23:59:59'";

// A. Total Uang Masuk (Valid)
$qMasuk = mysqli_query($conn, "SELECT SUM(amount) as total FROM pembayaran p $whereStats AND p.status_verifikasi = 'valid'");
$totalMasuk = mysqli_fetch_assoc($qMasuk)['total'] ?? 0;

// B. Menunggu Verifikasi
$qPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pembayaran p $whereStats AND p.status_verifikasi = 'menunggu'");
$totalPending = mysqli_fetch_assoc($qPending)['total'] ?? 0;

// C. Total Transaksi
$qCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM pembayaran p $whereStats");
$totalTransaksi = mysqli_fetch_assoc($qCount)['total'] ?? 0;

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Custom Professional Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
    --border-radius: 12px;
}

.content-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

/* Stats Cards */
.stats-card {
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.stats-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.filter-card:hover {
    box-shadow: var(--shadow-md);
}

.form-control:focus,
.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Main Table Card */
.table-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    border: none;
}

.table-card-header {
    background: var(--primary-gradient);
    padding: 1.5rem;
    border: none;
}

/* Modern Table */
#tblPembayaran {
    font-size: 0.875rem;
    margin: 0;
}

#tblPembayaran thead th {
    background: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem 0.75rem;
    position: sticky;
    top: 0;
    z-index: 10;
}

#tblPembayaran tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3e6f0;
}

#tblPembayaran tbody tr:hover {
    background: #f8f9fc;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

#tblPembayaran tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

/* Badge Styles */
.badge {
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    font-size: 0.75rem;
    letter-spacing: 0.3px;
}

.badge.bg-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
}

/* Thumbnail Styles */
.proof-thumbnail {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

.proof-thumbnail:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-md);
}

.proof-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.proof-overlay {
    position: absolute;
    top: 0;
    right: 0;
    background: rgba(102, 126, 234, 0.9);
    width: 20px;
    height: 20px;
    border-radius: 0 8px 0 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
}

/* Button Styles */
.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    padding: 0.5rem 1rem;
}

.btn-primary {
    background: var(--primary-gradient);
}

.btn-success {
    background: var(--success-gradient);
}

.btn-warning {
    background: var(--warning-gradient);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
}

/* Modal Styles */
.modal-content {
    border-radius: var(--border-radius);
    border: none;
    overflow: hidden;
}

.modal-header {
    background: var(--primary-gradient);
    border: none;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,0.05);
}

.total-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    color: white;
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: var(--shadow-sm);
    animation: slideInDown 0.5s ease;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .stats-card {
        margin-bottom: 1rem;
    }
    
    #tblPembayaran {
        font-size: 0.75rem;
    }
    
    #tblPembayaran thead th,
    #tblPembayaran tbody td {
        padding: 0.5rem 0.25rem;
    }
}

/* Loading Animation */
.loader {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="content-wrapper">
  
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-3 mb-md-0">
          <h1 class="mb-2" style="font-weight: 700; color: #2d3748;">
            <i class="fas fa-wallet me-2" style="color: #667eea;"></i> 
            Data Pembayaran
          </h1>
          <p class="text-muted mb-0 d-flex align-items-center">
            <i class="far fa-calendar-alt me-2"></i>
            <span class="fw-semibold"><?= date('d M Y', strtotime($filter_start)) ?></span>
            <span class="mx-2">—</span>
            <span class="fw-semibold"><?= date('d M Y', strtotime($filter_end)) ?></span>
          </p>
        </div>
        <a href="pembayaran.php" class="btn btn-outline-primary">
          <i class="fas fa-sync-alt me-1"></i> Refresh Data
        </a>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i> 
          <strong>Berhasil!</strong> <?= $_SESSION['success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>Gagal!</strong> <?= $_SESSION['error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-money-check-alt fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Total Terverifikasi</p>
                  <h2 class="mb-0 fw-bold">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-chart-line me-1"></i>Periode Ini
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-hourglass-half fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Menunggu Verifikasi</p>
                  <h2 class="mb-0 fw-bold"><?= $totalPending ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-clock me-1"></i>Transaksi Pending
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-receipt fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Total Transaksi</p>
                  <h2 class="mb-0 fw-bold"><?= $totalTransaksi ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-list me-1"></i>Semua Status
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Card -->
      <div class="card filter-card mb-4">
        <div class="card-body p-4">
          <form method="GET" action="pembayaran.php" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="far fa-calendar-alt me-1"></i> Dari Tanggal
              </label>
              <input type="date" name="start_date" class="form-control" value="<?= $filter_start ?>" required>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="far fa-calendar-check me-1"></i> Sampai Tanggal
              </label>
              <input type="date" name="end_date" class="form-control" value="<?= $filter_end ?>" required>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="fas fa-filter me-1"></i> Status Verifikasi
              </label>
              <select name="status" class="form-select">
                <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                <option value="menunggu" <?= $filter_status == 'menunggu' ? 'selected' : '' ?>>⏳ Menunggu</option>
                <option value="valid" <?= $filter_status == 'valid' ? 'selected' : '' ?>>✓ Valid</option>
                <option value="tidak_valid" <?= $filter_status == 'tidak_valid' ? 'selected' : '' ?>>✗ Tidak Valid</option>
              </select>
            </div>
            <div class="col-lg-3 col-md-6">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-2"></i> Terapkan Filter
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Main Table -->
      <div class="card table-card">
        <div class="table-card-header">
          <h3 class="card-title mb-0 text-white fw-bold">
            <i class="fas fa-list-alt me-2"></i> Rincian Pembayaran
          </h3>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblPembayaran" class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th style="width: 3%">No</th>
                  <th style="width: 12%">Pengguna</th>
                  <th style="width: 10%">Lapangan</th>
                  <th style="width: 8%">Tgl Bayar</th>
                  <th style="width: 5%">Tipe</th>
                  <th style="width: 5%">Jenis</th>
                  <th style="width: 10%">Nominal</th>
                  <th style="width: 8%">Metode</th>
                  <th style="width: 8%">Bukti</th>
                  <th style="width: 10%">Status</th>
                  <th style="width: 10%">Verified By</th>
                  <th style="width: 8%">Aksi</th>
                </tr>
              </thead>
              <tbody>
<?php
$no = 1;
$table_rows = [];
$modal_details = [];
$rendered_modals = [];

// QUERY UTAMA DENGAN FILTER
$sql = "
  SELECT 
    p.*, 
    b.id_booking,
    b.tanggal,
    b.tipe_booking,
    b.status AS booking_status,
    b.payment_status,
    b.remaining_amount,
    b.total_amount,
    b.info_produk, 
    u.nama AS nama_user,
    l.nama_lapangan,
    admin.nama AS verified_by_name
  FROM pembayaran p
  JOIN booking b ON p.booking_id = b.id_booking
  LEFT JOIN users u ON b.id_user = u.id_user
  LEFT JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  LEFT JOIN users admin ON p.verified_by = admin.id_user
  $whereClause
  ORDER BY 
      CASE 
        WHEN p.status_verifikasi = 'menunggu' THEN 1
        WHEN p.status_verifikasi = 'tidak_valid' THEN 2
        WHEN p.status_verifikasi = 'valid' THEN 3
      END ASC,
      p.created_at DESC,
      p.id_pembayaran DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo '</tbody></table><div class="alert alert-danger m-3">Error Database: '.mysqli_error($conn).'</div><table class="d-none"><tbody>';
} elseif (mysqli_num_rows($result) > 0) {
  while ($r = mysqli_fetch_assoc($result)):
    $status = strtolower($r['status_verifikasi']);
    
    // Badge Status Verifikasi
    $badge_class = 'bg-warning'; $icon = 'fa-hourglass-half'; $label = 'Menunggu';
    if ($status === 'valid') {
      $badge_class = 'bg-success'; $icon = 'fa-check-circle'; $label = 'Valid';
      if (strtolower($r['tipe']) === 'dp' && $r['payment_status'] === 'dp_bayar' && floatval($r['remaining_amount']) > 0) {
        $badge_class = 'bg-info'; $icon = 'fa-clock'; $label = 'DP (Sisa)';
      }
    } elseif ($status === 'tidak_valid') {
      $badge_class = 'bg-danger'; $icon = 'fa-times-circle'; $label = 'Invalid';
    }

    $metode = $r['method'] ? htmlspecialchars(ucfirst(str_replace('_', ' ', $r['method']))) : '<em class="text-muted">-</em>';
    $nominal = '<span class="fw-bold text-dark">Rp ' . number_format($r['amount'], 0, ',', '.') . '</span>';

    // LOGIKA BUKTI (THUMBNAIL)
    $bukti_html = '<span class="text-muted small">No File</span>';
    if (!empty($r['bukti_pembayaran'])) {
      $filename = basename($r['bukti_pembayaran']);
      $path_check = [
          'nested' => __DIR__ . '/../uploads/bukti_pembayaran/' . $filename,
          'root'   => __DIR__ . '/../uploads/' . $filename
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
        $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
        if ($is_image) {
           $bukti_html = '
             <div class="proof-thumbnail mx-auto" onclick="showImagePreview(\'' . htmlspecialchars($web_path) . '\')">
                <img src="' . htmlspecialchars($web_path) . '" alt="Bukti">
                <div class="proof-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
             </div>';
        } else {
           $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i></a>';
        }
      } else {
        $bukti_html = '<span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Hilang</span>';
      }
    }

    $verified_by = $r['verified_by_name']
      ? '<div class="fw-semibold" style="font-size: 0.8rem;">' . htmlspecialchars($r['verified_by_name']) . '</div><div class="text-muted" style="font-size:0.7rem;">' . ($r['verified_at'] ? date('d/m H:i', strtotime($r['verified_at'])) : '') . '</div>'
      : '<span class="text-muted small">-</span>';

    $booking_id = intval($r['id_booking']);
    $user_display = $r['nama_user'] ? '<div class="fw-semibold">' . htmlspecialchars($r['nama_user']) . '</div>' : '<em class="text-muted">Walk-in</em>';

    // BUTTON ACTIONS
    $btnDetail = '
    <button class="btn btn-sm btn-primary w-100 mb-1" 
            data-bs-toggle="modal" data-bs-target="#modalDetail' . $booking_id . '">
        <i class="fas fa-eye me-1"></i> Detail
    </button>';
    
    $aksi_content = '';
    
    if ($status === 'menunggu') {
      $linkValid = "pembayaran_validasi.php?id=" . intval($r['id_pembayaran']) . "&aksi=valid";
      $linkTolak = "pembayaran_validasi.php?id=" . intval($r['id_pembayaran']) . "&aksi=tolak";

      $aksi_content = '
        <div class="d-flex gap-1">
            <a href="javascript:void(0);" 
               onclick="konfirmasiValid(\'' . $linkValid . '\')"
               class="btn btn-success btn-sm flex-fill" title="Validasi">
              <i class="fas fa-check"></i>
            </a>
            <a href="javascript:void(0);" 
               onclick="konfirmasiTolak(\'' . $linkTolak . '\')"
               class="btn btn-danger btn-sm flex-fill" title="Tolak">
              <i class="fas fa-times"></i>
            </a>
        </div>';
    } else {
      $remaining = floatval($r['remaining_amount']);
      if ($remaining > 0 && $r['payment_status'] !== 'lunas' && $status === 'valid' && ($_SESSION['role'] ?? '') === 'admin') {
        $linkLunas = "pembayaran_validasi.php?aksi=pelunasan&booking_id=" . $booking_id;
        
        $aksi_content = '
          <a href="javascript:void(0);" 
             onclick="konfirmasiValid(\'' . $linkLunas . '\')"
             class="btn btn-warning btn-sm w-100">
            <i class="fas fa-money-bill-wave me-1"></i> Lunas
          </a>';
      }
    }
    $aksi_html = '<div class="d-flex flex-column">' . $btnDetail . $aksi_content . '</div>';
    
    // Render Baris
    $table_rows[] = '
              <tr>
                <td class="text-center fw-semibold">' . $no++ . '</td>
                <td>' . $user_display . '</td>
                <td class="fw-semibold text-primary">' . ($r['nama_lapangan'] ? htmlspecialchars($r['nama_lapangan']) : '-') . '</td>
                <td class="text-center small">' . ($r['created_at'] ? date('d/m/y H:i', strtotime($r['created_at'])) : '-') . '</td>
                <td class="text-center">
                  <span class="badge ' . ($r['tipe_booking'] == 'member' ? 'bg-success' : 'bg-secondary') . '">' . ($r['tipe_booking'] ? ucfirst($r['tipe_booking']) : '-') . '</span>
                </td>
                <td class="text-center">
                  <span class="badge ' . (strtolower($r['tipe']) == 'dp' ? 'bg-info' : 'bg-primary') . '">' . ucfirst($r['tipe']) . '</span>
                </td>
                <td class="text-end">' . $nominal . '</td>
                <td class="text-center small">' . $metode . '</td>
                <td class="text-center">' . $bukti_html . '</td>
                <td class="text-center">
                  <span class="badge ' . $badge_class . '"><i class="fas ' . $icon . ' me-1"></i>' . $label . '</span>
                </td>
                <td class="text-center">' . $verified_by . '</td>
                <td class="text-center">' . $aksi_html . '</td>
              </tr>';

    // Data Modal
    if (!in_array($booking_id, $rendered_modals, true)) {
        $rendered_modals[] = $booking_id;
        $modal_details[$booking_id] = [
            'user_display' => $r['nama_user'] ? htmlspecialchars($r['nama_user']) : 'Walk-in',
            'nama_lapangan' => $r['nama_lapangan'] ? htmlspecialchars($r['nama_lapangan']) : '-',
            'total_amount' => $r['total_amount'] ?? 0,
            'remaining_amount' => $r['remaining_amount'] ?? 0,
            'booking_status' => htmlspecialchars(ucfirst($r['booking_status'])),
            'payment_status' => htmlspecialchars(str_replace('_',' ',ucfirst($r['payment_status']))),
            'info_produk' => $r['info_produk'] ? htmlspecialchars($r['info_produk']) : '<span class="text-muted fst-italic">Tidak ada info produk</span>',
            'booking_id' => $booking_id,
        ];
    }
  endwhile;
} else {
    $table_rows[] = '<tr><td colspan="12" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Tidak ada data pembayaran untuk periode ini</td></tr>';
}

echo implode('', $table_rows);
?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php
// Modal Detail
foreach ($modal_details as $booking_id => $data):
    $stmtPayments = mysqli_prepare($conn, "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtPayments, "i", $booking_id);
    mysqli_stmt_execute($stmtPayments);
    $resPayments = mysqli_stmt_get_result($stmtPayments);
    
    $status_color = match(strtolower($data['booking_status'])) {
        'disetujui', 'selesai' => 'success',
        'menunggu' => 'warning',
        'batal', 'ditolak' => 'danger',
        default => 'secondary'
    };
?>
  <div class="modal fade" id="modalDetail<?= $booking_id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-white fw-bold">
            <i class="fas fa-file-invoice-dollar me-2"></i> 
            Rincian Transaksi #<?= $booking_id ?>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background: #f8f9fc;">
          <div class="row g-3">
            <div class="col-md-7">
              <div class="info-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                  <i class="fas fa-info-circle me-2 text-primary"></i> 
                  Informasi Booking
                </h6>
                <table class="table table-sm table-borderless mb-0">
                  <tr>
                    <td width="35%" class="text-muted">
                      <i class="fas fa-user me-2"></i>Nama
                    </td>
                    <td class="fw-semibold"><?= $data['user_display'] ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">
                      <i class="fas fa-futbol me-2"></i>Lapangan
                    </td>
                    <td class="fw-semibold text-primary"><?= $data['nama_lapangan'] ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">
                      <i class="fas fa-box me-2"></i>Produk
                    </td>
                    <td><?= $data['info_produk'] ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">
                      <i class="fas fa-flag me-2"></i>Status
                    </td>
                    <td>
                      <span class="badge bg-<?= $status_color ?>">
                        <?= $data['booking_status'] ?>
                      </span>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="col-md-5">
              <div class="total-card">
                <p class="mb-2 opacity-90" style="font-size: 0.85rem; font-weight: 500;">TOTAL TAGIHAN</p>
                <h2 class="fw-bold mb-3">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></h2>
                <?php if ($data['remaining_amount'] > 0): ?>
                  <div class="p-3 rounded" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <small class="d-block mb-1 opacity-90">Sisa Pembayaran</small>
                    <div class="fw-bold fs-5">Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></div>
                  </div>
                <?php else: ?>
                  <div class="p-3 rounded" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>LUNAS</strong>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="card mt-4 border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
              <h6 class="mb-0 fw-bold text-primary">
                <i class="fas fa-history me-2"></i> Riwayat Pembayaran
              </h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead style="background: #f8f9fc;">
                    <tr>
                      <th width="5%">#</th>
                      <th>Tanggal</th>
                      <th>Tipe</th>
                      <th class="text-end">Nominal</th>
                      <th class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i=1; 
                    if (mysqli_num_rows($resPayments) > 0):
                        mysqli_data_seek($resPayments, 0);
                        while ($pay = mysqli_fetch_assoc($resPayments)): 
                          $st_cls = match($pay['status_verifikasi']) { 
                            'valid'=>'success', 
                            'menunggu'=>'warning', 
                            'tidak_valid'=>'danger', 
                            default=>'secondary' 
                          };
                    ?>
                      <tr>
                        <td class="fw-semibold"><?= $i++ ?></td>
                        <td>
                          <div class="fw-semibold" style="font-size: 0.875rem;">
                            <?= date('d M Y', strtotime($pay['created_at'])) ?>
                          </div>
                          <small class="text-muted"><?= date('H:i', strtotime($pay['created_at'])) ?> WIB</small>
                        </td>
                        <td>
                          <span class="badge bg-<?= strtolower($pay['tipe']) == 'dp' ? 'info' : 'primary' ?>">
                            <?= htmlspecialchars($pay['tipe']) ?>
                          </span>
                        </td>
                        <td class="text-end fw-bold">Rp <?= number_format($pay['amount'],0,',','.') ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= $st_cls ?>">
                            <?= ucfirst($pay['status_verifikasi']) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endwhile; else: ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Belum ada riwayat pembayaran
                          </td>
                        </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #f8f9fc;">
          <button class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
<?php mysqli_stmt_close($stmtPayments); endforeach; ?>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0 shadow-lg">
      <div class="modal-header border-0 p-2">
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-0">
        <img id="previewImage" src="" class="img-fluid rounded" alt="Bukti Pembayaran" style="max-height: 85vh;">
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
  // Auto hide alerts
  setTimeout(() => $('.alert').fadeOut('slow'), 5000);

  // Image preview
  function showImagePreview(imageSrc) {
    document.getElementById('previewImage').src = imageSrc;
    new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
  }

  // SweetAlert2 Confirmations
  function konfirmasiValid(url) {
      Swal.fire({
          title: 'Validasi Pembayaran?',
          html: 'Pastikan dana sudah masuk.<br><b>Saldo akan dicatat ke keuangan.</b>',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#11998e',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fas fa-check me-2"></i>Ya, Valid!',
          cancelButtonText: 'Batal',
          customClass: {
              popup: 'border-0 shadow-lg',
              confirmButton: 'btn-lg',
              cancelButton: 'btn-lg'
          }
      }).then((result) => {
          if (result.isConfirmed) window.location.href = url;
      });
  }

  function konfirmasiTolak(url) {
      Swal.fire({
          title: 'Tolak Pembayaran?',
          html: 'Status booking akan otomatis berubah menjadi <b>DITOLAK</b>.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fas fa-times me-2"></i>Ya, Tolak!',
          cancelButtonText: 'Batal',
          customClass: {
              popup: 'border-0 shadow-lg',
              confirmButton: 'btn-lg',
              cancelButton: 'btn-lg'
          }
      }).then((result) => {
          if (result.isConfirmed) window.location.href = url;
      });
  }

  // Display session messages
  <?php if (isset($_SESSION['success'])): ?>
      Swal.fire({
          title: 'Berhasil!',
          html: `<?= $_SESSION['success']; ?>`,
          icon: 'success',
          timer: 2500,
          showConfirmButton: false,
          customClass: { popup: 'border-0 shadow-lg' }
      });
      <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
      Swal.fire({
          title: 'Gagal!',
          text: `<?= $_SESSION['error']; ?>`,
          icon: 'error',
          customClass: { popup: 'border-0 shadow-lg' }
      });
      <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
</script>