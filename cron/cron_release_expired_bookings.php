<?php
declare(strict_types=1);

/**
 * Cron Job: Release Expired Bookings
 * 
 * Fungsi: Mengecek member_jadwal dengan status 'pending' yang sudah melampaui 30 menit
 * Jika member belum melakukan pembayaran, slot booking akan dirilis (dihapus) 
 * sehingga pengguna lain bisa mengakses jam tersebut.
 * 
 * Jalankan setiap 1-2 menit via cPanel Cron Job atau Windows Task Scheduler
 * Command: php C:\laragon\www\BookingLapanganKel2\cron\cron_release_expired_bookings.php
 */

// DB CONFIG
$DB_HOST = '127.0.0.1';
$DB_NAME = 'bookinglapanganb2';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHAR = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHAR",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    error_log("CRON ERROR: DB Connection Failed - " . $e->getMessage());
    die("Database connection error");
}

// ===== MAIN LOGIC =====

// Timeout dalam menit
$TIMEOUT_MINUTES = 30;

// Hitung waktu expired: 30 menit yang lalu
$expiredTime = date('Y-m-d H:i:s', strtotime("-$TIMEOUT_MINUTES minutes"));

// Query: Ambil member_jadwal dengan status='pending' dan created_at lebih tua dari 30 menit
$sql = "SELECT id_member_jadwal, id_member, id_lapangan, tanggal_booking, jam_mulai, created_at
        FROM member_jadwal
        WHERE status = 'pending' 
        AND created_at <= :expired_time
        ORDER BY created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':expired_time' => $expiredTime]);
$expiredBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalReleased = 0;

if (!empty($expiredBookings)) {
    foreach ($expiredBookings as $booking) {
        try {
            // Sebelum delete, check apakah member sudah bayar atau tidak
            // Query member untuk cek status pembayaran
            $checkPaymentStmt = $pdo->prepare("SELECT status, total_bayar FROM member WHERE id_member = :id_member LIMIT 1");
            $checkPaymentStmt->execute([':id_member' => $booking['id_member']]);
            $memberData = $checkPaymentStmt->fetch(PDO::FETCH_ASSOC);

            // Jika member masih 'pending' (belum verifikasi pembayaran), hapus jadwal
            if ($memberData && $memberData['status'] === 'pending') {
                // DELETE member_jadwal yang expired
                $deleteStmt = $pdo->prepare("DELETE FROM member_jadwal WHERE id_member_jadwal = :id LIMIT 1");
                $deleteStmt->execute([':id' => $booking['id_member_jadwal']]);

                $totalReleased++;

                // Log untuk monitoring
                error_log(sprintf(
                    "CRON: Released booking - ID:%d, Member:%d, Lapangan:%d, Date:%s %s, Created:%s",
                    $booking['id_member_jadwal'],
                    $booking['id_member'],
                    $booking['id_lapangan'],
                    $booking['tanggal_booking'],
                    $booking['jam_mulai'],
                    $booking['created_at']
                ));
            }
        } catch (Exception $e) {
            error_log("CRON ERROR: Failed to process booking " . $booking['id_member_jadwal'] . " - " . $e->getMessage());
        }
    }
}

// Log summary
error_log("CRON COMPLETE: Released $totalReleased expired bookings at " . date('Y-m-d H:i:s'));

echo "SUCCESS: Released $totalReleased expired bookings\n";
exit(0);
?>
