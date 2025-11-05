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
      <h1><i class="fas fa-calendar-check mr-2"></i> Data Booking Lapangan</h1>
      <a href="booking_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Booking Manual
      </a>
    </div>
  </section>

  <!-- KONTEN UTAMA -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-list mr-2"></i> Daftar Semua Booking (Reguler & Member)
          </h3>
        </div>

        <div class="card-body">
          <table id="tblBooking" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Pemesan</th>
                <th>Tipe Booking</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Total</th>
                <th>Status Booking</th>
                <th>Status Pembayaran</th>
                <th>Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>

            <tbody>
              <?php
              $no = 1;

              // 🔹 Query diperbaiki agar booking member (dari member_jadwal) juga tampil
              $sql = "
                SELECT 
                  b.id_booking,
                  u.nama AS nama_pemesan,
                  COALESCE(b.tipe_booking, 'reguler') AS tipe_booking,
                  l.nama_lapangan,
                  b.tanggal,
                  b.total_amount,
                  b.status,
                  b.payment_status,
                  b.created_at,
                  COALESCE(
                    GROUP_CONCAT(
                      CONCAT(
                        DATE_FORMAT(jw.jam_mulai, '%H:%i'), ' - ', DATE_FORMAT(jw.jam_selesai, '%H:%i')
                      ) ORDER BY jw.jam_mulai SEPARATOR '<br>'
                    ),
                    CONCAT(
                      DATE_FORMAT(b.created_at, '%H:%i'), ' - ', DATE_FORMAT(ADDTIME(b.created_at, '01:00:00'), '%H:%i'))
                  ) AS jam_booking
                FROM booking b
                JOIN users u ON b.id_user = u.id_user
                JOIN lapangan l ON b.id_lapangan = l.id_lapangan
                LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
                LEFT JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
                GROUP BY b.id_booking
                ORDER BY 
                  b.tanggal DESC,
                  (b.tipe_booking = 'member') DESC,
                  b.created_at DESC
              ";

              $result = mysqli_query($conn, $sql);

              while ($row = mysqli_fetch_assoc($result)):
                // 🎨 Badge status booking
                switch ($row['status']) {
                  case 'menunggu': $badgeBooking = 'badge bg-warning text-dark'; break;
                  case 'disetujui': $badgeBooking = 'badge bg-primary'; break;
                  case 'selesai': $badgeBooking = 'badge bg-success'; break;
                  case 'ditolak': $badgeBooking = 'badge bg-danger'; break;
                  default: $badgeBooking = 'badge bg-secondary';
                }

                // 💳 Badge status pembayaran
                switch ($row['payment_status']) {
                  case 'belum_bayar': $badgePay = 'badge bg-secondary'; break;
                  case 'menunggu_verifikasi': $badgePay = 'badge bg-warning text-dark'; break;
                  case 'dp_bayar': $badgePay = 'badge bg-info'; break;
                  case 'lunas': $badgePay = 'badge bg-success'; break;
                  default: $badgePay = 'badge bg-light text-dark';
                }

                // 🏷️ Badge tipe booking
                $badgeTipe = ($row['tipe_booking'] == 'member')
                  ? '<span class="badge bg-success"><i class="fas fa-user-check"></i> Member</span>'
                  : '<span class="badge bg-secondary"><i class="fas fa-user"></i> Reguler</span>';
              ?>

              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_pemesan']) ?></td>
                <td class="text-center"><?= $badgeTipe ?></td>
                <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-center"><?= $row['jam_booking'] ?: '<em>-</em>' ?></td>
                <td class="text-end">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                <td class="text-center"><span class="<?= $badgeBooking ?>"><?= ucfirst($row['status']) ?></span></td>
                <td class="text-center"><span class="<?= $badgePay ?>"><?= ucfirst(str_replace('_', ' ', $row['payment_status'])) ?></span></td>
                <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>

                <td class="text-center">
                  <a href="booking_detail.php?id=<?= $row['id_booking'] ?>" 
                     class="btn btn-sm btn-info" title="Detail Booking">
                    <i class="fas fa-info-circle"></i>
                  </a>

                  <a href="booking_edit.php?id=<?= $row['id_booking'] ?>" 
                     class="btn btn-sm btn-warning" title="Edit Booking">
                    <i class="fas fa-edit"></i>
                  </a>

                  <?php if ($row['status'] === 'menunggu'): ?>
                    <a href="booking_action_controller.php?action=approve&id=<?= $row['id_booking'] ?>" 
                       class="btn btn-sm btn-success" title="Setujui Booking">
                      <i class="fas fa-check"></i>
                    </a>
                    <a href="booking_action_controller.php?action=reject&id=<?= $row['id_booking'] ?>" 
                       class="btn btn-sm btn-danger" title="Tolak Booking">
                      <i class="fas fa-times"></i>
                    </a>
                  <?php endif; ?>

                  <a href="booking_action_controller.php?action=delete&id=<?= $row['id_booking'] ?>" 
                     class="btn btn-sm btn-danger btn-delete" title="Hapus Booking"
                     onclick="return confirm('Yakin ingin menghapus booking ini?')">
                    <i class="fas fa-trash"></i>
                  </a>
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

<!-- 🧩 Inisialisasi DataTable -->

