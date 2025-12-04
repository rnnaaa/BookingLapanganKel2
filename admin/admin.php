<?php
require_once 'auth_check.php';
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
      <h1><i class="fas fa-user-shield mr-2"></i> Data Administrator Sistem</h1>
      <a href="admin_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Admin Baru
      </a>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Daftar Administrator Aktif</h3>
        </div>

        <div class="card-body table-responsive">
          <table id="tblAdmin" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>No. HP</th>
                <th>Role</th>
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;

              $sql = "
                SELECT 
                  id_user,
                  nama,
                  email,
                  no_hp,
                  role,
                  created_at
                FROM users
                WHERE role = 'admin'
                ORDER BY id_user DESC
              ";

              $result = mysqli_query($conn, $sql);

              if (!$result) {
                echo "<tr><td colspan='7' class='text-center text-danger'>Query Error: " . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
              } else {
                while ($row = mysqli_fetch_assoc($result)):
                  $roleBadge = '<span class="badge bg-danger">Admin</span>';
              ?>
              <tr id="user-<?= (int)$row['id_user'] ?>">
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                <td class="text-center"><?= $roleBadge ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="admin_edit.php?id=<?= (int)$row['id_user'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                  <button class="btn btn-sm btn-danger btn-delete" data-id="<?= (int)$row['id_user'] ?>" title="Hapus"><i class="fas fa-trash"></i></button>
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
  // 1. Konfigurasi Toast (Notifikasi Pojok Kanan Atas)
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

  // 2. Tampilkan Notifikasi jika ada Session dari users_hapus.php
  <?php if (isset($_SESSION['alert'])): ?>
    Toast.fire({
      icon: '<?= $_SESSION['alert']['type'] ?>',
      title: '<?= $_SESSION['alert']['message'] ?>'
    });
    <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>

  // 3. Logika Tombol Hapus Admin
  $('.btn-delete').click(function(e){
    e.preventDefault();
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data administrator ini akan dihapus permanen!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33', // Merah untuk bahaya
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if(result.isConfirmed){
        // PERUBAHAN UTAMA DI SINI:
        // Kita tambahkan parameter '&redirect=admin_data.php'
        // Sesuaikan 'admin_data.php' dengan nama file halaman ini yang sebenarnya (misal admin.php)
        window.location.href = 'users_hapus.php?id=' + id + '&redirect=admin.php';
      }
    });
  });

});
</script>
