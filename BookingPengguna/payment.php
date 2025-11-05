<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// --- BAGIAN USER ---
// Halaman pembayaran HARUS login, tidak bisa demo
if (!isset($_SESSION['id_user'])) {
    // Jika tidak ada user, redirect ke login
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['id_user'];

// --- LOGIKA PENGAMBILAN DATA ---
// Ada 2 cara masuk ke halaman ini:
// 1. ?cart=1 (dari checkout keranjang)
// 2. ?direct=1&id_jadwal_waktu=... (dari "Langsung Checkout" di booking.php)

$items_to_pay = [];
$id_lapangan_ref = 0;
$lapangan_data = null;
$total_biaya = 0;

if (isset($_GET['cart']) && !empty($_SESSION['keranjang'])) {
    // 1. Ambil dari Keranjang Session
    $items_to_pay = $_SESSION['keranjang'];
    if (isset($items_to_pay[0]['id_lapangan'])) {
        $id_lapangan_ref = (int)$items_to_pay[0]['id_lapangan'];
    }

} elseif (isset($_GET['direct']) && isset($_GET['id_jadwal_waktu'])) {
    // 2. Ambil dari "Langsung Checkout"
    $id_jadwal_waktu = (int)$_GET['id_jadwal_waktu'];
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
    $id_lapangan_ref = (int)$_GET['id_lapangan'];

    // Ambil detail jadwal dari DB
    $q_jadwal = "SELECT jam_mulai, jam_selesai, harga_per_slot FROM jadwal_waktu WHERE id_jadwal_waktu = ?";
    $stmt_j = mysqli_prepare($conn, $q_jadwal);
    mysqli_stmt_bind_param($stmt_j, "i", $id_jadwal_waktu);
    mysqli_stmt_execute($stmt_j);
    $res_j = mysqli_stmt_get_result($stmt_j);
    
    if ($jadwal_data = mysqli_fetch_assoc($res_j)) {
        // Masukkan ke array $items_to_pay agar formatnya sama
        $items_to_pay[] = [
            'id_jadwal_waktu' => $id_jadwal_waktu,
            'id_lapangan' => $id_lapangan_ref,
            'tanggal' => $tanggal,
            'jam' => substr($jadwal_data['jam_mulai'], 0, 5) . ' - ' . substr($jadwal_data['jam_selesai'], 0, 5),
            'harga' => (float)$jadwal_data['harga_per_slot']
        ];
    }
}

// Jika tidak ada item, redirect kembali
if (empty($items_to_pay) || $id_lapangan_ref == 0) {
    header("Location: booking.php");
    exit;
}

// Ambil data lapangan (venue)
$q_lap = "SELECT nama_lapangan, tipe FROM lapangan WHERE id_lapangan = ?";
$stmt_l = mysqli_prepare($conn, $q_lap);
mysqli_stmt_bind_param($stmt_l, "i", $id_lapangan_ref);
mysqli_stmt_execute($stmt_l);
$lapangan_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_l));
if (!$lapangan_data) {
    die("Lapangan tidak ditemukan.");
}

// Hitung total
foreach ($items_to_pay as $item) {
    $total_biaya += $item['harga'];
}

// Di sini Anda bisa menambahkan logika untuk menyimpan data $items_to_pay ke DB
// ... (misalnya saat tombol "Lanjutkan ke Pembayaran" ditekan)
// ...

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pembayaran | Rush Academy</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  
  <script>
    // Konfigurasi Tailwind (Sama seperti booking.php)
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sans': ['Inter', 'sans-serif'],
            'poppins': ['Poppins', 'sans-serif'],
          },
          colors: { 
            primary: "#0b63d6", 
            primaryDark: "#094ea8", 
            softGray: "#f9fafb",
            'primary-light': '#e7f0ff',
          },
          boxShadow: { 
            lift: "0 18px 40px rgba(11,26,54,0.10)", 
            soft: "0 8px 24px rgba(11,26,54,0.06)",
            'lg-soft': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05)'
          }
        },
      },
    };
  </script>
  
  <style>
    body { font-family: 'Inter', sans-serif; }
    /* Style kartu kustom untuk halaman ini */
    .card {
      @apply bg-white rounded-xl shadow-soft p-5;
    }
  </style>
</head>

<body class="bg-softGray text-slate-900 antialiased">

 <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-md">
      <div class="max-w-5xl mx-auto px-4">
        <nav class="flex items-center justify-start h-20">
          <a href="/BookingLapanganKel2/index.php" class="flex items-center gap-3">
            <div class="w-14 h-14 flex items-center justify-center transform transition-all duration-500 hover:scale-110">
              <img src="../assets/images/LogoRush.png" alt="Rush Academy Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
            </div>
            <div>
              <div class="font-poppins font-semibold text-lg leading-tight">Rush Badminton Academy</div>
              <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan Online</div>
            </div>
          </a>
          </nav>
      </div>
    </header>

  <main class="max-w-5xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-3 gap-6">

      <div class="lg:col-span-2 flex flex-col gap-5">
        
        <div class="card">
            <h2 class="font-poppins font-bold text-xl mb-1"><?= htmlspecialchars($lapangan_data['nama_lapangan']) ?></h2>
            <div class="text-sm text-slate-500 flex items-center gap-2">
                <span class="text-yellow-500 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-star text-xs"></i> 4.90
                </span>
                <span>•</span>
                <span><?= htmlspecialchars($lapangan_data['tipe']) ?></span>
            </div>
        </div>

        <div class="card">
            <?php foreach ($items_to_pay as $item): ?>
            <div class="flex justify-between items-center py-4 border-b last:border-b-0">
                <div>
                    <div class="font-semibold text-base">Jam: <?= htmlspecialchars($item['jam']) ?></div>
                    <div class="text-sm text-slate-500"><?= date('l, j F Y', strtotime($item['tanggal'])) ?></div>
                    <div class="text-sm font-semibold text-primary mt-1">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                </div>
                <div>
                    <button class="text-slate-400 hover:text-red-500" title="Hapus">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <a href="booking.php?lapangan=<?= $id_lapangan_ref ?>" class="block text-sm font-semibold text-primary mt-4 hover:underline">
                <i class="fa-solid fa-arrow-left mr-2"></i>Tambah Jadwal
            </a>
        </div>

        <div class="card">
            <h3 class="font-poppins font-semibold text-base mb-4">Metode Pembayaran</h3>
            <div class="space-y-3">
                
                <label for="pay_qris" class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                    <div class="flex items-center gap-4">
                        <i class="fa-solid fa-qrcode text-primary text-2xl w-8 text-center"></i>
                        <span class="font-medium text-sm">QRIS</span>
                    </div>
                    <input type="radio" id="pay_qris" name="metode_pembayaran" value="qris" class="h-5 w-5 text-primary focus:ring-primary focus:ring-2" checked>
                </label>

                <label for="pay_bca" class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                    <div class="flex items-center gap-4">
                        <i class="fa-solid fa-building-columns text-blue-800 text-2xl w-8 text-center"></i>
                        <span class="font-medium text-sm">Transfer BCA</span>
                    </div>
                    <input type="radio" id="pay_bca" name="metode_pembayaran" value="bca" class="h-5 w-5 text-primary focus:ring-primary focus:ring-2">
                </label>

                <label for="pay_mandiri" class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                    <div class="flex items-center gap-4">
                        <i class="fa-solid fa-building-columns text-blue-600 text-2xl w-8 text-center"></i>
                        <span class="font-medium text-sm">Transfer Mandiri</span>
                    </div>
                    <input type="radio" id="pay_mandiri" name="metode_pembayaran" value="mandiri" class="h-5 w-5 text-primary focus:ring-primary focus:ring-2">
                </label>

            </div>
        </div>
        </div>

      <div class="lg:col-span-1">
        <div class="sticky top-28 flex flex-col gap-5">
            
            <div class="card">
                <h3 class="font-poppins font-semibold text-base mb-4">Rincian Biaya</h3>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-600">Biaya Sewa</span>
                    <span class="font-medium">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between text-sm mb-4">
                    <span class="text-slate-600">Biaya Produk Tambahan</span>
                    <span class="font-medium">Rp0</span>
                </div>
                <div class="flex justify-between font-bold text-base pt-4 border-t border-slate-200">
                    <span>Total Bayar</span>
                    <span>Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="card">
                <h3 class="font-poppins font-semibold text-base mb-4">Atur Pembayaran</h3>
                <div class="flex justify-between items-center text-sm p-3 bg-primary-light rounded-lg border border-primary/20">
                    <span class="font-semibold text-primary">Bayar Lunas</span>
                    <span class="font-bold text-primary">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="card flex justify-between items-center cursor-pointer hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-shield-halved text-primary text-lg"></i>
                    <span class="font-semibold text-sm">Kebijakan Reschedule & Pembatalan</span>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i>
            </div>
            
            <button class="w-full text-white bg-primary hover:bg-primaryDark font-semibold rounded-lg py-3 transition-all duration-300 shadow-lg shadow-primary/30">
                Lanjutkan ke Pembayaran
            </button>
        </div>
      </div>
    </div>
  </main>
  <footer class="bg-white border-t mt-16 py-8 text-center text-sm text-slate-500">
    © 2025 Rush Academy — All rights reserved
  </footer>
</body>
</html>