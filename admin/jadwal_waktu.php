<?php
//jadwal_waktu.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Time Schedule Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --info-gradient: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
.time-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.time-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.time-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.time-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.time-card .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Enhanced Table Styling */
#tblWaktu {
    margin: 0;
}

#tblWaktu thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblWaktu thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    vertical-align: middle;
}

#tblWaktu tbody tr {
    transition: all 0.2s ease;
}

#tblWaktu tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblWaktu tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f3f9;
}

/* Time Badge Enhancement */
.time-badge {
    background: var(--info-gradient);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .time-card .card-body {
        padding: 1.5rem;
    }
    
    #tblWaktu thead th,
    #tblWaktu tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid">
      <h1><i class="fas fa-clock me-2"></i> Jadwal Waktu Lapangan</h1>
      <p class="text-muted mb-0 mt-2">Daftar slot waktu untuk setiap lapangan</p>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card time-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-clock me-2"></i> Daftar Jadwal Waktu
          </h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="tblWaktu" class="table table-hover align-middle w-100 mb-0">
              <thead class="text-center">
                <tr>
                  <th style="width: 8%">No</th>
                  <th style="width: 30%">Lapangan</th>
                  <th style="width: 35%">Jam</th>
                  <th style="width: 27%">Dibuat</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($conn, "
                  SELECT jw.*, l.nama_lapangan 
                  FROM jadwal_waktu jw
                  JOIN lapangan l ON jw.id_lapangan = l.id_lapangan
                  ORDER BY l.nama_lapangan, jw.jam_mulai
                ");
                while ($row = mysqli_fetch_assoc($query)):
                ?>
                  <tr>
                    <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                    <td class="text-center">
                      <span class="time-badge">
                        <i class="fas fa-clock"></i>
                        <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="fw-semibold"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                      <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                    </td>
                  </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($query) == 0): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">
                    <i class="fas fa-info-circle me-1"></i> Belum ada jadwal waktu tersedia
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
  if (!$.fn.DataTable.isDataTable('#tblWaktu')) {
    $('#tblWaktu').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 25,
      order: [[1, 'asc'], [2, 'asc']], // Sort by lapangan, then jam
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  }
});
</script>