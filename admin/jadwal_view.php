<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

date_default_timezone_set('Asia/Jakarta');

// Ambil semua lapangan
$lapangan_q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// Ambil parameter dari URL (default: hari ini)
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$id_lapangan = isset($_GET['id_lapangan']) ? $_GET['id_lapangan'] : null;
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-alt mr-2"></i> Jadwal Lapangan</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- Filter -->
      <form method="GET" class="mb-3">
        <div class="row">
          <div class="col-md-4">
            <label>Pilih Lapangan</label>
            <select name="id_lapangan" class="form-control" required>
              <option value="">-- Pilih Lapangan --</option>
              <?php while ($lap = mysqli_fetch_assoc($lapangan_q)): ?>
                <option value="<?= $lap['id_lapangan'] ?>" <?= ($id_lapangan == $lap['id_lapangan']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lap['nama_lapangan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label>Pilih Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>" required>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Tampilkan</button>
          </div>
        </div>
      </form>

      <?php if ($id_lapangan): ?>
        <?php
        // Cek jadwal_harian
        $jadwal_harian = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT * FROM jadwal_harian 
            WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'
        "));

        if (!$jadwal_harian) {
          echo "<div class='alert alert-warning'>Belum ada jadwal untuk tanggal ini.</div>";
        } else {
          $id_jadwal_harian = $jadwal_harian['id_jadwal_harian'];

          $slots = mysqli_query($conn, "
              SELECT jd.id_detail, jw.jam_mulai, jw.jam_selesai, jd.status 
              FROM jadwal_detail jd
              JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
              WHERE jd.id_jadwal_harian='$id_jadwal_harian'
              ORDER BY jw.jam_mulai ASC
          ");
        ?>
          <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white">
              <h3 class="card-title mb-0">
                <i class="fas fa-clock mr-2"></i>
                Jadwal <?= htmlspecialchars($tanggal) ?>
              </h3>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped text-center" id="tabelJadwal">
                  <thead class="thead-dark">
                    <tr>
                      <th>Jam Mulai</th>
                      <th>Jam Selesai</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($slots) == 0): ?>
                      <tr><td colspan="4" class="text-center">Tidak ada data slot.</td></tr>
                    <?php else: ?>
                      <?php while ($row = mysqli_fetch_assoc($slots)): ?>
                        <tr id="row-<?= $row['id_detail'] ?>">
                          <td><?= substr($row['jam_mulai'], 0, 5) ?></td>
                          <td><?= substr($row['jam_selesai'], 0, 5) ?></td>
                          <td class="status">
                            <?php if ($row['status'] == 'tersedia'): ?>
                              <span class="badge badge-success px-3 py-2">Tersedia</span>
                            <?php else: ?>
                              <span class="badge badge-danger px-3 py-2">Dibooking</span>
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
        <?php } ?>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- AJAX Update -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.ubahStatus').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const newStatus = this.dataset.status;
      const row = document.querySelector(`#row-${id}`);
      const statusCell = row.querySelector('.status');
      const buttonCell = this.parentElement;

      fetch('ubah_status_slot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id_detail=${id}&status=${newStatus}`
      })
      .then(res => res.text())
      .then(resp => {
        if (resp.trim() === 'ok') {
          if (newStatus === 'dibooking') {
            statusCell.innerHTML = `<span class='badge badge-danger px-3 py-2'>Dibooking</span>`;
            buttonCell.innerHTML = `
              <button class="btn btn-sm btn-success ubahStatus" data-id="${id}" data-status="tersedia">
                <i class="fas fa-undo"></i> Batal
              </button>`;
          } else {
            statusCell.innerHTML = `<span class='badge badge-success px-3 py-2'>Tersedia</span>`;
            buttonCell.innerHTML = `
              <button class="btn btn-sm btn-danger ubahStatus" data-id="${id}" data-status="dibooking">
                <i class="fas fa-times"></i> Booking
              </button>`;
          }
        } else {
          alert('Gagal mengubah status!');
        }
      });
    });
  });
});
</script>

<?php include('../includes/footer.php'); ?>
