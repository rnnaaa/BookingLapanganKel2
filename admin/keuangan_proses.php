<?php
// keuangan_proses.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input dasar
    $id_keuangan = intval($_POST['id_keuangan']);
    $tanggal     = $_POST['tanggal'];
    $jenis       = $_POST['jenis'];
    $kategori    = $_POST['kategori'];
    $jumlah      = str_replace('.', '', $_POST['jumlah']); // Hapus titik jika ada
    $keterangan  = $_POST['keterangan'];
    
    // Ambil ID User untuk Audit Trail
    $admin_id = $_SESSION['id_user']; 

    // Query Update
    $sql = "UPDATE keuangan 
            SET tanggal = ?, 
                jenis = ?, 
                kategori = ?, 
                jumlah = ?, 
                keterangan = ?,
                updated_by = ?, 
                updated_at = NOW() 
            WHERE id_keuangan = ?";
             
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsii", $tanggal, $jenis, $kategori, $jumlah, $keterangan, $admin_id, $id_keuangan);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Data keuangan berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal menyimpan: " . $conn->error;
    }

    // Redirect kembali ke halaman utama
    header("Location: keuangan.php");
    exit;
} else {
    // Jika diakses langsung tanpa POST
    header("Location: keuangan.php");
    exit;
}
?>