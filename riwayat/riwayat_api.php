<?php
// Suppress semua output sebelum JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start(); // Buffer output

session_start();
require '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}
$user_id = $_SESSION['id_user'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function untuk format tanggal Indonesia
if (!function_exists('tglIndo')) {
    function tglIndo($tgl) {
        if (empty($tgl) || $tgl == '0000-00-00' || $tgl == '0000-00-00 00:00:00') {
            return '-';
        }
        $bulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        $split = explode('-', $tgl);
        return $split[2] . ' ' . $bulan[(int)$split[1] - 1] . ' ' . $split[0];
    }
}

switch ($action) {

    // 1. AMBIL DETAIL BOOKING
    case 'get_booking_detail':
        $id_booking = $_POST['id_booking'] ?? 0;

        $query = "
            SELECT 
                b.id_booking, b.tanggal, b.status AS status_booking, b.payment_status,
                b.total_amount, b.dp_amount, b.remaining_amount,
                l.nama_lapangan, u.nama AS nama_user,
                MIN(jw.jam_mulai) AS jam_mulai, 
                MAX(jw.jam_selesai) AS jam_selesai,
                
                /* Ambil status refund dari salah satu sesi */
                (SELECT status FROM pembatalan_booking pb 
                 JOIN detail_booking db2 ON pb.id_detail_booking = db2.id_detail_booking 
                 WHERE db2.id_booking = b.id_booking 
                 ORDER BY pb.id DESC LIMIT 1) AS status_refund,
                 
                /* Ambil bukti refund */
                (SELECT bukti_refund FROM pembatalan_booking pb 
                 JOIN detail_booking db2 ON pb.id_detail_booking = db2.id_detail_booking 
                 WHERE db2.id_booking = b.id_booking 
                 ORDER BY pb.id DESC LIMIT 1) AS bukti_refund

            FROM booking b
            JOIN lapangan l ON b.id_lapangan = l.id_lapangan
            JOIN users u ON b.id_user = u.id_user
            JOIN detail_booking db ON b.id_booking = db.id_booking
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE b.id_booking = ? AND b.id_user = ?
            GROUP BY b.id_booking
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id_booking, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $dateObj = new DateTime($row['tanggal']);
            $jamMain = substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5);
            
            echo json_encode(['status' => 'success', 'data' => [
                'id_booking' => $row['id_booking'],
                'kode_booking' => "INV-" . date('ymd', strtotime($row['tanggal'])) . "-" . $row['id_booking'],
                'pemesan' => $memData['nama_user'],
                'nama_lapangan' => $row['nama_lapangan'],
                'tanggal' => $dateObj->format('d M Y'),
                'jam' => $jamMain,
                'user' => $row['nama_user'],
                'total_harga' => "Rp " . number_format($row['total_amount'], 0, ',', '.'),
                'dp' => "Rp " . number_format($row['dp_amount'], 0, ',', '.'),
                'sisa' => "Rp " . number_format($row['remaining_amount'], 0, ',', '.'),
                'sisa_raw' => $row['remaining_amount'], // Untuk cek logic di JS
                'status_booking' => $row['status_booking'],
                'payment_status' => $row['payment_status'],
                'status_refund' => $row['status_refund'] ?? null,
                'bukti_refund' => $row['bukti_refund'] ?? null
            ]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
        break;

// 2. AJUKAN PEMBATALAN (Insert Lengkap)
    case 'ajukan_pembatalan':
        $id_sesi = $_POST['id_sesi'] ?? 0;
        $bank = $_POST['bank_ewallet'] ?? '';
        $rek = $_POST['no_rekening'] ?? '';
        $nama = $_POST['nama_penerima'] ?? '';

        if (empty($id_sesi) || empty($bank) || empty($rek) || empty($nama)) {
            echo json_encode(['status' => 'error', 'message' => 'Data form tidak lengkap.']); exit;
        }

        // 2a. Ambil Data Detail Termasuk Nama Lapangan
        $qCek = "
            SELECT db.id_booking, db.id_jadwal_waktu, db.harga, 
                   b.payment_status, b.total_amount, b.dp_amount, b.tanggal, 
                   jw.jam_mulai, jw.jam_selesai, l.nama_lapangan
            FROM detail_booking db 
            JOIN booking b ON db.id_booking = b.id_booking 
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            JOIN lapangan l ON b.id_lapangan = l.id_lapangan
            WHERE db.id_detail_booking = ? AND b.id_user = ?
        ";
        $stmt = $conn->prepare($qCek);
        $stmt->bind_param("ii", $id_sesi, $user_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Data booking tidak valid']); exit;
        }

        try {
            // Validasi Waktu
            $waktuMain = new DateTime($data['tanggal'] . ' ' . $data['jam_mulai']);
            $limit = clone $waktuMain;
            $limit->sub(new DateInterval('PT5H'));
            if (new DateTime() > $limit) throw new Exception("Batas waktu pengajuan pembatalan telah habis.");

            // Cek Duplikasi
            $qDup = "SELECT id FROM pembatalan_booking WHERE id_detail_booking = ?";
            $stmtDup = $conn->prepare($qDup);
            $stmtDup->bind_param("i", $id_sesi);
            $stmtDup->execute();
            if ($stmtDup->get_result()->num_rows > 0) throw new Exception("Pengajuan untuk sesi ini sudah ada.");

            // =============================================
            // [FIX] HITUNG REFUND DI BACKEND
            // =============================================
            $refundAmount = 0;
            $hargaSesi = (float)$data['harga'];
            $totalTagihan = (float)$data['total_amount'];
            $totalDP = (float)$data['dp_amount'];
            $statusBayar = strtolower($data['payment_status']);

            if ($statusBayar == 'lunas' || $statusBayar == 'selesai') {
                $refundAmount = $hargaSesi;
            } else {
                // Jika DP
                if ($totalTagihan > 0) {
                    $ratio = $totalDP / $totalTagihan;
                    $refundAmount = $hargaSesi * $ratio;
                } else {
                    $refundAmount = $totalDP;
                }
            }
            $refundAmount = floor($refundAmount);

            // Siapkan Data Snapshot
            $namaLapangan = $data['nama_lapangan'];
            $tanggalMain = $data['tanggal'];
            $jamMain = substr($data['jam_mulai'], 0, 5) . ' - ' . substr($data['jam_selesai'], 0, 5);
            $keterangan = "Bank: $bank";

            $conn->begin_transaction();

            // 1. Insert ke pembatalan_booking (DENGAN DATA LENGKAP)
            // Pastikan kolom nama_lapangan, tanggal_main, jam_main sudah dibuat di DB
            $qIns = "INSERT INTO pembatalan_booking 
                    (id_detail_booking, id_user, nama_lapangan, tanggal_main, jam_main, nama_pengaju, nomor_rekening, atas_nama, jumlah_refund, status, keterangan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            
            $stmtIns = $conn->prepare($qIns);
            $stmtIns->bind_param("iissssssds", 
                $id_sesi, 
                $user_id, 
                $namaLapangan, 
                $tanggalMain, 
                $jamMain, 
                $nama, 
                $rek, 
                $nama, 
                $refundAmount, 
                $keterangan
            );
            
            if (!$stmtIns->execute()) {
                throw new Exception("Gagal menyimpan data pengajuan: " . $stmtIns->error);
            }

            // 2. Update Status Booking
            $qUpdBooking = "UPDATE booking SET status = 'dibatalkan', payment_status = 'dibatalkan' WHERE id_booking = ?";
            $stmtUpdB = $conn->prepare($qUpdBooking);
            $stmtUpdB->bind_param("i", $data['id_booking']);
            $stmtUpdB->execute();

            // 3. Lepas Slot Jadwal
            $qReleaseSlot = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL WHERE id_booking = ?";
            $stmtRelease = $conn->prepare($qReleaseSlot);
            $stmtRelease->bind_param("i", $data['id_booking']);
            $stmtRelease->execute();

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Pengajuan berhasil dikirim. Dana Refund: Rp ' . number_format($refundAmount, 0, ',', '.')]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // 3. GET AVAILABLE SESSIONS
    case 'get_available_sessions':
        try {
            $lapangan_id = $_GET['lapangan_id'] ?? 0;
            $selected_date = $_GET['selected_date'] ?? date('Y-m-d');
            
            if (!$lapangan_id || !$selected_date) {
                throw new Exception('Lapangan atau tanggal tidak valid.');
            }

            $qHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtH = $conn->prepare($qHarian);
            if (!$stmtH) throw new Exception('Prepare jadwal_harian error: ' . $conn->error);
            
            $stmtH->bind_param("is", $lapangan_id, $selected_date);
            if (!$stmtH->execute()) throw new Exception('Execute jadwal_harian error: ' . $stmtH->error);
            
            $resH = $stmtH->get_result();

            if ($resH->num_rows == 0) {
                ob_end_clean();
                echo json_encode(['status' => 'success', 'data' => []]);
                exit;
            }
            $id_harian = $resH->fetch_assoc()['id_jadwal_harian'];

            $qSlots = "SELECT jd.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai 
                      FROM jadwal_detail jd 
                      JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu 
                      WHERE jd.id_jadwal_harian = ? AND jd.status = 'tersedia' 
                      ORDER BY jw.jam_mulai ASC";
            $stmtS = $conn->prepare($qSlots);
            if (!$stmtS) throw new Exception('Prepare jadwal_detail error: ' . $conn->error);
            
            $stmtS->bind_param("i", $id_harian);
            if (!$stmtS->execute()) throw new Exception('Execute jadwal_detail error: ' . $stmtS->error);
            
            $resS = $stmtS->get_result();

            $slots = [];
            while ($row = $resS->fetch_assoc()) {
                $slots[] = [
                    'id_jadwal_waktu' => (int)$row['id_jadwal_waktu'],
                    'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                    'jam_selesai' => substr($row['jam_selesai'], 0, 5),
                    'jam_display' => substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5)
                ];
            }
            ob_end_clean();
            echo json_encode(['status' => 'success', 'data' => $slots]);
        } catch (Exception $e) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
        break;

    // 4. UBAH JADWAL
    case 'ubah_jadwal_sesi':
        $id_sesi = $_POST['id_sesi'] ?? 0;
        $id_lapangan = $_POST['id_lapangan'] ?? 0;
        $new_date = $_POST['new_date'] ?? '';
        $new_waktu_id = $_POST['new_jadwal_waktu'] ?? 0;

        if (empty($id_sesi) || empty($new_date) || empty($new_waktu_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']); exit;
        }

        $conn->begin_transaction();
        try {
            // Ambil Data Lama
            $qOld = "SELECT db.id_booking, db.id_jadwal_waktu, b.tanggal, jw.jam_mulai, (SELECT COUNT(*) FROM history_ubah_jadwal h WHERE h.id_detail_booking = db.id_detail_booking) as count_ubah FROM detail_booking db JOIN booking b ON db.id_booking = b.id_booking JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu WHERE db.id_detail_booking = ? AND b.id_user = ? FOR UPDATE";
            $stmtOld = $conn->prepare($qOld);
            $stmtOld->bind_param("ii", $id_sesi, $user_id);
            $stmtOld->execute();
            $oldData = $stmtOld->get_result()->fetch_assoc();

            if (!$oldData) throw new Exception("Data booking tidak valid.");
            if ($oldData['count_ubah'] > 0) throw new Exception("Anda sudah pernah mengubah jadwal ini.");

            // Cek Jadwal Baru
            $qHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtH = $conn->prepare($qHarian);
            $stmtH->bind_param("is", $id_lapangan, $new_date);
            $stmtH->execute();
            $harianRes = $stmtH->get_result();
            if ($harianRes->num_rows == 0) throw new Exception("Jadwal belum dibuka.");
            $id_harian_baru = $harianRes->fetch_assoc()['id_jadwal_harian'];

            // Cek Slot Kosong
            $qCheck = "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ? AND status = 'tersedia' FOR UPDATE";
            $stmtCheck = $conn->prepare($qCheck);
            $stmtCheck->bind_param("ii", $id_harian_baru, $new_waktu_id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows == 0) throw new Exception("Slot sudah terisi.");

            // Lepas Slot Lama
            $qHarianLama = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtHL = $conn->prepare($qHarianLama);
            $stmtHL->bind_param("is", $id_lapangan, $oldData['tanggal']);
            $stmtHL->execute();
            $id_harian_lama = $stmtHL->get_result()->fetch_assoc()['id_jadwal_harian'];

            $conn->query("UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL WHERE id_jadwal_harian = $id_harian_lama AND id_jadwal_waktu = {$oldData['id_jadwal_waktu']}");
            
            // Ambil Slot Baru
            $stmtUpdNew = $conn->prepare("UPDATE jadwal_detail SET status = 'dibooking', id_booking = ? WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?");
            $stmtUpdNew->bind_param("iii", $oldData['id_booking'], $id_harian_baru, $new_waktu_id);
            $stmtUpdNew->execute();

            // Update Detail Booking
            $conn->query("UPDATE detail_booking SET id_jadwal_waktu = $new_waktu_id WHERE id_detail_booking = $id_sesi");

            // Update Tanggal Utama (Jika berubah)
            if ($oldData['tanggal'] != $new_date) {
                $stmtUpdB = $conn->prepare("UPDATE booking SET tanggal = ? WHERE id_booking = ?");
                $stmtUpdB->bind_param("si", $new_date, $oldData['id_booking']);
                $stmtUpdB->execute();
            }

            // Log History
            $qJamBaru = "SELECT jam_mulai FROM jadwal_waktu WHERE id_jadwal_waktu = $new_waktu_id";
            $jamBaruStr = $conn->query($qJamBaru)->fetch_assoc()['jam_mulai'];
            
            $stmtHist = $conn->prepare("INSERT INTO history_ubah_jadwal (id_detail_booking, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user) VALUES (?, 'reguler', ?, ?, ?, ?, ?, ?)");
            $stmtHist->bind_param("issssii", $id_sesi, $oldData['tanggal'], $oldData['jam_mulai'], $new_date, $jamBaruStr, $id_lapangan, $user_id);
            $stmtHist->execute();

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diubah.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

// 5. GET MEMBER UPCOMING SESSIONS (Untuk Dropdown Ubah Jadwal)
    case 'get_member_upcoming_sessions':
        try {
            $id_member = $_POST['id_member'] ?? 0;
            if (!$id_member) throw new Exception('ID member tidak valid.');
            
            // Ambil jadwal yang tanggalnya >= hari ini
            $q = "SELECT id_member_jadwal, tanggal_booking, jam_mulai, jam_selesai 
                  FROM member_jadwal 
                  WHERE id_member = ? AND tanggal_booking >= CURDATE() AND status = 'aktif'
                  ORDER BY tanggal_booking ASC";
                  
            $stmt = $conn->prepare($q);
            if (!$stmt) throw new Exception('Prepare error: ' . $conn->error);
            
            $stmt->bind_param("i", $id_member);
            if (!$stmt->execute()) throw new Exception('Execute error: ' . $stmt->error);
            
            $res = $stmt->get_result();
            
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'id_member_jadwal' => (int)$row['id_member_jadwal'],
                    'tanggal_indo' => tglIndo($row['tanggal_booking']),
                    'jam_main' => substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5)
                ];
            }
            ob_end_clean();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
        break;

    // 6. PROSES UBAH JADWAL MEMBER
    case 'ubah_jadwal_member':
        try {
            $id_member = $_POST['id_member'] ?? 0;
            $id_member_jadwal = $_POST['id_member_jadwal'] ?? 0; // Sesi lama yang dipilih
            $id_lapangan = $_POST['id_lapangan'] ?? 0;
            $new_date = $_POST['new_date'] ?? '';
            $new_waktu_id = $_POST['new_jadwal_waktu'] ?? 0;

            if (empty($id_member_jadwal) || empty($new_date) || empty($new_waktu_id)) {
                throw new Exception('Data tidak lengkap.');
            }

            $conn->begin_transaction();
            
            // A. Cek Kuota Ubah
            $qCount = "SELECT COUNT(*) as total FROM history_ubah_jadwal WHERE id_member = ? AND tipe = 'member'";
            $stmtC = $conn->prepare($qCount);
            if (!$stmtC) throw new Exception('Prepare error: ' . $conn->error);
            $stmtC->bind_param("i", $id_member);
            if (!$stmtC->execute()) throw new Exception('Execute error: ' . $stmtC->error);
            $used = $stmtC->get_result()->fetch_assoc()['total'];
            
            if ($used >= 3) throw new Exception("Kuota ubah jadwal (3x) sudah habis.");

            // B. Ambil Data Sesi Lama (Untuk log & lepas slot)
            $qOld = "SELECT * FROM member_jadwal WHERE id_member_jadwal = ? AND id_member = ? FOR UPDATE";
            $stmtOld = $conn->prepare($qOld);
            if (!$stmtOld) throw new Exception('Prepare error: ' . $conn->error);
            $stmtOld->bind_param("ii", $id_member_jadwal, $id_member);
            if (!$stmtOld->execute()) throw new Exception('Execute error: ' . $stmtOld->error);
            $oldData = $stmtOld->get_result()->fetch_assoc();
            
            if (!$oldData) throw new Exception("Sesi tidak valid.");
            
            // C. Cek Slot Baru Availability
            $qHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtH = $conn->prepare($qHarian);
            if (!$stmtH) throw new Exception('Prepare error: ' . $conn->error);
            $stmtH->bind_param("is", $id_lapangan, $new_date);
            if (!$stmtH->execute()) throw new Exception('Execute error: ' . $stmtH->error);
            $resH = $stmtH->get_result();
            if ($resH->num_rows == 0) throw new Exception("Jadwal harian belum tersedia.");
            $id_harian_baru = $resH->fetch_assoc()['id_jadwal_harian'];

            $qCheck = "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ? AND status = 'tersedia' FOR UPDATE";
            $stmtCheck = $conn->prepare($qCheck);
            if (!$stmtCheck) throw new Exception('Prepare error: ' . $conn->error);
            $stmtCheck->bind_param("ii", $id_harian_baru, $new_waktu_id);
            if (!$stmtCheck->execute()) throw new Exception('Execute error: ' . $stmtCheck->error);
            $checkRes = $stmtCheck->get_result();
            if ($checkRes->num_rows == 0) throw new Exception("Slot waktu yang dipilih sudah terisi.");

            // D. Lepas Slot Lama (Set to 'tersedia') - cari dulu jadwal_harian lama
            $qOldHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtOldH = $conn->prepare($qOldHarian);
            if (!$stmtOldH) throw new Exception('Prepare error: ' . $conn->error);
            $stmtOldH->bind_param("is", $id_lapangan, $oldData['tanggal_booking']);
            if (!$stmtOldH->execute()) throw new Exception('Execute error: ' . $stmtOldH->error);
            $oldHarianRes = $stmtOldH->get_result();
            
            if ($oldHarianRes->num_rows > 0) {
                $oldHarian = $oldHarianRes->fetch_assoc();
                // Cari detail yang lama untuk di-release
                // Ambil id_jadwal_waktu dari jam yang lama
                $qOldJam = "SELECT id_jadwal_waktu FROM jadwal_waktu WHERE jam_mulai = ? AND jam_selesai = ?";
                $stmtOldJam = $conn->prepare($qOldJam);
                if (!$stmtOldJam) throw new Exception('Prepare error: ' . $conn->error);
                $stmtOldJam->bind_param("ss", $oldData['jam_mulai'], $oldData['jam_selesai']);
                if (!$stmtOldJam->execute()) throw new Exception('Execute error: ' . $stmtOldJam->error);
                $oldJamRes = $stmtOldJam->get_result();
                
                if ($oldJamRes->num_rows > 0) {
                    $oldJam = $oldJamRes->fetch_assoc();
                    $old_waktu_id = $oldJam['id_jadwal_waktu'];
                    
                    // Release old detail slot
                    $stmtRelease = $conn->prepare("UPDATE jadwal_detail SET status = 'tersedia' WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?");
                    if (!$stmtRelease) throw new Exception('Prepare error: ' . $conn->error);
                    $stmtRelease->bind_param("ii", $oldHarian['id_jadwal_harian'], $old_waktu_id);
                    if (!$stmtRelease->execute()) throw new Exception('Execute error: ' . $stmtRelease->error);
                }
            }

            // E. Booking Slot Baru
            $stmtBookNew = $conn->prepare("UPDATE jadwal_detail SET status = 'dibooking' WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?");
            if (!$stmtBookNew) throw new Exception('Prepare error: ' . $conn->error);
            $stmtBookNew->bind_param("ii", $id_harian_baru, $new_waktu_id);
            if (!$stmtBookNew->execute()) throw new Exception('Execute error: ' . $stmtBookNew->error);

            // F. Ambil jam string dari tabel jadwal_waktu
            $qJam = "SELECT jam_mulai, jam_selesai FROM jadwal_waktu WHERE id_jadwal_waktu = ?";
            $stmtJam = $conn->prepare($qJam);
            if (!$stmtJam) throw new Exception('Prepare error: ' . $conn->error);
            $stmtJam->bind_param("i", $new_waktu_id);
            if (!$stmtJam->execute()) throw new Exception('Execute error: ' . $stmtJam->error);
            $jamData = $stmtJam->get_result()->fetch_assoc();
            if (!$jamData) throw new Exception("Jam tidak valid.");

            // G. Update Tabel Member Jadwal (Tanggal, Jam)
            $stmtUpdM = $conn->prepare("UPDATE member_jadwal SET tanggal_booking = ?, jam_mulai = ?, jam_selesai = ? WHERE id_member_jadwal = ?");
            if (!$stmtUpdM) throw new Exception('Prepare error: ' . $conn->error);
            $stmtUpdM->bind_param("sssi", $new_date, $jamData['jam_mulai'], $jamData['jam_selesai'], $id_member_jadwal);
            if (!$stmtUpdM->execute()) throw new Exception('Execute error: ' . $stmtUpdM->error);

            // H. Catat History
            $jamLamaStr = substr($oldData['jam_mulai'], 0, 5);
            $jamBaruStr = substr($jamData['jam_mulai'], 0, 5);
            
            $stmtHist = $conn->prepare("INSERT INTO history_ubah_jadwal (id_detail_booking, id_member, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user) VALUES (NULL, ?, 'member', ?, ?, ?, ?, ?, ?)");            
            if (!$stmtHist) throw new Exception('Prepare error (History): ' . $conn->error);
            $stmtHist->bind_param("isssisi", $id_member, $oldData['tanggal_booking'], $jamLamaStr, $new_date, $jamBaruStr, $id_lapangan, $user_id);
            if (!$stmtHist->execute()) throw new Exception('Execute error: ' . $stmtHist->error);

            $conn->commit();
            ob_end_clean();
            echo json_encode(['status' => 'success', 'message' => 'Jadwal member berhasil diubah.']);

        } catch (Exception $e) {
            $conn->rollback();
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
        break;

    // 7. GET MEMBER DETAIL (Untuk tombol Detail)
    case 'get_member_detail':
         // ... (Kode Get Member Detail SAMA seperti yang sebelumnya, tidak perlu diubah) ...
         // Copy dari kode sebelumnya
         $id_member = $_POST['id_member'] ?? 0;
         $qMem = "SELECT m.id_member, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir, m.total_bayar, m.status, l.nama_lapangan, u.nama AS nama_user FROM member m JOIN lapangan l ON m.id_lapangan = l.id_lapangan JOIN users u ON m.id_user = u.id_user WHERE m.id_member = ? AND m.id_user = ?";
         $stmt = $conn->prepare($qMem);
         $stmt->bind_param("ii", $id_member, $user_id);
         $stmt->execute();
         $memData = $stmt->get_result()->fetch_assoc();
 
         if (!$memData) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']); exit; }
 
         $qJadwal = "SELECT mj.tanggal_booking, mj.jam_mulai, mj.jam_selesai FROM member_jadwal mj WHERE mj.id_member = ? ORDER BY mj.tanggal_booking ASC";
         $stmtJ = $conn->prepare($qJadwal);
         $stmtJ->bind_param("i", $id_member);
         $stmtJ->execute();
         $resJadwal = $stmtJ->get_result();
         
         $jadwalList = [];
         while($row = $resJadwal->fetch_assoc()) {
             $dateObj = new DateTime($row['tanggal_booking']);
             $jadwalList[] = [
                 'tanggal' => $dateObj->format('l, j F Y'),
                 'jam' => substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5)
             ];
         }
 
         $qCount = "SELECT COUNT(*) as total FROM history_ubah_jadwal WHERE id_member = ? AND tipe = 'member'";
         $stmtC = $conn->prepare($qCount);
         $stmtC->bind_param("i", $id_member);
         $stmtC->execute();
         $used = $stmtC->get_result()->fetch_assoc()['total'];
         $sisaUbah = max(0, 3 - $used);
 
         echo json_encode(['status' => 'success', 'data' => [
             'id_member' => $memData['id_member'],
             'kode_member' => "MMBR" . str_pad($memData['id_member'], 8, '0', STR_PAD_LEFT),
             'pemesan' => $memData['nama_user'],
             'nama_lapangan' => $memData['nama_lapangan'],
             'durasi' => $memData['durasi_bulan'] . " Bulan",
             'periode' => date('d M Y', strtotime($memData['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($memData['tanggal_berakhir'])),
             'status' => strtoupper($memData['status']),
             'sisa_ubah' => $sisaUbah,
             'jadwal_list' => $jadwalList
         ]]);
        break;

    default:
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
        break;
}
?>