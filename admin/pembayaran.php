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
    // Ambil data pembayaran
    $stmtPayments = mysqli_prepare($conn, "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtPayments, "i", $booking_id);
    mysqli_stmt_execute($stmtPayments);
    $resPayments = mysqli_stmt_get_result($stmtPayments);
    
    // Tentukan warna status booking
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
          <h5 class="modal-title">
            <i class="fas fa-file-invoice-dollar me-2"></i> Rincian Transaksi #<?= $booking_id ?>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body bg-light">
          <div class="row g-3">
            <div class="col-md-7">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-muted border-bottom pb-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i> Informasi Booking
                  </h6>
                  <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td width="30%" class="text-muted"><i class="fas fa-user me-2"></i>Nama</td>
                        <td class="fw-bold text-dark"><?= $data['user_display'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-futbol me-2"></i>Lapangan</td>
                        <td class="fw-bold text-primary"><?= $data['nama_lapangan'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-tag me-2"></i>Status</td>
                        <td>
                            <span class="badge bg-<?= $status_color ?> rounded-pill px-3">
                                <?= $data['booking_status'] ?>
                            </span>
                        </td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-md-5">
              <div class="card shadow-sm border-0 h-100" style="background-color: #f8f9fa;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                  <h6 class="text-uppercase text-muted letter-spacing-1" style="font-size: 0.8rem;">Total Tagihan</h6>
                  <h3 class="fw-bold text-dark mb-3">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></h3>
                  
                  <?php if ($data['remaining_amount'] > 0): ?>
                      <div class="p-2 rounded bg-white border border-danger">
                        <small class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</small>
                        <div class="fw-bold text-danger fs-5">Sisa: Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></div>
                      </div>
                  <?php else: ?>
                      <div class="p-2 rounded bg-success text-white">
                        <i class="fas fa-check-circle me-1"></i> <strong>LUNAS</strong>
                      </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card mt-4 shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
              <h6 class="mb-0 text-primary"><i class="fas fa-history me-2"></i> Riwayat Pembayaran</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                  <thead class="bg-light text-secondary small">
                    <tr>
                      <th class="text-center">#</th>
                      <th><i class="far fa-calendar-alt me-1"></i> Tanggal</th>
                      <th class="text-center">Tipe</th>
                      <th class="text-end">Nominal</th>
                      <th class="text-center">Status</th>
                      <th class="text-center">Bukti</th>
                    </tr>
                  </thead>
                  <tbody class="small">
                    <?php 
                    $i=1; 
                    if (mysqli_num_rows($resPayments) > 0):
                        mysqli_data_seek($resPayments, 0); // Reset pointer
                        while ($pay = mysqli_fetch_assoc($resPayments)):
                            // --- LOGIKA BUKTI (SAMA SEPERTI SEBELUMNYA) ---
                            $bukti_html_modal = '<span class="text-muted fst-italic">-</span>';
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
                                       $bukti_html_modal = '<button type="button" onclick="showImagePreview(\'' . htmlspecialchars($web_path_modal) . '\')" class="btn btn-xs btn-outline-info rounded-circle" title="Lihat Foto"><i class="fas fa-eye"></i></button>';
                                    } else {
                                       $bukti_html_modal = '<a href="' . htmlspecialchars($web_path_modal) . '" target="_blank" class="btn btn-xs btn-outline-secondary rounded-circle" title="Unduh"><i class="fas fa-download"></i></a>';
                                    }
                                }
                            }

                            // Badge Status Pembayaran Kecil
                            $status_ver_class = match($pay['status_verifikasi']) {
                                'valid' => 'success',
                                'menunggu' => 'warning',
                                'tidak_valid' => 'danger',
                                default => 'secondary'
                            };
                            $icon_ver = match($pay['status_verifikasi']) {
                                'valid' => 'fa-check',
                                'menunggu' => 'fa-clock',
                                'tidak_valid' => 'fa-times',
                                default => 'fa-question'
                            };
                    ?>
                      <tr>
                        <td class="text-center text-muted"><?= $i++ ?></td>
                        <td><?= $pay['created_at'] ? date('d/m/y H:i', strtotime($pay['created_at'])) : '-' ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= strtolower($pay['tipe']) == 'dp' ? 'info' : 'primary' ?> bg-opacity-75 text-white" style="font-weight: normal;">
                                <?= htmlspecialchars($pay['tipe']) ?>
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark">Rp <?= number_format($pay['amount'],0,',','.') ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $status_ver_class ?>-subtle text-<?= $status_ver_class ?> border border-<?= $status_ver_class ?>">
                                <i class="fas <?= $icon_ver ?> me-1"></i> <?= ucfirst($pay['status_verifikasi']) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= $bukti_html_modal ?></td>
                      </tr>
                    <?php endwhile; 
                    else: ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat pembayaran.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
            <div>
                <small class="text-muted fst-italic"><i class="fas fa-shield-alt me-1"></i> Transaksi Aman</small>
            </div>
            <div>
                <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>

                <?php 
                // --- LOGIKA TOMBOL LUNAS DI MODAL ---
                // Tampilkan jika sisa > 0 DAN user adalah admin
                if ($data['remaining_amount'] > 0 && ($_SESSION['role'] ?? '') === 'admin'): 
                ?>
                    <a href="pembayaran_validasi.php?aksi=pelunasan&booking_id=<?= $booking_id ?>" 
                       class="btn btn-warning px-4 fw-bold shadow-sm"
                       onclick="return confirm('Apakah Anda yakin ingin memproses pelunasan tunai untuk Booking #<?= $booking_id ?>? Total: Rp <?= number_format($data['remaining_amount'],0,',','.') ?>')">
                        <i class="fas fa-hand-holding-dollar me-2"></i> Proses Pelunasan
                    </a>
                <?php endif; ?>
            </div>
        </div>
      </div>
    </div>
  </div>
<?php
    mysqli_stmt_close($stmtPayments);
endforeach;
?>

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