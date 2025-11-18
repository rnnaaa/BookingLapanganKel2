<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// === LOGIKA LOGOUT OTOMATIS (20 MENIT) ===
if (isset($_SESSION['id_user'])) { // Hanya cek jika user sudah login
    
    $timeout_duration = 1200; // 20 menit x 60 detik = 1200 detik

    if (isset($_SESSION['last_activity'])) {
        // Hitung selisih waktu (dalam detik)
        $inactive_time = time() - $_SESSION['last_activity'];

        if ($inactive_time > $timeout_duration) {
            // Waktu habis, hancurkan session
            session_unset();
            session_destroy();
            
            // Arahkan ke halaman login dengan pesan timeout
            // (Kita gunakan JavaScript redirect agar tidak konflik dengan header PHP lain)
            echo "<script>
                    alert('Anda telah logout otomatis karena tidak aktif selama 20 menit.');
                    window.location.href = '/BookingLapanganKel2/index.php';
                  </script>";
            exit; // Hentikan sisa script
            
        } else {
            // Jika belum timeout, perbarui waktu aktivitas terakhir
            $_SESSION['last_activity'] = time();
        }
    } else {
        // Jika 'last_activity' belum ada (misal sesi lama), set sekarang
        $_SESSION['last_activity'] = time();
    }
}

// === DEFINISI BASE URL (Sangat Penting) ===
$base_url = '/BookingLapanganKel2'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Rush Badminton</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#0b63d6",
                        primaryDark: "#094ea8",
                        softGray: "#f6f8fb",
                        accent: "#ffb500",
                        'primary-light': '#e7f0ff',
                        'gray-hover': '#f4f4f5',
                    },
                    boxShadow: {
                        lift: "0 18px 40px rgba(11,26,54,0.10)",
                        soft: "0 8px 24px rgba(11,26,54,0.06)",
                    },
                    animation: {
                        "bounce-slow": "bounce 3s infinite",
                        "pulse-slow": "pulse 3s infinite",
                        'pop': 'pop 0.3s ease-out',
                    },
                    keyframes: {
                        pop: {
                          '0%': { transform: 'scale(0.98)' },
                          '100%': { transform: 'scale(1)' },
                        }
                    }
                },
            },
            plugins: [],
        };
    </script>
    
    <script>window.USER_ID = <?= json_encode($_SESSION['id_user'] ?? 0); ?>;</script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css" />

    <style type="text/tailwindcss">
        body { font-family: 'Inter', sans-serif; background-color: #f6f8fb; }
        .nav-link { @apply relative text-slate-600 transition-colors duration-300; }
        .nav-link:not(.active):hover { @apply text-primary; }
        .nav-link.active { color: #0b63d6 !important; font-weight: 600 !important; }
        html { scroll-behavior: smooth; }
        
        /* Sidebar & Modal Styles (Disatukan disini agar tersedia di semua halaman) */
        .sidebar {
          @apply fixed top-0 flex flex-col z-[2000] border-l border-solid;
          right: -420px; width: 380px; height: 100vh; background: #fff;
          box-shadow: -10px 0 30px rgba(10,10,20,0.12);
          transition: right 0.38s cubic-bezier(.2,.9,.2,1); border-left-color: #f1f3f5;
        }
        .sidebar.active { right: 0; }
        .sidebar-header { @apply p-[18px] border-b border-solid flex items-center justify-between; border-bottom-color: #f3f4f6; }
        .sidebar-body { @apply p-[14px] overflow-y-auto flex-1; }
        .sidebar-footer { @apply p-[14px] border-t border-solid; border-top-color: #f3f4f6; }
        .close-btn { @apply bg-none border-none text-xl cursor-pointer; color: #475569;}
        .keranjang-item { @apply flex justify-between gap-[10px] py-[10px] items-center border-b border-solid; border-bottom-color: #f3f4f6;}
        .keranjang-item .left { @apply flex-1; }
        .keranjang-item .right { @apply text-right min-w-[90px]; }
        .checkout-btn { @apply w-full p-[10px_12px] rounded-lg text-white font-semibold border-none cursor-pointer; background:#0b63d6; }
        .checkout-btn:disabled { @apply opacity-50 cursor-not-allowed; }
        
        /* Style untuk .modal (dipakai oleh booking.php) */
        /* Ini (flex, items-center, justify-center) sudah cukup untuk menengahkan .modal-panel */
        .modal {
            display: none; /* Sembunyi by default, diatur oleh JS */
            @apply fixed inset-0 z-[9999] flex items-center justify-center p-4;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        
        /* BLOK CSS .modal > .modal-panel YANG KONFLIK TELAH DIHAPUS */

        .modal-backdrop {
            @apply fixed inset-0 z-[3000] flex items-center justify-center p-4;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        }
        .modal-backdrop.hidden { display: none !important; }
        
        /* .modal-panel (kotak putih) tidak perlu position fixed, biarkan flexbox yang bekerja */
        .modal-panel { @apply bg-white rounded-xl shadow-lift w-full max-w-sm; }
        
        /* Slot Card (untuk booking.php) */
        .slot-card { @apply border rounded-xl p-3 text-center transition-all duration-300 min-h-20 flex flex-col justify-center; }
        .slot-card.available { @apply bg-white border-gray-200 text-slate-700 shadow-md shadow-gray-200/50 hover:border-primary hover:shadow-lg hover:-translate-y-1 hover:bg-gray-hover cursor-pointer; }
        .slot-card.available .price { @apply text-green-600 font-bold; }
        .slot-card.available:hover .time { @apply text-primary; }
        .slot-card.booked { @apply bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed; }
        
        @media (max-width: 640px) {
            .sidebar { width: 100%; right: -100%; }
            .sidebar.active { right: 0; }
        }
    </style>
</head>
<body class="bg-softGray text-slate-900 antialiased">

    <div id="sidebarKeranjang" class="sidebar">
        <div class="sidebar-header">
            <h4 class="font-poppins font-semibold">JADWAL DIPILIH</h4>
            <button id="closeSidebar" class="close-btn" aria-label="Tutup">&times;</button>
        </div>
        <div class="sidebar-body" id="keranjangList">
            <?php if (empty($_SESSION['keranjang'] ?? [])): ?>
                <p class="text-slate-400">Belum ada jadwal di keranjang.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['keranjang'] as $i => $it): ?>
                    <div class="keranjang-item" data-index="<?= $i ?>">
                        <div class="left">
                            <div class="text-sm font-semibold"><?= htmlspecialchars($it['jam']) ?></div>
                            <div class="text-xs text-slate-500"><?= date('d M Y', strtotime($it['tanggal'])) ?></div>
                            <div class="text-xs text-slate-500">Lapangan: <?= htmlspecialchars($it['nama_lapangan'] ?? 'N/A') ?></div>
                        </div>
                        <div class="right">
                            <div class="text-sm font-semibold">Rp <?= number_format($it['harga'],0,',','.') ?></div>
                            <button class="text-xs mt-2 text-red-600 remove-item-btn" data-index="<?= $i ?>" style="background:none;border:none;cursor:pointer;">Hapus</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-footer">
            <button id="checkoutBtn" class="checkout-btn" <?= empty($_SESSION['keranjang'] ?? []) ? 'disabled' : '' ?>>Checkout</button>
        </div>
    </div>

    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-md">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center justify-between h-20">
                <a href="<?= $base_url ?>/index.php" class="flex items-center gap-3">
                    <div class="w-14 h-14 flex items-center justify-center transform transition-all duration-500 hover:scale-110">
                        <img src="<?= $base_url ?>/assets/images/LogoRush.png" alt="Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
                    </div>
                    <div>
                        <div class="font-poppins font-semibold text-lg leading-tight">Rush Badminton Academy</div>
                        <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan Online</div>
                    </div>
                </a>

                <div class="hidden lg:flex flex-1 justify-center">
                    <ul id="topNav" class="flex gap-8 items-end">
                        <li><a href="<?= $base_url ?>/index.php" class="nav-link px-2 py-1 text-sm">Beranda</a></li>
                        <li><a href="<?= $base_url ?>/BookingPengguna/booking.php" class="nav-link px-2 py-1 text-sm">Lapangan</a></li>
                        <li><a href="<?= $base_url ?>/kontak.php" class="nav-link px-2 py-1 text-sm">Kontak</a></li>
                        <li><a href="<?= $base_url ?>/member.php" class="nav-link px-2 py-1 text-sm">Member</a></li>
                        <li><a href="<?= $base_url ?>/riwayat.php" class="nav-link px-2 py-1 text-sm">Riwayat</a></li>
                        <li>
                            <a href="#" id="cartIcon" class="cart-btn text-gray-700 hover:text-primary relative cursor-pointer" title="Lihat Keranjang">
                                <i class="fa-solid fa-cart-shopping text-lg"></i>
                                <span id="cartCount" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5">
                                    <?= count($_SESSION['keranjang'] ?? []) ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="hidden md:flex items-center gap-4">
                    <?php if (isset($_SESSION['id_user']) && $_SESSION['id_user'] != 1): ?>
                        <?php
                        $foto_profil = $base_url . '/assets/images/default-avatar.png';
                        if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                            $foto_profil = $base_url . '/uploads/profiles/' . htmlspecialchars($_SESSION['foto_profil']);
                        }
                        $nama_depan = explode(' ', htmlspecialchars($_SESSION['nama']))[0];
                        ?>

                        <a href="<?= $base_url ?>/DashPengguna.php" 
                        class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-primary transition-colors duration-300"
                        title="Lihat Dashboard">
                            <img src="<?= $foto_profil ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover border-2 border-primary-light">
                            <span>Halo, <?= $nama_depan ?></span>
                        </a>
                        <a href="<?= $base_url ?>/auth/php/logout.php" id="btnLogout" class="text-sm text-gray-500 hover:text-red-600" title="Keluar">
                           <i class="fa-solid fa-right-from-bracket"></i>
                        </a>

                    <?php else: ?>

                        <a href="<?= $base_url ?>/auth/login.php" class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all duration-300">Masuk</a>
                        <a href="<?= $base_url ?>/auth/register.php" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300">Daftar</a>

                    <?php endif; ?>
                </div>

                <div class="lg:hidden">
                    <button id="mobileBtn" class="p-2 rounded-md hover:bg-slate-100 focus:outline-none transition-colors">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6H21M3 12H21M3 18H21" stroke="#0b1a2b" stroke-width="1.5" stroke-linecap="round" /></svg>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <div id="mobileNav" class="lg:hidden hidden bg-white border-t border-slate-100 shadow-lg">
        <div class="px-4 py-4 flex flex-col gap-2">
            <a href="<?= $base_url ?>/index.php" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Beranda</a>
            <a href="<?= $base_url ?>/BookingPengguna/booking.php" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Lapangan</a>
            <a href="<?= $base_url ?>/kontak.php" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Kontak</a>
            <a href="<?= $base_url ?>/member.php" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Member</a>
            <a href="<?= $base_url ?>/riwayat.php" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Riwayat</a>
            
            <div class="pt-2 flex gap-2 mt-2 border-t">
                 <?php if (isset($_SESSION['id_user']) && $_SESSION['id_user'] != 1): ?>
                    <a href="<?= $base_url ?>/DashPengguna.php" class="flex-1 text-center border border-primary text-primary py-2 rounded-lg font-medium">Dashboard</a>
                    <a href="<?= $base_url ?>/auth/php/logout.php" id="btnLogoutMobile" class="flex-1 text-center bg-red-600 text-white py-2 rounded-lg font-medium">Keluar</a>
                 <?php else: ?>
                    <a href="<?= $base_url ?>/auth/login.php" class="flex-1 text-center border border-primary text-primary py-2 rounded-lg font-medium">Masuk</a>
                    <a href="<?= $base_url ?>/auth/register.php" class="flex-1 text-center bg-primary text-white py-2 rounded-lg font-medium">Daftar</a>
                 <?php endif; ?>
            </div>
        </div>
    </div>