<?php
session_start();
require 'config/database.php';

ob_clean(); 
header('Content-Type: application/json');

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis. Silakan login ulang.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request tidak valid.']);
    exit;
}

$user_id = $_SESSION['id_user'];

// 2. Ambil Data (Username baru ditambahkan, Email tidak diambil untuk update)
$nama = trim($_POST['nama'] ?? '');
$username = trim($_POST['username'] ?? ''); // Username baru
$no_hp = trim($_POST['no_hp'] ?? '');
$pekerjaan = trim($_POST['pekerjaan'] ?? '');
$pekerjaan_lain = trim($_POST['pekerjaan_lain'] ?? '');

// 3. Validasi
if (empty($nama) || empty($username) || empty($no_hp)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama, Username, dan No HP wajib diisi.']);
    exit;
}

// 4. Cek DUPLIKAT USERNAME (Kecuali punya sendiri)
$stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
$stmt_check->bind_param('si', $username, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username ini sudah dipakai. Silakan pilih yang lain.']);
    exit;
}
$stmt_check->close();

// 5. Rapikan Data Pekerjaan
if ($pekerjaan !== 'Lainnya') {
    $pekerjaan_lain = ''; 
}

// 6. Update Database (EMAIL DIHAPUS DARI QUERY)
// Hanya update: nama, username, no_hp, pekerjaan
$stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, no_hp = ?, pekerjaan = ?, pekerjaan_lain = ? WHERE id_user = ?");
$stmt->bind_param('sssssi', $nama, $username, $no_hp, $pekerjaan, $pekerjaan_lain, $user_id);

if ($stmt->execute()) {
    // Update Session
    $_SESSION['nama'] = $nama;
    
    echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
}

$stmt->close();
$conn->close();