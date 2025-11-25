<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// === 1. PANGGIL FUNGSI AUTO-RELEASE (PENTING!) ===
// Membersihkan slot 'hold' yang sudah lewat waktu 7 menit
require '../include_user/release_slots.php';
// ==================================================

// --- BAGIAN USER ---
if (!isset($_SESSION['id_user'])) {
    $_SESSION['id_user'] = 1;
    $_SESSION['nama'] = "User Demo";
}
$user_id = $_SESSION['id_user'];

// ------------------ BACKEND ENDPOINT UNTUK CART (AJAX) ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json; charset=utf-8');

    if ($action === 'add_to_cart') {
        $id_jadwal_waktu = isset($_POST['id_jadwal_waktu']) ? (int)$_POST['id_jadwal_waktu'] : 0;
        $id_lapangan = isset($_POST['id_lapangan']) ? (int)$_POST['id_lapangan'] : 0;
        $tanggal = $_POST['tanggal'] ?? '';
        $jam = $_POST['jam'] ?? '';
        $harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0.0;
        $nama_lapangan = $_POST['nama_lapangan'] ?? 'Lapangan';

        if (!$id_jadwal_waktu || !$tanggal || !$jam) {
            echo json_encode(['status' => 'error', 'message' => 'Data slot tidak lengkap.']);
            exit;
        }

        if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }

        $duplicate = false;
        foreach ($_SESSION['keranjang'] as $it) {
            if ((int)$it['id_jadwal_waktu'] === $id_jadwal_waktu && $it['tanggal'] === $tanggal) {
                $duplicate = true;
                break;
            }
        }
        if ($duplicate) {
            echo json_encode(['status' => 'error', 'message' => 'Slot sudah ada di keranjang.', 'count' => count($_SESSION['keranjang'])]);
            exit;
        }

        // Cek DB (Safety Check Update)
        // Cek status 'dibooking' ATAU 'hold' yang belum expired
        $check_q = "SELECT 1 
                    FROM jadwal_detail jd
                    JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                    LEFT JOIN booking b ON jd.id_booking = b.id_booking
                    WHERE jd.id_jadwal_waktu = ? 
                    AND jh.tanggal = ? 
                    AND (
                        jd.status = 'dibooking' 
                        OR (jd.status = 'hold' AND b.expired_at > NOW())
                    )";
                    
        $stmt_check = mysqli_prepare($conn, $check_q);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "is", $id_jadwal_waktu, $tanggal);
            mysqli_stmt_execute($stmt_check);
            $res_check = mysqli_stmt_get_result($stmt_check);
            if ($row_check = mysqli_fetch_assoc($res_check)) {
                echo json_encode(['status' => 'error', 'message' => 'Slot sudah dibooking oleh orang lain.']);
                exit;
            }
        }

        $_SESSION['keranjang'][] = [
            'id_jadwal_waktu' => $id_jadwal_waktu,
            'id_lapangan' => $id_lapangan,
            'tanggal' => $tanggal,
            'jam' => $jam,
            'harga' => $harga,
            'nama_lapangan' => $nama_lapangan
        ];

        echo json_encode(['status' => 'ok', 'message' => 'Slot ditambahkan ke keranjang.', 'count' => count($_SESSION['keranjang'])]);
        exit;
    }

    if ($action === 'remove_from_cart') {
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        if (isset($_SESSION['keranjang'][$index])) {
            array_splice($_SESSION['keranjang'], $index, 1);
            echo json_encode(['status' => 'ok', 'message' => 'Item dihapus.', 'count' => count($_SESSION['keranjang'])]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Index tidak ditemukan.']);
        }
        exit;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
    exit;
}
// ------------------ AKHIR ENDPOINT CART --------------------------------

// --- PARAMETER ---
$selected_lapangan = (int)($_GET['lapangan'] ?? 0);
$selected_date     = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// --- AMBIL DATA LAPANGAN ---
if ($selected_lapangan <= 0) {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif' ORDER BY id_lapangan LIMIT 1"));
    $selected_lapangan = $first['id_lapangan'] ?? 0;
}

$lapangan_query = "SELECT id_lapangan, nama_lapangan, deskripsi, foto, tipe, harga_per_jam FROM lapangan WHERE id_lapangan = ?";
$stmt = mysqli_prepare($conn, $lapangan_query);
mysqli_stmt_bind_param($stmt, "i", $selected_lapangan);
mysqli_stmt_execute($stmt);
$lapangan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$lapangan) {
    die("Lapangan tidak ditemukan.");
}

$all_lapangan_result = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");

// --- LOGIKA TANGGAL & STATUS HARI ---
$hari_num = date('N', strtotime($selected_date));
$hari_map = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
$hari = $hari_map[$hari_num - 1];

$date_range_query = mysqli_query($conn, "SELECT MIN(tanggal) AS min_date, MAX(tanggal) AS max_date FROM jadwal_harian WHERE id_lapangan = $selected_lapangan AND tanggal >= CURDATE()");
$date_range = mysqli_fetch_assoc($date_range_query);
$min_date = $date_range['min_date'] ?? date('Y-m-d');
$max_date = $date_range['max_date'] ?? date('Y-m-d');

$hari_status = 'tidak_tersedia';
$hari_status_message = '';
$id_jadwal_harian_today = 0; 

if (strtotime($selected_date) < strtotime(date('Y-m-d'))) {
    $hari_status = 'kadaluarsa';
    $hari_status_message = 'Anda tidak dapat memesan jadwal di masa lalu.';
} else {
    $status_query = "SELECT id_jadwal_harian, status_hari FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
    $stmt_status = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt_status, "is", $selected_lapangan, $selected_date);
    mysqli_stmt_execute($stmt_status);
    $result_status = mysqli_stmt_get_result($stmt_status);
    
    if ($row_status = mysqli_fetch_assoc($result_status)) {
        $hari_status = $row_status['status_hari']; 
        $id_jadwal_harian_today = $row_status['id_jadwal_harian']; 
        if ($hari_status === 'penuh') $hari_status_message = 'Jadwal penuh untuk tanggal ini.';
        if ($hari_status === 'libur') $hari_status_message = 'Lapangan libur pada tanggal ini.';
    } else {
        $hari_status = 'belum_tersedia';
        $hari_status_message = 'Jadwal untuk tanggal ini belum diatur oleh admin.';
    }
    mysqli_stmt_close($stmt_status);
}

// JADWAL JAM
$jadwal_list = [];
if ($hari_status === 'tersedia') {
    $jam_min = ($hari == 'sabtu' || $hari == 'minggu') ? '07:00:00' : '08:00:00';
    $jadwal_query = "SELECT * FROM jadwal_waktu WHERE id_lapangan = ? AND jam_mulai >= ? ORDER BY jam_mulai";
    $stmt_jam = mysqli_prepare($conn, $jadwal_query);
    mysqli_stmt_bind_param($stmt_jam, "is", $selected_lapangan, $jam_min);
    mysqli_stmt_execute($stmt_jam);
    $jadwal_result = mysqli_stmt_get_result($stmt_jam);
    while ($row_jam = mysqli_fetch_assoc($jadwal_result)) {
        $jadwal_list[] = $row_jam;
    }
}

// CEK BOOKED (UPDATE LOGIKA HOLD)
$booked_slots = [];
if ($id_jadwal_harian_today > 0) { 
    $check_query = "SELECT jd.id_jadwal_waktu, jd.status 
                    FROM jadwal_detail jd
                    LEFT JOIN booking b ON jd.id_booking = b.id_booking
                    WHERE jd.id_jadwal_harian = ? 
                    AND (
                        jd.status = 'dibooking' 
                        OR (jd.status = 'hold' AND b.expired_at > NOW())
                    )";
    
    $stmt_booked = mysqli_prepare($conn, $check_query);
    if ($stmt_booked) {
        mysqli_stmt_bind_param($stmt_booked, "i", $id_jadwal_harian_today);
        mysqli_stmt_execute($stmt_booked);
        $result_booked = mysqli_stmt_get_result($stmt_booked);
        while ($row_booked = mysqli_fetch_assoc($result_booked)) {
            $booked_slots[$row_booked['id_jadwal_waktu']] = true;
        }
        mysqli_stmt_close($stmt_booked);
    }
}

// HITUNG AVAILABLE (Termasuk cek waktu)
$available_count = 0;
$is_today_check = ($selected_date == date('Y-m-d'));
$current_time_check = date('H:i:s');

foreach ($jadwal_list as $jadwal) {
    $jadwal_id = $jadwal['id_jadwal_waktu'];
    $is_booked = isset($booked_slots[$jadwal_id]);
    $is_past_time = false;
    if ($is_today_check && (strtotime($jadwal['jam_mulai']) < strtotime($current_time_check))) {
        $is_past_time = true;
    }
    if (!$is_booked && !$is_past_time) {
        $available_count++;
    }
}

$message = ''; 

require '../include_user/header.php';
?>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-2 gap-8">

      <div class="bg-white rounded-xl shadow-soft p-6 animate-fade-in">
        <div class="overflow-hidden rounded-lg mb-4 shadow-inner">
            <img src="../uploads/lapangan/<?= htmlspecialchars($lapangan['foto'] ?? 'default.jpg') ?>" 
                 alt="<?= htmlspecialchars($lapangan['nama_lapangan']) ?>" 
                 class="w-full h-64 object-cover transition-transform duration-300 hover:scale-105">
        </div>
        
        <h1 class="text-3xl font-bold font-poppins text-slate-800"><?= htmlspecialchars($lapangan['nama_lapangan']) ?></h1>
        <p class="text-sm text-slate-500 mt-1 mb-4 font-medium capitalize"><?= htmlspecialchars($lapangan['tipe'] ?? 'Tipe Lapangan') ?></p>
        
        <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($lapangan['deskripsi'] ?? 'Lapangan berkualitas')) ?></p>
        
        <div class="flex flex-wrap gap-2 mt-4 text-xs font-semibold">
          <span class="inline-block bg-primary-light text-primary px-3 py-1 rounded-full"><i class="fa-solid fa-feather mr-1.5"></i>Badminton</span>
          <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full"><i class="fa-solid fa-building mr-1.5"></i>Indoor</span>
          <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full"><i class="fa-solid fa-layer-group mr-1.5"></i>Karpet Vinyl</span>
        </div>
        
        <div class="mt-6 bg-primary-light border border-primary/20 rounded-lg p-4 flex items-center gap-3">
          <i class="fas fa-calendar-check text-primary text-xl"></i>
          <div>
            <div class="text-sm font-bold text-primary-dark"><?= $available_count ?> Jadwal Tersedia</div>
            <div class="text-xs text-primary/80">pada <?= date('d M Y', strtotime($selected_date)) ?></div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-soft p-6 animate-fade-in-delay" style="animation-delay: 0.1s;">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <div class="flex-1">
            <label for="lapangan_select" class="block text-sm font-medium mb-1 text-slate-700">Pilih Lapangan</label>
            <form method="get" id="formLapangan">
              <select name="lapangan" id="lapangan_select" onchange="this.form.submit()" class="form-control">
                <?php mysqli_data_seek($all_lapangan_result, 0); ?>
                <?php while ($row = mysqli_fetch_assoc($all_lapangan_result)): ?>
                  <option value="<?= $row['id_lapangan'] ?>" <?= $row['id_lapangan'] == $selected_lapangan ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama_lapangan']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </form>
          </div>
          
          <div class="flex-1">
            <label for="date_input" class="block text-sm font-medium mb-1 text-slate-700">Pilih Tanggal</label>
            <form method="get" id="formTanggal">
              <input type="hidden" name="lapangan" value="<?= $selected_lapangan ?>">
              <input type="date" name="date" id="date_input" 
                     value="<?= htmlspecialchars($selected_date) ?>" 
                     onchange="this.form.submit()" 
                     class="form-control" 
                     min="<?= $min_date ?>" 
                     max="<?= $max_date ?>">
            </form>
          </div>
        </div>

        <?= $message ?>

        <hr class="mb-6 border-gray-200">

        <?php if ($hari_status !== 'tersedia'): ?>
          <div class="message-box <?= ($hari_status == 'kadaluarsa' ? 'error' : 'warning') ?>">
            <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
            <div>
              <h4 class="font-bold">Jadwal Tidak Tersedia</h4>
              <p class="text-xs"><?= htmlspecialchars($hari_status_message ?? 'Silakan pilih tanggal lain.') ?></p>
            </div>
          </div>
          
        <?php elseif (empty($jadwal_list)): ?>
           <div class="message-box info">
            <i class="fas fa-info-circle mr-3 text-xl"></i>
            <div>
              <h4 class="font-bold">Slot Belum Diatur</h4>
              <p class="text-xs">Admin belum mengatur slot jam untuk lapangan ini pada tanggal yang dipilih.</p>
            </div>
          </div>
           
        <?php else: ?>
          <h3 class="text-lg font-bold font-poppins text-slate-800 mb-4">Pilih Jam Main</h3>
          <?php
          $is_today = ($selected_date == date('Y-m-d'));
          $current_time_str = date('H:i:s'); 
          ?>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($jadwal_list as $jadwal): 
              $start = substr($jadwal['jam_mulai'], 0, 5);
              $end = substr($jadwal['jam_selesai'], 0, 5);
              $jadwal_id = $jadwal['id_jadwal_waktu'];
              
              $is_booked = isset($booked_slots[$jadwal_id]);
              
              $is_past_time = false;
              if ($is_today && (strtotime($jadwal['jam_mulai']) < strtotime($current_time_str))) {
                  $is_past_time = true;
              }
              
              $harga = (float)($lapangan['harga_per_jam'] ?? 0);
            ?>
              <?php if ($is_booked || $is_past_time): ?>
                <div class="slot-card booked">
                  <div class="text-xs font-medium">60 Menit</div>
                  <div class="text-sm font-semibold mt-1 line-through"><?= $start ?> - <?= $end ?></div>
                  
                  <div class="text-sm font-medium mt-1">
                      <?= $is_past_time ? 'Terlewat' : 'Dipesan' ?>
                  </div>
                  </div>
              <?php else: ?>
                <button 
                  type="button" 
                  class="slot-card available w-full h-full jam-main" 
                  data-id="<?= $jadwal_id ?>"
                  data-lapangan="<?= $selected_lapangan ?>"
                  data-tanggal="<?= htmlspecialchars($selected_date) ?>"
                  data-jam="<?= htmlspecialchars($start . ' - ' . $end) ?>"
                  data-harga="<?= htmlspecialchars($harga) ?>">
                    <div class="text-xs font-medium text-slate-500">60 Menit</div>
                    <div class="text-sm font-semibold mt-1 time"><?= $start ?> - <?= $end ?></div>
                    <div class="text-sm mt-1 price">
                      Rp <?= number_format($harga, 0, ',', '.') ?>
                    </div>
                </button>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <p class="text-xs text-slate-500 mt-6">
          Menampilkan jadwal untuk: <strong><?= date('d/m/Y', strtotime($selected_date)) ?> (<?= ucfirst($hari) ?>)</strong>
        </p>
      </div>
    </div>
  </main>
  <div id="loginRequiredModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden bg-black/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none">
    <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-[320px] p-6 text-center transform scale-95 transition-transform duration-300" id="loginModalContent">
        
        <h3 class="text-lg font-bold text-slate-800 mb-2">Login Diperlukan</h3>
        
        <p class="text-sm text-slate-500 mb-6 leading-relaxed">
            Anda harus login terlebih dahulu untuk melanjutkan checkout. Apakah Anda ingin login sekarang?
        </p>
        
        <div class="flex flex-col gap-3">
            <button id="btnLoginYes" class="w-full bg-[#2563EB] hover:bg-[#1d4ed8] text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-200">
                IYA, LOGIN
            </button>
            
            <button id="btnLoginNo" class="w-full bg-white border-2 border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-50 transition-all">
                TIDAK
            </button>
        </div>
    </div>
  </div>

  <?php 
require '../include_user/footer.php'; 
?>