<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$id_booking = intval($_GET['id'] ?? 0);
$tipe = strtolower($_GET['tipe'] ?? 'dp');

$q = mysqli_query($conn, "SELECT * FROM booking WHERE id_booking='$id_booking'");
$booking = mysqli_fetch_assoc($q);

if (!$booking) {
  echo "<script>alert('Data booking tidak ditemukan!'); window.location='booking_user.php';</script>";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $method = $_POST['method'];
  $tipe = $_POST['tipe'];
  $amount = str_replace(['.',','],'',$_POST['amount']);

  $target_dir = "../uploads/";
  $file_name = time() . "_" . basename($_FILES["bukti"]["name"]);
  $target_file = $target_dir . $file_name;

  if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $target_file)) {
    mysqli_query($conn, "
      INSERT INTO pembayaran (booking_id, tipe, amount, method, bukti_path, status_verifikasi, tanggal_upload, created_at)
      VALUES ('$id_booking', '$tipe', '$amount', '$method', '$file_name', 'menunggu', NOW(), NOW())
    ");
    mysqli_query($conn, "UPDATE booking SET payment_status='menunggu_verifikasi' WHERE id_booking='$id_booking'");
    echo "<script>alert('Bukti pembayaran berhasil diunggah!'); window.location='booking_user.php';</script>";
  } else {
    echo "<script>alert('Upload gagal, coba lagi!');</script>";
  }
}
?>

<div class="container py-4">
  <h3 class="mb-4"><i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran <?= ucfirst($tipe) ?></h3>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="tipe" value="<?= $tipe ?>">

    <div class="mb-3">
      <label>Metode Pembayaran</label>
      <select name="method" class="form-control" required>
        <option value="">-- Pilih --</option>
        <option value="bank_transfer">Bank Transfer</option>
        <option value="qris">QRIS</option>
        <option value="tunai">Tunai</option>
      </select>
    </div>

    <div class="mb-3">
      <label>Jumlah Bayar</label>
      <input type="text" name="amount" class="form-control" 
             value="<?= $tipe=='dp' ? number_format($booking['dp_amount'],0,',','.') : number_format($booking['remaining_amount'],0,',','.') ?>" readonly>
    </div>

    <div class="mb-3">
      <label>Upload Bukti Pembayaran</label>
      <input type="file" name="bukti" class="form-control" accept="image/*" required>
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Upload</button>
    <a href="booking_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
  </form>
</div>
