<?php
date_default_timezone_set('Asia/Jakarta');
// Booking/booking.php
session_start();
require '../config/database.php';
// --- BAGIAN USER (ASUMSI LOGGED IN ATAU DEMO) ---
if (!isset($_SESSION['id_user'])) {
    $_SESSION['id_user'] = 1;
    $_SESSION['nama'] = "User Demo";
}
$user_id = $_SESSION['id_user'];

// --- PARAMETER ---
$selected_lapangan = (int)($_GET['lapangan'] ?? 0);

// --- PERBAIKAN: INI ADALAH LOGIKA TANGGAL DEFAULT ---
// Jika $_GET['date'] tidak ada (saat pertama kali buka), maka gunakan date('Y-m-d') (tanggal hari ini)
$selected_date     = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}
// --- AKHIR LOGIKA TANGGAL DEFAULT ---

// --- AMBIL DATA LAPANGAN (TERMASUK UNTUK DROPDOWN) ---
if ($selected_lapangan <= 0) {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif' ORDER BY id_lapangan LIMIT 1"));
    $selected_lapangan = $first['id_lapangan'] ?? 0;
}

$lapangan_query = "SELECT id_lapangan, nama_lapangan, deskripsi, foto, tipe FROM lapangan WHERE id_lapangan = ?";
$stmt = mysqli_prepare($conn, $lapangan_query);
mysqli_stmt_bind_param($stmt, "i", $selected_lapangan);
mysqli_stmt_execute($stmt);
$lapangan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$lapangan) {
    die("Lapangan tidak ditemukan.");
}

$all_lapangan_result = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");

// --- LOGIKA PERBAIKAN TANGGAL & STATUS HARI ---
$hari_num = date('N', strtotime($selected_date));
$hari_map = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
$hari = $hari_map[$hari_num - 1];

// Ambil MIN dan MAX tanggal yang tersedia di DB untuk input date picker
$date_range_query = mysqli_query($conn, "SELECT MIN(tanggal) AS min_date, MAX(tanggal) AS max_date FROM jadwal_harian WHERE id_lapangan = $selected_lapangan AND tanggal >= CURDATE()");
$date_range = mysqli_fetch_assoc($date_range_query);
$min_date = $date_range['min_date'] ?? date('Y-m-d');
$max_date = $date_range['max_date'] ?? date('Y-m-d');

// Cek status hari HANYA untuk tanggal yang dipilih
$hari_status = 'tidak_tersedia';
$hari_status_message = '';

if (strtotime($selected_date) < strtotime(date('Y-m-d'))) {
    $hari_status = 'kadaluarsa';
    $hari_status_message = 'Anda tidak dapat memesan jadwal di masa lalu.';
} else {
    $status_query = "SELECT status_hari FROM jadwal_harian WHERE id_lapangan = ? AND tanggal = ?";
    $stmt = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt, "is", $selected_lapangan, $selected_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $hari_status = $row['status_hari']; // 'tersedia', 'penuh', 'libur'
        if ($hari_status === 'penuh') $hari_status_message = 'Jadwal penuh untuk tanggal ini.';
        if ($hari_status === 'libur') $hari_status_message = 'Lapangan libur pada tanggal ini.';
    } else {
        // Jika tidak ada di DB (misal, melebihi max_date)
        $hari_status = 'belum_tersedia';
        $hari_status_message = 'Jadwal untuk tanggal ini belum diatur oleh admin.';
    }
    mysqli_stmt_close($stmt);
}
// --- AKHIR LOGIKA PERBAIKAN ---

// JADWAL JAM (HANYA JIKA 'tersedia')
$jadwal_list = [];
if ($hari_status === 'tersedia') {
    $jam_min = ($hari == 'sabtu' || $hari == 'minggu') ? '07:00:00' : '08:00:00';
    $jadwal_query = "SELECT * FROM jadwal_waktu WHERE id_lapangan = ? AND jam_mulai >= ? ORDER BY jam_mulai";
    $stmt = mysqli_prepare($conn, $jadwal_query);
    mysqli_stmt_bind_param($stmt, "is", $selected_lapangan, $jam_min);
    mysqli_stmt_execute($stmt);
    $jadwal_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($jadwal_result)) {
        $jadwal_list[] = $row;
    }
}

// CEK BOOKED (Logika Anda sudah benar, menggunakan detail_booking)
$booked_slots = [];
$check_query = "
    SELECT jw.jam_mulai, jw.jam_selesai, db.id_jadwal_waktu
    FROM detail_booking db
    JOIN booking b ON db.id_booking = b.id_booking
    JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
    WHERE jw.id_lapangan = ? AND b.tanggal = ? AND b.status IN ('disetujui', 'menunggu')
";
$stmt = mysqli_prepare($conn, $check_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $selected_lapangan, $selected_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $start = substr($row['jam_mulai'], 0, 5);
        $end = substr($row['jam_selesai'], 0, 5);
        // Simpan berdasarkan ID dan juga berdasarkan Teks (sesuai logika Anda)
        $booked_slots["$start-$end"] = true; 
        $booked_slots[$row['id_jadwal_waktu']] = true;
    }
    mysqli_stmt_close($stmt);
}

// Hitung jadwal tersedia
$available_count = 0;
foreach ($jadwal_list as $jadwal) {
    if (!isset($booked_slots[$jadwal['id_jadwal_waktu']])) {
        $available_count++;
    }
}

// PROSES BOOKING (Logika Anda sudah benar)
$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'book_slot') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $slot_text = $_POST['slot'] ?? ''; // '08:00-09:00'

    if ($hari_status !== 'tersedia') {
        $message = "<div class='message-box error'><i class='fas fa-exclamation-triangle mr-2'></i>Lapangan tidak tersedia pada hari ini!</div>";
    } elseif ($jadwal_id && !isset($booked_slots[$jadwal_id]) && !isset($booked_slots[$slot_text])) {
        mysqli_begin_transaction($conn);
        try {
            // ... (Logika INSERT booking dan detail_booking Anda sudah benar) ...
            $q = "INSERT INTO booking (id_user, id_lapangan, tanggal, status, payment_status) 
                  VALUES (?, ?, ?, 'menunggu', 'belum_bayar')";
            $stmt = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $selected_lapangan, $selected_date);
            mysqli_stmt_execute($stmt);
            $booking_id = mysqli_insert_id($conn);

            $q = "SELECT harga_per_slot FROM jadwal_waktu WHERE id_jadwal_waktu = ?";
            $stmt = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($stmt, "i", $jadwal_id);
            mysqli_stmt_execute($stmt);
            $harga = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['harga_per_slot'] ?? 30000;

            $q = "INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($stmt, "iid", $booking_id, $jadwal_id, $harga);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            $message = "<div class='message-box success'><i class='fas fa-check-circle mr-2'></i>Booking berhasil! Menunggu konfirmasi.</div>";
            $booked_slots[$slot_text] = true;
            $booked_slots[$jadwal_id] = true;
            $available_count--;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "<div class='message-box error'><i class='fas fa-exclamation-triangle mr-2'></i>Gagal: " . $e->getMessage() . "</div>";
        }
    }
}
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
            // Aksen baru
            'primary-light': '#e7f0ff', // Biru sangat muda
            'gray-hover': '#f4f4f5', // Abu-abu untuk hover slot
          },
          boxShadow: { 
            lift: "0 18px 40px rgba(11,26,54,0.10)", 
            soft: "0 8px 24px rgba(11,26,54,0.06)",
            'lg-soft': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05)'
          },
          // Animasi "muncul"
          animation: {
            'fade-in': 'fadeIn 0.5s ease-out forwards',
            'fade-in-delay': 'fadeIn 0.7s ease-out forwards',
            'pop': 'pop 0.3s ease-out', // Animasi hover slot
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
  
  <style>
    /* Custom Styles */
    body { font-family: 'Inter', sans-serif; }
    .nav-link { @apply relative text-slate-600; }
    .nav-link.active { @apply text-primary font-semibold; }
    .nav-link:not(.active):hover { @apply text-primary; }
    
    /* Indikator Navigasi (dari kode Anda) */
    .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: #0b63d6; /* primary */
    }

    /* PERBAIKAN: Slot Card (Sesuai Permintaan) */
    .slot-card {
      @apply border rounded-xl p-3 text-center transition-all duration-300 min-h-20 flex flex-col justify-center;
    }
    
    .slot-card.available {
      @apply bg-white border-gray-200 text-slate-700 hover:border-primary hover:shadow-lg hover:-translate-y-1 hover:bg-gray-hover cursor-pointer;
      animation: pop 0.3s ease-out; /* Animasi "muncul" saat dimuat */
    }
    .slot-card.available:hover {
      /* Tidak perlu 'pop' di hover, cukup translate-y */
    }
    .slot-card.available .price {
      @apply text-green-600 font-bold;
    }
    .slot-card.available:hover .time {
      @apply text-primary; /* Aksen biru saat hover */
    }

    .slot-card.booked {
      @apply bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed;
    }
    
    /* PERBAIKAN: Form Inputs (Lebih modern) */
    .form-control {
      @apply w-full px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-200;
    }
    
    /* PERBAIKAN: Pesan Status (Dengan ikon dan warna tema) */
    .message-box {
      @apply p-4 rounded-lg mb-4 text-sm font-medium flex items-center border;
    }
    .message-box.error {
      @apply bg-red-50 text-red-700 border-red-200;
    }
    .message-box.success {
      @apply bg-green-50 text-green-700 border-green-200;
    }
    .message-box.warning {
      @apply bg-yellow-50 text-yellow-800 border-yellow-200;
    }
    .message-box.info {
      @apply bg-primary-light text-primary border-primary/20; /* Aksen Biru */
    }
    
  </style>
</head>
<body class="bg-softGray text-slate-900 antialiased">

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
                <a href="/BookingLapanganKel2/index.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300 active">Beranda</a>
              </li>
              <li><a href="#" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lapangan</a></li>
              <li><a href="#pricing" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Harga</a></li>
              <li><a href="#location" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lokasi</a></li>
              <li><a href="about.html" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Kontak</a></li>
            </ul>
          </div>
          <div class="hidden md:flex items-center gap-4"> <a href="keranjang.php" 
               class="relative text-slate-600 p-2 rounded-full hover:bg-primary-light hover:text-primary transition-all duration-300"
               title="Keranjang Belanja">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-xs font-medium text-white">
                  0 </span>
            </a>
            
            <div class="h-6 w-px bg-gray-200"></div>

            <a href="login.php" 
              class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all duration-300">
              Masuk
            </a>
            
            <a href="register.php" 
              class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300 shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30">
              Daftar
            </a>
          </div>
          <div class="lg:hidden">
            <button id="mobileBtn" class="p-2 rounded-md hover:bg-slate-100 focus:outline-none transition-colors">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6H21M3 12H21M3 18H21" stroke="#0b1a2b" stroke-width="1.5" stroke-linecap="round" /></svg>
            </button>
          </div>
        </nav>
      </div>
      <div class="hidden lg:block">
        <div id="navIndicator" class="mx-auto max-w-7xl px-4">
          <div class="h-0.5 bg-transparent relative">
            <div id="navLine" class="absolute h-0.5 bg-primary rounded transition-all duration-300" style="width: 68px; left: 0px"></div>
          </div>
        </div>
      </div>
    </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-2 gap-8">

      <div class="bg-white rounded-xl shadow-soft p-6 animate-fade-in">
        <div class="overflow-hidden rounded-lg mb-4 shadow-inner">
            <img src="../assets/images/<?= htmlspecialchars($lapangan['foto'] ?? 'default.jpg') ?>" 
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
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($jadwal_list as $jadwal): 
              $start = substr($jadwal['jam_mulai'], 0, 5);
              $end = substr($jadwal['jam_selesai'], 0, 5);
              $slot_text = "$start-$end";
              $jadwal_id = $jadwal['id_jadwal_waktu'];
              $is_booked = isset($booked_slots[$jadwal_id]) || isset($booked_slots[$slot_text]);
              $harga = (float)($jadwal['harga_per_slot'] ?? 30000);
            ?>
              <?php if ($is_booked): ?>
                <div class="slot-card booked">
                  <div class="text-xs font-medium">60 Menit</div>
                  <div class="text-sm font-semibold mt-1 line-through"><?= $start ?> - <?= $end ?></div>
                  <div class="text-sm font-medium mt-1">Dipesan</div>
                </div>
              <?php else: ?>
                <form method="post" class="contents">
                  <input type="hidden" name="action" value="book_slot">
                  <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                  <input type="hidden" name="slot" value="<?= $slot_text ?>">
                  <button type="submit" class="slot-card available w-full h-full">
                    <div class="text-xs font-medium text-slate-500">60 Menit</div>
                    <div class="text-sm font-semibold mt-1 time"><?= $start ?> - <?= $end ?></div>
                    <div class="text-sm mt-1 price">
                      Rp <?= number_format($harga, 0, ',', '.') ?>
                    </div>
                  </button>
                </form>
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

  <script>
    document.addEventListener("DOMContentLoaded", () => {
        const topNav = document.getElementById("topNav");
        const navLine = document.getElementById("navLine");
        const activeLink = topNav.querySelector(".active");

        if (activeLink && navLine) {
            navLine.style.width = `${activeLink.offsetWidth}px`;
            navLine.style.left = `${activeLink.offsetLeft - topNav.offsetLeft}px`;
        }
    });
  </script>
</body>
</html>