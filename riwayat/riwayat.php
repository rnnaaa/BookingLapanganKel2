<?php
// ==============================================
// FILE: riwayat.php
// DESCRIPTION: Main file untuk riwayat booking (Backend + Frontend + API)
// ==============================================

// ===== 1. INITIALIZATION & CONFIGURATION =====
require '../include_user/header.php';
require '../config/database.php';
// Ambil data user dari session (header.php sudah start session)
$user_id = $_SESSION['id_user'] ?? null;
$is_logged_in = ($user_id && $user_id != 1); // admin ID = 1 gak boleh masuk sini
$bookings = [];
$memberBookings = [];
$error = null;

// ===== 2. PHP FUNCTIONS (BACKEND BUSINESS LOGIC) =====

/**
 * Generate unique ID untuk booking reguler
 * Format: RGLR{dd}{mm}{HH}{ii} -> RGLR24110800 (24 Nov 08:00)
 */

function generateRegulerId($tanggal, $jam_pertama) {
    try {
        $date = new DateTime($tanggal);
        
        // Fix: Kalau jam_pertama NULL atau kosong, pakai 00:00
        $jam = '00:00';
        if ($jam_pertama && trim($jam_pertama) !== '') {
            // Kalau formatnya 16:00 atau 16:00:00, ambil jam & menit aja
            $parts = explode(':', $jam_pertama);
            $hour = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $minute = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : '00';
            $jam = "$hour:$minute";
        }
        
        $day = $date->format('d');
        $month = $date->format('m');
        $hour = substr($jam, 0, 2);
        $minute = substr($jam, 3, 2);
        
        return "RGLR{$day}{$month}{$hour}{$minute}";
    } catch (Exception $e) {
        // Kalau error, return fallback
        return "RGLR00000000";
    }
}

/**
 * Generate unique ID untuk member
 * Format: MMBR{durasi}{start_month}{end_month} -> MMBR21101 (2 bulan, Nov - Jan)
 */
function generateMemberId($durasi_bulan, $tanggal_mulai, $tanggal_berakhir) {
    try {
        $start = new DateTime($tanggal_mulai);
        $end = new DateTime($tanggal_berakhir);
        
        $durasi = str_pad($durasi_bulan, 1, '0', STR_PAD_LEFT);
        $start_month = $start->format('m');
        $end_month = $end->format('m');
        
        return "MMBR{$durasi}{$start_month}{$end_month}";
    } catch (Exception $e) {
        return "MMBR100";
    }}

/**
 * Validasi H-5 jam — versi FINAL & ANTI-ERROR
 */
function validateH5Jam($tanggal_booking, $jam_pertama) {
    if (empty($tanggal_booking) || empty($jam_pertama)) return false;

    $tz = new DateTimeZone('Asia/Jakarta');
    
    // Pastikan hanya ambil HH:MM (bisa dari 21:00 atau 21:00:00)
    $jam = substr(trim($jam_pertama), 0, 5);

    try {
        $bookingTime = new DateTime("$tanggal_booking $jam:00", $tz);
        $deadline = clone $bookingTime;
        $deadline->sub(new DateInterval('PT5H'));
        
        $now = new DateTime('now', $tz);
        return $now <= $deadline;
    } catch (Exception $e) {
        return false;
    }
}
// ===== 3. API ENDPOINTS (JSON RESPONSES ONLY) =====
// Handle AJAX request untuk get available sessions (FIXED VERSION)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_available_sessions') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $lapangan_id = $_GET['lapangan_id'] ?? 0;
    $selected_date = $_GET['selected_date'] ?? '';
    $current_date = $_GET['current_date'] ?? '';
    $booking_id = $_GET['booking_id'] ?? 0;

    if (!$lapangan_id) {
        echo json_encode(['status' => 'error', 'message' => 'Lapangan tidak valid']);
        exit;
    }

    $target_date = $selected_date ?: $current_date;
    if (!$target_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Tanggal tidak valid']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 1. Pastikan jadwal_harian ada
        $stmt = $conn->prepare("SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?");
        $stmt->bind_param("is", $lapangan_id, $target_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO jadwal_harian (id_lapangan, tanggal, status_hari, created_at) VALUES (?, ?, 'tersedia', NOW())");
            $stmt->bind_param("is", $lapangan_id, $target_date);
            $stmt->execute();
            $id_jadwal_harian = $conn->insert_id;
               } else {
            $row = $result->fetch_assoc();
            $id_jadwal_harian = $row['id_jadwal_harian'];
               }
        // 2. Pastikan semua jam di jadwal_waktu punya record di jadwal_detail
        $stmt = $conn->prepare("
            INSERT IGNORE INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, created_at)
            SELECT ?, jw.id_jadwal_waktu, 'available', NOW()
            FROM jadwal_waktu jw 
            WHERE jw.id_lapangan = ?
        ");
        $stmt->bind_param("ii", $id_jadwal_harian, $lapangan_id);
        $stmt->execute();

        $conn->commit();

        // 3. Query available sessions — YANG INI YANG BENAR untuk ubah jadwal
        $query = "
            SELECT 
                jw.id_jadwal_waktu,
                jw.jam_mulai,
                jw.jam_selesai,
                jw.harga_per_jam AS harga,
                jh.tanggal
            FROM jadwal_waktu jw
            CROSS JOIN jadwal_harian jh ON jh.id_lapangan = jw.id_lapangan
            LEFT JOIN jadwal_detail jd ON jd.id_jadwal_waktu = jw.id_jadwal_waktu 
                                      AND jd.id_jadwal_harian = jh.id_jadwal_harian
            WHERE jh.id_jadwal_harian = ?
              AND jw.id_lapangan = ?
              AND (
                jd.status = 'available' 
                OR jd.status IS NULL 
                OR (jd.id_booking = ? AND jd.status = 'dibooking')
              )
            ORDER BY jw.jam_mulai
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $id_jadwal_harian, $lapangan_id, $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $available_sessions = [];
        while ($row = $result->fetch_assoc()) {
            $available_sessions[] = [
                'id_jadwal_waktu' => $row['id_jadwal_waktu'],
                'jam_mulai'       => substr($row['jam_mulai'], 0, 5),
                'jam_selesai'     => substr($row['jam_selesai'], 0, 5),
                'harga'           => $row['harga'],
                'tanggal'         => $row['tanggal']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'available_sessions' => $available_sessions
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle AJAX request untuk get member sessions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_member_sessions') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $member_id = $_GET['member_id'] ?? 0;

    if (!$member_id) {
        echo json_encode(['status' => 'error', 'message' => 'Member ID tidak valid']);
        exit;
    }

    try {
        $query = "
            SELECT mj.id_member_jadwal, mj.tanggal_booking, mj.jam_mulai, mj.jam_selesai,
                   l.nama_lapangan
            FROM member_jadwal mj
            JOIN member m ON mj.id_member = m.id_member
            JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
            WHERE mj.id_member = ?
              AND CONCAT(mj.tanggal_booking, ' ', mj.jam_mulai) > NOW()
            ORDER BY mj.tanggal_booking, mj.jam_mulai
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $member_sessions = [];
        while ($row = $result->fetch_assoc()) {
            $member_sessions[] = [
                'id_member_jadwal' => $row['id_member_jadwal'],
                'tanggal_booking' => $row['tanggal_booking'],
                'jam_mulai' => $row['jam_mulai'],
                'jam_selesai' => $row['jam_selesai'],
                'nama_lapangan' => $row['nama_lapangan']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'member_sessions' => $member_sessions
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle AJAX request untuk ubah jadwal reguler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_jadwal_reguler') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $booking_id = $_POST['booking_id'] ?? 0;
    $new_date = $_POST['new_date'] ?? '';
    $selected_session = $_POST['selected_session'] ?? '';

    if (!$booking_id || !$new_date || !$selected_session) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Validasi format tanggal
    if (!DateTime::createFromFormat('Y-m-d', $new_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Ambil data booking lama
        $stmt_old = $conn->prepare("
            SELECT b.tanggal, b.id_lapangan, 
                   GROUP_CONCAT(CONCAT(jw.jam_mulai, '-', jw.jam_selesai) SEPARATOR ', ') as jam_booking,
                   MIN(jw.jam_mulai) as jam_pertama
            FROM booking b
            JOIN detail_booking db ON b.id_booking = db.id_booking
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE b.id_booking = ? AND b.id_user = ?
            GROUP BY b.id_booking
        ");
        $stmt_old->bind_param("ii", $booking_id, $user_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        
        if ($result_old->num_rows === 0) {
            throw new Exception('Booking tidak ditemukan');
        }
        
        $old_data = $result_old->fetch_assoc();
        $tanggal_lama = $old_data['tanggal'];
        $jam_lama = $old_data['jam_booking'];
        $id_lapangan = $old_data['id_lapangan'];
        $jam_pertama = $old_data['jam_pertama'];

        // 2. Validasi H-5 jam
        if (!validateH5Jam($tanggal_lama, $jam_pertama)) {
            throw new Exception('Waktu ubah jadwal sudah habis (H-5 jam dari booking)');
        }

        // 3. Validasi sudah pernah ubah
        $stmt_check = $conn->prepare("
            SELECT COUNT(*) as total_ubah 
            FROM history_ubah_jadwal 
            WHERE id_booking = ? AND tipe = 'reguler'
        ");
        $stmt_check->bind_param("i", $booking_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $check_data = $result_check->fetch_assoc();
        
        if ($check_data['total_ubah'] > 0) {
            throw new Exception('Anda sudah menggunakan kesempatan ubah jadwal');
        }

        // 4. Ambil data jadwal waktu baru
        $stmt_new = $conn->prepare("
            SELECT jam_mulai, jam_selesai 
            FROM jadwal_waktu 
            WHERE id_jadwal_waktu = ?
        ");
        $stmt_new->bind_param("i", $selected_session);
        $stmt_new->execute();
        $result_new = $stmt_new->get_result();
        
        if ($result_new->num_rows === 0) {
            throw new Exception('Jadwal tidak valid');
        }
        
        $new_time = $result_new->fetch_assoc();
        $jam_baru = $new_time['jam_mulai'] . '-' . $new_time['jam_selesai'];

        // 5. Update booking tanggal
        $stmt_update_booking = $conn->prepare("
            UPDATE booking 
            SET tanggal = ?, updated_at = NOW() 
            WHERE id_booking = ? AND id_user = ?
        ");
        $stmt_update_booking->bind_param("sii", $new_date, $booking_id, $user_id);
        $stmt_update_booking->execute();

        // 6. Hapus detail booking lama
        $stmt_delete_detail = $conn->prepare("
            DELETE FROM detail_booking 
            WHERE id_booking = ?
        ");
        $stmt_delete_detail->bind_param("i", $booking_id);
        $stmt_delete_detail->execute();

        // 7. Insert detail booking baru
        $stmt_insert_detail = $conn->prepare("
            INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        // Ambil harga dari jadwal_waktu
        $stmt_harga = $conn->prepare("
            SELECT harga_per_jam FROM jadwal_waktu WHERE id_jadwal_waktu = ?
        ");
        $stmt_harga->bind_param("i", $selected_session);
        $stmt_harga->execute();
        $result_harga = $stmt_harga->get_result();
        $harga_data = $result_harga->fetch_assoc();
        $harga = $harga_data['harga_per_jam'];
        
        $stmt_insert_detail->bind_param("iid", $booking_id, $selected_session, $harga);
        $stmt_insert_detail->execute();

        // 8. Update jadwal_detail (lepas booking lama)
        $stmt_release_old = $conn->prepare("
            UPDATE jadwal_detail jd
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            SET jd.id_booking = NULL, jd.status = 'available'
            WHERE jh.tanggal = ? 
              AND jw.id_jadwal_waktu IN (
                  SELECT id_jadwal_waktu FROM detail_booking WHERE id_booking = ?
              )
        ");
        $stmt_release_old->bind_param("si", $tanggal_lama, $booking_id);
        $stmt_release_old->execute();

        // 9. Update jadwal_detail (isi booking baru)
        $stmt_assign_new = $conn->prepare("
            UPDATE jadwal_detail jd
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            SET jd.id_booking = ?, jd.status = 'dibooking'
            WHERE jh.tanggal = ? 
              AND jw.id_jadwal_waktu = ?
        ");
        $stmt_assign_new->bind_param("isi", $booking_id, $new_date, $selected_session);
        $stmt_assign_new->execute();

        // 10. Insert history ubah jadwal
        $stmt_history = $conn->prepare("
            INSERT INTO history_ubah_jadwal 
            (id_booking, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user) 
            VALUES (?, 'reguler', ?, ?, ?, ?, ?, ?)
        ");
        $stmt_history->bind_param("issssii", $booking_id, $tanggal_lama, $jam_lama, $new_date, $jam_baru, $id_lapangan, $user_id);
        $stmt_history->execute();

        $conn->commit();

        $_SESSION['ubah_jadwal_success'] = "Jadwal booking berhasil diubah";
        echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diubah']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX request untuk ubah jadwal member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_jadwal_member') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $member_id = $_POST['member_id'] ?? 0;
    $lapangan_id = $_POST['lapangan_id'] ?? 0;
    $new_date = $_POST['new_date'] ?? '';
    $member_session_ids = $_POST['member_session_ids'] ?? [];
    $selected_session = $_POST['selected_session'] ?? '';

    if (!$member_id || !$new_date || !$selected_session || empty($member_session_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Jika member_session_ids adalah string (dari form data), convert ke array
    if (is_string($member_session_ids)) {
        $member_session_ids = explode(',', $member_session_ids);
    }

    $conn->begin_transaction();

    try {
        // 1. Validasi membership
        $stmt_member = $conn->prepare("
            SELECT status, durasi_bulan 
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

        // 2. Validasi kuota ubah jadwal
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

        // 3. Ambil data sesi lama
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
            
            // Validasi H-5 jam per session
            if (!validateH5Jam($row['tanggal_booking'], $row['jam_mulai'])) {
                throw new Exception("Sesi tanggal {$row['tanggal_booking']} jam {$row['jam_mulai']} sudah melewati batas waktu ubah (H-5 jam)");
            }
        }

        // 4. Ambil data jadwal waktu baru
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

        // 5. Update member_jadwal untuk setiap sesi yang dipilih
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

        // 6. Update jadwal_detail untuk sesi lama (lepas)
        foreach ($old_sessions_data as $old_session) {
            $stmt_release_old = $conn->prepare("
                UPDATE jadwal_detail jd
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
                SET jd.id_member_jadwal = NULL, jd.status = 'available'
                WHERE jh.tanggal = ? 
                  AND jw.jam_mulai = ?
                  AND jd.id_member_jadwal = ?
            ");
            $stmt_release_old->bind_param("ssi", $old_session['tanggal_booking'], $old_session['jam_mulai'], $old_session['id_member_jadwal']);
            $stmt_release_old->execute();
        }

        // 7. Update jadwal_detail untuk sesi baru (isi)
        foreach ($member_session_ids as $session_id) {
            $stmt_assign_new = $conn->prepare("
                UPDATE jadwal_detail jd
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
                SET jd.id_member_jadwal = ?, jd.status = 'dibooking'
                WHERE jh.tanggal = ? 
                  AND jw.jam_mulai = ?
                  AND jw.id_lapangan = ?
            ");
            $stmt_assign_new->bind_param("issi", $session_id, $new_date, $jam_baru_mulai, $lapangan_id);
            $stmt_assign_new->execute();
        }

        // 8. Insert history ubah jadwal (1 record untuk semua sesi yang diubah)
        $jam_lama_text = count($old_sessions_data) . ' sesi';
        $jam_baru_text = $jam_baru_mulai . '-' . $jam_baru_selesai;
        
        $stmt_history = $conn->prepare("
            INSERT INTO history_ubah_jadwal 
            (id_member, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user) 
            VALUES (?, 'member', 'multiple', ?, ?, ?, ?, ?)
        ");
        $tanggal_lama_text = count($old_sessions_data) . ' tanggal berbeda';
        $stmt_history->bind_param("isssii", $member_id, $tanggal_lama_text, $new_date, $jam_baru_text, $lapangan_id, $user_id);
        $stmt_history->execute();

        $conn->commit();

        $_SESSION['ubah_jadwal_success'] = "Jadwal member berhasil diubah untuk " . count($member_session_ids) . " sesi";
        echo json_encode(['status' => 'success', 'message' => 'Jadwal member berhasil diubah']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== 4. BACKEND DATA PREPARATION =====

if ($is_logged_in) {
    try {
        // Regular bookings dengan cek ubah jadwal
        $stmtBookings = $conn->prepare("
            SELECT 
                b.id_booking, b.tanggal, b.tipe_booking, b.status, b.total_amount, 
                b.alasan_penolakan, l.nama_lapangan, l.harga_per_jam, l.id_lapangan,
                GROUP_CONCAT(CONCAT(jw.jam_mulai, '-', jw.jam_selesai) SEPARATOR ', ') as jam_booking,
                MIN(jw.jam_mulai) as jam_pertama,
                COUNT(h.id_ubah_jadwal) as sudah_ubah
            FROM booking b
            JOIN lapangan l ON b.id_lapangan = l.id_lapangan
            LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
            LEFT JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            LEFT JOIN history_ubah_jadwal h ON b.id_booking = h.id_booking AND h.tipe = 'reguler'
            WHERE b.id_user = ?
            GROUP BY b.id_booking
            ORDER BY b.tanggal DESC, b.id_booking DESC
        ");
        $stmtBookings->bind_param("i", $user_id);
        $stmtBookings->execute();
        $resultBookings = $stmtBookings->get_result();
        $bookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
        $stmtBookings->close();

        // Generate unique IDs untuk booking reguler
        foreach ($bookings as &$booking) {
            if ($booking['jam_pertama']) {
                $booking['unique_id'] = generateRegulerId($booking['tanggal'], $booking['jam_pertama']);
            }
        }

        // Member bookings dengan cek ubah jadwal
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
                ) SEPARATOR '; ') as jadwal
            FROM member m
            JOIN lapangan l ON m.id_lapangan = l.id_lapangan
            LEFT JOIN member_jadwal mj ON m.id_member = mj.id_member
            LEFT JOIN history_ubah_jadwal h ON m.id_member = h.id_member AND h.tipe = 'member'
            WHERE m.id_user = ?
            GROUP BY m.id_member
            ORDER BY m.tanggal_mulai DESC
        ");
        $stmtMember->bind_param("i", $user_id);
        $stmtMember->execute();
        $resultMember = $stmtMember->get_result();
        $memberBookings = $resultMember->fetch_all(MYSQLI_ASSOC);
        $stmtMember->close();

        // Generate unique IDs untuk member
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

// ===== 5. FRONTEND TEMPLATE (HTML OUTPUT) =====
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking</title>
    <link rel="stylesheet" href="riwayat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Riwayat Booking</h1>
            <p>Lihat status dan detail pemesanan Anda</p>
        </header>

        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i> 
                <div>
                    <strong>Berhasil!</strong> <?= htmlspecialchars($_SESSION['booking_success']); ?>
                </div>
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['ubah_jadwal_success'])): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i> 
                <div>
                    <strong>Berhasil!</strong> <?= htmlspecialchars($_SESSION['ubah_jadwal_success']); ?>
                </div>
            </div>
            <?php unset($_SESSION['ubah_jadwal_success']); ?>
        <?php endif; ?>
        
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

            <div id="booking-tab" class="tab-content active">
                <?php if (isset($error)): ?>
                    <div class="error-state">
                        <h3><i class="fa-solid fa-triangle-exclamation"></i> Terjadi Kesalahan</h3>
                        <p><?php echo $error; ?></p>
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
                        $statusClass = '';
                        $status = $booking['status'];
                        if (stripos($status, 'menunggu') !== false) $statusClass = 'menunggu';
                        elseif (stripos($status, 'disetujui') !== false) $statusClass = 'disetujui';
                        elseif (stripos($status, 'ditolak') !== false) $statusClass = 'ditolak';
                        

                        // LOGIC UBAH JADWAL: Bisa ubah jika status MENUNGGU atau DISETUJUI
                        $canEdit = false;
                        $deadlineTimestamp = '';

                        if (in_array($status, ['menunggu', 'disetujui'])) {
                            $sudahPernahUbah = ($booking['sudah_ubah'] ?? 0) > 0;
                            $jam_pertama = $booking['jam_pertama'] ?? '';

                            if ($jam_pertama && strlen(trim($jam_pertama)) >= 5) {
                                $jam_clean = substr(trim($jam_pertama), 0, 5); // 21:00 atau 21:00:00 → 21:00

                                if (validateH5Jam($booking['tanggal'], $jam_clean)) {
                                    if (!$sudahPernahUbah) {
                                        $canEdit = true;

                                        try {
                                            $tz = new DateTimeZone('Asia/Jakarta');
                                            $bookingTime = new DateTime("{$booking['tanggal']} {$jam_clean}:00", $tz);
                                            $deadline = clone $bookingTime;
                                            $deadline->sub(new DateInterval('PT5H'));
                                            $deadlineTimestamp = $deadline->format('c');
                                        } catch (Exception $e) {
                                            $canEdit = false;
                                        }
                                    }
                                }
                            }
                        }
                        ?>
        
                        <div class="card" data-booking="<?= $booking['id_booking'] ?>">
                            <div class="card-header">
                                <div>
                                    <h3>
                                        <?php echo htmlspecialchars($booking['nama_lapangan']); ?>
                                        <span class="user-type reguler">REGULER</span>
                                    </h3>
                                    <p class="booking-id">ID: <?= $booking['unique_id'] ?? '#' . $booking['id_booking'] ?></p>
                                </div>
                                <span class="status <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p><strong>Tanggal:</strong> 
                                    <?php 
                                    $date = new DateTime($booking['tanggal']);
                                    echo $date->format('l, j F Y');
                                    ?>
                                </p>
                                <p><strong>Jam:</strong> <?php echo htmlspecialchars($booking['jam_booking'] ?? '-'); ?></p>
                                <p><strong>Total:</strong> Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="card-footer">
                                <?php if ($status === 'menunggu' || $status === 'disetujui'): ?>
                            <div class="countdown-timer <?= !$canEdit ? 'expired' : '' ?>"
     <?= $canEdit ? "data-booking-deadline=\"$deadlineTimestamp\"" : '' ?>>
    <?php if ($canEdit): ?>
        <span class="time-left">Tersisa <span id="timer-<?= $booking['id_booking'] ?>">Menghitung...</span></span>
    <?php else: ?>
        <span class="expired-text">Waktu ubah jadwal habis</span>
    <?php endif; ?>
</div>
                                <?php else: ?>
                                    <div class="countdown expired">Tidak dapat diubah</div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <button class="btn-detail" onclick="showDetail(
                                        '<?php echo $booking['id_booking']; ?>',
                                        '<?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES); ?>',
                                        '<?php echo $booking['tanggal']; ?>',
                                        '<?php echo htmlspecialchars($booking['jam_booking'] ?? '', ENT_QUOTES); ?>',
                                        '<?php echo $booking['total_amount']; ?>',
                                        '<?php echo htmlspecialchars($booking['status'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($booking['alasan_penolakan'] ?? '', ENT_QUOTES); ?>',
                                        '<?= $booking['unique_id'] ?? '#' . $booking['id_booking'] ?>'
                                    )">Lihat Detail</button>
                                    
                                    <?php if ($canEdit): ?>
                                        <button class="btn-ubah" 
                                            onclick="showUbahJadwalReguler(
                                                '<?= $booking['id_booking'] ?>',
                                                '<?= $booking['tanggal'] ?>',
                                                '<?= htmlspecialchars($booking['jam_booking'] ?? '') ?>',
                                                '<?= htmlspecialchars($booking['nama_lapangan']) ?>',
                                                '<?= $booking['id_lapangan'] ?>'
                                            )">
                                            Ubah Jadwal
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-ubah disabled" 
                                            onclick="showDisabledReason(
                                                '<?= $booking['sudah_ubah'] > 0 ? 'already_used' : 'time_expired' ?>'
                                            )">
                                            Ubah Jadwal
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

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
                        $statusClass = '';
                        $status = $member['status'];
                        if (stripos($status, 'pending') !== false) $statusClass = 'menunggu';
                        elseif (stripos($status, 'aktif') !== false) $statusClass = 'disetujui';
                        elseif (stripos($status, 'nonaktif') !== false) $statusClass = 'ditolak';

                        // LOGIC MEMBER: Bisa ubah jika status AKTIF dan belum melebihi kuota
                        $ubahCount = $member['sudah_ubah'] ?? 0; 
                        $maxUbah = 3;
                        $canUbah = ($ubahCount < $maxUbah) && ($status === 'aktif');
                        
                        $countdownText = $canUbah ? 'Dapat diubah' : 'Tidak dapat diubah';
                        ?>
                        
                        <div class="card" data-member="<?= $member['id_member'] ?>">
                            <div class="card-header">
                                <div>
                                    <h3>
                                        <?php echo htmlspecialchars($member['nama_lapangan']); ?>
                                        <span class="user-type member">MEMBER</span>
                                    </h3>
                                    <p class="booking-id">ID: <?= $member['unique_id'] ?? '#' . $member['id_member'] ?></p>
                                </div>
                                <span class="status <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p><strong>Durasi:</strong> <?php echo htmlspecialchars($member['durasi_bulan']); ?> bulan</p>
                                <p><strong>Periode:</strong> 
                                    <?php 
                                    $start = new DateTime($member['tanggal_mulai']);
                                    $end = new DateTime($member['tanggal_berakhir']);
                                    echo $start->format('d M Y') . ' - ' . $end->format('d M Y');
                                    ?>
                                </p>
                                <p><strong>Total Bayar:</strong> Rp <?php echo number_format($member['total_bayar'], 0, ',', '.'); ?></p>
                                <p><strong>Sisa Ubah Jadwal:</strong> <?= $maxUbah - $ubahCount ?> dari <?= $maxUbah ?> kali</p>
                                
                                <?php if ($member['jadwal']): ?>
                                    <p><strong>Jadwal Terjadwal:</strong> <?= $member['total_sessions'] ?> sesi</p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="countdown <?php echo !$canUbah ? 'expired' : ''; ?>">
                                    <?= $countdownText ?>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn-detail" onclick="showMemberDetail(
                                        '<?php echo $member['id_member']; ?>',
                                        '<?php echo htmlspecialchars($member['nama_lapangan'], ENT_QUOTES); ?>',
                                        '<?php echo $member['durasi_bulan']; ?>',
                                        '<?php echo $member['tanggal_mulai']; ?>',
                                        '<?php echo $member['tanggal_berakhir']; ?>',
                                        '<?php echo $member['total_bayar']; ?>',
                                        '<?php echo htmlspecialchars($member['status'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($member['jadwal'] ?? '', ENT_QUOTES); ?>',
                                        '<?php echo $ubahCount; ?>',
                                        '<?php echo $maxUbah; ?>',
                                        '<?= $member['unique_id'] ?? '#' . $member['id_member'] ?>'
                                    )">Lihat Detail</button>
                                    
                                    <?php if ($canUbah): ?>
                                        <button class="btn-ubah" 
                                            onclick="showUbahJadwalMember(
                                                '<?= $member['id_member'] ?>',
                                                '<?= htmlspecialchars($member['nama_lapangan']) ?>',
                                                '<?= $maxUbah - $ubahCount ?>',
                                                '<?= $member['id_lapangan'] ?>'
                                            )">
                                            Ubah Jadwal
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-ubah disabled" 
                                            onclick="showDisabledReason(
                                                '<?= $status !== 'aktif' ? 'member_not_active' : 'quota_exceeded' ?>'
                                            )">
                                            Ubah Jadwal
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- MODALS -->
            <div class="modal" id="detailModal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Detail Booking</h2>
                    <div id="detailContent"></div>
                    <div id="qrcode" class="qrcode"></div>
                </div>
            </div>
            
            <div class="modal" id="ubahJadwalModal">
                <div class="modal-content">
                    <span class="close" onclick="closeUbahJadwalModal()">&times;</span>
                    <h2>Ubah Jadwal Booking Reguler</h2>
                    <div id="ubahJadwalContent">
                        <!-- Content akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>
            
            <div class="modal" id="ubahJadwalMemberModal">
                <div class="modal-content">
                    <span class="close" onclick="closeUbahJadwalMemberModal()">&times;</span>
                    <h2>Ubah Jadwal Member</h2>
                    <div id="ubahJadwalMemberContent">
                        <!-- Content akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script src="riwayat.js"></script>

        <?php endif; ?>
    </div>
</body>
</html>

<?php 
require '../include_user/footer.php'; 
?>