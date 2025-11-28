<?php
// 1. BUFFERING & SESSION
ob_start(); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// Matikan display error agar tidak merusak JSON response di AJAX
ini_set('display_errors', 0);
error_reporting(E_ALL);

require '../config/database.php';

// Auto-release slot yang hold-nya sudah expired (> 15 menit sesuai setting baru)
// Kita bersihkan data sampah di database setiap kali halaman ini dimuat
mysqli_query($conn, "UPDATE jadwal_detail jd JOIN booking b ON jd.id_booking = b.id_booking SET jd.status='tersedia', jd.id_booking=NULL WHERE b.status='hold' AND b.expired_at < NOW()");
mysqli_query($conn, "DELETE FROM booking WHERE status='hold' AND expired_at < NOW()");

// 2. Cek Login Status
$is_logged_in = isset($_SESSION['id_user']);
$user_id = $_SESSION['id_user'] ?? 0;
$user_nama = $_SESSION['nama'] ?? ''; 

// =======================================================================
// API BACKEND (AJAX HANDLER)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    ob_clean(); 
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login.']);
        exit;
    }

    try {
        // A. HOLD SLOT (Kunci Jadwal di Database)
        if ($_POST['action'] === 'hold_slot') {
            $id_waktu = $_POST['id_waktu'];
            $tanggal  = $_POST['tanggal'];
            $id_lapangan = $_POST['id_lapangan'];

            // 1. Cek apakah slot MASIH tersedia (Double check)
            $q_cek = "SELECT 1 FROM jadwal_detail jd
                      JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                      LEFT JOIN booking b ON jd.id_booking = b.id_booking
                      WHERE jd.id_jadwal_waktu = '$id_waktu' 
                      AND jh.tanggal = '$tanggal' 
                      AND jh.id_lapangan = '$id_lapangan'
                      AND (
                          jd.status = 'dibooking' 
                          OR (jd.status = 'hold' AND b.expired_at > NOW() AND b.id_user != '$user_id')
                      ) LIMIT 1";
            
            if (mysqli_num_rows(mysqli_query($conn, $q_cek)) > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Slot baru saja diambil orang lain!']);
                exit;
            }

            mysqli_begin_transaction($conn);

            // 2. Cek/Buat Booking Dummy untuk Hold
            $id_booking_hold = $_SESSION['member_hold_booking_id'] ?? 0;
            
            // --- [UBAH DISINI] Setting Waktu Hold 15 Menit ---
            $expiry = date('Y-m-d H:i:s', time() + (15 * 60)); 
            // -------------------------------------------------

            // Validasi ID hold di session apakah masih ada di DB
            if ($id_booking_hold > 0) {
                $q_valid = mysqli_query($conn, "SELECT id_booking FROM booking WHERE id_booking = '$id_booking_hold' AND status = 'hold'");
                if (mysqli_num_rows($q_valid) == 0) $id_booking_hold = 0;
            }

            if ($id_booking_hold == 0) {
                // Buat Booking Hold Baru
                $stmt_b = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, expired_at) VALUES (?, ?, 'member', ?, 'hold', ?)");
                $stmt_b->bind_param("iiss", $user_id, $id_lapangan, $tanggal, $expiry);
                $stmt_b->execute();
                $id_booking_hold = $conn->insert_id;
                $_SESSION['member_hold_booking_id'] = $id_booking_hold;
            } else {
                // Perpanjang durasi hold
                $stmt_up = $conn->prepare("UPDATE booking SET expired_at = ? WHERE id_booking = ?");
                $stmt_up->bind_param("si", $expiry, $id_booking_hold);
                $stmt_up->execute();
            }

            // 3. Pastikan Jadwal Harian & Detail Ada
            $q_h = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'");
            if (mysqli_num_rows($q_h) == 0) {
                $dayEnglish = date('l', strtotime($tanggal));
                $daysIndo = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $hariIndo = $daysIndo[$dayEnglish];
                
                $stmt_ins_h = $conn->prepare("INSERT INTO jadwal_harian (id_lapangan, tanggal, hari) VALUES (?, ?, ?)");
                $stmt_ins_h->bind_param("iss", $id_lapangan, $tanggal, $hariIndo);
                $stmt_ins_h->execute();
                $id_harian = $conn->insert_id;
            } else {
                $id_harian = mysqli_fetch_assoc($q_h)['id_jadwal_harian'];
            }

            // 4. Update/Insert Jadwal Detail menjadi HOLD
            $q_d = mysqli_query($conn, "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian='$id_harian' AND id_jadwal_waktu='$id_waktu'");
            if (mysqli_num_rows($q_d) > 0) {
                $stmt_upd = $conn->prepare("UPDATE jadwal_detail SET status='hold', id_booking=? WHERE id_jadwal_harian=? AND id_jadwal_waktu=?");
                $stmt_upd->bind_param("iii", $id_booking_hold, $id_harian, $id_waktu);
                $stmt_upd->execute();
            } else {
                $stmt_ins_d = $conn->prepare("INSERT INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, id_booking) VALUES (?, ?, 'hold', ?)");
                $stmt_ins_d->bind_param("iii", $id_harian, $id_waktu, $id_booking_hold);
                $stmt_ins_d->execute();
            }

            // Set Session Timer jika belum ada
            if (!isset($_SESSION['member_expired_at'])) {
                $_SESSION['member_expired_at'] = $expiry;
            } else {
                // Update session timer agar sinkron dengan database
                $_SESSION['member_expired_at'] = $expiry;
            }

            mysqli_commit($conn);
            echo json_encode(['status' => 'success', 'message' => 'Slot di-hold']);
            exit;
        }

        // B. UNHOLD SLOT (Lepas Kunci)
        if ($_POST['action'] === 'unhold_slot') {
            $id_waktu = $_POST['id_waktu'];
            $tanggal  = $_POST['tanggal'];
            $id_lapangan = $_POST['id_lapangan'];
            $user_id  = $_SESSION['id_user'];

            mysqli_begin_transaction($conn);
            $q_h = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'");
            if ($row_h = mysqli_fetch_assoc($q_h)) {
                $id_harian = $row_h['id_jadwal_harian'];
                $q_release = "UPDATE jadwal_detail jd 
                              JOIN booking b ON jd.id_booking = b.id_booking
                              SET jd.status = 'tersedia', jd.id_booking = NULL 
                              WHERE jd.id_jadwal_harian = '$id_harian' 
                              AND jd.id_jadwal_waktu = '$id_waktu'
                              AND b.id_user = '$user_id' 
                              AND jd.status = 'hold'";
                mysqli_query($conn, $q_release);
            }
            mysqli_commit($conn);
            echo json_encode(['status' => 'success']);
            exit;
        }

        // C. RESET TIMER / BATAL (Lepas Semua Hold)
        if ($_POST['action'] === 'reset_timer') {
            if (isset($_SESSION['member_hold_booking_id'])) {
                $id_hold = $_SESSION['member_hold_booking_id'];
                mysqli_query($conn, "UPDATE jadwal_detail SET status='tersedia', id_booking=NULL WHERE id_booking='$id_hold'");
                mysqli_query($conn, "DELETE FROM booking WHERE id_booking='$id_hold'");
                unset($_SESSION['member_hold_booking_id']);
            }
            unset($_SESSION['member_expired_at']);
            echo json_encode(['status' => 'success']);
            exit;
        }

        // D. START TIMER
        if ($_POST['action'] === 'start_timer') {
            if (!isset($_SESSION['member_expired_at'])) {
                // --- [UBAH DISINI] Set Session Timer 15 Menit ---
                $_SESSION['member_expired_at'] = date('Y-m-d H:i:s', time() + (15 * 60));
            }
            $remaining = strtotime($_SESSION['member_expired_at']) - time();
            echo json_encode(['status' => 'success', 'remaining' => $remaining]);
            exit;
        }

        // E. GET SLOTS (Cek Status + Own Hold)
        if ($_POST['action'] === 'get_slots') {
            $id_lapangan = $_POST['id_lapangan'];
            $tanggal = $_POST['tanggal'];
            
            $slots = [];
            $q_waktu = mysqli_query($conn, "SELECT * FROM jadwal_waktu WHERE id_lapangan = '$id_lapangan' ORDER BY jam_mulai ASC");
            
            while ($w = mysqli_fetch_assoc($q_waktu)) {
                $status = 'tersedia';
                $is_my_hold = false;

                $q_cek = "SELECT jd.status, b.id_user, b.expired_at 
                          FROM jadwal_detail jd
                          JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                          LEFT JOIN booking b ON jd.id_booking = b.id_booking
                          WHERE jd.id_jadwal_waktu = '{$w['id_jadwal_waktu']}' 
                          AND jh.tanggal = '$tanggal' 
                          AND jh.id_lapangan = '$id_lapangan'
                          LIMIT 1";
                
                $res_cek = mysqli_query($conn, $q_cek);
                if (mysqli_num_rows($res_cek) > 0) {
                    $data = mysqli_fetch_assoc($res_cek);
                    // Slot di-hold user sendiri -> Tampilkan sebagai selected
                    if ($data['status'] === 'hold' && $data['id_user'] == $user_id && strtotime($data['expired_at']) > time()) {
                        $is_my_hold = true;
                    } 
                    // Slot booked/hold orang lain
                    elseif ($data['status'] === 'dibooking' || ($data['status'] === 'hold' && strtotime($data['expired_at']) > time())) {
                        $status = 'dibooking';
                    }
                }

                $slots[] = [
                    'id_waktu' => $w['id_jadwal_waktu'],
                    'jam' => date('H:i', strtotime($w['jam_mulai'])) . ' - ' . date('H:i', strtotime($w['jam_selesai'])),
                    'status' => $status,
                    'is_my_hold' => $is_my_hold
                ];
            }
            echo json_encode(['status' => 'success', 'slots' => $slots]);
            exit;
        }

        // F. SUBMIT FINAL
        if ($_POST['action'] === 'submit_member') {
            if (!isset($_SESSION['member_hold_booking_id'])) throw new Exception("Sesi habis. Silakan ulangi.");
            $id_booking_hold = $_SESSION['member_hold_booking_id'];

            $id_lapangan = $_POST['id_lapangan'];
            $paket_bulan = $_POST['paket_bulan'];
            $total_bayar = $_POST['total_bayar'];
            $metode_input = $_POST['metode_pembayaran'];
            $selected_slots = json_decode($_POST['selected_slots'], true);

            $metode_db = ($metode_input === 'qris') ? 'qris' : (($metode_input === 'tunai') ? 'tunai' : 'bank_transfer');

            // Upload Bukti
            $bukti = null;
            $uploadDir = __DIR__ . '/../uploads/bukti_pembayaran/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
                $bukti = "member_" . $user_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $uploadDir . $bukti);
            } else {
                throw new Exception("Bukti transfer wajib diupload.");
            }

            mysqli_begin_transaction($conn);

            // Insert Member
            usort($selected_slots, function($a, $b) { return strtotime($a['tanggal']) - strtotime($b['tanggal']); });
            $tgl_mulai = $selected_slots[0]['tanggal'];
            $tgl_akhir = end($selected_slots)['tanggal'];

            $stmt_m = $conn->prepare("INSERT INTO member (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
            $stmt_m->bind_param("iiissssd", $user_id, $id_lapangan, $paket_bulan, $tgl_mulai, $tgl_akhir, $bukti, $metode_db, $total_bayar);
            $stmt_m->execute();
            $id_member = $conn->insert_id;

            // Update Role
            mysqli_query($conn, "UPDATE users SET role='member' WHERE id_user='$user_id'");
            $_SESSION['role'] = 'member';

            // Finalisasi Booking Hold
            $stmt_up_b = $conn->prepare("UPDATE booking SET total_amount=?, payment_method=?, status='disetujui', payment_status='lunas', tipe_booking='member', expired_at=NULL WHERE id_booking=?");
            $stmt_up_b->bind_param("dsi", $total_bayar, $metode_input, $id_booking_hold);
            $stmt_up_b->execute();

            // Insert Member Jadwal
            $q_lap = mysqli_query($conn, "SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan = '$id_lapangan'");
            $hrg = mysqli_fetch_assoc($q_lap)['harga_per_jam_member'];
            $stmt_dm = $conn->prepare("INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status) VALUES (?, ?, ?, ?, ?, ?, 'aktif')");

            foreach($selected_slots as $slot){
                 $jam_parts = explode(' - ', $slot['jam']);
                 $stmt_dm->bind_param("iisssd", $id_member, $id_lapangan, $slot['tanggal'], $jam_parts[0], $jam_parts[1], $hrg);
                 $stmt_dm->execute();
                 
                 // Update status di jadwal detail
                 $q_h_id = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='{$slot['tanggal']}'");
                 if($row_h = mysqli_fetch_assoc($q_h_id)){
                     $id_h = $row_h['id_jadwal_harian'];
                     mysqli_query($conn, "UPDATE jadwal_detail SET status='dibooking' WHERE id_jadwal_harian='$id_h' AND id_jadwal_waktu='{$slot['id_waktu']}' AND id_booking='$id_booking_hold'");
                 }
            }

            mysqli_commit($conn);
            unset($_SESSION['member_hold_booking_id']);
            unset($_SESSION['member_expired_at']);
            echo json_encode(['status'=>'success', 'message'=>'Pendaftaran Berhasil!']);
            exit;
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        exit;
    }
}
ob_end_flush();

$lapangans = [];
$q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'");
while($r = mysqli_fetch_assoc($q)){ $lapangans[] = $r; }
?>

<?php require '../include_user/header.php'; ?>

<div id="memberTimerBar" class="hidden fixed top-[80px] left-1/2 transform -translate-x-1/2 z-50 bg-white shadow-xl rounded-full px-6 py-3 border border-slate-200 flex items-center gap-4 animate-bounce-in">
    <div class="text-sm font-semibold text-slate-600">Selesaikan dalam:</div>
    <div class="flex items-center gap-2 bg-red-600 text-white px-4 py-1.5 rounded-full shadow-md transition-all duration-300" id="timerContainer">
        <i class="fa-regular fa-clock text-sm"></i>
        <span id="countdownDisplay" class="font-mono font-bold text-base tracking-widest">00:00</span>
    </div>
</div>

<style>
    .step-item { display: flex; align-items: center; gap: 10px; opacity: 0.5; transition: all 0.3s; font-weight: 500; }
    .step-item.active { opacity: 1; font-weight: bold; color: #0b63d6; }
    .step-circle { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #64748b; transition: all 0.3s; }
    .step-item.active .step-circle { background: #0b63d6; color: white; box-shadow: 0 4px 10px rgba(11, 99, 214, 0.3); }
    .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
    .slot-btn { padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.2s; background: white; font-size: 0.9rem; }
    .slot-btn:hover:not(.disabled) { border-color: #0b63d6; background: #eff6ff; }
    .slot-btn.selected { background: #0b63d6; color: white; border-color: #0b63d6; }
    .slot-btn.disabled { background: #f1f5f9; color: #cbd5e1; cursor: not-allowed; border-color: #f1f5f9; }
    .selected-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0; font-size: 0.9rem; }
    .review-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .review-table th, .review-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .review-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
    .upload-box { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; }
    .upload-box:hover { border-color: #0b63d6; background: #eff6ff; }
    @keyframes pulse-red { 0%, 100% { background-color: #ef4444; } 50% { background-color: #dc2626; } }
    .animate-pulse-red { animation: pulse-red 1s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes bounceIn { from { transform: translate(-50%, -20px); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
    .animate-bounce-in { animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>

<main class="max-w-6xl mx-auto px-4 py-10">
    <div class="text-center mb-8">
        <span class="text-primary font-bold tracking-wider uppercase text-sm bg-blue-50 px-3 py-1 rounded-full">Membership</span>
        <h1 class="text-3xl md:text-4xl font-poppins font-bold text-slate-800 mt-3">Gabung Member</h1>
        <p class="text-slate-500 mt-2">Main rutin, lebih hemat, slot terjamin.</p>
    </div>

    <div class="flex justify-center mb-10 overflow-x-auto">
        <div class="flex items-center gap-4 min-w-max">
            <div class="step-item active" id="step1-ind"><div class="step-circle">1</div> Paket</div>
            <div class="w-8 h-0.5 bg-gray-200 rounded"></div>
            <div class="step-item" id="step2-ind"><div class="step-circle">2</div> Jadwal</div>
            <div class="w-8 h-0.5 bg-gray-200 rounded"></div>
            <div class="step-item" id="step3-ind"><div class="step-circle">3</div> Review</div>
            <div class="w-8 h-0.5 bg-gray-200 rounded"></div>
            <div class="step-item" id="step4-ind"><div class="step-circle">4</div> Bayar</div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden min-h-[500px] p-8 relative">
        
        <div id="step1" class="step-content">
            <h3 class="text-xl font-bold text-slate-800 mb-6 text-center">1. Pilih Durasi & Lapangan</h3>
            <div class="max-w-2xl mx-auto">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Lapangan</label>
                    <select id="inputLapangan" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-100 outline-none">
                        <?php foreach($lapangans as $lap): ?>
                            <option value="<?= $lap['id_lapangan'] ?>" data-harga="<?= $lap['harga_per_jam_member'] ?>">
                                <?= htmlspecialchars($lap['nama_lapangan']) ?> (Rp <?= number_format($lap['harga_per_jam_member'],0,',','.') ?>/jam)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <label class="cursor-pointer relative group">
                        <input type="radio" name="paket" value="1" data-quota="4" class="peer sr-only" checked>
                        <div class="p-6 rounded-2xl border-2 border-slate-200 peer-checked:border-primary peer-checked:bg-blue-50 transition-all h-full flex flex-col items-center justify-center">
                            <div class="w-14 h-14 bg-blue-100 text-primary rounded-full flex items-center justify-center text-2xl mb-3"><i class="fa-solid fa-calendar-days"></i></div>
                            <h4 class="font-bold text-lg">Paket 1 Bulan</h4>
                            <p class="text-sm text-slate-500 mt-1">Kuota: 4x Main</p>
                        </div>
                        <div class="absolute top-4 right-4 text-primary opacity-0 peer-checked:opacity-100"><i class="fa-solid fa-circle-check text-xl"></i></div>
                    </label>
                    <label class="cursor-pointer relative group">
                        <input type="radio" name="paket" value="3" data-quota="12" class="peer sr-only">
                        <div class="p-6 rounded-2xl border-2 border-slate-200 peer-checked:border-primary peer-checked:bg-blue-50 transition-all h-full flex flex-col items-center justify-center">
                            <div class="absolute top-0 right-0 bg-orange-400 text-white text-xs font-bold px-3 py-1 rounded-bl-xl">HEMAT</div>
                            <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-2xl mb-3"><i class="fa-solid fa-medal"></i></div>
                            <h4 class="font-bold text-lg">Paket 3 Bulan</h4>
                            <p class="text-sm text-slate-500 mt-1">Kuota: 12x Main</p>
                        </div>
                        <div class="absolute top-4 left-4 text-primary opacity-0 peer-checked:opacity-100"><i class="fa-solid fa-circle-check text-xl"></i></div>
                    </label>
                </div>
                <div class="mt-8 text-right">
                    <button onclick="nextStep(2)" class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primaryDark transition-all shadow-lg">Lanjut <i class="fa-solid fa-arrow-right ml-2"></i></button>
                </div>
            </div>
        </div>

        <div id="step2" class="step-content hidden">
            <h3 class="text-xl font-bold text-slate-800 mb-4">2. Pilih Jadwal Main</h3>
            <div class="flex flex-col lg:flex-row gap-8 h-full">
                <div class="flex-1">
                    <div class="alert-info bg-blue-50 text-blue-700 p-3 rounded-lg text-sm mb-4 flex gap-2 border border-blue-100">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <div><strong>Aturan Member:</strong> Wajib memilih 1 jadwal untuk setiap minggunya.</div>
                    </div>
                    <div class="mb-4">
                        <label class="text-sm font-bold text-slate-700">Pilih Tanggal</label>
                        <input type="date" id="inputTanggal" class="w-full mt-1 px-4 py-3 rounded-xl border border-slate-300 focus:border-primary outline-none" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div id="slotContainer">
                        <p class="text-center text-slate-400 py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">Pilih tanggal untuk melihat slot.</p>
                    </div>
                </div>
                <div class="lg:w-1/3 border-l border-slate-100 pl-0 lg:pl-8">
                    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 h-full flex flex-col">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-slate-800">Keranjang Jadwal</h4>
                            <span class="text-xs font-bold bg-white border border-slate-200 px-2 py-1 rounded-md text-primary"><span id="countSelected">0</span> / <span id="maxQuota">0</span></span>
                        </div>
                        <div id="selectedList" class="flex-1 overflow-y-auto max-h-[300px] space-y-2 mb-4 pr-1 custom-scrollbar">
                            <p class="text-xs text-slate-400 text-center mt-10">Belum ada jadwal dipilih.</p>
                        </div>
                        <div class="pt-4 border-t border-slate-200">
                            <button onclick="nextStep(3)" id="btnToStep3" class="w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primaryDark transition-all opacity-50 cursor-not-allowed" disabled>Lanjut Review</button>
                            <button onclick="resetTimer()" class="w-full mt-2 text-red-500 text-sm hover:text-red-700 py-2">Batal & Kembali</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="step3" class="step-content hidden">
            <h3 class="text-xl font-bold text-slate-800 mb-6 text-center">3. Review Pesanan & Metode</h3>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h4 class="font-bold text-slate-700 mb-3">Jadwal Yang Dipilih</h4>
                    <div class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden max-h-[300px] overflow-y-auto">
                        <table class="review-table">
                            <thead><tr><th>No</th><th>Tanggal</th><th>Jam</th></tr></thead>
                            <tbody id="reviewTableBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-between items-center p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <span class="text-slate-600 font-medium">Total Harga</span>
                        <span class="text-xl font-bold text-primary" id="reviewTotalPrice">Rp 0</span>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-slate-700 mb-3">Pilih Metode Pembayaran</h4>
                    <div class="space-y-3">
                        <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-all bg-white">
                            <div class="flex items-center gap-3"><i class="fa-solid fa-qrcode text-primary text-2xl"></i><span class="font-medium text-sm">QRIS</span></div>
                            <input type="radio" name="metode" value="qris" class="h-5 w-5 text-primary" checked>
                        </label>
                        <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-all bg-white">
                            <div class="flex items-center gap-3"><i class="fa-solid fa-building-columns text-blue-800 text-2xl"></i><span class="font-medium text-sm">Transfer BCA</span></div>
                            <input type="radio" name="metode" value="bca" class="h-5 w-5 text-primary">
                        </label>
                        <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-all bg-white">
                            <div class="flex items-center gap-3"><i class="fa-solid fa-building-columns text-yellow-600 text-2xl"></i><span class="font-medium text-sm">Transfer Mandiri</span></div>
                            <input type="radio" name="metode" value="mandiri" class="h-5 w-5 text-primary">
                        </label>
                    </div>
                    <div class="mt-8 flex gap-3">
                        <button onclick="prevStep(2)" class="px-6 py-3 border border-slate-300 rounded-xl text-slate-600 font-bold hover:bg-slate-50">Kembali</button>
                        <button onclick="nextStep(4)" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primaryDark shadow-lg shadow-primary/30">Lanjut Bayar</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="step4" class="step-content hidden">
            <h3 class="text-xl font-bold text-slate-800 mb-6 text-center">4. Selesaikan Pembayaran</h3>
            <div class="max-w-2xl mx-auto">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nama Pemesan</label>
                    <input type="text" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm font-medium text-slate-700 bg-gray-50" value="<?= htmlspecialchars($user_nama ?? '') ?>" readonly>
                </div>

                <div id="paymentInstruction" class="text-center mb-8 p-6 bg-slate-50 rounded-2xl border border-slate-200"></div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center">Upload Bukti Transfer</label>
                    <div class="upload-box" onclick="document.getElementById('inputBukti').click()">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-400 mb-3"></i>
                        <p class="text-sm text-slate-500 font-medium" id="fileName">Klik area ini untuk memilih file (JPG/PNG/PDF)</p>
                        <input type="file" id="inputBukti" class="hidden" accept="image/*,application/pdf" onchange="document.getElementById('fileName').textContent = this.files[0].name">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button onclick="prevStep(3)" class="px-6 py-3 border border-slate-300 rounded-xl text-slate-600 font-bold hover:bg-slate-50">Kembali</button>
                    <button onclick="submitMember()" id="btnFinalSubmit" class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-600/30 flex justify-center items-center gap-2"><i class="fa-solid fa-check-circle"></i> Konfirmasi Pembayaran</button>
                </div>
            </div>
        </div>

    </div>
</main>
<script src="member.js"></script>
<?php require '../include_user/footer.php'; ?>