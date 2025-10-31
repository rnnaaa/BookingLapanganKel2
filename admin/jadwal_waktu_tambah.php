<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_lapangan = $_POST['id_lapangan'];
  $jam_mulai = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];
  $harga_per_slot = $_POST['harga_per_slot'];

  // Validasi
  if (empty($id_lapangan) || empty($jam_mulai) || empty($jam_selesai) || empty($harga_per_slot)) {
    $_SESSION['toast_error'] = 'Semua field wajib diisi.';
    header('Location: jadwal_waktu_tambah.php');
    exit;
  }

  // Cek duplikat waktu
  $cek = mysqli_query($conn, "
    SELECT * FROM jadwal_waktu 
    WHERE id_lapangan='$id_lapangan' 
    AND jam_mulai='$jam_mulai' 
    AND jam_selesai='$jam_selesai'
  ");
  if (mysqli_num_rows($cek) > 0) {
    $_SESSION['toast_error'] = 'Waktu ini sudah terdaftar untuk lapangan tersebut.';
    header('Location: jadwal_waktu_tambah.php');
    exit;
  }

  // Insert jadwal waktu
  $insert = mysqli_query($conn, "
    INSERT INTO jadwal_waktu (id_lapangan, jam_mulai, jam_selesai, harga_per_slot, created_at)
    VALUES ('$id_lapangan', '$jam_mulai', '$jam_selesai', '$harga_per_slot', NOW())
  ");

  // Setelah insert, otomatis generate untuk 7 hari ke depan (jadwal_harian)
  if ($insert) {
    $today = date('Y-m-d');
    for ($i = 0; $i < 7; $i++) {
      $tanggal = date('Y-m-d', strtotime("+$i day", strtotime($today)));

      // Pastikan jadwal_harian ada
      $cekHari = mysqli_query($conn, "
        SELECT * FROM jadwal_harian 
        WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'
      ");
      if (mysqli_num_rows($cekHari) == 0) {
        mysqli_query($conn, "
          INSERT INTO jadwal_harian (id_lapangan, tanggal, status_hari, created_at)
          VALUES ('$id_lapangan', '$tanggal', 'tersedia', NOW())
        ");
      }
    }

    $_SESSION['toast_success'] = 'Jadwal waktu berhasil ditambahkan dan disinkronkan ke jadwal harian.';
    header('Location: jadwal_waktu.php');
    exit;
  } else {
    $_SESSION['toast_error'] = 'Gagal menambahkan jadwal waktu.';
    header('Location: jadwal_waktu_tambah.php');
    exit;
  }
}

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-clock mr-2"></i> Tambah Jadwal Waktu</h1>
      <a href="jadwal_waktu.php" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle mr-2"></i> Form Tambah Jadwal Waktu</h3>
        </div>
        <div class="card-body">
          <form action="" method="POST">
            <div class="form-group">
              <label for="id_lapangan">Lapangan</label>
              <select name="id_lapangan" id="id_lapangan" class="form-control" required>
                <option value="">-- Pilih Lapangan --</option>
                <?php
                $lap = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'");
                while ($l = mysqli_fetch_assoc($lap)) {
                  echo "<option value='{$l['id_lapangan']}'>{$l['nama_lapangan']} ({$l['tipe']})</option>";
                }
                ?>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6">
                <label for="jam_mulai">Jam Mulai</label>
                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label for="jam_selesai">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="harga_per_slot">Harga per Slot (Rp)</label>
              <input type="number" name="harga_per_slot" id="harga_per_slot" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save mr-1"></i> Simpan Jadwal</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
