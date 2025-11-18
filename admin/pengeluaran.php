<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// if (!isset($_SESSION['id_user'])) {
//     die("Unauthorized access.");
// }

// Ambil data pengeluaran
$qData = mysqli_query($conn, "
    SELECT p.*, u.nama AS input_by_nama
    FROM pengeluaran p
    LEFT JOIN users u ON p.input_by = u.id_user
    ORDER BY p.tanggal DESC
");

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">

<!-- HEADER -->
<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-wallet mr-2"></i> Data Pengeluaran</h1>

        <!-- Tombol seperti di member -->
        <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambah">
            <i class="fas fa-plus-circle"></i> Tambah Pengeluaran
        </button>
    </div>
</section>

<section class="content">

<!-- ===== FORM TAMBAH ===== -->
<div class="collapse mt-3" id="formTambah">
    <div class="card card-primary shadow-lg border-0">

        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
            <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Tambah Pengeluaran</h3>
        </div>

        <form id="formPengeluaran">
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
                <button type="button" onclick="simpanPengeluaran()" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== TABEL ===== -->
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
                    <th>Input By</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($p=mysqli_fetch_assoc($qData)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($p['tanggal'])) ?></td>
                    <td class="text-center"><?= htmlspecialchars($p['kategori']) ?></td>
                    <td><?= htmlspecialchars($p['keterangan'] ?? '-') ?></td>
                    <td class="text-end fw-bold text-danger">Rp <?= number_format($p['jumlah'],0,',','.') ?></td>
                    <td class="text-center"><?= htmlspecialchars($p['input_by_nama'] ?? '-') ?></td>

                    <td class="text-center">
                        <a href="pengeluaran_edit.php?id=<?= $p['id_pengeluaran'] ?>"
                           class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>

                        <a href="pengeluaran_hapus.php?id=<?= $p['id_pengeluaran'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Yakin ingin menghapus?');">
                            <i class="fas fa-trash"></i>
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
    $('#tblPengeluaran').DataTable();
});

// AJAX SIMPAN
function simpanPengeluaran() {
    let data = $('#formPengeluaran').serialize();

    $.post("pengeluaran_tambah.php", data, function(res) {
        try {
            let x = JSON.parse(res);

            if (x.status === 'success') {
                location.reload();
            } else {
                alert("Gagal: " + x.message);
            }

        } catch (e) {
            alert("Terjadi kesalahan.");
        }
    });
}
</script>
