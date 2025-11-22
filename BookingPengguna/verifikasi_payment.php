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
    
    echo "<script>
            alert('Waktu pembayaran habis! Slot telah dilepas.');
            window.location.href = 'booking.php';
          </script>";
    exit;
}

// 3. Validasi Data
if (!isset($_POST['metode_pembayaran']) || !isset($_POST['payment_type']) || !isset($_POST['payment_amount'])) {
    header("Location: payment.php?cart=1"); 
    exit;
}

$metode_pembayaran = $_POST['metode_pembayaran'];
$payment_type = $_POST['payment_type'];
$payment_amount = (float)$_POST['payment_amount'];

// 4. Hitung Ulang Total
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

// Validasi Jumlah Bayar
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
    .file-input-wrapper {
      display: inline-flex;
      align-items: center;
      gap: .6rem;
      cursor: pointer;
      padding: .65rem .9rem;
      border: 2px dashed rgba(15,23,42,0.06);
      border-radius: .6rem;
      background: linear-gradient(180deg, rgba(243,244,246,0.6), rgba(255,255,255,0.4));
      color: #0b63d6;
      font-weight: 600;
      transition: all .18s ease;
    }
    .file-input-wrapper:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(11,26,54,0.06); }
    .file-input-wrapper i { font-size: 1.1rem; }
    #file-name-preview { font-size: .95rem; color: #0b63d6; }
    .file-preview { margin-top: .75rem; display:flex; gap:.75rem; align-items:center; }
    .file-preview img { max-width:96px; max-height:72px; border-radius:8px; object-fit:cover; border:1px solid #e6eefc }
    .muted { color: #64748b; font-size: .85rem }
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

  <main class="max-w-3xl mx-auto px-4 py-8">
    <div class="card">
        <div class="modal-body">
            <form id="verifyForm" action="<?= $form_action ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_payment">
                <input type="hidden" name="metode_pembayaran_hidden" value="<?= htmlspecialchars($metode_pembayaran) ?>">
                <input type="hidden" name="payment_type_hidden" value="<?= htmlspecialchars($payment_type) ?>">
                <input type="hidden" name="payment_amount_hidden" value="<?= $payment_amount ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="border-r-0 md:border-r md:pr-6 border-gray-200">
                        <h4 class="font-poppins font-semibold text-base mb-3">Selesaikan Pembayaran</h4>
                        <p class="text-sm text-slate-600 mb-4">Silakan lakukan pembayaran <?= ($payment_type === 'lunas') ? 'lunas' : 'DP (50%)' ?> sejumlah 
                            <strong class="text-primary">Rp <?= number_format($payment_amount, 0, ',', '.') ?></strong> 
                            menggunakan metode di bawah ini.
                            <?php if ($payment_type === 'dp'): ?>
                            <br>Sisa pembayaran sejumlah Rp <?= number_format($sisa_biaya, 0, ',', '.') ?> dapat dibayarkan di lokasi.
                            <?php endif; ?>
                        </p>

                        <?php if ($metode_pembayaran === 'qris'): ?>
                        <div id="qris-view" class="text-center">
                            <h5 class="text-sm font-semibold mb-2">Scan QRIS</h5>
                            <img src="../assets/images/qris_rush.jpg" alt="QRIS Rush Badminton" class="w-full max-w-[200px] mx-auto rounded-lg border p-1">
                            <p class="text-xs text-slate-500 text-center mt-2">NMID: ID1025384582157 - RUSH BADMINTON JEMBER</p>
                        </div>

                        <?php elseif ($metode_pembayaran === 'bca'): ?>
                        <div id="bca-view">
                            <h5 class="text-sm font-semibold mb-2">Transfer Bank BCA</h5>
                            <div class="bg-gray-100 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">No. Rekening:</div>
                                <div class="text-lg font-bold text-slate-800">123 456 7890</div>
                                <div class="text-sm text-gray-600 mt-2">Atas Nama:</div>
                                <div class="text-lg font-bold text-slate-800">Rush Badminton Academy</div>
                            </div>
                        </div>

                        <?php elseif ($metode_pembayaran === 'mandiri'): ?>
                        <div id="mandiri-view">
                            <h5 class="text-sm font-semibold mb-2">Transfer Bank Mandiri</h5>
                            <div class="bg-gray-100 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">No. Rekening:</div>
                                <div class="text-lg font-bold text-slate-800">098 765 4321</div>
                                <div class="text-sm text-gray-600 mt-2">Atas Nama:</div>
                                <div class="text-lg font-bold text-slate-800">Rush Badminton Academy</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h4 class="font-poppins font-semibold text-base mb-3">Upload Konfirmasi</h4>
                        
                        <div class="mb-4">
                            <label for="nama_pemesan" class="block text-sm font-medium mb-1 text-slate-700">Nama Pemesan</label>
                            <input type="text" id="nama_pemesan" name="nama_pemesan" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" 
                                   value="<?= htmlspecialchars($user_nama) ?>" required>
                        </div>

                        <div class="mb-6">
                          <label for="bukti_pembayaran" class="block text-sm font-medium mb-1 text-slate-700">Upload Bukti Pembayaran</label>
                          <label for="bukti_pembayaran" class="file-input-wrapper w-full justify-center" id="fileSelectBtn">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span id="file-name-preview">Pilih file bukti (jpg, png, pdf)</span>
                          </label>
                          <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" class="hidden" accept="image/*,application/pdf" required>
                          <div class="file-preview" id="filePreviewContainer" style="display:none;">
                            <img id="fileThumb" src="#" alt="preview" style="display:none;">
                            <div>
                              <div id="fileMeta" class="muted">Tidak ada file terpilih</div>
                            </div>
                          </div>
                          <div class="text-xs text-slate-400 mt-2">Maksimum 5MB. Format: JPG, PNG atau PDF.</div>
                        </div>

                        <button type="submit" id="btnSubmit" class="w-full text-white bg-primary hover:bg-primaryDark font-semibold rounded-lg py-3 transition-all duration-300 shadow-md">
                            Kirim Bukti & Selesaikan
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

  <div id="cancelModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden bg-black/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none">
    <div id="cancelModalContent" class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-[320px] p-6 text-center transform scale-95 transition-transform duration-300">
        <h3 class="text-lg font-bold text-slate-800 mb-2">Batalkan Pesanan</h3>
        <p class="text-sm text-slate-500 mb-6 leading-relaxed">
            Apakah anda yakin untuk membatalkan Booking? Slot akan dilepas untuk orang lain.
        </p>
        <div class="flex flex-col gap-3">
            <button id="btnCancelYes" class="w-full bg-[#2563EB] hover:bg-[#1d4ed8] text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-200">IYA</button>
            <button id="btnCancelNo" class="w-full bg-white border-2 border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-50 transition-all">TIDAK</button>
        </div>
    </div>
  </div>

  <script>
    // 1. VARIABLE GLOBAL & FUNGSI MODAL
    // Ditaruh di luar DOMContentLoaded agar bisa diakses oleh onclick di Header
    let isSafeExit = false;
    let timeLeft = <?= $remaining_seconds ?>;

    // Fungsi Penjaga Halaman (BeforeUnload)
    const handleBeforeUnload = (e) => {
        if (isSafeExit || timeLeft <= 0) {
            return;
        }
        e.preventDefault();
        e.returnValue = '';
    };

    // Pasang Penjaga
    window.addEventListener('beforeunload', handleBeforeUnload);

    // Handler Beacon
    window.addEventListener('pagehide', function () {
        if (!isSafeExit) {
            navigator.sendBeacon('cancel_booking.php');
        }
    });

    // FUNGSI MODAL GLOBAL
    function showCancelModal() {
        const modal = document.getElementById('cancelModal');
        const content = document.getElementById('cancelModalContent');
        if(modal && content) {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }
    }

    function hideCancelModal() {
        const modal = document.getElementById('cancelModal');
        const content = document.getElementById('cancelModalContent');
        if(modal && content) {
            modal.classList.add('opacity-0', 'pointer-events-none');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    }

    // Fungsi Trigger Manual (Dipanggil Header)
    window.triggerManualCancel = function() {
        showCancelModal();
    };

    // === LOGIKA DOM ===
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Event Listener Modal Custom ---
        const btnCancelYes = document.getElementById('btnCancelYes');
        const btnCancelNo = document.getElementById('btnCancelNo');
        const cancelModal = document.getElementById('cancelModal');

        if(btnCancelNo) btnCancelNo.addEventListener('click', hideCancelModal);
        
        if(btnCancelYes) {
            btnCancelYes.addEventListener('click', function() {
                // === PENTING: HAPUS PENJAGA HALAMAN AGAR TIDAK MUNCUL ALERT BROWSER ===
                isSafeExit = true;
                window.removeEventListener('beforeunload', handleBeforeUnload);
                
                // Eksekusi Batal
                navigator.sendBeacon('cancel_booking.php');
                window.location.href = 'booking.php';
            });
        }

        if(cancelModal) {
            cancelModal.addEventListener('click', (e) => {
                if(e.target === cancelModal) hideCancelModal();
            });
        }

        // --- TIMER ---
        const timerElem = document.getElementById('countdown-timer');
        const timerContainer = document.getElementById('timer-container');

        const countdown = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(countdown);
                
                // Auto exit aman
                isSafeExit = true;
                window.removeEventListener('beforeunload', handleBeforeUnload);

                alert('Waktu pembayaran habis! Slot akan dilepas.');
                window.location.href = 'booking.php';
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

        // --- SUBMIT FORM ---
        const verifyForm = document.getElementById('verifyForm');
        if (verifyForm) {
            verifyForm.addEventListener('submit', function() {
                // Hapus penjaga saat submit sukses
                isSafeExit = true;
                window.removeEventListener('beforeunload', handleBeforeUnload);

                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                btn.innerText = 'Memproses...';
            });
        }

        // --- PREVIEW GAMBAR ---
        const fileInput = document.getElementById('bukti_pembayaran');
        const fileSelectBtn = document.getElementById('fileSelectBtn');
        const fileNamePreview = document.getElementById('file-name-preview');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        const fileThumb = document.getElementById('fileThumb');
        const fileMeta = document.getElementById('fileMeta');

        if (fileSelectBtn && fileInput) {
            fileSelectBtn.addEventListener('click', function(e){
              e.preventDefault();
              fileInput.click();
            });
            fileInput.addEventListener('change', function(){
              const f = this.files[0];
              if (!f) return;
              if (f.size > 5 * 1024 * 1024) {
                alert('File terlalu besar. Maksimum 5MB.');
                this.value = '';
                return;
              }
              fileNamePreview.textContent = f.name;
              filePreviewContainer.style.display = 'flex';
              fileMeta.textContent = (f.type || 'Unknown') + ' • ' + Math.round(f.size/1024) + ' KB';
              if (f.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(evt){
                  fileThumb.src = evt.target.result;
                  fileThumb.style.display = 'block';
                };
                reader.readAsDataURL(f);
              } else {
                fileThumb.style.display = 'none';
                fileThumb.src = '#';
              }
            });
        }
    });
  </script>
</body>
</html>