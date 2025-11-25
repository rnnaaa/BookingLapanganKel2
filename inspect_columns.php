<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bookinglapanganb2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Check member table status column
echo "<h2>Member Table - Status Column</h2>";
$stmt = $pdo->query('SHOW FULL COLUMNS FROM member');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($row['Field'] === 'status') {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}

// Check member_jadwal table status column
echo "<h2>Member_Jadwal Table - Status Column</h2>";
$stmt = $pdo->query('SHOW FULL COLUMNS FROM member_jadwal');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($row['Field'] === 'status') {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}

// Try to insert test record dan lihat error details
echo "<h2>Test Insert</h2>";
try {
    $stmt = $pdo->prepare("INSERT INTO member 
                          (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status, created_at, updated_at)
                          VALUES (1, 1, 1, '2025-11-20', '2025-11-20', '', 'qris', 0, 'pending', NOW(), NOW())");
    $stmt->execute();
    echo "✅ Insert berhasil!";
} catch(Exception $e) {
    echo "<span style='color:red'>❌ Error: " . $e->getMessage() . "</span>";
}
?>
