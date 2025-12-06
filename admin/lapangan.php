<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
:root {
    --primary-blue: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    --success-blue: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%);
    --info-blue: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
    --light-blue: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.header-card {
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

.btn-add {
    background: var(--primary-blue);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
    color: white;
}

.table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(14, 92, 145, 0.15);
    border: none;
}

.card-header-gradient {
    background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    padding: 1.5rem;
    border: none;
}

#tblLapangan {
    margin-bottom: 0;
    font-size: 0.9rem;
}

#tblLapangan thead th {
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

#tblLapangan tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3f2fd;
}

#tblLapangan tbody tr:hover {
    background: #e3f2fd;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

#tblLapangan tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.field-photo {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid #2196f3;
}

.field-photo:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
}

.field-name {
    font-weight: 600;
    color: #0d47a1;
    font-size: 1rem;
}

.field-type {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: var(--light-blue);
    color: white;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.price-regular {
    font-weight: 700;
    color: #0d47a1;
    font-size: 1rem;
}

.price-member {
    font-weight: 700;
    color: #1976d2;
    font-size: 1rem;
}

.badge {
    padding: 0.5rem 1rem;
    font-weight: 600;
    border-radius: 6px;
    font-size: 0.8rem;
}

.badge.bg-success {
    background: var(--success-blue) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #607d8b 0%, #78909c 100%) !important;
}

.description-text {
    color: #546e7a;
    font-size: 0.875rem;
    line-height: 1.5;
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
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-edit {
    background: var(--info-blue);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
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
    
    .header-card {
        padding: 1rem;
    }
    
    #tblLapangan {
        font-size: 0.8rem;
    }
    
    .field-photo {
        width: 60px;
        height: 60px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}

/* Image Preview Modal */
.modal-photo .modal-content {
    background: transparent;
    border: none;
}

.modal-photo .modal-body {
    padding: 0;
}

.modal-photo img {
    width: 100%;
    border-radius: 12px;
    border: 3px solid #2196f3;
}
</style>

<div class="content-wrapper">
  <!-- Header -->
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="header-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div class="mb-3 mb-md-0">
            <h1 class="page-title">
              <i class="fas fa-futbol me-2"></i> Data Lapangan
            </h1>
            <p class="text-muted mb-0 mt-2">
              <i class="fas fa-layer-group me-2"></i>
              Kelola informasi lapangan badminton
            </p>
          </div>
          <a href="lapangan_tambah.php" class="btn btn-add">
            <i class="fas fa-plus-circle me-2"></i> Tambah Lapangan
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card table-card">
        <div class="card-header card-header-gradient text-white">
          <h5 class="mb-0">
            <i class="fas fa-table me-2"></i> Daftar Lapangan Badminton
          </h5>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblLapangan" class="table table-hover align-middle">
              <thead class="text-center">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 10%">Foto</th>
                  <th style="width: 15%">Nama Lapangan</th>
                  <th style="width: 10%">Tipe</th>
                  <th style="width: 12%">Harga / Jam</th>
                  <th style="width: 12%">Harga Member</th>
                  <th style="width: 25%">Deskripsi</th>
                  <th style="width: 8%">Status</th>
                  <th style="width: 12%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY id_lapangan DESC");

                if(mysqli_num_rows($query) == 0):
                ?>
                  <tr>
                    <td colspan="9" class="text-center">
                      <div class="empty-state">
                        <i class="fas fa-inbox d-block"></i>
                        <h5 class="mb-2" style="color: #0d47a1;">Belum Ada Data Lapangan</h5>
                        <p class="text-muted mb-3">Mulai tambahkan lapangan badminton untuk mengelola booking</p>
                        <a href="lapangan_tambah.php" class="btn btn-add">
                          <i class="fas fa-plus me-2"></i> Tambah Lapangan Pertama
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php
                else:
                  while ($r = mysqli_fetch_assoc($query)):
                    $fotoPath = !empty($r['foto']) ? "../uploads/lapangan/" . htmlspecialchars($r['foto']) : "../assets/img/no-image.png";
                    $statusBadge = ($r['status'] === 'aktif')
                      ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Aktif</span>'
                      : '<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Nonaktif</span>';
                ?>
                  <tr>
                    <td class="text-center fw-semibold"><?= $no++ ?></td>
                    <td class="text-center">
                      <img src="<?= $fotoPath ?>" 
                           alt="Foto Lapangan"
                           class="field-photo"
                           onclick="showPhotoModal('<?= $fotoPath ?>', '<?= htmlspecialchars($r['nama_lapangan']) ?>')">
                    </td>
                    <td>
                      <div class="field-name"><?= htmlspecialchars($r['nama_lapangan']) ?></div>
                      <small class="text-muted">ID: <?= $r['id_lapangan'] ?></small>
                    </td>
                    <td class="text-center">
                      <span class="field-type"><?= htmlspecialchars($r['tipe']) ?></span>
                    </td>
                    <td class="text-end">
                      <div class="price-regular">
                        Rp <?= number_format($r['harga_per_jam'], 0, ',', '.') ?>
                      </div>
                      <small class="text-muted">Regular</small>
                    </td>
                    <td class="text-end">
                      <div class="price-member">
                        Rp <?= number_format($r['harga_per_jam_member'], 0, ',', '.') ?>
                      </div>
                      <small class="text-muted">Member</small>
                    </td>
                    <td>
                      <div class="description-text">
                        <?= !empty($r['deskripsi'])
                            ? htmlspecialchars(strlen($r['deskripsi']) > 80
                                ? substr($r['deskripsi'], 0, 80) . '...'
                                : $r['deskripsi'])
                            : '<em class="text-muted">Tidak ada deskripsi</em>' ?>
                      </div>
                    </td>
                    <td class="text-center"><?= $statusBadge ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="lapangan_edit.php?id=<?= $r['id_lapangan'] ?>"
                           class="btn btn-action btn-edit btn-sm"
                           title="Edit Data">
                          <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="javascript:void(0)"
                           class="btn btn-action btn-delete btn-sm"
                           onclick="confirmDelete(<?= $r['id_lapangan'] ?>, '<?= htmlspecialchars($r['nama_lapangan']) ?>')"
                           title="Hapus Data">
                          <i class="fas fa-trash-alt me-1"></i> Hapus
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php 
                  endwhile;
                endif;
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Photo Preview Modal -->
<div class="modal fade modal-photo" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
        <h5 class="modal-title" id="photoModalTitle">
          <i class="fas fa-image me-2"></i> Foto Lapangan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-dark p-0">
        <img id="photoModalImage" src="" class="img-fluid" alt="Foto Lapangan">
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Show photo modal
function showPhotoModal(photoSrc, fieldName) {
    document.getElementById('photoModalImage').src = photoSrc;
    document.getElementById('photoModalTitle').innerHTML = '<i class="fas fa-image me-2"></i> ' + fieldName;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}

// Delete confirmation
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: `Yakin ingin menghapus lapangan <strong>${name}</strong>?<br><small class="text-muted">Data yang terhapus tidak dapat dikembalikan.</small>`,
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
            window.location.href = 'lapangan_hapus.php?id=' + id;
        }
    });
}

// Toast notifications
<?php if(isset($_SESSION['toast_success'])): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?= $_SESSION['toast_success'] ?>',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['toast_error'])): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: '<?= $_SESSION['toast_error'] ?>',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    <?php unset($_SESSION['toast_error']); ?>
<?php endif; ?>


</script>