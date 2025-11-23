<?php
// pembayaran.php - Daftar pembayaran
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-wallet me-2"></i> Data Pembayaran Pelanggan</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= $_SESSION['success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $_SESSION['error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="card shadow-lg border-0">
        <div class="card-header text-white"
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%); box-shadow: inset 0 -2px 8px rgba(0,0,0,0.15);">
          <h3 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2"></i> Daftar Pembayaran Pelanggan</h3>
        </div>

        <div class="card-body">
          <table id="tblPembayaran" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama Pengguna</th>
                <th>Lapangan</th>
                <th>Tanggal Booking</th>
                <th>Tipe Booking</th>
                <th>Tipe Pembayaran</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Bukti</th>
                <th>Status Verifikasi</th>
                <th>Verified By</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
<?php
$no = 1;
$table_rows = [];
$modal_details = [];
$rendered_modals = [];

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
    u.nama AS nama_user,
    l.nama_lapangan,
    admin.nama AS verified_by_name
  FROM pembayaran p
  JOIN booking b ON p.booking_id = b.id_booking
  LEFT JOIN users u ON b.id_user = u.id_user
  LEFT JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  LEFT JOIN users admin ON p.verified_by = admin.id_user
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
  echo '<tr><td colspan="12" class="text-center text-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</td></tr>';
} else {
  while ($r = mysqli_fetch_assoc($result)):
    $status = strtolower($r['status_verifikasi']);
    
    // Badge Status
    $badge_class = 'bg-warning text-dark'; $icon = 'fa-hourglass-half'; $label = 'Menunggu';
    if ($status === 'valid') {
      $badge_class = 'bg-success'; $icon = 'fa-check-circle'; $label = 'Valid';
      if (strtolower($r['tipe']) === 'dp' && $r['payment_status'] === 'dp_bayar' && floatval($r['remaining_amount']) > 0) {
        $badge_class = 'bg-info text-dark'; $icon = 'fa-clock'; $label = 'DP - Belum Pelunasan';
      }
    } elseif ($status === 'tidak_valid') {
      $badge_class = 'bg-danger'; $icon = 'fa-times-circle'; $label = 'Tidak Valid';
    }

    $metode = $r['method'] ? htmlspecialchars(ucfirst(str_replace('_', ' ', $r['method']))) : '<em class="text-muted">-</em>';
    $nominal = 'Rp ' . number_format($r['amount'], 0, ',', '.');

    // --- LOGIKA PENCARIAN FILE BUKTI (TABEL UTAMA) ---
    $bukti_html = '<em class="text-muted">Belum upload</em>';
    
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
           // PANGGIL FUNGSI JS UNTUK BUKA MODAL
           $bukti_html = '<button type="button" onclick="showImagePreview(\'' . htmlspecialchars($web_path) . '\')" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye"></i> Lihat</button>';
        } else {
           $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Unduh</a>';
        }
      } else {
        $bukti_html = '<em class="text-danger" title="File tidak ditemukan di server">File hilang</em>';
      }
    }
    // ---------------------------------------------

    $verified_by = $r['verified_by_name']
      ? htmlspecialchars($r['verified_by_name']) . '<br><small class="text-muted">' . ($r['verified_at'] ? date('d/m/Y H:i', strtotime($r['verified_at'])) : '') . '</small>'
      : '<em class="text-muted">-</em>';

    $booking_id = intval($r['id_booking']);
    $user_display = $r['nama_user'] ? htmlspecialchars($r['nama_user']) : '<em class="text-muted">Walk-in / -</em>';

    // Tombol Aksi
    $aksi_html = '';
    if ($status === 'menunggu') {
      $aksi_html = '
        <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=valid" class="btn btn-success btn-sm mb-1"
           onclick="return confirm(\'✅ Validasi pembayaran ini sebagai sah?\')">
          <i class="fas fa-check"></i> Valid
        </a>
        <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=tolak" class="btn btn-danger btn-sm mb-1"
           onclick="return confirm(\'❌ Tolak pembayaran ini?\')">
          <i class="fas fa-times"></i> Tolak
        </a>';
    } else {
      $aksi_html = '<button class="btn btn-info btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDetail' . $booking_id . '"><i class="fas fa-info-circle"></i> Detail</button>';
      
      $remaining = floatval($r['remaining_amount']);
      if ($remaining > 0 && $r['payment_status'] !== 'lunas' && strtolower($r['status_verifikasi']) === 'valid' && ($_SESSION['role'] ?? '') === 'admin') {
        $aksi_html .= '
          <a href="pembayaran_validasi.php?aksi=pelunasan&booking_id=' . $booking_id . '" class="btn btn-warning btn-sm"
             onclick="return confirm(\'Proses pelunasan booking #' . $booking_id . '?\')">
            <i class="fas fa-hand-holding-dollar"></i> Lunas
          </a>';
      }
    }

    // Render Baris Tabel
    $table_rows[] = '
              <tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . $user_display . '</td>
                <td>' . ($r['nama_lapangan'] ? htmlspecialchars($r['nama_lapangan']) : '<em class="text-muted">-</em>') . '</td>
                <td class="text-center">' . ($r['tanggal'] ? date('d/m/Y', strtotime($r['tanggal'])) : '<em class="text-muted">-</em>') . '</td>
                <td class="text-center">
                  <span class="badge ' . ($r['tipe_booking'] == 'member' ? 'bg-success' : 'bg-secondary') . '">' . ($r['tipe_booking'] ? htmlspecialchars(ucfirst($r['tipe_booking'])) : '-') . '</span>
                </td>
                <td class="text-center">
                  <span class="badge ' . (strtolower($r['tipe']) == 'dp' ? 'bg-info' : 'bg-primary') . '">' . htmlspecialchars(ucfirst($r['tipe'])) . '</span>
                </td>
                <td class="text-end">' . $nominal . '</td>
                <td class="text-center">' . $metode . '</td>
                <td class="text-center">' . $bukti_html . '</td>
                <td class="text-center">
                  <span class="badge ' . $badge_class . '"><i class="fas ' . $icon . ' me-1"></i>' . $label . '</span>
                </td>
                <td class="text-center">' . $verified_by . '</td>
                <td class="text-center">' . $aksi_html . '</td>
              </tr>';

    // Data untuk Modal Detail
    if (!in_array($booking_id, $rendered_modals, true)) {
        $rendered_modals[] = $booking_id;
        $modal_details[$booking_id] = [
            'user_display' => $user_display,
            'nama_lapangan' => $r['nama_lapangan'] ? htmlspecialchars($r['nama_lapangan']) : '<em class="text-muted">-</em>',
            'total_amount' => $r['total_amount'] ?? 0,
            'remaining_amount' => $r['remaining_amount'] ?? 0,
            'booking_status' => htmlspecialchars(ucfirst($r['booking_status'])),
            'payment_status' => htmlspecialchars(str_replace('_',' ',ucfirst($r['payment_status']))),
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
  </section>
</div>

<?php
// LOOP Modal Detail
foreach ($modal_details as $booking_id => $data):
    $stmtPayments = mysqli_prepare($conn, "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtPayments, "i", $booking_id);
    mysqli_stmt_execute($stmtPayments);
    $resPayments = mysqli_stmt_get_result($stmtPayments);
?>
  <div class="modal fade" id="modalDetail<?= $booking_id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title">Detail Booking #<?= $booking_id ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
             <div class="col-md-6">
                <table class="table table-borderless table-sm">
                    <tr><td width="100"><strong>Pengguna</strong></td><td>: <?= $data['user_display'] ?></td></tr>
                    <tr><td><strong>Lapangan</strong></td><td>: <?= $data['nama_lapangan'] ?></td></tr>
                </table>
             </div>
             <div class="col-md-6 text-md-end">
                <div class="p-2 bg-light border rounded">
                    <small class="text-muted">Total Booking</small>
                    <h5 class="fw-bold text-primary mb-1">Rp <?= number_format($data['total_amount'],0,',','.') ?></h5>
                    <small class="text-muted">Sisa Tagihan</small>
                    <h5 class="fw-bold text-danger mb-0">Rp <?= number_format($data['remaining_amount'],0,',','.') ?></h5>
                </div>
             </div>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
              <thead class="table-light text-center">
                <tr><th>#</th><th>Tanggal</th><th>Tipe</th><th>Nominal</th><th>Status</th><th>Bukti</th></tr>
              </thead>
              <tbody>
                <?php 
                $i=1; 
                while ($pay = mysqli_fetch_assoc($resPayments)):
                    // --- LOGIKA PENCARIAN FILE BUKTI (UNTUK MODAL) ---
                    $bukti_html_modal = '<small class="text-muted">-</small>';
                    
                    if (!empty($pay['bukti_pembayaran'])) {
                        $filename_modal = basename($pay['bukti_pembayaran']);
                        
                        $path_check_modal = [
                            'nested' => __DIR__ . '/../uploads/bukti_pembayaran/' . $filename_modal,
                            'root'   => __DIR__ . '/../uploads/' . $filename_modal
                        ];
                        
                        $web_path_modal = '';
                        $found_modal = false;

                        if (file_exists($path_check_modal['nested'])) {
                            $web_path_modal = '../uploads/bukti_pembayaran/' . rawurlencode($filename_modal);
                            $found_modal = true;
                        } elseif (file_exists($path_check_modal['root'])) {
                            $web_path_modal = '../uploads/' . rawurlencode($filename_modal);
                            $found_modal = true;
                        }

                        if ($found_modal) {
                            $ext_modal = strtolower(pathinfo($filename_modal, PATHINFO_EXTENSION));
                            $is_image_modal = in_array($ext_modal, ['jpg','jpeg','png','gif','webp','bmp']);
                            
                            if ($is_image_modal) {
                               // PANGGIL FUNGSI JS UNTUK BUKA MODAL PREVIEW
                               $bukti_html_modal = '<button type="button" onclick="showImagePreview(\'' . htmlspecialchars($web_path_modal) . '\')" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-eye"></i></button>';
                            } else {
                               $bukti_html_modal = '<a href="' . htmlspecialchars($web_path_modal) . '" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-download"></i></a>';
                            }
                        } else {
                            $bukti_html_modal = '<span class="text-danger" style="font-size:0.8em">File hilang</span>';
                        }
                    }
                    // ------------------------------------------------
                ?>
                  <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="text-center"><?= $pay['created_at'] ? date('d/m/Y H:i', strtotime($pay['created_at'])) : '-' ?></td>
                    <td class="text-center"><?= htmlspecialchars($pay['tipe']) ?></td>
                    <td class="text-end">Rp <?= number_format($pay['amount'],0,',','.') ?></td>
                    <td class="text-center"><?= $pay['status_verifikasi'] ?></td>
                    <td class="text-center"><?= $bukti_html_modal ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
<?php
    mysqli_stmt_close($stmtPayments);
endforeach;
?>

<!-- MODAL PREVIEW GAMBAR (BARU) -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title"><i class="fas fa-image me-2"></i> Bukti Pembayaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-0 bg-dark">
        <img id="previewImage" src="" class="img-fluid" alt="Bukti Pembayaran" style="max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
  setTimeout(function(){ $('.alert').fadeOut('slow'); }, 5000);

  // Fungsi untuk menampilkan gambar di modal
  function showImagePreview(imageSrc) {
    const modalEl = document.getElementById('imagePreviewModal');
    const imageEl = document.getElementById('previewImage');
    
    // Set sumber gambar
    imageEl.src = imageSrc;
    
    // Tampilkan modal menggunakan Bootstrap API
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }
</script>