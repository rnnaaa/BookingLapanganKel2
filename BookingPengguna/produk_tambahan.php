<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Cek Session Hold & Timer
if (!isset($_SESSION['temp_booking_id']) || !isset($_SESSION['booking_expired_at'])) {
    header("Location: booking.php");
    exit;
}

$expired_time = strtotime($_SESSION['booking_expired_at']);
$remaining_seconds = $expired_time - time();

if ($remaining_seconds <= 0) {
    header("Location: cancel_booking.php");
    exit;
}

// 3. Data Keranjang
$items_to_pay = $_SESSION['keranjang'] ?? [];
if (empty($items_to_pay)) {
    header("Location: booking.php");
    exit;
}

$total_biaya_sewa = 0;
foreach ($items_to_pay as $item) {
    $total_biaya_sewa += (float)$item['harga'];
}

// Ambil Produk dari DB
$products = [];
$query_produk = "SELECT * FROM produk WHERE status = 'aktif' ORDER BY kategori ASC, nama_produk ASC";
$result_produk = mysqli_query($conn, $query_produk);
while ($row = mysqli_fetch_assoc($result_produk)) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Produk Tambahan | Rush Academy</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { 'sans': ['Inter', 'sans-serif'], 'poppins': ['Poppins', 'sans-serif'] },
          colors: { primary: "#0b63d6", primaryDark: "#094ea8", softGray: "#f9fafb", 'primary-light': '#e7f0ff' },
          boxShadow: { lift: "0 18px 40px rgba(11,26,54,0.10)", soft: "0 8px 24px rgba(11,26,54,0.06)" }
        },
      },
    };
  </script>
  <style type="text/tailwindcss">
    body { font-family: 'Inter', sans-serif; }
    .card { @apply bg-white rounded-xl shadow-soft p-5; }
  </style>
</head>

<body class="bg-softGray text-slate-900 antialiased">

 <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-sm transition-all">
      <div class="max-w-5xl mx-auto px-4">
        <nav class="flex items-center justify-between h-20">
          <a href="#" class="flex items-center gap-4 pointer-events-none">
            <div class="w-14 h-14 flex items-center justify-center">
              <img src="../assets/images/LogoRush.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="hidden sm:block">
              <h1 class="font-poppins font-bold text-xl text-slate-900 leading-tight">Rush Badminton Academy</h1>
              <p class="text-sm font-medium text-slate-500 mt-0.5">Booking Lapangan Online</p>
            </div>
          </a>
          <div class="flex items-center gap-4">
              <div id="timer-container" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-full shadow-md transition-colors duration-300">
                  <i class="fa-regular fa-clock text-xs opacity-80"></i>
                  <span id="countdown-timer" class="font-mono font-bold text-sm tracking-wider">00:00</span>
              </div>
              <button onclick="triggerManualCancel()" class="text-sm font-medium text-slate-500 hover:text-red-600 flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-red-50 transition-all" title="Batalkan Pesanan">
                  <i class="fa-solid fa-right-from-bracket text-lg"></i>
                  <span class="hidden sm:inline">Batalkan Booking</span>
              </button>
          </div>
        </nav>
      </div>
    </header>

  <form id="productForm" action="payment.php?cart=1&from_products=1" method="POST">
    <main class="max-w-3xl mx-auto px-4 py-8">
       <div class="flex flex-col gap-5">
        <div class="card flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-light rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-box-open text-primary text-xl"></i>
            </div>
            <div>
                <h2 class="font-poppins font-semibold text-lg">Produk Tambahan</h2>
                <p class="text-sm text-slate-500">Opsional, bisa Anda lewati.</p>
            </div>
        </div>

        <div class="card">
            <h3 class="font-poppins font-medium text-base mb-4">Daftar Produk</h3>
            
            <?php if(empty($products)): ?>
                <p class="text-sm text-slate-500 text-center py-4">Tidak ada produk tambahan yang tersedia saat ini.</p>
            <?php else: ?>
                <div class="w-full bg-primary-light border-b-2 border-primary text-primary font-semibold text-sm p-3 rounded-t-lg">
                    Perlengkapan & Minuman
                </div>
                <div class="p-4 border border-t-0 rounded-b-lg border-gray-200">
                    <h4 class="font-semibold text-slate-700 mb-3">Pilih Item</h4>
                    <div class="space-y-3">
                        <?php foreach ($products as $product): ?>
                        <label for="prod_<?= $product['id_produk'] ?>" class="flex justify-between items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <div>
                                <div class="font-medium text-sm"><?= htmlspecialchars($product['nama_produk']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($product['kategori']) ?></div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-sm text-primary">Rp <?= number_format($product['harga'], 0, ',', '.') ?></span>
                                
                                <input type="checkbox" 
                                       id="prod_<?= $product['id_produk'] ?>" 
                                       name="produk[<?= $product['id_produk'] ?>]" 
                                       value="<?= $product['harga'] . '|' . htmlspecialchars($product['nama_produk']) ?>"
                                       class="h-5 w-5 text-primary rounded focus:ring-primary product-checkbox"
                                       data-harga="<?= $product['harga'] ?>">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="font-poppins font-semibold text-base mb-3">Ringkasan Booking Anda</h3>
            <div class="space-y-3">
                <?php foreach ($items_to_pay as $item): ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="font-semibold text-sm text-primary uppercase mb-1"><?= htmlspecialchars($item['nama_lapangan'] ?? 'Lapangan') ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($item['jam']) ?> • <?= date('d M Y', strtotime($item['tanggal'])) ?></div>
                    </div>
                    <span class="font-medium text-sm text-slate-700">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
      </div>
    </main>

    <footer class="sticky bottom-0 bg-white border-t border-gray-200 shadow-lg p-4 z-10">
        <div class="max-w-3xl mx-auto flex justify-between items-center">
            <div>
                <div class="text-xs text-slate-500">Total Bayar</div>
                <div id="total-display" class="font-poppins font-bold text-xl text-primary">
                    Rp <?= number_format($total_biaya_sewa, 0, ',', '.') ?>
                </div>
            </div>
            <button type="button" id="dynamicActionButton" class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-all duration-300">Lewati</button>
        </div>
    </footer>
  </form> 

  <script>
    let isSafeExit = false;
    let timeLeft = <?= $remaining_seconds ?>;

    // --- 1. FUNGSI KELUAR HALAMAN ---
    function exitPage() {
        isSafeExit = true;
        window.removeEventListener('beforeunload', handleBeforeUnload);
        navigator.sendBeacon('cancel_booking.php?ajax=1');
        window.location.href = 'booking.php';
    }

    // --- 2. TOMBOL BATAL MANUAL ---
    function triggerManualCancel() {
        Swal.fire({
            title: 'Batalkan Booking?',
            text: "Slot yang sudah dipilih akan dilepas kembali.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Kembali'
        }).then((result) => {
            if (result.isConfirmed) exitPage();
        });
    }

    // --- 3. CEGAH BACK BUTTON (SWEETALERT) ---
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        if (isSafeExit) return;
        history.pushState(null, null, location.href);
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: "Booking Anda belum selesai dan akan dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Lanjut'
        }).then((result) => {
            if (result.isConfirmed) exitPage();
        });
    };

    // --- 4. CEGAH REFRESH/CLOSE (NATIVE) ---
    const handleBeforeUnload = (e) => {
        if (isSafeExit || timeLeft <= 0) return;
        e.preventDefault();
        e.returnValue = '';
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    window.addEventListener('pagehide', function () {
        if (!isSafeExit) navigator.sendBeacon('cancel_booking.php?ajax=1');
    });
    
    // === DOM LOADED ===
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- TIMER ---
        const timerElem = document.getElementById('countdown-timer');
        const timerContainer = document.getElementById('timer-container');
        
        const countdown = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(countdown);
                isSafeExit = true; 
                window.removeEventListener('beforeunload', handleBeforeUnload);

                Swal.fire({
                  icon: 'error',
                  title: 'Waktu Habis!',
                  text: 'Batas waktu pembayaran 7 menit telah berakhir.',
                  confirmButtonColor: '#0b63d6',
                  confirmButtonText: 'Kembali ke Booking',
                  allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        navigator.sendBeacon('cancel_booking.php?ajax=1');
                        window.location.href = 'booking.php'; 
                    }
                });
            } else {
                const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                const s = (timeLeft % 60).toString().padStart(2, '0');
                timerElem.innerText = `${m}:${s}`;
                if (timeLeft < 60) {
                  timerContainer.classList.remove('bg-indigo-600');
                  timerContainer.classList.add('bg-red-600', 'animate-pulse');
                }
                timeLeft--;
            }
        }, 1000);

        // --- LOGIKA UI PRODUK ---
        const baseTotal = <?= $total_biaya_sewa ?>;
        let productTotal = 0;
        const totalDisplay = document.getElementById('total-display');
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const actionButton = document.getElementById('dynamicActionButton');
        
        const continueClasses = ['text-white', 'bg-primary', 'hover:bg-primaryDark', 'shadow-md', 'border-transparent'];
        const skipClasses = ['text-gray-700', 'bg-white', 'hover:bg-gray-100', 'border-gray-300'];

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function updateTotalsAndButton() {
            productTotal = 0;
            let itemsSelected = false;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    productTotal += parseFloat(cb.dataset.harga);
                    itemsSelected = true;
                }
            });
            totalDisplay.textContent = formatRupiah(baseTotal + productTotal);

            if (itemsSelected) {
                actionButton.textContent = 'Lanjutkan';
                actionButton.setAttribute('type', 'submit');
                actionButton.classList.remove(...skipClasses);
                actionButton.classList.add(...continueClasses);
            } else {
                actionButton.textContent = 'Lewati';
                actionButton.setAttribute('type', 'button');
                actionButton.classList.remove(...continueClasses);
                actionButton.classList.add(...skipClasses);
            }
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateTotalsAndButton));

        // Tombol Lanjut / Lewati
        actionButton.addEventListener('click', function(e) {
            isSafeExit = true;
            window.removeEventListener('beforeunload', handleBeforeUnload);

            if (actionButton.getAttribute('type') === 'button') {
                e.preventDefault();
                window.location.href = "payment.php?cart=1&from_products=1";
            }
        });
        
        document.getElementById('productForm').addEventListener('submit', function() {
            isSafeExit = true;
            window.removeEventListener('beforeunload', handleBeforeUnload);
        });
    });
  </script>
</body>
</html>