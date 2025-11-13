<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Tentukan halaman aktif untuk navbar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="SportField - Booking lapangan olahraga dengan mudah. Pesan lapangan futsal, badminton, basket dengan sistem booking yang cepat dan aman." />
    <meta name="keywords" content="booking lapangan, futsal, badminton, basket, olahraga, sportfield" />
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
                    },
                    boxShadow: {
                        lift: "0 18px 40px rgba(11,26,54,0.10)",
                        soft: "0 8px 24px rgba(11,26,54,0.06)",
                    },
                    borderRadius: {
                        xlcard: "14px",
                    },
                    animation: {
                        "bounce-slow": "bounce 3s infinite",
                        "pulse-slow": "pulse 3s infinite",
                    },
                },
            },
            plugins: [],
        };
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />

    <style>
        .nav-link.active {
            color: #0b63d6 !important;
            font-weight: 600 !important;
        }

        #navLine {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #0b63d6;
        }

        html {
            scroll-behavior: smooth;
        }
        
        .btn-primary {
            background-color: #0b63d6;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #094ea8;
            transform: scale(1.05);
        }
        
        .btn-outline {
            border: 1px solid #0b63d6;
            color: #0b63d6;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background-color: #0b63d6;
            color: white;
            transform: scale(1.05);
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            right: -420px;
            width: 380px;
            height: 100vh;
            background: #fff;
            box-shadow: -10px 0 30px rgba(10,10,20,0.12
                    .sidebar.active { right: 0; }
        .sidebar-header { padding: 18px; border-bottom: 1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
        .sidebar-body { padding: 14px; overflow-y: auto; flex: 1; }
        .sidebar-footer { padding: 14px; border-top: 1px solid #f3f4f6; }
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #475569;}
        .keranjang-item { display:flex; justify-content:space-between; gap:10px; padding:10px 0; align-items:center; border-bottom:1px solid #f3f4f6;}
        .keranjang-item .left { flex:1; }
        .keranjang-item .right { text-align:right; min-width:90px; }
        .checkout-btn { width:100%; padding:10px 12px; border-radius:8px; background:#0b63d6; color:#fff; font-weight:600; border:none; cursor:pointer; }
        .checkout-btn:disabled { opacity:0.5; cursor:not-allowed; }
        
        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        .modal-panel {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }
        .modal-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        @media (max-width: 640px) {
            .sidebar { width: 100%; right: -100%; }
            .sidebar.active { right: 0; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-softGray text-slate-900 antialiased">

    <div id="sidebarKeranjang" class="sidebar <?= (isset($_SESSION['show_sidebar']) && $_SESSION['show_sidebar']) ? 'active' : '' ?>">
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
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-14 h-14 flex items-center justify-center transform transition-all duration-500 hover:scale-110">
                        <img src="assets/images/LogoRush.png" alt="SportField Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
                    </div>
                    <div>
                        <div class="font-poppins font-semibold text-lg leading-tight">Rush Badminton Academy</div>
                        <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan Online</div>
                    </div>
                </a>

                <div class="hidden lg:flex flex-1 justify-center">
                    <ul id="topNav" class="flex gap-8 items-end">
                        <li>
                            <a href="index.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Beranda</a>
                        </li>
                        <li>
                            <a href="/BookingLapanganKel2/BookingPengguna/booking.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lapangan</a>
                        </li>
                        <li>
                            <a href="kontak.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Kontak</a>
                        </li>
                        <li>
                            <a href="member.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Member</a>
                        </li>
                        <li>
                            <a href="riwayat.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Riwayat</a>
                        </li>

                        <li>
                            <div id="cartIcon" class="cart-btn text-gray-700 hover:text-primary relative cursor-pointer mr-2" title="Lihat Keranjang">
                                <i class="fa-solid fa-cart-shopping text-lg"></i>
                                <span id="cartCount"
                                    class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5">
                                    <?= count($_SESSION['keranjang'] ?? []) ?>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="hidden lg:block">
                    <div id="navIndicator" class="mx-auto max-w-7xl px-4">
                        <div class="h-0.5 bg-transparent relative">
                            <div id="navLine" class="absolute h-0.5 bg-primary rounded transition-all duration-300"></div>
                        </div>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-4">
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <?php
                        // Tentukan path foto profil
                        $foto_profil = 'assets/images/default-avatar.png';
                        if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                            $foto_profil = 'uploads/profiles/' . htmlspecialchars($_SESSION['foto_profil']);
                        }
                        
                        // Ambil nama depan saja
                        $nama_depan = explode(' ', htmlspecialchars($_SESSION['nama']))[0];
                        ?>

                        <a href="../BookingLapanganKel2/auth/dash_user.php" 
                        class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-primary transition-colors duration-300"
                        title="Lihat Dashboard">
                            <img src="<?= $foto_profil ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover border-2 border-primary-light">
                            <span>Halo, <?= $nama_depan ?></span>
                        </a>

                    <?php else: ?>

                        <a href="auth/login.php" 
                        class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all duration-300">
                        Masuk
                        </a>
                        
                        <a href="auth/register.php" 
                        class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300">
                        Daftar
                        </a>

                    <?php endif; ?>
                </div>

                <div class="lg:hidden">
                    <button id="mobileBtn" class="p-2 rounded-md hover:bg-slate-100 focus:outline-none transition-colors">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M3 6H21M3 12H21M3 18H21" stroke="#0b1a2b" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
            </nav>
        </div>

        <div class="hidden lg:block">
            <div id="navIndicator" class="mx-auto max-w-7xl px-4">
                <div class="h-0.5 bg-transparent relative">
                    <div id="navLine" class="absolute h-0.5 bg-primary rounded transition-all duration-300" style="width: 68px; left: 0px"></div>
                </div>
            </div>
        </div>
    </header>

    <div id="mobileNav" class="lg:hidden hidden bg-white border-t border-slate-100 shadow-lg">
        <div class="px-4 py-4 flex flex-col gap-2">
            <a href="#home" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors active">Beranda</a>
            <a href="#facilities" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Lapangan</a>
            <a href="about.html" class="py-3 px-2 rounded-lg hover:bg-slate-50 transition-colors">Kontak</a>
            <div class="pt-2 flex gap-2 mt-2">
                <button class="flex-1 border border-primary text-primary py-2 rounded-lg font-medium" data-modal-open="loginModal">Masuk</button>
                <button class="flex-1 bg-primary text-white py-2 rounded-lg font-medium" data-modal-open="registerModal">Daftar</button>
            </div>
        </div>
    </div>