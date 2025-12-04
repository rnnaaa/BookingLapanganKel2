<?php
// sidebar.php (Sidebar + Topbar/Navbar)
// Solusi Final: Mengatasi konflik rotasi panah AdminLTE

$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi menandai menu aktif & membuka submenu otomatis
function isActive($pages)
{
    global $current_page;
    if(!is_array($pages)) { $pages = [$pages]; }
    return in_array($current_page, $pages) ? 'menu-open' : '';
}
function activeLink($page)
{
    global $current_page;
    return $current_page == $page ? 'active' : '';
}
?>

<aside class="main-sidebar sidebar-light-primary elevation-4" style="background-color: #1874ad;">

    <a href="dashboard.php" class="brand-link d-flex align-items-center" style="gap: 12px; padding: 12px 16px; background: #166a9c;">
        <img src="../uploads/bukti_pembayaran/LogoRush2.png" alt="Logo" class="brand-image img-circle elevation-3" style="width: 40px; height: 40px; object-fit: cover; opacity: .95;">
        <span class="brand-text font-weight-bold text-white" style="font-size: 13px; margin-top: 2px;">
            Rush Badminton Academy
        </span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <?php
            $default_profile_pic = '../uploads/users/';
            $profile_pic_path = $default_profile_pic;
            if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                $profile_pic_path = '../uploads/users/' . $_SESSION['foto_profil']; 
            }
            ?>
            <div class="image">
                <img src="<?= $profile_pic_path ?>" alt="User Image" class="img-circle elevation-2" style="width: 35px; height: 35px; object-fit: cover;">
            </div>
            <div class="info text-truncate">
                <a href="#" class="d-block text-white" style="font-weight: 500;">
                    <?php echo isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Administrator'; ?>
                </a>
            </div>
        </div>

        <div class="form-inline mb-2">
            <div class="input-group" id="sidebar-search-container">
                <input id="sidebar-search" class="form-control form-control-sidebar" type="search" placeholder="Cari menu..." aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar" id="btn-sidebar-search">
                        <i class="fas fa-search fa-fw text-dark"></i>
                    </button>
                </div>
            </div>
        </div>

        <nav class="mt-2">
            <ul id="sidebar-menu" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= activeLink('dashboard.php') ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item has-treeview <?= isActive(['lapangan.php', 'jadwal_waktu.php', 'jadwal_harian.php', 'jadwal_view.php', 'jadwal_singkronisasi.php', 'jam_operasional.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-futbol"></i>
                        <p>Lapangan & Jadwal <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="lapangan.php" class="nav-link <?= activeLink('lapangan.php') ?>"><p>Data Lapangan</p></a></li>
                        <li class="nav-item"><a href="jadwal_waktu.php" class="nav-link <?= activeLink('jadwal_waktu.php') ?>"><p>Jadwal Waktu</p></a></li>
                        <li class="nav-item"><a href="jadwal_harian.php" class="nav-link <?= activeLink('jadwal_harian.php') ?>"><p>Jadwal Harian</p></a></li>
                        <li class="nav-item"><a href="jadwal_view.php" class="nav-link <?= activeLink('jadwal_view.php') ?>"><p>Jadwal View</p></a></li>
                        <li class="nav-item"><a href="jadwal_singkronisasi.php" class="nav-link <?= activeLink('jadwal_singkronisasi.php') ?>"><p>Sinkronisasi</p></a></li>
                        <li class="nav-item"><a href="jam_operasional.php" class="nav-link <?= activeLink('jam_operasional.php') ?>"><p>Jam Operasional</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['booking.php', 'pembayaran.php', 'pembatalan_booking.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Booking & Pembayaran <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="booking.php" class="nav-link <?= activeLink('booking.php') ?>"><p>Data Booking</p></a></li>
                        <li class="nav-item"><a href="pembatalan_booking.php" class="nav-link <?= activeLink('pembatalan_booking.php') ?>"><p>Pembatalan Booking</p></a></li>
                        <li class="nav-item"><a href="pembayaran.php" class="nav-link <?= activeLink('pembayaran.php') ?>"><p>Pembayaran</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['member.php', 'member_jadwal.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-id-card"></i>
                        <p>Member <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="member.php" class="nav-link <?= activeLink('member.php') ?>"><p>Data Member</p></a></li>
                        <li class="nav-item"><a href="member_jadwal.php" class="nav-link <?= activeLink('member_jadwal.php') ?>"><p>Jadwal Member</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['produk.php', 'saran.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Produk & Saran <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="produk.php" class="nav-link <?= activeLink('produk.php') ?>"><p>Data Produk</p></a></li>
                        <li class="nav-item"><a href="saran.php" class="nav-link <?= activeLink('saran.php') ?>"><p>Data Saran & Masukan</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['keuangan.php', 'pengeluaran.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-coins"></i>
                        <p>Laporan Keuangan <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="keuangan.php" class="nav-link <?= activeLink('keuangan.php') ?>"><p>Rekap Keuangan</p></a></li>
                        <li class="nav-item"><a href="pengeluaran.php" class="nav-link <?= activeLink('pengeluaran.php') ?>"><p>Data Pengeluaran</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['users.php', 'admin.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Pengguna Sistem <i class="right fas fa-angle-right rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="users.php" class="nav-link <?= activeLink('users.php') ?>"><p>Data Pengguna</p></a></li>
                        <li class="nav-item"><a href="admin.php" class="nav-link <?= activeLink('admin.php') ?>"><p>Data Admin</p></a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="pengaturan.php" class="nav-link <?= activeLink('pengaturan.php') ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Pengaturan Sistem</p>
                    </a>
                </li>
                
            </ul>
        </nav>
    </div>
</aside>

<style>
    /* 1. STATE DEFAULT (Tertutup) */
    /* Kita Paksa menjadi 0 derajat dengan !important untuk mengalahkan style bawaan */
    .nav-sidebar .nav-item > .nav-link .rotate-icon {
        transform: rotate(0deg) !important; 
        transition: transform 0.3s ease !important;
    }

    /* 2. STATE TERBUKA (Menu Open) */
    /* Kita Paksa menjadi 90 derajat (Searah Jarum Jam) dengan !important */
    /* Panah Kanan (>) diputar 90 derajat jadi Panah Bawah (v) */
    .nav-sidebar .nav-item.menu-open > .nav-link .rotate-icon {
        transform: rotate(90deg) !important;
    }

    /* --- Style Dekorasi Lainnya (Garis-garis) --- */
    .nav-treeview { margin-left: 15px; padding-left: 15px; position: relative; }
    .nav-treeview::before {
        content: ""; position: absolute; top: 0; left: 2px; width: 2px; height: 100%;
        background: rgba(255, 255, 255, 0.25); opacity: 0; transform: scaleY(0); transition: all 0.35s ease;
    }
    .nav-item.menu-open>.nav-treeview::before { opacity: 1; transform: scaleY(1); animation: glowingLine 1.5s infinite; }
    
    @keyframes glowingLine {
        0% { box-shadow: 0 0 0px rgba(255, 255, 255, 0); }
        50% { box-shadow: 0 0 6px rgba(255, 255, 255, 0.4); }
        100% { box-shadow: 0 0 0px rgba(255, 255, 255, 0); }
    }

    .nav-treeview .nav-item { position: relative; padding-left: 20px; }
    .nav-treeview .nav-item::before {
        content: ""; position: absolute; top: 50%; left: -15px; width: 15px; height: 2px; background: rgba(255, 255, 255, 0.35);
    }
    .nav-treeview .nav-item::after {
        content: ""; position: absolute; top: 0; left: -15px; width: 2px; height: 100%; background: rgba(255, 255, 255, 0.25);
    }
    .nav-treeview .nav-item:last-child::after { height: 50%; }
    
    .nav-item.menu-open>.nav-treeview .nav-item::before,
    .nav-item.menu-open>.nav-treeview .nav-item::after { animation: glowingBranch 1.6s infinite; }
    
    @keyframes glowingBranch {
        0% { opacity: 0.2; } 50% { opacity: 1; } 100% { opacity: 0.2; }
    }

    .nav-treeview .nav-link p { color: #dcdcdc; font-size: 14px; }
    .nav-treeview .nav-link:hover p { color: #ffffff; text-shadow: 0 0 4px rgba(255, 255, 255, 0.5); }
    .nav-treeview .nav-link.active { background-color: rgba(255, 255, 255, 0.15); border-radius: 8px; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('sidebar-search');
        const searchButton = document.getElementById('btn-sidebar-search');
        const menuItems = document.querySelectorAll('#sidebar-menu > li.nav-item');
        
        function filterMenu(query) {
            query = query.toLowerCase();
            menuItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        }
        if(searchInput) {
            searchInput.addEventListener('keyup', () => filterMenu(searchInput.value));
            searchButton.addEventListener('click', () => filterMenu(searchInput.value));
        }
    });
</script>

<?php
// Konfigurasi Database & Hitung Notifikasi
require_once __DIR__ . '/../config/database.php';
if (!isset($conn)) { die("Error: Variabel koneksi ($conn) tidak ditemukan."); }

$jml_booking = 0; $jml_bayar = 0; $jml_batal = 0;

if($result = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status = 'menunggu'")) {
    $jml_booking = mysqli_fetch_assoc($result)['total'];
}
if($result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pembayaran WHERE status_verifikasi = 'menunggu'")) {
    $jml_bayar = mysqli_fetch_assoc($result)['total'];
}
if($result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pembatalan_booking WHERE status = 'pending'")) {
    $jml_batal = mysqli_fetch_assoc($result)['total'];
}

$total_notif = $jml_booking + $jml_bayar + $jml_batal;
?>

<div class="preloader flex-column justify-content-center align-items-center">
  <img class="animation__shake" src="../uploads/bukti_pembayaran/bukti_13_1763043777.png" alt="Badmintoon Logo" height="60" width="60">
</div>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
  </ul>

  <ul class="navbar-nav ml-auto">
    <li class="nav-item">
      <a class="nav-link" data-widget="navbar-search" href="#" role="button"><i class="fas fa-search"></i></a>
      <div class="navbar-search-block">
        <form class="form-inline">
          <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Cari data..." aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-navbar" type="submit"><i class="fas fa-search"></i></button>
              <button class="btn btn-navbar" type="button" data-widget="navbar-search"><i class="fas fa-times"></i></button>
            </div>
          </div>
        </form>
      </div>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <?php if ($total_notif > 0): ?><span class="badge badge-warning navbar-badge"><?= $total_notif ?></span><?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header"><?= $total_notif ?> Notifikasi</span>
        <div class="dropdown-divider"></div>
        <a href="booking.php" class="dropdown-item"><i class="fas fa-calendar-check mr-2"></i> <?= $jml_booking ?> Booking baru</a>
        <div class="dropdown-divider"></div>
        <a href="pembayaran.php" class="dropdown-item"><i class="fas fa-money-bill-wave mr-2"></i> <?= $jml_bayar ?> Pembayaran Masuk</a>
        <div class="dropdown-divider"></div>
        <a href="pembatalan_booking.php" class="dropdown-item"><i class="fas fa-undo-alt mr-2"></i> <?= $jml_batal ?> Permintaan Refund</a>
      </div>
    </li>

    <li class="nav-item"><a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a></li>
    
    <li class="nav-item">
        <a href="logout.php" class="nav-link text-danger" role="button" title="Keluar dari Sistem">
            <i class="fas fa-sign-out-alt mr-1"></i> <span class="d-none d-md-inline font-weight-bold">Logout</span>
        </a>
    </li>
  </ul>
</nav>