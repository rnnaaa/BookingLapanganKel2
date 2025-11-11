<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Booking — <?= htmlspecialchars($lapangan['nama_lapangan']) ?> | SportField</title>

  <!-- Tailwind -->
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
    .nav-link.active { color: #0b63d6; font-weight: 600; }
    .slot-card { @apply p-4 border rounded-lg text-center transition-all; }
    .slot-card.available { @apply bg-white border-gray-200 hover:border-primary hover:shadow-md; }
    .slot-card.booked { @apply bg-red-50 border-red-200 text-red-600; }
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
        <img src="../assets/images/lapangan1.jpg" alt="Lapangan" class="w-full h-64 object-cover rounded-lg mb-4">
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

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <?php foreach ($jadwal_list as $jadwal): 
            $start = substr($jadwal['jam_mulai'], 0, 5);
            $end = substr($jadwal['jam_selesai'], 0, 5);
            $slot = "$start-$end";
            $is_booked = isset($booked_slots[$slot]);
            $harga = (float)($jadwal['harga'] ?? 30000);
          ?>
            <div class="slot-card <?= $is_booked ? 'booked' : 'available' ?>">
              <div class="text-xs font-medium">60 Menit</div>
              <div class="text-sm font-semibold mt-1"><?= $start ?> - <?= $end ?></div>
              <div class="text-sm">
                <?= $is_booked ? 'Booked' : 'Rp ' . number_format($harga, 0, ',', '.') ?>
              </div>

              <?php if (!$is_booked): ?>
                <form method="post" class="mt-2">
                  <input type="hidden" name="action" value="book">
                  <input type="hidden" name="jadwal_id" value="<?= $jadwal['id_jadwal_waktu'] ?>">
                  <input type="hidden" name="slot" value="<?= $slot ?>">
                  <input type="text" name="cust_name" placeholder="Nama" required class="w-full text-xs px-2 py-1 border rounded mt-1">
                  <button type="submit" class="w-full mt-1 bg-primary text-white text-xs py-1.5 rounded">Booking</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <p class="text-xs text-slate-500 mt-6">
          Jadwal: <strong><?= date('d/m/Y', strtotime($selected_date)) ?></strong>
        </p>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="bg-white border-t mt-16 py-8 text-center text-sm text-slate-500">
    © 2025 SportField — All rights reserved
  </footer>
</body>
</html>