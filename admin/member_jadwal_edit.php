<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Pastikan ID jadwal dikirim
if (!isset($_GET['id'])) {
  header("Location: member_jadwal.php");
  exit;
}

$id_jadwal = intval($_GET['id']);

// Ambil data jadwal yang mau diedit
$qJadwal = mysqli_query($conn, "
  SELECT mj.*, u.nama AS nama_member, l.nama_lapangan, l.harga_member
  FROM member_jadwal mj
  JOIN member m ON mj.id_member = m.id_member
  JOIN users u ON m.id_user = u.id_user
  JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
  WHERE mj.id_member_jadwal = '$id_jadwal'
");

if (mysqli_num_rows($qJadwal) == 0) {
  $_SESSION['toast_error'] = "⚠️ Jadwal tidak ditemukan!";
  header("Location: member_jadwal.php");
  exit;
}

$data = mysqli_fetch_assoc($qJadwal);

// Ambil semua lapangan aktif
$lapangan = mysqli_query($conn, "SELECT * FROM lapangan WHERE status = 'aktif'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_lapangan = mysqli_real_escape_string($conn, $_POST['id_lapangan']);
  $hari = mysqli_real_escape_string($conn, $_POST['hari']);
  $jam_mulai = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];
  $harga = $_POST['harga'];
  $status = $_POST['status'];

  // Validasi jam
  if ($jam_mulai >= $jam_selesai) {
    $_SESSION['toast_error'] = "⚠️ Jam selesai harus lebih besar dari jam mulai!";
  } else {
    // Cek bentrok dengan jadwal lain
    $cek = mysqli_query($conn, "
      SELECT * FROM member_jadwal 
      WHERE id_lapangan = '$id_lapangan'
      AND hari = '$hari'
      AND id_member_jadwal != '$id_jadwal'
      AND (jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')
    ");

    if (mysqli_num_rows($cek) > 0) {
      $_SESSION['toast_error'] = "⚠️ Jadwal bentrok dengan member lain di lapangan ini!";
    } else {
      // Update data jadwal
      $update = mysqli_query($conn, "
        UPDATE member_jadwal 
        SET id_lapangan = '$id_lapangan',
            hari = '$hari',
            jam_mulai = '$jam_mulai',
            jam_selesai = '$jam_selesai',
            harga_per_jam_member = '$harga',
            status = '$status',
            updated_at = NOW()
        WHERE id_member_jadwal = '$id_jadwal'
      ");

      if ($update) {
        $_SESSION['toast_success'] = "✅ Jadwal rutin berhasil diperbarui!";
        header("Location: member_jadwal.php");
        exit;
      } else {
        $_SESSION['toast_error'] = "❌ Gagal memperbarui data jadwal!";
      }
    }
  }
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Jadwal Rutin Member</h1>
      <a href="member_jadwal.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-warning text-dark">
          <h3 class="card-title"><i class="fas fa-clock mr-2"></i> Form Edit Jadwal</h3>
        </div>

        <form method="POST">
          <div class="card-body">
            <div class="row">
              <!-- NAMA MEMBER -->
              <div class="col-md-6 mb-3">
                <label>Nama Member</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_member']) ?>" readonly>
              </div>

              <!-- PILIH LAPANGAN -->
              <div class="col-md-6 mb-3">
                <label>Pilih Lapangan</label>
                <select name="id_lapangan" id="id_lapangan" class="form-control select2" required>
                  <option value="">-- Pilih Lapangan --</option>
                  <?php while ($l = mysqli_fetch_assoc($lapangan)): ?>
                    <option value="<?= $l['id_lapangan'] ?>" 
                      data-harga="<?= $l['harga_member'] ?>"
                      <?= $l['id_lapangan'] == $data['id_lapangan'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($l['nama_lapangan']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- HARI -->
              <div class="col-md-4 mb-3">
                <label>Hari</label>
                <select name="hari" class="form-control" required>
                  <?php
                  $hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                  foreach ($hariList as $h) {
                    $sel = ($data['hari'] == $h) ? 'selected' : '';
                    echo "<option value='$h' $sel>$h</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- JAM -->
              <div class="col-md-4 mb-3">
                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai" value="<?= $data['jam_mulai'] ?>" class="form-control" required>
              </div>

              <div class="col-md-4 mb-3">
                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai" value="<?= $data['jam_selesai'] ?>" class="form-control" required>
              </div>

              <!-- HARGA -->
              <div class="col-md-6 mb-3">
                <label>Harga per Jam Member</label>
                <input type="number" id="harga" name="harga" class="form-control" 
                       value="<?= $data['harga_per_jam_member'] ?>" required>
                <small class="text-muted">Harga otomatis dari lapangan, bisa disesuaikan manual.</small>
              </div>

              <!-- STATUS -->
              <div class="col-md-6 mb-3">
                <label>Status Jadwal</label>
                <select name="status" class="form-control" required>
                  <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                  <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  // Update harga otomatis saat pilih lapangan
  $('#id_lapangan').change(function() {
    const harga = $(this).find(':selected').data('harga');
    $('#harga').val(harga ? harga : '');
  });
});
</script>

<?php include('../includes/footer.php'); ob_end_flush(); ?>
