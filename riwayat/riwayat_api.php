<?php
session_start();
require '../config/database.php';

// Set Header JSON
header('Content-Type: application/json');

// Cek Login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login.']);
    exit;
}

$user_id = $_SESSION['id_user'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    // ============================================================
    // 1. AMBIL DETAIL BOOKING (Untuk Modal Detail)
    // ============================================================
    case 'get_booking_detail':
        $id_booking = $_POST['id_booking'] ?? 0;

        $query = "
            SELECT 
                b.id_booking, 
                b.tanggal, 
                b.status AS status_booking, 
                b.payment_status,
                b.total_amount, 
                b.dp_amount,
                b.remaining_amount,
                b.payment_method,
                b.alasan_penolakan,
                l.nama_lapangan,
                u.nama AS nama_user,
                -- Mengambil rentang jam dari detail_booking
                MIN(jw.jam_mulai) AS jam_mulai,
                MAX(jw.jam_selesai) AS jam_selesai
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

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Format Tanggal & Jam
            $dateObj = new DateTime($row['tanggal']);
            $formattedDate = $dateObj->format('l, j F Y');
            
            // Generate Kode Booking Dummy (Bisa disesuaikan dengan logic asli)
            $kodeBooking = "INV-" . date('ymd', strtotime($row['tanggal'])) . "-" . $row['id_booking'];

            // Hitung Sisa Tagihan (Pastikan tidak negatif)
            $sisa = $row['remaining_amount'];
            if ($row['payment_status'] == 'lunas') {
                $sisa = 0;
            }

            // Format Jam
            $jamMain = substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5);

            $response = [
                'id_booking' => $row['id_booking'],
                'kode_booking' => $kodeBooking,
                'nama_lapangan' => $row['nama_lapangan'],
                'tanggal' => $formattedDate,
                'jam' => $jamMain,
                'user' => $row['nama_user'],
                'total_harga' => number_format($row['total_amount'], 0, ',', '.'),
                'dp' => number_format($row['dp_amount'], 0, ',', '.'),
                'sisa' => number_format($sisa, 0, ',', '.'),
                'sisa_raw' => $sisa, // Data mentah untuk logic JS
                'pembayaran' => strtoupper($row['payment_method'] ?? '-'),
                'status_booking' => $row['status_booking'],
                'payment_status' => $row['payment_status'], // Data penting untuk JS warning
                'alasan' => $row['alasan_penolakan'] ?? '-'
            ];

            echo json_encode(['status' => 'success', 'data' => $response]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data booking tidak ditemukan.']);
        }
        break;

    // ============================================================
    // 2. CEK KETERSEDIAAN JADWAL (Untuk Reschedule)
    // ============================================================
    case 'get_available_sessions':
        $lapangan_id = $_GET['lapangan_id'] ?? 0;
        $selected_date = $_GET['selected_date'] ?? date('Y-m-d');

        // Ambil id_jadwal_harian berdasarkan tanggal dan lapangan
        $qHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
        $stmtH = $conn->prepare($qHarian);
        $stmtH->bind_param("is", $lapangan_id, $selected_date);
        $stmtH->execute();
        $resH = $stmtH->get_result();

        if ($resH->num_rows == 0) {
            echo json_encode(['status' => 'success', 'available_sessions' => []]); // Tidak ada jadwal hari itu
            exit;
        }

        $harian = $resH->fetch_assoc();
        $id_harian = $harian['id_jadwal_harian'];

        // Ambil slot waktu yang statusnya 'tersedia'
        $qSlots = "
            SELECT jd.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai 
            FROM jadwal_detail jd
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE jd.id_jadwal_harian = ? AND jd.status = 'tersedia'
            ORDER BY jw.jam_mulai ASC
        ";
        $stmtS = $conn->prepare($qSlots);
        $stmtS->bind_param("i", $id_harian);
        $stmtS->execute();
        $resS = $stmtS->get_result();

        $slots = [];
        while ($row = $resS->fetch_assoc()) {
            $slots[] = [
                'id_jadwal_waktu' => $row['id_jadwal_waktu'],
                'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                'jam_selesai' => substr($row['jam_selesai'], 0, 5),
                'available' => true
            ];
        }

        echo json_encode(['status' => 'success', 'available_sessions' => $slots]);
        break;

    // ============================================================
    // 3. PROSES UBAH JADWAL (Reschedule)
    // ============================================================
    case 'ubah_jadwal_sesi':
        $id_sesi = $_POST['id_sesi'] ?? 0; // id_detail_booking
        $id_lapangan = $_POST['id_lapangan'] ?? 0;
        $new_date = $_POST['new_date'] ?? '';
        $new_waktu_id = $_POST['new_jadwal_waktu'] ?? 0;

        if (empty($id_sesi) || empty($new_date) || empty($new_waktu_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            exit;
        }

        $conn->begin_transaction();

        try {
            // 1. Ambil Data Lama & Validasi
            $qOld = "
                SELECT 
                    db.id_booking, db.id_jadwal_waktu, b.tanggal, 
                    jw.jam_mulai, jw.jam_selesai, b.status,
                    (SELECT COUNT(*) FROM history_ubah_jadwal h WHERE h.id_detail_booking = db.id_detail_booking) as count_ubah
                FROM detail_booking db
                JOIN booking b ON db.id_booking = b.id_booking
                JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
                WHERE db.id_detail_booking = ? AND b.id_user = ?
                FOR UPDATE
            ";
            $stmtOld = $conn->prepare($qOld);
            $stmtOld->bind_param("ii", $id_sesi, $user_id);
            $stmtOld->execute();
            $oldData = $stmtOld->get_result()->fetch_assoc();

            if (!$oldData) throw new Exception("Data booking tidak valid.");
            if ($oldData['count_ubah'] > 0) throw new Exception("Anda sudah pernah mengubah jadwal ini. Maksimal 1x.");
            
            // Cek Batas Waktu (H-5 Jam) - Server Side Check
            $waktuMain = new DateTime($oldData['tanggal'] . ' ' . $oldData['jam_mulai']);
            $limit = clone $waktuMain;
            $limit->sub(new DateInterval('PT5H'));
            if (new DateTime() > $limit) throw new Exception("Batas waktu perubahan jadwal telah habis (H-5 Jam).");

            // 2. Cek Ketersediaan Jadwal Baru
            // Ambil ID Jadwal Harian Baru
            $qHarian = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtH = $conn->prepare($qHarian);
            $stmtH->bind_param("is", $id_lapangan, $new_date);
            $stmtH->execute();
            $harianRes = $stmtH->get_result();
            
            if ($harianRes->num_rows == 0) throw new Exception("Jadwal belum dibuka untuk tanggal tersebut.");
            $id_harian_baru = $harianRes->fetch_assoc()['id_jadwal_harian'];

            // Cek Slot Kosong & Kunci
            $qCheck = "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ? AND status = 'tersedia' FOR UPDATE";
            $stmtCheck = $conn->prepare($qCheck);
            $stmtCheck->bind_param("ii", $id_harian_baru, $new_waktu_id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows == 0) throw new Exception("Jadwal yang dipilih sudah tidak tersedia.");

            // 3. Update Jadwal Detail (Lepas Slot Lama)
            // Cari id_harian lama
            $qHarianLama = "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
            $stmtHL = $conn->prepare($qHarianLama);
            $stmtHL->bind_param("is", $id_lapangan, $oldData['tanggal']);
            $stmtHL->execute();
            $id_harian_lama = $stmtHL->get_result()->fetch_assoc()['id_jadwal_harian'];

            $updOld = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?";
            $stmtUpdOld = $conn->prepare($updOld);
            $stmtUpdOld->bind_param("ii", $id_harian_lama, $oldData['id_jadwal_waktu']);
            $stmtUpdOld->execute();

            // 4. Update Jadwal Detail (Ambil Slot Baru)
            $updNew = "UPDATE jadwal_detail SET status = 'dibooking', id_booking = ? WHERE id_jadwal_harian = ? AND id_jadwal_waktu = ?";
            $stmtUpdNew = $conn->prepare($updNew);
            $stmtUpdNew->bind_param("iii", $oldData['id_booking'], $id_harian_baru, $new_waktu_id);
            $stmtUpdNew->execute();

            // 5. Update Tabel detail_booking
            $updDetail = "UPDATE detail_booking SET id_jadwal_waktu = ? WHERE id_detail_booking = ?";
            $stmtUpdDet = $conn->prepare($updDetail);
            $stmtUpdDet->bind_param("ii", $new_waktu_id, $id_sesi);
            $stmtUpdDet->execute();

            // 6. Update Tanggal di Tabel booking (JIKA BERUBAH TANGGAL)
            if ($oldData['tanggal'] != $new_date) {
                // Perhatian: Jika 1 booking ID punya banyak jam di tanggal lama, 
                // mengubah tanggal di tabel 'booking' akan memindah SEMUA jam tersebut.
                // Asumsi di sini: Sistem per-sesi memungkinkan pemecahan booking atau 
                // user hanya punya 1 sesi per booking ID. 
                // Jika sistem multi-slot dalam 1 booking ID, logic ini perlu penyesuaian.
                // Untuk amannya, kita update tanggal booking utama.
                $updBooking = "UPDATE booking SET tanggal = ? WHERE id_booking = ?";
                $stmtUpdB = $conn->prepare($updBooking);
                $stmtUpdB->bind_param("si", $new_date, $oldData['id_booking']);
                $stmtUpdB->execute();
            }

            // 7. Catat History
            // Ambil data jam baru untuk log
            $qJamBaru = "SELECT jam_mulai FROM jadwal_waktu WHERE id_jadwal_waktu = ?";
            $stmtJB = $conn->prepare($qJamBaru);
            $stmtJB->bind_param("i", $new_waktu_id);
            $stmtJB->execute();
            $jamBaruStr = $stmtJB->get_result()->fetch_assoc()['jam_mulai'];

            $insHist = "INSERT INTO history_ubah_jadwal (id_detail_booking, tipe, tanggal_lama, jam_lama, tanggal_baru, jam_baru, id_lapangan, id_user) VALUES (?, 'reguler', ?, ?, ?, ?, ?, ?)";
            $stmtHist = $conn->prepare($insHist);
            $stmtHist->bind_param("issssii", $id_sesi, $oldData['tanggal'], $oldData['jam_mulai'], $new_date, $jamBaruStr, $id_lapangan, $user_id);
            $stmtHist->execute();

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diubah.']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // ============================================================
    // 4. AJUKAN PEMBATALAN
    // ============================================================
    case 'ajukan_pembatalan':
        $id_sesi = $_POST['id_sesi'] ?? 0;
        $bank = $_POST['bank_ewallet'] ?? '';
        $rek = $_POST['no_rekening'] ?? '';
        $nama = $_POST['nama_penerima'] ?? '';

        if (empty($id_sesi) || empty($bank) || empty($rek) || empty($nama)) {
            echo json_encode(['status' => 'error', 'message' => 'Data form tidak lengkap.']);
            exit;
        }

        // Validasi Batas Waktu
        $qCek = "
            SELECT b.tanggal, jw.jam_mulai, b.id_booking
            FROM detail_booking db
            JOIN booking b ON db.id_booking = b.id_booking
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE db.id_detail_booking = ? AND b.id_user = ?
        ";
        $stmt = $conn->prepare($qCek);
        $stmt->bind_param("ii", $id_sesi, $user_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Booking tidak ditemukan.']);
            exit;
        }

        try {
            $waktuMain = new DateTime($data['tanggal'] . ' ' . $data['jam_mulai']);
            $batasBatal = clone $waktuMain;
            $batasBatal->sub(new DateInterval('PT12H')); // H-12 Jam
            
            if (new DateTime() > $batasBatal) {
                throw new Exception("Pengajuan ditolak. Batas waktu pembatalan (H-12 Jam) sudah terlewati.");
            }

            // Cek Duplikasi
            $qDup = "SELECT id FROM pembatalan_booking WHERE id_detail_booking = ? AND status = 'pending'";
            $stmtDup = $conn->prepare($qDup);
            $stmtDup->bind_param("i", $id_sesi);
            $stmtDup->execute();
            if ($stmtDup->get_result()->num_rows > 0) {
                throw new Exception("Anda sudah mengajukan pembatalan untuk sesi ini.");
            }

            // Insert Pengajuan
            $qIns = "
                INSERT INTO pembatalan_booking 
                (id_detail_booking, id_user, nama_pengaju, nomor_rekening, atas_nama, status, keterangan)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)
            ";
            $ket = "Bank: $bank";
            $stmtIns = $conn->prepare($qIns);
            $stmtIns->bind_param("iissss", $id_sesi, $user_id, $nama, $rek, $nama, $ket); // nama_pengaju dan atas_nama disamakan dulu
            
            if ($stmtIns->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan pembatalan dikirim. Admin akan memproses refund jika memenuhi syarat.']);
            } else {
                throw new Exception("Gagal menyimpan data.");
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        // ============================================================
    // 5. AMBIL DETAIL MEMBER (BARU)
    // ============================================================
    case 'get_member_detail':
        $id_member = $_POST['id_member'] ?? 0;

        // 1. Ambil Info Member Utama
        $qMem = "
            SELECT 
                m.id_member, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir,
                m.total_bayar, m.status, l.nama_lapangan, u.nama AS nama_user
            FROM member m
            JOIN lapangan l ON m.id_lapangan = l.id_lapangan
            JOIN users u ON m.id_user = u.id_user
            WHERE m.id_member = ? AND m.id_user = ?
        ";
        $stmt = $conn->prepare($qMem);
        $stmt->bind_param("ii", $id_member, $user_id);
        $stmt->execute();
        $memData = $stmt->get_result()->fetch_assoc();

        if (!$memData) {
            echo json_encode(['status' => 'error', 'message' => 'Data member tidak ditemukan.']);
            exit;
        }

        // 2. Ambil Daftar Jadwal Rutin Member (Tanggal & Jam Main)
        $qJadwal = "
            SELECT 
                mj.tanggal_booking,
                mj.jam_mulai,
                mj.jam_selesai,
                mj.status
            FROM member_jadwal mj
            WHERE mj.id_member = ?
            ORDER BY mj.tanggal_booking ASC
        ";
        $stmtJ = $conn->prepare($qJadwal);
        $stmtJ->bind_param("i", $id_member);
        $stmtJ->execute();
        $resJadwal = $stmtJ->get_result();
        
        $jadwalList = [];
        while($row = $resJadwal->fetch_assoc()) {
            $dateObj = new DateTime($row['tanggal_booking']);
            $jadwalList[] = [
                'tanggal' => $dateObj->format('l, j F Y'), // Format: Friday, 5 December 2025
                'jam' => substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5),
                'status' => $row['status']
            ];
        }

        // Hitung Sisa Kuota Ubah (Default 3)
        // Hitung berapa kali sudah ubah di history
        $qCountUbah = "SELECT COUNT(*) as total FROM history_ubah_jadwal WHERE id_member = ? AND tipe = 'member'";
        $stmtC = $conn->prepare($qCountUbah);
        $stmtC->bind_param("i", $id_member);
        $stmtC->execute();
        $used = $stmtC->get_result()->fetch_assoc()['total'];
        $sisaUbah = 3 - $used;
        if($sisaUbah < 0) $sisaUbah = 0;

        $response = [
            'id_member' => $memData['id_member'],
            'kode_member' => "MMBR" . str_pad($memData['id_member'], 8, '0', STR_PAD_LEFT),
            'nama_lapangan' => $memData['nama_lapangan'],
            'user' => $memData['nama_user'],
            'durasi' => $memData['durasi_bulan'] . " Bulan",
            'periode' => date('d M Y', strtotime($memData['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($memData['tanggal_berakhir'])),
            'status' => strtoupper($memData['status']),
            'total_bayar' => number_format($memData['total_bayar'], 0, ',', '.'),
            'sisa_ubah' => $sisaUbah,
            'jadwal_list' => $jadwalList // Array jadwal main
        ];

        echo json_encode(['status' => 'success', 'data' => $response]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
        break;
}
?>