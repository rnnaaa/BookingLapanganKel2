<?php
// 1. Memanggil header (sudah termasuk session_start() dan $base_url)
require '../include_user/header.php';
// Memanggil koneksi database (MySQLi)
require '../config/database.php';

// 2. Keamanan & Ambil User ID (menggunakan 'id_user' sesuai standar Anda)
$user_id = $_SESSION['id_user'] ?? null;
if (!$user_id || $user_id == 1) { // 1 adalah user demo
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

// 3. Get booking data (Kueri telah dikonversi dari PDO ke MySQLi)
$bookings = [];
$memberBookings = [];

try {
    // Regular bookings
    $stmtBookings = $conn->prepare("
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
    $stmtBookings->bind_param("i", $user_id);
    $stmtBookings->execute();
    $resultBookings = $stmtBookings->get_result();
    $bookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
    $stmtBookings->close();

    // Member bookings
    $stmtMember = $conn->prepare("
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
    $stmtMember->bind_param("i", $user_id);
    $stmtMember->execute();
    $resultMember = $stmtMember->get_result();
    $memberBookings = $resultMember->fetch_all(MYSQLI_ASSOC);
    $stmtMember->close();

} catch (Exception $e) {
    // Gunakan Exception, bukan PDOException
    $error = "Error mengambil data: " . $e->getMessage();
}
?>

<style>
* {
  box-sizing: border-box;
}
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
.header {
  text-align: center;
  margin-bottom: 30px;
  /* Warna teks diambil dari Tailwind agar kontras dengan bg-softGray */
  color: #1e293b; 
}
.header h1 {
  font-size: 2.5rem;
  margin-bottom: 10px;
  font-weight: 700;
  font-family: 'Poppins', sans-serif;
}
.header p {
  font-size: 1.1rem;
  color: #475569; /* slate-600 */
}
.tabs {
  display: flex;
  margin-bottom: 30px;
  background: white;
  border-radius: 8px;
  padding: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.tab-button {
  flex: 1;
  padding: 15px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  color: #4a5568;
  border-radius: 6px;
  transition: all 0.3s ease;
}
.tab-button.active {
  background: #0b63d6; /* var(--color-primary) */
  color: white;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
.card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}
.card:hover {
  transform: translateY(-2px);
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e2e8f0;
}
.card-header h3 {
  color: #2d3748;
  font-size: 1.3rem;
  font-family: 'Poppins', sans-serif;
}
.user-type {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 10px;
}
.user-type.reguler {
  background: #bee3f8;
  color: #2b6cb0;
}
.user-type.member {
  background: #c6f6d5;
  color: #276749;
}
.status {
  padding: 6px 15px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
}
.status.menunggu {
  background: #fefcbf;
  color: #d69e2e;
}
.status.disetujui {
  background: #c6f6d5;
  color: #276749;
}
.status.ditolak {
  background: #fed7d7;
  color: #c53030;
}
.card-body {
  margin-bottom: 20px;
}
.card-body p {
  margin-bottom: 8px;
  color: #4a5568;
}
.card-body strong {
  color: #2d3748;
}
.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 15px;
  border-top: 1px solid #e2e8f0;
}
.countdown {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}
.countdown:not(.expired) {
  background: #c6f6d5;
  color: #276749;
}
.countdown.expired {
  background: #fed7d7;
  color: #c53030;
}
.btn-detail,
.btn-ubah {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  transition: background 0.3s ease;
  margin-left: 10px;
}
.btn-detail {
  background: #0b63d6; /* var(--color-primary) */
  color: white;
}
.btn-detail:hover {
  background: #094ea8; /* var(--color-primary-dark) */
}
.btn-ubah {
  background: #48bb78;
  color: white;
}
.btn-ubah:hover:not(.disabled) {
  background: #38a169;
}
.btn-ubah.disabled {
  background: #a0aec0;
  cursor: not-allowed;
}
.empty-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.empty-state h3,
.error-state h3 {
  color: #4a5568;
  margin-bottom: 15px;
  font-size: 1.5rem;
}
.empty-state p,
.error-state p {
  color: #718096;
  margin-bottom: 25px;
  font-size: 1.1rem;
}
.btn-primary {
  background: #0b63d6; /* var(--color-primary) */
  color: white;
  border: none;
  padding: 12px 30px;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: background 0.3s ease;
}
.btn-primary:hover {
  background: #094ea8; /* var(--color-primary-dark) */
}
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  /* Ditambahkan dari header.php untuk menengahkan */
  align-items: center; 
  justify-content: center;
}
.modal-content {
  background-color: white;
  margin: auto; /* Dihapus: margin: 2% auto; */
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  /* Ditambahkan dari header.php untuk animasi */
  animation: modalPopIn 0.3s ease-out; 
}
/* Keyframes untuk animasi modalPopIn (jika belum ada) */
@keyframes modalPopIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
.close {
  position: absolute;
  right: 20px;
  top: 15px;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  color: #a0aec0;
}
.close:hover {
  color: #718096;
}
#detailContent {
  margin-bottom: 20px;
}
#detailContent p {
  margin-bottom: 10px;
  color: #4a5568;
}
#detailContent strong {
  color: #2d3748;
}
.qrcode {
  text-align: center;
  margin-top: 20px;
  padding: 20px;
  background: #f7fafc;
  border-radius: 8px;
}
.detail-pesanan {
  background: #f7fafc;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid #0b63d6; /* var(--color-primary) */
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #4a5568;
  font-weight: 600;
}
.session-list {
  max-height: 200px;
  overflow-y: auto;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  padding: 10px;
}
.session-item {
  display: flex;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #e2e8f0;
}
.session-item:last-child {
  border-bottom: none;
}
.session-item input[type="checkbox"] {
  margin-right: 10px;
}
.session-info {
  flex: 1;
}
.session-date {
  font-weight: 600;
  color: #2d3748;
}
.session-time {
  color: #718096;
  font-size: 0.9rem;
}
.time-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.select-input {
  width: 100%;
  padding: 10px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}
.select-input:focus {
  outline: none;
  border-color: #0b63d6; /* var(--color-primary) */
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}
.btn-secondary {
  background: #a0aec0;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.3s ease;
}
.btn-secondary:hover {
  background: #718096;
}
.loading {
  text-align: center;
  padding: 20px;
  color: #718096;
}
@media (max-width: 768px) {
  .container { padding: 10px; }
  .header h1 { font-size: 2rem; }
  .tabs { flex-direction: column; }
  .card-header { flex-direction: column; align-items: flex-start; gap: 10px; }
  .card-footer { flex-direction: column; gap: 15px; align-items: flex-start; }
  .time-selector { grid-template-columns: 1fr; }
  .modal-content { margin: 5% auto; width: 95%; padding: 20px; }
  .modal-footer { flex-direction: column; }
  .btn-detail, .btn-ubah { margin-left: 0; margin-top: 10px; width: 100%; }
}
</style>

<div class="container">
    <header class="header">
        <h1>Riwayat Booking</h1>
        <p>Lihat status dan detail pemesanan Anda</p>
    </header>

    <div class="tabs">
        <button class="tab-button active" data-tab="booking">Booking Reguler</button>
        <button class="tab-button" data-tab="member">Member Saya</button>
    </div>

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
                // Logika status booking Anda
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
                                '<?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES); ?>',
                                '<?php echo $booking['tanggal']; ?>',
                                '<?php echo htmlspecialchars($booking['jam_booking'] ?? '', ENT_QUOTES); ?>',
                                '<?php echo $booking['total_amount']; ?>',
                                'reguler',
                                '', '', '', // Placeholder untuk parameter member
                                '<?php echo htmlspecialchars($booking['status'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($booking['alasan_penolakan'] ?? '', ENT_QUOTES); ?>'
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

    <div id="member-tab" class="tab-content">
        <?php if (empty($memberBookings)): ?>
            <div class="empty-state">
                <h3>Belum ada membership aktif</h3>
                <p>Daftar member untuk menikmati berbagai keuntungan</p>
                <a href="<?= $base_url ?>../member/member.php" class="btn-primary">Daftar Member</a>
            </div>
        <?php else: ?>
            <?php foreach ($memberBookings as $member): ?>
                <?php
                $statusClass = '';
                $status = $member['status'];
                if (stripos($status, 'pending') !== false) $statusClass = 'menunggu';
                elseif (stripos($status, 'aktif') !== false) $statusClass = 'disetujui';
                elseif (stripos($status, 'nonaktif') !== false) $statusClass = 'ditolak';

                $ubahCount = 0; // Logika ini perlu disempurnakan di database Anda
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
                                '<?php echo htmlspecialchars($member['nama_lapangan'], ENT_QUOTES); ?>',
                                '<?php echo $member['durasi_bulan']; ?>',
                                '<?php echo $member['tanggal_mulai']; ?>',
                                '<?php echo $member['tanggal_berakhir']; ?>',
                                '<?php echo $member['total_bayar']; ?>',
                                '<?php echo htmlspecialchars($member['status'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($member['jadwal'] ?? '', ENT_QUOTES); ?>',
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

<script src="<?= $base_url ?>/assets/js/qrcode.min.js"></script>
<script>
// --- Isi dari riwayat.js ditempel di sini ---
document.addEventListener("DOMContentLoaded", function () {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const tabId = button.getAttribute("data-tab");

      // Update buttons
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      // Update contents
      tabContents.forEach((content) => content.classList.remove("active"));
      document.getElementById(tabId + "-tab").classList.add("active");
    });
  });
});

// Modal Detail for Regular Booking
function showDetail(id, lapangan, tanggal, jam, total, tipeUser, durasiMember, tanggalMulai, tanggalBerakhir, status, deskripsi) {
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = "";

  const date = new Date(tanggal);
  const formattedDate = date.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  let detailHTML = `
        <p><strong>ID Booking:</strong> #${id}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Tanggal Booking:</strong> ${formattedDate}</p>
        <p><strong>Jam:</strong> ${jam || "-"}</p>
        <p><strong>Total:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Tipe Booking:</strong> ${tipeUser.toUpperCase()}</p>
        <p><strong>Status:</strong> ${status.charAt(0).toUpperCase() + status.slice(1)}</p>
    `;

  if (status === "ditolak" && deskripsi) {
    detailHTML += `<p><strong>Alasan Penolakan:</strong> ${deskripsi}</p>`;
  } else if (status === "menunggu") {
    detailHTML += `<p><strong>Keterangan:</strong> Mohon tunggu verifikasi admin. Cek secara berkala.</p>`;
  } else if (status === "disetujui") {
    detailHTML += `<p><strong>Keterangan:</strong> Silakan tunjukkan QR code saat di tempat dan lakukan pelunasan.</p>`;
  }

  content.innerHTML = detailHTML;

  if (status === "disetujui") {
    new QRCode(qrContainer, {
      text: `https://badmintoon.com/verify/${id}`, // PERBAIKAN: Ganti URL ini
      width: 150,
      height: 150,
      colorDark: "#1e3a8a",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  modal.style.display = "flex";
}

// Modal Detail for Member
function showMemberDetail(id, lapangan, durasi, mulai, berakhir, total, status, jadwal, ubahCount, maxUbah) {
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = "";

  const startDate = new Date(mulai);
  const endDate = new Date(berakhir);
  const startFormatted = startDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
  const endFormatted = endDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });

  let detailHTML = `
        <p><strong>ID Member:</strong> #${id}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Durasi:</strong> ${durasi} bulan</p>
        <p><strong>Periode:</strong> ${startFormatted} - ${endFormatted}</p>
        <p><strong>Total Bayar:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Status:</strong> ${status.charAt(0).toUpperCase() + status.slice(1)}</p>
        <p><strong>Sisa Ubah Jadwal:</strong> ${maxUbah - ubahCount} dari ${maxUbah} kali</p>
    `;

  if (status === "pending") {
    detailHTML += `<p><strong>Keterangan:</strong> Mohon tunggu verifikasi admin. Cek secara berkala.</p>`;
  } else if (status === "aktif") {
    detailHTML += `<p><strong>Keterangan:</strong> Membership aktif. Silakan gunakan QR code untuk check-in.</p>`;

    new QRCode(qrContainer, {
      text: `https://badmintoon.com/verify/member/${id}`, // PERBAIKAN: Ganti URL ini
      width: 150,
      height: 150,
      colorDark: "#1e3a8a",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  if (jadwal) {
    detailHTML += `<p><strong>Jadwal:</strong></p>`;
    const jadwalList = jadwal.split("; ");
    detailHTML += '<ul style="margin-left: 20px;">';
    jadwalList.forEach((j) => {
      detailHTML += `<li>${j}</li>`;
    });
    detailHTML += "</ul>";
  }

  content.innerHTML = detailHTML;
  modal.style.display = "flex";
}

function closeModal() {
  document.getElementById("detailModal").style.display = "none";
}

// Ubah Jadwal for Regular Booking
function showUbahJadwal(bookingId, tipeBooking) {
  document.getElementById("formBookingId").value = bookingId;
  document.getElementById("formTipeBooking").value = tipeBooking;

  const detailPesanan = document.getElementById("detailPesanan");
  detailPesanan.innerHTML = `
        <h4>Detail Pesanan</h4>
        <p><strong>ID Booking:</strong> #${bookingId}</p>
        <p><strong>Tipe:</strong> ${tipeBooking.toUpperCase()}</p>
        <p><strong>Validasi:</strong> Dapat diubah H-5 jam dari waktu booking</p>
        <p><strong>Batas:</strong> 1x ubah jadwal</p>
    `;

  loadBookingSessions(bookingId, tipeBooking);
  document.getElementById("ubahJadwalModal").style.display = "flex";
}

function loadBookingSessions(bookingId, tipeBooking) {
  const sessionList = document.getElementById("sessionList");
  sessionList.innerHTML = '<div class="loading">Memuat sesi...</div>';

  // Simulasi AJAX call
  setTimeout(() => {
    let sessions = [
      { id: 1, tanggal: new Date().toISOString().split("T")[0], jam_mulai: "14:00", jam_selesai: "16:00" }
    ];
    displaySessionList(sessions, tipeBooking);
  }, 1000);
}

function displaySessionList(sessions, tipeBooking) {
  const sessionList = document.getElementById("sessionList");
  sessionList.innerHTML = "";

  if (sessions.length === 0) {
    sessionList.innerHTML = '<div class="empty-state">Tidak ada sesi yang dapat diubah</div>';
    return;
  }

  sessions.forEach((session) => {
    const sessionItem = document.createElement("div");
    sessionItem.className = "session-item";
    const date = new Date(session.tanggal);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long", year: "numeric", month: "long", day: "numeric"
    });
    sessionItem.innerHTML = `
            <input type="radio" name="session_ids" value="${session.id}" 
                   id="session-${session.id}" checked>
            <div class="session-info">
                <div class="session-date">${formattedDate}</div>
                <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
            </div>
        `;
    sessionList.appendChild(sessionItem);
  });
  updateSubmitButton(tipeBooking);
}

function updateSubmitButton(tipeBooking) {
  const submitBtn = document.getElementById("submitUbahJadwal");
  submitBtn.disabled = false;
  submitBtn.textContent = "Simpan Perubahan";
}

function closeUbahJadwalModal() {
  document.getElementById("ubahJadwalModal").style.display = "none";
}

// Ubah Jadwal for Member
function showUbahJadwalMember(memberId) {
  document.getElementById("formMemberId").value = memberId;
  const detailPesanan = document.getElementById("detailPesananMember");
  detailPesanan.innerHTML = `
        <h4>Detail Membership</h4>
        <p><strong>ID Member:</strong> #${memberId}</p>
        <p><strong>Validasi:</strong> Dapat diubah H-5 jam dari jadwal terdekat</p>
        <p><strong>Batas:</strong> Maksimal 3x ubah jadwal selama periode member</p>
        <p><strong>Catatan:</strong> Dapat memilih multiple sesi untuk diubah</p>
    `;

  loadMemberSessions(memberId);
  document.getElementById("ubahJadwalMemberModal").style.display = "flex";
}

function loadMemberSessions(memberId) {
  const sessionList = document.getElementById("memberSessionList");
  sessionList.innerHTML = '<div class="loading">Memuat sesi member...</div>';

  // Simulasi AJAX call
  setTimeout(() => {
    const sessions = [
      { id: 1, tanggal: new Date(Date.now() + 86400000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
      { id: 2, tanggal: new Date(Date.now() + 172800000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
      { id: 3, tanggal: new Date(Date.now() + 259200000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
    ];
    displayMemberSessionList(sessions);
  }, 1000);
}

function displayMemberSessionList(sessions) {
  const sessionList = document.getElementById("memberSessionList");
  sessionList.innerHTML = "";

  sessions.forEach((session) => {
    const sessionItem = document.createElement("div");
    sessionItem.className = "session-item";
    const date = new Date(session.tanggal);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long", year: "numeric", month: "long", day: "numeric"
    });
    const now = new Date();
    const sessionDateTime = new Date(session.tanggal + "T" + session.jam_mulai);
    const timeDiff = (sessionDateTime - now) / (1000 * 60 * 60);
    const isWithin5Hours = timeDiff <= 5;

    sessionItem.innerHTML = `
            <input type="checkbox" name="member_session_ids[]" value="${session.id}" 
                   id="member-session-${session.id}" ${isWithin5Hours ? "disabled" : "checked"}>
            <div class="session-info">
                <div class="session-date">${formattedDate}</div>
                <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
                ${isWithin5Hours ? '<div style="color: #e53e3e; font-size: 0.8rem;">Tidak dapat diubah (H-5 jam)</div>' : ""}
            </div>
        `;
    sessionList.appendChild(sessionItem);
  });
  updateMemberSubmitButton();
}

function updateMemberSubmitButton() {
  const submitBtn = document.getElementById("submitUbahJadwalMember");
  const checkedSessions = document.querySelectorAll('#memberSessionList input[type="checkbox"]:checked');
  if (checkedSessions.length > 0) {
    submitBtn.disabled = false;
    submitBtn.textContent = `Simpan Perubahan (${checkedSessions.length} sesi)`;
  } else {
    submitBtn.disabled = true;
    submitBtn.textContent = "Simpan Perubahan";
  }
}

function closeUbahJadwalMemberModal() {
  document.getElementById("ubahJadwalMemberModal").style.display = "none";
}

document.addEventListener("change", function (e) {
  if (e.target.name === "session_ids[]") {
    updateMemberSubmitButton();
  }
});

window.onclick = function (event) {
  const modals = ["detailModal", "ubahJadwalModal", "ubahJadwalMemberModal"];
  modals.forEach((modalId) => {
    const modal = document.getElementById(modalId);
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeModal();
    closeUbahJadwalModal();
    closeUbahJadwalMemberModal();
  }
});

document.getElementById("ubahJadwalForm")?.addEventListener("submit", function (e) {
  // Validasi form...
});

document.getElementById("ubahJadwalMemberForm")?.addEventListener("submit", function (e) {
  // Validasi form...
});
</script>


<?php 
// 8. Memanggil footer
require '../include_user/footer.php'; 
?>