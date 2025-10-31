<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Ambil data user & lapangan
$users = mysqli_query($conn, "SELECT id_user, nama, tipe_user FROM users ORDER BY nama ASC");
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// Proses submit booking baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  mysqli_begin_transaction($conn);
  try {
    $id_user = $_POST['id_user'];
    $id_lapangan = $_POST['id_lapangan'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $payment_method = $_POST['payment_method'];
    $total = str_replace(['.', ','], '', $_POST['total_amount']);
    $dp = str_replace(['.', ','], '', $_POST['dp_amount']);
    $sisa = str_replace(['.', ','], '', $_POST['remaining_amount']);

    // Insert ke booking
    mysqli_query($conn, "
      INSERT INTO booking (id_user, id_lapangan, tanggal, total_amount, dp_amount, remaining_amount, 
      payment_status, payment_method, status, created_at)
      VALUES ('$id_user', '$id_lapangan', '$tanggal', '$total', '$dp', '$sisa', 'belum_bayar', '$payment_method', 'menunggu', NOW())
    ");
    $booking_id = mysqli_insert_id($conn);

    // Insert detail booking
    $qJam = mysqli_query($conn, "
      SELECT id_jadwal_waktu FROM jadwal_waktu 
      WHERE id_lapangan='$id_lapangan' 
      AND jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai'
    ");
    while ($j = mysqli_fetch_assoc($qJam)) {
      mysqli_query($conn, "INSERT INTO detail_booking (id_booking, id_jadwal_waktu) VALUES ('$booking_id', '{$j['id_jadwal_waktu']}')");
    }

    // Insert pembayaran DP otomatis
    mysqli_query($conn, "
      INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, created_at)
      VALUES ('$booking_id', 'DP', '$dp', '$payment_method', 'menunggu', NOW())
    ");

    mysqli_commit($conn);
    $_SESSION['toast_success'] = "Booking berhasil dibuat dan menunggu pembayaran DP.";
    header("Location: booking.php");
    exit;
  } catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast_error'] = "Terjadi kesalahan: " . $e->getMessage();
  }
}
?>


<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i> Tambah Booking Baru</h1>
      <a href="booking.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-calendar-plus mr-2"></i> Form Booking Lapangan</h3>
        </div>

        <form method="POST">
          <div class="card-body">
            <div class="row">
              
              <!-- User -->
              <div class="col-md-6 mb-3">
                <label for="id_user">Pilih Pemesan</label>
                <select name="id_user" id="id_user" class="form-control select2" required>
                  <option value="">-- Pilih Pemesan --</option>
                  <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $u['id_user'] ?>" data-tipe="<?= $u['tipe_user'] ?>">
                      <?= htmlspecialchars($u['nama']) ?> (<?= ucfirst($u['tipe_user']) ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Lapangan -->
              <div class="col-md-6 mb-3">
                <label for="id_lapangan">Pilih Lapangan</label>
                <select name="id_lapangan" id="id_lapangan" class="form-control select2" required>
                  <option value="">-- Pilih Lapangan --</option>
                  <?php while ($l = mysqli_fetch_assoc($lapangan)): ?>
                    <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>">
                      <?= htmlspecialchars($l['nama_lapangan']) ?> - Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Tanggal -->
              <div class="col-md-4 mb-3">
                <label for="tanggal">Tanggal Booking</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>">
              </div>

              <!-- Jam -->
              <div class="col-md-4 mb-3">
                <label for="jam_mulai">Jam Mulai</label>
                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
              </div>

              <div class="col-md-4 mb-3">
                <label for="jam_selesai">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
              </div>

              <!-- Pembayaran -->
              <div class="col-md-6 mb-3">
                <label for="payment_method">Metode Pembayaran</label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="qris">QRIS</option>
                  <option value="tunai">Tunai</option>
                </select>
              </div>

              <!-- Total -->
              <div class="col-md-6 mb-3">
                <label>Total Bayar</label>
                <input type="text" name="total_amount" id="total_amount" class="form-control" readonly>
              </div>

              <div class="col-md-6 mb-3">
                <label>DP (30%)</label>
                <input type="text" name="dp_amount" id="dp_amount" class="form-control" readonly>
              </div>

              <div class="col-md-6 mb-3">
                <label>Sisa Pembayaran</label>
                <input type="text" name="remaining_amount" id="remaining_amount" class="form-control" readonly>
              </div>

            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> Simpan Booking
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

<script>
$(function() {
  // Hitung otomatis total + DP
  function hitungTotal() {
    const harga = parseFloat($('#id_lapangan option:selected').data('harga') || 0);
    const mulai = $('#jam_mulai').val();
    const selesai = $('#jam_selesai').val();
    const tipeUser = $('#id_user option:selected').data('tipe');
    
    if (mulai && selesai) {
      const diff = (new Date(`1970-01-01T${selesai}:00`) - new Date(`1970-01-01T${mulai}:00`)) / (1000 * 60 * 60);
      if (diff > 0) {
        let total = harga * diff;
        if (tipeUser === 'member') total *= 0.9; // Diskon 10% untuk member

        const dp = total * 0.3;
        const sisa = total - dp;

        $('#total_amount').val(total.toLocaleString('id-ID'));
        $('#dp_amount').val(dp.toLocaleString('id-ID'));
        $('#remaining_amount').val(sisa.toLocaleString('id-ID'));
      }
    }
  }

  $('#id_lapangan, #jam_mulai, #jam_selesai, #id_user').on('change', hitungTotal);
});
</script>
