<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');
?>

<style>
:root {
    --primary-blue: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    --success-blue: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%);
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

.btn-add-admin {
    background: var(--primary-blue);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
}

.btn-add-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
    color: white;
}

.admin-table-card {
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

#tblAdmin {
    margin-bottom: 0;
    font-size: 0.9rem;
}

#tblAdmin thead th {
    background: #e3f2fd;
    color: #0d47a1;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #2196f3;
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

#tblAdmin tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3f2fd;
}

#tblAdmin tbody tr:hover {
    background: #e3f2fd;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

#tblAdmin tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.admin-name {
    font-weight: 600;
    color: #0d47a1;
}

.badge.bg-danger {
    background: var(--danger-red) !important;
    padding: 0.5rem 1rem;
    font-weight: 600;
    border-radius: 6px;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
}

.btn-action:hover {
    transform: translateY(-2px);
}

.btn-edit {
    background: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
    color: white;
}

.btn-delete {
    background: var(--danger-red);
    color: white;
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
    
    #tblAdmin {
        font-size: 0.8rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="header-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div class="mb-3 mb-md-0">
            <h1 class="page-title">
              <i class="fas fa-user-shield me-2"></i> Data Administrator Sistem
            </h1>
            <p class="text-muted mb-0 mt-2">
              <i class="fas fa-users-cog me-2"></i>
              Kelola akun administrator sistem
            </p>
          </div>
          <a href="admin_tambah.php" class="btn btn-add-admin">
            <i class="fas fa-plus-circle me-2"></i> Tambah Admin Baru
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card admin-table-card">
        <div class="card-header card-header-blue text-white">
          <h5 class="mb-0">
            <i class="fas fa-list me-2"></i> Daftar Administrator Aktif
          </h5>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblAdmin" class="table table-hover align-middle">
              <thead class="text-center">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 20%">Nama</th>
                  <th style="width: 20%">Email</th>
                  <th style="width: 15%">No. HP</th>
                  <th style="width: 10%">Role</th>
                  <th style="width: 15%">Tanggal Daftar</th>
                  <th style="width: 15%">Aksi</th>
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
                  echo "<tr><td colspan='7' class='text-center text-danger py-4'>Query Error: " . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
                } elseif (mysqli_num_rows($result) == 0) {
                  echo '<tr><td colspan="7" class="text-center">
                          <div class="empty-state">
                            <i class="fas fa-user-shield d-block"></i>
                            <h5 class="mb-2" style="color: #0d47a1;">Belum Ada Administrator</h5>
                            <p class="text-muted mb-3">Tambahkan administrator pertama untuk mengelola sistem</p>
                            <a href="admin_tambah.php" class="btn btn-add-admin">
                              <i class="fas fa-plus me-2"></i> Tambah Admin Pertama
                            </a>
                          </div>
                        </td></tr>';
                } else {
                  while ($row = mysqli_fetch_assoc($result)):
                    $roleBadge = '<span class="badge bg-danger">Admin</span>';
                ?>
                <tr id="user-<?= (int)$row['id_user'] ?>">
                  <td class="text-center fw-semibold"><?= $no++ ?></td>
                  <td>
                    <div class="admin-name"><?= htmlspecialchars($row['nama']) ?></div>
                    <small class="text-muted">ID: <?= $row['id_user'] ?></small>
                  </td>
                  <td>
                    <i class="fas fa-envelope text-primary me-2"></i>
                    <?= htmlspecialchars($row['email']) ?>
                  </td>
                  <td class="text-center">
                    <?php if (!empty($row['no_hp'])): ?>
                      <i class="fas fa-phone text-success me-2"></i>
                      <?= htmlspecialchars($row['no_hp']) ?>
                    <?php else: ?>
                      <span class="badge bg-secondary">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><?= $roleBadge ?></td>
                  <td class="text-center">
                    <i class="far fa-calendar-alt text-primary me-2"></i>
                    <?= date('d-m-Y', strtotime($row['created_at'])) ?>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <a href="admin_edit.php?id=<?= (int)$row['id_user'] ?>" 
                         class="btn btn-action btn-edit btn-sm" 
                         title="Edit">
                        <i class="fas fa-edit me-1"></i> Edit
                      </a>
                      <button class="btn btn-action btn-delete btn-sm" 
                              data-id="<?= (int)$row['id_user'] ?>" 
                              title="Hapus">
                        <i class="fas fa-trash me-1"></i> Hapus
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endwhile; } ?>
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
$(function(){
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
  $('.btn-delete').click(function(e){
    e.preventDefault();
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Konfirmasi Hapus',
      html: 'Yakin ingin menghapus administrator ini?<br><small class="text-muted">Data yang terhapus tidak dapat dikembalikan.</small>',
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
      if(result.isConfirmed){
        window.location.href = 'users_hapus.php?id=' + id + '&redirect=admin.php';
      }
    });
  });

  // DataTable initialization
  // $('#tblAdmin').DataTable({
  //   responsive: true,
  //   language: {
  //     url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
  //   },
  //   pageLength: 10,
  //   order: [[0, 'desc']]
  // });
});
</script>