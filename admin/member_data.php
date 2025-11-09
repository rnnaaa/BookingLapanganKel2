<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$data = [];
$no = 1;

$sql = "
  SELECT 
    m.id_member,
    u.nama AS nama_user,
    GROUP_CONCAT(DISTINCT l.nama_lapangan SEPARATOR ', ') AS lapangan,
    m.durasi_bulan,
    m.tanggal_mulai,
    m.tanggal_berakhir,
    m.total_bayar,
    m.status
  FROM member m
  LEFT JOIN users u ON m.id_user = u.id_user
  LEFT JOIN member_jadwal mj ON m.id_member = mj.id_member
  LEFT JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
  GROUP BY m.id_member
  ORDER BY m.tanggal_mulai DESC
";

$res = mysqli_query($conn, $sql);
if (!$res) {
  echo json_encode(['error' => mysqli_error($conn)]);
  exit;
}

while ($row = mysqli_fetch_assoc($res)) {
  $data[] = [
    'no' => $no++,
    'nama_user' => $row['nama_user'] ?: '-',
    'lapangan' => $row['lapangan'] ?: '-',
    'durasi_bulan' => $row['durasi_bulan'] . ' bulan',
    'tanggal_mulai' => date('d-m-Y', strtotime($row['tanggal_mulai'])),
    'tanggal_berakhir' => date('d-m-Y', strtotime($row['tanggal_berakhir'])),
    'total_bayar' => 'Rp ' . number_format($row['total_bayar'], 0, ',', '.'),
    'status' => ($row['status'] === 'aktif') 
      ? '<span class="badge bg-success">Aktif</span>'
      : '<span class="badge bg-secondary">Nonaktif</span>'
  ];
}

echo json_encode(['data' => $data]);
