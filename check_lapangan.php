<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bookinglapanganb2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2>Lapangan yang tersedia di database:</h2>";
$stmt = $pdo->query("SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY id_lapangan");
echo "<table border='1'><tr><th>ID</th><th>Nama</th></tr>";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>" . $row['id_lapangan'] . "</td><td>" . htmlspecialchars($row['nama_lapangan']) . "</td></tr>";
}
echo "</table>";

echo "<h2>Total lapangan: " . $pdo->query("SELECT COUNT(*) FROM lapangan")->fetchColumn() . "</h2>";
?>
