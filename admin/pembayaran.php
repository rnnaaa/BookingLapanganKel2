<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-wallet mr-2"></i> Data Pembayaran</h1>
      <a href="booking.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Booking
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title mb-0"><i class="fas fa-money-bill-wave mr-2"></i> Daftar Pembayaran Pelanggan</h3>
        </div>
        <div class="card-body">
          <table id="tblPembayaran" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama Pengguna</th>
                <th>ID Booking</th>
                <th>Tipe</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Bukti</th>
                <th>Status</th>
                <th>Tanggal Upload</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $sql = "
                SELECT p.*, b.tanggal, u.nama AS nama_user
                FROM pembayaran p
                JOIN booking b ON p.booking_id = b.id_booking
                JOIN users u ON b.id_user = u.id_user
                ORDER BY p.created_at DESC
              ";
              $result = mysqli_query($conn, $sql);
              while ($r = mysqli_fetch_assoc($result)):
                $status = strtolower($r['status_verifikasi']);
                switch ($status) {
                  case 'valid': $badge='bg-success'; $icon='fa-check-circle'; break;
                  case 'tidak_valid': $badge='bg-danger'; $icon='fa-times-circle'; break;
                  default: $badge='bg-warning text-dark'; $icon='fa-hourglass-half';
                }
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td class="text-center"><?= $r['booking_id'] ?><br><small><?= date('d/m/Y', strtotime($r['tanggal'])) ?></small></td>
                <td class="text-center"><?= ucfirst($r['tipe']) ?></td>
                <td class="text-end">Rp <?= number_format($r['amount'], 0, ',', '.') ?></td>
                <td class="text-center"><?= ucfirst(str_replace('_', ' ', $r['method'])) ?></td>
                <td class="text-center">
                  <?php if ($r['bukti_path']): ?>
                    <a href="../uploads/<?= htmlspecialchars($r['bukti_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                      <i class="fas fa-eye"></i> Lihat
                    </a>
                  <?php else: ?>
                    <em class="text-muted">Belum upload</em>
                  <?php endif; ?>
                </td>
                <td class="text-center"><span class="badge <?= $badge ?>"><i class="fas <?= $icon ?> me-1"></i><?= ucfirst($status) ?></span></td>
                <td class="text-center"><?= $r['tanggal_upload'] ? date('d-m-Y H:i', strtotime($r['tanggal_upload'])) : '-' ?></td>
                <td class="text-center">
                  <?php if ($status === 'menunggu'): ?>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=valid" class="btn btn-success btn-sm" onclick="return confirm('Validasi pembayaran ini sebagai sah?')"><i class="fas fa-check"></i> Valid</a>
                    <a href="pembayaran_validasi.php?id=<?= $r['id_pembayaran'] ?>&aksi=tolak" class="btn btn-danger btn-sm" onclick="return confirm('Tolak pembayaran ini?')"><i class="fas fa-times"></i> Tolak</a>
                  <?php else: ?><em class="text-muted">-</em><?php endif; ?>
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
$(function(){
  if ($.fn.DataTable.isDataTable('#tblPembayaran')) {
    $('#tblPembayaran').DataTable().destroy();
  }
  const table = $('#tblPembayaran').DataTable({
    responsive: true,
    pageLength: 10,
    dom: '<"row mb-3"<"col-sm-6"B><"col-sm-6"f>>rtip',
    buttons: [
      { extend: 'copy', text: '<i class="fas fa-copy"></i> Salin', className: 'btn btn-light border me-1' },
      { extend: 'excel', text: '<i class="fas fa-file-excel text-success"></i> Excel', className: 'btn btn-light border me-1' },
      { extend: 'pdf', text: '<i class="fas fa-file-pdf text-danger"></i> PDF', className: 'btn btn-light border me-1' },
      { extend: 'print', text: '<i class="fas fa-print text-primary"></i> Cetak', className: 'btn btn-light border me-1' }
    ],
    language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" }
  });
});
</script>
