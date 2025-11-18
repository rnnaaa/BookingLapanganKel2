<?php
session_start();
require 'config/database.php';

// Atur header untuk merespon sebagai JSON
header('Content-Type: application/json');

// 1. Keamanan: Pastikan pengguna login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk melakukan aksi ini.']);
    exit;
}

// 2. Pastikan ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$user_id = $_SESSION['id_user'];

// 3. Ambil dan bersihkan data dari POST
$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$pekerjaan = trim($_POST['pekerjaan'] ?? '');
$pekerjaan_lain = trim($_POST['pekerjaan_lain'] ?? '');

// 4. Validasi Server-Side
if (empty($nama) || empty($email) || empty($no_hp)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama, email, dan No. HP tidak boleh kosong.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

// 5. Cek apakah email sudah digunakan oleh PENGGUNA LAIN
$stmt_check = $conn->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
$stmt_check->bind_param('si', $email, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email ini sudah digunakan oleh akun lain.']);
    exit;
}
$stmt_check->close();

// 6. Tentukan nilai pekerjaan_lain
if ($pekerjaan !== 'Lainnya') {
    $pekerjaan_lain = ''; // Kosongkan
}

// 7. Update Database
$stmt_update = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, pekerjaan = ?, pekerjaan_lain = ? WHERE id_user = ?");
$stmt_update->bind_param('sssssi', $nama, $email, $no_hp, $pekerjaan, $pekerjaan_lain, $user_id);

if ($stmt_update->execute()) {
    // 8. Perbarui juga data di Session
    $_SESSION['nama'] = $nama;
    
    echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan. Gagal menyimpan ke database.']);
}

$stmt_update->close();
$conn->close();

// <-- Tag penutup ?> sengaja dihapus di sini