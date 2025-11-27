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

// 3. Ambil Data Keranjang
$items_to_pay = isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];

if (empty($items_to_pay)) {
    header("Location: booking.php");
    exit;
}

// === INISIALISASI VARIABEL ===
$produk_tambahan = []; 
$total_biaya_produk = 0;

// 4. Logika Produk (Hapus/Tambah/Skip)
if (isset($_GET['action']) && $_GET['action'] === 'remove_product' && isset($_GET['product_id'])) {
    unset($_SESSION['produk_tambahan'][$_GET['product_id']]);
    header("Location: payment.php?cart=1");
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produk'])) {
    $new_products = [];
    foreach ($_POST['produk'] as $id => $val) {
        $parts = explode('|', $val); // Format: "Harga|Nama"
        if (count($parts) >= 2) {
            $new_products[$id] = [
                'harga' => (float)$parts[0],
                'nama'  => $parts[1]
            ];
        }
    }
    $_SESSION['produk_tambahan'] = $new_products;
    header("Location: payment.php?cart=1");
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['skip_products'])) {
    unset($_SESSION['produk_tambahan']);
}

// 5. Ambil Data Produk dari Session & Hitung Total
if (isset($_SESSION['produk_tambahan']) && is_array($_SESSION['produk_tambahan'])) {
    $produk_tambahan = $_SESSION['produk_tambahan']; // Assign ke variabel lokal
    foreach ($produk_tambahan as $item) {
        $total_biaya_produk += (float)$item['harga'];
    }
}

// 6. Hitung Total Keseluruhan
$total_biaya_sewa = 0;
foreach ($items_to_pay as $item) {
    $total_biaya_sewa += (float)$item['harga'];
}
$total_biaya = $total_biaya_sewa + $total_biaya_produk;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pembayaran | Rush Academy</title>
  
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
    .modal-backdrop { display: none; }
    .modal-backdrop.open { @apply fixed inset-0 z-50 flex items-center justify-center p-4; background-color: rgba(10, 20, 40, 0.6); backdrop-filter: blur(4px); }
    .modal-panel { @apply bg-white rounded-xl shadow-lift w-full max-w-md overflow-hidden; }
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

<form id="paymentForm" action="verifikasi_payment.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" method="POST">
    <main class="max-w-5xl mx-auto px-4 py-8">
      <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 flex flex-col gap-5">
          <div class="card" id="item-list-card">
              <h3 class="font-poppins font-semibold text-base mb-4">Item yang Dibayar</h3>
              <?php foreach ($items_to_pay as $index => $item): ?>
              <div class="flex justify-between items-center py-4 border-b last:border-b-0">
                  <div>
                      <div class="text-xs font-bold text-primary uppercase mb-1">
                          <?= htmlspecialchars($item['nama_lapangan'] ?? 'Lapangan') ?>
                      </div>
                      <div class="font-semibold text-base">Jam: <?= htmlspecialchars($item['jam']) ?></div>
                      <div class="text-sm text-slate-500"><?= date('l, j F Y', strtotime($item['tanggal'])) ?></div>
                      <div class="text-sm font-semibold text-primary mt-1">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                  </div>
                  <div>
                      <button type="button" class="text-slate-400 hover:text-red-500 delete-item-btn" 
                              title="Hapus" 
                              data-index="<?= $index ?>">
                          <i class="fa-solid fa-trash-can"></i>
                      </button>
                  </div>
              </div>
              <?php endforeach; ?>
          </div>

          <div class="card">
              <h3 class="font-poppins font-semibold text-base mb-4">Metode Pembayaran</h3>
              <div class="space-y-3">
                  <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                      <div class="flex items-center gap-4">
                          <i class="fa-solid fa-qrcode text-primary text-2xl w-8 text-center"></i>
                          <span class="font-medium text-sm">QRIS</span>
                      </div>
                      <input type="radio" name="metode_pembayaran" value="qris" class="h-5 w-5 text-primary" checked>
                  </label>
                  <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                      <div class="flex items-center gap-4">
                          <i class="fa-solid fa-building-columns text-blue-800 text-2xl w-8 text-center"></i>
                          <span class="font-medium text-sm">Transfer BCA</span>
                      </div>
                      <input type="radio" name="metode_pembayaran" value="bca" class="h-5 w-5 text-primary">
                  </label>
                  <label class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                      <div class="flex items-center gap-4">
                          <i class="fa-solid fa-building-columns text-blue-600 text-2xl w-8 text-center"></i>
                          <span class="font-medium text-sm">Transfer Mandiri</span>
                      </div>
                      <input type="radio" name="metode_pembayaran" value="mandiri" class="h-5 w-5 text-primary">
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
                    <span class="font-medium">Rp <?= number_format($total_biaya_sewa, 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Biaya Produk Tambahan</span>
                    <span class="font-medium">Rp <?= number_format($total_biaya_produk, 0, ',', '.') ?></span>
                </div>
                
                <?php if (!empty($produk_tambahan)): ?>
                <div class="text-xs text-slate-500 pl-4 border-l-2 border-gray-200 ml-2 mt-2 space-y-2">
                    <?php foreach ($produk_tambahan as $id => $item): ?>
                        <div class="flex justify-between items-center group">
                            <span><?= htmlspecialchars($item['nama']) ?> (Rp <?= number_format($item['harga'], 0, ',', '.') ?>)</span>
                            <?php
                            $baseQueryParams = $_GET;
                            unset($baseQueryParams['action'], $baseQueryParams['product_id']);
                            $removeParams = array_merge($baseQueryParams, ['action' => 'remove_product', 'product_id' => $id]);
                            ?>
                            <a href="payment.php?<?= http_build_query($removeParams) ?>" 
                               class="text-slate-300 hover:text-red-500 transition-colors product-remove-btn" 
                               title="Hapus Produk">
                               <i class="fa-solid fa-circle-xmark"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="flex justify-between font-bold text-base pt-4 mt-4 border-t border-slate-200">
                    <span>Total Bayar</span>
                    <span>Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                </div>
              </div>

              <div class="card">
                  <h3 class="font-poppins font-semibold text-base mb-4">Atur Pembayaran</h3>
                  <div class="space-y-3">
                      <label class="flex justify-between items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                          <div class="flex items-center gap-3">
                              <span class="font-medium text-sm">Bayar Lunas</span>
                          </div>
                          <div class="text-right">
                              <span class="font-bold text-primary text-sm">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                          </div>
                          <input type="radio" name="payment_type" value="lunas" class="hidden payment-type" checked data-amount="<?= $total_biaya ?>">
                      </label>
                      <label class="flex justify-between items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                          <div class="flex items-center gap-3">
                              <span class="font-medium text-sm">Bayar DP (50%)</span>
                          </div>
                          <div class="text-right">
                              <span class="font-bold text-primary text-sm">Rp <?= number_format($total_biaya / 2, 0, ',', '.') ?></span>
                          </div>
                          <input type="radio" name="payment_type" value="dp" class="hidden payment-type" data-amount="<?= $total_biaya / 2 ?>">
                      </label>
                  </div>
                  <div class="flex justify-between font-bold text-base pt-4 border-t border-slate-200 mt-4">
                      <span>Total Bayar Sekarang</span>
                      <span id="currentPaymentAmount" class="text-green-600">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                  </div>
                  <input type="hidden" name="payment_amount" id="paymentAmountInput" value="<?= $total_biaya ?>">
              </div>
              
              <div class="card flex justify-between items-center cursor-pointer hover:bg-gray-50">
                  <div class="flex items-center gap-3">
                      <i class="fa-solid fa-shield-halved text-primary text-lg"></i>
                      <span class="font-semibold text-sm text-slate-700">Kebijakan Reschedule</span>
                  </div>
                  <i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i>
              </div>
              
              <button type="button" id="openVerificationModal" class="w-full text-white bg-primary hover:bg-primaryDark font-semibold rounded-lg py-3 transition-all duration-300 shadow-lg shadow-primary/30">
                  Lanjutkan ke Pembayaran
              </button>
          </div>
        </div>
      </div>
    </main>
</form> 

<footer class="bg-white border-t mt-16 py-8 text-center text-sm text-slate-500">
    © 2025 Rush Academy — All rights reserved
</footer>

<div id="verificationModal" class="modal-backdrop">
    <div class="modal-panel animate-modal-pop-in">
        <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h3 class="font-poppins font-semibold text-lg text-slate-800">Konfirmasi Pesanan</h3>
            <button id="closeVerificationModal" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <div class="p-6 text-center">
            <i class="fa-solid fa-circle-question text-5xl text-primary opacity-80 mb-4"></i>
            <h4 class="font-semibold text-slate-700 text-lg mb-2">Apakah pesanan Anda sudah sesuai?</h4>
            <p class="text-sm text-slate-500">Pastikan jadwal dan total pembayaran Anda sudah benar.</p>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button id="cancelVerificationBtn" type="button" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">Batal</button>
            <button id="confirmVerificationBtn" type="button" class="px-5 py-2 text-sm font-medium text-white bg-primary hover:bg-primaryDark rounded-lg transition-colors">Ya, Sudah Sesuai</button>
        </div>
    </div>
</div>

  <script>
    let isSafeExit = false;
    let timeLeft = <?= $remaining_seconds ?>;

    // --- 1. FUNGSI KELUAR HALAMAN (CLEANUP) ---
    function exitPage() {
        isSafeExit = true;
        window.removeEventListener('beforeunload', handleBeforeUnload);
        navigator.sendBeacon('cancel_booking.php?ajax=1');
        window.location.href = 'booking.php';
    }

    // --- 2. TOMBOL BATAL MANUAL (HEADER) ---
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

    // --- 3. CEGAH TOMBOL BACK BROWSER/HP (SWEETALERT) ---
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        if (isSafeExit) return;
        history.pushState(null, null, location.href); // Push lagi agar tetap di halaman
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: "Proses pembayaran belum selesai. Booking Anda akan dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Lanjut Bayar'
        }).then((result) => {
            if (result.isConfirmed) exitPage();
        });
    };

    // --- 4. CEGAH REFRESH / TUTUP TAB (NATIVE) ---
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
      
      // --- LOGIKA TIMER ---
      const timerElem = document.getElementById('countdown-timer');
      const timerContainer = document.getElementById('timer-container');

      const countdown = setInterval(() => {
          if (timeLeft <= 0) {
              clearInterval(countdown);
              isSafeExit = true; // Matikan guard karena akan dipaksa keluar
              window.removeEventListener('beforeunload', handleBeforeUnload);

              Swal.fire({
                  icon: 'error',
                  title: 'Waktu Habis!',
                  text: 'Batas waktu pembayaran 7 menit telah berakhir.',
                  confirmButtonColor: '#0b63d6',
                  confirmButtonText: 'Kembali ke Booking',
                  allowOutsideClick: false
              }).then(() => {
                  navigator.sendBeacon('cancel_booking.php?ajax=1');
                  window.location.href = 'booking.php'; 
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

      // --- FIX: Hapus Produk Tambahan (Bypass Alert) ---
      // Saat user klik hapus produk, kita izinkan reload halaman
      const productRemoveBtns = document.querySelectorAll('.product-remove-btn');
      productRemoveBtns.forEach(btn => {
          btn.addEventListener('click', function() {
              isSafeExit = true; // Tandai aman keluar untuk reload
          });
      });

      // --- HAPUS ITEM KERANJANG (SWEETALERT) ---
      const deleteButtons = document.querySelectorAll('.delete-item-btn');
      deleteButtons.forEach(btn => {
          btn.addEventListener('click', function() {
              const index = this.dataset.index;
              Swal.fire({
                  title: 'Hapus Item?',
                  text: "Item ini akan dihapus dari rincian.",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#d33',
                  cancelButtonColor: '#3085d6',
                  confirmButtonText: 'Ya, Hapus'
              }).then((result) => {
                  if (result.isConfirmed) {
                      isSafeExit = true; 
                      
                      const data = new URLSearchParams();
                      data.append('action', 'remove_from_cart');
                      data.append('index', index);

                      fetch('booking.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                          body: data.toString()
                      })
                      .then(r => r.json())
                      .then(res => {
                          if (res.status === 'ok') {
                              if (res.count <= 0) {
                                 navigator.sendBeacon('cancel_booking.php?ajax=1');
                                 window.location.href = 'booking.php';
                              } else {
                                 location.reload();
                              }
                          } else {
                              isSafeExit = false; 
                              Swal.fire('Gagal', res.message, 'error');
                          }
                      });
                  }
              });
          });
      });

      // --- UI PAYMENT TYPE ---
      const paymentTypes = document.querySelectorAll('.payment-type');
      const currentPaymentAmount = document.getElementById('currentPaymentAmount');
      const paymentAmountInput = document.getElementById('paymentAmountInput');
      paymentTypes.forEach(type => {
          type.addEventListener('change', function() {
              const amount = parseFloat(this.dataset.amount);
              const formattedAmount = 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
              currentPaymentAmount.textContent = formattedAmount;
              paymentAmountInput.value = amount;
          });
      });

      // --- MODAL KONFIRMASI ---
      const paymentForm = document.getElementById('paymentForm');
      const openModalBtn = document.getElementById('openVerificationModal');
      const modal = document.getElementById('verificationModal');
      const closeModalBtn = document.getElementById('closeVerificationModal');
      const cancelBtn = document.getElementById('cancelVerificationBtn');
      const confirmBtn = document.getElementById('confirmVerificationBtn');
      
      const openModal = () => modal && modal.classList.add('open');
      const closeModal = () => modal && modal.classList.remove('open');

      if (openModalBtn) openModalBtn.addEventListener('click', openModal);
      if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
      if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
      if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
      
      if (confirmBtn) {
          confirmBtn.addEventListener('click', function() {
              closeModal();
              // Izinkan pindah halaman karena mau submit
              isSafeExit = true; 
              window.removeEventListener('beforeunload', handleBeforeUnload);
              paymentForm.submit();
          });
      }
  });
  </script>
</body>
</html>