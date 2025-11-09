<?php
require_once __DIR__ . '/../config/database.php';

if (isset($_POST['id_detail']) && isset($_POST['status'])) {
    $id = intval($_POST['id_detail']);
    $status = ($_POST['status'] === 'dibooking') ? 'dibooking' : 'tersedia';

    $sql = "UPDATE jadwal_detail SET status='$status' WHERE id_detail='$id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "ok";
    } else {
        echo "error: " . mysqli_error($conn);
    }
} else {
    echo "invalid";
}
