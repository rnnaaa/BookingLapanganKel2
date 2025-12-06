<?php
// File: pengeluaran.php
require_once 'auth_check.php';

// Hapus notifikasi error sisa (Ghost Error)
if (isset($_SESSION['error']) && (stripos($_SESSION['error'], 'habis') !== false || stripos($_SESSION['error'], 'login') !== false)) {
    unset($_SESSION['error']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// =================================================================================
// 1. LOGIKA FILTER & DATE RANGE
// =================================================================================
$filter_start = $_GET['start_date'] ?? date('Y-m-01'); // Default: Awal bulan ini
$filter_end   = $_GET['end_date']   ?? date('Y-m-t');  // Default: Akhir bulan ini

// Klausa WHERE untuk filter tanggal
$whereClause = "WHERE p.tanggal BETWEEN '$filter_start' AND '$filter_end'";

// =================================================================================
// 2. QUERY DATA
// =================================================================================

// A. Hitung Total Pengeluaran (Sesuai Filter)
$qTotal = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM pengeluaran p $whereClause");
$rowTotal = mysqli_fetch_assoc($qTotal);
$totalPengeluaran = $rowTotal['total'] ?? 0;

// B. Ambil Data Pengeluaran (Sesuai Filter)
$queryStr = "
    SELECT 
        p.*, 
        u_input.nama AS input_by_nama,
        u_update.nama AS updated_by_nama
    FROM pengeluaran p
    LEFT JOIN users u_input ON p.input_by = u_input.id_user
    LEFT JOIN users u_update ON p.updated_by = u_update.id_user
    $whereClause
    ORDER BY p.tanggal DESC, p.created_at DESC
";
$qData = mysqli_query($conn, $queryStr);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">

    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><i class="fas fa-wallet mr-2"></i> Data Pengeluaran</h1>
                <p class="text-muted mb-0">Periode: <?= date('d M Y', strtotime($filter_start)) ?> s/d <?= date('d M Y', strtotime($filter_end)) ?></p>
            </div>
            
            <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambah">
                <i class="fas fa-plus-circle"></i> Tambah Baru
            </button>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="collapse mb-4" id="formTambah">
                <div class="card card-primary shadow-lg border-0">
                    <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
                        <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Input Pengeluaran</h3>
                    </div>
                    <form method="POST" action="pengeluaran_tambah.php">
                        <div class="card-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Kategori</label>
                                <input type="text" name="kategori" class="form-control" placeholder="Contoh: Listrik, Perbaikan" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Jumlah (Rp)</label>
                                <input type="number" step="0.01" name="jumlah" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Keterangan</label>
                                <textarea name="keterangan" class="form-control" placeholder="Opsional" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="card-footer text-end bg-light">
                            <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i> Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-lg border-0">
                
                <div class="card-header bg-white p-4 border-bottom">
                    <form method="GET" action="pengeluaran.php" class="row g-3 align-items-end">
                        
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1 fw-bold">Dari Tanggal</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-calendar-alt small"></i></span>
                                <input type="date" name="start_date" class="form-control" value="<?= $filter_start ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1 fw-bold">Sampai Tanggal</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-calendar-alt small"></i></span>
                                <input type="date" name="end_date" class="form-control" value="<?= $filter_end ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fas fa-filter me-1"></i> Terapkan
                                </button>
                                <a href="pengeluaran.php" class="btn btn-outline-secondary btn-sm" title="Reset ke Bulan Ini">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-3 mt-3 mt-md-0">
                            <div class="p-2 rounded-3 text-end border" 
                                 style="background-color: #fff5f5; border-color: #ffcdd2 !important; color: #c62828;">
                                <small class="d-block fw-bold opacity-75" style="font-size: 0.75rem;">TOTAL PENGELUARAN</small>
                                <h5 class="mb-0 fw-bolder">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></h5>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="card-body table-responsive p-0">
                    <table id="tblPengeluaran" class="table table-striped table-hover align-middle w-100 mb-0 border-top-0">
                        <thead class="bg-light text-center text-nowrap">
                            <tr>
                                <th style="width: 5%">No</th>
                                <th style="width: 15%">Tanggal</th>
                                <th style="width: 15%">Kategori</th>
                                <th style="width: 25%">Keterangan</th>
                                <th style="width: 15%">Jumlah</th>
                                <th style="width: 15%">Log Audit</th> 
                                <th style="width: 10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $no=1; 
                        while($p=mysqli_fetch_assoc($qData)): 
                            // Tentukan nama dan waktu untuk kolom Log Audit
                            $log_nama = htmlspecialchars($p['updated_by_nama'] ?? $p['input_by_nama']);
                            $log_waktu_raw = $p['updated_at'] ?? $p['created_at']; 
                            $log_waktu = $log_waktu_raw ? date('d/m H:i', strtotime($log_waktu_raw)) : '';
                        ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++ ?></td>
                                <td class="text-center">
                                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border px-3 py-2 rounded-pill">
                                        <?= htmlspecialchars($p['kategori']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(!empty($p['keterangan'])): ?>
                                        <?= htmlspecialchars($p['keterangan']) ?>
                                    <?php else: ?>
                                        <span class="text-muted font-italic small">- Tidak ada keterangan -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-danger">Rp <?= number_format($p['jumlah'],0,',','.') ?></td>
                                
                                <td class="text-center">
                                    <?php if($log_waktu): ?>
                                        <i class="fas fa-pencil-alt me-1" style="color: #0e5c91;"></i>
                                        <span class="d-inline-block fw-bold"><?= $log_nama ?></span>
                                        <br>
                                        <span class="ms-3 text-muted" style="font-size: 0.85em;"><?= $log_waktu ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <a href="pengeluaran_edit.php?id=<?= $p['id_pengeluaran'] ?>"
                                       class="btn btn-sm text-white shadow-sm" 
                                       title="Edit Data"
                                       style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
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
    </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(document).ready(function() {
    
    // Inisialisasi DataTables
    if (!$.fn.DataTable.isDataTable('#tblPengeluaran')) {
        $('#tblPengeluaran').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [], // Matikan default sort JS agar ikut sort PHP
            "language": {
                "emptyTable": "<div class='py-4 text-muted'><i class='fas fa-file-invoice-dollar fa-3x mb-3'></i><br>Tidak ada data pengeluaran pada periode ini.</div>",
                "zeroRecords": "Data tidak ditemukan yang cocok dengan pencarian."
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });
    }

    // Notifikasi Toastr
    <?php if (isset($_SESSION['toast_success'])): ?>
        toastr.success("<?= $_SESSION['toast_success'] ?>", "Berhasil!");
        <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['toast_error'])): ?>
        toastr.error("<?= $_SESSION['toast_error'] ?>", "Gagal!");
        <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>
});
</script>