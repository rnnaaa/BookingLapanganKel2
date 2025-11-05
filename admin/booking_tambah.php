<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Ambil data user & lapangan
$users = mysqli_query($conn, "SELECT id_user, nama, tipe_user FROM users ORDER BY nama ASC");
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan, harga_per_jam, harga_member FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// Proses submit booking baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  mysqli_begin_transaction($conn);
  try {
    $id_user = $_POST['id_user'];
    $id_lapangan = $_POST['id_lapangan'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $total = str_replace(['.', ','], '', $_POST['total_amount']);
    $dp = str_replace(['.', ','], '', $_POST['dp_amount']);
    $sisa = str_replace(['.', ','], '', $_POST['remaining_amount']);
    $payment_method = $_POST['payment_method'];

    // Ambil tipe booking otomatis dari user
    $qUser = mysqli_query($conn, "SELECT tipe_user FROM users WHERE id_user='$id_user'");
    $dUser = mysqli_fetch_assoc($qUser);
    $tipe_booking = $dUser ? $dUser['tipe_user'] : 'manual';

    // Tambah booking baru
    mysqli_query($conn, "
      INSERT INTO booking (
        id_user, id_lapangan, tipe_booking, tanggal, total_amount, dp_amount, remaining_amount,
        status, payment_status, payment_method, created_at
      )
      VALUES (
        '$id_user', '$id_lapangan', '$tipe_booking', '$tanggal', '$total', '$dp', '$sisa',
        'menunggu', 'belum_bayar', '$payment_method', NOW()
      )
    ");

    $booking_id = mysqli_insert_id($conn);

    // Simpan ke detail_booking
    $qJam = mysqli_query($conn, "
      SELECT id_jadwal_waktu FROM jadwal_waktu 
      WHERE id_lapangan='$id_lapangan' 
      AND jam_mulai >= '$jam_mulai' 
      AND jam_selesai <= '$jam_selesai'
    ");
    while ($j = mysqli_fetch_assoc($qJam)) {
      mysqli_query($conn, "INSERT INTO detail_booking (id_booking, id_jadwal_waktu) VALUES ('$booking_id', '{$j['id_jadwal_waktu']}')");
    }

    mysqli_commit($conn);
    $_SESSION['toast_success'] = "Booking berhasil dibuat. Silakan lakukan pembayaran di menu Pembayaran.";
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

              <!-- Pemesan -->
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
                    <option 
                      value="<?= $l['id_lapangan'] ?>" 
                      data-harga-regular="<?= $l['harga_per_jam'] ?>" 
                      data-harga-member="<?= $l['harga_member'] ?>">
                      <?= htmlspecialchars($l['nama_lapangan']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Tanggal dan Jam -->
              <div class="col-md-4 mb-3">
                <label for="tanggal">Tanggal Booking</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>">
              </div>

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

              <!-- Harga Otomatis -->
              <div class="col-md-6 mb-3">
                <label>Harga Per Jam (Rp)</label>
                <input type="text" id="harga_per_jam" class="form-control" readonly>
              </div>

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

<?php include('../includes/footer.php'); ob_end_flush(); ?>

<script>
$(document).ready(function() {
  // Aktifkan semua elemen Select2
  $('.select2').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: 'Pilih atau cari data...',
    allowClear: true
  });

  // Update harga otomatis sesuai tipe user dan lapangan
  function updateHarga() {
    const tipeUser = $('#id_user option:selected').data('tipe');
    const lapangan = $('#id_lapangan option:selected');
    if (!lapangan.val() || !tipeUser) return;

    const harga = (tipeUser === 'member')
      ? parseFloat(lapangan.data('harga-member') || 0)
      : parseFloat(lapangan.data('harga-regular') || 0);

    $('#harga_per_jam').val(harga.toLocaleString('id-ID'));
    hitungTotal();
  }

  // Hitung total otomatis
  function hitungTotal() {
    const harga = parseFloat($('#harga_per_jam').val().replace(/\./g, '') || 0);
    const mulai = $('#jam_mulai').val();
    const selesai = $('#jam_selesai').val();

    if (mulai && selesai && harga > 0) {
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

  // Event trigger
  $('#id_user, #id_lapangan').on('change', updateHarga);
  $('#jam_mulai, #jam_selesai').on('change', hitungTotal);
});
</script>

