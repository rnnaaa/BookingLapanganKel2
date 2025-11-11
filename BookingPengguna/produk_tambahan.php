<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php';

// Logika ini disalin dari payment.php untuk mengambil data booking
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$items_to_pay = [];
$total_biaya_sewa = 0;

if (isset($_GET['cart']) && !empty($_SESSION['keranjang'])) {
    $items_to_pay = $_SESSION['keranjang'];
} elseif (isset($_GET['direct']) && isset($_GET['id_jadwal_waktu'])) {
    // ... (Logika direct checkout disederhanakan, diasumsikan user selalu via keranjang)
    // Jika Anda butuh direct checkout, logika lengkap dari payment.php bisa disalin ke sini
    $items_to_pay = $_SESSION['keranjang'] ?? []; // Fallback ke keranjang
}

if (empty($items_to_pay)) {
    // Jika tidak ada item, lempar ke booking
    header("Location: booking.php");
    exit;
}

// Hitung total sewa SAJA
foreach ($items_to_pay as $item) {
    $total_biaya_sewa += $item['harga'];
}

// Definisikan produk tambahan Anda
$products = [
    ['id' => 'cock_12', 'nama' => 'Shuttlecock (12k)', 'harga' => 12000],
    ['id' => 'cock_15', 'nama' => 'Shuttlecock (15k)', 'harga' => 15000],
    ['id' => 'cock_20', 'nama' => 'Shuttlecock (20k)', 'harga' => 20000],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Produk Tambahan | Rush Academy</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  
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
  
  <style type="text/tailwindcss">
    body { font-family: 'Inter', sans-serif; }
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
            <div class="w-14 h-14 flex items-center justify-center">
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

  <form action="payment.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>&from_products=1" method="POST">
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
            <h3 class="font-poppins font-medium text-base mb-4">Kategori</h3>
            <div class="w-full bg-primary-light border-b-2 border-primary text-primary font-semibold text-sm p-3 rounded-t-lg">
                Sewa Shuttlecock
            </div>
            
            <div class="p-4 border border-t-0 rounded-b-lg border-gray-200">
                <h4 class="font-semibold text-slate-700 mb-3">Varian</h4>
                <div class="space-y-3">
                    
                    <?php foreach ($products as $product): ?>
                    <label for="<?= $product['id'] ?>" class="flex justify-between items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <div class="font-medium text-sm"><?= htmlspecialchars($product['nama']) ?></div>
                            <div class="text-xs text-slate-500">Harga per tabung</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-sm text-primary">Rp <?= number_format($product['harga'], 0, ',', '.') ?></span>
                            <input type="checkbox" 
                                   id="<?= $product['id'] ?>" 
                                   name="produk[<?= htmlspecialchars($product['nama']) ?>]" 
                                   value="<?= $product['harga'] ?>"
                                   class="h-5 w-5 text-primary rounded focus:ring-primary product-checkbox"
                                   data-harga="<?= $product['harga'] ?>">
                        </div>
                    </label>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <div class="card">
            <h3 class="font-poppins font-semibold text-base mb-3">Ringkasan Booking Anda</h3>
            <div class="space-y-3">
                <?php foreach ($items_to_pay as $item): ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="font-semibold text-sm"><?= htmlspecialchars($item['nama_lapangan'] ?? 'Lapangan') ?></div>
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
            
            <button type="button" 
                    id="dynamicActionButton"
                    class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-all duration-300">
                Lewati
            </button>
        </div>
    </footer>
  </form> 

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseTotal = <?= $total_biaya_sewa ?>;
        let productTotal = 0;

        const totalDisplay = document.getElementById('total-display');
        const checkboxes = document.querySelectorAll('.product-checkbox');
        
        // --- Perubahan Dimulai Di Sini ---
        const actionButton = document.getElementById('dynamicActionButton');
        const paymentForm = document.querySelector('form');
  const paymentPageUrl = "payment.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>&from_products=1";

        // Style untuk tombol "Lanjutkan"
        const continueClasses = [
            'text-white', 'bg-primary', 'hover:bg-primaryDark', 
            'shadow-md', 'shadow-primary/30', 'border-primary'
        ];
        // Style untuk tombol "Lewati"
        const skipClasses = [
            'text-gray-700', 'bg-white', 'hover:bg-gray-100', 
            'border-gray-300'
        ];

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
            
            // Update tampilan total di footer
            const newTotal = baseTotal + productTotal;
            totalDisplay.textContent = formatRupiah(newTotal);

            // Update Tombol Aksi
            if (itemsSelected) {
                actionButton.textContent = 'Lanjutkan';
                actionButton.setAttribute('type', 'submit'); // Penting: Ubah jadi tombol submit
                actionButton.classList.remove(...skipClasses);
                actionButton.classList.add(...continueClasses);
            } else {
                actionButton.textContent = 'Lewati';
                actionButton.setAttribute('type', 'button'); // Penting: Ubah jadi tombol biasa
                actionButton.classList.remove(...continueClasses);
                actionButton.classList.add(...skipClasses);
            }
        }

        // Tambahkan listener ke setiap checkbox
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotalsAndButton);
        });

        // Tambahkan listener ke tombol (hanya jika tipenya BUKAN submit)
        actionButton.addEventListener('click', function(e) {
            if (actionButton.getAttribute('type') === 'button') {
                // Jika ini tombol "Lewati", arahkan manual
                e.preventDefault(); 
                window.location.href = paymentPageUrl;
            }
            // Jika tipenya "submit", biarkan form bekerja normal
        });

        // Jalankan sekali saat memuat untuk jaga-jaga jika ada yg sudah tercentang (misal, saat back)
        updateTotalsAndButton(); 
    });
  </script>
</body>
</html>