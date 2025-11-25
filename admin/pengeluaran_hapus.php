<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = intval($_GET['id']);

mysqli_query($conn, "DELETE FROM pengeluaran WHERE id_pengeluaran = $id");

echo "<script>
alert('Data berhasil dihapus');
window.location='pengeluaran.php';
</script>";
