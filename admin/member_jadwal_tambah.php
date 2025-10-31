<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

$members = mysqli_query($conn, "SELECT m.id_member, u.nama FROM member m JOIN users u ON m.id_user = u.id_user WHERE m.status='aktif'");
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status='aktif'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_member = $_POST['id_member'];
  $id_lapangan = $_POST['id_lapangan'];
  $hari = $_POST['hari'];
  $jam_mulai = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];
  $harga = $_POST['harga'];

  // Validasi jadwal bentrok
  $cek = mysqli_query($conn, "
    SELECT * FROM member_jadwal 
    WHERE id_lapangan='$id_lapangan' 
    AND hari='$hari' 
    AND (
      (jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')
    )
  ");
  if (mysqli_num_rows($cek) > 0) {
    $_SESSION['toast_error'] = "⚠️ Jadwal bentrok dengan member lain di lapangan ini!";
  } else {
    mysqli_query($conn, "
      INSERT INTO member_jadwal (id_member, id_lapangan, hari, jam_mulai, jam_selesai, harga_per_jam_member, status, created_at)
      VALUES ('$id_member', '$id_lapangan', '$hari', '$jam_mulai', '$jam_selesai', '$harga', 'aktif', NOW())
    ");
    $_SESSION['toast_success'] = "✅ Jadwal rutin berhasil ditambahkan!";
    header("Location: member_jadwal.php");
    exit;
  }
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-plus mr-2"></i> Tambah Jadwal Rutin Member</h1>
      <a href="member_jadwal.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-clock mr-2"></i> Form Jadwal Rutin</h3>
        </div>

        <form method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="id_member">Pilih Member</label>
                <select name="id_member" id="id_member" class="form-control select2" required>
                  <option value="">-- Pilih Member --</option>
                  <?php while ($m = mysqli_fetch_assoc($members)): ?>
                    <option value="<?= $m['id_member'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="id_lapangan">Pilih Lapangan</label>
                <select name="id_lapangan" id="id_lapangan" class="form-control select2" required>
                  <option value="">-- Pilih Lapangan --</option>
                  <?php while ($l = mysqli_fetch_assoc($lapangan)): ?>
                    <option value="<?= $l['id_lapangan'] ?>"><?= htmlspecialchars($l['nama_lapangan']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Hari</label>
                <select name="hari" class="form-control" required>
                  <option>Senin</option><option>Selasa</option><option>Rabu</option>
                  <option>Kamis</option><option>Jumat</option><option>Sabtu</option><option>Minggu</option>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control" required>
              </div>

              <div class="col-md-4 mb-3">
                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" required>
              </div>

              <div class="col-md-6 mb-3">
                <label>Harga per Jam Member</label>
                <input type="number" name="harga" class="form-control" placeholder="Masukkan harga" required>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save"></i> Simpan Jadwal
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php');
ob_end_flush();
?>
