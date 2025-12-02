<?php
ob_start(); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 0); error_reporting(E_ALL);
require '../config/database.php';

// --- AUTO-RELEASE LOGIC ---
mysqli_query($conn, "UPDATE jadwal_detail jd JOIN booking b ON jd.id_booking = b.id_booking SET jd.status='tersedia', jd.id_booking=NULL WHERE b.status='hold' AND b.expired_at < NOW()");
mysqli_query($conn, "DELETE FROM booking WHERE status='hold' AND expired_at < NOW()");

$is_logged_in = isset($_SESSION['id_user']);
$user_id = $_SESSION['id_user'] ?? 0;
$user_nama = $_SESSION['nama'] ?? ''; 

// --- CEK ROLE USER TERBARU ---
$user_role = 'user'; 
if ($is_logged_in) {
    $q_role = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$user_id'");
    if ($row_role = mysqli_fetch_assoc($q_role)) {
        $user_role = $row_role['role'];
    }
}

// --- API BACKEND HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); 
    header('Content-Type: application/json');
    if (!$is_logged_in) { echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login.']); exit; }

    try {
        // 1. HOLD SLOT
        if ($_POST['action'] === 'hold_slot') {
            $id_waktu = $_POST['id_waktu']; $tanggal  = $_POST['tanggal']; $id_lapangan = $_POST['id_lapangan'];
            
            $q_cek = "SELECT 1 FROM jadwal_detail jd JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian LEFT JOIN booking b ON jd.id_booking = b.id_booking WHERE jd.id_jadwal_waktu = '$id_waktu' AND jh.tanggal = '$tanggal' AND jh.id_lapangan = '$id_lapangan' AND (jd.status = 'dibooking' OR (jd.status = 'hold' AND b.expired_at > NOW() AND b.id_user != '$user_id')) LIMIT 1";
            if (mysqli_num_rows(mysqli_query($conn, $q_cek)) > 0) { echo json_encode(['status' => 'error', 'message' => 'Slot baru saja diambil orang lain!']); exit; }

            mysqli_begin_transaction($conn);
            $id_booking_hold = $_SESSION['member_hold_booking_id'] ?? 0;
            $expiry = date('Y-m-d H:i:s', time() + (15 * 60)); 

            if ($id_booking_hold > 0) {
                $q_valid = mysqli_query($conn, "SELECT id_booking FROM booking WHERE id_booking = '$id_booking_hold' AND status = 'hold'");
                if (mysqli_num_rows($q_valid) == 0) $id_booking_hold = 0;
            }

            if ($id_booking_hold == 0) {
                $stmt_b = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, expired_at) VALUES (?, ?, 'member', ?, 'hold', ?)");
                $stmt_b->bind_param("iiss", $user_id, $id_lapangan, $tanggal, $expiry); $stmt_b->execute(); $id_booking_hold = $conn->insert_id; $_SESSION['member_hold_booking_id'] = $id_booking_hold;
            } else {
                $stmt_up = $conn->prepare("UPDATE booking SET expired_at = ? WHERE id_booking = ?");
                $stmt_up->bind_param("si", $expiry, $id_booking_hold); $stmt_up->execute();
            }

            $q_h = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'");
            if (mysqli_num_rows($q_h) == 0) {
                $dayEnglish = date('l', strtotime($tanggal)); $daysIndo = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $hariIndo = $daysIndo[$dayEnglish];
                $stmt_ins_h = $conn->prepare("INSERT INTO jadwal_harian (id_lapangan, tanggal, hari) VALUES (?, ?, ?)");
                $stmt_ins_h->bind_param("iss", $id_lapangan, $tanggal, $hariIndo); $stmt_ins_h->execute(); $id_harian = $conn->insert_id;
            } else { $id_harian = mysqli_fetch_assoc($q_h)['id_jadwal_harian']; }

            $q_d = mysqli_query($conn, "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian='$id_harian' AND id_jadwal_waktu='$id_waktu'");
            if (mysqli_num_rows($q_d) > 0) {
                $stmt_upd = $conn->prepare("UPDATE jadwal_detail SET status='hold', id_booking=? WHERE id_jadwal_harian=? AND id_jadwal_waktu=?");
                $stmt_upd->bind_param("iii", $id_booking_hold, $id_harian, $id_waktu); $stmt_upd->execute();
            } else {
                $stmt_ins_d = $conn->prepare("INSERT INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, id_booking) VALUES (?, ?, 'hold', ?)");
                $stmt_ins_d->bind_param("iii", $id_harian, $id_waktu, $id_booking_hold); $stmt_ins_d->execute();
            }

            if (!isset($_SESSION['member_expired_at'])) { $_SESSION['member_expired_at'] = $expiry; } else { $_SESSION['member_expired_at'] = $expiry; }
            mysqli_commit($conn); echo json_encode(['status' => 'success', 'message' => 'Slot di-hold']); exit;
        }

        // 2. UNHOLD SLOT
        if ($_POST['action'] === 'unhold_slot') {
            $id_waktu = $_POST['id_waktu']; $tanggal  = $_POST['tanggal']; $id_lapangan = $_POST['id_lapangan']; $user_id  = $_SESSION['id_user'];
            mysqli_begin_transaction($conn);
            $q_h = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'");
            if ($row_h = mysqli_fetch_assoc($q_h)) {
                $id_harian = $row_h['id_jadwal_harian'];
                mysqli_query($conn, "UPDATE jadwal_detail jd JOIN booking b ON jd.id_booking = b.id_booking SET jd.status = 'tersedia', jd.id_booking = NULL WHERE jd.id_jadwal_harian = '$id_harian' AND jd.id_jadwal_waktu = '$id_waktu' AND b.id_user = '$user_id' AND jd.status = 'hold'");
            }
            mysqli_commit($conn); echo json_encode(['status' => 'success']); exit;
        }

        // 3. RESET TIMER / CANCEL ALL
        if ($_POST['action'] === 'reset_timer') {
            if (isset($_SESSION['member_hold_booking_id'])) {
                $id_hold = $_SESSION['member_hold_booking_id'];
                mysqli_query($conn, "UPDATE jadwal_detail SET status='tersedia', id_booking=NULL WHERE id_booking='$id_hold'");
                mysqli_query($conn, "DELETE FROM booking WHERE id_booking='$id_hold'");
                unset($_SESSION['member_hold_booking_id']);
            }
            unset($_SESSION['member_expired_at']); echo json_encode(['status' => 'success']); exit;
        }

        // 4. START TIMER
        if ($_POST['action'] === 'start_timer') {
            if (!isset($_SESSION['member_expired_at'])) { $_SESSION['member_expired_at'] = date('Y-m-d H:i:s', time() + (15 * 60)); }
            $remaining = strtotime($_SESSION['member_expired_at']) - time();
            echo json_encode(['status' => 'success', 'remaining' => $remaining]); exit;
        }

        // 5. GET SLOTS (UPDATE JAM OPERASIONAL DISINI)
        if ($_POST['action'] === 'get_slots') {
            $id_lapangan = $_POST['id_lapangan']; 
            $tanggal = $_POST['tanggal'];
            $slots = [];

            // Tentukan Hari (1=Senin, 7=Minggu)
            $dayOfWeek = date('N', strtotime($tanggal)); 
            
            // Aturan Jam Operasional
            // Sabtu (6) & Minggu (7): 07:00 - 24:00
            // Senin (1) - Jumat (5): 08:00 - 23:00
            $jam_buka = '08:00:00';
            $jam_tutup = '23:00:00';

            if ($dayOfWeek >= 6) { // Weekend
                $jam_buka = '07:00:00';
                $jam_tutup = '24:00:00'; // atau '23:59:59' tergantung data di DB
            }

            // Query dengan Filter Jam
            // Note: Kita asumsikan '24:00:00' di database mungkin tidak ada, jadi kita ambil semua yg >= jam buka
            // Jika database menggunakan 00:00 untuk 24:00, perlu penyesuaian query.
            // Di sini kita pakai jam_mulai sebagai patokan.
            
            $stmt_waktu = $conn->prepare("SELECT * FROM jadwal_waktu WHERE id_lapangan = ? AND jam_mulai >= ? AND jam_selesai <= ? ORDER BY jam_mulai ASC");
            // Khusus untuk jam tutup 24:00, kita bisa handle '00:00' jika perlu, 
            // tapi biasanya di sistem booking jam terakhir itu misal 23:00-00:00.
            
            // Jika jam tutup 24:00, kita set query agak longgar di batas atas jika data jam_selesai = '00:00:00'
            if ($jam_tutup === '24:00:00') {
                 $stmt_waktu = $conn->prepare("SELECT * FROM jadwal_waktu WHERE id_lapangan = ? AND jam_mulai >= ? ORDER BY jam_mulai ASC");
                 $stmt_waktu->bind_param("is", $id_lapangan, $jam_buka);
            } else {
                 $stmt_waktu->bind_param("iss", $id_lapangan, $jam_buka, $jam_tutup);
            }
            
            $stmt_waktu->execute();
            $result_waktu = $stmt_waktu->get_result();

            while ($w = $result_waktu->fetch_assoc()) {
                $status = 'tersedia'; $is_my_hold = false;
                
                $q_cek = "SELECT jd.status, b.id_user, b.expired_at FROM jadwal_detail jd JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian LEFT JOIN booking b ON jd.id_booking = b.id_booking WHERE jd.id_jadwal_waktu = '{$w['id_jadwal_waktu']}' AND jh.tanggal = '$tanggal' AND jh.id_lapangan = '$id_lapangan' LIMIT 1";
                $res_cek = mysqli_query($conn, $q_cek);
                if (mysqli_num_rows($res_cek) > 0) {
                    $data = mysqli_fetch_assoc($res_cek);
                    if ($data['status'] === 'hold' && $data['id_user'] == $user_id && strtotime($data['expired_at']) > time()) { $is_my_hold = true; } 
                    elseif ($data['status'] === 'dibooking' || ($data['status'] === 'hold' && strtotime($data['expired_at']) > time())) { $status = 'dibooking'; }
                }
                $slots[] = ['id_waktu' => $w['id_jadwal_waktu'], 'jam' => date('H:i', strtotime($w['jam_mulai'])) . ' - ' . date('H:i', strtotime($w['jam_selesai'])), 'status' => $status, 'is_my_hold' => $is_my_hold];
            }
            echo json_encode(['status' => 'success', 'slots' => $slots]); exit;
        }

        // 6. SUBMIT FINAL
        if ($_POST['action'] === 'submit_member') {
            if (!isset($_SESSION['member_hold_booking_id'])) throw new Exception("Sesi habis. Silakan ulangi.");
            $id_booking_hold = $_SESSION['member_hold_booking_id'];
            $id_lapangan = $_POST['id_lapangan']; $paket_bulan = $_POST['paket_bulan']; $total_bayar = $_POST['total_bayar']; $metode_input = $_POST['metode_pembayaran']; $selected_slots = json_decode($_POST['selected_slots'], true);
            $metode_db = ($metode_input === 'qris') ? 'qris' : (($metode_input === 'tunai') ? 'tunai' : 'bank_transfer');

            $bukti = null; $uploadDir = __DIR__ . '/../uploads/bukti_pembayaran/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
                $bukti = "member_" . $user_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $uploadDir . $bukti);
            } else { throw new Exception("Bukti transfer wajib diupload."); }

            mysqli_begin_transaction($conn);
            usort($selected_slots, function($a, $b) { return strtotime($a['tanggal']) - strtotime($b['tanggal']); });
            $tgl_mulai = $selected_slots[0]['tanggal']; $tgl_akhir = end($selected_slots)['tanggal'];
            $stmt_m = $conn->prepare("INSERT INTO member (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
            $stmt_m->bind_param("iiissssd", $user_id, $id_lapangan, $paket_bulan, $tgl_mulai, $tgl_akhir, $bukti, $metode_db, $total_bayar); $stmt_m->execute(); $id_member = $conn->insert_id;

            mysqli_query($conn, "UPDATE users SET role='member' WHERE id_user='$user_id'"); $_SESSION['role'] = 'member';
            $stmt_up_b = $conn->prepare("UPDATE booking SET total_amount=?, payment_method=?, status='disetujui', payment_status='lunas', tipe_booking='member', expired_at=NULL WHERE id_booking=?");
            $stmt_up_b->bind_param("dsi", $total_bayar, $metode_input, $id_booking_hold); $stmt_up_b->execute();

            $q_lap = mysqli_query($conn, "SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan = '$id_lapangan'");
            $hrg = mysqli_fetch_assoc($q_lap)['harga_per_jam_member'];
            $stmt_dm = $conn->prepare("INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status) VALUES (?, ?, ?, ?, ?, ?, 'aktif')");

            foreach($selected_slots as $slot){
                 $jam_parts = explode(' - ', $slot['jam']); $stmt_dm->bind_param("iisssd", $id_member, $id_lapangan, $slot['tanggal'], $jam_parts[0], $jam_parts[1], $hrg); $stmt_dm->execute();
                 $q_h_id = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='{$slot['tanggal']}'");
                 if($row_h = mysqli_fetch_assoc($q_h_id)){ $id_h = $row_h['id_jadwal_harian']; mysqli_query($conn, "UPDATE jadwal_detail SET status='dibooking' WHERE id_jadwal_harian='$id_h' AND id_jadwal_waktu='{$slot['id_waktu']}' AND id_booking='$id_booking_hold'"); }
            }
            mysqli_commit($conn); unset($_SESSION['member_hold_booking_id']); unset($_SESSION['member_expired_at']);
            echo json_encode(['status'=>'success', 'message'=>'Pendaftaran Berhasil!']); exit;
        }
    } catch (Exception $e) { mysqli_rollback($conn); echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); exit; }
}
ob_end_flush();
$lapangans = []; $q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'"); while($r = mysqli_fetch_assoc($q)){ $lapangans[] = $r; }
?>

<?php require '../include_user/header.php'; ?>
<link rel="stylesheet" href="member.css">

<div id="memberTimerBar" class="hidden fixed top-[90px] left-1/2 transform -translate-x-1/2 z-50 flex items-center gap-3 animate-bounce-in">
    <div class="bg-white/90 backdrop-blur-md shadow-2xl rounded-full pl-6 pr-2 py-2 border border-amber-100 flex items-center gap-4 ring-4 ring-amber-50/50">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:block">Selesaikan dalam:</div>
        <div class="flex items-center gap-2 bg-amber-600 text-white px-4 py-2 rounded-full shadow-lg transition-all duration-300" id="timerContainer">
            <i class="fa-regular fa-clock text-sm animate-pulse"></i>
            <span id="countdownDisplay" class="font-mono font-bold text-lg tracking-widest">00:00</span>
        </div>
        
        <button onclick="confirmCancelBooking()" class="ml-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-full text-xs font-bold transition-all border border-red-100 flex items-center gap-2 group">
            <span class="hidden sm:inline">Batal</span>
            <i class="fa-solid fa-xmark text-sm group-hover:rotate-90 transition-transform"></i>
        </button>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 py-12">
    
    <div class="relative bg-slate-900 rounded-[2rem] p-10 md:p-16 mb-16 overflow-hidden text-white shadow-2xl shadow-slate-900/20">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-amber-600/20 rounded-full blur-[100px] -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-yellow-600/10 rounded-full blur-[80px] -ml-20 -mb-20"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-12">
            <div class="max-w-2xl">
                <div class="flex items-center gap-3 mb-6">
                    <span class="bg-amber-500 text-slate-900 text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-widest shadow-lg shadow-amber-500/20">VIP Access</span>
                    <span class="text-slate-400 text-sm font-medium">Rush Badminton Academy</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold font-sans mb-6 leading-tight">
                    Upgrade to <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-amber-500">Premium</span>
                </h1>
                <p class="text-slate-300 text-lg mb-8 font-light leading-relaxed max-w-xl">
                    Nikmati prioritas booking, harga eksklusif, dan jaminan jadwal rutin tanpa perlu berebut slot setiap minggunya.
                </p>
                
                <div class="flex flex-wrap gap-4 text-sm font-medium text-slate-300">
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10 hover:bg-white/10 transition-colors">
                        <i class="fa-solid fa-check text-amber-400"></i> Hemat Biaya
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10 hover:bg-white/10 transition-colors">
                        <i class="fa-solid fa-check text-amber-400"></i> Jadwal Pasti
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10 hover:bg-white/10 transition-colors">
                        <i class="fa-solid fa-check text-amber-400"></i> Prioritas
                    </div>
                </div>
            </div>
            
            <div class="hidden md:block relative group">
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-400 to-yellow-600 rounded-[2rem] blur-2xl opacity-30 group-hover:opacity-50 transition-opacity duration-500"></div>
                
                <div class="relative w-80 bg-slate-800/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl transition-transform duration-500 hover:-translate-y-2 overflow-hidden">
                    
                    <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-b from-white/5 to-transparent"></div>

                    <div class="relative z-10 flex flex-col items-center text-center mt-4">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-yellow-300 to-amber-600 p-1 shadow-lg shadow-amber-500/30 mb-4 group-hover:scale-110 transition-transform duration-300">
                            <div class="w-full h-full rounded-full bg-slate-900 flex items-center justify-center border border-white/10">
                                <i class="fa-solid fa-crown text-4xl text-transparent bg-clip-text bg-gradient-to-br from-yellow-300 to-amber-500"></i>
                            </div>
                        </div>

                        <h2 class="text-2xl font-bold text-white tracking-tight">RUSH VIP</h2>
                        
                        <div class="flex items-center gap-2 mt-1 mb-6">
                            <?php if($user_role === 'member'): ?>
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span>
                                <p class="text-xs font-mono text-amber-200/80 uppercase tracking-widest font-semibold">Active Member Status</p>
                            <?php else: ?>
                                <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.8)]"></span>
                                <p class="text-xs font-mono text-slate-400 uppercase tracking-widest font-semibold">Not A Member</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-xl p-4 border border-white/5 relative z-10">
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-1 text-center">Member Name</div>
                        <div class="text-lg font-bold text-white text-center truncate"><?= $user_nama ?: 'Guest User' ?></div>
                        
                        <div class="h-px w-full bg-white/10 my-3"></div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-3 text-xs text-slate-300">
                                <div class="w-5 h-5 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-500"><i class="fa-solid fa-bolt"></i></div>
                                <span>Prioritas Booking</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-300">
                                <div class="w-5 h-5 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-500"><i class="fa-solid fa-tags"></i></div>
                                <span>Harga Spesial</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto mb-16 step-indicator-wrapper px-4">
        <div class="step-line">
             <div class="step-line-progress" id="progressLine"></div>
        </div>
        
        <div class="flex justify-between relative">
            <div class="step-item active" id="step1-ind">
                <div class="step-circle">1</div>
                <div class="text-sm font-bold text-slate-700 mt-2">Pilih Paket</div>
            </div>
            <div class="step-item" id="step2-ind">
                <div class="step-circle">2</div>
                <div class="text-sm font-bold text-slate-700 mt-2">Atur Jadwal</div>
            </div>
            <div class="step-item" id="step3-ind">
                <div class="step-circle">3</div>
                <div class="text-sm font-bold text-slate-700 mt-2">Review</div>
            </div>
            <div class="step-item" id="step4-ind">
                <div class="step-circle">4</div>
                <div class="text-sm font-bold text-slate-700 mt-2">Pembayaran</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden min-h-[600px] relative">
        
        <div id="step1" class="step-content p-8 md:p-12">
            <div class="text-center mb-12">
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Pilih Durasi & Lapangan</h3>
                <p class="text-slate-500">Sesuaikan paket membership dengan kebutuhan latihan Anda.</p>
            </div>
            
            <div class="max-w-5xl mx-auto">
                <div class="mb-10 max-w-md mx-auto relative">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Lokasi Lapangan</label>
                    <div class="relative">
                        <select id="inputLapangan" class="w-full pl-5 pr-12 py-4 rounded-xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-amber-100 focus:border-amber-500 outline-none font-semibold text-slate-700 appearance-none cursor-pointer transition-all">
                            <?php foreach($lapangans as $lap): ?>
                                <option value="<?= $lap['id_lapangan'] ?>" data-harga="<?= $lap['harga_per_jam_member'] ?>">
                                    <?= htmlspecialchars($lap['nama_lapangan']) ?> — Rp <?= number_format($lap['harga_per_jam_member'],0,',','.') ?>/jam
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 transform -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <label class="cursor-pointer relative group w-full">
                        <input type="radio" name="paket" value="1" data-quota="4" class="peer sr-only" checked>
                        <div class="package-card h-full p-8 rounded-3xl border border-slate-200 bg-white peer-checked:border-amber-500 peer-checked:ring-4 peer-checked:ring-amber-50 relative overflow-hidden flex flex-col">
                            <div class="mb-6">
                                <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-600 text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-stopwatch"></i>
                                </div>
                                <h4 class="font-bold text-2xl text-slate-800">1 Bulan</h4>
                                <p class="text-slate-500 text-sm mt-1">Short Term</p>
                            </div>
                            <div class="mt-auto">
                                <div class="flex items-baseline gap-1 mb-4">
                                    <span class="text-3xl font-bold text-slate-900">4x</span>
                                    <span class="text-sm text-slate-500">pertemuan</span>
                                </div>
                                <div class="w-full py-3 bg-slate-50 rounded-xl text-slate-600 font-bold text-center text-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">Pilih Paket</div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer relative group w-full">
                        <input type="radio" name="paket" value="2" data-quota="8" class="peer sr-only">
                        <div class="package-card h-full p-8 rounded-3xl border border-slate-200 bg-white peer-checked:border-amber-500 peer-checked:ring-4 peer-checked:ring-amber-50 relative overflow-hidden flex flex-col transform md:-translate-y-4 shadow-xl shadow-amber-100 z-10">
                            <div class="absolute top-0 right-0 bg-amber-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-bl-xl uppercase tracking-wider">Most Popular</div>
                            <div class="mb-6">
                                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <h4 class="font-bold text-2xl text-slate-800">2 Bulan</h4>
                                <p class="text-slate-500 text-sm mt-1">Best Balance</p>
                            </div>
                            <div class="mt-auto">
                                <div class="flex items-baseline gap-1 mb-4">
                                    <span class="text-3xl font-bold text-amber-600">8x</span>
                                    <span class="text-sm text-slate-500">pertemuan</span>
                                </div>
                                <div class="w-full py-3 bg-amber-600 rounded-xl text-white font-bold text-center text-sm shadow-lg shadow-amber-200">Pilih Paket</div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer relative group w-full">
                        <input type="radio" name="paket" value="3" data-quota="12" class="peer sr-only">
                        <div class="package-card h-full p-8 rounded-3xl border border-slate-200 bg-white peer-checked:border-amber-500 peer-checked:ring-4 peer-checked:ring-amber-50 relative overflow-hidden flex flex-col">
                            <div class="absolute top-0 right-0 bg-slate-800 text-white text-[10px] font-bold px-4 py-1.5 rounded-bl-xl uppercase tracking-wider">Best Value</div>
                            <div class="mb-6">
                                <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-amber-600 text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-crown"></i>
                                </div>
                                <h4 class="font-bold text-2xl text-slate-800">3 Bulan</h4>
                                <p class="text-slate-500 text-sm mt-1">Pro Commitment</p>
                            </div>
                            <div class="mt-auto">
                                <div class="flex items-baseline gap-1 mb-4">
                                    <span class="text-3xl font-bold text-slate-900">12x</span>
                                    <span class="text-sm text-slate-500">pertemuan</span>
                                </div>
                                <div class="w-full py-3 bg-slate-50 rounded-xl text-slate-600 font-bold text-center text-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">Pilih Paket</div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="mt-16 flex justify-end">
                    <button onclick="nextStep(2)" class="bg-slate-900 text-white pl-8 pr-6 py-4 rounded-full font-bold hover:bg-slate-800 transition-all shadow-xl hover:shadow-2xl hover:scale-105 flex items-center gap-3 group">
                        Lanjut Pilih Jadwal <span class="bg-white/20 rounded-full w-6 h-6 flex items-center justify-center group-hover:translate-x-1 transition-transform"><i class="fa-solid fa-chevron-right text-xs"></i></span>
                    </button>
                </div>
            </div>
        </div>

        <div id="step2" class="step-content hidden p-6 md:p-8 h-full">
            <div class="flex flex-col lg:flex-row gap-8 h-full">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-slate-800 mb-6">Ketersediaan Jadwal</h3>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-blue-600 mt-1"></i>
                        <div class="text-sm text-blue-800">
                            <strong>Note:</strong> Pilih 1 slot jadwal untuk setiap minggunya. Slot yang sudah dipilih akan dikunci sementara selama 15 menit.
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal Main</label>
                        <input type="date" id="inputTanggal" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 outline-none transition-all cursor-pointer text-slate-700 font-medium" min="<?= date('Y-m-d') ?>">
                        
                        <div id="slotContainer" class="min-h-[200px] mt-6">
                            <div class="flex flex-col items-center justify-center h-40 text-slate-400 border-2 border-dashed border-slate-100 rounded-xl">
                                <i class="fa-regular fa-calendar-days text-3xl mb-2"></i>
                                <p class="text-sm">Pilih tanggal untuk melihat ketersediaan lapangan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:w-[350px] border-l border-slate-100 pl-0 lg:pl-8">
                    <div class="sticky top-6">
                        <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-xl shadow-slate-900/10 flex flex-col h-[500px]">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-white/10">
                                <h4 class="font-bold text-lg">Keranjang Slot</h4>
                                <div class="px-3 py-1 bg-white/10 rounded-full text-xs font-mono">
                                    <span id="countSelected" class="text-amber-400 font-bold">0</span>/<span id="maxQuota">0</span>
                                </div>
                            </div>
                            
                            <div id="selectedList" class="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-2">
                                <p class="text-xs text-white/40 text-center mt-10">Belum ada jadwal dipilih.</p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-white/10">
                                <button onclick="nextStep(3)" id="btnToStep3" class="w-full bg-amber-600 text-white py-4 rounded-xl font-bold hover:bg-amber-500 transition-all opacity-50 cursor-not-allowed shadow-lg shadow-amber-900/50 mb-3" disabled>
                                    Lanjut Review
                                </button>
                                <button onclick="resetTimer()" class="w-full text-white/50 text-xs hover:text-white py-2 transition-colors">
                                    Batal & Reset Pilihan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="step3" class="step-content hidden p-8 md:p-12">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-10">
                    <h3 class="text-2xl font-bold text-slate-800">Review Pesanan</h3>
                    <p class="text-slate-500">Pastikan jadwal sudah benar sebelum lanjut ke pembayaran.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-12">
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 font-bold text-slate-700">Rincian Slot</div>
                        <div class="max-h-[350px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-white text-xs text-slate-400 uppercase">
                                    <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Tanggal</th><th class="px-6 py-3">Jam</th></tr>
                                </thead>
                                <tbody id="reviewTableBody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                        <div class="bg-slate-900 px-6 py-5 flex justify-between items-center text-white">
                            <span class="text-sm font-medium opacity-80">Total Tagihan</span>
                            <span class="text-xl font-bold text-amber-400" id="reviewTotalPrice">Rp 0</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-slate-700 mb-4">Metode Pembayaran</h4>
                        <div class="space-y-4">
                            <label class="flex items-center p-4 border border-slate-200 rounded-xl cursor-pointer hover:border-amber-500 hover:ring-1 hover:ring-amber-500 transition-all bg-white shadow-sm group">
                                <input type="radio" name="metode" value="qris" class="w-5 h-5 text-amber-600 focus:ring-amber-500 border-gray-300" checked>
                                <div class="ml-4 flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-800 group-hover:text-amber-600 transition-colors">QRIS (Instant)</span>
                                        <i class="fa-solid fa-qrcode text-slate-400 text-xl"></i>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Scan menggunakan GoPay, OVO, Dana, dll.</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border border-slate-200 rounded-xl cursor-pointer hover:border-amber-500 hover:ring-1 hover:ring-amber-500 transition-all bg-white shadow-sm group">
                                <input type="radio" name="metode" value="bca" class="w-5 h-5 text-amber-600 border-gray-300">
                                <div class="ml-4 flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-800 group-hover:text-amber-600 transition-colors">Bank BCA</span>
                                        <i class="fa-solid fa-building-columns text-slate-400 text-xl"></i>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Verifikasi manual</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border border-slate-200 rounded-xl cursor-pointer hover:border-amber-500 hover:ring-1 hover:ring-amber-500 transition-all bg-white shadow-sm group">
                                <input type="radio" name="metode" value="mandiri" class="w-5 h-5 text-amber-600 border-gray-300">
                                <div class="ml-4 flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-800 group-hover:text-amber-600 transition-colors">Bank Mandiri</span>
                                        <i class="fa-solid fa-building-columns text-slate-400 text-xl"></i>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Verifikasi manual</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="mt-10 flex gap-4">
                            <button onclick="prevStep(2)" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 hover:text-slate-900 transition-colors">Kembali</button>
                            <button onclick="nextStep(4)" class="flex-1 bg-amber-600 text-white py-3 rounded-xl font-bold hover:bg-amber-700 shadow-lg shadow-amber-200 transition-all transform active:scale-95">Bayar Sekarang</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="step4" class="step-content hidden p-8 md:p-12">
            <div class="max-w-xl mx-auto text-center">
                <div class="mb-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-3xl mx-auto mb-4 animate-bounce">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800">Selesaikan Pembayaran</h3>
                    <p class="text-slate-500">Silakan transfer sesuai nominal di bawah ini.</p>
                </div>

                <div id="paymentInstruction" class="mb-8"></div>

                <div class="text-left mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Upload Bukti Transfer</label>
                    <div class="upload-box group relative overflow-hidden" onclick="document.getElementById('inputBukti').click()">
                        <div class="relative z-10">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-400 mb-3 group-hover:text-amber-500 transition-colors"></i>
                            <p class="text-sm text-slate-600 font-medium" id="fileName">Klik untuk pilih file (JPG/PNG)</p>
                            <p class="text-xs text-slate-400 mt-1">Max size 2MB</p>
                        </div>
                        <input type="file" id="inputBukti" class="hidden" accept="image/*,application/pdf" onchange="document.getElementById('fileName').textContent = this.files[0].name; document.getElementById('fileName').classList.add('text-amber-600', 'font-bold');">
                    </div>
                </div>

                <div class="flex gap-4">
                    <button onclick="prevStep(3)" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition-colors">Kembali</button>
                    <button onclick="submitMember()" id="btnFinalSubmit" class="flex-1 bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-slate-800 shadow-xl shadow-slate-900/20 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
</script>

<script src="member.js"></script>
<?php require '../include_user/footer.php'; ?>