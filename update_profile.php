<?php
session_start();
require 'config/database.php';

ob_clean(); 
header('Content-Type: application/json');

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis. Silakan login ulang.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request tidak valid.']);
    exit;
}

$user_id = $_SESSION['id_user'];

// 2. Ambil Data Input
$nama = trim($_POST['nama'] ?? '');
$username = trim($_POST['username'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$pekerjaan = trim($_POST['pekerjaan'] ?? '');
$pekerjaan_lain = trim($_POST['pekerjaan_lain'] ?? '');

// 3. Validasi Input Dasar
if (empty($nama) || empty($username) || empty($no_hp)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama, Username, dan No HP wajib diisi.']);
    exit;
}

// 4. Cek Duplikat Username
$stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
$stmt_check->bind_param('si', $username, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username ini sudah dipakai.']);
    exit;
}
$stmt_check->close();

// 5. Rapikan Data Pekerjaan
if ($pekerjaan !== 'Lainnya') {
    $pekerjaan_lain = ''; 
}

// ==========================================
// 6. LOGIC UPLOAD FOTO
// ==========================================
$foto_query_part = ""; // String tambahan untuk query SQL
$types = "sssss";      // Tipe data bind_param (nama, un, hp, pek, pek_lain)
$params = [&$nama, &$username, &$no_hp, &$pekerjaan, &$pekerjaan_lain]; // Array parameter

// Cek apakah ada file yang diupload dan tidak error
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    
    $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
    $fileName = $_FILES['foto_profil']['name'];
    $fileSize = $_FILES['foto_profil']['size'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Validasi Ekstensi
    $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        
        // Buat nama file unik: user_ID_timestamp.ext
        $newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
        
        // Direktori tujuan
        $uploadFileDir = 'uploads/profiles/';
        
        // Buat folder jika belum ada
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        $dest_path = $uploadFileDir . $newFileName;

        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            
            // HAPUS FOTO LAMA (Optional, agar server tidak penuh)
            // Ambil nama foto lama dari session atau database
            if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                $old_file = $uploadFileDir . $_SESSION['foto_profil'];
                if (file_exists($old_file)) {
                    unlink($old_file); // Hapus file lama
                }
            }

            // Update variabel untuk SQL
            $foto_query_part = ", foto_profil = ?";
            $types .= "s"; // Tambah tipe string untuk foto
            $params[] = &$newFileName; // Masukkan nama file baru ke parameter

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file ke folder upload.']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus JPG, JPEG, atau PNG.']);
        exit;
    }
}

// Tambahkan ID User ke parameter terakhir untuk WHERE clause
$params[] = &$user_id;
$types .= "i"; // Tipe integer untuk id_user

// ==========================================
// 7. EKSEKUSI QUERY
// ==========================================

// Query dinamis (tergantung apakah foto diupdate atau tidak)
$sql = "UPDATE users SET nama = ?, username = ?, no_hp = ?, pekerjaan = ?, pekerjaan_lain = ? $foto_query_part WHERE id_user = ?";

$stmt = $conn->prepare($sql);

// Bind param secara dinamis menggunakan call_user_func_array
// Kita perlu menggabungkan string types dan array params
$bind_names[] = $types;
for ($i=0; $i<count($params);$i++) {
    $bind_names[] = &$params[$i];
}

call_user_func_array(array($stmt, 'bind_param'), $bind_names);

if ($stmt->execute()) {
    // Update Session agar Header langsung berubah
    $_SESSION['nama'] = $nama;
    $_SESSION['username'] = $username;
    
    // Jika ada foto baru, update session foto
    if (isset($newFileName)) {
        $_SESSION['foto_profil'] = $newFileName;
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>