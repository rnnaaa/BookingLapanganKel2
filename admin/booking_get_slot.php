<?php
// booking_get_slot.php - AJAX endpoint untuk load slot tersedia
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
$tanggal = $_GET['tanggal'] ?? '';

if (!$id_lapangan || !$tanggal) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
    exit;
}

try {
    // Ambil slot dari jadwal_detail yang tersedia untuk tanggal & lapangan ini
    $sql = "
        SELECT 
            jd.id_detail,
            jd.status,
            jw.id_jadwal_waktu,
            DATE_FORMAT(jw.jam_mulai, '%H:%i') AS jam_mulai,
            DATE_FORMAT(jw.jam_selesai, '%H:%i') AS jam_selesai
        FROM jadwal_detail jd
        JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
        JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
        WHERE jh.id_lapangan = ?
        AND jh.tanggal = ?
        ORDER BY jw.jam_mulai ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_lapangan, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = [
            'id_detail' => intval($row['id_detail']),
            'id_jadwal_waktu' => intval($row['id_jadwal_waktu']),
            'jam_mulai' => $row['jam_mulai'],
            'jam_selesai' => $row['jam_selesai'],
            'status' => $row['status']
        ];
    }
    
    $stmt->close();
    
    if (empty($slots)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Tidak ada slot tersedia. Pastikan jadwal sudah di-synchronize untuk tanggal ini.',
            'slots' => []
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'slots' => $slots
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Gagal memuat slot: ' . $e->getMessage()
    ]);
}
?>