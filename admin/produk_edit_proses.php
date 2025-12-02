<?php
// produk_edit_proses.php (Handle Update Produk)
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

$upload_dir = __DIR__ . '/../uploads/produk/';
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    
    // Ambil data dari POST
    $id_produk = trim($_POST['id_produk'] ?? '');
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga_str = trim($_POST['harga'] ?? '0');
    $kategori = trim($_POST['kategori'] ?? 'Umum');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');
    $foto_lama = trim($_POST['foto_lama'] ?? '');
    
    $harga = filter_var($harga_str, FILTER_VALIDATE_FLOAT);

    // Validasi Dasar
    if (empty($id_produk) || !is_numeric($id_produk) || empty($nama_produk) || $harga === false || $harga <= 0) {
        $_SESSION['toast_error'] = "❌ Data ID, Nama produk, atau Harga tidak valid.";
        header("Location: produk.php");
        exit;
    }

    $nama_file_foto_baru = $foto_lama; // Default: pertahankan foto lama

    $conn->begin_transaction();

    try {
        // --- Proses Upload Foto Baru ---
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
            
            if (!in_array($file_ext, $allowed_ext) || $file['size'] > 5 * 1024 * 1024) { 
                throw new Exception("Ekstensi atau ukuran foto tidak valid (Maks 5MB).");
            }

            // Generate nama unik dan pindahkan
            $nama_file_foto_baru = uniqid('produk_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $nama_file_foto_baru;

            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception("Gagal mengunggah file foto baru.");
            }

            // Hapus foto lama jika ada dan berhasil diunggah
            if ($foto_lama && file_exists($upload_dir . $foto_lama)) {
                unlink($upload_dir . $foto_lama);
            }
        }
        // -------------------------------------

        // Query UPDATE data
        $stmt = $conn->prepare("
            UPDATE produk SET 
            nama_produk = ?, 
            harga = ?, 
            kategori = ?, 
            deskripsi = ?, 
            foto = ?, 
            status = ? 
            WHERE id_produk = ?
        ");
        
        $stmt->bind_param("sdssssi", 
            $nama_produk, 
            $harga, 
            $kategori, 
            $deskripsi, 
            $nama_file_foto_baru, 
            $status,
            $id_produk
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi query UPDATE.");
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['toast_success'] = "✅ Produk **" . htmlspecialchars($nama_produk) . "** berhasil diperbarui!";

    } catch (Exception $e) {
        $conn->rollback();
        // Hapus file baru jika terjadi kegagalan DB
        if ($nama_file_foto_baru !== $foto_lama && $nama_file_foto_baru && file_exists($upload_dir . $nama_file_foto_baru)) {
            unlink($upload_dir . $nama_file_foto_baru);
        }
        $_SESSION['toast_error'] = "❌ Gagal memperbarui produk: " . $e->getMessage();
    }

    header("Location: produk.php");
    exit;

} else {
    $_SESSION['toast_error'] = "⚠️ Akses tidak valid.";
    header("Location: produk.php");
    exit;
}
?>