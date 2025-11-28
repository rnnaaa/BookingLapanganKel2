<?php
session_start();
require '../config/database.php';
require '../include_user/header.php';

// CSS
echo '<link rel="stylesheet" href="./riwayat.css?v=' . time() . '">';

$user_id = $_SESSION['id_user'] ?? null;
$is_logged_in = ($user_id && $user_id != 1);
$bookings = [];
$memberBookings = [];
$error = null;

// ==============================================
// FUNGSI BANTUAN
// ==============================================

function generateRegulerId($tanggal, $jam_mulai) {
    $day = substr($tanggal, 8, 2);
    $month = substr($tanggal, 5, 2);
    $jam = !empty($jam_mulai) ? str_replace(':', '', substr($jam_mulai, 0, 5)) : '0000';
    return "RGLR{$day}{$month}-{$jam}";
}

function generateMemberId($durasi_bulan, $tanggal_mulai, $tanggal_berakhir) {
    $start = new DateTime($tanggal_mulai);
    $end = new DateTime($tanggal_berakhir);
    $durasi = str_pad($durasi_bulan, 2, '0', STR_PAD_LEFT);
    return "MMBR{$durasi}" . $start->format('md') . $end->format('md');
}

function validateH5Jam($tanggal, $jam) {
    if (!$tanggal || !$jam) return false;
    
    $tz = new DateTimeZone('Asia/Jakarta');
    try {
        $bookingTime = new DateTime("$tanggal " . substr($jam, 0, 5) . ":00", $tz);
        $deadline = (clone $bookingTime)->sub(new DateInterval('PT5H'));
        $now = new DateTime('now', $tz);
        
        return $now <= $deadline;
    } catch (Exception $e) {
        return false;
    }
}

function validateH12Jam($tanggal, $jam) {
    if (!$tanggal || !$jam) return false;
    
    $tz = new DateTimeZone('Asia/Jakarta');
    try {
        $bookingTime = new DateTime("$tanggal " . substr($jam, 0, 5) . ":00", $tz);
        $deadline = (clone $bookingTime)->sub(new DateInterval('PT12H'));
        $now = new DateTime('now', $tz);
        
        return $now <= $deadline;
    } catch (Exception $e) {
        return false;
    }
}

function ensureJadwalHarianExists($conn, $lapangan_id, $tanggal) {
    $stmt = $conn->prepare("SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?");
    $stmt->bind_param("is", $lapangan_id, $tanggal);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) return $res->fetch_assoc()['id_jadwal_harian'];

    $hari = date('l', strtotime($tanggal));
    $stmt = $conn->prepare("INSERT INTO jadwal_harian (id_lapangan, tanggal, hari, status_hari, created_at) VALUES (?, ?, ?, 'tersedia', NOW())");
    $stmt->bind_param("iss", $lapangan_id, $tanggal, $hari);
    $stmt->execute();
    return $conn->insert_id;
}

function ensureJadwalDetailExists($conn, $id_jadwal_harian, $lapangan_id) {
    $stmt = $conn->prepare("INSERT IGNORE INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, created_at)
        SELECT ?, id_jadwal_waktu, 'available', NOW() FROM jadwal_waktu WHERE id_lapangan = ?");
    $stmt->bind_param("ii", $id_jadwal_harian, $lapangan_id);
    $stmt->execute();
}

function getStatusInfo($status_booking, $status_pembatalan, $payment_status) {
    // 6 STATUS SYSTEM SESUAI REQUIREMENT
    if ($status_pembatalan === 'pending') {
        return ['status' => 'Menunggu Pengajuan', 'class' => 'orange'];
    } elseif ($status_pembatalan === 'approved') {
        return ['status' => 'Pembatalan Disetujui', 'class' => 'primary'];
    } elseif ($status_pembatalan === 'rejected') {
        return ['status' => 'Pembatalan Ditolak', 'class' => 'ditolak'];
    } elseif ($status_booking === 'disetujui') {
        return ['status' => 'Disetujui', 'class' => 'disetujui'];
    } elseif ($status_booking === 'ditolak') {
        return ['status' => 'Ditolak', 'class' => 'ditolak'];
    } elseif ($payment_status === 'belum_bayar' || $payment_status === 'menunggu_verifikasi') {
        return ['status' => 'Menunggu', 'class' => 'menunggu'];
    } else {
        return ['status' => 'Menunggu', 'class' => 'menunggu'];
    }
}

// ==============================================
// API ENDPOINTS (JSON ONLY) - GET
// ==============================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

if ($_GET['action'] === 'get_available_sessions') {
    $lapangan_id = (int)($_GET['lapangan_id'] ?? 0);
    $tanggal = $_GET['selected_date'] ?? date('Y-m-d');
    $booking_id = (int)($_GET['booking_id'] ?? 0);

    // Debug logging
    error_log("get_available_sessions called: lapangan_id=$lapangan_id, tanggal=$tanggal, booking_id=$booking_id");

    if ($lapangan_id <= 0) {
        error_log("Invalid lapangan_id: $lapangan_id");
        echo json_encode(['status' => 'error', 'message' => 'Lapangan tidak valid']);
        exit;
    }

    // Validasi tanggal
    $today = date('Y-m-d');
    $max_date = date('Y-m-d', strtotime('+7 days'));
    
    if ($tanggal < $today) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Tidak bisa memilih tanggal yang sudah lewat'
        ]);
        exit;
    }
    
    if ($tanggal > $max_date) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Tanggal booking maksimal 7 hari dari sekarang'
        ]);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Pastikan jadwal harian ada
        $id_jadwal_harian = ensureJadwalHarianExists($conn, $lapangan_id, $tanggal);
        ensureJadwalDetailExists($conn, $id_jadwal_harian, $lapangan_id);

        // Query yang lebih simple dan reliable
        $sql = "
            SELECT 
                jw.id_jadwal_waktu,
                jw.jam_mulai,
                jw.jam_selesai,
                l.harga_per_jam AS harga,
                COALESCE(jd.status, 'available') as jadwal_status
            FROM jadwal_waktu jw
            JOIN lapangan l ON jw.id_lapangan = l.id_lapangan
            LEFT JOIN jadwal_detail jd ON jd.id_jadwal_waktu = jw.id_jadwal_waktu 
                AND jd.id_jadwal_harian = ?
            WHERE jw.id_lapangan = ?
              AND jw.aktif = 1
              AND (jd.status IS NULL OR jd.status = 'available' OR jd.id_booking = ?)
            ORDER BY jw.jam_mulai ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iii", $id_jadwal_harian, $lapangan_id, $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = [
                'id_jadwal_waktu' => $row['id_jadwal_waktu'],
                'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                'jam_selesai' => substr($row['jam_selesai'], 0, 5),
                'harga' => (int)$row['harga'],
                'status' => $row['jadwal_status']
            ];
        }

        $conn->commit();
        
        error_log("Sessions found: " . count($sessions) . " for date $tanggal");
        
        echo json_encode([
            'status' => 'success',
            'available_sessions' => $sessions,
            'debug' => [
                'lapangan_id' => $lapangan_id,
                'tanggal' => $tanggal,
                'total_sessions' => count($sessions)
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in get_available_sessions: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    exit;
}

    if ($_GET['action'] === 'get_member_sessions') {
        $member_id = (int)($_GET['member_id'] ?? 0);
        
        if ($member_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Member ID tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id_member FROM member WHERE id_member = ? AND id_user = ?");
        $stmt->bind_param("ii", $member_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Member tidak ditemukan']);
            exit;
        }

        $sql = "
            SELECT 
                mj.id_member_jadwal,
                mj.tanggal_booking,
                mj.jam_mulai,
                mj.jam_selesai,
                mj.status as status_jadwal,
                l.nama_lapangan
            FROM member_jadwal mj
            JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
            WHERE mj.id_member = ?
            ORDER BY mj.tanggal_booking, mj.jam_mulai
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = [
                'id_member_jadwal' => $row['id_member_jadwal'],
                'tanggal_booking' => $row['tanggal_booking'],
                'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                'jam_selesai' => substr($row['jam_selesai'], 0, 5),
                'status_jadwal' => $row['status_jadwal'],
                'nama_lapangan' => $row['nama_lapangan']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'member_sessions' => $sessions
        ]);
        exit;
    }

    if ($_GET['action'] === 'get_user_info') {
        $stmt = $conn->prepare("SELECT username, nama FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode([
                'status' => 'success',
                'username' => $user['username'],
                'nama' => $user['nama']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
        }
        exit;
    }
}

// ==============================================
// API ENDPOINTS - POST
// ==============================================

// 1. UBAH JADWAL REGULER PER SESI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_jadwal_sesi') {
    header('Content-Type: application/json');
   
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $id_sesi = (int)($_POST['id_sesi'] ?? 0);
    $new_date = $_POST['new_date'] ?? '';
    $new_jadwal_waktu = (int)($_POST['new_jadwal_waktu'] ?? 0);

    if (!$id_sesi || !$new_date || !$new_jadwal_waktu) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Ambil data sesi lama
        $stmt = $conn->prepare("
            SELECT db.id_detail_booking, b.id_booking, b.tanggal AS tanggal_lama, b.id_lapangan,
                   jw.jam_mulai AS jam_lama, jw.jam_selesai AS jam_selesai_lama,
                   jw2.jam_mulai AS jam_baru, jw2.jam_selesai AS jam_selesai_baru
            FROM detail_booking db
            JOIN booking b ON db.id_booking = b.id_booking
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            JOIN jadwal_waktu jw2 ON jw2.id_jadwal_waktu = ?
            WHERE db.id_detail_booking = ? AND b.id_user = ?
        ");
        $stmt->bind_param("iii", $new_jadwal_waktu, $id_sesi, $user_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        
        if (!$data) throw new Exception('Sesi tidak ditemukan');

        // Validasi H-5 jam untuk ubah jadwal
        if (!validateH5Jam($data['tanggal_lama'], $data['jam_lama'])) {
            throw new Exception('Waktu ubah jadwal sudah habis (H-5 jam sebelum booking)');
        }

        // Cek sudah pernah diubah belum (per sesi)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM history_ubah_jadwal WHERE id_detail_booking = ? AND tipe = 'reguler'");
        $stmt->bind_param("i", $id_sesi);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()[0] > 0) {
            throw new Exception('Sesi ini sudah pernah diubah');
        }

        // Validasi batas tanggal reguler (Hari ini + 7 hari)
        $max_date = date('Y-m-d', strtotime('+7 days'));
        if ($new_date > $max_date) {
            throw new Exception('Tanggal booking reguler maksimal 7 hari dari hari ini');
        }

        // Pastikan slot baru available
        $id_jadwal_harian = ensureJadwalHarianExists($conn, $data['id_lapangan'], $new_date);
        ensureJadwalDetailExists($conn, $id_jadwal_harian, $data['id_lapangan']);

        $stmt = $conn->prepare("
            SELECT jd.status 
            FROM jadwal_detail jd 
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian 
            WHERE jh.tanggal = ? AND jd.id_jadwal_waktu = ?
        ");
        $stmt->bind_param("si", $new_date, $new_jadwal_waktu);
        $stmt->execute();
        $status = $stmt->get_result()->fetch_row()[0] ?? 'available';
        if ($status !== 'available') throw new Exception('Jadwal sudah dipesan orang lain');

        // Release jadwal lama
        $stmt = $conn->prepare("
            UPDATE jadwal_detail jd 
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian 
            SET jd.id_booking = NULL, jd.status = 'available'
            WHERE jh.tanggal = ? AND jd.id_jadwal_waktu = ?
        ");
        $old_jadwal_waktu = $data['id_jadwal_waktu'] ?? 0;
        $stmt->bind_param("si", $data['tanggal_lama'], $old_jadwal_waktu);
        $stmt->execute();

        // Booking jadwal baru
        $stmt = $conn->prepare("
            UPDATE jadwal_detail jd 
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian 
            SET jd.id_booking = ?, jd.status = 'dibooking'
            WHERE jh.tanggal = ? AND jd.id_jadwal_waktu = ?
        ");
        $stmt->bind_param("isi", $data['id_booking'], $new_date, $new_jadwal_waktu);
        $stmt->execute();

        // Update detail_booking
        $stmt = $conn->prepare("UPDATE detail_booking SET id_jadwal_waktu = ? WHERE id_detail_booking = ?");
        $stmt->bind_param("ii", $new_jadwal_waktu, $id_sesi);
        $stmt->execute();

        // Update tanggal booking jika perlu
        $stmt = $conn->prepare("UPDATE booking SET tanggal = ?, updated_at = NOW() WHERE id_booking = ?");
        $stmt->bind_param("si", $new_date, $data['id_booking']);
        $stmt->execute();

        // Catat history
        $jam_lama = substr($data['jam_lama'], 0, 5) . '-' . substr($data['jam_selesai_lama'], 0, 5);
        $jam_baru = substr($data['jam_baru'], 0, 5) . '-' . substr($data['jam_selesai_baru'], 0, 5);
        $stmt = $conn->prepare("
            INSERT INTO history_ubah_jadwal 
            (id_detail_booking, id_booking, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user, waktu_ubah)
            VALUES (?, ?, 'reguler', ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissssii", $id_sesi, $data['id_booking'], $data['tanggal_lama'], $jam_lama, $new_date, $jam_baru, $data['id_lapangan'], $user_id);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diubah']);
    
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. AJUKAN PEMBATALAN - SESUAI FITUR NO.6
// 2. AJUKAN PEMBATALAN - FIXED VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajukan_pembatalan') {
    header('Content-Type: application/json');
    
    // Log incoming request
    error_log("ajukan_pembatalan called: " . print_r($_POST, true));
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $id_sesi = (int)($_POST['id_sesi'] ?? 0);
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $bank_ewallet = trim($_POST['bank_ewallet'] ?? '');

    error_log("Processing cancellation: id_sesi=$id_sesi, nama_penerima=$nama_penerima");

    if ($id_sesi <= 0 || empty($nama_penerima) || empty($no_rekening) || empty($bank_ewallet)) {
        echo json_encode(['status' => 'error', 'message' => 'Data rekening harus diisi lengkap']);
        exit;
    }

    try {
        // Cek apakah sesi milik user
        $stmt = $conn->prepare("
            SELECT db.id_detail_booking, b.tanggal, jw.jam_mulai, b.status, b.payment_status
            FROM detail_booking db 
            JOIN booking b ON db.id_booking = b.id_booking 
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
            WHERE db.id_detail_booking = ? AND b.id_user = ?
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $id_sesi, $user_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Sesi tidak ditemukan']);
            exit;
        }

        // Validasi status booking
        if (!in_array($data['status'], ['menunggu', 'disetujui'])) {
            echo json_encode(['status' => 'error', 'message' => 'Hanya booking dengan status menunggu atau disetujui yang bisa dibatalkan']);
            exit;
        }

        // Validasi H-12 jam
        if (!validateH12Jam($data['tanggal'], $data['jam_mulai'])) {
            echo json_encode(['status' => 'error', 'message' => 'Waktu pengajuan pembatalan telah habis (H-12 jam sebelum booking)']);
            exit;
        }

        // Cek sudah pernah diajukan belum
        $stmt = $conn->prepare("SELECT id FROM pembatalan_booking WHERE id_detail_booking = ?");
        $stmt->bind_param("i", $id_sesi);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Pengajuan pembatalan sudah pernah dikirim']);
            exit;
        }

        // Get user info
        $stmt_user = $conn->prepare("SELECT username, nama FROM users WHERE id_user = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $user_info = $stmt_user->get_result()->fetch_assoc();

        // Insert pengajuan pembatalan
        $stmt = $conn->prepare("
            INSERT INTO pembatalan_booking 
            (id_detail_booking, id_user, nama_pengaju, nomor_rekening, atas_nama, jumlah_refund, status, requested_at) 
            VALUES (?, ?, ?, ?, ?, 0, 'pending', NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare insert failed: " . $conn->error);
        }
        
        $stmt->bind_param("iisss", 
            $id_sesi, 
            $user_id, 
            $user_info['nama'] ?? 'User', 
            $no_rekening, 
            $nama_penerima
        );
        
        if ($stmt->execute()) {
            error_log("Cancellation request inserted successfully for session: $id_sesi");
            echo json_encode([
                'status' => 'success', 
                'message' => 'Pengajuan berhasil dikirim. Mohon cek berkala'
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error in ajukan_pembatalan: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// 3. UBAH JADWAL MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_jadwal_member') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $member_id = (int)($_POST['member_id'] ?? 0);
    $lapangan_id = (int)($_POST['lapangan_id'] ?? 0);
    $new_date = $_POST['new_date'] ?? '';
    $member_session_ids = $_POST['member_session_ids'] ?? [];
    $selected_session = (int)($_POST['selected_session'] ?? 0);

    if (!$member_id || !$new_date || !$selected_session || empty($member_session_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    if (is_string($member_session_ids)) {
        $member_session_ids = explode(',', $member_session_ids);
    }

    $conn->begin_transaction();

    try {
        // Cek member dan ambil data
        $stmt_member = $conn->prepare("
            SELECT status, durasi_bulan, tanggal_berakhir 
            FROM member 
            WHERE id_member = ? AND id_user = ?
        ");
        $stmt_member->bind_param("ii", $member_id, $user_id);
        $stmt_member->execute();
        $result_member = $stmt_member->get_result();
        
        if ($result_member->num_rows === 0) {
            throw new Exception('Membership tidak ditemukan');
        }
        
        $member_data = $result_member->fetch_assoc();
        if ($member_data['status'] !== 'aktif') {
            throw new Exception('Membership tidak aktif');
        }

        // Validasi batas tanggal member (tidak boleh melebihi tanggal_berakhir)
        if ($new_date > $member_data['tanggal_berakhir']) {
            throw new Exception('Tanggal booking tidak boleh melebihi periode membership');
        }

        // Cek kuota ubah jadwal
        $stmt_quota = $conn->prepare("
            SELECT COUNT(*) as total_ubah 
            FROM history_ubah_jadwal 
            WHERE id_member = ? AND tipe = 'member'
        ");
        $stmt_quota->bind_param("i", $member_id);
        $stmt_quota->execute();
        $result_quota = $stmt_quota->get_result();
        $quota_data = $result_quota->fetch_assoc();
        
        $max_quota = 3;
        if ($quota_data['total_ubah'] >= $max_quota) {
            throw new Exception('Kuota ubah jadwal sudah habis (maksimal 3x)');
        }

        // Ambil data sesi lama
        $placeholders = str_repeat('?,', count($member_session_ids) - 1) . '?';
        $stmt_old_sessions = $conn->prepare("
            SELECT id_member_jadwal, tanggal_booking, jam_mulai, jam_selesai
            FROM member_jadwal
            WHERE id_member_jadwal IN ($placeholders)
        ");
        
        $types = str_repeat('i', count($member_session_ids));
        $stmt_old_sessions->bind_param($types, ...$member_session_ids);
        $stmt_old_sessions->execute();
        $result_old_sessions = $stmt_old_sessions->get_result();
        
        $old_sessions_data = [];
        while ($row = $result_old_sessions->fetch_assoc()) {
            $old_sessions_data[] = $row;
        }

        // Validasi H-12 jam untuk setiap sesi
        foreach ($old_sessions_data as $session) {
            if (!validateH12Jam($session['tanggal_booking'], $session['jam_mulai'])) {
                throw new Exception('Salah satu sesi sudah melewati batas waktu ubah jadwal (H-12 jam)');
            }
        }

        // Ambil data jadwal baru
        $stmt_new_time = $conn->prepare("
            SELECT jam_mulai, jam_selesai 
            FROM jadwal_waktu 
            WHERE id_jadwal_waktu = ?
        ");
        $stmt_new_time->bind_param("i", $selected_session);
        $stmt_new_time->execute();
        $result_new_time = $stmt_new_time->get_result();
        
        if ($result_new_time->num_rows === 0) {
            throw new Exception('Jadwal baru tidak valid');
        }
        
        $new_time_data = $result_new_time->fetch_assoc();
        $jam_baru_mulai = $new_time_data['jam_mulai'];
        $jam_baru_selesai = $new_time_data['jam_selesai'];

        $id_jadwal_harian_baru = ensureJadwalHarianExists($conn, $lapangan_id, $new_date);
        ensureJadwalDetailExists($conn, $id_jadwal_harian_baru, $lapangan_id);

        // Update setiap sesi member
        foreach ($member_session_ids as $session_id) {
            $stmt_update = $conn->prepare("
                UPDATE member_jadwal 
                SET tanggal_booking = ?, jam_mulai = ?, jam_selesai = ?, updated_at = NOW()
                WHERE id_member_jadwal = ? AND id_member = ?
            ");
            $stmt_update->bind_param("sssii", $new_date, $jam_baru_mulai, $jam_baru_selesai, $session_id, $member_id);
            $stmt_update->execute();
            
            if ($stmt_update->affected_rows === 0) {
                throw new Exception('Gagal mengupdate sesi member');
            }
        }

        // Release jadwal lama
        foreach ($old_sessions_data as $old_session) {
            $stmt_release_old = $conn->prepare("
                UPDATE jadwal_detail jd
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                SET jd.id_member_jadwal = NULL, jd.status = 'available'
                WHERE jh.tanggal = ? 
                  AND jd.id_member_jadwal = ?
            ");
            $stmt_release_old->bind_param("si", $old_session['tanggal_booking'], $old_session['id_member_jadwal']);
            $stmt_release_old->execute();
        }

        // Booking jadwal baru
        foreach ($member_session_ids as $session_id) {
            $stmt_assign_new = $conn->prepare("
                UPDATE jadwal_detail jd
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                SET jd.id_member_jadwal = ?, jd.status = 'dibooking'
                WHERE jh.tanggal = ? 
                  AND jd.id_jadwal_waktu = ?
            ");
            $stmt_assign_new->bind_param("isi", $session_id, $new_date, $selected_session);
            $stmt_assign_new->execute();
        }

        // Catat history
        $jam_lama_text = count($old_sessions_data) . ' sesi';
        $jam_baru_text = substr($jam_baru_mulai, 0, 5) . '-' . substr($jam_baru_selesai, 0, 5);
        
        $stmt_history = $conn->prepare("
            INSERT INTO history_ubah_jadwal 
            (id_member, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user, waktu_ubah) 
            VALUES (?, 'member', 'multiple', ?, ?, ?, ?, ?, NOW())
        ");
        $tanggal_lama_text = count($old_sessions_data) . ' tanggal berbeda';
        $stmt_history->bind_param("isssii", $member_id, $tanggal_lama_text, $new_date, $jam_baru_text, $lapangan_id, $user_id);
        $stmt_history->execute();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Jadwal member berhasil diubah untuk ' . count($member_session_ids) . ' sesi']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ==============================================
// BACKEND DATA PREPARATION
// ==============================================

if ($is_logged_in) {
    try {
        // Query untuk booking reguler (per sesi)
$stmtBookings = $conn->prepare("
    SELECT 
        db.id_detail_booking AS id_sesi,
        b.id_booking,
        b.tanggal,
        b.status AS status_booking,
        b.payment_status,
        b.total_amount,
        l.nama_lapangan,
        l.id_lapangan,
        jw.jam_mulai,
        jw.jam_selesai,
        COALESCE(h.cnt, 0) AS sudah_ubah,
        p.status AS status_pembatalan,
        p.bukti_refund,
        b.created_at
    FROM detail_booking db
    JOIN booking b ON db.id_booking = b.id_booking
    JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
    JOIN lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN (
        SELECT id_detail_booking, COUNT(*) AS cnt 
        FROM history_ubah_jadwal 
        WHERE tipe = 'reguler' 
        GROUP BY id_detail_booking
    ) h ON db.id_detail_booking = h.id_detail_booking
    LEFT JOIN pembatalan_booking p ON db.id_detail_booking = p.id_detail_booking
    WHERE b.id_user = ? AND b.tipe_booking = 'reguler'
    ORDER BY b.created_at DESC, b.tanggal DESC, jw.jam_mulai DESC
");
        $stmtBookings->bind_param("i", $user_id);
        $stmtBookings->execute();
        $resultBookings = $stmtBookings->get_result();
        $bookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
        $stmtBookings->close();

        // Query untuk member bookings
 $stmtMember = $conn->prepare("
    SELECT 
        m.id_member, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir, 
        m.bukti_pembayaran, m.method, m.total_bayar, m.status, l.nama_lapangan, l.id_lapangan,
        COUNT(DISTINCT mj.id_member_jadwal) as total_sessions,
        COUNT(DISTINCT h.id_ubah_jadwal) as sudah_ubah,
        GROUP_CONCAT(DISTINCT CONCAT(
            DATE_FORMAT(mj.tanggal_booking, '%d/%m/%Y'), 
            ' ', 
            mj.jam_mulai, '-', mj.jam_selesai
        ) SEPARATOR '; ') as jadwal,
        m.created_at
    FROM member m
    JOIN lapangan l ON m.id_lapangan = l.id_lapangan
    LEFT JOIN member_jadwal mj ON m.id_member = mj.id_member
    LEFT JOIN history_ubah_jadwal h ON m.id_member = h.id_member AND h.tipe = 'member'
    WHERE m.id_user = ?
    GROUP BY m.id_member
    ORDER BY m.created_at DESC, m.tanggal_mulai DESC
");
        $stmtMember->bind_param("i", $user_id);
        $stmtMember->execute();
        $resultMember = $stmtMember->get_result();
        $memberBookings = $resultMember->fetch_all(MYSQLI_ASSOC);
        $stmtMember->close();

        foreach ($memberBookings as &$member) {
            $member['unique_id'] = generateMemberId(
                $member['durasi_bulan'], 
                $member['tanggal_mulai'], 
                $member['tanggal_berakhir']
            );
        }

    } catch (Exception $e) {
        $error = "Error mengambil data: " . $e->getMessage();
    }
}
?>

<?php if (!$is_logged_in): ?>
    <div class="not-login-container">
        <i class="fa-solid fa-lock not-login-icon"></i>
        <h3>Akses Diperlukan</h3>
        <p>Anda belum login. Silakan masuk terlebih dahulu untuk melihat riwayat pemesanan dan status membership Anda.</p>
        <div class="auth-buttons">
            <a href="<?= $base_url ?>/auth/login.php" class="btn-primary">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk
            </a>
            <a href="<?= $base_url ?>/auth/register.php" class="btn-outline">
                <i class="fa-solid fa-user-plus"></i> Daftar
            </a>
        </div>
    </div>

<?php else: ?>
    <div class="tabs">
        <button class="tab-button active" data-tab="booking">Booking Reguler</button>
        <button class="tab-button" data-tab="member">Member Saya</button>
    </div>

    <!-- TAB BOOKING REGULER -->
    <div id="booking-tab" class="tab-content active">
        <?php if (isset($error)): ?>
            <div class="error-state">
                <h3>Terjadi Kesalahan</h3>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php elseif (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                <h3>Belum ada riwayat booking reguler</h3>
                <p>Booking lapangan pertama Anda untuk melihat riwayat di sini</p>
                <a href="<?= $base_url ?>/BookingPengguna/booking.php" class="btn-primary">Booking Sekarang</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                // LOGIKA 6 STATUS SESUAI REQUIREMENT
                $status_info = getStatusInfo($booking['status_booking'], $booking['status_pembatalan'], $booking['payment_status']);
                $final_status = $status_info['status'];
                $status_class = $status_info['class'];

                // LOGIKA TOMBOL UBAH & AJUKAN PEMBATALAN
                $canEdit = false;
                $canBatal = false;
                $deadline = "";
                $countdownText = "";

                if (in_array($booking['status_booking'], ['menunggu', 'disetujui']) && empty($booking['status_pembatalan'])) {
    // Tombol Ubah Jadwal
    if ($booking['sudah_ubah'] == 0 && validateH5Jam($booking['tanggal'], $booking['jam_mulai'])) {
        $canEdit = true;
        
        // Bersihkan format waktu sebelum parsing
        $timePart = trim($booking['jam_mulai']);
        // Hapus detik jika sudah ada
        if (substr_count($timePart, ':') >= 2) {
            $timeParts = explode(':', $timePart);
            $timePart = $timeParts[0] . ':' . $timeParts[1];
        }
        
        $datetimeString = "{$booking['tanggal']} {$timePart}:00";
        
        try {
            $bt = new DateTime($datetimeString, new DateTimeZone('Asia/Jakarta'));
            $deadline = (clone $bt)->sub(new DateInterval('PT5H'))->format('c');
            $countdownText = "Bisa diubah sebelum H-5 jam";
        } catch (Exception $e) {
            echo "Error parsing datetime: " . $e->getMessage();
            $canEdit = false;
        }
    }


                    // Tombol Ajukan Pembatalan
                    if (validateH12Jam($booking['tanggal'], $booking['jam_mulai'])) {
                        $canBatal = true;
                        if (empty($countdownText)) {
                            $bt = new DateTime("{$booking['tanggal']} {$booking['jam_mulai']}:00", new DateTimeZone('Asia/Jakarta'));
                            $deadline = (clone $bt)->sub(new DateInterval('PT12H'))->format('c');
                            $countdownText = "Bisa dibatalkan sebelum H-12 jam";
                        }
                    }
                }
                ?>

                <div class="card" data-sesi="<?= $booking['id_sesi'] ?>">
                    <div class="card-header">
                        <div>
                            <h3><?= htmlspecialchars($booking['nama_lapangan']) ?> <span class="user-type reguler">REGULER</span></h3>
                            <p class="booking-id">
                                ID: <?= generateRegulerId($booking['tanggal'], $booking['jam_mulai']) ?>
                                <small style="color:#888; margin-left:8px;">#<?= $booking['id_sesi'] ?></small>
                            </p>
                        </div>
                        <span class="status <?= $status_class ?>"><?= $final_status ?></span>
                    </div>

                    <div class="card-body">
                        <p><strong>Tanggal:</strong> <?= date('l, j F Y', strtotime($booking['tanggal'])) ?></p>
                        <p><strong>Jam:</strong> <?= substr($booking['jam_mulai'], 0, 5) . ' - ' . substr($booking['jam_selesai'], 0, 5) ?></p>
                        <p><strong>Total:</strong> Rp <?= number_format($booking['total_amount'] ?? 0, 0, ',', '.') ?></p>
                    </div>

                    <div class="card-footer">
                        <?php if ($canEdit || $canBatal): ?>
                            <div class="countdown-timer" data-deadline="<?= $deadline ?>">
                                <small>Tersisa: <span class="timer">menghitung...</span></small>
                            </div>
                        <?php else: ?>
                            <div class="countdown-timer expired">
                                <small>Tidak dapat diubah/dibatalkan</small>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="btn-detail" onclick="showDetail(
                                '<?= $booking['id_booking'] ?>',
                                '<?= htmlspecialchars($booking['nama_lapangan']) ?>',
                                '<?= $booking['tanggal'] ?>',
                                '<?= substr($booking['jam_mulai'], 0, 5) . '-' . substr($booking['jam_selesai'], 0, 5) ?>',
                                '<?= number_format($booking['total_amount'] ?? 0, 0, ',', '.') ?>',
                                '<?= $final_status ?>',
                                '<?= htmlspecialchars($booking['alasan_penolakan'] ?? '') ?>',
                                '<?= generateRegulerId($booking['tanggal'], $booking['jam_mulai']) ?>'
                            )">Lihat Detail</button>

                            <?php if ($canEdit): ?>
                                <button class="btn-ubah" onclick="ubahRegulerSesi(<?= $booking['id_sesi'] ?>, '<?= $booking['id_lapangan'] ?>', '<?= $booking['tanggal'] ?>', '<?= substr($booking['jam_mulai'], 0, 5) ?>', '<?= htmlspecialchars($booking['nama_lapangan']) ?>')">
                                    Ubah Jadwal
                                </button>
                            <?php else: ?>
                                <button class="btn-ubah disabled" disabled onclick="showDisabledMessage('ubah')">Ubah Jadwal</button>
                            <?php endif; ?>

                            <?php if ($canBatal && empty($booking['status_pembatalan'])): ?>
                                <button class="btn-batal" onclick="ajukanBatal(<?= $booking['id_sesi'] ?>, '<?= $booking['tanggal'] ?>', '<?= substr($booking['jam_mulai'], 0, 5) ?>')">
                                    Ajukan Pembatalan
                                </button>
                            <?php elseif ($booking['status_pembatalan'] === 'pending'): ?>
                                <button class="btn-batal disabled">Menunggu Konfirmasi</button>
                            <?php elseif ($booking['status_pembatalan'] === 'approved'): ?>
                                <button class="btn-batal disabled">Telah Direfund</button>
                            <?php else: ?>
                                <button class="btn-batal disabled" disabled onclick="showDisabledMessage('batal')">Batalkan</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>  
        <?php endif; ?>
    </div>

    <!-- TAB MEMBER -->
    <div id="member-tab" class="tab-content">
        <?php if (empty($memberBookings)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-id-card"></i>
                <h3>Belum ada membership aktif</h3>
                <p>Daftar member untuk menikmati harga spesial dan fitur ubah jadwal</p>
                <a href="<?= $base_url ?>/member.php" class="btn-primary">Daftar Member</a>
            </div>
        <?php else: ?>
            <?php foreach ($memberBookings as $member): ?>
                <?php
                $ubahCount = $member['sudah_ubah'] ?? 0;
                $maxUbah = 3;
                $canUbah = ($ubahCount < $maxUbah) && ($member['status'] === 'aktif');
                $countdownText = $canUbah ? 'Dapat diubah' : 'Tidak dapat diubah';
                $statusClass = $member['status'] === 'aktif' ? 'disetujui' : 'ditolak';
                ?>
                
                <div class="card" data-member="<?= $member['id_member'] ?>">
                    <div class="card-header">
                        <div>
                            <h3>
                                <?= htmlspecialchars($member['nama_lapangan']) ?> 
                                <span class="user-type member">MEMBER</span>
                            </h3>
                            <p class="booking-id">ID: <?= $member['unique_id'] ?? '#' . $member['id_member'] ?></p>
                        </div>
                        <span class="status <?= $statusClass ?>">
                            <?= ucfirst($member['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>Durasi:</strong> <?= $member['durasi_bulan'] ?> bulan</p>
                        <p><strong>Periode:</strong> 
                            <?= (new DateTime($member['tanggal_mulai']))->format('d M Y') . ' - ' . (new DateTime($member['tanggal_berakhir']))->format('d M Y') ?>
                        </p>
                        <p><strong>Total Bayar:</strong> Rp <?= number_format($member['total_bayar'], 0, ',', '.') ?></p>
                        <p><strong>Sisa Ubah Jadwal:</strong> <?= $maxUbah - $ubahCount ?> dari <?= $maxUbah ?> kali</p>
                        <?php if ($member['total_sessions'] ?? 0 > 0): ?>
                            <p><strong>Jadwal Terjadwal:</strong> <?= $member['total_sessions'] ?> sesi</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="countdown <?= !$canUbah ? 'expired' : '' ?>">
                            <?= $countdownText ?>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-detail" onclick="showMemberDetail(
                                '<?= $member['id_member'] ?>',
                                '<?= htmlspecialchars($member['nama_lapangan'], ENT_QUOTES) ?>',
                                '<?= $member['durasi_bulan'] ?>',
                                '<?= $member['tanggal_mulai'] ?>',
                                '<?= $member['tanggal_berakhir'] ?>',
                                '<?= number_format($member['total_bayar'], 0, ',', '.') ?>',
                                '<?= htmlspecialchars($member['status'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($member['jadwal'] ?? 'Belum ada jadwal', ENT_QUOTES) ?>',
                                '<?= $ubahCount ?>',
                                '<?= $maxUbah ?>',
                                '<?= $member['unique_id'] ?? 'MMBR' . $member['id_member'] ?>'
                            )">
                                <i class="fa-solid fa-eye"></i> Lihat Detail
                            </button>
                            
                            <?php if ($canUbah): ?>
                                <button class="btn-ubah" 
                                    onclick="showUbahJadwalMember(
                                        '<?= $member['id_member'] ?>',
                                        '<?= htmlspecialchars($member['nama_lapangan']) ?>',
                                        '<?= $maxUbah - $ubahCount ?>',
                                        '<?= $member['id_lapangan'] ?>'
                                    )">
                                    <i class="fa-solid fa-calendar-alt"></i> Ubah Jadwal
                                </button>
                            <?php else: ?>
                                <button class="btn-ubah disabled" 
                                    onclick="showDisabledReason('<?= $member['status'] !== 'aktif' ? 'member_not_active' : 'quota_exceeded' ?>')">
                                    <i class="fa-solid fa-calendar-alt"></i> Ubah Jadwal
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="riwayat.js?v=<?= time() ?>"></script>

<?php endif; ?>

<?php 
require '../include_user/footer.php'; 
?>