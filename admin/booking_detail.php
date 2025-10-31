<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Cek ID booking
if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID booking tidak ditemukan.";
  header("Location: booking.php");
  exit;
}

$id_booking = intval($_GET['id']);

// Ambil data booking + user + lapangan
$sql = "
SELECT 
  b.*, 
  u.nama AS nama_user, u.tipe_user, u.email, u.no_hp,
  l.nama_lapangan, l.harga_per_jam
FROM booking b
JOIN users u ON b.id_user = u.id_user
JOIN lapangan l ON b.id_lapangan = l.id_lapangan
WHERE b.id_booking = $id_booking
";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
  $_SESSION['toast_error'] = "Data booking tidak ditemukan.";
  header("Location: booking.php");
  exit;
}

// Ambil jadwal main
$jadwal = mysqli_query($conn, "
  SELECT jw.jam_mulai, jw.jam_selesai
  FROM detail_booking db
  JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
  WHERE db.id_booking = $id_booking
");

// Ambil data pembayaran (DP & pelunasan)
$pembayaran = mysqli_query($conn, "
  SELECT *
  FROM pembayaran
  WHERE booking_id = $id_booking
  ORDER BY created_at ASC
");
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-info-circle mr-2"></i> Detail Booking</h1>
      <a href="booking.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
          <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i> Informasi Booking</h3>
          <div>
            <a href="booking_edit.php?id=<?= $id_booking ?>" class="btn btn-warning btn-sm">
              <i class="fas fa-edit"></i> Edit
            </a>
            <a href="invoice_booking.php?id=<?= $id_booking ?>" class="btn btn-light btn-sm" target="_blank">
              <i class="fas fa-print"></i> Cetak Invoice
            </a>
          </div>
        </div>

        <div class="card-body">

          <!-- Informasi Umum -->
          <div class="row">
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-2">🧑 Data Pemesan</h5>
              <table class="table table-sm">
                <tr><th>Nama</th><td><?= htmlspecialchars($data['nama_user']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($data['email']) ?></td></tr>
                <tr><th>No HP</th><td><?= htmlspecialchars($data['no_hp']) ?></td></tr>
                <tr><th>Tipe User</th>
                  <td><?= $data['tipe_user'] == 'member' ? '<span class="badge bg-success">Member</span>' : '<span class="badge bg-secondary">Reguler</span>'; ?></td>
                </tr>
              </table>
            </div>

            <div class="col-md-6">
              <h5 class="font-weight-bold mb-2">🏸 Data Lapangan</h5>
              <table class="table table-sm">
                <tr><th>Nama Lapangan</th><td><?= htmlspecialchars($data['nama_lapangan']) ?></td></tr>
                <tr><th>Tanggal Main</th><td><?= date('d F Y', strtotime($data['tanggal'])) ?></td></tr>
                <tr><th>Jadwal</th>
                  <td>
                    <?php while ($j = mysqli_fetch_assoc($jadwal)): ?>
                      <?= date('H:i', strtotime($j['jam_mulai'])) . " - " . date('H:i', strtotime($j['jam_selesai'])) ?><br>
                    <?php endwhile; ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <hr>

          <!-- Status Booking & Pembayaran -->
          <div class="row mt-3">
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-2">📋 Status Booking</h5>
              <?php
              $statusMap = [
                'menunggu' => 'bg-warning text-dark',
                'disetujui' => 'bg-primary',
                'selesai' => 'bg-success',
                'ditolak' => 'bg-danger',
                'dibatalkan' => 'bg-secondary'
              ];
              $badge = $statusMap[$data['status']] ?? 'bg-light';
              ?>
              <p><span class="badge <?= $badge ?> p-2"><?= ucfirst($data['status']) ?></span></p>
            </div>

            <div class="col-md-6">
              <h5 class="font-weight-bold mb-2">💳 Status Pembayaran</h5>
              <?php
              $payMap = [
                'belum_bayar' => 'bg-secondary',
                'menunggu_verifikasi' => 'bg-warning text-dark',
                'dp_bayar' => 'bg-info',
                'lunas' => 'bg-success',
                'dibatalkan' => 'bg-danger'
              ];
              $payBadge = $payMap[$data['payment_status']] ?? 'bg-light';
              ?>
              <p><span class="badge <?= $payBadge ?> p-2"><?= ucfirst(str_replace('_', ' ', $data['payment_status'])) ?></span></p>
            </div>
          </div>

          <hr>

          <!-- Rincian Pembayaran -->
          <h5 class="font-weight-bold mb-3">💰 Rincian Pembayaran</h5>
          <table class="table table-bordered table-hover text-center">
            <thead class="bg-light">
              <tr>
                <th>Tipe</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Status Verifikasi</th>
                <th>Tanggal Upload</th>
                <th>Bukti</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = mysqli_fetch_assoc($pembayaran)): ?>
                <tr>
                  <td><?= $p['tipe'] == 'DP' ? 'Down Payment (DP)' : 'Pelunasan' ?></td>
                  <td>Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                  <td><?= ucfirst(str_replace('_', ' ', $p['method'])) ?></td>
                  <td>
                    <?php
                      $statusClass = [
                        'menunggu' => 'badge bg-warning text-dark',
                        'valid' => 'badge bg-success',
                        'tidak_valid' => 'badge bg-danger'
                      ];
                    ?>
                    <span class="<?= $statusClass[$p['status_verifikasi']] ?? 'badge bg-secondary' ?>">
                      <?= ucfirst(str_replace('_', ' ', $p['status_verifikasi'])) ?>
                    </span>
                  </td>
                  <td><?= $p['tanggal_upload'] ? date('d-m-Y H:i', strtotime($p['tanggal_upload'])) : '-' ?></td>
                  <td>
                    <?php if (!empty($p['bukti_path'])): ?>
                      <a href="../uploads/<?= htmlspecialchars($p['bukti_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    <?php else: ?>
                      <span class="text-muted"><em>Tidak ada</em></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

          <div class="mt-3 text-right">
            <h5>Total Bayar: <b>Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></b></h5>
            <h6>DP: Rp <?= number_format($data['dp_amount'], 0, ',', '.') ?> — Sisa: Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></h6>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
