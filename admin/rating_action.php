<?php
require_once __DIR__ . '/../config/database.php';

$action = $_REQUEST['action'] ?? '';

if ($action == 'read') {
  $no = 1;
  $sql = "SELECT r.*, u.nama AS nama_user, l.nama_lapangan 
          FROM rating r
          JOIN users u ON r.user_id = u.id_user
          JOIN lapangan l ON r.lapangan_id = l.id_lapangan
          ORDER BY r.rating_id DESC";
  $result = mysqli_query($conn, $sql);
  while ($r = mysqli_fetch_assoc($result)) {
    $badge = ($r['nilai'] >= 4) ? 'bg-success' : (($r['nilai'] == 3) ? 'bg-warning text-dark' : 'bg-danger');
    echo "
      <tr>
        <td class='text-center'>{$no}</td>
        <td>{$r['nama_user']}</td>
        <td>{$r['nama_lapangan']}</td>
        <td class='text-center'><span class='badge {$badge}'><i class='fas fa-star'></i> {$r['nilai']}</span></td>
        <td>{$r['komentar']}</td>
        <td class='text-center'>".date('d M Y, H:i', strtotime($r['created_at']))."</td>
        <td class='text-center'>
          <button class='btn btn-danger btn-sm btn-delete' data-id='{$r['id_rating']}'><i class='fas fa-trash'></i></button>
        </td>
      </tr>
    ";
  }
}

elseif ($action == 'create') {
  $user = $_POST['user_id'];
  $lapangan = $_POST['lapangan_id'];
  $nilai = $_POST['nilai'];
  $komentar = $_POST['komentar'];
  mysqli_query($conn, "INSERT INTO rating (user_id, lapangan_id, nilai, komentar, created_at)
                       VALUES ('$user', '$lapangan', '$nilai', '$komentar', NOW())");
  echo "Rating berhasil ditambahkan!";
}

elseif ($action == 'delete') {
  $id = $_POST['id'];
  mysqli_query($conn, "DELETE FROM rating WHERE id_rating='$id'");
  echo "Rating berhasil dihapus!";
}
?>
