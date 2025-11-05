<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">

  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-wallet mr-2"></i> Data Pembayaran Pelanggan</h1>
      <a href="pembayaran_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Pembayaran
      </a>
    </div>
  </section>

  <!-- KONTEN -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-money-bill-wave mr-2"></i> Daftar Pembayaran Pelanggan
          </h3>
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
                  u.nama AS nama_user,
                  l.nama_lapangan
                FROM pembayaran p
                JOIN booking b ON p.booking_id = b.id_booking
                JOIN users u ON b.id_user = u.id_user
                JOIN lapangan l ON b.id_lapangan = l.id_lapangan
                ORDER BY p.created_at DESC
              ";
              $result = mysqli_query($conn, $sql);
              while ($r = mysqli_fetch_assoc($result)):

                // 🟢 Status Verifikasi Badge
                $status = strtolower($r['status_verifikasi']);
                switch ($status) {
                  case 'valid': 
                    $badge = 'bg-success'; 
                    $icon = 'fa-check-circle'; 
                    $label = 'Valid'; 
                    break;
                  case 'tidak_valid': 
                    $badge = 'bg-danger'; 
                    $icon = 'fa-times-circle'; 
                    $label = 'Tidak Valid'; 
                    break;
                  default: 
                    $badge = 'bg-warning text-dark'; 
                    $icon = 'fa-hourglass-half'; 
                    $label = 'Menunggu'; 
                }

                // 💳 Metode Pembayaran
                $metode = $r['method'] ? ucfirst(str_replace('_', ' ', $r['method'])) : '<em class="text-muted">-</em>';

                // 💰 Nominal
                $nominal = 'Rp ' . number_format($r['amount'], 0, ',', '.');

                // 📁 Bukti Pembayaran
                $bukti = $r['bukti_path']
                  ? '<a href="../uploads/' . htmlspecialchars($r['bukti_path']) . '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye"></i> Lihat</a>'
                  : '<em class="text-muted">Belum upload</em>';
              ?>

              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                <td class="text-center"><?= ucfirst($r['tipe_booking']) ?></td>
                <td class="text-center"><?= ucfirst($r['tipe']) ?></td>
                <td class="text-end"><?= $nominal ?></td>
                <td class="text-center"><?= $metode ?></td>
                <td class="text-center"><?= $bukti ?></td>
                <td class="text-center">
                  <span class="badge <?= $badge ?>"><i class="fas <?= $icon ?> me-1"></i><?= $label ?></span>
                </td>
                <td class="text-center">
                  <?php if ($status === 'menunggu'): ?>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=valid"
                       class="btn btn-success btn-sm" 
                       onclick="return confirm('Validasi pembayaran ini sebagai sah?')">
                       <i class="fas fa-check"></i> Valid
                    </a>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=tolak"
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('Tolak pembayaran ini?')">
                       <i class="fas fa-times"></i> Tolak
                    </a>
                  <?php else: ?>
                    <em class="text-muted">-</em>
                  <?php endif; ?>
                </td>
              </tr>

              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
