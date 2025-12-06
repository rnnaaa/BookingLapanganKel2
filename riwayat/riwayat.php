<?php
session_start();
require '../config/database.php';
require '../include_user/header.php';

// CSS Versioning
echo '<link rel="stylesheet" href="./riwayat.css?v=' . time() . '">';

$user_id = $_SESSION['id_user'] ?? null;
$is_logged_in = ($user_id && $user_id != 1); 

$bookings = [];
$memberBookings = [];

// Helper Tanggal Indo
function formatDateIndo($tanggal) {
    $hari_array = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $bulan_array = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
    $dateObj = new DateTime($tanggal);
    return $hari_array[$dateObj->format('l')] . ", " . $dateObj->format('j') . " " . $bulan_array[$dateObj->format('F')] . " " . $dateObj->format('Y');
}

// Config Status
function getStatusConfig($status_booking, $payment_status, $status_pembatalan) {
    // 1. Cek Pembatalan Dulu
    if ($status_pembatalan == 'pending') return ['class' => 'status-pill yellow', 'text' => 'PENGAJUAN PEMBATALAN'];
    if ($status_pembatalan == 'approved') return ['class' => 'status-pill red', 'text' => 'REFUND DISETUJUI'];
    if ($status_pembatalan == 'rejected') return ['class' => 'status-pill gray', 'text' => 'REFUND DITOLAK'];

    // 2. Status Booking Normal
    if ($status_booking == 'dibatalkan') return ['class' => 'status-pill red', 'text' => 'DIBATALKAN'];
    if ($status_booking == 'ditolak') return ['class' => 'status-pill red', 'text' => 'DITOLAK'];
    if ($status_booking == 'selesai') return ['class' => 'status-pill gray', 'text' => 'SELESAI'];
    
    // 3. Status Pembayaran
    if ($payment_status == 'lunas') return ['class' => 'status-pill green', 'text' => 'LUNAS'];
    if ($payment_status == 'dp_bayar') return ['class' => 'status-pill blue', 'text' => 'DP BELUM LUNAS'];
    if ($payment_status == 'menunggu_verifikasi') return ['class' => 'status-pill yellow', 'text' => 'MENUNGGU VERIFIKASI'];
    if ($payment_status == 'belum_bayar') return ['class' => 'status-pill orange', 'text' => 'BELUM BAYAR'];

    return ['class' => 'status-pill gray', 'text' => strtoupper($status_booking)];
}

if ($is_logged_in) {
    // QUERY REGULER (Join tabel pembatalan untuk cek status)
    $qReguler = "
        SELECT 
            db.id_detail_booking AS id_sesi,
            b.id_booking, b.tanggal, b.status AS status_booking, b.payment_status,
            b.dp_amount, b.total_amount, b.remaining_amount,
            l.nama_lapangan, l.id_lapangan,
            jw.jam_mulai, jw.jam_selesai, db.harga AS harga_sesi,
            (SELECT COUNT(*) FROM history_ubah_jadwal h WHERE h.id_detail_booking = db.id_detail_booking AND h.tipe = 'reguler') AS sudah_ubah,
            pb.status AS status_pembatalan,
            u.nama as nama_user, u.username
        FROM detail_booking db
        JOIN booking b ON db.id_booking = b.id_booking
        JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        JOIN users u ON b.id_user = u.id_user
        LEFT JOIN pembatalan_booking pb ON db.id_detail_booking = pb.id_detail_booking
        WHERE b.id_user = ? AND b.tipe_booking = 'reguler'
        ORDER BY b.created_at DESC, db.id_detail_booking ASC
    ";
    $stmt = $conn->prepare($qReguler);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // QUERY MEMBER
    $qMember = "
        SELECT 
            m.id_member, m.id_lapangan, m.id_user, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir,
            m.total_bayar, m.status, l.nama_lapangan, u.nama AS nama_pengguna, u.username,
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
                <i class="fa-solid fa-list-check"></i> Aktivitas Anda
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
                <div class="text-3xl font-bold font-poppins text-yellow-300"><?= count($bookings) ?></div>
                <div class="text-xs text-white/80 uppercase tracking-wider font-semibold">Total Booking</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl text-center min-w-[120px] shadow-sm transform hover:scale-105 transition-transform">
                <div class="text-3xl font-bold font-poppins text-green-300"><?= !empty($memberBookings) && $memberBookings[0]['status'] == 'aktif' ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>' ?></div>
                <div class="text-xs text-white/80 uppercase tracking-wider font-semibold">Status Member</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<main class="main-content-wrapper max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-20">
    <?php if (!$is_logged_in): ?>
        <div class="access-card mt-12">
            <div class="access-icon-wrapper"><i class="fa-solid fa-shield-halved"></i></div>
            <h2 class="access-title">Akses Halaman Riwayat</h2>
            <p class="access-desc">Silakan masuk untuk melihat riwayat booking, mengubah jadwal lapangan, atau mengajukan pembatalan.</p>
            <div class="access-actions">
                <a href="../auth/login.php" class="btn btn-login"><i class="fa-solid fa-right-to-bracket"></i> Masuk</a>
                <a href="../auth/register.php" class="btn btn-register"><i class="fa-solid fa-user-plus"></i> Daftar</a>
            </div>
        </div>
    <?php else: ?>
        <div class="tabs-container"><div class="tabs"><button class="tab-button active" data-tab="booking">Booking Reguler</button><button class="tab-button" data-tab="member">Membership</button></div></div>

        <div id="booking-tab" class="tab-content active">
            <?php if (empty($bookings)): ?>
                 <div class="empty-card">
                    <div class="empty-icon-wrapper"><i class="fa-regular fa-calendar-plus"></i></div>
                    <h3 class="empty-title">Belum Ada Jadwal Main</h3>
                    <p class="empty-desc">Yuk, pesan lapangan untuk latihan atau tanding sekarang!</p>
                    <a href="../BookingPengguna/booking.php" class="btn btn-primary-action"><i class="fa-solid fa-plus"></i> Booking Sekarang</a>
                </div>
            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($bookings as $row): 
                        // 1. Format Tanggal
                        $formattedDate = formatDateIndo($row['tanggal']);
                        $bookingIdDisplay = "RGLR" . date('md', strtotime($row['tanggal'])) . "-" . substr($row['jam_mulai'],0,2) . substr($row['jam_mulai'],3,2);
                        $stConfig = getStatusConfig($row['status_booking'], $row['payment_status'], $row['status_pembatalan']);

                        // 2. Waktu
                        $jamMain = $row['tanggal'] . ' ' . $row['jam_mulai'];
                        $rentangJam = substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5);

                        // 3. Validasi Tombol
                        $now = new DateTime();
                        try { $limitBatal = (new DateTime($jamMain))->sub(new DateInterval('PT5H')); } 
                        catch (Exception $e) { $limitBatal = $now; }
                        
                        $is_active_booking = !in_array($row['status_booking'], ['selesai', 'dibatalkan', 'ditolak']);
                        $is_pending_cancel = ($row['status_pembatalan'] != null); 
                        $sudahUbah = ($row['sudah_ubah'] > 0);
                        
                        $can_edit = ($now < $limitBatal && $is_active_booking && !$sudahUbah && !$is_pending_cancel);
                        $can_cancel = ($now < $limitBatal && $is_active_booking && !$is_pending_cancel);

                        // ============================================================
                        // 4. [FIX] LOGIKA HITUNG DANA REFUND
                        // ============================================================
                        $refundAmount = 0;
                        $hargaSesi = (float)$row['harga_sesi'];
                        $totalTagihan = (float)$row['total_amount'];
                        $totalDP = (float)$row['dp_amount'];
                        $statusBayar = strtolower($row['payment_status']);

                        if ($statusBayar == 'lunas' || $statusBayar == 'selesai') {
                            // Jika Lunas, kembalikan seharga sesi itu
                            $refundAmount = $hargaSesi;
                        } else {
                            // Jika DP, hitung proporsional. 
                            // Jika cuma 1 sesi, maka Refund = DP yang dibayar.
                            if ($totalTagihan > 0) {
                                $ratio = $totalDP / $totalTagihan;
                                $refundAmount = $hargaSesi * $ratio;
                            } else {
                                $refundAmount = $totalDP; // Fallback jika error
                            }
                        }
                        // Pastikan tidak koma/pecahan aneh
                        $refundAmount = floor($refundAmount);
                        // ============================================================
                    ?>
                    
                    <div class="card card-reguler">
                        <div class="card-header">
                            <div class="header-left">
                                <h3 class="venue-name"><?= htmlspecialchars($row['nama_lapangan']) ?> <span class="badge-type reguler">REGULER</span></h3>
                                <div class="booking-meta">ID: <span class="font-mono"><?= $bookingIdDisplay ?></span></div>
                            </div>
                            <div class="header-right"><span class="<?= $stConfig['class'] ?>"><?= $stConfig['text'] ?></span></div>
                        </div>

                        <div class="card-body">
                            <div class="data-row"><div class="data-label">Tanggal:</div><div class="data-value"><?= $formattedDate ?></div></div>
                            <div class="data-row"><div class="data-label">Jam Main:</div><div class="data-value font-bold text-slate-700"><?= $rentangJam ?></div></div>
                            <div class="data-row"><div class="data-label">Pemesan:</div><div class="data-value"><?= htmlspecialchars($row['nama_user']) ?></div></div>
                            <div class="data-row">
                                <div class="data-label">Status Bayar:</div>
                                <div class="data-value">
                                    <?php if($statusBayar == 'lunas'): ?>
                                        <span class="text-green font-bold">LUNAS (Rp <?= number_format($hargaSesi,0,',','.') ?>)</span>
                                    <?php else: ?>
                                        <span class="text-blue-600 font-bold">DP (Rp <?= number_format($refundAmount,0,',','.') ?> terbayar)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="footer-info">
                                <?php if ($row['status_pembatalan'] == 'pending'): ?>
                                    <div class="info-box warning">Sedang dalam proses verifikasi admin.</div>

                                <?php elseif ($sudahUbah): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Sudah Reschedule
                                    </span>
                                <?php elseif ($can_cancel || $can_edit): ?>
                                    <div class="info-box">
                                        <?php if ($can_edit): ?>
                                            Bisa ubah jadwal & batalkan sebelum: <strong><?= $limitBatal->format('d M Y, H:i') ?></strong>
                                        <?php else: ?>
                                            Bisa dibatalkan sebelum: <strong><?= $limitBatal->format('d M Y, H:i') ?></strong>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="footer-actions">
                                <button class="btn-solid blue" onclick="openDetailBooking(<?= (int)$row['id_booking'] ?>)"><i class="fa-solid fa-eye"></i> Detail</button>
                                
                                <?php if ($can_edit): ?>
                                    <button class="btn-solid orange" onclick="openUbahJadwal(<?= (int)$row['id_sesi'] ?>, <?= (int)$row['id_lapangan'] ?>)"><i class="fa-solid fa-calendar-days"></i> Ubah Jadwal</button>
                                <?php endif; ?>

                                <?php if ($can_cancel): ?>
                                    <button class="btn-solid red" onclick="openAjukanBatal(
                                        <?= (int)$row['id_sesi'] ?>, 
                                        '<?= htmlspecialchars($row['nama_lapangan']) ?>', 
                                        '<?= htmlspecialchars($formattedDate) ?>', 
                                        '<?= htmlspecialchars($rentangJam) ?>', 
                                        <?= (int)$refundAmount ?> 
                                    )">
                                        <i class="fa-solid fa-ban"></i> Batalkan
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
                    <div class="empty-icon-wrapper member-icon"><i class="fa-solid fa-crown"></i></div>
                    <h3 class="empty-title">Belum Jadi Member?</h3>
                    <p class="empty-desc">Dapatkan harga spesial dan prioritas booking.</p>
                    <a href="../Member/member.php" class="btn btn-outline-action">Lihat Paket Member</a>
                </div>
            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($memberBookings as $mem): 
                        $start = date('d M Y', strtotime($mem['tanggal_mulai']));
                        $end = date('d M Y', strtotime($mem['tanggal_berakhir']));
                        $sisaUbah = 3 - $mem['used_reschedule']; if ($sisaUbah < 0) $sisaUbah = 0;
                        $memIdDisplay = "MMBR" . str_pad($mem['id_member'], 8, '0', STR_PAD_LEFT);
                        $statusClass = ($mem['status'] == 'aktif') ? 'status-pill green' : 'status-pill gray';
                        $statusText = ($mem['status'] == 'aktif') ? 'AKTIF' : 'NONAKTIF';
                    ?>
                    <div class="card card-member">
                        <div class="card-header">
                            <div class="header-left">
                                <h3 class="venue-name"><?= htmlspecialchars($mem['nama_lapangan']) ?> <span class="badge-type member">MEMBER</span></h3>
                                <div class="booking-meta">ID: <span class="font-mono"><?= $memIdDisplay ?></span></div>
                            </div>
                            <div class="header-right"><span class="<?= $statusClass ?>"><?= $statusText ?></span></div>
                        </div>
                        <div class="card-body">
                            <div class="data-row"><div class="data-label">Durasi:</div><div class="data-value"><?= $mem['durasi_bulan'] ?> bulan</div></div>
                            <div class="data-row"><div class="data-label">Periode:</div><div class="data-value"><?= $start ?> - <?= $end ?></div></div>
                            <div class="data-row"><div class="data-label">Pemesan:</div><div class="data-value"><?= htmlspecialchars($row['nama_user']) ?></div></div>
                            <div class="data-row"><div class="data-label">Total Bayar:</div><div class="data-value text-green font-bold">Rp <?= number_format($mem['total_bayar'], 0, ',', '.') ?></div></div>
                            <div class="data-row"><div class="data-label">Sisa Ubah Jadwal:</div><div class="data-value font-bold text-blue-600"><?= $sisaUbah ?> dari 3 kali</div></div>
                            <div class="data-row"><div class="data-label">Jadwal Terjadwal:</div><div class="data-value"><?= $mem['total_sesi'] ?> sesi</div></div>
                        </div>
                        <div class="card-footer">
                            <div class="footer-info">
                                <?php 
                                    // Untuk member, tampilkan info ubah jadwal dengan deadline
                                    if ($mem['status'] == 'aktif' && $sisaUbah > 0):
                                        // Fetch upcoming session untuk member ini
                                        $qSesi = "SELECT tanggal_booking, jam_mulai FROM member_jadwal WHERE id_member = ? AND tanggal_booking >= CURDATE() AND status = 'aktif' ORDER BY tanggal_booking ASC LIMIT 1";
                                        $stmtSesi = $conn->prepare($qSesi);
                                        $stmtSesi->bind_param("i", $mem['id_member']);
                                        $stmtSesi->execute();
                                        $sesiRes = $stmtSesi->get_result()->fetch_assoc();
                                        
                                        if ($sesiRes):
                                            try {
                                                $jamSesi = new DateTime($sesiRes['tanggal_booking'] . ' ' . $sesiRes['jam_mulai']);
                                                $deadlineUbah = (new DateTime($sesiRes['tanggal_booking'] . ' ' . $sesiRes['jam_mulai']))->sub(new DateInterval('PT5H'));
                                                $sesiNum = 1; // Ini bisa di-improve kalau butuh nomor sesi yang akurat
                                ?>
                                    <div class="info-box">
                                        Sesi 1 bisa diubah jadwal sebelum: <strong><?= $deadlineUbah->format('d M Y, H:i') ?></strong>
                                    </div>
                                <?php 
                                            } catch (Exception $e) {}
                                        endif;
                                    elseif ($mem['status'] == 'aktif' && $mem['used_reschedule'] > 0):
                                ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Sudah Reschedule (<?= $mem['used_reschedule'] ?> dari 3 kali)
                                    </span>
                                <?php 
                                    endif; 
                                ?>
                            </div>
                            <div class="footer-actions">
                                <button class="btn-solid blue" onclick="openDetailMember(<?= $mem['id_member'] ?>)">
                                    <i class="fa-solid fa-eye"></i> Lihat Detail
                                </button>
                                <?php if ($mem['status'] == 'aktif' && $sisaUbah > 0): ?>
                                    <button class="btn-solid orange" onclick="openUbahJadwalMember(
                                        <?= (int)$mem['id_member'] ?>, 
                                        <?= (int)$mem['id_lapangan'] ?>, 
                                        <?= (int)$sisaUbah ?>, 
                                        '<?= htmlspecialchars($mem['nama_pengguna'], ENT_QUOTES) ?>', 
                                        <?= (int)$mem['id_user'] ?>
                                    )">
                                        <i class="fa-solid fa-calendar-days"></i> Ubah Jadwal
                                    </button>
                                <?php elseif ($sisaUbah == 0): ?>
                                    <span class="text-xs text-red-500 font-bold px-3 py-2 bg-red-50 rounded">Kuota ubah habis (3/3)</span>
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
        <h3 class="text-xl font-bold mb-4">Detail Transaksi</h3>
        <div id="detailContent" class="space-y-3 text-sm"></div>
        <div id="dynamic-auth-area" class="text-center pt-4 border-t border-slate-100 mt-4" style="display:none;"></div>
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
        <h3 class="text-xl font-bold mb-4 text-red-600 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> Pengajuan Pembatalan
        </h3>
        <form id="formBatal">
            <input type="hidden" id="batal_id_sesi" name="id_sesi">
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-100 mb-4">
                <p class="text-sm text-red-800 font-semibold mb-2">Detail Jadwal yang Dibatalkan:</p>
                <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                    <li>Lapangan: <span class="font-bold" id="batal_lapangan"></span></li>
                    <li>Tanggal: <span class="font-bold" id="batal_tanggal"></span></li>
                    <li>Jam Main: <span class="font-bold" id="batal_jam"></span></li>
                </ul>
                <div class="mt-3 pt-2 border-t border-red-200">
                    <p class="text-xs text-red-600 uppercase tracking-wider font-bold">Dana Pengembalian (Estimasi)</p>
                    <p class="text-2xl font-bold text-red-700" id="batal_refund_amount">Rp 0</p>
                    <p class="text-xs text-red-500 italic">*Sesuai jumlah yang telah Anda bayarkan untuk sesi ini.</p>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Pemilik Rekening</label>
                    <input type="text" name="nama_penerima" placeholder="Contoh: Budi Santoso" class="w-full p-2.5 border rounded-lg bg-slate-50 focus:bg-white outline-none focus:border-red-400" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                     <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Bank / E-Wallet</label>
                        <select name="bank_ewallet" class="w-full p-2.5 border rounded-lg bg-slate-50 focus:bg-white outline-none focus:border-red-400" required>
                            <option value="">Pilih Bank</option>
                            <option value="BCA">BCA</option><option value="BRI">BRI</option><option value="Mandiri">Mandiri</option>
                            <option value="DANA">DANA</option><option value="OVO">OVO</option><option value="GOPAY">GOPAY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rekening</label>
                        <input type="number" name="no_rekening" placeholder="098xxxxx" class="w-full p-2.5 border rounded-lg bg-slate-50 focus:bg-white outline-none focus:border-red-400" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-solid red w-full mt-6 py-3 rounded-lg font-bold shadow-lg hover:shadow-red-200 transition-shadow">
                Kirim Pengajuan Refund
            </button>
        </form>
    </div>
</div>
<div id="modalUbahMember" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('modalUbahMember')">&times;</span>
        <h3 class="text-xl font-bold mb-2">Ubah Jadwal Member</h3>
        <p class="text-sm text-slate-500 mb-4">Nama: <span class="font-bold text-slate-700" id="member_nama_pengguna"></span></p>
        
        <form id="formUbahMember">
            <input type="hidden" id="member_id" name="id_member">
            <input type="hidden" id="member_id_lapangan" name="id_lapangan">
            <input type="hidden" id="member_id_user" name="id_user">
            
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">1. Pilih Jadwal Lama</label>
                <select id="pilih_sesi_lama" name="id_member_jadwal" class="w-full p-2 border rounded bg-slate-50" required>
                    <option value="">Memuat data...</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">Hanya jadwal upcoming yang bisa diubah.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">2. Pilih Tanggal Baru</label>
                <input type="date" id="member_new_date" name="new_date" class="w-full p-2 border rounded" disabled required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">3. Pilih Jam Baru</label>
                <select id="member_new_jam" name="new_jadwal_waktu" class="w-full p-2 border rounded" disabled required>
                    <option value="">Pilih tanggal dulu...</option>
                </select>
            </div>

            <button type="submit" class="btn-solid blue w-full py-2">Simpan Perubahan Jadwal</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="riwayat.js?v=<?= time() ?>"></script>

<?php require '../include_user/footer.php'; ?>