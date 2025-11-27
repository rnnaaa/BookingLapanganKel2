<?php
// produk_hapus.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
date_default_timezone_set('Asia/Jakarta');

// Anda mungkin perlu auth_check di sini
// require_once __DIR__ . '/auth_check.php'; 
require_once __DIR__ . '/../config/database.php';

// Validasi ID
$id_produk = $_GET['id'] ?? null;

if (empty($id_produk) || !is_numeric($id_produk)) {
    $_SESSION['toast_error'] = "❌ ID Produk tidak valid.";
    header("Location: produk.php");
    exit;
}

$upload_dir = __DIR__ . '/../uploads/produk/';

$conn->begin_transaction();

try {
    // 1. Ambil nama file foto sebelum dihapus dari DB
    $stmt = $conn->prepare("SELECT foto, nama_produk FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stmt->close();
    
    if (!$produk) {
        throw new Exception("Produk dengan ID $id_produk tidak ditemukan.");
    }
    
    $nama_file_foto = $produk['foto'];
    $nama_produk = $produk['nama_produk'];

    // 2. Hapus data dari database
    $stmtDelete = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmtDelete->bind_param("i", $id_produk);
    
    if (!$stmtDelete->execute()) {
        throw new Exception("Gagal menghapus data dari database.");
    }
    $stmtDelete->close();
    
    // 3. Hapus file fisik (jika ada)
    if (!empty($nama_file_foto)) {
        $file_path = $upload_dir . $nama_file_foto;
        if (file_exists($file_path)) {
            // Cek apakah file benar-benar terhapus
            if (!unlink($file_path)) {
                // Log warning, tapi tidak membatalkan transaksi karena data DB sudah terhapus
                error_log("Gagal menghapus file fisik: " . $file_path);
            }
        }
    }

    $conn->commit();
    $_SESSION['toast_success'] = "✅ Produk **" . htmlspecialchars($nama_produk) . "** berhasil dihapus.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast_error'] = "❌ Gagal menghapus produk: " . $e->getMessage();

}

header("Location: produk.php");
exit;
?>