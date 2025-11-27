<?php
// File: pengeluaran.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// PASTIKAN SESSION SUDAH DIMULAI DI SINI ATAU DI HEADER.PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Ambil data pengeluaran dengan JOIN
$qData = mysqli_query($conn, "
    SELECT 
        p.*, 
        u_input.nama AS input_by_nama,
        u_update.nama AS updated_by_nama
    FROM pengeluaran p
    LEFT JOIN users u_input ON p.input_by = u_input.id_user
    LEFT JOIN users u_update ON p.updated_by = u_update.id_user
    ORDER BY p.tanggal DESC
");

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">

<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-wallet mr-2"></i> Data Pengeluaran</h1>

        <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambah">
            <i class="fas fa-plus-circle"></i> Tambah Pengeluaran
        </button>
    </div>
</section>

<section class="content">

<div class="collapse mt-3" id="formTambah">
    <div class="card card-primary shadow-lg border-0">

        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
            <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Tambah Pengeluaran</h3>
        </div>

        <form method="POST" action="pengeluaran_tambah.php">
            <div class="card-body row g-3">

                <div class="col-md-4">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-4">
                    <label>Kategori</label>
                    <input type="text" name="kategori" class="form-control"
                        placeholder="Contoh: Listrik, Perbaikan" required>
                </div>

                <div class="col-md-4">
                    <label>Jumlah (Rp)</label>
                    <input type="number" step="0.01" name="jumlah"
                        class="form-control" required>
                </div>

                <div class="col-md-12">
                    <label>Keterangan</label>
                    <textarea name="keterangan" class="form-control"
                        placeholder="Opsional"></textarea>
                </div>

            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-lg border-0 mt-4">

    <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
        <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Pengeluaran</h3>
    </div>

    <div class="card-body table-responsive">
        <table id="tblPengeluaran" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Log Audit</th> 
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($p=mysqli_fetch_assoc($qData)): 
                // Tentukan nama dan waktu untuk kolom Log Audit
                $log_nama = htmlspecialchars($p['updated_by_nama'] ?? $p['input_by_nama']);
                $log_waktu_raw = $p['updated_at'] ?? $p['created_at']; 
                $log_waktu = $log_waktu_raw ? date('d/m H:i', strtotime($log_waktu_raw)) : '';
            ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($p['tanggal'])) ?></td>
                    <td class="text-center"><?= htmlspecialchars($p['kategori']) ?></td>
                    <td><?= htmlspecialchars($p['keterangan'] ?? '-') ?></td>
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
                           class="btn btn-sm text-white" 
                           style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
                            <i class="fas fa-edit"></i> Edit Data
                        </a>
                        </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(document).ready(function() {
    
    <?php if (isset($_SESSION['toast_success'])): ?>
        toastr.success("<?= $_SESSION['toast_success'] ?>", "Sukses!");
        <?php unset($_SESSION['toast_success']); // Hapus session agar tidak muncul lagi ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['toast_error'])): ?>
        toastr.error("<?= $_SESSION['toast_error'] ?>", "Gagal!");
        <?php unset($_SESSION['toast_error']); // Hapus session agar tidak muncul lagi ?>
    <?php endif; ?>

    // Hapus fungsi simpanPengeluaran() yang lama (berbasis AJAX)

});
</script>