<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = intval($_POST['id']);
  $field = $_POST['field'];
  $value = $_POST['value'];

  $allowed = ['role', 'status'];
  if (in_array($field, $allowed)) {
    mysqli_query($conn, "UPDATE users SET $field='$value' WHERE id_user='$id'");
    echo "success";
  } else {
    http_response_code(400);
    echo "Invalid field";
  }
}
