<?php
// pembatalan_proses.php
require_once 'auth_check.php';
session_start();
require_once __DIR__ . '/../config/database.php';

// --- LOGIKA KEAMANAN & REALISTIS ---
// 1. Cek apakah Admin sudah login?
// Sesuaikan 'id_user' dengan nama session di file login.php Anda. 
// Biasanya: $_SESSION['id_user'], $_SESSION['user_id'], atau $_SESSION['admin_id']
if (!isset($_SESSION['id_user'])) {
    // Jika tidak ada session, tolak akses
    $_SESSION['toast_error'] = "Sesi Anda telah berakhir. Silakan login kembali.";
    header("Location: ../login.php"); // Arahkan ke halaman login
    exit;
}

// 2. Ambil ID Admin dari Session
$id_admin_login = $_SESSION['id_user']; 

// -----------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve') {
    
    $id = intval($_POST['id']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Cek Data Lama
    $cek = mysqli_query($conn, "SELECT * FROM pembatalan_booking WHERE id = '$id'");
    $data_lama = mysqli_fetch_assoc($cek);
    
    if (!$data_lama) {
        $_SESSION['toast_error'] = "Data booking tidak ditemukan!";
        header("Location: pembatalan_booking.php");
        exit;
    }

    // Proses Upload Gambar
    $bukti_filename = $data_lama['bukti_refund']; // Default lama

    if (isset($_FILES['bukti_tf']) && $_FILES['bukti_tf']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['bukti_tf']['name'];
        $file_tmp = $_FILES['bukti_tf']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Nama file unik: refund_ID_TIMESTAMP.ext
            $new_filename = 'refund_' . $id . '_' . time() . '.' . $ext;
            $upload_dir = '../uploads/bukti_refund/';

            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $bukti_filename = $new_filename;
            } else {
                $_SESSION['toast_error'] = "Gagal mengupload gambar ke server.";
                header("Location: pembatalan_booking.php");
                exit;
            }
        } else {
            $_SESSION['toast_error'] = "Format file wajib JPG, JPEG, atau PNG.";
            header("Location: pembatalan_booking.php");
            exit;
        }
    } else {
        // Jika validasi mengharuskan upload
        $_SESSION['toast_error'] = "Mohon upload bukti transfer.";
        header("Location: pembatalan_booking.php");
        exit;
    }

    // --- UPDATE DATABASE ---
    // Memasukkan $id_admin_login ke kolom processed_by
    $query = "UPDATE pembatalan_booking SET 
                status = 'approved',
                bukti_refund = '$bukti_filename',
                keterangan = '$keterangan',
                processed_at = NOW(),
                processed_by = '$id_admin_login' 
              WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['toast_success'] = "Refund Disetujui! Data telah diperbarui oleh sistem.";
    } else {
        $_SESSION['toast_error'] = "Database Error: " . mysqli_error($conn);
    }

    header("Location: pembatalan_booking.php");
    exit;
}
?>