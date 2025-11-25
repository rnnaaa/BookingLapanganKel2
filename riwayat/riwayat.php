<?php
session_start();
require_once 'config/database.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('<Location:auth/login.php');
    exit;
}

// Get booking data
$bookings = [];
$memberBookings = [];

try {
    // Regular bookings
    $stmtBookings = $pdo->prepare("
        SELECT b.id_booking, b.tanggal, b.tipe_booking, b.status, b.total_amount, 
               b.alasan_penolakan, l.nama_lapangan, l.harga_per_jam,
               GROUP_CONCAT(CONCAT(jw.jam_mulai, '-', jw.jam_selesai) SEPARATOR ', ') as jam_booking
        FROM booking b
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
        LEFT JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
        WHERE b.id_user = ?
        GROUP BY b.id_booking
        ORDER BY b.tanggal DESC, b.id_booking DESC
    ");
    $stmtBookings->execute([$user_id]);
    $bookings = $stmtBookings->fetchAll(PDO::FETCH_ASSOC);

    // Member bookings
    $stmtMember = $pdo->prepare("
        SELECT m.id_member, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir, 
               m.bukti_pembayaran, m.method, m.total_bayar, m.status, l.nama_lapangan,
               COUNT(mj.id_member_jadwal) as total_sessions,
               GROUP_CONCAT(DISTINCT CONCAT(mj.tanggal_booking, ' ', mj.jam_mulai) SEPARATOR '; ') as jadwal
        FROM member m
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        LEFT JOIN member_jadwal mj ON m.id_member = mj.id_member
        WHERE m.id_user = ?
        GROUP BY m.id_member
        ORDER BY m.tanggal_mulai DESC
    ");
    $stmtMember->execute([$user_id]);
    $memberBookings = $stmtMember->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error mengambil data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking</title>
    <link rel="stylesheet" href="riwayat.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Riwayat Booking</h1>
            <p>Lihat status dan detail pemesanan Anda</p>
        </header>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" data-tab="booking">Booking Reguler</button>
            <button class="tab-button" data-tab="member">Member Saya</button>
        </div>

        <!-- Booking Reguler Tab -->
        <div id="booking-tab" class="tab-content active">
            <?php if (isset($error)): ?>
                <div class="error-state">
                    <h3><?php echo $error; ?></h3>
                    <p>Silakan coba lagi atau hubungi administrator.</p>
                </div>
            <?php elseif (empty($bookings)): ?>
                <div class="empty-state">
                    <h3>Belum ada riwayat booking reguler</h3>
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
                                    <span class="user-type reguler">
                                        REGULER
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
                            <p><strong>Total:</strong> Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?></p>
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
                                    '<?php echo $booking['total_amount']; ?>',
                                    'reguler',
                                    '',
                                    '',
                                    '',
                                    '<?php echo htmlspecialchars($booking['status']); ?>',
                                    '<?php echo htmlspecialchars($booking['alasan_penolakan'] ?? ''); ?>'
                                )">Lihat Detail</button>
                                
                                <?php if ($status === 'disetujui' && $canEdit): ?>
                                    <button class="btn-ubah" 
                                        onclick="showUbahJadwal('<?php echo $booking['id_booking']; ?>', 'reguler')">
                                        Ubah Jadwal
                                    </button>
                                <?php else: ?>
                                    <button class="btn-ubah disabled" disabled>Ubah Jadwal</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Member Saya Tab -->
        <div id="member-tab" class="tab-content">
            <?php if (empty($memberBookings)): ?>
                <div class="empty-state">
                    <h3>Belum ada membership aktif</h3>
                    <p>Daftar member untuk menikmati berbagai keuntungan</p>
                    <a href="member.php" class="btn-primary">Daftar Member</a>
                </div>
            <?php else: ?>
                <?php foreach ($memberBookings as $member): ?>
                    <?php
                    $statusClass = '';
                    $status = $member['status'];
                    if (stripos($status, 'pending') !== false) $statusClass = 'menunggu';
                    elseif (stripos($status, 'aktif') !== false) $statusClass = 'disetujui';
                    elseif (stripos($status, 'nonaktif') !== false) $statusClass = 'ditolak';

                    $ubahCount = 0; // This should come from database
                    $maxUbah = $member['durasi_bulan'];
                    $canUbah = $ubahCount < $maxUbah && $status === 'aktif';
                    ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3>
                                    <?php echo htmlspecialchars($member['nama_lapangan']); ?>
                                    <span class="user-type member">
                                        MEMBER
                                    </span>
                                </h3>
                            </div>
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><strong>ID Member:</strong> #<?php echo htmlspecialchars($member['id_member']); ?></p>
                            <p><strong>Durasi:</strong> <?php echo htmlspecialchars($member['durasi_bulan']); ?> bulan</p>
                            <p><strong>Periode:</strong> 
                                <?php 
                                $start = new DateTime($member['tanggal_mulai']);
                                $end = new DateTime($member['tanggal_berakhir']);
                                echo $start->format('d M Y') . ' - ' . $end->format('d M Y');
                                ?>
                            </p>
                            <p><strong>Total Sesi:</strong> <?php echo htmlspecialchars($member['total_sessions']); ?> sesi</p>
                            <p><strong>Total Bayar:</strong> Rp <?php echo number_format($member['total_bayar'], 0, ',', '.'); ?></p>
                            <p><strong>Sisa Ubah Jadwal:</strong> <?php echo ($maxUbah - $ubahCount) . '/' . $maxUbah; ?> kali</p>
                        </div>
                        <div class="card-footer">
                            <div class="countdown <?php echo !$canUbah ? 'expired' : ''; ?>">
                                <?php echo $canUbah ? 'Dapat diubah' : 'Tidak dapat diubah'; ?>
                            </div>
                            <div>
                                <button class="btn-detail" onclick="showMemberDetail(
                                    '<?php echo $member['id_member']; ?>',
                                    '<?php echo htmlspecialchars($member['nama_lapangan']); ?>',
                                    '<?php echo $member['durasi_bulan']; ?>',
                                    '<?php echo $member['tanggal_mulai']; ?>',
                                    '<?php echo $member['tanggal_berakhir']; ?>',
                                    '<?php echo $member['total_bayar']; ?>',
                                    '<?php echo htmlspecialchars($member['status']); ?>',
                                    '<?php echo htmlspecialchars($member['jadwal'] ?? ''); ?>',
                                    '<?php echo $ubahCount; ?>',
                                    '<?php echo $maxUbah; ?>'
                                )">Lihat Detail</button>
                                
                                <?php if ($canUbah): ?>
                                    <button class="btn-ubah" 
                                        onclick="showUbahJadwalMember('<?php echo $member['id_member']; ?>')">
                                        Ubah Jadwal
                                    </button>
                                <?php else: ?>
                                    <button class="btn-ubah disabled" disabled>Ubah Jadwal</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Detail Booking</h2>
            <div id="detailContent"></div>
            <div id="qrcode" class="qrcode"></div>
        </div>
    </div>

    <!-- Modal Ubah Jadwal Reguler -->
    <div class="modal" id="ubahJadwalModal">
        <div class="modal-content">
            <span class="close" onclick="closeUbahJadwalModal()">&times;</span>
            <h2>Ubah Jadwal</h2>
            <div id="ubahJadwalContent">
                <form id="ubahJadwalForm" action="proses_ubah_jadwal.php" method="POST">
                    <input type="hidden" name="booking_id" id="formBookingId">
                    <input type="hidden" name="tipe_booking" id="formTipeBooking">
                    
                    <div class="detail-pesanan" id="detailPesanan"></div>
                    
                    <div class="form-group">
                        <label>Pilih sesi:</label>
                        <div id="sessionList" class="session-list"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Pindah ke:</label>
                        <div class="time-selector">
                            <select name="new_day" id="newDay" class="select-input" required>
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                            <select name="new_time" id="newTime" class="select-input" required>
                                <option value="">-- Pilih Jam --</option>
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

    <!-- Modal Ubah Jadwal Member -->
    <div class="modal" id="ubahJadwalMemberModal">
        <div class="modal-content">
            <span class="close" onclick="closeUbahJadwalMemberModal()">&times;</span>
            <h2>Ubah Jadwal Member</h2>
            <div id="ubahJadwalMemberContent">
                <form id="ubahJadwalMemberForm" action="proses_ubah_jadwal_member.php" method="POST">
                    <input type="hidden" name="member_id" id="formMemberId">
                    
                    <div class="detail-pesanan" id="detailPesananMember"></div>
                    
                    <div class="form-group">
                        <label>Pilih sesi yang ingin diubah:</label>
                        <div id="memberSessionList" class="session-list"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Pindah ke tanggal dan jam baru:</label>
                        <div class="time-selector">
                            <input type="date" name="new_date" id="newDate" class="select-input" required>
                            <select name="new_time_member" id="newTimeMember" class="select-input" required>
                                <option value="">-- Pilih Jam --</option>
                                <option value="08:00">08:00-09:00</option>
                                <option value="09:00">09:00-10:00</option>
                                <option value="10:00">10:00-11:00</option>
                                <option value="11:00">11:00-12:00</option>
                                <option value="12:00">12:00-13:00</option>
                                <option value="13:00">13:00-14:00</option>
                                <option value="14:00">14:00-15:00</option>
                                <option value="15:00">15:00-16:00</option>
                                <option value="16:00">16:00-17:00</option>
                                <option value="17:00">17:00-18:00</option>
                                <option value="18:00">18:00-19:00</option>
                                <option value="19:00">19:00-20:00</option>
                                <option value="20:00">20:00-21:00</option>
                                <option value="21:00">21:00-22:00</option>
                                <option value="22:00">22:00-23:00</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeUbahJadwalMemberModal()">Batal</button>
                        <button type="submit" class="btn-primary" id="submitUbahJadwalMember">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="riwayat.js"></script>
</body>
</html>