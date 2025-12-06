<?php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');
?>

<style>
:root {
    --primary-blue: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    --success-blue: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%);
    --info-blue: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
    --danger-red: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.header-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(14, 92, 145, 0.15);
    margin-bottom: 1.5rem;
    border-left: 4px solid #2196f3;
}

.page-title {
    font-weight: 700;
    color: #0d47a1;
    margin: 0;
}

.page-title i {
    color: #2196f3;
}

.users-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(14, 92, 145, 0.15);
    border: none;
}

.card-header-blue {
    background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    padding: 1.5rem;
    border: none;
}

#tblUsers {
    margin-bottom: 0;
    font-size: 0.85rem;
}

#tblUsers thead th {
    background: #e3f2fd;
    color: #0d47a1;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #2196f3;
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    white-space: nowrap;
}

#tblUsers tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3f2fd;
}

#tblUsers tbody tr:hover {
    background: #e3f2fd;
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

#tblUsers tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

.user-name {
    font-weight: 600;
    color: #0d47a1;
}

.badge {
    padding: 0.4rem 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    font-size: 0.75rem;
}

.badge.bg-primary {
    background: var(--primary-blue) !important;
}

.badge.bg-success {
    background: var(--success-blue) !important;
}

.badge.bg-info {
    background: var(--info-blue) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #607d8b 0%, #78909c 100%) !important;
}

.badge.bg-light {
    background: #f5f5f5 !important;
    color: #757575 !important;
}

.financial-amount {
    font-weight: 700;
    color: #0e5c91;
    font-size: 0.9rem;
}

.btn-delete {
    background: var(--danger-red);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(211, 47, 47, 0.3);
    color: white;
}

.stats-badge {
    display: inline-block;
    min-width: 35px;
    text-align: center;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #546e7a;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: #2196f3;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .header-section {
        padding: 1rem;
    }
    
    #tblUsers {
        font-size: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="header-section">
        <div class="mb-3 mb-md-0">
          <h1 class="page-title">
            <i class="fas fa-users me-2"></i> Data Pengguna
          </h1>
          <p class="text-muted mb-0 mt-2">
            <i class="fas fa-user-friends me-2"></i>
            Kelola data pengguna dan member sistem
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card users-table-card">
        <div class="card-header card-header-blue text-white">
          <h5 class="mb-0">
            <i class="fas fa-list me-2"></i> Daftar Pengguna
          </h5>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblUsers" class="table table-hover align-middle">
              <thead class="text-center">
                <tr>
                  <th style="width: 3%">No</th>
                  <th style="width: 12%">Nama</th>
                  <th style="width: 15%">Email</th>
                  <th style="width: 8%">No. HP</th>
                  <th style="width: 6%">Role</th>
                  <th style="width: 8%">Status Member</th>
                  <th style="width: 7%">Total Booking</th>
                  <th style="width: 7%">Total Jadwal</th>
                  <th style="width: 12%">Total Pembayaran</th>
                  <th style="width: 10%">Tgl Daftar</th>
                  <th style="width: 7%">Aksi</th>
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
                  echo "<tr><td colspan='11' class='text-center text-danger py-4'>Query Error: " . mysqli_error($conn) . "</td></tr>";
                } elseif (mysqli_num_rows($result) == 0) {
                  echo '<tr><td colspan="11" class="text-center">
                          <div class="empty-state">
                            <i class="fas fa-users d-block"></i>
                            <h5 class="mb-2" style="color: #0d47a1;">Belum Ada Pengguna</h5>
                            <p class="text-muted">Pengguna akan muncul setelah melakukan registrasi</p>
                          </div>
                        </td></tr>';
                } else {
                  while ($row = mysqli_fetch_assoc($result)):

                    // Badge Role
                    $roleBadge = ($row['role'] == 'member')
                      ? '<span class="badge bg-info">Member</span>'
                      : '<span class="badge bg-secondary">User</span>';

                    // No HP Badge
                    $nohp_raw = $row['no_hp'] ?? '';
                    if ($nohp_raw === null || $nohp_raw === '' || $nohp_raw === '-') {
                      $nohp_badge = '<span class="badge bg-secondary">-</span>';
                    } else {
                      $nohp_badge = '<i class="fas fa-phone text-success me-1"></i>' . htmlspecialchars($nohp_raw);
                    }

                    // Badge Status Member
                    $statusMember = strtolower($row['status_member']);
                    if ($statusMember == 'aktif') {
                      $memberBadge = '<span class="badge bg-success">Aktif</span>';
                    } elseif ($statusMember == 'nonaktif') {
                      $memberBadge = '<span class="badge bg-secondary">Nonaktif</span>';
                    } else {
                      $memberBadge = '<span class="badge bg-light">Belum Member</span>';
                    }

                    // Format numbers
                    $totalBookingUser = (int)$row['total_booking_user'];
                    $totalJadwalMember = (int)$row['total_jadwal_member'];
                    $totalPembayaran = (float)$row['total_pembayaran'];
                ?>

                  <tr id="user-<?= $row['id_user'] ?>">
                    <td class="text-center fw-semibold"><?= $no++ ?></td>

                    <!-- Nama -->
                    <td>
                      <div class="user-name"><?= htmlspecialchars($row['nama'] ?? '-') ?></div>
                      <small class="text-muted">ID: <?= $row['id_user'] ?></small>
                    </td>

                    <!-- Email -->
                    <td>
                      <i class="fas fa-envelope text-primary me-1"></i>
                      <small><?= htmlspecialchars($row['email'] ?? '-') ?></small>
                    </td>

                    <!-- No HP -->
                    <td class="text-center small"><?= $nohp_badge ?></td>

                    <!-- Role -->
                    <td class="text-center"><?= $roleBadge ?></td>

                    <!-- Status Member -->
                    <td class="text-center"><?= $memberBadge ?></td>

                    <!-- Total Booking -->
                    <td class="text-center">
                      <span class="badge bg-primary stats-badge"><?= $totalBookingUser ?></span>
                    </td>

                    <!-- Total Jadwal -->
                    <td class="text-center">
                      <span class="badge bg-success stats-badge"><?= $totalJadwalMember ?></span>
                    </td>

                    <!-- Total Pembayaran -->
                    <td class="text-end">
                      <div class="financial-amount">
                        Rp <?= number_format($totalPembayaran, 0, ',', '.') ?>
                      </div>
                    </td>

                    <!-- Tgl Daftar -->
                    <td class="text-center small">
                      <i class="far fa-calendar-alt text-primary me-1"></i>
                      <?= !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : '-' ?>
                    </td>

                    <td class="text-center">
                      <button class="btn btn-delete btn-sm" 
                              data-id="<?= $row['id_user'] ?>" 
                              title="Hapus">
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
    </div>
  </section>
</div>

<?php include_once('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(function() {
    // Toast Configuration
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    // Show notification if exists
    <?php if (isset($_SESSION['alert'])): ?>
      Toast.fire({
        icon: '<?= $_SESSION['alert']['type'] ?>',
        title: '<?= $_SESSION['alert']['message'] ?>'
      });
      <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    // Delete confirmation
    $('.btn-delete').click(function(e) {
      e.preventDefault();
      const id = $(this).data('id');
      
      Swal.fire({
        title: 'Konfirmasi Hapus',
        html: 'Yakin ingin menghapus pengguna ini?<br><small class="text-muted">Data beserta riwayatnya akan dihapus permanen!</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d32f2f',
        cancelButtonColor: '#2196f3',
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: {
          popup: 'border-0 shadow-lg',
          confirmButton: 'btn-lg',
          cancelButton: 'btn-lg'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'users_hapus.php?id=' + id;
        }
      });
    });

    // DataTable initialization
    // $('#tblUsers').DataTable({
    //   responsive: true,
    //   language: {
    //     url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
    //   },
    //   pageLength: 10,
    //   order: [[0, 'desc']],
    //   columnDefs: [
    //     { orderable: false, targets: [10] } // Disable sorting for action column
    //   ]
    // });
  });
</script>