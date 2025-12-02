<?php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');

date_default_timezone_set('Asia/Jakarta');

// Ambil semua lapangan aktif
$lapangan_q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// Ambil parameter filter
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
?>

<style>
/* ======== TATA LETAK & GAYA ======== */
.content-header {
  margin-bottom: 10px;
}
.form-label {
  font-weight: 600;
  color: #000; /* label hitam */
}
.gradient-btn {
  background: linear-gradient(90deg,#0e5c91,#2196f3);
  border: none;
  color: #fff;
  font-weight: 600;
  transition: all 0.25s ease;
}
.gradient-btn:hover {
  background: linear-gradient(90deg,#2196f3,#0e5c91);
  transform: scale(1.03);
}
.card {
  border-radius: 12px;
}
.card-header h3 {
  font-size: 1.1rem;
  font-weight: 600;
}
.table th, .table td {
  vertical-align: middle !important;
  padding: 10px;
}
.filter-row {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}
.filter-row .col-md-4,
.filter-row .col-md-3,
.filter-row .col-md-2 {
  flex: 1;
  min-width: 200px;
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="mb-2"><i class="fas fa-calendar-alt me-2"></i> Jadwal Lapangan</h1>
    </div>
  </section>

  <section class="content pt-0">
    <div class="container-fluid">

      <!-- Filter Jadwal -->
      <form method="GET" class="mb-2">
        <div class="filter-row">
          <div class="col-md-4">
            <label class="form-label">Pilih Lapangan</label>
            <select name="id_lapangan" class="form-select" required>
              <option value="">-- Pilih Lapangan --</option>
              <?php while ($lap = mysqli_fetch_assoc($lapangan_q)): ?>
                <option value="<?= $lap['id_lapangan'] ?>" <?= ($id_lapangan == $lap['id_lapangan']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lap['nama_lapangan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>" required>
          </div>

          <div class="col-md-2 d-grid">
            <button type="submit" class="btn gradient-btn">
              <i class="fas fa-search me-1"></i> Tampilkan
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
          <div class="alert alert-warning mt-3">⚠️ Belum ada jadwal untuk tanggal ini. Jalankan sinkronisasi terlebih dahulu.</div>
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
          <div class="card shadow-lg border-0 mt-3">
            <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3); border-top-left-radius:12px; border-top-right-radius:12px;">
              <h3 class="card-title mb-0">
                <i class="fas fa-clock me-2"></i> Jadwal <?= date('d-m-Y', strtotime($tanggal)) ?>
              </h3>
            </div>

            <div class="card-body p-3">
              <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle" id="tabelJadwal">
                  <thead class="bg-light">
                    <tr>
                      <th>Jam Mulai</th>
                      <th>Jam Selesai</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($slots) == 0): ?>
                      <tr><td colspan="4" class="text-muted py-3">Tidak ada data slot.</td></tr>
                    <?php else: ?>
                      <?php while ($row = mysqli_fetch_assoc($slots)): ?>
                        <tr id="row-<?= $row['id_detail'] ?>">
                          <td><?= substr($row['jam_mulai'], 0, 5) ?></td>
                          <td><?= substr($row['jam_selesai'], 0, 5) ?></td>
                          <td class="status">
                            <?php if ($row['status'] == 'tersedia'): ?>
                              <span class="badge bg-success px-3 py-2">Tersedia</span>
                            <?php else: ?>
                              <span class="badge bg-danger px-3 py-2">Dibooking</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($row['status'] == 'tersedia'): ?>
                              <button class="btn btn-sm btn-danger ubahStatus" data-id="<?= $row['id_detail'] ?>" data-status="dibooking">
                                <i class="fas fa-times"></i> Booking
                              </button>
                            <?php else: ?>
                              <button class="btn btn-sm btn-success ubahStatus" data-id="<?= $row['id_detail'] ?>" data-status="tersedia">
                                <i class="fas fa-undo"></i> Batal
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

  table.addEventListener('click', (e) => {
    if (!e.target.closest('.ubahStatus')) return;
    const btn = e.target.closest('.ubahStatus');
    const id = btn.dataset.id;
    const newStatus = btn.dataset.status;

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
          statusCell.innerHTML = `<span class='badge bg-danger px-3 py-2'>Dibooking</span>`;
          buttonCell.innerHTML = `
            <button class="btn btn-sm btn-success ubahStatus" data-id="${id}" data-status="tersedia">
              <i class="fas fa-undo"></i> Batal
            </button>`;
        } else {
          statusCell.innerHTML = `<span class='badge bg-success px-3 py-2'>Tersedia</span>`;
          buttonCell.innerHTML = `
            <button class="btn btn-sm btn-danger ubahStatus" data-id="${id}" data-status="dibooking">
              <i class="fas fa-times"></i> Booking
            </button>`;
        }
      } else {
        alert('Gagal mengubah status slot!');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan koneksi ke server.');
    });
  });
});
</script>

<?php include('../includes/footer.php'); ?>
