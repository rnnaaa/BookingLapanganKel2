<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-primary elevation-4" style="background-color: #1874ad;">

  <!-- Brand Logo -->
  <a href="dashboard.php" class="brand-link d-flex align-items-center justify-content-center" style="gap: 10px; padding: 15px 0;">
    <img src="../public/asseth/tampilan_admin/dist/img/AdminLTELogo.png" 
         alt="Logo" 
         class="brand-image img-circle elevation-3" 
         style="width: 40px; height: 40px; opacity: .9;">
    <span class="brand-text font-weight-bold text-white" style="font-size: 20px;">Badmintoon</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">

    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="../public/asseth/tampilan_admin/dist/img/user2-160x160.jpg" 
             class="img-circle elevation-2" 
             alt="User Image" 
             style="width: 40px; height: 40px;">
      </div>
      <div class="info">
        <a href="#" class="d-block text-white" style="font-weight: 500;">Administrator</a>
      </div>
    </div>

    <!-- Search (aktif) -->
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

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul id="sidebar-menu" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

        <!-- DASHBOARD -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- LAPANGAN & JADWAL -->
        <li class="nav-header text-light mt-2">LAPANGAN & JADWAL</li>
        <li class="nav-item"><a href="lapangan.php" class="nav-link <?= $current_page=='lapangan.php'?'active':'' ?>"><i class="nav-icon fas fa-futbol"></i><p>Data Lapangan</p></a></li>
        <li class="nav-item"><a href="jadwal_waktu.php" class="nav-link <?= $current_page=='jadwal_waktu.php'?'active':'' ?>"><i class="nav-icon fas fa-clock"></i><p>Jadwal Waktu</p></a></li>
        <li class="nav-item"><a href="jadwal_harian.php" class="nav-link <?= $current_page=='jadwal_harian.php'?'active':'' ?>"><i class="nav-icon fas fa-calendar-day"></i><p>Jadwal Harian</p></a></li>
        <li class="nav-item"><a href="jadwal_view.php" class="nav-link <?= $current_page=='jadwal_view.php'?'active':'' ?>"><i class="fas fa-calendar-alt nav-icon"></i><p>Jadwal View</p></a></li>
        <li class="nav-item"><a href="jadwal_singkronisasi.php" class="nav-link <?= $current_page=='jadwal_singkronisasi.php'?'active':'' ?>"><i class="fas fa-sync-alt nav-icon"></i><p>Singkronisasi</p></a></li>
        <li class="nav-item"><a href="jam_operasional.php" class="nav-link <?= $current_page=='jam_operasional.php'?'active':'' ?>"><i class="nav-icon fas fa-clock"></i><p>Jam Operasional</p></a></li>

        <!-- BOOKING & PEMBAYARAN -->
        <li class="nav-header text-light mt-2">BOOKING & PEMBAYARAN</li>
        <li class="nav-item"><a href="booking.php" class="nav-link <?= $current_page=='booking.php'?'active':'' ?>"><i class="nav-icon fas fa-calendar-check"></i><p>Data Booking</p></a></li>
        <li class="nav-item"><a href="pembayaran.php" class="nav-link <?= $current_page=='pembayaran.php'?'active':'' ?>"><i class="nav-icon fas fa-credit-card"></i><p>Pembayaran</p></a></li>

        <!-- MEMBER -->
        <li class="nav-header text-light mt-2">MEMBER & JADWAL</li>
        <li class="nav-item"><a href="member.php" class="nav-link <?= $current_page=='member.php'?'active':'' ?>"><i class="nav-icon fas fa-id-card"></i><p>Data Member</p></a></li>
        <li class="nav-item"><a href="member_jadwal.php" class="nav-link <?= $current_page=='member_jadwal.php'?'active':'' ?>"><i class="nav-icon fas fa-calendar-week"></i><p>Jadwal Member</p></a></li>

        <!-- KEUANGAN -->
        <li class="nav-header text-light mt-2">KEUANGAN</li>
        <li class="nav-item"><a href="keuangan.php" class="nav-link <?= $current_page=='keuangan.php'?'active':'' ?>"><i class="nav-icon fas fa-coins"></i><p>Rekap Keuangan</p></a></li>
        <li class="nav-item"><a href="pengeluaran.php" class="nav-link <?= $current_page=='pengeluaran.php'?'active':'' ?>"><i class="nav-icon fas fa-receipt"></i><p>Data Pengeluaran</p></a></li>

        <!-- USERS -->
        <li class="nav-header text-light mt-2">PENGGUNA SISTEM</li>
        <li class="nav-item"><a href="users.php" class="nav-link <?= $current_page=='users.php'?'active':'' ?>"><i class="nav-icon fas fa-users"></i><p>Data Pengguna</p></a></li>
        <li class="nav-item"><a href="admin.php" class="nav-link <?= $current_page=='admin.php'?'active':'' ?>"><i class="nav-icon fas fa-users"></i><p>Data Pengguna Admin</p></a></li>

        <!-- LAPORAN -->
        <li class="nav-header text-light mt-2">LAPORAN</li>
        <li class="nav-item"><a href="laporan_booking.php" class="nav-link <?= $current_page=='laporan_booking.php'?'active':'' ?>"><i class="nav-icon fas fa-file-alt"></i><p>Laporan Booking</p></a></li>
        <li class="nav-item"><a href="laporan_keuangan.php" class="nav-link <?= $current_page=='laporan_keuangan.php'?'active':'' ?>"><i class="nav-icon fas fa-chart-line"></i><p>Laporan Keuangan</p></a></li>

        <!-- PENGATURAN -->
        <li class="nav-header text-light mt-2">PENGATURAN</li>
        <li class="nav-item"><a href="settings.php" class="nav-link <?= $current_page=='settings.php'?'active':'' ?>"><i class="nav-icon fas fa-cog"></i><p>Pengaturan Sistem</p></a></li>

        <!-- LOGOUT -->
        <li class="nav-item mt-2">
          <a href="logout.php" class="nav-link text-danger">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>

<!-- === Script untuk aktifkan pencarian menu === -->
<script>
  document.getElementById('btn-sidebar-search').addEventListener('click', function() {
    const query = document.getElementById('sidebar-search').value.toLowerCase();
    const menuItems = document.querySelectorAll('#sidebar-menu li.nav-item');

    menuItems.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(query) ? '' : 'none';
    });
  });

  // Bisa langsung cari sambil mengetik
  document.getElementById('sidebar-search').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const menuItems = document.querySelectorAll('#sidebar-menu li.nav-item');

    menuItems.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(query) ? '' : 'none';
    });
  });
</script>
