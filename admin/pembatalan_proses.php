<?php
// pembatalan_proses.php
require_once 'auth_check.php';
// session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['id_user'])) {
    $_SESSION['toast_error'] = "Sesi Anda telah berakhir. Silakan login kembali.";
    header("Location: ../login.php");
    exit;
}

$id_admin_login = $_SESSION['id_user']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Cek Data
    $cek = mysqli_query($conn, "SELECT * FROM pembatalan_booking WHERE id = '$id'");
    if (mysqli_num_rows($cek) == 0) {
        $_SESSION['toast_error'] = "Data tidak ditemukan!";
        header("Location: pembatalan_booking.php");
        exit;
    }

    // --- LOGIKA APPROVE ---
    if ($action == 'approve') {
        
        // Proses Upload Gambar (Wajib jika Approve)
        if (isset($_FILES['bukti_tf']) && $_FILES['bukti_tf']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['bukti_tf']['name'];
            $file_tmp = $_FILES['bukti_tf']['tmp_name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'refund_' . $id . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/bukti_refund/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    
                    // Update DB Approve
                    $query = "UPDATE pembatalan_booking SET 
                                status = 'approved',
                                bukti_refund = '$new_filename',
                                keterangan = '$keterangan',
                                processed_at = NOW(),
                                processed_by = '$id_admin_login' 
                              WHERE id = '$id'";
                    
                    if (mysqli_query($conn, $query)) {
                        $_SESSION['toast_success'] = "Refund Disetujui.";
                    } else {
                        $_SESSION['toast_error'] = "Error DB: " . mysqli_error($conn);
                    }

                } else {
                    $_SESSION['toast_error'] = "Gagal upload gambar.";
                }
            } else {
                $_SESSION['toast_error'] = "Format file salah.";
            }
        } else {
            $_SESSION['toast_error'] = "Bukti transfer wajib diupload untuk approval.";
        }

    // --- LOGIKA REJECT (BARU) ---
    } elseif ($action == 'reject') {

        if (trim($keterangan) == '') {
            $_SESSION['toast_error'] = "Alasan penolakan wajib diisi.";
            header("Location: pembatalan_booking.php");
            exit;
        }

        // Update DB Reject (Tanpa upload gambar)
        $query = "UPDATE pembatalan_booking SET 
                    status = 'rejected',
                    keterangan = '$keterangan',
                    processed_at = NOW(),
                    processed_by = '$id_admin_login' 
                  WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['toast_success'] = "Pengajuan Refund Ditolak.";
        } else {
            $_SESSION['toast_error'] = "Error DB: " . mysqli_error($conn);
        }
    }

    header("Location: pembatalan_booking.php");
    exit;
}
?>