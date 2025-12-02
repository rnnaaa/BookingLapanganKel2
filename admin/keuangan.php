<?php
// keuangan.php - FINAL: DASHBOARD SERAGAM & TANPA CARD PENGELUARAN
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =================================================================================
// 1. LOGIKA FILTER & DATE RANGE
// =================================================================================
$filter_start = $_GET['start_date'] ?? date('Y-m-01'); // Default: Awal bulan ini
$filter_end   = $_GET['end_date']   ?? date('Y-m-t');  // Default: Akhir bulan ini
$filter_jenis = $_GET['jenis']      ?? 'all';          // Default: Semua jenis

// Buat klausa WHERE dasar untuk tanggal
$whereDate = "tanggal BETWEEN '$filter_start' AND '$filter_end'";

// =================================================================================
// 2. HITUNG STATISTIK DASHBOARD
// =================================================================================

// A. Total Pemasukan
$qMasuk = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM keuangan WHERE jenis='pemasukan' AND $whereDate");
$totalMasuk = mysqli_fetch_assoc($qMasuk)['total'] ?? 0;

// B. Total Pengeluaran (Tetap dihitung untuk kalkulasi laba bersih, tapi card-nya tidak ditampilkan)
$qKeluar = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM keuangan WHERE jenis='pengeluaran' AND $whereDate");
$totalKeluar = mysqli_fetch_assoc($qKeluar)['total'] ?? 0;

// C. Saldo / Keuntungan Bersih
$saldoBersih = $totalMasuk - $totalKeluar;

// D. Total Transaksi
$qCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM keuangan WHERE $whereDate");
$totalTransaksi = mysqli_fetch_assoc($qCount)['total'] ?? 0;

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
          <h1><i class="fas fa-chart-line mr-2"></i> Laporan Keuangan</h1>
          <p class="text-muted mb-0">Periode: <?= date('d M Y', strtotime($filter_start)) ?> s/d <?= date('d M Y', strtotime($filter_end)) ?></p>
      </div>
      <!-- <a href="keuangan_tambah.php" class="btn text-white shadow-sm" style="background: linear-gradient(90deg, #0e5c91, #2196f3); border:none;">
        <i class="fas fa-plus-circle"></i> Catat Transaksi
      </a> -->
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
           <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
           <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row g-3 mb-4">
          <div class="col-md-4 col-sm-6">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(90deg, #0e5c91, #2196f3); color: white;">
                  <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0 opacity-75">Pemasukan</h6>
                          <i class="fas fa-arrow-up bg-white bg-opacity-25 rounded-circle p-2"></i>
                      </div>
                      <h4 class="fw-bold mb-0">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></h4>
                      <small class="opacity-75">Periode ini</small>
                  </div>
              </div>
          </div>

          <div class="col-md-4 col-sm-6">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(90deg, #0e5c91, #2196f3); color: white;">
                  <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0 opacity-75">Keuntungan Bersih</h6>
                          <i class="fas fa-wallet bg-white bg-opacity-25 rounded-circle p-2"></i>
                      </div>
                      <h4 class="fw-bold mb-0">Rp <?= number_format($saldoBersih, 0, ',', '.') ?></h4>
                      <small class="opacity-75">Masuk - Keluar</small>
                  </div>
              </div>
          </div>

          <div class="col-md-4 col-sm-12">
              <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(90deg, #0e5c91, #2196f3); color: white;">
                  <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0 opacity-75">Total Transaksi</h6>
                          <i class="fas fa-list bg-white bg-opacity-25 rounded-circle p-2"></i>
                      </div>
                      <h4 class="fw-bold mb-0"><?= $totalTransaksi ?></h4>
                      <small class="opacity-75">Item Data</small>
                  </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="keuangan.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $filter_start ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $filter_end ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Jenis Transaksi</label>
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="all" <?= $filter_jenis == 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan </option>
                        <!-- <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran Saja</option> -->
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                </div>
            </form>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);">
          <h3 class="card-title mb-0">
            <i class="fas fa-table mr-2"></i> Rincian Data Keuangan
          </h3>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="example1" class="table table-striped table-hover align-middle mb-0">
              <thead class="bg-light text-center text-nowrap">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 12%">Tanggal</th>
                  <th style="width: 10%">Jenis</th>
                  <th style="width: 15%">Kategori</th>
                  <th style="width: 25%">Keterangan</th>
                  <th style="width: 15%">Jumlah</th>
                  <th style="width: 10%">Log Audit</th>
                  <th style="width: 8%">Aksi</th>
                </tr>
              </thead>

              <tbody>
                <?php
                // Query Data Tabel (Mengikuti Filter)
                $queryStr = "SELECT k.*, u.nama AS editor_name 
                             FROM keuangan k 
                             LEFT JOIN users u ON k.updated_by = u.id_user 
                             WHERE $whereDate";

                if ($filter_jenis !== 'all') {
                    $queryStr .= " AND k.jenis = '$filter_jenis'";
                }

                $queryStr .= " ORDER BY k.tanggal DESC, k.created_at DESC";
                
                $query = mysqli_query($conn, $queryStr);
                $no = 1;

                if (mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) :
                      $isMasuk = $row['jenis'] === 'pemasukan';
                      $warnaText = $isMasuk ? 'text-success' : 'text-danger';
                      $ikon = $isMasuk ? 'fa-arrow-up' : 'fa-arrow-down';
                      $badgeClass = $isMasuk ? 'bg-success' : 'bg-danger';
                      // Highlight tipis untuk pengeluaran di tabel agar tetap mudah dibedakan
                      $bgRow = $isMasuk ? '' : 'bg-danger bg-opacity-10'; 
                ?>
                <tr>
                  <td class="text-center text-muted"><?= $no++ ?></td>
                  <td class="text-center">
                      <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $badgeClass ?> bg-opacity-75">
                        <i class="fas <?= $ikon ?> mr-1"></i> <?= ucfirst($row['jenis']) ?>
                    </span>
                  </td>
                  <td class="text-center text-dark"><?= htmlspecialchars($row['kategori']) ?></td>
                  <td>
                      <div class="text-wrap" style="max-width: 300px;">
                        <?= htmlspecialchars($row['keterangan'] ?? '') ?>
                        <?php if(!empty($row['booking_id'])): ?>
                            <a href="booking_detail.php?id=<?= $row['booking_id'] ?>" class="badge bg-primary text-decoration-none ml-1">
                                <i class="fas fa-link"></i> #<?= $row['booking_id'] ?>
                            </a>
                        <?php endif; ?>
                      </div>
                  </td>
                  <td class="text-end fw-bold <?= $warnaText ?>">
                    Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                  </td>
                  
                  <td class="text-center">
                      <?php if ($row['updated_at']): ?>
                          <small class="text-muted d-block" style="font-size: 0.75rem;">
                             <i class="fas fa-pen-alt me-1"></i> <?= htmlspecialchars($row['editor_name'] ?? '-') ?>
                          </small>
                          <small class="text-muted" style="font-size: 0.7rem;">
                             <?= date('d/m H:i', strtotime($row['updated_at'])) ?>
                          </small>
                      <?php else: ?>
                          <span class="text-muted small">-</span>
                      <?php endif; ?>
                  </td>

                  <td class="text-center">
                    <a href="keuangan_edit.php?id=<?= $row['id_keuangan'] ?>" 
                       class="btn btn-sm text-white shadow-sm" 
                       title="Edit Data"
                       style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"> 
                       <i class="fas fa-edit"></i> Edit
                    </a>
                  </td>
                </tr>
                <?php 
                    endwhile; 
                } else {
                    echo '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-search fa-2x mb-3"></i><br>Tidak ada data transaksi pada periode ini.</td></tr>';
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Auto hide alert
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>