<?php
ob_start(); 
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

$bookings=mysqli_query($conn,"
  SELECT id_booking, tanggal, total_amount, dp_amount, remaining_amount, payment_status 
  FROM booking 
  WHERE payment_status!='lunas' ORDER BY created_at DESC
");

if($_SERVER['REQUEST_METHOD']==='POST'){
  $booking_id=$_POST['booking_id'];
  $tipe=$_POST['tipe'];
  $amount=str_replace(['.',','],'',$_POST['amount']);
  $method=$_POST['method'];

  mysqli_begin_transaction($conn);
  try{
    mysqli_query($conn,"
      INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, created_at)
      VALUES ('$booking_id','$tipe','$amount','$method','menunggu',NOW())
    ");
    mysqli_query($conn,"
      UPDATE booking SET payment_status='menunggu_verifikasi' WHERE id_booking='$booking_id'
    ");
    mysqli_commit($conn);
    $_SESSION['toast_success']='Pembayaran berhasil ditambahkan.';
    header('Location: pembayaran.php');
    exit;
  }catch(Exception $e){
    mysqli_rollback($conn);
    $_SESSION['toast_error']="Gagal: ".$e->getMessage();
  }
}
?>
<div class="content-wrapper animate__animated animate__fadeIn">
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-plus-circle mr-2"></i> Tambah Pembayaran</h1>
    <a href="pembayaran.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div>
</section>
<section class="content">
<div class="container-fluid">
<div class="card shadow-lg border-0">
<div class="card-header bg-info text-white">
<h3 class="card-title mb-0"><i class="fas fa-credit-card mr-2"></i> Form Tambah Pembayaran</h3>
</div>
<form method="POST">
<div class="card-body">
<div class="row">
  <div class="col-md-6 mb-3">
    <label for="booking_id">Pilih Booking</label>
    <select name="booking_id" id="booking_id" class="form-control select2" required>
      <option value="">-- Pilih Booking --</option>
      <?php while($b=mysqli_fetch_assoc($bookings)):?>
        <option value="<?=$b['id_booking']?>">
          #<?=$b['id_booking']?> - <?=$b['tanggal']?> (<?=$b['payment_status']?>)
        </option>
      <?php endwhile;?>
    </select>
  </div>
  <div class="col-md-6 mb-3">
    <label>Tipe Pembayaran</label>
    <select name="tipe" class="form-control" required>
      <option value="DP">DP</option>
      <option value="Pelunasan">Pelunasan</option>
    </select>
  </div>
  <div class="col-md-6 mb-3">
    <label>Nominal</label>
    <input type="text" name="amount" id="amount" class="form-control" required>
  </div>
  <div class="col-md-6 mb-3">
    <label>Metode</label>
    <select name="method" class="form-control" required>
      <option value="bank_transfer">Bank Transfer</option>
      <option value="qris">QRIS</option>
      <option value="tunai">Tunai</option>
    </select>
  </div>
</div>
</div>
<div class="card-footer text-right">
  <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
</div>
</form>
</div>
</div>
</section>
</div>
<?php include('../includes/footer.php');?>
