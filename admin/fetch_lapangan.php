<?php
require_once '../config/database.php';

if (isset($_POST['id_member'])) {
    $id_member = intval($_POST['id_member']);
    
    $query = "
        SELECT l.id_lapangan, l.nama_lapangan
        FROM member m
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        WHERE m.id_member = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_member);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = $result->fetch_assoc();
    echo json_encode($data);
}
?>
