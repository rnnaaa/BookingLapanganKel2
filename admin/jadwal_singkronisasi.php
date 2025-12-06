<?php
//jadwal_sinkronisasi.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include('../includes/header.php');
include('../includes/sidebar.php');

// === Ambil log terakhir ===
$qLog = $conn->query("SELECT * FROM log_sinkronisasi ORDER BY id_log DESC LIMIT 20");
?>

<style>
/* Professional Synchronization Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --card-shadow: 0 4px 20px rgba(14, 92, 145, 0.15);
    --card-hover-shadow: 0 8px 30px rgba(14, 92, 145, 0.25);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Content Header Enhancement */
.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
    margin: 0;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Sync Button Enhancement */
.btn-sync {
    background: var(--primary-gradient);
    border: none;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.75rem 1.75rem;
    font-size: 0.938rem;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.btn-sync:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
    color: #fff;
}

.btn-sync i {
    margin-right: 0.5rem;
}

/* Log Card Enhancement */
.log-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.log-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.log-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.log-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.log-card .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Enhanced Table Styling */
#tblLog {
    margin: 0;
}

#tblLog thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblLog thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    vertical-align: middle;
}

#tblLog tbody tr {
    transition: all 0.2s ease;
}

#tblLog tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblLog tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f3f9;
}

/* Toast Enhancement */
.toast {
    border-radius: 12px;
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    border: none;
}

.toast-body {
    padding: 1rem 1.25rem;
    font-size: 0.938rem;
    font-weight: 600;
}

.toast.text-bg-success {
    background: var(--success-gradient) !important;
}

.toast.text-bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header {
        text-align: center;
    }
    
    .content-header h1 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .btn-sync {
        width: 100%;
    }
    
    .log-card .card-body {
        padding: 1.5rem;
    }
    
    #tblLog thead th,
    #tblLog tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
      <div class="mb-3 mb-md-0">
        <h1><i class="fas fa-sync-alt me-2"></i> Sinkronisasi Jadwal</h1>
        <p class="text-muted mb-0 mt-2">Sinkronkan jadwal lapangan untuk 5 bulan ke depan</p>
      </div>
      <form method="POST" action="jadwal_sinkron_proses.php"
            onsubmit="return confirm('⚠️ Apakah Anda yakin ingin menjalankan sinkronisasi untuk 5 bulan ke depan?')">
        <button type="submit" name="sinkron" class="btn btn-sync shadow">
          <i class="fas fa-redo"></i> Jalankan Sinkronisasi
        </button>
      </form>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card log-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-history me-2"></i> Riwayat Sinkronisasi
          </h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblLog" class="table table-hover align-middle w-100 mb-0">
              <thead class="text-center">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 18%">Waktu</th>
                  <th style="width: 12%">Lapangan</th>
                  <th style="width: 15%">Jadwal Harian</th>
                  <th style="width: 15%">Jadwal Waktu</th>
                  <th style="width: 35%">Pesan</th>
                </tr>
              </thead>
              <tbody>
                <?php $no=1; while($r=$qLog->fetch_assoc()): ?>
                <tr>
                  <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                  <td class="text-center">
                    <div class="fw-semibold"><?= date('d M Y', strtotime($r['waktu_sinkron'])) ?></div>
                    <small class="text-muted"><?= date('H:i:s', strtotime($r['waktu_sinkron'])) ?></small>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary"><?= $r['jumlah_lapangan'] ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge" style="background: var(--primary-gradient);"><?= $r['jumlah_jadwal_harian'] ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge" style="background: var(--success-gradient);"><?= $r['jumlah_jadwal_waktu'] ?></span>
                  </td>
                  <td>
                    <div class="text-wrap" style="max-width: 400px;">
                      <?= htmlspecialchars($r['pesan']) ?>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($qLog->num_rows == 0): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">
                    <i class="fas fa-info-circle me-1"></i> Belum ada riwayat sinkronisasi
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Bootstrap Modern Toast (pojok kanan atas) -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
  <div id="toastNotif" class="toast align-items-center text-white border-0" 
       role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(function() {
  // Inisialisasi DataTable
  if (!$.fn.DataTable.isDataTable('#tblLog')) {
    $('#tblLog').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      order: [[1, 'desc']], // Sort by waktu
      language: { 
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" 
      }
    });
  }

  // Toast Bootstrap Modern
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