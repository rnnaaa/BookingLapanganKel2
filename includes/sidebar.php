<?php
// sidebar.php
// Pastikan session sudah dimulai di file induk (misalnya header.php atau index.php)
// session_start(); 

$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi menandai menu aktif & membuka submenu otomatis
function isActive($pages)
{
    global $current_page;
    return in_array($current_page, $pages) ? 'menu-open' : '';
}
function activeLink($page)
{
    global $current_page;
    return $current_page == $page ? 'active' : '';
}
?>

<aside class="main-sidebar sidebar-light-primary elevation-4" style="background-color: #1874ad;">

    <a href="dashboard.php"
        class="brand-link d-flex align-items-center"
        style="gap: 12px; padding: 12px 16px; background: #166a9c;">

        <img src="../uploads/bukti_pembayaran/LogoRush2.png"
            alt="Logo"
            class="brand-image img-circle elevation-3"
            style="width: 40px; height: 40px; object-fit: cover; opacity: .95;">

        <span class="brand-text font-weight-bold text-white"
            style="font-size: 13px; margin-top: 2px;">
            Rush Badminton Academy
        </span>
    </a>


    <div class="sidebar">

        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            
            <?php
            $default_profile_pic = '../uploads/users/'; // Ganti dengan path ke foto profil default
            $profile_pic_path = $default_profile_pic;

            // Jika session foto_profil ada, gunakan path dari session
            if (isset($_SESSION['foto_profil']) && !empty($_SESSION['foto_profil'])) {
                // Sesuaikan path ini jika direktori penyimpanan berbeda
                $profile_pic_path = '../uploads/users/' . $_SESSION['foto_profil']; 
            }
            ?>
            <div class="image">
                <img src="<?= $profile_pic_path ?>"
                    alt="User Image"
                    class="img-circle elevation-2"
                    style="width: 35px; height: 35px; object-fit: cover;">
            </div>
            <div class="info text-truncate">
                <a href="#" class="d-block text-white" style="font-weight: 500;">
                    <?php
                    // Ambil nama dari session yang sudah diverifikasi
                    if (isset($_SESSION['nama'])) {
                        echo htmlspecialchars($_SESSION['nama']);
                    } else {
                        echo 'Administrator'; // Fallback jika session tidak terdeteksi
                    }
                    ?>
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
                        <p>Lapangan & Jadwal <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="lapangan.php" class="nav-link <?= activeLink('lapangan.php') ?>">
                            <p>Data Lapangan</p>
                        </a></li>
                        <li class="nav-item"><a href="jadwal_waktu.php" class="nav-link <?= activeLink('jadwal_waktu.php') ?>">
                            <p>Jadwal Waktu</p>
                        </a></li>
                        <li class="nav-item"><a href="jadwal_harian.php" class="nav-link <?= activeLink('jadwal_harian.php') ?>">
                            <p>Jadwal Harian</p>
                        </a></li>
                        <li class="nav-item"><a href="jadwal_view.php" class="nav-link <?= activeLink('jadwal_view.php') ?>">
                            <p>Jadwal View</p>
                        </a></li>
                        <li class="nav-item"><a href="jadwal_singkronisasi.php" class="nav-link <?= activeLink('jadwal_singkronisasi.php') ?>">
                            <p>Sinkronisasi</p>
                        </a></li>
                        <li class="nav-item"><a href="jam_operasional.php" class="nav-link <?= activeLink('jam_operasional.php') ?>">
                            <p>Jam Operasional</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['booking.php', 'pembayaran.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Booking & Pembayaran <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="booking.php" class="nav-link <?= activeLink('booking.php') ?>">
                            <p>Data Booking</p>
                        </a></li>
                        <li class="nav-item"><a href="pembayaran.php" class="nav-link <?= activeLink('pembayaran.php') ?>">
                            <p>Pembayaran</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['member.php', 'member_jadwal.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-id-card"></i>
                        <p>Member <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="member.php" class="nav-link <?= activeLink('member.php') ?>">
                            <p>Data Member</p>
                        </a></li>
                        <li class="nav-item"><a href="member_jadwal.php" class="nav-link <?= activeLink('member_jadwal.php') ?>">
                            <p>Jadwal Member</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['produk.php', 'saran.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Manajemen Konten <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="produk.php" class="nav-link <?= activeLink('produk.php') ?>">
                            <p>Data Produk</p>
                        </a></li>
                        <li class="nav-item"><a href="saran.php" class="nav-link <?= activeLink('saran.php') ?>">
                            <p>Data Saran & Masukan</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['keuangan.php', 'pengeluaran.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-coins"></i>
                        <p>Keuangan <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="keuangan.php" class="nav-link <?= activeLink('keuangan.php') ?>">
                            <p>Rekap Keuangan</p>
                        </a></li>
                        <li class="nav-item"><a href="pengeluaran.php" class="nav-link <?= activeLink('pengeluaran.php') ?>">
                            <p>Data Pengeluaran</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview <?= isActive(['users.php', 'admin.php']) ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Pengguna Sistem <i class="right fas fa-angle-left rotate-icon"></i></p>
                    </a>
                    <ul class="nav nav-treeview ml-3">
                        <li class="nav-item"><a href="users.php" class="nav-link <?= activeLink('users.php') ?>">
                            <p>Data Pengguna</p>
                        </a></li>
                        <li class="nav-item"><a href="admin.php" class="nav-link <?= activeLink('admin.php') ?>">
                            <p>Data Admin</p>
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?= activeLink('settings.php') ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Pengaturan Sistem</p>
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>

<style>
    /* Rotasi panah */
    .nav-item>a .rotate-icon {
        transition: transform 0.3s ease;
    }

    .nav-item.menu-open>a .rotate-icon {
        transform: rotate(-90deg);
    }

    /* Garis vertikal utama */
    .nav-treeview {
        margin-left: 15px;
        padding-left: 15px;
        position: relative;
    }

    .nav-treeview::before {
        content: "";
        position: absolute;
        top: 0;
        left: 2px;
        width: 2px;
        height: 100%;
        background: rgba(255, 255, 255, 0.25);
        opacity: 0;
        transform: scaleY(0);
        transition: all 0.35s ease;
    }

    .nav-item.menu-open>.nav-treeview::before {
        opacity: 1;
        transform: scaleY(1);
        animation: glowingLine 1.5s infinite;
    }

    @keyframes glowingLine {
        0% {
            box-shadow: 0 0 0px rgba(255, 255, 255, 0);
        }

        50% {
            box-shadow: 0 0 6px rgba(255, 255, 255, 0.4);
        }

        100% {
            box-shadow: 0 0 0px rgba(255, 255, 255, 0);
        }
    }

    /* Garis cabang submenu */
    .nav-treeview .nav-item {
        position: relative;
        padding-left: 20px;
    }

    .nav-treeview .nav-item::before {
        content: "";
        position: absolute;
        top: 50%;
        left: -15px;
        width: 15px;
        height: 2px;
        background: rgba(255, 255, 255, 0.35);
    }

    .nav-treeview .nav-item::after {
        content: "";
        position: absolute;
        top: 0;
        left: -15px;
        width: 2px;
        height: 100%;
        background: rgba(255, 255, 255, 0.25);
    }

    .nav-treeview .nav-item:last-child::after {
        height: 50%;
    }

    .nav-item.menu-open>.nav-treeview .nav-item::before,
    .nav-item.menu-open>.nav-treeview .nav-item::after {
        animation: glowingBranch 1.6s infinite;
    }

    @keyframes glowingBranch {
        0% {
            opacity: 0.2;
        }

        50% {
            opacity: 1;
        }

        100% {
            opacity: 0.2;
        }
    }

    /* Teks submenu */
    .nav-treeview .nav-link p {
        color: #dcdcdc;
        font-size: 14px;
    }

    .nav-treeview .nav-link:hover p {
        color: #ffffff;
        text-shadow: 0 0 4px rgba(255, 255, 255, 0.5);
    }

    /* Aktif */
    .nav-treeview .nav-link.active {
        background-color: rgba(255, 255, 255, 0.15);
        border-radius: 8px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* Pencarian menu */
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

        searchInput.addEventListener('keyup', () => filterMenu(searchInput.value));
        searchButton.addEventListener('click', () => filterMenu(searchInput.value));

        /* Rotasi panah otomatis */
        const allMenus = document.querySelectorAll('.nav-item.has-treeview');
        allMenus.forEach(menu => {
            const link = menu.querySelector('a');
            const arrow = link.querySelector('.rotate-icon');
            if (!arrow) return;

            if (menu.classList.contains('menu-open')) {
                arrow.style.transform = 'rotate(-90deg)';
            }

            link.addEventListener('click', function() {
                setTimeout(() => {
                    arrow.style.transform =
                        menu.classList.contains('menu-open') ? 'rotate(-90deg)' : 'rotate(0deg)';
                }, 150);
            });
        });

    });
</script>