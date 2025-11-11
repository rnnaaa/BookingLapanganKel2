<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// --- BAGIAN USER ---
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['id_user'];
$user_nama = $_SESSION['nama'] ?? 'User';

// --- AMBIL METODE PEMBAYARAN DAN TIPE PEMBAYARAN DARI POST ---
if (!isset($_POST['metode_pembayaran']) || !isset($_POST['payment_type']) || !isset($_POST['payment_amount'])) {
    // Jika tidak ada data lengkap, tendang kembali ke payment.php
    header("Location: payment.php?" . htmlspecialchars($_SERVER['QUERY_STRING']));
    exit;
}
$metode_pembayaran = $_POST['metode_pembayaran'];
$payment_type = $_POST['payment_type'];
$payment_amount = (float)$_POST['payment_amount'];

// --- LOGIKA PENGAMBILAN DATA (SAMA SEPERTI PAYMENT.PHP) ---
// Kita perlu menjalankan ulang logika ini untuk mendapatkan Total Biaya
// (Kita tidak bisa percaya total biaya dari POST, harus hitung ulang)
$items_to_pay = [];
$total_biaya = 0;

if (isset($_GET['cart']) && !empty($_SESSION['keranjang'])) {
    $items_to_pay = $_SESSION['keranjang'];
} elseif (isset($_GET['direct']) && isset($_GET['id_jadwal_waktu'])) {
    $id_jadwal_waktu = (int)$_GET['id_jadwal_waktu'];
    $q_jadwal = "SELECT harga_per_jam FROM jadwal_waktu WHERE id_jadwal_waktu = ?";
    $stmt_j = mysqli_prepare($conn, $q_jadwal);
    mysqli_stmt_bind_param($stmt_j, "i", $id_jadwal_waktu);
    mysqli_stmt_execute($stmt_j);
    $res_j = mysqli_stmt_get_result($stmt_j);
    if ($jadwal_data = mysqli_fetch_assoc($res_j)) {
        $items_to_pay[] = ['harga' => (float)$jadwal_data['harga_per_jam']];
    }
}

if (empty($items_to_pay)) {
    header("Location: booking.php");
    exit;
}

// Hitung total lagi
// Jika ada produk tambahan di session, tambahkan ke total juga (sama seperti di payment.php)
$total_biaya_produk = 0;
if (isset($_SESSION['produk_tambahan']) && is_array($_SESSION['produk_tambahan'])) {
  foreach ($_SESSION['produk_tambahan'] as $nama => $harga) {
    $total_biaya_produk += (float)$harga;
  }
}

foreach ($items_to_pay as $item) {
  $total_biaya += $item['harga'];
}

// Tambahkan biaya produk ke total keseluruhan
$total_biaya += $total_biaya_produk;

// Validasi payment_amount berdasarkan payment_type
if ($payment_type === 'lunas') {
    $expected_amount = $total_biaya;
} else { // dp
    $expected_amount = $total_biaya / 2;
}
if ($payment_amount != $expected_amount) {
    // Jika tidak cocok, tendang kembali
    header("Location: payment.php?" . htmlspecialchars($_SERVER['QUERY_STRING']));
    exit;
}

$sisa_biaya = $total_biaya - $payment_amount;

// Tujuan form upload
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
  
  <link rel="stylesheet" href="../assets/css/verifikasi.css">
  
  <script>
    // Konfigurasi Tailwind (Sama seperti payment.php)
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
    /* File input styling */
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
    .amount-badge { background: linear-gradient(90deg,#0b63d6,#094ea8); color:white; padding:.45rem .8rem; border-radius:999px; font-weight:700 }
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

  <main class="max-w-3xl mx-auto px-4 py-8">
    <div class="card">
        <div class="modal-body">
            <form action="<?= $form_action ?>" method="POST" enctype="multipart/form-data">
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
                        <div id="qris-view">
                            <h5 class="text-sm font-semibold mb-2">Scan QRIS</h5>
                            <img src="../assets/images/qris_rush.jpg" alt="QRIS Rush Badminton" class="w-full max-w-xs mx-auto rounded-lg border p-1">
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

            <div class="mb-4">
              <label for="bukti_pembayaran" class="block text-sm font-medium mb-1 text-slate-700">Upload Bukti Pembayaran</label>
              <label for="bukti_pembayaran" class="file-input-wrapper" id="fileSelectBtn">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span id="file-name-preview">Klik untuk memilih bukti (jpg, png, pdf)</span>
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

                        <button type="submit" class="w-full text-white bg-primary hover:bg-primaryDark font-semibold rounded-lg py-3 transition-all duration-300">
                            Saya Sudah Bayar
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
  
  <script src="../assets/js/verifikasi.js"></script>
  <script>
    (function(){
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
          // Basic validation
          const maxSize = 5 * 1024 * 1024; // 5MB
          if (f.size > maxSize) {
            alert('File terlalu besar. Maksimum 5MB.');
            this.value = '';
            return;
          }
          // Update name
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
            // Not an image
            fileThumb.style.display = 'none';
            fileThumb.src = '#';
          }
        });
      }
    })();
  </script>
</body>
</html>