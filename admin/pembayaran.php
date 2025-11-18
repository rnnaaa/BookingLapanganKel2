<?php
// pembayaran.php - Daftar pembayaran (header.php sudah meng-handle session_start)
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
$sql = "
  SELECT 
    p.*, 
    b.id_booking,
    b.tanggal,
    b.tipe_booking,
    b.status AS booking_status,
    b.payment_status,
    u.nama AS nama_user,
    l.nama_lapangan,
    admin.nama AS verified_by_name
  FROM pembayaran p
  JOIN booking b ON p.booking_id = b.id_booking
  JOIN users u ON b.id_user = u.id_user
  JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  LEFT JOIN users admin ON p.verified_by = admin.id_user
  ORDER BY 
    CASE 
      WHEN p.status_verifikasi = 'menunggu' THEN 1
      WHEN p.status_verifikasi = 'tidak_valid' THEN 2
      WHEN p.status_verifikasi = 'valid' THEN 3
    END,
    p.created_at DESC
";
$result = mysqli_query($conn, $sql);

if (!$result) {
  echo '<tr><td colspan="12" class="text-center text-danger">Error: ' . mysqli_error($conn) . '</td></tr>';
} else {
  while ($r = mysqli_fetch_assoc($result)):
    $status = strtolower($r['status_verifikasi']);
    switch ($status) {
      case 'valid': $badge = 'bg-success'; $icon = 'fa-check-circle'; $label = 'Valid'; break;
      case 'tidak_valid': $badge = 'bg-danger'; $icon = 'fa-times-circle'; $label = 'Tidak Valid'; break;
      default: $badge = 'bg-warning text-dark'; $icon = 'fa-hourglass-half'; $label = 'Menunggu';
    }

    $metode = $r['method'] ? ucfirst(str_replace('_', ' ', $r['method'])) : '<em class="text-muted">-</em>';
    $nominal = 'Rp ' . number_format($r['amount'], 0, ',', '.');

    // cek file bukti di beberapa lokasi (lebih robust)
    $bukti = '<em class="text-muted">Belum upload</em>';
    if (!empty($r['bukti_pembayaran'])) {
      $filename = basename($r['bukti_pembayaran']); // hindari path traversal
      $possible_paths = [
        __DIR__ . '/../uploads/' . $filename,
        __DIR__ . '/uploads/' . $filename,
        rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/' . $filename,
      ];
      $found_path = null;
      foreach ($possible_paths as $p_path) {
        if (@is_file($p_path)) {
          $found_path = $p_path;
          break;
        }
      }

      if ($found_path) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tif','tiff']);
        $web_path = '/uploads/' . rawurlencode($filename);
        if ($is_image) {
          $bukti = '<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBukti' . $r['id_pembayaran'] . '"><i class="fas fa-eye"></i> Lihat</button>';
        } else {
          $bukti = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Download</a>';
        }
      } else {
        $bukti = '<em class="text-danger">File hilang</em>';
      }
    }

    $verified_by = $r['verified_by_name']
      ? htmlspecialchars($r['verified_by_name']) . '<br><small class="text-muted">' . ($r['verified_at'] ? date('d/m/Y H:i', strtotime($r['verified_at'])) : '') . '</small>'
      : '<em class="text-muted">-</em>';
?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                <td class="text-center">
                  <span class="badge <?= $r['tipe_booking'] == 'member' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($r['tipe_booking']) ?></span>
                </td>
                <td class="text-center">
                  <span class="badge <?= $r['tipe'] == 'DP' ? 'bg-info' : 'bg-primary' ?>"><?= ucfirst($r['tipe']) ?></span>
                </td>
                <td class="text-end"><?= $nominal ?></td>
                <td class="text-center"><?= $metode ?></td>
                <td class="text-center"><?= $bukti ?></td>
                <td class="text-center">
                  <span class="badge <?= $badge ?>"><i class="fas <?= $icon ?> me-1"></i><?= $label ?></span>
                  <br><small class="text-muted">Booking: <span class="badge bg-secondary"><?= ucfirst($r['booking_status']) ?></span><br>Payment: <span class="badge bg-info"><?= str_replace('_',' ',ucfirst($r['payment_status'])) ?></span></small>
                </td>
                <td class="text-center"><?= $verified_by ?></td>
                <td class="text-center">
                  <?php if ($status === 'menunggu'): ?>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=valid" class="btn btn-success btn-sm mb-1"
                       onclick="return confirm('✅ Validasi pembayaran ini sebagai sah?\n\nIni akan:\n- Update status verifikasi\n- (Jika tipe = Pelunasan) Masuk ke tabel keuangan (jumlah keseluruhan)\n- Update status booking & payment\n\nLanjutkan?')">
                      <i class="fas fa-check"></i> Valid
                    </a>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=tolak" class="btn btn-danger btn-sm mb-1"
                       onclick="return confirm('❌ Tolak pembayaran ini?\n\nUser harus upload ulang bukti yang benar.')">
                      <i class="fas fa-times"></i> Tolak
                    </a>
                  <?php else: ?>
                    <a href="booking_detail.php?id=<?= $r['id_booking'] ?>" class="btn btn-info btn-sm"><i class="fas fa-info-circle"></i> Detail</a>
                  <?php endif; ?>
                </td>
              </tr>

<?php
  // modal gambar jika ada file image
  if (!empty($r['bukti_pembayaran'])):
    $filename = basename($r['bukti_pembayaran']);
    $possible_paths = [
      __DIR__ . '/../uploads/' . $filename,
      __DIR__ . '/uploads/' . $filename,
      rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/' . $filename,
    ];
    $found_path = null;
    foreach ($possible_paths as $p_path) {
      if (@is_file($p_path)) { $found_path = $p_path; break; }
    }
    if ($found_path) {
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tif','tiff']);
      if ($is_image):
?>
              <div class="modal fade" id="modalBukti<?= $r['id_pembayaran'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title"><i class="fas fa-image me-2"></i>Bukti Pembayaran #<?= $r['id_pembayaran'] ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                      <img src="/uploads/<?= htmlspecialchars($filename) ?>" class="img-fluid rounded shadow" alt="Bukti Pembayaran" style="max-height:70vh;">
                      <div class="mt-3">
                        <table class="table table-bordered">
                          <tr><th width="150">Pengguna</th><td><?= htmlspecialchars($r['nama_user']) ?></td></tr>
                          <tr><th>Nominal</th><td><?= $nominal ?></td></tr>
                          <tr><th>Tipe</th><td><?= ucfirst($r['tipe']) ?></td></tr>
                          <tr><th>Upload</th><td><?= date('d/m/Y H:i', strtotime($r['tanggal_upload'])) ?></td></tr>
                        </table>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <a href="/uploads/<?= htmlspecialchars($filename) ?>" download class="btn btn-primary"><i class="fas fa-download"></i> Download</a>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                  </div>
                </div>
              </div>
<?php
      endif;
    }
  endif; // end modal
  endwhile; // end while rows
} // end else result
?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
  setTimeout(function(){ $('.alert').fadeOut('slow'); }, 5000);
  // prevent double click visual feedback
  document.addEventListener('DOMContentLoaded', function() {
    const validButtons = document.querySelectorAll('a[href*="pembayaran_validasi.php"]');
    validButtons.forEach(btn => {
      let isProcessing = false;
      btn.addEventListener('click', function(e) {
        if (isProcessing) { e.preventDefault(); alert('⏳ Sedang memproses...'); return false; }
        if (!confirm(this.href.includes('aksi=valid') ? 'Yakin valid?' : 'Yakin tolak?')) { e.preventDefault(); return false; }
        isProcessing = true;
        this.classList.add('disabled'); this.style.opacity='0.5'; this.style.pointerEvents='none';
        const orig = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        setTimeout(()=>{ isProcessing=false; this.classList.remove('disabled'); this.style.opacity='1'; this.style.pointerEvents='auto'; this.innerHTML = orig; }, 5000);
      });
    });
  });
</script>
