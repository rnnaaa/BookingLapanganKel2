<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Ambil POST
    $tanggal = $_POST['tanggal_pengeluaran'];
    $kategori = $_POST['kategori_pengeluaran'];
    $deskripsi = $_POST['deskripsi_pengeluaran'];
    $jumlah = $_POST['jumlah_pengeluaran'];

    // Escape
    $tanggal = mysqli_real_escape_string($conn, $tanggal);
    $kategori = mysqli_real_escape_string($conn, $kategori);
    $deskripsi = mysqli_real_escape_string($conn, $deskripsi);
    $jumlah = (int) str_replace(",", "", $jumlah);

    // 1. Tambahkan ke tabel keuangan (jenis = pengeluaran)
    $q1 = mysqli_query($conn, "
      INSERT INTO keuangan (tanggal, jenis, deskripsi, nominal)
      VALUES ('$tanggal', 'pengeluaran', '$deskripsi', '$jumlah')
    ");

    // Ambil id_keuangan baru
    $id_keuangan = mysqli_insert_id($conn);

    // 2. Tambahkan ke tabel pengeluaran (link ke keuangan)
    $q2 = mysqli_query($conn, "
      INSERT INTO pengeluaran (id_keuangan, kategori_pengeluaran, deskripsi_pengeluaran, tanggal_pengeluaran, jumlah_pengeluaran)
      VALUES ('$id_keuangan', '$kategori', '$deskripsi', '$tanggal', '$jumlah')
    ");

    echo json_encode([
        'success' => true,
        'message' => "Pengeluaran berhasil disimpan & pembukuan tercatat ✅"
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Gagal menyimpan data: ".$e->getMessage()
    ]);
}
