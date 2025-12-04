<?php
// File: pengeluaran_edit.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// session_start();
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    // Jika tidak ada session, kembalikan ke halaman login atau tampilkan pesan error
    // header('Location: login.php');
    die("Unauthorized access. Silakan login kembali.");
}

$current_user_id = $_SESSION['id_user'];

// ====================
// AMBIL ID
// ====================
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id_pengeluaran = intval($_GET['id']);

// ====================
// AMBIL DATA PENGELUARAN BERDASARKAN ID (dengan JOIN untuk mendapatkan nama)
// ====================
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        u_input.nama AS input_by_nama,
        u_update.nama AS updated_by_nama
    FROM pengeluaran p 
    LEFT JOIN users u_input ON p.input_by = u_input.id_user
    LEFT JOIN users u_update ON p.updated_by = u_update.id_user
    WHERE p.id_pengeluaran = ?
");
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
    
    // Tambahkan Output Buffering untuk mencegah error header() pada redirect
    ob_start(); 
    
    try {
        $tanggal = $_POST['tanggal'];
        $kategori = trim($_POST['kategori']);
        $jumlah = floatval($_POST['jumlah']);
        $keterangan = $_POST['keterangan'];
        $updated_at = date('Y-m-d H:i:s'); // Waktu saat ini

        $stmt = $conn->prepare("
            UPDATE pengeluaran
            SET tanggal=?, kategori=?, keterangan=?, jumlah=?, updated_by=?, updated_at=?
            WHERE id_pengeluaran=?
        ");
        
        // KOREKSI BIND PARAMETER: sssdisi
        // s (tanggal), s (kategori), s (keterangan), d (jumlah), 
        // i (current_user_id), s (updated_at), i (id_pengeluaran)
        $stmt->bind_param("sssdisi", $tanggal, $kategori, $keterangan, $jumlah, $current_user_id, $updated_at, $id_pengeluaran);
        
        $stmt->execute();
        $stmt->close();

        // Hapus output buffer sebelum redirect
        ob_end_clean();
        
        // Set session toast message sukses
        $_SESSION['toast_success'] = 'Data pengeluaran berhasil diperbarui!';
        header('Location: pengeluaran.php');
        exit;

    } catch (Exception $e) {
        // Hapus output buffer sebelum redirect
        ob_end_clean();
        
        // Set session toast message error
        $_SESSION['toast_error'] = 'Gagal memperbarui data: ' . $e->getMessage();
        header('Location: pengeluaran.php');
        exit;
    }
}
include('../includes/header.php');
// include('../includes/topbar.php');
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
    <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
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
                <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="fas fa-user-tag"></i> Diinput Oleh: 
                    <span class="fw-bold"><?= htmlspecialchars($data['input_by_nama'] ?? 'N/A') ?></span>
                    <br>
                    <i class="fas fa-clock"></i> Diinput Pada: 
                    <span class="fw-bold"><?= $data['created_at'] ? date('d-m-Y H:i', strtotime($data['created_at'])) : 'N/A' ?></span>
                </small>
            </div>

            <div class="col-md-6 text-end">
                <small class="text-muted">
                    <i class="fas fa-user-edit"></i> Terakhir Diubah Oleh: 
                    <span class="fw-bold"><?= htmlspecialchars($data['updated_by_nama'] ?? 'N/A') ?></span>
                    <br>
                    <i class="fas fa-history"></i> Terakhir Diubah Pada: 
                    <span class="fw-bold"><?= $data['updated_at'] ? date('d-m-Y H:i', strtotime($data['updated_at'])) : 'N/A' ?></span>
                </small>
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