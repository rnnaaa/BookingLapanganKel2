<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1>Tambah Jadwal Mingguan Member</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-body">
          <form action="proses_member_jadwal.php" method="POST">

            <!-- Pilih Member Aktif -->
            <div class="form-group">
              <label for="id_member">Pilih Member Aktif</label>
              <select name="id_member" id="id_member" class="form-control" required>
                <option value="">-- Pilih Member --</option>
                <?php
                $stmt = $pdo->query("
                  SELECT m.id_member, u.nama_lengkap, l.nama_lapangan, m.tgl_mulai, m.tgl_berakhir
                  FROM member m
                  JOIN users u ON m.id_user = u.id_user
                  JOIN lapangan l ON m.id_lapangan = l.id_lapangan
                  WHERE m.status = 'aktif'
                  ORDER BY u.nama_lengkap ASC
                ");
                foreach ($stmt as $m) {
                  echo "<option value='{$m['id_member']}' data-lapangan='{$m['nama_lapangan']}' data-start='{$m['tgl_mulai']}' data-end='{$m['tgl_berakhir']}'>
                    {$m['nama_lengkap']} - {$m['nama_lapangan']} (Masa aktif: {$m['tgl_mulai']} s/d {$m['tgl_berakhir']})
                  </option>";
                }
                ?>
              </select>
            </div>

            <!-- Tanggal Bermain -->
            <div class="form-group">
              <label for="tanggal_main">Tanggal Main</label>
              <input type="date" name="tanggal_main" id="tanggal_main" class="form-control" required>
              <small id="info_masa" class="text-muted"></small>
            </div>

            <!-- Jam Mulai -->
            <div class="form-group">
              <label for="jam_mulai">Jam Mulai</label>
              <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
            </div>

            <!-- Jam Selesai -->
            <div class="form-group">
              <label for="jam_selesai">Jam Selesai</label>
              <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jadwal</button>
              <a href="member.php" class="btn btn-secondary">Kembali</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
document.getElementById('id_member').addEventListener('change', function() {
  const start = this.selectedOptions[0].getAttribute('data-start');
  const end = this.selectedOptions[0].getAttribute('data-end');
  document.getElementById('info_masa').textContent = `Masa aktif: ${start} s/d ${end}`;
});
</script>

<?php include('../includes/footer.php'); ?>
