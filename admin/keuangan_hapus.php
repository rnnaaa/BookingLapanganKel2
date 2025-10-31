<?php
require_once __DIR__ . '/../config/database.php';
$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM keuangan WHERE id_keuangan='$id'");
$_SESSION['toast_success'] = 'Data berhasil dihapus!';
header('Location: keuangan.php');
exit;
?>
