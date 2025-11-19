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

// Siapkan array untuk menampung baris tabel dan data modal
$table_rows = [];
$modal_details = [];
$rendered_modals = []; // untuk mencegah pembuatan modal ganda per booking_id

// gunakan LEFT JOIN untuk toleran terhadap data user/lapangan hilang (walkin,dll)
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
  // LOOP 1: Ambil data dan siapkan HTML baris tabel & data modal
  while ($r = mysqli_fetch_assoc($result)):
    $status = strtolower($r['status_verifikasi']);
    // badge for verification status, with special label for DP that belum pelunasan
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

    // cek file bukti di beberapa lokasi (lebih robust)
    $bukti_html = '<em class="text-muted">Belum upload</em>';
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
          // Ganti ke modal Bukti per pembayaran jika diperlukan
          $bukti_html = '<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBukti' . intval($r['id_pembayaran']) . '"><i class="fas fa-eye"></i> Lihat</button>';
        } else {
          $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Download</a>';
        }
      } else {
        $bukti_html = '<em class="text-danger">File hilang</em>';
      }
    }

    $verified_by = $r['verified_by_name']
      ? htmlspecialchars($r['verified_by_name']) . '<br><small class="text-muted">' . ($r['verified_at'] ? date('d/m/Y H:i', strtotime($r['verified_at'])) : '') . '</small>'
      : '<em class="text-muted">-</em>';

    $booking_id = intval($r['id_booking']);
    $user_display = $r['nama_user'] ? htmlspecialchars($r['nama_user']) : '<em class="text-muted">Walk-in / -</em>';

    // Aksi button
    $aksi_html = '';
    if ($status === 'menunggu') {
      $aksi_html = '
        <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=valid" class="btn btn-success btn-sm mb-1"
           onclick="return confirm(\'✅ Validasi pembayaran ini sebagai sah?\n\nIni akan:\n- Update status verifikasi\n- (Jika tipe = Pelunasan) Masuk ke tabel keuangan (jumlah keseluruhan)\n- Update booking & payment\n\nLanjutkan?\')">
          <i class="fas fa-check"></i> Valid
        </a>
        <a href="pembayaran_validasi.php?id=' . intval($r['id_pembayaran']) . '&aksi=tolak" class="btn btn-danger btn-sm mb-1"
           onclick="return confirm(\'❌ Tolak pembayaran ini?\n\nUser harus upload ulang bukti yang benar.\')">
          <i class="fas fa-times"></i> Tolak
        </a>';
    } else {
      $aksi_html = '<button class="btn btn-info btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDetail' . $booking_id . '"><i class="fas fa-info-circle"></i> Detail</button>';

      // show Proses Pelunasan button when booking not lunas, remaining > 0, and current session user is admin
      $remaining = floatval($r['remaining_amount']);
      if ($remaining > 0 && $r['payment_status'] !== 'lunas' && strtolower($r['status_verifikasi']) === 'valid' && ($_SESSION['role'] ?? '') === 'admin') {
        $aksi_html .= '
          <a href="pembayaran_validasi.php?aksi=pelunasan&booking_id=' . $booking_id . '" class="btn btn-warning btn-sm"
             onclick="return confirm(\'Proses pelunasan booking #' . $booking_id . '?\n\nAksi ini akan:\n- Membuat pembayaran tipe Pelunasan (tunai) yang otomatis divalidasi\n- Menambahkan 1 entri ke tabel keuangan (jika belum ada)\n- Mengubah status booking menjadi LUNAS / disetujui\n\nLanjutkan?\')">
            <i class="fas fa-hand-holding-dollar"></i> Proses Pelunasan
          </a>';
      }
    }

    // Bangun HTML baris tabel
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
                  <br><small class="text-muted">Booking: <span class="badge bg-secondary">' . htmlspecialchars(ucfirst($r['booking_status'])) . '</span><br>Payment: <span class="badge bg-info">' . htmlspecialchars(str_replace('_',' ',ucfirst($r['payment_status']))) . '</span></small>
                </td>
                <td class="text-center">' . $verified_by . '</td>
                <td class="text-center">' . $aksi_html . '</td>
              </tr>';

    // Siapkan data untuk Modal Detail (hanya sekali per booking_id)
    if (!in_array($booking_id, $rendered_modals, true)) {
        $rendered_modals[] = $booking_id;
        
        // Simpan semua data yang diperlukan untuk modal
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
  endwhile; // end while rows
} // end else result

// LOOP 2: Cetak semua baris tabel
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
// LOOP 3: Cetak semua Modal Detail (di luar content-wrapper atau di akhir body)
foreach ($modal_details as $booking_id => $data):
    // Ambil riwayat pembayaran dari database
    $stmtPayments = mysqli_prepare($conn, "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtPayments, "i", $booking_id);
    mysqli_stmt_execute($stmtPayments);
    $resPayments = mysqli_stmt_get_result($stmtPayments);

    // Ambil data sisa tagihan dan status
    $remaining = floatval($data['remaining_amount']);
    $payment_status_str = strtolower($data['payment_status']);
    
    // --- LOGIKA TOMBOL PELUNASAN (TANPA CEK ADMIN) ---
    // Tombol muncul jika: Masih ada Sisa Tagihan DAN Status belum 'lunas'
    $show_pelunasan = ($remaining > 0 && $payment_status_str !== 'lunas');
?>
  <div class="modal fade" id="modalDetail<?= $booking_id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Detail Pembayaran - Booking #<?= $booking_id ?></h5>
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
                    <small class="text-muted">Sisa Tagihan (Remaining)</small>
                    <h5 class="fw-bold text-danger mb-0">Rp <?= number_format($data['remaining_amount'],0,',','.') ?></h5>
                </div>
             </div>
          </div>
          
          <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i> 
            Status Booking: <strong><?= $data['booking_status'] ?></strong> | 
            Status Pembayaran: <strong><?= $data['payment_status'] ?></strong>
          </div>

          <hr>
          
          <h6 class="mb-3"><i class="fas fa-history me-1"></i> Riwayat Pembayaran</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle table-hover">
              <thead class="table-light text-center">
                <tr>
                  <th>#</th>
                  <th>Tanggal</th>
                  <th>Tipe</th>
                  <th>Nominal</th>
                  <th>Metode</th>
                  <th>Status</th>
                  <th>Bukti</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $i=1; 
                // Loop data pembayaran
                while ($pay = mysqli_fetch_assoc($resPayments)):
                  $ext = $pay['bukti_pembayaran'] ? strtolower(pathinfo($pay['bukti_pembayaran'], PATHINFO_EXTENSION)) : '';
                  $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tif','tiff']);
                  
                  // Normalisasi status verifikasi untuk badge
                  $status_ver = strtolower($pay['status_verifikasi']);
                ?>
                  <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="text-center"><?= $pay['created_at'] ? date('d/m/Y H:i', strtotime($pay['created_at'])) : '-' ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($pay['tipe']) ?></span></td>
                    <td class="text-end">Rp <?= number_format($pay['amount'],0,',','.') ?></td>
                    <td class="text-center"><?= $pay['method'] ? htmlspecialchars($pay['method']) : '-' ?></td>
                    <td class="text-center">
                        <?php if($status_ver == 'valid'): ?>
                            <span class="badge bg-success">Valid</span>
                        <?php elseif($status_ver == 'tidak_valid'): ?>
                            <span class="badge bg-danger">Ditolak</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Menunggu</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php 
                      $filename_pay = basename($pay['bukti_pembayaran'] ?? '');
                      if ($pay['bukti_pembayaran'] && $is_image): ?>
                        <a href="/uploads/<?= rawurlencode($filename_pay) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                      <?php elseif ($pay['bukti_pembayaran']): ?>
                        <a href="/uploads/<?= rawurlencode($filename_pay) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>
                      <?php else: ?>
                        <small class="text-muted">-</small>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
                
                <?php if($i == 1): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada riwayat pembayaran.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        
        <div class="modal-footer d-flex justify-content-between align-items-center">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          
          <?php if ($show_pelunasan): ?>
            <div>
                <span class="text-muted me-2 small">Sisa: Rp <?= number_format($remaining,0,',','.') ?></span>
                <a href="pembayaran_validasi.php?aksi=pelunasan&booking_id=<?= $booking_id ?>" 
                   class="btn btn-warning fw-bold shadow-sm"
                   onclick="return confirm('⚠️ KONFIRMASI PELUNASAN (TUNAI)\n\nSistem akan:\n1. Membuat pembayaran baru TIPE: PELUNASAN (Tunai)\n2. Mengubah status booking jadi LUNAS\n3. Memasukkan TOTAL HARGA (Rp <?= number_format($data['total_amount'],0,',','.') ?>) ke tabel Keuangan secara OTOMATIS.\n\nPastikan uang tunai Rp <?= number_format($remaining,0,',','.') ?> sudah diterima.\n\nLanjutkan?')">
                  <i class="fas fa-hand-holding-dollar me-2"></i> Proses Pelunasan
                </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php
    mysqli_stmt_close($stmtPayments);
endforeach;
?>

<?php include('../includes/footer.php'); ?>

<?php
// Jika Anda ingin modal per bukti pembayaran (yang saat ini hanya menggunakan button "Lihat" jika file hilang)
?>

<script>
  setTimeout(function(){ const alerts = document.querySelectorAll('.alert'); alerts.forEach(a=>a.classList.remove('show')); }, 5000);
  // prevent double click visual feedback
  document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('a[href*="pembayaran_validasi.php"], button[data-bs-toggle="modal"]');
    actionButtons.forEach(btn => {
      let isProcessing = false;
      btn.addEventListener('click', function(e) {
        if (isProcessing) { e.preventDefault(); alert('⏳ Sedang memproses...'); return false; }
        isProcessing = true;
        if (this.tagName.toLowerCase() === 'a') {
          this.classList.add('disabled'); this.style.opacity='0.5'; this.style.pointerEvents='none';
          const orig = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
          setTimeout(()=>{ isProcessing=false; this.classList.remove('disabled'); this.style.opacity='1'; this.style.pointerEvents='auto'; this.innerHTML = orig; }, 4000);
        } else {
          // modal open - quickly allow
          isProcessing = false;
        }
      });
    });
  });



  setTimeout(function() {
  $('.alert').fadeOut('slow');
}, 5000);

</script>