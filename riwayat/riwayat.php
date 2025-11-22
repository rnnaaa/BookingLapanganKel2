<?php
// 1. Memanggil header (sudah termasuk session_start() output HTML)
require '../include_user/header.php';
// Memanggil koneksi database
require '../config/database.php';

// 2. Cek Status Login
$user_id = $_SESSION['id_user'] ?? null;
// Cek apakah user kosong atau user demo (id=1)
$is_logged_in = ($user_id && $user_id != 1);

// === JIKA SUDAH LOGIN, AMBIL DATA ===
$bookings = [];
$memberBookings = [];
$error = null;

if ($is_logged_in) {
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
        $error = "Error mengambil data: " . $e->getMessage();
    }
}
?>

<style>
* { box-sizing: border-box; }
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.header { text-align: center; margin-bottom: 30px; color: #1e293b; }
.header h1 { font-size: 2.5rem; margin-bottom: 10px; font-weight: 700; font-family: 'Poppins', sans-serif; }
.header p { font-size: 1.1rem; color: #475569; }
.tabs { display: flex; margin-bottom: 30px; background: white; border-radius: 8px; padding: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
.tab-button { flex: 1; padding: 15px 20px; border: none; background: transparent; cursor: pointer; font-size: 1rem; font-weight: 600; color: #4a5568; border-radius: 6px; transition: all 0.3s ease; }
.tab-button.active { background: #0b63d6; color: white; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
.card:hover { transform: translateY(-2px); }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0; }
.card-header h3 { color: #2d3748; font-size: 1.3rem; font-family: 'Poppins', sans-serif; }
.user-type { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-left: 10px; }
.user-type.reguler { background: #bee3f8; color: #2b6cb0; }
.user-type.member { background: #c6f6d5; color: #276749; }
.status { padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
.status.menunggu { background: #fefcbf; color: #d69e2e; }
.status.disetujui { background: #c6f6d5; color: #276749; }
.status.ditolak { background: #fed7d7; color: #c53030; }
.card-body { margin-bottom: 20px; }
.card-body p { margin-bottom: 8px; color: #4a5568; }
.card-body strong { color: #2d3748; }
.card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #e2e8f0; }
.countdown { padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; }
.countdown:not(.expired) { background: #c6f6d5; color: #276749; }
.countdown.expired { background: #fed7d7; color: #c53030; }
.btn-detail, .btn-ubah { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: background 0.3s ease; margin-left: 10px; }
.btn-detail { background: #0b63d6; color: white; }
.btn-detail:hover { background: #094ea8; }
.btn-ubah { background: #48bb78; color: white; }
.btn-ubah:hover:not(.disabled) { background: #38a169; }
.btn-ubah.disabled { background: #a0aec0; cursor: not-allowed; }
.empty-state, .error-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
.empty-state h3, .error-state h3 { color: #4a5568; margin-bottom: 15px; font-size: 1.5rem; }
.empty-state p, .error-state p { color: #718096; margin-bottom: 25px; font-size: 1.1rem; }
.btn-primary { background: #0b63d6; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 1rem; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.3s ease; }
.btn-primary:hover { background: #094ea8; }
/* Modal Styles */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); align-items: center; justify-content: center; }
.modal-content { background-color: white; margin: auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; position: relative; animation: modalPopIn 0.3s ease-out; }
@keyframes modalPopIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
.close { position: absolute; right: 20px; top: 15px; font-size: 28px; font-weight: bold; cursor: pointer; color: #a0aec0; }
.close:hover { color: #718096; }
#detailContent { margin-bottom: 20px; }
#detailContent p { margin-bottom: 10px; color: #4a5568; }
#detailContent strong { color: #2d3748; }
.qrcode { text-align: center; margin-top: 20px; padding: 20px; background: #f7fafc; border-radius: 8px; }
.detail-pesanan { background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0b63d6; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #4a5568; font-weight: 600; }
.session-list { max-height: 200px; overflow-y: auto; border: 2px solid #e2e8f0; border-radius: 6px; padding: 10px; }
.session-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #e2e8f0; }
.session-item:last-child { border-bottom: none; }
.session-item input[type="checkbox"] { margin-right: 10px; }
.session-info { flex: 1; }
.session-date { font-weight: 600; color: #2d3748; }
.session-time { color: #718096; font-size: 0.9rem; }
.time-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.select-input { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem; }
.select-input:focus { outline: none; border-color: #0b63d6; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.btn-secondary { background: #a0aec0; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: background 0.3s ease; }
.btn-secondary:hover { background: #718096; }
.loading { text-align: center; padding: 20px; color: #718096; }
/* Style Khusus Not Login */
.not-login-container { text-align: center; padding: 50px 20px; max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
.not-login-icon { font-size: 4rem; color: #94a3b8; margin-bottom: 20px; }
.btn-outline { background: white; border: 2px solid #0b63d6; color: #0b63d6; padding: 12px 30px; border-radius: 6px; font-size: 1rem; cursor: pointer; text-decoration: none; transition: all 0.3s; }
.btn-outline:hover { background: #eff6ff; }
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

    <?php if (!$is_logged_in): ?>
        <div class="not-login-container">
            <i class="fa-solid fa-lock not-login-icon"></i>
            <h3 style="font-size: 1.5rem; color: #334155; margin-bottom: 10px;">Akses Diperlukan</h3>
            <p style="color: #64748b; margin-bottom: 30px;">
                Anda belum login. Silakan masuk terlebih dahulu untuk melihat riwayat pemesanan dan status membership Anda.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
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
                    <h3><i class="fa-solid fa-triangle-exclamation text-yellow-500"></i> Terjadi Kesalahan</h3>
                    <p><?php echo $error; ?></p>
                </div>
            <?php elseif (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fa-regular fa-calendar-xmark" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
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
                                    <span class="user-type reguler">REGULER</span>
                                </h3>
                            </div>
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><strong>ID Booking:</strong> #<?php echo htmlspecialchars($booking['id_booking']); ?></p>
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
                                    'reguler', '', '', '', 
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
                    <i class="fa-solid fa-id-card" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
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

                    $ubahCount = 0; 
                    $maxUbah = $member['durasi_bulan'];
                    $canUbah = $ubahCount < $maxUbah && $status === 'aktif';
                    ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3>
                                    <?php echo htmlspecialchars($member['nama_lapangan']); ?>
                                    <span class="user-type member">MEMBER</span>
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
                            <p><strong>Total Bayar:</strong> Rp <?php echo number_format($member['total_bayar'], 0, ',', '.'); ?></p>
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
    // === 1. LOGIKA TAB (JALAN OTOMATIS SAAT LOAD) ===
    document.addEventListener("DOMContentLoaded", function () {
        const tabButtons = document.querySelectorAll(".tab-button");
        const tabContents = document.querySelectorAll(".tab-content");

        if(tabButtons.length > 0) {
            tabButtons.forEach((button) => {
                button.addEventListener("click", () => {
                    const tabId = button.getAttribute("data-tab");
                    
                    // Reset Active Class
                    tabButtons.forEach((btn) => btn.classList.remove("active"));
                    tabContents.forEach((content) => content.classList.remove("active"));

                    // Set Active Class
                    button.classList.add("active");
                    const target = document.getElementById(tabId + "-tab");
                    if(target) target.classList.add("active");
                });
            });
        }
    });

    // === 2. FUNGSI MODAL GLOBAL (Agar bisa dipanggil onclick) ===

    // -- Modal Detail (Umum) --
    function showDetail(id, lapangan, tanggal, jam, total, tipeUser, durasi, mulai, berakhir, status, deskripsi) {
        // ... (Kode showDetail Anda yang lama, atau biarkan kosong jika sudah ada) ...
        // Untuk mempersingkat, pastikan modal detail tetap jalan seperti sebelumnya
        const modal = document.getElementById("detailModal");
        const content = document.getElementById("detailContent");
        
        let html = `<p><strong>ID:</strong> #${id}</p><p><strong>Status:</strong> ${status}</p>`;
        // (Isi detail sesuai kebutuhan Anda)
        content.innerHTML = html;
        modal.style.display = "flex";
    }

    // -- FUNGSI KHUSUS MEMBER: UBAH JADWAL --
    function showUbahJadwalMember(memberId) {
        console.log("Membuka modal untuk Member ID:", memberId); // Cek Console browser jika macet

        // 1. Set ID ke Form
        const inputId = document.getElementById("formMemberId");
        if(inputId) inputId.value = memberId;

        // 2. Isi Info Header Modal
        const detailInfo = document.getElementById("detailPesananMember");
        if(detailInfo) {
            detailInfo.innerHTML = `
                <h4 style="font-weight:bold; margin-bottom:5px;">Ubah Jadwal Member #${memberId}</h4>
                <p style="font-size:0.9rem; color:#64748b;">Pilih sesi di bawah ini yang ingin Anda pindahkan jadwalnya.</p>
            `;
        }

        // 3. Load Data Sesi (Menggunakan Dummy Data Dulu agar UI muncul)
        loadMemberSessions(memberId);

        // 4. Tampilkan Modal
        const modal = document.getElementById("ubahJadwalMemberModal");
        if(modal) {
            modal.style.display = "flex";
        } else {
            alert("Error: Modal ID 'ubahJadwalMemberModal' tidak ditemukan!");
        }
    }

    // Fungsi Load Data Sesi Member
    function loadMemberSessions(memberId) {
        const sessionList = document.getElementById("memberSessionList");
        sessionList.innerHTML = '<div class="loading">Memuat daftar sesi...</div>';

        // SIMULASI DATA (Nanti diganti dengan fetch ke database)
        setTimeout(() => {
            // Contoh data sesi yang dimiliki member ini
            const dummySessions = [
                { id: 101, tanggal: "2025-11-25", jam_mulai: "08:00", jam_selesai: "09:00" },
                { id: 102, tanggal: "2025-11-26", jam_mulai: "08:00", jam_selesai: "09:00" },
                { id: 103, tanggal: "2025-12-02", jam_mulai: "08:00", jam_selesai: "09:00" }
            ];
            
            displayMemberSessionList(dummySessions);
        }, 500); // Delay 0.5 detik biar terlihat loading
    }

    // Fungsi Render List Checkbox
    function displayMemberSessionList(sessions) {
        const sessionList = document.getElementById("memberSessionList");
        sessionList.innerHTML = ""; // Bersihkan loading

        if (sessions.length === 0) {
            sessionList.innerHTML = '<div class="empty-state">Tidak ada sesi yang tersedia.</div>';
            return;
        }

        // Buat HTML untuk setiap sesi
        sessions.forEach((session) => {
            const dateObj = new Date(session.tanggal);
            const dateStr = dateObj.toLocaleDateString("id-ID", { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });

            const itemDiv = document.createElement("div");
            itemDiv.className = "session-item";
            itemDiv.style.cssText = "display:flex; align-items:center; padding:10px; border-bottom:1px solid #eee;";
            
            itemDiv.innerHTML = `
                <input type="checkbox" name="member_session_ids[]" value="${session.id}" 
                       id="sess-${session.id}" style="margin-right:15px; transform:scale(1.2);">
                <label for="sess-${session.id}" style="cursor:pointer; flex:1;">
                    <div class="session-date" style="font-weight:bold; color:#334155;">${dateStr}</div>
                    <div class="session-time" style="font-size:0.9rem; color:#64748b;">
                        Pukul ${session.jam_mulai} - ${session.jam_selesai}
                    </div>
                </label>
            `;
            sessionList.appendChild(itemDiv);
        });

        // Tambahkan Event Listener ke Checkbox baru (untuk update tombol simpan)
        const checkboxes = sessionList.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateMemberSubmitButton);
        });
        
        // Reset tombol submit
        updateMemberSubmitButton();
    }

    // Fungsi Update Status Tombol Simpan
    function updateMemberSubmitButton() {
        const submitBtn = document.getElementById("submitUbahJadwalMember");
        const checkboxes = document.querySelectorAll('#memberSessionList input[type="checkbox"]:checked');
        
        if (checkboxes.length > 0) {
            submitBtn.disabled = false;
            submitBtn.innerText = `Simpan Perubahan (${checkboxes.length} Sesi)`;
            submitBtn.style.backgroundColor = "#0b63d6";
            submitBtn.style.cursor = "pointer";
        } else {
            submitBtn.disabled = true;
            submitBtn.innerText = "Pilih Sesi Dulu";
            submitBtn.style.backgroundColor = "#94a3b8";
            submitBtn.style.cursor = "not-allowed";
        }
    }

    // -- FUNGSI TUTUP MODAL --
    function closeModal() {
        document.getElementById("detailModal").style.display = "none";
    }
    function closeUbahJadwalModal() {
        document.getElementById("ubahJadwalModal").style.display = "none";
    }
    function closeUbahJadwalMemberModal() {
        document.getElementById("ubahJadwalMemberModal").style.display = "none";
    }

    // Tutup jika klik di luar (backdrop)
    window.onclick = function (event) {
        if (event.target.classList.contains("modal")) {
            event.target.style.display = "none";
        }
    };
</script>
    
    <script src="<?= $base_url ?>/assets/js/riwayat.js"></script>

    <?php endif; ?> </div>

<?php 
require '../include_user/footer.php'; 
?>