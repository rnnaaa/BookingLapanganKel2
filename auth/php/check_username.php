<?php
// File: php/check_username.php
require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    
    // Cek apakah username sudah ada (Case Insensitive di database biasanya)
    // Ini mencegah ada "Admin" dan "admin" sebagai dua orang berbeda (bahaya imposter)
    $stmt = $conn->prepare("SELECT id_user FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo 'taken';
    } else {
        echo 'available';
    }
    $stmt->close();
}
?>