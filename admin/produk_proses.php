<?php
// produk_proses.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

// Tentukan direktori upload
$upload_dir = __DIR__ . '/../uploads/produk/';
// Pastikan direktori ada
if (!is_dir($upload_dir)) {
    // Gunakan try-catch atau error_log untuk menangani kegagalan mkdir jika perlu
    if (!mkdir($upload_dir, 0777, true)) {
        $_SESSION['toast_error'] = "❌ Gagal membuat direktori upload: {$upload_dir}";
        header("Location: produk.php");
        exit;
    }
}

// Ambil aksi yang diminta (asumsi aksi 'tambah' dari form produk.php)
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'tambah') {
    
    // Ambil data dari POST
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga_str = trim($_POST['harga'] ?? '0');
    $kategori = trim($_POST['kategori'] ?? 'Umum');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');
    
    // Konversi harga ke format float yang aman
    $harga = filter_var($harga_str, FILTER_VALIDATE_FLOAT);

    // Validasi dasar
    if (empty($nama_produk) || $harga === false || $harga <= 0) {
        // PERBAIKAN: Menggunakan $_SESSION['toast_error']
        $_SESSION['toast_error'] = "❌ Nama produk dan Harga (harus lebih dari 0) wajib diisi.";
        header("Location: produk.php");
        exit;
    }

    $nama_file_foto = null;

    // --- Proses Upload Foto (Opsional) ---
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
        
        // Validasi ekstensi dan ukuran (contoh: maks 5MB)
        if (!in_array($file_ext, $allowed_ext)) {
            // PERBAIKAN: Menggunakan $_SESSION['toast_error']
            $_SESSION['toast_error'] = "❌ Gagal: Ekstensi foto tidak valid. Hanya JPG, JPEG, PNG, WEBP.";
            header("Location: produk.php");
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            // PERBAIKAN: Menggunakan $_SESSION['toast_error']
            $_SESSION['toast_error'] = "❌ Gagal: Ukuran foto terlalu besar (maks 5MB).";
            header("Location: produk.php");
            exit;
        }

        // Tentukan nama file unik
        $nama_file_foto = uniqid('produk_', true) . '.' . $file_ext;
        $file_path = $upload_dir . $nama_file_foto;

        // Pindahkan file
        if (!move_uploaded_file($file_tmp, $file_path)) {
            // PERBAIKAN: Menggunakan $_SESSION['toast_error']
            $_SESSION['toast_error'] = "❌ Gagal mengunggah file foto.";
            header("Location: produk.php");
            exit;
        }
    }
    // -------------------------------------

    $conn->begin_transaction();

    try {
        // Query INSERT data
        $stmt = $conn->prepare("
            INSERT INTO produk (nama_produk, harga, kategori, deskripsi, foto, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("sdssss", 
            $nama_produk, 
            $harga, 
            $kategori, 
            $deskripsi, 
            $nama_file_foto, 
            $status
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
        }

        $stmt->close();
        $conn->commit();

        // PERBAIKAN: Menggunakan $_SESSION['toast_success']
        $_SESSION['toast_success'] = "✅ Produk **" . htmlspecialchars($nama_produk) . "** berhasil ditambahkan!";

    } catch (Exception $e) {
        $conn->rollback();
        // Hapus file yang sudah diunggah jika terjadi kegagalan DB
        if ($nama_file_foto && file_exists($upload_dir . $nama_file_foto)) {
            unlink($upload_dir . $nama_file_foto);
        }
        // PERBAIKAN: Menggunakan $_SESSION['toast_error']
        $_SESSION['toast_error'] = "❌ Terjadi kesalahan saat menyimpan produk: " . $e->getMessage();
    }

    // Redirect kembali ke halaman produk
    header("Location: produk.php");
    exit;

} else {
    // Jika diakses tanpa POST atau aksi tidak sesuai
    // PERBAIKAN: Menggunakan $_SESSION['toast_error']
    $_SESSION['toast_error'] = "⚠️ Akses tidak valid.";
    header("Location: produk.php");
    exit;
}
?>