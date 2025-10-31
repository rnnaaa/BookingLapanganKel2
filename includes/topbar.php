<!-- Preloader -->

<div class="preloader flex-column justify-content-center align-items-center">
  <img class="animation__shake" src="../public/asseth/tampilan_admin/dist/img/AdminLTELogo.png" alt="Badmintoon Logo" height="60" width="60">
</div>

<!-- Navbar -->

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button">
        <i class="fas fa-bars"></i>
      </a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="dashboard.php" class="nav-link">Dashboard</a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="#" class="nav-link">Kontak</a>
    </li>
  </ul>

  <!-- Right navbar links -->

  <ul class="navbar-nav ml-auto">
    <!-- Search -->
    <li class="nav-item">
      <a class="nav-link" data-widget="navbar-search" href="#" role="button">
        <i class="fas fa-search"></i>
      </a>
      <div class="navbar-search-block">
        <form class="form-inline">
          <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Cari data..." aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-navbar" type="submit">
                <i class="fas fa-search"></i>
              </button>
              <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </li>

<!-- Notifications Dropdown -->
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#">
    <i class="far fa-bell"></i>
    <span class="badge badge-warning navbar-badge">5</span>
  </a>
  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
    <span class="dropdown-item dropdown-header">5 Notifikasi</span>
    <div class="dropdown-divider"></div>
    <a href="booking.php" class="dropdown-item">
      <i class="fas fa-calendar-check mr-2"></i> 2 Booking baru
      <span class="float-right text-muted text-sm">Baru saja</span>
    </a>
    <div class="dropdown-divider"></div>
    <a href="pembayaran.php" class="dropdown-item">
      <i class="fas fa-money-bill-wave mr-2"></i> 1 Pembayaran menunggu
    </a>
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item dropdown-footer">Lihat semua notifikasi</a>
  </div>
</li>

<!-- Fullscreen -->
<li class="nav-item">
  <a class="nav-link" data-widget="fullscreen" href="#" role="button">
    <i class="fas fa-expand-arrows-alt"></i>
  </a>
</li>

<!-- Control Sidebar -->
<li class="nav-item">
  <a class="nav-link" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#" role="button">
    <i class="fas fa-th-large"></i>
  </a>
</li>

  </ul>
</nav>
<!-- /.navbar -->
