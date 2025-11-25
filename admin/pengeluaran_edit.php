<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    die("Unauthorized access.");
}

// ====================
// AMBIL ID
// ====================
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id_pengeluaran = intval($_GET['id']);

// ====================
// AMBIL DATA PENGELUARAN BERDASARKAN ID
// ====================
$stmt = $conn->prepare("SELECT * FROM pengeluaran WHERE id_pengeluaran = ?");
$stmt->bind_param("i", $id_pengeluaran);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data tidak ditemukan.");
}

// ====================
// PROSES UPDATE
// ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tanggal = $_POST['tanggal'];
    $kategori = trim($_POST['kategori']);
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];

    $stmt = $conn->prepare("
        UPDATE pengeluaran
        SET tanggal=?, kategori=?, keterangan=?, jumlah=?
        WHERE id_pengeluaran=?
    ");
    $stmt->bind_param("sssdi", $tanggal, $kategori, $keterangan, $jumlah, $id_pengeluaran);
    $stmt->execute();
    $stmt->close();

    // Alert + kembali ke daftar
    echo "<script>
            alert('Data pengeluaran berhasil diperbarui!');
            window.location='pengeluaran.php';
          </script>";
    exit;
}

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">

<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i> Edit Pengeluaran</h1>
        <a href="pengeluaran.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</section>

<section class="content">

<div class="card card-primary shadow-lg border-0">
    <div class="card-header" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
        <h3 class="card-title mb-0"><i class="fas fa-pencil-alt"></i> Form Edit Pengeluaran</h3>
    </div>

    <form method="POST">
        <div class="card-body row g-3">

            <div class="col-md-4">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" 
                       value="<?= $data['tanggal'] ?>" required>
            </div>

            <div class="col-md-4">
                <label>Kategori</label>
                <input type="text" name="kategori" class="form-control"
                       value="<?= htmlspecialchars($data['kategori']) ?>" required>
            </div>

            <div class="col-md-4">
                <label>Jumlah (Rp)</label>
                <input type="number" step="0.01" name="jumlah" class="form-control"
                       value="<?= $data['jumlah'] ?>" required>
            </div>

            <div class="col-md-12">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan']) ?></textarea>
            </div>

        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

</section>
</div>

<?php include('../includes/footer.php'); ?>
