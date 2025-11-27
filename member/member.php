<?php
// 1. BUFFERING & SESSION
ob_start(); 
session_start();
date_default_timezone_set('Asia/Jakarta');

// Matikan display error agar tidak merusak JSON response di AJAX
ini_set('display_errors', 0);
error_reporting(E_ALL);

require '../config/database.php';

// 2. Cek Login
if (!isset($_SESSION['id_user'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang.']);
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id_user'];
// PERBAIKAN 1: Berikan nilai default string kosong jika session nama tidak ada
$user_nama = $_SESSION['nama'] ?? ''; 

// =======================================================================
// API BACKEND (AJAX HANDLER)
// =======================================================================
// PERBAIKAN 2: Cek isset($_POST['action']) sebelum mengaksesnya
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        // A. AMBIL SLOT TERSEDIA
        if ($_POST['action'] === 'get_slots') {
            $id_lapangan = $_POST['id_lapangan'] ?? 0;
            $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
            
            $slots = [];
            $q_waktu = mysqli_query($conn, "SELECT * FROM jadwal_waktu WHERE id_lapangan = '$id_lapangan' ORDER BY jam_mulai ASC");
            
            while ($w = mysqli_fetch_assoc($q_waktu)) {
                $status = 'tersedia';
                $q_cek = "SELECT 1 FROM jadwal_detail jd
                          LEFT JOIN booking b ON jd.id_booking = b.id_booking
                          JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                          WHERE jd.id_jadwal_waktu = '{$w['id_jadwal_waktu']}' 
                          AND jh.tanggal = '$tanggal' 
                          AND jh.id_lapangan = '$id_lapangan'
                          AND (jd.status = 'dibooking' OR (b.status IN ('menunggu', 'disetujui', 'hold') AND b.status IS NOT NULL))
                          LIMIT 1";
                
                if (mysqli_num_rows(mysqli_query($conn, $q_cek)) > 0) {
                    $status = 'dibooking';
                }

                $slots[] = [
                    'id_waktu' => $w['id_jadwal_waktu'],
                    'jam' => date('H:i', strtotime($w['jam_mulai'])) . ' - ' . date('H:i', strtotime($w['jam_selesai'])),
                    'status' => $status
                ];
            }
            
            echo json_encode(['status' => 'success', 'slots' => $slots]);
            exit;
        }

        // B. PROSES SUBMIT MEMBER
        if ($_POST['action'] === 'submit_member') {
        try {
            $id_lapangan = $_POST['id_lapangan'];
            $paket_bulan = (int)$_POST['paket_bulan'];
            $total_bayar = (float)$_POST['total_bayar'];
            $metode = $_POST['metode_pembayaran'];
            $selected_slots = json_decode($_POST['selected_slots'], true);

            if (!$selected_slots) throw new Exception("Tidak ada jadwal yang dipilih.");

            // Upload Bukti
            $bukti = null;
            $uploadDir = __DIR__ . '/../uploads/bukti_pembayaran/';
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Gagal membuat folder penyimpanan.");
                }
            }

            if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
                $fileInfo = pathinfo($_FILES['bukti_transfer']['name']);
                $ext = strtolower($fileInfo['extension']);
                
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Format file tidak diizinkan. Gunakan JPG, PNG, atau PDF.");
                }

                $bukti = "member_" . $user_id . "_" . time() . "." . $ext;
                $targetFile = $uploadDir . $bukti;

                if (!move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $targetFile)) {
                    throw new Exception("Gagal mengupload file.");
                }
            } else {
                throw new Exception("Bukti transfer wajib diupload.");
            }

            // Sort Tanggal
            usort($selected_slots, function($a, $b) {
                return strtotime($a['tanggal']) - strtotime($b['tanggal']);
            });
            $tgl_mulai = $selected_slots[0]['tanggal'];
            $tgl_akhir = end($selected_slots)['tanggal'];

            mysqli_begin_transaction($conn);

            // Insert Member
            $stmt_m = $conn->prepare("INSERT INTO member (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
            $stmt_m->bind_param("iiissssd", $user_id, $id_lapangan, $paket_bulan, $tgl_mulai, $tgl_akhir, $bukti, $metode, $total_bayar);
            
            if (!$stmt_m->execute()) throw new Exception("Gagal menyimpan data member.");
            $id_member = $conn->insert_id;

            // Insert Booking Dummy
            $stmt_b = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, total_amount, payment_status, payment_method) VALUES (?, ?, 'member', ?, 'disetujui', ?, 'lunas', ?)");
            $stmt_b->bind_param("iisds", $user_id, $id_lapangan, $tgl_mulai, $total_bayar, $metode);
            $stmt_b->execute();
            $id_booking_dummy = $conn->insert_id;

            // Ambil Harga
            $q_lap = mysqli_query($conn, "SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan = '$id_lapangan'");
            $hrg = mysqli_fetch_assoc($q_lap)['harga_per_jam_member'];

            // Insert Detail
            $stmt_dm = $conn->prepare("INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status) VALUES (?, ?, ?, ?, ?, ?, 'aktif')");

            foreach ($selected_slots as $slot) {
                $jam_parts = explode(' - ', $slot['jam']);
                
                // Simpan Member Jadwal
                $stmt_dm->bind_param("iissssd", $id_member, $id_lapangan, $slot['tanggal'], $jam_parts[0], $jam_parts[1], $hrg);
                $stmt_dm->execute();

                // Kunci Slot Utama
                $cek_h = mysqli_query($conn, "SELECT id_jadwal_harian FROM jadwal_harian WHERE id_lapangan='$id_lapangan' AND tanggal='{$slot['tanggal']}'");
                if (mysqli_num_rows($cek_h) == 0) {
                    mysqli_query($conn, "INSERT INTO jadwal_harian (id_lapangan, tanggal, hari) VALUES ('$id_lapangan', '{$slot['tanggal']}', DAYNAME('{$slot['tanggal']}'))");
                    $id_harian = $conn->insert_id;
                } else {
                    $id_harian = mysqli_fetch_assoc($cek_h)['id_jadwal_harian'];
                }

                $cek_d = mysqli_query($conn, "SELECT id_detail FROM jadwal_detail WHERE id_jadwal_harian='$id_harian' AND id_jadwal_waktu='{$slot['id_waktu']}'");
                
                if(mysqli_num_rows($cek_d) > 0) {
                    mysqli_query($conn, "UPDATE jadwal_detail SET status='dibooking', id_booking='$id_booking_dummy' WHERE id_jadwal_harian='$id_harian' AND id_jadwal_waktu='{$slot['id_waktu']}'");
                } else {
                    mysqli_query($conn, "INSERT INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, id_booking) VALUES ('$id_harian', '{$slot['id_waktu']}', 'dibooking', '$id_booking_dummy')");
                }
            }

            mysqli_commit($conn);
            echo json_encode(['status'=>'success', 'message' => 'Pendaftaran berhasil!']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['status'=>'error', 'message'=> $e->getMessage()]);
        }
        exit;
    }
    
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['status'=>'error', 'message'=> $e->getMessage()]);
        exit;
    }

}

ob_end_flush(); 

// 4. Ambil Data Lapangan
$lapangans = [];
$q = mysqli_query($conn, "SELECT * FROM lapangan WHERE status='aktif'");
while($r = mysqli_fetch_assoc($q)){ $lapangans[] = $r; }
?>

<?php require '../include_user/header.php'; ?>

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
                            <button onclick="prevStep(1)" class="w-full mt-2 text-slate-500 text-sm hover:text-slate-700 py-2">Kembali</button>
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

<script>
    let currentStep = 1;
    let selectedSlots = []; 
    let maxQuota = 0;
    let hargaPerJam = 0;
    let selectedMethod = 'qris';

    function getISOWeek(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    function nextStep(step) {
        if (step === 2) {
            const paketEl = document.querySelector('input[name="paket"]:checked');
            const lapEl = document.getElementById('inputLapangan');
            if (!paketEl) return Swal.fire('Pilih Paket', 'Silakan pilih durasi paket.', 'warning');
            
            maxQuota = parseInt(paketEl.dataset.quota);
            hargaPerJam = parseInt(lapEl.options[lapEl.selectedIndex].dataset.harga);
            document.getElementById('maxQuota').textContent = maxQuota;
            renderSelectedList(); 
        }
        
        if (step === 3) {
            if (selectedSlots.length !== maxQuota) return Swal.fire('Jadwal Belum Lengkap', `Anda harus memilih <b>${maxQuota}</b> slot jadwal.`, 'warning');
            renderReviewStep();
        }

        if (step === 4) {
            selectedMethod = document.querySelector('input[name="metode"]:checked').value;
            renderPaymentInstruction();
        }

        document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(`step${step}`).classList.remove('hidden');
        document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
        document.getElementById(`step${step}-ind`).classList.add('active');
        currentStep = step;
    }

    function prevStep(step) {
        document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(`step${step}`).classList.remove('hidden');
        document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
        document.getElementById(`step${step}-ind`).classList.add('active');
        currentStep = step;
    }

    document.getElementById('inputTanggal').addEventListener('change', function() {
        const tanggal = this.value;
        const id_lapangan = document.getElementById('inputLapangan').value;
        const container = document.getElementById('slotContainer');
        
        container.innerHTML = '<div class="text-center py-10"><i class="fa-solid fa-circle-notch fa-spin text-primary text-2xl"></i></div>';

        const formData = new FormData();
        formData.append('action', 'get_slots');
        formData.append('id_lapangan', id_lapangan);
        formData.append('tanggal', tanggal);

        fetch('member.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                let html = '<div class="slot-grid">';
                data.slots.forEach(slot => {
                    const isSelected = selectedSlots.some(s => s.id_waktu === slot.id_waktu && s.tanggal === tanggal);
                    const statusClass = slot.status === 'tersedia' ? 'available' : 'booked disabled';
                    const selectedClass = isSelected ? 'selected' : '';
                    html += `<div class="slot-btn ${statusClass} ${selectedClass}" onclick="toggleSlot('${slot.id_waktu}', '${tanggal}', '${slot.jam}', this)">${slot.jam}</div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        });
    });

    function toggleSlot(id_waktu, tanggal, jam, el) {
        if (el.classList.contains('disabled')) return;
        const index = selectedSlots.findIndex(s => s.id_waktu === id_waktu && s.tanggal === tanggal);

        if (index > -1) {
            selectedSlots.splice(index, 1);
            el.classList.remove('selected');
        } else {
            if (selectedSlots.length >= maxQuota) return Swal.fire('Kuota Penuh', `Maksimal ${maxQuota} slot.`, 'info');
            
            const targetDate = new Date(tanggal);
            const targetWeek = getISOWeek(targetDate);
            const targetYear = targetDate.getFullYear();
            const isWeekOccupied = selectedSlots.some(s => {
                const d = new Date(s.tanggal);
                return getISOWeek(d) === targetWeek && d.getFullYear() === targetYear;
            });
            if (isWeekOccupied) return Swal.fire('Jadwal Bentrok', 'Anda hanya boleh memilih <b>1 jadwal</b> dalam minggu ini.', 'warning');

            selectedSlots.push({ id_waktu, tanggal, jam });
            el.classList.add('selected');
        }
        renderSelectedList();
    }

    function renderSelectedList() {
        const listEl = document.getElementById('selectedList');
        const btnTo3 = document.getElementById('btnToStep3');
        document.getElementById('countSelected').textContent = selectedSlots.length;
        
        if (selectedSlots.length === maxQuota) {
            btnTo3.disabled = false;
            btnTo3.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btnTo3.disabled = true;
            btnTo3.classList.add('opacity-50', 'cursor-not-allowed');
        }

        if (selectedSlots.length === 0) listEl.innerHTML = '<p class="text-xs text-slate-400 text-center mt-10">Belum ada jadwal.</p>';
        else {
            selectedSlots.sort((a,b) => new Date(a.tanggal) - new Date(b.tanggal));
            listEl.innerHTML = selectedSlots.map((s, i) => `<div class="selected-item"><div><div class="text-xs font-bold text-slate-700">${formatDate(s.tanggal)}</div><div class="text-xs text-slate-500">${s.jam}</div></div><button onclick="removeSlot(${i})" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash"></i></button></div>`).join('');
        }
    }

    function removeSlot(index) {
        const removed = selectedSlots[index];
        selectedSlots.splice(index, 1);
        renderSelectedList();
        const currentTanggal = document.getElementById('inputTanggal').value;
        if (currentTanggal === removed.tanggal) document.getElementById('inputTanggal').dispatchEvent(new Event('change'));
    }

    function renderReviewStep() {
        const tbody = document.getElementById('reviewTableBody');
        const totalEl = document.getElementById('reviewTotalPrice');
        const totalBayar = selectedSlots.length * hargaPerJam;
        tbody.innerHTML = selectedSlots.map((s, i) => `<tr><td>${i+1}</td><td>${formatDate(s.tanggal)}</td><td>${s.jam}</td></tr>`).join('');
        totalEl.textContent = 'Rp ' + totalBayar.toLocaleString('id-ID');
    }

    function renderPaymentInstruction() {
        const container = document.getElementById('paymentInstruction');
        const totalBayar = selectedSlots.length * hargaPerJam;
        const totalFormatted = 'Rp ' + totalBayar.toLocaleString('id-ID');
        let content = `<p class="text-slate-600 mb-2">Total Pembayaran: <strong class="text-lg text-slate-800">${totalFormatted}</strong></p>`;
        if (selectedMethod === 'qris') content += `<div class="mt-4"><img src="../assets/images/qris_rush.jpg" alt="QRIS" class="mx-auto w-48 rounded-lg border p-2 mb-2"><p class="text-xs font-mono text-slate-500">NMID: ID1025384582157</p><p class="text-sm text-slate-600 mt-2">Scan QRIS di atas.</p></div>`;
        else if (selectedMethod === 'bca') content += `<div class="mt-4 bg-blue-100 p-4 rounded-xl inline-block"><h5 class="font-bold text-blue-900">Bank BCA</h5><p class="text-2xl font-bold text-slate-800 my-2 tracking-widest">123 456 7890</p><p class="text-sm text-slate-600">a.n Rush Badminton Academy</p></div>`;
        else if (selectedMethod === 'mandiri') content += `<div class="mt-4 bg-yellow-100 p-4 rounded-xl inline-block"><h5 class="font-bold text-yellow-900">Bank Mandiri</h5><p class="text-2xl font-bold text-slate-800 my-2 tracking-widest">098 765 4321</p><p class="text-sm text-slate-600">a.n Rush Badminton Academy</p></div>`;
        container.innerHTML = content;
    }

    function submitMember() {
        const fileInput = document.getElementById('inputBukti');
        if (fileInput.files.length === 0) return Swal.fire('Upload Bukti', 'Bukti transfer wajib diupload.', 'warning');

        const btn = document.getElementById('btnFinalSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';

        const formData = new FormData();
        formData.append('action', 'submit_member');
        formData.append('id_lapangan', document.getElementById('inputLapangan').value);
        formData.append('paket_bulan', document.querySelector('input[name="paket"]:checked').value);
        formData.append('total_bayar', selectedSlots.length * hargaPerJam);
        formData.append('metode_pembayaran', selectedMethod);
        formData.append('selected_slots', JSON.stringify(selectedSlots));
        formData.append('bukti_transfer', fileInput.files[0]);

        fetch('member.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') Swal.fire('Berhasil!', 'Pendaftaran berhasil.', 'success').then(() => window.location.href = '../DashPengguna.php');
            else { Swal.fire('Gagal', data.message, 'error'); btn.disabled = false; btn.innerHTML = 'Konfirmasi Pembayaran'; }
        })
        .catch(err => { console.error(err); Swal.fire('Error', 'Kesalahan koneksi.', 'error'); btn.disabled = false; btn.innerHTML = 'Konfirmasi Pembayaran'; });
    }

    function formatDate(dateString) { return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }
</script>

<?php require '../include_user/footer.php'; ?>