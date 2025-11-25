<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bookinglapanganb2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Get all info about member table
echo "<h2>Member Table DDL</h2>";
$stmt = $pdo->query('SHOW CREATE TABLE member\G');
$result = $stmt->fetchAll();
echo "<pre>";
print_r($result);
echo "</pre>";

// Try direct insert test
echo "<h2>Test Insert dengan berbagai status values</h2>";
$testValues = ['pending', 'aktif', 'p', 'a', '0', '1'];

foreach ($testValues as $val) {
    try {
        $id_user = rand(1000, 9999);
        $stmt = $pdo->prepare("INSERT INTO member 
                              (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status, created_at, updated_at)
                              VALUES (?, 0, 1, NOW(), NOW(), '', 'qris', 0, ?, NOW(), NOW())");
        $stmt->execute([$id_user, $val]);
        echo "✅ Status '$val' - SUCCESS<br>";
        // Delete test record
        $pdo->exec("DELETE FROM member WHERE id_user = $id_user");
    } catch(Exception $e) {
        echo "❌ Status '$val' - FAILED: " . $e->getMessage() . "<br>";
    }
}

// Check existing member records to see what status values are used
echo "<h2>Existing Status Values</h2>";
$stmt = $pdo->query("SELECT DISTINCT status, LENGTH(status) as len FROM member LIMIT 10");
echo "<pre>";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status: '" . $row['status'] . "' (Length: " . $row['len'] . ")<br>";
}
echo "</pre>";
?>
