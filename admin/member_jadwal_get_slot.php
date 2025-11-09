<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_GET['id_lapangan']) || !isset($_GET['tanggal'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter id_lapangan dan tanggal wajib diisi.'
    ]);
    exit;
}

$id_lapangan = intval($_GET['id_lapangan']);
$tanggal = $_GET['tanggal'];

try {
    // ambil hari dari tanggal (Senin, Selasa, dst)
    $hari = date('l', strtotime($tanggal));
    $hariMap = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $hari_id = $hariMap[$hari];

    // ambil semua slot waktu dari tabel jadwal_waktu untuk lapangan tersebut
    $query = "
        SELECT 
            jw.id_jadwal_waktu,
            jw.jam_mulai,
            jw.jam_selesai,
            COALESCE(mj.id_member, NULL) AS id_member,
            COALESCE(u.nama, NULL) AS nama_member
        FROM jadwal_waktu jw
        LEFT JOIN member_jadwal mj 
            ON jw.id_lapangan = mj.id_lapangan 
            AND mj.jam_mulai = jw.jam_mulai 
            AND mj.tanggal_booking = ?
        LEFT JOIN member m ON mj.id_member = m.id_member
        LEFT JOIN users u ON m.id_user = u.id_user
        WHERE jw.id_lapangan = ?
        ORDER BY jw.jam_mulai ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $tanggal, $id_lapangan);
    $stmt->execute();
    $result = $stmt->get_result();

    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = [
            'id_jadwal_waktu' => $row['id_jadwal_waktu'],
            'jam_mulai' => $row['jam_mulai'],
            'jam_selesai' => $row['jam_selesai'],
            'status' => $row['id_member'] ? 'booked' : 'available',
            'nama_member' => $row['nama_member']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'hari' => $hari_id,
        'tanggal' => $tanggal,
        'slots' => $slots
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
