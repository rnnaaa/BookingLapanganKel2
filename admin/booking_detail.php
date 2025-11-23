<?php
// booking_detail.php - VERSI FINAL (Icons Restored + Edit/Print + Image Preview)
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Cek ID booking
if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID booking tidak ditemukan.";
  echo "<script>window.location='booking.php';</script>";
  exit;
}

$id_booking = intval($_GET['id']);

// Ambil data booking + user + lapangan + admin approval
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

// Ambil data pembayaran (DP & pelunasan)
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

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1>
          <i class="fas fa-info-circle me-2"></i> 
          Detail Booking #<?= $id_booking ?>
        </h1>
        <a href="booking.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <!-- Alert Toast -->
      <?php if (!empty($_SESSION['toast_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?= $_SESSION['toast_error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['toast_error']); ?>
      <?php endif; ?>
      
      <?php if (!empty($_SESSION['toast_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?= $_SESSION['toast_success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['toast_success']); ?>
      <?php endif; ?>

      <!-- Card Detail Booking -->
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
          <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-calendar-check me-2"></i> Informasi Booking
            </h3>
            <div class="btn-group">
              <!-- TOMBOL EDIT & CETAK -->
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

        <div class="card-body">
          
          <!-- 1. STATUS BADGES -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="alert alert-light border">
                <div class="row text-center">
                  <div class="col-md-4">
                    <h6 class="text-muted mb-2">Tipe Booking</h6>
                    <?php if ($data['tipe_booking'] === 'member'): ?>
                      <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-crown"></i> Member</span>
                    <?php elseif ($data['tipe_booking'] === 'manual'): ?>
                      <span class="badge bg-info fs-6 px-3 py-2"><i class="fas fa-walking"></i> Walk-In</span>
                    <?php else: ?>
                      <span class="badge bg-secondary fs-6 px-3 py-2"><i class="fas fa-user"></i> Reguler</span>
                    <?php endif; ?>
                  </div>
                  
                  <div class="col-md-4">
                    <h6 class="text-muted mb-2">Status Booking</h6>
                    <?php
                      $statusClass = [
                          'menunggu' => 'bg-warning text-dark',
                          'disetujui' => 'bg-primary',
                          'selesai' => 'bg-success',
                          'ditolak' => 'bg-danger',
                          'dibatalkan' => 'bg-secondary'
                      ];
                      $statusIcon = [
                          'menunggu' => 'clock',
                          'disetujui' => 'check-circle',
                          'selesai' => 'check-double',
                          'ditolak' => 'times-circle',
                          'dibatalkan' => 'ban'
                      ];
                    ?>
                    <span class="badge <?= $statusClass[$data['status']] ?? 'bg-secondary' ?> fs-6 px-3 py-2">
                      <i class="fas fa-<?= $statusIcon[$data['status']] ?? 'question' ?>"></i> <?= ucfirst($data['status']) ?>
                    </span>
                  </div>

                  <div class="col-md-4">
                    <h6 class="text-muted mb-2">Status Pembayaran</h6>
                    <?php
                      $payClass = [
                          'belum_bayar' => 'bg-secondary',
                          'menunggu_verifikasi' => 'bg-warning text-dark',
                          'dp_bayar' => 'bg-info',
                          'lunas' => 'bg-success'
                      ];
                      $payIcon = [
                          'belum_bayar' => 'wallet',
                          'menunggu_verifikasi' => 'hourglass-half',
                          'dp_bayar' => 'coins',
                          'lunas' => 'check-circle'
                      ];
                    ?>
                    <span class="badge <?= $payClass[$data['payment_status']] ?? 'bg-light' ?> fs-6 px-3 py-2">
                      <i class="fas fa-<?= $payIcon[$data['payment_status']] ?? 'question' ?>"></i> 
                      <?= ucfirst(str_replace('_', ' ', $data['payment_status'])) ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. DATA PEMESAN & LAPANGAN -->
          <div class="row">
            <div class="col-md-6">
              <div class="card border-primary mb-3">
                <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i> Data Pemesan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
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
                            <td><?= ucfirst($data['role']) ?></td>
                        </tr>
                    </table>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card border-success mb-3">
                <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
                    <h5 class="mb-0"><i class="fas fa-basketball-ball me-2"></i> Data Lapangan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%"><i class="fas fa-map-marker-alt text-success me-2"></i> Lapangan</th>
                            <td><strong><?= htmlspecialchars($data['nama_lapangan']) ?></strong></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt text-success me-2"></i> Tanggal</th>
                            <td><?= date('d F Y', strtotime($data['tanggal'])) ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-tag text-success me-2"></i> Harga/Jam</th>
                            <td>Rp <?= number_format($data['harga_per_jam'],0,',','.') ?></td>
                        </tr>
                    </table>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. SLOT WAKTU -->
          <div class="mb-4">
            <h5><i class="far fa-clock me-2"></i> Slot Waktu Booking</h5>
            <div class="d-flex flex-wrap gap-2">
               <?php 
               $jadwal->data_seek(0);
               while ($j = $jadwal->fetch_assoc()): ?>
                  <span class="badge bg-light text-dark border p-2 fs-6">
                      <i class="far fa-clock text-primary"></i> 
                      <?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                  </span>
               <?php endwhile; ?>
            </div>
          </div>

          <hr class="my-4">

          <!-- 4. RINCIAN PEMBAYARAN -->
          <div class="card border-warning mb-3">
            <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
              <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Rincian Keuangan</h5>
            </div>
            <div class="card-body">
              <!-- Summary Cards -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <div class="card bg-light text-center p-3">
                    <small class="text-muted">Total Tagihan</small>
                    <h3 class="text-primary mb-0">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></h3>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-light text-center p-3">
                    <small class="text-muted">Sudah Dibayar (DP)</small>
                    <h3 class="text-info mb-0">Rp <?= number_format($data['dp_amount'], 0, ',', '.') ?></h3>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-light text-center p-3">
                    <small class="text-muted">Sisa Kekurangan</small>
                    <h3 class="text-danger mb-0">Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></h3>
                  </div>
                </div>
              </div>

              <!-- History Table -->
              <h6 class="mb-2"><i class="fas fa-history"></i> Riwayat Pembayaran</h6>
              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead class="bg-light">
                    <tr>
                      <th>No</th>
                      <th>Tipe</th>
                      <th>Nominal</th>
                      <th>Metode</th>
                      <th>Status Verifikasi</th>
                      <th>Diverifikasi Oleh</th>
                      <th>Bukti</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $pembayaran->data_seek(0);
                    $no = 1;
                    if ($pembayaran->num_rows > 0):
                        while ($p = $pembayaran->fetch_assoc()): ?>
                        <tr>
                          <td class="text-center"><?= $no++ ?></td>
                          <td>
                            <?php if ($p['tipe'] === 'DP'): ?>
                                <span class="badge bg-info">DP</span>
                            <?php else: ?>
                                <span class="badge bg-success">Pelunasan</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-end">Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                          <td class="text-center"><?= strtoupper($p['method']) ?></td>
                          <td class="text-center">
                            <?php if($p['status_verifikasi'] == 'valid'): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Valid</span>
                            <?php elseif($p['status_verifikasi'] == 'menunggu'): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Menunggu</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times"></i> Invalid</span>
                            <?php endif; ?>
                          </td>
                          <td>
                             <?php if($p['verified_by']): ?>
                                <i class="fas fa-user-check text-success"></i> <?= $p['verified_by_name'] ?> <br>
                                <small class="text-muted"><?= $p['verified_at'] ?></small>
                             <?php else: ?> - <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <?php 
                              // --- LOGIKA PENCARIAN FILE BUKTI & MODAL ---
                              $bukti_html = '<span class="text-muted">-</span>';
                              if (!empty($p['bukti_pembayaran'])) {
                                  $filename = basename($p['bukti_pembayaran']);
                                  
                                  // Cek path (Sama seperti pembayaran.php)
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
                                         // Tombol Modal Preview
                                         $bukti_html = '<button type="button" onclick="showImagePreview(\'' . htmlspecialchars($web_path) . '\')" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i> Lihat</button>';
                                      } else {
                                         // Tombol Download
                                         $bukti_html = '<a href="' . htmlspecialchars($web_path) . '" target="_blank" class="btn btn-xs btn-outline-primary"><i class="fas fa-download"></i> Unduh</a>';
                                      }
                                  } else {
                                      $bukti_html = '<span class="text-danger" style="font-size:0.8em">File hilang</span>';
                                  }
                              }
                              echo $bukti_html;
                            ?>
                          </td>
                        </tr>
                        <?php endwhile; 
                    else: ?>
                        <tr><td colspan="7" class="text-center text-muted">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 5. INFORMASI LAINNYA -->
          <div class="row">
             <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Log Sistem</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th width="40%"><i class="far fa-clock text-info me-2"></i> Dibuat</th>
                                <td><?= date('d F Y, H:i', strtotime($data['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-sync-alt text-info me-2"></i> Update</th>
                                <td><?= date('d F Y, H:i', strtotime($data['updated_at'])) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-user-shield text-info me-2"></i> Disetujui</th>
                                <td><?= $data['approved_by_name'] ?? '-' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
             </div>
             
             <?php if (!empty($data['alasan_penolakan'])): ?>
             <div class="col-md-6">
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-circle"></i> Alasan Penolakan:</strong><br>
                    <?= nl2br(htmlspecialchars($data['alasan_penolakan'])) ?>
                </div>
             </div>
             <?php endif; ?>
          </div>

        </div>

        <div class="card-footer ">
          <a href="booking.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- MODAL PREVIEW GAMBAR (Generik) -->
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
  // setTimeout(function(){ $('.alert').fadeOut('slow'); }, 5000);

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