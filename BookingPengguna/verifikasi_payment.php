<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// ========================================================
// 1. CEK LOGIN & SESSION
// ========================================================
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = $_SESSION['id_user'];
$user_nama = $_SESSION['nama'] ?? 'User';

// ========================================================
// 2. LOGIKA TIMER (HOLD BOOKING)
// ========================================================
if (!isset($_SESSION['temp_booking_id']) || !isset($_SESSION['booking_expired_at'])) {
    header("Location: booking.php");
    exit;
}

$expired_time_str = $_SESSION['booking_expired_at'];
$expired_timestamp = strtotime($expired_time_str);
$remaining_seconds = $expired_timestamp - time();

if ($remaining_seconds <= 0) {
    unset($_SESSION['temp_booking_id']);
    unset($_SESSION['booking_expired_at']);
    unset($_SESSION['keranjang']);
    unset($_SESSION['produk_tambahan']);
    
    echo '<!DOCTYPE html><html><head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
          </head><body>
          <script>
            Swal.fire({
                icon: "error",
                title: "Waktu Habis!",
                text: "Batas waktu pembayaran telah berakhir.",
                confirmButtonText: "Kembali ke Booking",
                allowOutsideClick: false
            }).then(() => {
                window.location.href = "booking.php";
            });
          </script>
          </body></html>';
    exit;
}

// 3. Validasi Data POST
if (!isset($_POST['metode_pembayaran']) || !isset($_POST['payment_type']) || !isset($_POST['payment_amount'])) {
    header("Location: payment.php?cart=1"); 
    exit;
}

$metode_pembayaran = $_POST['metode_pembayaran'];
$payment_type = $_POST['payment_type'];
$payment_amount = (float)$_POST['payment_amount'];

// 4. Hitung Ulang Total (Keamanan)
$items_to_pay = $_SESSION['keranjang'] ?? [];

if (empty($items_to_pay)) {
    header("Location: booking.php");
    exit;
}

$total_biaya = 0;
foreach ($items_to_pay as $item) {
    $total_biaya += $item['harga'];
}

$total_biaya_produk = 0;
if (isset($_SESSION['produk_tambahan']) && is_array($_SESSION['produk_tambahan'])) {
    foreach ($_SESSION['produk_tambahan'] as $nama => $harga) {
        $total_biaya_produk += (float)$harga;
    }
}

$total_biaya += $total_biaya_produk;

if ($payment_type === 'lunas') {
    $expected_amount = $total_biaya;
} else { 
    $expected_amount = $total_biaya / 2;
}

if (abs($payment_amount - $expected_amount) > 1) {
    header("Location: payment.php?cart=1");
    exit;
}

$sisa_biaya = $total_biaya - $payment_amount;
$form_action = "proses_pembayaran.php"; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Verifikasi Pembayaran | Rush Academy</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { 'sans': ['Inter', 'sans-serif'], 'poppins': ['Poppins', 'sans-serif'] },
          colors: { 
            primary: "#0b63d6", 
            primaryDark: "#094ea8", 
            softGray: "#f9fafb",
            'primary-light': '#e7f0ff',
          },
          boxShadow: { 
            lift: "0 18px 40px rgba(11,26,54,0.10)", 
            soft: "0 8px 24px rgba(11,26,54,0.06)",
          }
        },
      },
    };
  </script>
  
  <style>
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

  <main class="max-w-4xl mx-auto px-4 py-8">
    <div class="card shadow-lg">
        <div class="modal-body">
            <form id="verifyForm" action="<?= $form_action ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_payment">
                <input type="hidden" name="metode_pembayaran_hidden" value="<?= htmlspecialchars($metode_pembayaran) ?>">
                <input type="hidden" name="payment_type_hidden" value="<?= htmlspecialchars($payment_type) ?>">
                <input type="hidden" name="payment_amount_hidden" value="<?= $payment_amount ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                    
                    <div class="border-r-0 md:border-r md:pr-8 border-gray-200 h-full">
                        <h4 class="font-poppins font-bold text-lg mb-4 text-slate-800">Selesaikan Pembayaran</h4>
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed bg-blue-50 p-4 rounded-lg border border-blue-100">
                            Lakukan transfer 
                            <span class="font-bold text-primary"><?= ($payment_type === 'lunas') ? 'LUNAS' : 'DP 50%' ?></span> 
                            sebesar <strong class="text-slate-800 text-base">Rp <?= number_format($payment_amount, 0, ',', '.') ?></strong> 
                            ke metode berikut:
                            <?php if ($payment_type === 'dp'): ?>
                                <br><span class="text-xs text-orange-600 mt-1 block">*Sisa Rp <?= number_format($sisa_biaya, 0, ',', '.') ?> dibayar di lapangan.</span>
                            <?php endif; ?>
                        </p>

                        <?php if ($metode_pembayaran === 'qris'): ?>
                        <div id="qris-view" class="text-center bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                            <h5 class="text-sm font-bold mb-3 uppercase tracking-wide text-slate-500">Scan QRIS</h5>
                            <img src="../assets/images/qris_rush.jpg" alt="QRIS Rush Badminton" class="w-full max-w-[250px] mx-auto rounded-lg border p-1 mb-2">
                            <p class="text-xs font-mono text-slate-500">NMID: ID1025384582157</p>
                        </div>

                        <?php elseif ($metode_pembayaran === 'bca'): ?>
                        <div id="bca-view" class="bg-[#f0f9ff] p-6 rounded-xl border border-blue-100">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="fa-solid fa-building-columns text-blue-600 text-xl"></i>
                                <h5 class="text-base font-bold text-blue-900">Bank BCA</h5>
                            </div>
                            <div class="text-sm text-slate-500 mb-1">Nomor Rekening</div>
                            <div class="text-2xl font-bold text-slate-800 tracking-wider mb-3">123 456 7890</div>
                            <div class="text-sm text-slate-500 mb-1">Atas Nama</div>
                            <div class="text-base font-semibold text-slate-800">Rush Badminton Academy</div>
                        </div>

                        <?php elseif ($metode_pembayaran === 'mandiri'): ?>
                        <div id="mandiri-view" class="bg-[#fffbeb] p-6 rounded-xl border border-yellow-100">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="fa-solid fa-building-columns text-yellow-600 text-xl"></i>
                                <h5 class="text-base font-bold text-yellow-900">Bank Mandiri</h5>
                            </div>
                            <div class="text-sm text-slate-500 mb-1">Nomor Rekening</div>
                            <div class="text-2xl font-bold text-slate-800 tracking-wider mb-3">098 765 4321</div>
                            <div class="text-sm text-slate-500 mb-1">Atas Nama</div>
                            <div class="text-base font-semibold text-slate-800">Rush Badminton Academy</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col">
                        <h4 class="font-poppins font-bold text-lg mb-4 text-slate-800">Konfirmasi Pembayaran</h4>
                        
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Pemesan</label>
                            <input type="text" name="nama_pemesan" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm font-medium text-slate-700 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all" 
                                   value="<?= htmlspecialchars($user_nama) ?>" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Upload Bukti Transfer</label>
                            
                            <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" class="hidden" accept="image/jpeg,image/png,application/pdf">

                            <label for="bukti_pembayaran" id="upload-trigger" class="flex items-center gap-3 w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-slate-500 cursor-pointer hover:bg-white hover:border-primary transition-all">
                                <i class="fa-solid fa-paperclip text-lg"></i>
                                <span class="text-sm font-medium truncate">Klik untuk pilih file (JPG, PNG, PDF)</span>
                            </label>

                            <div id="preview-container" class="hidden items-center gap-3 w-full p-3 border border-primary/30 bg-primary-light/20 rounded-lg mt-2">
                                <img id="file-thumb" src="" alt="Preview" class="w-12 h-12 object-cover rounded-md border border-gray-200 bg-white">
                                <div class="flex-1 min-w-0">
                                    <p id="file-name" class="text-sm font-semibold text-primary truncate"></p>
                                    <p id="file-size" class="text-xs text-slate-500"></p>
                                </div>
                                <button type="button" id="remove-file" class="text-slate-400 hover:text-red-500 p-2 transition-colors" title="Hapus file">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmit" class="w-full text-white bg-primary hover:bg-primaryDark font-bold rounded-xl py-3.5 shadow-lg shadow-primary/30 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                            <span>Kirim Bukti</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
  </main>
  
  <footer class="bg-white border-t mt-16 py-8 text-center text-sm text-slate-500">
    © 2025 Rush Academy — All rights reserved
  </footer>

<script>
    let isSafeExit = false;
    let timeLeft = <?= $remaining_seconds ?>;

    // --- 1. FUNGSI BATAL MANUAL (TOMBOL DI HEADER) ---
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
            if (result.isConfirmed) {
                exitPage();
            }
        });
    }

    // --- 2. FUNGSI KELUAR HALAMAN & BERSIHKAN SLOT ---
    function exitPage() {
        isSafeExit = true;
        // Matikan listener agar tidak konflik
        window.removeEventListener('beforeunload', handleBeforeUnload);
        // Kirim sinyal hapus booking ke server
        navigator.sendBeacon('cancel_booking.php?ajax=1');
        // Pindah halaman
        window.location.href = 'booking.php';
    }

    // --- 3. INTERCEPSI TOMBOL BACK (SWEETALERT UNTUK BACK BUTTON) ---
    // Kita memanipulasi history browser agar saat back ditekan, event ini tertangkap
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        if (isSafeExit) return;

        // Masukkan lagi state ke history agar user tidak langsung 'lolos' ke halaman sebelumnya
        history.pushState(null, null, location.href);

        Swal.fire({
            title: 'Yakin ingin kembali?',
            text: "Proses pembayaran belum selesai. Booking Anda akan dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Lanjut Bayar'
        }).then((result) => {
            if (result.isConfirmed) {
                exitPage();
            }
        });
    };

    // --- 4. INTERCEPSI TUTUP TAB / REFRESH (NATIVE BROWSER) ---
    // Browser TIDAK MENGIZINKAN SweetAlert di sini. Hanya teks default browser.
    const handleBeforeUnload = (e) => {
        if (isSafeExit || timeLeft <= 0) return;
        e.preventDefault();
        e.returnValue = ''; // Memunculkan dialog konfirmasi bawaan browser
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    
    // Backup: Jika user memaksa tutup tab, kirim sinyal cancel di background
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
                isSafeExit = true;
                window.removeEventListener('beforeunload', handleBeforeUnload);

                Swal.fire({
                    icon: 'error',
                    title: 'Waktu Habis!',
                    text: 'Sesi pembayaran Anda telah berakhir.',
                    confirmButtonColor: '#0b63d6',
                    allowOutsideClick: false
                }).then(() => {
                    navigator.sendBeacon('cancel_booking.php');
                    window.location.href = 'booking.php';
                });
            } else {
                const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                const s = (timeLeft % 60).toString().padStart(2, '0');
                timerElem.innerText = `${m}:${s}`;
                
                // Efek visual jika waktu < 1 menit
                if (timeLeft < 60) {
                  timerContainer.classList.remove('bg-indigo-600');
                  timerContainer.classList.add('bg-red-600', 'animate-pulse');
                }
                timeLeft--;
            }
        }, 1000);

        // --- LOGIKA FILE UPLOAD ---
        const fileInput = document.getElementById('bukti_pembayaran');
        const uploadTrigger = document.getElementById('upload-trigger');
        const previewContainer = document.getElementById('preview-container');
        const fileThumb = document.getElementById('file-thumb');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const removeFileBtn = document.getElementById('remove-file');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Terlalu Besar', text: 'Maksimal ukuran file adalah 5MB.', confirmButtonColor: '#0b63d6' });
                    this.value = ''; return;
                }
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024 < 1024) ? (file.size / 1024).toFixed(1) + ' KB' : (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => fileThumb.src = e.target.result;
                    reader.readAsDataURL(file);
                } else {
                    fileThumb.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="%230b63d6" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>'; 
                    fileThumb.classList.add('p-2'); 
                }
                uploadTrigger.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                previewContainer.classList.add('flex');
            }
        });

        removeFileBtn.addEventListener('click', function() {
            fileInput.value = ''; 
            fileThumb.src = ''; 
            fileThumb.classList.remove('p-2'); 
            uploadTrigger.classList.remove('hidden');
            previewContainer.classList.add('hidden');
            previewContainer.classList.remove('flex');
        });

        // --- VALIDASI SUBMIT ---
        const verifyForm = document.getElementById('verifyForm');
        verifyForm.addEventListener('submit', function(e) {
            if (fileInput.files.length === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Bukti Belum Diupload', text: 'Silakan pilih file bukti pembayaran terlebih dahulu.', confirmButtonColor: '#0b63d6' });
                return;
            }
            isSafeExit = true; // Izinkan pindah halaman karena sukses submit
            window.removeEventListener('beforeunload', handleBeforeUnload);
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';
        });
    });
  </script>
</body>
</html>