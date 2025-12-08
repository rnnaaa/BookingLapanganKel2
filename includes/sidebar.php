<?php
// sidebar.php (Sidebar + Topbar/Navbar) - Uniform Background Version
$current_page = basename($_SERVER['PHP_SELF']);

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

<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: linear-gradient(120deg, #0184dbff 0%, #014d69c5 100%) !important;">

    <a href="dashboard.php" class="brand-link d-flex align-items-center" style="padding: 16px 20px; background: linear-gradient(135deg, #257ec7ff 0%, #2d7eb1ff 100%); border-bottom: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease;">
        <img src="../uploads/bukti_pembayaran/LogoRush2.png" alt="Logo" class="brand-image elevation-3" style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4);">
        <span class="brand-text font-weight-bold text-white ml-2" style="font-size: 12px; letter-spacing: 0.3px; text-shadow: 0 1px 3px rgba(0,0,0,0.15);">
            Rush Badminton Academy
        </span>
    </a>

    <div class="sidebar" style="padding-top: 2px;">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.2); padding: 12px 16px;">
            <?php
            $default_profile_pic = '../uploads/users/';
            $profile_pic_path = $default_profile_pic;
            if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                $profile_pic_path = '../uploads/users/' . $_SESSION['foto_profil']; 
            }
            ?>
            <div class="image">
                <img src="<?= $profile_pic_path ?>" alt="User Image" class="img-circle elevation-3" style="width: 35px; height: 35px; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); transition: transform 0.3s ease;">
            </div>
            <div class="info text-truncate ml-2">
                <a href="#" class="d-block text-white" style="font-weight: 600; font-size: 14px; letter-spacing: 0.2px;">
                    <?php echo isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Administrator'; ?>
                </a>
                <small class="text-white-50" style="font-size: 11px;">Administrator</small>
            </div>
        </div>

        <div class="form-inline mb-3 px-3">
            <div class="input-group" id="sidebar-search-container" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
                <input id="sidebar-search" class="form-control form-control-sidebar" type="search" placeholder="Cari menu..." aria-label="Search" style="border: none; background: rgba(255,255,255,0.95); font-size: 13px; padding: 10px 12px;">
                <div class="input-group-append">
                    <button class="btn btn-sidebar" id="btn-sidebar-search" style="background: rgba(255,255,255,0.95); border: none; color: #2980b9; transition: all 0.3s ease;">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <nav class="mt-2 px-2">
            <ul id="sidebar-menu" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <li class="nav-item mb-1">
                    <a href="dashboard.php" class="nav-link <?= activeLink('dashboard.php') ?>" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-tachometer-alt" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">Dashboard</p>
                    </a>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['lapangan.php', 'jadwal_waktu.php', 'jadwal_harian.php', 'jadwal_view.php', 'jadwal_singkronisasi.php', 'jam_operasional.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-futbol" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Lapangan & Jadwal 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="lapangan.php" class="nav-link <?= activeLink('lapangan.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Lapangan</p></a></li>
                        <li class="nav-item"><a href="jadwal_waktu.php" class="nav-link <?= activeLink('jadwal_waktu.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Jadwal Waktu</p></a></li>
                        <li class="nav-item"><a href="jadwal_harian.php" class="nav-link <?= activeLink('jadwal_harian.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Jadwal Harian</p></a></li>
                        <li class="nav-item"><a href="jadwal_view.php" class="nav-link <?= activeLink('jadwal_view.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Jadwal View</p></a></li>
                        <li class="nav-item"><a href="jadwal_singkronisasi.php" class="nav-link <?= activeLink('jadwal_singkronisasi.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Sinkronisasi</p></a></li>
                        <li class="nav-item"><a href="jam_operasional.php" class="nav-link <?= activeLink('jam_operasional.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Jam Operasional</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['booking.php', 'pembayaran.php', 'pembatalan_booking.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-calendar-check" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Booking & Pembayaran 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="booking.php" class="nav-link <?= activeLink('booking.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Booking</p></a></li>
                        <li class="nav-item"><a href="pembatalan_booking.php" class="nav-link <?= activeLink('pembatalan_booking.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Pembatalan Booking</p></a></li>
                        <li class="nav-item"><a href="pembayaran.php" class="nav-link <?= activeLink('pembayaran.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Pembayaran</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['member.php', 'member_jadwal.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-id-card" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Member 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="member.php" class="nav-link <?= activeLink('member.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Member</p></a></li>
                        <li class="nav-item"><a href="member_jadwal.php" class="nav-link <?= activeLink('member_jadwal.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Jadwal Member</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['produk.php', 'saran.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-cogs" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Produk & Saran 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="produk.php" class="nav-link <?= activeLink('produk.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Produk</p></a></li>
                        <li class="nav-item"><a href="saran.php" class="nav-link <?= activeLink('saran.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Saran & Masukan</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['keuangan.php', 'pengeluaran.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-coins" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Laporan Keuangan 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="keuangan.php" class="nav-link <?= activeLink('keuangan.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Rekap Keuangan</p></a></li>
                        <li class="nav-item"><a href="pengeluaran.php" class="nav-link <?= activeLink('pengeluaran.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Pengeluaran</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview mb-1 <?= isActive(['users.php', 'admin.php']) ?>">
                    <a href="#" class="nav-link" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-users" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">
                            Pengguna Sistem 
                            <i class="right fas fa-angle-right rotate-icon" style="transition: transform 0.3s ease;"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="padding-left: 12px;">
                        <li class="nav-item"><a href="users.php" class="nav-link <?= activeLink('users.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Pengguna</p></a></li>
                        <li class="nav-item"><a href="admin.php" class="nav-link <?= activeLink('admin.php') ?>" style="border-radius: 6px; padding: 8px 16px;"><i class="fas fa-circle nav-icon" style="font-size: 6px; margin-right: 8px;"></i><p style="font-size: 13px;">Data Admin</p></a></li>
                    </ul>
                </li>

                <li class="nav-item mb-1">
                    <a href="pengaturan.php" class="nav-link <?= activeLink('pengaturan.php') ?>" style="border-radius: 8px; padding: 10px 16px; transition: all 0.3s ease;">
                        <i class="nav-icon fas fa-cog" style="width: 24px; text-align: center;"></i>
                        <p style="font-size: 14px; font-weight: 500; margin-left: 8px;">Pengaturan Sistem</p>
                    </a>
                </li>
                
            </ul>
        </nav>
    </div>
</aside>

<style>
    /* ===== PROFESSIONAL SIDEBAR STYLING (UNIFORM COLORS) ===== */
    
    /* Brand Link Hover (Sedikit lebih terang saat di-hover) */
    .brand-link:hover {
        background: linear-gradient(135deg, #1d6fa5 0%, #3498db 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .brand-link:hover .brand-image {
        transform: scale(1.05);
    }
    
    /* User Panel Hover */
    .user-panel:hover .img-circle {
        transform: scale(1.08);
        box-shadow: 0 0 15px rgba(255,255,255,0.4);
    }
    
    /* Search Button Hover */
    #btn-sidebar-search:hover {
        background: rgba(255,255,255,1) !important;
        color: #2c7bb6 !important; 
        transform: scale(1.05);
    }
    
    /* Navigation Links */
    .nav-sidebar .nav-link {
        color: rgba(255,255,255,0.95) !important; 
        margin-bottom: 4px;
    }
    
    .nav-sidebar .nav-link:hover {
        background-color: rgba(255,255,255,0.15) !important;
        color: #fff !important;
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .nav-sidebar .nav-link.active {
        background: linear-gradient(90deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.1) 100%) !important;
        color: #fff !important;
        border-left: 4px solid #fff;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    /* Icon Styling */
    .nav-sidebar .nav-icon {
        color: rgba(255,255,255,0.9);
        font-size: 16px;
    }
    
    .nav-sidebar .nav-link:hover .nav-icon,
    .nav-sidebar .nav-link.active .nav-icon {
        color: #fff;
        text-shadow: 0 0 10px rgba(255,255,255,0.5);
    }
    
    /* Arrow Rotation */
    .nav-sidebar .nav-item > .nav-link .rotate-icon {
        transform: rotate(0deg) !important; 
        transition: transform 0.3s ease !important;
        font-size: 14px;
        margin-left: auto;
    }
    
    .nav-sidebar .nav-item.menu-open > .nav-link .rotate-icon {
        transform: rotate(90deg) !important;
    }
    
    /* Treeview Styling */
    .nav-treeview {
        margin-top: 6px;
        padding-left: 20px;
        position: relative;
    }
    
    .nav-treeview::before {
        content: "";
        position: absolute;
        top: 0;
        left: 20px;
        width: 2px;
        height: 100%;
        background: linear-gradient(180deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.1) 100%);
        opacity: 0;
        transform: scaleY(0);
        transition: all 0.4s ease;
    }
    
    .nav-item.menu-open > .nav-treeview::before {
        opacity: 1;
        transform: scaleY(1);
    }
    
    .nav-treeview .nav-link {
        color: rgba(255,255,255,0.85) !important;
        padding-left: 28px !important;
        margin-bottom: 2px;
    }
    
    .nav-treeview .nav-link:hover {
        color: #fff !important;
        background-color: rgba(255,255,255,0.15) !important;
        padding-left: 32px !important;
    }
    
    .nav-treeview .nav-link.active {
        background: linear-gradient(90deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.08) 100%) !important;
        color: #fff !important;
        font-weight: 600;
        border-left: 3px solid rgba(255,255,255,0.7);
    }
    
    .nav-treeview .nav-icon {
        color: rgba(255,255,255,0.7);
        transition: all 0.3s ease;
    }
    
    .nav-treeview .nav-link:hover .nav-icon {
        color: #fff;
        transform: scale(1.2);
    }
    
    /* Smooth Transitions */
    .nav-link, .nav-icon, .rotate-icon, .brand-image, .img-circle {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Scrollbar Styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }
    
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.3);
        border-radius: 10px;
    }
    
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.4);
    }
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
                if(text.includes(query)) {
                    item.style.display = '';
                    item.style.animation = 'fadeInMenu 0.3s ease';
                } else {
                    item.style.display = 'none';
                }
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

<nav class="main-header navbar navbar-expand navbar-white navbar-light" style="border-bottom: 1px solid #dee2e6; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" style="transition: all 0.3s ease;">
                <i class="fas fa-bars" style="color: #2980b9;"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="dashboard.php" class="nav-link" style="color: #495057; font-weight: 500; transition: all 0.3s ease;">
                <i class="fas fa-home mr-1" style="color: #2980b9;"></i> Dashboard
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button" style="transition: all 0.3s ease;">
                <i class="fas fa-search" style="color: #6c757d;"></i>
            </a>
            <div class="navbar-search-block">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Cari data..." aria-label="Search" style="border-radius: 20px 0 0 20px; border-right: none;">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit" style="border-radius: 0; background: #f8f9fa; border: 1px solid #ced4da; border-left: none; color: #2980b9;">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search" style="border-radius: 0 20px 20px 0; background: #f8f9fa; border: 1px solid #ced4da; border-left: none; color: #6c757d;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" style="position: relative; transition: all 0.3s ease;">
                <i class="far fa-bell" style="color: #6c757d; font-size: 18px;"></i>
                <?php if ($total_notif > 0): ?>
                    <span class="badge badge-warning navbar-badge" style="position: absolute; top: 5px; right: 5px; padding: 3px 6px; font-size: 10px; border-radius: 10px; animation: pulse 2s infinite;">
                        <?= $total_notif ?>
                    </span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border: none; min-width: 320px;">
                <span class="dropdown-item dropdown-header" style="background: linear-gradient(135deg, #2980b9 0%, #3498db 100%); color: white; font-weight: 600; padding: 12px 16px; border-radius: 8px 8px 0 0;">
                    <i class="fas fa-bell mr-2"></i><?= $total_notif ?> Notifikasi Baru
                </span>
                <div class="dropdown-divider m-0"></div>
                <a href="booking.php" class="dropdown-item" style="padding: 12px 16px; transition: all 0.3s ease;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-primary mr-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                        <div>
                            <strong><?= $jml_booking ?></strong> Booking Baru
                            <br><small class="text-muted">Perlu verifikasi</small>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a href="pembayaran.php" class="dropdown-item" style="padding: 12px 16px; transition: all 0.3s ease;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-success mr-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-money-bill-wave text-white"></i>
                        </div>
                        <div>
                            <strong><?= $jml_bayar ?></strong> Pembayaran Masuk
                            <br><small class="text-muted">Menunggu konfirmasi</small>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a href="pembatalan_booking.php" class="dropdown-item" style="padding: 12px 16px; transition: all 0.3s ease;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-warning mr-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-undo-alt text-white"></i>
                        </div>
                        <div>
                            <strong><?= $jml_batal ?></strong> Permintaan Refund
                            <br><small class="text-muted">Perlu diproses</small>
                        </div>
                    </div>
                </a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button" style="transition: all 0.3s ease;">
                <i class="fas fa-expand-arrows-alt" style="color: #6c757d;"></i>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="logout.php" class="nav-link" role="button" title="Keluar dari Sistem" style="color: #dc3545; font-weight: 600; transition: all 0.3s ease; padding: 8px 16px; border-radius: 6px;">
                <i class="fas fa-sign-out-alt mr-1"></i> 
                <span class="d-none d-md-inline">Logout</span>
            </a>
        </li>
    </ul>
</nav>

<style>
    /* Navbar Hover Effects */
    .navbar-nav .nav-link:hover {
        transform: translateY(-2px);
        color: #2980b9 !important; 
    }
    
    .navbar-nav .nav-link:hover i {
        color: #2980b9 !important; 
    }
    
    /* Dropdown Hover */
    .dropdown-item:hover {
        background: linear-gradient(90deg, rgba(41, 128, 185, 0.08) 0%, transparent 100%);
        transform: translateX(5px);
    }
    
    /* Notification Pulse */
    @keyframes pulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
        }
        50% { 
            transform: scale(1.05);
            box-shadow: 0 0 0 5px rgba(255, 193, 7, 0);
        }
    }
    
    /* Logout Hover */
    .nav-item a[href="logout.php"]:hover {
        background: rgba(220, 53, 69, 0.1);
        transform: translateY(-2px);
    }
</style>