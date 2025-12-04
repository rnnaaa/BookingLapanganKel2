<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// === DEFINISI BASE URL ===
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$folder_project = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? '/BookingLapanganKel2' : ''; 
$base_url = $protocol . "://" . $host . $folder_project;

// === AUTO LOGOUT (20 MENIT) ===
if (isset($_SESSION['id_user'])) {
    $timeout_duration = 1200; 
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > $timeout_duration) {
            session_unset();
            session_destroy();
            header("Location: " . $base_url . "/index.php");
            exit; 
        } else {
            $_SESSION['last_activity'] = time();
        }
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// === CEK STATUS MEMBER UNTUK HEADER ===
$is_member_active = false;
if (isset($_SESSION['id_user']) && isset($conn)) {
    $uid_check = $_SESSION['id_user'];
    $q_member_check = mysqli_query($conn, "SELECT 1 FROM member WHERE id_user = '$uid_check' AND status = 'aktif' AND tanggal_berakhir >= CURDATE() LIMIT 1");
    if ($q_member_check && mysqli_num_rows($q_member_check) > 0) {
        $is_member_active = true;
    }
}

function isActive($page_name) {
    return (basename($_SERVER['PHP_SELF']) == $page_name) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Rush Badminton</title>
    
    <link rel="icon" href="<?= $base_url ?>/assets/images/LogoRush1(white).png" type="image/png">
    <link rel="shortcut icon" href="<?= $base_url ?>/assets/images/LogoRush1(white).png" type="image/png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    
    <script>
        window.USER_ID = <?= json_encode($_SESSION['id_user'] ?? 0); ?>;
        window.BASE_URL = "<?= $base_url ?>"; 
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css" />

    <style type="text/tailwindcss">
        body { font-family: 'Inter', sans-serif; background-color: #f6f8fb; }
        .nav-link { @apply relative text-slate-600 transition-colors duration-300; }
        .nav-link:not(.active):hover { @apply text-primary; }
        .nav-link.active { color: #0b63d6 !important; font-weight: 700 !important; }
        html { scroll-behavior: smooth; }
        
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
        
        .modal-backdrop {
            @apply fixed inset-0 z-[3000] flex items-center justify-center p-4;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        }
        .modal-backdrop.hidden { display: none !important; }
        .modal-panel { @apply bg-white rounded-xl shadow-lift w-full max-w-sm; }
        
        .slot-card { @apply border rounded-xl p-3 text-center transition-all duration-300 min-h-20 flex flex-col justify-center; }
        .slot-card.available { @apply bg-white border-gray-200 text-slate-700 shadow-md shadow-gray-200/50 hover:border-primary hover:shadow-lg hover:-translate-y-1 hover:bg-gray-hover cursor-pointer; }
        .slot-card.available .price { @apply text-green-600 font-bold; }
        .slot-card.available:hover .time { @apply text-primary; }
        .slot-card.booked { @apply bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed; }
        
        /* PERBAIKAN BUG GHOST CLICK:
           Menambahkan visibility dan pointer-events 
        */
        #mobileNav {
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease-in-out, visibility 0.3s;
            max-height: 0;
            opacity: 0;
            visibility: hidden;      /* Agar tidak terbaca reader/keyboard saat tutup */
            pointer-events: none;    /* Agar klik tembus ke bawah saat tutup */
            overflow: hidden;
        }
        #mobileNav.open {
            max-height: 100vh;
            opacity: 1;
            visibility: visible;     /* Munculkan kembali */
            pointer-events: auto;    /* Aktifkan klik */
        }

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
                <p class="text-slate-400 text-center mt-10">Belum ada jadwal di keranjang.</p>
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
                            <button class="text-xs mt-2 text-red-600 hover:text-red-800 font-medium remove-item-btn" data-index="<?= $i ?>">Hapus</button>
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
                
                <a href="<?= $base_url ?>/index.php" class="flex items-center gap-2 group mr-auto">
                    <div class="w-9 h-9 sm:w-12 sm:h-12 flex items-center justify-center transform transition-all duration-500 group-hover:scale-110 shrink-0">
                        <img src="<?= $base_url ?>/assets/images/LogoRush.png" alt="Logo" class="w-full h-full object-contain rounded-xl shadow-sm">
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="font-poppins font-bold text-slate-800 leading-tight whitespace-nowrap">
                            <span class="block sm:hidden text-base">Rush Badminton</span>
                            <span class="hidden sm:block text-lg">Rush Badminton Academy</span>
                        </div>
                        <div class="text-xs text-slate-500 -mt-0.5 hidden sm:block">Booking Lapangan Online</div>
                    </div>
                </a>

                <div class="hidden lg:flex flex-1 justify-center">
                    <ul id="topNav" class="flex gap-8 items-end">
                        <li><a href="<?= $base_url ?>/index.php" class="nav-link px-2 py-1 text-sm <?= isActive('index.php') ?>">Beranda</a></li>
                        <li><a href="<?= $base_url ?>/BookingPengguna/booking.php" class="nav-link px-2 py-1 text-sm <?= isActive('booking.php') ?>">Lapangan</a></li>                       
                        <li><a href="<?= $base_url ?>/member/member.php" class="nav-link px-2 py-1 text-sm <?= isActive('member.php') ?>">Member</a></li>
                        <li><a href="<?= $base_url ?>/riwayat/riwayat.php" class="nav-link px-2 py-1 text-sm <?= isActive('riwayat.php') ?>">Riwayat</a></li>
                        <li><a href="<?= $base_url ?>/kontak.php" class="nav-link px-2 py-1 text-sm <?= isActive('kontak.php') ?>">Kontak</a></li>
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
                
                <div class="hidden lg:flex items-center gap-4">
                    <?php if (isset($_SESSION['id_user']) && $_SESSION['id_user'] != 1): ?>
                        <?php
                        $foto_profil = $base_url . '/assets/images/default-avatar.png';
                        if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                            $foto_profil = $base_url . '/uploads/profiles/' . htmlspecialchars($_SESSION['foto_profil']);
                        }
                        $tampil_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Member';
                        ?>

                        <a href="<?= $base_url ?>/DashPengguna.php" 
                        class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-primary transition-colors duration-300"
                        title="Lihat Dashboard">
                            <img src="<?= $foto_profil ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover border-2 border-primary-light">
                            <span class="flex items-center gap-1">
                                Halo, <?= $tampil_username ?>
                                <?php if($is_member_active): ?>
                                    <i class="fa-solid fa-crown text-yellow-500 text-xs" title="Member VIP"></i>
                                <?php endif; ?>
                            </span>
                        </a>
                        
                        <a href="<?= $base_url ?>/auth/php/logout.php" id="btnLogout" class="text-sm text-gray-500 hover:text-red-600" title="Keluar">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        </a>

                    <?php else: ?>
                        <a href="<?= $base_url ?>/auth/login.php" class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-all duration-300">Masuk</a>
                        <a href="<?= $base_url ?>/auth/register.php" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300">Daftar</a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3 sm:gap-4 lg:hidden ml-2">
                    <a href="#" id="mobileCartIcon" class="relative text-slate-700 hover:text-primary transition-colors p-1">
                        <i class="fa-solid fa-cart-shopping text-xl"></i>
                        <span id="mobileCartCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5">
                            <?= count($_SESSION['keranjang'] ?? []) ?>
                        </span>
                    </a>

                    <button id="mobileBtn" class="p-2 rounded-md text-slate-700 hover:bg-slate-100 focus:outline-none transition-colors">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <div id="loginRequiredModal" class="modal-backdrop hidden">
      <div class="modal-panel animate-pop p-6 text-center" id="loginModalContent">
          <h3 class="text-lg font-bold text-slate-800 mb-2">Login Diperlukan</h3>
          <p class="text-sm text-slate-500 mb-6">Anda harus login terlebih dahulu untuk melanjutkan checkout.</p>
          <div class="flex flex-col gap-3">
              <a href="<?= $base_url ?>/auth/login.php" id="btnLoginYes" class="w-full bg-[#2563EB] hover:bg-[#1d4ed8] text-white font-bold py-3 rounded-xl transition-all block">IYA, LOGIN</a>
              <button id="btnLoginNo" class="w-full bg-white border-2 border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-50 transition-all">BATAL</button>
          </div>
      </div>
    </div>

    <div id="mobileNav" class="lg:hidden bg-white/95 backdrop-blur-xl border-t border-slate-100 shadow-2xl fixed top-20 left-0 w-full z-40 overflow-y-auto" style="max-height: calc(100vh - 80px);">
        <div class="px-4 py-6 flex flex-col gap-3">
            <a href="<?= $base_url ?>/index.php" class="py-3 px-4 rounded-xl hover:bg-primary-light transition-all font-medium text-slate-700 flex items-center group <?= isActive('index.php') ? 'bg-primary-light text-primary font-bold' : '' ?>">
                <i class="fa-solid fa-house mr-4 w-6 text-center text-slate-400 group-hover:text-primary transition-colors"></i> Beranda
            </a>
            <a href="<?= $base_url ?>/BookingPengguna/booking.php" class="py-3 px-4 rounded-xl hover:bg-primary-light transition-all font-medium text-slate-700 flex items-center group <?= isActive('booking.php') ? 'bg-primary-light text-primary font-bold' : '' ?>">
                <i class="fa-regular fa-calendar-check mr-4 w-6 text-center text-slate-400 group-hover:text-primary transition-colors"></i> Lapangan
            </a>
            <a href="<?= $base_url ?>/kontak.php" class="py-3 px-4 rounded-xl hover:bg-primary-light transition-all font-medium text-slate-700 flex items-center group <?= isActive('kontak.php') ? 'bg-primary-light text-primary font-bold' : '' ?>">
                <i class="fa-regular fa-envelope mr-4 w-6 text-center text-slate-400 group-hover:text-primary transition-colors"></i> Kontak
            </a>
            <a href="<?= $base_url ?>/member/member.php" class="py-3 px-4 rounded-xl hover:bg-primary-light transition-all font-medium text-slate-700 flex items-center group <?= isActive('member.php') ? 'bg-primary-light text-primary font-bold' : '' ?>">
                <i class="fa-solid fa-crown mr-4 w-6 text-center text-yellow-500"></i> Member
            </a>
            <a href="<?= $base_url ?>/riwayat/riwayat.php" class="py-3 px-4 rounded-xl hover:bg-primary-light transition-all font-medium text-slate-700 flex items-center group <?= isActive('riwayat.php') ? 'bg-primary-light text-primary font-bold' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left mr-4 w-6 text-center text-slate-400 group-hover:text-primary transition-colors"></i> Riwayat
            </a>
            
            <div class="mt-4 pt-6 border-t border-slate-100 flex flex-col gap-4">
                 <?php if (isset($_SESSION['id_user']) && $_SESSION['id_user'] != 1): ?>
                    <div class="flex items-center gap-4 px-2 bg-slate-50 p-4 rounded-xl">
                        <?php 
                        $foto_profil_mobile = $base_url . '/assets/images/default-avatar.png';
                        if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                            $foto_profil_mobile = $base_url . '/uploads/profiles/' . htmlspecialchars($_SESSION['foto_profil']);
                        }
                        ?>
                        <img src="<?= $foto_profil_mobile ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover">
                        <div>
                            <div class="text-sm font-bold text-slate-800 flex items-center gap-1">
                                <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Member' ?>
                                <?php if($is_member_active): ?>
                                    <i class="fa-solid fa-crown text-yellow-500 text-xs animate-bounce"></i>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500">User Terdaftar</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="<?= $base_url ?>/DashPengguna.php" class="text-center border border-slate-200 text-slate-600 py-3 rounded-xl font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-user-gear"></i> Dashboard
                        </a>
                        <a href="<?= $base_url ?>/auth/php/logout.php" class="text-center bg-red-50 text-red-600 py-3 rounded-xl font-bold hover:bg-red-100 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-power-off"></i> Keluar
                        </a>
                    </div>
                 <?php else: ?>
                    <a href="<?= $base_url ?>/auth/login.php" class="w-full text-center border-2 border-primary text-primary py-3 rounded-xl font-bold hover:bg-primary hover:text-white transition-all">Masuk Akun</a>
                    <a href="<?= $base_url ?>/auth/register.php" class="w-full text-center bg-gradient-to-r from-primary to-primaryDark text-white py-3 rounded-xl font-bold hover:shadow-lg hover:-translate-y-0.5 transition-all shadow-blue-200">Daftar Akun Baru</a>
                 <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Mobile Menu Toggle
            const mobileBtn = document.getElementById('mobileBtn');
            const mobileNav = document.getElementById('mobileNav');
            
            if(mobileBtn && mobileNav) {
                mobileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    mobileNav.classList.toggle('open');
                    // Toggle icon icon bars/xmark
                    const icon = mobileBtn.querySelector('i');
                    if(mobileNav.classList.contains('open')) {
                        icon.classList.replace('fa-bars', 'fa-xmark');
                    } else {
                        icon.classList.replace('fa-xmark', 'fa-bars');
                    }
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!mobileNav.contains(e.target) && !mobileBtn.contains(e.target)) {
                        mobileNav.classList.remove('open');
                        mobileBtn.querySelector('i').classList.replace('fa-xmark', 'fa-bars');
                    }
                });
            }

            // 2. Sync Cart Count (Desktop <-> Mobile)
            const deskCart = document.getElementById('cartCount');
            const mobCart = document.getElementById('mobileCartCount');
            
            if(deskCart && mobCart) {
                const observer = new MutationObserver(() => {
                    mobCart.textContent = deskCart.textContent;
                });
                observer.observe(deskCart, {childList: true, characterData: true, subtree: true});
            }

            // 3. Mobile Cart Click -> Open Sidebar
            const mobileCartIcon = document.getElementById('mobileCartIcon');
            const sidebar = document.getElementById('sidebarKeranjang');
            if(mobileCartIcon && sidebar) {
                mobileCartIcon.addEventListener('click', (e) => {
                    e.preventDefault();
                    sidebar.classList.add('active');
                });
            }
        });
    </script>