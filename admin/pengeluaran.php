<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

/**
 * Deteksi nama kolom penting pada tabel `pengeluaran`.
 * Kita cari kolom untuk: tanggal, kategori, deskripsi, jumlah, id.
 * Jika tidak ditemukan, fallback ke nama yang sering dipakai.
 */
$cols = [];
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM pengeluaran");
while ($c = mysqli_fetch_assoc($colRes)) {
  $cols[] = $c['Field'];
}

function find_col($candidates, $cols, $fallback = null) {
  foreach ($candidates as $cand) {
    if (in_array($cand, $cols)) return $cand;
  }
  return $fallback;
}

$id_col = find_col(['id_pengeluaran','id'], $cols, 'id_pengeluaran');
$date_col = find_col(['tanggal_pengeluaran','tanggal','tgl','date'], $cols, null);
$category_col = find_col(['kategori_pengeluaran','kategori','category'], $cols, null);
$desc_col = find_col(['deskripsi_pengeluaran','deskripsi','description'], $cols, null);
$amount_col = find_col(['jumlah_pengeluaran','jumlah','amount','nominal'], $cols, null);

// Jika date_col null => set ke first date-like fallback to avoid crash (but will likely be null)
if (!$date_col) {
  // jika tidak ada kolom tanggal, ambil first DATE or DATETIME column
  $colRes2 = mysqli_query($conn, "SHOW COLUMNS FROM pengeluaran");
  while ($c = mysqli_fetch_assoc($colRes2)) {
    $type = strtolower($c['Type']);
    if (strpos($type,'date') !== false || strpos($type,'timestamp') !== false || strpos($type,'datetime') !== false) {
      $date_col = $c['Field'];
      break;
    }
  }
}

// Jika masih null, set fallback to first column after id
if (!$date_col) {
  foreach ($cols as $c) {
    if ($c !== $id_col) { $date_col = $c; break; }
  }
}

?>
<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-receipt mr-2"></i> Manajemen Pengeluaran</h1>
      <!-- tombol modal: nama fields akan di-render sesuai kolom terdeteksi -->
      <a href="#" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalPengeluaran">
        <i class="fas fa-plus-circle"></i> Tambah Pengeluaran
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-danger text-white">
          <h3 class="card-title"><i class="fas fa-list mr-2"></i> Data Pengeluaran</h3>
        </div>

        <!-- MODAL: form field names mengikuti nama kolom sebenarnya -->
        <div class="modal fade" id="modalPengeluaran" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">

              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle mr-1"></i>Tambah Pengeluaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
              </div>

              <form id="formPengeluaran">
                <div class="modal-body">
                  <?php if ($date_col): ?>
                  <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="<?= htmlspecialchars($date_col) ?>" class="form-control" required>
                  </div>
                  <?php endif; ?>

                  <?php if ($category_col): ?>
                  <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="<?= htmlspecialchars($category_col) ?>" class="form-control" required>
                  </div>
                  <?php endif; ?>

                  <?php if ($desc_col): ?>
                  <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="<?= htmlspecialchars($desc_col) ?>" class="form-control" required></textarea>
                  </div>
                  <?php endif; ?>

                  <?php if ($amount_col): ?>
                  <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="<?= htmlspecialchars($amount_col) ?>" class="form-control" required>
                  </div>
                  <?php endif; ?>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
              </form>

            </div>
          </div>
        </div>
        <!-- END MODAL -->

        <div class="card-body">
          <table id="example1" class="table table-bordered table-striped table-hover">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Ambil data, urut berdasarkan kolom tanggal yang terdeteksi
              $order_by = $date_col ? $date_col : $id_col;
              $sql = "SELECT * FROM pengeluaran ORDER BY `$order_by` DESC";
              $res = mysqli_query($conn, $sql);
              $no = 1;
              while ($row = mysqli_fetch_assoc($res)):
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center">
                  <?php
                    $d = $row[$date_col] ?? null;
                    echo $d ? date('d-m-Y', strtotime($d)) : '-';
                  ?>
                </td>
                <td><span class="badge bg-info"><?= htmlspecialchars($row[$category_col] ?? '-') ?></span></td>
                <td><?= htmlspecialchars($row[$desc_col] ?? '-') ?></td>
                <td class="text-right text-danger font-weight-bold">
                  Rp <?= number_format($row[$amount_col] ?? 0, 0, ',', '.') ?>
                </td>
                <td class="text-center">
                  <a href="pengeluaran_edit.php?id=<?= $row[$id_col] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                  <a href="pengeluaran_hapus.php?id=<?= $row[$id_col] ?>" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></a>
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
/*
AJAX submit:
- data sent will include fields with names exactly sesuai nama kolom yang terdeteksi,
  karena form di-render oleh PHP di atas menggunakan nama kolom aktual.
- server akan merespon JSON { success: true } atau { success: false, message: "..."}
*/
$("#formPengeluaran").on("submit", function(e){
  e.preventDefault();
  const $form = $(this);
  $.ajax({
    url: "pengeluaran_simpan_ajax.php",
    type: "POST",
    data: $form.serialize(),
    dataType: "json"
  }).done(function(resp){
    if (resp.success) {
      $("#modalPengeluaran").modal("hide");
      toastr.success(resp.message || "Pengeluaran berhasil ditambahkan!");
      // refresh table (simple) — reload halaman untuk memastikan DataTables reinit
      setTimeout(() => location.reload(), 700);
    } else {
      toastr.error(resp.message || "Gagal menambahkan pengeluaran!");
      console.error("Server error:", resp);
    }
  }).fail(function(xhr, status, err){
    toastr.error("Terjadi kesalahan koneksi (AJAX). Cek console.");
    console.error("AJAX fail:", status, err, xhr.responseText);
  });
});
</script>
