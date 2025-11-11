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

// --- LOGIKA PENGAMBILAN DATA BOOKING---
$items_to_pay = [];
if (isset($_GET['cart']) && !empty($_SESSION['keranjang'])) {
    $items_to_pay = $_SESSION['keranjang'];
} elseif (isset($_GET['direct']) && isset($_GET['id_jadwal_waktu'])) {
    // ... (Logika direct checkout, jika Anda masih menggunakannya) ...
    // Untuk saat ini, kita fokus pada alur keranjang
    $items_to_pay = $_SESSION['keranjang'] ?? [];
}

// --- LOGIKA BARU: TANGANI PRODUK TAMBAHAN (DENGAN FUNGSI HAPUS) ---
$produk_tambahan = [];
$total_biaya_produk = 0;

// 1. Aksi Hapus Produk (Prioritas 1)
if (isset($_GET['action']) && $_GET['action'] === 'remove_product' && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    if (isset($_SESSION['produk_tambahan'][$product_id])) {
        unset($_SESSION['produk_tambahan'][$product_id]);
    }
    
    // Buat URL baru tanpa parameter action/product_id untuk refresh
    $queryParams = $_GET;
    unset($queryParams['action']);
    unset($queryParams['product_id']);
    $queryString = http_build_query($queryParams);
    
    // Redirect ke URL bersih agar tidak terjadi loop hapus saat di-refresh
    header("Location: payment.php?" . $queryString);
    exit;
}
// 2. Aksi Tambah Produk (POST from produk_tambahan.php)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produk'])) {
    $_SESSION['produk_tambahan'] = $_POST['produk'];
    
    // === PERBAIKAN DIMULAI DI SINI ===
    // Paksa reload halaman via GET untuk "membersihkan" status POST.
    // Ini akan membuat form submit JavaScript berfungsi.
    header("Location: payment.php?" . htmlspecialchars($_SERVER['QUERY_STRING']));
    exit;
    // === AKHIR PERBAIKAN ===
}
// 3. Aksi Lewati (GET from produk_tambahan.php dengan parameter khusus)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['skip_products'])) {
    // User mengklik "Lewati", bersihkan session produk
    unset($_SESSION['produk_tambahan']);
}
// 4. Jika tidak ada aksi di atas (misal, refresh, atau kembali dari 'hapus'), 
// biarkan session produk apa adanya.

// Jika user datang ke payment.php dengan request GET biasa (misal: kembali dari index
// atau klik checkout tanpa melalui produk_tambahan), hapus produk_tambahan agar
// tidak menggunakan pilihan produk lama yang sudah kadaluarsa bagi alur baru.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    // hanya bersihkan jika ada produk tersimpan
    if (isset($_SESSION['produk_tambahan'])) {
        unset($_SESSION['produk_tambahan']);
    }
}

// Hitung total dari session yang tersisa
if (isset($_SESSION['produk_tambahan']) && is_array($_SESSION['produk_tambahan'])) {
    foreach ($_SESSION['produk_tambahan'] as $nama => $harga) {
        $produk_tambahan[$nama] = (float)$harga;
        $total_biaya_produk += (float)$harga;
    }
}
// --- AKHIR LOGIKA BARU ---

// --- LOGIKA LAMA: HITUNG TOTAL BIAYA ---
$total_biaya_sewa = 0;
$id_lapangan_ref = 0; // Ambil ID lapangan untuk tombol "Tambah Jadwal"

foreach ($items_to_pay as $item) {
    $total_biaya_sewa += $item['harga'];
    if ($id_lapangan_ref == 0) {
        $id_lapangan_ref = $item['id_lapangan']; // Ambil ID dari item pertama
    }
}

// Ambil detail untuk semua lapangan yang ada pada item yang dibayar
$lapangan_details = [];
$unique_ids = [];
foreach ($items_to_pay as $it) {
    $unique_ids[] = (int)$it['id_lapangan'];
}
$unique_ids = array_values(array_unique($unique_ids));

if (!empty($unique_ids)) {
    // Untuk kesederhanaan dan kompatibilitas mysqli, lakukan query per id
    $q_lap_single = "SELECT id_lapangan, nama_lapangan, tipe, foto FROM lapangan WHERE id_lapangan = ?";
    $stmt_single = mysqli_prepare($conn, $q_lap_single);
    foreach ($unique_ids as $lid) {
        mysqli_stmt_bind_param($stmt_single, "i", $lid);
        mysqli_stmt_execute($stmt_single);
        $res = mysqli_stmt_get_result($stmt_single);
        if ($row = mysqli_fetch_assoc($res)) {
            $lapangan_details[$row['id_lapangan']] = $row;
        }
    }
}
// Total keseluruhan
$total_biaya = $total_biaya_sewa + $total_biaya_produk;
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
          },
          animation: {
            'modal-fade-in': 'modal-fade-in 0.3s ease-out forwards',
            'modal-pop-in': 'modal-pop-in 0.3s ease-out forwards',
          },
          keyframes: {
            'modal-fade-in': {
              '0%': { opacity: 0 },
              '100%': { opacity: 1 },
            },
            'modal-pop-in': {
              '0%': { opacity: 0, transform: 'scale(0.95) translateY(10px)' },
              '100%': { opacity: 1, transform: 'scale(1) translateY(0)' },
            }
          }
        },
      },
    };
  </script>
  
  <style type="text/tailwindcss">
    body { font-family: 'Inter', sans-serif; }
    .card {
      @apply bg-white rounded-xl shadow-soft p-5;
    }
        .modal-backdrop {
            display: none;
        }
        .modal-backdrop.open {
            @apply fixed inset-0 z-50 flex items-center justify-center p-4;
            background-color: rgba(10, 20, 40, 0.6); /* Latar belakang gelap transparan */
            backdrop-filter: blur(4px); /* Efek blur */
        }
    .modal-panel {
      @apply bg-white rounded-xl shadow-lift w-full max-w-md overflow-hidden;
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

  <form id="paymentForm" action="verifikasi_payment.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" method="POST">
    <main class="max-w-5xl mx-auto px-4 py-8">
      <div class="grid lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 flex flex-col gap-5">
          <div class="card">
              <div class="grid grid-cols-1 gap-4">
                <?php if (!empty($lapangan_details)): ?>
                    <?php foreach ($lapangan_details as $lap): ?>
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                <i class="fa-solid fa-table-tennis-paddle-ball"></i>
                            </div>
                            <div>
                                <div class="font-poppins font-bold text-lg"><?= htmlspecialchars($lap['nama_lapangan']) ?></div>
                                <div class="text-sm text-slate-500 flex items-center gap-2">
                                    <span class="text-yellow-500 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-star text-xs"></i> 4.90
                                    </span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($lap['tipe']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-sm text-slate-500">Tidak ada lapangan yang dipilih.</div>
                <?php endif; ?>
              </div>
          </div>

          <div class="card" id="item-list-card">
              <?php foreach ($items_to_pay as $index => $item): ?>
              <div class="flex justify-between items-center py-4 border-b last:border-b-0">
                  <div>
                      <div class="font-semibold text-base">Jam: <?= htmlspecialchars($item['jam']) ?></div>
                      <div class="text-sm text-slate-500"><?= date('l, j F Y', strtotime($item['tanggal'])) ?></div>
                      <div class="text-sm font-semibold text-primary mt-1">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                  </div>
                  <div>
                      <button type="button" class="text-slate-400 hover:text-red-500 delete-item-btn" 
                              title="Hapus" 
                              data-index="<?= $index ?>" 
                              data-source="<?= (isset($_GET['cart'])) ? 'cart' : 'direct' ?>">
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
                    <span class="font-medium">Rp <?= number_format($total_biaya_sewa, 0, ',', '.') ?></span>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Biaya Produk Tambahan</span>
                    <span class="font-medium">Rp <?= number_format($total_biaya_produk, 0, ',', '.') ?></span>
                </div>

                <?php if (!empty($produk_tambahan)): ?>
                <div class="text-xs text-slate-500 pl-4 border-l-2 border-gray-200 ml-2 mt-1 space-y-1">
                    <?php
                    // Buat query string dasar (tanpa action/product_id)
                    $baseQueryParams = $_GET;
                    unset($baseQueryParams['action']);
                    unset($baseQueryParams['product_id']);
                    ?>
                    <?php foreach ($produk_tambahan as $nama => $harga): ?>
                        <div class="flex justify-between items-center">
                            <span><?= htmlspecialchars($nama) ?> (Rp <?= number_format($harga, 0, ',', '.') ?>)</span>
                            <?php
                            // Buat URL Hapus yang unik untuk item ini
                            $removeParams = array_merge($baseQueryParams, ['action' => 'remove_product', 'product_id' => $nama]);
                            $removeQueryString = http_build_query($removeParams);
                            ?>
                            <a href="payment.php?<?= htmlspecialchars($removeQueryString) ?>" 
                               class="text-red-500 hover:text-red-700 text-xs font-medium ml-2"
                               title="Hapus produk ini">
                                Hapus
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
                      <label for="pay_lunas" class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                          <div class="flex items-center gap-4">
                              <i class="fa-solid fa-money-check text-primary text-2xl w-8 text-center"></i>
                              <span class="font-medium text-sm">Bayar Lunas</span>
                          </div>
                          <div class="text-right">
                              <span class="font-bold text-primary text-sm">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                          </div>
                          <input type="radio" id="pay_lunas" name="payment_type" value="lunas" class="hidden payment-type" checked data-amount="<?= $total_biaya ?>">
                      </label>
                      <label for="pay_dp" class="flex justify-between items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-primary hover:bg-primary-light transition-all">
                          <div class="flex items-center gap-4">
                              <i class="fa-solid fa-money-bill-wave text-green-600 text-2xl w-8 text-center"></i>
                              <span class="font-medium text-sm">Bayar DP (50%)</span>
                          </div>
                          <div class="text-right">
                              <span class="font-bold text-primary text-sm">Rp <?= number_format($total_biaya / 2, 0, ',', '.') ?></span>
                          </div>
                          <input type="radio" id="pay_dp" name="payment_type" value="dp" class="hidden payment-type" data-amount="<?= $total_biaya / 2 ?>">
                      </label>
                  </div>
                  <div class="flex justify-between font-bold text-base pt-4 border-t border-slate-200 mt-4">
                      <span>Total Bayar Sekarang</span>
                      <span id="currentPaymentAmount">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                  </div>
                  <input type="hidden" name="payment_amount" id="paymentAmountInput" value="<?= $total_biaya ?>">
              </div>

              <div class="card flex justify-between items-center cursor-pointer hover:bg-gray-50">
                  <div class="flex items-center gap-3">
                      <i class="fa-solid fa-shield-halved text-primary text-lg"></i>
                      <span class="font-semibold text-sm">Kebijakan Reschedule & Pembatalan</span>
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

    <div id="verificationModal" class="modal-backdrop animate-modal-fade-in">
    <div class="modal-panel animate-modal-pop-in">
        <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h3 class="font-poppins font-semibold text-lg text-slate-800">Konfirmasi Pesanan</h3>
            <button id="closeVerificationModal" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        
        <div class="p-6 text-center">
            <i class="fa-solid fa-circle-question text-5xl text-primary opacity-80 mb-4"></i>
            <h4 class="font-semibold text-slate-700 text-lg mb-2">Apakah pesanan Anda sudah sesuai?</h4>
            <p class="text-sm text-slate-500">
                Pastikan kembali jadwal dan total pembayaran Anda sudah benar sebelum melanjutkan.
            </p>
        </div>
        
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button id="cancelVerificationBtn" type="button" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                Batal
            </button>
            <button id="confirmVerificationBtn" type="button" class="px-5 py-2 text-sm font-medium text-white bg-primary hover:bg-primaryDark rounded-lg transition-colors">
                Ya, Sudah Sesuai
            </button>
        </div>
    </div>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      
      // --- BAGIAN 1: SCRIPT HAPUS ITEM ---
      const itemListCard = document.getElementById('item-list-card');
      const redirectUrl = 'booking.php?lapangan=<?= $id_lapangan_ref ?>';

      if (itemListCard) {
          itemListCard.addEventListener('click', function(e) {
              const deleteButton = e.target.closest('.delete-item-btn');
              if (deleteButton) {
                  e.preventDefault(); 
                  const index = deleteButton.dataset.index;
                  const source = deleteButton.dataset.source;
                  if (!confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
                      return;
                  }
                  if (source === 'direct') {
                      window.location.href = redirectUrl;
                      return;
                  }
                  if (source === 'cart') {
                      const data = new URLSearchParams();
                      data.append('action', 'remove_from_cart');
                      data.append('index', index);
                      fetch('booking.php', {
                          method: 'POST',
                          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                          body: data.toString()
                      })
                      .then(response => response.json())
                      .then(res => {
                          if (res.status === 'ok') {
                              if (res.count === 0) {
                                  window.location.href = redirectUrl;
                              } else {
                                  location.reload();
                              }
                          } else {
                              alert(res.message || 'Gagal menghapus item.');
                          }
                      })
                      .catch(err => {
                          console.error('Error:', err);
                          alert('Terjadi kesalahan jaringan.');
                      });
                  }
              }
          });
      }

      // --- BAGIAN BARU: SCRIPT UNTUK PILIHAN BAYAR LUNAS / DP ---
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

      // --- BAGIAN 2: SCRIPT MODAL VERIFIKASI (INI YANG BARU) ---
      const paymentForm = document.getElementById('paymentForm');
      const openModalBtn = document.getElementById('openVerificationModal');
      const modal = document.getElementById('verificationModal');
      const closeModalBtn = document.getElementById('closeVerificationModal');
      const cancelBtn = document.getElementById('cancelVerificationBtn');
      const confirmBtn = document.getElementById('confirmVerificationBtn');

      // Fungsi untuk menampilkan modal (menggunakan kelas 'open')
      const openModal = () => {
          if (modal) {
              modal.classList.add('open');
          }
      };

      // Fungsi untuk menyembunyikan modal
      const closeModal = () => {
          if (modal) {
              modal.classList.remove('open');
          }
      };

      // Event Listeners
      if (openModalBtn) {
          openModalBtn.addEventListener('click', openModal);
      }
      if (closeModalBtn) {
          closeModalBtn.addEventListener('click', closeModal);
      }
      if (cancelBtn) {
          cancelBtn.addEventListener('click', closeModal);
      }

      // Klik di luar modal untuk menutup
      if (modal) {
          modal.addEventListener('click', function(e) {
              if (e.target === modal) {
                  closeModal();
              }
          });
      }

      // Tombol KONFIRMASI (paling penting)
      if (confirmBtn) {
          confirmBtn.addEventListener('click', function() {
              closeModal(); // 1. Sembunyikan modal
              if (paymentForm) {
                  paymentForm.submit(); // 2. Lanjutkan submit form
              }
          });
      }
  });
  </script>
  </body>
</html>