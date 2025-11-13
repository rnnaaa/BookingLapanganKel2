<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// --- BAGIAN USER (ASUMSI LOGGED IN ATAU DEMO) ---
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

        // Cek DB (opsional, tapi bagus)
        $check_q = "SELECT 1 
                    FROM jadwal_detail jd
                    JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                    WHERE jd.id_jadwal_waktu = ? AND jh.tanggal = ? AND jd.status = 'dibooking'";
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

    // ... (aksi lain) ...
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

// --- AMBIL DATA LAPANGAN (TERMASUK UNTUK DROPDOWN) ---
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

if (strtotime($selected_date) < strtotime(date('Y-m-d'))) {
    $hari_status = 'kadaluarsa';
    $hari_status_message = 'Anda tidak dapat memesan jadwal di masa lalu.';
} else {
    $status_query = "SELECT id_jadwal_harian, status_hari FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
    $stmt_status = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt_status, "is", $selected_lapangan, $selected_date);
    mysqli_stmt_execute($stmt_status);
    $result_status = mysqli_stmt_get_result($stmt_status);
    
    $id_jadwal_harian_today = 0; // Simpan ID jadwal harian untuk kueri nanti

    if ($row_status = mysqli_fetch_assoc($result_status)) {
        $hari_status = $row_status['status_hari']; 
        $id_jadwal_harian_today = $row_status['id_jadwal_harian']; // Ambil ID
        if ($hari_status === 'penuh') $hari_status_message = 'Jadwal penuh untuk tanggal ini.';
        if ($hari_status === 'libur') $hari_status_message = 'Lapangan libur pada tanggal ini.';
    } else {
        $hari_status = 'belum_tersedia';
        $hari_status_message = 'Jadwal untuk tanggal ini belum diatur oleh admin.';
    }
    mysqli_stmt_close($stmt_status);
}

// JADWAL JAM (HANYA JIKA 'tersedia')
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

// CEK BOOKED (LOGIKA BARU SESUAI JADWAL_DETAIL)
$booked_slots = [];
if ($id_jadwal_harian_today > 0) { // Hanya cek jika jadwal harian ada
    $check_query = "SELECT id_jadwal_waktu FROM jadwal_detail 
                    WHERE id_jadwal_harian = ? AND status = 'dibooking'";
    
    $stmt_booked = mysqli_prepare($conn, $check_query);
    if ($stmt_booked) {
        mysqli_stmt_bind_param($stmt_booked, "i", $id_jadwal_harian_today);
        mysqli_stmt_execute($stmt_booked);
        $result_booked = mysqli_stmt_get_result($stmt_booked);
        while ($row_booked = mysqli_fetch_assoc($result_booked)) {
            $booked_slots[$row_booked['id_jadwal_waktu']] = true; // Simpan berdasarkan ID
        }
        mysqli_stmt_close($stmt_booked);
    }
}

// === PERBAIKAN: Perhitungan $available_count DIPINDAHKAN KE ATAS ===
$available_count = 0;
$is_today_check = ($selected_date == date('Y-m-d'));
$current_time_check = date('H:i:s');

foreach ($jadwal_list as $jadwal) {
    $jadwal_id = $jadwal['id_jadwal_waktu'];
    
    // Cek 1: Sudah dibooking?
    $is_booked = isset($booked_slots[$jadwal_id]);
    
    // Cek 2: Sudah terlewat?
    $is_past_time = false;
    if ($is_today_check && (strtotime($jadwal['jam_mulai']) < strtotime($current_time_check))) {
        $is_past_time = true;
    }

    // Jika TIDAK dibooking DAN TIDAK terlewat, tambahkan hitungan
    if (!$is_booked && !$is_past_time) {
        $available_count++;
    }
}
// === AKHIR PERBAIKAN ===


// PROSES BOOKING (LOGIKA LAMA - HANYA UNTUK FORM TANPA JS)
$message = '';
// ... (Logika form 'book_slot' lama Anda bisa diletakkan di sini jika masih dipakai) ...
// ... (Saat ini logika ini tidak terpakai karena AJAX) ...

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Booking — <?= htmlspecialchars($lapangan['nama_lapangan']) ?> | Rush Academy</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  
  <script>
    // Konfigurasi Tailwind (Menambahkan Aksen Biru)
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sans': ['Inter', 'sans-serif'],
            'poppins': ['Poppins', 'sans-serif'],
          },
          colors: { 
            primary: "#0b63d6", // Biru Tema Anda
            primaryDark: "#094ea8", 
            softGray: "#f9fafb",
            'primary-light': '#e7f0ff', 
            'gray-hover': '#f4f4f5', 
          },
          boxShadow: { 
            lift: "0 18px 40px rgba(11,26,54,0.10)", 
            soft: "0 8px 24px rgba(11,26,54,0.06)",
            'lg-soft': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05)'
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-out forwards',
            'fade-in-delay': 'fadeIn 0.7s ease-out forwards',
            'pop': 'pop 0.3s ease-out', 
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: 0, transform: 'translateY(10px)' },
              '100%': { opacity: 1, transform: 'translateY(0)' },
            },
            pop: {
              '0%': { transform: 'scale(0.98)' },
              '100%': { transform: 'scale(1)' },
            }
          }
        },
      },
    };
  </script>

  <script>window.USER_ID = <?= json_encode($_SESSION['id_user'] ?? 0); ?>;</script>

  <style type="text/tailwindcss">
    /* Custom Styles */
    body { font-family: 'Inter', sans-serif; }
    .nav-link { @apply relative text-slate-600 transition-colors duration-300; }
    .nav-link.active { @apply text-primary font-semibold; }
    .nav-link:not(.active):hover { @apply text-primary; }
    
    .slot-card {
      @apply border rounded-xl p-3 text-center transition-all duration-300 min-h-20 flex flex-col justify-center;
    }
    
    .slot-card.available {
      @apply bg-white border-gray-200 text-slate-700 shadow-md shadow-gray-200/50 hover:border-primary hover:shadow-lg hover:-translate-y-1 hover:bg-gray-hover cursor-pointer;
      animation: pop 0.3s ease-out;
    }
    .slot-card.available .price {
      @apply text-green-600 font-bold;
    }
    .slot-card.available:hover .time {
      @apply text-primary;
    }

    .slot-card.booked {
      @apply bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed;
    }
    
    .form-control {
      @apply w-full px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-200 hover:border-primary/70;
    }
    
    .message-box {
      @apply p-4 rounded-lg mb-4 text-sm font-medium flex items-center border;
    }
    .message-box.error { @apply bg-red-50 text-red-700 border-red-200; }
    .message-box.success { @apply bg-green-50 text-green-700 border-green-200; }
    .message-box.warning { @apply bg-yellow-50 text-yellow-800 border-yellow-200; }
    .message-box.info { @apply bg-primary-light text-primary border-primary/20; }

    /* ---------------- Sidebar Keranjang ---------------- */
    .sidebar {
      @apply fixed top-0 flex flex-col z-[2000] border-l border-solid;
      right: -420px;
      width: 380px;
      height: 100vh;
      background: #fff;
      box-shadow: -10px 0 30px rgba(10,10,20,0.12);
      transition: right 0.38s cubic-bezier(.2,.9,.2,1);
      border-left-color: #f1f3f5;
    }
    .sidebar.active { right: 0; }
    .sidebar-header { @apply p-[18px] border-b border-solid flex items-center justify-between; border-bottom-color: #f3f4f6; }
    .sidebar-body { @apply p-[14px] overflow-y-auto flex-1; }
    .sidebar-footer { @apply p-[14px] border-t border-solid; border-top-color: #f3f4f6; }
    .close-btn { @apply bg-none border-none text-xl cursor-pointer; color: #475569;}
    .keranjang-item { @apply flex justify-between gap-[10px] py-[10px] items-center border-b border-solid; border-bottom-color: #f3f4f6;}
    .keranjang-item .left { @apply flex-1; }
    .keranjang-item .right { @apply text-right min-w-[90px]; }
    .checkout-btn { @apply w-full p-[10px_12px] rounded-lg text-white font-semibold border-none cursor-pointer; background:#0b63d6; }
    .checkout-btn:disabled { @apply opacity-50 cursor-not-allowed; }

    @media (max-width: 640px) {
      .sidebar { width: 100%; right: -100%; }
      .sidebar.active { right: 0; }
    }
    /* Modal Pop-up */
    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(2,6,23,0.45);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 3000;
      padding: 20px;
    }
    .modal[style*="display:none"] { display:none; }

    .modal-backdrop {
      @apply fixed inset-0 z-[4000] flex items-center justify-center p-4;
      background-color: rgba(10, 20, 40, 0.6);
      backdrop-filter: blur(4px);
    }
    .modal-backdrop.hidden {
      display: none !important;
    }
    .modal-panel {
      @apply bg-white rounded-xl shadow-lift w-full max-w-sm overflow-hidden;
    }

  </style>
</head>
<body class="bg-softGray text-slate-900 antialiased">
 
<div id="sidebarKeranjang" class="sidebar">
   <div class="sidebar-header">
     <h4 class="font-poppins font-semibold">JADWAL DIPILIH</h4>
     <button id="closeSidebar" class="close-btn" aria-label="Tutup">&times;</button>
   </div>
   <div class="sidebar-body" id="keranjangList">
     <?php if (empty($_SESSION['keranjang'] ?? [])): ?>
       <p class="text-slate-400">Belum ada jadwal di keranjang.</p>
     <?php else: ?>
       <?php foreach ($_SESSION['keranjang'] as $i => $it): ?>
         <div class="keranjang-item" data-index="<?= $i ?>">
           <div class="left">
             <div class="text-sm font-semibold"><?= htmlspecialchars($it['jam']) ?></div>
             <div class="text-xs text-slate-500"><?= date('d M Y', strtotime($it['tanggal'])) ?></div>
             <div class="text-xs text-slate-500">Lapangan: <?= htmlspecialchars($it['nama_lapangan'] ?? 'N/A') ?></div>
           </div>
           <div class="right">
             <div class="text-sm font-semibold">Rp <?= number_format($it['harga'],0,',','.') ?></div>
             <button class="text-xs mt-2 text-red-600 remove-item-btn" data-index="<?= $i ?>" style="background:none;border:none;cursor:pointer;">Hapus</button>
           </div>
         </div>
       <?php endforeach; ?>
     <?php endif; ?>
   </div>
   <div class="sidebar-footer">
     <button id="checkoutBtn" class="checkout-btn" <?= empty($_SESSION['keranjang'] ?? []) ? 'disabled' : '' ?>>Checkout</button>
   </div>
 </div>

 <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-md">
      <div class="max-w-7xl mx-auto px-4">
        <nav class="flex items-center justify-between h-20">
          <a href="/BookingLapanganKel2/index.php" class="flex items-center gap-3">
            <div class="w-14 h-14 flex items-center justify-center transform transition-all duration-500 hover:scale-110">
              <img src="../assets/images/LogoRush.png" alt="SportField Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
            </div>
            <div>
              <div class="font-poppins font-semibold text-lg leading-tight">Rush Badminton Academy</div>
              <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan Online</div>
            </div>
          </a>
          <div class="hidden lg:flex flex-1 justify-center">
            <ul id="topNav" class="flex gap-8 items-end">
              <li>
                <a href="/BookingLapanganKel2/index.php" class="nav-link px-2 py-1 text-sm">Beranda</a>
              </li>
              <li><a href="#" class="nav-link px-2 py-1 text-sm active">Lapangan</a></li>
              <li><a href="kontak.php" class="nav-link px-2 py-1 text-sm">Kontak</a></li>
              <li>
                <a href="#" id="cartIcon" class="cart-btn text-gray-700 hover:text-primary relative cursor-pointer">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span id="cartCount"
                      class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5">
                  <?= count($_SESSION['keranjang'] ?? []) ?>
                </span>
                </a>
              </li>           
            </ul>
          </div>
          
          <div class="hidden md:flex items-center gap-4"> 
            
            <?php if (isset($_SESSION['id_user']) && $_SESSION['nama'] !== 'User Demo'): ?>
                
                <?php
                // Tentukan path foto profil
                $foto_profil = '../assets/images/default-avatar.png'; // Sediakan gambar default
                if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                    $foto_profil = '../uploads/profiles/' . htmlspecialchars($_SESSION['foto_profil']);
                }
                $nama_depan = explode(' ', htmlspecialchars($_SESSION['nama']))[0];
                ?>

                <a href="../DashPengguna.php" 
                   class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-primary transition-colors duration-300"
                   title="Lihat Dashboard">
                    <img src="<?= $foto_profil ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover border-2 border-primary-light">
                    <span>Halo, <?= $nama_depan ?></span>
                </a>
                <a href="../auth/php/logout.php" 
                   class="text-sm text-gray-500 hover:text-red-600"
                   title="Keluar"
                   id="btnLogout"> <i class="fa-solid fa-right-from-bracket"></i>
                </a>

            <?php else: ?>

                <a href="../auth/login.php" 
                  class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all duration-300">
                  Masuk
                </a>
                
                <a href="../auth/register.php" 
                  class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300 shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30">
                  Daftar
                </a>

            <?php endif; ?>
          </div>
          
          <div class="lg:hidden">
            <button id="mobileBtn" class="p-2 rounded-md hover:bg-slate-100 focus:outline-none transition-colors">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6H21M3 12H21M3 18H21" stroke="#0b1a2b" stroke-width="1.5" stroke-linecap="round" /></svg>
            </button>
          </div>
        </nav>
      </div>
      
    </header>

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
          // Variabel ini sudah dihitung di bagian atas
          $is_today = ($selected_date == date('Y-m-d'));
          $current_time_str = date('H:i:s'); 
          ?>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($jadwal_list as $jadwal): 
              $start = substr($jadwal['jam_mulai'], 0, 5);
              $end = substr($jadwal['jam_selesai'], 0, 5);
              $jadwal_id = $jadwal['id_jadwal_waktu'];
              
              // Cek 1: Sudah dibooking?
              $is_booked = isset($booked_slots[$jadwal_id]);
              
              // Cek 2: Sudah terlewat?
              $is_past_time = false;
              if ($is_today && (strtotime($jadwal['jam_mulai']) < strtotime($current_time_str))) {
                  $is_past_time = true;
              }
              
              $harga = (float)($lapangan['harga_per_jam'] ?? 0);
            ?>
              <?php if ($is_booked || $is_past_time): // Gabungkan kondisi ?>
                <div class="slot-card booked">
                  <div class="text-xs font-medium">60 Menit</div>
                  <div class="text-sm font-semibold mt-1 line-through"><?= $start ?> - <?= $end ?></div>
                  
                  <div class="text-sm font-medium mt-1">
                      <?= $is_past_time ? 'Terlewat' : 'Dipesan' ?>
                  </div>
                  </div>
              <?php else: // Jika tidak ter-booking DAN tidak terlewat ?>
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

  <footer class="bg-white border-t mt-16 py-8 text-center text-sm text-slate-500">
    © 2025 SportField — All rights reserved
  </footer>

  <div id="bookingModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 420px; margin: auto; padding: 20px; background: white; border-radius: 10px; text-align:center;">
      <button id="closeBookingModal" style="position:absolute; right:18px; top:12px; background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
      <h3 class="text-lg font-semibold mb-2">Pilih Aksi</h3>
      <p class="text-sm text-slate-600">Apakah Anda ingin langsung checkout atau masukkan ke keranjang?</p>
      <div style="margin-top:20px; display:flex; gap:12px; justify-content:center;">
        <button id="btnCheckout" class="checkout-btn" style="background:#0b63d6; border-radius:8px; padding:8px 16px; color:#fff;">Langsung Checkout</button>
        <button id="btnKeranjang" class="checkout-btn" style="background:#6b7280; border-radius:8px; padding:8px 16px; color:#fff;">Masukkan ke Keranjang</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/booking-script.js"></script>
  <div id="logoutModal" class="modal-backdrop hidden">
    <div class="modal-panel animate-pop">
        <div class="p-6 text-center">
            <i class="fa-solid fa-triangle-exclamation text-5xl text-red-500 mb-4"></i>
            
            <h3 class="font-poppins font-semibold text-lg text-slate-800 mb-2">Konfirmasi Keluar</h3>
            <p class="text-sm text-slate-500 mb-6">
                Apakah Anda yakin ingin keluar dari akun Anda?
            </p>
            
            <div class="flex justify-center gap-3">
                <button id="cancelLogoutBtn" type="button" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <button id="confirmLogoutBtn" type="button" class="px-6 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Ya, Keluar
                </button>
            </div>
        </div>
    </div>
  </div>
</body>
</html>