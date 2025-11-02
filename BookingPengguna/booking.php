<?php
// Booking/booking.php
session_start();
require '../config/database.php';

// === USER (demo) ===
if (!isset($_SESSION['id_user'])) {
    $_SESSION['id_user'] = 1;
    $_SESSION['nama'] = "User Demo";
}
$user_id = $_SESSION['id_user'];

// === PARAMETER ===
$selected_lapangan = (int)($_GET['lapangan'] ?? 0);
$selected_date     = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// === AMBIL LAPANGAN PERTAMA ===
if ($selected_lapangan <= 0) {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_lapangan FROM lapangan ORDER BY id_lapangan LIMIT 1"));
    $selected_lapangan = $first['id_lapangan'] ?? 0;
}

// === AMBIL DATA LAPANGAN ===
$lapangan_query = "SELECT * FROM lapangan WHERE id_lapangan = ?";
$stmt = mysqli_prepare($conn, $lapangan_query);
mysqli_stmt_bind_param($stmt, "i", $selected_lapangan);
mysqli_stmt_execute($stmt);
$lapangan_result = mysqli_stmt_get_result($stmt);
$lapangan = mysqli_fetch_assoc($lapangan_result);

if (!$lapangan) {
    die("Lapangan tidak ditemukan. Tambahkan di database.");
}

// === DROPDOWN LAPANGAN ===
$all_lapangan_result = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY nama_lapangan");

// === JADWAL WAKTU ===
$jadwal_query = "SELECT * FROM jadwal_waktu WHERE id_lapangan = ? ORDER BY jam_mulai";
$stmt = mysqli_prepare($conn, $jadwal_query);
mysqli_stmt_bind_param($stmt, "i", $selected_lapangan);
mysqli_stmt_execute($stmt);
$jadwal_result = mysqli_stmt_get_result($stmt);

$jadwal_list = [];
while ($row = mysqli_fetch_assoc($jadwal_result)) {
    $jadwal_list[] = $row;
}

// === CEK BOOKED ===
$booked_slots = [];
$check_query = "
    SELECT jw.jam_mulai, jw.jam_selesai 
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
        $booked_slots["$start-$end"] = true;
    }
    mysqli_stmt_close($stmt);
}

$available_count = count($jadwal_list) - count($booked_slots);

// === PROSES BOOKING (KLIK KOTAK) ===
$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'book_slot') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $slot = $_POST['slot'] ?? '';

    if ($jadwal_id && $slot && !isset($booked_slots[$slot])) {
        mysqli_begin_transaction($conn);
        try {
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
            $harga = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['harga_per_slot'] ?? 30000.00;

            $q = "INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($stmt, "iid", $booking_id, $jadwal_id, $harga);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            $message = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Booking berhasil! Menunggu konfirmasi.</div>";
            $booked_slots[$slot] = true;
            $available_count--;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Gagal: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Booking — <?= htmlspecialchars($lapangan['nama_lapangan']) ?> | SportField</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "#0b63d6",
            primaryDark: "#094ea8",
            softGray: "#f6f8fb",
          },
          boxShadow: {
            lift: "0 18px 40px rgba(11,26,54,0.10)",
            soft: "0 8px 24px rgba(11,26,54,0.06)",
          },
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />

  <style>
    .slot-card {
      @apply border-2 rounded-xl p-3 text-center transition-all;
      min-height: 80px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .slot-card.available {
      @apply bg-white border-gray-300 hover:border-primary hover:shadow-md cursor-pointer;
    }
    .slot-card.booked {
      @apply bg-red-50 border-red-300 text-red-600 cursor-not-allowed;
    }
    .slot-card.available:hover {
      transform: translateY(-2px);
    }
  </style>
</head>
<body class="bg-softGray text-slate-900 antialiased">

 <!-- HEADER -->
  <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-md">
      <div class="max-w-7xl mx-auto px-4">
        <nav class="flex items-center justify-between h-20">
          <!-- Logo -->
          <a href="/BookingLapanganKel2/index.php" class="flex items-center gap-3">
            <div class="w-14 h-14 flex items-center justify-center transform transition-all duration-500 hover:scale-110">
              <img src="../uploads/images/LogoRush.png" alt="SportField Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
            </div>
            <div>
              <div class="font-poppins font-semibold text-lg leading-tight">SportField</div>
              <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan</div>
            </div>
          </a>

          <!-- Desktop Navigation -->
          <div class="hidden lg:flex flex-1 justify-center">
            <ul id="topNav" class="flex gap-8 items-end">
              <li>
                <a href="/BookingLapanganKel2/index.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300 active">Beranda</a>
              </li>
              <li>
                <a href="/BookingLapanganKel2/BookingPengguna/booking.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lapangan</a>
              </li>
              <li>
                <a href="#pricing" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Harga</a>
              </li>
              <li>
                <a href="#location" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lokasi</a>
              </li>
              <li>
                <a href="about.html" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Kontak</a>
              </li>
            </ul>
          </div>

          <!-- Auth Buttons (Desktop) -->
          <div class="hidden md:flex items-center gap-3">
            <button class="border border-primary text-primary px-4 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-all duration-300" data-modal-open="loginModal">Masuk</button>
            <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primaryDark transition-all duration-300" data-modal-open="registerModal">Daftar</button>
          </div>

          <!-- Mobile Menu Toggle -->
          <div class="lg:hidden">
            <button id="mobileBtn" class="p-2 rounded-md hover:bg-slate-100 focus:outline-none transition-colors">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 6H21M3 12H21M3 18H21" stroke="#0b1a2b" stroke-width="1.5" stroke-linecap="round" />
              </svg>
            </button>
          </div>
        </nav>
      </div>

      <!-- Navigation Indicator (Desktop) -->
      <div class="hidden lg:block">
        <div id="navIndicator" class="mx-auto max-w-7xl px-4">
          <div class="h-0.5 bg-transparent relative">
            <div id="navLine" class="absolute h-0.5 bg-primary rounded transition-all duration-300" style="width: 68px; left: 0px"></div>
          </div>
        </div>
      </div>
    </header>

  <!-- MAIN -->
  <main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-2 gap-8">

      <!-- LAPANGAN INFO -->
      <div class="bg-white rounded-xl shadow-soft p-6">
        <img src="../uploads/images/lapangan1.jpg" alt="Lapangan" class="w-full h-64 object-cover rounded-lg mb-4">
        <h1 class="text-2xl font-bold font-poppins"><?= htmlspecialchars($lapangan['nama_lapangan']) ?></h1>
        <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($lapangan['deskripsi'] ?? 'Biasa') ?></p>
        <div class="flex gap-2 mt-3 text-sm text-slate-600">
          <span>Badminton</span>
          <span>Indoor</span>
          <span>Karpet vinyl</span>
        </div>
        <button class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
          <?= $available_count ?> Jadwal Tersedia
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>

      <!-- BOOKING SLOTS -->
      <div class="bg-white rounded-xl shadow-soft p-6">
        <div class="flex flex-wrap gap-3 mb-6">
          <form method="get" class="flex-1">
            <label class="block text-sm font-medium mb-1">Lapangan</label>
            <select name="lapangan" onchange="this.form.submit()" class="w-full px-3 py-2 border rounded-lg text-sm">
              <?php mysqli_data_seek($all_lapangan_result, 0); ?>
              <?php while ($row = mysqli_fetch_assoc($all_lapangan_result)): ?>
                <option value="<?= $row['id_lapangan'] ?>" <?= $row['id_lapangan'] == $selected_lapangan ? 'selected' : '' ?>>
                  <?= htmlspecialchars($row['nama_lapangan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </form>
          <form method="get" class="flex-1">
            <input type="hidden" name="lapangan" value="<?= $selected_lapangan ?>">
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input type="date" name="date" value="<?= $selected_date ?>" onchange="this.form.submit()" class="w-full px-3 py-2 border rounded-lg text-sm">
          </form>
        </div>

        <?= $message ?>

        <!-- GRID SLOT -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <?php foreach ($jadwal_list as $jadwal): 
            $start = substr($jadwal['jam_mulai'], 0, 5);
            $end = substr($jadwal['jam_selesai'], 0, 5);
            $slot = "$start-$end";
            $is_booked = isset($booked_slots[$slot]);
            $harga = (float)($jadwal['harga_per_slot'] ?? 30000);
          ?>
            <?php if ($is_booked): ?>
              <!-- BOOKED: Tidak bisa diklik -->
              <div class="slot-card booked">
                <div class="text-xs font-medium">60 Menit</div>
                <div class="text-sm font-semibold mt-1"><?= $start ?> - <?= $end ?></div>
                <div class="text-sm font-medium">Booked</div>
              </div>
            <?php else: ?>
              <!-- AVAILABLE: Kotak penuh bisa diklik -->
              <form method="post" class="contents">
                <input type="hidden" name="action" value="book_slot">
                <input type="hidden" name="jadwal_id" value="<?= $jadwal['id_jadwal_waktu'] ?>">
                <input type="hidden" name="slot" value="<?= $slot ?>">
                <button type="submit" class="slot-card available w-full h-full">
                  <div class="text-xs font-medium">60 Menit</div>
                  <div class="text-sm font-semibold mt-1"><?= $start ?> - <?= $end ?></div>
                  <div class="text-sm text-primary font-medium">
                    Rp <?= number_format($harga, 0, ',', '.') ?>
                  </div>
                </button>
              </form>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <p class="text-xs text-slate-500 mt-6">
          Jadwal: <strong><?= date('d/m/Y', strtotime($selected_date)) ?></strong>
        </p>
      </div>
    </div>
  </main>

   <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-100">
      <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid md:grid-cols-3 gap-6">
          <div>
            <div class="font-poppins font-semibold text-lg">SportField</div>
            <div class="text-sm text-slate-500 mt-2">Booking lapangan cepat & aman untuk semua olahraga.</div>
          </div>
          <div>
            <div class="font-semibold mb-2">Navigasi</div>
            <ul class="text-sm text-slate-600 space-y-1">
              <li><a href="#lapangan" class="hover:text-primary">Lapangan</a></li>
              <li><a href="#penawaran" class="hover:text-primary">Penawaran</a></li>
              <li><a href="#fasilitas" class="hover:text-primary">Fasilitas</a></li>
              <li><a href="#harga" class="hover:text-primary">Paket</a></li>
            </ul>
          </div>
          <div>
            <div class="font-semibold mb-2">Kontak</div>
            <div class="text-sm text-slate-600">admin@sportfield.id • +62 812 3456 7890</div>
            <div class="mt-3 text-sm text-slate-500">Alamat: Jl. Olahraga No.1</div>
          </div>
        </div>

        <div class="mt-8 text-center text-xs text-slate-500">© 2025 SportField — Semua hak dilindungi</div>
      </div>
    </footer>
</body>
</html>