<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bookinglapanganb2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2>Member Table Structure</h2>";
$stmt = $pdo->query('SHOW CREATE TABLE member');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
echo htmlspecialchars($row['Create Table']);
echo "</pre>";

echo "<h2>Existing Status Values in Member Table</h2>";
$stmt = $pdo->query("SELECT DISTINCT status FROM member LIMIT 20");
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($rows);
echo "</pre>";
?>
