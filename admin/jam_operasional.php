<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($_POST['jam_buka'] as $hari => $jam_buka) {
    $jam_tutup = $_POST['jam_tutup'][$hari];
    $stmt = $conn->prepare("UPDATE jam_operasional SET jam_buka=?, jam_tutup=? WHERE hari=?");
    $stmt->bind_param("sss", $jam_buka, $jam_tutup, $hari);
    $stmt->execute();
    $stmt->close();
  }

  // Jalankan sinkronisasi otomatis setelah ubah jam operasional
  include 'jadwal_sinkronisasi.php';


  $_SESSION['toast_success'] = 'Jam operasional berhasil diperbarui!';
  header('Location: jam_operasional.php');
  exit;
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-clock mr-2"></i>Jam Operasional</h1>
    </div>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-edit mr-2"></i>Atur Jam Buka & Tutup</h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <table class="table table-bordered text-center">
              <thead class="bg-light">
                <tr>
                  <th>Hari</th>
                  <th>Jam Buka</th>
                  <th>Jam Tutup</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM jam_operasional ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')");
                while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr>
                          <td><strong>{$row['hari']}</strong></td>
                          <td><input type='time' name='jam_buka[{$row['hari']}]' value='{$row['jam_buka']}' class='form-control'></td>
                          <td><input type='time' name='jam_tutup[{$row['hari']}]' value='{$row['jam_tutup']}' class='form-control'></td>
                        </tr>";
                }
                ?>
              </tbody>
            </table>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
