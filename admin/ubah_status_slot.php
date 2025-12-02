<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'method_not_allowed';
    exit;
}

$id_detail = intval($_POST['id_detail'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id_detail || !in_array($status, ['tersedia', 'dibooking'])) {
    echo 'invalid';
    exit;
}

$stmt = $conn->prepare("SELECT id_detail FROM jadwal_detail WHERE id_detail = ?");
$stmt->bind_param('i', $id_detail);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo 'not_found';
    exit;
}
$stmt->close();

$update = $conn->prepare("UPDATE jadwal_detail SET status = ? WHERE id_detail = ?");
$update->bind_param('si', $status, $id_detail);

echo $update->execute() ? 'ok' : 'error';

$update->close();
$conn->close();
