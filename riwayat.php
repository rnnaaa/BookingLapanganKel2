<?php
// Koneksi database dan ambil data
$host = 'localhost';
$dbname = 'booking_badmintoon'; 
$username = 'root';
$password = '';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ambil data booking
session_start();
$user_id = $_SESSION['user_id'] ?? 9; // Default user Budi untuk testing

$bookings = [];
try {
    // Query yang fixed untuk menghindari group by error
    $stmt = $pdo->prepare("
        SELECT 
            b.id_booking,
            b.tanggal,
            b.tipe_booking,
            b.status,
            b.total_amount as total,
            b.alasan_penolakan as deskripsi,
            l.nama_lapangan,
            l.harga_per_jam,
            l.harga_per_jam_member,
            (SELECT GROUP_CONCAT(CONCAT(jw.jam_mulai, '-', jw.jam_selesai) SEPARATOR ', ') 
             FROM detail_booking db 
             JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
             WHERE db.id_booking = b.id_booking) as jam_booking,
            m.durasi_bulan,
            m.tanggal_mulai,
            m.tanggal_berakhir
        FROM booking b
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        LEFT JOIN member m ON b.id_user = m.id_user AND b.tipe_booking = 'member'
        WHERE b.id_user = ?
        ORDER BY b.tanggal DESC, b.id_booking DESC
    ");
    
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error mengambil data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Booking</title>
    <link rel="stylesheet" href="riwayat.css" /> <!-- Path CSS diperbaiki -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
</head>
<body>
    <header class="header">
        <h1>Riwayat Booking</h1>
        <p>Lihat status dan detail pemesanan lapangan Anda</p>
    </header>

    <main class="container" id="bookingContainer">
        <?php if (isset($error)): ?>
            <div class="error-state">
                <h3><?php echo $error; ?></h3>
                <p>Silakan coba lagi atau hubungi administrator.</p>
            </div>
        <?php elseif (empty($bookings)): ?>
            <div class="empty-state">
                <h3>Belum ada riwayat booking</h3>
                <p>Booking lapangan pertama Anda untuk melihat riwayat di sini</p>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                $statusClass = '';
                $status = $booking['status'];
                if (stripos($status, 'menunggu') !== false) $statusClass = 'menunggu';
                elseif (stripos($status, 'disetujui') !== false) $statusClass = 'disetujui';
                elseif (stripos($status, 'ditolak') !== false) $statusClass = 'ditolak';
                elseif (stripos($status, 'selesai') !== false) $statusClass = 'disetujui';
                elseif (stripos($status, 'dibatalkan') !== false) $statusClass = 'ditolak';
                
                $userTypeClass = $booking['tipe_booking'] === 'member' ? 'member' : 'reguler';
                
                // Hitung bisa edit atau tidak (H-5 jam)
                $canEdit = false;
                if ($status === 'disetujui') {
                    $now = new DateTime();
                    $bookingDate = new DateTime($booking['tanggal']);
                    $diff = $bookingDate->diff($now);
                    $hoursDiff = ($diff->days * 24) + $diff->h;
                    $canEdit = $hoursDiff > 5;
                }
                
                $countdownText = 'Tidak dapat diubah';
                if ($canEdit) {
                    $now = new DateTime();
                    $bookingDate = new DateTime($booking['tanggal']);
                    $diff = $bookingDate->diff($now);
                    $days = $diff->days;
                    $hours = $diff->h;
                    $countdownText = "Dapat diubah: {$days}h {$hours}j";
                }
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>
                                <?php echo htmlspecialchars($booking['nama_lapangan']); ?>
                                <span class="user-type <?php echo $userTypeClass; ?>">
                                    <?php echo strtoupper($booking['tipe_booking']); ?>
                                </span>
                            </h3>
                        </div>
                        <span class="status <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>ID Booking:</strong> #<?php echo htmlspecialchars($booking['id_booking']); ?></p>
                        <p><strong>Tanggal Booking:</strong> 
                            <?php 
                            $date = new DateTime($booking['tanggal']);
                            echo $date->format('l, j F Y');
                            ?>
                        </p>
                        <p><strong>Jam:</strong> <?php echo htmlspecialchars($booking['jam_booking'] ?? '-'); ?></p>
                        <p><strong>Total:</strong> Rp <?php echo number_format($booking['total'], 0, ',', '.'); ?></p>
                        <?php if ($booking['tipe_booking'] === 'member' && $booking['durasi_bulan']): ?>
                            <p><strong>Durasi Member:</strong> <?php echo htmlspecialchars($booking['durasi_bulan']); ?> bulan</p>
                            <p><strong>Periode:</strong> 
                                <?php 
                                $start = new DateTime($booking['tanggal_mulai']);
                                $end = new DateTime($booking['tanggal_berakhir']);
                                echo $start->format('d M Y') . ' - ' . $end->format('d M Y');
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="countdown <?php echo !$canEdit ? 'expired' : ''; ?>">
                            <?php echo $countdownText; ?>
                        </div>
                        <div>
                            <button class="btn-detail" onclick="showDetail(
                                '<?php echo $booking['id_booking']; ?>',
                                '<?php echo htmlspecialchars($booking['nama_lapangan']); ?>',
                                '<?php echo $booking['tanggal']; ?>',
                                '<?php echo htmlspecialchars($booking['jam_booking'] ?? ''); ?>',
                                '<?php echo $booking['total']; ?>',
                                '<?php echo htmlspecialchars($booking['tipe_booking']); ?>',
                                '<?php echo htmlspecialchars($booking['durasi_bulan'] ?? ''); ?>',
                                '<?php echo htmlspecialchars($booking['tanggal_mulai'] ?? ''); ?>',
                                '<?php echo htmlspecialchars($booking['tanggal_berakhir'] ?? ''); ?>',
                                '<?php echo htmlspecialchars($booking['status']); ?>',
                                '<?php echo htmlspecialchars($booking['deskripsi'] ?? ''); ?>'
                            )">Lihat Detail</button>
                            
                            <?php if ($status === 'disetujui'): ?>
                                <button class="btn-ubah" 
                                    onclick="showUbahJadwal('<?php echo $booking['id_booking']; ?>', '<?php echo $booking['tipe_booking']; ?>')" 
                                    <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                    Ubah Jadwal
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Modal Detail -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Detail Booking</h2>
            <div id="detailContent"></div>
            <div id="qrcode" class="qrcode"></div>
        </div>
    </div>

    <!-- Modal Ubah Jadwal -->
    <div class="modal" id="ubahJadwalModal">
        <div class="modal-content">
            <span class="close" onclick="closeUbahJadwalModal()">&times;</span>
            <h2>Ubah Jadwal</h2>
            <div id="ubahJadwalContent">
                <form id="ubahJadwalForm" action="proses_ubah_jadwal.php" method="POST">
                    <input type="hidden" name="booking_id" id="formBookingId">
                    <input type="hidden" name="tipe_booking" id="formTipeBooking">
                    <div class="form-group">
                        <label>Pilih sesi:</label>
                        <div id="sessionList" class="session-list"></div>
                    </div>
                    <div class="form-group">
                        <label>Pindah ke:</label>
                        <div class="time-selector">
                            <select name="new_day" id="newDay" class="select-input" required>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                            <select name="new_time" id="newTime" class="select-input" required>
                                <option value="08:00-09:00">08:00-09:00</option>
                                <option value="09:00-10:00">09:00-10:00</option>
                                <option value="10:00-11:00">10:00-11:00</option>
                                <option value="11:00-12:00">11:00-12:00</option>
                                <option value="12:00-13:00">12:00-13:00</option>
                                <option value="13:00-14:00">13:00-14:00</option>
                                <option value="14:00-15:00">14:00-15:00</option>
                                <option value="15:00-16:00">15:00-16:00</option>
                                <option value="16:00-17:00">16:00-17:00</option>
                                <option value="17:00-18:00">17:00-18:00</option>
                                <option value="18:00-19:00">18:00-19:00</option>
                                <option value="19:00-20:00">19:00-20:00</option>
                                <option value="20:00-21:00">20:00-21:00</option>
                                <option value="21:00-22:00">21:00-22:00</option>
                                <option value="22:00-23:00">22:00-23:00</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeUbahJadwalModal()">Batal</button>
                        <button type="submit" class="btn-primary" id="submitUbahJadwal">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="riwayat.js"></script> <!-- Path JS diperbaiki -->
</body>
</html>