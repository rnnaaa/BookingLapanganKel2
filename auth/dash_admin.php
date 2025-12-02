<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// === STATISTIK RINGKAS ===
$JumlahBooking     = mysqli_num_rows(mysqli_query($conn, "SELECT id_booking FROM booking"));
$JumlahUser        = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='user'"));
$JumlahLapangan    = mysqli_num_rows(mysqli_query($conn, "SELECT id_lapangan FROM lapangan"));
$JumlahPembayaran  = mysqli_num_rows(mysqli_query($conn, "SELECT id_pembayaran FROM pembayaran WHERE status_verifikasi='valid'"));
$JumlahMember      = mysqli_num_rows(mysqli_query($conn, "SELECT id_member FROM member WHERE status='aktif'"));

// === DATA GRAFIK BOOKING PER BULAN ===
$chartBooking = mysqli_query($conn, "
  SELECT 
    MONTH(tanggal) AS bulan_num,
    DATE_FORMAT(tanggal, '%M') AS bulan,
    COUNT(*) AS total
  FROM booking
  WHERE YEAR(tanggal) = YEAR(CURDATE())
  GROUP BY bulan_num, bulan
  ORDER BY bulan_num
");

$bulanLabels = [];
$dataBooking = [];
while ($row = mysqli_fetch_assoc($chartBooking)) {
  $bulanLabels[] = $row['bulan'];
  $dataBooking[] = $row['total'];
}

// === DATA GRAFIK STATUS BOOKING ===
$statusData = mysqli_query($conn, "
  SELECT status, COUNT(*) AS jumlah
  FROM booking
  GROUP BY status
");
$statusLabels = [];
$statusJumlah = [];
while ($s = mysqli_fetch_assoc($statusData)) {
  $statusLabels[] = ucfirst($s['status']);
  $statusJumlah[] = $s['jumlah'];
}

// === DATA GRAFIK KEUANGAN BULANAN ===
$keuanganData = mysqli_query($conn, "
  SELECT 
    MONTH(tanggal) AS bulan_num,
    DATE_FORMAT(tanggal, '%M') AS bulan,
    SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE 0 END) AS pemasukan,
    SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END) AS pengeluaran
  FROM keuangan
  WHERE YEAR(tanggal) = YEAR(CURDATE())
  GROUP BY bulan_num, bulan
  ORDER BY bulan_num
");

$bulanKeuangan = [];
$pemasukanData = [];
$pengeluaranData = [];
while ($r = mysqli_fetch_assoc($keuanganData)) {
  $bulanKeuangan[] = $r['bulan'];
  $pemasukanData[] = (float)$r['pemasukan'];
  $pengeluaranData[] = (float)$r['pengeluaran'];
}
?>

<!-- Content Wrapper -->
<div class="content-wrapper">

  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-tachometer-alt mr-2"></i>Dashboard Admin</h1>
      <small class="text-muted">Selamat datang di panel manajemen <b>Badmintoon</b></small>
    </div>
  </section>

  <!-- Statistik -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">

        <!-- Total Booking -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info shadow-sm">
            <div class="inner">
              <h3><?= $JumlahBooking ?></h3>
              <p>Total Booking</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <a href="booking.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Total User -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success shadow-sm">
            <div class="inner">
              <h3><?= $JumlahUser ?></h3>
              <p>Pengguna Terdaftar</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="users.php" class="small-box-footer">Lihat User <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Lapangan -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning shadow-sm">
            <div class="inner">
              <h3><?= $JumlahLapangan ?></h3>
              <p>Data Lapangan</p>
            </div>
            <div class="icon"><i class="fas fa-futbol"></i></div>
            <a href="lapangan.php" class="small-box-footer">Lihat Lapangan <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Pembayaran Valid -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger shadow-sm">
            <div class="inner">
              <h3><?= $JumlahPembayaran ?></h3>
              <p>Pembayaran Valid</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="pembayaran.php" class="small-box-footer">Lihat Pembayaran <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Member Aktif -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-secondary shadow-sm">
            <div class="inner">
              <h3><?= $JumlahMember ?></h3>
              <p>Member Aktif</p>
            </div>
            <div class="icon"><i class="fas fa-id-card"></i></div>
            <a href="member.php" class="small-box-footer">Lihat Member <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

      </div>

      <!-- Grafik -->
      <div class="row">
        <!-- Booking Bulanan -->
        <div class="col-md-8">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Grafik Booking per Bulan</h3>
            </div>
            <div class="card-body">
              <canvas id="chartBooking" height="100"></canvas>
            </div>
          </div>
        </div>

        <!-- Status Booking -->
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
              <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Status Booking</h3>
            </div>
            <div class="card-body">
              <canvas id="chartStatus" height="220"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Grafik Keuangan -->
      <div class="col-md-12">
        <div class="card shadow-sm mt-4">
          <div class="card-header bg-success text-white">
            <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Grafik Keuangan Bulanan</h3>
          </div>
          <div class="card-body">
            <canvas id="chartKeuangan" height="100"></canvas>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// === Chart Booking per Bulan ===
const ctx1 = document.getElementById('chartBooking').getContext('2d');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: <?= json_encode($bulanLabels) ?>,
    datasets: [{
      label: 'Jumlah Booking',
      data: <?= json_encode($dataBooking) ?>,
      backgroundColor: '#1874ad',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 } },
      x: { grid: { display: false } }
    }
  }
});

// === Chart Status Booking ===
const ctx2 = document.getElementById('chartStatus').getContext('2d');
new Chart(ctx2, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($statusLabels) ?>,
    datasets: [{
      data: <?= json_encode($statusJumlah) ?>,
      backgroundColor: ['#ffc107', '#007bff', '#dc3545', '#28a745', '#6c757d'],
      borderWidth: 2,
      borderColor: '#fff'
    }]
  },
  options: {
    plugins: {
      legend: { position: 'bottom' },
      tooltip: { callbacks: { label: c => c.label + ': ' + c.formattedValue } }
    }
  }
});

// === Chart Keuangan ===
const ctx3 = document.getElementById('chartKeuangan').getContext('2d');
new Chart(ctx3, {
  type: 'bar',
  data: {
    labels: <?= json_encode($bulanKeuangan) ?>,
    datasets: [
      {
        label: 'Pemasukan',
        data: <?= json_encode($pemasukanData) ?>,
        backgroundColor: 'rgba(40,167,69,0.7)',
        borderColor: '#28a745',
        borderWidth: 1,
        borderRadius: 6
      },
      {
        label: 'Pengeluaran',
        data: <?= json_encode($pengeluaranData) ?>,
        backgroundColor: 'rgba(220,53,69,0.7)',
        borderColor: '#dc3545',
        borderWidth: 1,
        borderRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: { mode: 'index', intersect: false }
    },
    scales: {
      y: { 
        beginAtZero: true,
        ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') }
      },
      x: { grid: { display: false } }
    }
  }
});
</script>
