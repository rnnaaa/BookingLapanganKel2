<?php
// check_username.php
session_start();
require 'config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_user']) || !isset($_GET['username'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$username = trim($_GET['username']);
$current_id = $_SESSION['id_user'];

// Cek username di database (kecuali milik sendiri)
$stmt = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
$stmt->bind_param("si", $username, $current_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'taken', 'message' => 'Username sudah digunakan']);
} else {
    echo json_encode(['status' => 'available', 'message' => 'Username tersedia']);
}
?>