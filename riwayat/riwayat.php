<?php
session_start();
require '../config/database.php';
require '../include_user/header.php';

// CSS Versioning agar update styling langsung terlihat
echo '<link rel="stylesheet" href="./riwayat.css?v=' . time() . '">';

$user_id = $_SESSION['id_user'] ?? null;
$is_logged_in = ($user_id && $user_id != 1); 

$bookings = [];
$memberBookings = [];

// ==========================================
// [HELPER] STATUS CONFIG (Warna & Teks)
// ==========================================
function getStatusConfig($status_booking, $payment_status) {
    // Prioritas 1: Status Booking (Batal/Selesai/Ditolak)
    if ($status_booking == 'dibatalkan') return ['class' => 'status-pill red', 'text' => 'DIBATALKAN'];
    if ($status_booking == 'ditolak') return ['class' => 'status-pill red', 'text' => 'DITOLAK'];
    if ($status_booking == 'selesai') return ['class' => 'status-pill gray', 'text' => 'SELESAI'];
    
    // Prioritas 2: Status Pembayaran (Jika booking masih aktif/disetujui)
    if ($payment_status == 'lunas') return ['class' => 'status-pill green', 'text' => 'LUNAS'];
    if ($payment_status == 'dp_bayar') return ['class' => 'status-pill blue', 'text' => 'DP DIBAYAR'];
    if ($payment_status == 'menunggu_verifikasi') return ['class' => 'status-pill yellow', 'text' => 'VERIFIKASI'];
    if ($payment_status == 'belum_bayar') return ['class' => 'status-pill orange', 'text' => 'BELUM BAYAR'];

    return ['class' => 'status-pill gray', 'text' => strtoupper($status_booking)];
}

if ($is_logged_in) {
    // ==========================================
    // 1. QUERY BOOKING REGULER
    // ==========================================
    $qReguler = "
        SELECT 
            db.id_detail_booking AS id_sesi,
            b.id_booking, b.tanggal, b.status AS status_booking, b.payment_status,
            b.dp_amount, b.total_amount, b.remaining_amount,
            l.nama_lapangan, l.id_lapangan,
            jw.jam_mulai, jw.jam_selesai, db.harga AS harga_sesi,
            (SELECT COUNT(*) FROM history_ubah_jadwal h WHERE h.id_detail_booking = db.id_detail_booking AND h.tipe = 'reguler') AS sudah_ubah,
            p.status AS status_pembatalan,
            u.nama as nama_user, u.username
        FROM detail_booking db
        JOIN booking b ON db.id_booking = b.id_booking
        JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        JOIN users u ON b.id_user = u.id_user
        LEFT JOIN pembatalan_booking p ON db.id_detail_booking = p.id_detail_booking
        WHERE b.id_user = ? AND b.tipe_booking = 'reguler'
        ORDER BY b.created_at DESC, db.id_detail_booking ASC
    ";
    $stmt = $conn->prepare($qReguler);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ==========================================
    // 2. QUERY MEMBER
    // ==========================================
    $qMember = "
        SELECT 
            m.id_member, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir,
            m.total_bayar, m.status, l.nama_lapangan, u.nama AS nama_pemesan, u.username,
            (SELECT COUNT(*) FROM member_jadwal mj WHERE mj.id_member = m.id_member) AS total_sesi,
            (SELECT COUNT(*) FROM history_ubah_jadwal h WHERE h.id_member = m.id_member AND h.tipe = 'member') AS used_reschedule
        FROM member m
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        JOIN users u ON m.id_user = u.id_user
        WHERE m.id_user = ? 
        ORDER BY m.created_at DESC
    ";
    $stmtM = $conn->prepare($qMember);
    $stmtM->bind_param("i", $user_id);
    $stmtM->execute();
    $memberBookings = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<section class="relative w-full bg-gradient-to-r from-primary to-primaryDark text-white overflow-hidden shadow-lg mb-[-2rem] pb-20 pt-10">
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-20%] left-[-5%] w-48 h-48 bg-yellow-400/20 rounded-full blur-2xl animate-pulse-slow"></div>
        <div class="absolute top-1/4 left-1/3 w-20 h-20 bg-white/5 rounded-full blur-xl animate-float"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
        
        <div class="text-center md:text-left">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 text-xs font-semibold border border-white/10 mb-4 text-yellow-300">
                <i class="fa-solid fa-list-check"></i>
                Aktivitas Anda
            </div>
            <h1 class="font-sans font-bold text-4xl md:text-5xl lg:text-6xl leading-tight mb-2">
                Riwayat <span class="text-yellow-300">Booking</span>
            </h1>
            <p class="text-blue-100 text-sm md:text-base max-w-lg font-light leading-relaxed">
                Pantau jadwal bermain, status pembayaran, dan kelola membership Anda dalam satu tempat yang terintegrasi.
            </p>
        </div>

        <?php if ($is_logged_in): ?>
        <div class="hidden md:flex gap-4">
            <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl text-center min-w-[120px] shadow-sm transform hover:scale-105 transition-transform">
                <div class="text-3xl font-bold font-poppins text-yellow-300">
                    <?= count($bookings) ?>
                </div>
                <div class="text-xs text-white/80 uppercase tracking-wider font-semibold">Total Booking</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl text-center min-w-[120px] shadow-sm transform hover:scale-105 transition-transform">
                <div class="text-3xl font-bold font-poppins text-green-300">
                    <?= !empty($memberBookings) && $memberBookings[0]['status'] == 'aktif' ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>' ?>
                </div>
                <div class="text-xs text-white/80 uppercase tracking-wider font-semibold">Status Member</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<main class="main-content-wrapper max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-20">
    
    <?php if (!$is_logged_in): ?>
        
        <div class="access-card mt-12">
            <div class="access-icon-wrapper">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="access-title">Akses Halaman Riwayat</h2>
            <p class="access-desc">
                Silakan masuk untuk melihat riwayat booking, mengubah jadwal lapangan, 
                atau mengajukan pembatalan. Belum punya akun? Daftar sekarang!
            </p>
            <div class="access-actions">
                <a href="../auth/login.php" class="btn btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk
                </a>
                <a href="../auth/register.php" class="btn btn-register">
                    <i class="fa-solid fa-user-plus"></i> Daftar
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-button active" data-tab="booking">Booking Reguler</button>
                <button class="tab-button" data-tab="member">Membership</button>
            </div>
        </div>

        <div id="booking-tab" class="tab-content active">
            <?php if (empty($bookings)): ?>
                
                <div class="empty-card">
                    <div class="empty-icon-wrapper">
                        <i class="fa-regular fa-calendar-plus"></i>
                    </div>
                    <h3 class="empty-title">Belum Ada Jadwal Main</h3>
                    <p class="empty-desc">
                        Sepertinya Anda belum memiliki jadwal aktif. <br>
                        Yuk, pesan lapangan untuk latihan atau tanding sekarang!
                    </p>
                    <a href="../BookingPengguna/booking.php" class="btn btn-primary-action">
                        <i class="fa-solid fa-plus"></i> Booking Sekarang
                    </a>
                </div>

            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($bookings as $row): 
                        // 1. Format Tanggal (English)
                        $dateObj = new DateTime($row['tanggal']);
                        $formattedDate = $dateObj->format('l, j F Y');
                        $bookingIdDisplay = "RGLR" . $dateObj->format('md') . "-" . str_replace(':','', substr($row['jam_mulai'],0,5));
                        
                        // 2. Config Status (Warna & Text)
                        $stConfig = getStatusConfig($row['status_booking'], $row['payment_status']);

                        // 3. Logika Batas Waktu & Aktif
                        $jamMain = $row['tanggal'] . ' ' . $row['jam_mulai'];
                        $now = new DateTime();
                        try {
                            $limitBatal = (new DateTime($jamMain))->sub(new DateInterval('PT5H')); // H-5 Jam
                        } catch (Exception $e) { $limitBatal = $now; }

                        $is_dp = ($row['payment_status'] == 'dp_bayar');
                        
                        // Syarat Dasar: Status bukan selesai/batal/ditolak
                        $is_active_booking = !in_array($row['status_booking'], ['selesai', 'dibatalkan', 'ditolak']);
                        
                        // [PENTING] Cek apakah sudah pernah ubah jadwal?
                        $sudahUbah = ($row['sudah_ubah'] > 0);

                        // Syarat Tombol Aktif
                        // - Ubah Jadwal: Waktu aman, Booking aktif, DAN BELUM PERNAH UBAH
                        $can_edit = ($now < $limitBatal && $is_active_booking && !$sudahUbah);
                        
                        // - Batal: Waktu aman, Booking aktif
                        $can_cancel = ($now < $limitBatal && $is_active_booking);
                    ?>
                    
                    <div class="card card-reguler">
                        <div class="card-header">
                            <div class="header-left">
                                <h3 class="venue-name"><?= htmlspecialchars($row['nama_lapangan']) ?> <span class="badge-type reguler">REGULER</span></h3>
                                <div class="booking-meta">
                                    ID: <span class="font-mono"><?= $bookingIdDisplay ?></span> &nbsp;<span class="text-slate-400">#<?= $row['id_booking'] ?></span>
                                </div>
                            </div>
                            <div class="header-right">
                                <span class="<?= $stConfig['class'] ?>"><?= $stConfig['text'] ?></span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="data-row">
                                <div class="data-label">Tanggal:</div>
                                <div class="data-value"><?= $formattedDate ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Jam:</div>
                                <div class="data-value"><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Pemesan:</div>
                                <div class="data-value"><?= htmlspecialchars($row['nama_user']) ?> (@<?= htmlspecialchars($row['username']) ?>)</div>
                            </div>

                            <?php if($is_dp): ?>
                                <div class="data-row">
                                    <div class="data-label">Sudah Bayar (DP):</div>
                                    <div class="data-value text-blue-600 font-bold">Rp <?= number_format($row['dp_amount'], 0, ',', '.') ?></div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Sisa Tagihan:</div>
                                    <div class="data-value text-red-600 font-bold">Rp <?= number_format($row['remaining_amount'], 0, ',', '.') ?></div>
                                </div>
                            <?php else: ?>
                                <div class="data-row">
                                    <div class="data-label">Total Lunas:</div>
                                    <div class="data-value text-green font-bold">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <div class="footer-info">
                                <?php if ($is_active_booking): ?>
                                    <?php if ($sudahUbah): ?>
                                        <div class="info-box warning">
                                            <i class="fa-solid fa-circle-exclamation"></i> Jadwal sudah diubah (Maks 1x).
                                        </div>
                                    <?php elseif ($now < $limitBatal): ?>
                                        <div class="info-box">
                                            Bisa diubah sebelum H-5 jam: <span class="countdown-timer text-primary" data-deadline="<?= $limitBatal->format('c') ?>">...</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="info-box expired">
                                            Batas waktu perubahan telah habis.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="footer-actions">
                                <button class="btn-solid blue" onclick="openDetailBooking(<?= $row['id_booking'] ?>)">
                                    <i class="fa-solid fa-eye"></i> Lihat Detail
                                </button>
                                
                                <?php if ($can_edit): ?>
                                    <button class="btn-solid orange" onclick="openUbahJadwal(<?= $row['id_sesi'] ?>, <?= $row['id_lapangan'] ?>)">
                                        <i class="fa-solid fa-calendar-days"></i> Ubah Jadwal
                                    </button>
                                <?php endif; ?>

                                <?php if ($can_cancel): ?>
                                    <button class="btn-solid red" onclick="openAjukanBatal(<?= $row['id_sesi'] ?>, '<?= htmlspecialchars($row['nama_lapangan']) ?>', '<?= $row['tanggal'] ?>', '<?= substr($row['jam_mulai'],0,5) ?>')">
                                        <i class="fa-solid fa-ban"></i> Ajukan Pembatalan
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="member-tab" class="tab-content">
            <?php if (empty($memberBookings)): ?>
                
                <div class="empty-card">
                    <div class="empty-icon-wrapper member-icon">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <h3 class="empty-title">Belum Jadi Member?</h3>
                    <p class="empty-desc">
                        Dapatkan harga spesial, prioritas booking, dan promo eksklusif 
                        dengan berlangganan membership.
                    </p>
                    <a href="../Member/member.php" class="btn btn-outline-action">
                        Lihat Paket Member
                    </a>
                </div>

            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($memberBookings as $mem): 
                        $start = date('d M Y', strtotime($mem['tanggal_mulai']));
                        $end = date('d M Y', strtotime($mem['tanggal_berakhir']));
                        
                        // Hitung sisa kuota
                        $sisa = 3 - $mem['used_reschedule']; 
                        if ($sisa < 0) $sisa = 0;

                        $memIdDisplay = "MMBR" . str_pad($mem['id_member'], 8, '0', STR_PAD_LEFT);
                        
                        $statusClass = ($mem['status'] == 'aktif') ? 'status-pill green' : 'status-pill gray';
                        $statusText = ($mem['status'] == 'aktif') ? 'AKTIF' : 'NONAKTIF';
                    ?>
                    <div class="card card-member">
                        <div class="card-header">
                            <div class="header-left">
                                <h3 class="venue-name"><?= htmlspecialchars($mem['nama_lapangan']) ?> <span class="badge-type member">MEMBER</span></h3>
                                <div class="booking-meta">
                                    ID: <span class="font-mono"><?= $memIdDisplay ?></span>
                                </div>
                            </div>
                            <div class="header-right">
                                <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="data-row">
                                <div class="data-label">Durasi:</div>
                                <div class="data-value"><?= $mem['durasi_bulan'] ?> bulan</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Periode:</div>
                                <div class="data-value"><?= $start ?> - <?= $end ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Pemesan:</div>
                                <div class="data-value"><?= htmlspecialchars($mem['nama_pemesan']) ?> (@<?= htmlspecialchars($mem['username']) ?>)</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Total Bayar:</div>
                                <div class="data-value text-green font-bold">Rp <?= number_format($mem['total_bayar'], 0, ',', '.') ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Sisa Ubah Jadwal:</div>
                                <div class="data-value font-bold text-blue-600"><?= $sisa ?> dari 3 kali</div>
                            </div>
                            <div class="data-row">
                                <div class="data-label">Jadwal Terjadwal:</div>
                                <div class="data-value"><?= $mem['total_sesi'] ?> sesi</div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="footer-info">
                                <div class="info-simple">Dapat diubah</div>
                            </div>
                            <div class="footer-actions">
                                <button class="btn-solid blue" onclick="openDetailMember(<?= $mem['id_member'] ?>)">
                                    <i class="fa-solid fa-eye"></i> Lihat Detail
                                </button>
                                
                                <?php if ($mem['status'] == 'aktif'): ?>
                                    <button class="btn-solid orange" onclick="openUbahJadwalMember(<?= $mem['id_member'] ?>)">
                                        <i class="fa-solid fa-calendar-days"></i> Ubah Jadwal
                                    </button>
                                    <button class="btn-solid red" onclick="batalkanMember(<?= $mem['id_member'] ?>)">
                                        <i class="fa-solid fa-ban"></i> Batalkan Member
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<div id="modalDetail" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('modalDetail')">&times;</span>
        <h3 class="text-xl font-bold mb-4">Detail Booking</h3>
        <div id="detailContent" class="space-y-3 text-sm"></div>
        <div id="qr-section" class="text-center pt-4 border-t border-slate-100 mt-4" style="display:none;">
            <p class="mb-2 font-bold text-slate-700">QR Code Check-in</p>
            <div id="qrcode" class="flex justify-center"></div>
        </div>
    </div>
</div>

<div id="modalUbah" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('modalUbah')">&times;</span>
        <h3 class="text-xl font-bold mb-4">Ubah Jadwal</h3>
        <form id="formUbahJadwal">
            <input type="hidden" id="ubah_id_sesi" name="id_sesi">
            <input type="hidden" id="ubah_id_lapangan" name="id_lapangan">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Pilih Tanggal Baru</label>
                <input type="date" id="new_date" name="new_date" class="w-full p-2 border rounded" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+7 days')) ?>">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Pilih Jam Baru</label>
                <select id="ubah_jam" name="new_jadwal_waktu" class="w-full p-2 border rounded" disabled>
                    <option>Pilih tanggal dulu...</option>
                </select>
            </div>
            <button type="submit" class="btn-solid blue w-full">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div id="modalBatal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('modalBatal')">&times;</span>
        <h3 class="text-xl font-bold mb-4 text-red-600">Ajukan Pembatalan</h3>
        <form id="formBatal">
            <input type="hidden" id="batal_id_sesi" name="id_sesi">
            <div class="bg-red-50 p-3 rounded mb-4 text-sm text-red-800">
                Anda akan membatalkan booking: <br>
                <strong><span id="batal_lapangan"></span></strong> <br> 
                <span id="batal_tanggal"></span>, Pukul <span id="batal_jam"></span>
            </div>
            <div class="space-y-3">
                <input type="text" name="nama_penerima" placeholder="Nama Pemilik Rekening" class="w-full p-2 border rounded" required>
                <input type="text" name="no_rekening" placeholder="Nomor Rekening" class="w-full p-2 border rounded" required>
                <select name="bank_ewallet" class="w-full p-2 border rounded" required>
                    <option value="">Pilih Bank / E-Wallet</option>
                    <option value="BCA">BCA</option><option value="BRI">BRI</option><option value="Mandiri">Mandiri</option>
                    <option value="DANA">DANA</option><option value="OVO">OVO</option><option value="GOPAY">GOPAY</option>
                </select>
            </div>
            <button type="submit" class="btn-solid red w-full mt-4">Kirim Pengajuan</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="riwayat.js?v=<?= time() ?>"></script>

<?php require '../include_user/footer.php'; ?>