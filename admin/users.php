<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-users mr-2"></i> Data Pengguna Sistem</h1>
      <a href="users_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Pengguna Baru
      </a>
    </div>
  </section>

  <!-- KONTEN UTAMA -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-list mr-2"></i> Daftar Pengguna Terdaftar</h3>
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
                <th>Total Booking</th>
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
                  COALESCE(m.status, 'belum_member') AS status_member,
                  (SELECT COUNT(*) FROM booking b WHERE b.id_user = u.id_user) AS total_booking,
                  (SELECT SUM(p.amount) FROM pembayaran p 
                    JOIN booking b ON p.booking_id = b.id_booking
                    WHERE b.id_user = u.id_user AND p.status_verifikasi = 'valid') AS total_pembayaran
                FROM users u
                LEFT JOIN member m ON m.id_user = u.id_user
                ORDER BY u.id_user DESC
              ";
              $result = mysqli_query($conn, $sql);

              while ($row = mysqli_fetch_assoc($result)):
                $role = $row['role'] == 'admin' 
                        ? '<span class="badge bg-danger">Admin</span>' 
                        : '<span class="badge bg-secondary">User</span>';

                // Badge member
                switch (strtolower($row['status_member'])) {
                  case 'aktif':
                    $memberBadge = '<span class="badge bg-success">Aktif</span>';
                    break;
                  case 'nonaktif':
                    $memberBadge = '<span class="badge bg-secondary">Nonaktif</span>';
                    break;
                  default:
                    $memberBadge = '<span class="badge bg-light text-muted">Belum Member</span>';
                }

                $totalPembayaran = $row['total_pembayaran'] ? number_format($row['total_pembayaran'], 0, ',', '.') : '0';
              ?>
              <tr id="user-<?= $row['id_user'] ?>">
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                <td class="text-center">
                  <select class="form-select form-select-sm role-select" data-id="<?= $row['id_user'] ?>">
                    <option value="user" <?= $row['role']=='user'?'selected':'' ?>>User</option>
                    <option value="admin" <?= $row['role']=='admin'?'selected':'' ?>>Admin</option>
                  </select>
                </td>
                <td class="text-center"><?= $memberBadge ?></td>
                <td class="text-center"><span class="badge bg-primary"><?= $row['total_booking'] ?></span></td>
                <td class="text-center">Rp <?= $totalPembayaran ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="users_edit.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                  <a href="users_hapus.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger btn-delete" title="Hapus"><i class="fas fa-trash"></i></a>
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

<!-- SweetAlert & AJAX Role Update -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
  // Hapus konfirmasi
  $('.btn-delete').on('click', function (e) {
    e.preventDefault();
    const url = $(this).attr('href');
    Swal.fire({
      title: 'Yakin ingin menghapus pengguna ini?',
      text: 'Data yang dihapus tidak dapat dikembalikan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    });
  });

  // Ganti Role via AJAX
  $('.role-select').change(function () {
    const id = $(this).data('id');
    const role = $(this).val();
    $.ajax({
      url: 'users_update_role.php',
      type: 'POST',
      data: { id_user: id, role: role },
      success: function () {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Role pengguna diperbarui.',
          timer: 1500,
          showConfirmButton: false
        });
      },
      error: function () {
        Swal.fire({
          icon: 'error',
          title: 'Gagal!',
          text: 'Terjadi kesalahan saat memperbarui role.'
        });
      }
    });
  });
});
</script>
