<?php
//jadwal_harian.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Daily Schedule Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --danger-gradient: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    --warning-gradient: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
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

/* Main Card Enhancement */
.schedule-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.schedule-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.schedule-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.schedule-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.schedule-card .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Enhanced Table Styling */
#tblHarian {
    margin: 0;
}

#tblHarian thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblHarian thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    vertical-align: middle;
}

#tblHarian tbody tr {
    transition: all 0.2s ease;
}

#tblHarian tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblHarian tbody td {
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
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.badge.bg-success {
    background: var(--success-gradient) !important;
}

.badge.bg-danger {
    background: var(--danger-gradient) !important;
}

.badge.bg-warning {
    background: var(--warning-gradient) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .schedule-card .card-body {
        padding: 1.5rem;
    }
    
    #tblHarian thead th,
    #tblHarian tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <section class="content-header">
    <div class="container-fluid">
      <h1><i class="fas fa-calendar-day me-2"></i> Jadwal Harian Lapangan</h1>
      <p class="text-muted mb-0 mt-2">Daftar jadwal harian untuk semua lapangan</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card schedule-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-calendar-alt me-2"></i> Daftar Jadwal Harian Lapangan
          </h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="tblHarian" class="table table-hover align-middle w-100 mb-0">
              <thead class="text-center">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 20%">Lapangan</th>
                  <th style="width: 15%">Tanggal</th>
                  <th style="width: 12%">Hari</th>
                  <th style="width: 18%">Status Hari</th>
                  <th style="width: 18%">Dibuat</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $sql = "
                  SELECT 
                    jh.id_jadwal_harian,
                    l.nama_lapangan,
                    jh.tanggal,
                    jh.hari,
                    jh.status_hari,
                    jh.created_at
                  FROM jadwal_harian jh
                  JOIN lapangan l ON jh.id_lapangan = l.id_lapangan
                  ORDER BY jh.tanggal DESC
                ";

                $q = mysqli_query($conn, $sql);
                while ($r = mysqli_fetch_assoc($q)):
                  switch ($r['status_hari']) {
                    case 'tersedia': 
                      $badge = 'bg-success'; 
                      $icon = 'fa-check-circle'; 
                      break;
                    case 'penuh': 
                      $badge = 'bg-danger'; 
                      $icon = 'fa-times-circle'; 
                      break;
                    case 'libur': 
                      $badge = 'bg-warning text-dark'; 
                      $icon = 'fa-ban'; 
                      break;
                    default: 
                      $badge = 'bg-secondary'; 
                      $icon = 'fa-question-circle';
                  }
                ?>
                <tr>
                  <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                  <td class="fw-semibold"><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                  <td class="text-center">
                    <div class="fw-semibold"><?= date('d M Y', strtotime($r['tanggal'])) ?></div>
                  </td>
                  <td class="text-center"><?= htmlspecialchars($r['hari']) ?></td>
                  <td class="text-center">
                    <span class="badge <?= $badge ?>">
                      <i class="fas <?= $icon ?>"></i><?= ucfirst($r['status_hari']) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="fw-semibold"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                    <small class="text-muted"><?= date('H:i', strtotime($r['created_at'])) ?></small>
                  </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($q) == 0): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">
                    <i class="fas fa-info-circle me-1"></i> Belum ada jadwal harian tersedia
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

<?php include('../includes/footer.php'); ?>

<script>
$(document).ready(function() {
  // Inisialisasi DataTable
  if (!$.fn.DataTable.isDataTable('#tblHarian')) {
    $('#tblHarian').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 25,
      order: [[2, 'desc']], // Sort by tanggal
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  }
});
</script>