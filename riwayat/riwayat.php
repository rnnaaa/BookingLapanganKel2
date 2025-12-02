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

function getJenisPembayaran($conn, $id_booking) {
    $sql = "SELECT tipe, amount FROM pembayaran WHERE booking_id = ? ORDER BY id_pembayaran";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_booking);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $hasDP = false;
    while ($row = $res->fetch_assoc()) {
        if ($row['tipe'] === 'DP') $hasDP = true;
    }
    return $hasDP ? 'dp' : 'lunas';
}

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

function getStatusInfo($status_booking, $status_pembatalan, $payment_status, $tanggal, $jam_mulai) {
    // AUTO REJECT jika sudah lewat H-12 dan status masih pending
    if ($status_pembatalan === 'pending') {
        if (!validateH12Jam($tanggal, $jam_mulai)) {
            return ['status' => 'Pembatalan Ditolak (Lewat Waktu)', 'class' => 'ditolak'];
        }
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
        return ['status' => 'Menunggu Pembayaran', 'class' => 'menunggu'];
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

        if ($lapangan_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Lapangan tidak valid']);
            exit;
        }

        // Validasi tanggal
        $today = date('Y-m-d');
        $max_date = date('Y-m-d', strtotime('+7 days'));
        
        if ($tanggal < $today) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak bisa memilih tanggal yang sudah lewat']);
            exit;
        }
        
        if ($tanggal > $max_date) {
            echo json_encode(['status' => 'error', 'message' => 'Tanggal booking maksimal 7 hari dari sekarang']);
            exit;
        }

        try {
            // QUERY untuk mengambil semua jam termasuk yang dibooking
            $sql = "
                SELECT 
                    jw.id_jadwal_waktu,
                    jw.jam_mulai,
                    jw.jam_selesai,
                    l.harga_per_jam AS harga,
                    COALESCE(jd.status, 'available') as status_jadwal,
                    jd.id_booking,
                    jd.id_member_jadwal
                FROM jadwal_waktu jw
                JOIN lapangan l ON jw.id_lapangan = l.id_lapangan
                LEFT JOIN jadwal_detail jd ON jd.id_jadwal_waktu = jw.id_jadwal_waktu 
                    AND jd.id_jadwal_harian = (
                        SELECT id_jadwal_harian FROM jadwal_harian 
                        WHERE id_lapangan = ? AND tanggal = ?
                    )
                WHERE jw.id_lapangan = ?
                AND jw.aktif = 1
                ORDER BY jw.jam_mulai ASC
            ";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("isi", $lapangan_id, $tanggal, $lapangan_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $sessions = [];
            while ($row = $result->fetch_assoc()) {
                // Tampilkan semua jam, tapi bedakan status
                $isAvailable = $row['status_jadwal'] === 'available';
                $isOwnBooking = ($row['id_booking'] == $booking_id);
                
                $sessions[] = [
                    'id_jadwal_waktu' => $row['id_jadwal_waktu'],
                    'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                    'jam_selesai' => substr($row['jam_selesai'], 0, 5),
                    'harga' => (int)$row['harga'],
                    'status' => $row['status_jadwal'],
                    'available' => $isAvailable || $isOwnBooking,
                    'disabled' => !$isAvailable && !$isOwnBooking,
                    'disabled_reason' => (!$isAvailable && !$isOwnBooking) ? 'Sudah dibooking' : ''
                ];
            }

            echo json_encode([
                'status' => 'success',
                'available_sessions' => $sessions
            ]);

        } catch (Exception $e) {
            error_log("ERROR in get_available_sessions: " . $e->getMessage());
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
                'nama_lapangan' => $row['nama_lapangan'],
                'can_change' => validateH12Jam($row['tanggal_booking'], $row['jam_mulai'])
            ];
        }

        echo json_encode([
            'status' => 'success',
            'member_sessions' => $sessions
        ]);
        exit;
    }

    if ($_GET['action'] === 'get_user_info') {
        header('Content-Type: application/json');
        
        if (!$is_logged_in) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        try {
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
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
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
                   jw2.jam_mulai AS jam_baru, jw2.jam_selesai AS jam_selesai_baru,
                   jw.id_jadwal_waktu as old_jadwal_waktu
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
        $row = $stmt->get_result()->fetch_assoc();
        $status = $row['status'] ?? 'available';
        
        if ($status !== 'available') {
            throw new Exception('Jadwal sudah dipesan orang lain');
        }

        // Release jadwal lama
        $stmt = $conn->prepare("
            UPDATE jadwal_detail jd 
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian 
            SET jd.id_booking = NULL, jd.status = 'available'
            WHERE jh.tanggal = ? AND jd.id_jadwal_waktu = ?
        ");
        $stmt->bind_param("si", $data['tanggal_lama'], $data['old_jadwal_waktu']);
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

// 2. AJUKAN PEMBATALAN - FIXED VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajukan_pembatalan') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $id_sesi = (int)($_POST['id_sesi'] ?? 0);
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $bank_ewallet = trim($_POST['bank_ewallet'] ?? '');

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
            (id_detail_booking, id_user, nama_pengaju, nomor_rekening, bank_ewallet, atas_nama, jumlah_refund, status, requested_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, 'pending', NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare insert failed: " . $conn->error);
        }
        
        $stmt->bind_param("iissss", 
            $id_sesi, 
            $user_id, 
            $user_info['nama'] ?? 'User', 
            $no_rekening,
            $bank_ewallet,
            $nama_penerima
        );
        
        if ($stmt->execute()) {
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
    $member_session_ids = array_map('intval', $member_session_ids);

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
            throw new Exception('Membership belum aktif');
        }

        // Validasi batas tanggal member
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

        // Pastikan jadwal baru tersedia
        $id_jadwal_harian_baru = ensureJadwalHarianExists($conn, $lapangan_id, $new_date);
        ensureJadwalDetailExists($conn, $id_jadwal_harian_baru, $lapangan_id);

        // Cek apakah slot baru available
        $stmt_check = $conn->prepare("
            SELECT status FROM jadwal_detail 
            WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?
        ");
        $stmt_check->bind_param("ii", $id_jadwal_harian_baru, $selected_session);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        $status_baru = $check_result->num_rows > 0 ? $check_result->fetch_assoc()['status'] : 'available';
        
        if ($status_baru !== 'available') {
            throw new Exception('Jadwal yang dipilih sudah tidak tersedia');
        }

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

// 4. AJUKAN PEMBATALAN MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajukan_batal_member') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $member_id = (int)($_POST['member_id'] ?? 0);
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $bank_ewallet = trim($_POST['bank_ewallet'] ?? '');

    if ($member_id <= 0 || empty($nama_penerima) || empty($no_rekening) || empty($bank_ewallet)) {
        echo json_encode(['status' => 'error', 'message' => 'Data rekening harus diisi lengkap']);
        exit;
    }

    try {
        // Cek member
        $stmt = $conn->prepare("
            SELECT m.id_member, m.status, m.tanggal_mulai, u.nama
            FROM member m
            JOIN users u ON m.id_user = u.id_user
            WHERE m.id_member = ? AND m.id_user = ?
        ");
        $stmt->bind_param("ii", $member_id, $user_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Member tidak ditemukan']);
            exit;
        }

        if ($data['status'] !== 'aktif') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya membership aktif yang bisa dibatalkan']);
            exit;
        }

        // Validasi H-12 jam dari tanggal mulai
        $tanggal_mulai = $data['tanggal_mulai'];
        $tanggal_mulai_obj = new DateTime($tanggal_mulai);
        $deadline = (clone $tanggal_mulai_obj)->sub(new DateInterval('PT12H'));
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        
        if ($now > $deadline) {
            echo json_encode(['status' => 'error', 'message' => 'Waktu pengajuan pembatalan telah habis (H-12 jam sebelum tanggal mulai)']);
            exit;
        }

        // Cek sudah pernah diajukan belum
        $stmt_check = $conn->prepare("SELECT id FROM pembatalan_member WHERE id_member = ?");
        $stmt_check->bind_param("i", $member_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Pengajuan pembatalan sudah pernah dikirim']);
            exit;
        }

        // Insert pengajuan pembatalan
        $stmt = $conn->prepare("
            INSERT INTO pembatalan_member 
            (id_member, id_user, nama_pengaju, nomor_rekening, bank_ewallet, atas_nama, status, requested_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->bind_param("iissss", 
            $member_id, 
            $user_id, 
            $data['nama'], 
            $no_rekening,
            $bank_ewallet,
            $nama_penerima
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Pengajuan pembatalan membership berhasil dikirim'
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error in ajukan_batal_member: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ==============================================
// BACKEND DATA PREPARATION
// ==============================================

if ($is_logged_in) {
    try {
        // Query untuk booking reguler - FIXED dengan harga yang benar
        $stmtBookings = $conn->prepare("
            SELECT 
                db.id_detail_booking AS id_sesi,
                b.id_booking,
                b.tanggal,
                b.status AS status_booking,
                b.payment_status,
                b.dp_amount,
                b.total_amount,
                b.remaining_amount,
                l.nama_lapangan,
                l.id_lapangan,
                jw.jam_mulai,
                jw.jam_selesai,
                db.harga AS harga_sesi,
                COALESCE(h.cnt, 0) AS sudah_ubah,
                p.status AS status_pembatalan,
                p.bukti_refund,
                b.created_at,
                u.username,
                u.nama as nama_user
            FROM detail_booking db
            JOIN booking b ON db.id_booking = b.id_booking
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            JOIN lapangan l ON b.id_lapangan = l.id_lapangan
            JOIN users u ON b.id_user = u.id_user
            LEFT JOIN (
                SELECT id_detail_booking, COUNT(*) AS cnt 
                FROM history_ubah_jadwal 
                WHERE tipe = 'reguler' 
                GROUP BY id_detail_booking
            ) h ON db.id_detail_booking = h.id_detail_booking
            LEFT JOIN pembatalan_booking p ON db.id_detail_booking = p.id_detail_booking
            WHERE b.id_user = ? AND b.tipe_booking = 'reguler'
            ORDER BY b.created_at DESC, b.id_booking DESC, db.id_detail_booking
        ");
        $stmtBookings->bind_param("i", $user_id);
        $stmtBookings->execute();
        $resultBookings = $stmtBookings->get_result();
        $bookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
        $stmtBookings->close();

        // Query untuk member bookings - FIXED dengan status yang benar
        $stmtMember = $conn->prepare("
            SELECT 
                m.id_member, 
                m.durasi_bulan, 
                m.tanggal_mulai, 
                m.tanggal_berakhir, 
                m.bukti_pembayaran, 
                m.method, 
                m.total_bayar, 
                m.status, 
                l.nama_lapangan, 
                l.id_lapangan,
                u.username,
                u.nama as nama_user,
                COUNT(DISTINCT mj.id_member_jadwal) as total_sessions,
                COUNT(DISTINCT h.id_ubah_jadwal) as sudah_ubah,
                GROUP_CONCAT(DISTINCT CONCAT(
                    DATE_FORMAT(mj.tanggal_booking, '%d/%m/%Y'), 
                    ' ', 
                    TIME_FORMAT(mj.jam_mulai, '%H:%i'), 
                    '-', 
                    TIME_FORMAT(mj.jam_selesai, '%H:%i')
                ) SEPARATOR '; ') as jadwal,
                m.created_at
            FROM member m
            JOIN lapangan l ON m.id_lapangan = l.id_lapangan
            JOIN users u ON m.id_user = u.id_user
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
                // LOGIKA 6 STATUS SESUAI REQUIREMENT dengan auto reject
                $status_info = getStatusInfo(
                    $booking['status_booking'], 
                    $booking['status_pembatalan'], 
                    $booking['payment_status'],
                    $booking['tanggal'],
                    $booking['jam_mulai']
                );
                $final_status = $status_info['status'];
                $status_class = $status_info['class'];

                // LOGIKA TOMBOL UBAH & AJUKAN PEMBATALAN
                $canEdit = false;
                $canBatal = false;
                $deadline = "";
                $countdownText = "";
                $disableReason = "";

                if (in_array($booking['status_booking'], ['menunggu', 'disetujui']) && empty($booking['status_pembatalan'])) {
                    // Tombol Ubah Jadwal
                    if ($booking['sudah_ubah'] == 0 && validateH5Jam($booking['tanggal'], $booking['jam_mulai'])) {
                        $canEdit = true;
                        $deadline = date('c', strtotime("{$booking['tanggal']} {$booking['jam_mulai']} -5 hours"));
                        $countdownText = "Bisa diubah sebelum H-5 jam";
                    } else {
                        if ($booking['sudah_ubah'] > 0) {
                            $disableReason = "Sudah pernah diubah";
                        } else {
                            $disableReason = "Sudah lewat batas H-5 jam";
                        }
                    }

                    // Tombol Ajukan Pembatalan
                    if (validateH12Jam($booking['tanggal'], $booking['jam_mulai'])) {
                        $canBatal = true;
                        if (empty($deadline)) {
                            $deadline = date('c', strtotime("{$booking['tanggal']} {$booking['jam_mulai']} -12 hours"));
                            $countdownText = "Bisa dibatalkan sebelum H-12 jam";
                        }
                    } else {
                        if (empty($disableReason)) {
                            $disableReason = "Sudah lewat batas H-12 jam";
                        }
                    }
                }
// Dalam bagian POST ajukan_pembatalan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajukan_pembatalan') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $id_sesi = (int)($_POST['id_sesi'] ?? 0);
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $bank_ewallet = trim($_POST['bank_ewallet'] ?? '');

    // Validasi input
    if ($id_sesi <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID sesi tidak valid']);
        exit;
    }

    if (empty($nama_penerima) || strlen($nama_penerima) < 3) {
        echo json_encode(['status' => 'error', 'message' => 'Nama penerima minimal 3 karakter']);
        exit;
    }

    // Validasi nomor rekening hanya angka
    if (empty($no_rekening) || !preg_match('/^[0-9]+$/', $no_rekening)) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor rekening hanya boleh berisi angka']);
        exit;
    }

    // Validasi panjang nomor rekening
    if (strlen($no_rekening) < 8 || strlen($no_rekening) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor rekening harus 8-20 digit']);
        exit;
    }

    if (empty($bank_ewallet)) {
        echo json_encode(['status' => 'error', 'message' => 'Bank/E-Wallet harus dipilih']);
        exit;
    }

    try {
        // ... sisa kode yang sama ...
        
    } catch (Exception $e) {
        error_log("Error in ajukan_pembatalan: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    exit;
}
                // Hitung total pembayaran yang benar
                $stmt_pay = $conn->prepare("SELECT tipe, SUM(amount) as total FROM pembayaran WHERE booking_id = ? GROUP BY tipe");
                $stmt_pay->bind_param("i", $booking['id_booking']);
                $stmt_pay->execute();
                $payments_result = $stmt_pay->get_result();
                
                $total_dp = 0;
                $total_lunas = 0;
                $ada_DP = false;
                
                while ($p = $payments_result->fetch_assoc()) {
                    if ($p['tipe'] === 'DP') {
                        $total_dp = $p['total'];
                        $ada_DP = true;
                    } elseif ($p['tipe'] === 'pelunasan') {
                        $total_lunas = $p['total'];
                    }
                }
                
                $total_bayar = $total_dp + $total_lunas;
                $total_normal = $booking['harga_sesi']; // Harga per sesi
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
                        <p><strong>Pemesan:</strong> <?= htmlspecialchars($booking['nama_user']) ?> (@<?= htmlspecialchars($booking['username']) ?>)</p>
                        
                        <?php if ($ada_DP): ?>
                            <p><strong>Pembayaran:</strong> 
                                <span style="color:#e67e22;font-weight:bold">DP Rp <?= number_format($total_dp, 0, ',', '.') ?></span>
                            </p>
                            <?php if ($total_lunas > 0): ?>
                                <p style="color:#27ae60;margin-top:4px">
                                    <small>+ Pelunasan: <strong>Rp <?= number_format($total_lunas, 0, ',', '.') ?></strong></small>
                                </p>
                            <?php else: ?>
                                <p style="color:#c0392b;margin-top:4px">
                                    <small>Sisa pelunasan: <strong>Rp <?= number_format($total_normal - $total_dp, 0, ',', '.') ?></strong></small>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><strong>Total Lunas:</strong> 
                                <span style="color:#27ae60;font-weight:bold">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <?php if ($canEdit || $canBatal): ?>
                            <div class="countdown-timer" data-deadline="<?= $deadline ?>">
                                <small><?= $countdownText ?>: <span class="timer">menghitung...</span></small>
                            </div>
                        <?php elseif (!empty($disableReason)): ?>
                            <div class="countdown-timer expired">
                                <small><?= $disableReason ?></small>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="btn-detail" onclick="showDetail(
                                '<?= $booking['id_booking'] ?>',
                                '<?= htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES) ?>',
                                '<?= $booking['tanggal'] ?>',
                                '<?= substr($booking['jam_mulai'], 0, 5) . '-' . substr($booking['jam_selesai'], 0, 5) ?>',
                                '<?= number_format($total_normal, 0, ',', '.') ?>',
                                '<?= $final_status ?>',
                                '<?= htmlspecialchars($booking['alasan_penolakan'] ?? '', ENT_QUOTES) ?>',
                                '<?= generateRegulerId($booking['tanggal'], $booking['jam_mulai']) ?>',
                                '<?= $booking['payment_status'] ?>',
                                '<?= $total_dp ?>',
                                '<?= max(0, $total_normal - $total_dp) ?>',
                                '<?= $booking['username'] ?>',
                                '<?= htmlspecialchars($booking['nama_user'], ENT_QUOTES) ?>'
                            )">Lihat Detail</button>

                            <?php if ($canEdit): ?>
                                <button class="btn-ubah" onclick="ubahRegulerSesi(<?= $booking['id_sesi'] ?>, '<?= $booking['id_lapangan'] ?>', '<?= $booking['tanggal'] ?>', '<?= substr($booking['jam_mulai'], 0, 5) ?>', '<?= htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES) ?>')">
                                    Ubah Jadwal
                                </button>
                            <?php else: ?>
                                <button class="btn-ubah disabled" onclick="showAlert('<?= $disableReason ?>')">Ubah Jadwal</button>
                            <?php endif; ?>

    <?php if ($canBatal && empty($booking['status_pembatalan'])): ?>
        <button class="btn-batal" onclick="ajukanBatal(
            <?= $booking['id_sesi'] ?>, 
            '<?= $booking['tanggal'] ?>', 
            '<?= substr($booking['jam_mulai'], 0, 5) ?>', 
            '<?= htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES) ?>', 
            '<?= substr($booking['jam_selesai'], 0, 5) ?>'
        )">
            Ajukan Pembatalan
        </button>
    <?php elseif ($booking['status_pembatalan'] === 'pending'): ?>
        <button class="btn-batal disabled">Menunggu Konfirmasi</button>
    <?php elseif ($booking['status_pembatalan'] === 'approved'): ?>
        <button class="btn-batal disabled">Telah Direfund</button>
    <?php else: ?>
        <button class="btn-batal disabled" onclick="showAlert('<?= $disableReason ?>')">Batalkan</button>
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
                $statusClass = $member['status'] === 'aktif' ? 'disetujui' : 
                              ($member['status'] === 'pending' ? 'menunggu' : 'ditolak');
                
                // Hitung H-12 jam dari tanggal mulai
                $tanggal_mulai = new DateTime($member['tanggal_mulai']);
                $deadline = (clone $tanggal_mulai)->sub(new DateInterval('PT12H'));
                $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
                $canBatalMember = ($member['status'] === 'aktif') && ($now <= $deadline);
                ?>
                
                <div class="card" data-member="<?= $member['id_member'] ?>">
                    <div class="card-header">
                        <div>
                            <h3>
                                <?= htmlspecialchars($member['nama_lapangan']) ?> 
                                <span class="user-type member">MEMBER</span>
                            </h3>
                            <p class="booking-id">ID: <?= $member['unique_id'] ?? 'MMBR' . $member['id_member'] ?></p>
                        </div>
                        <span class="status <?= $statusClass ?>">
                            <?= ucfirst($member['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>Durasi:</strong> <?= $member['durasi_bulan'] ?> bulan</p>
                        <p><strong>Periode:</strong> 
                            <?= date('d M Y', strtotime($member['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($member['tanggal_berakhir'])) ?>
                        </p>
                        <p><strong>Pemesan:</strong> <?= htmlspecialchars($member['nama_user']) ?> (@<?= htmlspecialchars($member['username']) ?>)</p>
                        <p><strong>Total Bayar:</strong> Rp <?= number_format($member['total_bayar'], 0, ',', '.') ?></p>
                        <p><strong>Sisa Ubah Jadwal:</strong> <?= $maxUbah - $ubahCount ?> dari <?= $maxUbah ?> kali</p>
                        <?php if ($member['total_sessions'] ?? 0 > 0): ?>
                            <p><strong>Jadwal Terjadwal:</strong> <?= $member['total_sessions'] ?> sesi</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="countdown <?= !$canUbah ? 'expired' : '' ?>">
                            <?= $canUbah ? 'Dapat diubah' : 'Kuota habis' ?>
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
                                '<?= $member['unique_id'] ?? 'MMBR' . $member['id_member'] ?>',
                                '<?= htmlspecialchars($member['username'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($member['nama_user'], ENT_QUOTES) ?>'
                            )">
                                <i class="fa-solid fa-eye"></i> Lihat Detail
                            </button>
                            
                            <?php if ($member['status'] === 'aktif' && $canUbah): ?>
                                <button class="btn-ubah" onclick="showUbahJadwalMember(<?= $member['id_member'] ?>, '<?= $member['id_lapangan'] ?>', '<?= htmlspecialchars($member['nama_lapangan'], ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-calendar-alt"></i> Ubah Jadwal
                                </button>
                            <?php else: ?>
                                <button class="btn-ubah disabled" onclick="showAlert('<?= $member['status'] !== 'aktif' ? 'Menunggu approve admin' : 'Kuota ubah jadwal habis' ?>')">
                                    <i class="fa-solid fa-calendar-alt"></i> Ubah Jadwal
                                </button>
                            <?php endif; ?>

                            <?php if ($canBatalMember): ?>
                                <button class="btn-batal" onclick="ajukanBatalMembership(<?= $member['id_member'] ?>, '<?= $member['tanggal_mulai'] ?>')">
                                    <i class="fa-solid fa-ban"></i> Batalkan Member
                                </button>
                            <?php else: ?>
                                <button class="btn-batal disabled" onclick="showAlert('<?= $member['status'] !== 'aktif' ? 'Membership belum aktif' : 'Sudah lewat batas H-12 jam' ?>')">
                                    <i class="fa-solid fa-ban"></i> Batalkan Member
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<!-- MODAL AJUKAN PEMBATALAN -->
<div id="modalBatal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Ajukan Pembatalan</h3>
        <form id="formPembatalan">
            <input type="hidden" id="id_sesi_batal">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username_batal" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Tanggal Booking</label>
                <input type="text" id="tanggal_batal" readonly>
            </div>
            
            <div class="form-group">
                <label>Jam Booking</label>
                <input type="text" id="jam_batal" readonly>
            </div>
            
            <div class="form-group">
                <label>Lapangan</label>
                <input type="text" id="lapangan_batal" readonly>
            </div>
            
            <div class="form-group">
                <label>Atas Nama Penerima Refund <span class="required">*</span></label>
                <input type="text" id="nama_penerima" placeholder="Nama pemilik rekening/ewallet" required>
            </div>
            
            <div class="form-group">
                <label>Nomor Rekening/E-Wallet <span class="required">*</span></label>
                <input type="text" id="no_rekening" 
                       placeholder="Contoh: 1234567890" 
                       pattern="[0-9]+"
                       title="Hanya angka yang diperbolehkan"
                       minlength="8"
                       maxlength="20"
                       required>
                <small class="form-text text-muted">Hanya angka, minimal 8 digit</small>
            </div>
            
            <div class="form-group">
                <label>Bank/E-Wallet <span class="required">*</span></label>
                <select id="bank_ewallet" required>
                    <option value="">Pilih Bank/E-Wallet</option>
                    <option value="BCA">BCA</option>
                    <option value="BRI">BRI</option>
                    <option value="BNI">BNI</option>
                    <option value="Mandiri">Mandiri</option>
                    <option value="Dana">Dana</option>
                    <option value="OVO">OVO</option>
                    <option value="Gopay">Gopay</option>
                    <option value="ShopeePay">ShopeePay</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-submit">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

    <!-- MODAL AJUKAN PEMBATALAN MEMBER -->
    <div id="modalBatalMember" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Ajukan Pembatalan Membership</h3>
            <form id="formPembatalanMember">
                <input type="hidden" id="id_member_batal">
                
                <div class="form-group">
                    <label>Tanggal Mulai Member</label>
                    <input type="text" id="tanggal_mulai_member" readonly>
                </div>
                
                <div class="form-group">
                    <label>Atas Nama Penerima Refund <span class="required">*</span></label>
                    <input type="text" id="nama_penerima_member" placeholder="Nama pemilik rekening/ewallet" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor Rekening/E-Wallet <span class="required">*</span></label>
                    <input type="text" id="no_rekening_member" placeholder="Contoh: 1234567890" required>
                </div>
                
                <div class="form-group">
                    <label>Bank/E-Wallet <span class="required">*</span></label>
                    <select id="bank_ewallet_member" required>
                        <option value="">Pilih Bank/E-Wallet</option>
                        <option value="BCA">BCA</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="Dana">Dana</option>
                        <option value="OVO">OVO</option>
                        <option value="Gopay">Gopay</option>
                        <option value="ShopeePay">ShopeePay</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModalMember()">Batal</button>
                    <button type="submit" class="btn-submit">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL UBAH JADWAL REGULER -->
    <div id="modalUbahReguler" class="modal large">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Ubah Jadwal Reguler</h3>
            <form id="formUbahReguler">
                <input type="hidden" id="id_sesi_ubah">
                <input type="hidden" id="id_lapangan_ubah">
                
                <div class="form-group">
                    <label>Lapangan</label>
                    <input type="text" id="nama_lapangan_ubah" readonly>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Lama</label>
                    <input type="text" id="tanggal_lama" readonly>
                </div>
                
                <div class="form-group">
                    <label>Jam Lama</label>
                    <input type="text" id="jam_lama" readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilih Tanggal Baru <span class="required">*</span></label>
                        <input type="date" id="new_date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Pilih Jam Baru <span class="required">*</span></label>
                    <div id="session-list" class="session-grid">
                        <p>Pilih tanggal terlebih dahulu</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModalUbah()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL UBAH JADWAL MEMBER -->
    <div id="modalUbahMember" class="modal large">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Ubah Jadwal Member</h3>
            <form id="formUbahMember">
                <input type="hidden" id="id_member_ubah">
                <input type="hidden" id="id_lapangan_member_ubah">
                
                <div class="form-group">
                    <label>Lapangan</label>
                    <input type="text" id="nama_lapangan_member" readonly>
                </div>
                
                <div class="form-group">
                    <label>Pilih Sesi yang Akan Diubah</label>
                    <div id="member-session-list" class="session-list">
                        <p>Memuat sesi...</p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilih Tanggal Baru <span class="required">*</span></label>
                        <input type="date" id="new_date_member" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Pilih Jam Baru <span class="required">*</span></label>
                    <div id="member-session-list-new" class="session-grid">
                        <p>Pilih tanggal terlebih dahulu</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModalUbahMember()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL BOOKING -->
    <div id="modalDetail" class="modal large">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Detail Booking</h3>
            <div class="detail-content">
                <div class="detail-section">
                    <h4>Informasi Booking</h4>
                    <table>
                        <tr><td>ID Booking</td><td id="detail-id">-</td></tr>
                        <tr><td>Lapangan</td><td id="detail-lapangan">-</td></tr>
                        <tr><td>Tanggal</td><td id="detail-tanggal">-</td></tr>
                        <tr><td>Jam</td><td id="detail-jam">-</td></tr>
                        <tr><td>Pemesan</td><td id="detail-pemesan">-</td></tr>
                        <tr><td>Status</td><td id="detail-status">-</td></tr>
                    </table>
                </div>
                
                <div class="detail-section">
                    <h4>Informasi Pembayaran</h4>
                    <table>
                        <tr><td>Total Harga</td><td id="detail-total">-</td></tr>
                        <tr><td>Status Pembayaran</td><td id="detail-payment-status">-</td></tr>
                        <tr><td>DP Dibayar</td><td id="detail-dp">-</td></tr>
                        <tr><td>Sisa Pelunasan</td><td id="detail-sisa">-</td></tr>
                    </table>
                </div>
                
                <div id="qr-section" class="detail-section" style="display:none;">
                    <h4>QR Code Check-in</h4>
                    <div id="qrcode"></div>
                    <p><small>Tunjukkan QR ini saat check-in di lokasi</small></p>
                </div>
                
                <div id="refund-section" class="detail-section" style="display:none;">
                    <h4>Informasi Refund</h4>
                    <table>
                        <tr><td>Status Refund</td><td id="detail-refund-status">-</td></tr>
                        <tr><td>Bukti Transfer</td><td id="detail-bukti-refund">-</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL MEMBER -->
    <div id="modalDetailMember" class="modal large">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Detail Membership</h3>
            <div class="detail-content">
                <div class="detail-section">
                    <h4>Informasi Member</h4>
                    <table>
                        <tr><td>ID Member</td><td id="detail-member-id">-</td></tr>
                        <tr><td>Lapangan</td><td id="detail-member-lapangan">-</td></tr>
                        <tr><td>Pemesan</td><td id="detail-member-pemesan">-</td></tr>
                        <tr><td>Durasi</td><td id="detail-member-durasi">-</td></tr>
                        <tr><td>Periode</td><td id="detail-member-periode">-</td></tr>
                        <tr><td>Status</td><td id="detail-member-status">-</td></tr>
                    </table>
                </div>
                
                <div class="detail-section">
                    <h4>Informasi Pembayaran</h4>
                    <table>
                        <tr><td>Total Bayar</td><td id="detail-member-total">-</td></tr>
                        <tr><td>Metode</td><td id="detail-member-metode">-</td></tr>
                    </table>
                </div>
                
                <div class="detail-section">
                    <h4>Jadwal Sesi</h4>
                    <div id="detail-member-jadwal" class="jadwal-list">
                        <!-- Daftar jadwal akan dimasukkan di sini -->
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Riwayat Ubah Jadwal</h4>
                    <table>
                        <tr><td>Sudah Diubah</td><td id="detail-member-sudah-ubah">-</td></tr>
                        <tr><td>Sisa Kuota</td><td id="detail-member-sisa-kuota">-</td></tr>
                    </table>
                </div>
                
                <div id="qr-section-member" class="detail-section" style="display:none;">
                    <h4>QR Code Member</h4>
                    <div id="qrcode-member"></div>
                    <p><small>Tunjukkan QR ini saat menggunakan fasilitas member</small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="riwayat.js?v=<?= time() ?>"></script>

<?php endif; ?>

<?php 
require '../include_user/footer.php'; 
?>