<?php
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM pengeluaran WHERE id_pengeluaran='$id'");
$_SESSION['toast_success'] = 'Data pengeluaran berhasil dihapus!';
header('Location: pengeluaran.php');
exit;
?>
