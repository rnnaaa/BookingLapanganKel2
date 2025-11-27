<?php
// File: pengeluaran_tambah.php
// Menggunakan session untuk menyimpan pesan notifikasi

// Pastikan session_start() sudah dijalankan (biasanya di auth_check.php)
// Jika auth_check.php tidak otomatis menjalankan session_start(), pastikan baris ini ada:
// session_start(); 

require_once __DIR__ . '/auth_check.php'; 
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// =========================================================
// CATATAN: KODE INI BERJALAN SAAT FORM DI-SUBMIT LANGSUNG
// =========================================================

try {
    // Memastikan ID pengguna tersedia dari sesi
    if (!isset($_SESSION['id_user'])) {
        // Simpan pesan error dan redirect
        $_SESSION['toast_error'] = "Sesi pengguna tidak ditemukan. Silakan login kembali.";
        header('Location: pengeluaran.php');
        exit;
    }

    // Ambil data POST
    $tanggal = $_POST['tanggal'] ?? null;
    $kategori = trim($_POST['kategori'] ?? '');
    $keterangan = $_POST['keterangan'] ?? null;
    $jumlah = floatval($_POST['jumlah'] ?? 0);
    $input_by = $_SESSION['id_user']; 

    if (!$tanggal || !$kategori || $jumlah <= 0) {
        throw new Exception("Data tidak lengkap (Tanggal, Kategori, atau Jumlah tidak valid).");
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah, input_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement SQL: " . $conn->error);
    }

    $stmt->bind_param("sssdi", $tanggal, $kategori, $keterangan, $jumlah, $input_by);
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan data: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->commit();

    // Set pesan sukses ke session dan redirect
    $_SESSION['toast_success'] = "Data pengeluaran berhasil ditambahkan!";
    header('Location: pengeluaran.php');
    exit;

} catch (Exception $e) {
    // Rollback dan set pesan error ke session, lalu redirect
    if (isset($conn) && $conn->begin_transaction()) {
        $conn->rollback(); 
    }
    $_SESSION['toast_error'] = "Gagal menyimpan data: " . $e->getMessage();
    header('Location: pengeluaran.php');
    exit;
}
?>