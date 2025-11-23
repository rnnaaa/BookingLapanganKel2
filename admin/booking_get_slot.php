<?php
// booking_get_slot.php - UPDATED FOR EDIT FEATURE
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// 1. SET TIMEZONE
date_default_timezone_set('Asia/Jakarta');

$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
$tanggal = $_GET['tanggal'] ?? '';
// Parameter baru: ID Booking yang sedang diedit (opsional)
$exclude_booking = isset($_GET['exclude_booking']) ? intval($_GET['exclude_booking']) : 0;

if (!$id_lapangan || !$tanggal) {
    echo json_encode(['status' => 'error', 'message' => 'Pilih lapangan dan tanggal terlebih dahulu.']);
    exit;
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode(['status' => 'error', 'message' => 'Format tanggal salah.']);
    exit;
}

try {
    // 2. CEK JADWAL HARIAN
    $checkSql = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ? LIMIT 1";
    $stmtCheck = $conn->prepare($checkSql);
    $stmtCheck->bind_param("is", $id_lapangan, $tanggal);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $exists = $resCheck->fetch_assoc();
    $stmtCheck->close();

    if (!$exists) {
        echo json_encode([
            'status' => 'error', 
            'message' => '⚠️ Jadwal untuk tanggal ini belum tersedia (Belum di-generate).'
        ]);
        exit;
    }

    // 3. SIAPKAN WAKTU SEKARANG
    $current_date = date('Y-m-d');
    $current_time = date('H:i');

    // 4. AMBIL DATA SLOT
    // Kita tambahkan logika: Ambil juga id_booking untuk pengecekan
    $sql = "
        SELECT 
            jd.id_detail,
            jd.status,
            jd.id_booking, 
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
        
        $status_final = $row['status'];

        // LOGIKA KHUSUS EDIT:
        // Jika slot ini milik booking yang sedang kita edit, anggap 'tersedia'
        if ($exclude_booking > 0 && $row['id_booking'] == $exclude_booking) {
            $status_final = 'tersedia';
        }

        // LOGIKA WAKTU LEWAT:
        if ($tanggal < $current_date) {
             $status_final = 'lewat'; 
        }
        elseif ($tanggal === $current_date) {
            // Jika statusnya tersedia (atau milik sendiri), tapi jam sudah lewat -> Lewat
            if ($status_final === 'tersedia' && $row['jam_mulai'] < $current_time) {
                $status_final = 'lewat';
            }
        }

        $slots[] = [
            'id_detail'       => intval($row['id_detail']),
            'id_jadwal_waktu' => intval($row['id_jadwal_waktu']),
            'jam_mulai'       => $row['jam_mulai'],
            'jam_selesai'     => $row['jam_selesai'],
            'status'          => $status_final,
            'is_mine'         => ($row['id_booking'] == $exclude_booking) // Penanda UI
        ];
    }
    
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'slots' => $slots]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>