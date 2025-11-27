<?php
// keuangan.php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-coins mr-2"></i> Manajemen Keuangan</h1>
      
      <a href="keuangan_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Transaksi Manual
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
           <?= $_SESSION['success']; unset($_SESSION['success']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
           <?= $_SESSION['error']; unset($_SESSION['error']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                     box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-list mr-2"></i> Data Transaksi Keuangan
          </h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="example1" class="table table-bordered table-striped table-hover align-middle">
              <thead class="text-center bg-light">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Jenis</th>
                  <th>Kategori</th>
                  <th>Keterangan</th>
                  <th>Jumlah</th>
                  <th>Log Audit</th>
                  <th>Aksi</th>
                </tr>
              </thead>

              <tbody>
                <?php
                $no = 1;
                
                $sql = "SELECT k.*, u.nama AS editor_name 
                        FROM keuangan k 
                        LEFT JOIN users u ON k.updated_by = u.id_user 
                        ORDER BY k.tanggal DESC, k.created_at DESC 
                        LIMIT 500"; 
                
                $query = mysqli_query($conn, $sql);

                if (!$query) {
                    echo "<tr><td colspan='8' class='text-center text-danger'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                } else {
                    while ($row = mysqli_fetch_assoc($query)) :
                      $warna = $row['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger';
                      $ikon = $row['jenis'] === 'pemasukan' ? 'fa-arrow-up' : 'fa-arrow-down';
                      $bg_badge = $row['jenis'] === 'pemasukan' ? 'bg-success' : 'bg-danger';
                ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td class="text-center">
                      <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $bg_badge ?>">
                        <i class="fas <?= $ikon ?> mr-1"></i> <?= ucfirst($row['jenis']) ?>
                    </span>
                  </td>
                  <td class="text-center"><?= htmlspecialchars($row['kategori']) ?></td>
                  <td>
                      <?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?: '-' ?>
                      <?php if(!empty($row['booking_id'])): ?>
                          <br><small class="text-muted"><i class="fas fa-link"></i> Linked to Booking #<?= $row['booking_id'] ?></small>
                      <?php endif; ?>
                  </td>
                  <td class="text-end font-weight-bold <?= $warna ?>">
                    Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                  </td>
                  
                  <td class="text-center text-muted small" style="font-size: 0.85em;">
                      <?php if ($row['updated_at']): ?>
                          <div data-bs-toggle="tooltip" title="Diedit pada <?= $row['updated_at'] ?>">
                              <i class="fas fa-pen-alt icon-gradient-blue"></i> <?= htmlspecialchars($row['editor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?: '-' ?>
                              <br>
                              <?= date('d/m H:i', strtotime($row['updated_at'])) ?>
                          </div>
                      <?php else: ?>
                          -
                      <?php endif; ?>
                  </td>

                  <td class="text-center">
                    <div class="btn-group">
                        <a href="keuangan_edit.php?id=<?= $row['id_keuangan'] ?>" 
                           class="btn btn-sm text-white btn-action-loader" 
                           title="Edit"
                           style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"> 
                            <i class="fas fa-edit"></i> Edit Data
                        </a>
                        
                        </div>
                  </td>
                </tr>
                <?php 
                    endwhile; 
                } 
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<style>
/* 1. CSS untuk menerapkan GRADIENT pada ikon (text color) */
.icon-gradient-blue {
    /* Fallback color */
    color: #0e5c91; 

    /* Apply gradient as text color */
    background: linear-gradient(90deg, #0e5c91, #2196f3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    document.body.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-action-loader");
        if (!btn) return;

        e.preventDefault();

        const targetUrl = btn.getAttribute("href");
        const confirmMsg = btn.getAttribute("data-confirm") || null;

        // Konfirmasi (Hanya akan berpengaruh pada tombol yang masih memiliki data-confirm)
        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        // Simpan isi tombol asli
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        // Disable tombol
        btn.style.pointerEvents = "none";
        btn.style.opacity = "0.6";
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Navigasi stabil
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 300);

        // SAFEGUARD: kalau gagal pindah halaman
        setTimeout(() => {
            btn.style.pointerEvents = "auto";
            btn.style.opacity = "1";
            btn.innerHTML = btn.dataset.originalHtml;
        }, 6000);
    });

    // Auto hide alert
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

});
</script>