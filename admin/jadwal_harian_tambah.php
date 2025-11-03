<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lapangan = $_POST['id_lapangan'] ?? null;
    $tanggal_awal = $_POST['tanggal'] ?? null;
    $status_hari = $_POST['status_hari'] ?? 'tersedia';
    $generate_week = isset($_POST['generate_week']);

    // Pengecekan dasar
    if (empty($id_lapangan) || empty($tanggal_awal)) {
        echo "<script>alert('Semua field wajib diisi!'); window.history.back();</script>";
        exit;
    }

    try {
        // Menggunakan begin_transaction() yang benar untuk objek mysqli
        if (!$conn->begin_transaction()) {
            throw new Exception("Gagal memulai transaksi.");
        }

        // Fungsi untuk menentukan nama hari dari tanggal (huruf kecil untuk ENUM database)
        function getNamaHari($tanggal) {
            $namaHariInggris = date('l', strtotime($tanggal));
            $daftarHari = [
                'Monday' => 'senin',
                'Tuesday' => 'selasa',
                'Wednesday' => 'rabu',
                'Thursday' => 'kamis',
                'Friday' => 'jumat',
                'Saturday' => 'sabtu',
                'Sunday' => 'minggu'
            ];
            // Mengembalikan nama hari dalam huruf kecil (sesuai ENUM di jadwal_harian.sql)
            return $daftarHari[$namaHariInggris];
        }

        // SQL Query yang menyertakan kolom 'hari'
        $sql_insert = "INSERT INTO jadwal_harian (id_lapangan, hari, tanggal, status_hari) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status_hari = VALUES(status_hari), hari = VALUES(hari)";
        $stmt = $conn->prepare($sql_insert);

        if ($generate_week) {
            // generate 7 hari ke depan
            for ($i = 0; $i < 7; $i++) {
                $tanggal_baru = date('Y-m-d', strtotime("+$i day", strtotime($tanggal_awal)));
                
                // Variabel $hari_baru DIBUAT dan DITENTUKAN nilainya di dalam loop
                $hari_baru = getNamaHari($tanggal_baru);

                // Eksekusi dengan 4 parameter (id_lapangan, hari, tanggal, status_hari)
                $stmt->execute([$id_lapangan, $hari_baru, $tanggal_baru, $status_hari]);
            }
        } else {
            // hanya 1 hari saja
            $hari_tunggal = getNamaHari($tanggal_awal);

            // Eksekusi untuk hari tunggal
            $stmt->execute([$id_lapangan, $hari_tunggal, $tanggal_awal, $status_hari]);
        }

        $conn->commit();
        echo "<script>alert('Jadwal berhasil ditambahkan!'); window.location='jadwal_harian.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: Gagal menyimpan jadwal. " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
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
                if (isset($conn)) {
                    $lap = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'");
                    while ($l = mysqli_fetch_assoc($lap)) {
                      echo "<option value='{$l['id_lapangan']}'>{$l['nama_lapangan']} ({$l['tipe']})</option>";
                    }
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