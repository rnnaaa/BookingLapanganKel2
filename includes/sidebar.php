<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-primary elevation-4" style="background-color:#1874ad;">
  <!-- Brand Logo -->
  <a href="dashboard.php" class="brand-link text-center">
    <img src="../public/asseth/tampilan_admin/dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
    <span class="brand-text font-weight-bold text-white">Badmintoon</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="../public/asseth/tampilan_admin/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
      </div>
      <div class="info">
        <a href="#" class="d-block text-white">Administrator</a>
      </div>
    </div>

    <!-- Search -->
    <div class="form-inline">
      <div class="input-group" data-widget="sidebar-search">
        <input class="form-control form-control-sidebar" type="search" placeholder="Cari menu..." aria-label="Search">
        <div class="input-group-append">
          <button class="btn btn-sidebar"><i class="fas fa-search fa-fw"></i></button>
        </div>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

        <!-- DASHBOARD -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- LAPANGAN & JADWAL -->
        <li class="nav-header text-light">LAPANGAN & JADWAL</li>
        <li class="nav-item">
          <a href="lapangan.php" class="nav-link <?= $current_page=='lapangan.php'?'active':'' ?>">
            <i class="nav-icon fas fa-futbol"></i>
            <p>Data Lapangan</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="jadwal_waktu.php" class="nav-link <?= $current_page=='jadwal_waktu.php'?'active':'' ?>">
            <i class="nav-icon fas fa-clock"></i>
            <p>Jadwal Waktu</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="jadwal_harian.php" class="nav-link <?= $current_page=='jadwal_harian.php'?'active':'' ?>">
            <i class="nav-icon fas fa-calendar-day"></i>
            <p>Jadwal Harian</p>
          </a>
        </li>

        <!-- BOOKING & PEMBAYARAN -->
        <li class="nav-header text-light">BOOKING & PEMBAYARAN</li>
        <li class="nav-item">
          <a href="booking.php" class="nav-link <?= $current_page=='booking.php'?'active':'' ?>">
            <i class="nav-icon fas fa-calendar-check"></i>
            <p>Data Booking</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="pembayaran.php" class="nav-link <?= $current_page=='pembayaran.php'?'active':'' ?>">
            <i class="nav-icon fas fa-credit-card"></i>
            <p>Pembayaran</p>
          </a>
        </li>

        <!-- MEMBER -->
        <li class="nav-header text-light">MEMBER & JADWAL</li>
        <li class="nav-item">
          <a href="member.php" class="nav-link <?= $current_page=='member.php'?'active':'' ?>">
            <i class="nav-icon fas fa-id-card"></i>
            <p>Data Member</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="member_jadwal.php" class="nav-link <?= $current_page=='member_jadwal.php'?'active':'' ?>">
            <i class="nav-icon fas fa-calendar-week"></i>
            <p>Jadwal Member</p>
          </a>
        </li>

        <!-- KEUANGAN -->
        <li class="nav-header text-light">KEUANGAN</li>
        <li class="nav-item">
          <a href="keuangan.php" class="nav-link <?= $current_page=='keuangan.php'?'active':'' ?>">
            <i class="nav-icon fas fa-coins"></i>
            <p>Rekap Keuangan</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="pengeluaran.php" class="nav-link <?= $current_page=='pengeluaran.php'?'active':'' ?>">
            <i class="nav-icon fas fa-receipt"></i>
            <p>Data Pengeluaran</p>
          </a>
        </li>

        <!-- USERS -->
        <li class="nav-header text-light">PENGGUNA SISTEM</li>
        <li class="nav-item">
          <a href="users.php" class="nav-link <?= $current_page=='users.php'?'active':'' ?>">
            <i class="nav-icon fas fa-users"></i>
            <p>Data Pengguna</p>
          </a>
        </li>

        <!-- LAPORAN -->
        <li class="nav-header text-light">LAPORAN</li>
        <li class="nav-item">
          <a href="laporan_booking.php" class="nav-link <?= $current_page=='laporan_booking.php'?'active':'' ?>">
            <i class="nav-icon fas fa-file-alt"></i>
            <p>Laporan Booking</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="laporan_keuangan.php" class="nav-link <?= $current_page=='laporan_keuangan.php'?'active':'' ?>">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Laporan Keuangan</p>
          </a>
        </li>

        <!-- PENGATURAN -->
        <li class="nav-header text-light">PENGATURAN</li>
        <li class="nav-item">
          <a href="settings.php" class="nav-link <?= $current_page=='settings.php'?'active':'' ?>">
            <i class="nav-icon fas fa-cog"></i>
            <p>Pengaturan Sistem</p>
          </a>
        </li>

        <!-- LOGOUT -->
        <li class="nav-item">
          <a href="logout.php" class="nav-link text-danger">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
