<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bookinglapanganb2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2>Checking member table status column</h2>";

// Get column info
$stmt = $pdo->query("SHOW FULL COLUMNS FROM member WHERE Field = 'status'");
$colInfo = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>Column Info:\n";
print_r($colInfo);
echo "</pre>";

// Get existing status values
echo "<h2>Existing status values in member table:</h2>";
$stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as cnt FROM member GROUP BY status");
echo "<table border='1'><tr><th>Status</th><th>Count</th><th>Length</th></tr>";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>" . htmlspecialchars($row['status']) . "</td><td>" . $row['cnt'] . "</td><td>" . strlen($row['status']) . "</td></tr>";
}
echo "</table>";

// Try to insert with different status values
echo "<h2>Testing insert dengan berbagai status values:</h2>";
$testValues = ['pending', 'aktif', 'p', 'a', '0'];
foreach ($testValues as $val) {
    try {
        $stmt = $pdo->prepare("INSERT INTO member 
                              (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status)
                              VALUES (?, 1, 1, NOW(), NOW(), '', 'qris', 0, ?)");
        $stmt->execute([rand(10000, 99999), $val]);
        echo "✅ Status '$val' - SUCCESS<br>";
        // Cleanup
        $pdo->exec("DELETE FROM member WHERE status = '$val' AND id_user > 9000");
    } catch(Exception $e) {
        echo "❌ Status '$val' - FAILED: " . $e->getMessage() . "<br>";
    }
}
?>
