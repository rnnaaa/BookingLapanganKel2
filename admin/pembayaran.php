<?php
// admin/pembayaran.php

// 1. Panggil auth_check paling atas (Ini akan memulai session dan cek login)
require_once 'auth_check.php'; 

// --- PERBAIKAN 1: HAPUS session_start() MANUAL ---
// if (session_status() === PHP_SESSION_NONE) { session_start(); } <--- HAPUS INI

// --- PERBAIKAN 2: "GHOST ERROR KILLER" ---
// Jika script sampai di baris ini, artinya User SUDAH LOGIN (lolos dari auth_check).
// Jadi, jika ada pesan "Sesi Habis" yang tersisa di session, itu adalah error lama (basi). Hapus saja.
if (isset($_SESSION['error']) && (stripos($_SESSION['error'], 'habis') !== false || stripos($_SESSION['error'], 'login') !== false)) {
    unset($_SESSION['error']);
}

require_once __DIR__ . '/../config/database.php';

// =================================================================================
// LOGIKA FILTER & DATE RANGE
// =================================================================================
$filter_start  = $_GET['start_date'] ?? date('Y-m-01'); // Default: Awal bulan ini
$filter_end    = $_GET['end_date']   ?? date('Y-m-t');  // Default: Akhir bulan ini
$filter_status = $_GET['status']     ?? 'all';          // Default: Semua status

// Buat klausa WHERE dasar untuk tanggal (p.created_at)
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

<div class="content-wrapper animate__animated animate__fadeIn">
  
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
          <h1><i class="fas fa-wallet me-2"></i> Data Pembayaran</h1>
          <p class="text-muted mb-0">Periode: <?= date('d M Y', strtotime($filter_start)) ?> s/d <?= date('d M Y', strtotime($filter_end)) ?></p>
      </div>
      <a href="pembayaran.php" class="btn btn-outline-secondary btn-sm shadow-sm">
         <i class="fas fa-sync-alt me-1"></i> Refresh
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="row g-3 mb-4">
          <div class="col-md-4 col-sm-6">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%); color: white;">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                          <i class="fas fa-money-check-alt fa-2x"></i>
                      </div>
                      <div>
                          <h6 class="mb-0 opacity-75">Total Terverifikasi</h6>
                          <h3 class="mb-0 fw-bold">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></h3>
                          <small class="opacity-75">Periode Ini</small>
                      </div>
                  </div>
              </div>
          </div>

          <div class="col-md-4 col-sm-6">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #ffc107 0%, #ffdb4d 100%); color: white;">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-light bg-opacity-10 rounded-circle p-3 me-3">
                          <i class="fas fa-hourglass-half fa-2x"></i>
                      </div>
                      <div>
                          <h6 class="mb-0 opacity-75">Menunggu Verifikasi</h6>
                          <h3 class="mb-0 fw-bold"><?= $totalPending ?></h3>
                          <small class="opacity-75">Transaksi</small>
                      </div>
                  </div>
              </div>
          </div>

          <div class="col-md-4 col-sm-12">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%); color: white;">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                          <i class="fas fa-receipt fa-2x"></i>
                      </div>
                      <div>
                          <h6 class="mb-0 opacity-75">Total Transaksi</h6>
                          <h3 class="mb-0 fw-bold"><?= $totalTransaksi ?></h3>
                          <small class="opacity-75">Semua Status</small>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="pembayaran.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $filter_start ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $filter_end ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Status Verifikasi</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="menunggu" <?= $filter_status == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="valid" <?= $filter_status == 'valid' ? 'selected' : '' ?>>Valid</option>
                        <option value="tidak_valid" <?= $filter_status == 'tidak_valid' ? 'selected' : '' ?>>Tidak Valid</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                </div>
            </form>
        </div>
      </div>

      <div class="card shadow-lg border-0">
        <div class="card-header text-white"
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);">
          <h3 class="card-title mb-0"><i class="fas fa-list me-2"></i> Rincian Pembayaran</h3>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblPembayaran" class="table table-bordered table-striped table-hover align-middle w-100 mb-0" style="font-size: 0.9rem;">
              <thead class="bg-light text-center text-nowrap">
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
    $badge_class = 'bg-warning text-dark'; $icon = 'fa-hourglass-half'; $label = 'Menunggu';
    if ($status === 'valid') {
      $badge_class = 'bg-success'; $icon = 'fa-check-circle'; $label = 'Valid';
      if (strtolower($r['tipe']) === 'dp' && $r['payment_status'] === 'dp_bayar' && floatval($r['remaining_amount']) > 0) {
        $badge_class = 'bg-info text-dark'; $icon = 'fa-clock'; $label = 'DP (Sisa)';
      }
    } elseif ($status === 'tidak_valid') {
      $badge_class = 'bg-danger'; $icon = 'fa-times-circle'; $label = 'Invalid';
    }

    $metode = $r['method'] ? htmlspecialchars(ucfirst(str_replace('_', ' ', $r['method']))) : '<em class="text-muted">-</em>';
    $nominal = '<span class="fw-bold">Rp ' . number_format($r['amount'], 0, ',', '.') . '</span>';

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
             <div class="position-relative" style="width: 50px; height: 50px; margin: 0 auto; cursor: pointer;" onclick="showImagePreview(\'' . htmlspecialchars($web_path) . '\')">
                <img src="' . htmlspecialchars($web_path) . '" class="img-thumbnail w-100 h-100 object-fit-cover shadow-sm" alt="Bukti">
                <div class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size: 0.4rem; padding: 0.2em 0.4em;">
                    <i class="fas fa-search"></i>
                </div>
             </div>';
        } else {
           $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-outline-secondary btn-xs"><i class="fas fa-file-download"></i> Unduh</a>';
        }
      } else {
        $bukti_html = '<span class="text-danger small" title="File fisik tidak ditemukan"><i class="fas fa-exclamation-triangle"></i> Hilang</span>';
      }
    }

    $verified_by = $r['verified_by_name']
      ? '<div class="small fw-bold">' . htmlspecialchars($r['verified_by_name']) . '</div><div class="text-muted" style="font-size:0.75rem;">' . ($r['verified_at'] ? date('d/m H:i', strtotime($r['verified_at'])) : '') . '</div>'
      : '<span class="text-muted small">-</span>';

    $booking_id = intval($r['id_booking']);
    $user_display = $r['nama_user'] ? '<div class="fw-bold">' . htmlspecialchars($r['nama_user']) . '</div>' : '<em class="text-muted">Walk-in</em>';

    // TOMBOL AKSI
    $btnDetail = '
    <button class="btn btn-sm text-white w-100 mb-1 shadow-sm" 
            style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"
            data-bs-toggle="modal" data-bs-target="#modalDetail' . $booking_id . '">
        <i class="fas fa-eye me-1"></i> Detail
    </button>';
    
    $aksi_content = '';
    if ($status === 'menunggu') {
      $aksi_content = '
        <div class="d-flex gap-1">
            <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=valid" 
               class="btn btn-success btn-sm flex-fill" onclick="return confirm(\'✅ Validasi pembayaran ini sebagai sah?\')">
              <i class="fas fa-check"></i>
            </a>
            <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=tolak" 
               class="btn btn-danger btn-sm flex-fill" onclick="return confirm(\'❌ Tolak pembayaran ini?\')">
              <i class="fas fa-times"></i>
            </a>
        </div>';
    } else {
      $remaining = floatval($r['remaining_amount']);
      if ($remaining > 0 && $r['payment_status'] !== 'lunas' && $status === 'valid' && ($_SESSION['role'] ?? '') === 'admin') {
        $aksi_content = '
          <a href="pembayaran_validasi.php?aksi=pelunasan&booking_id=' . $booking_id . '" 
             class="btn btn-warning btn-sm w-100 text-dark"
             onclick="return confirm(\'Proses pelunasan booking #' . $booking_id . '?\')">
            <i class="fas fa-money-bill-wave"></i> Lunas
          </a>';
      }
    }
    $aksi_html = '<div class="d-flex flex-column">' . $btnDetail . $aksi_content . '</div>';
    
    // Render Baris
    $table_rows[] = '
              <tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . $user_display . '</td>
                <td>' . ($r['nama_lapangan'] ? htmlspecialchars($r['nama_lapangan']) : '-') . '</td>
                <td class="text-center small">' . ($r['created_at'] ? date('d/m/y H:i', strtotime($r['created_at'])) : '-') . '</td>
                <td class="text-center">
                  <span class="badge ' . ($r['tipe_booking'] == 'member' ? 'bg-success' : 'bg-secondary') . '" style="font-size:0.7rem;">' . ($r['tipe_booking'] ? ucfirst($r['tipe_booking']) : '-') . '</span>
                </td>
                <td class="text-center">
                  <span class="badge ' . (strtolower($r['tipe']) == 'dp' ? 'bg-info text-dark' : 'bg-primary') . '" style="font-size:0.7rem;">' . ucfirst($r['tipe']) . '</span>
                </td>
                <td class="text-end">' . $nominal . '</td>
                <td class="text-center small">' . $metode . '</td>
                <td class="text-center">' . $bukti_html . '</td>
                <td class="text-center">
                  <span class="badge ' . $badge_class . '" style="font-size: 0.75rem;">' . $label . '</span>
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
// LOOP Modal Detail (TETAP SAMA)
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
  <div class="modal fade" id="modalDetail<?= $booking_id ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
          <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i> Rincian Transaksi #<?= $booking_id ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body bg-light">
          <div class="row g-3">
            <div class="col-md-7">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-muted border-bottom pb-2 mb-3"><i class="fas fa-info-circle me-1"></i> Informasi Booking</h6>
                  <table class="table table-borderless table-sm mb-0">
                    <tr><td width="30%" class="text-muted">Nama</td><td class="fw-bold"><?= $data['user_display'] ?></td></tr>
                    <tr><td class="text-muted">Lapangan</td><td class="fw-bold text-primary"><?= $data['nama_lapangan'] ?></td></tr>
                    <tr><td class="text-muted">Produk</td><td class="text-dark"><?= $data['info_produk'] ?></td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="badge bg-<?= $status_color ?>"><?= $data['booking_status'] ?></span></td></tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="card shadow-sm border-0 h-100" style="background-color: #f8f9fa;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                  <h6 class="text-uppercase text-muted" style="font-size: 0.8rem;">Total Tagihan</h6>
                  <h3 class="fw-bold text-dark mb-3">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></h3>
                  <?php if ($data['remaining_amount'] > 0): ?>
                      <div class="p-2 rounded bg-white border border-danger">
                        <small class="text-danger fw-bold">Belum Lunas</small>
                        <div class="fw-bold text-danger fs-5">Sisa: Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></div>
                      </div>
                  <?php else: ?>
                      <div class="p-2 rounded bg-success text-white"><strong>LUNAS</strong></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="card mt-4 shadow-sm border-0">
            <div class="card-header bg-white border-bottom"><h6 class="mb-0 text-primary">Riwayat Pembayaran</h6></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle small">
                  <thead class="bg-light text-secondary">
                    <tr><th>#</th><th>Tanggal</th><th>Tipe</th><th class="text-end">Nominal</th><th>Status</th></tr>
                  </thead>
                  <tbody>
                    <?php $i=1; 
                    if (mysqli_num_rows($resPayments) > 0):
                        mysqli_data_seek($resPayments, 0);
                        while ($pay = mysqli_fetch_assoc($resPayments)): 
                          $st_cls = match($pay['status_verifikasi']) { 'valid'=>'success', 'menunggu'=>'warning', 'tidak_valid'=>'danger', default=>'secondary' };
                    ?>
                      <tr>
                        <td><?= $i++ ?></td>
                        <td><?= date('d/m/y H:i', strtotime($pay['created_at'])) ?></td>
                        <td><?= htmlspecialchars($pay['tipe']) ?></td>
                        <td class="text-end fw-bold">Rp <?= number_format($pay['amount'],0,',','.') ?></td>
                        <td><span class="badge bg-<?= $st_cls ?>"><?= ucfirst($pay['status_verifikasi']) ?></span></td>
                      </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light d-flex justify-content-between">
           <small class="text-muted"><i class="fas fa-shield-alt"></i> Transaksi Aman</small>
           <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
<?php mysqli_stmt_close($stmtPayments); endforeach; ?>

<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 bg-transparent shadow-none">
      <div class="modal-header border-0 p-0 mb-2">
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-0">
        <img id="previewImage" src="" class="img-fluid rounded shadow-lg" alt="Bukti Pembayaran" style="max-height: 85vh; border: 2px solid #fff;">
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
  setTimeout(function(){ $('.alert').fadeOut('slow'); }, 5000);

  function showImagePreview(imageSrc) {
    const modalEl = document.getElementById('imagePreviewModal');
    const imageEl = document.getElementById('previewImage');
    imageEl.src = imageSrc;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }
</script>