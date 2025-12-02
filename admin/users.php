<?php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-users mr-2"></i> Data Pengguna </h1>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Daftar Pengguna </h3>
        </div>

        <div class="card-body table-responsive">
          <table id="tblUsers" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>No. HP</th>
                <th>Role</th>
                <th>Status Member</th>
                <th>Total Booking (User)</th>
                <th>Total Jadwal (Member)</th>
                <th>Total Pembayaran</th>
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;

              $sql = "
                SELECT 
                  u.id_user,
                  u.nama,
                  u.email,
                  u.no_hp,
                  u.role,
                  u.created_at,

                  COALESCE(
                    (SELECT m.status FROM member m WHERE m.id_user = u.id_user ORDER BY m.id_member DESC LIMIT 1),
                    'belum_member'
                  ) AS status_member,

                  (SELECT COUNT(*) FROM booking b WHERE b.id_user = u.id_user) AS total_booking_user,

                  (SELECT COUNT(*) FROM member_jadwal mj 
                   WHERE mj.id_member IN (SELECT m2.id_member FROM member m2 WHERE m2.id_user = u.id_user)
                  ) AS total_jadwal_member,

                  (
                    CASE 
                      WHEN (
                        SELECT m.status 
                        FROM member m 
                        WHERE m.id_user = u.id_user 
                        ORDER BY m.id_member DESC LIMIT 1
                      ) = 'aktif'
                      THEN (
                        SELECT COALESCE(SUM(m2.total_bayar),0)
                        FROM member m2
                        WHERE m2.id_user = u.id_user
                          AND m2.status = 'aktif'
                      )
                      ELSE (
                        SELECT COALESCE(SUM(p.amount),0)
                        FROM pembayaran p
                        INNER JOIN booking b2 ON b2.id_booking = p.booking_id
                        WHERE b2.id_user = u.id_user
                          AND p.status_verifikasi = 'valid'
                      )
                    END
                  ) AS total_pembayaran

                FROM users u
                WHERE u.role IN ('user', 'member')
                ORDER BY u.id_user DESC
              ";

              $result = mysqli_query($conn, $sql);

              if (!$result) {
                echo "<tr><td colspan='11' class='text-center text-danger'>Query Error: " . mysqli_error($conn) . "</td></tr>";
              } else {
                while ($row = mysqli_fetch_assoc($result)):

                  // Badge Role
                  $roleBadge = ($row['role'] == 'member')
                    ? '<span class="badge bg-info">Member</span>'
                    : '<span class="badge bg-secondary">User</span>';


                  // No HP: jika null / kosong → badge abu-abu
                  $nohp_raw = $row['no_hp'] ?? '';
                  if ($nohp_raw === null || $nohp_raw === '' || $nohp_raw === '-') {
                    $nohp_badge = '<span class="badge bg-secondary">-</span>';
                  } else {
                    $nohp_badge = htmlspecialchars($nohp_raw);
                  }

                  // Badge Status Member
                  $statusMember = strtolower($row['status_member']);
                  if ($statusMember == 'aktif') {
                    $memberBadge = '<span class="badge bg-success">Aktif</span>';
                  } elseif ($statusMember == 'nonaktif') {
                    $memberBadge = '<span class="badge bg-secondary">Nonaktif</span>';
                  } else {
                    $memberBadge = '<span class="badge bg-light text-muted">Belum Member</span>';
                  }

                  // Format angka
                  $totalBookingUser = (int)$row['total_booking_user'];
                  $totalJadwalMember = (int)$row['total_jadwal_member'];
                  $totalPembayaran = (float)$row['total_pembayaran'];

                  // No HP: deteksi null / '-' → buat abu-abu
                  $nohp_raw = $row['no_hp'] ?? '-';
                  $nohp_display = htmlspecialchars($nohp_raw);

                  $nohp_style = ($nohp_raw === null || $nohp_raw === '' || $nohp_raw === '-')
                    ? "background-color:#e0e0e0; text-align:center; font-weight:bold;"
                    : "";
              ?>

                  <tr id="user-<?= $row['id_user'] ?>">
                    <td class="text-center"><?= $no++ ?></td>

                    <!-- Nama -->
                    <td><?= htmlspecialchars($row['nama'] ?? '-') ?></td>

                    <!-- Email -->
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>

                    <!-- No HP -->
                    <td class="text-center"><?= $nohp_badge ?></td>

                    <!-- Role -->
                    <td class="text-center"><?= $roleBadge ?></td>

                    <!-- Status Member -->
                    <td class="text-center"><?= $memberBadge ?></td>

                    <!-- Total Booking -->
                    <td class="text-center">
                      <span class="badge bg-primary"><?= $totalBookingUser ?></span>
                    </td>

                    <!-- Total Jadwal -->
                    <td class="text-center">
                      <span class="badge bg-success"><?= $totalJadwalMember ?></span>
                    </td>

                    <!-- Total Pembayaran -->
                    <td class="text-center fw-bold">
                      Rp <?= number_format($totalPembayaran, 0, ',', '.') ?>
                    </td>

                    <!-- Tgl Daftar -->
                    <td class="text-center">
                      <?= !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : '-' ?>
                    </td>

                    <td class="text-center">
                      <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $row['id_user'] ?>" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>

              <?php endwhile;
              } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include_once('../includes/footer.php'); ?>

<script>
  $(function() {
    $('.btn-delete').click(function() {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data pengguna ini akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'users_hapus.php?id=' + id;
        }
      });
    });
  });
</script>