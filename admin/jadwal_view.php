<?php
//jadwal_view.php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');

date_default_timezone_set('Asia/Jakarta');

// Ambil semua lapangan aktif
$lapangan_q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// Ambil parameter filter
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
?>

<style>
/* Professional Schedule View Styling */
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

/* Filter Card Enhancement */
.filter-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
    margin-bottom: 2rem;
    border: none;
    transition: all 0.3s ease;
}

.filter-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.filter-card .form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    display: block;
}

.filter-card .form-control,
.filter-card .form-select {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
    height: 48px;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

/* Filter Row Layout */
.filter-row {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-row > div {
    flex: 1;
    min-width: 200px;
}

/* Button Enhancement */
.gradient-btn {
    background: var(--primary-gradient);
    border: none;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-size: 0.938rem;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
    width: 100%;
    height: 48px;
}

.gradient-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
    color: #fff;
}

.gradient-btn i {
    margin-right: 0.5rem;
}

/* Alert Enhancement */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1.25rem 1.5rem;
    font-size: 0.938rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-top: 1.5rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    color: #856404;
    border-left: 4px solid #ffc107;
}

/* Schedule Card Enhancement */
.schedule-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-top: 1.5rem;
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
#tabelJadwal {
    margin: 0;
}

#tabelJadwal thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tabelJadwal thead th {
    font-size: 0.875rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    vertical-align: middle;
}

#tabelJadwal tbody tr {
    transition: all 0.2s ease;
}

#tabelJadwal tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tabelJadwal tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.938rem;
    border-bottom: 1px solid #f1f3f9;
}

/* Badge Enhancements */
.badge {
    padding: 0.5rem 1rem;
    font-size: 0.813rem;
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

/* Action Button Enhancements */
.btn-sm {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.813rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-sm.btn-danger {
    background: var(--danger-gradient);
    border: none;
}

.btn-sm.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.btn-sm.btn-success {
    background: var(--success-gradient);
    border: none;
}

.btn-sm.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

/* Empty State */
.text-muted.py-3 {
    font-size: 1rem;
    color: #6c757d;
    font-style: italic;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .filter-card {
        padding: 1.5rem;
    }
    
    .filter-row > div {
        min-width: 100%;
        flex: none;
    }
    
    .schedule-card .card-body {
        padding: 1.5rem;
    }
    
    #tabelJadwal thead th,
    #tabelJadwal tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <section class="content-header">
    <div class="container-fluid">
      <h1><i class="fas fa-calendar-alt me-2"></i> Jadwal Lapangan</h1>
      <p class="text-muted mb-0 mt-2">Kelola dan pantau jadwal booking lapangan</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- Filter Jadwal -->
      <form method="GET" class="filter-card">
        <div class="filter-row">
          <div>
            <label class="form-label">
              <i class="fas fa-futbol me-1"></i> Pilih Lapangan
            </label>
            <select name="id_lapangan" class="form-select" required>
              <option value="">-- Pilih Lapangan --</option>
              <?php while ($lap = mysqli_fetch_assoc($lapangan_q)): ?>
                <option value="<?= $lap['id_lapangan'] ?>" <?= ($id_lapangan == $lap['id_lapangan']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lap['nama_lapangan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div>
            <label class="form-label">
              <i class="fas fa-calendar me-1"></i> Pilih Tanggal
            </label>
            <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>" required>
          </div>

          <div>
            <button type="submit" class="btn gradient-btn">
              <i class="fas fa-search"></i> Tampilkan Jadwal
            </button>
          </div>
        </div>
      </form>

      <?php if ($id_lapangan): ?>
        <?php
        $jadwal_harian = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT * FROM jadwal_harian 
            WHERE id_lapangan = '$id_lapangan' AND tanggal = '$tanggal'
        "));

        if (!$jadwal_harian):
        ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Perhatian:</strong> Belum ada jadwal untuk tanggal ini. Silakan jalankan sinkronisasi terlebih dahulu.
          </div>
        <?php else:
          $id_jadwal_harian = $jadwal_harian['id_jadwal_harian'];
          $slots = mysqli_query($conn, "
              SELECT jd.id_detail, jw.jam_mulai, jw.jam_selesai, jd.status 
              FROM jadwal_detail jd
              JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
              WHERE jd.id_jadwal_harian = '$id_jadwal_harian'
              ORDER BY jw.jam_mulai ASC
          ");
        ?>
          <div class="card schedule-card">
            <div class="card-header text-white">
              <h3 class="card-title mb-0">
                <i class="fas fa-clock me-2"></i> Jadwal <?= date('d M Y', strtotime($tanggal)) ?>
              </h3>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover text-center align-middle" id="tabelJadwal">
                  <thead>
                    <tr>
                      <th style="width: 25%">Jam Mulai</th>
                      <th style="width: 25%">Jam Selesai</th>
                      <th style="width: 20%">Status</th>
                      <th style="width: 30%">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($slots) == 0): ?>
                      <tr><td colspan="4" class="text-muted py-3">Tidak ada data slot tersedia.</td></tr>
                    <?php else: ?>
                      <?php while ($row = mysqli_fetch_assoc($slots)): ?>
                        <tr id="row-<?= $row['id_detail'] ?>">
                          <td class="fw-semibold"><?= substr($row['jam_mulai'], 0, 5) ?></td>
                          <td class="fw-semibold"><?= substr($row['jam_selesai'], 0, 5) ?></td>
                          <td class="status">
                            <?php if ($row['status'] == 'tersedia'): ?>
                              <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Tersedia
                              </span>
                            <?php else: ?>
                              <span class="badge bg-danger">
                                <i class="fas fa-times-circle"></i> Dibooking
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($row['status'] == 'tersedia'): ?>
                              <button class="btn btn-sm btn-danger ubahStatus" data-id="<?= $row['id_detail'] ?>" data-status="dibooking">
                                <i class="fas fa-lock"></i> Booking
                              </button>
                            <?php else: ?>
                              <button class="btn btn-sm btn-success ubahStatus" data-id="<?= $row['id_detail'] ?>" data-status="tersedia">
                                <i class="fas fa-unlock"></i> Batal Booking
                              </button>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('tabelJadwal');
  if (!table) return;

  table.addEventListener('click', (e) => {
    if (!e.target.closest('.ubahStatus')) return;
    const btn = e.target.closest('.ubahStatus');
    const id = btn.dataset.id;
    const newStatus = btn.dataset.status;

    // Disable button during request
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

    fetch('ubah_status_slot.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id_detail=${id}&status=${newStatus}`
    })
    .then(res => res.text())
    .then(resp => {
      if (resp.trim() === 'ok') {
        const row = document.getElementById(`row-${id}`);
        const statusCell = row.querySelector('.status');
        const buttonCell = row.querySelector('td:last-child');

        if (newStatus === 'dibooking') {
          statusCell.innerHTML = `<span class='badge bg-danger'><i class='fas fa-times-circle'></i> Dibooking</span>`;
          buttonCell.innerHTML = `
            <button class="btn btn-sm btn-success ubahStatus" data-id="${id}" data-status="tersedia">
              <i class="fas fa-unlock"></i> Batal Booking
            </button>`;
        } else {
          statusCell.innerHTML = `<span class='badge bg-success'><i class='fas fa-check-circle'></i> Tersedia</span>`;
          buttonCell.innerHTML = `
            <button class="btn btn-sm btn-danger ubahStatus" data-id="${id}" data-status="dibooking">
              <i class="fas fa-lock"></i> Booking
            </button>`;
        }
      } else {
        alert('❌ Gagal mengubah status slot!');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
      }
    })
    .catch(err => {
      console.error(err);
      alert('❌ Terjadi kesalahan koneksi ke server.');
      btn.disabled = false;
      btn.innerHTML = originalHTML;
    });
  });
});
</script>

<?php include('../includes/footer.php'); ?>