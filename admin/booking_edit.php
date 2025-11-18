<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID booking tidak ditemukan.";
  header("Location: booking.php");
  exit;
}

$id_booking = intval($_GET['id']);

// 🔹 Ambil data booking utama
$q = mysqli_query($conn, "
  SELECT b.*, u.nama AS nama_user, u.tipe_user, l.nama_lapangan, l.harga_per_jam
  FROM booking b
  JOIN users u ON b.id_user = u.id_user
  JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  WHERE b.id_booking = $id_booking
");
$data = mysqli_fetch_assoc($q);
if (!$data) {
  $_SESSION['toast_error'] = "Data booking tidak ditemukan!";
  header("Location: booking.php");
  exit;
}

// 🔹 Ambil pilihan dropdown
$users = mysqli_query($conn, "SELECT id_user, nama, tipe_user FROM users ORDER BY nama ASC");
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan ORDER BY nama_lapangan ASC");

// 🔹 Ambil jadwal jam booking
$jadwal = [];
$j = mysqli_query($conn, "
  SELECT jw.jam_mulai, jw.jam_selesai
  FROM detail_booking db
  JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
  WHERE db.id_booking = $id_booking
");
while ($row = mysqli_fetch_assoc($j)) $jadwal[] = $row;

// 🔹 Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_user       = $_POST['id_user'];
  $id_lapangan   = $_POST['id_lapangan'];
  $tanggal       = $_POST['tanggal'];
  $jam_mulai     = $_POST['jam_mulai'];
  $jam_selesai   = $_POST['jam_selesai'];

  // Hitung total, DP, dan sisa bayar
  $hargaPerJam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga_per_jam FROM lapangan WHERE id_lapangan='$id_lapangan'"))['harga_per_jam'];
  $durasi = (strtotime($jam_selesai) - strtotime($jam_mulai)) / 3600;
  $total = $hargaPerJam * $durasi;
  $dp = $total * 0.3;
  $sisa = $total - $dp;

  // Update data utama booking
  mysqli_query($conn, "
    UPDATE booking SET
      id_user='$id_user',
      id_lapangan='$id_lapangan',
      tanggal='$tanggal',
      total_amount='$total',
      dp_amount='$dp',
      remaining_amount='$sisa',
      updated_at=NOW()
    WHERE id_booking=$id_booking
  ");

  // Hapus detail_booking lama dan buat baru
  mysqli_query($conn, "DELETE FROM detail_booking WHERE id_booking=$id_booking");
  $jadwalBaru = mysqli_query($conn, "
    SELECT id_jadwal_waktu 
    FROM jadwal_waktu 
    WHERE id_lapangan='$id_lapangan'
      AND jam_mulai >= '$jam_mulai'
      AND jam_selesai <= '$jam_selesai'
  ");
  while ($j = mysqli_fetch_assoc($jadwalBaru)) {
    mysqli_query($conn, "
      INSERT INTO detail_booking (id_booking, id_jadwal_waktu)
      VALUES ('$id_booking', '{$j['id_jadwal_waktu']}')
    ");
  }

  $_SESSION['toast_success'] = "Data booking berhasil diperbarui tanpa mengubah status.";
  header("Location: booking.php");
  exit;
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Data Booking</h1>
      <a href="booking.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i> Ubah Data Booking</h3>
        </div>

        <form method="POST">
          <div class="card-body">
            <div class="row">

              <!-- Pemesan -->
              <div class="col-md-6 mb-3">
                <label for="id_user">Pemesan</label>
                <select name="id_user" id="id_user" class="form-control select2" required>
                  <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $u['id_user'] ?>" <?= $u['id_user']==$data['id_user']?'selected':'' ?>>
                      <?= htmlspecialchars($u['nama']) ?> (<?= ucfirst($u['tipe_user']) ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Lapangan -->
              <div class="col-md-6 mb-3">
                <label for="id_lapangan">Lapangan</label>
                <select name="id_lapangan" id="id_lapangan" class="form-control select2" required>
                  <?php while ($l = mysqli_fetch_assoc($lapangan)): ?>
                    <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>" <?= $l['id_lapangan']==$data['id_lapangan']?'selected':'' ?>>
                      <?= htmlspecialchars($l['nama_lapangan']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Tanggal -->
              <div class="col-md-4 mb-3">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= $data['tanggal'] ?>" required>
              </div>

              <!-- Jam -->
              <div class="col-md-4 mb-3">
                <label for="jam_mulai">Jam Mulai</label>
                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" value="<?= $jadwal[0]['jam_mulai'] ?? '' ?>" required>
              </div>

              <div class="col-md-4 mb-3">
                <label for="jam_selesai">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" value="<?= $jadwal[0]['jam_selesai'] ?? '' ?>" required>
              </div>

              <!-- Total -->
              <div class="col-md-4 mb-3">
                <label>Total Biaya</label>
                <input type="text" name="total_amount" id="total_amount" class="form-control" value="<?= number_format($data['total_amount'],0,',','.') ?>" readonly>
              </div>

              <div class="col-md-4 mb-3">
                <label>DP (30%)</label>
                <input type="text" name="dp_amount" id="dp_amount" class="form-control" value="<?= number_format($data['dp_amount'],0,',','.') ?>" readonly>
              </div>

              <div class="col-md-4 mb-3">
                <label>Sisa Pembayaran</label>
                <input type="text" name="remaining_amount" id="remaining_amount" class="form-control" value="<?= number_format($data['remaining_amount'],0,',','.') ?>" readonly>
              </div>

              <!-- Info -->
              <div class="col-12">
                <div class="alert alert-info mt-3">
                  <i class="fas fa-info-circle"></i> 
                  Status <strong>Booking</strong> dan <strong>Pembayaran</strong> dikelola otomatis melalui halaman <em>Booking Action</em> & <em>Pembayaran Validasi</em>.
                </div>
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

<?php include('../includes/footer.php'); ?>
