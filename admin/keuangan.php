<?php
// keuangan.php 
require_once 'auth_check.php';

// Hapus pesan error sisa (misal: "Sesi habis") agar tidak muncul notifikasi merah palsu.
if (isset($_SESSION['error']) && (stripos($_SESSION['error'], 'habis') !== false || stripos($_SESSION['error'], 'login') !== false)) {
    unset($_SESSION['error']);
}

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta'); // Pastikan timezone diset

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

// B. Total Transaksi (Pemasukan + Pengeluaran)
$qCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM keuangan WHERE $whereDate");
$totalTransaksi = mysqli_fetch_assoc($qCount)['total'] ?? 0;

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Blue Gradient Theme */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --secondary-gradient: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --card-shadow: 0 4px 20px rgba(14, 92, 145, 0.15);
    --card-hover-shadow: 0 8px 30px rgba(14, 92, 145, 0.25);
}

/* Smooth Animations */
.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Enhanced Card Statistics */
.stats-card {
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    box-shadow: var(--card-shadow);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
}

.stats-card .card-body {
    padding: 1.75rem;
}

.stats-card .icon-wrapper {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.stats-card:hover .icon-wrapper {
    transform: rotate(10deg) scale(1.1);
}

.stats-card h6 {
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.stats-card h4 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.5px;
}

.stats-card small {
    font-size: 0.813rem;
    opacity: 0.9;
}

/* Filter Card Enhancement */
.filter-card {
    border-radius: 16px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.filter-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.filter-card .card-header {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-bottom: 2px solid #e3e6f0;
    padding: 1.5rem;
}

.filter-card .form-label {
    font-size: 0.813rem;
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
}

.filter-card .input-group-text {
    background: linear-gradient(135deg, #f1f3f9 0%, #e9ecf4 100%);
    border: 1px solid #d1d3e2;
    color: #4e73df;
}

.filter-card .form-control,
.filter-card .form-select {
    border: 1px solid #d1d3e2;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.filter-card .btn-primary {
    background: var(--primary-gradient);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.filter-card .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

.filter-card .btn-outline-secondary {
    border-radius: 8px;
    border: 2px solid #d1d3e2;
    color: #5a5c69;
    transition: all 0.3s ease;
}

.filter-card .btn-outline-secondary:hover {
    background: #f8f9fc;
    border-color: #2196f3;
    color: #2196f3;
}

/* Main Table Card */
.main-table-card {
    border-radius: 16px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.main-table-card .card-header {
    background: var(--primary-gradient);
    padding: 1.5rem;
    border: none;
}

.main-table-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

/* Enhanced Table Styling */
#tblKeuangan {
    margin: 0;
}

#tblKeuangan thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblKeuangan thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
}

#tblKeuangan tbody tr {
    transition: all 0.2s ease;
}

#tblKeuangan tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblKeuangan tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f3f9;
}

/* Badge Enhancements */
.badge {
    padding: 0.5rem 0.875rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 8px;
    letter-spacing: 0.3px;
}

.badge.bg-success {
    background: var(--success-gradient) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.badge.bg-primary {
    background: var(--primary-gradient) !important;
}

/* Button Edit Enhancement */
.btn-edit-custom {
    background: var(--primary-gradient);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.813rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-edit-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
    color: white;
}

.btn-edit-custom i {
    font-size: 0.875rem;
}

/* Alert Enhancements */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
}

/* Content Header Enhancement */
.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.content-header .text-muted {
    font-size: 0.938rem;
    color: #6c757d !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .stats-card h4 {
        font-size: 1.5rem;
    }
    
    .filter-card .card-header {
        padding: 1rem;
    }
    
    #tblKeuangan tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
      <div>
          <h1><i class="fas fa-chart-line me-2"></i> Laporan Keuangan</h1>
          <p class="text-muted mb-0">Periode: <?= date('d M Y', strtotime($filter_start)) ?> s/d <?= date('d M Y', strtotime($filter_end)) ?></p>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
           <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
           <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Statistics Cards -->
      <div class="row g-4 mb-4 justify-content-center">
          <div class="col-md-6 col-sm-6">
              <div class="card stats-card text-white h-100" 
                   style="background: var(--success-gradient);">
                  <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                          <div>
                              <h6 class="mb-0">Total Pemasukan</h6>
                          </div>
                          <div class="icon-wrapper">
                              <i class="fas fa-arrow-up fa-lg"></i>
                          </div>
                      </div>
                      <h4 class="fw-bold mb-1">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></h4>
                      <small>Periode yang dipilih</small>
                  </div>
              </div>
          </div>

          <div class="col-md-6 col-sm-6">
              <div class="card stats-card text-white h-100" 
                   style="background: var(--secondary-gradient);">
                  <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                          <div>
                              <h6 class="mb-0">Total Transaksi</h6>
                          </div>
                          <div class="icon-wrapper">
                              <i class="fas fa-list-alt fa-lg"></i>
                          </div>
                      </div>
                      <h4 class="fw-bold mb-1"><?= $totalTransaksi ?></h4>
                      <small>Item Data Tercatat</small>
                  </div>
              </div>
          </div>
      </div>

      <!-- Filter Card -->
      <div class="card filter-card mb-4">
        <div class="card-header">
            <form method="GET" action="keuangan.php" class="row g-3 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label">Dari Tanggal</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?= $filter_start ?>">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label">Sampai Tanggal</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?= $filter_end ?>">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label">Jenis Transaksi</label>
                    <select name="jenis" class="form-select">
                        <option value="all" <?= $filter_jenis == 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i> Terapkan
                        </button>
                        <a href="keuangan.php" class="btn btn-outline-secondary" title="Reset ke Bulan Ini">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
      </div>

      <!-- Main Data Table -->
      <div class="card main-table-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-table me-2"></i> Rincian Data Keuangan
          </h3>
        </div>

        <div class="card-body table-responsive p-0">
          <table id="tblKeuangan" class="table table-hover align-middle w-100 mb-0">
              <thead class="text-center text-nowrap">
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

                while ($row = mysqli_fetch_assoc($query)) :
                    $isMasuk = $row['jenis'] === 'pemasukan';
                    $warnaText = $isMasuk ? 'text-success' : 'text-danger';
                    $ikon = $isMasuk ? 'fa-arrow-up' : 'fa-arrow-down';
                    $badgeClass = $isMasuk ? 'bg-success' : 'bg-danger';
                ?>
                <tr>
                  <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>
                  <td class="text-center">
                      <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $badgeClass ?>">
                        <i class="fas <?= $ikon ?> me-1"></i> <?= ucfirst($row['jenis']) ?>
                    </span>
                  </td>
                  <td class="text-center fw-semibold text-dark"><?= htmlspecialchars($row['kategori']) ?></td>
                  <td>
                      <div class="text-wrap" style="max-width: 300px;">
                        <?php if(!empty($row['keterangan'])): ?>
                            <?= htmlspecialchars($row['keterangan']) ?>
                        <?php else: ?>
                            <span class="text-muted fst-italic small">- Tidak ada keterangan -</span>
                        <?php endif; ?>
                        <?php if(!empty($row['booking_id'])): ?>
                            <a href="booking_detail.php?id=<?= $row['booking_id'] ?>" class="badge bg-primary text-decoration-none ms-1">
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
                       class="btn-edit-custom" 
                       title="Edit Data">
                       <i class="fas fa-edit"></i> Edit
                    </a>
                  </td>
                </tr>
                <?php endwhile; ?>
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
$(document).ready(function() {
    // Auto hide alert with fade effect
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>