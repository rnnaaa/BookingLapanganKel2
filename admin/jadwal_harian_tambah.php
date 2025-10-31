<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_lapangan = $_POST['id_lapangan'];
  $tanggal = $_POST['tanggal'];
  $status_hari = $_POST['status_hari'] ?? 'tersedia';
  $generate_week = isset($_POST['generate_week']); // kalau user centang generate otomatis

  if (empty($id_lapangan)) {
    $_SESSION['toast_error'] = 'Lapangan wajib dipilih.';
    header('Location: jadwal_harian_tambah.php');
    exit;
  }

  // Jika generate otomatis 7 hari ke depan
  if ($generate_week) {
    $today = date('Y-m-d');
    for ($i = 0; $i < 7; $i++) {
      $tgl = date('Y-m-d', strtotime("+$i day", strtotime($today)));
      mysqli_query($conn, "
        INSERT IGNORE INTO jadwal_harian (id_lapangan, tanggal, status_hari, created_at)
        VALUES ('$id_lapangan', '$tgl', 'tersedia', NOW())
      ");
    }
    $_SESSION['toast_success'] = 'Jadwal harian 7 hari ke depan berhasil digenerate.';
    header('Location: jadwal_harian.php');
    exit;
  }

  // Jika tambah 1 tanggal manual
  $cek = mysqli_query($conn, "
    SELECT * FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'
  ");
  if (mysqli_num_rows($cek) > 0) {
    $_SESSION['toast_error'] = 'Tanggal ini sudah ada untuk lapangan tersebut.';
    header('Location: jadwal_harian_tambah.php');
    exit;
  }

  mysqli_query($conn, "
    INSERT INTO jadwal_harian (id_lapangan, tanggal, status_hari, created_at)
    VALUES ('$id_lapangan', '$tanggal', '$status_hari', NOW())
  ");

  $_SESSION['toast_success'] = 'Jadwal harian berhasil ditambahkan.';
  header('Location: jadwal_harian.php');
  exit;
}

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-day mr-2"></i> Tambah Jadwal Harian</h1>
      <a href="jadwal_harian.php" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle mr-2"></i> Form Tambah Jadwal Harian</h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label>Lapangan</label>
              <select name="id_lapangan" class="form-control" required>
                <option value="">-- Pilih Lapangan --</option>
                <?php
                $lap = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'");
                while ($l = mysqli_fetch_assoc($lap)) {
                  echo "<option value='{$l['id_lapangan']}'>{$l['nama_lapangan']} ({$l['tipe']})</option>";
                }
                ?>
              </select>
            </div>

            <div class="form-group">
              <label>Tanggal</label>
              <input type="date" name="tanggal" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Status Hari</label>
              <select name="status_hari" class="form-control">
                <option value="tersedia">Tersedia</option>
                <option value="penuh">Penuh</option>
                <option value="libur">Libur</option>
              </select>
            </div>

            <div class="form-group form-check mt-3">
              <input type="checkbox" class="form-check-input" id="generate_week" name="generate_week">
              <label class="form-check-label" for="generate_week">Generate Otomatis untuk 7 Hari ke Depan</label>
            </div>

            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save mr-1"></i> Simpan Jadwal</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
