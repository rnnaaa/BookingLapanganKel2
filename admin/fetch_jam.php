<?php
require_once '../config/database.php';

if (isset($_POST['id_lapangan']) && isset($_POST['tanggal_booking'])) {
    $id_lapangan = intval($_POST['id_lapangan']);
    $tanggal = $_POST['tanggal_booking'];

    $query = "
        SELECT jd.id_detail, jw.jam_mulai, jw.jam_selesai, jd.status
        FROM jadwal_detail jd
        JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
        JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
        WHERE jh.id_lapangan = ?
          AND jh.tanggal = ?
          AND jd.status = 'tersedia'
        ORDER BY jw.jam_mulai ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $id_lapangan, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="">-- Pilih Jam --</option>';
    while ($row = $result->fetch_assoc()) {
        $mulai = substr($row['jam_mulai'], 0, 5);
        $selesai = substr($row['jam_selesai'], 0, 5);
        $options .= '<option value="' . $row['id_detail'] . '">' . $mulai . ' - ' . $selesai . '</option>';
    }

    echo $options;
}
?>
