<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Validasi ID
if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID booking tidak ditemukan.";
  header("Location: booking.php");
  exit;
}

$id_booking = intval($_GET['id']);

// Ambil data booking
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

// Ambil data dropdown
$users = mysqli_query($conn, "SELECT id_user, nama, tipe_user FROM users ORDER BY nama ASC");
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan ORDER BY nama_lapangan ASC");

// Ambil jadwal booking
$jadwal = [];
$j = mysqli_query($conn, "
  SELECT jw.jam_mulai, jw.jam_selesai
  FROM detail_booking db
  JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
  WHERE db.id_booking = $id_booking
");
while ($row = mysqli_fetch_assoc($j)) {
  $jadwal[] = $row;
}

// Proses update booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_user = $_POST['id_user'];
  $id_lapangan = $_POST['id_lapangan'];
  $tanggal = $_POST['tanggal'];
  $status = $_POST['status'];
  $payment_status = $_POST['payment_status'];
  $payment_method = $_POST['payment_method'];
  $total = str_replace('.', '', $_POST['total_amount']);
  $dp = str_replace('.', '', $_POST['dp_amount']);
  $sisa = str_replace('.', '', $_POST['remaining_amount']);
  $jam_mulai = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];

  // Update data utama
  mysqli_query($conn, "
    UPDATE booking SET
      id_user='$id_user',
      id_lapangan='$id_lapangan',
      tanggal='$tanggal',
      total_amount='$total',
      dp_amount='$dp',
      remaining_amount='$sisa',
      status='$status',
      payment_status='$payment_status',
      payment_method='$payment_method',
      updated_at=NOW()
    WHERE id_booking=$id_booking
  ");

  // Hapus detail_booking lama
  mysqli_query($conn, "DELETE FROM detail_booking WHERE id_booking=$id_booking");

  // Tambahkan ulang detail_booking baru berdasarkan jam baru
  $jadwalBaru = mysqli_query($conn, "
    SELECT id_jadwal_waktu FROM jadwal_waktu 
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

  // Update pembayaran jika DP berubah
  $pembayaran = mysqli_query($conn, "
    SELECT * FROM pembayaran WHERE booking_id='$id_booking' AND tipe='DP'
  ");
  if (mysqli_num_rows($pembayaran) > 0) {
    mysqli_query($conn, "
      UPDATE pembayaran SET amount='$dp', updated_at=NOW()
      WHERE booking_id='$id_booking' AND tipe='DP'
    ");
  } else {
    mysqli_query($conn, "
      INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, created_at)
      VALUES ('$id_booking', 'DP', '$dp', '$payment_method', 'menunggu', NOW())
    ");
  }

  $_SESSION['toast_success'] = "Data booking berhasil diperbarui.";
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

              <!-- Status -->
              <div class="col-md-4 mb-3">
                <label>Status Booking</label>
                <select name="status" class="form-control">
                  <?php
                  $statuses = ['menunggu','disetujui','ditolak','selesai','dibatalkan'];
                  foreach ($statuses as $st) {
                    $sel = $st==$data['status'] ? 'selected' : '';
                    echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                  }
                  ?>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Status Pembayaran</label>
                <select name="payment_status" class="form-control">
                  <?php
                  $pays = ['belum_bayar','menunggu_verifikasi','dp_bayar','lunas','dibatalkan'];
                  foreach ($pays as $st) {
                    $sel = $st==$data['payment_status'] ? 'selected' : '';
                    echo "<option value='$st' $sel>" . ucfirst(str_replace('_',' ',$st)) . "</option>";
                  }
                  ?>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Metode Bayar</label>
                <select name="payment_method" class="form-control">
                  <?php
                  $methods = ['bank_transfer','qris','tunai'];
                  foreach ($methods as $m) {
                    $sel = $m==$data['payment_method']?'selected':'';
                    echo "<option value='$m' $sel>" . ucfirst(str_replace('_',' ',$m)) . "</option>";
                  }
                  ?>
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

<?php include('../includes/footer.php'); ?>

<script>
$(function() {
  function hitungTotal() {
    const harga = parseFloat($('#id_lapangan option:selected').data('harga') || 0);
    const mulai = $('#jam_mulai').val();
    const selesai = $('#jam_selesai').val();
    if (mulai && selesai) {
      const diff = (new Date(`1970-01-01T${selesai}:00`) - new Date(`1970-01-01T${mulai}:00`)) / 3600000;
      if (diff > 0) {
        const total = harga * diff;
        const dp = total * 0.3;
        const sisa = total - dp;
        $('#total_amount').val(total.toLocaleString('id-ID'));
        $('#dp_amount').val(dp.toLocaleString('id-ID'));
        $('#remaining_amount').val(sisa.toLocaleString('id-ID'));
      }
    }
  }
  $('#id_lapangan, #jam_mulai, #jam_selesai').on('change', hitungTotal);
});
</script>
