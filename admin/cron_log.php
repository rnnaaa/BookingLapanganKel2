<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-history mr-2"></i> Log Cron System</h1>
      <div>
        <button id="runCronBtn" class="btn btn-success shadow-sm me-2">
          <i class="fas fa-play"></i> Jalankan Cron Manual
        </button>
        <button id="autoCronBtn" class="btn btn-warning shadow-sm me-2">
          <i class="fas fa-clock"></i> Simulasi Auto Harian
        </button>
        <a href="booking.php" class="btn btn-secondary shadow-sm">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </section>

  <!-- Konten -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Riwayat Eksekusi Cron</h3>
        </div>

        <div class="card-body">
          <div id="cronStatus" class="alert alert-secondary text-center" style="display:none;"></div>

          <table id="tblCron" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Tipe Cron</th>
                <th>Jumlah Data</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Tanggal Dijalankan</th>
              </tr>
            </thead>
            <tbody id="cronTableBody">
              <?php
              $no = 1;
              $query = "SELECT * FROM cron_log ORDER BY tanggal_jalankan DESC";
              $result = mysqli_query($conn, $query);
              while ($row = mysqli_fetch_assoc($result)):
                $badge = ($row['status'] == 'sukses')
                  ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Sukses</span>'
                  : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Gagal</span>';
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= ucfirst(str_replace('_', ' ', $row['tipe'])) ?></td>
                <td class="text-center"><span class="badge bg-primary"><?= $row['jumlah_data'] ?></span></td>
                <td class="text-center"><?= $badge ?></td>
                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y H:i:s', strtotime($row['tanggal_jalankan'])) ?></td>
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

<!-- Jalankan Cron Manual dan Simulasi Otomatis -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Jalankan Cron Manual
  $('#runCronBtn').click(function() {
    Swal.fire({
      title: 'Jalankan Auto Booking Member?',
      text: 'Cron akan membuat booking otomatis untuk member aktif minggu ini.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Jalankan!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#28a745'
    }).then((result) => {
      if (result.isConfirmed) jalankanCron();
    });
  });

  // Simulasi Cron Otomatis Harian
  $('#autoCronBtn').click(function() {
    Swal.fire({
      title: 'Aktifkan simulasi cron otomatis?',
      text: 'Sistem akan menjalankan cron setiap pukul 00:00 secara otomatis (simulasi untuk Laragon).',
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Aktifkan!',
      cancelButtonText: 'Batal'
    }).then((res) => {
      if (res.isConfirmed) {
        $('#cronStatus').removeClass().addClass('alert alert-warning').text('⚙️ Simulasi aktif: Cron akan jalan tiap 00:00').show();
        simulasiCron();
      }
    });
  });

  // Fungsi Jalankan Cron
  function jalankanCron() {
    $('#cronStatus').removeClass().addClass('alert alert-info').text('⏳ Sedang menjalankan cron...').show();
    $.ajax({
      url: '../cron/cron_auto_booking_member.php',
      type: 'GET',
      success: function(res) {
        $('#cronStatus').removeClass().addClass('alert alert-success').html('✅ ' + res);
        refreshCronTable();
      },
      error: function() {
        $('#cronStatus').removeClass().addClass('alert alert-danger').text('❌ Gagal menjalankan cron.');
      }
    });
  }

  // Fungsi Refresh Tabel Cron
  function refreshCronTable() {
    $.get('cron_log_reload.php', function(data) {
      $('#cronTableBody').html(data);
    });
  }

  // Simulasi Cron Otomatis Harian (setiap 00:00)
  function simulasiCron() {
    setInterval(function() {
      const now = new Date();
      if (now.getHours() === 0 && now.getMinutes() === 0 && now.getSeconds() === 0) {
        jalankanCron();
      }
    }, 1000);
  }
});
</script>