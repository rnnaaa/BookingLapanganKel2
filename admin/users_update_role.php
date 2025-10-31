<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $id = intval($_POST['id_user']);
  $role = $_POST['role'];

  $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id_user = ?");
  $stmt->bind_param('si', $role, $id);
  $stmt->execute();

  echo "success";
}
