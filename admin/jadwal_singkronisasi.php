<?php
//jadwal_singkronisasi.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');

// === Ambil log terakhir ===
$qLog = $conn->query("SELECT * FROM log_sinkronisasi ORDER BY id_log DESC LIMIT 20");
?>

<div class="content-wrapper animate__animated animate__fadeIn">
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-sync-alt mr-2"></i> Sinkronisasi Jadwal Otomatis</h1>
    <form method="POST" action="jadwal_sinkron_proses.php"
          onsubmit="return confirm('Lanjutkan sinkronisasi 5 bulan ke depan?')">
      <button type="submit" name="sinkron" class="btn btn-primary shadow-sm">
        <i class="fas fa-redo"></i> Sinkronkan 5 Bulan ke Depan
      </button>
    </form>
  </div>
</section>

<section class="content">

<div class="card shadow-sm">
  <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
    <h3 class="card-title mb-0"><i class="fas fa-history"></i> Riwayat Sinkronisasi</h3>
  </div>
  <div class="card-body table-responsive">
    <table id="tblLog" class="table table-bordered table-striped table-hover align-middle w-100">
      <thead class="bg-light text-center">
        <tr>
          <th>No</th><th>Waktu</th><th>Lapangan</th><th>Jadwal Harian</th><th>Jadwal Waktu</th><th>Pesan</th>
        </tr>
      </thead>
      <tbody>
        <?php $no=1; while($r=$qLog->fetch_assoc()): ?>
        <tr>
          <td class="text-center"><?= $no++ ?></td>
          <td class="text-center"><?= date('d-m-Y H:i:s', strtotime($r['waktu_sinkron'])) ?></td>
          <td class="text-center"><?= $r['jumlah_lapangan'] ?></td>
          <td class="text-center"><?= $r['jumlah_jadwal_harian'] ?></td>
          <td class="text-center"><?= $r['jumlah_jadwal_waktu'] ?></td>
          <td><?= htmlspecialchars($r['pesan']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- === Bootstrap Modern Toast (pojok kanan atas) === -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
  <div id="toastNotif" class="toast align-items-center text-bg-success border-0 shadow-lg" 
       role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
    <div class="d-flex">
      <div class="toast-body fw-semibold" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
$(function() {
  // Inisialisasi DataTable jika belum aktif (karena sudah ada global di footer)
  if (!$.fn.DataTable.isDataTable('#tblLog')) {
    $('#tblLog').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" }
    });
  }

  // === Toast Bootstrap Modern ===
  <?php if (!empty($_SESSION['toast_success'])): ?>
    showToast("<?= addslashes($_SESSION['toast_success']) ?>", "success");
    <?php unset($_SESSION['toast_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['toast_error'])): ?>
    showToast("<?= addslashes($_SESSION['toast_error']) ?>", "danger");
    <?php unset($_SESSION['toast_error']); ?>
  <?php endif; ?>

  function showToast(message, type) {
    const toastEl = document.getElementById('toastNotif');
    const toastBody = document.getElementById('toastMessage');
    toastBody.textContent = message;

    toastEl.classList.remove('text-bg-success', 'text-bg-danger');
    toastEl.classList.add(type === 'success' ? 'text-bg-success' : 'text-bg-danger');

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
  }
});
</script>
