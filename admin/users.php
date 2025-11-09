<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-users mr-2"></i> Data Pengguna Sistem</h1>
      <a href="users_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Pengguna Baru
      </a>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Daftar Pengguna Terdaftar</h3>
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
              // Query disederhanakan agar MySQL tidak hang
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
    (
      SELECT COUNT(*) 
      FROM booking b 
      WHERE b.id_user = u.id_user
    ) AS total_booking,
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
  ORDER BY u.id_user DESC
";


              $result = mysqli_query($conn, $sql);

              if (!$result) {
                  echo "<tr><td colspan='10' class='text-center text-danger'>Query Error: " . mysqli_error($conn) . "</td></tr>";
              } else {
                while ($row = mysqli_fetch_assoc($result)):
                  // Badge Role
                  if ($row['role'] == 'admin') {
                      $roleBadge = '<span class="badge bg-danger">Admin</span>';
                  } elseif ($row['role'] == 'member') {
                      $roleBadge = '<span class="badge bg-info">Member</span>';
                  } else {
                      $roleBadge = '<span class="badge bg-secondary">User</span>';
                  }

                  // Badge Member
                  $statusMember = strtolower($row['status_member']);
                  if ($statusMember == 'aktif') {
                      $memberBadge = '<span class="badge bg-success">Aktif</span>';
                  } elseif ($statusMember == 'nonaktif') {
                      $memberBadge = '<span class="badge bg-secondary">Nonaktif</span>';
                  } else {
                      $memberBadge = '<span class="badge bg-light text-muted">Belum Member</span>';
                  }

                  $totalPembayaran = number_format($row['total_pembayaran'], 0, ',', '.');
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
                <td class="text-center fw-bold">Rp <?= $totalPembayaran ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="users_edit.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                  <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $row['id_user'] ?>" title="Hapus"><i class="fas fa-trash"></i></button>
                </td>
              </tr>
              <?php endwhile; } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include_once('../includes/footer.php'); ?>

<script>
$(function(){
  // Update Role dengan AJAX
  $('.role-select').change(function(){
    const id_user = $(this).data('id');
    const role = $(this).val();
    Swal.fire({
      title: 'Perbarui Role?',
      text: `Anda yakin ingin mengubah role pengguna ini menjadi "${role}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, perbarui',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if(result.isConfirmed){
        $.ajax({
          url: 'users_update_role.php',
          type: 'POST',
          data: { id_user, role },
          beforeSend: () => Swal.fire({ title:'Menyimpan...', text:'Harap tunggu sebentar', allowOutsideClick:false, didOpen:()=>Swal.showLoading() }),
          success: () => Swal.fire({ icon:'success', title:'Berhasil!', text:'Role berhasil diperbarui.', timer:1500, showConfirmButton:false }),
          error: () => Swal.fire({ icon:'error', title:'Gagal!', text:'Terjadi kesalahan saat memperbarui.' })
        });
      }
    });
  });

  // Hapus User
  $('.btn-delete').click(function(){
    const id = $(this).data('id');
    Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data pengguna ini akan dihapus permanen!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if(result.isConfirmed){
        window.location.href = 'users_hapus.php?id=' + id;
      }
    });
  });
});
</script>
