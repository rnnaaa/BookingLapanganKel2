<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

try {

    $tanggal = $_POST['tanggal'] ?? null;
    $kategori = trim($_POST['kategori'] ?? '');
    $keterangan = $_POST['keterangan'] ?? null;
    $jumlah = floatval($_POST['jumlah'] ?? 0);
    $input_by = $_SESSION['id_user'] ?? null;

    if (!$tanggal || !$kategori || $jumlah <= 0 || !$input_by) {
        throw new Exception("Data tidak lengkap atau tidak valid.");
    }

    $stmt = $conn->prepare("
        INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah, input_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssdi", $tanggal, $kategori, $keterangan, $jumlah, $input_by);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status" => "success"]);
    exit;

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}
